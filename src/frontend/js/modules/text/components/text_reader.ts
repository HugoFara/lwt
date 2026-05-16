/**
 * Text Reader - Main Alpine.js component for text reading view.
 *
 * Handles initialization, rendering, and event coordination for the text reading interface.
 * Uses the word store for state and text renderer for display.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import type { WordStoreState } from '@modules/vocabulary/stores/word_store';
import { renderText, updateWordStatusInDOM, type RenderSettings } from '../pages/reading/text_renderer';
import { setupMultiWordSelection } from '../pages/reading/text_multiword_selection';
import { TextsApi } from '@modules/text/api/texts_api';
import { SettingsApi } from '@modules/admin/api/settings_api';
import { scrollTo } from '@shared/utils/hover_intent';
import { openDictionaryPopup, createTheDictUrl } from '@modules/vocabulary/services/dictionary';
import { speechDispatcher } from '@shared/utils/user_interactions';
import { lwt_audio_controller } from '@/media/html5_audio_player';
import { getWordFormStore } from '@modules/vocabulary/stores/word_form_store';
import { getPositionFromId } from '@shared/utils/ajax_utilities';

/**
 * Text reader Alpine.js component interface.
 */
export interface TextReaderData {
  // State
  isLoading: boolean;
  showAll: boolean;
  showTranslations: boolean;
  error: string | null;
  statusMessage: string | null;
  markedPosition: number;

  // Computed properties
  readonly store: WordStoreState;
  readonly textId: number;
  readonly title: string;
  readonly isInitialized: boolean;

  // Lifecycle methods
  init(): Promise<void>;

  // Rendering methods
  renderTextContent(): void;
  getRenderSettings(): RenderSettings;

  // Event handlers
  handleWordClick(event: MouseEvent): void;
  handleKeydown(event: KeyboardEvent): void;

  // Actions
  toggleShowAll(): void;
  toggleTranslations(): void;
  markAllWellKnown(): Promise<void>;
  markAllIgnored(): Promise<void>;
  goBack(): void;
  goNext(): void;

  // Reader layout
  readerWidth: number;
  readerTextSize: number;
  increaseTextSize(): void;
  decreaseTextSize(): void;
  onReaderWidthChange(): void;
  applyReaderLayout(): void;

  // Helpers
  getTextIdFromUrl(): number;
  updateWordDisplay(hex: string, status: number, wordId: number | null): void;
  setupEventListeners(): void;
}

/** Debounce timer for persisting reader settings. */
let saveWidthTimer: ReturnType<typeof setTimeout> | null = null;
let saveTextSizeTimer: ReturnType<typeof setTimeout> | null = null;

/**
 * Debounced save of a setting (300ms).
 */
function debouncedSave(
  timer: ReturnType<typeof setTimeout> | null,
  key: string,
  value: string
): ReturnType<typeof setTimeout> {
  if (timer) clearTimeout(timer);
  return setTimeout(() => {
    SettingsApi.save(key, value);
  }, 300);
}

/**
 * Create the text reader Alpine.js component data.
 */
export function textReaderData(): TextReaderData {
  return {
    isLoading: true,
    showAll: false,
    showTranslations: true,
    error: null,
    statusMessage: null,
    markedPosition: -1,
    readerWidth: 100,
    readerTextSize: 0,

    get store(): WordStoreState {
      return Alpine.store('words') as WordStoreState;
    },

    get textId(): number {
      return this.store.textId;
    },

    get title(): string {
      return this.store.title;
    },

    get isInitialized(): boolean {
      return this.store.isInitialized;
    },

    async init(): Promise<void> {
      this.isLoading = true;
      this.error = null;

      try {
        const textId = this.getTextIdFromUrl();
        if (!textId || textId === 0) {
          // No text ID - we're not on a text reading page
          this.isLoading = false;
          return;
        }

        await this.store.loadText(textId);

        if (!this.store.isInitialized) {
          this.error = 'Failed to load text';
          this.isLoading = false;
          return;
        }

        // Initialize reader layout from store
        this.readerWidth = this.store.readerWidth;
        this.readerTextSize = this.store.textSize;

        // Render the text content
        this.renderTextContent();
        this.applyReaderLayout();

        // Set up event listeners
        this.setupEventListeners();

        this.isLoading = false;
      } catch (err) {
        console.error('Error initializing text reader:', err);
        this.error = 'An error occurred while loading the text';
        this.isLoading = false;
      }
    },

    renderTextContent(): void {
      const container = document.getElementById('thetext');
      if (!container) {
        console.error('Text container not found');
        return;
      }

      const settings = this.getRenderSettings();
      const html = renderText(this.store.words, settings);
      container.innerHTML = html;

      // Apply RTL styling if needed
      if (this.store.rightToLeft) {
        container.style.direction = 'rtl';
      }

      // Apply text size
      if (this.store.textSize !== 100) {
        container.style.fontSize = `${this.store.textSize}%`;
      }
    },

    getRenderSettings(): RenderSettings {
      return {
        showAll: this.showAll,
        showTranslations: this.showTranslations,
        rightToLeft: this.store.rightToLeft,
        textSize: this.store.textSize,
        // Annotation settings required for Markdown-rendered translations
        showLearning: this.store.showLearning,
        displayStatTrans: this.store.displayStatTrans,
        modeTrans: this.store.modeTrans,
        annTextSize: this.store.annTextSize
      };
    },

    setupEventListeners(): void {
      const container = document.getElementById('thetext');
      if (!container) return;

      // Use event delegation for word clicks
      container.addEventListener('click', (e) => this.handleWordClick(e));

      // Keyboard navigation
      document.addEventListener('keydown', (e) => this.handleKeydown(e));

      // Multi-word selection via native text selection
      // When user selects multiple words, the multi-word modal opens
      setupMultiWordSelection(container);
    },

    handleWordClick(event: MouseEvent): void {
      const target = event.target as HTMLElement;

      // Find the word span (might be the target or a parent)
      const wordEl = target.closest('.word, .mword') as HTMLElement | null;
      if (!wordEl) return;

      event.preventDefault();
      event.stopPropagation();

      // Get word data from element (use getAttribute for underscore attributes)
      const hex = wordEl.getAttribute('data_hex') || wordEl.className.match(/TERM([0-9A-F]+)/)?.[1] || '';
      const position = parseInt(wordEl.getAttribute('data_order') || wordEl.getAttribute('data_pos') || '0', 10);

      if (!hex) return;

      // Select the word (opens popover near the clicked element)
      this.store.selectWord(hex, position, wordEl);

    },

    handleKeydown(e: KeyboardEvent): void {
      // Skip if the user is typing in an interactive element
      const target = e.target as HTMLElement;
      if (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;
      // Let the edit modal handle its own keys
      if (this.store.isEditModalOpen) return;

      const keyCode = e.keyCode || e.which;

      const clearMarked = (): void => {
        document.querySelectorAll('.kwordmarked, .uwordmarked').forEach(
          el => el.classList.remove('kwordmarked', 'uwordmarked')
        );
      };

      const knownWords = (): HTMLElement[] => Array.from(
        document.querySelectorAll<HTMLElement>(
          'span.word:not(.hide):not(.status0), span.mword:not(.hide)'
        )
      );

      // ESC: reset all marks and close popover
      if (keyCode === 27) {
        clearMarked();
        this.markedPosition = -1;
        this.store.closePopover();
        e.preventDefault();
        return;
      }

      // RETURN: jump to next unknown word
      if (keyCode === 13) {
        document.querySelectorAll('.uwordmarked').forEach(el => el.classList.remove('uwordmarked'));
        const unknown = document.querySelector<HTMLElement>('span.status0.word:not(.hide)');
        if (unknown) {
          scrollTo(unknown, { offset: -150 });
          unknown.classList.add('uwordmarked');
          unknown.click();
          this.store.closePopover();
        }
        e.preventDefault();
        return;
      }

      // Navigation: HOME / END / LEFT / RIGHT / SPACE
      const words = knownWords();
      if (words.length === 0) return;

      if (keyCode === 36) { // HOME: first known word
        clearMarked();
        this.markedPosition = 0;
        words[0].classList.add('kwordmarked');
        scrollTo(words[0], { offset: -150 });
        words[0].click();
        e.preventDefault();
        return;
      }

      if (keyCode === 35) { // END: last known word
        clearMarked();
        this.markedPosition = words.length - 1;
        words[this.markedPosition].classList.add('kwordmarked');
        scrollTo(words[this.markedPosition], { offset: -150 });
        words[this.markedPosition].click();
        e.preventDefault();
        return;
      }

      if (keyCode === 37) { // LEFT: previous known word
        const marked = document.querySelector<HTMLElement>('.kwordmarked');
        const currid = marked ? getPositionFromId(marked.id) : Number.MAX_SAFE_INTEGER;
        clearMarked();
        let newPos = words.length - 1;
        for (let i = words.length - 1; i >= 0; i--) {
          if (getPositionFromId(words[i].id) < currid) { newPos = i; break; }
        }
        this.markedPosition = newPos;
        words[newPos].classList.add('kwordmarked');
        scrollTo(words[newPos], { offset: -150 });
        words[newPos].click();
        e.preventDefault();
        return;
      }

      if (keyCode === 39 || keyCode === 32) { // RIGHT / SPACE: next known word
        const marked = document.querySelector<HTMLElement>('.kwordmarked');
        const currid = marked ? getPositionFromId(marked.id) : -1;
        clearMarked();
        let newPos = 0;
        for (let i = 0; i < words.length; i++) {
          if (getPositionFromId(words[i].id) > currid) { newPos = i; break; }
        }
        this.markedPosition = newPos;
        words[newPos].classList.add('kwordmarked');
        scrollTo(words[newPos], { offset: -150 });
        words[newPos].click();
        e.preventDefault();
        return;
      }

      // All remaining shortcuts operate on the currently marked or hovered word
      const markedEl = document.querySelector<HTMLElement>('.kwordmarked, .uwordmarked');
      const curr = markedEl ?? document.querySelector<HTMLElement>('.hword:hover');
      if (!curr) return;

      const hex = curr.getAttribute('data_hex') ?? curr.className.match(/TERM([0-9A-Fa-f]+)/)?.[1] ?? '';
      const position = parseInt(curr.getAttribute('data_order') ?? curr.getAttribute('data_pos') ?? '0', 10);
      const widAttr = curr.getAttribute('data_wid');
      const wordId = widAttr ? parseInt(widAttr, 10) : null;
      const status = parseInt(curr.getAttribute('data_status') ?? '0', 10);
      const text = curr.classList.contains('mwsty')
        ? (curr.getAttribute('data_text') ?? curr.textContent ?? '')
        : (curr.textContent ?? '');

      // 1-5: set status (or open edit form for new words)
      for (let i = 1; i <= 5; i++) {
        if (keyCode === 48 + i || keyCode === 96 + i) {
          if (status === 0) {
            this._openEditForm(position);
          } else {
            void this.store.setStatus(hex, i);
          }
          e.preventDefault();
          return;
        }
      }

      // I: ignored (98)
      if (keyCode === 73) {
        if (status === 0) {
          void this.store.createQuickWord(hex, position, 98);
        } else {
          void this.store.setStatus(hex, 98);
        }
        e.preventDefault();
        return;
      }

      // W: well-known (99)
      if (keyCode === 87) {
        if (status === 0) {
          void this.store.createQuickWord(hex, position, 99);
        } else {
          void this.store.setStatus(hex, 99);
        }
        e.preventDefault();
        return;
      }

      // P: pronounce with TTS
      if (keyCode === 80) {
        speechDispatcher(text, this.store.langId);
        e.preventDefault();
        return;
      }

      // T: open translator popup with current word
      if (keyCode === 84) {
        const link = this.store.dictLinks.translator?.replace(/^\*/, '');
        if (link) openDictionaryPopup(createTheDictUrl(link, text));
        e.preventDefault();
        return;
      }

      // A: seek audio to current word's position
      if (keyCode === 65) {
        const pos = parseInt(curr.getAttribute('data_pos') ?? '0', 10);
        const totalEl = document.getElementById('totalcharcount');
        const total = parseInt(totalEl?.textContent ?? '0', 10);
        if (total > 0) {
          lwt_audio_controller.newPosition(Math.max(0, 100 * (pos - 5) / total));
        }
        e.preventDefault();
        return;
      }

      // G: open translator then fall through to open edit form
      if (keyCode === 71) {
        const link = this.store.dictLinks.translator?.replace(/^\*/, '');
        if (link) setTimeout(() => openDictionaryPopup(createTheDictUrl(link, text)), 10);
      }

      // E / G: open edit form for current word
      if (keyCode === 69 || keyCode === 71) {
        this._openEditForm(position, wordId ?? undefined);
        e.preventDefault();
      }
    },

    _openEditForm(position: number, wordId?: number): void {
      try {
        const formStore = getWordFormStore();
        void formStore.loadForEdit(this.store.textId, position, wordId);
        this.store.openEditModal();
      } catch {
        // word_form_store not available on this page
      }
    },

    toggleShowAll(): void {
      this.showAll = !this.showAll;
      this.store.showAll = this.showAll;
      this.renderTextContent();
    },

    toggleTranslations(): void {
      this.showTranslations = !this.showTranslations;
      this.store.showTranslations = this.showTranslations;
      // Translations are typically shown via CSS, so we just toggle a class
      const container = document.getElementById('thetext');
      if (container) {
        container.classList.toggle('hide-translations', !this.showTranslations);
      }
    },

    increaseTextSize(): void {
      const next = Math.min(this.readerTextSize + 10, 300);
      this.readerTextSize = next;
      this.applyReaderLayout();
      saveTextSizeTimer = debouncedSave(
        saveTextSizeTimer, 'set-reader-text-size', String(next)
      );
    },

    decreaseTextSize(): void {
      const next = Math.max(this.readerTextSize - 10, 50);
      this.readerTextSize = next;
      this.applyReaderLayout();
      saveTextSizeTimer = debouncedSave(
        saveTextSizeTimer, 'set-reader-text-size', String(next)
      );
    },

    onReaderWidthChange(): void {
      this.applyReaderLayout();
      saveWidthTimer = debouncedSave(
        saveWidthTimer, 'set-reader-width', String(this.readerWidth)
      );
    },

    applyReaderLayout(): void {
      const content = document.querySelector(
        '.reading-content'
      ) as HTMLElement | null;
      if (content) {
        content.style.maxWidth = this.readerWidth < 100
          ? this.readerWidth + '%' : '';
      }
      const textEl = document.getElementById('thetext');
      if (textEl) {
        textEl.style.fontSize = this.readerTextSize + '%';
      }
    },

    async markAllWellKnown(): Promise<void> {
      if (!confirm('Mark all unknown words as Well Known?')) return;

      this.statusMessage = null;

      try {
        const response = await TextsApi.markAllWellKnown(this.store.textId);

        if (response.error) {
          console.error('Failed to mark all well-known:', response.error);
          this.statusMessage = 'Failed to mark words as well-known.';
          return;
        }

        // Update display for each affected word
        const words = response.data?.words ?? [];
        for (const word of words) {
          this.updateWordDisplay(word.hex, 99, word.wid);
          this.store.updateWordInStore(word.hex, {
            wordId: word.wid,
            status: 99
          });
        }

        this.statusMessage = `Marked ${words.length} word${words.length !== 1 ? 's' : ''} as Well Known.`;
      } catch (err) {
        console.error('Error marking all well-known:', err);
        this.statusMessage = 'Error marking words as well-known.';
      }
    },

    async markAllIgnored(): Promise<void> {
      if (!confirm('Mark all unknown words as Ignored?')) return;

      this.statusMessage = null;

      try {
        const response = await TextsApi.markAllIgnored(this.store.textId);

        if (response.error) {
          console.error('Failed to mark all ignored:', response.error);
          this.statusMessage = 'Failed to mark words as ignored.';
          return;
        }

        // Update display for each affected word
        const words = response.data?.words ?? [];
        for (const word of words) {
          this.updateWordDisplay(word.hex, 98, word.wid);
          this.store.updateWordInStore(word.hex, {
            wordId: word.wid,
            status: 98
          });
        }

        this.statusMessage = `Marked ${words.length} word${words.length !== 1 ? 's' : ''} as Ignored.`;
      } catch (err) {
        console.error('Error marking all ignored:', err);
        this.statusMessage = 'Error marking words as ignored.';
      }
    },

    goBack(): void {
      // Navigate to previous text or text list
      window.history.back();
    },

    goNext(): void {
      // NOTE: Next text navigation requires store.nextTextId from API
      // TextNavigationService provides server-rendered links in read_header.php
    },

    getTextIdFromUrl(): number {
      // Try to get from URL path: /text/123/read (RESTful) or /text/read/123 (legacy)
      const restfulMatch = window.location.pathname.match(/\/text\/(\d+)\/read/);
      if (restfulMatch) {
        return parseInt(restfulMatch[1], 10);
      }
      const legacyMatch = window.location.pathname.match(/\/text\/read\/(\d+)/);
      if (legacyMatch) {
        return parseInt(legacyMatch[1], 10);
      }

      // Try to get from query string: ?text=123 or ?tid=123 or ?start=123
      const params = new URLSearchParams(window.location.search);
      const textParam = params.get('text') || params.get('tid') || params.get('start');
      if (textParam) {
        return parseInt(textParam, 10);
      }

      return 0;
    },

    updateWordDisplay(hex: string, status: number, wordId: number | null): void {
      updateWordStatusInDOM(hex, status, wordId);
    }
  };
}

/**
 * Initialize the text reader Alpine.js component.
 */
export function initTextReaderAlpine(): void {
  Alpine.data('textReader', textReaderData);
}

// Register the component immediately
initTextReaderAlpine();

// Expose for global access
declare global {
  interface Window {
    textReaderData: typeof textReaderData;
  }
}

window.textReaderData = textReaderData;

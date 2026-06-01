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

/** Keyboard focus position saved before the edit modal opens, restored on close. */
let _kbSavedPosition = -1;

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

      container.addEventListener('click', (e) => this.handleWordClick(e));
      document.addEventListener('keydown', (e) => this.handleKeydown(e));
      setupMultiWordSelection(container);

      // Restore keyboard focus mark after the edit modal closes
      Alpine.effect(() => {
        const isOpen = this.store.isEditModalOpen;
        if (!isOpen && _kbSavedPosition >= 0) {
          const savedPos = _kbSavedPosition;
          _kbSavedPosition = -1;
          requestAnimationFrame(() => {
            const el = Array.from(
              document.querySelectorAll<HTMLElement>('span.word:not(.hide), span.mword:not(.hide)')
            ).find(w => parseInt(w.getAttribute('data_order') ?? '-1', 10) === savedPos);
            if (el) {
              document.querySelectorAll('.kwordmarked').forEach(e => e.classList.remove('kwordmarked'));
              el.classList.add('kwordmarked');
            }
          });
        }
      });
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

      // Keep keyboard mark in sync with clicks
      document.querySelectorAll('.kwordmarked').forEach(el => el.classList.remove('kwordmarked'));
      wordEl.classList.add('kwordmarked');

      // Select the word (opens popover near the clicked element)
      this.store.selectWord(hex, position, wordEl);

    },

    handleKeydown(e: KeyboardEvent): void {
      const target = e.target as HTMLElement;
      if (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;
      if (this.store.isEditModalOpen) return;

      const keyCode = e.keyCode || e.which;

      // All visible word elements (every status, including unknown)
      const allWords = (): HTMLElement[] => Array.from(
        document.querySelectorAll<HTMLElement>('span.word:not(.hide), span.mword:not(.hide)')
      );

      // The kwordmarked element is the single source of truth for keyboard focus
      const currentEl = (): HTMLElement | null =>
        document.querySelector<HTMLElement>('.kwordmarked');

      const currentPos = (): number => {
        const el = currentEl();
        return el ? parseInt(el.getAttribute('data_order') ?? '-1', 10) : -1;
      };

      // Move keyboard focus to a word. If the popover is already open, reposition it too.
      const focusWord = (el: HTMLElement): void => {
        document.querySelectorAll('.kwordmarked').forEach(e => e.classList.remove('kwordmarked'));
        el.classList.add('kwordmarked');
        if (this.store.isPopoverOpen) {
          const hex = el.getAttribute('data_hex') ?? el.className.match(/TERM([0-9A-Fa-f]+)/)?.[1] ?? '';
          const pos = parseInt(el.getAttribute('data_order') ?? el.getAttribute('data_pos') ?? '0', 10);
          this.store.selectWord(hex, pos, el);
        }
      };

      // ESC: first press closes popover (mark stays), second press clears mark
      if (keyCode === 27) {
        if (this.store.isPopoverOpen) {
          this.store.isPopoverOpen = false;
        } else {
          document.querySelectorAll('.kwordmarked').forEach(el => el.classList.remove('kwordmarked'));
          this.store.closePopover();
        }
        e.preventDefault();
        return;
      }

      // RETURN: next unknown word after current position, wrapping to start
      if (keyCode === 13) {
        const pos = currentPos();
        const words = allWords();
        const unknown = words.find(
          el => el.getAttribute('data_status') === '0' &&
                parseInt(el.getAttribute('data_order') ?? '0', 10) > pos
        ) ?? words.find(el => el.getAttribute('data_status') === '0');
        if (unknown) {
          focusWord(unknown);
          scrollTo(unknown, { offset: -150 });
        }
        e.preventDefault();
        return;
      }

      // SPACE: toggle popover for the focused word (focus is preserved either way)
      if (keyCode === 32) {
        if (this.store.isPopoverOpen) {
          this.store.isPopoverOpen = false;
        } else {
          const curr = currentEl();
          if (curr) {
            const hex = curr.getAttribute('data_hex') ?? curr.className.match(/TERM([0-9A-Fa-f]+)/)?.[1] ?? '';
            const pos = parseInt(curr.getAttribute('data_order') ?? '0', 10);
            this.store.selectWord(hex, pos, curr);
          }
        }
        e.preventDefault();
        return;
      }

      // HOME / END / LEFT / RIGHT: move focus
      const words = allWords();
      if (words.length === 0) return;

      if (keyCode === 36) { // HOME
        focusWord(words[0]);
        scrollTo(words[0], { offset: -150 });
        e.preventDefault();
        return;
      }

      if (keyCode === 35) { // END
        focusWord(words[words.length - 1]);
        scrollTo(words[words.length - 1], { offset: -150 });
        e.preventDefault();
        return;
      }

      if (keyCode === 37) { // LEFT: previous word (or last if nothing focused)
        const pos = currentPos();
        let prev: HTMLElement | null = null;
        if (pos < 0) {
          prev = words[words.length - 1];
        } else {
          for (let i = words.length - 1; i >= 0; i--) {
            if (parseInt(words[i].getAttribute('data_order') ?? '0', 10) < pos) {
              prev = words[i];
              break;
            }
          }
        }
        if (prev) { focusWord(prev); scrollTo(prev, { offset: -150 }); }
        e.preventDefault();
        return;
      }

      if (keyCode === 39) { // RIGHT: next word (or first if nothing focused)
        const pos = currentPos();
        const next = words.find(
          el => parseInt(el.getAttribute('data_order') ?? '0', 10) > pos
        ) ?? (pos < 0 ? words[0] : null);
        if (next) { focusWord(next); scrollTo(next, { offset: -150 }); }
        e.preventDefault();
        return;
      }

      // All remaining shortcuts act on the focused word
      const curr = currentEl();
      if (!curr) return;

      const hex = curr.getAttribute('data_hex') ?? curr.className.match(/TERM([0-9A-Fa-f]+)/)?.[1] ?? '';
      const position = parseInt(curr.getAttribute('data_order') ?? curr.getAttribute('data_pos') ?? '0', 10);
      const widAttr = curr.getAttribute('data_wid');
      const wordId = widAttr ? parseInt(widAttr, 10) : null;
      const status = parseInt(curr.getAttribute('data_status') ?? '0', 10);
      // Use store word text to avoid picking up annotation child-element text
      const wordData = this.store.wordsByHex.get(hex)?.[0];
      const text = wordData?.text ?? curr.getAttribute('data_text') ?? curr.textContent?.trim() ?? '';
      const translation = wordData?.translation ?? '';

      // 1-5: set status
      for (let i = 1; i <= 5; i++) {
        if (keyCode === 48 + i || keyCode === 96 + i) {
          if (status === 0) this._openEditForm(position);
          else void this.store.setStatus(hex, i);
          e.preventDefault();
          return;
        }
      }

      if (keyCode === 73) { // I: ignored (98)
        if (status === 0) void this.store.createQuickWord(hex, position, 98);
        else void this.store.setStatus(hex, 98);
        e.preventDefault();
        return;
      }

      if (keyCode === 87) { // W: well-known (99)
        if (status === 0) void this.store.createQuickWord(hex, position, 99);
        else void this.store.setStatus(hex, 99);
        e.preventDefault();
        return;
      }

      if (keyCode === 80) { // p: word only  P (shift): word + translation
        const toSpeak = e.shiftKey && translation
          ? `${text}. ${translation}`
          : text;
        speechDispatcher(toSpeak, this.store.langId);
        e.preventDefault();
        return;
      }

      if (keyCode === 84) { // T: open translator
        const link = this.store.dictLinks.translator?.replace(/^\*/, '');
        if (link) openDictionaryPopup(createTheDictUrl(link, text));
        e.preventDefault();
        return;
      }

      if (keyCode === 65) { // A: seek audio to word position
        const pos = parseInt(curr.getAttribute('data_pos') ?? '0', 10);
        const totalEl = document.getElementById('totalcharcount');
        const total = parseInt(totalEl?.textContent ?? '0', 10);
        if (total > 0) lwt_audio_controller.newPosition(Math.max(0, 100 * (pos - 5) / total));
        e.preventDefault();
        return;
      }

      if (keyCode === 71) { // G: open translator and edit form
        const link = this.store.dictLinks.translator?.replace(/^\*/, '');
        if (link) openDictionaryPopup(createTheDictUrl(link, text));
        this._openEditForm(position, wordId ?? undefined);
        e.preventDefault();
        return;
      }

      if (keyCode === 69) { // E: edit term
        this._openEditForm(position, wordId ?? undefined);
        e.preventDefault();
        return;
      }
    },

    _openEditForm(position: number, wordId?: number): void {
      try {
        // Save keyboard focus position so it can be restored when the modal closes
        const curr = document.querySelector<HTMLElement>('.kwordmarked');
        _kbSavedPosition = curr
          ? parseInt(curr.getAttribute('data_order') ?? '-1', 10)
          : -1;

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

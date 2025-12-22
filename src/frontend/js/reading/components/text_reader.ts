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
import type { WordStoreState } from '../stores/word_store';
import { renderText, updateWordStatusInDOM, type RenderSettings } from '../text_renderer';
import { setupMultiWordSelection } from '../text_multiword_selection';
// speechDispatcher available when TTS is implemented

/**
 * Text reader Alpine.js component interface.
 */
export interface TextReaderData {
  // State
  isLoading: boolean;
  showAll: boolean;
  showTranslations: boolean;
  error: string | null;

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

  // Helpers
  getTextIdFromUrl(): number;
  updateWordDisplay(hex: string, status: number, wordId: number | null): void;
  setupEventListeners(): void;
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

        // Render the text content
        this.renderTextContent();

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
        textSize: this.store.textSize
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

      // Select the word (opens modal)
      this.store.selectWord(hex, position);

      // Speak the word if TTS is enabled
      // TODO: Check TTS settings
      // speechDispatcher(wordEl.textContent || '', this.store.langId);
    },

    handleKeydown(): void {
      // Only handle if modal is not open
      if (this.store.isModalOpen) return;

      // TODO: Implement keyboard navigation
      // Arrow keys for word navigation
      // Number keys for quick status change
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

    async markAllWellKnown(): Promise<void> {
      if (!confirm('Mark all unknown words as Well Known?')) return;

      this.isLoading = true;

      try {
        const { TextsApi } = await import('../../api/texts');
        const response = await TextsApi.markAllWellKnown(this.store.textId);

        if (response.error) {
          console.error('Failed to mark all well-known:', response.error);
          this.isLoading = false;
          return;
        }

        // Update display for each affected word
        if (response.data?.words) {
          for (const word of response.data.words) {
            this.updateWordDisplay(word.hex, 99, word.wid);
            this.store.updateWordInStore(word.hex, {
              wordId: word.wid,
              status: 99
            });
          }
        }

        this.isLoading = false;
      } catch (err) {
        console.error('Error marking all well-known:', err);
        this.isLoading = false;
      }
    },

    async markAllIgnored(): Promise<void> {
      if (!confirm('Mark all unknown words as Ignored?')) return;

      this.isLoading = true;

      try {
        const { TextsApi } = await import('../../api/texts');
        const response = await TextsApi.markAllIgnored(this.store.textId);

        if (response.error) {
          console.error('Failed to mark all ignored:', response.error);
          this.isLoading = false;
          return;
        }

        // Update display for each affected word
        if (response.data?.words) {
          for (const word of response.data.words) {
            this.updateWordDisplay(word.hex, 98, word.wid);
            this.store.updateWordInStore(word.hex, {
              wordId: word.wid,
              status: 98
            });
          }
        }

        this.isLoading = false;
      } catch (err) {
        console.error('Error marking all ignored:', err);
        this.isLoading = false;
      }
    },

    goBack(): void {
      // Navigate to previous text or text list
      window.history.back();
    },

    goNext(): void {
      // TODO: Navigate to next text
    },

    getTextIdFromUrl(): number {
      // Try to get from URL path: /text/read/123
      const pathMatch = window.location.pathname.match(/\/text\/read\/(\d+)/);
      if (pathMatch) {
        return parseInt(pathMatch[1], 10);
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

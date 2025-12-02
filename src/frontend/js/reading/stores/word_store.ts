/**
 * Word Store - Alpine.js store for text reading word state management.
 *
 * Provides centralized state management for all words in the text reading view.
 * Uses Map for O(1) lookups and supports reactive updates across all word instances.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { TermsApi } from '../../api/terms';
import { TextsApi, type TextWord, type TextReadingConfig, type DictLinks } from '../../api/texts';

/**
 * Word data stored in the word store.
 */
export interface WordData {
  position: number;
  sentenceId: number;
  text: string;
  textLc: string;
  hex: string;
  isNotWord: boolean;
  wordCount: number;
  hidden: boolean;
  wordId: number | null;
  status: number;
  translation: string;
  romanization: string;
  tags?: string;
}

/**
 * Word store state interface.
 */
export interface WordStoreState {
  // Word data - keyed by hex for O(1) lookup
  // Multiple words can share the same hex (same word appearing multiple times)
  wordsByHex: Map<string, WordData[]>;

  // All words in order (for rendering)
  words: WordData[];

  // Configuration
  textId: number;
  langId: number;
  title: string;
  audioUri: string | null;
  sourceUri: string | null;
  audioPosition: number;
  rightToLeft: boolean;
  textSize: number;
  dictLinks: DictLinks;

  // UI state
  selectedHex: string | null;
  selectedPosition: number | null;
  isModalOpen: boolean;
  isLoading: boolean;
  isInitialized: boolean;

  // Display settings
  showAll: boolean;
  showTranslations: boolean;

  // Methods
  init(textId: number): Promise<void>;
  initFromData(words: TextWord[], config: TextReadingConfig): void;
  selectWord(hex: string, position: number): void;
  closeModal(): void;
  getSelectedWord(): WordData | null;
  getWordsByHex(hex: string): WordData[];
  setStatus(hex: string, status: number): Promise<boolean>;
  createQuickWord(hex: string, position: number, status: 98 | 99): Promise<boolean>;
  deleteWord(hex: string): Promise<boolean>;
  getDictUrl(which: 'dict1' | 'dict2' | 'translator'): string;
  updateWordInStore(hex: string, updates: Partial<WordData>): void;
}

/**
 * Create the word store data object.
 */
function createWordStore(): WordStoreState {
  return {
    // Word data
    wordsByHex: new Map(),
    words: [],

    // Configuration
    textId: 0,
    langId: 0,
    title: '',
    audioUri: null,
    sourceUri: null,
    audioPosition: 0,
    rightToLeft: false,
    textSize: 100,
    dictLinks: {
      dict1: '',
      dict2: '',
      translator: ''
    },

    // UI state
    selectedHex: null,
    selectedPosition: null,
    isModalOpen: false,
    isLoading: false,
    isInitialized: false,

    // Display settings
    showAll: false,
    showTranslations: true,

    /**
     * Initialize the store by fetching words from API.
     */
    async init(textId: number): Promise<void> {
      this.isLoading = true;

      try {
        const response = await TextsApi.getWords(textId);

        if (response.error || !response.data) {
          console.error('Failed to load text words:', response.error);
          this.isLoading = false;
          return;
        }

        this.initFromData(response.data.words, response.data.config);
      } catch (error) {
        console.error('Error loading text words:', error);
      }

      this.isLoading = false;
    },

    /**
     * Initialize from pre-loaded data.
     */
    initFromData(words: TextWord[], config: TextReadingConfig): void {
      // Set configuration
      this.textId = config.textId;
      this.langId = config.langId;
      this.title = config.title;
      this.audioUri = config.audioUri;
      this.sourceUri = config.sourceUri;
      this.audioPosition = config.audioPosition;
      this.rightToLeft = config.rightToLeft;
      this.textSize = config.textSize;
      this.dictLinks = config.dictLinks;

      // Build word data and index
      this.words = [];
      this.wordsByHex.clear();

      for (const word of words) {
        const wordData: WordData = {
          position: word.position,
          sentenceId: word.sentenceId,
          text: word.text,
          textLc: word.textLc,
          hex: word.hex,
          isNotWord: word.isNotWord,
          wordCount: word.wordCount,
          hidden: word.hidden,
          wordId: word.wordId ?? null,
          status: word.status ?? 0,
          translation: word.translation ?? '',
          romanization: word.romanization ?? '',
          tags: word.tags
        };

        this.words.push(wordData);

        // Index by hex for quick lookup
        if (!word.isNotWord) {
          const existing = this.wordsByHex.get(word.hex) || [];
          existing.push(wordData);
          this.wordsByHex.set(word.hex, existing);
        }
      }

      this.isInitialized = true;
    },

    /**
     * Select a word and open the modal.
     */
    selectWord(hex: string, position: number): void {
      this.selectedHex = hex;
      this.selectedPosition = position;
      this.isModalOpen = true;
    },

    /**
     * Close the modal.
     */
    closeModal(): void {
      this.isModalOpen = false;
      this.selectedHex = null;
      this.selectedPosition = null;
    },

    /**
     * Get the currently selected word data.
     */
    getSelectedWord(): WordData | null {
      if (!this.selectedHex || this.selectedPosition === null) return null;

      const wordsWithHex = this.wordsByHex.get(this.selectedHex);
      if (!wordsWithHex) return null;

      // Find the specific word by position
      return wordsWithHex.find(w => w.position === this.selectedPosition) || wordsWithHex[0];
    },

    /**
     * Get all words with a given hex.
     */
    getWordsByHex(hex: string): WordData[] {
      return this.wordsByHex.get(hex) || [];
    },

    /**
     * Set status for all words with the given hex.
     */
    async setStatus(hex: string, status: number): Promise<boolean> {
      const words = this.wordsByHex.get(hex);
      if (!words || words.length === 0) return false;

      // Get the first word's ID (they all share the same word entry)
      const wordId = words[0].wordId;
      if (!wordId) {
        // Word doesn't exist yet - need to create it first
        return false;
      }

      this.isLoading = true;

      try {
        const response = await TermsApi.setStatus(wordId, status);

        if (response.error) {
          console.error('Failed to set status:', response.error);
          this.isLoading = false;
          return false;
        }

        // Update all words with this hex
        this.updateWordInStore(hex, { status });

        this.isLoading = false;
        this.closeModal();
        return true;
      } catch (error) {
        console.error('Error setting status:', error);
        this.isLoading = false;
        return false;
      }
    },

    /**
     * Create a word quickly with well-known (99) or ignored (98) status.
     */
    async createQuickWord(hex: string, position: number, status: 98 | 99): Promise<boolean> {
      this.isLoading = true;

      try {
        const response = await TermsApi.createQuick(this.textId, position, status);

        if (response.error || !response.data?.term_id) {
          console.error('Failed to create word:', response.error);
          this.isLoading = false;
          return false;
        }

        // Update all words with this hex
        this.updateWordInStore(hex, {
          wordId: response.data.term_id,
          status
        });

        this.isLoading = false;
        this.closeModal();
        return true;
      } catch (error) {
        console.error('Error creating word:', error);
        this.isLoading = false;
        return false;
      }
    },

    /**
     * Delete a word (reset to unknown status).
     */
    async deleteWord(hex: string): Promise<boolean> {
      const words = this.wordsByHex.get(hex);
      if (!words || words.length === 0) return false;

      const wordId = words[0].wordId;
      if (!wordId) return false;

      this.isLoading = true;

      try {
        const response = await TermsApi.delete(wordId);

        if (response.error) {
          console.error('Failed to delete word:', response.error);
          this.isLoading = false;
          return false;
        }

        // Reset all words with this hex to unknown
        this.updateWordInStore(hex, {
          wordId: null,
          status: 0,
          translation: '',
          romanization: '',
          tags: undefined
        });

        this.isLoading = false;
        this.closeModal();
        return true;
      } catch (error) {
        console.error('Error deleting word:', error);
        this.isLoading = false;
        return false;
      }
    },

    /**
     * Get dictionary URL for the selected word.
     */
    getDictUrl(which: 'dict1' | 'dict2' | 'translator'): string {
      const word = this.getSelectedWord();
      if (!word) return '#';

      const template = this.dictLinks[which];
      if (!template) return '#';

      return template.replace('###', encodeURIComponent(word.text));
    },

    /**
     * Update word data in the store (triggers reactivity).
     */
    updateWordInStore(hex: string, updates: Partial<WordData>): void {
      const words = this.wordsByHex.get(hex);
      if (!words) return;

      // Update each word with this hex
      for (const word of words) {
        Object.assign(word, updates);
      }

      // Force reactivity by creating new Map entry
      this.wordsByHex.set(hex, [...words]);
    }
  };
}

/**
 * Initialize the word store as an Alpine.js store.
 */
export function initWordStore(): void {
  Alpine.store('words', createWordStore());
}

/**
 * Get the word store instance.
 */
export function getWordStore(): WordStoreState {
  return Alpine.store('words') as WordStoreState;
}

// Register the store immediately
initWordStore();

// Expose for global access
declare global {
  interface Window {
    getWordStore: typeof getWordStore;
  }
}

window.getWordStore = getWordStore;

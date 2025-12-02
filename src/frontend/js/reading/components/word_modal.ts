/**
 * Word Modal - Alpine.js component for Bulma word action modal.
 *
 * Displays word information and action buttons in a centered Bulma modal.
 * Integrates with the word store for state management.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import type { WordStoreState, WordData } from '../stores/word_store';
import { speechDispatcher } from '../../core/user_interactions';

/**
 * Status display information.
 */
interface StatusInfo {
  value: number;
  label: string;
  abbr: string;
  class: string;
}

/**
 * Status definitions.
 */
const STATUSES: StatusInfo[] = [
  { value: 1, label: 'Learning (1)', abbr: '1', class: 'is-danger' },
  { value: 2, label: 'Learning (2)', abbr: '2', class: 'is-warning' },
  { value: 3, label: 'Learning (3)', abbr: '3', class: 'is-info' },
  { value: 4, label: 'Learning (4)', abbr: '4', class: 'is-primary' },
  { value: 5, label: 'Learned', abbr: '5', class: 'is-success' },
  { value: 99, label: 'Well Known', abbr: 'WKn', class: 'is-success is-light' },
  { value: 98, label: 'Ignored', abbr: 'Ign', class: 'is-light' }
];

/**
 * Word modal Alpine.js component interface.
 */
export interface WordModalData {
  // Computed properties
  readonly store: WordStoreState;
  readonly word: WordData | null;
  readonly isOpen: boolean;
  readonly isLoading: boolean;
  readonly isUnknown: boolean;
  readonly modalTitle: string;
  readonly statuses: StatusInfo[];

  // Methods
  close(): void;
  speakWord(): void;
  setStatus(status: number): Promise<void>;
  markWellKnown(): Promise<void>;
  markIgnored(): Promise<void>;
  deleteWord(): Promise<void>;
  getEditUrl(): string;
  getDictUrl(which: 'dict1' | 'dict2' | 'translator'): string;
  isCurrentStatus(status: number): boolean;
  getStatusButtonClass(status: number): string;
}

/**
 * Create the word modal Alpine.js component data.
 */
export function wordModalData(): WordModalData {
  return {
    get store(): WordStoreState {
      return Alpine.store('words') as WordStoreState;
    },

    get word(): WordData | null {
      return this.store.getSelectedWord();
    },

    get isOpen(): boolean {
      return this.store.isModalOpen;
    },

    get isLoading(): boolean {
      return this.store.isLoading;
    },

    get isUnknown(): boolean {
      const word = this.word;
      return !word || word.status === 0;
    },

    get modalTitle(): string {
      if (this.isUnknown) {
        return 'New Word';
      }
      return 'Word';
    },

    get statuses(): StatusInfo[] {
      return STATUSES;
    },

    close(): void {
      this.store.closeModal();
    },

    speakWord(): void {
      const word = this.word;
      if (word && this.store.langId) {
        speechDispatcher(word.text, this.store.langId);
      }
    },

    async setStatus(status: number): Promise<void> {
      const word = this.word;
      if (!word) return;

      await this.store.setStatus(word.hex, status);
    },

    async markWellKnown(): Promise<void> {
      const word = this.word;
      if (!word) return;

      await this.store.createQuickWord(word.hex, word.position, 99);
    },

    async markIgnored(): Promise<void> {
      const word = this.word;
      if (!word) return;

      await this.store.createQuickWord(word.hex, word.position, 98);
    },

    async deleteWord(): Promise<void> {
      const word = this.word;
      if (!word) return;

      if (confirm('Delete this term?')) {
        await this.store.deleteWord(word.hex);
      }
    },

    getEditUrl(): string {
      const word = this.word;
      if (!word) return '#';

      const params = new URLSearchParams({
        tid: String(this.store.textId),
        ord: String(word.position)
      });

      if (word.wordId) {
        params.set('wid', String(word.wordId));
      }

      return `/word/edit?${params.toString()}`;
    },

    getDictUrl(which: 'dict1' | 'dict2' | 'translator'): string {
      return this.store.getDictUrl(which);
    },

    isCurrentStatus(status: number): boolean {
      const word = this.word;
      return word ? word.status === status : false;
    },

    getStatusButtonClass(status: number): string {
      const statusInfo = STATUSES.find(s => s.value === status);
      const baseClass = statusInfo?.class || '';

      if (this.isCurrentStatus(status)) {
        return `button is-small ${baseClass}`;
      }
      return `button is-small is-outlined ${baseClass}`;
    }
  };
}

/**
 * Initialize the word modal Alpine.js component.
 */
export function initWordModalAlpine(): void {
  Alpine.data('wordModal', wordModalData);
}

// Register the component immediately
initWordModalAlpine();

// Expose for global access
declare global {
  interface Window {
    wordModalData: typeof wordModalData;
  }
}

window.wordModalData = wordModalData;

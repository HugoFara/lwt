/**
 * Word Edit Form - Alpine.js component for term editing in modal.
 *
 * Provides a reactive form for creating and editing terms within the word modal.
 * Integrates with the word form store for state management.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import type { WordFormStoreState, SaveResult } from '../stores/word_form_store';
import type { WordStoreState } from '../stores/word_store';
import type { SimilarTermForEdit } from '../../api/terms';

/**
 * Status display information.
 */
interface StatusInfo {
  value: number;
  label: string;
  abbr: string;
}

/**
 * Status definitions matching word_modal.ts.
 */
const STATUSES: StatusInfo[] = [
  { value: 1, label: 'Learning (1)', abbr: '1' },
  { value: 2, label: 'Learning (2)', abbr: '2' },
  { value: 3, label: 'Learning (3)', abbr: '3' },
  { value: 4, label: 'Learning (4)', abbr: '4' },
  { value: 5, label: 'Learned', abbr: '5' },
  { value: 99, label: 'Well Known', abbr: 'WKn' },
  { value: 98, label: 'Ignored', abbr: 'Ign' }
];

/**
 * Word edit form Alpine.js component interface.
 */
export interface WordEditFormData {
  // Computed properties
  readonly formStore: WordFormStoreState;
  readonly wordStore: WordStoreState;
  readonly isLoading: boolean;
  readonly isSubmitting: boolean;
  readonly isDirty: boolean;
  readonly isValid: boolean;
  readonly isNewWord: boolean;
  readonly showRomanization: boolean;
  readonly statuses: StatusInfo[];

  // Tag input state
  tagInput: string;
  showTagSuggestions: boolean;
  filteredTags: string[];

  // Methods
  validateField(field: string): void;
  save(): Promise<void>;
  cancel(): void;
  addTag(tag: string): void;
  removeTag(tag: string): void;
  filterTags(): void;
  selectTagSuggestion(tag: string): void;
  hideTagSuggestions(): void;
  copyFromSimilar(term: SimilarTermForEdit): void;
  getStatusClass(status: number): string;

  // Callbacks (set by parent modal)
  onSaved?: (result: SaveResult) => void;
  onCancelled?: () => void;
}

/**
 * Create the word edit form Alpine.js component data.
 */
export function wordEditFormData(): WordEditFormData {
  return {
    // Tag input state
    tagInput: '',
    showTagSuggestions: false,
    filteredTags: [],

    get formStore(): WordFormStoreState {
      return Alpine.store('wordForm') as WordFormStoreState;
    },

    get wordStore(): WordStoreState {
      return Alpine.store('words') as WordStoreState;
    },

    get isLoading(): boolean {
      return this.formStore.isLoading;
    },

    get isSubmitting(): boolean {
      return this.formStore.isSubmitting;
    },

    get isDirty(): boolean {
      return this.formStore.isDirty;
    },

    get isValid(): boolean {
      return this.formStore.isValid;
    },

    get isNewWord(): boolean {
      return this.formStore.isNewWord;
    },

    get showRomanization(): boolean {
      return this.formStore.showRomanization;
    },

    get statuses(): StatusInfo[] {
      return STATUSES;
    },

    validateField(field: string): void {
      this.formStore.validateField(field as keyof typeof this.formStore.formData);
    },

    async save(): Promise<void> {
      const result = await this.formStore.save();

      if (result.success && result.hex) {
        // Update the word store with new data
        this.wordStore.updateWordInStore(result.hex, {
          wordId: result.wordId ?? null,
          status: this.formStore.formData.status,
          translation: this.formStore.formData.translation,
          romanization: this.formStore.formData.romanization,
          tags: this.formStore.formData.tags.join(', ')
        });

        // Call the onSaved callback if set
        if (this.onSaved) {
          this.onSaved(result);
        }
      }
    },

    cancel(): void {
      if (this.isDirty) {
        if (!confirm('You have unsaved changes. Are you sure you want to cancel?')) {
          return;
        }
      }

      // Reset the form store
      this.formStore.reset();

      // Call the onCancelled callback if set
      if (this.onCancelled) {
        this.onCancelled();
      }
    },

    addTag(tag: string): void {
      tag = tag.trim();
      if (tag && !this.formStore.formData.tags.includes(tag)) {
        this.formStore.formData.tags.push(tag);
      }
      this.tagInput = '';
      this.showTagSuggestions = false;
    },

    removeTag(tag: string): void {
      const index = this.formStore.formData.tags.indexOf(tag);
      if (index > -1) {
        this.formStore.formData.tags.splice(index, 1);
      }
    },

    filterTags(): void {
      const input = this.tagInput.toLowerCase().trim();
      if (!input) {
        this.filteredTags = [];
        this.showTagSuggestions = false;
        return;
      }

      // Filter tags that start with input and are not already selected
      this.filteredTags = this.formStore.allTags
        .filter(tag =>
          tag.toLowerCase().startsWith(input) &&
          !this.formStore.formData.tags.includes(tag)
        )
        .slice(0, 8); // Limit to 8 suggestions

      this.showTagSuggestions = this.filteredTags.length > 0;
    },

    selectTagSuggestion(tag: string): void {
      this.addTag(tag);
    },

    hideTagSuggestions(): void {
      // Delay hiding to allow click on suggestion
      setTimeout(() => {
        this.showTagSuggestions = false;
      }, 200);
    },

    copyFromSimilar(term: SimilarTermForEdit): void {
      if (term.translation) {
        this.formStore.copyTranslationFromSimilar(term.translation);
      }
    },

    getStatusClass(status: number): string {
      switch (status) {
        case 1: return 'is-danger';
        case 2: return 'is-warning';
        case 3: return 'is-info';
        case 4: return 'is-primary';
        case 5:
        case 99: return 'is-success';
        case 98: return 'is-light';
        default: return '';
      }
    },

    // Callbacks - can be set by parent component
    onSaved: undefined,
    onCancelled: undefined
  };
}

/**
 * Initialize the word edit form Alpine.js component.
 */
export function initWordEditFormAlpine(): void {
  Alpine.data('wordEditForm', wordEditFormData);
}

// Register the component immediately
initWordEditFormAlpine();

// Expose for global access
declare global {
  interface Window {
    wordEditFormData: typeof wordEditFormData;
  }
}

window.wordEditFormData = wordEditFormData;

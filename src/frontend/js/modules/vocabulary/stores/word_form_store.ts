/**
 * Word Form Store - Alpine.js store for term edit form state management.
 *
 * Provides centralized state management for the term edit form in the reading interface.
 * Handles form data, validation, dirty state detection, and API interactions.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import {
  TermsApi,
  type SimilarTermForEdit,
  type TermCreateFullRequest,
  type TermUpdateFullRequest
} from '@modules/vocabulary/api/terms_api';

/**
 * Form data structure.
 */
export interface WordFormData {
  text: string;
  textLc: string;
  lemma: string;
  translation: string;
  romanization: string;
  sentence: string;
  notes: string;
  status: number;
  tags: string[];
}

/**
 * Validation errors keyed by field name.
 */
export interface ValidationErrors {
  lemma: string | null;
  translation: string | null;
  romanization: string | null;
  sentence: string | null;
  notes: string | null;
  general: string | null;
}

/**
 * Result of a save operation.
 */
export interface SaveResult {
  success: boolean;
  hex?: string;
  wordId?: number;
  error?: string;
}

/**
 * Word form store state interface.
 */
export interface WordFormStoreState {
  // Form data
  formData: WordFormData;
  originalData: WordFormData | null;

  // Context
  wordId: number | null;
  textId: number;
  position: number;
  hex: string;
  isNewWord: boolean;

  // Language settings
  showRomanization: boolean;
  allTags: string[];
  similarTerms: SimilarTermForEdit[];

  // UI state
  isVisible: boolean;
  isSubmitting: boolean;
  isLoading: boolean;
  shouldCloseModal: boolean;
  shouldReturnToInfo: boolean;

  // Validation
  errors: ValidationErrors;

  // Computed
  readonly isDirty: boolean;
  readonly isValid: boolean;
  readonly canSubmit: boolean;

  // Methods
  loadForEdit(textId: number, position: number, wordId?: number): Promise<void>;
  reset(): void;
  validate(): boolean;
  validateField(field: keyof WordFormData): void;
  save(): Promise<SaveResult>;
  copyTranslationFromSimilar(translation: string): void;
}

/**
 * Create initial empty form data.
 */
function createEmptyFormData(): WordFormData {
  return {
    text: '',
    textLc: '',
    lemma: '',
    translation: '',
    romanization: '',
    sentence: '',
    notes: '',
    status: 1,
    tags: []
  };
}

/**
 * Create initial validation errors (all null = no errors).
 */
function createEmptyErrors(): ValidationErrors {
  return {
    lemma: null,
    translation: null,
    romanization: null,
    sentence: null,
    notes: null,
    general: null
  };
}

/**
 * Deep clone form data for dirty detection.
 */
function cloneFormData(data: WordFormData): WordFormData {
  return {
    ...data,
    tags: [...data.tags]
  };
}

/**
 * Check if two arrays have the same elements.
 */
function arraysEqual(a: string[], b: string[]): boolean {
  if (a.length !== b.length) return false;
  const sortedA = [...a].sort();
  const sortedB = [...b].sort();
  return sortedA.every((val, idx) => val === sortedB[idx]);
}

/**
 * Create the word form store data object.
 */
function createWordFormStore(): WordFormStoreState {
  return {
    // Form data
    formData: createEmptyFormData(),
    originalData: null,

    // Context
    wordId: null,
    textId: 0,
    position: 0,
    hex: '',
    isNewWord: true,

    // Language settings
    showRomanization: false,
    allTags: [],
    similarTerms: [],

    // UI state
    isVisible: false,
    isSubmitting: false,
    isLoading: false,
    shouldCloseModal: false,
    shouldReturnToInfo: false,

    // Validation
    errors: createEmptyErrors(),

    /**
     * Check if form has unsaved changes.
     */
    get isDirty(): boolean {
      if (!this.originalData) return false;
      return (
        this.formData.lemma !== this.originalData.lemma ||
        this.formData.translation !== this.originalData.translation ||
        this.formData.romanization !== this.originalData.romanization ||
        this.formData.sentence !== this.originalData.sentence ||
        this.formData.notes !== this.originalData.notes ||
        this.formData.status !== this.originalData.status ||
        !arraysEqual(this.formData.tags, this.originalData.tags)
      );
    },

    /**
     * Check if form is valid (no validation errors).
     */
    get isValid(): boolean {
      return (
        this.errors.lemma === null &&
        this.errors.translation === null &&
        this.errors.romanization === null &&
        this.errors.sentence === null &&
        this.errors.notes === null &&
        this.errors.general === null
      );
    },

    /**
     * Check if form can be submitted.
     */
    get canSubmit(): boolean {
      return this.isValid && !this.isSubmitting && !this.isLoading;
    },

    /**
     * Load term data for editing.
     */
    async loadForEdit(textId: number, position: number, wordId?: number): Promise<void> {
      this.isLoading = true;
      this.errors = createEmptyErrors();

      try {
        const response = await TermsApi.getForEdit(textId, position, wordId);

        if (response.error || !response.data) {
          this.errors.general = response.error || 'Failed to load term data';
          this.isLoading = false;
          return;
        }

        const data = response.data;

        if (data.error) {
          this.errors.general = data.error;
          this.isLoading = false;
          return;
        }

        // Set context
        this.textId = textId;
        this.position = position;
        this.wordId = data.term.id;
        this.hex = data.term.hex;
        this.isNewWord = data.isNew;

        // Set language settings
        this.showRomanization = data.language.showRomanization;
        this.allTags = data.allTags;
        this.similarTerms = data.similarTerms;

        // Set form data
        this.formData = {
          text: data.term.text,
          textLc: data.term.textLc,
          lemma: data.term.lemma || '',
          translation: data.term.translation === '*' ? '' : data.term.translation,
          romanization: data.term.romanization,
          sentence: data.term.sentence,
          notes: data.term.notes || '',
          status: data.term.status,
          tags: [...data.term.tags]
        };

        // Store original for dirty detection
        this.originalData = cloneFormData(this.formData);

        this.isVisible = true;
      } catch (error) {
        console.error('Error loading term for edit:', error);
        this.errors.general = 'Failed to load term data';
      }

      this.isLoading = false;
    },

    /**
     * Reset the form to initial state.
     */
    reset(): void {
      this.formData = createEmptyFormData();
      this.originalData = null;
      this.wordId = null;
      this.textId = 0;
      this.position = 0;
      this.hex = '';
      this.isNewWord = true;
      this.showRomanization = false;
      this.allTags = [];
      this.similarTerms = [];
      this.isVisible = false;
      this.isSubmitting = false;
      this.isLoading = false;
      this.shouldCloseModal = false;
      this.shouldReturnToInfo = false;
      this.errors = createEmptyErrors();
    },

    /**
     * Validate all form fields.
     */
    validate(): boolean {
      this.validateField('lemma');
      this.validateField('translation');
      this.validateField('romanization');
      this.validateField('sentence');
      this.validateField('notes');
      return this.isValid;
    },

    /**
     * Validate a single form field.
     */
    validateField(field: keyof WordFormData): void {
      switch (field) {
        case 'lemma':
          if (this.formData.lemma.length > 250) {
            this.errors.lemma = 'Lemma must be 250 characters or less';
          } else {
            this.errors.lemma = null;
          }
          break;

        case 'translation':
          if (this.formData.translation.length > 500) {
            this.errors.translation = 'Translation must be 500 characters or less';
          } else {
            this.errors.translation = null;
          }
          break;

        case 'romanization':
          if (this.formData.romanization.length > 100) {
            this.errors.romanization = 'Romanization must be 100 characters or less';
          } else {
            this.errors.romanization = null;
          }
          break;

        case 'sentence':
          if (this.formData.sentence.length > 1000) {
            this.errors.sentence = 'Sentence must be 1000 characters or less';
          } else {
            this.errors.sentence = null;
          }
          break;

        case 'notes':
          if (this.formData.notes.length > 1000) {
            this.errors.notes = 'Notes must be 1000 characters or less';
          } else {
            this.errors.notes = null;
          }
          break;
      }
    },

    /**
     * Save the form (create or update term).
     */
    async save(): Promise<SaveResult> {
      // Validate first
      if (!this.validate()) {
        return { success: false, error: 'Please fix validation errors' };
      }

      this.isSubmitting = true;
      this.errors.general = null;

      try {
        let response;

        if (this.isNewWord) {
          // Create new term
          const createData: TermCreateFullRequest = {
            textId: this.textId,
            position: this.position,
            translation: this.formData.translation,
            romanization: this.formData.romanization,
            sentence: this.formData.sentence,
            notes: this.formData.notes,
            lemma: this.formData.lemma || undefined,
            status: this.formData.status,
            tags: this.formData.tags
          };

          response = await TermsApi.createFull(createData);
        } else {
          // Update existing term
          if (this.wordId === null) {
            this.isSubmitting = false;
            return { success: false, error: 'Word ID is missing' };
          }

          const updateData: TermUpdateFullRequest = {
            translation: this.formData.translation,
            romanization: this.formData.romanization,
            sentence: this.formData.sentence,
            notes: this.formData.notes,
            lemma: this.formData.lemma || undefined,
            status: this.formData.status,
            tags: this.formData.tags
          };

          response = await TermsApi.updateFull(this.wordId, updateData);
        }

        if (response.error || !response.data) {
          this.errors.general = response.error || 'Failed to save term';
          this.isSubmitting = false;
          return { success: false, error: this.errors.general };
        }

        if (response.data.error) {
          this.errors.general = response.data.error;
          this.isSubmitting = false;
          return { success: false, error: response.data.error };
        }

        if (!response.data.term) {
          this.errors.general = 'No term data returned';
          this.isSubmitting = false;
          return { success: false, error: this.errors.general };
        }

        // Update context with returned data
        this.wordId = response.data.term.id;
        this.hex = response.data.term.hex;
        this.isNewWord = false;

        // Update original data for dirty detection
        this.originalData = cloneFormData(this.formData);

        this.isSubmitting = false;

        return {
          success: true,
          hex: response.data.term.hex,
          wordId: response.data.term.id
        };
      } catch (error) {
        console.error('Error saving term:', error);
        this.errors.general = 'Failed to save term';
        this.isSubmitting = false;
        return { success: false, error: this.errors.general };
      }
    },

    /**
     * Copy translation from a similar term.
     */
    copyTranslationFromSimilar(translation: string): void {
      if (this.formData.translation) {
        // Append to existing translation
        this.formData.translation += '; ' + translation;
      } else {
        this.formData.translation = translation;
      }
      this.validateField('translation');
    }
  };
}

/**
 * Initialize the word form store as an Alpine.js store.
 */
export function initWordFormStore(): void {
  Alpine.store('wordForm', createWordFormStore());
}

/**
 * Get the word form store instance.
 */
export function getWordFormStore(): WordFormStoreState {
  return Alpine.store('wordForm') as WordFormStoreState;
}

// Register the store immediately
initWordFormStore();

// Expose for global access
declare global {
  interface Window {
    getWordFormStore: typeof getWordFormStore;
  }
}

window.getWordFormStore = getWordFormStore;

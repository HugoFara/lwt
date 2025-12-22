/**
 * Multi-Word Form Store - Alpine.js store for multi-word expression edit form.
 *
 * Provides centralized state management for creating and editing multi-word
 * expressions in the reading interface.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { TermsApi, type MultiWordData, type MultiWordInput } from '../../api/terms';
import { updateWordStatusInDOM } from '../text_renderer';

/**
 * Form data structure for multi-word expressions.
 */
export interface MultiWordFormData {
  text: string;
  textLc: string;
  translation: string;
  romanization: string;
  sentence: string;
  status: number;
  wordCount: number;
}

/**
 * Validation errors keyed by field name.
 */
export interface ValidationErrors {
  translation: string | null;
  romanization: string | null;
  sentence: string | null;
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
 * Multi-word form store state interface.
 */
export interface MultiWordFormStoreState {
  // Form data
  formData: MultiWordFormData;
  originalData: MultiWordFormData | null;

  // Context
  wordId: number | null;
  textId: number;
  position: number;
  hex: string;
  isNewWord: boolean;

  // Language settings
  showRomanization: boolean;
  langId: number;

  // UI state
  isVisible: boolean;
  isSubmitting: boolean;
  isLoading: boolean;

  // Validation
  errors: ValidationErrors;

  // Computed
  readonly isDirty: boolean;
  readonly isValid: boolean;
  readonly canSubmit: boolean;

  // Methods
  loadForEdit(textId: number, position: number, text: string, wordCount: number, wordId?: number): Promise<void>;
  reset(): void;
  close(): void;
  validate(): boolean;
  validateField(field: keyof MultiWordFormData): void;
  save(): Promise<SaveResult>;
}

/**
 * Create initial empty form data.
 */
function createEmptyFormData(): MultiWordFormData {
  return {
    text: '',
    textLc: '',
    translation: '',
    romanization: '',
    sentence: '',
    status: 1,
    wordCount: 0
  };
}

/**
 * Create initial validation errors (all null = no errors).
 */
function createEmptyErrors(): ValidationErrors {
  return {
    translation: null,
    romanization: null,
    sentence: null,
    general: null
  };
}

/**
 * Deep clone form data for dirty detection.
 */
function cloneFormData(data: MultiWordFormData): MultiWordFormData {
  return { ...data };
}

/**
 * Create the multi-word form store data object.
 */
function createMultiWordFormStore(): MultiWordFormStoreState {
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
    langId: 0,

    // UI state
    isVisible: false,
    isSubmitting: false,
    isLoading: false,

    // Validation
    errors: createEmptyErrors(),

    /**
     * Check if form has unsaved changes.
     */
    get isDirty(): boolean {
      if (!this.originalData) return false;
      return (
        this.formData.translation !== this.originalData.translation ||
        this.formData.romanization !== this.originalData.romanization ||
        this.formData.sentence !== this.originalData.sentence ||
        this.formData.status !== this.originalData.status
      );
    },

    /**
     * Check if form is valid (no validation errors).
     */
    get isValid(): boolean {
      return (
        this.errors.translation === null &&
        this.errors.romanization === null &&
        this.errors.sentence === null &&
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
     * Load multi-word data for editing.
     *
     * @param textId    Text ID
     * @param position  Position in text (word order)
     * @param text      Multi-word text (for new expressions)
     * @param wordCount Number of words in expression
     * @param wordId    Word ID (for existing expressions)
     */
    async loadForEdit(
      textId: number,
      position: number,
      text: string,
      wordCount: number,
      wordId?: number
    ): Promise<void> {
      this.isLoading = true;
      this.errors = createEmptyErrors();

      try {
        // Call API to get multi-word data
        const response = await TermsApi.getMultiWord(textId, position, text, wordId);

        if (response.error || !response.data) {
          this.errors.general = response.error || 'Failed to load multi-word data';
          this.isLoading = false;
          return;
        }

        const data: MultiWordData = response.data;

        if (data.error) {
          this.errors.general = data.error;
          this.isLoading = false;
          return;
        }

        // Set context
        this.textId = textId;
        this.position = position;
        this.wordId = data.id;
        this.isNewWord = data.isNew;
        this.langId = data.langId;

        // Determine if romanization should be shown (would need language data)
        // For now, show if there's existing romanization or language supports it
        this.showRomanization = data.romanization !== '';

        // Set form data
        this.formData = {
          text: data.text,
          textLc: data.textLc,
          translation: data.translation === '*' ? '' : data.translation,
          romanization: data.romanization,
          sentence: data.sentence,
          status: data.status || 1,
          wordCount: data.wordCount || wordCount
        };

        // Store original for dirty detection
        this.originalData = cloneFormData(this.formData);

        this.isVisible = true;
      } catch (error) {
        console.error('Error loading multi-word for edit:', error);
        this.errors.general = 'Failed to load multi-word data';
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
      this.langId = 0;
      this.isVisible = false;
      this.isSubmitting = false;
      this.isLoading = false;
      this.errors = createEmptyErrors();
    },

    /**
     * Close the modal.
     */
    close(): void {
      if (this.isDirty) {
        if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
          return;
        }
      }
      this.reset();
    },

    /**
     * Validate all form fields.
     */
    validate(): boolean {
      this.validateField('translation');
      this.validateField('romanization');
      this.validateField('sentence');
      return this.isValid;
    },

    /**
     * Validate a single form field.
     */
    validateField(field: keyof MultiWordFormData): void {
      switch (field) {
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
      }
    },

    /**
     * Save the form (create or update multi-word expression).
     */
    async save(): Promise<SaveResult> {
      // Validate first
      if (!this.validate()) {
        return { success: false, error: 'Please fix validation errors' };
      }

      this.isSubmitting = true;
      this.errors.general = null;

      try {
        if (this.isNewWord) {
          // Create new multi-word expression
          const createData: MultiWordInput = {
            textId: this.textId,
            position: this.position,
            text: this.formData.text,
            wordCount: this.formData.wordCount,
            translation: this.formData.translation,
            romanization: this.formData.romanization,
            sentence: this.formData.sentence,
            status: this.formData.status
          };

          const response = await TermsApi.createMultiWord(createData);

          if (response.error || !response.data) {
            this.errors.general = response.error || 'Failed to create multi-word';
            this.isSubmitting = false;
            return { success: false, error: this.errors.general };
          }

          const data = response.data;

          if (data.error) {
            this.errors.general = data.error;
            this.isSubmitting = false;
            return { success: false, error: data.error };
          }

          // Update context with returned data
          this.wordId = data.term_id || null;
          this.hex = data.term_lc || '';
          this.isNewWord = false;

          // Update DOM with new term
          if (this.hex && this.wordId) {
            updateWordStatusInDOM(this.hex, this.formData.status, this.wordId);
          }

          // Update original data for dirty detection
          this.originalData = cloneFormData(this.formData);

          this.isSubmitting = false;

          return {
            success: true,
            hex: this.hex,
            wordId: this.wordId || undefined
          };
        } else {
          // Update existing multi-word expression
          if (this.wordId === null) {
            this.isSubmitting = false;
            return { success: false, error: 'Word ID is missing' };
          }

          const updateData: Partial<MultiWordInput> = {
            translation: this.formData.translation,
            romanization: this.formData.romanization,
            sentence: this.formData.sentence,
            status: this.formData.status
          };

          const response = await TermsApi.updateMultiWord(this.wordId, updateData);

          if (response.error || !response.data) {
            this.errors.general = response.error || 'Failed to update multi-word';
            this.isSubmitting = false;
            return { success: false, error: this.errors.general };
          }

          if (response.data.error) {
            this.errors.general = response.data.error;
            this.isSubmitting = false;
            return { success: false, error: response.data.error };
          }

          // Update DOM with new status
          if (this.hex) {
            updateWordStatusInDOM(this.hex, this.formData.status, this.wordId);
          }

          // Update original data for dirty detection
          this.originalData = cloneFormData(this.formData);

          this.isSubmitting = false;

          return {
            success: true,
            hex: this.hex,
            wordId: this.wordId
          };
        }
      } catch (error) {
        console.error('Error saving multi-word:', error);
        this.errors.general = 'Failed to save multi-word';
        this.isSubmitting = false;
        return { success: false, error: this.errors.general };
      }
    }
  };
}

/**
 * Initialize the multi-word form store as an Alpine.js store.
 */
export function initMultiWordFormStore(): void {
  Alpine.store('multiWordForm', createMultiWordFormStore());
}

/**
 * Get the multi-word form store instance.
 */
export function getMultiWordFormStore(): MultiWordFormStoreState {
  return Alpine.store('multiWordForm') as MultiWordFormStoreState;
}

// Register the store immediately
initMultiWordFormStore();

// Expose for global access
declare global {
  interface Window {
    getMultiWordFormStore: typeof getMultiWordFormStore;
  }
}

window.getMultiWordFormStore = getMultiWordFormStore;

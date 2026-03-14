/**
 * Multi-Word Modal - Alpine.js component for multi-word expression editing.
 *
 * Displays a form for creating or editing multi-word expressions.
 * Uses Bulma modal styling.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import type { MultiWordFormStoreState } from '../stores/multi_word_form_store';
import { initIcons } from '@shared/icons/lucide_icons';
import { trapFocus, releaseFocus } from '@shared/accessibility/focus_trap';
import { announce } from '@shared/accessibility/aria_live';

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
 * Status definitions for learning words (1-5 only for multi-words).
 */
const STATUSES: StatusInfo[] = [
  { value: 1, label: 'Learning (1)', abbr: '1', class: 'is-danger' },
  { value: 2, label: 'Learning (2)', abbr: '2', class: 'is-warning' },
  { value: 3, label: 'Learning (3)', abbr: '3', class: 'is-info' },
  { value: 4, label: 'Learning (4)', abbr: '4', class: 'is-primary' },
  { value: 5, label: 'Learned', abbr: '5', class: 'is-success' }
];

/**
 * Multi-word modal Alpine.js component interface.
 */
export interface MultiWordModalData {
  // Computed properties
  readonly store: MultiWordFormStoreState;
  readonly isOpen: boolean;
  readonly isLoading: boolean;
  readonly isSubmitting: boolean;
  readonly modalTitle: string;
  readonly statuses: StatusInfo[];

  // CSP-safe proxy properties for x-model
  translation: string;
  romanization: string;
  sentence: string;
  readonly formText: string;
  readonly wordCountLabel: string;
  readonly hasGeneralError: boolean;
  readonly generalError: string | null;
  readonly hasTranslationError: boolean;
  readonly translationError: string | null;
  readonly hasRomanizationError: boolean;
  readonly romanizationError: string | null;
  readonly hasSentenceError: boolean;
  readonly sentenceError: string | null;
  readonly showRomanization: boolean;
  readonly canSubmit: boolean;

  // Lifecycle
  init(): void;

  // Methods
  close(): void;
  save(): Promise<void>;
  setStatus(status: number): void;
  clearGeneralError(): void;
  validateField(field: string): void;
  isCurrentStatus(status: number): boolean;
  getStatusButtonClass(status: number): string;
}

/**
 * Create the multi-word modal Alpine.js component data.
 */
export function multiWordModalData(): MultiWordModalData {
  return {
    // Initialize icons and focus trap when modal opens
    init(): void {
      // Close on Escape key
      document.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'Escape' && this.isOpen) {
          this.close();
        }
      });

      Alpine.effect(() => {
        if (this.store.isVisible) {
          requestAnimationFrame(() => {
            initIcons();
            const modalCard = document.querySelector<HTMLElement>('#multi-word-modal-title')
              ?.closest('.modal-card');
            if (modalCard) {
              trapFocus(modalCard as HTMLElement);
            }
            announce(this.modalTitle);
          });
        }
      });
    },

    get store(): MultiWordFormStoreState {
      return Alpine.store('multiWordForm') as MultiWordFormStoreState;
    },

    get isOpen(): boolean {
      return this.store.isVisible;
    },

    get isLoading(): boolean {
      return this.store.isLoading;
    },

    get isSubmitting(): boolean {
      return this.store.isSubmitting;
    },

    get modalTitle(): string {
      if (this.store.isNewWord) {
        return `New Multi-Word Expression (${this.store.formData.wordCount} words)`;
      }
      return `Edit Multi-Word Expression (${this.store.formData.wordCount} words)`;
    },

    get statuses(): StatusInfo[] {
      return STATUSES;
    },

    // CSP-safe proxy properties — Alpine CSP build prohibits nested
    // property assignments like `store.formData.translation = ...`.
    get translation(): string {
      return this.store.formData.translation;
    },
    set translation(value: string) {
      this.store.formData.translation = value;
    },

    get romanization(): string {
      return this.store.formData.romanization;
    },
    set romanization(value: string) {
      this.store.formData.romanization = value;
    },

    get sentence(): string {
      return this.store.formData.sentence;
    },
    set sentence(value: string) {
      this.store.formData.sentence = value;
    },

    get formText(): string {
      return this.store.formData.text;
    },

    get wordCountLabel(): string {
      return this.store.formData.wordCount + ' words';
    },

    get hasGeneralError(): boolean {
      return !!this.store.errors.general;
    },

    get generalError(): string | null {
      return this.store.errors.general;
    },

    get hasTranslationError(): boolean {
      return !!this.store.errors.translation;
    },

    get translationError(): string | null {
      return this.store.errors.translation ?? null;
    },

    get hasRomanizationError(): boolean {
      return !!this.store.errors.romanization;
    },

    get romanizationError(): string | null {
      return this.store.errors.romanization ?? null;
    },

    get hasSentenceError(): boolean {
      return !!this.store.errors.sentence;
    },

    get sentenceError(): string | null {
      return this.store.errors.sentence ?? null;
    },

    get showRomanization(): boolean {
      return this.store.showRomanization;
    },

    get canSubmit(): boolean {
      return this.store.canSubmit;
    },

    clearGeneralError(): void {
      this.store.errors.general = null;
    },

    validateField(field: string): void {
      this.store.validateField(field as keyof import('../stores/multi_word_form_store').MultiWordFormData);
    },

    /**
     * Close the modal.
     */
    close(): void {
      releaseFocus();
      this.store.close();
    },

    /**
     * Save the form.
     */
    async save(): Promise<void> {
      const result = await this.store.save();

      if (result.success) {
        // Close modal on success
        releaseFocus();
        this.store.reset();
      }
      // On error, store.errors.general will be set and displayed
    },

    /**
     * Set the status value.
     */
    setStatus(status: number): void {
      this.store.formData.status = status;
    },

    /**
     * Check if a status is the current status.
     */
    isCurrentStatus(status: number): boolean {
      return this.store.formData.status === status;
    },

    /**
     * Get Bulma button class for a status.
     */
    getStatusButtonClass(status: number): string {
      const statusInfo = STATUSES.find(s => s.value === status);
      const base = 'button is-small';
      const colorClass = statusInfo?.class || '';

      if (this.isCurrentStatus(status)) {
        return `${base} ${colorClass}`;
      }
      return `${base} is-outlined ${colorClass}`;
    }
  };
}

/**
 * Register the multi-word modal as an Alpine.js component.
 */
export function registerMultiWordModal(): void {
  Alpine.data('multiWordModal', multiWordModalData);
}

// Register the component immediately
registerMultiWordModal();

// Expose for global access
declare global {
  interface Window {
    multiWordModalData: typeof multiWordModalData;
  }
}

window.multiWordModalData = multiWordModalData;

// Also export as default for simpler imports
export default multiWordModalData;

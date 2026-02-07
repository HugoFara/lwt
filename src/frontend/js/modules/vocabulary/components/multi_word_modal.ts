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

  // Lifecycle
  init(): void;

  // Methods
  close(): void;
  save(): Promise<void>;
  setStatus(status: number): void;
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

    /**
     * Get the multi-word form store.
     */
    get store(): MultiWordFormStoreState {
      return Alpine.store('multiWordForm') as MultiWordFormStoreState;
    },

    /**
     * Check if modal is open.
     */
    get isOpen(): boolean {
      return this.store.isVisible;
    },

    /**
     * Check if loading.
     */
    get isLoading(): boolean {
      return this.store.isLoading;
    },

    /**
     * Check if submitting.
     */
    get isSubmitting(): boolean {
      return this.store.isSubmitting;
    },

    /**
     * Get modal title.
     */
    get modalTitle(): string {
      if (this.store.isNewWord) {
        return `New Multi-Word Expression (${this.store.formData.wordCount} words)`;
      }
      return `Edit Multi-Word Expression (${this.store.formData.wordCount} words)`;
    },

    /**
     * Get available statuses.
     */
    get statuses(): StatusInfo[] {
      return STATUSES;
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

/**
 * Table Management - Alpine.js component for table set management.
 *
 * Handles table set selection, creation, and deletion with
 * reactive validation and confirmation dialogs.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

interface TableManagementState {
  selectedPrefix: string;
  newPrefix: string;
  createError: string | null;
  deletePrefix: string;
  confirmDelete: boolean;
  validatePrefix(): boolean;
  submitCreate(event: Event): void;
  submitDelete(event: Event): void;
}

/**
 * Validate table prefix value.
 *
 * @param value The prefix value to validate
 * @returns Error message or null if valid
 */
function getValidationError(value: string): string | null {
  const trimmed = value.trim();
  if (!trimmed) {
    return 'Table Set Name must not be empty';
  }
  if (!/^[a-zA-Z0-9_]+$/.test(trimmed)) {
    return 'Only letters, numbers, and underscores allowed';
  }
  if (trimmed.length > 20) {
    return 'Maximum 20 characters';
  }
  return null;
}

/**
 * Alpine.js data component for the table management page.
 * Manages table set operations with validation and confirmations.
 */
export function tableManagementApp(): TableManagementState {
  return {
    selectedPrefix: '-',
    newPrefix: '',
    createError: null,
    deletePrefix: '-',
    confirmDelete: false,

    /**
     * Validate the new prefix input and update error state.
     * @returns true if valid
     */
    validatePrefix(): boolean {
      this.createError = getValidationError(this.newPrefix);
      return this.createError === null;
    },

    /**
     * Handle create form submission.
     * Prevents submission if validation fails.
     */
    submitCreate(event: Event): void {
      if (!this.validatePrefix()) {
        event.preventDefault();
      }
    },

    /**
     * Handle delete form submission.
     * Shows confirmation dialog and prevents if not confirmed.
     */
    submitDelete(event: Event): void {
      if (this.deletePrefix === '-' || !this.confirmDelete) {
        event.preventDefault();
        return;
      }

      const confirmMessage =
        `*** DELETING TABLE SET: ${this.deletePrefix} ***\n\n` +
        `ALL DATA IN THIS TABLE SET WILL BE LOST!\n\n` +
        `Are you sure?`;

      if (!confirm(confirmMessage)) {
        event.preventDefault();
      }
    }
  };
}

/**
 * Register the Alpine.js component.
 */
export function initTableManagementAlpine(): void {
  Alpine.data('tableManagementApp', tableManagementApp);
}

// Auto-register before Alpine.start() is called
initTableManagementAlpine();

// Legacy exports for backward compatibility
declare global {
  interface Window {
    initTableManagement: typeof initTableManagementAlpine;
    check_table_prefix: (value: string) => boolean;
    checkTablePrefix: (value: string) => boolean;
  }
}

/**
 * Legacy function for checking table prefix validity.
 * @deprecated Use tableManagementApp().validatePrefix() instead
 */
function checkTablePrefix(value: string): boolean {
  const error = getValidationError(value);
  if (error) {
    alert(error);
    return false;
  }
  return true;
}

window.initTableManagement = initTableManagementAlpine;
window.check_table_prefix = checkTablePrefix;
window.checkTablePrefix = checkTablePrefix;

/**
 * EPUB import form — submits the upload to the API and renders the outcome
 * in place, rather than navigating to a server-rendered result page.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.3.0
 */

import Alpine from 'alpinejs';
import { importEpubForm } from '@modules/book/api/books_api';
import { t } from '@shared/i18n/translator';

interface EpubImportFormState {
  isSubmitting: boolean;
  isDone: boolean;
  message: string;
  messageType: string;
  bookId: number | null;
  fileName: string;
  updateFileName(event: Event): void;
  bookUrl(): string;
  hasBook(): boolean;
  submitButtonClass(): string;
  submit(event: Event): Promise<void>;
}

/**
 * Alpine component backing the standalone EPUB import form.
 *
 * @returns Component state and methods
 */
export function epubImportFormData(): EpubImportFormState {
  return {
    isSubmitting: false,
    isDone: false,
    message: '',
    messageType: 'is-success',
    bookId: null,
    fileName: t('book.no_file_selected'),

    /**
     * Mirror the chosen file's name next to the picker.
     *
     * Lives here rather than inline because the CSP Alpine build cannot
     * evaluate optional chaining.
     *
     * @param event Change event from the file input
     */
    updateFileName(event: Event): void {
      const input = event.target as HTMLInputElement | null;
      const file = input && input.files ? input.files[0] : null;
      this.fileName = file ? file.name : t('book.no_file_selected');
    },

    /**
     * URL of the imported book, for the success link.
     *
     * @returns Book URL, or '#' before an import has succeeded
     */
    bookUrl(): string {
      return this.bookId === null ? '#' : `/book/${this.bookId}`;
    },

    /**
     * Whether the import produced a book to link to.
     *
     * @returns True once an import has succeeded
     */
    hasBook(): boolean {
      return this.bookId !== null;
    },

    /**
     * Loading modifier for the submit button.
     *
     * @returns Bulma class list
     */
    submitButtonClass(): string {
      return this.isSubmitting ? 'is-loading' : '';
    },

    /**
     * Upload the form and render the result without leaving the page.
     *
     * @param event Submit event
     */
    async submit(event: Event): Promise<void> {
      event.preventDefault();

      const form = event.target as HTMLFormElement | null;
      if (!form || this.isSubmitting) return;

      this.isSubmitting = true;

      const result = await importEpubForm(form);

      if (result.error !== '') {
        this.message = result.error;
        this.messageType = 'is-danger';
        this.bookId = null;
      } else {
        this.message = result.message;
        this.messageType = 'is-success';
        this.bookId = result.bookId;
      }

      this.isSubmitting = false;
      this.isDone = true;
    }
  };
}

Alpine.data('epubImportForm', epubImportFormData);

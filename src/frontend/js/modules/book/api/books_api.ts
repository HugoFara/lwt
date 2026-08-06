/**
 * Books API client.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.3.0
 */

import { apiPostMultipart, type ApiResponse } from '@shared/api/client';

/**
 * Result of an EPUB import.
 *
 * `error` carries a failure the server handled deliberately (no language
 * chosen, unreadable EPUB); a transport failure surfaces on the envelope.
 */
export interface BookImportResponse {
  success: boolean;
  message?: string;
  bookId?: number | null;
  error?: string;
}

/** Books API methods. */
export const BooksApi = {
  /**
   * Import an EPUB as a book.
   *
   * @param formData Multipart body carrying the file plus LgID/TxLgID,
   *                 and optionally TxTitle and TextTags
   *
   * @returns Promise with the created book's ID and a message
   */
  async importEpub(formData: FormData): Promise<ApiResponse<BookImportResponse>> {
    return apiPostMultipart<BookImportResponse>('/books', formData);
  }
};

/** Outcome of an import, flattened for callers that only branch on success. */
export interface EpubImportOutcome {
  bookId: number | null;
  message: string;
  error: string;
}

/**
 * Submit a form's fields as an EPUB import and normalise the outcome.
 *
 * Collapses the three failure shapes — transport error, envelope error, and a
 * handled `success: false` — into a single `error` string, so callers only
 * have to check whether `bookId` came back.
 *
 * @param form Form holding the file input and language field
 *
 * @returns Book ID on success, otherwise a non-empty error
 */
export async function importEpubForm(form: HTMLFormElement): Promise<EpubImportOutcome> {
  const response = await BooksApi.importEpub(new FormData(form));
  const payload = response.data;

  if (response.error || !payload || payload.success !== true) {
    return {
      bookId: null,
      message: '',
      error: response.error || payload?.error || 'Import failed'
    };
  }

  return {
    bookId: payload.bookId ?? null,
    message: payload.message ?? '',
    error: ''
  };
}

/**
 * Books API client.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.3.0
 */

import {
  apiDelete,
  apiGet,
  apiPostMultipart,
  apiPut,
  type ApiResponse
} from '@shared/api/client';

/** A book as the list and detail endpoints return it. */
export interface Book {
  id: number;
  title: string;
  author: string | null;
  description?: string | null;
  sourceType: string;
  totalChapters: number;
  currentChapter: number;
  progress: number;
}

/** One chapter of a book; `id` is the text ID it maps to. */
export interface BookChapter {
  id: number;
  num: number;
  title: string;
}

/** Pagination block accompanying a book list. */
export interface BookPagination {
  total: number;
  page: number;
  per_page: number;
  total_pages: number;
}

/** Envelope returned by `GET /books`. */
export interface BookListResponse {
  success: boolean;
  data: Book[];
  pagination: BookPagination;
}

/**
 * Envelope returned by `GET /books/{id}`.
 *
 * The payload nests the book alongside its chapters, so the detail page needs
 * only this one request — `/books/{id}/chapters` exists for callers that want
 * the chapters alone.
 */
export interface BookResponse {
  success: boolean;
  data?: {
    book: Book;
    chapters: BookChapter[];
  };
  error?: string;
}

/** Envelope returned by `GET /books/{id}/chapters`. */
export interface BookChaptersResponse {
  success: boolean;
  data?: BookChapter[];
  error?: string;
}

/** Envelope returned by `DELETE /books/{id}` and the progress update. */
export interface BookMutationResponse {
  success: boolean;
  message?: string;
  error?: string;
}

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
  },

  /**
   * List books, newest first, optionally filtered to one language.
   *
   * @param languageId Language to filter by, or null for all languages
   * @param page       1-based page number
   *
   * @returns Promise with the page of books and its pagination block
   */
  async list(languageId: number | null, page: number): Promise<ApiResponse<BookListResponse>> {
    const params: Record<string, number> = { page };
    if (languageId !== null && languageId > 0) {
      params.lg_id = languageId;
    }
    return apiGet<BookListResponse>('/books', params);
  },

  /**
   * Fetch a single book's metadata, progress and chapters.
   *
   * @param bookId Book ID
   *
   * @returns Promise with the book and chapters, or an error envelope if it is gone
   */
  async get(bookId: number): Promise<ApiResponse<BookResponse>> {
    return apiGet<BookResponse>(`/books/${bookId}`);
  },

  /**
   * Fetch a book's chapters in reading order.
   *
   * @param bookId Book ID
   *
   * @returns Promise with the chapter list
   */
  async chapters(bookId: number): Promise<ApiResponse<BookChaptersResponse>> {
    return apiGet<BookChaptersResponse>(`/books/${bookId}/chapters`);
  },

  /**
   * Delete a book and its chapters.
   *
   * @param bookId Book ID
   *
   * @returns Promise with the outcome message
   */
  async remove(bookId: number): Promise<ApiResponse<BookMutationResponse>> {
    return apiDelete<BookMutationResponse>(`/books/${bookId}`);
  },

  /**
   * Record which chapter the reader has reached.
   *
   * @param bookId     Book ID
   * @param chapterNum 1-based chapter number
   *
   * @returns Promise with the outcome message
   */
  async setProgress(bookId: number, chapterNum: number): Promise<ApiResponse<BookMutationResponse>> {
    return apiPut<BookMutationResponse>(`/books/${bookId}/progress`, { chapter: chapterNum });
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

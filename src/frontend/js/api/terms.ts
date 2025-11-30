/**
 * Terms API - Type-safe wrapper for term/word operations.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import {
  apiGet,
  apiPost,
  apiPut,
  apiDelete,
  apiPostForm,
  type ApiResponse
} from '../core/api_client';

/**
 * Term/word data structure.
 */
export interface Term {
  id: number;
  text: string;
  textLc: string;
  translation: string;
  romanization?: string;
  status: number;
  langId: number;
  sentence?: string;
  tags?: string[];
}

/**
 * Response for term status operations.
 */
export interface TermStatusResponse {
  set?: number;
  increment?: string;
  error?: string;
}

/**
 * Response for term translation operations.
 */
export interface TermTranslationResponse {
  update?: string;
  add?: string;
  term_id?: number;
  term_lc?: string;
  error?: string;
}

/**
 * Response for term deletion.
 */
export interface TermDeleteResponse {
  deleted?: boolean;
  error?: string;
}

/**
 * Response for quick term creation.
 */
export interface TermQuickCreateResponse {
  term_id?: number;
  term_lc?: string;
  error?: string;
}

/**
 * Similar term suggestion.
 */
export interface SimilarTerm {
  id: number;
  text: string;
  translation: string;
  status: number;
}

/**
 * Sentence containing a term.
 */
export interface SentenceWithTerm {
  id: number;
  sentence: string;
  textId: number;
  textTitle?: string;
}

/**
 * Terms API methods.
 */
export const TermsApi = {
  /**
   * Get term by ID.
   *
   * @param termId Term ID
   * @returns Promise with term data or error
   */
  async get(termId: number): Promise<ApiResponse<Term>> {
    return apiGet<Term>(`/terms/${termId}`);
  },

  /**
   * Set term status to a specific value.
   *
   * @param termId Term ID
   * @param status New status (1-5, 98, 99)
   * @returns Promise with operation result
   */
  async setStatus(
    termId: number,
    status: number
  ): Promise<ApiResponse<TermStatusResponse>> {
    return apiPostForm<TermStatusResponse>(
      `/terms/${termId}/status/${status}`,
      {}
    );
  },

  /**
   * Increment or decrement term status.
   *
   * @param termId    Term ID
   * @param direction 'up' to increment, 'down' to decrement
   * @returns Promise with operation result including HTML controls
   */
  async incrementStatus(
    termId: number,
    direction: 'up' | 'down'
  ): Promise<ApiResponse<TermStatusResponse>> {
    return apiPostForm<TermStatusResponse>(
      `/terms/${termId}/status/${direction}`,
      {}
    );
  },

  /**
   * Delete a term.
   *
   * @param termId Term ID to delete
   * @returns Promise with deletion result
   */
  async delete(termId: number): Promise<ApiResponse<TermDeleteResponse>> {
    return apiDelete<TermDeleteResponse>(`/terms/${termId}`);
  },

  /**
   * Update term translation (adds to existing translations).
   *
   * @param termId      Term ID
   * @param translation New translation to add
   * @returns Promise with update result
   */
  async updateTranslation(
    termId: number,
    translation: string
  ): Promise<ApiResponse<TermTranslationResponse>> {
    return apiPut<TermTranslationResponse>(`/terms/${termId}/translation`, {
      translation
    });
  },

  /**
   * Add a new term with translation.
   *
   * @param text        Term text
   * @param langId      Language ID
   * @param translation Translation
   * @returns Promise with new term data
   */
  async addWithTranslation(
    text: string,
    langId: number,
    translation: string
  ): Promise<ApiResponse<TermTranslationResponse>> {
    return apiPost<TermTranslationResponse>('/terms', {
      text,
      lg_id: langId,
      translation
    });
  },

  /**
   * Create a term quickly with wellknown (99) or ignored (98) status.
   * Used for marking unknown words without opening the edit form.
   *
   * @param textId   Text ID containing the word
   * @param position Word position in text
   * @param status   Status to set (98 for ignored, 99 for well-known)
   * @returns Promise with new term data
   */
  async createQuick(
    textId: number,
    position: number,
    status: 98 | 99
  ): Promise<ApiResponse<TermQuickCreateResponse>> {
    return apiPost<TermQuickCreateResponse>('/terms/quick', {
      textId,
      position,
      status
    });
  },

  /**
   * Get similar terms for a given term.
   *
   * @param termText Term text to find similar terms for
   * @param langId   Language ID
   * @returns Promise with array of similar terms
   */
  async getSimilar(
    termText: string,
    langId: number
  ): Promise<ApiResponse<SimilarTerm[]>> {
    return apiGet<SimilarTerm[]>('/similar-terms', {
      term: termText,
      lg_id: langId
    });
  },

  /**
   * Get sentences containing a term.
   *
   * @param termId Term ID
   * @param langId Language ID
   * @returns Promise with array of sentences
   */
  async getSentences(
    termId: number,
    langId: number
  ): Promise<ApiResponse<SentenceWithTerm[]>> {
    return apiGet<SentenceWithTerm[]>('/sentences-with-term', {
      term_id: termId,
      lg_id: langId
    });
  },

  /**
   * Get imported terms (terms that were bulk imported).
   *
   * @returns Promise with array of imported terms
   */
  async getImported(): Promise<ApiResponse<Term[]>> {
    return apiGet<Term[]>('/terms/imported');
  }
};

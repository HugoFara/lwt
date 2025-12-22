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
  notes?: string;
  status: number;
  langId: number;
  sentence?: string;
  tags?: string[];
}

/**
 * Detailed term data including sentence and tags.
 * Returned by GET /terms/{id}/details
 */
export interface TermDetails extends Term {
  sentence: string;
  tags: string[];
  statusLabel: string;
}

/**
 * Multi-word expression data for editing.
 * Returned by GET /terms/multi
 */
export interface MultiWordData {
  id: number | null;
  text: string;
  textLc: string;
  translation: string;
  romanization: string;
  sentence: string;
  status: number;
  langId: number;
  wordCount: number;
  isNew: boolean;
  error?: string;
}

/**
 * Data for creating/updating multi-word expressions.
 */
export interface MultiWordInput {
  textId: number;
  position?: number;
  text: string;
  wordCount?: number;
  translation?: string;
  romanization?: string;
  sentence?: string;
  status?: number;
}

/**
 * Response for multi-word update operations.
 */
export interface MultiWordUpdateResponse {
  success?: boolean;
  status?: number;
  error?: string;
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

// =========================================================================
// Full Term CRUD Types for Reactive UI
// =========================================================================

/**
 * Request body for creating a term with full data.
 */
export interface TermCreateFullRequest {
  textId: number;
  position: number;
  translation: string;
  romanization?: string;
  sentence?: string;
  notes?: string;
  status: number;
  tags?: string[];
}

/**
 * Request body for updating a term with full data.
 */
export interface TermUpdateFullRequest {
  translation: string;
  romanization?: string;
  sentence?: string;
  notes?: string;
  status: number;
  tags?: string[];
}

/**
 * Term data returned from create/update operations.
 */
export interface TermFullResponse {
  success?: boolean;
  term?: {
    id: number;
    text: string;
    textLc: string;
    hex: string;
    translation: string;
    romanization: string;
    sentence: string;
    status: number;
    tags: string[];
  };
  error?: string;
}

/**
 * Similar term for display in edit form.
 */
export interface SimilarTermForEdit {
  id: number;
  text: string;
  translation: string;
  status: number;
}

/**
 * Language settings for term editing.
 */
export interface TermEditLanguage {
  id: number;
  name: string;
  showRomanization: boolean;
  translateUri: string;
}

/**
 * Term data for editing in modal.
 */
export interface TermForEdit {
  id: number | null;
  text: string;
  textLc: string;
  hex: string;
  translation: string;
  romanization: string;
  sentence: string;
  notes: string;
  status: number;
  tags: string[];
}

/**
 * Response from GET /terms/for-edit endpoint.
 */
export interface TermForEditResponse {
  isNew: boolean;
  term: TermForEdit;
  language: TermEditLanguage;
  allTags: string[];
  similarTerms: SimilarTermForEdit[];
  error?: string;
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
  },

  /**
   * Get detailed term information including sentence and tags.
   *
   * @param termId Term ID
   * @param ann    Optional annotation to highlight in translation
   * @returns Promise with term details or error
   */
  async getDetails(
    termId: number,
    ann?: string
  ): Promise<ApiResponse<TermDetails>> {
    const params: Record<string, string> = {};
    if (ann) {
      params.ann = ann;
    }
    return apiGet<TermDetails>(`/terms/${termId}/details`, params);
  },

  // =========================================================================
  // Multi-word Expression Methods
  // =========================================================================

  /**
   * Get multi-word expression data for editing.
   *
   * @param textId   Text ID
   * @param position Position in text
   * @param text     Multi-word text (for new expressions)
   * @param wordId   Word ID (for existing expressions)
   * @returns Promise with multi-word data or error
   */
  async getMultiWord(
    textId: number,
    position: number,
    text?: string,
    wordId?: number
  ): Promise<ApiResponse<MultiWordData>> {
    const params: Record<string, string> = {
      tid: String(textId),
      ord: String(position)
    };
    if (text) {
      params.txt = text;
    }
    if (wordId !== undefined) {
      params.wid = String(wordId);
    }
    return apiGet<MultiWordData>('/terms/multi', params);
  },

  /**
   * Create a new multi-word expression.
   *
   * @param data Multi-word data
   * @returns Promise with new term data
   */
  async createMultiWord(
    data: MultiWordInput
  ): Promise<ApiResponse<TermQuickCreateResponse>> {
    return apiPost<TermQuickCreateResponse>('/terms/multi', data as unknown as Record<string, unknown>);
  },

  /**
   * Update an existing multi-word expression.
   *
   * @param termId Term ID
   * @param data   Multi-word data (translation, romanization, sentence, status)
   * @returns Promise with update result
   */
  async updateMultiWord(
    termId: number,
    data: Partial<MultiWordInput>
  ): Promise<ApiResponse<MultiWordUpdateResponse>> {
    return apiPut<MultiWordUpdateResponse>(`/terms/multi/${termId}`, data as unknown as Record<string, unknown>);
  },

  // =========================================================================
  // Full Term CRUD Methods for Reactive UI
  // =========================================================================

  /**
   * Get term data for editing in modal.
   *
   * Returns term data, language settings, all tags for autocomplete, and similar terms.
   *
   * @param textId   Text ID
   * @param position Word position in text
   * @param wordId   Word ID (optional, for existing terms)
   * @returns Promise with term edit data
   */
  async getForEdit(
    textId: number,
    position: number,
    wordId?: number
  ): Promise<ApiResponse<TermForEditResponse>> {
    const params: Record<string, string> = {
      tid: String(textId),
      ord: String(position)
    };
    if (wordId !== undefined && wordId !== null) {
      params.wid = String(wordId);
    }
    return apiGet<TermForEditResponse>('/terms/for-edit', params);
  },

  /**
   * Create a new term with full data.
   *
   * @param data Term creation data (textId, position, translation, romanization, sentence, status, tags)
   * @returns Promise with created term data
   */
  async createFull(
    data: TermCreateFullRequest
  ): Promise<ApiResponse<TermFullResponse>> {
    return apiPost<TermFullResponse>('/terms/full', data as unknown as Record<string, unknown>);
  },

  /**
   * Update an existing term with full data.
   *
   * @param termId Term ID
   * @param data   Term update data (translation, romanization, sentence, status, tags)
   * @returns Promise with updated term data
   */
  async updateFull(
    termId: number,
    data: TermUpdateFullRequest
  ): Promise<ApiResponse<TermFullResponse>> {
    return apiPut<TermFullResponse>(`/terms/${termId}`, data as unknown as Record<string, unknown>);
  }
};

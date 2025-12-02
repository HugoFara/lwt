/**
 * Words API - Type-safe wrapper for word list operations.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { apiGet, apiPut, type ApiResponse } from '../core/api_client';

/**
 * Word item in the list.
 */
export interface WordItem {
  id: number;
  text: string;
  translation: string;
  romanization: string;
  sentence: string;
  sentenceOk: boolean;
  status: number;
  statusAbbr: string;
  statusLabel: string;
  days: string;
  score: number;
  score2: number;
  tags: string;
  langId: number;
  langName: string;
  rightToLeft: boolean;
  ttsClass: string | null;
  textsWordCount?: number;
}

/**
 * Pagination information.
 */
export interface PaginationInfo {
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

/**
 * Word list response.
 */
export interface WordListResponse {
  words: WordItem[];
  pagination: PaginationInfo;
}

/**
 * Filter state for word list.
 */
export interface WordListFilters {
  lang?: number | string | null;
  text_id?: number | string | null;
  status?: string;
  query?: string;
  query_mode?: string;
  regex_mode?: string;
  tag1?: number | string | null;
  tag2?: number | string | null;
  tag12?: number | string;
  sort?: number;
  page?: number;
  per_page?: number;
}

/**
 * Language option.
 */
export interface LanguageOption {
  id: number;
  name: string;
}

/**
 * Text option.
 */
export interface TextOption {
  id: number;
  title: string;
}

/**
 * Tag option.
 */
export interface TagOption {
  id: number;
  name: string;
}

/**
 * Status option.
 */
export interface StatusOption {
  value: string;
  label: string;
}

/**
 * Sort option.
 */
export interface SortOption {
  value: number;
  label: string;
}

/**
 * Filter options response.
 */
export interface FilterOptions {
  languages: LanguageOption[];
  texts: TextOption[];
  tags: TagOption[];
  statuses: StatusOption[];
  sorts: SortOption[];
}

/**
 * Bulk action response.
 */
export interface BulkActionResponse {
  success: boolean;
  count: number;
  message: string;
}

/**
 * Inline edit response.
 */
export interface InlineEditResponse {
  success: boolean;
  value: string;
  error?: string;
}

/**
 * Words API methods.
 */
export const WordsApi = {
  /**
   * Get paginated, filtered word list.
   *
   * @param filters Filter parameters
   * @returns Promise with word list and pagination
   */
  async getList(filters: WordListFilters): Promise<ApiResponse<WordListResponse>> {
    const params: Record<string, string | number | boolean | undefined> = {};

    if (filters.lang !== null && filters.lang !== undefined && filters.lang !== '') {
      params.lang = Number(filters.lang);
    }
    if (filters.text_id !== null && filters.text_id !== undefined && filters.text_id !== '') {
      params.text_id = Number(filters.text_id);
    }
    if (filters.status) {
      params.status = filters.status;
    }
    if (filters.query) {
      params.query = filters.query;
    }
    if (filters.query_mode) {
      params.query_mode = filters.query_mode;
    }
    if (filters.regex_mode) {
      params.regex_mode = filters.regex_mode;
    }
    if (filters.tag1 !== null && filters.tag1 !== undefined && filters.tag1 !== '') {
      params.tag1 = Number(filters.tag1);
    }
    if (filters.tag2 !== null && filters.tag2 !== undefined && filters.tag2 !== '') {
      params.tag2 = Number(filters.tag2);
    }
    if (filters.tag12 !== undefined) {
      params.tag12 = Number(filters.tag12);
    }
    if (filters.sort) {
      params.sort = filters.sort;
    }
    if (filters.page) {
      params.page = filters.page;
    }
    if (filters.per_page) {
      params.per_page = filters.per_page;
    }

    return apiGet<WordListResponse>('/terms/list', params);
  },

  /**
   * Get filter dropdown options.
   *
   * @param langId Optional language ID for filtering texts
   * @returns Promise with filter options
   */
  async getFilterOptions(langId?: number | null): Promise<ApiResponse<FilterOptions>> {
    const params: Record<string, string | number | boolean | undefined> = {};
    if (langId !== null && langId !== undefined) {
      params.lang = langId;
    }
    return apiGet<FilterOptions>('/terms/filter-options', params);
  },

  /**
   * Perform bulk action on selected terms.
   *
   * @param ids    Array of term IDs
   * @param action Action code
   * @param data   Optional data (e.g., tag name)
   * @returns Promise with action result
   */
  async bulkAction(
    ids: number[],
    action: string,
    data?: string
  ): Promise<ApiResponse<BulkActionResponse>> {
    return apiPut<BulkActionResponse>('/terms/bulk-action', { ids, action, data });
  },

  /**
   * Perform action on all terms matching filter.
   *
   * @param filters Filter parameters
   * @param action  Action code
   * @param data    Optional data
   * @returns Promise with action result
   */
  async allAction(
    filters: WordListFilters,
    action: string,
    data?: string
  ): Promise<ApiResponse<BulkActionResponse>> {
    return apiPut<BulkActionResponse>('/terms/all-action', { filters, action, data });
  },

  /**
   * Inline edit translation or romanization.
   *
   * @param termId Term ID
   * @param field  Field name ('translation' or 'romanization')
   * @param value  New value
   * @returns Promise with edit result
   */
  async inlineEdit(
    termId: number,
    field: 'translation' | 'romanization',
    value: string
  ): Promise<ApiResponse<InlineEditResponse>> {
    return apiPut<InlineEditResponse>(`/terms/${termId}/inline-edit`, { field, value });
  }
};

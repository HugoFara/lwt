/**
 * Texts API - Type-safe wrapper for text operations.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { apiGet, apiPost, apiPut, type ApiResponse } from '../core/api_client';

/**
 * Dictionary links for a language.
 */
export interface DictLinks {
  dict1: string;
  dict2: string;
  translator: string;
}

/**
 * Text configuration for reading view.
 */
export interface TextReadingConfig {
  textId: number;
  langId: number;
  title: string;
  audioUri: string | null;
  sourceUri: string | null;
  audioPosition: number;
  rightToLeft: boolean;
  textSize: number;
  dictLinks: DictLinks;
}

/**
 * Word data for client-side rendering.
 */
export interface TextWord {
  position: number;
  sentenceId: number;
  text: string;
  textLc: string;
  hex: string;
  isNotWord: boolean;
  wordCount: number;
  hidden: boolean;
  wordId?: number | null;
  status?: number;
  translation?: string;
  romanization?: string;
  tags?: string;
  // Multiword references (mw2, mw3, etc.)
  [key: `mw${number}`]: string | undefined;
}

/**
 * Response for getting text words.
 */
export interface TextWordsResponse {
  words: TextWord[];
  config: TextReadingConfig;
}

/**
 * Word count statistics for a text.
 */
export interface TextWordCount {
  total: number;
  unique: number;
  unknown: number;
  learning: number;
  learned: number;
  wellKnown: number;
  ignored: number;
}

/**
 * Text statistics response.
 */
export interface TextStatistics {
  wordCounts: TextWordCount;
  statusBreakdown?: Record<number, number>;
}

/**
 * Display mode settings for text reading.
 */
export interface TextDisplayMode {
  annotations: number;
  romanization: boolean;
  translation: boolean;
}

/**
 * Text creation request.
 */
export interface TextCreateRequest {
  title: string;
  langId: number;
  text: string;
  sourceUri?: string;
  audioUri?: string;
  tags?: string[];
}

/**
 * Text creation response.
 */
export interface TextCreateResponse {
  id?: number;
  error?: string;
}

/**
 * Display mode update response.
 */
export interface DisplayModeResponse {
  updated?: boolean;
  error?: string;
}

/**
 * Word data returned from mark-all operations.
 */
export interface MarkedWordData {
  wid: number;
  hex: string;
  term: string;
  status: number;
}

/**
 * Response for mark-all operations.
 */
export interface MarkAllResponse {
  count: number;
  words?: MarkedWordData[];
}

/**
 * Texts API methods.
 */
export const TextsApi = {
  /**
   * Get word count statistics for one or more texts.
   *
   * @param textIds Array of text IDs or comma-separated string
   * @returns Promise with text statistics
   */
  async getStatistics(
    textIds: number[] | string
  ): Promise<ApiResponse<TextStatistics>> {
    const ids = Array.isArray(textIds) ? textIds.join(',') : textIds;
    return apiGet<TextStatistics>('/texts/statistics', { texts_id: ids });
  },

  /**
   * Create a new text.
   *
   * @param data Text creation data
   * @returns Promise with new text ID or error
   */
  async create(data: TextCreateRequest): Promise<ApiResponse<TextCreateResponse>> {
    return apiPost<TextCreateResponse>('/texts', {
      title: data.title,
      lg_id: data.langId,
      text: data.text,
      source_uri: data.sourceUri,
      audio_uri: data.audioUri,
      tags: data.tags
    });
  },

  /**
   * Update display mode for text reading.
   *
   * @param textId Text ID
   * @param mode   Display mode settings
   * @returns Promise with update result
   */
  async setDisplayMode(
    textId: number,
    mode: Partial<TextDisplayMode>
  ): Promise<ApiResponse<DisplayModeResponse>> {
    return apiPut<DisplayModeResponse>(`/texts/${textId}/display-mode`, mode);
  },

  /**
   * Mark all unknown words in a text as well-known.
   *
   * @param textId Text ID
   * @returns Promise with count and words data
   */
  async markAllWellKnown(
    textId: number
  ): Promise<ApiResponse<MarkAllResponse>> {
    return apiPut<MarkAllResponse>(`/texts/${textId}/mark-all-wellknown`, {});
  },

  /**
   * Mark all unknown words in a text as ignored.
   *
   * @param textId Text ID
   * @returns Promise with count and words data
   */
  async markAllIgnored(
    textId: number
  ): Promise<ApiResponse<MarkAllResponse>> {
    return apiPut<MarkAllResponse>(`/texts/${textId}/mark-all-ignored`, {});
  },

  /**
   * Get all words for a text (for client-side rendering).
   *
   * @param textId Text ID
   * @returns Promise with words array and config
   */
  async getWords(textId: number): Promise<ApiResponse<TextWordsResponse>> {
    return apiGet<TextWordsResponse>(`/texts/${textId}/words`);
  }
};

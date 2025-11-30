/**
 * Texts API - Type-safe wrapper for text operations.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { apiGet, apiPost, apiPut, type ApiResponse } from '../core/api_client';

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
   * @returns Promise with operation result
   */
  async markAllWellKnown(
    textId: number
  ): Promise<ApiResponse<{ count: number }>> {
    return apiPost<{ count: number }>(`/texts/${textId}/mark-all-wellknown`, {});
  }
};

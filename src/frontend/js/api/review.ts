/**
 * Review API - Type-safe wrapper for test/review operations.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { apiGet, apiPut, type ApiResponse } from '../core/api_client';

/**
 * Word test data returned from the API.
 */
export interface WordTestData {
  word_id: number | string;
  solution?: string;
  word_text: string;
  group: string;
}

/**
 * Tomorrow count response.
 */
export interface TomorrowCountResponse {
  count: number;
}

/**
 * Review status update request.
 */
export interface ReviewStatusRequest {
  wordId: number;
  status?: number;
  change?: number;
}

/**
 * Review status update response.
 */
export interface ReviewStatusResponse {
  status?: number;
  controls?: string;
  error?: string;
}

/**
 * Parameters for getting next word to test.
 */
export interface NextWordParams {
  testKey: string;
  selection: string;
  wordMode: boolean;
  lgId: number;
  wordRegex: string;
  type: number;
}

/**
 * Review API methods.
 */
export const ReviewApi = {
  /**
   * Get the next word to test.
   *
   * @param params Test parameters
   * @returns Promise with word test data
   */
  async getNextWord(params: NextWordParams): Promise<ApiResponse<WordTestData>> {
    return apiGet<WordTestData>('/review/next-word', {
      test_key: params.testKey,
      selection: params.selection,
      word_mode: params.wordMode,
      lg_id: params.lgId,
      word_regex: params.wordRegex,
      type: params.type
    });
  },

  /**
   * Get count of words due for review tomorrow.
   *
   * @param testKey   Test session key
   * @param selection Word selection criteria
   * @returns Promise with count
   */
  async getTomorrowCount(
    testKey: string,
    selection: string
  ): Promise<ApiResponse<TomorrowCountResponse>> {
    return apiGet<TomorrowCountResponse>('/review/tomorrow-count', {
      test_key: testKey,
      selection
    });
  },

  /**
   * Update word status during review.
   *
   * @param wordId Word ID
   * @param status New status (1-5, 98, 99) or undefined for increment
   * @param change Status change amount (+1 or -1)
   * @returns Promise with update result
   */
  async updateStatus(
    wordId: number,
    status?: number,
    change?: number
  ): Promise<ApiResponse<ReviewStatusResponse>> {
    return apiPut<ReviewStatusResponse>('/review/status', {
      word_id: wordId,
      status,
      change
    });
  }
};

/**
 * Review API - Type-safe wrapper for test/review operations.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { apiGet, apiPut, type ApiResponse } from '@shared/api/client';

/**
 * Word test data returned from the API.
 */
export interface WordTestData {
  term_id: number | string;
  solution?: string;
  term_text: string;
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
 * Language settings for a test.
 */
export interface TestLangSettings {
  name: string;
  dict1Uri: string;
  dict2Uri: string;
  translateUri: string;
  textSize: number;
  rtl: boolean;
  langCode: string;
}

/**
 * Test configuration response from server.
 */
export interface TestConfigResponse {
  testKey: string;
  selection: string;
  testType: number;
  isTableMode: boolean;
  wordMode: boolean;
  langId: number;
  wordRegex: string;
  langSettings: TestLangSettings;
  progress: {
    total: number;
    remaining: number;
    wrong: number;
    correct: number;
  };
  timer: {
    startTime: number;
    serverTime: number;
  };
  title: string;
  property: string;
}

/**
 * Word data for table test.
 */
export interface TableTestWord {
  id: number;
  text: string;
  translation: string;
  romanization: string;
  sentence: string;
  sentenceHtml: string;
  status: number;
  score: number;
}

/**
 * Table test words response.
 */
export interface TableWordsResponse {
  words: TableTestWord[];
  langSettings: TestLangSettings;
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
      language_id: params.lgId,
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
    termId: number,
    status?: number,
    change?: number
  ): Promise<ApiResponse<ReviewStatusResponse>> {
    return apiPut<ReviewStatusResponse>('/review/status', {
      term_id: termId,
      status,
      change
    });
  },

  /**
   * Get test configuration.
   *
   * @param params Test parameters (lang, text, or selection)
   * @returns Promise with test configuration
   */
  async getTestConfig(params: {
    lang?: number;
    text?: number;
    selection?: number;
  }): Promise<ApiResponse<TestConfigResponse>> {
    return apiGet<TestConfigResponse>('/review/config', params);
  },

  /**
   * Get all words for table test mode.
   *
   * @param testKey   Test session key
   * @param selection Word selection criteria
   * @returns Promise with table words
   */
  async getTableWords(
    testKey: string,
    selection: string
  ): Promise<ApiResponse<TableWordsResponse>> {
    return apiGet<TableWordsResponse>('/review/table-words', {
      test_key: testKey,
      selection
    });
  }
};

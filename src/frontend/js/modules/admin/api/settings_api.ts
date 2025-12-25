/**
 * Settings API - Type-safe wrapper for settings operations.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { apiGet, apiPostForm, type ApiResponse } from '../core/api_client';

/**
 * Response for saving a setting.
 */
export interface SettingSaveResponse {
  message?: string;
  error?: string;
}

/**
 * Response for getting theme path.
 */
export interface ThemePathResponse {
  theme_path: string;
}

/**
 * Settings API methods.
 */
export const SettingsApi = {
  /**
   * Save a setting to the database.
   *
   * @param key   Setting name
   * @param value Setting value
   * @returns Promise with save result
   */
  async save(key: string, value: string): Promise<ApiResponse<SettingSaveResponse>> {
    return apiPostForm<SettingSaveResponse>('/settings', { key, value });
  },

  /**
   * Get the file path using the current theme.
   *
   * @param path Relative filepath
   * @returns Promise with theme-resolved path
   */
  async getThemePath(path: string): Promise<ApiResponse<ThemePathResponse>> {
    return apiGet<ThemePathResponse>('/settings/theme-path', { path });
  }
};

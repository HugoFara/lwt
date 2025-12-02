/**
 * Tests for api/settings.ts - Settings API operations
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { SettingsApi } from '../../../src/frontend/js/api/settings';
import * as apiClient from '../../../src/frontend/js/core/api_client';

// Mock the api_client module
vi.mock('../../../src/frontend/js/core/api_client', () => ({
  apiGet: vi.fn(),
  apiPostForm: vi.fn(),
}));

describe('api/settings.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  // ===========================================================================
  // save Tests
  // ===========================================================================

  describe('SettingsApi.save', () => {
    it('calls apiPostForm with key and value', async () => {
      const mockResponse = {
        data: { message: 'Setting saved' },
        error: undefined,
      };
      vi.mocked(apiClient.apiPostForm).mockResolvedValue(mockResponse);

      const result = await SettingsApi.save('theme', 'dark');

      expect(apiClient.apiPostForm).toHaveBeenCalledWith('/settings', {
        key: 'theme',
        value: 'dark',
      });
      expect(result.data?.message).toBe('Setting saved');
    });

    it('handles empty value', async () => {
      const mockResponse = { data: { message: 'OK' }, error: undefined };
      vi.mocked(apiClient.apiPostForm).mockResolvedValue(mockResponse);

      await SettingsApi.save('reset_setting', '');

      expect(apiClient.apiPostForm).toHaveBeenCalledWith('/settings', {
        key: 'reset_setting',
        value: '',
      });
    });

    it('handles numeric string value', async () => {
      const mockResponse = { data: { message: 'OK' }, error: undefined };
      vi.mocked(apiClient.apiPostForm).mockResolvedValue(mockResponse);

      await SettingsApi.save('items_per_page', '50');

      expect(apiClient.apiPostForm).toHaveBeenCalledWith('/settings', {
        key: 'items_per_page',
        value: '50',
      });
    });

    it('handles error response', async () => {
      const mockResponse = {
        data: undefined,
        error: 'Invalid setting key',
      };
      vi.mocked(apiClient.apiPostForm).mockResolvedValue(mockResponse);

      const result = await SettingsApi.save('invalid_key', 'value');

      expect(result.error).toBe('Invalid setting key');
    });

    it('handles special characters in value', async () => {
      const mockResponse = { data: { message: 'OK' }, error: undefined };
      vi.mocked(apiClient.apiPostForm).mockResolvedValue(mockResponse);

      await SettingsApi.save('custom_text', 'Hello & <World>');

      expect(apiClient.apiPostForm).toHaveBeenCalledWith('/settings', {
        key: 'custom_text',
        value: 'Hello & <World>',
      });
    });
  });

  // ===========================================================================
  // getThemePath Tests
  // ===========================================================================

  describe('SettingsApi.getThemePath', () => {
    it('calls apiGet with path parameter', async () => {
      const mockResponse = {
        data: { theme_path: '/assets/themes/dark/styles.css' },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await SettingsApi.getThemePath('styles.css');

      expect(apiClient.apiGet).toHaveBeenCalledWith('/settings/theme-path', {
        path: 'styles.css',
      });
      expect(result.data?.theme_path).toBe('/assets/themes/dark/styles.css');
    });

    it('handles relative path', async () => {
      const mockResponse = {
        data: { theme_path: '/assets/themes/default/images/logo.png' },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await SettingsApi.getThemePath('images/logo.png');

      expect(apiClient.apiGet).toHaveBeenCalledWith('/settings/theme-path', {
        path: 'images/logo.png',
      });
    });

    it('handles error response', async () => {
      const mockResponse = {
        data: undefined,
        error: 'Theme path not found',
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      const result = await SettingsApi.getThemePath('nonexistent.css');

      expect(result.error).toBe('Theme path not found');
    });

    it('handles empty path', async () => {
      const mockResponse = {
        data: { theme_path: '/assets/themes/default/' },
        error: undefined,
      };
      vi.mocked(apiClient.apiGet).mockResolvedValue(mockResponse);

      await SettingsApi.getThemePath('');

      expect(apiClient.apiGet).toHaveBeenCalledWith('/settings/theme-path', {
        path: '',
      });
    });
  });
});

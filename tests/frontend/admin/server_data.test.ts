/**
 * Tests for server_data.ts - Server Data admin page functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  fetchApiVersion,
  initServerDataAlpine,
  serverDataApp
} from '../../../src/frontend/js/modules/admin/pages/server_data';

describe('server_data.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // fetchApiVersion (Legacy) Tests
  // ===========================================================================

  describe('fetchApiVersion (legacy)', () => {
    it('makes GET request to api.php/v1/version', async () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      const fetchSpy = vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ version: '1.0.0', release_date: '2024-01-01' })
      } as Response);

      fetchApiVersion();
      await vi.waitFor(() => expect(fetchSpy).toHaveBeenCalled());

      expect(fetchSpy).toHaveBeenCalledWith('/api/v1/version');
    });

    it('displays version on successful response', async () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ version: '2.5.0', release_date: '2024-03-15' })
      } as Response);

      fetchApiVersion();

      await vi.waitFor(() => {
        expect(document.getElementById('rest-api-version')?.textContent).toBe('2.5.0');
      });
      expect(document.getElementById('rest-api-release-date')?.textContent).toBe('2024-03-15');
    });

    it('displays error message when API returns error', async () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ error: 'Connection failed' })
      } as Response);

      fetchApiVersion();

      await vi.waitFor(() => {
        // New implementation shows the error message directly
        expect(document.getElementById('rest-api-version')?.textContent).toBe('Connection failed');
      });
      expect(document.getElementById('rest-api-release-date')?.textContent).toBe('');
    });

    it('handles empty version response', async () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({})
      } as Response);

      fetchApiVersion();

      await vi.waitFor(() => {
        expect(document.getElementById('rest-api-version')?.textContent).toBe('');
      });
      expect(document.getElementById('rest-api-release-date')?.textContent).toBe('');
    });

    it('handles null values in response', async () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ version: null, release_date: null })
      } as Response);

      fetchApiVersion();

      await vi.waitFor(() => {
        // Null values fallback to empty string
        expect(document.getElementById('rest-api-version')?.textContent).toBe('');
      });
      expect(document.getElementById('rest-api-release-date')?.textContent).toBe('');
    });

    it('handles missing elements gracefully', async () => {
      document.body.innerHTML = '';

      vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ version: '1.0.0', release_date: '2024-01-01' })
      } as Response);

      // Should not throw when elements don't exist
      expect(() => fetchApiVersion()).not.toThrow();
    });
  });

  // ===========================================================================
  // serverDataApp (Alpine.js Component) Tests
  // ===========================================================================

  describe('serverDataApp', () => {
    it('initializes with loading state', () => {
      const app = serverDataApp();

      expect(app.apiVersion).toBe('');
      expect(app.apiReleaseDate).toBe('');
      expect(app.isLoading).toBe(true);
      expect(app.error).toBeNull();
    });

    it('fetches version on successful response', async () => {
      vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ version: '2.5.0', release_date: '2024-03-15' })
      } as Response);

      const app = serverDataApp();
      await app.fetchApiVersion();

      expect(app.apiVersion).toBe('2.5.0');
      expect(app.apiReleaseDate).toBe('2024-03-15');
      expect(app.isLoading).toBe(false);
      expect(app.error).toBeNull();
    });

    it('sets error on API error response', async () => {
      vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ error: 'Server error' })
      } as Response);

      const app = serverDataApp();
      await app.fetchApiVersion();

      expect(app.error).toBe('Server error');
      expect(app.apiVersion).toBe('');
      expect(app.isLoading).toBe(false);
    });

    it('sets error on fetch rejection', async () => {
      vi.spyOn(global, 'fetch').mockRejectedValue(new Error('Network error'));

      const app = serverDataApp();
      await app.fetchApiVersion();

      expect(app.error).toBe('Network error');
      expect(app.isLoading).toBe(false);
    });

    it('prefers error field even if version is also present', async () => {
      vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ version: '1.0.0', error: 'Something went wrong' })
      } as Response);

      const app = serverDataApp();
      await app.fetchApiVersion();

      // Error takes precedence when both are present
      expect(app.error).toBe('Something went wrong');
      expect(app.apiVersion).toBe('');
    });
  });

  // ===========================================================================
  // initServerDataAlpine Tests
  // ===========================================================================

  describe('initServerDataAlpine', () => {
    it('does not throw when called', () => {
      expect(() => initServerDataAlpine()).not.toThrow();
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles version with special characters', async () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ version: 'v3.0.0-beta.1', release_date: '2024-01-01T00:00:00Z' })
      } as Response);

      fetchApiVersion();

      await vi.waitFor(() => {
        expect(document.getElementById('rest-api-version')?.textContent).toBe('v3.0.0-beta.1');
      });
      expect(document.getElementById('rest-api-release-date')?.textContent).toBe('2024-01-01T00:00:00Z');
    });

    it('clears release date on error', async () => {
      document.body.innerHTML = `
        <span id="rest-api-version">old version</span>
        <span id="rest-api-release-date">old date</span>
      `;

      vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ error: 'Server error' })
      } as Response);

      fetchApiVersion();

      await vi.waitFor(() => {
        // Error message is shown
        expect(document.getElementById('rest-api-version')?.textContent).toBe('Server error');
      });
      // Release date is emptied
      expect(document.getElementById('rest-api-release-date')?.textContent).toBe('');
    });

    it('handles error with empty message', async () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn(global, 'fetch').mockResolvedValue({
        json: () => Promise.resolve({ error: '' })
      } as Response);

      fetchApiVersion();

      await vi.waitFor(() => {
        // Empty error string should be falsy, so version branch runs
        expect(document.getElementById('rest-api-version')?.textContent).toBe('');
      });
    });

    it('handles fetch rejection (legacy)', async () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn(global, 'fetch').mockRejectedValue(new Error('Network error'));

      fetchApiVersion();

      await vi.waitFor(() => {
        expect(document.getElementById('rest-api-version')?.textContent).toBe('Network error');
      });
    });
  });
});

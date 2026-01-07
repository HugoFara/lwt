/**
 * Tests for server_data.ts - Server Data Alpine.js component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  serverDataApp
} from '../../../src/frontend/js/modules/admin/pages/server_data';

describe('server_data.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    global.fetch = vi.fn();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // serverDataApp Tests
  // ===========================================================================

  describe('serverDataApp', () => {
    it('initializes with default values', () => {
      const app = serverDataApp();

      expect(app.apiVersion).toBe('');
      expect(app.apiReleaseDate).toBe('');
      expect(app.isLoading).toBe(true);
      expect(app.error).toBeNull();
    });

    describe('fetchApiVersion', () => {
      it('fetches and displays API version', async () => {
        (global.fetch as ReturnType<typeof vi.fn>).mockResolvedValue({
          ok: true,
          json: () => Promise.resolve({
            version: '1.2.3',
            release_date: '2024-01-15'
          })
        });

        const app = serverDataApp();
        await app.fetchApiVersion();

        expect(app.apiVersion).toBe('1.2.3');
        expect(app.apiReleaseDate).toBe('2024-01-15');
        expect(app.isLoading).toBe(false);
        expect(app.error).toBeNull();
      });

      it('handles API error response', async () => {
        (global.fetch as ReturnType<typeof vi.fn>).mockResolvedValue({
          ok: true,
          json: () => Promise.resolve({
            error: 'API unavailable'
          })
        });

        const app = serverDataApp();
        await app.fetchApiVersion();

        expect(app.apiVersion).toBe('');
        expect(app.error).toBe('API unavailable');
        expect(app.isLoading).toBe(false);
      });

      it('handles fetch rejection', async () => {
        (global.fetch as ReturnType<typeof vi.fn>).mockRejectedValue(
          new Error('Network error')
        );

        const app = serverDataApp();
        await app.fetchApiVersion();

        expect(app.error).toBe('Network error');
        expect(app.isLoading).toBe(false);
      });

      it('handles missing version in response', async () => {
        (global.fetch as ReturnType<typeof vi.fn>).mockResolvedValue({
          ok: true,
          json: () => Promise.resolve({})
        });

        const app = serverDataApp();
        await app.fetchApiVersion();

        expect(app.apiVersion).toBe('');
        expect(app.apiReleaseDate).toBe('');
        expect(app.isLoading).toBe(false);
      });
    });

    describe('init', () => {
      it('calls fetchApiVersion on init', () => {
        const app = serverDataApp();
        const fetchSpy = vi.spyOn(app, 'fetchApiVersion').mockResolvedValue();

        app.init();

        expect(fetchSpy).toHaveBeenCalled();
      });
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('clears release date on error', async () => {
      (global.fetch as ReturnType<typeof vi.fn>).mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ error: 'Some error' })
      });

      const app = serverDataApp();
      app.apiReleaseDate = 'old date';
      await app.fetchApiVersion();

      // Version and date remain empty when there's an error
      expect(app.apiVersion).toBe('');
      expect(app.error).toBe('Some error');
    });

    it('handles error with empty message', async () => {
      (global.fetch as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error('')
      );

      const app = serverDataApp();
      await app.fetchApiVersion();

      expect(app.error).toBe('');
      expect(app.isLoading).toBe(false);
    });
  });
});

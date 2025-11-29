/**
 * Tests for server_data.ts - Server Data admin page functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import { fetchApiVersion, initServerData } from '../../../src/frontend/js/admin/server_data';

// Make jQuery available globally
(global as any).$ = $;
(global as any).jQuery = $;

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
  // fetchApiVersion Tests
  // ===========================================================================

  describe('fetchApiVersion', () => {
    it('makes GET request to api.php/v1/version', () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(
        ((_url: string, _data: object, callback: Function) => {
          callback({ version: '1.0.0', release_date: '2024-01-01' });
          return {} as any;
        }) as any
      );

      fetchApiVersion();

      expect(getJSONSpy).toHaveBeenCalledWith(
        'api.php/v1/version',
        {},
        expect.any(Function)
      );
    });

    it('displays version on successful response', () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn($, 'getJSON').mockImplementation(
        ((_url: string, _data: object, callback: Function) => {
          callback({ version: '2.5.0', release_date: '2024-03-15' });
          return {} as any;
        }) as any
      );

      fetchApiVersion();

      expect($('#rest-api-version').text()).toBe('2.5.0');
      expect($('#rest-api-release-date').text()).toBe('2024-03-15');
    });

    it('displays error message when API returns error', () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn($, 'getJSON').mockImplementation(
        ((_url: string, _data: object, callback: Function) => {
          callback({ error: 'Connection failed' });
          return {} as any;
        }) as any
      );

      fetchApiVersion();

      expect($('#rest-api-version').text()).toContain('Error while getting data from the REST API!');
      expect($('#rest-api-version').text()).toContain('Connection failed');
      expect($('#rest-api-release-date').text()).toBe('');
    });

    it('handles empty version response', () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn($, 'getJSON').mockImplementation(
        ((_url: string, _data: object, callback: Function) => {
          callback({});
          return {} as any;
        }) as any
      );

      fetchApiVersion();

      expect($('#rest-api-version').text()).toBe('');
      expect($('#rest-api-release-date').text()).toBe('');
    });

    it('handles null values in response', () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn($, 'getJSON').mockImplementation(
        ((_url: string, _data: object, callback: Function) => {
          callback({ version: null, release_date: null });
          return {} as any;
        }) as any
      );

      fetchApiVersion();

      // Null values fallback to empty string
      expect($('#rest-api-version').text()).toBe('');
      expect($('#rest-api-release-date').text()).toBe('');
    });

    it('handles missing elements gracefully', () => {
      document.body.innerHTML = '';

      vi.spyOn($, 'getJSON').mockImplementation(
        ((_url: string, _data: object, callback: Function) => {
          callback({ version: '1.0.0', release_date: '2024-01-01' });
          return {} as any;
        }) as any
      );

      // Should not throw when elements don't exist
      expect(() => fetchApiVersion()).not.toThrow();
    });
  });

  // ===========================================================================
  // initServerData Tests
  // ===========================================================================

  describe('initServerData', () => {
    it('calls fetchApiVersion when rest-api-version element exists', () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(() => ({} as any));

      initServerData();

      expect(getJSONSpy).toHaveBeenCalled();
    });

    it('does not call fetchApiVersion when rest-api-version element is missing', () => {
      document.body.innerHTML = '<div>Some other content</div>';

      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(() => ({} as any));

      initServerData();

      expect(getJSONSpy).not.toHaveBeenCalled();
    });

    it('does nothing on empty page', () => {
      document.body.innerHTML = '';

      const getJSONSpy = vi.spyOn($, 'getJSON').mockImplementation(() => ({} as any));

      initServerData();

      expect(getJSONSpy).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles version with special characters', () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn($, 'getJSON').mockImplementation(
        ((_url: string, _data: object, callback: Function) => {
          callback({ version: 'v3.0.0-beta.1', release_date: '2024-01-01T00:00:00Z' });
          return {} as any;
        }) as any
      );

      fetchApiVersion();

      expect($('#rest-api-version').text()).toBe('v3.0.0-beta.1');
      expect($('#rest-api-release-date').text()).toBe('2024-01-01T00:00:00Z');
    });

    it('clears release date on error', () => {
      document.body.innerHTML = `
        <span id="rest-api-version">old version</span>
        <span id="rest-api-release-date">old date</span>
      `;

      vi.spyOn($, 'getJSON').mockImplementation(
        ((_url: string, _data: object, callback: Function) => {
          callback({ error: 'Server error' });
          return {} as any;
        }) as any
      );

      fetchApiVersion();

      // Error message replaces version
      expect($('#rest-api-version').text()).toContain('Error');
      // Release date is emptied
      expect($('#rest-api-release-date').text()).toBe('');
    });

    it('handles error with empty message', () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn($, 'getJSON').mockImplementation(
        ((_url: string, _data: object, callback: Function) => {
          callback({ error: '' });
          return {} as any;
        }) as any
      );

      fetchApiVersion();

      // Empty error string should be falsy, so version branch runs
      expect($('#rest-api-version').text()).toBe('');
    });

    it('prefers error field even if version is also present', () => {
      document.body.innerHTML = `
        <span id="rest-api-version"></span>
        <span id="rest-api-release-date"></span>
      `;

      vi.spyOn($, 'getJSON').mockImplementation(
        ((_url: string, _data: object, callback: Function) => {
          callback({ version: '1.0.0', error: 'Something went wrong' });
          return {} as any;
        }) as any
      );

      fetchApiVersion();

      // Error takes precedence when both are present
      expect($('#rest-api-version').text()).toContain('Error');
    });
  });
});

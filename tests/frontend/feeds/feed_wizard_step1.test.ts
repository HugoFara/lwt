/**
 * Tests for feed_wizard_step1.ts - Feed wizard step 1 (URL entry)
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock Alpine.js
vi.mock('alpinejs', () => ({
  default: {
    data: vi.fn()
  }
}));

// Create shared mock store
const mockStore = {
  configure: vi.fn()
};

// Mock feed_wizard_store
vi.mock('../../../src/frontend/js/modules/feed/stores/feed_wizard_store', () => ({
  getFeedWizardStore: vi.fn(() => mockStore)
}));

import Alpine from 'alpinejs';
import { feedWizardStep1Data, initFeedWizardStep1Alpine, type Step1Config } from '../../../src/frontend/js/modules/feed/components/feed_wizard_step1';

describe('feed_wizard_step1.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    mockStore.configure.mockClear();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // feedWizardStep1Data Factory Tests
  // ===========================================================================

  describe('feedWizardStep1Data', () => {
    it('creates component with default values when no config', () => {
      const component = feedWizardStep1Data();

      expect(component.config).toBeDefined();
      expect(component.config.rssUrl).toBe('');
      expect(component.config.hasError).toBe(false);
      expect(component.config.editFeedId).toBe(null);
      expect(component.rssUrl).toBe('');
    });

    it('reads config from script tag', () => {
      const config: Step1Config = {
        rssUrl: 'https://example.com/feed.xml',
        hasError: true,
        editFeedId: 5
      };
      document.body.innerHTML = `
        <script id="wizard-step1-config" type="application/json">
          ${JSON.stringify(config)}
        </script>
      `;

      const component = feedWizardStep1Data();

      expect(component.config.rssUrl).toBe('https://example.com/feed.xml');
      expect(component.config.hasError).toBe(true);
      expect(component.config.editFeedId).toBe(5);
      expect(component.rssUrl).toBe('https://example.com/feed.xml');
    });

    it('handles invalid JSON config gracefully', () => {
      document.body.innerHTML = `
        <script id="wizard-step1-config" type="application/json">
          { invalid json }
        </script>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      const component = feedWizardStep1Data();

      expect(component.config.rssUrl).toBe('');
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // isValidUrl Tests
  // ===========================================================================

  describe('isValidUrl', () => {
    it('returns false for empty URL', () => {
      const component = feedWizardStep1Data();
      component.rssUrl = '';

      expect(component.isValidUrl).toBe(false);
    });

    it('returns true for valid HTTP URL', () => {
      const component = feedWizardStep1Data();
      component.rssUrl = 'http://example.com/feed.xml';

      expect(component.isValidUrl).toBe(true);
    });

    it('returns true for valid HTTPS URL', () => {
      const component = feedWizardStep1Data();
      component.rssUrl = 'https://example.com/feed.xml';

      expect(component.isValidUrl).toBe(true);
    });

    it('returns false for invalid URL', () => {
      const component = feedWizardStep1Data();
      component.rssUrl = 'not a url';

      expect(component.isValidUrl).toBe(false);
    });

    it('returns false for URL without protocol', () => {
      const component = feedWizardStep1Data();
      component.rssUrl = 'example.com/feed.xml';

      expect(component.isValidUrl).toBe(false);
    });
  });

  // ===========================================================================
  // init() Tests
  // ===========================================================================

  describe('init()', () => {
    it('configures store with step 1', () => {
      const component = feedWizardStep1Data();

      component.init();

      expect(mockStore.configure).toHaveBeenCalledWith({
        step: 1,
        rssUrl: '',
        editFeedId: null
      });
    });

    it('configures store with config values', () => {
      const config: Step1Config = {
        rssUrl: 'https://test.com/feed',
        hasError: false,
        editFeedId: 10
      };
      document.body.innerHTML = `
        <script id="wizard-step1-config" type="application/json">
          ${JSON.stringify(config)}
        </script>
      `;

      const component = feedWizardStep1Data();
      component.init();

      expect(mockStore.configure).toHaveBeenCalledWith({
        step: 1,
        rssUrl: 'https://test.com/feed',
        editFeedId: 10
      });
    });
  });

  // ===========================================================================
  // cancel() Tests
  // ===========================================================================

  describe('cancel()', () => {
    it('navigates to feeds edit page with del_wiz', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      const component = feedWizardStep1Data();
      component.cancel();

      expect(window.location.href).toBe('/feeds/edit?del_wiz=1');

      window.location = originalLocation;
    });
  });

  // ===========================================================================
  // store getter Tests
  // ===========================================================================

  describe('store getter', () => {
    it('returns store from getFeedWizardStore', () => {
      const component = feedWizardStep1Data();

      expect(component.store).toBe(mockStore);
    });
  });

  // ===========================================================================
  // initFeedWizardStep1Alpine Tests
  // ===========================================================================

  describe('initFeedWizardStep1Alpine', () => {
    it('registers feedWizardStep1 component with Alpine', () => {
      initFeedWizardStep1Alpine();

      expect(Alpine.data).toHaveBeenCalledWith('feedWizardStep1', feedWizardStep1Data);
    });
  });

  // ===========================================================================
  // Global Window Exposure Tests
  // ===========================================================================

  describe('global window exposure', () => {
    it('exposes feedWizardStep1Data on window', () => {
      expect(typeof window.feedWizardStep1Data).toBe('function');
    });
  });
});

/**
 * Tests for feed_wizard_step4.ts - Feed wizard step 4 (final configuration)
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock Alpine.js
vi.mock('alpinejs', () => ({
  default: {
    data: vi.fn()
  }
}));

vi.mock('../../../src/frontend/js/shared/api/client', () => ({
  apiGet: vi.fn(),
  apiPost: vi.fn(),
  apiPut: vi.fn(),
  apiDelete: vi.fn()
}));

// Create shared mock store
const mockStore = {
  configure: vi.fn(),
  reset: vi.fn()
};

// Mock feed_wizard_store
vi.mock('../../../src/frontend/js/modules/feed/stores/feed_wizard_store', () => ({
  getFeedWizardStore: vi.fn(() => mockStore)
}));

import Alpine from 'alpinejs';
import { feedWizardStep4Data, initFeedWizardStep4Alpine, type Step4Config } from '../../../src/frontend/js/modules/feed/components/feed_wizard_step4';
import { apiPost, apiPut } from '../../../src/frontend/js/shared/api/client';

describe('feed_wizard_step4.ts', () => {
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
  // feedWizardStep4Data Factory Tests
  // ===========================================================================

  describe('feedWizardStep4Data', () => {
    it('creates component with default values when no config', () => {
      const component = feedWizardStep4Data();

      expect(component.config).toBeDefined();
      expect(component.languageId).toBe('');
      expect(component.feedName).toBe('');
      expect(component.sourceUri).toBe('');
      expect(component.languages).toEqual([]);
    });

    it('reads config from script tag', () => {
      const config: Step4Config = {
        editFeedId: 5,
        feedTitle: 'Test Feed',
        rssUrl: 'https://example.com/feed.xml',
        articleSection: '//article',
        filterTags: '',
        feedText: 'body',
        langId: 2,
        options: { editText: true },
        languages: [{ id: 1, name: 'English' }, { id: 2, name: 'German' }]
      };
      document.body.innerHTML = `
        <script id="wizard-step4-config" type="application/json">
          ${JSON.stringify(config)}
        </script>
      `;

      const component = feedWizardStep4Data();

      expect(component.feedName).toBe('Test Feed');
      expect(component.sourceUri).toBe('https://example.com/feed.xml');
      expect(component.languageId).toBe('2');
      expect(component.languages).toHaveLength(2);
      expect(component.editText).toBe(true);
    });

    it('handles invalid JSON config gracefully', () => {
      document.body.innerHTML = `
        <script id="wizard-step4-config" type="application/json">
          { invalid json }
        </script>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      const component = feedWizardStep4Data();

      expect(component.feedName).toBe('');
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // isEditMode and submitLabel Tests
  // ===========================================================================

  describe('isEditMode and submitLabel', () => {
    it('returns false for new feed', () => {
      const component = feedWizardStep4Data();

      expect(component.isEditMode).toBe(false);
      expect(component.submitLabel).toBe('Save');
    });

    it('returns true when editFeedId is set', () => {
      const config: Step4Config = {
        editFeedId: 10,
        feedTitle: '',
        rssUrl: '',
        articleSection: '',
        filterTags: '',
        feedText: '',
        langId: null,
        options: {},
        languages: []
      };
      document.body.innerHTML = `
        <script id="wizard-step4-config" type="application/json">
          ${JSON.stringify(config)}
        </script>
      `;

      const component = feedWizardStep4Data();

      expect(component.isEditMode).toBe(true);
      expect(component.submitLabel).toBe('Update');
    });
  });

  // ===========================================================================
  // init() Tests
  // ===========================================================================

  describe('init()', () => {
    it('configures store with step 4', () => {
      const component = feedWizardStep4Data();

      component.init();

      expect(mockStore.configure).toHaveBeenCalledWith(
        expect.objectContaining({
          step: 4
        })
      );
    });
  });

  // ===========================================================================
  // toggleOption() Tests
  // ===========================================================================

  describe('toggleOption()', () => {
    it('handles autoUpdate option', () => {
      const component = feedWizardStep4Data();

      expect(() => component.toggleOption('autoUpdate')).not.toThrow();
    });

    it('handles maxLinks option', () => {
      const component = feedWizardStep4Data();

      expect(() => component.toggleOption('maxLinks')).not.toThrow();
    });

    it('handles maxTexts option', () => {
      const component = feedWizardStep4Data();

      expect(() => component.toggleOption('maxTexts')).not.toThrow();
    });

    it('handles charset option', () => {
      const component = feedWizardStep4Data();

      expect(() => component.toggleOption('charset')).not.toThrow();
    });

    it('handles tag option', () => {
      const component = feedWizardStep4Data();

      expect(() => component.toggleOption('tag')).not.toThrow();
    });
  });

  // ===========================================================================
  // buildOptionsString() Tests
  // ===========================================================================

  describe('buildOptionsString()', () => {
    it('returns empty string when no options enabled', () => {
      const component = feedWizardStep4Data();

      expect(component.buildOptionsString()).toBe('');
    });

    it('includes edit_text when enabled', () => {
      const component = feedWizardStep4Data();
      component.editText = true;

      expect(component.buildOptionsString()).toContain('edit_text=1');
    });

    it('includes autoupdate with interval and unit', () => {
      const component = feedWizardStep4Data();
      component.autoUpdateEnabled = true;
      component.autoUpdateInterval = '24';
      component.autoUpdateUnit = 'h';

      expect(component.buildOptionsString()).toContain('autoupdate=24h');
    });

    it('includes max_links when enabled', () => {
      const component = feedWizardStep4Data();
      component.maxLinksEnabled = true;
      component.maxLinks = '50';

      expect(component.buildOptionsString()).toContain('max_links=50');
    });

    it('includes max_texts when enabled', () => {
      const component = feedWizardStep4Data();
      component.maxTextsEnabled = true;
      component.maxTexts = '100';

      expect(component.buildOptionsString()).toContain('max_texts=100');
    });

    it('includes charset when enabled', () => {
      const component = feedWizardStep4Data();
      component.charsetEnabled = true;
      component.charset = 'UTF-8';

      expect(component.buildOptionsString()).toContain('charset=UTF-8');
    });

    it('includes tag when enabled', () => {
      const component = feedWizardStep4Data();
      component.tagEnabled = true;
      component.tag = 'news';

      expect(component.buildOptionsString()).toContain('tag=news');
    });

    it('builds multiple options comma-separated', () => {
      const component = feedWizardStep4Data();
      component.editText = true;
      component.maxLinksEnabled = true;
      component.maxLinks = '25';

      const result = component.buildOptionsString();
      expect(result).toContain('edit_text=1');
      expect(result).toContain('max_links=25');
      expect(result).toContain(',');
    });
  });

  // ===========================================================================
  // goBack() Tests
  // ===========================================================================

  describe('goBack()', () => {
    it('navigates to step 3 with options', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      const component = feedWizardStep4Data();
      component.languageId = '2';
      component.feedName = 'Test';
      component.goBack();

      expect(window.location.href).toContain('/feeds/wizard?step=3');
      expect(window.location.href).toContain('NfLgID=2');
      expect(window.location.href).toContain('NfName=Test');

      window.location = originalLocation;
    });
  });

  // ===========================================================================
  // handleSubmit() Tests
  // ===========================================================================

  describe('handleSubmit()', () => {
    /**
     * A submit event whose default the component is expected to prevent.
     */
    function submitEvent(): Event {
      return { preventDefault: vi.fn(), target: document.createElement('form') } as unknown as Event;
    }

    /**
     * Put a step 4 config on the page.
     */
    function withConfig(overrides: Partial<Step4Config> = {}): void {
      const config: Step4Config = {
        editFeedId: null,
        feedTitle: 'Tagesschau',
        rssUrl: 'https://example.com/rss',
        articleSection: '//div',
        filterTags: '',
        feedText: '',
        langId: 2,
        options: {},
        languages: [],
        ...overrides
      };
      document.body.innerHTML = `
        <script id="wizard-step4-config" type="application/json">
          ${JSON.stringify(config)}
        </script>
      `;
    }

    /**
     * The wizard's last step used to post to /feeds/edit, which has 302'd to
     * the manager SPA since the server-rendered feeds list was retired — so
     * finishing the wizard saved nothing at all. It creates through the API
     * now.
     */
    it('creates the feed through POST', async () => {
      vi.mocked(apiPost).mockResolvedValue({ data: { success: true, feed: { id: 7 } } });
      withConfig();

      const component = feedWizardStep4Data();
      component.editText = true;
      component.handleSubmit(submitEvent());
      await vi.waitFor(() => expect(apiPost).toHaveBeenCalled());

      expect(apiPost).toHaveBeenCalledWith('/feeds', expect.objectContaining({
        langId: 2,
        name: 'Tagesschau',
        sourceUri: 'https://example.com/rss',
        articleSectionTags: '//div'
      }));
      const payload = vi.mocked(apiPost).mock.calls[0][1] as { options: string };
      expect(payload.options).toContain('edit_text=1');
    });

    it('updates through PUT in edit mode', async () => {
      vi.mocked(apiPut).mockResolvedValue({ data: { success: true, feed: { id: 5 } } });
      withConfig({ editFeedId: 5 });

      const component = feedWizardStep4Data();
      component.handleSubmit(submitEvent());
      await vi.waitFor(() => expect(apiPut).toHaveBeenCalled());

      expect(apiPut).toHaveBeenCalledWith('/feeds/5', expect.any(Object));
      expect(apiPost).not.toHaveBeenCalled();
    });

    it('prevents the browser from posting the form', () => {
      withConfig();
      const event = submitEvent();

      feedWizardStep4Data().handleSubmit(event);

      expect(event.preventDefault).toHaveBeenCalled();
    });

    it('shows the API error and stays on the step', async () => {
      vi.mocked(apiPost).mockResolvedValue({ error: 'Language not found or access denied' });
      withConfig();

      const component = feedWizardStep4Data();
      component.handleSubmit(submitEvent());
      await vi.waitFor(() => expect(component.hasSaveError()).toBe(true));

      expect(component.saveError).toBe('Language not found or access denied');
      expect(component.saving).toBe(false);
    });

    it('refuses a feed with no language chosen', async () => {
      withConfig({ langId: null });

      const component = feedWizardStep4Data();
      component.handleSubmit(submitEvent());
      await vi.waitFor(() => expect(component.hasSaveError()).toBe(true));

      expect(apiPost).not.toHaveBeenCalled();
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

      const component = feedWizardStep4Data();
      component.cancel();

      expect(window.location.href).toBe('/feeds/edit?del_wiz=1');

      window.location = originalLocation;
    });
  });

  // ===========================================================================
  // initFeedWizardStep4Alpine Tests
  // ===========================================================================

  describe('initFeedWizardStep4Alpine', () => {
    it('registers feedWizardStep4 component with Alpine', () => {
      initFeedWizardStep4Alpine();

      expect(Alpine.data).toHaveBeenCalledWith('feedWizardStep4', feedWizardStep4Data);
    });
  });

  // ===========================================================================
  // Global Window Exposure Tests
  // ===========================================================================

  describe('global window exposure', () => {
    it('exposes feedWizardStep4Data on window', () => {
      expect(typeof window.feedWizardStep4Data).toBe('function');
    });
  });
});

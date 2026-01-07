/**
 * Tests for feeds/stores/feed_wizard_store.ts - Feed wizard Alpine.js store
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock Alpine.js before importing the store
vi.mock('alpinejs', () => {
  const stores: Record<string, unknown> = {};
  return {
    default: {
      store: vi.fn((name: string, data?: unknown) => {
        if (data !== undefined) {
          stores[name] = data;
        }
        return stores[name];
      })
    }
  };
});

// Mock xpath_utils
vi.mock('../../../../src/frontend/js/modules/feed/utils/xpath_utils', () => ({
  isValidXPath: vi.fn((xpath: string) => xpath.startsWith('//')),
  xpathQuery: vi.fn(() => [])
}));

import Alpine from 'alpinejs';
import { getFeedWizardStore, initFeedWizardStore } from '../../../../src/frontend/js/modules/feed/stores/feed_wizard_store';

describe('feeds/stores/feed_wizard_store.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Re-initialize store for each test
    initFeedWizardStore();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // Store Initialization Tests
  // ===========================================================================

  describe('Store initialization', () => {
    it('registers store with Alpine', () => {
      expect(Alpine.store).toHaveBeenCalledWith('feedWizard', expect.any(Object));
    });

    it('initializes with default values', () => {
      const store = getFeedWizardStore();

      expect(store.currentStep).toBe(1);
      expect(store.isLoading).toBe(false);
      expect(store.isInitialized).toBe(false);
      expect(store.rssUrl).toBe('');
      expect(store.feedTitle).toBe('');
      expect(store.feedItems).toEqual([]);
      expect(store.articleSelectors).toEqual([]);
      expect(store.filterSelectors).toEqual([]);
    });

    it('initializes feedOptions with defaults', () => {
      const store = getFeedWizardStore();

      expect(store.feedOptions.languageId).toBeNull();
      expect(store.feedOptions.editText).toBe(false);
      expect(store.feedOptions.autoUpdate.enabled).toBe(false);
      expect(store.feedOptions.maxLinks.enabled).toBe(false);
      expect(store.feedOptions.maxTexts.enabled).toBe(false);
      expect(store.feedOptions.charset.enabled).toBe(false);
      expect(store.feedOptions.tag.enabled).toBe(false);
    });
  });

  // ===========================================================================
  // configure Tests
  // ===========================================================================

  describe('configure', () => {
    it('sets step from config', () => {
      const store = getFeedWizardStore();

      store.configure({ step: 3 });

      expect(store.currentStep).toBe(3);
    });

    it('sets rssUrl from config', () => {
      const store = getFeedWizardStore();

      store.configure({ rssUrl: 'https://example.com/feed.xml' });

      expect(store.rssUrl).toBe('https://example.com/feed.xml');
    });

    it('sets feedTitle from config', () => {
      const store = getFeedWizardStore();

      store.configure({ feedTitle: 'My Feed' });

      expect(store.feedTitle).toBe('My Feed');
    });

    it('sets feedItems from config', () => {
      const store = getFeedWizardStore();
      const items = [{ title: 'Item 1' }, { title: 'Item 2' }];

      store.configure({ feedItems: items as never });

      expect(store.feedItems.length).toBe(2);
    });

    it('parses articleSelectors strings into objects', () => {
      const store = getFeedWizardStore();

      store.configure({ articleSelectors: ['//div', '//span'] });

      expect(store.articleSelectors.length).toBe(2);
      expect(store.articleSelectors[0].xpath).toBe('//div');
      expect(store.articleSelectors[0].id).toBeDefined();
      expect(store.articleSelectors[0].isHighlighted).toBe(false);
    });

    it('parses filterSelectors strings into objects', () => {
      const store = getFeedWizardStore();

      store.configure({ filterSelectors: ['//nav'] });

      expect(store.filterSelectors.length).toBe(1);
      expect(store.filterSelectors[0].xpath).toBe('//nav');
    });

    it('sets settings from config', () => {
      const store = getFeedWizardStore();

      store.configure({
        settings: {
          selectionMode: 'all',
          hideImages: false,
          isMinimized: true
        }
      });

      expect(store.selectionMode).toBe('all');
      expect(store.hideImages).toBe(false);
      expect(store.isMinimized).toBe(true);
    });

    it('sets options from config', () => {
      const store = getFeedWizardStore();

      store.configure({
        options: {
          languageId: 5,
          editText: true
        } as never
      });

      expect(store.feedOptions.languageId).toBe(5);
      expect(store.feedOptions.editText).toBe(true);
    });

    it('sets isInitialized to true', () => {
      const store = getFeedWizardStore();

      store.configure({});

      expect(store.isInitialized).toBe(true);
    });
  });

  // ===========================================================================
  // addSelector Tests
  // ===========================================================================

  describe('addSelector', () => {
    it('adds article selector', () => {
      const store = getFeedWizardStore();

      store.addSelector('//article', 'article');

      expect(store.articleSelectors.length).toBe(1);
      expect(store.articleSelectors[0].xpath).toBe('//article');
    });

    it('adds filter selector', () => {
      const store = getFeedWizardStore();

      store.addSelector('//nav', 'filter');

      expect(store.filterSelectors.length).toBe(1);
      expect(store.filterSelectors[0].xpath).toBe('//nav');
    });

    it('ignores empty xpath', () => {
      const store = getFeedWizardStore();

      store.addSelector('', 'article');
      store.addSelector('   ', 'article');

      expect(store.articleSelectors.length).toBe(0);
    });

    it('clears currentXPath after adding', () => {
      const store = getFeedWizardStore();
      store.currentXPath = '//test';

      store.addSelector('//article', 'article');

      expect(store.currentXPath).toBe('');
    });

    it('clears markActionOptions after adding', () => {
      const store = getFeedWizardStore();
      store.markActionOptions = [{ value: '//test', label: 'test', tagName: 'DIV' }];

      store.addSelector('//article', 'article');

      expect(store.markActionOptions.length).toBe(0);
    });
  });

  // ===========================================================================
  // removeSelector Tests
  // ===========================================================================

  describe('removeSelector', () => {
    it('removes article selector by id', () => {
      const store = getFeedWizardStore();
      store.addSelector('//div', 'article');
      const id = store.articleSelectors[0].id;

      store.removeSelector(id, 'article');

      expect(store.articleSelectors.length).toBe(0);
    });

    it('removes filter selector by id', () => {
      const store = getFeedWizardStore();
      store.addSelector('//nav', 'filter');
      const id = store.filterSelectors[0].id;

      store.removeSelector(id, 'filter');

      expect(store.filterSelectors.length).toBe(0);
    });

    it('does not remove non-matching id', () => {
      const store = getFeedWizardStore();
      store.addSelector('//div', 'article');

      store.removeSelector('nonexistent', 'article');

      expect(store.articleSelectors.length).toBe(1);
    });
  });

  // ===========================================================================
  // highlightSelector Tests
  // ===========================================================================

  describe('highlightSelector', () => {
    it('highlights matching selector', () => {
      const store = getFeedWizardStore();
      store.addSelector('//div', 'article');
      const id = store.articleSelectors[0].id;

      store.highlightSelector(id);

      expect(store.articleSelectors[0].isHighlighted).toBe(true);
    });

    it('clears other highlights', () => {
      const store = getFeedWizardStore();
      store.addSelector('//div', 'article');
      store.addSelector('//span', 'article');
      const id1 = store.articleSelectors[0].id;
      const id2 = store.articleSelectors[1].id;

      store.highlightSelector(id1);
      store.highlightSelector(id2);

      expect(store.articleSelectors[0].isHighlighted).toBe(false);
      expect(store.articleSelectors[1].isHighlighted).toBe(true);
    });
  });

  // ===========================================================================
  // clearHighlight Tests
  // ===========================================================================

  describe('clearHighlight', () => {
    it('clears all highlights', () => {
      const store = getFeedWizardStore();
      store.addSelector('//div', 'article');
      store.addSelector('//nav', 'filter');
      store.articleSelectors[0].isHighlighted = true;
      store.filterSelectors[0].isHighlighted = true;

      store.clearHighlight();

      expect(store.articleSelectors[0].isHighlighted).toBe(false);
      expect(store.filterSelectors[0].isHighlighted).toBe(false);
    });
  });

  // ===========================================================================
  // setCurrentXPath Tests
  // ===========================================================================

  describe('setCurrentXPath', () => {
    it('sets currentXPath value', () => {
      const store = getFeedWizardStore();

      store.setCurrentXPath('//div[@class="test"]');

      expect(store.currentXPath).toBe('//div[@class="test"]');
    });
  });

  // ===========================================================================
  // setMarkActionOptions Tests
  // ===========================================================================

  describe('setMarkActionOptions', () => {
    it('sets options and selects first', () => {
      const store = getFeedWizardStore();
      const options = [
        { value: '//first', label: 'First', tagName: 'DIV' },
        { value: '//second', label: 'Second', tagName: 'SPAN' }
      ];

      store.setMarkActionOptions(options);

      expect(store.markActionOptions).toEqual(options);
      expect(store.currentXPath).toBe('//first');
    });

    it('handles empty options', () => {
      const store = getFeedWizardStore();
      store.currentXPath = '//existing';

      store.setMarkActionOptions([]);

      expect(store.markActionOptions).toEqual([]);
      expect(store.currentXPath).toBe('//existing');
    });
  });

  // ===========================================================================
  // Advanced Modal Tests
  // ===========================================================================

  describe('openAdvanced', () => {
    it('opens advanced modal with options', () => {
      const store = getFeedWizardStore();
      const options = [
        { type: 'id' as const, label: 'ID option', xpath: '//div[@id="x"]' }
      ];

      store.openAdvanced(options);

      expect(store.isAdvancedOpen).toBe(true);
      expect(store.advancedOptions).toEqual(options);
    });

    it('clears custom xpath state', () => {
      const store = getFeedWizardStore();
      store.customXPath = '//existing';
      store.customXPathValid = true;

      store.openAdvanced([]);

      expect(store.customXPath).toBe('');
      expect(store.customXPathValid).toBe(false);
    });
  });

  describe('closeAdvanced', () => {
    it('closes advanced modal', () => {
      const store = getFeedWizardStore();
      store.isAdvancedOpen = true;
      store.advancedOptions = [{ type: 'id' as const, label: 'x', xpath: '//x' }];

      store.closeAdvanced();

      expect(store.isAdvancedOpen).toBe(false);
      expect(store.advancedOptions).toEqual([]);
    });
  });

  // ===========================================================================
  // validateCustomXPath Tests
  // ===========================================================================

  describe('validateCustomXPath', () => {
    it('returns false for empty string', () => {
      const store = getFeedWizardStore();

      const result = store.validateCustomXPath('');

      expect(result).toBe(false);
      expect(store.customXPathValid).toBe(false);
    });

    it('returns false for whitespace only', () => {
      const store = getFeedWizardStore();

      const result = store.validateCustomXPath('   ');

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // buildSelectorsString Tests
  // ===========================================================================

  describe('buildSelectorsString', () => {
    it('combines article selectors with pipe', () => {
      const store = getFeedWizardStore();
      store.addSelector('//div', 'article');
      store.addSelector('//span', 'article');

      const result = store.buildSelectorsString('article');

      expect(result).toBe('//div | //span');
    });

    it('combines filter selectors with pipe', () => {
      const store = getFeedWizardStore();
      store.addSelector('//nav', 'filter');
      store.addSelector('//footer', 'filter');

      const result = store.buildSelectorsString('filter');

      expect(result).toBe('//nav | //footer');
    });

    it('returns empty string for no selectors', () => {
      const store = getFeedWizardStore();

      const result = store.buildSelectorsString('article');

      expect(result).toBe('');
    });
  });

  // ===========================================================================
  // buildOptionsString Tests
  // ===========================================================================

  describe('buildOptionsString', () => {
    it('includes edit_text when enabled', () => {
      const store = getFeedWizardStore();
      store.feedOptions.editText = true;

      const result = store.buildOptionsString();

      expect(result).toContain('edit_text=1');
    });

    it('includes autoupdate when enabled', () => {
      const store = getFeedWizardStore();
      store.feedOptions.autoUpdate = { enabled: true, interval: 24, unit: 'h' };

      const result = store.buildOptionsString();

      expect(result).toContain('autoupdate=24h');
    });

    it('includes max_links when enabled', () => {
      const store = getFeedWizardStore();
      store.feedOptions.maxLinks = { enabled: true, value: 10 };

      const result = store.buildOptionsString();

      expect(result).toContain('max_links=10');
    });

    it('includes max_texts when enabled', () => {
      const store = getFeedWizardStore();
      store.feedOptions.maxTexts = { enabled: true, value: 5 };

      const result = store.buildOptionsString();

      expect(result).toContain('max_texts=5');
    });

    it('includes charset when enabled', () => {
      const store = getFeedWizardStore();
      store.feedOptions.charset = { enabled: true, value: 'UTF-8' };

      const result = store.buildOptionsString();

      expect(result).toContain('charset=UTF-8');
    });

    it('includes tag when enabled', () => {
      const store = getFeedWizardStore();
      store.feedOptions.tag = { enabled: true, value: 'my-tag' };

      const result = store.buildOptionsString();

      expect(result).toContain('tag=my-tag');
    });

    it('includes article_source when feedText set', () => {
      const store = getFeedWizardStore();
      store.feedText = 'content';

      const result = store.buildOptionsString();

      expect(result).toContain('article_source=content');
    });

    it('combines multiple options with comma', () => {
      const store = getFeedWizardStore();
      store.feedOptions.editText = true;
      store.feedOptions.maxLinks = { enabled: true, value: 10 };

      const result = store.buildOptionsString();

      expect(result).toContain(',');
    });
  });

  // ===========================================================================
  // canProceed Tests
  // ===========================================================================

  describe('canProceed', () => {
    it('step 1 requires rssUrl', () => {
      const store = getFeedWizardStore();
      store.currentStep = 1;

      expect(store.canProceed()).toBe(false);

      store.rssUrl = 'https://example.com/feed';
      expect(store.canProceed()).toBe(true);
    });

    it('step 2 requires articleSelectors', () => {
      const store = getFeedWizardStore();
      store.currentStep = 2;

      expect(store.canProceed()).toBe(false);

      store.addSelector('//article', 'article');
      expect(store.canProceed()).toBe(true);
    });

    it('step 3 always returns true', () => {
      const store = getFeedWizardStore();
      store.currentStep = 3;

      expect(store.canProceed()).toBe(true);
    });

    it('step 4 requires languageId and feedTitle', () => {
      const store = getFeedWizardStore();
      store.currentStep = 4;

      expect(store.canProceed()).toBe(false);

      store.feedOptions.languageId = 1;
      expect(store.canProceed()).toBe(false);

      store.feedTitle = 'My Feed';
      expect(store.canProceed()).toBe(true);
    });

    it('invalid step returns false', () => {
      const store = getFeedWizardStore();
      store.currentStep = 99;

      expect(store.canProceed()).toBe(false);
    });
  });

  // ===========================================================================
  // reset Tests
  // ===========================================================================

  describe('reset', () => {
    it('resets all values to defaults', () => {
      const store = getFeedWizardStore();

      // Set some values
      store.currentStep = 4;
      store.rssUrl = 'https://example.com';
      store.feedTitle = 'Test';
      store.addSelector('//div', 'article');
      store.isAdvancedOpen = true;
      store.feedOptions.languageId = 5;

      store.reset();

      expect(store.currentStep).toBe(1);
      expect(store.rssUrl).toBe('');
      expect(store.feedTitle).toBe('');
      expect(store.articleSelectors).toEqual([]);
      expect(store.isAdvancedOpen).toBe(false);
      expect(store.feedOptions.languageId).toBeNull();
    });
  });

  // ===========================================================================
  // Window Export Tests
  // ===========================================================================

  describe('Window Exports', () => {
    it('exposes getFeedWizardStore on window', () => {
      expect(window.getFeedWizardStore).toBeDefined();
    });
  });
});

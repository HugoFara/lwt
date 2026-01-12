/**
 * Tests for feed_wizard_step2.ts - Feed wizard step 2 (article selection)
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
  configure: vi.fn(),
  articleSelectors: [],
  markActionOptions: [],
  currentXPath: '',
  isMinimized: false,
  selectionMode: 'smart' as 'smart' | 'all' | 'adv',
  hideImages: true,
  setCurrentXPath: vi.fn(),
  setMarkActionOptions: vi.fn(),
  addSelector: vi.fn(),
  removeSelector: vi.fn(),
  highlightSelector: vi.fn(),
  clearHighlight: vi.fn(),
  buildSelectorsString: vi.fn(() => '//article'),
  openAdvanced: vi.fn(),
  closeAdvanced: vi.fn(),
  customXPath: '',
  customXPathValid: false
};

// Mock feed_wizard_store
vi.mock('../../../src/frontend/js/modules/feed/stores/feed_wizard_store', () => ({
  getFeedWizardStore: vi.fn(() => mockStore)
}));

// Mock highlight_service
vi.mock('../../../src/frontend/js/modules/feed/services/highlight_service', () => ({
  getHighlightService: vi.fn(() => ({
    clearAll: vi.fn(),
    clearMarking: vi.fn(),
    clearSelections: vi.fn(),
    clearHighlighting: vi.fn(),
    applySelections: vi.fn(),
    highlightListItem: vi.fn(),
    markElements: vi.fn(),
    toggleImages: vi.fn(),
    updateLastMargin: vi.fn()
  })),
  initHighlightService: vi.fn()
}));

// Mock xpath_utils
vi.mock('../../../src/frontend/js/modules/feed/utils/xpath_utils', () => ({
  xpathQuery: vi.fn(() => []),
  generateMarkActionOptions: vi.fn(() => []),
  generateAdvancedXPathOptions: vi.fn(() => []),
  getAncestorsAndSelf: vi.fn(() => []),
  parseSelectionList: vi.fn(() => [])
}));

import Alpine from 'alpinejs';
import { feedWizardStep2Data, initFeedWizardStep2Alpine, type Step2Config } from '../../../src/frontend/js/modules/feed/components/feed_wizard_step2';

describe('feed_wizard_step2.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    mockStore.configure.mockClear();
    mockStore.articleSelectors = [];
    mockStore.currentXPath = '';
    mockStore.isMinimized = false;
    mockStore.selectionMode = 'smart';
    mockStore.hideImages = true;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // feedWizardStep2Data Factory Tests
  // ===========================================================================

  describe('feedWizardStep2Data', () => {
    it('creates component with default values when no config', () => {
      const component = feedWizardStep2Data();

      expect(component.config).toBeDefined();
      expect(component.settingsOpen).toBe(false);
      expect(component.feedName).toBe('');
      expect(component.articleSource).toBe('');
      expect(component.selectedFeedIndex).toBe(0);
      expect(component.hostStatus).toBe('-');
    });

    it('reads config from script tag', () => {
      const config: Step2Config = {
        rssUrl: 'https://example.com/feed.xml',
        feedTitle: 'Test Feed',
        feedText: 'body',
        detectedFeed: 'RSS 2.0',
        feedItems: [{ title: 'Article 1', link: 'http://example.com/1' }],
        selectedFeedIndex: 2,
        articleTags: '',
        settings: { selectionMode: 'all', hideImages: false, isMinimized: true },
        editFeedId: 5,
        articleSources: ['body', 'article'],
        multipleHosts: true
      };
      document.body.innerHTML = `
        <script id="wizard-step2-config" type="application/json">
          ${JSON.stringify(config)}
        </script>
      `;

      const component = feedWizardStep2Data();

      expect(component.feedName).toBe('Test Feed');
      expect(component.articleSource).toBe('body');
      expect(component.selectedFeedIndex).toBe(2);
    });

    it('handles invalid JSON config gracefully', () => {
      document.body.innerHTML = `
        <script id="wizard-step2-config" type="application/json">
          { invalid json }
        </script>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      const component = feedWizardStep2Data();

      expect(component.feedName).toBe('');
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Computed Properties Tests
  // ===========================================================================

  describe('computed properties', () => {
    it('canProceed returns false when no selectors', () => {
      mockStore.articleSelectors = [];

      const component = feedWizardStep2Data();

      expect(component.canProceed).toBe(false);
    });

    it('canProceed returns true when selectors exist', () => {
      mockStore.articleSelectors = [{ id: '1', xpath: '//p', isHighlighted: false }];

      const component = feedWizardStep2Data();

      expect(component.canProceed).toBe(true);
    });

    it('articleSelectors returns store selectors', () => {
      mockStore.articleSelectors = [{ id: '1', xpath: '//p', isHighlighted: false }];

      const component = feedWizardStep2Data();

      expect(component.articleSelectors).toEqual(mockStore.articleSelectors);
    });

    it('currentXPath returns store value', () => {
      mockStore.currentXPath = '//div[@class="content"]';

      const component = feedWizardStep2Data();

      expect(component.currentXPath).toBe('//div[@class="content"]');
    });

    it('isMinimized returns store value', () => {
      mockStore.isMinimized = true;

      const component = feedWizardStep2Data();

      expect(component.isMinimized).toBe(true);
    });

    it('selectionMode maps smart to 0', () => {
      mockStore.selectionMode = 'smart';

      const component = feedWizardStep2Data();

      expect(component.selectionMode).toBe('0');
    });

    it('selectionMode maps all correctly', () => {
      mockStore.selectionMode = 'all';

      const component = feedWizardStep2Data();

      expect(component.selectionMode).toBe('all');
    });

    it('selectionMode maps adv correctly', () => {
      mockStore.selectionMode = 'adv';

      const component = feedWizardStep2Data();

      expect(component.selectionMode).toBe('adv');
    });
  });

  // ===========================================================================
  // Action Methods Tests
  // ===========================================================================

  describe('action methods', () => {
    it('deleteSelector calls store removeSelector', () => {
      const component = feedWizardStep2Data();

      component.deleteSelector('selector-1');

      expect(mockStore.removeSelector).toHaveBeenCalledWith('selector-1', 'article');
    });

    it('toggleSelectorHighlight toggles highlight', () => {
      mockStore.articleSelectors = [{ id: '1', xpath: '//p', isHighlighted: false }];

      const component = feedWizardStep2Data();
      component.toggleSelectorHighlight('1');

      expect(mockStore.highlightSelector).toHaveBeenCalledWith('1');
    });

    it('toggleSelectorHighlight clears highlight when already highlighted', () => {
      mockStore.articleSelectors = [{ id: '1', xpath: '//p', isHighlighted: true }];

      const component = feedWizardStep2Data();
      component.toggleSelectorHighlight('1');

      expect(mockStore.clearHighlight).toHaveBeenCalled();
    });

    it('toggleMinimize toggles isMinimized', () => {
      mockStore.isMinimized = false;

      const component = feedWizardStep2Data();
      component.toggleMinimize();

      expect(mockStore.isMinimized).toBe(true);
    });

    it('changeSelectMode clears marking', () => {
      const component = feedWizardStep2Data();
      component.changeSelectMode();

      expect(mockStore.setCurrentXPath).toHaveBeenCalledWith('');
      expect(mockStore.setMarkActionOptions).toHaveBeenCalledWith([]);
    });
  });

  // ===========================================================================
  // Navigation Tests
  // ===========================================================================

  describe('navigation', () => {
    it('goBack navigates to step 1', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      const component = feedWizardStep2Data();
      component.goBack();

      expect(window.location.href).toContain('/feeds/wizard?step=1');

      window.location = originalLocation;
    });

    it('cancel navigates to feeds edit with del_wiz', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      const component = feedWizardStep2Data();
      component.cancel();

      expect(window.location.href).toBe('/feeds/edit?del_wiz=1');

      window.location = originalLocation;
    });

    it('goNext submits form', () => {
      document.body.innerHTML = `
        <form name="lwt_form1">
          <input type="hidden" name="html" value="" />
          <input type="hidden" name="step" value="2" />
          <input type="hidden" name="article_tags" value="" disabled />
        </form>
      `;

      const form = document.querySelector('form')!;
      const submitSpy = vi.spyOn(form, 'submit').mockImplementation(() => {});

      const component = feedWizardStep2Data();
      component.goNext();

      expect(submitSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Event Handlers Tests
  // ===========================================================================

  describe('event handlers', () => {
    it('handleMarkActionChange updates currentXPath', () => {
      const component = feedWizardStep2Data();
      const event = { target: { value: '//div' } } as unknown as Event;

      component.handleMarkActionChange(event);

      expect(mockStore.setCurrentXPath).toHaveBeenCalledWith('//div');
    });

    it('handleContentClick does nothing without target', () => {
      const component = feedWizardStep2Data();
      const event = { target: null } as unknown as MouseEvent;

      expect(() => component.handleContentClick(event)).not.toThrow();
    });
  });

  // ===========================================================================
  // Advanced Mode Tests
  // ===========================================================================

  describe('advanced mode', () => {
    it('cancelAdvanced closes advanced panel', () => {
      const component = feedWizardStep2Data();
      component.cancelAdvanced();

      expect(mockStore.closeAdvanced).toHaveBeenCalled();
    });

    it('selectAdvancedOption sets custom xpath', () => {
      const component = feedWizardStep2Data();
      component.selectAdvancedOption('//custom/xpath');

      expect(mockStore.customXPath).toBe('//custom/xpath');
    });
  });

  // ===========================================================================
  // initFeedWizardStep2Alpine Tests
  // ===========================================================================

  describe('initFeedWizardStep2Alpine', () => {
    it('registers feedWizardStep2 component with Alpine', () => {
      initFeedWizardStep2Alpine();

      expect(Alpine.data).toHaveBeenCalledWith('feedWizardStep2', feedWizardStep2Data);
    });
  });

  // ===========================================================================
  // Global Window Exposure Tests
  // ===========================================================================

  describe('global window exposure', () => {
    it('exposes feedWizardStep2Data on window', () => {
      expect(typeof window.feedWizardStep2Data).toBe('function');
    });
  });
});

/**
 * Tests for feed_wizard_step3.ts - Feed wizard step 3 (filter selection)
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
  filterSelectors: [],
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
  buildSelectorsString: vi.fn(() => '//div[@class="filter"]'),
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
    applyArticleSectionFilter: vi.fn(),
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
import { feedWizardStep3Data, initFeedWizardStep3Alpine, type Step3Config } from '../../../src/frontend/js/modules/feed/components/feed_wizard_step3';

describe('feed_wizard_step3.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    mockStore.configure.mockClear();
    mockStore.filterSelectors = [];
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
  // feedWizardStep3Data Factory Tests
  // ===========================================================================

  describe('feedWizardStep3Data', () => {
    it('creates component with default values when no config', () => {
      const component = feedWizardStep3Data();

      expect(component.config).toBeDefined();
      expect(component.settingsOpen).toBe(false);
      expect(component.selectedFeedIndex).toBe(0);
      expect(component.hostStatus).toBe('-');
    });

    it('reads config from script tag', () => {
      const config: Step3Config = {
        rssUrl: 'https://example.com/feed.xml',
        feedTitle: 'Test Feed',
        feedText: 'body',
        articleSection: '//article',
        articleSelector: '//article',
        filterTags: '',
        feedItems: [{ title: 'Article 1', link: 'http://example.com/1' }],
        selectedFeedIndex: 3,
        settings: { selectionMode: 'adv', hideImages: false, isMinimized: true },
        editFeedId: 10,
        multipleHosts: false
      };
      document.body.innerHTML = `
        <script id="wizard-step3-config" type="application/json">
          ${JSON.stringify(config)}
        </script>
      `;

      const component = feedWizardStep3Data();

      expect(component.selectedFeedIndex).toBe(3);
      expect(component.config.articleSelector).toBe('//article');
    });

    it('handles invalid JSON config gracefully', () => {
      document.body.innerHTML = `
        <script id="wizard-step3-config" type="application/json">
          { invalid json }
        </script>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      const component = feedWizardStep3Data();

      expect(component.selectedFeedIndex).toBe(0);
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Computed Properties Tests
  // ===========================================================================

  describe('computed properties', () => {
    it('filterSelectors returns store selectors', () => {
      mockStore.filterSelectors = [{ id: '1', xpath: '//nav', isHighlighted: false }];

      const component = feedWizardStep3Data();

      expect(component.filterSelectors).toEqual(mockStore.filterSelectors);
    });

    it('currentXPath returns store value', () => {
      mockStore.currentXPath = '//div[@class="sidebar"]';

      const component = feedWizardStep3Data();

      expect(component.currentXPath).toBe('//div[@class="sidebar"]');
    });

    it('isMinimized returns store value', () => {
      mockStore.isMinimized = true;

      const component = feedWizardStep3Data();

      expect(component.isMinimized).toBe(true);
    });

    it('selectionMode maps smart to 0', () => {
      mockStore.selectionMode = 'smart';

      const component = feedWizardStep3Data();

      expect(component.selectionMode).toBe('0');
    });

    it('selectionMode maps all correctly', () => {
      mockStore.selectionMode = 'all';

      const component = feedWizardStep3Data();

      expect(component.selectionMode).toBe('all');
    });

    it('selectionMode maps adv correctly', () => {
      mockStore.selectionMode = 'adv';

      const component = feedWizardStep3Data();

      expect(component.selectionMode).toBe('adv');
    });

    it('hideImages returns store value', () => {
      mockStore.hideImages = false;

      const component = feedWizardStep3Data();

      expect(component.hideImages).toBe(false);
    });
  });

  // ===========================================================================
  // Action Methods Tests
  // ===========================================================================

  describe('action methods', () => {
    it('deleteSelector calls store removeSelector with filter type', () => {
      const component = feedWizardStep3Data();

      component.deleteSelector('selector-1');

      expect(mockStore.removeSelector).toHaveBeenCalledWith('selector-1', 'filter');
    });

    it('toggleSelectorHighlight toggles highlight', () => {
      mockStore.filterSelectors = [{ id: '1', xpath: '//nav', isHighlighted: false }];

      const component = feedWizardStep3Data();
      component.toggleSelectorHighlight('1');

      expect(mockStore.highlightSelector).toHaveBeenCalledWith('1');
    });

    it('toggleSelectorHighlight clears highlight when already highlighted', () => {
      mockStore.filterSelectors = [{ id: '1', xpath: '//nav', isHighlighted: true }];

      const component = feedWizardStep3Data();
      component.toggleSelectorHighlight('1');

      expect(mockStore.clearHighlight).toHaveBeenCalled();
    });

    it('toggleMinimize toggles isMinimized', () => {
      mockStore.isMinimized = false;

      const component = feedWizardStep3Data();
      component.toggleMinimize();

      expect(mockStore.isMinimized).toBe(true);
    });

    it('changeSelectMode clears marking', () => {
      const component = feedWizardStep3Data();
      component.changeSelectMode();

      expect(mockStore.setCurrentXPath).toHaveBeenCalledWith('');
      expect(mockStore.setMarkActionOptions).toHaveBeenCalledWith([]);
    });

    it('filterSelection does nothing without xpath', () => {
      mockStore.currentXPath = '';

      const component = feedWizardStep3Data();
      component.filterSelection();

      expect(mockStore.addSelector).not.toHaveBeenCalled();
    });

    it('filterSelection adds selector with filter type', () => {
      mockStore.currentXPath = '//nav';
      mockStore.selectionMode = 'smart';

      const component = feedWizardStep3Data();
      component.filterSelection();

      expect(mockStore.addSelector).toHaveBeenCalledWith('//nav', 'filter');
    });
  });

  // ===========================================================================
  // Navigation Tests
  // ===========================================================================

  describe('navigation', () => {
    it('goBack navigates to step 2', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      document.body.innerHTML = `
        <input type="hidden" id="maxim" value="1" />
        <div id="lwt_sel"></div>
      `;

      const component = feedWizardStep3Data();
      component.goBack();

      expect(window.location.href).toContain('/feeds/wizard?step=2');
      expect(window.location.href).toContain('article_tags=1');

      window.location = originalLocation;
    });

    it('cancel navigates to feeds edit with del_wiz', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      const component = feedWizardStep3Data();
      component.cancel();

      expect(window.location.href).toBe('/feeds/edit?del_wiz=1');

      window.location = originalLocation;
    });

    it('goNext submits form', () => {
      document.body.innerHTML = `
        <form name="lwt_form1">
          <input type="hidden" name="html" value="" />
          <input type="hidden" name="step" value="3" />
          <input type="hidden" name="filter_tags" value="" disabled />
        </form>
        <div id="lwt_sel"></div>
      `;

      const form = document.querySelector('form')!;
      const submitSpy = vi.spyOn(form, 'submit').mockImplementation(() => {});

      const component = feedWizardStep3Data();
      component.goNext();

      expect(submitSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Event Handlers Tests
  // ===========================================================================

  describe('event handlers', () => {
    it('handleMarkActionChange updates currentXPath', () => {
      const component = feedWizardStep3Data();
      const event = { target: { value: '//nav' } } as unknown as Event;

      component.handleMarkActionChange(event);

      expect(mockStore.setCurrentXPath).toHaveBeenCalledWith('//nav');
    });

    it('handleContentClick does nothing without target', () => {
      const component = feedWizardStep3Data();
      const event = { target: null } as unknown as MouseEvent;

      expect(() => component.handleContentClick(event)).not.toThrow();
    });
  });

  // ===========================================================================
  // Advanced Mode Tests
  // ===========================================================================

  describe('advanced mode', () => {
    it('cancelAdvanced closes advanced panel', () => {
      const component = feedWizardStep3Data();
      component.cancelAdvanced();

      expect(mockStore.closeAdvanced).toHaveBeenCalled();
    });

    it('getAdvanced adds selector and closes advanced', () => {
      document.body.innerHTML = `
        <div id="adv">
          <input type="radio" value="//custom" checked />
        </div>
      `;

      const component = feedWizardStep3Data();
      component.getAdvanced();

      expect(mockStore.addSelector).toHaveBeenCalledWith('//custom', 'filter');
      expect(mockStore.closeAdvanced).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // initFeedWizardStep3Alpine Tests
  // ===========================================================================

  describe('initFeedWizardStep3Alpine', () => {
    it('registers feedWizardStep3 component with Alpine', () => {
      initFeedWizardStep3Alpine();

      expect(Alpine.data).toHaveBeenCalledWith('feedWizardStep3', feedWizardStep3Data);
    });
  });

  // ===========================================================================
  // Global Window Exposure Tests
  // ===========================================================================

  describe('global window exposure', () => {
    it('exposes feedWizardStep3Data on window', () => {
      expect(typeof window.feedWizardStep3Data).toBe('function');
    });
  });
});

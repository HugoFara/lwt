/**
 * Tests for dictionaries/local_dictionary_panel.ts - Local dictionary inline panel component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock Alpine.js before importing the module
vi.mock('alpinejs', () => {
  const registeredData: Record<string, unknown> = {};
  return {
    default: {
      data: vi.fn((name: string, fn: () => unknown) => {
        registeredData[name] = fn;
        return fn;
      }),
      initTree: vi.fn(),
      $data: vi.fn(),
      _registeredData: registeredData
    }
  };
});

// Mock local_dictionary_api
vi.mock('../../../src/frontend/js/dictionaries/local_dictionary_api', () => ({
  lookupLocal: vi.fn(),
  formatResults: vi.fn(),
  hasLocalDictionaries: vi.fn(),
  shouldUseOnline: vi.fn()
}));

import Alpine from 'alpinejs';
import * as dictApi from '../../../src/frontend/js/dictionaries/local_dictionary_api';
import {
  createPanelElement,
  registerPanelComponent,
  showLocalDictPanel,
  hideLocalDictPanel,
  isPanelVisible,
  showInlineResults
} from '../../../src/frontend/js/dictionaries/local_dictionary_panel';
import type { LocalDictResult } from '../../../src/frontend/js/dictionaries/local_dictionary_api';

// Helper to get mocked functions
const mockLookupLocal = vi.mocked(dictApi.lookupLocal);
const mockFormatResults = vi.mocked(dictApi.formatResults);
const mockHasLocalDictionaries = vi.mocked(dictApi.hasLocalDictionaries);
const mockShouldUseOnline = vi.mocked(dictApi.shouldUseOnline);
const mockInitTree = vi.mocked(Alpine.initTree);
const mock$data = vi.mocked(Alpine.$data);

describe('dictionaries/local_dictionary_panel.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
    hideLocalDictPanel();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // createPanelElement Tests
  // ===========================================================================

  describe('createPanelElement', () => {
    it('creates a div element', () => {
      const panel = createPanelElement();

      expect(panel.tagName).toBe('DIV');
    });

    it('adds local-dict-panel class', () => {
      const panel = createPanelElement();

      expect(panel.className).toBe('local-dict-panel');
    });

    it('sets x-data attribute for Alpine', () => {
      const panel = createPanelElement();

      expect(panel.getAttribute('x-data')).toBe('localDictPanel');
    });

    it('sets x-show attribute', () => {
      const panel = createPanelElement();

      expect(panel.getAttribute('x-show')).toBe('visible');
    });

    it('sets transition attributes', () => {
      const panel = createPanelElement();

      expect(panel.getAttribute('x-transition:enter')).toBeTruthy();
      expect(panel.getAttribute('x-transition:leave')).toBeTruthy();
    });

    it('contains header element', () => {
      const panel = createPanelElement();
      const header = panel.querySelector('.local-dict-panel-header');

      expect(header).not.toBeNull();
    });

    it('contains close button', () => {
      const panel = createPanelElement();
      const closeBtn = panel.querySelector('.local-dict-panel-close');

      expect(closeBtn).not.toBeNull();
    });

    it('contains content area', () => {
      const panel = createPanelElement();
      const content = panel.querySelector('.local-dict-panel-content');

      expect(content).not.toBeNull();
    });

    it('contains title with loading indicator', () => {
      const panel = createPanelElement();
      const title = panel.querySelector('.local-dict-panel-title');
      const loading = panel.querySelector('.local-dict-loading');

      expect(title).not.toBeNull();
      expect(loading).not.toBeNull();
    });
  });

  // ===========================================================================
  // registerPanelComponent Tests
  // ===========================================================================

  describe('registerPanelComponent', () => {
    it('registers localDictPanel component with Alpine', () => {
      registerPanelComponent();

      expect(Alpine.data).toHaveBeenCalledWith('localDictPanel', expect.any(Function));
    });
  });

  // ===========================================================================
  // Alpine Component Data Tests
  // ===========================================================================

  describe('localDictPanel component data', () => {
    let componentData: ReturnType<typeof getComponentData>;

    function getComponentData() {
      // Get the registered component function
      const calls = vi.mocked(Alpine.data).mock.calls;
      const call = calls.find(c => c[0] === 'localDictPanel');
      if (!call) throw new Error('localDictPanel not registered');
      const factory = call[1] as () => unknown;
      return factory() as {
        visible: boolean;
        loading: boolean;
        term: string;
        results: LocalDictResult[];
        error: string | null;
        show: (langId: number, term: string) => Promise<void>;
        hide: () => void;
        toggle: () => void;
      };
    }

    beforeEach(() => {
      registerPanelComponent();
      componentData = getComponentData();
    });

    it('initializes with default state', () => {
      expect(componentData.visible).toBe(false);
      expect(componentData.loading).toBe(false);
      expect(componentData.term).toBe('');
      expect(componentData.results).toEqual([]);
      expect(componentData.error).toBeNull();
    });

    describe('show method', () => {
      it('sets term and makes visible', async () => {
        mockLookupLocal.mockResolvedValue({ data: { results: [] } });

        await componentData.show(1, 'test');

        expect(componentData.term).toBe('test');
        expect(componentData.visible).toBe(true);
      });

      it('sets loading during lookup', async () => {
        let resolvePromise: (value: unknown) => void;
        mockLookupLocal.mockReturnValue(new Promise(resolve => {
          resolvePromise = resolve;
        }));

        const promise = componentData.show(1, 'test');

        expect(componentData.loading).toBe(true);

        resolvePromise!({ data: { results: [] } });
        await promise;

        expect(componentData.loading).toBe(false);
      });

      it('clears previous error and results', async () => {
        componentData.error = 'Previous error';
        componentData.results = [{ term: 'old', definition: 'old def', dictionary: 'dict' }];
        mockLookupLocal.mockResolvedValue({ data: { results: [] } });

        await componentData.show(1, 'test');

        expect(componentData.error).toBeNull();
        expect(componentData.results).toEqual([]);
      });

      it('sets results from response', async () => {
        const mockResults: LocalDictResult[] = [
          { term: 'test', definition: 'a definition', dictionary: 'Dict1' }
        ];
        mockLookupLocal.mockResolvedValue({ data: { results: mockResults } });

        await componentData.show(1, 'test');

        expect(componentData.results).toEqual(mockResults);
      });

      it('sets error from response', async () => {
        mockLookupLocal.mockResolvedValue({ error: 'Lookup failed' });

        await componentData.show(1, 'test');

        expect(componentData.error).toBe('Lookup failed');
      });

      it('handles exception during lookup', async () => {
        const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
        mockLookupLocal.mockRejectedValue(new Error('Network error'));

        await componentData.show(1, 'test');

        expect(componentData.error).toBe('Failed to look up term');
        consoleSpy.mockRestore();
      });
    });

    describe('hide method', () => {
      it('sets visible to false', () => {
        componentData.visible = true;

        componentData.hide();

        expect(componentData.visible).toBe(false);
      });
    });

    describe('toggle method', () => {
      it('toggles visible from false to true', () => {
        componentData.visible = false;

        componentData.toggle();

        expect(componentData.visible).toBe(true);
      });

      it('toggles visible from true to false', () => {
        componentData.visible = true;

        componentData.toggle();

        expect(componentData.visible).toBe(false);
      });
    });
  });

  // ===========================================================================
  // showLocalDictPanel Tests
  // ===========================================================================

  describe('showLocalDictPanel', () => {
    let targetElement: HTMLElement;

    beforeEach(() => {
      targetElement = document.createElement('span');
      targetElement.textContent = 'test word';
      document.body.appendChild(targetElement);
      // Mock getBoundingClientRect
      targetElement.getBoundingClientRect = vi.fn(() => ({
        top: 100,
        left: 50,
        bottom: 120,
        right: 100,
        width: 50,
        height: 20,
        x: 50,
        y: 100,
        toJSON: () => ({})
      }));
    });

    it('returns showOnline true when no local dictionaries', async () => {
      mockHasLocalDictionaries.mockResolvedValue(false);

      const result = await showLocalDictPanel(1, 'test', targetElement);

      expect(result.results).toEqual([]);
      expect(result.showOnline).toBe(true);
    });

    it('creates and appends panel to body', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockShouldUseOnline.mockResolvedValue(false);
      mock$data.mockReturnValue({
        show: vi.fn().mockResolvedValue(undefined),
        results: []
      });

      await showLocalDictPanel(1, 'test', targetElement);

      const panel = document.querySelector('.local-dict-panel');
      expect(panel).not.toBeNull();
    });

    it('positions panel below target element', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockShouldUseOnline.mockResolvedValue(false);
      mock$data.mockReturnValue({
        show: vi.fn().mockResolvedValue(undefined),
        results: []
      });

      await showLocalDictPanel(1, 'test', targetElement);

      const panel = document.querySelector('.local-dict-panel') as HTMLElement;
      expect(panel.style.position).toBe('absolute');
      expect(panel.style.zIndex).toBe('1000');
    });

    it('initializes Alpine on the panel', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockShouldUseOnline.mockResolvedValue(false);
      mock$data.mockReturnValue({
        show: vi.fn().mockResolvedValue(undefined),
        results: []
      });

      await showLocalDictPanel(1, 'test', targetElement);

      expect(mockInitTree).toHaveBeenCalled();
    });

    it('calls show on panel component', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockShouldUseOnline.mockResolvedValue(false);
      const mockShow = vi.fn().mockResolvedValue(undefined);
      mock$data.mockReturnValue({
        show: mockShow,
        results: []
      });

      await showLocalDictPanel(1, 'test', targetElement);

      expect(mockShow).toHaveBeenCalledWith(1, 'test');
    });

    it('returns results and showOnline status', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockShouldUseOnline.mockResolvedValue(true);
      const mockResults: LocalDictResult[] = [
        { term: 'test', definition: 'def', dictionary: 'Dict' }
      ];
      mock$data.mockReturnValue({
        show: vi.fn().mockResolvedValue(undefined),
        results: mockResults
      });

      const result = await showLocalDictPanel(1, 'test', targetElement);

      expect(result.results).toEqual(mockResults);
      expect(result.showOnline).toBe(true);
    });

    it('removes existing panel before showing new one', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockShouldUseOnline.mockResolvedValue(false);
      mock$data.mockReturnValue({
        show: vi.fn().mockResolvedValue(undefined),
        results: []
      });

      await showLocalDictPanel(1, 'test1', targetElement);
      await showLocalDictPanel(1, 'test2', targetElement);

      const panels = document.querySelectorAll('.local-dict-panel');
      expect(panels.length).toBe(1);
    });
  });

  // ===========================================================================
  // hideLocalDictPanel Tests
  // ===========================================================================

  describe('hideLocalDictPanel', () => {
    it('removes panel from DOM', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockShouldUseOnline.mockResolvedValue(false);
      mock$data.mockReturnValue({
        show: vi.fn().mockResolvedValue(undefined),
        results: []
      });

      const targetElement = document.createElement('span');
      document.body.appendChild(targetElement);
      targetElement.getBoundingClientRect = vi.fn(() => ({
        top: 100, left: 50, bottom: 120, right: 100,
        width: 50, height: 20, x: 50, y: 100, toJSON: () => ({})
      }));

      await showLocalDictPanel(1, 'test', targetElement);
      expect(document.querySelector('.local-dict-panel')).not.toBeNull();

      hideLocalDictPanel();

      expect(document.querySelector('.local-dict-panel')).toBeNull();
    });

    it('does nothing when no panel is visible', () => {
      expect(() => hideLocalDictPanel()).not.toThrow();
    });
  });

  // ===========================================================================
  // isPanelVisible Tests
  // ===========================================================================

  describe('isPanelVisible', () => {
    it('returns false when no panel exists', () => {
      expect(isPanelVisible()).toBe(false);
    });

    it('returns true when panel is shown', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockShouldUseOnline.mockResolvedValue(false);
      mock$data.mockReturnValue({
        show: vi.fn().mockResolvedValue(undefined),
        results: []
      });

      const targetElement = document.createElement('span');
      document.body.appendChild(targetElement);
      targetElement.getBoundingClientRect = vi.fn(() => ({
        top: 100, left: 50, bottom: 120, right: 100,
        width: 50, height: 20, x: 50, y: 100, toJSON: () => ({})
      }));

      await showLocalDictPanel(1, 'test', targetElement);

      expect(isPanelVisible()).toBe(true);
    });

    it('returns false after panel is hidden', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockShouldUseOnline.mockResolvedValue(false);
      mock$data.mockReturnValue({
        show: vi.fn().mockResolvedValue(undefined),
        results: []
      });

      const targetElement = document.createElement('span');
      document.body.appendChild(targetElement);
      targetElement.getBoundingClientRect = vi.fn(() => ({
        top: 100, left: 50, bottom: 120, right: 100,
        width: 50, height: 20, x: 50, y: 100, toJSON: () => ({})
      }));

      await showLocalDictPanel(1, 'test', targetElement);
      hideLocalDictPanel();

      expect(isPanelVisible()).toBe(false);
    });
  });

  // ===========================================================================
  // showInlineResults Tests
  // ===========================================================================

  describe('showInlineResults', () => {
    let targetElement: HTMLElement;

    beforeEach(() => {
      targetElement = document.createElement('div');
      document.body.appendChild(targetElement);
    });

    it('returns showOnline true when no local dictionaries', async () => {
      mockHasLocalDictionaries.mockResolvedValue(false);

      const result = await showInlineResults(1, 'test', targetElement);

      expect(result.results).toEqual([]);
      expect(result.showOnline).toBe(true);
    });

    it('removes loading indicator after lookup completes', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockLookupLocal.mockResolvedValue({ data: { results: [] } });
      mockShouldUseOnline.mockResolvedValue(false);

      await showInlineResults(1, 'test', targetElement);

      // After completion, loading indicator should be removed
      expect(targetElement.querySelector('.local-dict-loading-inline')).toBeNull();
    });

    it('displays error message on API error', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockLookupLocal.mockResolvedValue({ error: 'Lookup failed' });

      await showInlineResults(1, 'test', targetElement);

      const errorDiv = targetElement.querySelector('.local-dict-error');
      expect(errorDiv).not.toBeNull();
      expect(errorDiv?.textContent).toBe('Lookup failed');
    });

    it('displays formatted results', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      const mockResults: LocalDictResult[] = [
        { term: 'test', definition: 'a test', dictionary: 'Dict' }
      ];
      mockLookupLocal.mockResolvedValue({ data: { results: mockResults } });
      mockFormatResults.mockReturnValue('<div>Formatted results</div>');
      mockShouldUseOnline.mockResolvedValue(false);

      await showInlineResults(1, 'test', targetElement);

      const resultsDiv = targetElement.querySelector('.local-dict-results-inline');
      expect(resultsDiv).not.toBeNull();
      expect(mockFormatResults).toHaveBeenCalledWith(mockResults);
    });

    it('returns results and showOnline status', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      const mockResults: LocalDictResult[] = [
        { term: 'test', definition: 'def', dictionary: 'Dict' }
      ];
      mockLookupLocal.mockResolvedValue({ data: { results: mockResults } });
      mockShouldUseOnline.mockResolvedValue(true);

      const result = await showInlineResults(1, 'test', targetElement);

      expect(result.results).toEqual(mockResults);
      expect(result.showOnline).toBe(true);
    });

    it('handles exception during lookup', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockLookupLocal.mockRejectedValue(new Error('Network error'));

      const result = await showInlineResults(1, 'test', targetElement);

      const errorDiv = targetElement.querySelector('.local-dict-error');
      expect(errorDiv).not.toBeNull();
      expect(errorDiv?.textContent).toBe('Failed to look up term');
      expect(result.showOnline).toBe(true);
    });

    it('does not add results div when no results', async () => {
      mockHasLocalDictionaries.mockResolvedValue(true);
      mockLookupLocal.mockResolvedValue({ data: { results: [] } });
      mockShouldUseOnline.mockResolvedValue(true);

      await showInlineResults(1, 'test', targetElement);

      const resultsDiv = targetElement.querySelector('.local-dict-results-inline');
      expect(resultsDiv).toBeNull();
    });
  });
});

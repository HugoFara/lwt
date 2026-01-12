/**
 * Tests for text_reader.ts - Text reading view Alpine component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock Alpine.js
vi.mock('alpinejs', () => ({
  default: {
    data: vi.fn(),
    store: vi.fn()
  }
}));

// Mock text renderer
vi.mock('../../../src/frontend/js/modules/text/pages/reading/text_renderer', () => ({
  renderText: vi.fn(() => '<div>rendered text</div>'),
  updateWordStatusInDOM: vi.fn()
}));

// Mock multiword selection
vi.mock('../../../src/frontend/js/modules/text/pages/reading/text_multiword_selection', () => ({
  setupMultiWordSelection: vi.fn()
}));

// Mock TextsApi
vi.mock('../../../src/frontend/js/modules/text/api/texts_api', () => ({
  TextsApi: {
    markAllWellKnown: vi.fn().mockResolvedValue({ data: { words: [] } }),
    markAllIgnored: vi.fn().mockResolvedValue({ data: { words: [] } })
  }
}));

// Create mock word store
const mockWordStore = {
  textId: 123,
  title: 'Test Text',
  isInitialized: true,
  rightToLeft: false,
  textSize: 100,
  showLearning: true,
  displayStatTrans: true,
  modeTrans: 1,
  annTextSize: 75,
  words: [],
  isPopoverOpen: false,
  isEditModalOpen: false,
  showAll: false,
  showTranslations: true,
  loadText: vi.fn().mockResolvedValue(undefined),
  selectWord: vi.fn(),
  updateWordInStore: vi.fn()
};

import Alpine from 'alpinejs';
import { textReaderData, initTextReaderAlpine } from '../../../src/frontend/js/modules/text/components/text_reader';
import { renderText, updateWordStatusInDOM } from '../../../src/frontend/js/modules/text/pages/reading/text_renderer';
import { setupMultiWordSelection } from '../../../src/frontend/js/modules/text/pages/reading/text_multiword_selection';
import { TextsApi } from '../../../src/frontend/js/modules/text/api/texts_api';

describe('text_reader.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Mock Alpine.store to return our mock word store
    (Alpine.store as ReturnType<typeof vi.fn>).mockReturnValue(mockWordStore);

    // Reset mock store state
    mockWordStore.isInitialized = true;
    mockWordStore.textId = 123;
    mockWordStore.isPopoverOpen = false;
    mockWordStore.isEditModalOpen = false;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // textReaderData Factory Tests
  // ===========================================================================

  describe('textReaderData', () => {
    it('creates component with default values', () => {
      const component = textReaderData();

      expect(component.isLoading).toBe(true);
      expect(component.showAll).toBe(false);
      expect(component.showTranslations).toBe(true);
      expect(component.error).toBe(null);
    });

    it('accesses store via Alpine.store', () => {
      const component = textReaderData();

      expect(component.store).toBe(mockWordStore);
      expect(Alpine.store).toHaveBeenCalledWith('words');
    });

    it('gets textId from store', () => {
      const component = textReaderData();

      expect(component.textId).toBe(123);
    });

    it('gets title from store', () => {
      const component = textReaderData();

      expect(component.title).toBe('Test Text');
    });

    it('gets isInitialized from store', () => {
      const component = textReaderData();

      expect(component.isInitialized).toBe(true);
    });
  });

  // ===========================================================================
  // getTextIdFromUrl Tests
  // ===========================================================================

  describe('getTextIdFromUrl', () => {
    it('extracts text ID from path /text/read/123', () => {
      Object.defineProperty(window, 'location', {
        value: { pathname: '/text/read/456', search: '' },
        writable: true
      });

      const component = textReaderData();
      const textId = component.getTextIdFromUrl();

      expect(textId).toBe(456);
    });

    it('extracts text ID from query ?text=789', () => {
      Object.defineProperty(window, 'location', {
        value: { pathname: '/', search: '?text=789' },
        writable: true
      });

      const component = textReaderData();
      const textId = component.getTextIdFromUrl();

      expect(textId).toBe(789);
    });

    it('extracts text ID from query ?tid=111', () => {
      Object.defineProperty(window, 'location', {
        value: { pathname: '/', search: '?tid=111' },
        writable: true
      });

      const component = textReaderData();
      const textId = component.getTextIdFromUrl();

      expect(textId).toBe(111);
    });

    it('extracts text ID from query ?start=222', () => {
      Object.defineProperty(window, 'location', {
        value: { pathname: '/', search: '?start=222' },
        writable: true
      });

      const component = textReaderData();
      const textId = component.getTextIdFromUrl();

      expect(textId).toBe(222);
    });

    it('returns 0 when no text ID found', () => {
      Object.defineProperty(window, 'location', {
        value: { pathname: '/other/page', search: '' },
        writable: true
      });

      const component = textReaderData();
      const textId = component.getTextIdFromUrl();

      expect(textId).toBe(0);
    });
  });

  // ===========================================================================
  // getRenderSettings Tests
  // ===========================================================================

  describe('getRenderSettings', () => {
    it('returns correct render settings', () => {
      const component = textReaderData();
      const settings = component.getRenderSettings();

      expect(settings.showAll).toBe(false);
      expect(settings.showTranslations).toBe(true);
      expect(settings.rightToLeft).toBe(false);
      expect(settings.textSize).toBe(100);
    });

    it('includes annotation settings', () => {
      const component = textReaderData();
      const settings = component.getRenderSettings();

      expect(settings.showLearning).toBe(true);
      expect(settings.displayStatTrans).toBe(true);
      expect(settings.modeTrans).toBe(1);
      expect(settings.annTextSize).toBe(75);
    });
  });

  // ===========================================================================
  // renderTextContent Tests
  // ===========================================================================

  describe('renderTextContent', () => {
    it('renders text to container', () => {
      document.body.innerHTML = '<div id="thetext"></div>';

      const component = textReaderData();
      component.renderTextContent();

      expect(renderText).toHaveBeenCalled();
      const container = document.getElementById('thetext');
      expect(container?.innerHTML).toBe('<div>rendered text</div>');
    });

    it('logs error when container not found', () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      const component = textReaderData();
      component.renderTextContent();

      expect(consoleSpy).toHaveBeenCalledWith('Text container not found');
    });

    it('applies RTL styling when enabled', () => {
      document.body.innerHTML = '<div id="thetext"></div>';
      mockWordStore.rightToLeft = true;

      const component = textReaderData();
      component.renderTextContent();

      const container = document.getElementById('thetext');
      expect(container?.style.direction).toBe('rtl');

      mockWordStore.rightToLeft = false;
    });

    it('applies text size when not 100%', () => {
      document.body.innerHTML = '<div id="thetext"></div>';
      mockWordStore.textSize = 125;

      const component = textReaderData();
      component.renderTextContent();

      const container = document.getElementById('thetext');
      expect(container?.style.fontSize).toBe('125%');

      mockWordStore.textSize = 100;
    });
  });

  // ===========================================================================
  // setupEventListeners Tests
  // ===========================================================================

  describe('setupEventListeners', () => {
    it('adds click listener to container', () => {
      document.body.innerHTML = '<div id="thetext"></div>';

      const container = document.getElementById('thetext')!;
      const addEventListenerSpy = vi.spyOn(container, 'addEventListener');

      const component = textReaderData();
      component.setupEventListeners();

      expect(addEventListenerSpy).toHaveBeenCalledWith('click', expect.any(Function));
    });

    it('adds keydown listener to document', () => {
      document.body.innerHTML = '<div id="thetext"></div>';

      const addEventListenerSpy = vi.spyOn(document, 'addEventListener');

      const component = textReaderData();
      component.setupEventListeners();

      expect(addEventListenerSpy).toHaveBeenCalledWith('keydown', expect.any(Function));
    });

    it('calls setupMultiWordSelection', () => {
      document.body.innerHTML = '<div id="thetext"></div>';

      const component = textReaderData();
      component.setupEventListeners();

      expect(setupMultiWordSelection).toHaveBeenCalled();
    });

    it('handles missing container gracefully', () => {
      const component = textReaderData();

      expect(() => component.setupEventListeners()).not.toThrow();
    });
  });

  // ===========================================================================
  // toggleShowAll Tests
  // ===========================================================================

  describe('toggleShowAll', () => {
    it('toggles showAll state', () => {
      document.body.innerHTML = '<div id="thetext"></div>';

      const component = textReaderData();
      expect(component.showAll).toBe(false);

      component.toggleShowAll();
      expect(component.showAll).toBe(true);

      component.toggleShowAll();
      expect(component.showAll).toBe(false);
    });

    it('updates store showAll', () => {
      document.body.innerHTML = '<div id="thetext"></div>';

      const component = textReaderData();
      component.toggleShowAll();

      expect(mockWordStore.showAll).toBe(true);
    });

    it('re-renders text content', () => {
      document.body.innerHTML = '<div id="thetext"></div>';

      const component = textReaderData();
      component.toggleShowAll();

      expect(renderText).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // toggleTranslations Tests
  // ===========================================================================

  describe('toggleTranslations', () => {
    it('toggles showTranslations state', () => {
      document.body.innerHTML = '<div id="thetext"></div>';

      const component = textReaderData();
      expect(component.showTranslations).toBe(true);

      component.toggleTranslations();
      expect(component.showTranslations).toBe(false);

      component.toggleTranslations();
      expect(component.showTranslations).toBe(true);
    });

    it('toggles hide-translations class on container', () => {
      document.body.innerHTML = '<div id="thetext"></div>';

      const component = textReaderData();
      component.toggleTranslations();

      const container = document.getElementById('thetext');
      expect(container?.classList.contains('hide-translations')).toBe(true);

      component.toggleTranslations();
      expect(container?.classList.contains('hide-translations')).toBe(false);
    });
  });

  // ===========================================================================
  // handleKeydown Tests
  // ===========================================================================

  describe('handleKeydown', () => {
    it('does nothing when popover is open', () => {
      mockWordStore.isPopoverOpen = true;

      const component = textReaderData();
      const event = new KeyboardEvent('keydown', { key: 'ArrowRight' });

      expect(() => component.handleKeydown(event)).not.toThrow();
    });

    it('does nothing when edit modal is open', () => {
      mockWordStore.isEditModalOpen = true;

      const component = textReaderData();
      const event = new KeyboardEvent('keydown', { key: 'ArrowRight' });

      expect(() => component.handleKeydown(event)).not.toThrow();
    });
  });

  // ===========================================================================
  // goBack Tests
  // ===========================================================================

  describe('goBack', () => {
    it('calls window.history.back', () => {
      const backSpy = vi.spyOn(window.history, 'back').mockImplementation(() => {});

      const component = textReaderData();
      component.goBack();

      expect(backSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // updateWordDisplay Tests
  // ===========================================================================

  describe('updateWordDisplay', () => {
    it('calls updateWordStatusInDOM', () => {
      const component = textReaderData();
      component.updateWordDisplay('ABC123', 5, 999);

      expect(updateWordStatusInDOM).toHaveBeenCalledWith('ABC123', 5, 999);
    });
  });

  // ===========================================================================
  // markAllWellKnown Tests
  // ===========================================================================

  describe('markAllWellKnown', () => {
    it('shows confirmation dialog', async () => {
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

      const component = textReaderData();
      await component.markAllWellKnown();

      expect(confirmSpy).toHaveBeenCalled();
    });

    it('does nothing when cancelled', async () => {
      vi.spyOn(window, 'confirm').mockReturnValue(false);

      const component = textReaderData();
      await component.markAllWellKnown();

      expect(TextsApi.markAllWellKnown).not.toHaveBeenCalled();
    });

    it('calls API when confirmed', async () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);

      const component = textReaderData();
      await component.markAllWellKnown();

      expect(TextsApi.markAllWellKnown).toHaveBeenCalledWith(123);
    });
  });

  // ===========================================================================
  // markAllIgnored Tests
  // ===========================================================================

  describe('markAllIgnored', () => {
    it('shows confirmation dialog', async () => {
      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

      const component = textReaderData();
      await component.markAllIgnored();

      expect(confirmSpy).toHaveBeenCalled();
    });

    it('does nothing when cancelled', async () => {
      vi.spyOn(window, 'confirm').mockReturnValue(false);

      const component = textReaderData();
      await component.markAllIgnored();

      expect(TextsApi.markAllIgnored).not.toHaveBeenCalled();
    });

    it('calls API when confirmed', async () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);

      const component = textReaderData();
      await component.markAllIgnored();

      expect(TextsApi.markAllIgnored).toHaveBeenCalledWith(123);
    });
  });

  // ===========================================================================
  // initTextReaderAlpine Tests
  // ===========================================================================

  describe('initTextReaderAlpine', () => {
    it('registers textReader component with Alpine', () => {
      initTextReaderAlpine();

      expect(Alpine.data).toHaveBeenCalledWith('textReader', textReaderData);
    });
  });

  // ===========================================================================
  // Global Window Exposure Tests
  // ===========================================================================

  describe('global window exposure', () => {
    it('exposes textReaderData on window', () => {
      expect(typeof window.textReaderData).toBe('function');
    });
  });
});

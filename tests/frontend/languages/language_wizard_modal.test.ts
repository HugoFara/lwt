/**
 * Tests for language_wizard_modal.ts - Language wizard modal component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock Alpine.js
vi.mock('alpinejs', () => ({
  default: {
    data: vi.fn()
  }
}));

// Mock lucide icons
vi.mock('../../../src/frontend/js/shared/icons/lucide_icons', () => ({
  initIcons: vi.fn()
}));

// Mock ajax utilities
vi.mock('../../../src/frontend/js/shared/utils/ajax_utilities', () => ({
  saveSetting: vi.fn()
}));

// Mock url utility
vi.mock('../../../src/frontend/js/shared/utils/url', () => ({
  url: vi.fn((path: string) => path)
}));

// Create shared mock stores
const mockLanguageStore = {
  definitions: {
    'English': { code: 'en' },
    'German': { code: 'de' },
    'French': { code: 'fr' }
  },
  openWizardModal: vi.fn(),
  closeWizardModal: vi.fn()
};

const mockFormStore = {};

// Mock stores
vi.mock('../../../src/frontend/js/modules/language/stores/language_store', () => ({
  getLanguageStore: vi.fn(() => mockLanguageStore)
}));

vi.mock('../../../src/frontend/js/modules/language/stores/language_form_store', () => ({
  getLanguageFormStore: vi.fn(() => mockFormStore)
}));

import Alpine from 'alpinejs';
import { wizardModalData, initWizardModalComponent } from '../../../src/frontend/js/modules/language/components/language_wizard_modal';
import { saveSetting } from '../../../src/frontend/js/shared/utils/ajax_utilities';

describe('language_wizard_modal.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // wizardModalData Factory Tests
  // ===========================================================================

  describe('wizardModalData', () => {
    it('creates component with default values', () => {
      const component = wizardModalData();

      expect(component.l1).toBe('');
      expect(component.l2).toBe('');
      expect(component.error).toBe(null);
    });

    it('has store reference', () => {
      const component = wizardModalData();

      expect(component.store).toBe(mockLanguageStore);
    });

    it('has formStore reference', () => {
      const component = wizardModalData();

      expect(component.formStore).toBe(mockFormStore);
    });
  });

  // ===========================================================================
  // sortedLanguages Tests
  // ===========================================================================

  describe('sortedLanguages', () => {
    it('returns sorted list of language names', () => {
      const component = wizardModalData();

      const sorted = component.sortedLanguages;

      expect(sorted).toEqual(['English', 'French', 'German']);
    });

    it('returns empty array when no definitions', () => {
      mockLanguageStore.definitions = null as unknown as Record<string, { code: string }>;

      const component = wizardModalData();
      const sorted = component.sortedLanguages;

      expect(sorted).toEqual([]);

      // Restore
      mockLanguageStore.definitions = {
        'English': { code: 'en' },
        'German': { code: 'de' },
        'French': { code: 'fr' }
      };
    });
  });

  // ===========================================================================
  // isValid Tests
  // ===========================================================================

  describe('isValid', () => {
    it('returns false when l1 is empty', () => {
      const component = wizardModalData();
      component.l1 = '';
      component.l2 = 'German';

      expect(component.isValid).toBe(false);
    });

    it('returns false when l2 is empty', () => {
      const component = wizardModalData();
      component.l1 = 'English';
      component.l2 = '';

      expect(component.isValid).toBe(false);
    });

    it('returns false when l1 equals l2', () => {
      const component = wizardModalData();
      component.l1 = 'English';
      component.l2 = 'English';

      expect(component.isValid).toBe(false);
    });

    it('returns true when l1 and l2 are different non-empty values', () => {
      const component = wizardModalData();
      component.l1 = 'English';
      component.l2 = 'German';

      expect(component.isValid).toBe(true);
    });
  });

  // ===========================================================================
  // init() Tests
  // ===========================================================================

  describe('init()', () => {
    it('calls init without error', () => {
      const component = wizardModalData();

      expect(() => component.init()).not.toThrow();
    });
  });

  // ===========================================================================
  // open() Tests
  // ===========================================================================

  describe('open()', () => {
    it('resets error to null', () => {
      const component = wizardModalData();
      component.error = 'Previous error';

      component.open();

      expect(component.error).toBe(null);
    });

    it('resets l2 to empty', () => {
      const component = wizardModalData();
      component.l2 = 'German';

      component.open();

      expect(component.l2).toBe('');
    });

    it('calls store openWizardModal', () => {
      const component = wizardModalData();

      component.open();

      expect(mockLanguageStore.openWizardModal).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // close() Tests
  // ===========================================================================

  describe('close()', () => {
    it('calls store closeWizardModal', () => {
      const component = wizardModalData();

      component.close();

      expect(mockLanguageStore.closeWizardModal).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // handleL1Change() Tests
  // ===========================================================================

  describe('handleL1Change()', () => {
    it('saves setting when l1 is set', () => {
      const component = wizardModalData();
      component.l1 = 'English';

      component.handleL1Change();

      expect(saveSetting).toHaveBeenCalledWith('currentnativelanguage', 'English');
    });

    it('does not save when l1 is empty', () => {
      const component = wizardModalData();
      component.l1 = '';

      component.handleL1Change();

      expect(saveSetting).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // apply() Tests
  // ===========================================================================

  describe('apply()', () => {
    it('sets error when l1 is empty', () => {
      const component = wizardModalData();
      component.l1 = '';
      component.l2 = 'German';

      component.apply();

      expect(component.error).toBe('Please choose your native language (L1)!');
    });

    it('sets error when l2 is empty', () => {
      const component = wizardModalData();
      component.l1 = 'English';
      component.l2 = '';

      component.apply();

      expect(component.error).toBe('Please choose the language you want to study (L2)!');
    });

    it('sets error when l1 equals l2', () => {
      const component = wizardModalData();
      component.l1 = 'English';
      component.l2 = 'English';

      component.apply();

      expect(component.error).toBe('L1 and L2 languages must be different!');
    });

    it('stores wizard data in sessionStorage on success', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      const component = wizardModalData();
      component.l1 = 'English';
      component.l2 = 'German';

      component.apply();

      const stored = sessionStorage.getItem('lwt_language_wizard');
      expect(stored).not.toBeNull();

      const data = JSON.parse(stored!);
      expect(data.l1).toBe('English');
      expect(data.l2).toBe('German');

      sessionStorage.removeItem('lwt_language_wizard');
      window.location = originalLocation;
    });

    it('navigates to language form on success', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      const component = wizardModalData();
      component.l1 = 'English';
      component.l2 = 'German';

      component.apply();

      expect(window.location.href).toBe('/languages?new=1&wizard=1');

      sessionStorage.removeItem('lwt_language_wizard');
      window.location = originalLocation;
    });

    it('clears error on successful apply', () => {
      const originalLocation = window.location;
      delete (window as { location?: Location }).location;
      window.location = { href: '' } as Location;

      const component = wizardModalData();
      component.l1 = 'English';
      component.l2 = 'German';
      component.error = 'Previous error';

      component.apply();

      expect(component.error).toBe(null);

      sessionStorage.removeItem('lwt_language_wizard');
      window.location = originalLocation;
    });
  });

  // ===========================================================================
  // initWizardModalComponent Tests
  // ===========================================================================

  describe('initWizardModalComponent', () => {
    it('registers wizardModal component with Alpine', () => {
      initWizardModalComponent();

      expect(Alpine.data).toHaveBeenCalledWith('wizardModal', wizardModalData);
    });
  });

  // ===========================================================================
  // Global Window Exposure Tests
  // ===========================================================================

  describe('global window exposure', () => {
    it('exposes wizardModalData on window', () => {
      expect(typeof window.wizardModalData).toBe('function');
    });
  });
});

/**
 * Tests for dictionary_import.ts - Dictionary import Alpine component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { dictionaryImportData, initDictionaryImportAlpine } from '../../../src/frontend/js/modules/dictionary/pages/dictionary_import';

// Mock Alpine.js
vi.mock('alpinejs', () => ({
  default: {
    data: vi.fn()
  }
}));

import Alpine from 'alpinejs';

describe('dictionary_import.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // dictionaryImportData Factory Function Tests
  // ===========================================================================

  describe('dictionaryImportData', () => {
    it('creates component with default values', () => {
      const component = dictionaryImportData();

      expect(component.format).toBe('csv');
      expect(component.fileName).toBe('');
      expect(component.submitting).toBe(false);
    });

    it('has correct accept types for csv format', () => {
      const component = dictionaryImportData();

      expect(component.acceptTypes.csv).toBe('.csv,.tsv,.txt');
    });

    it('has correct accept types for json format', () => {
      const component = dictionaryImportData();

      expect(component.acceptTypes.json).toBe('.json');
    });

    it('has correct accept types for stardict format', () => {
      const component = dictionaryImportData();

      expect(component.acceptTypes.stardict).toBe('.ifo');
    });
  });

  // ===========================================================================
  // fileSelected() Method Tests
  // ===========================================================================

  describe('fileSelected()', () => {
    it('updates fileName when file is selected', () => {
      const component = dictionaryImportData();

      const mockFile = new File(['content'], 'test-dictionary.csv', { type: 'text/csv' });
      const mockInput = { files: [mockFile] } as unknown as HTMLInputElement;
      const event = { target: mockInput } as unknown as Event;

      component.fileSelected(event);

      expect(component.fileName).toBe('test-dictionary.csv');
    });

    it('clears fileName when no file is selected', () => {
      const component = dictionaryImportData();
      component.fileName = 'previous-file.csv';

      const mockInput = { files: [] } as unknown as HTMLInputElement;
      const event = { target: mockInput } as unknown as Event;

      component.fileSelected(event);

      expect(component.fileName).toBe('');
    });

    it('clears fileName when files is null', () => {
      const component = dictionaryImportData();
      component.fileName = 'previous-file.csv';

      const mockInput = { files: null } as unknown as HTMLInputElement;
      const event = { target: mockInput } as unknown as Event;

      component.fileSelected(event);

      expect(component.fileName).toBe('');
    });

    it('handles json file selection', () => {
      const component = dictionaryImportData();
      component.format = 'json';

      const mockFile = new File(['{}'], 'dictionary.json', { type: 'application/json' });
      const mockInput = { files: [mockFile] } as unknown as HTMLInputElement;
      const event = { target: mockInput } as unknown as Event;

      component.fileSelected(event);

      expect(component.fileName).toBe('dictionary.json');
    });

    it('handles stardict file selection', () => {
      const component = dictionaryImportData();
      component.format = 'stardict';

      const mockFile = new File(['test'], 'dict.ifo', { type: 'application/octet-stream' });
      const mockInput = { files: [mockFile] } as unknown as HTMLInputElement;
      const event = { target: mockInput } as unknown as Event;

      component.fileSelected(event);

      expect(component.fileName).toBe('dict.ifo');
    });
  });

  // ===========================================================================
  // resetOptions() Method Tests
  // ===========================================================================

  describe('resetOptions()', () => {
    it('exists and is callable', () => {
      const component = dictionaryImportData();

      expect(typeof component.resetOptions).toBe('function');
      expect(() => component.resetOptions()).not.toThrow();
    });

    it('can be extended for format-specific options clearing', () => {
      const component = dictionaryImportData();

      // Format can be changed
      component.format = 'json';

      // resetOptions should work without errors
      component.resetOptions();

      // Format remains changed (resetOptions doesn't reset format)
      expect(component.format).toBe('json');
    });
  });

  // ===========================================================================
  // Format Switching Tests
  // ===========================================================================

  describe('format switching', () => {
    it('allows changing format to csv', () => {
      const component = dictionaryImportData();
      component.format = 'json';

      component.format = 'csv';

      expect(component.format).toBe('csv');
    });

    it('allows changing format to json', () => {
      const component = dictionaryImportData();

      component.format = 'json';

      expect(component.format).toBe('json');
    });

    it('allows changing format to stardict', () => {
      const component = dictionaryImportData();

      component.format = 'stardict';

      expect(component.format).toBe('stardict');
    });

    it('can check accept types for current format', () => {
      const component = dictionaryImportData();

      expect(component.acceptTypes[component.format]).toBe('.csv,.tsv,.txt');

      component.format = 'json';
      expect(component.acceptTypes[component.format]).toBe('.json');

      component.format = 'stardict';
      expect(component.acceptTypes[component.format]).toBe('.ifo');
    });
  });

  // ===========================================================================
  // initDictionaryImportAlpine Tests
  // ===========================================================================

  describe('initDictionaryImportAlpine', () => {
    it('registers dictionaryImport component with Alpine', () => {
      initDictionaryImportAlpine();

      expect(Alpine.data).toHaveBeenCalledWith('dictionaryImport', dictionaryImportData);
    });

    it('can be called multiple times without error', () => {
      expect(() => {
        initDictionaryImportAlpine();
        initDictionaryImportAlpine();
      }).not.toThrow();
    });
  });

  // ===========================================================================
  // Global Window Exposure Tests
  // ===========================================================================

  describe('global window exposure', () => {
    it('exposes dictionaryImportData on window', () => {
      expect(typeof window.dictionaryImportData).toBe('function');
    });

    it('exposes initDictionaryImportAlpine on window', () => {
      expect(typeof window.initDictionaryImportAlpine).toBe('function');
    });

    it('window.dictionaryImportData creates valid component', () => {
      const component = window.dictionaryImportData();

      expect(component.format).toBe('csv');
      expect(component.fileName).toBe('');
      expect(component.submitting).toBe(false);
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('full workflow: select file, change format, reset', () => {
      const component = dictionaryImportData();

      // Initially csv format
      expect(component.format).toBe('csv');

      // Select a csv file
      const csvFile = new File(['data'], 'words.csv', { type: 'text/csv' });
      component.fileSelected({ target: { files: [csvFile] } } as unknown as Event);
      expect(component.fileName).toBe('words.csv');

      // Change format to json
      component.format = 'json';
      component.resetOptions();

      // Select a json file
      const jsonFile = new File(['{}'], 'dict.json', { type: 'application/json' });
      component.fileSelected({ target: { files: [jsonFile] } } as unknown as Event);
      expect(component.fileName).toBe('dict.json');
    });

    it('handles submitting state correctly', () => {
      const component = dictionaryImportData();

      expect(component.submitting).toBe(false);

      component.submitting = true;
      expect(component.submitting).toBe(true);

      component.submitting = false;
      expect(component.submitting).toBe(false);
    });
  });
});

/**
 * Tests for form_initialization.ts - Form initialization module
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  clearRightFrameOnUnload,
  changeTextboxesLanguage,
  initTextEditForm,
  initWordEditForm,
  autoInitializeForms
} from '../../../src/frontend/js/forms/form_initialization';

// Mock unloadformcheck
vi.mock('../../../src/frontend/js/forms/unloadformcheck', () => ({
  lwtFormCheck: {
    askBeforeExit: vi.fn()
  }
}));

import { lwtFormCheck } from '../../../src/frontend/js/forms/unloadformcheck';

describe('form_initialization.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    $(window).off('beforeunload');
  });

  // ===========================================================================
  // clearRightFrameOnUnload Tests
  // ===========================================================================

  describe('clearRightFrameOnUnload', () => {
    it('sets up beforeunload handler', () => {
      clearRightFrameOnUnload();

      // The function should have set up a handler - we can verify by checking
      // that it doesn't throw when triggered
      expect(() => $(window).trigger('beforeunload')).not.toThrow();
    });

    it('attempts to clear right frame on beforeunload', async () => {
      // Mock window.parent.frames
      const mockFrame = { location: { href: '' } };
      const mockParent = {
        frames: {
          ru: mockFrame
        }
      };

      Object.defineProperty(window, 'parent', {
        value: mockParent,
        writable: true,
        configurable: true
      });

      clearRightFrameOnUnload();

      // Trigger beforeunload
      $(window).trigger('beforeunload');

      // Wait for setTimeout
      await new Promise(resolve => setTimeout(resolve, 10));

      // Frame should be cleared
      expect(mockFrame.location.href).toBe('empty.html');
    });

    it('handles missing parent gracefully', async () => {
      Object.defineProperty(window, 'parent', {
        value: null,
        writable: true,
        configurable: true
      });

      clearRightFrameOnUnload();

      // Should not throw
      expect(() => $(window).trigger('beforeunload')).not.toThrow();
    });
  });

  // ===========================================================================
  // changeTextboxesLanguage Tests
  // ===========================================================================

  describe('changeTextboxesLanguage', () => {
    it('sets lang attribute on TxTitle and TxText', () => {
      document.body.innerHTML = `
        <select id="TxLgID">
          <option value="1">English</option>
          <option value="2">French</option>
        </select>
        <input id="TxTitle" type="text" />
        <textarea id="TxText"></textarea>
      `;

      const languageData = {
        '1': 'en',
        '2': 'fr'
      };

      const langSelect = document.getElementById('TxLgID') as HTMLSelectElement;
      langSelect.value = '2';

      changeTextboxesLanguage(languageData);

      expect($('#TxTitle').attr('lang')).toBe('fr');
      expect($('#TxText').attr('lang')).toBe('fr');
    });

    it('handles missing language select element', () => {
      document.body.innerHTML = `
        <input id="TxTitle" type="text" />
        <textarea id="TxText"></textarea>
      `;

      expect(() => changeTextboxesLanguage({ '1': 'en' })).not.toThrow();
    });

    it('sets empty string when language not in data', () => {
      document.body.innerHTML = `
        <select id="TxLgID">
          <option value="99">Unknown</option>
        </select>
        <input id="TxTitle" type="text" lang="en" />
        <textarea id="TxText" lang="en"></textarea>
      `;

      const langSelect = document.getElementById('TxLgID') as HTMLSelectElement;
      langSelect.value = '99';

      changeTextboxesLanguage({ '1': 'en' });

      expect($('#TxTitle').attr('lang')).toBe('');
      expect($('#TxText').attr('lang')).toBe('');
    });
  });

  // ===========================================================================
  // initTextEditForm Tests
  // ===========================================================================

  describe('initTextEditForm', () => {
    it('does nothing when config element does not exist', () => {
      expect(() => initTextEditForm()).not.toThrow();
      expect(lwtFormCheck.askBeforeExit).not.toHaveBeenCalled();
    });

    it('parses config from JSON and sets up language change handler', () => {
      document.body.innerHTML = `
        <script id="text-edit-config" type="application/json">
          {"languageData": {"1": "en", "2": "fr"}}
        </script>
        <select data-action="change-language" id="TxLgID">
          <option value="1">English</option>
          <option value="2">French</option>
        </select>
        <input id="TxTitle" type="text" />
        <textarea id="TxText"></textarea>
      `;

      initTextEditForm();

      // Initial language should be applied
      expect($('#TxTitle').attr('lang')).toBe('en');
      expect($('#TxText').attr('lang')).toBe('en');

      // Change language
      const langSelect = document.querySelector('[data-action="change-language"]') as HTMLSelectElement;
      langSelect.value = '2';
      langSelect.dispatchEvent(new Event('change'));

      expect($('#TxTitle').attr('lang')).toBe('fr');
      expect($('#TxText').attr('lang')).toBe('fr');
    });

    it('sets up form change tracking', () => {
      document.body.innerHTML = `
        <script id="text-edit-config" type="application/json">
          {"languageData": {}}
        </script>
      `;

      initTextEditForm();

      expect(lwtFormCheck.askBeforeExit).toHaveBeenCalled();
    });

    it('handles invalid JSON config gracefully', () => {
      document.body.innerHTML = `
        <script id="text-edit-config" type="application/json">
          {invalid json}
        </script>
      `;

      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      expect(() => initTextEditForm()).not.toThrow();
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // initWordEditForm Tests
  // ===========================================================================

  describe('initWordEditForm', () => {
    it('sets up form change tracking', () => {
      initWordEditForm();

      expect(lwtFormCheck.askBeforeExit).toHaveBeenCalled();
    });

    it('sets up right frame cleanup on unload', () => {
      const mockFrame = { location: { href: '' } };
      Object.defineProperty(window, 'parent', {
        value: { frames: { ru: mockFrame } },
        writable: true,
        configurable: true
      });

      initWordEditForm();

      // Trigger beforeunload and wait
      $(window).trigger('beforeunload');
    });
  });

  // ===========================================================================
  // autoInitializeForms Tests
  // ===========================================================================

  describe('autoInitializeForms', () => {
    it('initializes text edit form when config is present', () => {
      document.body.innerHTML = `
        <script id="text-edit-config" type="application/json">
          {"languageData": {}}
        </script>
      `;

      autoInitializeForms();

      expect(lwtFormCheck.askBeforeExit).toHaveBeenCalled();
    });

    it('initializes forms with data-lwt-form-check attribute', () => {
      document.body.innerHTML = `
        <form data-lwt-form-check="true"></form>
        <form data-lwt-form-check="true"></form>
      `;

      autoInitializeForms();

      // Should call askBeforeExit for each form (but only once overall since it's global)
      expect(lwtFormCheck.askBeforeExit).toHaveBeenCalled();

      // Forms should be marked as initialized
      const forms = document.querySelectorAll('form');
      forms.forEach(form => {
        expect(form.hasAttribute('data-lwt-form-init')).toBe(true);
      });
    });

    it('does not re-initialize forms already marked', () => {
      document.body.innerHTML = `
        <form data-lwt-form-check="true" data-lwt-form-init="true"></form>
      `;

      vi.clearAllMocks();

      autoInitializeForms();

      // askBeforeExit should not be called for already-initialized form
      expect(lwtFormCheck.askBeforeExit).not.toHaveBeenCalled();
    });

    it('initializes forms with data-lwt-clear-frame attribute', async () => {
      document.body.innerHTML = `
        <form data-lwt-clear-frame="true"></form>
      `;

      const mockFrame = { location: { href: '' } };
      Object.defineProperty(window, 'parent', {
        value: { frames: { ru: mockFrame } },
        writable: true,
        configurable: true
      });

      autoInitializeForms();

      // Trigger beforeunload
      $(window).trigger('beforeunload');

      // Wait for setTimeout
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(mockFrame.location.href).toBe('empty.html');
    });

    it('marks validate class forms as initialized', () => {
      document.body.innerHTML = `
        <form class="validate"></form>
        <form class="validate"></form>
      `;

      autoInitializeForms();

      const forms = document.querySelectorAll('form.validate');
      forms.forEach(form => {
        expect(form.hasAttribute('data-lwt-form-init')).toBe(true);
      });
    });

    it('does not re-mark already initialized validate forms', () => {
      document.body.innerHTML = `
        <form class="validate" data-lwt-form-init="true"></form>
      `;

      autoInitializeForms();

      const form = document.querySelector('form.validate')!;
      expect(form.getAttribute('data-lwt-form-init')).toBe('true');
    });
  });
});

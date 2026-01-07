/**
 * Tests for word_list_table.ts - Word list table Alpine component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { wordListTableApp } from '../../../src/frontend/js/modules/vocabulary/stores/word_list_table';

// Mock bulk_actions module
vi.mock('../../../src/frontend/js/shared/forms/bulk_actions', () => ({
  selectToggle: vi.fn(),
  multiActionGo: vi.fn(),
  allActionGo: vi.fn()
}));

import { selectToggle, multiActionGo, allActionGo } from '../../../src/frontend/js/shared/forms/bulk_actions';

describe('word_list_table.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // wordListTableApp Component Tests
  // ===========================================================================

  describe('wordListTableApp', () => {
    it('creates component with default config', () => {
      const component = wordListTableApp();

      expect(component.recno).toBe(0);
      expect(component.formName).toBe('form2');
      expect(component.isLoading).toBe(false);
    });

    it('creates component with custom config', () => {
      const component = wordListTableApp({ recno: 100, formName: 'customForm' });

      expect(component.recno).toBe(100);
      expect(component.formName).toBe('customForm');
    });

    it('init reads config from JSON script tag', () => {
      document.body.innerHTML = `
        <script type="application/json" id="word-list-table-config">
          {"recno": 50, "formName": "form3"}
        </script>
      `;

      const component = wordListTableApp();
      component.init();

      expect(component.recno).toBe(50);
      expect(component.formName).toBe('form3');
    });

    it('init hides waitinfo element', () => {
      document.body.innerHTML = `
        <div id="waitinfo">Loading...</div>
      `;

      const component = wordListTableApp();
      component.init();

      const waitInfo = document.getElementById('waitinfo');
      expect(waitInfo?.classList.contains('hide')).toBe(true);
    });

    it('init handles missing config element gracefully', () => {
      const component = wordListTableApp({ recno: 25 });
      component.init();

      // Should keep the passed config
      expect(component.recno).toBe(25);
    });

    it('init handles invalid JSON in config element', () => {
      document.body.innerHTML = `
        <script type="application/json" id="word-list-table-config">
          invalid json
        </script>
      `;

      const component = wordListTableApp({ recno: 10 });
      component.init();

      // Should keep default values
      expect(component.recno).toBe(10);
    });
  });

  // ===========================================================================
  // Mark All/None Tests
  // ===========================================================================

  describe('markAll and markNone', () => {
    it('markAll calls selectToggle with true', () => {
      const component = wordListTableApp();
      component.markAll();

      expect(selectToggle).toHaveBeenCalledWith(true, 'form2');
    });

    it('markNone calls selectToggle with false', () => {
      const component = wordListTableApp();
      component.markNone();

      expect(selectToggle).toHaveBeenCalledWith(false, 'form2');
    });

    it('uses custom formName for toggle', () => {
      const component = wordListTableApp({ formName: 'myForm' });
      component.markAll();

      expect(selectToggle).toHaveBeenCalledWith(true, 'myForm');
    });
  });

  // ===========================================================================
  // Action Handler Tests
  // ===========================================================================

  describe('handleAllAction', () => {
    it('calls allActionGo with form and recno', () => {
      document.body.innerHTML = `
        <form name="form2">
          <select id="all-action">
            <option value="delall">Delete All</option>
          </select>
        </form>
      `;

      const component = wordListTableApp({ recno: 42 });
      const select = document.getElementById('all-action') as HTMLSelectElement;
      const event = { target: select } as unknown as Event;

      component.handleAllAction(event);

      expect(allActionGo).toHaveBeenCalledWith(
        document.forms.namedItem('form2'),
        select,
        42
      );
    });

    it('does nothing when form is not found', () => {
      const component = wordListTableApp({ formName: 'nonexistent' });
      const select = document.createElement('select');
      const event = { target: select } as unknown as Event;

      component.handleAllAction(event);

      expect(allActionGo).not.toHaveBeenCalled();
    });
  });

  describe('handleMarkAction', () => {
    it('calls multiActionGo with form and select', () => {
      document.body.innerHTML = `
        <form name="form2">
          <select id="mark-action">
            <option value="del">Delete</option>
          </select>
        </form>
      `;

      const component = wordListTableApp();
      const select = document.getElementById('mark-action') as HTMLSelectElement;
      const event = { target: select } as unknown as Event;

      component.handleMarkAction(event);

      expect(multiActionGo).toHaveBeenCalledWith(
        document.forms.namedItem('form2'),
        select
      );
    });
  });

  // ===========================================================================
  // getForm Tests
  // ===========================================================================

  describe('getForm', () => {
    it('returns form element when exists', () => {
      document.body.innerHTML = `<form name="form2"></form>`;

      const component = wordListTableApp();
      const form = component.getForm();

      expect(form).toBeInstanceOf(HTMLFormElement);
      expect(form?.name).toBe('form2');
    });

    it('returns null when form does not exist', () => {
      const component = wordListTableApp({ formName: 'nonexistent' });
      const form = component.getForm();

      expect(form).toBeNull();
    });
  });

  // ===========================================================================
  // Window Export Tests
  // ===========================================================================

  describe('Window export', () => {
    it('exports wordListTableApp to window', () => {
      expect(typeof window.wordListTableApp).toBe('function');
    });
  });
});

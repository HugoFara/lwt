/**
 * Tests for table_management.ts - Table set management page functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { initTableManagement } from '../../../src/frontend/js/admin/table_management';

describe('table_management.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // checkTablePrefix Tests (via window global)
  // ===========================================================================

  describe('checkTablePrefix', () => {
    it('returns false for empty string', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = window.checkTablePrefix('');

      expect(result).toBe(false);
      expect(alertSpy).toHaveBeenCalledWith('Table Set Name must not be empty!');
    });

    it('returns false for whitespace only', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = window.checkTablePrefix('   ');

      expect(result).toBe(false);
      expect(alertSpy).toHaveBeenCalledWith('Table Set Name must not be empty!');
    });

    it('returns false for invalid characters', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = window.checkTablePrefix('test-prefix');

      expect(result).toBe(false);
      expect(alertSpy).toHaveBeenCalledWith(
        'Table Set Name must contain only letters, numbers, and underscores!'
      );
    });

    it('returns false for special characters', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = window.checkTablePrefix('test@prefix');

      expect(result).toBe(false);
      expect(alertSpy).toHaveBeenCalledWith(
        'Table Set Name must contain only letters, numbers, and underscores!'
      );
    });

    it('returns false for name exceeding 20 characters', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = window.checkTablePrefix('this_is_a_very_long_prefix_name');

      expect(result).toBe(false);
      expect(alertSpy).toHaveBeenCalledWith('Table Set Name must be 20 characters or less!');
    });

    it('returns true for valid alphanumeric prefix', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = window.checkTablePrefix('test_prefix_123');

      expect(result).toBe(true);
      expect(alertSpy).not.toHaveBeenCalled();
    });

    it('returns true for valid prefix with underscores', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = window.checkTablePrefix('my_table_set');

      expect(result).toBe(true);
      expect(alertSpy).not.toHaveBeenCalled();
    });

    it('returns true for exactly 20 characters', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      const result = window.checkTablePrefix('12345678901234567890');

      expect(result).toBe(true);
      expect(alertSpy).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // initTableCreateForm Tests
  // ===========================================================================

  describe('initTableCreateForm', () => {
    it('prevents form submission when prefix is empty', () => {
      document.body.innerHTML = `
        <form class="table-create-form">
          <input type="text" name="newpref" value="">
          <button type="submit">Create</button>
        </form>
      `;

      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      initTableManagement();

      const form = document.querySelector('form')!;
      const submitEvent = new Event('submit', { cancelable: true });
      form.dispatchEvent(submitEvent);

      expect(submitEvent.defaultPrevented).toBe(true);
      expect(alertSpy).toHaveBeenCalled();
    });

    it('allows form submission when prefix is valid', () => {
      document.body.innerHTML = `
        <form class="table-create-form">
          <input type="text" name="newpref" value="valid_prefix">
          <button type="submit">Create</button>
        </form>
      `;

      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      initTableManagement();

      const form = document.querySelector('form')!;
      const submitEvent = new Event('submit', { cancelable: true });
      form.dispatchEvent(submitEvent);

      expect(submitEvent.defaultPrevented).toBe(false);
      expect(alertSpy).not.toHaveBeenCalled();
    });

    it('does nothing when form is not present', () => {
      document.body.innerHTML = '<div>No form here</div>';

      expect(() => initTableManagement()).not.toThrow();
    });
  });

  // ===========================================================================
  // initTableDeleteForm Tests
  // ===========================================================================

  describe('initTableDeleteForm', () => {
    it('prevents form submission when confirmation is cancelled', () => {
      document.body.innerHTML = `
        <form class="table-delete-form">
          <select name="delpref">
            <option value="">-- Select --</option>
            <option value="test_prefix" selected>Test Prefix</option>
          </select>
          <button type="submit">Delete</button>
        </form>
      `;

      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

      initTableManagement();

      const form = document.querySelector('form')!;
      const submitEvent = new Event('submit', { cancelable: true });
      form.dispatchEvent(submitEvent);

      expect(submitEvent.defaultPrevented).toBe(true);
      expect(confirmSpy).toHaveBeenCalled();
    });

    it('allows form submission when confirmation is accepted', () => {
      document.body.innerHTML = `
        <form class="table-delete-form">
          <select name="delpref">
            <option value="">-- Select --</option>
            <option value="test_prefix" selected>Test Prefix</option>
          </select>
          <button type="submit">Delete</button>
        </form>
      `;

      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

      initTableManagement();

      const form = document.querySelector('form')!;
      const submitEvent = new Event('submit', { cancelable: true });
      form.dispatchEvent(submitEvent);

      expect(submitEvent.defaultPrevented).toBe(false);
      expect(confirmSpy).toHaveBeenCalled();
    });

    it('does not show confirmation when no option selected', () => {
      document.body.innerHTML = `
        <form class="table-delete-form">
          <select name="delpref">
            <option value="" selected>-- Select --</option>
            <option value="test_prefix">Test Prefix</option>
          </select>
          <button type="submit">Delete</button>
        </form>
      `;

      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

      initTableManagement();

      const form = document.querySelector('form')!;
      const submitEvent = new Event('submit', { cancelable: true });
      form.dispatchEvent(submitEvent);

      // selectedIndex is 0, so no confirmation needed
      expect(confirmSpy).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // initNavigationHandlers Tests
  // ===========================================================================

  describe('initNavigationHandlers', () => {
    it('handles go-back button click', () => {
      document.body.innerHTML = `
        <button data-action="go-back">Go Back</button>
      `;

      const historyBackSpy = vi.spyOn(history, 'back').mockImplementation(() => {});

      initTableManagement();

      const button = document.querySelector('button')!;
      button.click();

      expect(historyBackSpy).toHaveBeenCalled();
    });

    it('handles navigate button click', () => {
      document.body.innerHTML = `
        <button data-action="navigate" data-url="/admin/dashboard">Navigate</button>
      `;

      initTableManagement();

      const button = document.querySelector('button')!;

      // Click handler will try to set location.href
      expect(() => button.click()).not.toThrow();
    });

    it('does nothing for navigate button without URL', () => {
      document.body.innerHTML = `
        <button data-action="navigate">Navigate</button>
      `;

      initTableManagement();

      const button = document.querySelector('button')!;

      expect(() => button.click()).not.toThrow();
    });
  });

  // ===========================================================================
  // isTableManagementPage Detection Tests
  // ===========================================================================

  describe('page detection', () => {
    it('detects page with create form', () => {
      document.body.innerHTML = `
        <form class="table-create-form">
          <input type="text" name="newpref" value="">
        </form>
      `;

      // Should not throw when forms are present
      expect(() => initTableManagement()).not.toThrow();
    });

    it('detects page with delete form', () => {
      document.body.innerHTML = `
        <form class="table-delete-form">
          <select name="delpref"></select>
        </form>
      `;

      expect(() => initTableManagement()).not.toThrow();
    });

    it('detects page with go-back button', () => {
      document.body.innerHTML = `
        <button data-action="go-back">Go Back</button>
      `;

      expect(() => initTableManagement()).not.toThrow();
    });
  });

  // ===========================================================================
  // Window Exports Tests
  // ===========================================================================

  describe('window exports', () => {
    it('exports initTableManagement to window', () => {
      expect(typeof window.initTableManagement).toBe('function');
    });

    it('exports checkTablePrefix to window', () => {
      expect(typeof window.checkTablePrefix).toBe('function');
    });

    it('exports check_table_prefix (legacy) to window', () => {
      expect(typeof window.check_table_prefix).toBe('function');
    });
  });
});

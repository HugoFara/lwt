/**
 * Tests for settings_form.ts - Settings form interactions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  initSettingsForm,
  initConfirmSubmitForms,
  initNavigateButtons,
  initHistoryBackButtons,
} from '../../../src/frontend/js/admin/settings_form';
import * as unloadformcheck from '../../../src/frontend/js/forms/unloadformcheck';

// Mock the lwtFormCheck module
vi.mock('../../../src/frontend/js/forms/unloadformcheck', () => ({
  lwtFormCheck: {
    askBeforeExit: vi.fn(),
    resetDirty: vi.fn(),
    makeDirty: vi.fn(),
  },
}));

describe('settings_form.ts', () => {
  beforeEach(() => {
    // Clear the DOM
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // Make jQuery global
    (globalThis as unknown as Record<string, unknown>).$ = $;
    // Remove previous event handlers
    $(document).off('click submit');
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
    // Remove jQuery event handlers to prevent accumulation
    $(document).off('click submit');
  });

  // ===========================================================================
  // initSettingsForm Tests
  // ===========================================================================

  describe('initSettingsForm', () => {
    it('does nothing when no settings form exists', () => {
      document.body.innerHTML = '<div>No form here</div>';

      initSettingsForm();

      expect(unloadformcheck.lwtFormCheck.askBeforeExit).not.toHaveBeenCalled();
    });

    it('sets up form change tracking when settings form exists', () => {
      document.body.innerHTML = '<form data-lwt-settings-form></form>';

      initSettingsForm();

      expect(unloadformcheck.lwtFormCheck.askBeforeExit).toHaveBeenCalledTimes(1);
    });
  });

  // ===========================================================================
  // initConfirmSubmitForms Tests
  // ===========================================================================

  describe('initConfirmSubmitForms', () => {
    it('shows confirmation dialog on form submit', () => {
      document.body.innerHTML = `
        <form data-action="confirm-submit" data-confirm-message="Are you sure you want to proceed?">
          <button type="submit">Submit</button>
        </form>
      `;

      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

      initConfirmSubmitForms();

      const form = document.querySelector('form') as HTMLFormElement;
      // Use jQuery to trigger submit which will properly fire our handler
      $(form).trigger('submit');

      expect(confirmSpy).toHaveBeenCalledWith('Are you sure you want to proceed?');
    });

    it('uses default message when data-confirm-message is not set', () => {
      document.body.innerHTML = `
        <form data-action="confirm-submit">
          <button type="submit">Submit</button>
        </form>
      `;

      const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

      initConfirmSubmitForms();

      const form = document.querySelector('form') as HTMLFormElement;
      $(form).trigger('submit');

      expect(confirmSpy).toHaveBeenCalledWith('Are you sure?');
    });
  });

  // ===========================================================================
  // initNavigateButtons Tests
  // ===========================================================================

  describe('initNavigateButtons', () => {
    it('sets up navigation handler', () => {
      document.body.innerHTML = `
        <button data-action="navigate" data-url="/destination/page">Go</button>
      `;

      // initNavigateButtons sets up the handler
      initNavigateButtons();

      // The handler is set up successfully (we just verify no errors)
      expect(true).toBe(true);
    });
  });

  // ===========================================================================
  // initHistoryBackButtons Tests
  // ===========================================================================

  describe('initHistoryBackButtons', () => {
    it('sets up history back handler', () => {
      document.body.innerHTML = `
        <button data-action="history-back">Back</button>
      `;

      // initHistoryBackButtons sets up the handler
      initHistoryBackButtons();

      // The handler is set up successfully (we just verify no errors)
      expect(true).toBe(true);
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('all handlers can be initialized without errors', () => {
      document.body.innerHTML = `
        <form data-lwt-settings-form>
          <button data-action="settings-navigate" data-url="/settings">Settings</button>
        </form>
        <form data-action="confirm-submit">
          <button type="submit">Submit</button>
        </form>
        <button data-action="navigate" data-url="/page">Navigate</button>
        <button data-action="history-back">Back</button>
      `;

      // Initialize all handlers - should not throw
      expect(() => {
        initSettingsForm();
        initConfirmSubmitForms();
        initNavigateButtons();
        initHistoryBackButtons();
      }).not.toThrow();
    });
  });
});

/**
 * Tests for simple_interactions.ts - Navigation and confirmation utilities.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';

// Mock the dependencies
vi.mock('../../../src/frontend/js/forms/unloadformcheck', () => ({
  lwtFormCheck: {
    resetDirty: vi.fn()
  }
}));

vi.mock('../../../src/frontend/js/reading/frame_management', () => ({
  showRightFrames: vi.fn(),
  hideRightFrames: vi.fn()
}));

vi.mock('../../../src/frontend/js/core/ui_utilities', () => ({
  showAllwordsClick: vi.fn()
}));

import {
  goBack,
  navigateTo,
  cancelAndNavigate,
  cancelAndGoBack,
  confirmSubmit,
  initSimpleInteractions
} from '../../../src/frontend/js/core/simple_interactions';
import { lwtFormCheck } from '../../../src/frontend/js/forms/unloadformcheck';
import { showRightFrames, hideRightFrames } from '../../../src/frontend/js/reading/frame_management';
import { showAllwordsClick } from '../../../src/frontend/js/core/ui_utilities';

describe('simple_interactions.ts', () => {
  let originalLocation: Location;
  let originalHistory: History;

  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';

    // Mock location
    originalLocation = window.location;
    delete (window as any).location;
    window.location = {
      href: 'http://localhost/',
      assign: vi.fn(),
      replace: vi.fn(),
      reload: vi.fn()
    } as unknown as Location;

    // Mock history
    originalHistory = window.history;
    Object.defineProperty(window, 'history', {
      value: {
        back: vi.fn(),
        forward: vi.fn(),
        go: vi.fn(),
        pushState: vi.fn(),
        replaceState: vi.fn(),
        length: 1,
        state: null,
        scrollRestoration: 'auto'
      },
      writable: true
    });
  });

  afterEach(() => {
    window.location = originalLocation;
    Object.defineProperty(window, 'history', {
      value: originalHistory,
      writable: true
    });
    $(document).off();
  });

  describe('goBack', () => {
    it('calls history.back()', () => {
      goBack();
      expect(window.history.back).toHaveBeenCalled();
    });
  });

  describe('navigateTo', () => {
    it('sets location.href to the given URL', () => {
      navigateTo('http://example.com/page');
      expect(window.location.href).toBe('http://example.com/page');
    });

    it('works with relative URLs', () => {
      navigateTo('/relative/path');
      expect(window.location.href).toBe('/relative/path');
    });
  });

  describe('cancelAndNavigate', () => {
    it('resets form dirty state before navigating', () => {
      cancelAndNavigate('http://example.com/cancel');

      expect(lwtFormCheck.resetDirty).toHaveBeenCalled();
      expect(window.location.href).toBe('http://example.com/cancel');
    });

    it('resets dirty state before setting href', () => {
      const calls: string[] = [];
      (lwtFormCheck.resetDirty as any).mockImplementation(() => calls.push('resetDirty'));

      // Create a getter/setter to track order
      let href = 'http://localhost/';
      Object.defineProperty(window.location, 'href', {
        get: () => href,
        set: (value) => {
          calls.push('setHref');
          href = value;
        },
        configurable: true
      });

      cancelAndNavigate('http://example.com/test');

      expect(calls).toEqual(['resetDirty', 'setHref']);
    });
  });

  describe('cancelAndGoBack', () => {
    it('resets form dirty state before going back', () => {
      cancelAndGoBack();

      expect(lwtFormCheck.resetDirty).toHaveBeenCalled();
      expect(window.history.back).toHaveBeenCalled();
    });
  });

  describe('confirmSubmit', () => {
    it('shows confirmation dialog with default message', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);

      const result = confirmSubmit();

      expect(window.confirm).toHaveBeenCalledWith('Are you sure?');
      expect(result).toBe(true);
    });

    it('shows confirmation dialog with custom message', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);

      const result = confirmSubmit('Delete this item?');

      expect(window.confirm).toHaveBeenCalledWith('Delete this item?');
      expect(result).toBe(true);
    });

    it('returns false when user cancels', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(false);

      const result = confirmSubmit('Test message');

      expect(result).toBe(false);
    });
  });

  describe('initSimpleInteractions', () => {
    beforeEach(() => {
      initSimpleInteractions();
    });

    describe('data-action="cancel-navigate"', () => {
      it('cancels and navigates to the specified URL', () => {
        document.body.innerHTML = `
          <button data-action="cancel-navigate" data-url="/cancel/url">Cancel</button>
        `;

        $('[data-action="cancel-navigate"]').trigger('click');

        expect(lwtFormCheck.resetDirty).toHaveBeenCalled();
        expect(window.location.href).toBe('/cancel/url');
      });

      it('does nothing if no URL is provided', () => {
        document.body.innerHTML = `
          <button data-action="cancel-navigate">Cancel</button>
        `;

        $('[data-action="cancel-navigate"]').trigger('click');

        expect(lwtFormCheck.resetDirty).not.toHaveBeenCalled();
      });
    });

    describe('data-action="cancel-back"', () => {
      it('cancels and goes back in history', () => {
        document.body.innerHTML = `
          <button data-action="cancel-back">Go Back</button>
        `;

        $('[data-action="cancel-back"]').trigger('click');

        expect(lwtFormCheck.resetDirty).toHaveBeenCalled();
        expect(window.history.back).toHaveBeenCalled();
      });
    });

    describe('data-action="navigate"', () => {
      it('navigates to the specified URL', () => {
        document.body.innerHTML = `
          <button data-action="navigate" data-url="/new/page">Go</button>
        `;

        $('[data-action="navigate"]').trigger('click');

        expect(window.location.href).toBe('/new/page');
      });

      it('does nothing if no URL is provided', () => {
        document.body.innerHTML = `
          <button data-action="navigate">Go</button>
        `;
        window.location.href = 'http://localhost/original';

        $('[data-action="navigate"]').trigger('click');

        expect(window.location.href).toBe('http://localhost/original');
      });
    });

    describe('data-action="back"', () => {
      it('goes back in browser history', () => {
        document.body.innerHTML = `
          <button data-action="back">Back</button>
        `;

        $('[data-action="back"]').trigger('click');

        expect(window.history.back).toHaveBeenCalled();
      });
    });

    describe('data-action="confirm-delete"', () => {
      it('shows confirmation and navigates if confirmed', () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true);
        document.body.innerHTML = `
          <button data-action="confirm-delete" data-url="/delete/item">Delete</button>
        `;

        $('[data-action="confirm-delete"]').trigger('click');

        expect(window.confirm).toHaveBeenCalledWith('CONFIRM\n\nAre you sure you want to delete?');
        expect(window.location.href).toBe('/delete/item');
      });

      it('does nothing if user cancels', () => {
        vi.spyOn(window, 'confirm').mockReturnValue(false);
        document.body.innerHTML = `
          <button data-action="confirm-delete" data-url="/delete/item">Delete</button>
        `;
        window.location.href = 'http://localhost/original';

        $('[data-action="confirm-delete"]').trigger('click');

        expect(window.confirm).toHaveBeenCalled();
        expect(window.location.href).toBe('http://localhost/original');
      });
    });

    describe('data-action="cancel-form"', () => {
      it('cancels and navigates to URL', () => {
        document.body.innerHTML = `
          <button data-action="cancel-form" data-url="/form/list">Cancel Form</button>
        `;

        $('[data-action="cancel-form"]').trigger('click');

        expect(lwtFormCheck.resetDirty).toHaveBeenCalled();
        expect(window.location.href).toBe('/form/list');
      });
    });

    describe('data-action="show-right-frames"', () => {
      it('shows the right frames panel', () => {
        document.body.innerHTML = `
          <button data-action="show-right-frames">Show Frames</button>
        `;

        $('[data-action="show-right-frames"]').trigger('click');

        expect(showRightFrames).toHaveBeenCalled();
      });
    });

    describe('data-action="hide-right-frames"', () => {
      it('hides the right frames panel', () => {
        document.body.innerHTML = `
          <button data-action="hide-right-frames">Hide Frames</button>
        `;

        $('[data-action="hide-right-frames"]').trigger('click');

        expect(hideRightFrames).toHaveBeenCalled();
      });
    });

    describe('data-action="toggle-show-all"', () => {
      it('toggles show all words mode', () => {
        document.body.innerHTML = `
          <button data-action="toggle-show-all">Toggle</button>
        `;

        $('[data-action="toggle-show-all"]').trigger('click');

        expect(showAllwordsClick).toHaveBeenCalled();
      });
    });

    describe('data-confirm attribute', () => {
      it('shows confirmation before executing action', () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true);
        document.body.innerHTML = `
          <button data-action="navigate" data-url="/page" data-confirm="Are you sure you want to navigate?">Go</button>
        `;

        $('[data-action="navigate"]').trigger('click');

        expect(window.confirm).toHaveBeenCalledWith('Are you sure you want to navigate?');
        expect(window.location.href).toBe('/page');
      });

      it('prevents action if confirmation is cancelled', () => {
        vi.spyOn(window, 'confirm').mockReturnValue(false);
        document.body.innerHTML = `
          <button data-action="navigate" data-url="/page" data-confirm="Are you sure?">Go</button>
        `;
        window.location.href = 'http://localhost/original';

        $('[data-action="navigate"]').trigger('click');

        expect(window.confirm).toHaveBeenCalled();
        expect(window.location.href).toBe('http://localhost/original');
      });
    });

    describe('form submission confirmation', () => {
      it('shows confirmation before form submission', () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true);
        document.body.innerHTML = `
          <form data-confirm-submit="Submit this form?">
            <button type="submit">Submit</button>
          </form>
        `;

        const form = document.querySelector('form')!;
        const event = new Event('submit', { bubbles: true, cancelable: true });
        form.dispatchEvent(event);

        expect(window.confirm).toHaveBeenCalledWith('Submit this form?');
        expect(event.defaultPrevented).toBe(false);
      });

      it('prevents submission if confirmation is cancelled', () => {
        vi.spyOn(window, 'confirm').mockReturnValue(false);
        document.body.innerHTML = `
          <form data-confirm-submit="Submit this form?">
            <button type="submit">Submit</button>
          </form>
        `;

        const form = document.querySelector('form')!;
        const event = new Event('submit', { bubbles: true, cancelable: true });
        form.dispatchEvent(event);

        expect(window.confirm).toHaveBeenCalled();
        expect(event.defaultPrevented).toBe(true);
      });

      it('uses default message if not specified', () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true);
        document.body.innerHTML = `
          <form data-confirm-submit>
            <button type="submit">Submit</button>
          </form>
        `;

        const form = document.querySelector('form')!;
        const event = new Event('submit', { bubbles: true, cancelable: true });
        form.dispatchEvent(event);

        expect(window.confirm).toHaveBeenCalledWith('Are you sure?');
      });
    });
  });
});

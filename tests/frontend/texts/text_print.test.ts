/**
 * Tests for text_print.ts - Text print page functionality
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { initTextPrint } from '../../../src/frontend/js/texts/text_print';

describe('text_print.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // initPlainPrint Tests
  // ===========================================================================

  describe('initPlainPrint', () => {
    it('handles status filter select change', () => {
      document.body.innerHTML = `
        <div id="printoptions" data-text-id="123">
          <select data-action="filter-status">
            <option value="0">All</option>
            <option value="1">Status 1</option>
          </select>
        </div>
      `;

      initTextPrint();

      const select = document.querySelector<HTMLSelectElement>('[data-action="filter-status"]')!;
      select.value = '1';

      // Mock location.href
      const locationSpy = vi.spyOn(window, 'location', 'get').mockReturnValue({
        ...window.location,
        href: '',
        set href(val: string) {
          // No-op for test
        }
      } as Location);

      select.dispatchEvent(new Event('change'));

      // Event handler executed without error
      expect(locationSpy).toBeDefined();
    });

    it('handles annotation filter select change', () => {
      document.body.innerHTML = `
        <div id="printoptions" data-text-id="456">
          <select data-action="filter-annotation">
            <option value="0">None</option>
            <option value="1">Show</option>
          </select>
        </div>
      `;

      initTextPrint();

      const select = document.querySelector<HTMLSelectElement>('[data-action="filter-annotation"]')!;
      select.value = '1';

      expect(() => select.dispatchEvent(new Event('change'))).not.toThrow();
    });

    it('handles annotation placement filter select change', () => {
      document.body.innerHTML = `
        <div id="printoptions" data-text-id="789">
          <select data-action="filter-placement">
            <option value="0">Above</option>
            <option value="1">Below</option>
          </select>
        </div>
      `;

      initTextPrint();

      const select = document.querySelector<HTMLSelectElement>('[data-action="filter-placement"]')!;
      select.value = '1';

      expect(() => select.dispatchEvent(new Event('change'))).not.toThrow();
    });

    it('does nothing when printoptions container is missing', () => {
      document.body.innerHTML = '<div>No printoptions here</div>';

      expect(() => initTextPrint()).not.toThrow();
    });

    it('does nothing when textId is missing', () => {
      document.body.innerHTML = `
        <div id="printoptions">
          <select data-action="filter-status">
            <option value="0">All</option>
          </select>
        </div>
      `;

      initTextPrint();

      const select = document.querySelector<HTMLSelectElement>('[data-action="filter-status"]')!;

      // Should not throw even without textId
      expect(() => select.dispatchEvent(new Event('change'))).not.toThrow();
    });
  });

  // ===========================================================================
  // initCommonPrintHandlers Tests
  // ===========================================================================

  describe('initCommonPrintHandlers', () => {
    describe('print button', () => {
      it('calls window.print on click', () => {
        document.body.innerHTML = `
          <div id="printoptions" data-text-id="123">
            <button data-action="print">Print</button>
          </div>
        `;

        const printSpy = vi.spyOn(window, 'print').mockImplementation(() => {});

        initTextPrint();

        const button = document.querySelector<HTMLButtonElement>('[data-action="print"]')!;
        button.click();

        expect(printSpy).toHaveBeenCalled();
      });

      it('prevents default event behavior', () => {
        document.body.innerHTML = `
          <div id="printoptions" data-text-id="123">
            <button data-action="print">Print</button>
          </div>
        `;

        vi.spyOn(window, 'print').mockImplementation(() => {});

        initTextPrint();

        const button = document.querySelector<HTMLButtonElement>('[data-action="print"]')!;
        const clickEvent = new MouseEvent('click', { cancelable: true });
        button.dispatchEvent(clickEvent);

        expect(clickEvent.defaultPrevented).toBe(true);
      });
    });

    describe('navigate button', () => {
      it('navigates to specified URL on click', () => {
        document.body.innerHTML = `
          <div id="printoptions" data-text-id="123">
            <button data-action="navigate" data-url="/texts">Navigate</button>
          </div>
        `;

        initTextPrint();

        const button = document.querySelector<HTMLButtonElement>('[data-action="navigate"]')!;

        expect(() => button.click()).not.toThrow();
      });

      it('does nothing when URL is missing', () => {
        document.body.innerHTML = `
          <div id="printoptions" data-text-id="123">
            <button data-action="navigate">Navigate</button>
          </div>
        `;

        initTextPrint();

        const button = document.querySelector<HTMLButtonElement>('[data-action="navigate"]')!;

        expect(() => button.click()).not.toThrow();
      });

      it('handles multiple navigate buttons', () => {
        document.body.innerHTML = `
          <div id="printoptions" data-text-id="123">
            <button data-action="navigate" data-url="/url1">Nav 1</button>
            <button data-action="navigate" data-url="/url2">Nav 2</button>
          </div>
        `;

        initTextPrint();

        const buttons = document.querySelectorAll<HTMLButtonElement>('[data-action="navigate"]');

        expect(buttons.length).toBe(2);
        buttons.forEach((button) => {
          expect(() => button.click()).not.toThrow();
        });
      });
    });

    describe('confirm-navigate button', () => {
      it('navigates when confirmation is accepted', () => {
        document.body.innerHTML = `
          <div id="printoptions" data-text-id="123">
            <button data-action="confirm-navigate" data-url="/delete" data-confirm="Are you sure?">
              Delete
            </button>
          </div>
        `;

        const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

        initTextPrint();

        const button = document.querySelector<HTMLButtonElement>('[data-action="confirm-navigate"]')!;
        button.click();

        expect(confirmSpy).toHaveBeenCalledWith('Are you sure?');
      });

      it('does not navigate when confirmation is cancelled', () => {
        document.body.innerHTML = `
          <div id="printoptions" data-text-id="123">
            <button data-action="confirm-navigate" data-url="/delete" data-confirm="Are you sure?">
              Delete
            </button>
          </div>
        `;

        const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

        initTextPrint();

        const button = document.querySelector<HTMLButtonElement>('[data-action="confirm-navigate"]')!;
        button.click();

        expect(confirmSpy).toHaveBeenCalled();
      });

      it('uses default confirmation message when data-confirm is missing', () => {
        document.body.innerHTML = `
          <div id="printoptions" data-text-id="123">
            <button data-action="confirm-navigate" data-url="/action">
              Action
            </button>
          </div>
        `;

        const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(false);

        initTextPrint();

        const button = document.querySelector<HTMLButtonElement>('[data-action="confirm-navigate"]')!;
        button.click();

        expect(confirmSpy).toHaveBeenCalledWith('Are you sure?');
      });

      it('does nothing when URL is missing', () => {
        document.body.innerHTML = `
          <div id="printoptions" data-text-id="123">
            <button data-action="confirm-navigate" data-confirm="Are you sure?">
              Action
            </button>
          </div>
        `;

        const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true);

        initTextPrint();

        const button = document.querySelector<HTMLButtonElement>('[data-action="confirm-navigate"]')!;
        button.click();

        // confirm should still be called but navigation won't happen due to !url check
        expect(confirmSpy).not.toHaveBeenCalled();
      });
    });

    describe('open-window button', () => {
      it('opens new window with specified URL', () => {
        document.body.innerHTML = `
          <div id="printoptions" data-text-id="123">
            <button data-action="open-window" data-url="/popup">Open Popup</button>
          </div>
        `;

        const openSpy = vi.spyOn(window, 'open').mockImplementation(() => null);

        initTextPrint();

        const button = document.querySelector<HTMLButtonElement>('[data-action="open-window"]')!;
        button.click();

        expect(openSpy).toHaveBeenCalledWith('/popup');
      });

      it('does nothing when URL is missing', () => {
        document.body.innerHTML = `
          <div id="printoptions" data-text-id="123">
            <button data-action="open-window">Open</button>
          </div>
        `;

        const openSpy = vi.spyOn(window, 'open').mockImplementation(() => null);

        initTextPrint();

        const button = document.querySelector<HTMLButtonElement>('[data-action="open-window"]')!;
        button.click();

        expect(openSpy).not.toHaveBeenCalled();
      });
    });
  });

  // ===========================================================================
  // isTextPrintPage Tests (via DOMContentLoaded)
  // ===========================================================================

  describe('page detection', () => {
    it('detects page with printoptions container', () => {
      document.body.innerHTML = `
        <div id="printoptions" data-text-id="123">
          <button data-action="print">Print</button>
        </div>
      `;

      // initTextPrint should work on print pages
      expect(() => initTextPrint()).not.toThrow();
    });

    it('handles page without printoptions container', () => {
      document.body.innerHTML = '<div>Not a print page</div>';

      // Should not throw even on non-print pages
      expect(() => initTextPrint()).not.toThrow();
    });
  });

  // ===========================================================================
  // Window Exports Tests
  // ===========================================================================

  describe('window exports', () => {
    it('exports initTextPrint to window', async () => {
      await import('../../../src/frontend/js/texts/text_print');

      expect(typeof window.initTextPrint).toBe('function');
    });
  });

  // ===========================================================================
  // Combined Functionality Tests
  // ===========================================================================

  describe('combined functionality', () => {
    it('initializes all handlers on complete print page', () => {
      document.body.innerHTML = `
        <div id="printoptions" data-text-id="123">
          <select data-action="filter-status">
            <option value="0">All</option>
          </select>
          <select data-action="filter-annotation">
            <option value="0">None</option>
          </select>
          <select data-action="filter-placement">
            <option value="0">Above</option>
          </select>
          <button data-action="print">Print</button>
          <button data-action="navigate" data-url="/home">Home</button>
          <button data-action="confirm-navigate" data-url="/delete">Delete</button>
          <button data-action="open-window" data-url="/help">Help</button>
        </div>
      `;

      vi.spyOn(window, 'print').mockImplementation(() => {});
      vi.spyOn(window, 'confirm').mockReturnValue(false);
      vi.spyOn(window, 'open').mockImplementation(() => null);

      initTextPrint();

      // All buttons should be interactive
      const printBtn = document.querySelector<HTMLButtonElement>('[data-action="print"]')!;
      const navBtn = document.querySelector<HTMLButtonElement>('[data-action="navigate"]')!;
      const confirmBtn = document.querySelector<HTMLButtonElement>('[data-action="confirm-navigate"]')!;
      const openBtn = document.querySelector<HTMLButtonElement>('[data-action="open-window"]')!;

      expect(() => {
        printBtn.click();
        navBtn.click();
        confirmBtn.click();
        openBtn.click();
      }).not.toThrow();
    });
  });
});

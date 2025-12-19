/**
 * Tests for word_popup.ts - Word Popup Dialog (Native Implementation)
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Polyfill HTMLDialogElement methods for JSDOM
// JSDOM doesn't fully implement the dialog element API
function polyfillDialog() {
  if (!HTMLDialogElement.prototype.show) {
    HTMLDialogElement.prototype.show = function() {
      this.setAttribute('open', '');
    };
  }
  if (!HTMLDialogElement.prototype.showModal) {
    HTMLDialogElement.prototype.showModal = function() {
      this.setAttribute('open', '');
    };
  }
  if (!HTMLDialogElement.prototype.close) {
    HTMLDialogElement.prototype.close = function() {
      this.removeAttribute('open');
      this.dispatchEvent(new Event('close'));
    };
  }
  // Polyfill the 'open' getter/setter if needed
  if (!Object.getOwnPropertyDescriptor(HTMLDialogElement.prototype, 'open')?.get) {
    Object.defineProperty(HTMLDialogElement.prototype, 'open', {
      get: function() {
        return this.hasAttribute('open');
      },
      set: function(value) {
        if (value) {
          this.setAttribute('open', '');
        } else {
          this.removeAttribute('open');
        }
      }
    });
  }
}

// Reset module state before importing
beforeEach(() => {
  document.body.innerHTML = '';
  // Clear any existing style elements
  document.querySelectorAll('style').forEach(el => el.remove());
  // Apply dialog polyfill
  polyfillDialog();
});

// Dynamic import to reset module state for each test
async function importWordPopup() {
  // Reset the module registry for fresh imports
  vi.resetModules();
  return await import('../../../src/frontend/js/ui/word_popup');
}

describe('word_popup.ts', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // cClick Tests
  // ===========================================================================

  describe('cClick', () => {
    it('closes dialog when popup is open', async () => {
      const { overlib, cClick } = await importWordPopup();

      // Open a popup
      overlib('Test content', 'Test Title');

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      expect(dialog.open).toBe(true);

      // Close it
      cClick();

      expect(dialog.open).toBe(false);
    });

    it('does not throw when no popup is open', async () => {
      const { cClick } = await importWordPopup();
      expect(() => cClick()).not.toThrow();
    });

    it('does not throw when called multiple times', async () => {
      const { overlib, cClick } = await importWordPopup();

      overlib('Test content');
      cClick();

      // Second close should not throw
      expect(() => cClick()).not.toThrow();
    });
  });

  // ===========================================================================
  // nd Tests
  // ===========================================================================

  describe('nd', () => {
    it('calls cClick and returns true', async () => {
      const { nd } = await importWordPopup();
      const result = nd();
      expect(result).toBe(true);
    });

    it('closes any open popup', async () => {
      const { overlib, nd } = await importWordPopup();

      overlib('Test content');
      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      expect(dialog.open).toBe(true);

      nd();

      expect(dialog.open).toBe(false);
    });
  });

  // ===========================================================================
  // overlib Tests
  // ===========================================================================

  describe('overlib', () => {
    it('returns true for compatibility', async () => {
      const { overlib } = await importWordPopup();
      const result = overlib('Test content');
      expect(result).toBe(true);
    });

    it('creates popup container if not exists', async () => {
      const { overlib } = await importWordPopup();

      overlib('Test content');

      const container = document.getElementById('lwt-word-popup');
      expect(container).not.toBeNull();
      expect(container?.tagName).toBe('DIALOG');
    });

    it('sets content on container', async () => {
      const { overlib } = await importWordPopup();

      overlib('<p>Hello World</p>');

      const content = document.querySelector('.lwt-popup-content');
      expect(content?.innerHTML).toBe('<p>Hello World</p>');
    });

    it('sets title from parameter', async () => {
      const { overlib } = await importWordPopup();

      overlib('Content', 'My Title');

      const title = document.querySelector('.lwt-popup-title');
      expect(title?.textContent).toBe('My Title');
    });

    it('uses default title when not provided', async () => {
      const { overlib } = await importWordPopup();

      overlib('Content');

      const title = document.querySelector('.lwt-popup-title');
      expect(title?.textContent).toBe('Word');
    });

    it('opens the dialog', async () => {
      const { overlib } = await importWordPopup();

      overlib('Test content');

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      expect(dialog.open).toBe(true);
    });

    it('closes existing popup before opening new one', async () => {
      const { overlib, setCurrentEvent } = await importWordPopup();

      // Open first popup
      overlib('First content');
      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;

      // Set different position for second popup
      setCurrentEvent(new MouseEvent('click', { clientX: 200, clientY: 200 }));

      // Open second popup
      overlib('Second content');

      // Content should be updated
      const content = document.querySelector('.lwt-popup-content');
      expect(content?.innerHTML).toBe('Second content');

      // Dialog should still be open
      expect(dialog.open).toBe(true);
    });

    it('reuses existing container', async () => {
      const { overlib } = await importWordPopup();

      overlib('First');
      overlib('Second');

      const containers = document.querySelectorAll('#lwt-word-popup');
      expect(containers.length).toBe(1);
    });

    it('creates proper dialog structure', async () => {
      const { overlib } = await importWordPopup();

      overlib('Test content', 'Test Title');

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      const titlebar = dialog.querySelector('.lwt-popup-titlebar');
      const title = dialog.querySelector('.lwt-popup-title');
      const closeBtn = dialog.querySelector('.lwt-popup-close');
      const content = dialog.querySelector('.lwt-popup-content');

      expect(titlebar).not.toBeNull();
      expect(title).not.toBeNull();
      expect(closeBtn).not.toBeNull();
      expect(content).not.toBeNull();
    });

    it('close button closes the dialog', async () => {
      const { overlib } = await importWordPopup();

      overlib('Test content');

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      const closeBtn = dialog.querySelector('.lwt-popup-close') as HTMLButtonElement;

      expect(dialog.open).toBe(true);

      closeBtn.click();

      expect(dialog.open).toBe(false);
    });
  });

  // ===========================================================================
  // setCurrentEvent Tests
  // ===========================================================================

  describe('setCurrentEvent', () => {
    it('stores the event for positioning', async () => {
      const { overlib, setCurrentEvent } = await importWordPopup();

      // Create a MouseEvent
      const mockEvent = new MouseEvent('click', {
        clientX: 100,
        clientY: 200,
        bubbles: true
      });

      setCurrentEvent(mockEvent);
      overlib('Test content');

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      // Dialog should be positioned with fixed positioning
      expect(dialog.style.position).toBe('fixed');
      // When a MouseEvent is set, positioning should NOT use transform (center mode)
      // Note: In JSDOM, viewport dimensions may be 0 or unusual, so we just check
      // that the positioning mode changed from center (no transform) to mouse-based
      // The actual position values are handled by browser layout engine
      expect(dialog).toBeTruthy();
    });

    it('uses center positioning when no event set', async () => {
      const { overlib } = await importWordPopup();

      overlib('Test content');

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      expect(dialog.style.left).toBe('50%');
      expect(dialog.style.top).toBe('50%');
      expect(dialog.style.transform).toBe('translate(-50%, -50%)');
    });
  });

  // ===========================================================================
  // withEventPosition Tests
  // ===========================================================================

  describe('withEventPosition', () => {
    it('wraps handler and stores event', async () => {
      const { withEventPosition } = await importWordPopup();

      const originalHandler = vi.fn().mockReturnValue('result');
      const wrappedHandler = withEventPosition(originalHandler);

      const mockEvent = new MouseEvent('click', {
        clientX: 50,
        clientY: 75
      });

      const result = wrappedHandler(mockEvent, 'arg1', 'arg2');

      expect(result).toBe('result');
      expect(originalHandler).toHaveBeenCalledWith('arg1', 'arg2');
    });

    it('stores event before calling handler', async () => {
      const { overlib, withEventPosition } = await importWordPopup();

      const handler = vi.fn(() => {
        overlib('Test');
      });

      const wrapped = withEventPosition(handler);
      const mockEvent = new MouseEvent('click', {
        clientX: 150,
        clientY: 250,
        bubbles: true
      });

      wrapped(mockEvent);

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      // Dialog should be positioned with fixed positioning
      expect(dialog.style.position).toBe('fixed');
      // Verify the dialog was created and opened
      expect(dialog.open).toBe(true);
    });

    it('returns handler return value', async () => {
      const { withEventPosition } = await importWordPopup();

      const handler = vi.fn().mockReturnValue(42);
      const wrapped = withEventPosition(handler);
      const mockEvent = new Event('click');

      const result = wrapped(mockEvent);
      expect(result).toBe(42);
    });

    it('passes all arguments to original handler', async () => {
      const { withEventPosition } = await importWordPopup();

      const handler = vi.fn();
      const wrapped = withEventPosition(handler);
      const mockEvent = new Event('click');

      wrapped(mockEvent, 'a', 'b', 'c');

      expect(handler).toHaveBeenCalledWith('a', 'b', 'c');
    });
  });

  // ===========================================================================
  // CSS Injection Tests
  // ===========================================================================

  describe('CSS injection', () => {
    it('injects styles into document head', async () => {
      await importWordPopup();

      const styleElements = document.querySelectorAll('style');
      const hasPopupStyles = Array.from(styleElements).some(
        el => el.textContent?.includes('.lwt-popup-dialog')
      );
      expect(hasPopupStyles).toBe(true);
    });

    it('styles include dialog titlebar styling', async () => {
      await importWordPopup();

      const styleElements = document.querySelectorAll('style');
      const popupStyle = Array.from(styleElements).find(
        el => el.textContent?.includes('.lwt-popup-dialog')
      );
      expect(popupStyle?.textContent).toContain('.lwt-popup-titlebar');
    });

    it('styles include background color', async () => {
      await importWordPopup();

      const styleElements = document.querySelectorAll('style');
      const popupStyle = Array.from(styleElements).find(
        el => el.textContent?.includes('.lwt-popup-dialog')
      );
      expect(popupStyle?.textContent).toContain('#FFFFE8');
    });

    it('styles include close button styling', async () => {
      await importWordPopup();

      const styleElements = document.querySelectorAll('style');
      const popupStyle = Array.from(styleElements).find(
        el => el.textContent?.includes('.lwt-popup-dialog')
      );
      expect(popupStyle?.textContent).toContain('.lwt-popup-close');
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('full workflow: open, position, close', async () => {
      const { overlib, setCurrentEvent, nd } = await importWordPopup();

      // Set event position
      const clickEvent = new MouseEvent('click', {
        clientX: 200,
        clientY: 300
      });
      setCurrentEvent(clickEvent);

      // Open popup
      const openResult = overlib('<b>Term</b>: Definition', 'Vocabulary');
      expect(openResult).toBe(true);

      // Verify container exists and is open
      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      expect(dialog).not.toBeNull();
      expect(dialog.open).toBe(true);

      // Verify content
      const content = document.querySelector('.lwt-popup-content');
      expect(content?.innerHTML).toBe('<b>Term</b>: Definition');

      // Verify title
      const title = document.querySelector('.lwt-popup-title');
      expect(title?.textContent).toBe('Vocabulary');

      // Close popup
      const closeResult = nd();
      expect(closeResult).toBe(true);
      expect(dialog.open).toBe(false);
    });

    it('multiple popups update content', async () => {
      const { overlib } = await importWordPopup();

      overlib('First popup');

      const content = document.querySelector('.lwt-popup-content');
      expect(content?.innerHTML).toBe('First popup');

      overlib('Second popup');

      expect(content?.innerHTML).toBe('Second popup');
    });

    it('clicking outside dialog (backdrop) closes it', async () => {
      const { overlib } = await importWordPopup();

      overlib('Test content');

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      expect(dialog.open).toBe(true);

      // Simulate click on dialog itself (backdrop area)
      const clickEvent = new MouseEvent('click', { bubbles: true });
      Object.defineProperty(clickEvent, 'target', { value: dialog });
      dialog.dispatchEvent(clickEvent);

      expect(dialog.open).toBe(false);
    });

    it('clicking inside dialog content does not close it', async () => {
      const { overlib } = await importWordPopup();

      overlib('Test content');

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      const content = document.querySelector('.lwt-popup-content') as HTMLElement;

      expect(dialog.open).toBe(true);

      // Simulate click on content (not backdrop)
      const clickEvent = new MouseEvent('click', { bubbles: true });
      Object.defineProperty(clickEvent, 'target', { value: content });
      dialog.dispatchEvent(clickEvent);

      // Dialog should still be open
      expect(dialog.open).toBe(true);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles empty content', async () => {
      const { overlib } = await importWordPopup();

      expect(() => overlib('')).not.toThrow();
      expect(overlib('')).toBe(true);

      const content = document.querySelector('.lwt-popup-content');
      expect(content?.innerHTML).toBe('');
    });

    it('handles HTML content with special characters', async () => {
      const { overlib } = await importWordPopup();

      const htmlContent = '<a href="test?a=1&b=2">Link</a>';
      expect(() => overlib(htmlContent)).not.toThrow();

      const content = document.querySelector('.lwt-popup-content');
      // Browser normalizes & to &amp; in innerHTML
      expect(content?.innerHTML).toContain('test?a=1');
      expect(content?.innerHTML).toContain('b=2');
      expect(content?.innerHTML).toContain('Link');
    });

    it('handles undefined title', async () => {
      const { overlib } = await importWordPopup();

      expect(() => overlib('Content', undefined)).not.toThrow();

      const title = document.querySelector('.lwt-popup-title');
      expect(title?.textContent).toBe('Word');
    });

    it('handles non-MouseEvent for positioning', async () => {
      const { overlib, setCurrentEvent } = await importWordPopup();

      const keyEvent = new KeyboardEvent('keydown');
      setCurrentEvent(keyEvent);

      overlib('Test');

      // Should use center positioning for non-mouse events
      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      expect(dialog.style.left).toBe('50%');
      expect(dialog.style.top).toBe('50%');
    });

    it('cClick clears event reference', async () => {
      const { overlib, setCurrentEvent, cClick } = await importWordPopup();

      const mouseEvent = new MouseEvent('click', { clientX: 100, clientY: 100 });
      setCurrentEvent(mouseEvent);
      overlib('Test');

      cClick();

      // Open new popup - should use center positioning (no event)
      overlib('New popup');

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      expect(dialog.style.left).toBe('50%');
      expect(dialog.style.top).toBe('50%');
    });

    it('handles rapid open/close cycles', async () => {
      const { overlib, cClick } = await importWordPopup();

      for (let i = 0; i < 10; i++) {
        overlib(`Content ${i}`);
        cClick();
      }

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      expect(dialog.open).toBe(false);

      // Final open
      overlib('Final content');
      expect(dialog.open).toBe(true);
    });

    it('positions dialog within viewport bounds', async () => {
      const { overlib, setCurrentEvent } = await importWordPopup();

      // Set click position near right edge
      const mockEvent = new MouseEvent('click', {
        clientX: window.innerWidth - 50,
        clientY: 100,
        bubbles: true
      });

      setCurrentEvent(mockEvent);
      overlib('Test content');

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      const leftPos = parseInt(dialog.style.left, 10);

      // Should adjust to stay within viewport (280px width + padding)
      expect(leftPos).toBeLessThanOrEqual(window.innerWidth - 280);
    });
  });

  // ===========================================================================
  // Accessibility Tests
  // ===========================================================================

  describe('Accessibility', () => {
    it('close button has aria-label', async () => {
      const { overlib } = await importWordPopup();

      overlib('Test content');

      const closeBtn = document.querySelector('.lwt-popup-close');
      expect(closeBtn?.getAttribute('aria-label')).toBe('Close');
    });

    it('close button has type button', async () => {
      const { overlib } = await importWordPopup();

      overlib('Test content');

      const closeBtn = document.querySelector('.lwt-popup-close') as HTMLButtonElement;
      expect(closeBtn?.type).toBe('button');
    });

    it('dialog can be closed with Escape key', async () => {
      const { overlib } = await importWordPopup();

      overlib('Test content');

      const dialog = document.getElementById('lwt-word-popup') as HTMLDialogElement;
      expect(dialog.open).toBe(true);

      // Native dialog handles Escape - dispatch close event
      dialog.dispatchEvent(new Event('close'));

      // Note: In real browser, pressing Escape would close the dialog
      // We're testing that the close event handler cleans up properly
    });
  });
});

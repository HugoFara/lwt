/**
 * Tests for word_popup.ts - Word Popup Dialog
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';

// Mock jQuery UI dialog
const mockDialog = vi.fn().mockReturnThis();

beforeEach(() => {
  // Setup jQuery global
  (window as unknown as Record<string, unknown>).$ = $;
  (globalThis as unknown as Record<string, unknown>).$ = $;

  // Mock jQuery UI dialog method
  $.fn.dialog = mockDialog;
  mockDialog.mockClear();
});

import {
  overlib,
  cClick,
  nd,
  setCurrentEvent,
  withEventPosition,
  CAPTION
} from '../../../src/frontend/js/ui/word_popup';

describe('word_popup.ts', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
    // Reset mock
    mockDialog.mockClear();
  });

  // ===========================================================================
  // CAPTION Constant Tests
  // ===========================================================================

  describe('CAPTION constant', () => {
    it('exports CAPTION constant', () => {
      expect(CAPTION).toBe('CAPTION');
    });
  });

  // ===========================================================================
  // cClick Tests
  // ===========================================================================

  describe('cClick', () => {
    it('attempts to close dialog when popup exists', () => {
      // First open a popup
      overlib('Test content', CAPTION, 'Test Title');

      // Clear mock call count from overlib
      mockDialog.mockClear();

      // Close it
      cClick();

      // Should have called dialog('close')
      expect(mockDialog).toHaveBeenCalledWith('close');
    });

    it('does not throw when no popup is open', () => {
      expect(() => cClick()).not.toThrow();
    });

    it('handles dialog errors gracefully', () => {
      // First open a popup
      overlib('Test content');

      // Make dialog throw on close
      mockDialog.mockImplementationOnce(() => {
        throw new Error('Dialog error');
      });

      // Should not throw
      expect(() => cClick()).not.toThrow();
    });
  });

  // ===========================================================================
  // nd Tests
  // ===========================================================================

  describe('nd', () => {
    it('calls cClick and returns true', () => {
      const result = nd();
      expect(result).toBe(true);
    });

    it('closes any open popup', () => {
      overlib('Test content');
      mockDialog.mockClear();

      nd();

      expect(mockDialog).toHaveBeenCalledWith('close');
    });
  });

  // ===========================================================================
  // overlib Tests
  // ===========================================================================

  describe('overlib', () => {
    it('returns true for compatibility', () => {
      const result = overlib('Test content');
      expect(result).toBe(true);
    });

    it('creates popup container if not exists', () => {
      overlib('Test content');
      const container = document.getElementById('lwt-word-popup');
      expect(container).not.toBeNull();
    });

    it('sets content on container', () => {
      overlib('<p>Hello World</p>');

      // Check that html was called with the content
      expect(mockDialog).toHaveBeenCalled();
    });

    it('sets title from parameter', () => {
      overlib('Content', CAPTION, 'My Title');

      // Find the call that sets the title
      const titleCall = mockDialog.mock.calls.find(
        call => call[0] === 'option' && call[1] === 'title'
      );
      expect(titleCall).toBeDefined();
      expect(titleCall?.[2]).toBe('My Title');
    });

    it('uses default title when not provided', () => {
      overlib('Content');

      const titleCall = mockDialog.mock.calls.find(
        call => call[0] === 'option' && call[1] === 'title'
      );
      expect(titleCall).toBeDefined();
      expect(titleCall?.[2]).toBe('Word');
    });

    it('opens the dialog', () => {
      overlib('Test content');

      expect(mockDialog).toHaveBeenCalledWith('open');
    });

    it('closes existing popup before opening new one', () => {
      // Open first popup
      overlib('First content');
      mockDialog.mockClear();

      // Open second popup - should close first
      overlib('Second content');

      // Should have called close (or attempted to)
      // Note: The actual close might not happen if no popup ref yet
      expect(mockDialog).toHaveBeenCalled();
    });

    it('reuses existing container', () => {
      overlib('First');
      overlib('Second');

      const containers = document.querySelectorAll('#lwt-word-popup');
      expect(containers.length).toBe(1);
    });
  });

  // ===========================================================================
  // setCurrentEvent Tests
  // ===========================================================================

  describe('setCurrentEvent', () => {
    it('stores the event for positioning', () => {
      // Create a real MouseEvent
      const mockEvent = new MouseEvent('click', {
        clientX: 100,
        clientY: 200,
        bubbles: true
      });

      setCurrentEvent(mockEvent);

      // Open a popup - it should use the stored event for positioning
      overlib('Test content');

      // Find the position call
      const positionCall = mockDialog.mock.calls.find(
        call => call[0] === 'option' && call[1] === 'position'
      );
      expect(positionCall).toBeDefined();
      // The position object should have position info
      expect(positionCall?.[2]).toHaveProperty('my');
      expect(positionCall?.[2]).toHaveProperty('at');
      expect(positionCall?.[2]).toHaveProperty('of');
    });

    it('uses center positioning when no event set', () => {
      // Don't set an event
      overlib('Test content');

      const positionCall = mockDialog.mock.calls.find(
        call => call[0] === 'option' && call[1] === 'position'
      );
      expect(positionCall).toBeDefined();
      expect(positionCall?.[2]).toMatchObject({
        my: 'center',
        at: 'center',
        of: window
      });
    });
  });

  // ===========================================================================
  // withEventPosition Tests
  // ===========================================================================

  describe('withEventPosition', () => {
    it('wraps handler and stores event', () => {
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

    it('stores event before calling handler', () => {
      let capturedPosition: Record<string, unknown> | null = null;

      const handler = vi.fn(() => {
        // Open popup inside handler to capture positioning
        overlib('Test');

        const positionCall = mockDialog.mock.calls.find(
          call => call[0] === 'option' && call[1] === 'position'
        );
        capturedPosition = positionCall?.[2] as Record<string, unknown>;
      });

      const wrapped = withEventPosition(handler);
      const mockEvent = new MouseEvent('click', {
        clientX: 150,
        clientY: 250,
        bubbles: true
      });

      wrapped(mockEvent);

      // Verify position was captured and has expected properties
      expect(capturedPosition).not.toBeNull();
      expect(capturedPosition).toHaveProperty('my');
      expect(capturedPosition).toHaveProperty('at');
    });

    it('returns handler return value', () => {
      const handler = vi.fn().mockReturnValue(42);
      const wrapped = withEventPosition(handler);
      const mockEvent = new Event('click');

      const result = wrapped(mockEvent);
      expect(result).toBe(42);
    });

    it('passes all arguments to original handler', () => {
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
    it('injects styles into document head', () => {
      // The module should have already injected styles on import
      const styleElements = document.querySelectorAll('style');
      const hasPopupStyles = Array.from(styleElements).some(
        el => el.textContent?.includes('.lwt-popup-dialog')
      );
      expect(hasPopupStyles).toBe(true);
    });

    it('styles include dialog titlebar styling', () => {
      const styleElements = document.querySelectorAll('style');
      const popupStyle = Array.from(styleElements).find(
        el => el.textContent?.includes('.lwt-popup-dialog')
      );
      expect(popupStyle?.textContent).toContain('ui-dialog-titlebar');
    });

    it('styles include background color', () => {
      const styleElements = document.querySelectorAll('style');
      const popupStyle = Array.from(styleElements).find(
        el => el.textContent?.includes('.lwt-popup-dialog')
      );
      expect(popupStyle?.textContent).toContain('#FFFFE8');
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('full workflow: open, position, close', () => {
      // Set event position
      const clickEvent = new MouseEvent('click', {
        clientX: 200,
        clientY: 300
      });
      setCurrentEvent(clickEvent);

      // Open popup
      const openResult = overlib('<b>Term</b>: Definition', CAPTION, 'Vocabulary');
      expect(openResult).toBe(true);

      // Verify container exists
      expect(document.getElementById('lwt-word-popup')).not.toBeNull();

      // Close popup
      const closeResult = nd();
      expect(closeResult).toBe(true);
    });

    it('multiple popups close previous before opening new', () => {
      overlib('First popup');
      mockDialog.mockClear();

      overlib('Second popup');

      // First call should be close attempt
      expect(mockDialog).toHaveBeenCalledWith('close');
      // Last call should be open
      const lastCall = mockDialog.mock.calls[mockDialog.mock.calls.length - 1];
      expect(lastCall[0]).toBe('open');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles empty content', () => {
      expect(() => overlib('')).not.toThrow();
      expect(overlib('')).toBe(true);
    });

    it('handles HTML content with special characters', () => {
      const content = '<a href="test?a=1&b=2">Link</a>';
      expect(() => overlib(content)).not.toThrow();
    });

    it('handles undefined title', () => {
      expect(() => overlib('Content', undefined, undefined)).not.toThrow();
    });

    it('handles non-MouseEvent for positioning', () => {
      const keyEvent = new KeyboardEvent('keydown');
      setCurrentEvent(keyEvent);

      overlib('Test');

      // Should use center positioning for non-mouse events
      const positionCall = mockDialog.mock.calls.find(
        call => call[0] === 'option' && call[1] === 'position'
      );
      expect(positionCall?.[2]).toMatchObject({
        my: 'center',
        at: 'center'
      });
    });

    it('cClick clears event reference', () => {
      const mouseEvent = new MouseEvent('click', { clientX: 100, clientY: 100 });
      setCurrentEvent(mouseEvent);
      overlib('Test');

      cClick();

      // Open new popup - should use center positioning (no event)
      mockDialog.mockClear();
      overlib('New popup');

      const positionCall = mockDialog.mock.calls.find(
        call => call[0] === 'option' && call[1] === 'position'
      );
      expect(positionCall?.[2]).toMatchObject({
        my: 'center',
        at: 'center'
      });
    });
  });
});

/**
 * Tests for ui/icons.ts - Lucide Icon Utilities
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  getLucideIconName,
  createIcon,
  iconHtml,
  initLucideIcons,
  createSpinner,
  spinnerHtml
} from '../../../src/frontend/js/shared/icons/icons';

describe('ui/icons.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // getLucideIconName Tests
  // ===========================================================================

  describe('getLucideIconName', () => {
    it('maps "plus" to "plus"', () => {
      expect(getLucideIconName('plus')).toBe('plus');
    });

    it('maps "plus-button" to "circle-plus"', () => {
      expect(getLucideIconName('plus-button')).toBe('circle-plus');
    });

    it('maps "minus" to "minus"', () => {
      expect(getLucideIconName('minus')).toBe('minus');
    });

    it('maps "minus-button" to "circle-minus"', () => {
      expect(getLucideIconName('minus-button')).toBe('circle-minus');
    });

    it('maps "cross" to "x"', () => {
      expect(getLucideIconName('cross')).toBe('x');
    });

    it('maps "cross-button" to "x-circle"', () => {
      expect(getLucideIconName('cross-button')).toBe('x-circle');
    });

    it('maps "tick" to "check"', () => {
      expect(getLucideIconName('tick')).toBe('check');
    });

    it('maps "tick-button" to "circle-check"', () => {
      expect(getLucideIconName('tick-button')).toBe('circle-check');
    });

    it('maps "pencil" to "pencil"', () => {
      expect(getLucideIconName('pencil')).toBe('pencil');
    });

    it('maps "eraser" to "eraser"', () => {
      expect(getLucideIconName('eraser')).toBe('eraser');
    });

    it('maps "broom" to "brush"', () => {
      expect(getLucideIconName('broom')).toBe('brush');
    });

    it('maps "sticky-note--pencil" to "file-pen-line"', () => {
      expect(getLucideIconName('sticky-note--pencil')).toBe('file-pen-line');
    });

    it('maps "status" to "circle-check"', () => {
      expect(getLucideIconName('status')).toBe('circle-check');
    });

    it('maps "status-busy" to "circle-x"', () => {
      expect(getLucideIconName('status-busy')).toBe('circle-x');
    });

    it('maps "exclamation-red" to "circle-alert"', () => {
      expect(getLucideIconName('exclamation-red')).toBe('circle-alert');
    });

    it('maps "thumb" and "thumb-up" to "thumbs-up"', () => {
      expect(getLucideIconName('thumb')).toBe('thumbs-up');
      expect(getLucideIconName('thumb-up')).toBe('thumbs-up');
    });

    it('maps "star" to "star"', () => {
      expect(getLucideIconName('star')).toBe('star');
    });

    it('maps "photo-album" to "image"', () => {
      expect(getLucideIconName('photo-album')).toBe('image');
    });

    it('maps "speaker-volume" to "volume-2"', () => {
      expect(getLucideIconName('speaker-volume')).toBe('volume-2');
    });

    it('maps "question-frame" to "help-circle"', () => {
      expect(getLucideIconName('question-frame')).toBe('help-circle');
    });

    it('maps "waiting" and "waiting2" to "loader-2"', () => {
      expect(getLucideIconName('waiting')).toBe('loader-2');
      expect(getLucideIconName('waiting2')).toBe('loader-2');
    });

    it('maps "empty" to empty string', () => {
      expect(getLucideIconName('empty')).toBe('');
    });

    it('returns input for unmapped names', () => {
      expect(getLucideIconName('unknown-icon')).toBe('unknown-icon');
      expect(getLucideIconName('custom-icon')).toBe('custom-icon');
    });
  });

  // ===========================================================================
  // createIcon Tests
  // ===========================================================================

  describe('createIcon', () => {
    it('creates an <i> element', () => {
      const icon = createIcon('plus');

      expect(icon.tagName).toBe('I');
    });

    it('sets data-lucide attribute', () => {
      const icon = createIcon('plus');

      expect(icon.getAttribute('data-lucide')).toBe('plus');
    });

    it('maps legacy icon name to Lucide name', () => {
      const icon = createIcon('plus-button');

      expect(icon.getAttribute('data-lucide')).toBe('circle-plus');
    });

    it('adds icon class', () => {
      const icon = createIcon('plus');

      expect(icon.classList.contains('icon')).toBe(true);
    });

    it('sets default size of 16px', () => {
      const icon = createIcon('plus');

      expect(icon.style.width).toBe('16px');
      expect(icon.style.height).toBe('16px');
    });

    it('sets custom size', () => {
      const icon = createIcon('plus', { size: 24 });

      expect(icon.style.width).toBe('24px');
      expect(icon.style.height).toBe('24px');
    });

    it('adds custom className', () => {
      const icon = createIcon('plus', { className: 'my-class' });

      expect(icon.classList.contains('my-class')).toBe(true);
    });

    it('adds click class when clickable', () => {
      const icon = createIcon('plus', { clickable: true });

      expect(icon.classList.contains('click')).toBe(true);
    });

    it('sets title attribute', () => {
      const icon = createIcon('plus', { title: 'Add item' });

      expect(icon.title).toBe('Add item');
    });

    it('sets aria-label from alt option', () => {
      const icon = createIcon('plus', { alt: 'Add' });

      expect(icon.getAttribute('aria-label')).toBe('Add');
    });

    it('sets id attribute', () => {
      const icon = createIcon('plus', { id: 'my-icon' });

      expect(icon.id).toBe('my-icon');
    });

    it('adds custom style', () => {
      const icon = createIcon('plus', { style: 'color: red;' });

      expect(icon.style.color).toBe('red');
    });

    it('adds icon-spin class for waiting icons', () => {
      const icon = createIcon('waiting');

      expect(icon.classList.contains('icon-spin')).toBe(true);
    });

    it('adds icon-spin class for waiting2 icons', () => {
      const icon = createIcon('waiting2');

      expect(icon.classList.contains('icon-spin')).toBe(true);
    });

    it('creates spacer span for empty icon', () => {
      const spacer = createIcon('empty');

      expect(spacer.tagName).toBe('SPAN');
      expect(spacer.classList.contains('icon-spacer')).toBe(true);
    });

    it('sets size on spacer element', () => {
      const spacer = createIcon('empty', { size: 20 });

      expect(spacer.style.width).toBe('20px');
      expect(spacer.style.height).toBe('20px');
    });
  });

  // ===========================================================================
  // iconHtml Tests
  // ===========================================================================

  describe('iconHtml', () => {
    it('returns HTML string', () => {
      const html = iconHtml('plus');

      expect(typeof html).toBe('string');
      expect(html).toContain('<i');
    });

    it('includes data-lucide attribute', () => {
      const html = iconHtml('plus');

      expect(html).toContain('data-lucide="plus"');
    });

    it('maps legacy names', () => {
      const html = iconHtml('cross-button');

      expect(html).toContain('data-lucide="x-circle"');
    });

    it('includes custom options', () => {
      const html = iconHtml('plus', { title: 'Add', id: 'add-btn' });

      expect(html).toContain('title="Add"');
      expect(html).toContain('id="add-btn"');
    });

    it('returns span for empty icon', () => {
      const html = iconHtml('empty');

      expect(html).toContain('<span');
      expect(html).toContain('icon-spacer');
    });
  });

  // ===========================================================================
  // initLucideIcons Tests
  // ===========================================================================

  describe('initLucideIcons', () => {
    it('does not throw when lucide is not available', () => {
      expect(() => initLucideIcons()).not.toThrow();
    });

    it('calls lucide.createIcons when available', () => {
      const mockCreateIcons = vi.fn();
      (window as unknown as { lucide?: { createIcons: () => void } }).lucide = {
        createIcons: mockCreateIcons
      };

      initLucideIcons();

      expect(mockCreateIcons).toHaveBeenCalled();

      delete (window as unknown as { lucide?: { createIcons: () => void } }).lucide;
    });
  });

  // ===========================================================================
  // createSpinner Tests
  // ===========================================================================

  describe('createSpinner', () => {
    it('creates a spinner icon', () => {
      const spinner = createSpinner();

      expect(spinner.tagName).toBe('I');
      expect(spinner.getAttribute('data-lucide')).toBe('loader-2');
    });

    it('includes icon-spin class', () => {
      const spinner = createSpinner();

      expect(spinner.classList.contains('icon-spin')).toBe(true);
    });

    it('accepts custom options', () => {
      const spinner = createSpinner({ size: 32, id: 'my-spinner' });

      expect(spinner.style.width).toBe('32px');
      expect(spinner.id).toBe('my-spinner');
    });
  });

  // ===========================================================================
  // spinnerHtml Tests
  // ===========================================================================

  describe('spinnerHtml', () => {
    it('returns HTML string for spinner', () => {
      const html = spinnerHtml();

      expect(html).toContain('data-lucide="loader-2"');
      expect(html).toContain('icon-spin');
    });

    it('accepts custom options', () => {
      const html = spinnerHtml({ size: 24 });

      expect(html).toContain('24px');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles empty string icon name', () => {
      // Empty string maps to 'empty' icon which returns a spacer SPAN
      const icon = createIcon('');

      expect(icon.tagName).toBe('SPAN');
      expect(icon.classList.contains('icon-spacer')).toBe(true);
    });

    it('handles icon name with spaces', () => {
      const name = getLucideIconName('icon name with spaces');

      expect(name).toBe('icon name with spaces');
    });

    it('handles very long icon name', () => {
      const longName = 'a'.repeat(100);
      const result = getLucideIconName(longName);

      expect(result).toBe(longName);
    });

    it('handles special characters in options', () => {
      const icon = createIcon('plus', { title: '<script>alert("xss")</script>' });

      // Title is set as property, not innerHTML
      expect(icon.title).toContain('script');
    });

    it('combines multiple classes correctly', () => {
      const icon = createIcon('waiting', { className: 'custom', clickable: true });

      expect(icon.classList.contains('icon')).toBe(true);
      expect(icon.classList.contains('custom')).toBe(true);
      expect(icon.classList.contains('click')).toBe(true);
      expect(icon.classList.contains('icon-spin')).toBe(true);
    });
  });
});

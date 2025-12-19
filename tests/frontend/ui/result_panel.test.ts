/**
 * Tests for ui/result_panel.ts - Result panel for displaying operation results
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  showResultPanel,
  hideResultPanel,
  showErrorInPanel,
  showSuccessInPanel,
  showWordDetails,
  showLoadingInPanel,
  updatePanelContent,
  isPanelVisible
} from '../../../src/frontend/js/ui/result_panel';

describe('ui/result_panel.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // showResultPanel Tests
  // ===========================================================================

  describe('showResultPanel', () => {
    it('creates panel element if it does not exist', () => {
      showResultPanel('Test content');

      expect(document.getElementById('lwt-result-panel')).not.toBeNull();
    });

    it('reuses existing panel element', () => {
      showResultPanel('First');
      showResultPanel('Second');

      const panels = document.querySelectorAll('#lwt-result-panel');
      expect(panels.length).toBe(1);
    });

    it('displays string content', () => {
      showResultPanel('Hello World');

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.innerHTML).toBe('Hello World');
    });

    it('displays HTML element content', () => {
      const div = document.createElement('div');
      div.textContent = 'Custom Element';
      div.className = 'custom-content';

      showResultPanel(div);

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.querySelector('.custom-content')).not.toBeNull();
    });

    it('sets panel title', () => {
      showResultPanel('Content', { title: 'Custom Title' });

      const title = document.querySelector('.lwt-result-panel-title');
      expect(title?.textContent).toBe('Custom Title');
    });

    it('uses default title when not specified', () => {
      showResultPanel('Content');

      const title = document.querySelector('.lwt-result-panel-title');
      expect(title?.textContent).toBe('Result');
    });

    it('makes panel visible', () => {
      showResultPanel('Content');

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(true);
    });

    it('applies position class for right position', () => {
      showResultPanel('Content', { position: 'right' });

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--right')).toBe(true);
    });

    it('applies position class for bottom position', () => {
      showResultPanel('Content', { position: 'bottom' });

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--bottom')).toBe(true);
    });

    it('applies position class for center position', () => {
      showResultPanel('Content', { position: 'center' });

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--center')).toBe(true);
    });

    it('applies custom CSS class', () => {
      showResultPanel('Content', { className: 'custom-class' });

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('custom-class')).toBe(true);
    });

    it('shows close button by default', () => {
      showResultPanel('Content');

      const closeBtn = document.querySelector('.lwt-result-panel-close') as HTMLElement;
      expect(closeBtn?.style.display).not.toBe('none');
    });

    it('hides close button when showCloseButton is false', () => {
      showResultPanel('Content', { showCloseButton: false });

      const closeBtn = document.querySelector('.lwt-result-panel-close') as HTMLElement;
      expect(closeBtn?.style.display).toBe('none');
    });

    it('auto-closes panel after duration', () => {
      showResultPanel('Content', { autoClose: true, duration: 1000 });

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(true);

      vi.advanceTimersByTime(1000);

      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(false);
    });

    it('does not auto-close when autoClose is false', () => {
      showResultPanel('Content', { autoClose: false });

      const panel = document.getElementById('lwt-result-panel');

      vi.advanceTimersByTime(5000);

      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(true);
    });

    it('clears previous auto-close timer', () => {
      showResultPanel('First', { autoClose: true, duration: 1000 });
      showResultPanel('Second', { autoClose: true, duration: 2000 });

      vi.advanceTimersByTime(1500);

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(true);

      vi.advanceTimersByTime(500);

      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(false);
    });

    it('updates content when panel already exists', () => {
      showResultPanel('First');
      showResultPanel('Second');

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.innerHTML).toBe('Second');
    });

    it('resets position class on new show', () => {
      showResultPanel('Content', { position: 'right' });
      showResultPanel('Content', { position: 'bottom' });

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--right')).toBe(false);
      expect(panel?.classList.contains('lwt-result-panel--bottom')).toBe(true);
    });
  });

  // ===========================================================================
  // hideResultPanel Tests
  // ===========================================================================

  describe('hideResultPanel', () => {
    it('removes visible class from panel', () => {
      showResultPanel('Content');
      hideResultPanel();

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(false);
    });

    it('does nothing when panel does not exist', () => {
      // Should not throw
      expect(() => hideResultPanel()).not.toThrow();
    });

    it('clears auto-close timer', () => {
      showResultPanel('Content', { autoClose: true, duration: 1000 });
      hideResultPanel();

      // Show again without auto-close
      showResultPanel('Content', { autoClose: false });

      vi.advanceTimersByTime(2000);

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(true);
    });
  });

  // ===========================================================================
  // showErrorInPanel Tests
  // ===========================================================================

  describe('showErrorInPanel', () => {
    it('shows error message in panel', () => {
      showErrorInPanel('Something went wrong');

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.innerHTML).toContain('Something went wrong');
    });

    it('sets Error title', () => {
      showErrorInPanel('Error message');

      const title = document.querySelector('.lwt-result-panel-title');
      expect(title?.textContent).toBe('Error');
    });

    it('adds error CSS class', () => {
      showErrorInPanel('Error message');

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--error')).toBe(true);
    });

    it('wraps message in error div', () => {
      showErrorInPanel('Error');

      const errorDiv = document.querySelector('.lwt-result-panel-error');
      expect(errorDiv).not.toBeNull();
    });

    it('auto-closes after 5 seconds', () => {
      showErrorInPanel('Error message');

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(true);

      vi.advanceTimersByTime(5000);

      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(false);
    });

    it('escapes HTML in error message', () => {
      showErrorInPanel('<script>alert("xss")</script>');

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.innerHTML).not.toContain('<script>');
      expect(content?.innerHTML).toContain('&lt;script&gt;');
    });
  });

  // ===========================================================================
  // showSuccessInPanel Tests
  // ===========================================================================

  describe('showSuccessInPanel', () => {
    it('shows success message in panel', () => {
      showSuccessInPanel('Operation completed');

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.innerHTML).toContain('Operation completed');
    });

    it('sets Success title', () => {
      showSuccessInPanel('Success');

      const title = document.querySelector('.lwt-result-panel-title');
      expect(title?.textContent).toBe('Success');
    });

    it('adds success CSS class', () => {
      showSuccessInPanel('Success');

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--success')).toBe(true);
    });

    it('wraps message in success div', () => {
      showSuccessInPanel('Success');

      const successDiv = document.querySelector('.lwt-result-panel-success');
      expect(successDiv).not.toBeNull();
    });

    it('auto-closes after 2 seconds', () => {
      showSuccessInPanel('Success');

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(true);

      vi.advanceTimersByTime(2000);

      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(false);
    });

    it('escapes HTML in success message', () => {
      showSuccessInPanel('<b>Bold</b>');

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.innerHTML).toContain('&lt;b&gt;');
    });
  });

  // ===========================================================================
  // showWordDetails Tests
  // ===========================================================================

  describe('showWordDetails', () => {
    it('displays word text', () => {
      showWordDetails({ text: 'hello' });

      const wordText = document.querySelector('.lwt-word-text');
      expect(wordText?.textContent).toBe('hello');
    });

    it('displays translation when provided', () => {
      showWordDetails({ text: 'hello', translation: 'bonjour' });

      const translation = document.querySelector('.lwt-word-translation');
      expect(translation?.textContent).toBe('bonjour');
    });

    it('does not display translation when not provided', () => {
      showWordDetails({ text: 'hello' });

      const translation = document.querySelector('.lwt-word-translation');
      expect(translation).toBeNull();
    });

    it('displays romanization when provided', () => {
      showWordDetails({ text: 'こんにちは', romanization: 'konnichiwa' });

      const romanization = document.querySelector('.lwt-word-romanization');
      expect(romanization?.textContent).toBe('konnichiwa');
    });

    it('does not display romanization when not provided', () => {
      showWordDetails({ text: 'hello' });

      const romanization = document.querySelector('.lwt-word-romanization');
      expect(romanization).toBeNull();
    });

    it('displays status name when provided', () => {
      showWordDetails({ text: 'hello', statusName: 'Learning (2)' });

      const status = document.querySelector('.lwt-word-status');
      expect(status?.textContent).toContain('Learning (2)');
    });

    it('sets Word Details title', () => {
      showWordDetails({ text: 'hello' });

      const title = document.querySelector('.lwt-result-panel-title');
      expect(title?.textContent).toBe('Word Details');
    });

    it('shows close button', () => {
      showWordDetails({ text: 'hello' });

      const closeBtn = document.querySelector('.lwt-result-panel-close') as HTMLElement;
      expect(closeBtn?.style.display).not.toBe('none');
    });

    it('escapes HTML in all fields', () => {
      showWordDetails({
        text: '<script>',
        translation: '<img>',
        romanization: '<div>',
        statusName: '<span>'
      });

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.innerHTML).not.toContain('<script>');
      expect(content?.innerHTML).not.toContain('<img>');
    });
  });

  // ===========================================================================
  // showLoadingInPanel Tests
  // ===========================================================================

  describe('showLoadingInPanel', () => {
    it('shows loading spinner', () => {
      showLoadingInPanel();

      const spinner = document.querySelector('.lwt-loading-spinner');
      expect(spinner).not.toBeNull();
    });

    it('displays default loading message', () => {
      showLoadingInPanel();

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.textContent).toContain('Loading...');
    });

    it('displays custom loading message', () => {
      showLoadingInPanel('Fetching data...');

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.textContent).toContain('Fetching data...');
    });

    it('sets Loading title', () => {
      showLoadingInPanel();

      const title = document.querySelector('.lwt-result-panel-title');
      expect(title?.textContent).toBe('Loading');
    });

    it('hides close button', () => {
      showLoadingInPanel();

      const closeBtn = document.querySelector('.lwt-result-panel-close') as HTMLElement;
      expect(closeBtn?.style.display).toBe('none');
    });

    it('does not auto-close', () => {
      showLoadingInPanel();

      const panel = document.getElementById('lwt-result-panel');

      vi.advanceTimersByTime(10000);

      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(true);
    });
  });

  // ===========================================================================
  // updatePanelContent Tests
  // ===========================================================================

  describe('updatePanelContent', () => {
    it('updates content with string', () => {
      showResultPanel('Initial');
      updatePanelContent('Updated');

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.innerHTML).toBe('Updated');
    });

    it('updates content with HTML element', () => {
      showResultPanel('Initial');

      const div = document.createElement('div');
      div.className = 'updated-content';
      div.textContent = 'Updated';

      updatePanelContent(div);

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.querySelector('.updated-content')).not.toBeNull();
    });

    it('does nothing when panel does not exist', () => {
      // Should not throw
      expect(() => updatePanelContent('Content')).not.toThrow();
    });

    it('preserves panel visibility', () => {
      showResultPanel('Initial');
      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(true);

      updatePanelContent('Updated');

      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(true);
    });

    it('preserves panel title', () => {
      showResultPanel('Initial', { title: 'Custom' });
      updatePanelContent('Updated');

      const title = document.querySelector('.lwt-result-panel-title');
      expect(title?.textContent).toBe('Custom');
    });
  });

  // ===========================================================================
  // isPanelVisible Tests
  // ===========================================================================

  describe('isPanelVisible', () => {
    it('returns true when panel is visible', () => {
      showResultPanel('Content');

      expect(isPanelVisible()).toBe(true);
    });

    it('returns false when panel is hidden', () => {
      showResultPanel('Content');
      hideResultPanel();

      expect(isPanelVisible()).toBe(false);
    });

    it('returns false after auto-close', () => {
      showResultPanel('Content', { autoClose: true, duration: 1000 });

      vi.advanceTimersByTime(1000);

      expect(isPanelVisible()).toBe(false);
    });

    it('returns false when panel was never shown in module', () => {
      // Note: This test documents the behavior based on module-level state.
      // If no panel has been created yet, isPanelVisible returns false.
      // However, because tests share module state, this test may see a
      // previously-created panel. Instead, we test hiding after showing.
      hideResultPanel();

      expect(isPanelVisible()).toBe(false);
    });
  });

  // ===========================================================================
  // Close Button Tests
  // ===========================================================================

  describe('Close Button', () => {
    it('hides panel when close button is clicked', () => {
      showResultPanel('Content');

      const closeBtn = document.querySelector('.lwt-result-panel-close') as HTMLElement;
      closeBtn.click();

      const panel = document.getElementById('lwt-result-panel');
      expect(panel?.classList.contains('lwt-result-panel--visible')).toBe(false);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles empty string content', () => {
      showResultPanel('');

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.innerHTML).toBe('');
    });

    it('handles very long content', () => {
      const longContent = 'A'.repeat(10000);
      showResultPanel(longContent);

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.innerHTML).toBe(longContent);
    });

    it('handles Unicode content', () => {
      showResultPanel('こんにちは 世界 🌍');

      const content = document.querySelector('.lwt-result-panel-content');
      expect(content?.innerHTML).toBe('こんにちは 世界 🌍');
    });

    it('handles rapid show/hide cycles', () => {
      for (let i = 0; i < 100; i++) {
        showResultPanel(`Content ${i}`);
        hideResultPanel();
      }

      showResultPanel('Final');

      const panels = document.querySelectorAll('#lwt-result-panel');
      expect(panels.length).toBe(1);
      expect(isPanelVisible()).toBe(true);
    });

    it('handles zero duration - auto-close not triggered', () => {
      // Note: duration: 0 is falsy, so auto-close is not set up
      // This is intentional - zero duration means "no auto-close"
      showResultPanel('Content', { autoClose: true, duration: 0 });

      // Should be visible
      expect(isPanelVisible()).toBe(true);

      // Advancing time shouldn't close it (no timer was set)
      vi.advanceTimersByTime(1000);
      expect(isPanelVisible()).toBe(true);

      // Must be closed manually
      hideResultPanel();
      expect(isPanelVisible()).toBe(false);
    });

    it('handles showing different content types sequentially', () => {
      showResultPanel('String');
      showErrorInPanel('Error');
      showSuccessInPanel('Success');
      showLoadingInPanel();
      showWordDetails({ text: 'word' });

      const panels = document.querySelectorAll('#lwt-result-panel');
      expect(panels.length).toBe(1);
    });

    it('handles panel removed from DOM externally', () => {
      showResultPanel('Content');

      // Simulate external removal
      document.getElementById('lwt-result-panel')?.remove();

      // Should recreate panel
      showResultPanel('New Content');

      expect(document.getElementById('lwt-result-panel')).not.toBeNull();
    });
  });

  // ===========================================================================
  // CSS Styles Injection Tests
  // ===========================================================================

  describe('CSS Styles', () => {
    it('injects styles into document head', () => {
      // Styles are injected on module load
      const styleEl = document.getElementById('lwt-result-panel-styles');
      expect(styleEl).not.toBeNull();
    });

    it('does not duplicate styles', () => {
      // Re-import should not add duplicate styles
      const styleElements = document.querySelectorAll('#lwt-result-panel-styles');
      expect(styleElements.length).toBe(1);
    });
  });
});

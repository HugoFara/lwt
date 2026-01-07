/**
 * Tests for native_tooltip.ts - Native tooltip implementation
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Reset module state before each test
beforeEach(() => {
  document.body.innerHTML = '';
  document.querySelectorAll('style').forEach(el => el.remove());
  // Remove any existing tooltips
  document.querySelectorAll('.lwt-tooltip, .ui-tooltip').forEach(el => el.remove());
});

import { initLanguageConfig, resetLanguageConfig } from '../../../src/frontend/js/modules/language/stores/language_config';

// Dynamic import to reset module state
async function importNativeTooltip() {
  vi.resetModules();
  return await import('../../../src/frontend/js/shared/components/native_tooltip');
}

beforeEach(() => {
  // Initialize language config with delimiter
  resetLanguageConfig();
  initLanguageConfig({ delimiter: ',' });
});

describe('native_tooltip.ts', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // generateWordTooltipContent Tests
  // ===========================================================================

  describe('generateWordTooltipContent', () => {
    it('generates basic tooltip content', async () => {
      const { generateWordTooltipContent } = await importNativeTooltip();

      document.body.innerHTML = `
        <span class="hword"
              data_text="test"
              data_rom="roman"
              data_trans="translation"
              data_status="3"
              data_ann="">test</span>
      `;

      const element = document.querySelector('.hword') as HTMLElement;
      const content = generateWordTooltipContent(element);

      expect(content).toContain('test');
      expect(content).toContain('Roman.');
      expect(content).toContain('roman');
      expect(content).toContain('Transl.');
      expect(content).toContain('translation');
      expect(content).toContain('Status');
      expect(content).toContain('Learning');
    });

    it('handles mwsty class for multiwords', async () => {
      const { generateWordTooltipContent } = await importNativeTooltip();

      document.body.innerHTML = `
        <span class="hword mwsty"
              data_text="multi word"
              data_trans="translation"
              data_status="3">display</span>
      `;

      const element = document.querySelector('.hword') as HTMLElement;
      const content = generateWordTooltipContent(element);

      expect(content).toContain('multi word');
    });

    it('shows Unknown status for status 0', async () => {
      const { generateWordTooltipContent } = await importNativeTooltip();

      document.body.innerHTML = `
        <span class="hword" data_status="0" data_trans="">word</span>
      `;

      const element = document.querySelector('.hword') as HTMLElement;
      const content = generateWordTooltipContent(element);

      expect(content).toContain('Unknown');
    });

    it('shows Learned status for status 5', async () => {
      const { generateWordTooltipContent } = await importNativeTooltip();

      document.body.innerHTML = `
        <span class="hword" data_status="5" data_trans="">word</span>
      `;

      const element = document.querySelector('.hword') as HTMLElement;
      const content = generateWordTooltipContent(element);

      expect(content).toContain('Learned');
    });

    it('shows Ignored status for status 98', async () => {
      const { generateWordTooltipContent } = await importNativeTooltip();

      document.body.innerHTML = `
        <span class="hword" data_status="98" data_trans="">word</span>
      `;

      const element = document.querySelector('.hword') as HTMLElement;
      const content = generateWordTooltipContent(element);

      expect(content).toContain('Ignored');
    });

    it('shows Well Known status for status 99', async () => {
      const { generateWordTooltipContent } = await importNativeTooltip();

      document.body.innerHTML = `
        <span class="hword" data_status="99" data_trans="">word</span>
      `;

      const element = document.querySelector('.hword') as HTMLElement;
      const content = generateWordTooltipContent(element);

      expect(content).toContain('Well Known');
    });

    it('skips translation when empty', async () => {
      const { generateWordTooltipContent } = await importNativeTooltip();

      document.body.innerHTML = `
        <span class="hword" data_status="3" data_trans="">word</span>
      `;

      const element = document.querySelector('.hword') as HTMLElement;
      const content = generateWordTooltipContent(element);

      expect(content).not.toContain('Transl.');
    });

    it('skips translation when asterisk', async () => {
      const { generateWordTooltipContent } = await importNativeTooltip();

      document.body.innerHTML = `
        <span class="hword" data_status="3" data_trans="*">word</span>
      `;

      const element = document.querySelector('.hword') as HTMLElement;
      const content = generateWordTooltipContent(element);

      expect(content).not.toContain('Transl.');
    });

    it('skips romanization when empty', async () => {
      const { generateWordTooltipContent } = await importNativeTooltip();

      document.body.innerHTML = `
        <span class="hword" data_status="3" data_trans="trans" data_rom="">word</span>
      `;

      const element = document.querySelector('.hword') as HTMLElement;
      const content = generateWordTooltipContent(element);

      expect(content).not.toContain('Roman.');
    });

    it('handles missing attributes gracefully', async () => {
      const { generateWordTooltipContent } = await importNativeTooltip();

      document.body.innerHTML = `<span class="hword">word</span>`;

      const element = document.querySelector('.hword') as HTMLElement;
      const content = generateWordTooltipContent(element);

      expect(content).toBeDefined();
      expect(content).toContain('word');
    });
  });

  // ===========================================================================
  // initNativeTooltips Tests
  // ===========================================================================

  describe('initNativeTooltips', () => {
    it('initializes without error', async () => {
      const { initNativeTooltips } = await importNativeTooltip();

      document.body.innerHTML = `
        <div id="container">
          <span class="hword" data_status="3" data_trans="test">Word</span>
        </div>
      `;

      const container = document.getElementById('container')!;
      expect(() => initNativeTooltips(container)).not.toThrow();
    });

    it('accepts string selector', async () => {
      const { initNativeTooltips } = await importNativeTooltip();

      document.body.innerHTML = `
        <div id="container">
          <span class="hword" data_status="3" data_trans="test">Word</span>
        </div>
      `;

      expect(() => initNativeTooltips('#container')).not.toThrow();
    });

    it('handles missing container gracefully', async () => {
      const { initNativeTooltips } = await importNativeTooltip();

      expect(() => initNativeTooltips('#nonexistent')).not.toThrow();
    });
  });

  // ===========================================================================
  // removeAllTooltips Tests
  // ===========================================================================

  describe('removeAllTooltips', () => {
    it('removes tooltip elements from DOM', async () => {
      const { removeAllTooltips } = await importNativeTooltip();

      document.body.innerHTML = `
        <div class="ui-tooltip">Tooltip 1</div>
        <div class="ui-tooltip">Tooltip 2</div>
      `;

      expect(document.querySelectorAll('.ui-tooltip').length).toBe(2);

      removeAllTooltips();

      expect(document.querySelectorAll('.ui-tooltip').length).toBe(0);
    });

    it('does not throw when no tooltips exist', async () => {
      const { removeAllTooltips } = await importNativeTooltip();

      document.body.innerHTML = '<div>No tooltips here</div>';

      expect(() => removeAllTooltips()).not.toThrow();
    });
  });

  // ===========================================================================
  // isTooltipVisible Tests
  // ===========================================================================

  describe('isTooltipVisible', () => {
    it('returns false when no tooltip shown', async () => {
      const { isTooltipVisible } = await importNativeTooltip();

      expect(isTooltipVisible()).toBe(false);
    });
  });

  // ===========================================================================
  // getCurrentTooltipTarget Tests
  // ===========================================================================

  describe('getCurrentTooltipTarget', () => {
    it('returns null when no tooltip shown', async () => {
      const { getCurrentTooltipTarget } = await importNativeTooltip();

      expect(getCurrentTooltipTarget()).toBeNull();
    });
  });

  // ===========================================================================
  // Tooltip Show/Hide Behavior Tests
  // ===========================================================================

  describe('Tooltip show/hide behavior', () => {
    beforeEach(() => {
      vi.useFakeTimers();
    });

    afterEach(() => {
      vi.useRealTimers();
    });

    it('shows tooltip on mouseenter for hword elements', async () => {
      const { initNativeTooltips, isTooltipVisible } = await importNativeTooltip();

      document.body.innerHTML = `
        <div id="container">
          <span class="hword" data_status="3" data_trans="hello">word</span>
        </div>
      `;

      initNativeTooltips('#container');

      const hword = document.querySelector('.hword') as HTMLElement;
      const event = new MouseEvent('mouseenter', { bubbles: true });
      hword.dispatchEvent(event);

      // Advance timer past show delay (100ms)
      vi.advanceTimersByTime(150);

      expect(isTooltipVisible()).toBe(true);
    });

    it('hides tooltip on mouseleave', async () => {
      const { initNativeTooltips, isTooltipVisible } = await importNativeTooltip();

      document.body.innerHTML = `
        <div id="container">
          <span class="hword" data_status="3" data_trans="test">word</span>
        </div>
      `;

      initNativeTooltips('#container');

      const hword = document.querySelector('.hword') as HTMLElement;

      // Show tooltip
      hword.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));
      vi.advanceTimersByTime(150);
      expect(isTooltipVisible()).toBe(true);

      // Hide tooltip
      hword.dispatchEvent(new MouseEvent('mouseleave', { bubbles: true }));
      vi.advanceTimersByTime(150);
      expect(isTooltipVisible()).toBe(false);
    });

    it('ignores mouseenter on non-hword elements', async () => {
      const { initNativeTooltips, isTooltipVisible } = await importNativeTooltip();

      document.body.innerHTML = `
        <div id="container">
          <span class="notword">normal text</span>
        </div>
      `;

      initNativeTooltips('#container');

      const span = document.querySelector('.notword') as HTMLElement;
      span.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));
      vi.advanceTimersByTime(150);

      expect(isTooltipVisible()).toBe(false);
    });

    it('shows tooltip on focus for accessibility', async () => {
      const { initNativeTooltips, isTooltipVisible } = await importNativeTooltip();

      document.body.innerHTML = `
        <div id="container">
          <span class="hword" data_status="3" data_trans="test" tabindex="0">word</span>
        </div>
      `;

      initNativeTooltips('#container');

      const hword = document.querySelector('.hword') as HTMLElement;
      hword.dispatchEvent(new FocusEvent('focusin', { bubbles: true }));

      expect(isTooltipVisible()).toBe(true);
    });

    it('hides tooltip on focusout', async () => {
      const { initNativeTooltips, isTooltipVisible } = await importNativeTooltip();

      document.body.innerHTML = `
        <div id="container">
          <span class="hword" data_status="3" data_trans="test" tabindex="0">word</span>
        </div>
      `;

      initNativeTooltips('#container');

      const hword = document.querySelector('.hword') as HTMLElement;

      // Show via focus
      hword.dispatchEvent(new FocusEvent('focusin', { bubbles: true }));
      expect(isTooltipVisible()).toBe(true);

      // Hide via focusout
      hword.dispatchEvent(new FocusEvent('focusout', { bubbles: true }));
      vi.advanceTimersByTime(150);

      expect(isTooltipVisible()).toBe(false);
    });

    it('cancels pending show if mouseleave happens quickly', async () => {
      const { initNativeTooltips, isTooltipVisible } = await importNativeTooltip();

      document.body.innerHTML = `
        <div id="container">
          <span class="hword" data_status="3" data_trans="test">word</span>
        </div>
      `;

      initNativeTooltips('#container');

      const hword = document.querySelector('.hword') as HTMLElement;

      // Enter and leave quickly (before show delay)
      hword.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));
      vi.advanceTimersByTime(50); // Less than 100ms show delay
      hword.dispatchEvent(new MouseEvent('mouseleave', { bubbles: true }));
      vi.advanceTimersByTime(150);

      // Tooltip should not appear
      expect(isTooltipVisible()).toBe(false);
    });

    it('cancels pending hide if mouseenter happens during hide delay', async () => {
      const { initNativeTooltips, isTooltipVisible } = await importNativeTooltip();

      document.body.innerHTML = `
        <div id="container">
          <span class="hword" data_status="3" data_trans="test">word</span>
        </div>
      `;

      initNativeTooltips('#container');

      const hword = document.querySelector('.hword') as HTMLElement;

      // Show tooltip
      hword.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));
      vi.advanceTimersByTime(150);
      expect(isTooltipVisible()).toBe(true);

      // Start to hide
      hword.dispatchEvent(new MouseEvent('mouseleave', { bubbles: true }));
      vi.advanceTimersByTime(50); // Less than hide delay

      // Re-enter
      hword.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));
      vi.advanceTimersByTime(150);

      // Should still be visible
      expect(isTooltipVisible()).toBe(true);
    });

    it('getCurrentTooltipTarget returns target element', async () => {
      const { initNativeTooltips, getCurrentTooltipTarget } = await importNativeTooltip();

      document.body.innerHTML = `
        <div id="container">
          <span class="hword" data_status="3" data_trans="test">word</span>
        </div>
      `;

      initNativeTooltips('#container');

      const hword = document.querySelector('.hword') as HTMLElement;
      hword.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));
      vi.advanceTimersByTime(150);

      expect(getCurrentTooltipTarget()).toBe(hword);
    });
  });

  // ===========================================================================
  // CSS Injection Tests
  // ===========================================================================

  describe('CSS injection', () => {
    it('injects styles into document head', async () => {
      await importNativeTooltip();

      const styleElements = document.querySelectorAll('style');
      const hasTooltipStyles = Array.from(styleElements).some(
        el => el.textContent?.includes('.lwt-tooltip')
      );
      expect(hasTooltipStyles).toBe(true);
    });

    it('styles include tooltip positioning', async () => {
      await importNativeTooltip();

      const styleElements = document.querySelectorAll('style');
      const tooltipStyle = Array.from(styleElements).find(
        el => el.textContent?.includes('.lwt-tooltip')
      );
      expect(tooltipStyle?.textContent).toContain('position: absolute');
    });

    it('styles include tooltip background color', async () => {
      await importNativeTooltip();

      const styleElements = document.querySelectorAll('style');
      const tooltipStyle = Array.from(styleElements).find(
        el => el.textContent?.includes('.lwt-tooltip')
      );
      expect(tooltipStyle?.textContent).toContain('#FFFFE8');
    });
  });
});

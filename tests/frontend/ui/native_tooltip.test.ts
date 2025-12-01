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

// Dynamic import to reset module state
async function importNativeTooltip() {
  vi.resetModules();
  return await import('../../../src/frontend/js/ui/native_tooltip');
}

// Setup mock LWT_DATA
const mockLWT_DATA = {
  language: {
    delimiter: ',',
  },
};

beforeEach(() => {
  (window as unknown as Record<string, unknown>).LWT_DATA = mockLWT_DATA;
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
    it('removes jQuery UI tooltips', async () => {
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

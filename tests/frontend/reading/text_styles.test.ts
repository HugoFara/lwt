/**
 * Tests for reading/text_styles.ts - Dynamic CSS generation for text reading
 */
import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import {
  generateTextStyles,
  injectTextStyles,
  generateParagraphStyles,
  removeTextStyles
} from '../../../src/frontend/js/reading/text_styles';
import type { TextReadingConfig } from '../../../src/frontend/js/api/texts';

describe('reading/text_styles.ts', () => {
  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
  });

  afterEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // Test Data Helpers
  // ===========================================================================

  function createConfig(overrides: Partial<TextReadingConfig> = {}): TextReadingConfig {
    return {
      textId: 1,
      langId: 1,
      textSize: 100,
      showLearning: true,
      displayStatTrans: 31, // Show status 1-5
      modeTrans: 1, // After text
      annTextSize: 75,
      removeSpaces: false,
      rightToLeft: false,
      ...overrides
    };
  }

  // ===========================================================================
  // generateTextStyles Tests
  // ===========================================================================

  describe('generateTextStyles', () => {
    it('generates CSS string', () => {
      const config = createConfig();
      const css = generateTextStyles(config);

      expect(typeof css).toBe('string');
      expect(css.length).toBeGreaterThan(0);
    });

    it('includes hide class rule', () => {
      const config = createConfig();
      const css = generateTextStyles(config);

      expect(css).toContain('.hide{display:none !important;}');
    });

    it('generates rules for status translations when showLearning is true', () => {
      const config = createConfig({ showLearning: true, displayStatTrans: 1 }); // Show status 1
      const css = generateTextStyles(config);

      expect(css).toContain('.wsty.status1');
    });

    it('skips status rules when showLearning is false', () => {
      const config = createConfig({ showLearning: false });
      const css = generateTextStyles(config);

      expect(css).not.toContain('.wsty.status1:after');
    });

    it('uses after pseudo-element for modeTrans 1', () => {
      const config = createConfig({ modeTrans: 1 });
      const css = generateTextStyles(config);

      expect(css).toContain(':after');
    });

    it('uses before pseudo-element for modeTrans 3', () => {
      const config = createConfig({ modeTrans: 3 });
      const css = generateTextStyles(config);

      expect(css).toContain(':before');
    });

    it('generates ruby layout for modeTrans 2', () => {
      const config = createConfig({ modeTrans: 2 });
      const css = generateTextStyles(config);

      expect(css).toContain('display: inline-block');
      expect(css).toContain('text-align: center');
    });

    it('generates ruby layout for modeTrans 4', () => {
      const config = createConfig({ modeTrans: 4 });
      const css = generateTextStyles(config);

      expect(css).toContain('margin-top: 0.2em');
    });

    it('uses annTextSize for font-size', () => {
      const config = createConfig({ annTextSize: 80 });
      const css = generateTextStyles(config);

      expect(css).toContain('font-size:80%');
    });

    it('generates rules for each enabled status', () => {
      // displayStatTrans: 1=status1, 2=status2, 4=status3, 8=status4, 16=status5
      const config = createConfig({ displayStatTrans: 7 }); // status 1, 2, 3
      const css = generateTextStyles(config);

      expect(css).toContain('status1');
      expect(css).toContain('status2');
      expect(css).toContain('status3');
      expect(css).not.toContain('status4:');
    });

    it('handles ignored status (98) in displayStatTrans', () => {
      const config = createConfig({ displayStatTrans: 32 }); // 32 = ignored
      const css = generateTextStyles(config);

      expect(css).toContain('status98');
    });

    it('handles well-known status (99) in displayStatTrans', () => {
      const config = createConfig({ displayStatTrans: 64 }); // 64 = well-known
      const css = generateTextStyles(config);

      expect(css).toContain('status99');
    });

    it('includes max-width constraint', () => {
      const config = createConfig();
      const css = generateTextStyles(config);

      expect(css).toContain('max-width:15em');
    });
  });

  // ===========================================================================
  // injectTextStyles Tests
  // ===========================================================================

  describe('injectTextStyles', () => {
    it('creates style element in head', () => {
      const config = createConfig();

      injectTextStyles(config);

      const styleEl = document.getElementById('text-dynamic-styles');
      expect(styleEl).not.toBeNull();
      expect(styleEl?.tagName).toBe('STYLE');
    });

    it('sets correct ID on style element', () => {
      const config = createConfig();

      injectTextStyles(config);

      const styleEl = document.getElementById('text-dynamic-styles');
      expect(styleEl).not.toBeNull();
    });

    it('includes generated CSS content', () => {
      const config = createConfig({ annTextSize: 60 });

      injectTextStyles(config);

      const styleEl = document.getElementById('text-dynamic-styles');
      expect(styleEl?.textContent).toContain('font-size:60%');
    });

    it('removes existing style element before adding new one', () => {
      const config1 = createConfig({ annTextSize: 70 });
      const config2 = createConfig({ annTextSize: 90 });

      injectTextStyles(config1);
      injectTextStyles(config2);

      const styleElements = document.querySelectorAll('#text-dynamic-styles');
      expect(styleElements.length).toBe(1);
      expect(styleElements[0].textContent).toContain('font-size:90%');
    });

    it('appends style to document head', () => {
      const config = createConfig();

      injectTextStyles(config);

      const styleEl = document.head.querySelector('#text-dynamic-styles');
      expect(styleEl).not.toBeNull();
    });
  });

  // ===========================================================================
  // generateParagraphStyles Tests
  // ===========================================================================

  describe('generateParagraphStyles', () => {
    it('includes margin-bottom', () => {
      const config = createConfig();
      const style = generateParagraphStyles(config);

      expect(style).toContain('margin-bottom: 10px');
    });

    it('includes font-size based on textSize', () => {
      const config = createConfig({ textSize: 120 });
      const style = generateParagraphStyles(config);

      expect(style).toContain('font-size: 120%');
    });

    it('includes word-break for removeSpaces languages', () => {
      const config = createConfig({ removeSpaces: true });
      const style = generateParagraphStyles(config);

      expect(style).toContain('word-break:break-all');
    });

    it('excludes word-break when removeSpaces is false', () => {
      const config = createConfig({ removeSpaces: false });
      const style = generateParagraphStyles(config);

      expect(style).not.toContain('word-break');
    });

    it('uses line-height 1 for ruby mode', () => {
      const config = createConfig({ modeTrans: 2 }); // Ruby above
      const style = generateParagraphStyles(config);

      expect(style).toContain('line-height: 1');
    });

    it('uses line-height 1.4 for non-ruby mode', () => {
      const config = createConfig({ modeTrans: 1 });
      const style = generateParagraphStyles(config);

      expect(style).toContain('line-height: 1.4');
    });
  });

  // ===========================================================================
  // removeTextStyles Tests
  // ===========================================================================

  describe('removeTextStyles', () => {
    it('removes existing style element', () => {
      const config = createConfig();
      injectTextStyles(config);

      removeTextStyles();

      const styleEl = document.getElementById('text-dynamic-styles');
      expect(styleEl).toBeNull();
    });

    it('does not throw when style element does not exist', () => {
      expect(() => removeTextStyles()).not.toThrow();
    });

    it('can be called multiple times without error', () => {
      const config = createConfig();
      injectTextStyles(config);

      removeTextStyles();
      removeTextStyles();
      removeTextStyles();

      const styleEl = document.getElementById('text-dynamic-styles');
      expect(styleEl).toBeNull();
    });
  });

  // ===========================================================================
  // Status Bitmap Tests
  // ===========================================================================

  describe('Status Bitmap', () => {
    it('shows status 1 when bit 1 is set', () => {
      const config = createConfig({ displayStatTrans: 1 });
      const css = generateTextStyles(config);

      expect(css).toContain('status1');
    });

    it('shows status 2 when bit 2 is set', () => {
      const config = createConfig({ displayStatTrans: 2 });
      const css = generateTextStyles(config);

      expect(css).toContain('status2');
    });

    it('shows status 3 when bit 4 is set', () => {
      const config = createConfig({ displayStatTrans: 4 });
      const css = generateTextStyles(config);

      expect(css).toContain('status3');
    });

    it('shows status 4 when bit 8 is set', () => {
      const config = createConfig({ displayStatTrans: 8 });
      const css = generateTextStyles(config);

      expect(css).toContain('status4');
    });

    it('shows status 5 when bit 16 is set', () => {
      const config = createConfig({ displayStatTrans: 16 });
      const css = generateTextStyles(config);

      expect(css).toContain('status5');
    });

    it('shows all statuses when all bits set', () => {
      const config = createConfig({ displayStatTrans: 127 }); // All bits 1-64
      const css = generateTextStyles(config);

      expect(css).toContain('status1');
      expect(css).toContain('status2');
      expect(css).toContain('status3');
      expect(css).toContain('status4');
      expect(css).toContain('status5');
      expect(css).toContain('status98');
      expect(css).toContain('status99');
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles zero annTextSize', () => {
      const config = createConfig({ annTextSize: 0 });
      const css = generateTextStyles(config);

      expect(css).toContain('font-size:0%');
    });

    it('handles very large textSize', () => {
      const config = createConfig({ textSize: 500 });
      const style = generateParagraphStyles(config);

      expect(style).toContain('font-size: 500%');
    });

    it('handles displayStatTrans of 0', () => {
      const config = createConfig({ displayStatTrans: 0 });
      const css = generateTextStyles(config);

      // Should not have status-specific rules (except general rules)
      expect(css).not.toMatch(/\.wsty\.status\d+:after\{content/);
    });

    it('handles modeTrans value 0 (hidden)', () => {
      const config = createConfig({ modeTrans: 0 });
      const css = generateTextStyles(config);

      // Should still generate valid CSS
      expect(css.length).toBeGreaterThan(0);
    });
  });
});

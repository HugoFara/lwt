/**
 * Tests for expression_interactable.ts - Auto-initialization for multi-word expressions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { autoInitExpressionInteractables } from '../../../src/frontend/js/modules/vocabulary/pages/expression_interactable';

// Mock dependencies
vi.mock('../../../src/frontend/js/modules/vocabulary/services/word_status', () => ({
  make_tooltip: vi.fn((text, trans, rom, status) =>
    `Tooltip: ${text} - ${trans} (${status})`
  )
}));

vi.mock('../../../src/frontend/js/shared/utils/user_interactions', () => ({
  newExpressionInteractable: vi.fn()
}));

import { make_tooltip } from '../../../src/frontend/js/modules/vocabulary/services/word_status';
import { newExpressionInteractable } from '../../../src/frontend/js/shared/utils/user_interactions';
import { initTextConfig, resetTextConfig } from '../../../src/frontend/js/modules/text/stores/text_config';

describe('expression_interactable.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Initialize text config with text ID
    resetTextConfig();
    initTextConfig({ id: 1 });
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // autoInitExpressionInteractables Tests - Multi-word Config
  // ===========================================================================

  describe('multi-word config initialization', () => {
    it('processes multi-word config elements', () => {
      document.body.innerHTML = `
        <script data-lwt-multiword-config type="application/json">
          {
            "attrs": {
              "class": "status2",
              "data_trans": "translation",
              "data_rom": "romanization",
              "data_code": 2,
              "data_status": "2",
              "data_wid": 123
            },
            "multiWords": {
              "1": { "0": "multi word" }
            },
            "hex": "abc123",
            "showAll": false
          }
        </script>
      `;

      autoInitExpressionInteractables();

      expect(newExpressionInteractable).toHaveBeenCalled();
      expect(make_tooltip).toHaveBeenCalledWith(
        'multi word',
        'translation',
        'romanization',
        2
      );
    });

    it('removes script tag after processing', () => {
      document.body.innerHTML = `
        <script data-lwt-multiword-config type="application/json">
          {
            "attrs": {
              "class": "status1",
              "data_trans": "",
              "data_rom": "",
              "data_code": 1,
              "data_status": "1",
              "data_wid": 1
            },
            "multiWords": { "1": { "0": "word" } },
            "hex": "abc",
            "showAll": true
          }
        </script>
      `;

      autoInitExpressionInteractables();

      expect(document.querySelector('[data-lwt-multiword-config]')).toBeNull();
    });

    it('warns when text ID is not available', () => {
      const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

      // Reset text config to have no text ID (id = 0)
      resetTextConfig();

      document.body.innerHTML = `
        <script data-lwt-multiword-config type="application/json">
          {
            "attrs": { "class": "", "data_trans": "", "data_rom": "", "data_code": 1, "data_status": "1", "data_wid": 1 },
            "multiWords": {},
            "hex": "abc",
            "showAll": false
          }
        </script>
      `;

      autoInitExpressionInteractables();

      expect(warnSpy).toHaveBeenCalledWith('Text ID not available for multi-word init');
    });

    it('handles invalid JSON gracefully', () => {
      const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      document.body.innerHTML = `
        <script data-lwt-multiword-config type="application/json">
          not valid json
        </script>
      `;

      autoInitExpressionInteractables();

      expect(errorSpy).toHaveBeenCalledWith(
        'Failed to parse multi-word config:',
        expect.any(Error)
      );
    });

    it('always creates native tooltip regardless of settings', () => {
      // Native tooltips are always created
      document.body.innerHTML = `
        <script data-lwt-multiword-config type="application/json">
          {
            "attrs": { "class": "status1", "data_trans": "trans", "data_rom": "", "data_code": 1, "data_status": "1", "data_wid": 1 },
            "multiWords": { "1": { "0": "word" } },
            "hex": "abc",
            "showAll": false
          }
        </script>
      `;

      autoInitExpressionInteractables();

      expect(make_tooltip).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // autoInitExpressionInteractables Tests - Expression Config (v2)
  // ===========================================================================

  describe('expression config (v2) initialization', () => {
    it('processes expression config elements', () => {
      document.body.innerHTML = `
        <script data-lwt-expression-config type="application/json">
          {
            "attrs": {
              "class": "status3",
              "data_trans": "meaning",
              "data_rom": "roman",
              "data_code": 3,
              "data_status": "3",
              "data_wid": 456
            },
            "appendText": { "0": "append text" },
            "term": "expression term",
            "len": 3,
            "hex": "def456",
            "showAll": true
          }
        </script>
      `;

      autoInitExpressionInteractables();

      expect(newExpressionInteractable).toHaveBeenCalledWith(
        { "0": "append text" },
        expect.stringContaining('class="status3"'),
        3,
        "def456",
        true
      );
    });

    it('removes script tag after processing', () => {
      document.body.innerHTML = `
        <script data-lwt-expression-config type="application/json">
          {
            "attrs": { "class": "", "data_trans": "", "data_rom": "", "data_code": 1, "data_status": "1", "data_wid": 1 },
            "appendText": {},
            "term": "t",
            "len": 1,
            "hex": "x",
            "showAll": false
          }
        </script>
      `;

      autoInitExpressionInteractables();

      expect(document.querySelector('[data-lwt-expression-config]')).toBeNull();
    });

    it('creates tooltip with term text', () => {
      document.body.innerHTML = `
        <script data-lwt-expression-config type="application/json">
          {
            "attrs": { "class": "status2", "data_trans": "trans", "data_rom": "rom", "data_code": 2, "data_status": "2", "data_wid": 1 },
            "appendText": {},
            "term": "the expression",
            "len": 2,
            "hex": "xy",
            "showAll": false
          }
        </script>
      `;

      autoInitExpressionInteractables();

      expect(make_tooltip).toHaveBeenCalledWith(
        'the expression',
        'trans',
        'rom',
        2
      );
    });

    it('handles invalid expression JSON gracefully', () => {
      const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      document.body.innerHTML = `
        <script data-lwt-expression-config type="application/json">
          {invalid}
        </script>
      `;

      autoInitExpressionInteractables();

      expect(errorSpy).toHaveBeenCalledWith(
        'Failed to parse expression config:',
        expect.any(Error)
      );
    });
  });

  // ===========================================================================
  // Multiple Config Elements Tests
  // ===========================================================================

  describe('multiple config elements', () => {
    it('processes multiple multi-word configs', () => {
      document.body.innerHTML = `
        <script data-lwt-multiword-config type="application/json">
          { "attrs": { "class": "", "data_trans": "", "data_rom": "", "data_code": 1, "data_status": "1", "data_wid": 1 }, "multiWords": { "1": { "0": "first" } }, "hex": "a", "showAll": false }
        </script>
        <script data-lwt-multiword-config type="application/json">
          { "attrs": { "class": "", "data_trans": "", "data_rom": "", "data_code": 2, "data_status": "2", "data_wid": 2 }, "multiWords": { "1": { "0": "second" } }, "hex": "b", "showAll": false }
        </script>
      `;

      autoInitExpressionInteractables();

      expect(newExpressionInteractable).toHaveBeenCalledTimes(2);
    });

    it('processes both multi-word and expression configs', () => {
      document.body.innerHTML = `
        <script data-lwt-multiword-config type="application/json">
          { "attrs": { "class": "", "data_trans": "", "data_rom": "", "data_code": 1, "data_status": "1", "data_wid": 1 }, "multiWords": { "1": { "0": "multi" } }, "hex": "a", "showAll": false }
        </script>
        <script data-lwt-expression-config type="application/json">
          { "attrs": { "class": "", "data_trans": "", "data_rom": "", "data_code": 1, "data_status": "1", "data_wid": 2 }, "appendText": {}, "term": "expr", "len": 1, "hex": "b", "showAll": false }
        </script>
      `;

      autoInitExpressionInteractables();

      expect(newExpressionInteractable).toHaveBeenCalledTimes(2);
    });
  });

  // ===========================================================================
  // Edge Cases Tests
  // ===========================================================================

  describe('edge cases', () => {
    it('handles empty config', () => {
      document.body.innerHTML = `
        <script data-lwt-multiword-config type="application/json">{}</script>
      `;

      expect(() => autoInitExpressionInteractables()).not.toThrow();
    });

    it('handles no config elements', () => {
      document.body.innerHTML = '<div>No configs here</div>';

      expect(() => autoInitExpressionInteractables()).not.toThrow();
      expect(newExpressionInteractable).not.toHaveBeenCalled();
    });

    it('builds attribute string from attrs object', () => {
      document.body.innerHTML = `
        <script data-lwt-expression-config type="application/json">
          {
            "attrs": {
              "class": "myclass",
              "data_trans": "translation",
              "data_rom": "",
              "data_code": 5,
              "data_status": "5",
              "data_wid": 999
            },
            "appendText": {},
            "term": "word",
            "len": 1,
            "hex": "abc",
            "showAll": false
          }
        </script>
      `;

      autoInitExpressionInteractables();

      expect(newExpressionInteractable).toHaveBeenCalledWith(
        expect.anything(),
        expect.stringContaining('data_wid="999"'),
        expect.anything(),
        expect.anything(),
        expect.anything()
      );
    });

    it('handles missing multiWords entry for text ID', () => {
      document.body.innerHTML = `
        <script data-lwt-multiword-config type="application/json">
          {
            "attrs": { "class": "", "data_trans": "", "data_rom": "", "data_code": 1, "data_status": "1", "data_wid": 1 },
            "multiWords": { "999": { "0": "wrong text id" } },
            "hex": "a",
            "showAll": false
          }
        </script>
      `;

      // Should not throw even with missing entry
      expect(() => autoInitExpressionInteractables()).not.toThrow();
    });
  });

  // ===========================================================================
  // Window Export Tests
  // ===========================================================================

  describe('window exports', () => {
    it('exports autoInitExpressionInteractables to window', async () => {
      await import('../../../src/frontend/js/modules/vocabulary/pages/expression_interactable');

      expect((window as unknown as Record<string, unknown>).autoInitExpressionInteractables).toBeDefined();
    });
  });
});

/**
 * Tests for feed_form_component.ts - Feed form Alpine component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { feedFormData, FeedFormConfig } from '../../../src/frontend/js/modules/feed/components/feed_form_component';

describe('feed_form_component.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // feedFormData Factory Function Tests
  // ===========================================================================

  describe('feedFormData', () => {
    it('creates component with default values', () => {
      const component = feedFormData();

      expect(component.editText).toBe(true); // Default to true
      expect(component.autoUpdate).toBe(false);
      expect(component.maxLinks).toBe(false);
      expect(component.charset).toBe(false);
      expect(component.maxTexts).toBe(false);
      expect(component.tag).toBe(false);
      expect(component.articleSource).toBe(false);
    });

    it('creates component with provided config values', () => {
      const config: FeedFormConfig = {
        editText: false,
        autoUpdate: true,
        autoUpdateValue: '24',
        autoUpdateUnit: 'd',
        maxLinks: true,
        maxLinksValue: '50',
        charset: true,
        charsetValue: 'ISO-8859-1',
        maxTexts: true,
        maxTextsValue: '10',
        tag: true,
        tagValue: 'news',
        articleSource: true,
        articleSourceValue: 'content'
      };

      const component = feedFormData(config);

      expect(component.editText).toBe(false);
      expect(component.autoUpdate).toBe(true);
      expect(component.autoUpdateValue).toBe('24');
      expect(component.autoUpdateUnit).toBe('d');
      expect(component.maxLinks).toBe(true);
      expect(component.maxLinksValue).toBe('50');
      expect(component.charset).toBe(true);
      expect(component.charsetValue).toBe('ISO-8859-1');
      expect(component.maxTexts).toBe(true);
      expect(component.maxTextsValue).toBe('10');
      expect(component.tag).toBe(true);
      expect(component.tagValue).toBe('news');
      expect(component.articleSource).toBe(true);
      expect(component.articleSourceValue).toBe('content');
    });

    it('uses default autoUpdateUnit value of h', () => {
      const component = feedFormData();

      expect(component.autoUpdateUnit).toBe('h');
    });

    it('allows partial config', () => {
      const config: FeedFormConfig = {
        autoUpdate: true,
        autoUpdateValue: '12'
      };

      const component = feedFormData(config);

      expect(component.editText).toBe(true); // Default
      expect(component.autoUpdate).toBe(true);
      expect(component.autoUpdateValue).toBe('12');
      expect(component.autoUpdateUnit).toBe('h'); // Default
    });
  });

  // ===========================================================================
  // init() Method Tests
  // ===========================================================================

  describe('init()', () => {
    it('reads config from JSON script tag', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-form-config">
          {"editText": false, "autoUpdate": true, "autoUpdateValue": "48", "autoUpdateUnit": "w"}
        </script>
      `;

      const component = feedFormData();
      component.init();

      expect(component.editText).toBe(false);
      expect(component.autoUpdate).toBe(true);
      expect(component.autoUpdateValue).toBe('48');
      expect(component.autoUpdateUnit).toBe('w');
    });

    it('keeps defaults if no JSON config element exists', () => {
      const component = feedFormData();
      component.init();

      expect(component.editText).toBe(true);
      expect(component.autoUpdate).toBe(false);
    });

    it('handles invalid JSON gracefully', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-form-config">
          {invalid json}
        </script>
      `;

      const component = feedFormData();

      expect(() => component.init()).not.toThrow();
      expect(component.editText).toBe(true); // Default value
    });

    it('handles empty JSON config', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-form-config">
          {}
        </script>
      `;

      const component = feedFormData();
      component.init();

      // Should keep defaults
      expect(component.editText).toBe(true);
      expect(component.autoUpdate).toBe(false);
    });
  });

  // ===========================================================================
  // serializeOptions() Method Tests
  // ===========================================================================

  describe('serializeOptions()', () => {
    it('returns empty string with trailing comma for no options', () => {
      const component = feedFormData({
        editText: false,
        autoUpdate: false,
        maxLinks: false,
        charset: false,
        maxTexts: false,
        tag: false,
        articleSource: false
      });

      const result = component.serializeOptions();
      expect(result).toBe('');
    });

    it('includes edit_text=1 when editText is true', () => {
      const component = feedFormData({ editText: true });

      const result = component.serializeOptions();
      expect(result).toContain('edit_text=1');
    });

    it('does not include edit_text when false', () => {
      const component = feedFormData({ editText: false });

      const result = component.serializeOptions();
      expect(result).not.toContain('edit_text');
    });

    it('includes autoupdate with unit', () => {
      const component = feedFormData({
        editText: false,
        autoUpdate: true,
        autoUpdateValue: '24',
        autoUpdateUnit: 'h'
      });

      const result = component.serializeOptions();
      expect(result).toContain('autoupdate=24h');
    });

    it('uses different autoupdate units', () => {
      const componentD = feedFormData({
        editText: false,
        autoUpdate: true,
        autoUpdateValue: '7',
        autoUpdateUnit: 'd'
      });

      const componentW = feedFormData({
        editText: false,
        autoUpdate: true,
        autoUpdateValue: '2',
        autoUpdateUnit: 'w'
      });

      expect(componentD.serializeOptions()).toContain('autoupdate=7d');
      expect(componentW.serializeOptions()).toContain('autoupdate=2w');
    });

    it('does not include autoupdate when unchecked', () => {
      const component = feedFormData({
        editText: false,
        autoUpdate: false,
        autoUpdateValue: '24',
        autoUpdateUnit: 'h'
      });

      const result = component.serializeOptions();
      expect(result).not.toContain('autoupdate');
    });

    it('does not include autoupdate when value is empty', () => {
      const component = feedFormData({
        editText: false,
        autoUpdate: true,
        autoUpdateValue: '',
        autoUpdateUnit: 'h'
      });

      const result = component.serializeOptions();
      expect(result).not.toContain('autoupdate');
    });

    it('includes max_links when checked with value', () => {
      const component = feedFormData({
        editText: false,
        maxLinks: true,
        maxLinksValue: '100'
      });

      const result = component.serializeOptions();
      expect(result).toContain('max_links=100');
    });

    it('includes charset when checked with value', () => {
      const component = feedFormData({
        editText: false,
        charset: true,
        charsetValue: 'UTF-8'
      });

      const result = component.serializeOptions();
      expect(result).toContain('charset=UTF-8');
    });

    it('includes max_texts when checked with value', () => {
      const component = feedFormData({
        editText: false,
        maxTexts: true,
        maxTextsValue: '15'
      });

      const result = component.serializeOptions();
      expect(result).toContain('max_texts=15');
    });

    it('includes tag when checked with value', () => {
      const component = feedFormData({
        editText: false,
        tag: true,
        tagValue: 'news-tag'
      });

      const result = component.serializeOptions();
      expect(result).toContain('tag=news-tag');
    });

    it('includes article_source when checked with value', () => {
      const component = feedFormData({
        editText: false,
        articleSource: true,
        articleSourceValue: 'description'
      });

      const result = component.serializeOptions();
      expect(result).toContain('article_source=description');
    });

    it('serializes multiple options correctly', () => {
      const component = feedFormData({
        editText: true,
        autoUpdate: true,
        autoUpdateValue: '12',
        autoUpdateUnit: 'h',
        maxLinks: true,
        maxLinksValue: '50',
        tag: true,
        tagValue: 'imported'
      });

      const result = component.serializeOptions();
      expect(result).toContain('edit_text=1');
      expect(result).toContain('autoupdate=12h');
      expect(result).toContain('max_links=50');
      expect(result).toContain('tag=imported');
    });

    it('skips options when toggled on but value is empty', () => {
      const component = feedFormData({
        editText: false,
        maxLinks: true,
        maxLinksValue: '' // Empty value
      });

      const result = component.serializeOptions();
      expect(result).not.toContain('max_links');
    });

    it('ends with comma when options are present', () => {
      const component = feedFormData({ editText: true });

      const result = component.serializeOptions();
      expect(result.endsWith(',')).toBe(true);
    });
  });

  // ===========================================================================
  // handleSubmit() Method Tests
  // ===========================================================================

  describe('handleSubmit()', () => {
    it('populates NfOptions hidden field on submit', () => {
      document.body.innerHTML = `
        <form>
          <input type="hidden" name="NfOptions" value="" />
        </form>
      `;

      const component = feedFormData({
        editText: true,
        maxLinks: true,
        maxLinksValue: '75'
      });

      const form = document.querySelector('form')!;
      const event = { target: form } as unknown as Event;
      component.handleSubmit(event);

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      expect(nfOptions.value).toContain('edit_text=1');
      expect(nfOptions.value).toContain('max_links=75');
    });

    it('handles missing NfOptions field gracefully', () => {
      document.body.innerHTML = `
        <form></form>
      `;

      const component = feedFormData({ editText: true });
      const form = document.querySelector('form')!;
      const event = { target: form } as unknown as Event;

      expect(() => component.handleSubmit(event)).not.toThrow();
    });

    it('clears previous NfOptions value', () => {
      document.body.innerHTML = `
        <form>
          <input type="hidden" name="NfOptions" value="old_value" />
        </form>
      `;

      const component = feedFormData({
        editText: false,
        autoUpdate: false,
        maxLinks: false,
        charset: false,
        maxTexts: false,
        tag: false,
        articleSource: false
      });

      const form = document.querySelector('form')!;
      const event = { target: form } as unknown as Event;
      component.handleSubmit(event);

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      expect(nfOptions.value).toBe('');
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('full workflow: init from config, modify, serialize', () => {
      document.body.innerHTML = `
        <script type="application/json" id="feed-form-config">
          {"editText": true, "autoUpdate": true, "autoUpdateValue": "24", "autoUpdateUnit": "h"}
        </script>
        <form>
          <input type="hidden" name="NfOptions" value="" />
        </form>
      `;

      const component = feedFormData();
      component.init();

      // Verify initial state from config
      expect(component.editText).toBe(true);
      expect(component.autoUpdate).toBe(true);

      // Simulate user modifying values
      component.maxLinks = true;
      component.maxLinksValue = '200';

      // Submit
      const form = document.querySelector('form')!;
      component.handleSubmit({ target: form } as unknown as Event);

      const nfOptions = document.querySelector<HTMLInputElement>('[name="NfOptions"]')!;
      expect(nfOptions.value).toContain('edit_text=1');
      expect(nfOptions.value).toContain('autoupdate=24h');
      expect(nfOptions.value).toContain('max_links=200');
    });
  });
});

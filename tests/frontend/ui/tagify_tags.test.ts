/**
 * Tests for tagify_tags.ts - Wrapper for @yaireo/tagify
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  initTagify,
  initTermTags,
  initTextTags,
  getTagifyInstance,
  setupTagChangeTracking
} from '../../../src/frontend/js/ui/tagify_tags';

// Mock Tagify module
vi.mock('@yaireo/tagify', () => {
  const MockTagify = vi.fn().mockImplementation(function(this: any, _input: HTMLInputElement, _options: any) {
    this.addTags = vi.fn();
    this.removeTags = vi.fn();
    this.on = vi.fn().mockReturnThis();
    this.off = vi.fn().mockReturnThis();
    this.DOM = { input: _input };
    return this;
  });
  return { default: MockTagify };
});

// Mock CSS import
vi.mock('@yaireo/tagify/dist/tagify.css', () => ({}));

// Mock form_validation
vi.mock('../../../src/frontend/js/forms/form_validation', () => ({
  containsCharacterOutsideBasicMultilingualPlane: vi.fn().mockReturnValue(false)
}));

import Tagify from '@yaireo/tagify';
import { containsCharacterOutsideBasicMultilingualPlane } from '../../../src/frontend/js/forms/form_validation';

describe('tagify_tags.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // initTagify Tests
  // ===========================================================================

  describe('initTagify', () => {
    it('returns null when element does not exist', () => {
      const result = initTagify('#nonexistent', { url: '/save' });

      expect(result).toBeNull();
    });

    it('extracts existing tags from LI elements', () => {
      document.body.innerHTML = `
        <ul id="mytags">
          <li>tag1</li>
          <li>tag2</li>
          <li>tag3</li>
        </ul>
      `;

      const tagify = initTagify('#mytags');

      expect(Tagify).toHaveBeenCalled();
      const mockInstance = (Tagify as any).mock.results[0].value;
      expect(mockInstance.addTags).toHaveBeenCalledWith(['tag1', 'tag2', 'tag3']);
    });

    it('replaces UL with input element', () => {
      document.body.innerHTML = `
        <ul id="mytags" class="tag-list">
          <li>tag1</li>
        </ul>
      `;

      initTagify('#mytags');

      expect(document.querySelector('ul#mytags')).toBeNull();
      const input = document.querySelector('input#mytags');
      expect(input).not.toBeNull();
      expect(input!.className).toBe('tag-list');
    });

    it('preserves element ID and class', () => {
      document.body.innerHTML = `
        <ul id="special-tags" class="custom-class">
          <li>tag</li>
        </ul>
      `;

      initTagify('#special-tags');

      const input = document.querySelector('input#special-tags') as HTMLInputElement;
      expect(input).not.toBeNull();
      expect(input.id).toBe('special-tags');
      expect(input.className).toBe('custom-class');
    });

    it('sets field name when provided', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      initTagify('#mytags', { fieldName: 'Tags[List][]' });

      const input = document.querySelector('input#mytags') as HTMLInputElement;
      expect(input.name).toBe('Tags[List][]');
    });

    it('initializes Tagify with whitelist', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      initTagify('#mytags', { whitelist: ['option1', 'option2', 'option3'] });

      expect(Tagify).toHaveBeenCalledWith(
        expect.any(HTMLInputElement),
        expect.objectContaining({
          whitelist: ['option1', 'option2', 'option3']
        })
      );
    });

    it('calls onAdd callback when tag is added', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      const onAdd = vi.fn();
      initTagify('#mytags', { onAdd });

      const mockInstance = (Tagify as any).mock.results[0].value;

      // Simulate the 'on' call
      expect(mockInstance.on).toHaveBeenCalledWith('add', expect.any(Function));
    });

    it('calls onRemove callback when tag is removed', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      const onRemove = vi.fn();
      initTagify('#mytags', { onRemove });

      const mockInstance = (Tagify as any).mock.results[0].value;

      expect(mockInstance.on).toHaveBeenCalledWith('remove', expect.any(Function));
    });

    it('stores instance for later access', () => {
      document.body.innerHTML = '<ul id="stored-tags"></ul>';

      const tagify = initTagify('#stored-tags');

      const retrieved = getTagifyInstance('stored-tags');
      expect(retrieved).toBe(tagify);
    });

    it('handles empty existing tags', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      const tagify = initTagify('#mytags');

      expect(tagify).not.toBeNull();
      const mockInstance = (Tagify as any).mock.results[0].value;
      expect(mockInstance.addTags).not.toHaveBeenCalled();
    });

    it('trims whitespace from tag text', () => {
      document.body.innerHTML = `
        <ul id="mytags">
          <li>  spaced  </li>
          <li>normal</li>
        </ul>
      `;

      initTagify('#mytags');

      const mockInstance = (Tagify as any).mock.results[0].value;
      expect(mockInstance.addTags).toHaveBeenCalledWith(['spaced', 'normal']);
    });

    it('filters out empty tag text', () => {
      document.body.innerHTML = `
        <ul id="mytags">
          <li>valid</li>
          <li></li>
          <li>  </li>
          <li>another</li>
        </ul>
      `;

      initTagify('#mytags');

      const mockInstance = (Tagify as any).mock.results[0].value;
      // Empty and whitespace-only tags should be filtered out
      expect(mockInstance.addTags).toHaveBeenCalledWith(['valid', 'another']);
    });

    it('returns Tagify instance', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      const result = initTagify('#mytags');

      expect(result).not.toBeNull();
      expect(result).toBe((Tagify as any).mock.results[0].value);
    });
  });

  // ===========================================================================
  // initTermTags Tests
  // ===========================================================================

  describe('initTermTags', () => {
    it('initializes tagify on #termtags element', () => {
      document.body.innerHTML = '<ul id="termtags"></ul>';

      initTermTags();

      expect(Tagify).toHaveBeenCalled();
    });

    it('sets correct field name for term tags', () => {
      document.body.innerHTML = '<ul id="termtags"></ul>';

      initTermTags();

      const input = document.querySelector('input#termtags') as HTMLInputElement;
      expect(input.name).toBe('TermTags[TagList][]');
    });

    it('passes whitelist to Tagify', () => {
      document.body.innerHTML = '<ul id="termtags"></ul>';

      initTermTags(['grammar', 'vocabulary', 'idiom']);

      expect(Tagify).toHaveBeenCalledWith(
        expect.any(HTMLInputElement),
        expect.objectContaining({
          whitelist: ['grammar', 'vocabulary', 'idiom']
        })
      );
    });

    it('returns null when element does not exist', () => {
      const result = initTermTags();

      expect(result).toBeNull();
    });

    it('passes onAdd callback', () => {
      document.body.innerHTML = '<ul id="termtags"></ul>';

      const onAdd = vi.fn();
      initTermTags([], onAdd);

      const mockInstance = (Tagify as any).mock.results[0].value;
      expect(mockInstance.on).toHaveBeenCalledWith('add', expect.any(Function));
    });

    it('passes onRemove callback', () => {
      document.body.innerHTML = '<ul id="termtags"></ul>';

      const onRemove = vi.fn();
      initTermTags([], undefined, onRemove);

      const mockInstance = (Tagify as any).mock.results[0].value;
      expect(mockInstance.on).toHaveBeenCalledWith('remove', expect.any(Function));
    });
  });

  // ===========================================================================
  // initTextTags Tests
  // ===========================================================================

  describe('initTextTags', () => {
    it('initializes tagify on #texttags element', () => {
      document.body.innerHTML = '<ul id="texttags"></ul>';

      initTextTags();

      expect(Tagify).toHaveBeenCalled();
    });

    it('sets correct field name for text tags', () => {
      document.body.innerHTML = '<ul id="texttags"></ul>';

      initTextTags();

      const input = document.querySelector('input#texttags') as HTMLInputElement;
      expect(input.name).toBe('TextTags[TagList][]');
    });

    it('passes whitelist to Tagify', () => {
      document.body.innerHTML = '<ul id="texttags"></ul>';

      initTextTags(['news', 'fiction', 'podcast']);

      expect(Tagify).toHaveBeenCalledWith(
        expect.any(HTMLInputElement),
        expect.objectContaining({
          whitelist: ['news', 'fiction', 'podcast']
        })
      );
    });

    it('returns null when element does not exist', () => {
      const result = initTextTags();

      expect(result).toBeNull();
    });
  });

  // ===========================================================================
  // getTagifyInstance Tests
  // ===========================================================================

  describe('getTagifyInstance', () => {
    it('returns undefined for non-existent instance', () => {
      const result = getTagifyInstance('nonexistent');

      expect(result).toBeUndefined();
    });

    it('returns stored instance by ID', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      const created = initTagify('#mytags');
      const retrieved = getTagifyInstance('mytags');

      expect(retrieved).toBe(created);
    });
  });

  // ===========================================================================
  // setupTagChangeTracking Tests
  // ===========================================================================

  describe('setupTagChangeTracking', () => {
    it('sets up event listeners on all tagify instances', () => {
      document.body.innerHTML = `
        <ul id="tags1"></ul>
        <ul id="tags2"></ul>
      `;

      initTagify('#tags1');
      initTagify('#tags2');

      const onChange = vi.fn();
      setupTagChangeTracking(onChange);

      // Both instances should have 'add' and 'remove' listeners
      const mockInstances = (Tagify as any).mock.results;
      expect(mockInstances[0].value.on).toHaveBeenCalledWith('add', expect.any(Function));
      expect(mockInstances[0].value.on).toHaveBeenCalledWith('remove', expect.any(Function));
      expect(mockInstances[1].value.on).toHaveBeenCalledWith('add', expect.any(Function));
      expect(mockInstances[1].value.on).toHaveBeenCalledWith('remove', expect.any(Function));
    });

    it('handles empty instances map', () => {
      const onChange = vi.fn();

      expect(() => setupTagChangeTracking(onChange)).not.toThrow();
    });
  });

  // ===========================================================================
  // Tagify Configuration Tests
  // ===========================================================================

  describe('Tagify configuration', () => {
    it('configures dropdown settings', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      initTagify('#mytags');

      expect(Tagify).toHaveBeenCalledWith(
        expect.any(HTMLInputElement),
        expect.objectContaining({
          dropdown: expect.objectContaining({
            enabled: 1,
            maxItems: 20,
            closeOnSelect: true,
            highlightFirst: true
          })
        })
      );
    });

    it('disables duplicates', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      initTagify('#mytags');

      expect(Tagify).toHaveBeenCalledWith(
        expect.any(HTMLInputElement),
        expect.objectContaining({
          duplicates: false
        })
      );
    });

    it('configures transformTag for BMP validation', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      initTagify('#mytags');

      expect(Tagify).toHaveBeenCalledWith(
        expect.any(HTMLInputElement),
        expect.objectContaining({
          transformTag: expect.any(Function)
        })
      );
    });

    it('validates tags against BMP characters', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      // Mock containsCharacterOutsideBasicMultilingualPlane to return true
      (containsCharacterOutsideBasicMultilingualPlane as any).mockReturnValue(true);

      initTagify('#mytags');

      // Get the transformTag function that was passed to Tagify
      const callArgs = (Tagify as any).mock.calls[0][1];
      const transformTag = callArgs.transformTag;

      // Test that invalid tags are cleared
      const tagData = { value: 'emoji🦄tag' };
      transformTag(tagData);

      expect(tagData.value).toBe('');
    });

    it('allows valid BMP tags', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      // Mock containsCharacterOutsideBasicMultilingualPlane to return false
      (containsCharacterOutsideBasicMultilingualPlane as any).mockReturnValue(false);

      initTagify('#mytags');

      const callArgs = (Tagify as any).mock.calls[0][1];
      const transformTag = callArgs.transformTag;

      const tagData = { value: 'validtag' };
      transformTag(tagData);

      expect(tagData.value).toBe('validtag');
    });

    it('configures originalInputValueFormat for comma separation', () => {
      document.body.innerHTML = '<ul id="mytags"></ul>';

      initTagify('#mytags');

      const callArgs = (Tagify as any).mock.calls[0][1];
      const format = callArgs.originalInputValueFormat;

      const result = format([
        { value: 'tag1' },
        { value: 'tag2' },
        { value: 'tag3' }
      ]);

      expect(result).toBe('tag1,tag2,tag3');
    });
  });
});

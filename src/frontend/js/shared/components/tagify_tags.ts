/**
 * Tagify Tags - Wrapper for @yaireo/tagify to replace jQuery tag-it
 *
 * This module initializes Tagify on tag input elements, supporting the
 * existing HTML structure (UL with LI elements) by transforming them
 * into Tagify-compatible inputs.
 *
 * @license unlicense
 * @since 3.0.0
 */

import Tagify, { TagData } from '@yaireo/tagify';
import '@yaireo/tagify/dist/tagify.css';

import { containsCharacterOutsideBasicMultilingualPlane } from '../forms/form_validation';

export interface TagifyInitOptions {
  /** Whitelist of available tags for autocomplete */
  whitelist?: string[];
  /** Form field name for the tags (e.g., 'TermTags[TagList][]') */
  fieldName?: string;
  /** Callback when a tag is added */
  onAdd?: (tagData: TagData) => void;
  /** Callback when a tag is removed */
  onRemove?: (tagData: TagData) => void;
}

// Store Tagify instances for external access (e.g., form dirty checking)
const tagifyInstances: Map<string, Tagify> = new Map();

/**
 * Get a Tagify instance by element ID
 */
export function getTagifyInstance(elementId: string): Tagify | undefined {
  return tagifyInstances.get(elementId);
}

/**
 * Initialize Tagify on a UL element (legacy tag-it format)
 *
 * Transforms a <ul id="..."><li>tag1</li><li>tag2</li></ul> structure
 * into a Tagify input while preserving the tag values.
 *
 * @param selector - CSS selector for the UL element
 * @param options - Tagify configuration options
 * @returns The Tagify instance, or null if element not found
 */
export function initTagify(
  selector: string,
  options: TagifyInitOptions = {}
): Tagify | null {
  const ulElement = document.querySelector<HTMLUListElement>(selector);
  if (!ulElement) {
    return null;
  }

  // Extract existing tags from LI elements
  const existingTags: string[] = [];
  ulElement.querySelectorAll('li').forEach((li) => {
    const text = li.textContent?.trim();
    if (text) {
      existingTags.push(text);
    }
  });

  // Create input element to replace the UL
  const input = document.createElement('input');
  input.type = 'text';
  input.id = ulElement.id;
  input.className = ulElement.className;
  input.value = existingTags.join(', ');

  // Set the form field name if provided
  if (options.fieldName) {
    input.name = options.fieldName;
  }

  // Replace UL with input
  ulElement.replaceWith(input);

  // Initialize Tagify
  const tagify = new Tagify(input, {
    whitelist: options.whitelist || [],
    dropdown: {
      enabled: 1, // Show suggestions after 1 character
      maxItems: 20,
      closeOnSelect: true,
      highlightFirst: true
    },
    // Validate tags - reject characters outside BMP
    transformTag: (tagData: TagData) => {
      if (containsCharacterOutsideBasicMultilingualPlane(tagData.value)) {
        tagData.value = ''; // Clear invalid tags
      }
    },
    // Allow duplicates to be rejected
    duplicates: false,
    // Original input value format - comma separated for form submission
    originalInputValueFormat: (valuesArr: TagData[]) =>
      valuesArr.map((item) => item.value).join(',')
  });

  // Add existing tags
  if (existingTags.length > 0) {
    tagify.addTags(existingTags);
  }

  // Set up event callbacks
  if (options.onAdd) {
    tagify.on('add', (e) => {
      if (e.detail.data) {
        options.onAdd!(e.detail.data);
      }
    });
  }

  if (options.onRemove) {
    tagify.on('remove', (e) => {
      if (e.detail.data) {
        options.onRemove!(e.detail.data);
      }
    });
  }

  // Store instance for later access
  tagifyInstances.set(input.id, tagify);

  return tagify;
}

/**
 * Initialize term tags element (#termtags)
 *
 * @param whitelist - Available tags for autocomplete
 * @param onAdd - Callback when tag is added
 * @param onRemove - Callback when tag is removed
 */
export function initTermTags(
  whitelist: string[] = [],
  onAdd?: (tagData: TagData) => void,
  onRemove?: (tagData: TagData) => void
): Tagify | null {
  return initTagify('#termtags', {
    whitelist,
    fieldName: 'TermTags[TagList][]',
    onAdd,
    onRemove
  });
}

/**
 * Initialize text tags element (#texttags)
 *
 * @param whitelist - Available tags for autocomplete
 * @param onAdd - Callback when tag is added
 * @param onRemove - Callback when tag is removed
 */
export function initTextTags(
  whitelist: string[] = [],
  onAdd?: (tagData: TagData) => void,
  onRemove?: (tagData: TagData) => void
): Tagify | null {
  return initTagify('#texttags', {
    whitelist,
    fieldName: 'TextTags[TagList][]',
    onAdd,
    onRemove
  });
}

/**
 * Set up change tracking on Tagify instances for form dirty checking
 *
 * @param onTagChange - Callback when any tag is added or removed
 */
export function setupTagChangeTracking(
  onTagChange: (duringInit: boolean) => void
): void {
  tagifyInstances.forEach((tagify) => {
    let initialized = false;

    // Track when initialization is complete
    setTimeout(() => {
      initialized = true;
    }, 100);

    tagify.on('add', () => {
      onTagChange(!initialized);
    });

    tagify.on('remove', () => {
      onTagChange(!initialized);
    });
  });
}

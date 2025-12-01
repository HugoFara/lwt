/**
 * Feed Text Edit - Bulk feed text import form functionality.
 *
 * Handles:
 * - Scrolling to the first table on page load
 * - Initializing Tagify on feed tag inputs
 * - Checkbox changes to enable/disable feed form fields
 *
 * @license unlicense
 * @since   3.0.0
 */

import Tagify from '@yaireo/tagify';
import { fetchTextTags, getTextTagsSync } from '../core/app_data';

// Extend HTMLInputElement to include the _tagify property
declare global {
  interface HTMLInputElement {
    _tagify?: Tagify;
  }
}

/**
 * Initialize Tagify on a single UL element, converting it to a Tagify input.
 *
 * @param ulElement - The UL element containing tag LI items
 */
function initTagifyOnFeedInput(ulElement: HTMLUListElement): void {
  const fieldName = ulElement.getAttribute('name');
  if (!fieldName) return;

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
  input.name = fieldName;
  input.className = 'tagify-feed-input';
  input.value = existingTags.join(', ');

  // Extract feed index from name like "feed[0][TxTags]"
  const match = fieldName.match(/feed\[(\d+)\]/);
  input.dataset.feedIndex = match ? match[1] : '';

  // Replace UL with input
  ulElement.replaceWith(input);

  // Initialize Tagify with currently cached text tags
  const tagify = new Tagify(input, {
    whitelist: getTextTagsSync(),
    dropdown: {
      enabled: 1,
      maxItems: 20,
      closeOnSelect: true,
      highlightFirst: true
    },
    duplicates: false
  });

  // Add existing tags
  if (existingTags.length > 0) {
    tagify.addTags(existingTags);
  }

  // Store tagify instance on the input for later access
  input._tagify = tagify;
}

/**
 * Handle checkbox change to enable/disable the associated feed form fields.
 */
function handleFeedCheckboxChange(checkbox: HTMLInputElement): void {
  const feedIndex = checkbox.value;
  const feedFields = document.querySelectorAll<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>(
    `[name^="feed[${feedIndex}]"]`
  );
  const tagifyInput = document.querySelector<HTMLInputElement>(
    '.tagify-feed-input[data-feed-index="' + feedIndex + '"]'
  );
  const tagify = tagifyInput?._tagify;

  if (checkbox.checked) {
    feedFields.forEach(el => {
      el.disabled = false;
      // Add notempty class to title and text fields
      if (el.name.includes('[TxTitle]') || el.name.includes('[TxText]')) {
        el.classList.add('notempty');
      }
    });
    if (tagify) {
      tagify.setDisabled(false);
    }
  } else {
    feedFields.forEach(el => {
      el.disabled = true;
      el.classList.remove('notempty');
    });
    if (tagify) {
      tagify.setDisabled(true);
    }
  }
}

/**
 * Initialize the feed text edit form.
 * Should only be called on pages with the bulk feed text import form.
 */
export async function initFeedTextEditForm(): Promise<void> {
  // Scroll to the first table
  const firstTable = document.querySelector('table');
  if (firstTable) {
    firstTable.scrollIntoView({ behavior: 'instant', block: 'start' });
  }

  // Prefetch text tags before initializing Tagify inputs
  await fetchTextTags();

  // Initialize Tagify on all feed tag UL elements
  document.querySelectorAll<HTMLUListElement>('ul[name^="feed"]').forEach(initTagifyOnFeedInput);

  // Handle checkbox changes for enabling/disabling feed forms
  document.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')
    .forEach(checkbox => {
      checkbox.addEventListener('change', () => handleFeedCheckboxChange(checkbox));
    });
}

/**
 * Auto-initialize on DOM ready if on the feed text edit page.
 * Detected by presence of checked_feeds_save hidden field.
 */
document.addEventListener('DOMContentLoaded', () => {
  if (document.querySelector('input[name="checked_feeds_save"]')) {
    initFeedTextEditForm();
  }
});

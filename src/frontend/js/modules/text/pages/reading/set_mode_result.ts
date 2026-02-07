/**
 * Set Mode Result - Handles annotation visibility toggling.
 *
 * This module manages the display of word annotations in the reading view,
 * allowing users to show/hide translations for learning words.
 *
 * @license unlicense
 * @since   3.0.0
 */

import { onDomReady } from '@shared/utils/dom_ready';

/**
 * Get the container element from a context parameter.
 *
 * @param context - The DOM context (window, document, or element)
 * @returns The container element to query within
 */
function getContainer(context: Window | Document | HTMLElement): Document | HTMLElement {
  if (context === window || context instanceof Window) {
    return document;
  }
  if (context instanceof Document) {
    return context;
  }
  return context as HTMLElement;
}

/**
 * Hide annotations for multi-word terms.
 * Converts display from word text to word code (abbreviated form).
 *
 * @param context - The DOM context (window or element) containing words
 */
export function hideAnnotations(context: Window | Document | HTMLElement = window): void {
  const container = getContainer(context);

  container.querySelectorAll<HTMLElement>('.mword').forEach(el => {
    el.classList.remove('wsty');
    el.classList.add('mwsty');
    const code = '&nbsp;' + el.getAttribute('data_code') + '&nbsp;';
    el.innerHTML = code;
  });

  container.querySelectorAll<HTMLElement>('span:not(#totalcharcount)').forEach(el => {
    el.classList.remove('hide');
  });
}

/**
 * Show annotations for multi-word terms.
 * Displays the full word text with translations.
 *
 * @param context - The DOM context (window or element) containing words
 */
export function showAnnotations(context: Window | Document | HTMLElement = window): void {
  const container = getContainer(context);

  container.querySelectorAll<HTMLElement>('.mword').forEach(el => {
    el.classList.remove('mwsty');
    el.classList.add('wsty');
    const text = el.getAttribute('data_text');
    el.textContent = text || '';

    if (!el.classList.contains('hide')) {
      const code = parseInt(el.getAttribute('data_code') || '0', 10);
      const order = parseInt(el.getAttribute('data_order') || '0', 10);
      const u = code * 2 + order - 1;

      // Find and hide siblings until the matching ID element
      let sibling = el.nextElementSibling;
      while (sibling) {
        if (sibling.id && sibling.id.startsWith('ID-' + u + '-')) {
          break;
        }
        if (sibling instanceof HTMLElement) {
          sibling.classList.add('hide');
        }
        sibling = sibling.nextElementSibling;
      }
    }
  });
}

interface SetModeConfig {
  showLearningChanged: boolean;
  showLearning: boolean;
}

/**
 * Initialize the set mode result page.
 * Reads configuration from JSON and updates the waiting indicator.
 */
export function initSetModeResult(): void {
  const configEl = document.getElementById('set-mode-config');
  if (!configEl) {
    return;
  }

  try {
    const config: SetModeConfig = JSON.parse(configEl.textContent || '{}');

    // Update waiting indicator
    const waitingEl = document.getElementById('waiting');
    if (waitingEl) {
      waitingEl.innerHTML = '<b>OK -- </b>';
    }

    // Export functions for potential use by parent frames
    (window as unknown as Record<string, unknown>).hideAnnotations = hideAnnotations;
    (window as unknown as Record<string, unknown>).showAnnotations = showAnnotations;

    // If showLearning changed, we may need to update the parent frame
    if (config.showLearningChanged) {
      // The parent frame will handle the actual update
      console.log('Learning translations mode changed to:', config.showLearning);
    }
  } catch (e) {
    console.error('Failed to parse set mode config:', e);
  }
}

// Auto-initialize on document ready
onDomReady(initSetModeResult);

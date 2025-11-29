/**
 * Set Mode Result - Handles annotation visibility toggling.
 *
 * This module manages the display of word annotations in the reading view,
 * allowing users to show/hide translations for learning words.
 *
 * @license unlicense
 * @since   3.0.0
 */

import $ from 'jquery';

/**
 * Hide annotations for multi-word terms.
 * Converts display from word text to word code (abbreviated form).
 *
 * @param context - The DOM context (window or element) containing words
 */
export function hideAnnotations(context: Window | JQuery | HTMLElement = window): void {
  $('.mword', context as unknown as JQuery.Selector)
    .removeClass('wsty')
    .addClass('mwsty')
    .each(function () {
      const code = '&nbsp;' + $(this).attr('data_code') + '&nbsp;';
      $(this).html(code);
    });
  $('span', context as unknown as JQuery.Selector)
    .not('#totalcharcount')
    .removeClass('hide');
}

/**
 * Show annotations for multi-word terms.
 * Displays the full word text with translations.
 *
 * @param context - The DOM context (window or element) containing words
 */
export function showAnnotations(context: Window | JQuery | HTMLElement = window): void {
  $('.mword', context as unknown as JQuery.Selector)
    .removeClass('mwsty')
    .addClass('wsty')
    .each(function () {
      const text = $(this).attr('data_text');
      $(this).text(text || '');
      if ($(this).not('.hide').length) {
        const code = parseInt($(this).attr('data_code') || '0', 10);
        const order = parseInt($(this).attr('data_order') || '0', 10);
        const u = code * 2 + order - 1;
        $(this)
          .nextUntil('[id^="ID-' + u + '-"]', context as unknown as JQuery.Selector)
          .addClass('hide');
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
    $('#waiting').html('<b>OK -- </b>');

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
$(document).ready(initSetModeResult);

/**
 * Annotation interactions for text display view.
 *
 * Handles click events on annotations and text in the print/display view.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.0.0
 */

import $ from 'jquery';

/**
 * Handle click on an annotation (translation) to toggle visibility.
 * Clicking an annotation hides it (by matching background color),
 * clicking again reveals it.
 */
export function clickAnnotation(this: HTMLElement): void {
  const $el = $(this);
  const attr = $el.attr('style');
  if (attr !== undefined && attr !== '') {
    $el.removeAttr('style');
  } else {
    $el.css('color', '#C8DCF0');
    $el.css('background-color', '#C8DCF0');
  }
}

/**
 * Handle click on text (term) to toggle visibility.
 * Clicking text hides it (by matching background color),
 * clicking again reveals it.
 */
export function clickText(this: HTMLElement): void {
  const $el = $(this);
  const bc = $('body').css('color');
  if ($el.css('color') !== bc) {
    $el.css('color', 'inherit');
    $el.css('background-color', '');
  } else {
    $el.css('color', '#E5E4E2');
    $el.css('background-color', '#E5E4E2');
  }
}

/**
 * Initialize annotation interactions.
 * Binds click handlers to annotation and text elements.
 */
export function initAnnotationInteractions(): void {
  $('.anntransruby2').on('click', clickAnnotation);
  $('.anntermruby').on('click', clickText);
}

/**
 * Auto-initialize if annotation elements exist on the page.
 */
function autoInit(): void {
  if ($('.anntransruby2').length > 0 || $('.anntermruby').length > 0) {
    initAnnotationInteractions();
  }
}

// Initialize on DOM ready
$(document).ready(autoInit);

// Export to window for potential external use
declare global {
  interface Window {
    clickAnnotation: typeof clickAnnotation;
    clickText: typeof clickText;
    initAnnotationInteractions: typeof initAnnotationInteractions;
  }
}

window.clickAnnotation = clickAnnotation;
window.clickText = clickText;
window.initAnnotationInteractions = initAnnotationInteractions;

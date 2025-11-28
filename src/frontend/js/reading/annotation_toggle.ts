/**
 * Annotation Toggle - Show/hide translations and annotations in text display.
 *
 * Extracted from Views/Text/display_header.php
 *
 * @license unlicense
 * @since 3.0.0
 */

import $ from 'jquery';

/**
 * Hide translations (text display).
 * Sets the translation ruby text to match background color.
 */
export function doHideTranslations(): void {
  $('#showt').show();
  $('#hidet').hide();
  $('.anntermruby').css('color', '#E5E4E2').css('background-color', '#E5E4E2');
}

/**
 * Show translations (text display).
 * Restores the translation ruby text to normal visibility.
 */
export function doShowTranslations(): void {
  $('#showt').hide();
  $('#hidet').show();
  $('.anntermruby').css('color', 'inherit').css('background-color', '');
}

/**
 * Hide annotations (text display).
 * Sets the annotation ruby text to match background color.
 */
export function doHideAnnotations(): void {
  $('#show').show();
  $('#hide').hide();
  $('.anntransruby2').css('color', '#C8DCF0').css('background-color', '#C8DCF0');
}

/**
 * Show annotations (text display).
 * Restores the annotation ruby text to normal visibility.
 */
export function doShowAnnotations(): void {
  $('#show').hide();
  $('#hide').show();
  $('.anntransruby2').css('color', '').css('background-color', '');
}

/**
 * Close the current window.
 * Used for the close button in print/display views.
 */
export function closeWindow(): void {
  window.top?.close();
}

/**
 * Initialize annotation toggle buttons.
 * Sets up click handlers using data-action attributes.
 */
export function initAnnotationToggles(): void {
  // Translation toggles
  const hideTransBtn = document.querySelector('[data-action="hide-translations"]');
  const showTransBtn = document.querySelector('[data-action="show-translations"]');

  if (hideTransBtn) {
    hideTransBtn.addEventListener('click', doHideTranslations);
  }
  if (showTransBtn) {
    showTransBtn.addEventListener('click', doShowTranslations);
  }

  // Annotation toggles
  const hideAnnBtn = document.querySelector('[data-action="hide-annotations"]');
  const showAnnBtn = document.querySelector('[data-action="show-annotations"]');

  if (hideAnnBtn) {
    hideAnnBtn.addEventListener('click', doHideAnnotations);
  }
  if (showAnnBtn) {
    showAnnBtn.addEventListener('click', doShowAnnotations);
  }

  // Close window button
  const closeBtn = document.querySelector('[data-action="close-window"]');
  if (closeBtn) {
    closeBtn.addEventListener('click', closeWindow);
  }
}

// Auto-initialize on DOM ready if toggle elements are present
document.addEventListener('DOMContentLoaded', () => {
  // Only initialize if we're on a page with annotation toggles
  if (
    document.getElementById('hidet') ||
    document.getElementById('hide') ||
    document.querySelector('[data-action="hide-translations"]')
  ) {
    initAnnotationToggles();
  }
});

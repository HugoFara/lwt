/**
 * Settings Form Module - Handles settings form interactions
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import $ from 'jquery';
import { lwtFormCheck } from '../forms/unloadformcheck';

/**
 * Initialize settings form event handlers.
 * Sets up form change tracking and navigation buttons.
 */
export function initSettingsForm(): void {
  const form = document.querySelector<HTMLFormElement>('[data-lwt-settings-form]');
  if (!form) {
    return;
  }

  // Set up form change tracking
  lwtFormCheck.askBeforeExit();

  // Handle settings navigation buttons (reset dirty before navigating)
  $(document).on('click', '[data-action="settings-navigate"]', function (this: HTMLElement) {
    const url = this.dataset.url;
    if (url) {
      lwtFormCheck.resetDirty();
      location.href = url;
    }
  });
}

/**
 * Initialize confirm submit forms.
 * Shows a confirmation dialog before form submission.
 */
export function initConfirmSubmitForms(): void {
  $(document).on('submit', 'form[data-action="confirm-submit"]', function (e) {
    const form = e.target as HTMLFormElement;
    const message = form.dataset.confirmMessage || 'Are you sure?';
    if (!confirm(message)) {
      e.preventDefault();
      return false;
    }
    return true;
  });
}

/**
 * Initialize navigation buttons with data-action="navigate".
 * This is a general handler for simple navigation buttons.
 */
export function initNavigateButtons(): void {
  $(document).on('click', '[data-action="navigate"]', function (this: HTMLElement) {
    const url = this.dataset.url;
    if (url) {
      location.href = url;
    }
  });
}

/**
 * Initialize history back buttons with data-action="history-back".
 * This is a general handler for back buttons.
 */
export function initHistoryBackButtons(): void {
  $(document).on('click', '[data-action="history-back"]', function (e) {
    e.preventDefault();
    history.back();
  });
}

// Auto-initialize when DOM is ready
$(document).ready(function () {
  initSettingsForm();
  initConfirmSubmitForms();
  initNavigateButtons();
  initHistoryBackButtons();
});

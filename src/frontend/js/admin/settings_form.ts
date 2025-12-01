/**
 * Settings Form Module - Handles settings form interactions
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

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
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    const button = target.closest<HTMLElement>('[data-action="settings-navigate"]');
    if (button) {
      const url = button.dataset.url;
      if (url) {
        lwtFormCheck.resetDirty();
        location.href = url;
      }
    }
  });
}

/**
 * Initialize confirm submit forms.
 * Shows a confirmation dialog before form submission.
 */
export function initConfirmSubmitForms(): void {
  document.addEventListener('submit', (e) => {
    const form = (e.target as HTMLElement).closest<HTMLFormElement>('form[data-action="confirm-submit"]');
    if (form) {
      const message = form.dataset.confirmMessage || 'Are you sure?';
      if (!confirm(message)) {
        e.preventDefault();
        return false;
      }
    }
    return true;
  });
}

/**
 * Initialize navigation buttons with data-action="navigate".
 * This is a general handler for simple navigation buttons.
 */
export function initNavigateButtons(): void {
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    const button = target.closest<HTMLElement>('[data-action="navigate"]');
    if (button) {
      const url = button.dataset.url;
      if (url) {
        location.href = url;
      }
    }
  });
}

/**
 * Initialize history back buttons with data-action="history-back".
 * This is a general handler for back buttons.
 */
export function initHistoryBackButtons(): void {
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    const button = target.closest<HTMLElement>('[data-action="history-back"]');
    if (button) {
      e.preventDefault();
      history.back();
    }
  });
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  initSettingsForm();
  initConfirmSubmitForms();
  initNavigateButtons();
  initHistoryBackButtons();
});

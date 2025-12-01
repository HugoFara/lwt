/**
 * Simple Interactions - Common UI patterns for navigation and confirmation.
 *
 * This module handles simple inline event handlers that were previously
 * embedded in PHP templates, including:
 * - Navigation buttons (cancel, back, redirect)
 * - Confirmation dialogs (form submission)
 * - Form dirty state management
 * - Translation actions (add/delete translation)
 * - Text word actions (know all, ignore all)
 *
 * @license unlicense
 * @since   3.0.0
 */

import { lwtFormCheck } from '../forms/unloadformcheck';
import { showRightFrames, hideRightFrames } from '../reading/frame_management';
import { showAllwordsClick } from './ui_utilities';
import { quickMenuRedirection } from './user_interactions';
import { deleteTranslation, addTranslation } from '../terms/translation_api';
import { changeTableTestStatus } from '../terms/term_operations';
import { showExportTemplateHelp } from '../ui/modal';

/**
 * Navigate back in browser history.
 */
export function goBack(): void {
  history.back();
}

/**
 * Navigate to a URL.
 *
 * @param url - The URL to navigate to
 */
export function navigateTo(url: string): void {
  location.href = url;
}

/**
 * Reset form dirty state and navigate to a URL.
 * Used for cancel buttons that should not trigger "unsaved changes" warning.
 *
 * @param url - The URL to navigate to
 */
export function cancelAndNavigate(url: string): void {
  lwtFormCheck.resetDirty();
  location.href = url;
}

/**
 * Reset form dirty state and go back in history.
 * Used for "Go Back" buttons that should not trigger "unsaved changes" warning.
 */
export function cancelAndGoBack(): void {
  lwtFormCheck.resetDirty();
  history.back();
}

/**
 * Show a confirmation dialog before form submission.
 *
 * @param message - The confirmation message to display
 * @returns true if user confirmed, false otherwise
 */
export function confirmSubmit(message: string = 'Are you sure?'): boolean {
  return confirm(message);
}

/**
 * Initialize simple interaction handlers using data attributes.
 *
 * Supported data attributes:
 * - data-action="cancel-navigate" data-url="..." - Cancel and navigate
 * - data-action="cancel-back" - Cancel and go back
 * - data-action="navigate" data-url="..." - Simple navigation
 * - data-action="back" - Go back in history
 * - data-confirm="message" - Show confirmation before action
 *
 * For forms:
 * - data-confirm-submit="message" - Confirm before form submission
 */
export function initSimpleInteractions(): void {
  // Handle click actions using event delegation
  document.addEventListener('click', (e) => {
    const el = (e.target as HTMLElement).closest<HTMLElement>('[data-action]');
    if (!el) return;

    const action = el.dataset.action;
    const url = el.dataset.url;
    const confirmMsg = el.dataset.confirm;

    // Check for confirmation first
    if (confirmMsg && !confirm(confirmMsg)) {
      e.preventDefault();
      return;
    }

    switch (action) {
    case 'cancel-navigate':
      if (url) {
        e.preventDefault();
        cancelAndNavigate(url);
      }
      break;

    case 'cancel-back':
      e.preventDefault();
      cancelAndGoBack();
      break;

    case 'navigate':
      if (url) {
        e.preventDefault();
        navigateTo(url);
      }
      break;

    case 'back':
      e.preventDefault();
      goBack();
      break;

    case 'confirm-delete':
      // Uses the existing confirmDelete function pattern
      if (!confirm('CONFIRM\n\nAre you sure you want to delete?')) {
        e.preventDefault();
        return;
      }
      // If confirmed and has URL, navigate
      if (url) {
        e.preventDefault();
        navigateTo(url);
      }
      break;

    case 'cancel-form':
      // Cancel and navigate (same as cancel-navigate but more semantic)
      if (url) {
        e.preventDefault();
        cancelAndNavigate(url);
      }
      break;

    case 'show-right-frames':
      // Show the right frames panel
      showRightFrames();
      break;

    case 'hide-right-frames':
      // Hide the right frames panel
      e.preventDefault();
      hideRightFrames();
      break;

    case 'toggle-show-all':
      // Toggle "Show All" or "Learning Translations" mode
      showAllwordsClick();
      break;

    case 'delete-translation':
      // Clear the translation field
      e.preventDefault();
      deleteTranslation();
      break;

    case 'add-translation':
      // Add a translation word to the field
      e.preventDefault();
      {
        const word = el.dataset.word;
        if (word) {
          addTranslation(word);
        }
      }
      break;

    case 'open-window':
      // Open URL in new window (optionally named via data-window-name)
      e.preventDefault();
      {
        const windowName = el.dataset.windowName;
        const targetUrl = url || (el.tagName === 'A' ? (el as HTMLAnchorElement).href : undefined);
        if (targetUrl) {
          window.open(targetUrl, windowName || '_blank');
        }
      }
      break;

    case 'know-all':
      // Mark all unknown words as well-known
      e.preventDefault();
      {
        const textId = el.dataset.textId;
        if (textId && confirm('Are you sure?')) {
          showRightFrames('all_words_wellknown.php?text=' + textId);
        }
      }
      break;

    case 'ignore-all':
      // Mark all unknown words as ignored
      e.preventDefault();
      {
        const textId = el.dataset.textId;
        if (textId && confirm('Are you sure?')) {
          showRightFrames('all_words_wellknown.php?text=' + textId + '&stat=98');
        }
      }
      break;

    case 'bulk-translate':
      // Open bulk translate in right frames
      e.preventDefault();
      if (url) {
        showRightFrames(url);
      }
      break;

    case 'change-test-status':
      // Change word status in test table (plus/minus buttons)
      e.preventDefault();
      {
        const wordId = el.dataset.wordId;
        const direction = el.dataset.direction;
        if (wordId) {
          changeTableTestStatus(wordId, direction === 'up');
        }
      }
      break;

    case 'go-back':
      // Navigate back in browser history
      e.preventDefault();
      history.back();
      break;

    case 'show-export-template-help':
      // Show export template help modal
      e.preventDefault();
      showExportTemplateHelp();
      break;
    }
  });

  // Handle pager navigation (select dropdown)
  document.addEventListener('change', (e) => {
    const target = e.target as HTMLElement;

    // Pager navigation
    if (target.matches('select[data-action="pager-navigate"]')) {
      const select = target as HTMLSelectElement;
      const baseUrl = select.dataset.baseUrl;
      const selectedValue = select.value;
      if (baseUrl && selectedValue) {
        location.href = baseUrl + '?page=' + selectedValue;
      }
      return;
    }

    // Quick menu navigation
    if (target.matches('select[data-action="quick-menu-redirect"]')) {
      quickMenuRedirection((target as HTMLSelectElement).value);
    }
  });

  // Handle form submission confirmation and auto-submit
  document.addEventListener('submit', (e) => {
    const form = e.target as HTMLFormElement;

    // Form submission confirmation
    if (form.dataset.confirmSubmit !== undefined) {
      const message = form.dataset.confirmSubmit || 'Are you sure?';
      if (!confirm(message)) {
        e.preventDefault();
        return;
      }
    }

    // Forms that auto-submit by clicking a button
    if (form.dataset.autoSubmitButton) {
      e.preventDefault();
      const buttonName = form.dataset.autoSubmitButton;
      const button = form.querySelector<HTMLElement>(`[name="${buttonName}"]`);
      if (button) {
        button.click();
      }
    }
  });
}

// Initialize on document ready
document.addEventListener('DOMContentLoaded', initSimpleInteractions);

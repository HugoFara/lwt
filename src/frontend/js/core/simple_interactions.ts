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

import $ from 'jquery';
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
  // Handle click actions
  $(document).on('click', '[data-action]', function (e) {
    const $el = $(this);
    const action = $el.data('action') as string;
    const url = $el.data('url') as string | undefined;
    const confirmMsg = $el.data('confirm') as string | undefined;

    // Check for confirmation first
    if (confirmMsg && !confirm(confirmMsg)) {
      e.preventDefault();
      return false;
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
        return false;
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
      // Show the right frames panel (for mobile layout)
      showRightFrames();
      break;

    case 'hide-right-frames':
      // Hide the right frames panel (for mobile layout)
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
        const word = $el.data('word') as string;
        if (word) {
          addTranslation(word);
        }
      }
      break;

    case 'open-window':
      // Open URL in new window (optionally named via data-window-name)
      e.preventDefault();
      {
        const windowName = $el.data('window-name') as string | undefined;
        const targetUrl = url || ($el.is('a') ? $el.attr('href') : undefined);
        if (targetUrl) {
          window.open(targetUrl, windowName || '_blank');
        }
      }
      break;

    case 'know-all':
      // Mark all unknown words as well-known
      e.preventDefault();
      {
        const textId = $el.data('text-id');
        if (textId && confirm('Are you sure?')) {
          showRightFrames('all_words_wellknown.php?text=' + textId);
        }
      }
      break;

    case 'ignore-all':
      // Mark all unknown words as ignored
      e.preventDefault();
      {
        const textId = $el.data('text-id');
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
        const wordId = $el.data('word-id') as string;
        const direction = $el.data('direction') as string;
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
  $(document).on('change', 'select[data-action="pager-navigate"]', function () {
    const $el = $(this);
    const baseUrl = $el.data('base-url') as string;
    const selectedValue = $el.val() as string;
    if (baseUrl && selectedValue) {
      location.href = baseUrl + '?page=' + selectedValue;
    }
  });

  // Handle quick menu navigation (select dropdown)
  $(document).on('change', 'select[data-action="quick-menu-redirect"]', function () {
    quickMenuRedirection($(this).val() as string);
  });

  // Handle form submission confirmation
  $(document).on('submit', 'form[data-confirm-submit]', function (e) {
    const message = $(this).data('confirm-submit') as string || 'Are you sure?';
    if (!confirm(message)) {
      e.preventDefault();
      return false;
    }
  });

  // Handle forms that auto-submit by clicking a button
  // (replaces onsubmit="document.form1.buttonname.click(); return false;")
  $(document).on('submit', 'form[data-auto-submit-button]', function (e) {
    e.preventDefault();
    const buttonName = $(this).data('auto-submit-button') as string;
    const button = this.querySelector(`[name="${buttonName}"]`) as HTMLElement | null;
    if (button) {
      button.click();
    }
    return false;
  });
}

// Initialize on document ready
$(document).ready(initSimpleInteractions);

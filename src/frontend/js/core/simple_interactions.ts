/**
 * Simple Interactions - Common UI patterns for navigation and confirmation.
 *
 * This module handles simple inline event handlers that were previously
 * embedded in PHP templates, including:
 * - Navigation buttons (cancel, back, redirect)
 * - Confirmation dialogs (form submission)
 * - Form dirty state management
 *
 * @license unlicense
 * @since   3.0.0
 */

import $ from 'jquery';
import { lwtFormCheck } from '../forms/unloadformcheck';
import { showRightFrames, hideRightFrames } from '../reading/frame_management';
import { showAllwordsClick } from './ui_utilities';

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
    }
  });

  // Handle form submission confirmation
  $(document).on('submit', 'form[data-confirm-submit]', function (e) {
    const message = $(this).data('confirm-submit') as string || 'Are you sure?';
    if (!confirm(message)) {
      e.preventDefault();
      return false;
    }
  });
}

// Initialize on document ready
$(document).ready(initSimpleInteractions);

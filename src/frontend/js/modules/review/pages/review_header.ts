/**
 * Review Header - Header initialization and navigation for vocabulary reviews.
 *
 * @license Unlicense
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

import { onDomReady } from '@shared/utils/dom_ready';

/**
 * Initialize the utterance (read aloud) setting from localStorage.
 *
 * Loads the saved preference and sets up the change listener to persist it.
 */
export function setUtteranceSetting(): void {
  const utteranceChecked = JSON.parse(
    localStorage.getItem('review-utterance-allowed') || 'false'
  );
  const utteranceCheckbox = document.getElementById('utterance-allowed') as HTMLInputElement | null;

  if (!utteranceCheckbox) {
    return;
  }

  utteranceCheckbox.checked = utteranceChecked;
  utteranceCheckbox.addEventListener('change', function () {
    localStorage.setItem(
      'review-utterance-allowed',
      String(utteranceCheckbox.checked)
    );
  });
}

/**
 * Start a word review of a specific type.
 *
 * @param type Review type (1-5)
 * @param property URL property string
 */
export function startWordReview(type: number, property: string): void {
  window.location.href = '/review?type=' + type + '&' + property;
}

/**
 * Start a table review.
 *
 * @param property URL property string
 */
export function startTableReview(property: string): void {
  window.location.href = '/review?type=table&' + property;
}

/**
 * Initialize event delegation for review header buttons.
 */
function initReviewHeaderEvents(): void {
  // Handle word review start via event delegation
  document.addEventListener('click', (e) => {
    const target = (e.target as HTMLElement).closest<HTMLElement>('[data-action="start-word-review"]');
    if (target) {
      const type = parseInt(target.dataset.reviewType || '1', 10);
      const property = target.dataset.property || '';
      startWordReview(type, property);
      return;
    }

    const tableTarget = (e.target as HTMLElement).closest<HTMLElement>('[data-action="start-table-review"]');
    if (tableTarget) {
      const property = tableTarget.dataset.property || '';
      startTableReview(property);
    }
  });
}

// Auto-initialize when DOM is ready
onDomReady(() => {
  initReviewHeaderEvents();
  // Initialize utterance setting if the checkbox exists
  if (document.getElementById('utterance-allowed')) {
    setUtteranceSetting();
  }
});

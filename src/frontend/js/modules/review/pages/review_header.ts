/**
 * Test Header - Header initialization and navigation for vocabulary tests.
 *
 * @license Unlicense
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

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
 * Reset the right frames to empty state.
 */
export function resetTestFrames(): void {
  const parentWindow = window.parent as Window & {
    frames: { [key: string]: Window };
  };
  if (parentWindow.frames['ro']) {
    parentWindow.frames['ro'].location.href = 'empty.html';
  }
  if (parentWindow.frames['ru']) {
    parentWindow.frames['ru'].location.href = 'empty.html';
  }
}

/**
 * Start a word test of a specific type.
 *
 * @param type Test type (1-5)
 * @param property URL property string
 */
export function startWordTest(type: number, property: string): void {
  resetTestFrames();
  window.location.href = '/test?type=' + type + '&' + property;
}

/**
 * Start a table test.
 *
 * @param property URL property string
 */
export function startTestTable(property: string): void {
  resetTestFrames();
  window.location.href = '/test?type=table&' + property;
}

/**
 * Initialize event delegation for test header buttons.
 */
function initTestHeaderEvents(): void {
  // Handle word test start via event delegation
  document.addEventListener('click', (e) => {
    const target = (e.target as HTMLElement).closest<HTMLElement>('[data-action="start-word-test"]');
    if (target) {
      const type = parseInt(target.dataset.testType || '1', 10);
      const property = target.dataset.property || '';
      startWordTest(type, property);
      return;
    }

    const tableTarget = (e.target as HTMLElement).closest<HTMLElement>('[data-action="start-test-table"]');
    if (tableTarget) {
      const property = tableTarget.dataset.property || '';
      startTestTable(property);
    }
  });
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  initTestHeaderEvents();
  // Initialize utterance setting if the checkbox exists
  if (document.getElementById('utterance-allowed')) {
    setUtteranceSetting();
  }
});

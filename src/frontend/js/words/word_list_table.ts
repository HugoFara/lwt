/**
 * Word list table functionality.
 *
 * Handles event delegation for the word list table/bulk actions,
 * replacing inline onclick/onchange handlers with data attributes.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { selectToggle, multiActionGo, allActionGo } from '../forms/bulk_actions';

/**
 * Initialize word list table event handlers.
 * Uses event delegation to handle bulk action interactions.
 */
export function initWordListTable(): void {
  const form2 = document.forms.namedItem('form2');
  if (!form2) return;

  // All action select (actions on all records)
  const allActionSelect = form2.querySelector<HTMLSelectElement>('[data-action="all-action"]');
  if (allActionSelect) {
    allActionSelect.addEventListener('change', () => {
      const recno = parseInt(allActionSelect.dataset.recno || '0', 10);
      allActionGo(form2, allActionSelect, recno);
    });
  }

  // Mark All button
  const markAllButton = form2.querySelector<HTMLButtonElement>('[data-action="mark-all"]');
  if (markAllButton) {
    markAllButton.addEventListener('click', (e) => {
      e.preventDefault();
      selectToggle(true, 'form2');
    });
  }

  // Mark None button
  const markNoneButton = form2.querySelector<HTMLButtonElement>('[data-action="mark-none"]');
  if (markNoneButton) {
    markNoneButton.addEventListener('click', (e) => {
      e.preventDefault();
      selectToggle(false, 'form2');
    });
  }

  // Mark action select (actions on marked records)
  const markActionSelect = form2.querySelector<HTMLSelectElement>('[data-action="mark-action"]');
  if (markActionSelect) {
    markActionSelect.addEventListener('change', () => {
      multiActionGo(form2, markActionSelect);
    });
  }

  // Hide wait info once page is loaded
  const waitInfo = document.getElementById('waitinfo');
  if (waitInfo) {
    waitInfo.classList.add('hide');
  }
}

/**
 * Check if the current page is the word list table page.
 */
function isWordListTablePage(): boolean {
  const form2 = document.forms.namedItem('form2');
  if (!form2) return false;

  // Check for characteristic elements of the word list table
  const hasAllAction = form2.querySelector('[data-action="all-action"]') !== null;
  const hasMarkAction = form2.querySelector('[data-action="mark-action"]') !== null;

  return hasAllAction && hasMarkAction;
}

// Auto-initialize on DOM ready if on word list table page
document.addEventListener('DOMContentLoaded', () => {
  if (isWordListTablePage()) {
    initWordListTable();
  }
});

// Export to window for potential external use
declare global {
  interface Window {
    initWordListTable: typeof initWordListTable;
  }
}

window.initWordListTable = initWordListTable;

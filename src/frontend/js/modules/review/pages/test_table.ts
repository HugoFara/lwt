/**
 * Test Table - Table test mode with column visibility toggles.
 *
 * @license Unlicense
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

import { do_ajax_save_setting } from '@shared/utils/ajax_utilities';

/**
 * Update left border radius for visible columns.
 */
function updateLeftBorderRadius(): void {
  // Reset all border radii
  document.querySelectorAll<HTMLElement>('th,td').forEach((el) => {
    el.style.borderTopLeftRadius = '';
    el.style.borderBottomLeftRadius = '';
  });

  // Set top-left radius on first visible th
  const firstVisibleTh = document.querySelector<HTMLElement>('th:not([style*="display: none"])');
  if (firstVisibleTh) {
    firstVisibleTh.style.borderTopLeftRadius = 'inherit';
    firstVisibleTh.style.borderBottomLeftRadius = '0px';
  }

  // Set bottom-left radius on first visible td in last row
  const lastRow = document.querySelector<HTMLTableRowElement>('tr:last-child');
  if (lastRow) {
    const firstVisibleTd = Array.from(lastRow.querySelectorAll<HTMLElement>('td'))
      .find((td) => td.style.display !== 'none');
    if (firstVisibleTd) {
      firstVisibleTd.style.borderBottomLeftRadius = 'inherit';
    }
  }
}

/**
 * Update right border radius for visible columns.
 */
function updateRightBorderRadius(): void {
  // Reset all border radii
  document.querySelectorAll<HTMLElement>('th,td').forEach((el) => {
    el.style.borderTopRightRadius = '';
    el.style.borderBottomRightRadius = '';
  });

  // Set top-right radius on last visible th
  const visibleThs = Array.from(document.querySelectorAll<HTMLElement>('th'))
    .filter((th) => th.style.display !== 'none');
  const lastVisibleTh = visibleThs[visibleThs.length - 1];
  if (lastVisibleTh) {
    lastVisibleTh.style.borderTopRightRadius = 'inherit';
  }

  // Set bottom-right radius on last visible td in last row
  const lastRow = document.querySelector<HTMLTableRowElement>('tr:last-child');
  if (lastRow) {
    const visibleTds = Array.from(lastRow.querySelectorAll<HTMLElement>('td'))
      .filter((td) => td.style.display !== 'none');
    const lastVisibleTd = visibleTds[visibleTds.length - 1];
    if (lastVisibleTd) {
      lastVisibleTd.style.borderBottomRightRadius = 'inherit';
    }
  }
}

/**
 * Toggle column visibility (show/hide entire column).
 *
 * @param columnIndex 1-based column index
 * @param isVisible Whether the column should be visible
 * @param settingKey Setting key to save
 * @param updateLeft Whether to update left border radius
 */
function toggleColumnVisibility(
  columnIndex: number,
  isVisible: boolean,
  settingKey: string,
  updateLeft: boolean = true
): void {
  const selector = `td:nth-child(${columnIndex}),th:nth-child(${columnIndex})`;
  document.querySelectorAll<HTMLElement>(selector).forEach((el) => {
    el.style.display = isVisible ? '' : 'none';
  });
  do_ajax_save_setting(settingKey, isVisible ? '1' : '0');

  if (updateLeft) {
    updateLeftBorderRadius();
  } else {
    updateRightBorderRadius();
  }
}

/**
 * Toggle column content visibility (make text white/black).
 *
 * @param columnIndex 1-based column index
 * @param isVisible Whether the content should be visible
 * @param settingKey Setting key to save
 */
function toggleContentVisibility(
  columnIndex: number,
  isVisible: boolean,
  settingKey: string
): void {
  const selector = `td:nth-child(${columnIndex})`;
  document.querySelectorAll<HTMLElement>(selector).forEach((el) => {
    el.style.color = isVisible ? 'black' : 'white';
    el.style.cursor = isVisible ? 'auto' : 'pointer';
  });
  do_ajax_save_setting(settingKey, isVisible ? '1' : '0');
}

/**
 * Initialize table test settings and event handlers.
 *
 * Sets up checkbox handlers for column visibility toggles and
 * click-to-reveal functionality.
 */
export function initTableTest(): void {
  // Edit column (1)
  const cbEdit = document.getElementById('cbEdit') as HTMLInputElement | null;
  cbEdit?.addEventListener('change', function () {
    toggleColumnVisibility(1, this.checked, 'currenttabletestsetting1', true);
  });

  // Status column (2)
  const cbStatus = document.getElementById('cbStatus') as HTMLInputElement | null;
  cbStatus?.addEventListener('change', function () {
    toggleColumnVisibility(2, this.checked, 'currenttabletestsetting2', true);
  });

  // Term column (3) - content visibility
  const cbTerm = document.getElementById('cbTerm') as HTMLInputElement | null;
  cbTerm?.addEventListener('change', function () {
    toggleContentVisibility(3, this.checked, 'currenttabletestsetting3');
  });

  // Translation column (4) - content visibility
  const cbTrans = document.getElementById('cbTrans') as HTMLInputElement | null;
  cbTrans?.addEventListener('change', function () {
    toggleContentVisibility(4, this.checked, 'currenttabletestsetting4');
  });

  // Romanization column (5)
  const cbRom = document.getElementById('cbRom') as HTMLInputElement | null;
  cbRom?.addEventListener('change', function () {
    toggleColumnVisibility(5, this.checked, 'currenttabletestsetting5', false);
  });

  // Sentence column (6)
  const cbSentence = document.getElementById('cbSentence') as HTMLInputElement | null;
  cbSentence?.addEventListener('change', function () {
    toggleColumnVisibility(6, this.checked, 'currenttabletestsetting6', false);
  });

  // Click to reveal hidden text in cells
  document.querySelectorAll<HTMLElement>('td').forEach((td) => {
    td.addEventListener('click', function () {
      this.style.color = 'black';
      this.style.cursor = 'auto';
    });
  });

  // Set white background for all cells
  document.querySelectorAll<HTMLElement>('td').forEach((td) => {
    td.style.backgroundColor = 'white';
  });

  // Trigger initial state from checkboxes
  cbEdit?.dispatchEvent(new Event('change'));
  cbStatus?.dispatchEvent(new Event('change'));
  cbTerm?.dispatchEvent(new Event('change'));
  cbTrans?.dispatchEvent(new Event('change'));
  cbRom?.dispatchEvent(new Event('change'));
  cbSentence?.dispatchEvent(new Event('change'));
}

// Auto-initialize when DOM is ready if table test checkboxes exist
document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('cbEdit')) {
    initTableTest();
  }
});

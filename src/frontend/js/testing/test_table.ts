/**
 * Test Table - Table test mode with column visibility toggles.
 *
 * @license Unlicense
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

import $ from 'jquery';
import { do_ajax_save_setting } from '../core/ajax_utilities';

/**
 * Update left border radius for visible columns.
 */
function updateLeftBorderRadius(): void {
  $('th,td').css('border-top-left-radius', '').css('border-bottom-left-radius', '');
  $('th:visible').eq(0).css('border-top-left-radius', 'inherit')
    .css('border-bottom-left-radius', '0px');
  $('tr:last-child>td:visible').eq(0).css('border-bottom-left-radius', 'inherit');
}

/**
 * Update right border radius for visible columns.
 */
function updateRightBorderRadius(): void {
  $('th,td').css('border-top-right-radius', '').css('border-bottom-right-radius', '');
  $('th:visible:last').css('border-top-right-radius', 'inherit');
  $('tr:last-child>td:visible:last').css('border-bottom-right-radius', 'inherit');
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
  if (isVisible) {
    $(selector).show();
    do_ajax_save_setting(settingKey, '1');
  } else {
    $(selector).hide();
    do_ajax_save_setting(settingKey, '0');
  }
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
  if (isVisible) {
    $(selector).css('color', 'black').css('cursor', 'auto');
    do_ajax_save_setting(settingKey, '1');
  } else {
    $(selector).css('color', 'white').css('cursor', 'pointer');
    do_ajax_save_setting(settingKey, '0');
  }
}

/**
 * Initialize table test settings and event handlers.
 *
 * Sets up checkbox handlers for column visibility toggles and
 * click-to-reveal functionality.
 */
export function initTableTest(): void {
  // Edit column (1)
  $('#cbEdit').on('change', function () {
    toggleColumnVisibility(1, $(this).is(':checked'), 'currenttabletestsetting1', true);
  });

  // Status column (2)
  $('#cbStatus').on('change', function () {
    toggleColumnVisibility(2, $(this).is(':checked'), 'currenttabletestsetting2', true);
  });

  // Term column (3) - content visibility
  $('#cbTerm').on('change', function () {
    toggleContentVisibility(3, $(this).is(':checked'), 'currenttabletestsetting3');
  });

  // Translation column (4) - content visibility
  $('#cbTrans').on('change', function () {
    toggleContentVisibility(4, $(this).is(':checked'), 'currenttabletestsetting4');
  });

  // Romanization column (5)
  $('#cbRom').on('change', function () {
    toggleColumnVisibility(5, $(this).is(':checked'), 'currenttabletestsetting5', false);
  });

  // Sentence column (6)
  $('#cbSentence').on('change', function () {
    toggleColumnVisibility(6, $(this).is(':checked'), 'currenttabletestsetting6', false);
  });

  // Click to reveal hidden text in cells
  $('td').on('click', function () {
    $(this).css('color', 'black').css('cursor', 'auto');
  });

  // Set white background for all cells
  $('td').css('background-color', 'white');

  // Trigger initial state from checkboxes
  $('#cbEdit').trigger('change');
  $('#cbStatus').trigger('change');
  $('#cbTerm').trigger('change');
  $('#cbTrans').trigger('change');
  $('#cbRom').trigger('change');
  $('#cbSentence').trigger('change');
}

// Auto-initialize when DOM is ready if table test checkboxes exist
$(document).ready(function () {
  if ($('#cbEdit').length > 0) {
    initTableTest();
  }
});

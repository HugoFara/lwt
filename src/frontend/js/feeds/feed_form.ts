/**
 * Feed Form - Options toggling and serialization for feed edit/new forms.
 *
 * This module handles the interactive elements on feed forms, including:
 * - Checkbox toggling to enable/disable associated input fields
 * - Serializing options into a hidden field on form submission
 *
 * @license unlicense
 * @since   3.0.0
 */

import $ from 'jquery';

/**
 * Handle checkbox change to enable/disable associated input fields.
 * When a checkbox with name starting with "c_" is checked/unchecked,
 * it enables/disables the input fields in the same parent container.
 */
function handleOptionCheckboxChange(this: HTMLInputElement): void {
  const $parent = $(this).parent();

  if (this.checked) {
    // Enable associated inputs and add required class
    $parent.children('input[type="text"], input[type="number"]')
      .removeAttr('disabled')
      .addClass('notempty');
    $parent.find('select').removeAttr('disabled');
  } else {
    // Disable associated inputs and remove required class
    $parent.children('input[type="text"], input[type="number"]')
      .attr('disabled', 'disabled')
      .removeClass('notempty');
    $parent.find('select').attr('disabled', 'disabled');
  }
}

/**
 * Serialize feed options into the hidden NfOptions field.
 * Called before form submission to collect all option values.
 */
function serializeFeedOptions(): void {
  let str = '';

  // Check if edit_text is checked
  if ($('[name="edit_text"]:checked').length > 0) {
    str = 'edit_text=1,';
  }

  // Iterate through all option checkboxes (c_*)
  $('[name^="c_"]').each(function () {
    const checkbox = this as HTMLInputElement;
    if (checkbox.checked) {
      const $parent = $(checkbox).parent();
      const $input = $parent.children('input[type="text"], input[type="number"]');
      const inputName = $input.attr('name') || '';
      const inputValue = $input.val() || '';

      // For autoupdate, include the select value (h/d/w)
      if ($(checkbox).attr('name') === 'c_autoupdate') {
        const selectValue = $parent.find('select').val() || '';
        str += `${inputName}=${inputValue}${selectValue},`;
      } else {
        str += `${inputName}=${inputValue},`;
      }
    }
  });

  // Set the hidden field value
  $('input[name="NfOptions"]').val(str);
}

/**
 * Initialize feed form interactions.
 */
export function initFeedForm(): void {
  // Only initialize on pages with feed forms
  if ($('input[name="NfOptions"]').length === 0) {
    return;
  }

  // Bind checkbox change handlers
  $('[name^="c_"]').on('change', handleOptionCheckboxChange);

  // Bind form submission handler
  $('[type="submit"]').on('click', serializeFeedOptions);

  // Also handle form submit event (in case Enter is pressed)
  $('form.validate').on('submit', serializeFeedOptions);
}

// Auto-initialize when DOM is ready
$(document).ready(initFeedForm);

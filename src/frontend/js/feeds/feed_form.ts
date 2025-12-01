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

/**
 * Handle checkbox change to enable/disable associated input fields.
 * When a checkbox with name starting with "c_" is checked/unchecked,
 * it enables/disables the input fields in the same parent container.
 */
function handleOptionCheckboxChange(checkbox: HTMLInputElement): void {
  const parent = checkbox.parentElement;
  if (!parent) return;

  if (checkbox.checked) {
    // Enable associated inputs and add required class
    parent.querySelectorAll<HTMLInputElement>(':scope > input[type="text"], :scope > input[type="number"]')
      .forEach(input => {
        input.disabled = false;
        input.classList.add('notempty');
      });
    parent.querySelectorAll<HTMLSelectElement>('select').forEach(select => {
      select.disabled = false;
    });
  } else {
    // Disable associated inputs and remove required class
    parent.querySelectorAll<HTMLInputElement>(':scope > input[type="text"], :scope > input[type="number"]')
      .forEach(input => {
        input.disabled = true;
        input.classList.remove('notempty');
      });
    parent.querySelectorAll<HTMLSelectElement>('select').forEach(select => {
      select.disabled = true;
    });
  }
}

/**
 * Serialize feed options into the hidden NfOptions field.
 * Called before form submission to collect all option values.
 */
function serializeFeedOptions(): void {
  let str = '';

  // Check if edit_text is checked
  const editTextCheckbox = document.querySelector<HTMLInputElement>('[name="edit_text"]:checked');
  if (editTextCheckbox) {
    str = 'edit_text=1,';
  }

  // Iterate through all option checkboxes (c_*)
  document.querySelectorAll<HTMLInputElement>('[name^="c_"]').forEach(checkbox => {
    if (checkbox.checked) {
      const parent = checkbox.parentElement;
      if (!parent) return;

      const input = parent.querySelector<HTMLInputElement>(':scope > input[type="text"], :scope > input[type="number"]');
      const inputName = input?.name || '';
      const inputValue = input?.value || '';

      // For autoupdate, include the select value (h/d/w)
      if (checkbox.name === 'c_autoupdate') {
        const select = parent.querySelector<HTMLSelectElement>('select');
        const selectValue = select?.value || '';
        str += `${inputName}=${inputValue}${selectValue},`;
      } else {
        str += `${inputName}=${inputValue},`;
      }
    }
  });

  // Set the hidden field value
  const hiddenField = document.querySelector<HTMLInputElement>('input[name="NfOptions"]');
  if (hiddenField) {
    hiddenField.value = str;
  }
}

/**
 * Initialize feed form interactions.
 */
export function initFeedForm(): void {
  // Only initialize on pages with feed forms
  const nfOptionsField = document.querySelector('input[name="NfOptions"]');
  if (!nfOptionsField) {
    return;
  }

  // Bind checkbox change handlers
  document.querySelectorAll<HTMLInputElement>('[name^="c_"]').forEach(checkbox => {
    checkbox.addEventListener('change', () => handleOptionCheckboxChange(checkbox));
  });

  // Bind form submission handler
  document.querySelectorAll<HTMLButtonElement>('[type="submit"]').forEach(btn => {
    btn.addEventListener('click', serializeFeedOptions);
  });

  // Also handle form submit event (in case Enter is pressed)
  document.querySelectorAll<HTMLFormElement>('form.validate').forEach(form => {
    form.addEventListener('submit', serializeFeedOptions);
  });
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initFeedForm);

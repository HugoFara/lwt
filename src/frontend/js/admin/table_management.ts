/**
 * Table set management page functionality.
 *
 * Handles event delegation for the table set management page,
 * replacing inline onclick/onsubmit handlers with event listeners.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

/**
 * Validate table prefix value.
 *
 * @param value The prefix value to validate
 * @returns True if valid, false otherwise
 */
function checkTablePrefix(value: string): boolean {
  if (value.trim() === '') {
    alert('Table Set Name must not be empty!');
    return false;
  }
  if (!/^[a-zA-Z0-9_]+$/.test(value)) {
    alert('Table Set Name must contain only letters, numbers, and underscores!');
    return false;
  }
  if (value.length > 20) {
    alert('Table Set Name must be 20 characters or less!');
    return false;
  }
  return true;
}

/**
 * Initialize table create form.
 */
function initTableCreateForm(): void {
  const form = document.querySelector<HTMLFormElement>('form.table-create-form');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    const input = form.querySelector<HTMLInputElement>('input[name="newpref"]');
    if (input && !checkTablePrefix(input.value)) {
      e.preventDefault();
    }
  });
}

/**
 * Initialize table delete form.
 */
function initTableDeleteForm(): void {
  const form = document.querySelector<HTMLFormElement>('form.table-delete-form');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    const select = form.querySelector<HTMLSelectElement>('select[name="delpref"]');
    if (select && select.selectedIndex > 0) {
      const selectedText = select.options[select.selectedIndex].text;
      const confirmMessage =
        '\n*** DELETING TABLE SET: ' + selectedText + ' ***\n\n' +
        '*** ALL DATA IN THIS TABLE SET WILL BE LOST! ***\n\n' +
        '*** ARE YOU SURE ?? ***';
      if (!confirm(confirmMessage)) {
        e.preventDefault();
      }
    }
  });
}

/**
 * Initialize common navigation handlers.
 */
function initNavigationHandlers(): void {
  // Go back buttons
  const goBackButtons = document.querySelectorAll<HTMLButtonElement>('[data-action="go-back"]');
  goBackButtons.forEach((button) => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      history.back();
    });
  });

  // Navigate buttons
  const navigateButtons = document.querySelectorAll<HTMLButtonElement>('[data-action="navigate"]');
  navigateButtons.forEach((button) => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      const url = button.dataset.url;
      if (url) {
        location.href = url;
      }
    });
  });
}

/**
 * Initialize all table management event handlers.
 */
export function initTableManagement(): void {
  initTableCreateForm();
  initTableDeleteForm();
  initNavigationHandlers();
}

/**
 * Check if the current page is the table management page.
 */
function isTableManagementPage(): boolean {
  // Check for the characteristic forms
  const createForm = document.querySelector('form.table-create-form');
  const deleteForm = document.querySelector('form.table-delete-form');
  // Also check for the fixed prefix warning page (has go-back button)
  const goBackButton = document.querySelector('[data-action="go-back"]');

  return createForm !== null || deleteForm !== null || goBackButton !== null;
}

// Auto-initialize on DOM ready if on table management page
document.addEventListener('DOMContentLoaded', () => {
  if (isTableManagementPage()) {
    initTableManagement();
  }
});

// Export to window for potential external use and legacy compatibility
declare global {
  interface Window {
    initTableManagement: typeof initTableManagement;
    check_table_prefix: typeof checkTablePrefix;
    checkTablePrefix: typeof checkTablePrefix;
  }
}

window.initTableManagement = initTableManagement;
window.check_table_prefix = checkTablePrefix;
window.checkTablePrefix = checkTablePrefix;

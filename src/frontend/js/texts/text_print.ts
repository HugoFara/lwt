/**
 * Text print page functionality.
 *
 * Handles event delegation for the text print pages (plain print and annotated display),
 * replacing inline onclick/onchange handlers with data attributes.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

/**
 * Get the text ID from the printoptions container.
 */
function getTextId(): string | null {
  const container = document.getElementById('printoptions');
  return container?.dataset.textId || null;
}

/**
 * Navigate to URL with updated query parameter.
 *
 * @param param Parameter name to update
 * @param value New value for the parameter
 */
function navigateWithParam(param: string, value: string): void {
  const textId = getTextId();
  if (!textId) return;
  location.href = `/text/print-plain?text=${textId}&${param}=${value}`;
}

/**
 * Initialize plain print page event handlers.
 */
function initPlainPrint(): void {
  const container = document.getElementById('printoptions');
  if (!container) return;

  // Status filter select
  const statusSelect = container.querySelector<HTMLSelectElement>('[data-action="filter-status"]');
  if (statusSelect) {
    statusSelect.addEventListener('change', () => {
      navigateWithParam('status', statusSelect.value);
    });
  }

  // Annotation filter select
  const annSelect = container.querySelector<HTMLSelectElement>('[data-action="filter-annotation"]');
  if (annSelect) {
    annSelect.addEventListener('change', () => {
      navigateWithParam('ann', annSelect.value);
    });
  }

  // Annotation placement filter select
  const placementSelect = container.querySelector<HTMLSelectElement>('[data-action="filter-placement"]');
  if (placementSelect) {
    placementSelect.addEventListener('change', () => {
      navigateWithParam('annplcmnt', placementSelect.value);
    });
  }
}

/**
 * Initialize common print page event handlers (shared by plain print and annotated display).
 */
function initCommonPrintHandlers(): void {
  // Print button
  const printButtons = document.querySelectorAll<HTMLButtonElement>('[data-action="print"]');
  printButtons.forEach((button) => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      window.print();
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

  // Confirm navigate buttons (with confirmation dialog)
  const confirmNavigateButtons = document.querySelectorAll<HTMLButtonElement>('[data-action="confirm-navigate"]');
  confirmNavigateButtons.forEach((button) => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      const url = button.dataset.url;
      const confirmMessage = button.dataset.confirm || 'Are you sure?';
      if (url && confirm(confirmMessage)) {
        location.href = url;
      }
    });
  });

  // Open window buttons
  const openWindowButtons = document.querySelectorAll<HTMLButtonElement>('[data-action="open-window"]');
  openWindowButtons.forEach((button) => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      const url = button.dataset.url;
      if (url) {
        window.open(url);
      }
    });
  });
}

/**
 * Initialize all text print event handlers.
 */
export function initTextPrint(): void {
  initPlainPrint();
  initCommonPrintHandlers();
}

/**
 * Check if the current page is a text print page.
 */
function isTextPrintPage(): boolean {
  // Check for printoptions container (present on both plain print and annotated display)
  const printOptions = document.getElementById('printoptions');
  return printOptions !== null;
}

// Auto-initialize on DOM ready if on text print page
document.addEventListener('DOMContentLoaded', () => {
  if (isTextPrintPage()) {
    initTextPrint();
  }
});

// Export to window for potential external use
declare global {
  interface Window {
    initTextPrint: typeof initTextPrint;
  }
}

window.initTextPrint = initTextPrint;

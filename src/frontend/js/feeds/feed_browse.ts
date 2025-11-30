/**
 * Feed browse page interactions.
 *
 * Handles event delegation for the feeds browse page, replacing inline
 * onclick/onchange handlers with data attributes.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { setLang, resetAll } from '../core/language_settings';
import { selectToggle } from '../forms/bulk_actions';

/**
 * Initialize feed browse page event handlers.
 * Uses event delegation to handle all interactions.
 */
export function initFeedBrowse(): void {
  const form1 = document.forms.namedItem('form1');
  if (!form1) return;

  // Language filter select
  const filterLang = form1.querySelector<HTMLSelectElement>('[data-action="filter-language"]');
  if (filterLang) {
    filterLang.addEventListener('change', () => {
      const url = filterLang.dataset.url || '/feeds?page=1&selected_feed=0';
      setLang(filterLang, url);
    });
  }

  // Query mode select
  const queryMode = form1.querySelector<HTMLSelectElement>('[data-action="query-mode"]');
  if (queryMode) {
    queryMode.addEventListener('change', () => {
      const queryInput = form1.querySelector<HTMLInputElement>('[name="query"]');
      const val = queryInput?.value || '';
      const mode = queryMode.value;
      location.href = `/feeds?page=1&query=${encodeURIComponent(val)}&query_mode=${encodeURIComponent(mode)}`;
    });
  }

  // Query filter button
  const queryButton = form1.querySelector<HTMLButtonElement>('[data-action="filter-query"]');
  if (queryButton) {
    queryButton.addEventListener('click', (e) => {
      e.preventDefault();
      const queryInput = form1.querySelector<HTMLInputElement>('[name="query"]');
      const val = encodeURIComponent(queryInput?.value || '');
      location.href = `/feeds?page=1&query=${val}`;
    });
  }

  // Query clear button
  const clearButton = form1.querySelector<HTMLButtonElement>('[data-action="clear-query"]');
  if (clearButton) {
    clearButton.addEventListener('click', (e) => {
      e.preventDefault();
      location.href = '/feeds?page=1&query=';
    });
  }

  // Reset all button
  const resetButton = form1.querySelector<HTMLButtonElement>('[data-action="reset-all"]');
  if (resetButton) {
    resetButton.addEventListener('click', (e) => {
      e.preventDefault();
      const url = resetButton.dataset.url || '/feeds';
      resetAll(url);
    });
  }

  // Feed select
  const feedSelect = form1.querySelector<HTMLSelectElement>('[data-action="filter-feed"]');
  if (feedSelect) {
    feedSelect.addEventListener('change', () => {
      const val = feedSelect.value;
      location.href = `/feeds?page=1&selected_feed=${encodeURIComponent(val)}`;
    });
  }

  // Sort select
  const sortSelect = form1.querySelector<HTMLSelectElement>('[data-action="sort"]');
  if (sortSelect) {
    sortSelect.addEventListener('change', () => {
      const val = sortSelect.value;
      location.href = `/feeds?page=1&sort=${encodeURIComponent(val)}`;
    });
  }
}

/**
 * Initialize mark all/none buttons for form2.
 * Uses event delegation for buttons with data-action="mark-all" or "mark-none".
 */
export function initMarkButtons(): void {
  document.addEventListener('click', (e: MouseEvent) => {
    const target = e.target as HTMLElement;

    if (target.matches('[data-action="mark-all"]')) {
      e.preventDefault();
      const formName = target.dataset.form || 'form2';
      selectToggle(true, formName);
    } else if (target.matches('[data-action="mark-none"]')) {
      e.preventDefault();
      const formName = target.dataset.form || 'form2';
      selectToggle(false, formName);
    }
  });
}

/**
 * Initialize popup link handlers for audio and external links.
 * Opens links in popup windows instead of navigating.
 */
export function initPopupLinks(): void {
  document.addEventListener('click', (e: MouseEvent) => {
    const target = e.target as HTMLElement;
    const link = target.closest<HTMLAnchorElement>('[data-action="popup-audio"], [data-action="popup-external"]');

    if (link) {
      e.preventDefault();
      const action = link.dataset.action;
      const url = link.href;

      if (action === 'popup-audio') {
        window.open(url, 'child', 'scrollbars,width=650,height=600');
      } else {
        window.open(url);
      }
    }
  });
}

/**
 * Initialize the "not found" image click handler.
 * Replaces error images with checkboxes when clicked.
 */
export function initNotFoundImages(): void {
  document.addEventListener('click', (e: MouseEvent) => {
    const target = e.target as HTMLElement;
    if (target.matches('img.not_found')) {
      const id = target.getAttribute('name') || '';
      const label = document.createElement('label');
      label.className = 'wrap_checkbox';
      label.setAttribute('for', id);
      label.innerHTML = '<span></span>';

      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.className = 'markcheck';
      checkbox.id = id;
      checkbox.value = id;
      checkbox.name = 'marked_items[]';
      checkbox.addEventListener('change', () => {
        if (typeof window.markClick === 'function') {
          window.markClick();
        }
      });

      target.after(label);
      target.replaceWith(checkbox);

      // Re-index tab order
      const elements = document.querySelectorAll<HTMLElement>(
        ':is(input, select, textarea, button), .wrap_checkbox span, a:not([name^="rec"])'
      );
      elements.forEach((el, i) => {
        el.setAttribute('tabindex', String(i + 1));
      });
    }
  });
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  initFeedBrowse();
  initMarkButtons();
  initPopupLinks();
  initNotFoundImages();
});

// Export to window for potential external use
declare global {
  interface Window {
    initFeedBrowse: typeof initFeedBrowse;
    initMarkButtons: typeof initMarkButtons;
    initPopupLinks: typeof initPopupLinks;
    initNotFoundImages: typeof initNotFoundImages;
  }
}

window.initFeedBrowse = initFeedBrowse;
window.initMarkButtons = initMarkButtons;
window.initPopupLinks = initPopupLinks;
window.initNotFoundImages = initNotFoundImages;

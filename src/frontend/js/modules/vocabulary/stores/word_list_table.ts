/**
 * Word List Table - Alpine.js component for word list bulk actions.
 *
 * Handles bulk selection, mark all/none, and bulk action execution
 * for the word list table.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 * @since   3.1.0 Migrated to Alpine.js component
 */

import Alpine from 'alpinejs';
import { selectToggle, multiActionGo, allActionGo } from '../forms/bulk_actions';

/**
 * Configuration for word list table component.
 */
export interface WordListTableConfig {
  recno?: number;
  formName?: string;
}

/**
 * Word list table Alpine component data interface.
 */
export interface WordListTableData {
  // Configuration
  recno: number;
  formName: string;

  // State
  isLoading: boolean;

  // Methods
  init(): void;
  markAll(): void;
  markNone(): void;
  handleAllAction(event: Event): void;
  handleMarkAction(event: Event): void;
  getForm(): HTMLFormElement | null;
}

/**
 * Alpine.js component for word list table bulk actions.
 * Replaces the vanilla JS event delegation pattern.
 */
export function wordListTableApp(config: WordListTableConfig = {}): WordListTableData {
  return {
    // Configuration
    recno: config.recno ?? 0,
    formName: config.formName ?? 'form2',

    // State
    isLoading: false,

    /**
     * Initialize the component.
     */
    init(): void {
      // Read config from JSON script tag if available
      const configEl = document.getElementById('word-list-table-config');
      if (configEl) {
        try {
          const jsonConfig = JSON.parse(configEl.textContent || '{}') as WordListTableConfig;
          this.recno = jsonConfig.recno ?? this.recno;
          this.formName = jsonConfig.formName ?? this.formName;
        } catch {
          // Invalid JSON, use defaults
        }
      }

      // Hide wait info once loaded
      const waitInfo = document.getElementById('waitinfo');
      if (waitInfo) {
        waitInfo.classList.add('hide');
      }
    },

    /**
     * Mark all checkboxes.
     */
    markAll(): void {
      selectToggle(true, this.formName);
    },

    /**
     * Unmark all checkboxes.
     */
    markNone(): void {
      selectToggle(false, this.formName);
    },

    /**
     * Handle "all action" select (actions on all records).
     */
    handleAllAction(event: Event): void {
      const select = event.target as HTMLSelectElement;
      const form = this.getForm();
      if (form) {
        allActionGo(form, select, this.recno);
      }
    },

    /**
     * Handle "mark action" select (actions on marked records).
     */
    handleMarkAction(event: Event): void {
      const select = event.target as HTMLSelectElement;
      const form = this.getForm();
      if (form) {
        multiActionGo(form, select);
      }
    },

    /**
     * Get the form element.
     */
    getForm(): HTMLFormElement | null {
      return document.forms.namedItem(this.formName);
    }
  };
}

// Register Alpine component
if (typeof Alpine !== 'undefined') {
  Alpine.data('wordListTableApp', wordListTableApp);
}

// ============================================================================
// Legacy API - For backward compatibility
// ============================================================================

/**
 * Initialize word list table event handlers.
 * Uses event delegation to handle bulk action interactions.
 * @deprecated Since 3.1.0, use wordListTableApp() Alpine component
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

// Auto-initialize on DOM ready if on word list table page (legacy support)
document.addEventListener('DOMContentLoaded', () => {
  if (isWordListTablePage()) {
    initWordListTable();
  }
});

// Export to window for potential external use
declare global {
  interface Window {
    initWordListTable: typeof initWordListTable;
    wordListTableApp: typeof wordListTableApp;
  }
}

window.initWordListTable = initWordListTable;
window.wordListTableApp = wordListTableApp;

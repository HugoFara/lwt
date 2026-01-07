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
import { selectToggle, multiActionGo, allActionGo } from '@shared/forms/bulk_actions';

// Note: selectToggle, multiActionGo, allActionGo are used by wordListTableApp component

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

// Export to window for potential external use
declare global {
  interface Window {
    wordListTableApp: typeof wordListTableApp;
  }
}

window.wordListTableApp = wordListTableApp;

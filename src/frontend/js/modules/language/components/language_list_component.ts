/**
 * Language List Component - Alpine.js component for language list page.
 *
 * Provides the reactive UI for displaying and managing languages.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';
import {
  getLanguageStore,
  type LanguageStoreState
} from '../stores/language_store';
import type { LanguageListItem } from '@modules/language/api/languages_api';

/**
 * Language list component data interface.
 */
export interface LanguageListComponentData {
  // Store reference
  store: LanguageStoreState;

  // Notification state
  notification: string | null;
  notificationType: 'success' | 'error' | 'info';

  // Methods
  init(): void;
  getLanguage(id: number): LanguageListItem | undefined;
  handleSetDefault(id: number): Promise<void>;
  handleDelete(id: number): Promise<void>;
  handleRefresh(id: number): Promise<void>;
  navigateToEdit(id: number): void;
  navigateToNew(): void;
  showNotification(message: string, type: 'success' | 'error' | 'info'): void;
  clearNotification(): void;
  canDelete(lang: LanguageListItem): boolean;
}

/**
 * Refresh Lucide icons after DOM changes.
 */
function refreshIcons(): void {
  setTimeout(() => {
    createIcons({ icons });
  }, 0);
}

/**
 * Create the language list component data.
 */
export function languageListData(): LanguageListComponentData {
  return {
    store: getLanguageStore(),

    notification: null,
    notificationType: 'info',

    /**
     * Initialize the component.
     */
    async init(): Promise<void> {
      await this.store.loadLanguages();
      await this.store.loadDefinitions();

      // Initialize icons after DOM updates
      refreshIcons();
    },

    /**
     * Get a language by ID.
     */
    getLanguage(id: number): LanguageListItem | undefined {
      return this.store.languages.find((l) => l.id === id);
    },

    /**
     * Handle setting a language as default.
     */
    async handleSetDefault(id: number): Promise<void> {
      const lang = this.getLanguage(id);
      if (!lang) return;

      const success = await this.store.setCurrentLanguage(id);

      if (success) {
        this.showNotification(
          `"${lang.name}" is now the current language`,
          'success'
        );
      } else {
        this.showNotification(
          this.store.error || 'Failed to set default language',
          'error'
        );
      }

      // Refresh icons
      refreshIcons();
    },

    /**
     * Handle deleting a language.
     */
    async handleDelete(id: number): Promise<void> {
      const lang = this.getLanguage(id);
      if (!lang) return;

      // Check if can delete
      if (!this.canDelete(lang)) {
        this.showNotification(
          'Cannot delete: language has texts, words, or feeds',
          'error'
        );
        this.store.hideDeleteConfirm();
        return;
      }

      const success = await this.store.deleteLanguage(id);

      if (success) {
        this.showNotification(`"${lang.name}" has been deleted`, 'success');
      } else {
        this.showNotification(
          this.store.error || 'Failed to delete language',
          'error'
        );
      }

      // Refresh icons
      refreshIcons();
    },

    /**
     * Handle refreshing (reparsing) a language.
     */
    async handleRefresh(id: number): Promise<void> {
      const lang = this.getLanguage(id);
      if (!lang) return;

      this.showNotification(`Reparsing texts for "${lang.name}"...`, 'info');

      const success = await this.store.refreshLanguage(id);

      if (success) {
        this.showNotification(
          `Texts for "${lang.name}" have been reparsed`,
          'success'
        );
      } else {
        this.showNotification(
          this.store.error || 'Failed to reparse texts',
          'error'
        );
      }
    },

    /**
     * Navigate to the edit page for a language.
     */
    navigateToEdit(id: number): void {
      window.location.href = `/languages?chg=${id}`;
    },

    /**
     * Navigate to the new language page.
     */
    navigateToNew(): void {
      window.location.href = '/languages?new=1';
    },

    /**
     * Show a notification message.
     */
    showNotification(
      message: string,
      type: 'success' | 'error' | 'info'
    ): void {
      this.notification = message;
      this.notificationType = type;

      // Auto-hide after 5 seconds for success/info
      if (type !== 'error') {
        setTimeout(() => {
          this.clearNotification();
        }, 5000);
      }
    },

    /**
     * Clear the notification.
     */
    clearNotification(): void {
      this.notification = null;
    },

    /**
     * Check if a language can be deleted.
     */
    canDelete(lang: LanguageListItem): boolean {
      return (
        lang.textCount === 0 &&
        lang.archivedTextCount === 0 &&
        lang.wordCount === 0 &&
        lang.feedCount === 0
      );
    }
  };
}

/**
 * Initialize the language list Alpine.js component.
 */
export function initLanguageListComponent(): void {
  Alpine.data('languageList', languageListData);
}

// Register the component immediately
initLanguageListComponent();

// Expose for global access
declare global {
  interface Window {
    languageListData: typeof languageListData;
  }
}

window.languageListData = languageListData;

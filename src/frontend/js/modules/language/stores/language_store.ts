/**
 * Language Store - Alpine.js store for language list state management.
 *
 * Provides centralized state management for the language list page.
 * Handles loading languages, setting default, delete operations.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import {
  LanguagesApi,
  type LanguageListItem,
  type LanguageDefinition
} from '../../api/languages';

/**
 * Language store state interface.
 */
export interface LanguageStoreState {
  // Data
  languages: LanguageListItem[];
  currentLanguageId: number;
  definitions: Record<string, LanguageDefinition>;

  // UI state
  isLoading: boolean;
  error: string | null;
  deleteConfirmId: number | null;
  refreshingId: number | null;

  // Wizard modal state
  wizardModalOpen: boolean;

  // Methods
  loadLanguages(): Promise<void>;
  loadDefinitions(): Promise<void>;
  setCurrentLanguage(id: number): Promise<boolean>;
  deleteLanguage(id: number): Promise<boolean>;
  refreshLanguage(id: number): Promise<boolean>;
  openWizardModal(): void;
  closeWizardModal(): void;
  showDeleteConfirm(id: number): void;
  hideDeleteConfirm(): void;

  // Computed
  readonly currentLanguage: LanguageListItem | undefined;
}

/**
 * Create the language store data object.
 */
function createLanguageStore(): LanguageStoreState {
  return {
    // Data
    languages: [],
    currentLanguageId: 0,
    definitions: {},

    // UI state
    isLoading: true,
    error: null,
    deleteConfirmId: null,
    refreshingId: null,

    // Wizard modal state
    wizardModalOpen: false,

    /**
     * Get the current language object.
     */
    get currentLanguage(): LanguageListItem | undefined {
      return this.languages.find((l) => l.id === this.currentLanguageId);
    },

    /**
     * Load all languages from the API.
     */
    async loadLanguages(): Promise<void> {
      this.isLoading = true;
      this.error = null;

      try {
        const response = await LanguagesApi.list();

        if (response.error || !response.data) {
          this.error = response.error || 'Failed to load languages';
          this.isLoading = false;
          return;
        }

        this.languages = response.data.languages;
        this.currentLanguageId = response.data.currentLanguageId;
      } catch (error) {
        console.error('Error loading languages:', error);
        this.error = 'Failed to load languages';
      }

      this.isLoading = false;
    },

    /**
     * Load language definitions (presets).
     */
    async loadDefinitions(): Promise<void> {
      try {
        const response = await LanguagesApi.getDefinitions();

        if (response.error || !response.data) {
          console.error('Failed to load language definitions:', response.error);
          return;
        }

        this.definitions = response.data.definitions;
      } catch (error) {
        console.error('Error loading language definitions:', error);
      }
    },

    /**
     * Set a language as the current/default language.
     */
    async setCurrentLanguage(id: number): Promise<boolean> {
      try {
        const response = await LanguagesApi.setDefault(id);

        if (response.error || !response.data?.success) {
          this.error = response.error || 'Failed to set default language';
          return false;
        }

        this.currentLanguageId = id;
        return true;
      } catch (error) {
        console.error('Error setting default language:', error);
        this.error = 'Failed to set default language';
        return false;
      }
    },

    /**
     * Delete a language.
     */
    async deleteLanguage(id: number): Promise<boolean> {
      try {
        const response = await LanguagesApi.delete(id);

        if (response.error) {
          this.error = response.error;
          return false;
        }

        if (!response.data?.success) {
          this.error = response.data?.error || 'Failed to delete language';
          return false;
        }

        // Remove from local list
        this.languages = this.languages.filter((l) => l.id !== id);

        // Clear current language if deleted
        if (this.currentLanguageId === id) {
          this.currentLanguageId = 0;
        }

        this.deleteConfirmId = null;
        return true;
      } catch (error) {
        console.error('Error deleting language:', error);
        this.error = 'Failed to delete language';
        return false;
      }
    },

    /**
     * Refresh (reparse) all texts for a language.
     */
    async refreshLanguage(id: number): Promise<boolean> {
      this.refreshingId = id;

      try {
        const response = await LanguagesApi.refresh(id);

        if (response.error || !response.data?.success) {
          this.error = response.error || 'Failed to refresh language';
          this.refreshingId = null;
          return false;
        }

        this.refreshingId = null;
        return true;
      } catch (error) {
        console.error('Error refreshing language:', error);
        this.error = 'Failed to refresh language';
        this.refreshingId = null;
        return false;
      }
    },

    /**
     * Open the language wizard modal.
     */
    openWizardModal(): void {
      this.wizardModalOpen = true;
    },

    /**
     * Close the language wizard modal.
     */
    closeWizardModal(): void {
      this.wizardModalOpen = false;
    },

    /**
     * Show delete confirmation for a language.
     */
    showDeleteConfirm(id: number): void {
      this.deleteConfirmId = id;
    },

    /**
     * Hide delete confirmation.
     */
    hideDeleteConfirm(): void {
      this.deleteConfirmId = null;
    }
  };
}

/**
 * Initialize the language store as an Alpine.js store.
 */
export function initLanguageStore(): void {
  Alpine.store('languages', createLanguageStore());
}

/**
 * Get the language store instance.
 */
export function getLanguageStore(): LanguageStoreState {
  return Alpine.store('languages') as LanguageStoreState;
}

// Register the store immediately
initLanguageStore();

// Expose for global access
declare global {
  interface Window {
    getLanguageStore: typeof getLanguageStore;
  }
}

window.getLanguageStore = getLanguageStore;

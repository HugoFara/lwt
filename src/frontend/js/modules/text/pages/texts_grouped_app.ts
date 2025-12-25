/**
 * Texts Grouped App - Alpine.js component for grouped texts by language.
 *
 * This component manages:
 * - Collapsible language sections with state persistence
 * - Lazy loading of texts per language
 * - Per-language pagination with "Show More"
 * - Per-language bulk selection and actions
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';
import { apiGet } from '@shared/api/client';
import { TextsApi } from '@modules/text/api/texts_api';
import { confirmDelete } from '@shared/utils/ui_utilities';
import { renderTags, renderStatusBarChart } from '@shared/utils/html_utils';

const STORAGE_KEY = 'lwt_collapsed_languages';

/**
 * Language with text count from API.
 */
interface LanguageWithTexts {
  id: number;
  name: string;
  text_count: number;
}

/**
 * Text item from API.
 */
interface TextItem {
  id: number;
  title: string;
  has_audio: boolean;
  source_uri: string;
  has_source: boolean;
  annotated: boolean;
  taglist: string;
}

/**
 * Text statistics from API.
 */
interface TextStats {
  total: number;
  saved: number;
  unknown: number;
  unknownPercent: number;
  statusCounts: Record<string, number>;
}

/**
 * Pagination info from API.
 */
interface PaginationInfo {
  current_page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

/**
 * State for texts within a single language section.
 */
interface LanguageTextsState {
  texts: TextItem[];
  stats: Map<number, TextStats>;
  pagination: PaginationInfo;
  loading: boolean;
  marked: Set<number>;
}

/**
 * Response from languages/with-texts API.
 */
interface LanguagesWithTextsResponse {
  languages: LanguageWithTexts[];
}

/**
 * Response from texts/by-language API.
 */
interface TextsByLanguageResponse {
  texts: TextItem[];
  pagination: PaginationInfo;
}

/**
 * Alpine.js component data interface.
 */
/**
 * Page configuration from PHP.
 */
interface PageConfig {
  activeLanguageId: number;
  statuses?: Record<string, unknown>;
}

export interface TextsGroupedData {
  // State
  loading: boolean;
  languages: LanguageWithTexts[];
  collapsedLanguages: number[];
  languageStates: Map<number, LanguageTextsState>;
  sort: number;
  activeLanguageId: number;

  // Lifecycle
  init(): Promise<void>;

  // Data loading
  loadLanguages(): Promise<void>;
  loadTextsForLanguage(langId: number, page?: number): Promise<void>;
  loadStatisticsForTexts(langId: number, textIds: number[]): Promise<void>;

  // Collapse state
  isCollapsed(langId: number): boolean;
  toggleLanguage(langId: number): Promise<void>;
  saveCollapseState(): void;
  loadCollapseState(): void;
  initializeDefaultCollapseState(): void;

  // Text operations
  getTextsForLanguage(langId: number): TextItem[];
  getStatsForText(langId: number, textId: number): TextStats | undefined;
  hasMoreTexts(langId: number): boolean;
  loadMoreTexts(langId: number): Promise<void>;
  isLoadingMore(langId: number): boolean;

  // Selection
  markAll(langId: number, checked: boolean): void;
  toggleMark(langId: number, textId: number, checked: boolean): void;
  isMarked(langId: number, textId: number): boolean;
  hasMarkedInLanguage(langId: number): boolean;
  getMarkedIds(langId: number): number[];
  getMarkedCount(langId: number): number;

  // Actions
  handleMultiAction(langId: number, event: Event): void;
  handleDelete(event: Event, url: string): void;

  // Sorting
  handleSortChange(event: Event): void;

  // Utility
  renderTags(tagList: string): string;
  renderStatusChart(langId: number, textId: number): string;
}

/**
 * Create the texts grouped app Alpine.js component.
 */
/**
 * Read page configuration from the embedded JSON script tag.
 */
function getPageConfig(): PageConfig {
  const configEl = document.getElementById('texts-grouped-config');
  if (configEl) {
    try {
      return JSON.parse(configEl.textContent || '{}');
    } catch {
      // Invalid JSON
    }
  }
  return { activeLanguageId: 0 };
}

export function textsGroupedData(): TextsGroupedData {
  const config = getPageConfig();

  return {
    loading: true,
    languages: [],
    collapsedLanguages: [],
    languageStates: new Map(),
    sort: 1,
    activeLanguageId: config.activeLanguageId,

    async init() {
      this.loadCollapseState();
      await this.loadLanguages();

      // If no stored collapse state, collapse all except active language
      if (!localStorage.getItem(STORAGE_KEY)) {
        this.initializeDefaultCollapseState();
      }

      // Load texts for expanded languages (up to first 3)
      let loadedCount = 0;
      for (const lang of this.languages) {
        if (!this.isCollapsed(lang.id) && loadedCount < 3) {
          await this.loadTextsForLanguage(lang.id);
          loadedCount++;
        }
      }

      this.loading = false;

      // Refresh icons after render
      setTimeout(() => {
        createIcons({ icons });
      }, 0);
    },

    async loadLanguages() {
      const response = await apiGet<LanguagesWithTextsResponse>(
        '/languages/with-texts'
      );
      if (response.data) {
        this.languages = response.data.languages;
        // Initialize state for each language
        for (const lang of this.languages) {
          this.languageStates.set(lang.id, {
            texts: [],
            stats: new Map(),
            pagination: {
              current_page: 0,
              per_page: 10,
              total: lang.text_count,
              total_pages: Math.ceil(lang.text_count / 10)
            },
            loading: false,
            marked: new Set()
          });
        }
      }
    },

    async loadTextsForLanguage(langId: number, page: number = 1) {
      const state = this.languageStates.get(langId);
      if (!state) return;

      state.loading = true;

      const response = await apiGet<TextsByLanguageResponse>(
        `/texts/by-language/${langId}`,
        { page, per_page: 10, sort: this.sort }
      );

      if (response.data) {
        if (page === 1) {
          state.texts = response.data.texts;
        } else {
          state.texts.push(...response.data.texts);
        }
        state.pagination = response.data.pagination;

        // Load statistics for the new texts
        const textIds = response.data.texts.map((t) => t.id);
        if (textIds.length > 0) {
          await this.loadStatisticsForTexts(langId, textIds);
        }
      }

      state.loading = false;

      // Refresh icons
      setTimeout(() => {
        createIcons({ icons });
      }, 0);
    },

    async loadStatisticsForTexts(langId: number, textIds: number[]) {
      if (textIds.length === 0) return;

      const state = this.languageStates.get(langId);
      if (!state) return;

      const response = await TextsApi.getStatistics(textIds);
      if (response.data) {
        // The statistics API returns data keyed by text ID
        const statsData = response.data as unknown as Record<string, TextStats>;
        for (const [textIdStr, stats] of Object.entries(statsData)) {
          const textId = parseInt(textIdStr, 10);
          state.stats.set(textId, stats);
        }
      }
    },

    // Collapse state management
    isCollapsed(langId: number): boolean {
      return this.collapsedLanguages.includes(langId);
    },

    async toggleLanguage(langId: number) {
      const index = this.collapsedLanguages.indexOf(langId);
      if (index > -1) {
        // Expanding
        this.collapsedLanguages.splice(index, 1);
        // Load texts when expanding if not loaded
        const state = this.languageStates.get(langId);
        if (state && state.texts.length === 0) {
          await this.loadTextsForLanguage(langId);
        }
      } else {
        // Collapsing
        this.collapsedLanguages.push(langId);
      }
      this.saveCollapseState();

      // Refresh icons
      setTimeout(() => {
        createIcons({ icons });
      }, 0);
    },

    saveCollapseState() {
      try {
        localStorage.setItem(
          STORAGE_KEY,
          JSON.stringify(this.collapsedLanguages)
        );
      } catch {
        // localStorage unavailable
      }
    },

    loadCollapseState() {
      try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
          this.collapsedLanguages = JSON.parse(stored);
        }
      } catch {
        this.collapsedLanguages = [];
      }
    },

    initializeDefaultCollapseState() {
      // Collapse all languages except the active one
      this.collapsedLanguages = this.languages
        .filter((lang) => lang.id !== this.activeLanguageId)
        .map((lang) => lang.id);
      this.saveCollapseState();
    },

    // Text operations
    getTextsForLanguage(langId: number): TextItem[] {
      return this.languageStates.get(langId)?.texts ?? [];
    },

    getStatsForText(langId: number, textId: number): TextStats | undefined {
      return this.languageStates.get(langId)?.stats.get(textId);
    },

    hasMoreTexts(langId: number): boolean {
      const state = this.languageStates.get(langId);
      if (!state) return false;
      return state.pagination.current_page < state.pagination.total_pages;
    },

    async loadMoreTexts(langId: number) {
      const state = this.languageStates.get(langId);
      if (!state || state.loading) return;
      await this.loadTextsForLanguage(langId, state.pagination.current_page + 1);
    },

    isLoadingMore(langId: number): boolean {
      return this.languageStates.get(langId)?.loading ?? false;
    },

    // Selection
    markAll(langId: number, checked: boolean) {
      const state = this.languageStates.get(langId);
      if (!state) return;

      if (checked) {
        state.texts.forEach((t) => state.marked.add(t.id));
      } else {
        state.marked.clear();
      }
    },

    toggleMark(langId: number, textId: number, checked: boolean) {
      const state = this.languageStates.get(langId);
      if (!state) return;

      if (checked) {
        state.marked.add(textId);
      } else {
        state.marked.delete(textId);
      }
    },

    isMarked(langId: number, textId: number): boolean {
      return this.languageStates.get(langId)?.marked.has(textId) ?? false;
    },

    hasMarkedInLanguage(langId: number): boolean {
      const state = this.languageStates.get(langId);
      return state ? state.marked.size > 0 : false;
    },

    getMarkedIds(langId: number): number[] {
      const state = this.languageStates.get(langId);
      return state ? Array.from(state.marked) : [];
    },

    getMarkedCount(langId: number): number {
      return this.languageStates.get(langId)?.marked.size ?? 0;
    },

    // Actions
    handleMultiAction(langId: number, event: Event) {
      const select = event.target as HTMLSelectElement;
      const action = select.value;
      if (!action) return;

      const markedIds = this.getMarkedIds(langId);
      if (markedIds.length === 0) {
        select.value = '';
        return;
      }

      // Create a temporary form with the marked IDs
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '/texts';

      // Add marked text IDs
      markedIds.forEach((id) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'marked[]';
        input.value = String(id);
        form.appendChild(input);
      });

      // Add action
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'markaction';
      actionInput.value = action;
      form.appendChild(actionInput);

      // Handle special actions that need confirmation
      if (action === 'del') {
        if (!confirmDelete()) {
          select.value = '';
          return;
        }
      }

      // Submit form
      document.body.appendChild(form);
      form.submit();
    },

    handleDelete(event: Event, url: string) {
      event.preventDefault();
      if (confirmDelete()) {
        window.location.href = url;
      }
    },

    // Sorting
    handleSortChange(event: Event) {
      const select = event.target as HTMLSelectElement;
      this.sort = parseInt(select.value, 10) || 1;

      // Reload all loaded languages with new sort
      for (const lang of this.languages) {
        const state = this.languageStates.get(lang.id);
        if (state && state.texts.length > 0) {
          state.texts = [];
          state.stats.clear();
          state.pagination.current_page = 0;
          if (!this.isCollapsed(lang.id)) {
            this.loadTextsForLanguage(lang.id);
          }
        }
      }
    },

    // Utility - render tags as Bulma components
    renderTags(tagList: string): string {
      return renderTags(tagList);
    },

    // Utility - render status bar chart
    renderStatusChart(langId: number, textId: number): string {
      const stats = this.getStatsForText(langId, textId);
      return renderStatusBarChart(stats);
    }
  };
}

/**
 * Initialize the texts grouped app Alpine.js component.
 */
export function initTextsGroupedAlpine(): void {
  Alpine.data('textsGroupedApp', textsGroupedData);
}

// Expose for global access
declare global {
  interface Window {
    textsGroupedData: typeof textsGroupedData;
  }
}

window.textsGroupedData = textsGroupedData;

// Register Alpine data component immediately (before Alpine.start() in main.ts)
initTextsGroupedAlpine();

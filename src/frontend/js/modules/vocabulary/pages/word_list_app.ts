/**
 * Word List App - Alpine.js component for word/term management.
 *
 * This component provides a full reactive SPA for:
 * - Filtered, paginated word list with sorting
 * - Bulk selection and actions
 * - Inline editing of translations and romanizations
 * - Mobile-responsive table/card views
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { initIcons } from '@shared/icons/lucide_icons';
import {
  WordsApi,
  type WordItem,
  type PaginationInfo,
  type FilterOptions,
  type WordListFilters
} from '@modules/vocabulary/api/words_api';

const STORAGE_KEY = 'lwt_word_list_filters';

/**
 * Page configuration from PHP.
 */
interface PageConfig {
  activeLanguageId: number;
  perPage: number;
}

/**
 * Editing state for inline edit.
 */
interface EditingState {
  id: number;
  field: 'translation' | 'romanization';
}

/**
 * Alpine.js component data interface.
 */
export interface WordListData {
  // State
  loading: boolean;
  words: WordItem[];
  filters: WordListFilters;
  pagination: PaginationInfo;
  filterOptions: FilterOptions;
  marked: Set<number>;

  // Inline edit state
  editingWord: EditingState | null;
  editValue: string;
  editSaving: boolean;

  // Lifecycle
  init(): Promise<void>;

  // Data loading
  loadWords(): Promise<void>;
  loadFilterOptions(): Promise<void>;

  // Filter methods
  setFilter(key: keyof WordListFilters, value: unknown): void;
  resetFilters(): void;
  loadFilterState(): void;
  saveFilterState(): void;

  // Pagination
  goToPage(page: number): Promise<void>;

  // Selection
  markAll(checked: boolean): void;
  toggleMark(wordId: number, checked: boolean): void;
  isMarked(wordId: number): boolean;
  getMarkedIds(): number[];
  getMarkedCount(): number;

  // Bulk actions
  handleMultiAction(event: Event): Promise<void>;
  handleAllAction(event: Event): Promise<void>;

  // Inline edit
  startEdit(wordId: number, field: 'translation' | 'romanization'): void;
  saveEdit(): Promise<void>;
  cancelEdit(): void;
  isEditing(wordId: number, field: 'translation' | 'romanization'): boolean;

  // Helpers
  formatScore(score: number): string;
  getStatusClass(status: number): string;
  getDisplayValue(word: WordItem, field: 'translation' | 'romanization'): string;

  // Page title
  updatePageTitle(): void;
  getSelectedLanguageName(): string;
}

/**
 * Read page configuration from the embedded JSON script tag.
 */
function getPageConfig(): PageConfig {
  const configEl = document.getElementById('word-list-config');
  if (configEl) {
    try {
      return JSON.parse(configEl.textContent || '{}');
    } catch {
      // Invalid JSON
    }
  }
  return { activeLanguageId: 0, perPage: 50 };
}

/**
 * Create the word list app Alpine.js component.
 */
export function wordListData(): WordListData {
  const config = getPageConfig();

  return {
    loading: true,
    words: [],
    filters: {
      lang: config.activeLanguageId || null,
      text_id: null,
      status: '',
      query: '',
      query_mode: 'term,rom,transl',
      regex_mode: '',
      tag1: null,
      tag2: null,
      tag12: 0,
      sort: 1,
      page: 1,
      per_page: config.perPage || 50
    },
    pagination: {
      page: 1,
      per_page: config.perPage || 50,
      total: 0,
      total_pages: 0
    },
    filterOptions: {
      languages: [],
      texts: [],
      tags: [],
      statuses: [],
      sorts: []
    },
    marked: new Set(),

    editingWord: null,
    editValue: '',
    editSaving: false,

    async init() {
      this.loadFilterState();
      await this.loadFilterOptions();
      await this.loadWords();
      this.loading = false;

      // Refresh icons after render
      setTimeout(() => {
        initIcons();
      }, 0);
    },

    async loadWords() {
      const response = await WordsApi.getList(this.filters);

      if (response.data) {
        this.words = response.data.words;
        this.pagination = response.data.pagination;
        // Update filters with actual page from response
        this.filters.page = response.data.pagination.page;
      }

      // Refresh icons
      setTimeout(() => {
        initIcons();
      }, 0);
    },

    async loadFilterOptions() {
      const langId =
        this.filters.lang !== null && this.filters.lang !== ''
          ? Number(this.filters.lang)
          : null;
      const response = await WordsApi.getFilterOptions(langId);

      if (response.data) {
        this.filterOptions = response.data;
      }
    },

    setFilter(key, value) {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      (this.filters as any)[key] = value;

      // Reset to page 1 when filter changes (except for page changes)
      if (key !== 'page') {
        this.filters.page = 1;
        this.marked.clear();
      }

      this.saveFilterState();
      this.loadWords();

      // Reload filter options when language changes (to update texts list)
      if (key === 'lang') {
        this.filters.text_id = null;
        this.loadFilterOptions();
      }
    },

    resetFilters() {
      const config = getPageConfig();
      this.filters = {
        lang: null,
        text_id: null,
        status: '',
        query: '',
        query_mode: 'term,rom,transl',
        regex_mode: '',
        tag1: null,
        tag2: null,
        tag12: 0,
        sort: 1,
        page: 1,
        per_page: config.perPage || 50
      };
      this.marked.clear();

      try {
        localStorage.removeItem(STORAGE_KEY);
      } catch {
        // localStorage unavailable
      }

      this.loadFilterOptions();
      this.loadWords();
    },

    loadFilterState() {
      // First check URL params
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('lang')) {
        const langParam = urlParams.get('lang');
        this.filters.lang = langParam ? parseInt(langParam, 10) : null;
      }

      // Then check localStorage for other filters
      try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
          const parsed = JSON.parse(stored);
          // Merge stored filters with current (URL params take precedence)
          this.filters = { ...this.filters, ...parsed };
          // But keep URL lang param if present
          if (urlParams.has('lang')) {
            const langParam = urlParams.get('lang');
            this.filters.lang = langParam ? parseInt(langParam, 10) : null;
          }
        }
      } catch {
        // localStorage unavailable or invalid JSON
      }
    },

    saveFilterState() {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this.filters));
      } catch {
        // localStorage unavailable
      }
    },

    async goToPage(page: number) {
      if (page < 1 || page > this.pagination.total_pages) return;
      this.setFilter('page', page);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    markAll(checked: boolean) {
      if (checked) {
        this.words.forEach((w) => this.marked.add(w.id));
      } else {
        this.marked.clear();
      }
    },

    toggleMark(wordId: number, checked: boolean) {
      if (checked) {
        this.marked.add(wordId);
      } else {
        this.marked.delete(wordId);
      }
    },

    isMarked(wordId: number): boolean {
      return this.marked.has(wordId);
    },

    getMarkedIds(): number[] {
      return Array.from(this.marked);
    },

    getMarkedCount(): number {
      return this.marked.size;
    },

    async handleMultiAction(event: Event) {
      const select = event.target as HTMLSelectElement;
      const action = select.value;
      if (!action) return;

      const ids = this.getMarkedIds();
      if (ids.length === 0) {
        alert('No terms selected');
        select.value = '';
        return;
      }

      let data: string | undefined;

      // Handle actions that need extra data
      if (action === 'addtag' || action === 'deltag') {
        const tag = prompt('Enter tag (max 20 chars, no spaces/commas):');
        if (!tag) {
          select.value = '';
          return;
        }
        if (tag.includes(' ') || tag.includes(',') || tag.length > 20) {
          alert('Invalid tag: must be <= 20 chars with no spaces or commas');
          select.value = '';
          return;
        }
        data = tag;
      }

      // Confirm destructive actions
      if (action === 'del') {
        if (!confirm(`Delete ${ids.length} term(s)? This cannot be undone.`)) {
          select.value = '';
          return;
        }
      }

      // Handle test action - redirect to test page
      if (action === 'test') {
        const testUrl = `/test?selection=${ids.join(',')}`;
        window.location.href = testUrl;
        return;
      }

      // Handle export actions - these need form submission
      if (action === 'exp' || action === 'expann' || action === 'exptsv') {
        this.submitExportForm(action, ids);
        select.value = '';
        return;
      }

      const response = await WordsApi.bulkAction(ids, action, data);

      if (response.data?.success) {
        this.marked.clear();
        await this.loadWords();
      } else {
        alert(response.data?.message || response.error || 'Action failed');
      }

      select.value = '';
    },

    async handleAllAction(event: Event) {
      const select = event.target as HTMLSelectElement;
      const action = select.value;
      if (!action) return;

      if (
        !confirm(
          `Apply action to ALL ${this.pagination.total} filtered term(s)?`
        )
      ) {
        select.value = '';
        return;
      }

      let data: string | undefined;

      if (action.endsWith('addtag') || action.endsWith('deltag')) {
        const tag = prompt('Enter tag:');
        if (!tag) {
          select.value = '';
          return;
        }
        data = tag;
      }

      // Strip 'all' prefix for action codes
      const actionCode = action.replace(/^all/, '');

      const response = await WordsApi.allAction(this.filters, actionCode, data);

      if (response.data?.success) {
        await this.loadWords();
      } else {
        alert(response.data?.message || response.error || 'Action failed');
      }

      select.value = '';
    },

    startEdit(wordId: number, field: 'translation' | 'romanization') {
      const word = this.words.find((w) => w.id === wordId);
      if (!word) return;

      this.editingWord = { id: wordId, field };

      // Get current value
      const currentValue = field === 'translation' ? word.translation : word.romanization;
      this.editValue = currentValue === '*' ? '' : currentValue;

      // Focus the textarea after render
      setTimeout(() => {
        const textarea = document.querySelector(
          `[data-edit-id="${wordId}"][data-edit-field="${field}"]`
        ) as HTMLTextAreaElement;
        if (textarea) {
          textarea.focus();
          textarea.select();
        }
      }, 0);
    },

    async saveEdit() {
      if (!this.editingWord) return;

      this.editSaving = true;
      const { id, field } = this.editingWord;

      const response = await WordsApi.inlineEdit(id, field, this.editValue);

      if (response.data?.success) {
        // Update the word in the list
        const word = this.words.find((w) => w.id === id);
        if (word) {
          if (field === 'translation') {
            word.translation = response.data.value;
          } else {
            word.romanization = response.data.value;
          }
        }
      } else {
        alert(response.data?.error || response.error || 'Save failed');
      }

      this.editingWord = null;
      this.editValue = '';
      this.editSaving = false;
    },

    cancelEdit() {
      this.editingWord = null;
      this.editValue = '';
    },

    isEditing(wordId: number, field: 'translation' | 'romanization'): boolean {
      return (
        this.editingWord !== null &&
        this.editingWord.id === wordId &&
        this.editingWord.field === field
      );
    },

    formatScore(score: number): string {
      if (score < 0) return '0%';
      return Math.floor(score) + '%';
    },

    getStatusClass(status: number): string {
      if (status === 99) return 'is-info';
      if (status === 98) return 'is-light';
      if (status >= 5) return 'is-success';
      if (status >= 3) return 'is-warning';
      return 'is-danger';
    },

    getDisplayValue(
      word: WordItem,
      field: 'translation' | 'romanization'
    ): string {
      const value = field === 'translation' ? word.translation : word.romanization;
      return value || '*';
    },

    /**
     * Get the name of the currently selected language.
     */
    getSelectedLanguageName(): string {
      if (!this.filters.lang) {
        return '';
      }
      const langId = Number(this.filters.lang);
      const lang = this.filterOptions.languages.find((l) => l.id === langId);
      return lang ? lang.name : '';
    },

    /**
     * Update the page title (h1) and document title based on selected language.
     */
    updatePageTitle(): void {
      const langName = this.getSelectedLanguageName();
      const title = langName ? `${langName} Terms` : 'Terms';

      // Update the h1 element
      const h1 = document.querySelector('h1');
      if (h1) {
        // Preserve any debug span that might be present
        const debugSpan = h1.querySelector('.red');
        h1.textContent = title;
        if (debugSpan) {
          h1.appendChild(document.createTextNode(' '));
          h1.appendChild(debugSpan);
        }
      }

      // Update the document title
      document.title = `LWT :: ${title}`;
    },

    // Helper method to create and submit export form
    submitExportForm(action: string, ids: number[]) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '/words';

      // Add marked IDs
      ids.forEach((id) => {
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

      document.body.appendChild(form);
      form.submit();
    }
  } as WordListData & { submitExportForm: (action: string, ids: number[]) => void };
}

/**
 * Initialize the word list app Alpine.js component.
 */
export function initWordListAlpine(): void {
  Alpine.data('wordListApp', wordListData);
}

// Expose for global access
declare global {
  interface Window {
    wordListData: typeof wordListData;
  }
}

window.wordListData = wordListData;

// Register Alpine data component immediately (before Alpine.start() in main.ts)
initWordListAlpine();

/**
 * Word Upload Module - Alpine.js component for word import.
 *
 * Handles word import form, import mode selection, and paginated
 * display of imported terms.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0 Extracted from PHP inline scripts
 * @since   3.1.0 Migrated to Alpine.js component
 */

import Alpine from 'alpinejs';
import { escapeHtml, renderTags } from '@shared/utils/html_utils';
import { statuses } from '@shared/stores/app_data';
import { iconHtml } from '@shared/icons/icons';

// Interface for imported term record
interface ImportedTerm {
  WoID: number;
  WoText: string;
  WoTranslation: string;
  WoRomanization: string;
  WoSentence: string;
  WoStatus: number;
  SentOK: number;
  taglist: string;
}

// Interface for navigation data
interface NavigationData {
  current_page: number;
  total_pages: number;
}

// Interface for API response
interface ImportedTermsResponse {
  navigation: NavigationData;
  terms: ImportedTerm[];
}

/**
 * Configuration for upload result view.
 */
export interface UploadResultConfig {
  lastUpdate: string;
  rtl: boolean;
  recno: number;
}

/**
 * Column label/example maps for preview.
 */
const COL_LABELS: Record<string, string> = {
  w: 'Term', t: 'Translation', r: 'Romanization', s: 'Sentence', g: 'Tags'
};
const COL_EXAMPLES: Record<string, string> = {
  w: 'Haus', t: 'house', r: 'haus', s: 'Das Haus ist gross.', g: 'A1 housing'
};

/**
 * Page config read from JSON script tag.
 */
interface PageConfig {
  activeTab?: string;
  currentLanguageId?: number;
  currentLanguageName?: string;
}

/**
 * Page-level wrapper component for language + input method tab state.
 *
 * Reads initial config from JSON config script tag.
 */
export function wordUploadPageApp() {
  const configEl = document.getElementById('word-upload-page-config');
  let cfg: PageConfig = {};
  if (configEl) {
    try {
      cfg = JSON.parse(configEl.textContent || '{}');
    } catch {
      // use default
    }
  }

  return {
    inputMethod: cfg.activeTab || 'dictionary',
    selectedLanguageId: cfg.currentLanguageId || 0,
    selectedLanguageName: cfg.currentLanguageName || '',

    setInputMethod(method: string): void {
      this.inputMethod = method;
    },

    selectLanguage(id: number, name: string): void {
      this.selectedLanguageId = id;
      this.selectedLanguageName = name;
    },

    get isDictionary(): boolean {
      return this.inputMethod === 'dictionary';
    },

    get isNotDictionary(): boolean {
      return this.inputMethod !== 'dictionary';
    }
  };
}

/**
 * Alpine.js component for the word upload form.
 *
 * Manages import mode, column assignment, delimiter, and dictionary format.
 */
export function wordUploadFormApp() {
  return {
    importMode: '0',
    showDelimiter: false,
    delimiter: 'c',
    cols: ['w', 't', 'x', 'x', 'x'] as string[],
    extraCols: 0,
    dictFormat: 'csv',
    dictFileName: '',

    updateImportMode(event: Event): void {
      const val = (event.target as HTMLSelectElement).value;
      this.importMode = val;
      this.showDelimiter = val === '4' || val === '5';
    },

    updateDictFileName(event: Event): void {
      const input = event.target as HTMLInputElement;
      this.dictFileName = input.files?.[0]?.name || '';
    },

    previewHeaders(): string[] {
      return this.cols.map(c => COL_LABELS[c]).filter(Boolean);
    },

    previewRow(): string[] {
      return this.cols.map(c => COL_EXAMPLES[c]).filter(Boolean);
    },

    hasPreview(): boolean {
      return this.previewHeaders().length > 0;
    },

    addColumn(): void {
      if (this.extraCols < 3) {
        this.extraCols++;
      }
    },

    removeColumn(): void {
      if (this.extraCols > 0) {
        this.cols[1 + this.extraCols] = 'x';
        this.extraCols--;
      }
    },

    showExtraCol(n: number): boolean {
      return this.extraCols >= n;
    },

    get dictFileLabel(): string {
      return this.dictFileName || 'No file selected';
    },

    get showDictCsvOptions(): boolean {
      // inputMethod inherited from parent wordUploadPageApp scope
      return (this as Record<string, unknown>).inputMethod === 'dictionary'
        && this.dictFormat === 'csv';
    },

    get showNonDictOptions(): boolean {
      return (this as Record<string, unknown>).inputMethod !== 'dictionary';
    }
  };
}

/**
 * Word upload result Alpine component data interface.
 */
export interface WordUploadResultData {
  // Config
  lastUpdate: string;
  rtl: boolean;
  recno: number;

  // State
  currentPage: number;
  totalPages: number;
  terms: ImportedTerm[];
  isLoading: boolean;
  hasTerms: boolean;

  // Methods
  init(): void;
  loadPage(page: number): Promise<void>;
  goToPage(page: number): void;
  goFirst(): void;
  goPrev(): void;
  goNext(): void;
  goLast(): void;
  formatTermRow(term: ImportedTerm): string;
  getStatusInfo(status: number): { name: string; abbr: string };
  setTableBodyHtml(el: HTMLElement): void;
}

/**
 * Alpine.js component for word upload result display.
 */
export function wordUploadResultApp(config: UploadResultConfig = { lastUpdate: '', rtl: false, recno: 0 }): WordUploadResultData {
  return {
    // Config
    lastUpdate: config.lastUpdate,
    rtl: config.rtl,
    recno: config.recno,

    // State
    currentPage: 1,
    totalPages: 1,
    terms: [],
    isLoading: false,
    hasTerms: false,

    /**
     * Initialize the component.
     */
    init(): void {
      // Read config from JSON script tag if available
      const configEl = document.querySelector<HTMLScriptElement>('script[data-lwt-upload-result-config]');
      if (configEl) {
        try {
          const jsonConfig = JSON.parse(configEl.textContent || '{}') as UploadResultConfig;
          this.lastUpdate = jsonConfig.lastUpdate ?? this.lastUpdate;
          this.rtl = jsonConfig.rtl ?? this.rtl;
          this.recno = jsonConfig.recno ?? this.recno;
        } catch {
          // Invalid JSON, use defaults
        }
      }

      this.hasTerms = this.recno > 0;
      if (this.hasTerms) {
        this.loadPage(1);
      }
    },

    /**
     * Load a page of imported terms.
     */
    async loadPage(page: number): Promise<void> {
      if (this.recno === 0) {
        this.hasTerms = false;
        return;
      }

      this.isLoading = true;

      const params = new URLSearchParams({
        last_update: this.lastUpdate,
        count: String(this.recno),
        page: String(page)
      });

      try {
        const response = await fetch('/api/v1/terms/imported?' + params.toString());
        if (!response.ok) {
          throw new Error(`HTTP error: ${response.status}`);
        }
        const data: ImportedTermsResponse = await response.json();

        this.currentPage = data.navigation.current_page;
        this.totalPages = data.navigation.total_pages;
        this.terms = data.terms;
        this.hasTerms = true;
      } catch (error) {
        console.error('Failed to fetch imported terms:', error);
        this.hasTerms = false;
      } finally {
        this.isLoading = false;
      }
    },

    /**
     * Navigate to a specific page.
     */
    goToPage(page: number): void {
      if (page >= 1 && page <= this.totalPages) {
        this.loadPage(page);
      }
    },

    /**
     * Go to first page.
     */
    goFirst(): void {
      this.goToPage(1);
    },

    /**
     * Go to previous page.
     */
    goPrev(): void {
      this.goToPage(this.currentPage - 1);
    },

    /**
     * Go to next page.
     */
    goNext(): void {
      this.goToPage(this.currentPage + 1);
    },

    /**
     * Go to last page.
     */
    goLast(): void {
      this.goToPage(this.totalPages);
    },

    /**
     * Format a term row as HTML.
     */
    formatTermRow(term: ImportedTerm): string {
      const statusInfo = this.getStatusInfo(term.WoStatus);

      return `<tr>
        <td>
          <span${this.rtl ? ' dir="rtl"' : ''}>${escapeHtml(term.WoText)}</span>
          <span class="has-text-grey"> / </span>
          <span id="roman${term.WoID}" class="edit_area clickedit has-text-grey-dark">${term.WoRomanization !== '' ? escapeHtml(term.WoRomanization) : '*'}</span>
        </td>
        <td>
          <span id="trans${term.WoID}" class="edit_area clickedit">${escapeHtml(term.WoTranslation)}</span>
        </td>
        <td>
          <span class="tags">${renderTags(term.taglist)}</span>
        </td>
        <td class="has-text-centered">
          ${term.SentOK !== 0
    ? iconHtml('check', { title: escapeHtml(term.WoSentence), alt: 'Yes', className: 'has-text-success' })
    : iconHtml('x', { title: '(No valid sentence)', alt: 'No', className: 'has-text-danger' })
}
        </td>
        <td class="has-text-centered" title="${escapeHtml(statusInfo.name)}">
          <span class="tag is-light">${escapeHtml(statusInfo.abbr)}</span>
        </td>
      </tr>`;
    },

    /**
     * Get status info for a status code.
     */
    getStatusInfo(status: number): { name: string; abbr: string } {
      return statuses[status] || { name: 'Unknown', abbr: '?' };
    },

    /**
     * Set table body HTML (CSP-compatible - use with x-effect)
     */
    setTableBodyHtml(el: HTMLElement): void {
      el.innerHTML = this.terms.map(term => this.formatTermRow(term)).join('');
    }
  };
}

/**
 * A curated dictionary source entry.
 */
interface CuratedDictSource {
  name: string;
  url: string;
  format: string;
  entries: string;
  license: string;
  notes: string;
}

/**
 * A curated dictionary language group.
 */
interface CuratedDictGroup {
  language: string;
  languageName: string;
  sources: CuratedDictSource[];
}

/**
 * Find a curated dictionary group matching a user language name.
 *
 * Handles cases like "Chinese (Simplified)" matching "Chinese",
 * or "French" matching "French".
 */
function findGroupByLanguageName(
  groups: CuratedDictGroup[],
  langName: string
): CuratedDictGroup | undefined {
  const lower = langName.toLowerCase();
  if (!lower) return undefined;
  // Exact match first
  const exact = groups.find(g => g.languageName.toLowerCase() === lower);
  if (exact) return exact;
  // User name starts with curated name ("Chinese (Simplified)" → "Chinese")
  const prefix = groups.find(g => lower.startsWith(g.languageName.toLowerCase()));
  if (prefix) return prefix;
  // Curated name starts with user name
  return groups.find(g => g.languageName.toLowerCase().startsWith(lower));
}

/**
 * Alpine.js component for browsing curated dictionaries.
 */
export function curatedDictBrowser() {
  const configEl = document.getElementById('curated-dictionaries-config');
  let allGroups: CuratedDictGroup[] = [];
  if (configEl) {
    try {
      allGroups = JSON.parse(configEl.textContent || '[]');
    } catch {
      // Invalid JSON, use empty
    }
  }

  return {
    allGroups,
    dictLanguageFilter: '',
    dictSearch: '',

    /**
     * Initialize: sync filter with parent's selectedLanguageName.
     */
    init(): void {
      // Read selectedLanguageName from parent scope (wordUploadPageApp)
      const langName = (this as Record<string, unknown>).selectedLanguageName as string || '';
      const match = findGroupByLanguageName(this.allGroups, langName);
      if (match) {
        this.dictLanguageFilter = match.language;
      }

      // Watch for language changes from parent tabs
      (this as unknown as { $watch: (prop: string, cb: (val: string) => void) => void }).$watch('selectedLanguageName', (name: string) => {
        const found = findGroupByLanguageName(this.allGroups, name);
        this.dictLanguageFilter = found ? found.language : '';
      });
    },

    get filteredGroups(): CuratedDictGroup[] {
      let groups = this.allGroups;

      if (this.dictLanguageFilter) {
        groups = groups.filter(g => g.language === this.dictLanguageFilter);
      }

      const search = this.dictSearch.toLowerCase().trim();
      if (search) {
        groups = groups
          .map(g => ({
            ...g,
            sources: g.sources.filter(
              s =>
                s.name.toLowerCase().includes(search) ||
                s.format.toLowerCase().includes(search) ||
                s.notes.toLowerCase().includes(search)
            )
          }))
          .filter(g => g.sources.length > 0);
      }

      return groups;
    }
  };
}

// Register Alpine components
if (typeof Alpine !== 'undefined') {
  Alpine.data('wordUploadPageApp', wordUploadPageApp);
  Alpine.data('wordUploadFormApp', wordUploadFormApp);
  Alpine.data('wordUploadResultApp', wordUploadResultApp);
  Alpine.data('curatedDictBrowser', curatedDictBrowser);
}

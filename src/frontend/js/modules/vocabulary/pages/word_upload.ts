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
import { escape_html_chars, renderTags } from '@shared/utils/html_utils';
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
 * Configuration for word upload form component.
 */
export interface WordUploadFormConfig {
  initialMode?: number;
}

/**
 * Word upload form Alpine component data interface.
 */
export interface WordUploadFormData {
  importMode: number;
  showDelimiter: boolean;

  init(): void;
  updateImportMode(value: number | string): void;
}

/**
 * Alpine.js component for word upload form.
 */
export function wordUploadFormApp(config: WordUploadFormConfig = {}): WordUploadFormData {
  return {
    importMode: config.initialMode ?? 0,
    showDelimiter: false,

    init(): void {
      this.showDelimiter = this.importMode > 3;
    },

    updateImportMode(value: number | string): void {
      const numValue = typeof value === 'string' ? parseInt(value, 10) : value;
      this.importMode = numValue;
      this.showDelimiter = numValue > 3;
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
        const response = await fetch('api.php/v1/terms/imported?' + params.toString());
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
          <span${this.rtl ? ' dir="rtl"' : ''}>${escape_html_chars(term.WoText)}</span>
          <span class="has-text-grey"> / </span>
          <span id="roman${term.WoID}" class="edit_area clickedit has-text-grey-dark">${term.WoRomanization !== '' ? escape_html_chars(term.WoRomanization) : '*'}</span>
        </td>
        <td>
          <span id="trans${term.WoID}" class="edit_area clickedit">${escape_html_chars(term.WoTranslation)}</span>
        </td>
        <td>
          <span class="tags">${renderTags(term.taglist)}</span>
        </td>
        <td class="has-text-centered">
          ${term.SentOK !== 0
    ? iconHtml('check', { title: escape_html_chars(term.WoSentence), alt: 'Yes', className: 'has-text-success' })
    : iconHtml('x', { title: '(No valid sentence)', alt: 'No', className: 'has-text-danger' })
}
        </td>
        <td class="has-text-centered" title="${escape_html_chars(statusInfo.name)}">
          <span class="tag is-light">${escape_html_chars(statusInfo.abbr)}</span>
        </td>
      </tr>`;
    },

    /**
     * Get status info for a status code.
     */
    getStatusInfo(status: number): { name: string; abbr: string } {
      return statuses[status] || { name: 'Unknown', abbr: '?' };
    }
  };
}

// Register Alpine components
if (typeof Alpine !== 'undefined') {
  Alpine.data('wordUploadFormApp', wordUploadFormApp);
  Alpine.data('wordUploadResultApp', wordUploadResultApp);
}

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

// ============================================================================
// Legacy API - For backward compatibility
// ============================================================================

/**
 * Show a supplementary field depending on long text import mode.
 * @deprecated Since 3.1.0, use wordUploadFormApp() Alpine component
 */
export function updateImportMode(value: string | number): void {
  const impTranslDelim = document.getElementById('imp_transl_delim');
  const impTranslDelimInput = impTranslDelim?.querySelector('input');

  if (parseInt(String(value), 10) > 3) {
    impTranslDelim?.classList.remove('hide');
    impTranslDelimInput?.classList.add('notempty');
  } else {
    impTranslDelimInput?.classList.remove('notempty');
    impTranslDelim?.classList.add('hide');
  }
}

/**
 * Navigation header for the imported terms.
 * @deprecated Since 3.1.0, use wordUploadResultApp() Alpine component
 */
function formatImportedTermsNavigation(
  data: NavigationData,
  lastUpdate: string,
  rtl: boolean
): void {
  const currentPage = parseInt(String(data.current_page), 10);
  const totalPages = parseInt(String(data.total_pages), 10);
  const recnoEl = document.getElementById('recno');
  const importedTerms = parseInt(recnoEl?.textContent || '0', 10);

  const showOtherPage = function (pageNumber: number) {
    return function () {
      showImportedTerms(lastUpdate, rtl, importedTerms, pageNumber);
    };
  };

  const prevNav = document.getElementById('res_data-navigation-prev');
  if (prevNav) {
    prevNav.style.display = currentPage > 1 ? 'flex' : 'none';
  }

  const prevFirst = document.getElementById('res_data-navigation-prev-first');
  const prevMinus = document.getElementById('res_data-navigation-prev-minus');

  // Clone and replace to remove old event listeners
  if (prevFirst) {
    const newPrevFirst = prevFirst.cloneNode(true) as HTMLElement;
    prevFirst.parentNode?.replaceChild(newPrevFirst, prevFirst);
    newPrevFirst.addEventListener('click', showOtherPage(1));
  }

  if (prevMinus) {
    const newPrevMinus = prevMinus.cloneNode(true) as HTMLElement;
    prevMinus.parentNode?.replaceChild(newPrevMinus, prevMinus);
    newPrevMinus.addEventListener('click', showOtherPage(currentPage - 1));
  }

  const quickNav = document.getElementById('res_data-navigation-quick_nav') as HTMLSelectElement | null;
  const noQuickNav = document.getElementById('res_data-navigation-no_quick_nav');

  if (totalPages <= 1) {
    if (quickNav) quickNav.style.display = 'none';
    if (noQuickNav) noQuickNav.style.display = 'inline';
  } else {
    if (quickNav) quickNav.style.display = 'inline-block';
    if (noQuickNav) noQuickNav.style.display = 'none';
  }

  if (quickNav) {
    let options = '';
    for (let i = 1; i < totalPages + 1; i++) {
      options += '<option value="' + i + '"' +
        (i === currentPage ? ' selected="selected"' : '') + '>' + i +
        '</option>';
    }
    quickNav.innerHTML = options;

    // Clone and replace to remove old event listeners
    const newQuickNav = quickNav.cloneNode(true) as HTMLSelectElement;
    quickNav.parentNode?.replaceChild(newQuickNav, quickNav);
    newQuickNav.addEventListener('change', function () {
      const form = document.forms.namedItem('form1') as HTMLFormElement | null;
      if (form) {
        const pageSelect = form.elements.namedItem('page') as HTMLSelectElement | null;
        if (pageSelect) {
          showImportedTerms(lastUpdate, rtl, importedTerms, parseInt(pageSelect.value, 10));
        }
      }
    });
  }

  const totalPagesEl = document.getElementById('res_data-navigation-totalPages');
  if (totalPagesEl) {
    totalPagesEl.textContent = String(totalPages);
  }

  const nextNav = document.getElementById('res_data-navigation-next');
  if (nextNav) {
    nextNav.style.display = currentPage < totalPages ? 'flex' : 'none';
  }

  const nextPlus = document.getElementById('res_data-navigation-next-plus');
  const nextLast = document.getElementById('res_data-navigation-next-last');

  if (nextPlus) {
    const newNextPlus = nextPlus.cloneNode(true) as HTMLElement;
    nextPlus.parentNode?.replaceChild(newNextPlus, nextPlus);
    newNextPlus.addEventListener('click', showOtherPage(currentPage + 1));
  }

  if (nextLast) {
    const newNextLast = nextLast.cloneNode(true) as HTMLElement;
    nextLast.parentNode?.replaceChild(newNextLast, nextLast);
    newNextLast.addEventListener('click', showOtherPage(totalPages));
  }
}

/**
 * Create the rows with the imported terms.
 * @deprecated Since 3.1.0, use wordUploadResultApp() Alpine component
 */
function formatImportedTerms(data: ImportedTerm[], rtl: boolean): string {
  let output = '';

  for (let i = 0; i < data.length; i++) {
    const record = data[i];
    const statusInfo = statuses[record.WoStatus] || { name: 'Unknown', abbr: '?' };

    const row = `<tr>
      <td>
        <span${rtl ? ' dir="rtl"' : ''}>${escape_html_chars(record.WoText)}</span>
        <span class="has-text-grey"> / </span>
        <span id="roman${record.WoID}" class="edit_area clickedit has-text-grey-dark">${record.WoRomanization !== '' ? escape_html_chars(record.WoRomanization) : '*'}</span>
      </td>
      <td>
        <span id="trans${record.WoID}" class="edit_area clickedit">${escape_html_chars(record.WoTranslation)}</span>
      </td>
      <td>
        <span class="tags">${renderTags(record.taglist)}</span>
      </td>
      <td class="has-text-centered">
        ${record.SentOK !== 0
    ? iconHtml('check', { title: escape_html_chars(record.WoSentence), alt: 'Yes', className: 'has-text-success' })
    : iconHtml('x', { title: '(No valid sentence)', alt: 'No', className: 'has-text-danger' })
}
      </td>
      <td class="has-text-centered" title="${escape_html_chars(statusInfo.name)}">
        <span class="tag is-light">${escape_html_chars(statusInfo.abbr)}</span>
      </td>
    </tr>`;
    output += row;
  }
  return output;
}

/**
 * Display page content based on raw server answer.
 * @deprecated Since 3.1.0, use wordUploadResultApp() Alpine component
 */
function importedTermsHandleAnswer(
  data: ImportedTermsResponse,
  lastUpdate: string,
  rtl: boolean
): void {
  formatImportedTermsNavigation(data.navigation, lastUpdate, rtl);
  const htmlContent = formatImportedTerms(data.terms, rtl);
  const tableBody = document.getElementById('res_data-res_table-body');
  if (tableBody) {
    tableBody.innerHTML = htmlContent;
  }
}

/**
 * Show the terms imported.
 * @deprecated Since 3.1.0, use wordUploadResultApp() Alpine component
 */
export function showImportedTerms(
  lastUpdate: string,
  rtl: boolean | string,
  count: number,
  page: number | string
): void {
  const rtlBool = typeof rtl === 'string' ? rtl === 'true' || rtl === '1' : rtl;
  const pageNum = typeof page === 'string' ? parseInt(page, 10) : page;

  const noTermsImported = document.getElementById('res_data-no_terms_imported');
  const navigation = document.getElementById('res_data-navigation');
  const resTable = document.getElementById('res_data-res_table');

  if (parseInt(String(count), 10) === 0) {
    if (noTermsImported) noTermsImported.style.display = 'inherit';
    if (navigation) navigation.style.display = 'none';
    if (resTable) resTable.style.display = 'none';
  } else {
    if (noTermsImported) noTermsImported.style.display = 'none';
    if (navigation) navigation.style.display = '';
    if (resTable) resTable.style.display = '';

    const params = new URLSearchParams({
      last_update: lastUpdate,
      count: String(count),
      page: String(pageNum)
    });

    fetch('api.php/v1/terms/imported?' + params.toString())
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error: ${response.status}`);
        }
        return response.json();
      })
      .then((data: ImportedTermsResponse) => {
        importedTermsHandleAnswer(data, lastUpdate, rtlBool);
      })
      .catch(error => {
        console.error('Failed to fetch imported terms:', error);
      });
  }
}

/**
 * Initialize the upload result display from JSON config.
 * @deprecated Since 3.1.0, use wordUploadResultApp() Alpine component
 */
function initUploadResult(config: UploadResultConfig): void {
  showImportedTerms(config.lastUpdate, config.rtl, config.recno, 1);
}

/**
 * Initialize event delegation for the upload form.
 * @deprecated Since 3.1.0, use wordUploadFormApp() Alpine component
 */
function initUploadFormEvents(): void {
  // Handle import mode change via event delegation
  document.addEventListener('change', function (e) {
    const target = e.target as HTMLElement;
    if (target.matches('[data-action="update-import-mode"]')) {
      updateImportMode((target as HTMLSelectElement).value);
    }
  });

  // Handle upload result form submission
  document.addEventListener('submit', function (e) {
    const target = e.target as HTMLElement;
    if (target.matches('form[data-action="upload-result-form"]')) {
      e.preventDefault();
      const form = target as HTMLFormElement;
      const lastUpdate = form.dataset.lastUpdate || '';
      const rtl = form.dataset.rtl === 'true';
      const recno = parseInt(form.dataset.recno || '0', 10);
      const pageSelect = form.elements.namedItem('page') as HTMLSelectElement | null;
      const page = pageSelect ? parseInt(pageSelect.value, 10) : 1;
      showImportedTerms(lastUpdate, rtl, recno, page);
    }
  });
}

/**
 * Auto-initialize upload result from JSON config element.
 * @deprecated Since 3.1.0, use wordUploadResultApp() Alpine component
 */
function autoInitUploadResult(): void {
  const configEl = document.querySelector<HTMLScriptElement>('script[data-lwt-upload-result-config]');
  if (configEl) {
    try {
      const config = JSON.parse(configEl.textContent || '{}') as UploadResultConfig;
      initUploadResult(config);
    } catch (e) {
      console.error('Failed to parse upload result config:', e);
    }
  }
}

// Auto-initialize when DOM is ready (legacy support)
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function () {
    initUploadFormEvents();
    autoInitUploadResult();
  });
} else {
  initUploadFormEvents();
  autoInitUploadResult();
}

// Export to window for potential external use
declare global {
  interface Window {
    wordUploadFormApp: typeof wordUploadFormApp;
    wordUploadResultApp: typeof wordUploadResultApp;
    updateImportMode: typeof updateImportMode;
    showImportedTerms: typeof showImportedTerms;
  }
}

window.wordUploadFormApp = wordUploadFormApp;
window.wordUploadResultApp = wordUploadResultApp;
window.updateImportMode = updateImportMode;
window.showImportedTerms = showImportedTerms;

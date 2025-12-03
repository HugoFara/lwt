/**
 * Word Upload Module - Handles word import form and results display.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

import { escape_html_chars, renderBulmaTags } from '../core/html_utils';
import { STATUSES } from '../core/app_data';
import { iconHtml } from '../ui/icons';
import type { WordStatus } from '../types/globals';

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
 * Show a supplementary field depending on long text import mode.
 *
 * When import mode is > 3 (merge/update translation modes), show the
 * translation delimiter field.
 *
 * @param value Import mode value (0-5)
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
 *
 * @param data Navigation data object
 * @param lastUpdate Terms import timestamp for SQL
 * @param rtl If text is right-to-left
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
 *
 * @param data Array of imported term records
 * @param rtl If text is right-to-left
 * @returns HTML-formatted rows to display
 */
function formatImportedTerms(data: ImportedTerm[], rtl: boolean): string {
  let output = '';

  for (let i = 0; i < data.length; i++) {
    const record = data[i];
    const statusInfo = STATUSES[record.WoStatus] || { name: 'Unknown', abbr: '?' };

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
        <span class="tags">${renderBulmaTags(record.taglist)}</span>
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
 *
 * @param data API response data
 * @param lastUpdate Terms import timestamp for SQL
 * @param rtl If text is right-to-left
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
 *
 * @param lastUpdate Last update date in SQL compatible format
 * @param rtl If text is right-to-left
 * @param count Number of terms imported
 * @param page Current page number
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
 * Configuration for upload result view.
 */
interface UploadResultConfig {
  lastUpdate: string;
  rtl: boolean;
  recno: number;
}

/**
 * Initialize the upload result display from JSON config.
 */
function initUploadResult(config: UploadResultConfig): void {
  showImportedTerms(config.lastUpdate, config.rtl, config.recno, 1);
}

/**
 * Initialize event delegation for the upload form.
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

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function () {
    initUploadFormEvents();
    autoInitUploadResult();
  });
} else {
  initUploadFormEvents();
  autoInitUploadResult();
}

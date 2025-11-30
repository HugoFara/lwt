/**
 * Word Upload Module - Handles word import form and results display.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

import $ from 'jquery';
import { escape_html_chars } from '../core/html_utils';

// Interface for word status (matches PHP STATUSES global)
interface WordStatus {
  name: string;
  abbr: string;
}

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
  if (parseInt(String(value), 10) > 3) {
    $('#imp_transl_delim').removeClass('hide');
    $('#imp_transl_delim input').addClass('notempty');
  } else {
    $('#imp_transl_delim input').removeClass('notempty');
    $('#imp_transl_delim').addClass('hide');
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
  const importedTerms = parseInt($('#recno').text(), 10);

  const showOtherPage = function (pageNumber: number) {
    return function () {
      showImportedTerms(lastUpdate, rtl, importedTerms, pageNumber);
    };
  };

  if (currentPage > 1) {
    $('#res_data-navigation-prev').css('display', 'initial');
  } else {
    $('#res_data-navigation-prev').css('display', 'none');
  }
  $('#res_data-navigation-prev-first').off('click').on('click', showOtherPage(1));
  $('#res_data-navigation-prev-minus').off('click').on('click', showOtherPage(currentPage - 1));

  if (totalPages === 1) {
    $('#res_data-navigation-quick_nav').css('display', 'none');
    $('#res_data-navigation-no_quick_nav').css('display', 'initial');
  } else {
    $('#res_data-navigation-quick_nav').css('display', 'initial');
    $('#res_data-navigation-no_quick_nav').css('display', 'none');
  }

  let options = '';
  for (let i = 1; i < totalPages + 1; i++) {
    options += '<option value="' + i + '"' +
      (i === currentPage ? ' selected="selected"' : '') + '>' + i +
      '</option>';
  }
  $('#res_data-navigation-quick_nav').html(options);
  $('#res_data-navigation-quick_nav').off('change').on('change', function () {
    const form = document.forms.namedItem('form1') as HTMLFormElement | null;
    if (form) {
      const pageSelect = form.elements.namedItem('page') as HTMLSelectElement | null;
      if (pageSelect) {
        showImportedTerms(lastUpdate, rtl, importedTerms, parseInt(pageSelect.value, 10));
      }
    }
  });

  $('#res_data-navigation-totalPages').text(totalPages);

  if (currentPage < totalPages) {
    $('#res_data-navigation-next').css('display', 'initial');
  } else {
    $('#res_data-navigation-next').css('display', 'none');
  }
  $('#res_data-navigation-next-plus').off('click').on('click', showOtherPage(currentPage + 1));
  $('#res_data-navigation-next-last').off('click').on('click', showOtherPage(totalPages));
}

/**
 * Create the rows with the imported terms.
 *
 * @param data Array of imported term records
 * @param rtl If text is right-to-left
 * @returns HTML-formatted rows to display
 */
function formatImportedTerms(data: ImportedTerm[], rtl: boolean): string {
  const statuses: Record<string, WordStatus> = window.STATUSES || {};
  let output = '';

  for (let i = 0; i < data.length; i++) {
    const record = data[i];
    const statusInfo = statuses[record.WoStatus] || { name: 'Unknown', abbr: '?' };

    const row = `<tr>
      <td class="td1">
        <span${rtl ? ' dir="rtl"' : ''}>${escape_html_chars(record.WoText)}</span>
        / <span id="roman${record.WoID}" class="edit_area clickedit">${record.WoRomanization !== '' ? escape_html_chars(record.WoRomanization) : '*'}</span>
      </td>
      <td class="td1">
        <span id="trans${record.WoID}" class="edit_area clickedit">${escape_html_chars(record.WoTranslation)}</span>
      </td>
      <td class="td1">
        <span class="smallgray2">${escape_html_chars(record.taglist)}</span>
      </td>
      <td class="td1 center">
        <b>${record.SentOK !== 0
    ? `<img src="/assets/icons/status.png" title="${escape_html_chars(record.WoSentence)}" alt="Yes" />`
    : '<img src="/assets/icons/status-busy.png" title="(No valid sentence)" alt="No" />'
}</b>
      </td>
      <td class="td1 center" title="${escape_html_chars(statusInfo.name)}">${escape_html_chars(statusInfo.abbr)}</td>
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
  $('#res_data-res_table-body').empty();
  $('#res_data-res_table-body').append($(htmlContent));
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

  if (parseInt(String(count), 10) === 0) {
    $('#res_data-no_terms_imported').css('display', 'inherit');
    $('#res_data-navigation').css('display', 'none');
    $('#res_data-res_table').css('display', 'none');
  } else {
    $('#res_data-no_terms_imported').css('display', 'none');
    $('#res_data-navigation').css('display', '');
    $('#res_data-res_table').css('display', '');
    $.getJSON(
      'api.php/v1/terms/imported',
      {
        last_update: lastUpdate,
        count: count,
        page: pageNum
      },
      function (data: ImportedTermsResponse) {
        importedTermsHandleAnswer(data, lastUpdate, rtlBool);
      }
    );
  }
}

/**
 * Initialize event delegation for the upload form.
 */
function initUploadFormEvents(): void {
  // Handle import mode change via event delegation
  $(document).on('change', '[data-action="update-import-mode"]', function (this: HTMLSelectElement) {
    updateImportMode(this.value);
  });
}

// Auto-initialize when DOM is ready
$(document).ready(function () {
  initUploadFormEvents();
});

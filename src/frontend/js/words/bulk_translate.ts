/**
 * Bulk Translate - Functions for the bulk translate word form
 *
 * Handles dictionary lookups, form interactions, and Google Translate
 * integration for bulk translating unknown words.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import $ from 'jquery';
import { LWT_DATA } from '../core/lwt_state';
import { createTheDictUrl, owin } from '../terms/dictionary';
import { selectToggle } from '../forms/bulk_actions';

declare global {
  interface Window {
    WBLINK?: string;
  }
  // Google Translate API types
  const google: {
    translate: {
      TranslateElement: {
        new(config: {
          pageLanguage: string;
          layout: unknown;
          includedLanguages: string;
          autoDisplay: boolean;
        }, elementId: string): unknown;
        InlineLayout: {
          SIMPLE: unknown;
        };
      };
    };
  };
}

/**
 * Handle click on a dictionary link in bulk translate form.
 * Opens the dictionary for the term in the same row.
 */
export function clickDictionary(this: HTMLElement): void {
  const $this = $(this);
  let dictLink: string;

  if ($this.hasClass('dict1')) {
    dictLink = LWT_DATA.language.dict_link1;
  } else if ($this.hasClass('dict2')) {
    dictLink = LWT_DATA.language.dict_link2;
  } else if ($this.hasClass('dict3')) {
    dictLink = LWT_DATA.language.translator_link;
  } else {
    return;
  }

  window.WBLINK = dictLink;

  let isPopup = false;
  if (dictLink.startsWith('*')) {
    isPopup = true;
    dictLink = dictLink.substring(1);
  }

  try {
    const finalUrl = new URL(dictLink);
    isPopup = isPopup || finalUrl.searchParams.has('lwt_popup');
  } catch (err) {
    if (!(err instanceof TypeError)) {
      throw err;
    }
  }

  const termText = $this.parent().prev().text();
  const dictUrl = createTheDictUrl(dictLink, termText);

  if (isPopup) {
    owin(dictUrl);
  } else {
    try {
      window.parent.frames['ru' as unknown as number].location.href = dictUrl;
    } catch {
      // Frame access denied, try opening in new window
      owin(dictUrl);
    }
  }

  // Swap WoTranslation name attributes to track current input
  const currentTranslation = $('[name="WoTranslation"]');
  currentTranslation.attr('name', currentTranslation.attr('data_name') ?? '');

  const el = $this.parent().parent().next().children();
  el.attr('data_name', el.attr('name') ?? '');
  el.attr('name', 'WoTranslation');
}

/**
 * Set up form interactions after page load.
 * - Form submission handler
 * - Dictionary click handlers
 * - Translation deletion handlers
 * - Wait for Google Translate to populate, then set up inputs
 */
export function bulkInteractions(): void {
  // Form submission - restore original input names and clear frame
  $('[name="form1"]').on('submit', function () {
    const currentTranslation = $('[name="WoTranslation"]');
    currentTranslation.attr('name', currentTranslation.attr('data_name') ?? '');

    try {
      window.parent.frames['ru' as unknown as number].location.href = 'empty.html';
    } catch {
      // Frame access denied, continue with submission
    }

    return true;
  });

  // Dictionary and delete handlers using event delegation
  $('td').on('click', 'span.dict1, span.dict2, span.dict3', clickDictionary)
    .on('click', '.del_trans', function () {
      $(this).prev().val('').trigger('focus');
    });

  // Wait for Google Translate to populate the .trans elements with <font> tags
  const displayTranslations = setInterval(function () {
    if ($('.trans>font').length === $('.trans').length) {
      // Convert translated text to input fields
      $('.trans').each(function () {
        const $trans = $(this);
        const txt = $trans.text();
        const cnt = ($trans.attr('id') ?? '').replace('Trans_', '');

        $trans
          .addClass('notranslate')
          .html(
            `<input type="text" name="term[${cnt}][trans]" value="${txt}" maxlength="100" class="respinput">` +
            '<div class="del_trans"></div>'
          );
      });

      // Add dictionary links after each term
      $('.term').each(function () {
        const $term = $(this);
        $term.parent().css('position', 'relative');

        const dictLinks =
          '<div class="dict">' +
          (LWT_DATA.language.dict_link1 ? '<span class="dict1">D1</span>' : '') +
          (LWT_DATA.language.dict_link2 ? '<span class="dict2">D2</span>' : '') +
          (LWT_DATA.language.translator_link ? '<span class="dict3">Tr</span>' : '') +
          '</div>';

        $term.after(dictLinks);
      });

      // Clean up Google Translate elements
      $('iframe,#google_translate_element').remove();

      // Enable all checkboxes and inputs
      selectToggle(true, 'form1');
      $('[name^=term]').prop('disabled', false);

      clearInterval(displayTranslations);
    }
  }, 300);
}

/**
 * Set up checkbox change handlers.
 * Controls enabling/disabling of term inputs based on checkbox state.
 */
export function bulkCheckbox(): void {
  // Clear right frame on load
  try {
    window.parent.frames['ru' as unknown as number].location.href = 'empty.html';
  } catch {
    // Frame access denied
  }

  $('input[type="checkbox"]').on('change', function () {
    const $checkbox = $(this);
    const v = parseInt($checkbox.val() as string, 10);

    // Selector for all inputs related to this term
    const inputSelector =
      `[name=term\\[${v}\\]\\[text\\]],` +
      `[name=term\\[${v}\\]\\[lg\\]],` +
      `[name=term\\[${v}\\]\\[status\\]]`;

    $(inputSelector).prop('disabled', !$checkbox.is(':checked'));
    $(`#Trans_${v} input`).prop('disabled', !$checkbox.is(':checked'));

    // Update submit button text based on state
    if ($('input[type="checkbox"]:checked').length) {
      let operationOption: string;
      if ($checkbox.is(':checked')) {
        operationOption = 'Save';
      } else if ($('input[name="offset"]').length) {
        operationOption = 'Next';
      } else {
        operationOption = 'End';
      }
      $('input[type="submit"]').val(operationOption);
    }
  });
}

/**
 * Initialize Google Translate widget.
 *
 * @param sourceLanguage Source language code (e.g., 'en')
 * @param targetLanguage Target language code (e.g., 'es')
 */
export function googleTranslateElementInit(sourceLanguage: string, targetLanguage: string): void {
  if (typeof google !== 'undefined' && google.translate) {
    new google.translate.TranslateElement({
      pageLanguage: sourceLanguage,
      layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
      includedLanguages: targetLanguage,
      autoDisplay: false
    }, 'google_translate_element');
  }
}

/**
 * Mark all terms for saving.
 */
export function markAll(): void {
  $('input[type^=submit]').val('Save');
  selectToggle(true, 'form1');
  $('[name^=term]').prop('disabled', false);
}

/**
 * Unmark all terms.
 */
export function markNone(): void {
  const submitValue = $('input[name^=offset]').length ? 'Next' : 'End';
  $('input[type^=submit]').val(submitValue);
  selectToggle(false, 'form1');
  $('[name^=term]').prop('disabled', true);
}

/**
 * Handle changes to the bulk action select (status changes, lowercase, delete translation).
 *
 * @param $elem jQuery element of the select
 */
export function changeTermToggles($elem: JQuery<HTMLSelectElement>): boolean {
  const v = $elem.val() as string;

  if (v === '6') {
    // Set to lowercase
    $('.markcheck:checked').each(function () {
      const checkboxValue = $(this).val();
      const $termSpan = $(`#Term_${checkboxValue}`).children('.term');
      const lowerText = $termSpan.text().toLowerCase();
      $termSpan.text(lowerText);
      $(`#Text_${checkboxValue}`).val(lowerText);
    });
    $elem.prop('selectedIndex', 0);
    return false;
  }

  if (v === '7') {
    // Delete translation (set to *)
    $('.markcheck:checked').each(function () {
      const checkboxValue = $(this).val();
      $(`#Trans_${checkboxValue} input`).val('*');
    });
    $elem.prop('selectedIndex', 0);
    return false;
  }

  // Set status for all checked terms
  $('.markcheck:checked').each(function () {
    const checkboxValue = $(this).val();
    $(`#Stat_${checkboxValue}`).val(v);
  });
  $elem.prop('selectedIndex', 0);
  return false;
}

/**
 * Initialize bulk translate page.
 * Sets up all event handlers for the bulk translate form.
 *
 * @param dictionaries Dictionary URLs configuration
 */
export function initBulkTranslate(dictionaries: {
  dict1: string;
  dict2: string;
  translate: string;
}): void {
  // Set dictionary links in LWT_DATA
  LWT_DATA.language.dict_link1 = dictionaries.dict1;
  LWT_DATA.language.dict_link2 = dictionaries.dict2;
  LWT_DATA.language.translator_link = dictionaries.translate;

  // Mark headers as not translatable
  $('h3,h4,title').addClass('notranslate');

  // Set up interactions when page is fully loaded
  $(window).on('load', bulkInteractions);

  // Set up checkbox handlers when DOM is ready
  $(document).ready(bulkCheckbox);
}

/**
 * Feed Wizard Step 2 - Select Article Text interactions.
 *
 * @license Unlicense
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

import $ from 'jquery';
import { extend_adv_xpath, lwt_feed_wizard } from './jq_feedwizard';

// Declare filter_Array as a global variable
declare global {
  // eslint-disable-next-line no-var
  var filter_Array: HTMLElement[];
}

// Initialize global filter_Array if not already defined
if (typeof window.filter_Array === 'undefined') {
  window.filter_Array = [];
}

/**
 * Object containing wizard step 2 test/selection interactions.
 */
export const lwt_wiz_select_test = {
  /**
   * Cancel advanced selection mode.
   */
  clickCancel: function (): boolean {
    $('#adv').hide();
    $('#lwt_last').css('margin-top', $('#lwt_header').height() || 0);
    return false;
  },

  /**
   * Reset selection mode - clear all marked text and reset UI.
   */
  changeSelectMode: function (): boolean {
    $('*').removeClass('lwt_marked_text');
    $('*[class=\'\']').removeAttr('class');
    $('#get_button').prop('disabled', true);
    $('#mark_action').empty();
    $('<option/>').val('').text('[Click On Text]').appendTo('#mark_action');
    return false;
  },

  /**
   * Toggle image visibility based on select value.
   */
  changeHideImage: function (this: HTMLSelectElement): boolean {
    if ($(this).val() === 'no') {
      $('img').not($('#lwt_header').find('*')).css('display', '');
    } else {
      $('img').not($('#lwt_header').find('*')).css('display', 'none');
    }
    return false;
  },

  /**
   * Navigate back to step 1 with current settings.
   */
  clickBack: function (): boolean {
    location.href = '/feeds/wizard?step=1&select_mode=' +
      encodeURIComponent($('select[name=\'select_mode\']').val() as string) +
      '&hide_images=' +
      encodeURIComponent($('select[name=\'hide_images\']').val() as string);
    return false;
  },

  /**
   * Toggle min/max state of the wizard container.
   */
  clickMinMax: function (): boolean {
    $('#lwt_container').toggle();
    if ($('#lwt_container').css('display') === 'none') {
      $('input[name=\'maxim\']').val(0);
    } else {
      $('input[name=\'maxim\']').val(1);
    }
    $('#lwt_last').css('margin-top', $('#lwt_header').height() || 0);
    return false;
  },

  /**
   * Handle feed selection change - submit form with current HTML.
   */
  changeSelectedFeed: function (): void {
    const html = $('#lwt_sel').html();
    $('input[name=\'html\']').val(html || '');
    (document as Document & { lwt_form1: HTMLFormElement }).lwt_form1.submit();
  },

  /**
   * Handle article section change - submit form with current HTML.
   */
  changeArticleSection: function (): void {
    const html = $('#lwt_sel').html();
    $('input[name=\'html\']').val(html || '');
    (document as Document & { lwt_form1: HTMLFormElement }).lwt_form1.submit();
  },

  /**
   * Set maxim state - minimize the container.
   */
  setMaxim: function (): void {
    $('#lwt_container').hide();
    $('#lwt_last').css('margin-top', $('#lwt_header').height() || 0);
    if ($('#lwt_container').css('display') === 'none') {
      $('input[name=\'maxim\']').val(0);
    } else {
      $('input[name=\'maxim\']').val(1);
    }
  }
};

/**
 * Initialize wizard step 2 with configuration.
 *
 * @param hideImages Whether to hide images initially
 * @param isMinimized Whether to start minimized
 */
export function initWizardStep2(hideImages: boolean, isMinimized: boolean): void {
  // Extend jQuery with get_adv_xpath
  $.fn.get_adv_xpath = extend_adv_xpath;

  // Initialize filter array
  window.filter_Array = [];

  // Prepare interactions from jq_feedwizard
  lwt_feed_wizard.prepareInteractions();

  // Hide images if configured
  if (hideImages) {
    $('img').not($('#lwt_header').find('*')).css('display', 'none');
  }

  // Set minimized state if configured
  if (isMinimized) {
    lwt_wiz_select_test.setMaxim();
  }
}

/**
 * Initialize event delegation for wizard step 2 buttons.
 */
function initWizardStep2Events(): void {
  // Cancel button in advanced selection
  $(document).on('click', '[data-action="wizard-cancel"]', function () {
    lwt_wiz_select_test.clickCancel();
  });

  // Selection mode change
  $(document).on('change', '[data-action="wizard-select-mode"]', function () {
    lwt_wiz_select_test.changeSelectMode();
  });

  // Hide images change
  $(document).on('change', '[data-action="wizard-hide-images"]', function (this: HTMLSelectElement) {
    lwt_wiz_select_test.changeHideImage.call(this);
  });

  // Back button
  $(document).on('click', '[data-action="wizard-back"]', function () {
    lwt_wiz_select_test.clickBack();
  });

  // Min/max button
  $(document).on('click', '[data-action="wizard-minmax"]', function () {
    lwt_wiz_select_test.clickMinMax();
  });

  // Selected feed change
  $(document).on('change', '[data-action="wizard-selected-feed"]', function () {
    lwt_wiz_select_test.changeSelectedFeed();
  });

  // Article section change
  $(document).on('change', '[data-action="wizard-article-section"]', function () {
    lwt_wiz_select_test.changeArticleSection();
  });

  // Settings open button
  $(document).on('click', '[data-action="wizard-settings-open"]', function (e) {
    e.preventDefault();
    $('#settings').show();
  });

  // Settings close button
  $(document).on('click', '[data-action="wizard-settings-close"]', function (e) {
    e.preventDefault();
    $('#settings').hide();
  });

  // Cancel wizard button (delete wizard and redirect)
  $(document).on('click', '[data-action="wizard-delete-cancel"]', function (e) {
    e.preventDefault();
    location.href = '/feeds/edit?del_wiz=1';
  });
}

/**
 * Auto-initialize wizard step 2 from config element.
 * Detects the lwt_header element's data attributes and initializes.
 */
function autoInitWizardStep2(): void {
  const header = document.getElementById('lwt_header');
  if (!header || !header.dataset.hideImages) {
    return;
  }

  initWizardStep2(
    header.dataset.hideImages === 'true',
    header.dataset.isMinimized === 'true'
  );
}

// Auto-initialize event handlers and step 2
$(document).ready(function () {
  initWizardStep2Events();
  autoInitWizardStep2();
});

// Expose to window for backward compatibility
(window as unknown as Record<string, unknown>).lwt_wiz_select_test = lwt_wiz_select_test;
(window as unknown as Record<string, unknown>).initWizardStep2 = initWizardStep2;

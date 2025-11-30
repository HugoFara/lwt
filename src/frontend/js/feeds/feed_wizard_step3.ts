/**
 * Feed Wizard Step 3 - Filter Text interactions.
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
  function xpathQuery(expression: string, context?: Node): JQuery<HTMLElement>;
}

// Initialize global filter_Array if not already defined
if (typeof window.filter_Array === 'undefined') {
  window.filter_Array = [];
}

/**
 * Configuration for wizard step 3.
 */
export interface WizardStep3Config {
  articleSelector: string;
  hideImages: boolean;
  isMinimized: boolean;
}

/**
 * Object containing wizard step 3 filter interactions.
 */
export const lwt_wizard_filter = {
  /**
   * Update the filter array based on article selector.
   * Adds lwt_filtered_text class to elements outside the article section.
   *
   * @param articleSelector - The XPath selector for the article section
   */
  updateFilterArray: function (articleSelector: string): void {
    const articleSection = articleSelector.trim();
    if (articleSection === '') {
      alert('Article section is empty!');
      return;
    }
    $('#lwt_header')
      .nextAll()
      .find('*')
      .addBack()
      .not(window.xpathQuery(articleSection).find('*').addBack())
      .not($('#lwt_header').find('*').addBack())
      .each(function () {
        $(this).addClass('lwt_filtered_text');
        window.filter_Array.push(this);
      });
  },

  /**
   * Hide all images except those in the header.
   */
  hideImages: function (): void {
    $('img').not($('#lwt_header').find('*')).css('display', 'none');
  },

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
  changeHideImages: function (this: HTMLSelectElement): boolean {
    if ($(this).val() === 'no') {
      $('img').not($('#lwt_header').find('*')).css('display', '');
    } else {
      $('img').not($('#lwt_header').find('*')).css('display', 'none');
    }
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
   * Navigate back to step 2 with current settings.
   */
  clickBack: function (): boolean {
    location.href = '/feeds/wizard?step=2&article_tags=1&maxim=' +
      $('#maxim').val() + '&filter_tags=' +
      encodeURIComponent($('#lwt_sel').html() || '') + '&select_mode=' +
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
 * Initialize wizard step 3 with configuration.
 *
 * @param config - Configuration for the wizard step
 */
export function initWizardStep3(config: WizardStep3Config): void {
  // Extend jQuery with get_adv_xpath
  $.fn.get_adv_xpath = extend_adv_xpath;

  // Initialize filter array
  window.filter_Array = [];

  // Prepare interactions from jq_feedwizard
  lwt_feed_wizard.prepareInteractions();

  // Hide images if configured
  if (config.hideImages) {
    lwt_wizard_filter.hideImages();
  }

  // Update filter array with article selector
  lwt_wizard_filter.updateFilterArray(config.articleSelector);

  // Set minimized state if configured
  if (config.isMinimized) {
    lwt_wizard_filter.setMaxim();
  }
}

/**
 * Initialize event delegation for wizard step 3 buttons.
 */
function initWizardStep3Events(): void {
  // Cancel button in advanced selection
  $(document).on('click', '[data-action="wizard-step3-cancel"]', function () {
    lwt_wizard_filter.clickCancel();
  });

  // Selection mode change
  $(document).on('change', '[data-action="wizard-step3-select-mode"]', function () {
    lwt_wizard_filter.changeSelectMode();
  });

  // Hide images change
  $(document).on(
    'change',
    '[data-action="wizard-step3-hide-images"]',
    function (this: HTMLSelectElement) {
      lwt_wizard_filter.changeHideImages.call(this);
    }
  );

  // Back button
  $(document).on('click', '[data-action="wizard-step3-back"]', function () {
    lwt_wizard_filter.clickBack();
  });

  // Min/max button
  $(document).on('click', '[data-action="wizard-step3-minmax"]', function () {
    lwt_wizard_filter.clickMinMax();
  });

  // Selected feed change
  $(document).on('change', '[data-action="wizard-step3-selected-feed"]', function () {
    lwt_wizard_filter.changeSelectedFeed();
  });

  // Settings dialog open
  $(document).on('click', '[data-action="wizard-settings-open"]', function () {
    $('#settings').show();
  });

  // Settings dialog close
  $(document).on('click', '[data-action="wizard-settings-close"]', function () {
    $('#settings').hide();
  });
}

// Auto-initialize event handlers
$(document).ready(function () {
  initWizardStep3Events();
});

// Expose to window for backward compatibility
(window as unknown as Record<string, unknown>).lwt_wizard_filter = lwt_wizard_filter;
(window as unknown as Record<string, unknown>).initWizardStep3 = initWizardStep3;

/**
 * Feed Wizard Step 4 - Edit Options interactions.
 *
 * @license Unlicense
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @since   3.0.0 Extracted from PHP inline scripts
 */

import $ from 'jquery';

/**
 * Configuration for wizard step 4.
 */
export interface WizardStep4Config {
  editFeedId: number | null;
}

/**
 * Object containing wizard step 4 edit options interactions.
 */
export const lwt_wizard_step4 = {
  /**
   * Build the options string from form checkboxes.
   *
   * @returns The options string for NfOptions field
   */
  buildOptionsString: function (): string {
    let str = $('[name="edit_text"]:checked').length > 0 ? 'edit_text=1,' : '';

    $('[name^="c_"]').each(function () {
      const checkbox = this as HTMLInputElement;
      if (checkbox.checked) {
        const $parent = $(checkbox).parent();
        const $textInput = $parent.children('input[type="text"]');
        const inputName = $textInput.attr('name') || '';
        const inputValue = $textInput.val() as string || '';

        str += inputName + '=' + inputValue;

        if ($(checkbox).attr('name') === 'c_autoupdate') {
          str += ($parent.find('select').val() as string) + ',';
        } else {
          str += ',';
        }
      }
    });

    const articleSource = $('input[name="article_source"]').val() as string;
    if (articleSource !== '') {
      str += 'article_source=' + articleSource;
    }

    return str;
  },

  /**
   * Handle checkbox change - toggle associated inputs.
   */
  handleCheckboxChange: function (this: HTMLInputElement): void {
    const $parent = $(this).parent();
    if (this.checked) {
      $parent.children('input[type="text"]')
        .removeAttr('disabled')
        .addClass('notempty');
      $parent.find('select').removeAttr('disabled');
    } else {
      $parent.children('input[type="text"]')
        .attr('disabled', 'disabled')
        .removeClass('notempty');
      $parent.find('select').attr('disabled', 'disabled');
    }
  },

  /**
   * Handle submit button click - build options string.
   */
  handleSubmit: function (): void {
    const str = lwt_wizard_step4.buildOptionsString();
    $('input[name="NfOptions"]').val(str);
  },

  /**
   * Handle back button click - navigate to step 3 with current options.
   */
  clickBack: function (): boolean {
    let str = $('[name="edit_text"]:checked').length > 0 ? 'edit_text=1,' : '';

    $('[name^="c_"]').each(function () {
      const checkbox = this as HTMLInputElement;
      if (checkbox.checked) {
        const $parent = $(checkbox).parent();
        const $textInput = $parent.children('input[type="text"]');
        const inputName = $textInput.attr('name') || '';
        const inputValue = $textInput.val() as string || '';

        str += inputName + '=' + inputValue;

        if ($(checkbox).attr('name') === 'c_autoupdate') {
          str += ($parent.find('select').val() as string) + ',';
        } else {
          str += ',';
        }
      }
    });

    location.href = '/feeds/wizard?step=3&NfOptions=' + str +
      '&NfLgID=' + $('select[name="NfLgID"]').val() +
      '&NfName=' + $('input[name="NfName"]').val();

    return false;
  },

  /**
   * Set up the page for edit mode if editing existing feed.
   *
   * @param editFeedId - The ID of the feed being edited, or null for new feed
   */
  setupEditMode: function (editFeedId: number | null): void {
    if (editFeedId) {
      $('input[name="save_feed"]').attr('name', 'update_feed');
      $('input[type="submit"]').val('Update');
    }
  },

  /**
   * Set up the page header.
   */
  setupHeader: function (): void {
    $('h1')
      .eq(-1)
      .html(
        'Feed Wizard | Step 4 - Edit Options ' +
        '<a href="docs/info.html#feed_wizard" target="_blank">' +
        '<img alt="Help" title="Help" src="/assets/icons/question-frame.png"></a>'
      )
      .css('text-align', 'center');
  }
};

/**
 * Initialize wizard step 4 with configuration.
 *
 * @param config - Configuration for the wizard step
 */
export function initWizardStep4(config: WizardStep4Config): void {
  // Set up edit mode if editing existing feed
  lwt_wizard_step4.setupEditMode(config.editFeedId);

  // Set up the page header
  lwt_wizard_step4.setupHeader();
}

/**
 * Initialize event delegation for wizard step 4.
 */
function initWizardStep4Events(): void {
  // Handle checkbox changes for option fields
  $(document).on('change', '[name^="c_"]', function (this: HTMLInputElement) {
    lwt_wizard_step4.handleCheckboxChange.call(this);
  });

  // Handle submit button click
  $(document).on('click', '[data-action="wizard-step4-submit"]', function () {
    lwt_wizard_step4.handleSubmit();
  });

  // Handle back button click
  $(document).on('click', '[data-action="wizard-step4-back"]', function () {
    lwt_wizard_step4.clickBack();
  });

  // Handle cancel button click
  $(document).on('click', '[data-action="wizard-step4-cancel"]', function () {
    location.href = '/feeds/edit?del_wiz=1';
  });
}

/**
 * Auto-initialize wizard step 4 from config element.
 * Detects the wizard-step4-config element's data attributes and initializes.
 */
function autoInitWizardStep4(): void {
  const configEl = document.getElementById('wizard-step4-config');
  if (!configEl) {
    return;
  }

  const editFeedId = configEl.dataset.editFeedId;
  initWizardStep4({
    editFeedId: editFeedId ? parseInt(editFeedId, 10) : null
  });
}

// Auto-initialize event handlers and step 4
$(document).ready(function () {
  initWizardStep4Events();
  autoInitWizardStep4();
});

// Expose to window for backward compatibility
(window as unknown as Record<string, unknown>).lwt_wizard_step4 = lwt_wizard_step4;
(window as unknown as Record<string, unknown>).initWizardStep4 = initWizardStep4;

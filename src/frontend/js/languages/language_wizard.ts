/**
 * Language Wizard - Helper for setting up new languages.
 *
 * Extracted from Views/Language/wizard.php
 * Provides UI for selecting native (L1) and study (L2) languages,
 * then auto-populates language form fields.
 *
 * @license unlicense
 * @since 3.0.0
 */

import $ from 'jquery';
import { do_ajax_save_setting } from '../core/ajax_utilities';
import { lwtFormCheck } from '../forms/unloadformcheck';

/**
 * Language definition array structure.
 * [0] = Glosbe code (e.g., 'en')
 * [1] = ISO code (e.g., 'en')
 * [2] = Large text size flag (boolean)
 * [3] = Word character regexp
 * [4] = Sentence split regexp
 * [5] = Split each char flag
 * [6] = Remove spaces flag
 * [7] = Right-to-left flag
 */
type LanguageDefinition = [
  string,   // Glosbe code
  string,   // ISO code
  boolean,  // Large text size
  string,   // Word character regexp
  string,   // Sentence split regexp
  boolean,  // Split each char
  boolean,  // Remove spaces
  boolean   // RTL
];

/**
 * Configuration for language wizard.
 * Passed from PHP via JSON.
 */
export interface LanguageWizardConfig {
  languageDefs: Record<string, LanguageDefinition>;
}

// Declare globals that are set by the language form
declare global {
  interface Window {
    GGTRANSLATE: string;
    LIBRETRANSLATE: string;
    reloadDictURLs: (sourceLg: string, targetLg: string) => void;
    checkLanguageChanged: (value: string) => void;
  }
}

/**
 * Language wizard object.
 * Handles the wizard UI for setting up language configurations.
 */
export const languageWizard = {
  /** Language definitions loaded from config */
  langDefs: {} as Record<string, LanguageDefinition>,

  /**
   * Initialize the wizard with language definitions.
   */
  init(config: LanguageWizardConfig): void {
    this.langDefs = config.languageDefs;
  },

  /**
   * Execute the wizard - validate and apply language settings.
   */
  go(): void {
    const l1 = ($('#l1').val() as string) || '';
    const l2 = ($('#l2').val() as string) || '';

    if (l1 === '') {
      alert('Please choose your native language (L1)!');
      return;
    }
    if (l2 === '') {
      alert('Please choose your language you want to read/study (L2)!');
      return;
    }
    if (l2 === l1) {
      alert('L1 L2 Languages must not be equal!');
      return;
    }

    this.apply(this.langDefs[l2], this.langDefs[l1], l2);
  },

  /**
   * Apply language settings to the form.
   *
   * @param learningLg - Language definition for the study language (L2)
   * @param knownLg - Language definition for the native language (L1)
   * @param learningLgName - Name of the learning language
   */
  apply(
    learningLg: LanguageDefinition,
    knownLg: LanguageDefinition,
    learningLgName: string
  ): void {
    // Reload dictionary URLs with the new language codes
    if (typeof window.reloadDictURLs === 'function') {
      window.reloadDictURLs(learningLg[1], knownLg[1]);
    }

    // Build LibreTranslate URL
    const url = new URL(window.location.href);
    const baseUrl = url.protocol + '//' + url.hostname;

    window.LIBRETRANSLATE = baseUrl + ':5000/?' + $.param({
      lwt_translator: 'libretranslate',
      lwt_translator_ajax: encodeURIComponent(baseUrl + ':5000/translate/?'),
      source: learningLg[1],
      target: knownLg[1],
      q: 'lwt_term'
    });

    // Set language name and trigger change event
    $('input[name="LgName"]').val(learningLgName).trigger('change');

    // Check for language-specific UI changes (e.g., Japanese regexp field)
    if (typeof window.checkLanguageChanged === 'function') {
      window.checkLanguageChanged(learningLgName);
    }

    // Set dictionary URL (Glosbe)
    $('input[name="LgDict1URI"]').val(
      'https://de.glosbe.com/' + learningLg[0] + '/' +
      knownLg[0] + '/lwt_term?lwt_popup=1'
    );
    $('input[name="LgDict1PopUp"]').prop('checked', true);

    // Set translator URL
    if (window.GGTRANSLATE) {
      $('input[name="LgGoogleTranslateURI"]').val(window.GGTRANSLATE);
    }

    // Set text size based on language needs
    $('input[name="LgTextSize"]')
      .val(learningLg[2] ? 200 : 150)
      .trigger('change');

    // Set language parsing rules
    $('input[name="LgRegexpSplitSentences"]').val(learningLg[4]);
    $('input[name="LgRegexpWordCharacters"]').val(learningLg[3]);
    $('input[name="LgSplitEachChar"]').prop('checked', learningLg[5]);
    $('input[name="LgRemoveSpaces"]').prop('checked', learningLg[6]);
    $('input[name="LgRightToLeft"]').prop('checked', learningLg[7]);
  },

  /**
   * Save the native language preference.
   *
   * @param value - The selected native language
   */
  changeNative(value: string): void {
    do_ajax_save_setting('currentnativelanguage', value);
  },

  /**
   * Toggle the wizard zone visibility.
   */
  toggleWizardZone(): void {
    $('#wizard_zone').toggle(400);
  }
};

/**
 * Initialize language wizard from JSON config element.
 */
export function initLanguageWizard(): void {
  const configEl = document.getElementById('language-wizard-config');
  if (!configEl) return;

  let config: LanguageWizardConfig;
  try {
    config = JSON.parse(configEl.textContent || '{}');
  } catch (e) {
    console.error('Failed to parse language-wizard-config:', e);
    return;
  }

  languageWizard.init(config);

  // Set up event listeners using data-action attributes
  const l1Select = document.getElementById('l1');
  if (l1Select) {
    l1Select.addEventListener('change', function (this: HTMLSelectElement) {
      languageWizard.changeNative(this.value);
    });
  }

  const goButton = document.querySelector('[data-action="wizard-go"]');
  if (goButton) {
    goButton.addEventListener('click', () => languageWizard.go());
  }

  const toggleHeader = document.querySelector('[data-action="wizard-toggle"]');
  if (toggleHeader) {
    toggleHeader.addEventListener('click', () => languageWizard.toggleWizardZone());
  }

  // Set up form check for unsaved changes
  lwtFormCheck.askBeforeExit();
}

// Auto-initialize on DOM ready if config element is present
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('language-wizard-config')) {
    initLanguageWizard();
  }
});

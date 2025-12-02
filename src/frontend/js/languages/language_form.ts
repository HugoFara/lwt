/**
 * Language Form - Handles the language configuration form.
 *
 * Extracted from Views/Language/form.php
 * Provides functions for managing dictionary URLs, translator settings,
 * and form validation.
 *
 * @license Unlicense
 * @since 3.0.0
 */

import { getLibreTranslateTranslation } from '../terms/translation_api';
import { deepFindValue, readTextWithExternal } from '../core/user_interactions';
import { lwtFormCheck } from '../forms/unloadformcheck';

/**
 * Build a URL query string from an object (replaces $.param).
 */
function buildQueryString(params: Record<string, string>): string {
  return new URLSearchParams(params).toString();
}

// Module-level variables for dictionary URLs
let GGTRANSLATE = '';
let LIBRETRANSLATE = '';

/**
 * Configuration for language form.
 * Passed from PHP via JSON.
 */
export interface LanguageFormConfig {
  languageId: number;
  languageName: string;
  sourceLg: string;
  targetLg: string;
  languageDefs: Record<string, [string, string, boolean, string, string, boolean, boolean, boolean]>;
  allLanguages: Record<string, number>;
}

declare global {
  interface Window {
    LANGDEFS: Record<string, [string, string, boolean, string, string, boolean, boolean, boolean]>;
  }
}

/**
 * Language form object.
 * Handles the language configuration form functionality.
 */
export const languageForm = {
  /** Current language ID */
  languageId: 0 as number,

  /** Current language name */
  languageName: '' as string,

  /** Language definitions loaded from config */
  langDefs: {} as Record<string, [string, string, boolean, string, string, boolean, boolean, boolean]>,

  /** All existing languages (name -> id map) */
  allLanguages: {} as Record<string, number>,

  /**
   * Initialize the form with language configuration.
   */
  init(config: LanguageFormConfig): void {
    this.languageId = config.languageId;
    this.languageName = config.languageName;
    this.langDefs = config.languageDefs;
    this.allLanguages = config.allLanguages;

    // Initialize dictionary URLs
    this.reloadDictURLs(config.sourceLg, config.targetLg);
  },

  /**
   * Reload dictionary URLs with the given language codes.
   *
   * @param sourceLg - Source language code (default: 'auto')
   * @param targetLg - Target language code (default: 'en')
   */
  reloadDictURLs(sourceLg = 'auto', targetLg = 'en'): void {
    let baseUrl = window.location.href;
    baseUrl = baseUrl.substring(0, baseUrl.lastIndexOf('/'));

    GGTRANSLATE = 'https://translate.google.com/?' + buildQueryString({
      ie: 'UTF-8',
      sl: sourceLg,
      tl: targetLg,
      text: 'lwt_term'
    });

    LIBRETRANSLATE = 'http://localhost:5000/?' + buildQueryString({
      lwt_translator: 'libretranslate',
      source: sourceLg,
      target: targetLg,
      q: 'lwt_term'
    });
  },

  /**
   * Check if language name has changed and update UI accordingly.
   * Shows/hides the MeCab option for Japanese.
   *
   * @param value - The language name
   */
  checkLanguageChanged(value: string): void {
    const lgForm = document.forms.namedItem('lg_form') as HTMLFormElement | null;
    if (!lgForm) return;

    const regexpAlt = lgForm.elements.namedItem('LgRegexpAlt') as HTMLSelectElement | null;
    if (regexpAlt) {
      regexpAlt.style.display = value === 'Japanese' ? 'block' : 'none';
    }
  },

  /**
   * Handle multi-word translator selection change.
   *
   * @param value - The selected translator type
   */
  multiWordsTranslateChange(value: string): void {
    let result: string | undefined;
    let usesKey = false;
    let baseUrl = window.location.href;
    baseUrl = baseUrl.replace('//languages', '/');

    switch (value) {
      case 'google_translate':
        result = GGTRANSLATE;
        break;
      case 'libretranslate':
        result = LIBRETRANSLATE;
        usesKey = true;
        break;
    }

    if (result) {
      const lgForm = document.forms.namedItem('lg_form') as HTMLFormElement | null;
      if (lgForm) {
        const translatorUri = lgForm.elements.namedItem('LgGoogleTranslateURI') as HTMLInputElement | null;
        if (translatorUri) {
          translatorUri.value = result;
        }
      }
    }

    const keyWrapper = document.getElementById('LgTranslatorKeyWrapper');
    if (keyWrapper) {
      keyWrapper.style.display = usesKey ? 'inherit' : 'none';
    }
  },

  /**
   * Display an error message for LibreTranslate connection issues.
   *
   * @param error - The error message
   */
  displayLibreTranslateError(error: string): void {
    const statusEl = document.getElementById('translator_status');
    if (statusEl) {
      statusEl.innerHTML =
        '<a href="https://libretranslate.com/">LibreTranslate</a> server seems to be unreachable. ' +
        'You can install it on your server with the <a href="">LibreTranslate installation guide</a>. ' +
        'Error: ' + error;
    }
  },

  /**
   * Check the status of a translator URL.
   *
   * @param url - The translator URL to check
   */
  checkTranslatorStatus(url: string): void {
    if (url.startsWith('*')) {
      url = url.substring(1);
    }

    let urlObj: URL;
    try {
      urlObj = new URL(url);
    } catch {
      return;
    }

    const params = urlObj.searchParams;
    if (params.get('lwt_translator') === 'libretranslate') {
      try {
        this.checkLibreTranslateStatus(urlObj, params.get('key') || '');
      } catch (error) {
        this.displayLibreTranslateError(String(error));
      }
    }
  },

  /**
   * Check the status of a LibreTranslate server.
   *
   * @param url - The LibreTranslate URL
   * @param key - Optional API key
   */
  checkLibreTranslateStatus(url: URL, key = ''): void {
    const transUrl = new URL(url.toString());
    transUrl.searchParams.append('lwt_key', key);

    getLibreTranslateTranslation(transUrl, 'ping', 'en', 'es')
      .then((translation: string) => {
        if (typeof translation === 'string') {
          const statusEl = document.getElementById('translator_status');
          if (statusEl) {
            statusEl.innerHTML = '<a href="https://libretranslate.com/">LibreTranslate</a> online!';
            statusEl.className = 'msgblue';
          }
        }
      })
      .catch((error: Error) => {
        this.displayLibreTranslateError(error.message || String(error));
      });
  },

  /**
   * Update the text size example when the slider changes.
   *
   * @param value - The text size percentage
   */
  changeLanguageTextSize(value: string | number): void {
    const exampleEl = document.getElementById('LgTextSizeExample');
    if (exampleEl) {
      exampleEl.style.fontSize = value + '%';
    }
  },

  /**
   * Handle word character method selection change.
   *
   * @param value - The selected method ('regexp' or 'mecab')
   */
  wordCharChange(value: string): void {
    const langDefs = this.langDefs || window.LANGDEFS;
    const regex = langDefs[this.languageName]?.[3] || '';
    const mecab = 'mecab';

    let result: string | undefined;
    switch (value) {
      case 'regexp':
        result = regex;
        break;
      case 'mecab':
        result = mecab;
        break;
    }

    if (result) {
      const lgForm = document.forms.namedItem('lg_form') as HTMLFormElement | null;
      if (lgForm) {
        const regexpWordChars = lgForm.elements.namedItem('LgRegexpWordCharacters') as HTMLInputElement | null;
        if (regexpWordChars) {
          regexpWordChars.value = result;
        }
      }
    }
  },

  /**
   * Add or remove the popup option from a dictionary URL.
   *
   * @param url - The dictionary URL
   * @param checked - Whether the popup option should be enabled
   * @returns The modified URL
   */
  addPopUpOption(url: string, checked: boolean): string {
    if (url.startsWith('*')) {
      url = url.substring(1);
    }

    let builtUrl: URL;
    try {
      builtUrl = new URL(url);
    } catch {
      return url;
    }

    if (checked && builtUrl.searchParams.has('lwt_popup')) {
      return builtUrl.href;
    }
    if (!checked && !builtUrl.searchParams.has('lwt_popup')) {
      return builtUrl.href;
    }
    if (checked) {
      builtUrl.searchParams.append('lwt_popup', 'true');
      return builtUrl.href;
    }
    builtUrl.searchParams.delete('lwt_popup');
    return builtUrl.href;
  },

  /**
   * Handle popup checkbox state change.
   *
   * @param elem - The checkbox element
   */
  changePopUpState(elem: HTMLInputElement): void {
    const lgForm = document.forms.namedItem('lg_form') as HTMLFormElement | null;
    if (!lgForm) return;

    let target: HTMLInputElement | null = null;
    switch (elem.name) {
      case 'LgDict1PopUp':
        target = lgForm.elements.namedItem('LgDict1URI') as HTMLInputElement | null;
        break;
      case 'LgDict2PopUp':
        target = lgForm.elements.namedItem('LgDict2URI') as HTMLInputElement | null;
        break;
      case 'LgGoogleTranslatePopUp':
        target = lgForm.elements.namedItem('LgGoogleTranslateURI') as HTMLInputElement | null;
        break;
    }

    if (target) {
      target.value = this.addPopUpOption(target.value, elem.checked);
    }
  },

  /**
   * Handle dictionary URL input change.
   * Updates the popup checkbox based on the URL.
   *
   * @param inputBox - The input element
   */
  checkDictionaryChanged(inputBox: HTMLInputElement): void {
    const lgForm = document.forms.namedItem('lg_form') as HTMLFormElement | null;
    if (!lgForm || inputBox.value === '') return;

    let target: HTMLInputElement | null = null;
    switch (inputBox.name) {
      case 'LgDict1URI':
        target = lgForm.elements.namedItem('LgDict1PopUp') as HTMLInputElement | null;
        break;
      case 'LgDict2URI':
        target = lgForm.elements.namedItem('LgDict2PopUp') as HTMLInputElement | null;
        break;
      case 'LgGoogleTranslateURI':
        target = lgForm.elements.namedItem('LgGoogleTranslatePopUp') as HTMLInputElement | null;
        break;
    }

    if (!target) return;

    let popup = false;
    if (inputBox.value.startsWith('*')) {
      inputBox.value = inputBox.value.substring(1);
      popup = true;
    }

    try {
      popup = popup || new URL(inputBox.value).searchParams.has('lwt_popup');
    } catch {
      // Invalid URL, keep current popup state
    }

    target.checked = popup;
  },

  /**
   * Check the translator type and update the select box.
   *
   * @param url - The translator URL
   * @param typeSelect - The select element to update
   */
  checkTranslatorType(url: string, typeSelect: HTMLSelectElement): void {
    let parsedUrl: URL;
    try {
      parsedUrl = new URL(url);
    } catch {
      return;
    }

    let finalValue: string;
    switch (parsedUrl.searchParams.get('lwt_translator')) {
      case 'libretranslate':
        finalValue = 'libretranslate';
        break;
      default:
        finalValue = 'google_translate';
        break;
    }
    typeSelect.value = finalValue;
  },

  /**
   * Update the word character method select based on current value.
   *
   * @param method - The current method value
   */
  checkWordChar(method: string): void {
    const methodOption = method === 'mecab' ? 'mecab' : 'regexp';
    const lgForm = document.forms.namedItem('lg_form') as HTMLFormElement | null;
    if (lgForm) {
      const regexpAlt = lgForm.elements.namedItem('LgRegexpAlt') as HTMLSelectElement | null;
      if (regexpAlt) {
        regexpAlt.value = methodOption;
      }
    }
  },

  /**
   * Validate the Voice API JSON configuration.
   *
   * @param apiValue - The API configuration JSON string
   * @returns true if valid, false otherwise
   */
  checkVoiceAPI(apiValue: string): boolean {
    const messageField = document.getElementById('voice-api-message-zone');
    if (!messageField) return true;

    if (apiValue === '') {
      messageField.style.display = 'none';
      return true;
    }

    if (!apiValue.includes('lwt_term')) {
      messageField.textContent = '"lwt_term" is missing!';
      messageField.style.display = '';
      return false;
    }

    let query: Record<string, unknown>;
    try {
      query = JSON.parse(apiValue);
    } catch (error) {
      messageField.textContent = 'Cannot parse as JSON! ' + error;
      messageField.style.display = '';
      return false;
    }

    if (deepFindValue(query, 'lwt_term') === null) {
      messageField.textContent = "Cannot find 'lwt_term' in JSON!";
      messageField.style.display = '';
      return false;
    }

    messageField.style.display = 'none';
    return true;
  },

  /**
   * Test the Voice API with a demo text.
   */
  testVoiceAPI(): void {
    const lgForm = document.forms.namedItem('lg_form') as HTMLFormElement | null;
    if (!lgForm) return;

    const apiValue = (lgForm.elements.namedItem('LgTTSVoiceAPI') as HTMLTextAreaElement)?.value || '';
    const term = (lgForm.elements.namedItem('LgVoiceAPIDemo') as HTMLInputElement)?.value || '';
    readTextWithExternal(term, apiValue, this.languageName);
  },

  /**
   * Perform a full form check on page load.
   */
  fullFormCheck(): void {
    const lgForm = document.forms.namedItem('lg_form') as HTMLFormElement | null;
    if (!lgForm) return;

    checkLanguageForm(lgForm);
  }
};

/**
 * Check the translator input and update related fields.
 *
 * @param translatorInput - The translator URL input element
 */
export function checkTranslatorChanged(translatorInput: HTMLInputElement): void {
  languageForm.checkTranslatorStatus(translatorInput.value);
  languageForm.checkDictionaryChanged(translatorInput);

  const lgForm = document.forms.namedItem('lg_form') as HTMLFormElement | null;
  if (lgForm) {
    const translatorName = lgForm.elements.namedItem('LgTranslatorName') as HTMLSelectElement | null;
    if (translatorName) {
      languageForm.checkTranslatorType(translatorInput.value, translatorName);
    }
  }
}

/**
 * Perform a full validation of the language form.
 *
 * @param lgForm - The language form element
 */
export function checkLanguageForm(lgForm: HTMLFormElement): void {
  const lgName = lgForm.elements.namedItem('LgName') as HTMLInputElement | null;
  const lgDict1URI = lgForm.elements.namedItem('LgDict1URI') as HTMLInputElement | null;
  const lgDict2URI = lgForm.elements.namedItem('LgDict2URI') as HTMLInputElement | null;
  const lgGoogleTranslateURI = lgForm.elements.namedItem('LgGoogleTranslateURI') as HTMLInputElement | null;
  const lgRegexpWordCharacters = lgForm.elements.namedItem('LgRegexpWordCharacters') as HTMLInputElement | null;

  if (lgName) {
    languageForm.checkLanguageChanged(lgName.value);
  }
  if (lgDict1URI) {
    languageForm.checkDictionaryChanged(lgDict1URI);
  }
  if (lgDict2URI) {
    languageForm.checkDictionaryChanged(lgDict2URI);
  }
  if (lgGoogleTranslateURI) {
    checkTranslatorChanged(lgGoogleTranslateURI);
  }
  if (lgRegexpWordCharacters) {
    languageForm.checkWordChar(lgRegexpWordCharacters.value);
  }
}

/**
 * Check for duplicate language names.
 *
 * @param curr - Current language ID (0 for new languages)
 * @param languages - Map of language names to IDs (optional, uses languageForm.allLanguages if not provided)
 * @returns true if no duplicate, false if duplicate found
 */
export function checkDuplicateLanguage(
  curr?: number,
  languages?: Record<string, number>
): boolean {
  const langId = curr ?? languageForm.languageId;
  const allLangs = languages ?? languageForm.allLanguages;
  const lgNameEl = document.getElementById('LgName') as HTMLInputElement | null;
  const lgName = lgNameEl?.value ?? '';

  if (lgName in allLangs) {
    if (langId !== allLangs[lgName]) {
      alert(
        'Language "' + lgName + '" already exists. Please change the language name!'
      );
      lgNameEl?.focus();
      return false;
    }
  }
  return true;
}

/**
 * Initialize the language form from JSON config element.
 */
export function initLanguageForm(): void {
  const configEl = document.getElementById('language-form-config');
  if (!configEl) return;

  let config: LanguageFormConfig;
  try {
    config = JSON.parse(configEl.textContent || '{}');
  } catch (e) {
    console.error('Failed to parse language-form-config:', e);
    return;
  }

  languageForm.init(config);

  // Set up event listeners
  const lgForm = document.forms.namedItem('lg_form') as HTMLFormElement | null;
  if (!lgForm) return;

  // Form submit handler - check for duplicate language names
  lgForm.addEventListener('submit', function (e) {
    if (!checkDuplicateLanguage()) {
      e.preventDefault();
      return false;
    }
    return true;
  });

  // Language name input
  const lgName = lgForm.elements.namedItem('LgName') as HTMLInputElement | null;
  if (lgName) {
    lgName.addEventListener('input', function () {
      languageForm.checkLanguageChanged(this.value);
    });
  }

  // Dictionary inputs
  const dictInputs = ['LgDict1URI', 'LgDict2URI'];
  dictInputs.forEach(name => {
    const input = lgForm.elements.namedItem(name) as HTMLInputElement | null;
    if (input) {
      input.addEventListener('input', function () {
        languageForm.checkDictionaryChanged(this);
      });
    }
  });

  // Translator URL input
  const translatorUri = lgForm.elements.namedItem('LgGoogleTranslateURI') as HTMLInputElement | null;
  if (translatorUri) {
    translatorUri.addEventListener('input', function () {
      checkTranslatorChanged(this);
    });
  }

  // Translator select
  const translatorName = lgForm.elements.namedItem('LgTranslatorName') as HTMLSelectElement | null;
  if (translatorName) {
    translatorName.addEventListener('change', function () {
      languageForm.multiWordsTranslateChange(this.value);
    });
  }

  // Popup checkboxes
  const popupCheckboxes = ['LgDict1PopUp', 'LgDict2PopUp', 'LgGoogleTranslatePopUp'];
  popupCheckboxes.forEach(name => {
    const checkbox = lgForm.elements.namedItem(name) as HTMLInputElement | null;
    if (checkbox) {
      checkbox.addEventListener('change', function () {
        languageForm.changePopUpState(this);
      });
    }
  });

  // Text size input
  const textSize = lgForm.elements.namedItem('LgTextSize') as HTMLInputElement | null;
  if (textSize) {
    textSize.addEventListener('change', function () {
      languageForm.changeLanguageTextSize(this.value);
    });
  }

  // Word character method select
  const regexpAlt = lgForm.elements.namedItem('LgRegexpAlt') as HTMLSelectElement | null;
  if (regexpAlt) {
    regexpAlt.addEventListener('change', function () {
      languageForm.wordCharChange(this.value);
    });
  }

  // Voice API textarea
  const voiceApi = lgForm.elements.namedItem('LgTTSVoiceAPI') as HTMLTextAreaElement | null;
  if (voiceApi) {
    voiceApi.addEventListener('change', function () {
      languageForm.checkVoiceAPI(this.value);
    });
  }

  // Check Voice API button
  const checkVoiceBtn = document.querySelector('[data-action="check-voice-api"]');
  if (checkVoiceBtn) {
    checkVoiceBtn.addEventListener('click', () => {
      const voiceApiEl = lgForm.elements.namedItem('LgTTSVoiceAPI') as HTMLTextAreaElement | null;
      if (voiceApiEl) {
        languageForm.checkVoiceAPI(voiceApiEl.value);
      }
    });
  }

  // Test Voice API button
  const testVoiceBtn = document.querySelector('[data-action="test-voice-api"]');
  if (testVoiceBtn) {
    testVoiceBtn.addEventListener('click', () => {
      languageForm.testVoiceAPI();
    });
  }

  // Cancel button
  const cancelBtn = document.querySelector('[data-action="cancel-form"]') as HTMLButtonElement | null;
  if (cancelBtn) {
    cancelBtn.addEventListener('click', () => {
      lwtFormCheck.resetDirty();
      const redirect = cancelBtn.dataset.redirect || '/languages';
      window.location.href = redirect;
    });
  }

  // Run initial form check (DOM should already be ready at this point)
  languageForm.fullFormCheck();

  // Set up form check for unsaved changes
  lwtFormCheck.askBeforeExit();
}

// Auto-initialize on DOM ready if config element is present
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('language-form-config')) {
    initLanguageForm();
  }
});

/**
 * Word Form Auto-translate and Auto-romanization.
 *
 * Extracted from Views/Word/form_edit_new.php
 * Handles automatic translation and romanization when creating new words.
 *
 * @license unlicense
 * @since 3.0.0
 */

import { getLibreTranslateTranslation } from '@modules/vocabulary/services/translation_api';
import { getPhoneticTextAsync } from '@shared/utils/user_interactions';
import { getLangFromDict } from '@modules/vocabulary/services/dictionary';

/**
 * Configuration for word form auto-translate.
 * Passed from PHP via JSON in a script element.
 */
export interface WordFormConfig {
  transUri: string;
  langShort: string;
  lang: number;
}

/**
 * Get the term text from the word form.
 */
function getTermText(): string {
  const wordField = document.getElementById('wordfield') as HTMLInputElement | null;
  return wordField?.value || '';
}

/**
 * Set the translation field value.
 */
function setTranslation(translation: string): void {
  const form = document.forms.namedItem('newword') as HTMLFormElement | null;
  if (form) {
    const translationField = form.elements.namedItem('WoTranslation') as HTMLTextAreaElement | null;
    if (translationField) {
      translationField.value = translation;
    }
  }
}

/**
 * Set the romanization field value.
 */
function setRomanization(romanization: string): void {
  const form = document.forms.namedItem('newword') as HTMLFormElement | null;
  if (form) {
    const romanField = form.elements.namedItem('WoRomanization') as HTMLInputElement | null;
    if (romanField) {
      romanField.value = romanization;
    }
  }
}

/**
 * Auto-translate the term using LibreTranslate if configured.
 *
 * @param config - Configuration from PHP
 */
export async function autoTranslate(config: WordFormConfig): Promise<void> {
  if (!config.transUri) {
    return;
  }

  try {
    const translatorUrl = new URL(config.transUri);
    const urlParams = translatorUrl.searchParams;

    if (urlParams.get('lwt_translator') === 'libretranslate') {
      const term = getTermText();
      if (!term) return;

      const sourceLang = urlParams.has('source')
        ? urlParams.get('source')!
        : config.langShort;
      const targetLang = urlParams.get('target');

      if (!targetLang) {
        console.warn('LibreTranslate target language not configured');
        return;
      }

      const translation = await getLibreTranslateTranslation(
        translatorUrl,
        term,
        sourceLang,
        targetLang
      );

      setTranslation(translation);
    }
  } catch (error) {
    console.error('Auto-translate failed:', error);
  }
}

/**
 * Auto-romanize the term using the phonetic API.
 *
 * @param langId - Language ID
 */
export async function autoRomanization(langId: number): Promise<void> {
  const term = getTermText();
  if (!term) return;

  try {
    const response = await getPhoneticTextAsync(term, langId);
    if (response && response.phonetic_reading) {
      setRomanization(response.phonetic_reading);
    }
  } catch (error) {
    console.error('Auto-romanization failed:', error);
  }
}

/**
 * Initialize word form auto-features.
 * Reads configuration from JSON script element and runs auto-translate/romanize.
 */
export function initWordFormAuto(): void {
  const configEl = document.getElementById('word-form-config');
  if (!configEl) return;

  let config: WordFormConfig;
  try {
    config = JSON.parse(configEl.textContent || '{}');
  } catch (e) {
    console.error('Failed to parse word-form-config:', e);
    return;
  }

  // Determine language short code
  const langShort = config.langShort || getLangFromDict(config.transUri);
  const fullConfig: WordFormConfig = {
    ...config,
    langShort
  };

  // Run auto-translate and auto-romanization
  autoTranslate(fullConfig);
  autoRomanization(config.lang);
}

// Auto-initialize on DOM ready if config element is present
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('word-form-config')) {
    initWordFormAuto();
  }
});

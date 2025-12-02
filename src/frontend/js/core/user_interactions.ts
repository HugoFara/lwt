/**
 * General file to control dynamic interactions with the user.
 *
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.0.3-fork
 */

import { overlib, cClick } from '../ui/word_popup';
import { scrollTo } from '../core/hover_intent';
import { getTTSSettingsWithMigration, type TTSLanguageSettings } from './tts_storage';

// Type for LWT_DATA global
interface LwtLanguage {
  id: number;
  name: string;
  abbreviation: string;
  reading_mode?: string;
  voiceapi?: string;
}

interface LwtText {
  id: number;
  reading_position: number;
}

interface LwtDataGlobal {
  language: LwtLanguage;
  text: LwtText;
  settings: {
    hts: number;
  };
}

declare const LWT_DATA: LwtDataGlobal;

// Type for text dictionary in newExpressionInteractable
interface TextDictionary {
  [key: string]: string;
}

// Type for language reading configuration
interface ReadingConfiguration {
  reading_mode: 'direct' | 'internal' | 'external';
  name: string;
  abbreviation: string;
  voiceapi?: string;
}

// TTSLanguageSettings is imported from tts_storage.ts

// Type for fetch request structure
interface FetchRequestOptions {
  body?: unknown;
  [key: string]: unknown;
}

interface FetchRequest {
  input: string;
  options: FetchRequestOptions;
}

/**
 * Map of quick menu values to their clean URL routes.
 * Used by quickMenuRedirection to navigate to the correct page.
 */
const quickMenuRoutes: Record<string, string> = {
  index: '/',
  edit_texts: '/texts',
  edit_archivedtexts: '/text/archived',
  edit_texttags: '/tags/text',
  check_text: '/text/check',
  long_text_import: '/text/import-long',
  edit_languages: '/languages',
  edit_words: '/words',
  edit_tags: '/tags',
  upload_words: '/word/upload',
  statistics: '/admin/statistics',
  rss_import: '/feeds',
  backup_restore: '/admin/backup',
  settings: '/admin/settings',
  text_to_speech_settings: '/admin/settings/tts',
  INFO: '/docs/'
};

/**
 * Redirect the user to a specific page depending on the value
 */
export function quickMenuRedirection(value: string): void {
  const qm = document.getElementById('quickmenu') as HTMLSelectElement | null;
  if (qm) {
    qm.selectedIndex = 0;
  }
  if (value === '') { return; }

  const route = quickMenuRoutes[value];
  if (route) {
    top!.location.href = route;
  } else {
    // Fallback for any unmapped values
    console.warn('Quick menu: unknown value "' + value + '", falling back to legacy URL');
    top!.location.href = value + '.php';
  }
}

/**
 * Create an interactable to add a new expression.
 *
 * WARNING! This function was not properly tested!
 *
 * @param text         An array of words forming the expression
 * @param attrs        A group of attributes to add
 * @param length       Number of words, should correspond to WoWordCount
 * @param hex          Lowercase formatted version of the text.
 * @param showallwords true: multi-word is a superscript, show mw index + words
 *                     false: only show the multiword, hide the words
 *
 * @since 2.5.2-fork Don't hide multi-word index when inserting new multi-word.
 */
export function newExpressionInteractable(
  text: TextDictionary,
  attrs: string,
  length: number,
  hex: string,
  showallwords: boolean
): void {
  const context = window.parent.document;
  // From each multi-word group
  for (const key in text) {
    // Remove any previous multi-word of same length + same position
    const existingEl = context.getElementById('ID-' + key + '-' + length);
    existingEl?.remove();

    // From text, select the first mword smaller than this one, or the first
    // word in this mword
    let next_term_key = '';
    for (let j = length - 1; j > 0; j--) {
      if (j === 1) { next_term_key = '#ID-' + key + '-1'; }
      if (context.getElementById('ID-' + key + '-' + j)) {
        next_term_key = '#ID-' + key + '-' + j;
        break;
      }
    }
    // Add the multi-word marker before
    const nextTermEl = context.querySelector<HTMLElement>(next_term_key);
    if (nextTermEl) {
      const newSpan = document.createElement('span');
      newSpan.id = 'ID-' + key + '-' + length;
      // Parse and set attributes from attrs string
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = '<span ' + attrs + '></span>';
      const tempSpan = tempDiv.firstElementChild;
      if (tempSpan) {
        Array.from(tempSpan.attributes).forEach(attr => {
          newSpan.setAttribute(attr.name, attr.value);
        });
      }
      newSpan.innerHTML = text[key];
      nextTermEl.parentNode?.insertBefore(newSpan, nextTermEl);
    }

    // Change multi-word properties
    const multi_word = context.getElementById('ID-' + key + '-' + length);
    if (multi_word) {
      multi_word.classList.add('order' + key);
      multi_word.setAttribute('data_order', key);

      // Get text from elements between this and the end
      const endKey = parseInt(key) + length * 2 - 1;
      const endEl = context.getElementById('ID-' + endKey + '-1');
      let txt = '';
      let currentEl: Element | null = multi_word.nextElementSibling;
      while (currentEl && currentEl !== endEl) {
        if (currentEl.id?.endsWith('-1')) {
          txt += currentEl.textContent || '';
        }
        currentEl = currentEl.nextElementSibling;
      }

      const firstWordEl = context.getElementById('ID-' + key + '-1');
      const pos: string = firstWordEl?.getAttribute('data_pos') || '';
      multi_word.setAttribute('data_text', txt);
      multi_word.setAttribute('data_pos', pos);
    }

    // Hide the next words if necessary
    if (showallwords) {
      return;
    }
    // TODO: overlapsing multi-words
    for (let i = 0; i < length * 2 - 1; i++) {
      const wordEl = context.querySelector<HTMLElement>('span[id="ID-' + (parseInt(key) + i) + '-1"]');
      if (wordEl) {
        wordEl.style.display = 'none';
      }
    }
  }
}

/**
 * Scroll to a specific reading position
 *
 * @since 2.0.3-fork
 */
export function goToLastPosition(): void {
  // Last registered position to go to
  const lookPos = LWT_DATA.text.reading_position;
  // Element to scroll to
  let targetElement: HTMLElement | null = null;
  if (lookPos > 0) {
    // Find element with matching data_pos
    const allWsty = Array.from(document.querySelectorAll<HTMLElement>('.wsty:not(.hide)'));
    const exactMatch = allWsty.find(el => el.getAttribute('data_pos') === String(lookPos));

    if (!exactMatch) {
      // Find the last element with data_pos <= lookPos
      const filtered = allWsty.filter(el => {
        const dataPosAttr = el.getAttribute('data_pos');
        return parseInt(dataPosAttr || '0', 10) <= lookPos;
      });
      if (filtered.length > 0) {
        targetElement = filtered[filtered.length - 1];
      }
    } else {
      targetElement = exactMatch;
    }
  }
  if (targetElement) {
    scrollTo(targetElement);
  } else {
    scrollTo(0);
  }
  focus();
  setTimeout(overlib, 10);
  setTimeout(cClick, 100);
}

/**
 * Save the current reading position.
 *
 * @param text_id Text id
 * @param position Position to save
 *
 * @since 2.9.0-fork
 */
export function saveReadingPosition(text_id: number, position: number): void {
  fetch('api.php/v1/texts/' + text_id + '/reading-position', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'position=' + encodeURIComponent(position)
  });
}

/**
 * Save audio position
 */
export function saveAudioPosition(text_id: number, pos: number): void {
  fetch('api.php/v1/texts/' + text_id + '/audio-position', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'position=' + encodeURIComponent(pos)
  });
}

/**
 * Get the phonetic version of a text, asynchronous.
 *
 * @param text Text to convert to phonetics.
 * @param lang Language, either two letters code or four letters (BCP 47), or language ID
 */
export function getPhoneticTextAsync(
  text: string,
  lang: string | number
): Promise<{ phonetic_reading: string }> {
  const params = new URLSearchParams();
  params.append('text', text);
  if (typeof lang === 'number') {
    params.append('lang_id', String(lang));
  } else {
    params.append('lang', lang);
  }
  return fetch('api.php/v1/phonetic-reading?' + params.toString())
    .then(response => response.json());
}

/**
 * Replace any searchValue on object value by replaceValue with deepth.
 *
 * @param obj Object to search in
 * @param searchValue Value to find
 * @param replaceValue Value to replace with
 */
export function deepReplace(
  obj: Record<string, unknown>,
  searchValue: string,
  replaceValue: string
): void {
  for (const key in obj) {
    if (typeof obj[key] === 'object' && obj[key] !== null) {
      // Recursively search nested objects
      deepReplace(obj[key] as Record<string, unknown>, searchValue, replaceValue);
    } else if (typeof obj[key] === 'string' && (obj[key] as string).includes(searchValue)) {
      // If the property is a string and contains the searchValue, replace it
      obj[key] = (obj[key] as string).replace(searchValue, replaceValue);
    }
  }
}

/**
 * Find the first string starting with searchValue in object.
 *
 * @param obj         Object to search in
 * @param searchValue Value to search
 */
export function deepFindValue(obj: Record<string, unknown>, searchValue: string): string | null {
  for (const key in obj) {
    if (Object.prototype.hasOwnProperty.call(obj, key)) {
      if (typeof obj[key] === 'string' && (obj[key] as string).startsWith(searchValue)) {
        return obj[key] as string;
      } else if (typeof obj[key] === 'object' && obj[key] !== null) {
        const result = deepFindValue(obj[key] as Record<string, unknown>, searchValue);
        if (result) {
          return result;
        }
      }
    }
  }
  return null; // Return null if no matching string is found
}

/**
 * Read text aloud using an external API service.
 * Makes a fetch request to an external TTS service and plays the returned audio.
 *
 * @param text Text to be read aloud
 * @param voice_api JSON string containing fetch request configuration
 * @param lang Language code for the text
 */
export function readTextWithExternal(text: string, voice_api: string, lang: string): void {
  const fetchRequest: FetchRequest = JSON.parse(voice_api);

  // TODO: can expose more vars to Request
  deepReplace(fetchRequest as unknown as Record<string, unknown>, 'lwt_term', text);
  deepReplace(fetchRequest as unknown as Record<string, unknown>, 'lwt_lang', lang);

  fetchRequest.options.body = JSON.stringify(fetchRequest.options.body);

  fetch(fetchRequest.input, fetchRequest.options as RequestInit)
    .then(response => response.json())
    .then((data: Record<string, unknown>) => {
      const encodeString = deepFindValue(data, 'data:');
      if (encodeString) {
        const utter = new Audio(encodeString);
        utter.play();
      }
    })
    .catch(error => {
      console.error(error);
    });
}

/**
 * Retrieve TTS (Text-to-Speech) settings from localStorage for a specific language.
 * Reads Rate, Pitch, and Voice settings from localStorage.
 *
 * @param language Language code to get TTS settings for
 * @returns TTSLanguageSettings object with rate, pitch, and voice properties
 *
 * @since 3.0.0 Changed from cookies to localStorage
 */
export function cookieTTSSettings(language: string): TTSLanguageSettings {
  // Use the first two characters of the language code (e.g., "en" from "en-US")
  const langCode = language.substring(0, 2).toLowerCase();
  return getTTSSettingsWithMigration(langCode);
}

/**
 * Read a text aloud, works with a phonetic version only.
 *
 * @param text  Text to read, won't be parsed further.
 * @param lang  Language code with BCP 47 convention
 *              (e. g. "en-US" for English with an American accent)
 * @param rate  Reading rate
 * @param pitch Pitch value
 * @param voice Optional voice
 *
 * @return The spoken message object
 *
 * @since 2.9.0 Accepts "voice" as a new optional argument
 */
export function readRawTextAloud(
  text: string,
  lang: string,
  rate?: number,
  pitch?: number,
  voice?: string
): SpeechSynthesisUtterance {
  const msg = new SpeechSynthesisUtterance();
  const tts_settings = cookieTTSSettings(lang.substring(0, 2));
  msg.text = text;
  if (lang) {
    msg.lang = lang;
  }
  // Voice is a string but we have to assign a SpeechSynthesysVoice
  const useVoice = voice || tts_settings.voice;
  if (useVoice) {
    const voices = window.speechSynthesis.getVoices();
    for (let i = 0; i < voices.length; i++) {
      if (voices[i].name === useVoice) {
        msg.voice = voices[i];
      }
    }
  }
  if (rate) {
    msg.rate = rate;
  } else if (tts_settings.rate) {
    msg.rate = tts_settings.rate;
  }
  if (pitch) {
    msg.pitch = pitch;
  } else if (tts_settings.pitch) {
    msg.pitch = tts_settings.pitch;
  }
  window.speechSynthesis.speak(msg);
  return msg;
}

/**
 * Read a text aloud, may parse the text to get a phonetic version.
 *
 * @param text   Text to read, do not need to be phonetic
 * @param lang   Language code with BCP 47 convention
 *               (e. g. "en-US" for English with an American accent)
 * @param rate   Reading rate
 * @param pitch  Pitch value
 * @param voice  Optional voice, the result will depend on the browser used
 * @param convert_to_phonetic Whether to convert to phonetic first
 *
 * @since 2.9.0 Accepts "voice" as a new optional argument
 */
export function readTextAloud(
  text: string,
  lang: string,
  rate?: number,
  pitch?: number,
  voice?: string,
  convert_to_phonetic?: boolean
): void {
  if (convert_to_phonetic) {
    getPhoneticTextAsync(text, lang)
      .then(
        function (data: { phonetic_reading: string }) {
          readRawTextAloud(
            data.phonetic_reading, lang, rate, pitch, voice
          );
        }
      );
  } else {
    readRawTextAloud(text, lang, rate, pitch, voice);
  }
}

/**
 * Handle text reading based on language reading configuration.
 * Supports three modes: direct (read as-is), internal (parse then read), and external (use API).
 *
 * @param language Reading configuration for the language
 * @param term Text to be read aloud
 * @param lang_id Language ID for API calls
 */
export function handleReadingConfiguration(
  language: ReadingConfiguration,
  term: string,
  lang_id: number
): void {
  if (language.reading_mode === 'direct' || language.reading_mode === 'internal') {
    const lang_settings = cookieTTSSettings(language.name);
    if (language.reading_mode === 'direct') {
      // No reparsing needed
      readRawTextAloud(
        term,
        language.abbreviation,
        lang_settings.rate,
        lang_settings.pitch,
        lang_settings.voice
      );
    } else {
      // Server handled reparsing
      getPhoneticTextAsync(term, lang_id)
        .then(
          function (reparsed_text: { phonetic_reading: string }) {
            readRawTextAloud(
              reparsed_text.phonetic_reading,
              language.abbreviation,
              lang_settings.rate,
              lang_settings.pitch,
              lang_settings.voice
            );
          }
        );
    }
  } else if (language.reading_mode === 'external') {
    // Use external API
    readTextWithExternal(term, language.voiceapi || '', language.name);
  }
}

/**
 * Dispatcher function to read text aloud based on language configuration.
 * Fetches the reading configuration from the API and delegates to handleReadingConfiguration.
 *
 * @param term Text to be read aloud
 * @param lang_id Language ID
 * @returns Promise resolving when the fetch completes
 */
export function speechDispatcher(
  term: string,
  lang_id: number
): Promise<ReadingConfiguration> {
  const params = new URLSearchParams();
  params.append('lang_id', String(lang_id));

  return fetch('api.php/v1/languages/' + lang_id + '/reading-configuration?' + params.toString())
    .then(response => response.json())
    .then((data: ReadingConfiguration) => {
      handleReadingConfiguration(data, term, lang_id);
      return data;
    });
}

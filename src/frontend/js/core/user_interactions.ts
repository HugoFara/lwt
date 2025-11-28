/**
 * General file to control dynamic interactions with the user.
 *
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.0.3-fork
 */

import { getCookie } from '../core/cookies';
import { overlib, cClick } from '../ui/word_popup';
import { scrollTo } from '../core/hover_intent';

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
    jQuery_tooltip: boolean;
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

// Type for TTS settings
interface TTSSettings {
  rate?: number;
  pitch?: number;
  voice?: string;
}

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
 * Redirect the user to a specific page depending on the value
 */
export function quickMenuRedirection(value: string): void {
  const qm = document.getElementById('quickmenu') as HTMLSelectElement | null;
  if (qm) {
    qm.selectedIndex = 0;
  }
  if (value === '') { return; }
  if (value === 'INFO') {
    top!.location.href = 'docs/info.html';
  } else if (value === 'rss_import') {
    top!.location.href = 'do_feeds.php?check_autoupdate=1';
  } else {
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
    $('#ID-' + key + '-' + length, context).remove();

    // From text, select the first mword smaller than this one, or the first
    // word in this mword
    let next_term_key = '';
    for (let j = length - 1; j > 0; j--) {
      if (j === 1) { next_term_key = '#ID-' + key + '-1'; }
      if ($('#ID-' + key + '-' + j, context).length) {
        next_term_key = '#ID-' + key + '-' + j;
        break;
      }
    }
    // Add the multi-word marker before
    $(next_term_key, context)
      .before(
        '<span id="ID-' + key + '-' + length + '"' + attrs + '>' + text[key] +
            '</span>'
      );

    // Change multi-word properties
    const multi_word = $('#ID-' + key + '-' + length, context);
    multi_word.addClass('order' + key).attr('data_order', key);
    const txt: string = multi_word
      .nextUntil(
        $('#ID-' + (parseInt(key) + length * 2 - 1) + '-1', context),
        '[id$="-1"]'
      )
      .map(function () {
        return $(this).text();
      })
      .get().join('');
    const posAttr = $('#ID-' + key + '-1', context).attr('data_pos');
    const pos: string = typeof posAttr === 'string' ? posAttr : '';
    multi_word.attr('data_text', txt).attr('data_pos', pos);

    // Hide the next words if necessary
    if (showallwords) {
      return;
    }
    const next_words: string[] = [];
    // TODO: overlapsing multi-words
    for (let i = 0; i < length * 2 - 1; i++) {
      next_words.push('span[id="ID-' + (parseInt(key) + i) + '-1"]');
    }
    $(next_words.join(','), context).hide();
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
    const posObj = $('.wsty[data_pos=' + lookPos + ']').not('.hide').eq(0);
    if (posObj.attr('data_pos') === undefined) {
      const found = $('.wsty').not('.hide').filter(function () {
        const dataPosAttr = $(this).attr('data_pos');
        return parseInt(typeof dataPosAttr === 'string' ? dataPosAttr : '0', 10) <= lookPos;
      }).eq(-1);
      if (found.length > 0) {
        targetElement = found[0] as HTMLElement;
      }
    } else if (posObj.length > 0) {
      targetElement = posObj[0] as HTMLElement;
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
  $.post(
    'api.php/v1/texts/' + text_id + '/reading-position',
    { position: position }
  );
}

/**
 * Save audio position
 */
export function saveAudioPosition(text_id: number, pos: number): void {
  $.post(
    'api.php/v1/texts/' + text_id + '/audio-position',
    { position: pos }
  );
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
): JQuery.jqXHR<{ phonetic_reading: string }> {
  const parameters: { text: string; lang?: string; lang_id?: number } = {
    text: text
  };
  if (typeof lang === 'number') {
    parameters.lang_id = lang;
  } else {
    parameters.lang = lang;
  }
  return $.getJSON(
    'api.php/v1/phonetic-reading',
    parameters
  );
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
 * Retrieve TTS (Text-to-Speech) settings from cookies for a specific language.
 * Reads Rate, Pitch, and Voice settings from browser cookies.
 *
 * @param language Language code to get TTS settings for
 * @returns TTSSettings object with rate, pitch, and voice properties
 */
export function cookieTTSSettings(language: string): TTSSettings {
  const prefix = 'tts[' + language;
  const lang_settings: TTSSettings = {};
  const num_vals = ['Rate', 'Pitch'];
  const cookies = ['Rate', 'Pitch', 'Voice'];
  let cookie_val: string | null;
  for (const cook of cookies) {
    cookie_val = getCookie(prefix + cook + ']');
    if (cookie_val) {
      if (num_vals.includes(cook)) {
        (lang_settings as Record<string, unknown>)[cook.toLowerCase()] = parseFloat(cookie_val);
      } else {
        (lang_settings as Record<string, unknown>)[cook.toLowerCase()] = cookie_val;
      }
    }
  }
  return lang_settings;
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
 * @returns jQuery AJAX promise
 */
export function speechDispatcher(
  term: string,
  lang_id: number
): JQuery.jqXHR<ReadingConfiguration> {
  return $.getJSON(
    'api.php/v1/languages/' + lang_id + '/reading-configuration',
    { lang_id },
    (data: ReadingConfiguration) => handleReadingConfiguration(data, term, lang_id)
  );
}


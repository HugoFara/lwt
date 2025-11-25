/**
 * General file to control dynamic interactions with the user.
 *
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.0.3-fork
 */

/**
 * Redirect the user to a specific page depending on the value
 */
function quickMenuRedirection (value) {
  const qm = document.getElementById('quickmenu');
  qm.selectedIndex = 0;
  if (value == '') { return; }
  if (value == 'INFO') {
    top.location.href = 'docs/info.html';
  } else if (value == 'rss_import') {
    top.location.href = 'do_feeds.php?check_autoupdate=1';
  } else {
    top.location.href = value + '.php';
  }
}

/**
 * Create an interactable to add a new expression.
 *
 * WARNING! This function was not properly tested!
 *
 * @param {string[]} text         An array of words forming the expression
 * @param {string}   attrs        A group of attributes to add
 * @param {int}      length       Number of words, should correspond to WoWordCount
 * @param {string}   hex          Lowercase formatted version of the text.
 * @param {bool}     showallwords true: multi-word is a superscript, show mw index + words
 *                                false: only show the multiword, hide the words
 * @returns {undefined}
 *
 * @since 2.5.2-fork Don't hide multi-word index when inserting new multi-word.
 */
function newExpressionInteractable (text, attrs, length, hex, showallwords) {
  const context = window.parent.document;
  // From each multi-word group
  for (key in text) {
    // Remove any previous multi-word of same length + same position
    $('#ID-' + key + '-' + length, context).remove();

    // From text, select the first mword smaller than this one, or the first
    // word in this mword
    let next_term_key = '';
    for (let j = length - 1; j > 0; j--) {
      if (j == 1) { next_term_key = '#ID-' + key + '-1'; }
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
    const txt = multi_word
      .nextUntil(
        $('#ID-' + (parseInt(key) + length * 2 - 1) + '-1', context),
        '[id$="-1"]'
      )
      .map(function () {
        return $(this).text();
      })
      .get().join('');
    const pos = $('#ID-' + key + '-1', context).attr('data_pos');
    multi_word.attr('data_text', txt).attr('data_pos', pos);

    // Hide the next words if necessary
    if (showallwords) {
      return;
    }
    const next_words = [];
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
function goToLastPosition () {
  // Last registered position to go to
  const lookPos = LWT_DATA.text.reading_position;
  // Position to scroll to
  let pos = 0;
  if (lookPos > 0) {
    const posObj = $('.wsty[data_pos=' + lookPos + ']').not('.hide').eq(0);
    if (posObj.attr('data_pos') === undefined) {
      pos = $('.wsty').not('.hide').filter(function () {
        return $(this).attr('data_pos') <= lookPos;
      }).eq(-1);
    }
  }
  $(document).scrollTo(pos);
  focus();
  setTimeout(overlib, 10);
  setTimeout(cClick, 100);
}

/**
 * Save the current reading position.
 *
 * @param {int} text_id Text id
 * @param {int} position Position to save
 *
 * @since 2.9.0-fork
 */
function saveReadingPosition (text_id, position) {
  $.post(
    'api.php/v1/texts/' + text_id + '/reading-position',
    { position: position }
  );
}

/**
 * Save audio position
 */
function saveAudioPosition (text_id, pos) {
  $.post(
    'api.php/v1/texts/' + text_id + '/audio-position',
    { position: pos }
  );
}

/**
 * Get the phonetic version of a text.
 *
 * @param {string} text Text to convert to phonetics.
 * @param {string} lang Language, either two letters code or four letters (BCP 47).
 *
 * @deprecated Since 2.10.0 use getPhoneticTextAsync
 */
function getPhoneticText (text, lang) {
  let phoneticText;
  $.ajax(
    'api.php/v1/phonetic-reading',
    {
      async: false,
      data: {
        text: text,
        lang: lang
      },
      dataType: 'json',
      type: 'GET'
    }
  )
    .done(
      function (data) {
        phoneticText = data.phonetic_reading;
      }
    );
  return phoneticText;
}

/**
 * Get the phonetic version of a text, asynchronous.
 *
 * @param {string}        text Text to convert to phonetics.
 * @param {string|number} lang Language, either two letters code or four letters (BCP 47), or language ID
 */
async function getPhoneticTextAsync (text, lang) {
  const parameters = {
    text: text
  };
  if (typeof lang == 'number') {
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
 * @param {dict} obj Object to search in
 * @param {string} searchValue Value to find
 * @param {string} replaceValue Value to replace with
 * */
function deepReplace (obj, searchValue, replaceValue) {
  for (let key in obj) {
    if (typeof obj[key] === 'object') {
      // Recursively search nested objects
      deepReplace(obj[key], searchValue, replaceValue);
    } else if (typeof obj[key] === 'string' && obj[key].includes(searchValue)) {
      // If the property is a string and contains the searchValue, replace it
      obj[key] = obj[key].replace(searchValue, replaceValue);
    }
  }
}

/**
 * Find the first string starting with searchValue in object.
 *
 * @param {dict}   obj         Object to search in
 * @param {string} searchValue Value to search
 */
function deepFindValue (obj, searchValue) {
  for (const key in obj) {
    if (obj.hasOwnProperty(key)) {
      if (typeof obj[key] === 'string' && obj[key].startsWith(searchValue)) {
        return obj[key];
      } else if (typeof obj[key] === 'object') {
        const result = deepFindValue(obj[key], searchValue);
        if (result) {
          return result;
        }
      }
    }
  }
  return null; // Return null if no matching string is found
}

function readTextWithExternal (text, voice_api, lang) {
  const fetchRequest = JSON.parse(voice_api);

  // TODO: can expose more vars to Request
  deepReplace(fetchRequest, 'lwt_term', text)
  deepReplace(fetchRequest, 'lwt_lang', lang)

  fetchRequest.options.body = JSON.stringify(fetchRequest.options.body)

  fetch(fetchRequest.input, fetchRequest.options)
    .then(response => response.json())
    .then(data => {
      const encodeString = deepFindValue(data, 'data:')
      const utter = new Audio(encodeString)
      utter.play()
    })
    .catch(error => {
      console.error(error)
    });
}

function cookieTTSSettings (language) {
  const prefix = 'tts[' + language;
  const lang_settings = {};
  const num_vals = ['Rate', 'Pitch'];
  const cookies = ['Rate', 'Pitch', 'Voice'];
  let cookie_val;
  for (const cook in cookies) {
    cookie_val = getCookie(prefix + cook + ']');
    if (cookie_val) {
      if (num_vals.includes(cook)) {
        lang_settings[cook.toLowerCase()] = parseFloat(cookie_val);
      } else {
        lang_settings[cook.toLowerCase()] = cookie_val;
      }
    }
  }
  return lang_settings;
}

/**
 * Read a text aloud, works with a phonetic version only.
 *
 * @param {string} text  Text to read, won't be parsed further.
 * @param {string} lang  Language code with BCP 47 convention
 *                       (e. g. "en-US" for English with an American accent)
 * @param {number} rate  Reading rate
 * @param {number} pitch Pitch value
 *
 * @return {SpeechSynthesisUtterance} The spoken message object
 *
 * @since 2.9.0 Accepts "voice" as a new optional argument
 */
function readRawTextAloud (text, lang, rate, pitch, voice) {
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
 * @param {string} text   Text to read, do not need to be phonetic
 * @param {string} lang   Language code with BCP 47 convention
 *                        (e. g. "en-US" for English with an American accent)
 * @param {number} rate   Reading rate
 * @param {number} pitch  Pitch value
 * @param {string} voice Optional voice, the result will depend on the browser used
 *
 * @since 2.9.0 Accepts "voice" as a new optional argument
 */
function readTextAloud (text, lang, rate, pitch, voice, convert_to_phonetic) {
  if (convert_to_phonetic) {
    getPhoneticTextAsync(text, lang)
      .then(
        function (data) {
          readRawTextAloud(
            data.phonetic_reading, lang, rate, pitch, voice
          );
        }
      );
  } else {
    readRawTextAloud(text, lang, rate, pitch, voice);
  }
}

function handleReadingConfiguration(language, term, lang_id) {
  if (language.reading_mode == "direct" || language.reading_mode == "internal") {
    const lang_settings = cookieTTSSettings(language.name);
    if (language.reading_mode == "direct") {
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
      getPhoneticTextAsync(term, parseInt(lang_id, 10))
        .then(
          function (reparsed_text) {
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
  } else if (language.reading_mode == "external") {
    // Use external API
    readTextWithExternal(term, language.voiceapi, language.name);
  }

}

function speechDispatcher (term, lang_id) {
  return $.getJSON(
    'api.php/v1/languages/' + lang_id + '/reading-configuration',
    { lang_id },
    (data) => handleReadingConfiguration(data, term, parseInt(lang_id, 10))
  );
}

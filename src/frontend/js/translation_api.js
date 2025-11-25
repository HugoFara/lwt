/**
 * Standard JS interface to get translations
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   1.6.16-fork
 */

function deleteTranslation () {
  let frame = window.parent.frames.ro;
  if (frame === undefined) { frame = window.opener; }
  if ($('[name="WoTranslation"]', frame.document).val().trim().length) {
    $('[name="WoTranslation"]', frame.document).val('');
    frame.lwtFormCheck.makeDirty();
  }
}

function addTranslation (s) {
  let frame = window.parent.frames.ro;
  if (frame === undefined) { frame = window.opener; }
  if (frame === undefined) {
    alert('Translation can not be copied!');
    return;
  }
  const word_trans = frame.document.forms[0].WoTranslation;
  if (typeof word_trans !== 'object') {
    alert('Translation can not be copied!');
    return;
  }
  const oldValue = word_trans.value;
  if (oldValue.trim() == '') {
    word_trans.value = s;
    frame.lwtFormCheck.makeDirty();
  } else {
    if (oldValue.indexOf(s) == -1) {
      word_trans.value = oldValue + ' / ' + s;
      frame.lwtFormCheck.makeDirty();
    } else {
      if (confirm(
        '"' + s + '" seems already to exist as a translation.\n' +
				'Insert anyway?'
      )) {
        word_trans.value = oldValue + ' / ' + s;
        frame.lwtFormCheck.makeDirty();
      }
    }
  }
}

function getGlosbeTranslation (text, lang, dest) {
  // Note from 2.9.0: make asynchronous if possible
  // Note: the Glosbe API is closed and may not be open again
  $.ajax({
    url: 'http://glosbe.com/gapi/translate?' + $.param({
      from: lang,
      dest: dest,
      format: 'json',
      phrase: text,
      callback: '?'
    }),
    type: 'GET',
    dataType: 'jsonp',
    jsonp: 'getTranslationFromGlosbeApi',
    jsonpCallback: 'getTranslationFromGlosbeApi',
    async: 'true'
  });
}

function getTranslationFromGlosbeApi (data) {
  try {
    $.each(data.tuc, function (i, rows) {
      if (rows.phrase) {
        $('#translations')
          .append(
            '<span class="click" onclick="addTranslation(\'' +
					rows.phrase.text + '\');">' +
						'<img src="icn/tick-button.png" title="Copy" alt="Copy" />' +
						' &nbsp; ' + rows.phrase.text +
					'</span><br />'
          );
      } else if (rows.meanings) {
        $('#translations')
          .append(
            '<span class="click" onclick="addTranslation(' + "'(" +
					rows.meanings[0].text + ")'" + ');">' +
						'<img src="icn/tick-button.png" title="Copy" alt="Copy" />' +
						' &nbsp; ' + '(' + rows.meanings[0].text + ')' +
					'</span><br />'
          );
      }
    });
    if (!data.tuc.length) {
      $('#translations')
        .before(
          '<p>No translations found (' + data.from + '-' + data.dest + ').</p>'
        );
      if (data.dest != 'en' && data.from != 'en') {
        $('#translations').attr('id', 'no_trans')
          .after(
            '<hr /><p>&nbsp;</p><h3><a href="http://glosbe.com/' +
					data.from + '/en/' + data.phrase + '">Glosbe Dictionary (' +
					data.from + '-en):  &nbsp; <span class="red2">' +
					data.phrase + '</span></a></h3>&nbsp;<p id="translations"></p>'
          );
        getGlosbeTranslation(data.phrase, data.from, 'en');
      } else $('#translations').after('<hr />');
    } else {
      $('#translations')
        .after('<p>&nbsp;<br/>' + data.tuc.length + ' translation' +
			(data.tuc.length == 1 ? '' : 's') +
			' retrieved via <a href="http://glosbe.com/a-api" target="_blank">' +
			'Glosbe API</a>.</p><hr />'
        );
    }
  } catch (err) {
    $('#translations')
      .text(
        'Retrieval error. Possible reason: There is a limit of Glosbe API ' +
			'calls that may be done from one IP address in a fixed period of time,' +
			' to prevent from abuse.'
      )
      .after('<hr />');
  }
}

/**
 * Base function to get a translation from LibreTranslate.
 *
 * @param {string}           text Text to translate
 * @param {string}           lang Source language (language of the text, two letters or "auto")
 * @param {string}           dest Destination language (two language)
 * @param {string|undefined} key  Optional API key
 * @param {string}           url  API URL
 * @returns {string} Translation
 */
async function getLibreTranslateTranslationBase (
  text, lang, dest, key = '', url = 'http://localhost:5000/translate'
) {
  const res = await fetch(
    url,
    {
      method: 'POST',
      body: JSON.stringify({
        q: text,
        source: lang,
        target: dest,
        format: 'text',
        api_key: key
      }),
      headers: { 'Content-Type': 'application/json' }
    }
  );

  const data = await res.json();
  return data.translatedText;
}

/**
 * Main wrapper for LibreTranslate translation.
 *
 * @param {URL}    libre_url URL of LibreTranslate.
 * @param {string} text      Text to translate
 * @param {string} lang      Source language (language of the text, two letters or "auto")
 * @param {string} dest      Destination language (two language)
 * @returns {string} Translation
 */
async function getLibreTranslateTranslation (libre_url, text, lang, dest) {
  const search_params = libre_url.searchParams;
  if (search_params.get('lwt_translator') != 'libretranslate') {
    throw 'Translation API not supported: ' +
		search_params.get('lwt_translator') + '!';
  }
  let translator_ajax;
  if (search_params.get('lwt_translator_ajax')) {
    translator_ajax = decodeURIComponent(search_params.get('lwt_translator_ajax'));
  } else {
    translator_ajax = libre_url.toString().replace(libre_url.search, '') + 'translate';
  }
  return getLibreTranslateTranslationBase(
    text, lang, dest, key = search_params.get('lwt_key'), translator_ajax
  );
}

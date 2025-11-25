/**
 * LWT Javascript functions
 * 
 * @author  HugoFara <HugoFara@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 */

/**************************************************************
Global variables for OVERLIB
***************************************************************/

/**
 * OVERLIB text font
 */
var ol_textfont = '"Lucida Grande",Arial,sans-serif,STHeiti,"Arial Unicode MS",MingLiu';
var ol_textsize = 3;
var ol_sticky = 1;
var ol_captionfont = '"Lucida Grande",Arial,sans-serif,STHeiti,"Arial Unicode MS",MingLiu';
var ol_captionsize = 3;
var ol_width = 260;
var ol_close = 'Close';
var ol_offsety = 30;
var ol_offsetx = 3;
var ol_fgcolor = '#FFFFE8';
var ol_closecolor = '#FFFFFF';

/**************************************************************
 * Helper functions for overlib
 ***************************************************************/

/**
 * Handle click event on ignored words
 * 
 * @param {string}    wblink1     First dictionary URL
 * @param {string}    wblink2     Second dictionary URL
 * @param {string}    wblink3     Google Translate dictionary URL
 * @param {string}    hints       Hint for the word
 * @param {int}       txid        Text ID
 * @param {*}         torder 
 * @param {string}    txt         Text
 * @param {int}       wid         Word ID 
 * @param {*}         multi_words 
 * @param {boolean}   rtl         Right-to-left text indicator
 * @param {*}         ann 
 * @returns {boolean}
 */
function run_overlib_status_98(
  wblink1, wblink2, wblink3, hints, txid, torder, txt, wid, multi_words, rtl, ann
) {
  const lang = getLangFromDict(wblink3);
  return overlib(
    make_overlib_audio(txt, lang) + 
    '<b>' + escape_html_chars_2(hints, ann) + '</b><br/>' +
    make_overlib_link_new_word(txid, torder, wid) + ' | ' +
    make_overlib_link_delete_word(txid, wid) +
    make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
    make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION,
    'Word'
  );
}

/**
 * Handle click event on well-known words
 * 
 * @param {string}    wblink1     First dictionary URL
 * @param {string}    wblink2     Second dictionary URL
 * @param {string}    wblink3     Google Translate dictionary URL
 * @param {string}    hints       Hint for the word
 * @param {int}       txid        Text ID
 * @param {*}         torder 
 * @param {string}    txt         Text
 * @param {int}       wid         Word ID 
 * @param {*} multi_words 
 * @param {boolean}   rtl         Right-to-left text indicator
 * @param {*} ann 
 * @returns {boolean}
 */
function run_overlib_status_99 (
  wblink1, wblink2, wblink3, hints, txid, torder, txt, wid, multi_words, rtl, ann
  ) {
  const lang = getLangFromDict(wblink3);
  return overlib(
    make_overlib_audio(txt, lang) + 
    '<b>' + escape_html_chars_2(hints, ann) + '</b><br/> ' +
		make_overlib_link_new_word(txid, torder, wid) + ' | ' +
		make_overlib_link_delete_word(txid, wid) +
		make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
		make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION, 
    'Word'
  );
}

/**
 * Handle click event on learning words (levels 1 to 5)
 * 
 * @param {string}    wblink1     First dictionary URL
 * @param {string}    wblink2     Second dictionary URL
 * @param {string}    wblink3     Google Translate dictionary URL
 * @param {string}    hints       Hint for the word
 * @param {int}       txid        Text ID
 * @param {*}         torder 
 * @param {string}    txt         Text
 * @param {int}       wid         Word ID 
 * @param {int}       stat 
 * @param {*} multi_words 
 * @param {boolean}   rtl         Right-to-left text indicator
 * @param {*}         ann         Unused
 * @returns {boolean}
 */
function run_overlib_status_1_to_5 (
  wblink1, wblink2, wblink3, hints, txid, 
  torder, txt, wid, stat, multi_words, rtl, ann
  ) {
  const lang = getLangFromDict(wblink3);
  return overlib(
    '<div>' + make_overlib_audio(txt, lang) + '<span>(Read)</span></div>' +
    make_overlib_link_change_status_all(txid, torder, wid, stat) + ' <br /> ' +
		make_overlib_link_edit_word(txid, torder, wid) + ' | ' +
		make_overlib_link_delete_word(txid, wid) +
		make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
		make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION, 
    make_overlib_link_edit_word_title(
      'Word &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;', 
      txid, torder, wid
    )
  );
}

/**
 * Handle click event on unknown words.
 * 
 * @param {string}        wblink1     First dictionary URL
 * @param {string}        wblink2     Second dictionary URL
 * @param {string}        wblink3     Google Translate dictionary URL
 * @param {string}        hints       Hint for the word
 * @param {int}           txid        Text ID
 * @param {*}             torder 
 * @param {string}        txt         Text
 * @param {array<string>} multi_words 
 * @param {int}           rtl         1 if right-to-left language
 * @returns {boolean}
 */
function run_overlib_status_unknown (
  wblink1, wblink2, wblink3, hints, txid, torder, txt, multi_words, rtl
  ) {
  const lang = getLangFromDict(wblink3);
  return overlib(
    make_overlib_audio(txt, lang) + '<b>' + escape_html_chars(hints) + '</b><br /> ' +
		make_overlib_link_wellknown_word(txid, torder) + ' <br /> ' +
		make_overlib_link_ignore_word(txid, torder) +
		make_overlib_link_new_multiword(txid, torder, multi_words, rtl) + ' <br /> ' +
		make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION, 
    'New Word'
  );
}

/**
 * Handle click event on a multi-word.
 * 
 * @param {string}        wblink1     First dictionary URL
 * @param {string}        wblink2     Second dictionary URL
 * @param {string}        wblink3     Google Translate dictionary URL
 * @param {string}        hints       Hint for the word
 * @param {int}           txid        Text ID
 * @param {*}             torder 
 * @param {string}        txt         Text
 * @param {int}           wid         Word ID 
 * @param {int}           rtl         1 if right-to-left language
 * @returns {boolean}
 */
function run_overlib_multiword (
  wblink1, wblink2, wblink3, hints, txid, torder, txt, wid, stat, wcnt, ann
  ) {
  const lang = getLangFromDict(wblink3);
  return overlib(
    make_overlib_audio(txt, lang) + '<b>' + escape_html_chars_2(hints, ann) + '</b><br /> ' +
		make_overlib_link_change_status_all(txid, torder, wid, stat) + ' <br /> ' +
		make_overlib_link_edit_multiword(txid, torder, wid) + ' | ' +
		make_overlib_link_delete_multiword(txid, wid) + ' <br /> ' +
		make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder),
    CAPTION, 
    make_overlib_link_edit_multiword_title(
      wcnt.trim() + '-Word-Expression', txid, torder, wid
    )
  );
}

/**
 * Make an overlib dialog so that the user can say if he knows the word or not.
 * 
 * @param {string} wblink1 Dictionary 1 URI
 * @param {string} wblink2 Dictionary 2 URI
 * @param {string} wblink3 Google Translate URI
 * @param {int}    wid     Word ID
 * @param {string} txt     Word text
 * @param {string} trans   Word translation 
 * @param {string} roman   Word romanization 
 * @param {string} stat    Word learning status
 * @param {string} sent    Lookup sentence in Google Translate
 * @param {int}    todo    If 1, the user should say if he knows the word.
 * @param {*}      oldstat Old status, unused
 * @returns {*} An overlib object
 */
function run_overlib_test(
  wblink1, wblink2, wblink3, wid, txt, trans, roman, stat, sent, todo, oldstat
  ) {
  const s = parseInt(stat, 10);
  let c = s + 1;
  if (c > 5) c = 5;
  let w = s - 1;
  if (w < 1) w = 1;
  let cc = stat + ' ▶ ' + c; 
  if (c == s) cc = c;
  let ww = stat + ' ▶ ' + w; 
  if (w == s) ww = w;
  let overlib_string = '';
  if (todo == 1) {
    overlib_string += '<center><hr noshade size=1 /><b>';
    if (stat >= 1 && stat <= 5) {
      overlib_string += 
		  make_overlib_link_change_status_test(
        wid, 
        1, 
        '<img src="icn/thumb-up.png" title="Got it!" alt="Got it!" /> Got it! [' + 
        cc + ']'
      ) +
		'<hr noshade size=1 />' +
		make_overlib_link_change_status_test(
      wid, 
      -1, 
      '<img src="icn/thumb.png" title="Oops!" alt="Oops!" /> Oops! [' + ww + ']'
      ) +
		'<hr noshade size=1 />';
    }
    overlib_string +=
		make_overlib_link_change_status_alltest(wid, stat) +
		'</b></center><hr noshade size=1 />';
  }
  overlib_string += '<b>' + escape_html_chars(make_tooltip(txt, trans, roman, stat)) +
  '</b><br />' +
  ' <a href="edit_tword.php?wid=' + wid + 
  '" target="ro" onclick="showRightFrames();">Edit term</a><br />' +
    createTheDictLink(wblink1, txt, 'Dict1', 'Lookup Term: ') +
    createTheDictLink(wblink2, txt, 'Dict2', '') +
    createTheDictLink(wblink3, txt, 'Trans', '') +
    createTheDictLink(wblink3, sent, 'Trans', '<br />Lookup Sentence:');

  return overlib(overlib_string, CAPTION, 'Got it?');
}

/**
 * Return all multiwords
 *
 * @param {int}             txid        Text ID
 * @param {any}             torder 
 * @param {array<string>}   multi_words A list of 8 string elements
 * @param {boolean}         rtl         Right-to-left indicator
 *
 * @return {string} All multiwords
 * 
 * @since 2.8.0-fork LTR texts were wrongly displayed
 */
function make_overlib_link_new_multiword (txid, torder, multi_words, rtl) {
  // Quit if all multiwords are '' or undefined
  if (multi_words.every((x) => !x)) return '';
  const output = Array();
  if (rtl) {
    for (let i = 7; i < 0; i--) { 
      if (multi_words[i]) {
        output.push(make_overlib_link_create_edit_multiword_rtl(
          i + 2, txid, torder, multi_words[i]
        ));
      } 
    }
  } else {
    for (let i = 0; i < 7; i++) {
      if (multi_words[i]) {
        output.push(make_overlib_link_create_edit_multiword(
          i + 2, txid, torder, multi_words[i]
        ));
      } 
    }
  }
  return ' <br />Expr: ' + output.join(' ') + ' ';
}

/**
 * Make link to translations through dictionaries or all sentences lookup.
 * 
 * @param {string} wblink1 Dictionary 1 URI
 * @param {string} wblink2 Dictionary 2 URI
 * @param {string} wblink3 Google Translate URI
 * @param {string} txt     Word string 
 * @param {int}    txid    Text ID 
 * @param {int}    torder 
 * @returns {string}
 */
function make_overlib_link_wb(wblink1, wblink2, wblink3, txt, txid, torder) {
  let s =
	createTheDictLink(wblink1, txt, 'Dict1', 'Lookup Term: ') +
	createTheDictLink(wblink2, txt, 'Dict2', '') +
	createTheDictLink(wblink3, txt, 'Trans', '');
  if (torder > 0 && txid > 0) {
    s += '<br />Lookup Sentence: ' + 
    createSentLookupLink(torder, txid, wblink3, 'Trans');
  }
  return s;
}

/**
 * Create a list of links for dictionary translation.
 * 
 * @param {string} wblink1 Dictionary 1 URI
 * @param {string} wblink2 Dictionary 2 URI
 * @param {string} wblink3 Google Translate URI
 * @param {string} txt     Word string 
 * @param {int}    txid    Text ID 
 * @param {int}    torder  
 * @returns {string} HTML-formatted list of dictionaries link, and sentece link
 */
function make_overlib_link_wbnl (wblink1, wblink2, wblink3, txt, txid, torder) {
  let s =
	createTheDictLink(wblink1, txt, 'Dict1', 'Term: ') +
	createTheDictLink(wblink2, txt, 'Dict2', '') +
	createTheDictLink(wblink3, txt, 'Trans', '');
  if (torder > 0 && txid > 0) {
    s += ' | Sentence: ' + createSentLookupLink(torder, txid, wblink3, 'Trans');
  }
  return s;
}

/**
 * Create link to dictionaries.
 * 
 * @param {string} wblink1 Dictionary 1 URI
 * @param {string} wblink2 Dictionary 2 URI
 * @param {string} wblink3 Google Translate URI
 * @param {string} txt     Word string 
 * @param {string} sent    Complete sentence 
 * @returns {string} HTML-formatted list of links
 */
function make_overlib_link_wbnl2 (wblink1, wblink2, wblink3, txt, sent) {
  let s = 
  createTheDictLink(wblink1, txt, 'Dict1', 'Term: ') +
	createTheDictLink(wblink2, txt, 'Dict2', '') +
	createTheDictLink(wblink3, txt, 'Trans', '');
  if (sent != '') {
    s += createTheDictLink(wblink3, sent, 'Trans', ' | Sentence:');
  }
  return s;
}

/**
 * Change the status of a word multiple time.
 * 
 * @param {int} txid Text ID 
 * @param {*} torder 
 * @param {int} wid Word ID 
 * @param {int} oldstat Old word status
 * @returns {string} Multiple links for a new word status.
 */
function make_overlib_link_change_status_all (txid, torder, wid, oldstat) {
  let result = 'St: ';
  for (let newstat = 1; newstat <= 5; newstat++) { 
    result += make_overlib_link_change_status(txid, torder, wid, oldstat, newstat); 
  }
  result += make_overlib_link_change_status(txid, torder, wid, oldstat, 99);
  result += make_overlib_link_change_status(txid, torder, wid, oldstat, 98);
  return result;
}

/**
 * Return a list of links to change word status
 * 
 * @param {int} wid     Word ID 
 * @param {int} oldstat Current status of the word
 * @returns {string} An HTML-formatted list of links.
 */
function make_overlib_link_change_status_alltest (wid, oldstat) {
  let result = '';
  for (let newstat = 1; newstat <= 5; newstat++) { 
    result += make_overlib_link_change_status_test2(wid, oldstat, newstat); 
  }
  result += make_overlib_link_change_status_test2(wid, oldstat, 99);
  result += make_overlib_link_change_status_test2(wid, oldstat, 98);
  return result;
}

/**
 * Return a link to change the status of a word.
 * 
 * @param {int} txid    Text ID 
 * @param {*}   torder 
 * @param {int} wid     Word ID 
 * @param {int} oldstat Old word status 
 * @param {int} newstat New word status
 * @returns {string} HTML formatted link to change word status
 */
function make_overlib_link_change_status (txid, torder, wid, oldstat, newstat) {
  if (oldstat == newstat) {
    return '<span title="' +
			getStatusName(oldstat) + '">◆</span>';
  } 
  return ' <a href="set_word_status.php?tid=' + txid +
    '&amp;ord=' + torder +
    '&amp;wid=' + wid +
    '&amp;status=' + newstat + '" target="ro" onclick="showRightFrames();">' + 
    '<span title="' + getStatusName(newstat) + '">[' +
    getStatusAbbr(newstat) + ']</span></a> ';
}

/**
 * Prepare an HTML-formated string containing the new statuses choices
 * 
 * @param {int}    wid     ID of the word
 * @param {int}    oldstat Old status
 * @param {int}    newstat New status
 * @returns {string} HTML-formatted link
 */
function make_overlib_link_change_status_test2 (wid, oldstat, newstat) {
  let output = ' <a href="set_test_status.php?wid=' + wid +
  '&amp;status=' + newstat + '&amp;ajax=1" target="ro" onclick="showRightFrames();">' + 
  '<span title="' + getStatusName(newstat) + '">[';
  output += (oldstat == newstat) ? '◆' : getStatusAbbr(newstat);
  output += ']</span></a> ';
  return output;
}

/**
 * Make a link for a word status change
 * 
 * @param {int}    wid       ID of the word
 * @param {int}    plusminus Amplitude of the change (normally 1 or -1) 
 * @param {string} text      Text to be embed
 *  
 * @returns {string} A tag containing formatted text
 */
function make_overlib_link_change_status_test (wid, plusminus, text) {
  return ' <a href="set_test_status.php?wid=' + wid +
		'&amp;stchange=' + plusminus + 
    '&amp;ajax=1" target="ro" onclick="showRightFrames();' + 
      (plusminus > 0 ? 'successSound()' : 'failureSound()') + ';">' + 
    text + '</a> ';
}

/**
 * Make a link to learn a new word.
 * 
 * 
 * @param {int} txid Text ID
 * @param {*} torder 
 * @param {int} wid Word ID
 * 
 * @returns {string}
 */
function make_overlib_link_new_word (txid, torder, wid) {
  return ' <a href="edit_word.php?tid=' + txid +
		'&amp;ord=' + torder +
		'&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">Learn term</a> ';
}

/**
 * Create a link to edit a multiword.
 * 
 * @param {int} txid Text ID 
 * @param {*} torder 
 * @param {int} wid Word ID 
 * @returns {string}
 */
function make_overlib_link_edit_multiword (txid, torder, wid) {
  return ' <a href="edit_mword.php?tid=' + txid +
		'&amp;ord=' + torder +
		'&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">Edit term</a> ';
}

/**
 * Create an overlib title for a multiword edition.
 * 
 * @param {*} text 
 * @param {int} txid 
 * @param {*} torder 
 * @param {int} wid 
 * @returns {string}
 */
function make_overlib_link_edit_multiword_title (text, txid, torder, wid) {
  return '<a style="color:yellow" href="edit_mword.php?tid=' + txid +
		'&amp;ord=' + torder +
		'&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">' + 
    text + '</a>';
}

/**
 * Create or edit a multiword with overlib.
 * 
 * @param {int}    len    Number of words in the multi-word 
 * @param {int}    txid   Text ID
 * @param {*}      torder 
 * @param {string} txt    Multi-word text
 * @returns {string}
 */
function make_overlib_link_create_edit_multiword (len, txid, torder, txt) {
  return ' <a href="edit_mword.php?tid=' + txid +
		'&amp;ord=' + torder +
		'&amp;txt=' + txt +
		'" target="ro" onclick="showRightFrames();">' + 
    len + '..' + escape_html_chars(txt.substring(2).trim()) + '</a> ';
}

/**
 * Create or edit a right-to-left multiword with overlib.
 * 
 * @param {int}    len    Number of words in the multi-word 
 * @param {int}    txid   Text ID
 * @param {*}      torder 
 * @param {string} txt    Multi-word text
 * @returns {string}
 */
function make_overlib_link_create_edit_multiword_rtl (len, txid, torder, txt) {
  return ' <a dir="rtl" href="edit_mword.php?tid=' + txid +
		'&amp;ord=' + torder +
		'&amp;txt=' + txt +
		'" target="ro" onclick="showRightFrames();">' + 
    len + '..' + escape_html_chars(txt.substring(2).trim()) + '</a> ';
}

/**
 * Make a link to edit a word, displaying "Edit term"
 * 
 * @param {int} txid
 * @param {*} torder 
 * @param {int} wid 
 * @returns {string}
 */
function make_overlib_link_edit_word (txid, torder, wid) {
  const url = 'edit_word.php?tid=' + txid + 
  '&amp;ord=' + torder +
  '&amp;wid=' + wid;
  return ' <a href="' + url + 
  ' " target="ro" onclick="showRightFrames()">Edit term</a> ';
}

/**
 * Make a link to edit a word for an overlib title, displaying the word's text.
 * 
 * @param {string} text Word text
 * @param {int}    txid Text ID 
 * @param {*} torder 
 * @param {int}    wid Word ID
 * @returns {string} HTML-formatted link
 */
function make_overlib_link_edit_word_title (text, txid, torder, wid) {
  return '<a style="color:yellow" href="edit_word.php?tid=' +
		txid + '&amp;ord=' + torder +
		'&amp;wid=' + wid + '" target="ro" onclick="showRightFrames();">' + 
    text + '</a>';
}

/**
 * Make a link to delete a word with overlib.
 * 
 * @param {int} txid Text ID
 * @param {int} wid  Word ID
 * @returns {string} HTML-formatted link.
 */
function make_overlib_link_delete_word (txid, wid) {
  return ' <a onclick="showRightFrames(); return confirmDelete();" ' + 
  'href="delete_word.php?wid=' + wid + '&amp;tid=' + txid + 
  '" target="ro">Delete term</a> ';
}

/**
 * Make a link to delete a multiword.
 * 
 * @param {int} txid Text ID 
 * @param {int} wid  Word ID
 * @returns {string} HTML-formatted string
 */
function make_overlib_link_delete_multiword (txid, wid) {
  return ' <a onclick="showRightFrames(); return confirmDelete();" ' + 
  'href="delete_mword.php?wid=' + wid + '&amp;tid=' + txid + 
  '" target="ro">Delete term</a> ';
}

/**
 * Return a link to a word well-known.
 * 
 * @param {int} txid 
 * @param {*} torder 
 * @returns {string} HTML link to mark the word well knwown
 */
function make_overlib_link_wellknown_word (txid, torder) {
  return ' <a href="insert_word_wellknown.php?tid=' +
		txid + '&amp;ord=' + torder + 
    '" target="ro" onclick="showRightFrames();">I know this term well</a> ';
}

/**
 * Return a link to ignore a word.
 * 
 * @param {int} txid 
 * @param {*} torder 
 * @returns {string} HTML string to ignore the word
 */
function make_overlib_link_ignore_word (txid, torder) {
  return ' <a href="insert_word_ignore.php?tid=' + txid +
		'&amp;ord=' + torder + 
    '" target="ro" onclick="showRightFrames();">Ignore this term</a> ';
}

/**
 * Create a clickable button to read a word aloud.
 * 
 * @param {string} txt  Word to say
 * @param {string} lang Language name (two letters or four letters separated with a 
 *                      caret)
 * @return {string} HTML-formatted clickable icon
 */
function make_overlib_audio(txt, lang) {
  let img = document.createElement("img");
  img.title = "Click to read!";
  img.src = "icn/speaker-volume.png";
  img.style.cursor = "pointer";
  img.setAttribute(
    "onclick", 
    "speechDispatcher('" + escape_html_chars(txt) + "', '" + LWT_DATA.language.id + "')"
  );
  return img.outerHTML;
}

/**
 * Interactions and user events on text reading only.
 */

/**
 * Add annotations to a word.
 *
 * @param {*} _ Unused, usually word number
 */
function word_each_do_text_text (_) {
  const wid = $(this).attr('data_wid');
  if (wid != '') {
    const order = $(this).attr('data_order');
    if (order in LWT_DATA.text.annotations) {
      if (wid == LWT_DATA.text.annotations[order][1]) {
        const ann = LWT_DATA.text.annotations[order][2];
        const re = new RegExp(
          '([' + LWT_DATA.language.delimiter + '][ ]{0,1}|^)(' +
            ann.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' +
            LWT_DATA.language.delimiter + '])',
          ''
        );
        if (!re.test($(this).attr('data_trans').replace(/ \[.*$/, ''))) {
          const trans = ann + ' / ' + $(this).attr('data_trans');
          $(this).attr('data_trans', trans.replace(' / *', ''));
        }
        $(this).attr('data_ann', ann);
      }
    }
  }
  if (!LWT_DATA.settings.jQuery_tooltip) {
    $(this).prop(
      'title',
      make_tooltip(
        $(this).text(),
        $(this).attr('data_trans'),
        $(this).attr('data_rom'),
        $(this).attr('data_status')
      )
    );
  }
}

function mword_each_do_text_text (_) {
  if ($(this).attr('data_status') != '') {
    const wid = $(this).attr('data_wid');
    if (wid != '') {
      const order = parseInt($(this).attr('data_order'));
      for (let j = 2; j <= 16; j = j + 2) {
        const index = (order + j).toString();
        if (index in LWT_DATA.text.annotations) {
          if (wid == LWT_DATA.text.annotations[index][1]) {
            const ann = LWT_DATA.text.annotations[index][2];
            const re = new RegExp(
              '([' + LWT_DATA.language.delimiter + '][ ]{0,1}|^)(' +
                ann.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' +
                LWT_DATA.language.delimiter + '])',
              ''
            );
            if (!re.test($(this).attr('data_trans').replace(/ \[.*$/, ''))) {
              const trans = ann + ' / ' + $(this).attr('data_trans');
              $(this).attr('data_trans', trans.replace(' / *', ''));
            }
            $(this).attr('data_ann', ann);
            break;
          }
        }
      }
    }
    if (!LWT_DATA.settings.jQuery_tooltip) {
      $(this).prop(
        'title',
        make_tooltip(
          $(this).attr('data_text'),
          $(this).attr('data_trans'),
          $(this).attr('data_rom'),
          $(this).attr('data_status')
        )
      );
    }
  }
}

function word_dblclick_event_do_text_text () {
  const t = parseInt($('#totalcharcount').text(), 10);
  if (t == 0) { return; }
  let p = 100 * ($(this).attr('data_pos') - 5) / t;
  if (p < 0) { p = 0; }
  if (typeof (window.parent.frames.h.lwt_audio_controller.newPosition) === 'function') {
    window.parent.frames.h.lwt_audio_controller.newPosition(p);
  }
}

/**
   * Do a word edition window. Usually called when the user clicks on a word.
   *
   * @since 2.9.10-fork Read word aloud if LWT_DATA.settings.hts equals 2.
   *
   * @returns {bool} false
   */
function word_click_event_do_text_text () {
  const status = $(this).attr('data_status');
  let ann = '';
  if ($(this).attr('data_ann') !== undefined) {
    ann = $(this).attr('data_ann');
  }

  let hints;
  if (LWT_DATA.settings.jQuery_tooltip) {
    hints = make_tooltip(
      $(this).text(), $(this).attr('data_trans'), $(this).attr('data_rom'), status
    );
  } else {
    hints = $(this).attr('title');
  }

  // Get multi-words containing word
  const multi_words = Array(7);
  for (let i = 0; i < 7; i++) {
    // Start from 2 as multi-words have at least two elements
    multi_words[i] = $(this).attr('data_mw' + (i + 2));
  }
  if (status < 1) {
    run_overlib_status_unknown(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, $(this).attr('data_order'), $(this).text(), multi_words, LWT_DATA.language.rtl
    );
    showRightFrames(
      'edit_word.php?tid=' + LWT_DATA.text.id + '&ord=' + $(this).attr('data_order') + '&wid='
    );
  } else if (status == 99) {
    run_overlib_status_99(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, $(this).attr('data_order'),
      $(this).text(), $(this).attr('data_wid'), multi_words, LWT_DATA.language.rtl, ann
    );
  } else if (status == 98) {
    run_overlib_status_98(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, $(this).attr('data_order'),
      $(this).text(), $(this).attr('data_wid'), multi_words, LWT_DATA.language.rtl, ann
    );
  } else {
    run_overlib_status_1_to_5(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, $(this).attr('data_order'),
      $(this).text(), $(this).attr('data_wid'), status, multi_words, LWT_DATA.language.rtl, ann
    );
  }
  if (LWT_DATA.settings.hts == 2) {
    speechDispatcher($(this).text(), LWT_DATA.language.id);
  }
  return false;
}

function mword_click_event_do_text_text () {
  const status = $(this).attr('data_status');
  if (status != '') {
    let ann = '';
    if ((typeof $(this).attr('data_ann')) !== 'undefined') {
      ann = $(this).attr('data_ann');
    }
    run_overlib_multiword(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link,
      LWT_DATA.settings.jQuery_tooltip
        ? make_tooltip(
          $(this).text(),
          $(this).attr('data_trans'),
          $(this).attr('data_rom'),
          status
        )
        : $(this).attr('title'),
      LWT_DATA.text.id, $(this).attr('data_order'), $(this).attr('data_text'),
      $(this).attr('data_wid'), status, $(this).attr('data_code'), ann
    );
  }
  if (LWT_DATA.settings.hts == 2) {
    speechDispatcher($(this).text(), LWT_DATA.language.id);
  }
  return false;
}

const mwordDragNDrop = {

  event: undefined,

  pos: undefined,

  timeout: undefined,

  context: undefined,

  /**
   * Multi-word selection is finished
   * 
   * @param {*} ev 
   */
  finish: function (ev) {
    const context = mwordDragNDrop.context;
    if (ev.handled !== true) {
      const len = $('.lword.tword', context).length;
      if (len > 0) {
        const word_ord = $('.lword', context).first().attr('data_order');
        if (len > 1) {
          const text = $('.lword', context)
            .map(function () { return $(this).text(); }).get().join('');
          if (text.length > 250) {
            alert('Selected text is too long!!!');
          } else {
            showRightFrames(
              'edit_mword.php?' + $.param({
                tid: LWT_DATA.text.id,
                len: len,
                ord: word_ord,
                txt: text
              })
            );
          }
        } else {
          // Create only a normal word
          showRightFrames(
            'edit_word.php?' + $.param({
              tid: LWT_DATA.text.id,
              ord: word_ord,
              txt: $('#ID-' + word_ord + '-1').text()
            })
          );
        }
      }
      $('span', context).removeClass('tword nword');
      ev.handled = true;
    }
  },

  /**
   * Function to trigger above a term word
   */
  twordMouseOver: function () {
    const context = mwordDragNDrop.context;
    $('html').one('mouseup', function () {
      $('.wsty', context).each(function () {
        $(this).addClass('status' + $(this).attr('data_status'));
      });
      if (!$(this).hasClass('tword')) {
        $('span', context).removeClass('nword tword lword');
        $('.wsty', context).css('background-color', '')
          .css('border-bottom-color', '');
        $('#pe').remove();
      }
    });
    mwordDragNDrop.pos = parseInt($(this).attr('data_order'));

    // Add ".lword" class on this element
    $('.lword', context).removeClass('lword');
    $(this).addClass('lword');
    $(context).on('mouseleave', function () {
      $('.lword', context).removeClass('lword');
    });
    $(context).one('mouseup', '.nword,.tword', mwordDragNDrop.finish);
  },

  /**
   * When having the cursor over the sentence.
   */
  sentenceOver: function () {
    const context = mwordDragNDrop.context;
    $('.lword', context).removeClass('lword');
    const lpos = parseInt($(this).attr('data_order'));
    $(this).addClass('lword');
    if (lpos > mwordDragNDrop.pos) {
      for (var i = mwordDragNDrop.pos; i < lpos; i++) {
        $(
          '.tword[data_order="' + i + '"],.nword[data_order="' + i + '"]',
          context
        ).addClass('lword');
      }
    } else {
      for (var i = mwordDragNDrop.pos; i > lpos; i--) {
        $(
          '.tword[data_order="' + i + '"],.nword[data_order="' + i + '"]',
          context
        ).addClass('lword');
      }
    }
  },

  /**
   * Start creating a multi-word.
   */
  startInteraction: function () {
    const context = mwordDragNDrop.context;
    context.off('mouseout');
    // Add .tword (term word) and .nword (not word) subelements 
    $('.wsty', context).css('background-color', 'inherit')
      .css('border-bottom-color', 'rgba(0,0,0,0)').not('.hide,.word')
      .each(function () {
        let f = parseInt($(this).attr('data_code')) * 2 +
        parseInt($(this).attr('data_order')) - 1;
        let childr_html = '';
        $(this).nextUntil($('[id^="ID-' + f + '-"]', context), '[id$="-1"]')
          .each(function () {
            let w_order = $(this).attr('data_order');
            if (w_order !== undefined) {
              childr_html += '<span class="tword" data_order="' + w_order + '">' + 
              $(this).text() + '</span>';
            } else {
              childr_html += '<span class="nword" data_order="' +
            $(this).attr('id').split('-')[1] + '">' + $(this).text() + '</span>';
            }
          });
        $(this).html(childr_html);
      });

    // Replace '#pe' element
    $('#pe').remove();
    $('body')
      .append(
        '<style id="pe">#' + context.attr('id') + ' .wsty:after,#' +
        context.attr('id') + ' .wsty:before{opacity:0}</style>'
      );

    // Add class ".nword" (not word), and set attribute "data_order"
    $('[id$="-1"]', context).not('.hide,.wsty').addClass('nword').each(function () {
      $(this).attr('data_order', $(this).attr('id').split('-')[1]);
    });

    // Attach children ".tword" (term) to ".word"
    $('.word', context).not('.hide').each(function () {
      $(this).html(
        '<span class="tword" data_order="' + $(this).attr('data_order') + '">' +
          $(this).text() + '</span>'
      );
    });

    // Edit "tword" elements by filling their attributes
    if (mwordDragNDrop.event.data.annotation == 1) {
      $('.wsty', context)
        .not('.hide')
        .each(function () {
          $(this).children('.tword').last()
            .attr('data_ann', $(this).attr('data_ann'))
            .attr('data_trans', $(this).attr('data_trans'))
            .addClass('content' + $(this).attr('data_status'));
          $(this).removeClass(
            'status1 status2 status3 status4 status5 status98 status99'
          );
        });
    } else if (mwordDragNDrop.event.data.annotation == 3) {
      $('.wsty', context)
        .not('.hide')
        .each(function () {
          $(this).children('.tword').first()
            .attr('data_ann', $(this).attr('data_ann'))
            .attr('data_trans', $(this).attr('data_trans'))
            .addClass('content' + $(this).attr('data_status'));
          $(this).removeClass(
            'status1 status2 status3 status4 status5 status98 status99'
          );
        });
    }

    // Prepare interaction on ".tword" to mouseover
    $(context).one('mouseover', '.tword', mwordDragNDrop.twordMouseOver);

    // Prepare a hover intent interaction
    $(context).hoverIntent({
      over: mwordDragNDrop.sentenceOver,
      out: function () {},
      sensitivity: 18,
      selector: '.tword'
    });
  },

  /**
   * Stop the multi-word creation interaction
   */
  stopInteraction: function () {
    clearTimeout(mwordDragNDrop.timeout);
    $('.nword').removeClass('nword');
    $('.tword').removeClass('tword');
    $('.lword').removeClass('lword');
    $('.wsty', mwordDragNDrop.context)
    .css('background-color', '')
    .css('border-bottom-color', '');
    $('#pe').remove();
  }
}

function mword_drag_n_drop_select (event) {
  if (LWT_DATA.settings.jQuery_tooltip) $('.ui-tooltip').remove();
  const sentence = $(this).parent();
  mwordDragNDrop.context = sentence;
  mwordDragNDrop.event = event;
  sentence.one('mouseup mouseout', $(this), mwordDragNDrop.stopInteraction);

  mwordDragNDrop.timeout = setTimeout(mwordDragNDrop.startInteraction, 300);
}

function word_hover_over () {
  if (!$('.tword')[0]) {
    const v = $(this).attr('class').replace(/.*(TERM[^ ]*)( .*)*/, '$1');
    $('.' + v).addClass('hword');
    if (LWT_DATA.settings.jQuery_tooltip) {
      $(this).trigger('mouseover');
    }
    if (LWT_DATA.settings.hts == 3) {
      speechDispatcher($(this).text(), LWT_DATA.language.id);
    }
  }
}

function word_hover_out () {
  $('.hword').removeClass('hword');
  if (LWT_DATA.settings.jQuery_tooltip)$('.ui-helper-hidden-accessible>div[style]').remove();
}

function keydown_event_do_text_text (e) {
  if (e.which == 27) { // esc = reset all
    LWT_DATA.text.reading_position = -1;
    $('span.uwordmarked').removeClass('uwordmarked');
    $('span.kwordmarked').removeClass('kwordmarked');
    cClick();
    return false;
  }

  if (e.which == 13) { // return = edit next unknown word
    $('span.uwordmarked').removeClass('uwordmarked');
    const unknownwordlist = $('span.status0.word:not(.hide):first');
    if (unknownwordlist.size() == 0) return false;
    $(window).scrollTo(unknownwordlist, { axis: 'y', offset: -150 });
    unknownwordlist.addClass('uwordmarked').trigger('click');
    cClick();
    return false;
  }

  const knownwordlist = $(
    'span.word:not(.hide):not(.status0)' + LWT_DATA.settings.word_status_filter +
      ',span.mword:not(.hide)' + LWT_DATA.settings.word_status_filter
  );
  const l_knownwordlist = knownwordlist.size();
  if (l_knownwordlist == 0) return true;

  // the following only for a non-zero known words list
  if (e.which == 36) { // home : known word navigation -> first
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = 0;
    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') {
      ann = curr.attr('data_ann');
    }
    showRightFrames(
      'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which == 35) { // end : known word navigation -> last
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = l_knownwordlist - 1;
    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') {
      ann = curr.attr('data_ann');
    }
    showRightFrames(
      'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which == 37) { // left : known word navigation
    var marked = $('span.kwordmarked');
    var currid = (marked.length == 0)
      ? (100000000)
      : get_position_from_id(marked.attr('id'));
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = l_knownwordlist - 1;
    for (var i = l_knownwordlist - 1; i >= 0; i--) {
      var iid = get_position_from_id(knownwordlist.eq(i).attr('id'));
      if (iid < currid) {
        LWT_DATA.text.reading_position = i;
        break;
      }
    }
    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') {
      ann = curr.attr('data_ann');
    }
    showRightFrames(
      'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which == 39 || e.which == 32) { // space /right : known word navigation
    var marked = $('span.kwordmarked');
    var currid = (marked.length == 0)
      ? (-1)
      : get_position_from_id(marked.attr('id'));
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = 0;
    for (var i = 0; i < l_knownwordlist; i++) {
      var iid = get_position_from_id(knownwordlist.eq(i).attr('id'));
      if (iid > currid) {
        LWT_DATA.text.reading_position = i;
        break;
      }
    }

    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    var ann = '';
    if ((typeof curr.attr('data_ann')) !== 'undefined') {
      ann = curr.attr('data_ann');
    }
    showRightFrames(
      'show_word.php?wid=' + curr.attr('data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }

  if ((!$('.kwordmarked, .uwordmarked')[0]) && $('.hword:hover')[0]) {
    curr = $('.hword:hover');
  } else {
    if (LWT_DATA.text.reading_position < 0 || LWT_DATA.text.reading_position >= l_knownwordlist) return true;
    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
  }
  const wid = curr.attr('data_wid');
  const ord = curr.attr('data_order');
  const stat = curr.attr('data_status');
  const txt = (curr.hasClass('mwsty')) ? curr.attr('data_text') : curr.text();
  let dict = '';

  for (var i = 1; i <= 5; i++) {
    if (e.which == (48 + i) || e.which == (96 + i)) { // 1,.. : status=i
      if (stat == '0') {
        if (i == 1) {
          /** @var {string} sl Source language */
          const sl = getLangFromDict(LWT_DATA.language.translator_link);
          const tl = LWT_DATA.language.translator_link.replace(/.*[?&]tl=([a-zA-Z\-]*)(&.*)*$/, '$1');
          if (sl != LWT_DATA.language.translator_link && tl != LWT_DATA.language.translator_link) { i = i + '&sl=' + sl + '&tl=' + tl; }
        }
        showRightFrames(
          'set_word_on_hover.php?text=' + txt + '&tid=' + LWT_DATA.text.id + '&status=' + i
        );
      } else {
        showRightFrames(
          'set_word_status.php?wid=' + wid + '&tid=' + LWT_DATA.text.id + '&ord=' + ord +
            '&status=' + i
        );
        return false;
      }
    }
  }
  if (e.which == 73) { // I : status=98
    if (stat == '0') {
      showRightFrames(
        'set_word_on_hover.php?text=' + txt + '&tid=' + LWT_DATA.text.id +
          '&status=98'
      );
    } else {
      showRightFrames(
        'set_word_status.php?wid=' + wid + '&tid=' + LWT_DATA.text.id +
          '&ord=' + ord + '&status=98'
      );
      return false;
    }
  }
  if (e.which == 87) { // W : status=99
    if (stat == '0') {
      showRightFrames(
        'set_word_on_hover.php?text=' + txt + '&tid=' + LWT_DATA.text.id + '&status=99'
      );
    } else {
      showRightFrames(
        'set_word_status.php?wid=' + wid + '&tid=' + LWT_DATA.text.id + '&ord=' + ord +
          '&status=99'
      );
    }
    return false;
  }
  if (e.which == 80) { // P : pronounce term
    speechDispatcher(txt, LWT_DATA.language.id);
    return false;
  }
  if (e.which == 84) { // T : translate sentence
    let popup = false;
    let dict_link = LWT_DATA.language.translator_link;
    if (LWT_DATA.language.translator_link.startsWith('*')) {
      popup = true;
      dict_link = substring(dict_link, 1);
    }
    if (dict_link.startsWith('ggl.php')) {
      dict_link = 'http://' + dict_link;
    }
    let open_url = true;
    let final_url;
    try {
      final_url = new URL(dict_link);
      popup |= final_url.searchParams.has('lwt_popup');
    } catch (err) {
      if (err instanceof TypeError) {
        open_url = false;
      }
    }
    if (popup) {
      owin('trans.php?x=1&i=' + ord + '&t=' + LWT_DATA.text.id);
    } else if (open_url) {
      showRightFrames(undefined, 'trans.php?x=1&i=' + ord + '&t=' + LWT_DATA.text.id);
    }
    return false;
  }
  if (e.which == 65) { // A : set audio pos.
    let p = curr.attr('data_pos');
    const t = parseInt($('#totalcharcount').text(), 10);
    if (t == 0) { return true; }
    p = 100 * (p - 5) / t;
    if (p < 0) p = 0;
    if (typeof (window.parent.frames.h.lwt_audio_controller.newPosition) === 'function') {
      window.parent.frames.h.lwt_audio_controller.newPosition(p);
    } else {
      return true;
    }
    return false;
  }
  if (e.which == 71) { //  G : edit term and open GTr
    dict = '&nodict';
    setTimeout(function () {
      const target_url = LWT_DATA.language.translator_link;
      let popup = false;
      popup = target_url.startsWith('*');
      try {
        const final_url = new URL(target_url);
        popup |= final_url.searchParams.has('lwt_popup');
      } catch (err) {
        if (!(err instanceof TypeError)) {
          throw err;
        }
      }
      if (popup) {
        owin(createTheDictUrl(target_url, txt));
      } else {
        showRightFrames(undefined, createTheDictUrl(target_url, txt));
      }
    }, 10);
  }
  if (e.which == 69 || e.which == 71) { //  E / G: edit term
    let url = '';
    if (curr.hasClass('mword')) {
      url = 'edit_mword.php?wid=' + wid + '&len=' + curr.attr('data_code') +
        '&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
    } else if (stat == '0') {
      url =	'edit_word.php?wid=&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
    } else {
      url =	'edit_word.php?wid=' + wid + '&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
    }
    showRightFrames(url);
    return false;
  }
  return true;
}

/**
   * Prepare the interaction events with the text.
   *
   * @since 2.0.3-fork
   */
function prepareTextInteractions () {
  $('.word').each(word_each_do_text_text);
  $('.mword').each(mword_each_do_text_text);
  $('.word').on('click', word_click_event_do_text_text);
  $('#thetext').on('selectstart', 'span', false).on(
    'mousedown', '.wsty',
    { annotation: LWT_DATA.settings.annotations_mode },
    mword_drag_n_drop_select);
  $('#thetext').on('click', '.mword', mword_click_event_do_text_text);
  $('.word').on('dblclick', word_dblclick_event_do_text_text);
  $('#thetext').on('dblclick', '.mword', word_dblclick_event_do_text_text);
  $(document).on('keydown', keydown_event_do_text_text);
  $('#thetext').hoverIntent(
    {
      over: word_hover_over,
      out: word_hover_out,
      interval: 150,
      selector: '.wsty,.mwsty'
    }
  );
}

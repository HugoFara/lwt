/**
 * Interactions and user events on text reading only.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { make_tooltip, getLangFromDict, createTheDictUrl } from './pgm';
import { speechDispatcher } from './user_interactions';

/**
 * Helper to safely get an HTML attribute value as a string.
 *
 * @param $el jQuery element to get attribute from
 * @param attr Name of the attribute to retrieve
 * @returns Attribute value as string, or empty string if undefined
 */
function getAttr($el: JQuery, attr: string): string {
  const val = $el.attr(attr);
  return typeof val === 'string' ? val : '';
}

// Declare external functions
declare function run_overlib_status_unknown(
  dict1: string, dict2: string, translator: string, hints: string,
  textId: number, order: string, text: string, multiWords: (string | undefined)[], rtl: boolean
): void;
declare function run_overlib_status_99(
  dict1: string, dict2: string, translator: string, hints: string,
  textId: number, order: string, text: string, wid: string, multiWords: (string | undefined)[], rtl: boolean, ann: string
): void;
declare function run_overlib_status_98(
  dict1: string, dict2: string, translator: string, hints: string,
  textId: number, order: string, text: string, wid: string, multiWords: (string | undefined)[], rtl: boolean, ann: string
): void;
declare function run_overlib_status_1_to_5(
  dict1: string, dict2: string, translator: string, hints: string,
  textId: number, order: string, text: string, wid: string, status: string, multiWords: (string | undefined)[], rtl: boolean, ann: string
): void;
declare function run_overlib_multiword(
  dict1: string, dict2: string, translator: string, hints: string,
  textId: number, order: string, text: string, wid: string, status: string, code: string, ann: string
): void;
declare function showRightFrames(url1?: string, url2?: string): void;
declare function owin(url: string): Window | null;
declare function cClick(): void;
declare function get_position_from_id(id: string): number;

// Type definitions
interface LwtLanguage {
  id: number;
  dict_link1: string;
  dict_link2: string;
  translator_link: string;
  delimiter: string;
  rtl: boolean;
}

interface LwtText {
  id: number;
  reading_position: number;
  annotations: Record<string, [unknown, string, string]>;
}

interface LwtSettings {
  jQuery_tooltip: boolean;
  hts: number;
  word_status_filter: string;
  annotations_mode: number;
}

interface LwtDataGlobal {
  language: LwtLanguage;
  text: LwtText;
  settings: LwtSettings;
}

declare const LWT_DATA: LwtDataGlobal;

// Extend JQuery for hoverIntent plugin
interface HoverIntentOptions {
  over: (this: HTMLElement) => void;
  out: (this: HTMLElement) => void;
  sensitivity?: number;
  interval?: number;
  selector?: string;
}

declare global {
  interface JQuery {
    hoverIntent(options: HoverIntentOptions): JQuery;
    scrollTo(target: unknown, options?: { axis?: string; offset?: number }): JQuery;
  }
}

// Audio controller type for frames
interface AudioController {
  newPosition: (p: number) => void;
}

interface LwtFrameH extends Window {
  lwt_audio_controller: AudioController;
}

interface FramesWithH {
  h: LwtFrameH;
}

/**
 * Add annotations to a word.
 *
 * @param _ Unused, usually word number
 */
export function word_each_do_text_text(this: HTMLElement, _: number): void {
  const $this = $(this);
  const wid = getAttr($this, 'data_wid');
  if (wid !== '') {
    const order = getAttr($this, 'data_order');
    if (order in LWT_DATA.text.annotations) {
      if (wid === LWT_DATA.text.annotations[order][1]) {
        const ann = LWT_DATA.text.annotations[order][2];
        const re = new RegExp(
          '([' + LWT_DATA.language.delimiter + '][ ]{0,1}|^)(' +
            ann.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' +
            LWT_DATA.language.delimiter + '])',
          ''
        );
        const dataTrans = getAttr($this, 'data_trans');
        if (!re.test(dataTrans.replace(/ \[.*$/, ''))) {
          const trans = ann + ' / ' + dataTrans;
          $this.attr('data_trans', trans.replace(' / *', ''));
        }
        $this.attr('data_ann', ann);
      }
    }
  }
  if (!LWT_DATA.settings.jQuery_tooltip) {
    $this.prop(
      'title',
      make_tooltip(
        $this.text(),
        getAttr($this, 'data_trans'),
        getAttr($this, 'data_rom'),
        getAttr($this, 'data_status') || '0'
      )
    );
  }
}

/**
 * Process multi-word expressions in text and update their annotations.
 * Checks for matching word IDs in nearby annotations and combines translations.
 *
 * @param this The HTML element being processed (word span)
 * @param _ Unused iteration index parameter
 */
export function mword_each_do_text_text(this: HTMLElement, _: number): void {
  const $this = $(this);
  if (getAttr($this, 'data_status') !== '') {
    const wid = getAttr($this, 'data_wid');
    if (wid !== '') {
      const order = parseInt(getAttr($this, 'data_order') || '0', 10);
      for (let j = 2; j <= 16; j = j + 2) {
        const index = (order + j).toString();
        if (index in LWT_DATA.text.annotations) {
          if (wid === LWT_DATA.text.annotations[index][1]) {
            const ann = LWT_DATA.text.annotations[index][2];
            const re = new RegExp(
              '([' + LWT_DATA.language.delimiter + '][ ]{0,1}|^)(' +
                ann.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' +
                LWT_DATA.language.delimiter + '])',
              ''
            );
            const dataTrans = getAttr($this, 'data_trans');
            if (!re.test(dataTrans.replace(/ \[.*$/, ''))) {
              const trans = ann + ' / ' + dataTrans;
              $this.attr('data_trans', trans.replace(' / *', ''));
            }
            $this.attr('data_ann', ann);
            break;
          }
        }
      }
    }
    if (!LWT_DATA.settings.jQuery_tooltip) {
      $this.prop(
        'title',
        make_tooltip(
          getAttr($this, 'data_text'),
          getAttr($this, 'data_trans'),
          getAttr($this, 'data_rom'),
          getAttr($this, 'data_status') || '0'
        )
      );
    }
  }
}

/**
 * Handle double-click event on a word to jump to its position in audio/video.
 * Calculates the position in the text and seeks the media player accordingly.
 *
 * @param this The HTML element (word) that was double-clicked
 */
export function word_dblclick_event_do_text_text(this: HTMLElement): void {
  const $this = $(this);
  const t = parseInt($('#totalcharcount').text(), 10);
  if (t === 0) { return; }
  let p = 100 * (parseInt(getAttr($this, 'data_pos') || '0', 10) - 5) / t;
  if (p < 0) { p = 0; }
  const parentFrames = window.parent as Window & { frames: FramesWithH };
  if (typeof parentFrames.frames.h?.lwt_audio_controller?.newPosition === 'function') {
    parentFrames.frames.h.lwt_audio_controller.newPosition(p);
  }
}

/**
 * Do a word edition window. Usually called when the user clicks on a word.
 *
 * @since 2.9.10-fork Read word aloud if LWT_DATA.settings.hts equals 2.
 *
 * @returns false
 */
export function word_click_event_do_text_text(this: HTMLElement): boolean {
  const $this = $(this);
  const status = getAttr($this, 'data_status');
  let ann = getAttr($this, 'data_ann');

  let hints: string;
  if (LWT_DATA.settings.jQuery_tooltip) {
    hints = make_tooltip(
      $this.text(), getAttr($this, 'data_trans'), getAttr($this, 'data_rom'), status
    );
  } else {
    const titleAttr = $this.attr('title');
    hints = typeof titleAttr === 'string' ? titleAttr : '';
  }

  // Get multi-words containing word
  const multi_words: (string | undefined)[] = Array(7);
  for (let i = 0; i < 7; i++) {
    // Start from 2 as multi-words have at least two elements
    const mwAttr = $this.attr('data_mw' + (i + 2));
    multi_words[i] = typeof mwAttr === 'string' ? mwAttr : undefined;
  }
  const statusNum = parseInt(status || '0', 10);
  const order = getAttr($this, 'data_order');
  const wid = getAttr($this, 'data_wid');

  if (statusNum < 1) {
    run_overlib_status_unknown(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, order, $this.text(), multi_words, LWT_DATA.language.rtl
    );
    showRightFrames(
      'edit_word.php?tid=' + LWT_DATA.text.id + '&ord=' + order + '&wid='
    );
  } else if (statusNum === 99) {
    run_overlib_status_99(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, order,
      $this.text(), wid, multi_words, LWT_DATA.language.rtl, ann
    );
  } else if (statusNum === 98) {
    run_overlib_status_98(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, order,
      $this.text(), wid, multi_words, LWT_DATA.language.rtl, ann
    );
  } else {
    run_overlib_status_1_to_5(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link, hints,
      LWT_DATA.text.id, order,
      $this.text(), wid, status, multi_words, LWT_DATA.language.rtl, ann
    );
  }
  if (LWT_DATA.settings.hts === 2) {
    speechDispatcher($this.text(), LWT_DATA.language.id);
  }
  return false;
}

/**
 * Handle click event on a multi-word expression to display its details.
 * Shows the word overlay with dictionary links and translation options.
 *
 * @param this The HTML element (multi-word) that was clicked
 * @returns false to prevent default behavior
 */
export function mword_click_event_do_text_text(this: HTMLElement): boolean {
  const $this = $(this);
  const status = getAttr($this, 'data_status');
  if (status !== '') {
    const ann = getAttr($this, 'data_ann');
    let hints: string;
    if (LWT_DATA.settings.jQuery_tooltip) {
      hints = make_tooltip(
        $this.text(),
        getAttr($this, 'data_trans'),
        getAttr($this, 'data_rom'),
        status
      );
    } else {
      const titleAttr = $this.attr('title');
      hints = typeof titleAttr === 'string' ? titleAttr : '';
    }
    run_overlib_multiword(
      LWT_DATA.language.dict_link1, LWT_DATA.language.dict_link2, LWT_DATA.language.translator_link,
      hints,
      LWT_DATA.text.id, getAttr($this, 'data_order'), getAttr($this, 'data_text'),
      getAttr($this, 'data_wid'), status, getAttr($this, 'data_code'), ann
    );
  }
  if (LWT_DATA.settings.hts === 2) {
    speechDispatcher($this.text(), LWT_DATA.language.id);
  }
  return false;
}

interface MwordDragNDropState {
  event: JQuery.TriggeredEvent | undefined;
  pos: number | undefined;
  timeout: ReturnType<typeof setTimeout> | undefined;
  context: JQuery | undefined;
  finish: (ev: JQuery.TriggeredEvent & { handled?: boolean }) => void;
  twordMouseOver: (this: HTMLElement) => void;
  sentenceOver: (this: HTMLElement) => void;
  startInteraction: () => void;
  stopInteraction: () => void;
}

export const mwordDragNDrop: MwordDragNDropState = {

  event: undefined,

  pos: undefined,

  timeout: undefined,

  context: undefined,

  /**
   * Multi-word selection is finished
   */
  finish: function (ev: JQuery.TriggeredEvent & { handled?: boolean }): void {
    const context = mwordDragNDrop.context;
    if (!context) return;
    if (ev.handled !== true) {
      const len = $('.lword.tword', context).length;
      if (len > 0) {
        const word_ord = getAttr($('.lword', context).first(), 'data_order');
        if (len > 1) {
          const text: string = $('.lword', context)
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
  twordMouseOver: function (this: HTMLElement): void {
    const context = mwordDragNDrop.context;
    if (!context) return;
    $('html').one('mouseup', function () {
      $('.wsty', context).each(function () {
        $(this).addClass('status' + getAttr($(this), 'data_status'));
      });
      if (!$(this).hasClass('tword')) {
        $('span', context).removeClass('nword tword lword');
        $('.wsty', context).css('background-color', '')
          .css('border-bottom-color', '');
        $('#pe').remove();
      }
    });
    mwordDragNDrop.pos = parseInt(getAttr($(this), 'data_order') || '0', 10);

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
  sentenceOver: function (this: HTMLElement): void {
    const context = mwordDragNDrop.context;
    if (!context) return;
    $('.lword', context).removeClass('lword');
    const lpos = parseInt(getAttr($(this), 'data_order') || '0', 10);
    $(this).addClass('lword');
    if (mwordDragNDrop.pos !== undefined && lpos > mwordDragNDrop.pos) {
      for (let i = mwordDragNDrop.pos; i < lpos; i++) {
        $(
          '.tword[data_order="' + i + '"],.nword[data_order="' + i + '"]',
          context
        ).addClass('lword');
      }
    } else if (mwordDragNDrop.pos !== undefined) {
      for (let i = mwordDragNDrop.pos; i > lpos; i--) {
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
  startInteraction: function (): void {
    const context = mwordDragNDrop.context;
    if (!context) return;
    context.off('mouseout');
    // Add .tword (term word) and .nword (not word) subelements
    $('.wsty', context).css('background-color', 'inherit')
      .css('border-bottom-color', 'rgba(0,0,0,0)').not('.hide,.word')
      .each(function () {
        const $el = $(this);
        const f = parseInt(getAttr($el, 'data_code') || '0', 10) * 2 +
        parseInt(getAttr($el, 'data_order') || '0', 10) - 1;
        let childr_html = '';
        $el.nextUntil($('[id^="ID-' + f + '-"]', context), '[id$="-1"]')
          .each(function () {
            const $child = $(this);
            const w_order = $child.attr('data_order');
            if (typeof w_order === 'string') {
              childr_html += '<span class="tword" data_order="' + w_order + '">' +
              $child.text() + '</span>';
            } else {
              const childIdAttr = $child.attr('id');
              const childId = typeof childIdAttr === 'string' ? childIdAttr : '';
              childr_html += '<span class="nword" data_order="' +
            childId.split('-')[1] + '">' + $child.text() + '</span>';
            }
          });
        $el.html(childr_html);
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
      const $el = $(this);
      const elIdAttr = $el.attr('id');
      const elId = typeof elIdAttr === 'string' ? elIdAttr : '';
      $el.attr('data_order', elId.split('-')[1]);
    });

    // Attach children ".tword" (term) to ".word"
    $('.word', context).not('.hide').each(function () {
      const $el = $(this);
      $el.html(
        '<span class="tword" data_order="' + getAttr($el, 'data_order') + '">' +
          $el.text() + '</span>'
      );
    });

    // Edit "tword" elements by filling their attributes
    const event = mwordDragNDrop.event;
    const annotationMode = (event?.data as { annotation?: number })?.annotation;
    if (annotationMode === 1) {
      $('.wsty', context)
        .not('.hide')
        .each(function () {
          const $el = $(this);
          $el.children('.tword').last()
            .attr('data_ann', getAttr($el, 'data_ann'))
            .attr('data_trans', getAttr($el, 'data_trans'))
            .addClass('content' + getAttr($el, 'data_status'));
          $el.removeClass(
            'status1 status2 status3 status4 status5 status98 status99'
          );
        });
    } else if (annotationMode === 3) {
      $('.wsty', context)
        .not('.hide')
        .each(function () {
          const $el = $(this);
          $el.children('.tword').first()
            .attr('data_ann', getAttr($el, 'data_ann'))
            .attr('data_trans', getAttr($el, 'data_trans'))
            .addClass('content' + getAttr($el, 'data_status'));
          $el.removeClass(
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
  stopInteraction: function (): void {
    if (mwordDragNDrop.timeout) {
      clearTimeout(mwordDragNDrop.timeout);
    }
    $('.nword').removeClass('nword');
    $('.tword').removeClass('tword');
    $('.lword').removeClass('lword');
    if (mwordDragNDrop.context) {
      $('.wsty', mwordDragNDrop.context)
        .css('background-color', '')
        .css('border-bottom-color', '');
    }
    $('#pe').remove();
  }
};

/**
 * Initialize multi-word drag-and-drop selection when mousedown occurs on a word.
 * Sets up the selection context and starts a timeout for the interaction.
 *
 * @param this The HTML element where mousedown occurred
 * @param event The mousedown event
 */
export function mword_drag_n_drop_select(this: HTMLElement, event: JQuery.TriggeredEvent): void {
  if (LWT_DATA.settings.jQuery_tooltip) { $('.ui-tooltip').remove(); }
  const sentence = $(this).parent();
  mwordDragNDrop.context = sentence;
  mwordDragNDrop.event = event;
  sentence.one('mouseup mouseout', mwordDragNDrop.stopInteraction);

  mwordDragNDrop.timeout = setTimeout(mwordDragNDrop.startInteraction, 300);
}

/**
 * Handle mouse hover over a word to highlight all instances of the same term.
 * Also triggers text-to-speech if enabled (HTS setting = 3).
 *
 * @param this The HTML element being hovered over
 */
export function word_hover_over(this: HTMLElement): void {
  if (!$('.tword')[0]) {
    const classAttrVal = $(this).attr('class');
    const classAttr = typeof classAttrVal === 'string' ? classAttrVal : '';
    const v = classAttr.replace(/.*(TERM[^ ]*)( .*)*/, '$1');
    $('.' + v).addClass('hword');
    if (LWT_DATA.settings.jQuery_tooltip) {
      $(this).trigger('mouseover');
    }
    if (LWT_DATA.settings.hts === 3) {
      speechDispatcher($(this).text(), LWT_DATA.language.id);
    }
  }
}

/**
 * Handle mouse hover out from a word to remove highlighting.
 * Cleans up tooltip elements and removes the 'hword' class.
 */
export function word_hover_out(): void {
  $('.hword').removeClass('hword');
  if (LWT_DATA.settings.jQuery_tooltip) {
    $('.ui-helper-hidden-accessible>div[style]').remove();
  }
}

/**
 * Handle keyboard events during text reading.
 * ESC key resets reading position, arrow keys navigate between words.
 *
 * @param e jQuery keyboard event
 * @returns false to prevent default behavior, true otherwise
 */
export function keydown_event_do_text_text(e: JQuery.KeyDownEvent): boolean {
  if (e.which === 27) { // esc = reset all
    LWT_DATA.text.reading_position = -1;
    $('span.uwordmarked').removeClass('uwordmarked');
    $('span.kwordmarked').removeClass('kwordmarked');
    cClick();
    return false;
  }

  if (e.which === 13) { // return = edit next unknown word
    $('span.uwordmarked').removeClass('uwordmarked');
    const unknownwordlist = $('span.status0.word:not(.hide):first');
    if (unknownwordlist.length === 0) return false;
    $(window).scrollTo(unknownwordlist, { axis: 'y', offset: -150 });
    unknownwordlist.addClass('uwordmarked').trigger('click');
    cClick();
    return false;
  }

  const knownwordlist = $(
    'span.word:not(.hide):not(.status0)' + LWT_DATA.settings.word_status_filter +
      ',span.mword:not(.hide)' + LWT_DATA.settings.word_status_filter
  );
  const l_knownwordlist = knownwordlist.length;
  if (l_knownwordlist === 0) return true;

  // the following only for a non-zero known words list
  let curr: JQuery;
  let ann: string;

  if (e.which === 36) { // home : known word navigation -> first
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = 0;
    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    ann = getAttr(curr, 'data_ann');
    showRightFrames(
      'show_word.php?wid=' + getAttr(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which === 35) { // end : known word navigation -> last
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = l_knownwordlist - 1;
    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    ann = getAttr(curr, 'data_ann');
    showRightFrames(
      'show_word.php?wid=' + getAttr(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which === 37) { // left : known word navigation
    const marked = $('span.kwordmarked');
    let currid: number;
    if (marked.length === 0) {
      currid = 100000000;
    } else {
      const markedId = marked.attr('id');
      currid = get_position_from_id(typeof markedId === 'string' ? markedId : '');
    }
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = l_knownwordlist - 1;
    for (let i = l_knownwordlist - 1; i >= 0; i--) {
      const itemId = knownwordlist.eq(i).attr('id');
      const iid = get_position_from_id(typeof itemId === 'string' ? itemId : '');
      if (iid < currid) {
        LWT_DATA.text.reading_position = i;
        break;
      }
    }
    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    ann = getAttr(curr, 'data_ann');
    showRightFrames(
      'show_word.php?wid=' + getAttr(curr, 'data_wid') + '&ann=' +
        encodeURIComponent(ann)
    );
    return false;
  }
  if (e.which === 39 || e.which === 32) { // space /right : known word navigation
    const marked = $('span.kwordmarked');
    let currid: number;
    if (marked.length === 0) {
      currid = -1;
    } else {
      const markedId = marked.attr('id');
      currid = get_position_from_id(typeof markedId === 'string' ? markedId : '');
    }
    $('span.kwordmarked').removeClass('kwordmarked');
    LWT_DATA.text.reading_position = 0;
    for (let i = 0; i < l_knownwordlist; i++) {
      const itemId = knownwordlist.eq(i).attr('id');
      const iid = get_position_from_id(typeof itemId === 'string' ? itemId : '');
      if (iid > currid) {
        LWT_DATA.text.reading_position = i;
        break;
      }
    }

    curr = knownwordlist.eq(LWT_DATA.text.reading_position);
    curr.addClass('kwordmarked');
    $(window).scrollTo(curr, { axis: 'y', offset: -150 });
    ann = getAttr(curr, 'data_ann');
    showRightFrames(
      'show_word.php?wid=' + getAttr(curr, 'data_wid') + '&ann=' +
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
  const wid = getAttr(curr, 'data_wid');
  const ord = getAttr(curr, 'data_order');
  const stat = getAttr(curr, 'data_status');
  const txt = curr.hasClass('mwsty') ? getAttr(curr, 'data_text') : curr.text();
  let dict = '';

  for (let i = 1; i <= 5; i++) {
    if (e.which === (48 + i) || e.which === (96 + i)) { // 1,.. : status=i
      if (stat === '0') {
        let statusVal: string | number = i;
        if (i === 1) {
          /** @var sl Source language */
          const sl = getLangFromDict(LWT_DATA.language.translator_link);
          const tl = LWT_DATA.language.translator_link.replace(/.*[?&]tl=([a-zA-Z-]*)(&.*)*$/, '$1');
          if (sl !== LWT_DATA.language.translator_link && tl !== LWT_DATA.language.translator_link) {
            statusVal = i + '&sl=' + sl + '&tl=' + tl;
          }
        }
        showRightFrames(
          'set_word_on_hover.php?text=' + txt + '&tid=' + LWT_DATA.text.id + '&status=' + statusVal
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
  if (e.which === 73) { // I : status=98
    if (stat === '0') {
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
  if (e.which === 87) { // W : status=99
    if (stat === '0') {
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
  if (e.which === 80) { // P : pronounce term
    speechDispatcher(txt, LWT_DATA.language.id);
    return false;
  }
  if (e.which === 84) { // T : translate sentence
    let popup = false;
    let dict_link = LWT_DATA.language.translator_link;
    if (LWT_DATA.language.translator_link.startsWith('*')) {
      popup = true;
      dict_link = dict_link.substring(1);
    }
    if (dict_link.startsWith('ggl.php')) {
      dict_link = 'http://' + dict_link;
    }
    let open_url = true;
    let final_url: URL | undefined;
    try {
      final_url = new URL(dict_link);
      popup = popup || final_url.searchParams.has('lwt_popup');
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
  if (e.which === 65) { // A : set audio pos.
    let p = parseInt(getAttr(curr, 'data_pos') || '0', 10);
    const t = parseInt($('#totalcharcount').text(), 10);
    if (t === 0) { return true; }
    p = 100 * (p - 5) / t;
    if (p < 0) p = 0;
    const parentFrames = window.parent as Window & { frames: FramesWithH };
    if (typeof parentFrames.frames.h?.lwt_audio_controller?.newPosition === 'function') {
      parentFrames.frames.h.lwt_audio_controller.newPosition(p);
    } else {
      return true;
    }
    return false;
  }
  if (e.which === 71) { //  G : edit term and open GTr
    dict = '&nodict';
    setTimeout(function () {
      const target_url = LWT_DATA.language.translator_link;
      let popup = false;
      popup = target_url.startsWith('*');
      try {
        const final_url = new URL(target_url);
        popup = popup || final_url.searchParams.has('lwt_popup');
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
  if (e.which === 69 || e.which === 71) { //  E / G: edit term
    let url = '';
    if (curr.hasClass('mword')) {
      url = 'edit_mword.php?wid=' + wid + '&len=' + getAttr(curr, 'data_code') +
        '&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
    } else if (stat === '0') {
      url = 'edit_word.php?wid=&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
    } else {
      url = 'edit_word.php?wid=' + wid + '&tid=' + LWT_DATA.text.id + '&ord=' + ord + dict;
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
export function prepareTextInteractions(): void {
  $('.word').each(word_each_do_text_text);
  $('.mword').each(mword_each_do_text_text);
  $('.word').on('click', word_click_event_do_text_text);
  $('#thetext').on('selectstart', 'span', function() { return false; }).on(
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

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  w.word_each_do_text_text = word_each_do_text_text;
  w.mword_each_do_text_text = mword_each_do_text_text;
  w.word_dblclick_event_do_text_text = word_dblclick_event_do_text_text;
  w.word_click_event_do_text_text = word_click_event_do_text_text;
  w.mword_click_event_do_text_text = mword_click_event_do_text_text;
  w.mwordDragNDrop = mwordDragNDrop;
  w.mword_drag_n_drop_select = mword_drag_n_drop_select;
  w.word_hover_over = word_hover_over;
  w.word_hover_out = word_hover_out;
  w.keydown_event_do_text_text = keydown_event_do_text_text;
  w.prepareTextInteractions = prepareTextInteractions;
}

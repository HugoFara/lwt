/**
 * Text Display - Word counts, barcharts, and text statistics
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import $ from 'jquery';
import { do_ajax_save_setting } from '../core/ajax_utilities';

// Word counts globals
declare let WORDCOUNTS: {
  expr: Record<string, number>;
  expru: Record<string, number>;
  total: Record<string, number>;
  totalu: Record<string, number>;
  stat: Record<string, Record<string, number>>;
  statu: Record<string, Record<string, number>>;
};
declare let SUW: number;
declare let SHOWUNIQUE: number;

/**
 * Helper to safely get an HTML attribute value as a string.
 */
function getAttr($el: JQuery, attr: string): string {
  const val = $el.attr(attr);
  return typeof val === 'string' ? val : '';
}

/**
 * Update WORDCOUNTS in with an AJAX request.
 */
export function do_ajax_word_counts(): void {
  const t = $('.markcheck').map(function () {
    return $(this).val();
  })
    .get().join(',');
  $.getJSON(
    'api.php/v1/texts/statistics',
    {
      texts_id: t
    },
    function (data: typeof WORDCOUNTS) {
      (window as unknown as { WORDCOUNTS: typeof WORDCOUNTS }).WORDCOUNTS = data;
      word_count_click();
      $('.barchart').removeClass('hide');
    }
  );
}

/**
 * Set a unique item in barchart to reflect how many words are known.
 */
export function set_barchart_item(this: HTMLElement): void {
  const idAttr = getAttr($(this).find('span').first(), 'id');
  const id = idAttr.split('_')[2] || '';
  /** @var v Number of terms in the text */
  let v: number;
  if (SUW & 16) {
    v = parseInt(String(WORDCOUNTS.expru[id] || 0), 10) +
    parseInt(String(WORDCOUNTS.totalu[id]), 10);
  } else {
    v = parseInt(String(WORDCOUNTS.expr[id] || 0), 10) +
    parseInt(String(WORDCOUNTS.total[id]), 10);
  }
  $(this).children('li').each(function () {
    /** Word count in the category */
    let cat_word_count = parseInt($(this).children('span').text(), 10);
    // Avoid to put 0 in logarithm
    cat_word_count += 1;
    v += 1;
    const h = 25 - Math.log(cat_word_count) / Math.log(v) * 25;
    $(this).css('border-top-width', h + 'px');
  });
}

/**
 * Set the number of words known in a text (in edit_texts.php main page).
 */
export function set_word_counts(): void {
  $.each(WORDCOUNTS.totalu, function (key: string, value: number) {
    let knownu = 0, known = 0, todo: number, stat0: number;
    const expr = WORDCOUNTS.expru[key] ? parseInt(String((SUW & 2) ? WORDCOUNTS.expru[key] : WORDCOUNTS.expr[key]), 10) : 0;
    if (!WORDCOUNTS.stat[key]) {
      WORDCOUNTS.statu[key] = WORDCOUNTS.stat[key] = {};
    }
    $('#total_' + key).html(String((SUW & 1) ? value : WORDCOUNTS.total[key]));
    $.each(WORDCOUNTS.statu[key], function (k: string, v: number) {
      if (SUW & 8) { $('#stat_' + k + '_' + key).html(String(v)); }
      knownu += parseInt(String(v), 10);
    });
    $.each(WORDCOUNTS.stat[key], function (k: string, v: number) {
      if (!(SUW & 8)) { $('#stat_' + k + '_' + key).html(String(v)); }
      known += parseInt(String(v), 10);
    });
    $('#saved_' + key).html(known ? (String((SUW & 2 ? knownu : known) - expr) + '+' + expr) : '0');
    if (SUW & 4) {
      todo = parseInt(String(value), 10) + parseInt(String(WORDCOUNTS.expru[key] || 0), 10) - parseInt(String(knownu), 10);
    } else {
      todo = parseInt(String(WORDCOUNTS.total[key]), 10) + parseInt(String(WORDCOUNTS.expr[key] || 0), 10) - parseInt(String(known), 10);
    }
    $('#todo_' + key).html(String(todo));

    // added unknown percent
    let unknowncount: number, unknownpercent: number;
    if (SUW & 8) {
      unknowncount = parseInt(String(value), 10) + parseInt(String(WORDCOUNTS.expru[key] || 0), 10) - parseInt(String(knownu), 10);
      unknownpercent = Math.round(unknowncount * 10000 / (knownu + unknowncount)) / 100;
    } else {
      unknowncount = parseInt(String(WORDCOUNTS.total[key]), 10) + parseInt(String(WORDCOUNTS.expr[key] || 0), 10) - parseInt(String(known), 10);
      unknownpercent = Math.round(unknowncount * 10000 / (known + unknowncount)) / 100;
    }
    $('#unknownpercent_' + key).html(unknownpercent === 0 ? '0' : unknownpercent.toFixed(2));
    // end here

    if (SUW & 16) {
      stat0 = parseInt(String(value), 10) + parseInt(String(WORDCOUNTS.expru[key] || 0), 10) - parseInt(String(knownu), 10);
    } else {
      stat0 = parseInt(String(WORDCOUNTS.total[key]), 10) + parseInt(String(WORDCOUNTS.expr[key] || 0), 10) - parseInt(String(known), 10);
    }
    $('#stat_0_' + key).html(String(stat0));
  });
  $('.barchart').each(set_barchart_item);
}

/**
 * Handle the click event to switch between total and
 * unique words count in edit_texts.php.
 */
export function word_count_click(): void {
  $('.wc_cont').children().each(function () {
    if (parseInt(getAttr($(this), 'data_wo_cnt') || '0', 10) === 1) {
      $(this).html('u');
    } else {
      $(this).html('t');
    }
    (window as unknown as { SUW: number }).SUW = (parseInt(getAttr($('#chart'), 'data_wo_cnt') || '0', 10) << 4) +
    (parseInt(getAttr($('#unknownpercent'), 'data_wo_cnt') || '0', 10) << 3) +
    (parseInt(getAttr($('#unknown'), 'data_wo_cnt') || '0', 10) << 2) +
    (parseInt(getAttr($('#saved'), 'data_wo_cnt') || '0', 10) << 1) +
    (parseInt(getAttr($('#total'), 'data_wo_cnt') || '0', 10));
    set_word_counts();
  });
}

export const lwt = {

  /**
   * Prepare the action so that a click switches between
   * unique word count and total word count.
   */
  prepare_word_count_click: function (): void {
    $('#total,#saved,#unknown,#chart,#unknownpercent')
      .on('click', function (event) {
        $(this).attr('data_wo_cnt', String(parseInt(getAttr($(this), 'data_wo_cnt') || '0', 10) ^ 1));
        word_count_click();
        event.stopImmediatePropagation();
      }).attr('title', 'u: Unique Word Counts\nt: Total  Word  Counts');
    do_ajax_word_counts();
  },

  /**
   * Save the settings about unique/total words count.
   */
  save_text_word_count_settings: function (): void {
    if (SUW === SHOWUNIQUE) {
      return;
    }
    const a = getAttr($('#total'), 'data_wo_cnt') +
      getAttr($('#saved'), 'data_wo_cnt') +
      getAttr($('#unknown'), 'data_wo_cnt') +
      getAttr($('#unknownpercent'), 'data_wo_cnt') +
      getAttr($('#chart'), 'data_wo_cnt');
    do_ajax_save_setting('set-show-text-word-counts', a);
  }
};

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  w.do_ajax_word_counts = do_ajax_word_counts;
  w.set_barchart_item = set_barchart_item;
  w.set_word_counts = set_word_counts;
  w.word_count_click = word_count_click;
  w.lwt = lwt;
}

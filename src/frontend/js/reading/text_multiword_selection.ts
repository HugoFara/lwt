/**
 * Multi-word drag-and-drop selection for text reading.
 * Handles the creation of multi-word expressions by click-and-drag selection.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { getAttr } from './text_annotations';

// Declare external functions
declare function showRightFrames(url1?: string, url2?: string): void;

// Type definitions
interface LwtSettings {
  jQuery_tooltip: boolean;
  hts: number;
  word_status_filter: string;
  annotations_mode: number;
}

interface LwtText {
  id: number;
  reading_position: number;
  annotations: Record<string, [unknown, string, string]>;
}

interface LwtDataGlobal {
  language: {
    id: number;
    dict_link1: string;
    dict_link2: string;
    translator_link: string;
    delimiter: string;
    rtl: boolean;
  };
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
  }
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


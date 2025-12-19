/**
 * Multi-word drag-and-drop selection for text reading.
 * Handles the creation of multi-word expressions by click-and-drag selection.
 *
 * @license Unlicense <http://unlicense.org/>
 */

// getAttr not needed - using native getAttribute
import { hoverIntent } from '../core/hover_intent';
import { loadModalFrame } from './frame_management';
import { removeAllTooltips } from '../ui/native_tooltip';

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

interface MwordDragNDropState {
  event: (MouseEvent & { data?: { annotation?: number } }) | undefined;
  pos: number | undefined;
  timeout: ReturnType<typeof setTimeout> | undefined;
  context: HTMLElement | undefined;
  finish: (ev: MouseEvent & { handled?: boolean }) => void;
  twordMouseOver: (this: HTMLElement) => void;
  sentenceOver: (this: HTMLElement) => void;
  startInteraction: () => void;
  stopInteraction: () => void;
}

/**
 * Helper to get attribute value from an element.
 */
function getElAttr(el: Element | null, attr: string): string {
  if (!el) return '';
  const val = el.getAttribute(attr);
  return val !== null ? val : '';
}

export const mwordDragNDrop: MwordDragNDropState = {

  event: undefined,

  pos: undefined,

  timeout: undefined,

  context: undefined,

  /**
   * Multi-word selection is finished
   */
  finish: function (ev: MouseEvent & { handled?: boolean }): void {
    const context = mwordDragNDrop.context;
    if (!context) return;
    if (ev.handled !== true) {
      const lwordTwordEls = context.querySelectorAll('.lword.tword');
      const len = lwordTwordEls.length;
      if (len > 0) {
        const firstLword = context.querySelector('.lword');
        const word_ord = getElAttr(firstLword, 'data_order');
        if (len > 1) {
          const lwordEls = context.querySelectorAll('.lword');
          let text = '';
          lwordEls.forEach(el => { text += el.textContent || ''; });
          if (text.length > 250) {
            alert('Selected text is too long!!!');
          } else {
            const params = new URLSearchParams({
              tid: String(LWT_DATA.text.id),
              len: String(len),
              ord: word_ord,
              txt: text
            });
            loadModalFrame('edit_mword.php?' + params.toString());
          }
        } else {
          // Create only a normal word
          const wordEl = document.getElementById('ID-' + word_ord + '-1');
          const params = new URLSearchParams({
            tid: String(LWT_DATA.text.id),
            ord: word_ord,
            txt: wordEl?.textContent || ''
          });
          loadModalFrame('/word/edit?' + params.toString());
        }
      }
      context.querySelectorAll('span').forEach(el => {
        el.classList.remove('tword', 'nword');
      });
      ev.handled = true;
    }
  },

  /**
   * Function to trigger above a term word
   */
  twordMouseOver: function (this: HTMLElement): void {
    const context = mwordDragNDrop.context;
    if (!context) return;
    // eslint-disable-next-line @typescript-eslint/no-this-alias
    const selfEl = this;

    const mouseUpHandler = function (e: MouseEvent) {
      context.querySelectorAll('.wsty').forEach(el => {
        el.classList.add('status' + getElAttr(el, 'data_status'));
      });
      const target = e.target as HTMLElement | null;
      if (!target?.classList.contains('tword')) {
        context.querySelectorAll('span').forEach(el => {
          el.classList.remove('nword', 'tword', 'lword');
        });
        context.querySelectorAll<HTMLElement>('.wsty').forEach(el => {
          el.style.backgroundColor = '';
          el.style.borderBottomColor = '';
        });
        document.getElementById('pe')?.remove();
      }
    };
    document.documentElement.addEventListener('mouseup', mouseUpHandler, { once: true });

    mwordDragNDrop.pos = parseInt(getElAttr(selfEl, 'data_order') || '0', 10);

    // Add ".lword" class on this element
    context.querySelectorAll('.lword').forEach(el => el.classList.remove('lword'));
    selfEl.classList.add('lword');

    const mouseLeaveHandler = function () {
      context.querySelectorAll('.lword').forEach(el => el.classList.remove('lword'));
    };
    context.addEventListener('mouseleave', mouseLeaveHandler);

    // One-time mouseup handler for .nword,.tword
    const finishHandler = function (e: MouseEvent) {
      const target = e.target as HTMLElement | null;
      if (target?.matches('.nword, .tword')) {
        mwordDragNDrop.finish(e);
        context.removeEventListener('mouseup', finishHandler);
      }
    };
    context.addEventListener('mouseup', finishHandler);
  },

  /**
   * When having the cursor over the sentence.
   */
  sentenceOver: function (this: HTMLElement): void {
    const context = mwordDragNDrop.context;
    if (!context) return;
    context.querySelectorAll('.lword').forEach(el => el.classList.remove('lword'));
    const lpos = parseInt(getElAttr(this, 'data_order') || '0', 10);
    this.classList.add('lword');
    if (mwordDragNDrop.pos !== undefined && lpos > mwordDragNDrop.pos) {
      for (let i = mwordDragNDrop.pos; i < lpos; i++) {
        context.querySelectorAll(
          '.tword[data_order="' + i + '"],.nword[data_order="' + i + '"]'
        ).forEach(el => el.classList.add('lword'));
      }
    } else if (mwordDragNDrop.pos !== undefined) {
      for (let i = mwordDragNDrop.pos; i > lpos; i--) {
        context.querySelectorAll(
          '.tword[data_order="' + i + '"],.nword[data_order="' + i + '"]'
        ).forEach(el => el.classList.add('lword'));
      }
    }
  },

  /**
   * Start creating a multi-word.
   */
  startInteraction: function (): void {
    const context = mwordDragNDrop.context;
    if (!context) return;

    // Helper function to get siblings until a matching element (like jQuery's nextUntil)
    const nextUntil = (el: Element, stopSelector: string, filterSelector: string): Element[] => {
      const result: Element[] = [];
      let sibling = el.nextElementSibling;
      while (sibling) {
        if (sibling.matches(stopSelector)) break;
        if (sibling.matches(filterSelector)) {
          result.push(sibling);
        }
        sibling = sibling.nextElementSibling;
      }
      return result;
    };

    // Add .tword (term word) and .nword (not word) subelements
    context.querySelectorAll<HTMLElement>('.wsty').forEach(el => {
      el.style.backgroundColor = 'inherit';
      el.style.borderBottomColor = 'rgba(0,0,0,0)';
    });
    context.querySelectorAll<HTMLElement>('.wsty:not(.hide):not(.word)').forEach(el => {
      const f = parseInt(getElAttr(el, 'data_code') || '0', 10) * 2 +
        parseInt(getElAttr(el, 'data_order') || '0', 10) - 1;
      let childr_html = '';
      const siblings = nextUntil(el, '[id^="ID-' + f + '-"]', '[id$="-1"]');
      siblings.forEach(child => {
        const w_order = child.getAttribute('data_order');
        if (w_order !== null) {
          childr_html += '<span class="tword" data_order="' + w_order + '">' +
            child.textContent + '</span>';
        } else {
          const childId = child.id || '';
          childr_html += '<span class="nword" data_order="' +
            childId.split('-')[1] + '">' + child.textContent + '</span>';
        }
      });
      el.innerHTML = childr_html;
    });

    // Replace '#pe' element
    document.getElementById('pe')?.remove();
    const contextId = context.id;
    document.body.insertAdjacentHTML('beforeend',
      '<style id="pe">#' + contextId + ' .wsty:after,#' +
      contextId + ' .wsty:before{opacity:0}</style>'
    );

    // Add class ".nword" (not word), and set attribute "data_order"
    context.querySelectorAll('[id$="-1"]:not(.hide):not(.wsty)').forEach(el => {
      el.classList.add('nword');
      const elId = el.id || '';
      el.setAttribute('data_order', elId.split('-')[1]);
    });

    // Attach children ".tword" (term) to ".word"
    context.querySelectorAll('.word:not(.hide)').forEach(el => {
      el.innerHTML = '<span class="tword" data_order="' + getElAttr(el, 'data_order') + '">' +
        el.textContent + '</span>';
    });

    // Edit "tword" elements by filling their attributes
    const event = mwordDragNDrop.event;
    const annotationMode = event?.data?.annotation;
    if (annotationMode === 1) {
      context.querySelectorAll('.wsty:not(.hide)').forEach(el => {
        const twords = el.querySelectorAll('.tword');
        const lastTword = twords[twords.length - 1];
        if (lastTword) {
          lastTword.setAttribute('data_ann', getElAttr(el, 'data_ann'));
          lastTword.setAttribute('data_trans', getElAttr(el, 'data_trans'));
          lastTword.classList.add('content' + getElAttr(el, 'data_status'));
        }
        el.classList.remove('status1', 'status2', 'status3', 'status4', 'status5', 'status98', 'status99');
      });
    } else if (annotationMode === 3) {
      context.querySelectorAll('.wsty:not(.hide)').forEach(el => {
        const firstTword = el.querySelector('.tword');
        if (firstTword) {
          firstTword.setAttribute('data_ann', getElAttr(el, 'data_ann'));
          firstTword.setAttribute('data_trans', getElAttr(el, 'data_trans'));
          firstTword.classList.add('content' + getElAttr(el, 'data_status'));
        }
        el.classList.remove('status1', 'status2', 'status3', 'status4', 'status5', 'status98', 'status99');
      });
    }

    // Prepare interaction on ".tword" to mouseover (one-time)
    const mouseOverHandler = function (e: MouseEvent) {
      const target = e.target as HTMLElement | null;
      if (target?.matches('.tword')) {
        mwordDragNDrop.twordMouseOver.call(target);
        context.removeEventListener('mouseover', mouseOverHandler);
      }
    };
    context.addEventListener('mouseover', mouseOverHandler);

    // Prepare a hover intent interaction
    hoverIntent(context, {
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
    document.querySelectorAll('.nword').forEach(el => el.classList.remove('nword'));
    document.querySelectorAll('.tword').forEach(el => el.classList.remove('tword'));
    document.querySelectorAll('.lword').forEach(el => el.classList.remove('lword'));
    if (mwordDragNDrop.context) {
      mwordDragNDrop.context.querySelectorAll<HTMLElement>('.wsty').forEach(el => {
        el.style.backgroundColor = '';
        el.style.borderBottomColor = '';
      });
    }
    document.getElementById('pe')?.remove();
  }
};

/**
 * Initialize multi-word drag-and-drop selection when mousedown occurs on a word.
 * Sets up the selection context and starts a timeout for the interaction.
 *
 * @param this The HTML element where mousedown occurred
 * @param event The mousedown event
 */
export function mword_drag_n_drop_select(
  this: HTMLElement,
  event: MouseEvent & { data?: { annotation?: number } }
): void {
  if (LWT_DATA.settings.jQuery_tooltip) { removeAllTooltips(); }
  const sentence = this.parentElement;
  if (!sentence) return;

  mwordDragNDrop.context = sentence;
  mwordDragNDrop.event = event;

  // One-time handlers for mouseup and mouseout
  const stopHandler = () => {
    mwordDragNDrop.stopInteraction();
    sentence.removeEventListener('mouseup', stopHandler);
    sentence.removeEventListener('mouseout', stopHandler);
  };
  sentence.addEventListener('mouseup', stopHandler, { once: true });
  sentence.addEventListener('mouseout', stopHandler, { once: true });

  mwordDragNDrop.timeout = setTimeout(mwordDragNDrop.startInteraction, 300);
}


/**
 * Text Display - Word counts, barcharts, and text statistics
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import { onDomReady } from '@shared/utils/dom_ready';
import { saveSetting } from '@shared/utils/ajax_utilities';
import { apiGet } from '@shared/api/client';
import { updateAllTextStatusCharts } from '@modules/text/pages/text_status_chart';

// Word counts data structure
interface WordCounts {
  expr: Record<string, number>;
  expru: Record<string, number>;
  total: Record<string, number>;
  totalu: Record<string, number>;
  stat: Record<string, Record<string, number>>;
  statu: Record<string, Record<string, number>>;
}

// Module-scoped state (no longer window globals)
let wordCounts: WordCounts | null = null;
/** Show unique words bitflags */
let showUniqueWords = 0;
/** Original show counts setting (for detecting changes) */
let initialShowCounts = 0;

/**
 * Initialize module state for testing purposes.
 * @internal
 */
export function _initTestState(counts: WordCounts | null, suw = 0, initial = 0): void {
  wordCounts = counts;
  showUniqueWords = suw;
  initialShowCounts = initial;
}

/**
 * Helper to safely get an HTML attribute value as a string.
 */
function getAttr(el: Element | null, attr: string): string {
  return el?.getAttribute(attr) || '';
}

/**
 * Update word counts with an AJAX request.
 */
export async function fetchWordCounts(): Promise<void> {
  const checkboxes = document.querySelectorAll<HTMLInputElement>('.markcheck');
  const textIds = Array.from(checkboxes)
    .map(cb => cb.value)
    .join(',');

  const response = await apiGet<WordCounts>('/texts/statistics', {
    text_ids: textIds
  });

  if (response.data) {
    wordCounts = response.data;
    handleWordCountClick();
    // Update legacy barcharts if present
    document.querySelectorAll('.barchart').forEach(el => {
      el.classList.remove('hide');
    });
    // Update Chart.js status charts if present
    updateAllTextStatusCharts();
  }
}

/**
 * Set a unique item in barchart to reflect how many words are known.
 */
export function updateBarchartItem(this: HTMLElement): void {
  if (!wordCounts) return;

  const spanEl = this.querySelector('span');
  const idAttr = getAttr(spanEl, 'id');
  const id = idAttr.split('_')[2] || '';
  /** @var v Number of terms in the text */
  let v: number;
  if (showUniqueWords & 16) {
    v = parseInt(String(wordCounts.expru[id] || 0), 10) +
    parseInt(String(wordCounts.totalu[id]), 10);
  } else {
    v = parseInt(String(wordCounts.expr[id] || 0), 10) +
    parseInt(String(wordCounts.total[id]), 10);
  }
  const children = this.querySelectorAll<HTMLElement>(':scope > li');
  children.forEach((li) => {
    const span = li.querySelector('span');
    /** Word count in the category */
    let cat_word_count = parseInt(span?.textContent || '0', 10);
    // Avoid to put 0 in logarithm
    cat_word_count += 1;
    v += 1;
    const h = 25 - Math.log(cat_word_count) / Math.log(v) * 25;
    li.style.borderTopWidth = h + 'px';
  });
}

/**
 * Set the number of words known in a text (in edit_texts.php main page).
 */
export function updateWordCounts(): void {
  if (!wordCounts) return;

  // Capture in local const for TypeScript narrowing in closures
  const counts = wordCounts;

  Object.entries(counts.totalu).forEach(([key, value]) => {
    let knownu = 0, known = 0, todo: number, stat0: number;
    const expr = counts.expru[key] ? parseInt(String((showUniqueWords & 2) ? counts.expru[key] : counts.expr[key]), 10) : 0;
    if (!counts.stat[key]) {
      counts.statu[key] = counts.stat[key] = {};
    }
    const totalEl = document.getElementById('total_' + key);
    if (totalEl) totalEl.innerHTML = String((showUniqueWords & 1) ? value : counts.total[key]);

    Object.entries(counts.statu[key]).forEach(([k, v]) => {
      if (showUniqueWords & 8) {
        const statEl = document.getElementById('stat_' + k + '_' + key);
        if (statEl) statEl.innerHTML = String(v);
      }
      knownu += parseInt(String(v), 10);
    });

    Object.entries(counts.stat[key]).forEach(([k, v]) => {
      if (!(showUniqueWords & 8)) {
        const statEl = document.getElementById('stat_' + k + '_' + key);
        if (statEl) statEl.innerHTML = String(v);
      }
      known += parseInt(String(v), 10);
    });

    const savedEl = document.getElementById('saved_' + key);
    if (savedEl) savedEl.innerHTML = known ? (String((showUniqueWords & 2 ? knownu : known) - expr) + '+' + expr) : '0';

    if (showUniqueWords & 4) {
      todo = parseInt(String(value), 10) + parseInt(String(counts.expru[key] || 0), 10) - parseInt(String(knownu), 10);
    } else {
      todo = parseInt(String(counts.total[key]), 10) + parseInt(String(counts.expr[key] || 0), 10) - parseInt(String(known), 10);
    }
    const todoEl = document.getElementById('todo_' + key);
    if (todoEl) todoEl.innerHTML = String(todo);

    // added unknown percent
    let unknowncount: number, unknownpercent: number;
    if (showUniqueWords & 8) {
      unknowncount = parseInt(String(value), 10) + parseInt(String(counts.expru[key] || 0), 10) - parseInt(String(knownu), 10);
      unknownpercent = Math.round(unknowncount * 10000 / (knownu + unknowncount)) / 100;
    } else {
      unknowncount = parseInt(String(counts.total[key]), 10) + parseInt(String(counts.expr[key] || 0), 10) - parseInt(String(known), 10);
      unknownpercent = Math.round(unknowncount * 10000 / (known + unknowncount)) / 100;
    }
    const unknownPercentEl = document.getElementById('unknownpercent_' + key);
    if (unknownPercentEl) unknownPercentEl.innerHTML = unknownpercent === 0 ? '0' : unknownpercent.toFixed(2);
    // end here

    if (showUniqueWords & 16) {
      stat0 = parseInt(String(value), 10) + parseInt(String(counts.expru[key] || 0), 10) - parseInt(String(knownu), 10);
    } else {
      stat0 = parseInt(String(counts.total[key]), 10) + parseInt(String(counts.expr[key] || 0), 10) - parseInt(String(known), 10);
    }
    const stat0El = document.getElementById('stat_0_' + key);
    if (stat0El) stat0El.innerHTML = String(stat0);
  });

  document.querySelectorAll<HTMLElement>('.barchart').forEach((el) => {
    updateBarchartItem.call(el);
  });
}

/**
 * Handle the click event to switch between total and
 * unique words count in edit_texts.php.
 */
export function handleWordCountClick(): void {
  document.querySelectorAll('.wc_cont').forEach((cont) => {
    cont.querySelectorAll<HTMLElement>(':scope > *').forEach((child) => {
      if (parseInt(getAttr(child, 'data_wo_cnt') || '0', 10) === 1) {
        child.innerHTML = 'u';
      } else {
        child.innerHTML = 't';
      }
    });
  });

  const chartEl = document.getElementById('chart');
  const unknownPercentEl = document.getElementById('unknownpercent');
  const unknownEl = document.getElementById('unknown');
  const savedEl = document.getElementById('saved');
  const totalEl = document.getElementById('total');

  showUniqueWords =
    (parseInt(getAttr(chartEl, 'data_wo_cnt') || '0', 10) << 4) +
    (parseInt(getAttr(unknownPercentEl, 'data_wo_cnt') || '0', 10) << 3) +
    (parseInt(getAttr(unknownEl, 'data_wo_cnt') || '0', 10) << 2) +
    (parseInt(getAttr(savedEl, 'data_wo_cnt') || '0', 10) << 1) +
    (parseInt(getAttr(totalEl, 'data_wo_cnt') || '0', 10));

  updateWordCounts();
}

export const lwt = {

  /**
   * Prepare the action so that a click switches between
   * unique word count and total word count.
   */
  prepare_handleWordCountClick: function (): void {
    const elements = document.querySelectorAll<HTMLElement>('#total,#saved,#unknown,#chart,#unknownpercent');
    elements.forEach((el) => {
      el.addEventListener('click', function (event) {
        const currentVal = parseInt(getAttr(this, 'data_wo_cnt') || '0', 10);
        this.setAttribute('data_wo_cnt', String(currentVal ^ 1));
        handleWordCountClick();
        event.stopImmediatePropagation();
      });
      el.title = 'u: Unique Word Counts\nt: Total  Word  Counts';
    });
    fetchWordCounts();
  },

  /**
   * Save the settings about unique/total words count.
   */
  save_text_word_count_settings: function (): void {
    if (showUniqueWords === initialShowCounts) {
      return;
    }
    const totalEl = document.getElementById('total');
    const savedEl = document.getElementById('saved');
    const unknownEl = document.getElementById('unknown');
    const unknownPercentEl = document.getElementById('unknownpercent');
    const chartEl = document.getElementById('chart');

    const a = getAttr(totalEl, 'data_wo_cnt') +
      getAttr(savedEl, 'data_wo_cnt') +
      getAttr(unknownEl, 'data_wo_cnt') +
      getAttr(unknownPercentEl, 'data_wo_cnt') +
      getAttr(chartEl, 'data_wo_cnt');
    saveSetting('set-show-text-word-counts', a);
  }
};

// Export lwt to window for inline PHP scripts
declare global {
  interface Window {
    lwt: typeof lwt;
  }
}

window.lwt = lwt;

/**
 * Auto-initialize text list word counts if config element is present.
 */
function autoInitTextList(): void {
  const configEl = document.getElementById('text-list-config');
  if (!configEl) return;

  try {
    const config = JSON.parse(configEl.textContent || '{}');
    // Initialize module state
    wordCounts = null;
    showUniqueWords = config.showCounts || 0;
    initialShowCounts = config.showCounts || 0;

    // Initialize word count click handlers
    lwt.prepare_handleWordCountClick();

    // Set up beforeunload handler
    window.addEventListener('beforeunload', lwt.save_text_word_count_settings);
  } catch (e) {
    console.error('Failed to parse text-list-config:', e);
  }
}

// Auto-initialize on DOM ready
onDomReady(autoInitTextList);

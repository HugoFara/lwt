/**
 * Expression Interactable - Auto-initialization for multi-word expressions.
 *
 * Handles the client-side initialization of newly created multi-word expressions
 * by reading configuration from JSON script tags.
 *
 * @license unlicense
 * @since 3.0.0
 */

import { make_tooltip } from '../terms/word_status';
import { newExpressionInteractable } from '../core/user_interactions';

/**
 * Term attributes for multi-word expression.
 */
interface TermAttrs {
  class: string;
  data_trans: string;
  data_rom: string;
  data_code: number;
  data_status: string;
  data_wid: number;
  title?: string;
}

/**
 * Configuration for multi-word expression interactable.
 */
interface MultiWordConfig {
  attrs: TermAttrs;
  multiWords: Record<number, Record<number, string>>;
  hex: string;
  showAll: boolean;
}

/**
 * Configuration for expression interactable (version 2).
 */
interface ExpressionConfig {
  attrs: TermAttrs;
  appendText: Record<number, string>;
  term: string;
  len: number;
  hex: string;
  showAll: boolean;
}

/**
 * Initialize a multi-word expression from config data.
 * Used by ExpressionService::newMultiWordInteractable().
 */
function initMultiWordInteractable(config: MultiWordConfig): void {
  const term = config.attrs;
  const textId = window.parent?.LWT_DATA?.text?.id;

  if (textId === undefined) {
    console.warn('LWT_DATA.text.id not available for multi-word init');
    return;
  }

  // Always generate tooltips (jQuery UI tooltips removed, now using native)
  const multiWordText = config.multiWords[textId]?.[0] || '';
  term.title = make_tooltip(
    multiWordText,
    term.data_trans,
    term.data_rom,
    parseInt(term.data_status, 10)
  );

  let attrs = '';
  Object.entries(term).forEach(([k, v]) => {
    attrs += ' ' + k + '="' + v + '"';
  });

  newExpressionInteractable(
    config.multiWords[textId],
    attrs,
    term.data_code,
    config.hex,
    config.showAll
  );
}

/**
 * Initialize an expression from config data (version 2).
 * Used by ExpressionService::newExpressionInteractable2().
 */
function initExpressionInteractable2(config: ExpressionConfig): void {
  const term = config.attrs;

  // Always generate tooltips (jQuery UI tooltips removed, now using native)
  term.title = make_tooltip(
    config.term,
    term.data_trans,
    term.data_rom,
    parseInt(term.data_status, 10)
  );

  let attrs = '';
  Object.entries(term).forEach(([k, v]) => {
    attrs += ' ' + k + '="' + v + '"';
  });

  newExpressionInteractable(
    config.appendText,
    attrs,
    config.len,
    config.hex,
    config.showAll
  );
}

/**
 * Auto-initialize multi-word expressions from JSON config elements.
 */
export function autoInitExpressionInteractables(): void {
  // Find and process multi-word config elements
  document.querySelectorAll<HTMLScriptElement>('script[data-lwt-multiword-config]').forEach((el) => {
    try {
      const config = JSON.parse(el.textContent || '{}') as MultiWordConfig;
      initMultiWordInteractable(config);
      // Remove the script tag after processing
      el.remove();
    } catch (e) {
      console.error('Failed to parse multi-word config:', e);
    }
  });

  // Find and process expression config elements (v2)
  document.querySelectorAll<HTMLScriptElement>('script[data-lwt-expression-config]').forEach((el) => {
    try {
      const config = JSON.parse(el.textContent || '{}') as ExpressionConfig;
      initExpressionInteractable2(config);
      // Remove the script tag after processing
      el.remove();
    } catch (e) {
      console.error('Failed to parse expression config:', e);
    }
  });
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', autoInitExpressionInteractables);

// Also expose for manual initialization (useful when content is loaded dynamically)
if (typeof window !== 'undefined') {
  (window as unknown as Record<string, unknown>).autoInitExpressionInteractables = autoInitExpressionInteractables;
}

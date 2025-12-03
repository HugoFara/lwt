/**
 * Global exports for LWT.
 *
 * This file exposes TypeScript functions to the global window object
 * so they can be called from:
 * 1. PHP-generated inline JavaScript (e.g., owin)
 * 2. Cross-frame/cross-window communication (e.g., cClick, do_ajax_edit_impr_text)
 *
 * NOTE: Most functions should be imported directly between TypeScript modules.
 * Only add exports here if they MUST be accessible from:
 * - PHP-generated JavaScript strings
 * - Child frames calling parent (window.parent.*)
 * - Popup windows calling opener (window.opener.*)
 *
 * @since 3.0.0
 */

// Import functions that need to be globally accessible for PHP/cross-frame use
import { owin } from './terms/dictionary';
import { cClick } from './ui/word_popup';
import { do_ajax_edit_impr_text } from './terms/term_operations';

// Declare global window interface extensions
declare global {
  interface Window {
    // Called from PHP-generated JavaScript (DictionaryService.php)
    owin: typeof owin;

    // Cross-frame communication (word_result_init.ts calls window.parent.cClick)
    cClick: typeof cClick;

    // Cross-window communication (word_result_init.ts calls window.opener.do_ajax_edit_impr_text)
    do_ajax_edit_impr_text: typeof do_ajax_edit_impr_text;
  }
}

// Expose to window - only functions needed for PHP/cross-frame communication
window.owin = owin;
window.cClick = cClick;
window.do_ajax_edit_impr_text = do_ajax_edit_impr_text;

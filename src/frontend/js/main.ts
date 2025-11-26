/**
 * Vite entry point - bridges ES modules and global scripts
 *
 * This file serves as the main entry point for the Vite build system.
 * It imports CSS and sets up jQuery globally for backward compatibility
 * with legacy scripts.
 */

// Import CSS
import '../css/styles.css';
import '../css/jquery-ui.css';
import '../css/jquery.tagit.css';
import '../css/feed_wizard.css';

// Import jQuery from npm and expose globally (for legacy compatibility)
import $ from 'jquery';
import 'jquery-ui-dist/jquery-ui';

// Import TypeScript modules (these auto-register globals for backward compat)
import './pgm';
import './word_status';
import './dictionary';
import './html_utils';
import './cookies';
import './bulk_actions';
import './audio_controller';
import './translation_api';
import './jq_pgm';
import './text_events';
import './user_interactions';
import './unloadformcheck';
import './jq_feedwizard';
import './overlib_interface';

// Expose jQuery globally for legacy scripts and plugins
declare global {
  interface Window {
    $: typeof $;
    jQuery: typeof $;
    LWT_VITE_LOADED: boolean;
  }
}

window.$ = $;
window.jQuery = $;
window.LWT_VITE_LOADED = true;

// Log to console in development
if (import.meta.env.DEV) {
  console.log('LWT Vite bundle loaded (development mode)');
}

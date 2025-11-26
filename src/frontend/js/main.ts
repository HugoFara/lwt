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

// Core utilities
import './core/html_utils';
import './core/cookies';

// Legacy modules (being refactored)
import './legacy/pgm';
import './legacy/jq_pgm';
import './legacy/user_interactions';

// Terms/vocabulary management
import './terms/word_status';
import './terms/dictionary';
import './terms/translation_api';
import './terms/overlib_interface';

// Reading interface
import './reading/audio_controller';
import './reading/text_events';

// Forms
import './forms/bulk_actions';
import './forms/unloadformcheck';

// Feeds
import './feeds/jq_feedwizard';

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

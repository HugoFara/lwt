/**
 * Vite entry point for the LWT application.
 *
 * This file serves as the main entry point for the Vite build system.
 * It imports CSS and all TypeScript modules.
 */

// Import CSS
import '../css/styles.css';
import '../css/jquery-ui.css';
import '../css/jquery.tagit.css';
import '../css/feed_wizard.css';

// Import jQuery from npm and expose globally for plugins
import $ from 'jquery';
import 'jquery-ui-dist/jquery-ui';

// Import TypeScript modules

// Core utilities
import './core/html_utils';
import './core/cookies';
import './core/lwt_state';
import './core/ajax_utilities';
import './core/ui_utilities';
import './core/user_interactions';
import './core/language_settings';

// Terms/vocabulary management
import './terms/word_status';
import './terms/dictionary';
import './terms/translation_api';
import './terms/overlib_interface';
import './terms/term_operations';

// Reading interface
import './reading/audio_controller';
import './reading/text_events';
import './reading/text_display';
import './reading/frame_management';

// Forms
import './forms/bulk_actions';
import './forms/unloadformcheck';
import './forms/form_validation';

// Testing
import './testing/test_mode';

// Media
import './media/media_selection';

// Feeds
import './feeds/jq_feedwizard';

// Expose jQuery globally for plugins
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

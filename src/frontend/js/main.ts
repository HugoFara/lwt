/**
 * Vite entry point for the LWT application.
 *
 * This file serves as the main entry point for the Vite build system.
 * It imports CSS and all TypeScript modules.
 */

// Import CSS from base directory
import '../css/base/styles.css';
import '../css/base/jquery-ui.css';
import '../css/base/html5_audio_player.css';

// jQuery is loaded externally (synchronously) for inline script compatibility
// In production, we use the global jQuery; in dev mode, we may import it
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
import './core/simple_interactions';

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
import './reading/annotation_toggle';
import './reading/set_mode_result';
import './reading/text_reading_init';
import './reading/annotation_interactions';

// Forms
import './forms/bulk_actions';
import './forms/unloadformcheck';
import './forms/form_validation';
import './forms/form_initialization';
import './forms/word_form_auto';

// Testing
import './testing/test_mode';

// Media
import './media/media_selection';
import './media/html5_audio_player';

// Feeds
import './feeds/jq_feedwizard';
import './feeds/feed_browse';
import './feeds/feed_loader';
import './feeds/feed_multi_load';
import './feeds/feed_index';
import './feeds/feed_form';

// Texts
import './texts/text_list';

// Words
import './words/word_list_filter';
import './words/word_list_table';

// Tags
import './tags/tag_list';

// Languages
import './languages/language_wizard';

// Admin
import './admin/server_data';
import './admin/tts_settings';

// UI Components
import './ui/modal';

// Global exports for inline PHP scripts
import './globals';

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

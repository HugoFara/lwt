/**
 * Vite entry point for the LWT application.
 *
 * This file serves as the main entry point for the Vite build system.
 * It imports CSS and all TypeScript modules.
 */

// Import Alpine.js
import Alpine from 'alpinejs';

// Import Bulma CSS framework
import 'bulma/css/bulma.min.css';

// Import CSS from base directory
import '../css/base/styles.css';
import '../css/base/html5_audio_player.css';
import '../css/base/icons.css';

// Import TypeScript modules

// Core utilities
import './core/html_utils';
import './core/cookies';
import './core/tts_storage';
import './core/lwt_state';
import './core/app_data';
import './core/api_client';
import './core/ajax_utilities';
import './core/ui_utilities';
import './core/user_interactions';
import './core/language_settings';
import './core/simple_interactions';

// API modules (Phase 1 - centralized API client)
import './api/terms';
import './api/texts';
import './api/review';
import './api/settings';
import './api/words';

// Terms/vocabulary management
import './terms/word_status';
import './terms/dictionary';
import './terms/translation_api';
import './terms/translation_page';
import './terms/overlib_interface';
import './terms/term_operations';

// Reading interface
import './reading/stores/word_store';
import './reading/stores/word_form_store';
import './reading/components/word_modal';
import './reading/components/word_edit_form';
import './reading/components/text_reader';
import './reading/text_renderer';
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

// Testing - Legacy
import './testing/test_mode';
import './testing/test_header';
import './testing/test_table';
import './testing/test_ajax';

// Testing - Bulma/Alpine components
import './testing/stores/test_store';
import './testing/components/test_view';

// Media
import './media/media_selection';
import './media/html5_audio_player';
import './media/audio_player_alpine';

// Feeds
import './feeds/feed_browse';
import './feeds/feed_loader';
import './feeds/feed_multi_load';
import './feeds/feed_index';
import './feeds/feed_form';
import './feeds/feed_text_edit';

// Feed Wizard - Alpine.js components
import './feeds/stores/feed_wizard_store';
import './feeds/components/feed_wizard_step1';
import './feeds/components/feed_wizard_step2';
import './feeds/components/feed_wizard_step3';
import './feeds/components/feed_wizard_step4';

// Feed Wizard - Legacy XPath selection (still needed for core functionality)
import './feeds/jq_feedwizard';

// Texts
import './texts/text_list';
import './texts/texts_grouped_app';
import './texts/archived_texts_grouped_app';
import './texts/text_status_chart';
import './texts/youtube_import';
import './texts/text_check_display';
import './texts/text_print_app';

// Words
import './words/word_list_app'; // Alpine.js SPA component
import './words/word_list_filter';
import './words/word_list_table';
import './words/word_dom_updates';
import './words/bulk_translate';
import './words/word_status_ajax';
import './words/word_upload';
import './words/expression_interactable';
import './words/word_result_init';

// Tags
import './tags/tag_list';

// Languages
import './languages/language_wizard';
import './languages/language_form';
import './languages/language_list';

// Admin
import './admin/server_data';
import './admin/tts_settings';
import './admin/table_management';
import './admin/settings_form';
import './admin/statistics_charts';
import './admin/backup_manager';

// Home
import './home/home_app';

// UI Components
import './ui/modal';
import './ui/sorttable';
import './ui/lucide_icons';
import './ui/navbar';
import './ui/footer';

declare global {
  interface Window {
    LWT_VITE_LOADED: boolean;
    Alpine: typeof Alpine;
  }
}

// Initialize Alpine.js globally
window.Alpine = Alpine;

// Start Alpine.js
Alpine.start();

window.LWT_VITE_LOADED = true;

// Log to console in development
if (import.meta.env.DEV) {
  console.log('LWT Vite bundle loaded (development mode)');
}

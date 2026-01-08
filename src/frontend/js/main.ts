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

// =============================================================================
// SHARED INFRASTRUCTURE
// =============================================================================

// Shared utilities
import '@shared/utils/html_utils';
import '@shared/utils/cookies';
import '@shared/utils/tts_storage';
import '@shared/utils/ajax_utilities';
import '@shared/utils/ui_utilities';
import '@shared/utils/user_interactions';
import '@shared/utils/simple_interactions';
import '@shared/utils/inline_markdown';

// Shared stores
import '@shared/stores/lwt_state';
import '@shared/stores/app_data';

// Shared API client
import '@shared/api/client';

// Shared components
import '@shared/components/modal';
import '@shared/components/sorttable';
import '@shared/components/navbar';
import '@shared/components/footer';

// Shared icons
import '@shared/icons/lucide_icons';

// Shared forms
import '@shared/forms/bulk_actions';
import '@shared/forms/unloadformcheck';
import '@shared/forms/form_validation';
import '@shared/forms/form_initialization';
import '@shared/forms/word_form_auto';

// =============================================================================
// FEATURE MODULES
// =============================================================================

// -----------------------------------------------------------------------------
// VOCABULARY MODULE
// -----------------------------------------------------------------------------

// Vocabulary API
import '@modules/vocabulary/api/terms_api';
import '@modules/vocabulary/api/words_api';

// Vocabulary stores
import '@modules/vocabulary/stores/word_store';
import '@modules/vocabulary/stores/word_form_store';
import '@modules/vocabulary/stores/multi_word_form_store';
import '@modules/vocabulary/stores/word_list_filter';
import '@modules/vocabulary/stores/word_list_table';

// Vocabulary components
import '@modules/vocabulary/components/word_popover';
import '@modules/vocabulary/components/word_modal';
import '@modules/vocabulary/components/word_edit_form';
import '@modules/vocabulary/components/multi_word_modal';

// Vocabulary services
import '@modules/vocabulary/services/word_status';
import '@modules/vocabulary/services/dictionary';
import '@modules/vocabulary/services/translation_api';
import '@modules/vocabulary/services/word_popup_interface';
import '@modules/vocabulary/services/term_operations';
import '@modules/vocabulary/services/word_dom_updates';
import '@modules/vocabulary/services/word_status_ajax';

// Vocabulary pages
import '@modules/vocabulary/pages/word_list_app';
import '@modules/vocabulary/pages/bulk_translate';
import '@modules/vocabulary/pages/word_upload';
import '@modules/vocabulary/pages/expression_interactable';
import '@modules/vocabulary/pages/word_result_init';
import '@modules/vocabulary/pages/translation_page';

// -----------------------------------------------------------------------------
// TEXT MODULE
// -----------------------------------------------------------------------------

// Text API
import '@modules/text/api/texts_api';

// Text components
import '@modules/text/components/text_reader';

// Text pages - Reading interface
import '@modules/text/pages/reading/text_renderer';
import '@modules/text/pages/reading/text_events';
import '@modules/text/pages/reading/text_display';
import '@modules/text/pages/reading/frame_management';
import '@modules/text/pages/reading/annotation_toggle';
import '@modules/text/pages/reading/set_mode_result';
import '@modules/text/pages/reading/text_reading_init';
import '@modules/text/pages/reading/annotation_interactions';

// Text pages - Text management
import '@modules/text/pages/text_list';
import '@modules/text/pages/texts_grouped_app';
import '@modules/text/pages/archived_texts_grouped_app';
import '@modules/text/pages/text_status_chart';
import '@modules/text/pages/youtube_import';
import '@modules/text/pages/text_check_display';
import '@modules/text/pages/text_print_app';

// -----------------------------------------------------------------------------
// REVIEW MODULE
// -----------------------------------------------------------------------------

// Review API
import '@modules/review/api/review_api';

// Review stores
import '@modules/review/stores/review_store';

// Review components
import '@modules/review/components/review_view';

// Review pages
import '@modules/review/pages/review_mode';
import '@modules/review/pages/review_header';
import '@modules/review/pages/review_table';
import '@modules/review/pages/review_ajax';

// -----------------------------------------------------------------------------
// FEED MODULE
// -----------------------------------------------------------------------------

// Feed API
import '@modules/feed/api/feeds_api';

// Feed stores
import '@modules/feed/stores/feed_wizard_store';
import '@modules/feed/stores/feed_manager_store';

// Feed components
import '@modules/feed/components/feed_form_component';
import '@modules/feed/components/feed_multi_load_component';
import '@modules/feed/components/feed_loader_component';
import '@modules/feed/components/feed_index_component';
import '@modules/feed/components/feed_browse_component';
import '@modules/feed/components/feed_text_edit_component';
import '@modules/feed/components/feed_wizard_step1';
import '@modules/feed/components/feed_wizard_step2';
import '@modules/feed/components/feed_wizard_step3';
import '@modules/feed/components/feed_wizard_step4';

// Feed pages
import '@modules/feed/pages/feed_manager_app';

// Feed utils
import '@modules/feed/utils/xpath_utils';

// -----------------------------------------------------------------------------
// LANGUAGE MODULE
// -----------------------------------------------------------------------------

// Language API
import '@modules/language/api/languages_api';

// Language stores
import '@modules/language/stores/language_store';
import '@modules/language/stores/language_form_store';
import '@modules/language/stores/language_settings';

// Language components
import '@modules/language/components/language_list_component';
import '@modules/language/components/language_wizard_modal';

// Language pages
import '@modules/language/pages/language_wizard';
import '@modules/language/pages/language_form';
import '@modules/language/pages/language_list';

// -----------------------------------------------------------------------------
// ADMIN MODULE
// -----------------------------------------------------------------------------

// Admin API
import '@modules/admin/api/settings_api';

// Admin pages
import '@modules/admin/pages/server_data';
import '@modules/admin/pages/tts_settings';
import '@modules/admin/pages/table_management';
import '@modules/admin/pages/settings_form';
import '@modules/admin/pages/statistics_charts';
import '@modules/admin/pages/backup_manager';

// -----------------------------------------------------------------------------
// TAGS MODULE
// -----------------------------------------------------------------------------

import '@modules/tags/pages/tag_list';

// -----------------------------------------------------------------------------
// AUTH MODULE
// -----------------------------------------------------------------------------

import '@modules/auth/pages/register_form';

// =============================================================================
// CROSS-CUTTING FEATURES (kept at root level)
// =============================================================================

// Media
import './media/media_selection';
import './media/html5_audio_player';
import './media/audio_player_alpine';

// Home
import './home/home_app';

// =============================================================================
// ALPINE.JS INITIALIZATION
// =============================================================================

declare global {
  interface Window {
    Alpine: typeof Alpine;
  }
}

// Initialize Alpine.js globally
window.Alpine = Alpine;

// Register Alpine.js magic method for inline Markdown parsing
// Note: Returns plain text since x-html is not CSP-compatible
// Markdown bold/italic is stripped, only plain text is returned
Alpine.magic('markdown', () => (text: string) => {
  // For CSP compatibility, strip markdown formatting and return plain text
  // This avoids needing innerHTML which is prohibited in CSP build
  if (!text) return '';
  return text
    .replace(/\*\*([^*]+)\*\*/g, '$1') // Bold
    .replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '$1') // Italic
    .replace(/~~([^~]+)~~/g, '$1') // Strikethrough
    .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1'); // Links (keep text only)
});

// Start Alpine.js
Alpine.start();

window.LWT_VITE_LOADED = true;

// Log to console in development
if (import.meta.env.DEV) {
  console.log('LWT Vite bundle loaded (development mode)');
}

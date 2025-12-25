/**
 * Shared Infrastructure - Re-exports for common utilities and components.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

// API
export * from './api/client';

// Stores
export * from './stores/app_data';
export * from './stores/lwt_state';

// Utils
export * from './utils/cookies';
export * from './utils/html_utils';
export * from './utils/ui_utilities';
export * from './utils/ajax_utilities';
export * from './utils/hover_intent';
export * from './utils/user_interactions';
export * from './utils/simple_interactions';
export * from './utils/inline_markdown';
export * from './utils/tts_storage';
export * from './utils/settings_config';

// Components
export * from './components/modal';
export * from './components/navbar';
export * from './components/footer';
export * from './components/sorttable';
export * from './components/inline_edit';
export * from './components/tagify_tags';
export * from './components/native_tooltip';

// Icons
export * from './icons/icons';
// lucide_icons has overlapping createIcon - export unique items only
export {
  initIcons,
  initIconsIn,
  replaceWithLucide
} from './icons/lucide_icons';

// Forms
export * from './forms/bulk_actions';
export * from './forms/unloadformcheck';
export * from './forms/form_validation';
export * from './forms/form_initialization';
export * from './forms/word_form_auto';

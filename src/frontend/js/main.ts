/**
 * Vite entry point for the LWT application.
 *
 * This file serves as the main entry point for the Vite build system.
 * It statically imports shared infrastructure and small modules, then
 * dynamically imports feature modules based on the lwt-modules meta tag
 * emitted by the server. Alpine.js is started after all dynamic imports
 * have resolved.
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
// SHARED INFRASTRUCTURE (always loaded)
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

// PWA support
import '@shared/pwa/register';

// Offline support
import '@shared/offline/offline-button';
import '@shared/offline/offline-indicator';

// Shared API client
import '@shared/api/client';

// Shared components
import '@shared/components/modal';
import '@shared/components/sorttable';
import '@shared/components/navbar';
import '@shared/components/theme_toggle';
import '@shared/components/footer';
import '@shared/components/searchable_select';

// Shared icons
import '@shared/icons/lucide_icons';

// Shared forms
import '@shared/forms/bulk_actions';
import '@shared/forms/unloadformcheck';
import '@shared/forms/form_validation';
import '@shared/forms/form_initialization';
import '@shared/forms/word_form_auto';

// =============================================================================
// SMALL MODULES (always loaded — kept in main bundle)
// =============================================================================

// Tags
import '@modules/tags/pages/tag_list';

// Auth
import '@modules/auth';

// Dictionary
import '@modules/dictionary/pages/dictionary_import';

// Media
import './media/media_selection';
import './media/html5_audio_player';
import './media/audio_player_alpine';

// Home
import './home/home_app';

// =============================================================================
// ASYNC CSS LOADING (CSP-compliant)
// =============================================================================

// Convert async CSS links from print to all media
// This enables non-render-blocking CSS loading without inline JS
document.querySelectorAll<HTMLLinkElement>('link[data-async-css]').forEach((link) => {
  link.media = 'all';
});

// =============================================================================
// DYNAMIC MODULE LOADING + ALPINE.JS INITIALIZATION
// =============================================================================

declare global {
  interface Window {
    Alpine: typeof Alpine;
  }
}

/**
 * Map of dynamically-loadable feature modules.
 *
 * Each key corresponds to a module name that the server can request
 * via the <meta name="lwt-modules"> tag.
 */
const moduleMap: Record<string, () => Promise<unknown>> = {
  vocabulary: () => import('@modules/vocabulary'),
  text: () => import('@modules/text'),
  review: () => import('@modules/review'),
  feed: () => import('@modules/feed'),
  language: () => import('@modules/language'),
  admin: () => import('@modules/admin'),
};

// Read which modules the current page needs from the server-emitted meta tag
const meta = document.querySelector<HTMLMetaElement>('meta[name="lwt-modules"]');
const requestedModules = meta?.content?.split(',').map(m => m.trim()).filter(Boolean) ?? [];

// Start loading all requested modules in parallel
const loaders = requestedModules
  .filter(m => m in moduleMap)
  .map(m => moduleMap[m]());

// Wait for all dynamic modules to load, then initialize Alpine
Promise.all(loaders).then(() => {
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
});

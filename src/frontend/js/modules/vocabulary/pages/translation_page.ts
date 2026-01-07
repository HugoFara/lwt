/**
 * Translation Page - Auto-initialization for translation pages.
 *
 * Handles initialization of:
 * - Google Translate term translation page
 * - Glosbe translation page
 *
 * @license unlicense
 * @since 3.0.0
 */

import { speechDispatcher } from '@shared/utils/user_interactions';
import { getGlosbeTranslation } from '../services/translation_api';

// Type for frames collection
interface FramesCollection {
  ro?: Window;
}

/**
 * Configuration for Google Translate term page.
 */
interface GoogleTranslateConfig {
  text: string;
  langId: number | string;
  hasParentFrame: boolean;
}

/**
 * Configuration for Glosbe translation page.
 */
interface GlosbeConfig {
  phrase: string;
  from: string;
  dest: string;
  hasParentFrame: boolean;
  error?: string;
}

/**
 * Check if the page is loaded in a frame or popup context.
 * Used to determine if the delete translation button should be shown.
 */
function hasParentFrameContext(): boolean {
  const frames = (window.parent as Window & { frames: FramesCollection }).frames;
  if (frames?.ro !== undefined) {
    return true;
  }
  if (window.opener !== null && window.opener !== undefined) {
    return true;
  }
  return false;
}

/**
 * Initialize the Google Translate term translation page.
 * Sets up text-to-speech and delete button visibility.
 */
function initGoogleTranslatePage(config: GoogleTranslateConfig): void {
  // Set up text-to-speech click handler
  const textToSpeechEl = document.getElementById('textToSpeech');
  if (textToSpeechEl) {
    textToSpeechEl.addEventListener('click', function () {
      const langId = typeof config.langId === 'string' ? parseInt(config.langId, 10) : config.langId;
      speechDispatcher(config.text, langId);
    });
  }

  // Remove delete button if not in a frame/popup context
  if (!hasParentFrameContext()) {
    const delTranslationEl = document.getElementById('del_translation');
    if (delTranslationEl) {
      delTranslationEl.remove();
    }
  }
}

/**
 * Initialize the Glosbe translation page.
 * Sets up delete button visibility and triggers translation.
 */
function initGlosbePage(config: GlosbeConfig): void {
  // Remove delete button if not in a frame/popup context
  if (!hasParentFrameContext()) {
    const delTranslationEl = document.getElementById('del_translation');
    if (delTranslationEl) {
      delTranslationEl.remove();
    }
  }

  // Handle error state
  if (config.error) {
    if (config.error === 'empty_term') {
      document.body.innerHTML = '<p class="msgblue">Term is not set!</p>';
    } else {
      document.body.innerHTML =
        '<p class="red">There seems to be something wrong with the Glosbe API!</p>' +
        '<p class="red">Please check the dictionaries in the Language Settings!</p>';
    }
    return;
  }

  // Trigger Glosbe translation
  getGlosbeTranslation(config.phrase, config.from, config.dest);
}

/**
 * Auto-initialize translation pages from JSON config elements.
 */
export function autoInitTranslationPages(): void {
  // Google Translate page
  const googleConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-google-translate-config]');
  if (googleConfigEl) {
    try {
      const config = JSON.parse(googleConfigEl.textContent || '{}') as GoogleTranslateConfig;
      initGoogleTranslatePage(config);
    } catch (e) {
      console.error('Failed to parse Google Translate config:', e);
    }
  }

  // Glosbe page
  const glosbeConfigEl = document.querySelector<HTMLScriptElement>('script[data-lwt-glosbe-config]');
  if (glosbeConfigEl) {
    try {
      const config = JSON.parse(glosbeConfigEl.textContent || '{}') as GlosbeConfig;
      initGlosbePage(config);
    } catch (e) {
      console.error('Failed to parse Glosbe config:', e);
    }
  }
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', autoInitTranslationPages);
} else {
  autoInitTranslationPages();
}

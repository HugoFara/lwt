/**
 * Global exports for LWT.
 *
 * This file exposes TypeScript functions to the global window object
 * so they can be called from inline PHP-generated scripts.
 *
 * @since 3.0.0
 */

// Import functions that need to be globally accessible
import { getLangFromDict, createTheDictUrl, createTheDictLink, owin, oewin } from './terms/dictionary';
import { prepareTextInteractions } from './reading/text_events';
import { goToLastPosition, saveReadingPosition, saveAudioPosition } from './core/user_interactions';

// Declare global window interface extensions
declare global {
  interface Window {
    // Dictionary functions
    getLangFromDict: typeof getLangFromDict;
    createTheDictUrl: typeof createTheDictUrl;
    createTheDictLink: typeof createTheDictLink;
    owin: typeof owin;
    oewin: typeof oewin;

    // Text reading functions
    prepareTextInteractions: typeof prepareTextInteractions;
    goToLastPosition: typeof goToLastPosition;
    saveReadingPosition: typeof saveReadingPosition;
    saveAudioPosition: typeof saveAudioPosition;
  }
}

// Expose to window
window.getLangFromDict = getLangFromDict;
window.createTheDictUrl = createTheDictUrl;
window.createTheDictLink = createTheDictLink;
window.owin = owin;
window.oewin = oewin;

window.prepareTextInteractions = prepareTextInteractions;
window.goToLastPosition = goToLastPosition;
window.saveReadingPosition = saveReadingPosition;
window.saveAudioPosition = saveAudioPosition;

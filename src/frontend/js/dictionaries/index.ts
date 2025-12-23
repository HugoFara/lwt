/**
 * Local Dictionary Module
 *
 * Provides local dictionary lookup functionality for the text reading interface.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

// Export API functions
export {
  lookupLocal,
  hasLocalDictionaries,
  shouldUseOnline,
  getLocalDictMode,
  clearModeCache,
  formatResults,
  formatResult,
  DictionaryMode,
  type LocalDictResult,
  type LocalDictLookupResponse,
  type DictionaryModeValue
} from './local_dictionary_api';

// Export panel functions
export {
  showLocalDictPanel,
  hideLocalDictPanel,
  isPanelVisible,
  showInlineResults,
  createPanelElement,
  registerPanelComponent,
  type LocalDictPanelState
} from './local_dictionary_panel';

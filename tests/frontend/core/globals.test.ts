/**
 * Tests for globals.ts - Global window exports
 *
 * This file tests that all functions and objects are correctly
 * exported to the window object for access by inline PHP scripts.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// We need to mock all the dependencies before importing globals
vi.mock('../../../src/frontend/js/terms/dictionary', () => ({
  getLangFromDict: vi.fn(),
  createTheDictUrl: vi.fn(),
  createTheDictLink: vi.fn(),
  owin: vi.fn(),
  oewin: vi.fn()
}));

vi.mock('../../../src/frontend/js/ui/modal', () => ({
  showExportTemplateHelp: vi.fn(),
  openModal: vi.fn(),
  closeModal: vi.fn()
}));

vi.mock('../../../src/frontend/js/reading/text_events', () => ({
  prepareTextInteractions: vi.fn()
}));

vi.mock('../../../src/frontend/js/core/user_interactions', () => ({
  goToLastPosition: vi.fn(),
  saveReadingPosition: vi.fn(),
  saveAudioPosition: vi.fn(),
  quickMenuRedirection: vi.fn(),
  readTextAloud: vi.fn(),
  deepFindValue: vi.fn(),
  readTextWithExternal: vi.fn(),
  readRawTextAloud: vi.fn(),
  speechDispatcher: {}
}));

vi.mock('../../../src/frontend/js/reading/frame_management', () => ({
  showRightFrames: vi.fn(),
  hideRightFrames: vi.fn(),
  cleanupRightFrames: vi.fn()
}));

vi.mock('../../../src/frontend/js/ui/word_popup', () => ({
  overlib: vi.fn(),
  cClick: vi.fn(),
  nd: vi.fn(),
  setCurrentEvent: vi.fn()
}));

vi.mock('../../../src/frontend/js/core/language_settings', () => ({
  setLang: vi.fn(),
  resetAll: vi.fn()
}));

vi.mock('../../../src/frontend/js/core/ui_utilities', () => ({
  markClick: vi.fn(),
  confirmDelete: vi.fn(),
  showAllwordsClick: vi.fn()
}));

vi.mock('../../../src/frontend/js/forms/bulk_actions', () => ({
  selectToggle: vi.fn(),
  multiActionGo: vi.fn(),
  allActionGo: vi.fn()
}));

vi.mock('../../../src/frontend/js/terms/term_operations', () => ({
  updateTermTranslation: vi.fn(),
  addTermTranslation: vi.fn(),
  changeTableTestStatus: vi.fn(),
  do_ajax_edit_impr_text: vi.fn()
}));

vi.mock('../../../src/frontend/js/media/html5_audio_player', () => ({
  lwt_audio_controller: {},
  setupAudioPlayer: vi.fn(),
  getAudioPlayer: vi.fn()
}));

vi.mock('../../../src/frontend/js/core/lwt_state', () => ({
  LWT_DATA: { settings: {} }
}));

vi.mock('../../../src/frontend/js/forms/unloadformcheck', () => ({
  lwtFormCheck: {
    resetDirty: vi.fn(),
    askBeforeExit: vi.fn()
  }
}));

vi.mock('../../../src/frontend/js/forms/form_initialization', () => ({
  changeTextboxesLanguage: vi.fn(),
  clearRightFrameOnUnload: vi.fn(),
  initTextEditForm: vi.fn(),
  initWordEditForm: vi.fn()
}));

vi.mock('../../../src/frontend/js/reading/annotation_toggle', () => ({
  doHideTranslations: vi.fn(),
  doShowTranslations: vi.fn(),
  doHideAnnotations: vi.fn(),
  doShowAnnotations: vi.fn(),
  closeWindow: vi.fn()
}));

vi.mock('../../../src/frontend/js/forms/word_form_auto', () => ({
  autoTranslate: vi.fn(),
  autoRomanization: vi.fn(),
  initWordFormAuto: vi.fn()
}));

vi.mock('../../../src/frontend/js/languages/language_wizard', () => ({
  languageWizard: {},
  languageWizardPopup: vi.fn(),
  initLanguageWizard: vi.fn(),
  initLanguageWizardPopup: vi.fn()
}));

vi.mock('../../../src/frontend/js/languages/language_form', () => ({
  languageForm: {
    reloadDictURLs: vi.fn(),
    checkLanguageChanged: vi.fn(),
    multiWordsTranslateChange: vi.fn(),
    checkTranslatorStatus: vi.fn(),
    checkLibreTranslateStatus: vi.fn(),
    changeLanguageTextSize: vi.fn(),
    wordCharChange: vi.fn(),
    addPopUpOption: vi.fn(),
    changePopUpState: vi.fn(),
    checkDictionaryChanged: vi.fn(),
    checkTranslatorType: vi.fn(),
    checkWordChar: vi.fn(),
    checkVoiceAPI: vi.fn()
  },
  checkTranslatorChanged: vi.fn(),
  checkLanguageForm: vi.fn(),
  checkDuplicateLanguage: vi.fn(),
  initLanguageForm: vi.fn()
}));

vi.mock('../../../src/frontend/js/admin/tts_settings', () => ({
  ttsSettings: {},
  initTTSSettings: vi.fn()
}));

vi.mock('../../../src/frontend/js/core/simple_interactions', () => ({
  goBack: vi.fn(),
  navigateTo: vi.fn(),
  cancelAndNavigate: vi.fn(),
  cancelAndGoBack: vi.fn(),
  confirmSubmit: vi.fn()
}));

vi.mock('../../../src/frontend/js/reading/set_mode_result', () => ({
  hideAnnotations: vi.fn(),
  showAnnotations: vi.fn()
}));

vi.mock('../../../src/frontend/js/admin/server_data', () => ({
  fetchApiVersion: vi.fn()
}));

vi.mock('../../../src/frontend/js/reading/text_reading_init', () => ({
  initTTS: vi.fn(),
  toggleReading: vi.fn(),
  saveTextStatus: vi.fn(),
  initTextReading: vi.fn(),
  initTextReadingHeader: vi.fn()
}));

vi.mock('../../../src/frontend/js/terms/word_status', () => ({
  make_tooltip: vi.fn()
}));

vi.mock('../../../src/frontend/js/words/word_dom_updates', () => ({
  updateNewWordInDOM: vi.fn(),
  updateExistingWordInDOM: vi.fn(),
  updateWordStatusInDOM: vi.fn(),
  deleteWordFromDOM: vi.fn(),
  markWordWellKnownInDOM: vi.fn(),
  markWordIgnoredInDOM: vi.fn(),
  updateMultiWordInDOM: vi.fn(),
  deleteMultiWordFromDOM: vi.fn(),
  updateBulkWordInDOM: vi.fn(),
  updateHoverSaveInDOM: vi.fn(),
  updateTestWordInDOM: vi.fn(),
  updateLearnStatus: vi.fn(),
  completeWordOperation: vi.fn()
}));

vi.mock('../../../src/frontend/js/words/bulk_translate', () => ({
  initBulkTranslate: vi.fn(),
  markAll: vi.fn(),
  markNone: vi.fn(),
  changeTermToggles: vi.fn(),
  googleTranslateElementInit: vi.fn()
}));

vi.mock('../../../src/frontend/js/words/word_status_ajax', () => ({
  initWordStatusChange: vi.fn()
}));

vi.mock('../../../src/frontend/js/feeds/jq_feedwizard', () => ({
  extend_adv_xpath: vi.fn(),
  lwt_feed_wizard: {
    deleteSelection: vi.fn(),
    changeXPath: vi.fn(),
    clickAdvGetButton: vi.fn(),
    clickSelectLi: vi.fn(),
    changeMarkAction: vi.fn(),
    clickGetOrFilter: vi.fn(),
    clickNextButton: vi.fn(),
    changeHostStatus: vi.fn()
  }
}));

vi.mock('../../../src/frontend/js/feeds/feed_wizard_step2', () => ({
  lwt_wiz_select_test: {},
  initWizardStep2: vi.fn()
}));

vi.mock('../../../src/frontend/js/words/word_upload', () => ({
  updateImportMode: vi.fn(),
  showImportedTerms: vi.fn()
}));

vi.mock('../../../src/frontend/js/testing/test_header', () => ({
  setUtteranceSetting: vi.fn(),
  resetTestFrames: vi.fn(),
  startWordTest: vi.fn(),
  startTestTable: vi.fn()
}));

vi.mock('../../../src/frontend/js/testing/test_table', () => ({
  initTableTest: vi.fn()
}));

vi.mock('../../../src/frontend/js/testing/test_ajax', () => ({
  getNewWord: vi.fn(),
  prepareTestFrames: vi.fn(),
  updateTestsCount: vi.fn(),
  handleStatusChangeResult: vi.fn(),
  initAjaxTest: vi.fn(),
  queryNextTerm: vi.fn(),
  doTestFinished: vi.fn()
}));

vi.mock('../../../src/frontend/js/testing/test_mode', () => ({
  word_click_event_do_test_test: vi.fn(),
  keydown_event_do_test_test: vi.fn()
}));

vi.mock('../../../src/frontend/js/terms/translation_api', () => ({
  deleteTranslation: vi.fn(),
  addTranslation: vi.fn(),
  getGlosbeTranslation: vi.fn(),
  getTranslationFromGlosbeApi: vi.fn()
}));

describe('globals.ts', () => {
  beforeEach(async () => {
    vi.clearAllMocks();
    // Import globals to trigger the window assignments
    await import('../../../src/frontend/js/globals');
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // Dictionary Functions
  // ===========================================================================

  describe('Dictionary Functions', () => {
    it('exports getLangFromDict to window', () => {
      expect(window.getLangFromDict).toBeDefined();
      expect(typeof window.getLangFromDict).toBe('function');
    });

    it('exports createTheDictUrl to window', () => {
      expect(window.createTheDictUrl).toBeDefined();
      expect(typeof window.createTheDictUrl).toBe('function');
    });

    it('exports createTheDictLink to window', () => {
      expect(window.createTheDictLink).toBeDefined();
      expect(typeof window.createTheDictLink).toBe('function');
    });

    it('exports owin to window', () => {
      expect(window.owin).toBeDefined();
      expect(typeof window.owin).toBe('function');
    });

    it('exports oewin to window', () => {
      expect(window.oewin).toBeDefined();
      expect(typeof window.oewin).toBe('function');
    });
  });

  // ===========================================================================
  // Text Reading Functions
  // ===========================================================================

  describe('Text Reading Functions', () => {
    it('exports prepareTextInteractions to window', () => {
      expect(window.prepareTextInteractions).toBeDefined();
    });

    it('exports goToLastPosition to window', () => {
      expect(window.goToLastPosition).toBeDefined();
    });

    it('exports saveReadingPosition to window', () => {
      expect(window.saveReadingPosition).toBeDefined();
    });

    it('exports saveAudioPosition to window', () => {
      expect(window.saveAudioPosition).toBeDefined();
    });
  });

  // ===========================================================================
  // Frame Management Functions
  // ===========================================================================

  describe('Frame Management Functions', () => {
    it('exports showRightFrames to window', () => {
      expect(window.showRightFrames).toBeDefined();
    });

    it('exports hideRightFrames to window', () => {
      expect(window.hideRightFrames).toBeDefined();
    });

    it('exports cleanupRightFrames to window', () => {
      expect(window.cleanupRightFrames).toBeDefined();
    });
  });

  // ===========================================================================
  // Popup Functions
  // ===========================================================================

  describe('Popup Functions', () => {
    it('exports overlib to window', () => {
      expect(window.overlib).toBeDefined();
    });

    it('exports cClick to window', () => {
      expect(window.cClick).toBeDefined();
    });

    it('exports nd to window', () => {
      expect(window.nd).toBeDefined();
    });

    it('exports setCurrentEvent to window', () => {
      expect(window.setCurrentEvent).toBeDefined();
    });
  });

  // ===========================================================================
  // Language Settings Functions
  // ===========================================================================

  describe('Language Settings Functions', () => {
    it('exports setLang to window', () => {
      expect(window.setLang).toBeDefined();
    });

    it('exports resetAll to window', () => {
      expect(window.resetAll).toBeDefined();
    });
  });

  // ===========================================================================
  // UI Utilities
  // ===========================================================================

  describe('UI Utilities', () => {
    it('exports markClick to window', () => {
      expect(window.markClick).toBeDefined();
    });

    it('exports confirmDelete to window', () => {
      expect(window.confirmDelete).toBeDefined();
    });

    it('exports showAllwordsClick to window', () => {
      expect(window.showAllwordsClick).toBeDefined();
    });
  });

  // ===========================================================================
  // Bulk Actions
  // ===========================================================================

  describe('Bulk Actions', () => {
    it('exports selectToggle to window', () => {
      expect(window.selectToggle).toBeDefined();
    });

    it('exports multiActionGo to window', () => {
      expect(window.multiActionGo).toBeDefined();
    });

    it('exports allActionGo to window', () => {
      expect(window.allActionGo).toBeDefined();
    });
  });

  // ===========================================================================
  // Term Operations
  // ===========================================================================

  describe('Term Operations', () => {
    it('exports updateTermTranslation to window', () => {
      expect(window.updateTermTranslation).toBeDefined();
    });

    it('exports addTermTranslation to window', () => {
      expect(window.addTermTranslation).toBeDefined();
    });

    it('exports changeTableTestStatus to window', () => {
      expect(window.changeTableTestStatus).toBeDefined();
    });

    it('exports do_ajax_edit_impr_text to window', () => {
      expect(window.do_ajax_edit_impr_text).toBeDefined();
    });
  });

  // ===========================================================================
  // Audio Player
  // ===========================================================================

  describe('Audio Player', () => {
    it('exports lwt_audio_controller to window', () => {
      expect(window.lwt_audio_controller).toBeDefined();
    });

    it('exports setupAudioPlayer to window', () => {
      expect(window.setupAudioPlayer).toBeDefined();
    });

    it('exports getAudioPlayer to window', () => {
      expect(window.getAudioPlayer).toBeDefined();
    });
  });

  // ===========================================================================
  // Modal Functions
  // ===========================================================================

  describe('Modal Functions', () => {
    it('exports showExportTemplateHelp to window', () => {
      expect(window.showExportTemplateHelp).toBeDefined();
    });

    it('exports openModal to window', () => {
      expect(window.openModal).toBeDefined();
    });

    it('exports closeModal to window', () => {
      expect(window.closeModal).toBeDefined();
    });
  });

  // ===========================================================================
  // Global State
  // ===========================================================================

  describe('Global State', () => {
    it('exports LWT_DATA to window', () => {
      expect(window.LWT_DATA).toBeDefined();
    });
  });

  // ===========================================================================
  // Form Utilities
  // ===========================================================================

  describe('Form Utilities', () => {
    it('exports lwtFormCheck to window', () => {
      expect(window.lwtFormCheck).toBeDefined();
    });

    it('exports changeTextboxesLanguage to window', () => {
      expect(window.changeTextboxesLanguage).toBeDefined();
    });

    it('exports clearRightFrameOnUnload to window', () => {
      expect(window.clearRightFrameOnUnload).toBeDefined();
    });

    it('exports initTextEditForm to window', () => {
      expect(window.initTextEditForm).toBeDefined();
    });

    it('exports initWordEditForm to window', () => {
      expect(window.initWordEditForm).toBeDefined();
    });
  });

  // ===========================================================================
  // Annotation Toggle Functions
  // ===========================================================================

  describe('Annotation Toggle Functions', () => {
    it('exports do_hide_t (legacy) to window', () => {
      expect(window.do_hide_t).toBeDefined();
    });

    it('exports do_show_t (legacy) to window', () => {
      expect(window.do_show_t).toBeDefined();
    });

    it('exports do_hide_a (legacy) to window', () => {
      expect(window.do_hide_a).toBeDefined();
    });

    it('exports do_show_a (legacy) to window', () => {
      expect(window.do_show_a).toBeDefined();
    });

    it('exports doHideTranslations to window', () => {
      expect(window.doHideTranslations).toBeDefined();
    });

    it('exports doShowTranslations to window', () => {
      expect(window.doShowTranslations).toBeDefined();
    });

    it('exports doHideAnnotations to window', () => {
      expect(window.doHideAnnotations).toBeDefined();
    });

    it('exports doShowAnnotations to window', () => {
      expect(window.doShowAnnotations).toBeDefined();
    });

    it('exports closeDisplayWindow to window', () => {
      expect(window.closeDisplayWindow).toBeDefined();
    });
  });

  // ===========================================================================
  // Word Form Auto Functions
  // ===========================================================================

  describe('Word Form Auto Functions', () => {
    it('exports autoTranslate to window', () => {
      expect(window.autoTranslate).toBeDefined();
    });

    it('exports autoRomanization to window', () => {
      expect(window.autoRomanization).toBeDefined();
    });

    it('exports initWordFormAuto to window', () => {
      expect(window.initWordFormAuto).toBeDefined();
    });
  });

  // ===========================================================================
  // Language Wizard Functions
  // ===========================================================================

  describe('Language Wizard Functions', () => {
    it('exports language_wizard (legacy) to window', () => {
      expect(window.language_wizard).toBeDefined();
    });

    it('exports languageWizard to window', () => {
      expect(window.languageWizard).toBeDefined();
    });

    it('exports languageWizardPopup to window', () => {
      expect(window.languageWizardPopup).toBeDefined();
    });

    it('exports initLanguageWizard to window', () => {
      expect(window.initLanguageWizard).toBeDefined();
    });

    it('exports initLanguageWizardPopup to window', () => {
      expect(window.initLanguageWizardPopup).toBeDefined();
    });
  });

  // ===========================================================================
  // Language Form Functions
  // ===========================================================================

  describe('Language Form Functions', () => {
    it('exports edit_languages_js (legacy) to window', () => {
      expect(window.edit_languages_js).toBeDefined();
    });

    it('exports languageForm to window', () => {
      expect(window.languageForm).toBeDefined();
    });

    it('exports checkTranslatorChanged to window', () => {
      expect(window.checkTranslatorChanged).toBeDefined();
    });

    it('exports checkLanguageForm to window', () => {
      expect(window.checkLanguageForm).toBeDefined();
    });

    it('exports check_dupl_lang (legacy) to window', () => {
      expect(window.check_dupl_lang).toBeDefined();
    });

    it('exports checkDuplicateLanguage to window', () => {
      expect(window.checkDuplicateLanguage).toBeDefined();
    });

    it('exports initLanguageForm to window', () => {
      expect(window.initLanguageForm).toBeDefined();
    });

    it('exports reloadDictURLs wrapper to window', () => {
      expect(window.reloadDictURLs).toBeDefined();
      expect(typeof window.reloadDictURLs).toBe('function');
    });

    it('exports checkLanguageChanged wrapper to window', () => {
      expect(window.checkLanguageChanged).toBeDefined();
      expect(typeof window.checkLanguageChanged).toBe('function');
    });

    it('exports multiWordsTranslateChange wrapper to window', () => {
      expect(window.multiWordsTranslateChange).toBeDefined();
    });

    it('exports checkTranslatorStatus wrapper to window', () => {
      expect(window.checkTranslatorStatus).toBeDefined();
    });

    it('exports changeLanguageTextSize wrapper to window', () => {
      expect(window.changeLanguageTextSize).toBeDefined();
    });

    it('exports wordCharChange wrapper to window', () => {
      expect(window.wordCharChange).toBeDefined();
    });

    it('exports addPopUpOption wrapper to window', () => {
      expect(window.addPopUpOption).toBeDefined();
    });

    it('exports changePopUpState wrapper to window', () => {
      expect(window.changePopUpState).toBeDefined();
    });

    it('exports checkDictionaryChanged wrapper to window', () => {
      expect(window.checkDictionaryChanged).toBeDefined();
    });

    it('exports checkTranslatorType wrapper to window', () => {
      expect(window.checkTranslatorType).toBeDefined();
    });

    it('exports checkWordChar wrapper to window', () => {
      expect(window.checkWordChar).toBeDefined();
    });

    it('exports checkVoiceAPI wrapper to window', () => {
      expect(window.checkVoiceAPI).toBeDefined();
    });
  });

  // ===========================================================================
  // TTS Settings Functions
  // ===========================================================================

  describe('TTS Settings Functions', () => {
    it('exports tts_settings (legacy) to window', () => {
      expect(window.tts_settings).toBeDefined();
    });

    it('exports ttsSettings to window', () => {
      expect(window.ttsSettings).toBeDefined();
    });

    it('exports initTTSSettings to window', () => {
      expect(window.initTTSSettings).toBeDefined();
    });
  });

  // ===========================================================================
  // Text-to-Speech Functions
  // ===========================================================================

  describe('Text-to-Speech Functions', () => {
    it('exports readTextAloud to window', () => {
      expect(window.readTextAloud).toBeDefined();
    });

    it('exports readRawTextAloud to window', () => {
      expect(window.readRawTextAloud).toBeDefined();
    });
  });

  // ===========================================================================
  // Simple Interactions Functions
  // ===========================================================================

  describe('Simple Interactions Functions', () => {
    it('exports goBack to window', () => {
      expect(window.goBack).toBeDefined();
    });

    it('exports navigateTo to window', () => {
      expect(window.navigateTo).toBeDefined();
    });

    it('exports cancelAndNavigate to window', () => {
      expect(window.cancelAndNavigate).toBeDefined();
    });

    it('exports cancelAndGoBack to window', () => {
      expect(window.cancelAndGoBack).toBeDefined();
    });

    it('exports confirmSubmit to window', () => {
      expect(window.confirmSubmit).toBeDefined();
    });
  });

  // ===========================================================================
  // Set Mode Result Functions
  // ===========================================================================

  describe('Set Mode Result Functions', () => {
    it('exports hideAnnotations to window', () => {
      expect(window.hideAnnotations).toBeDefined();
    });

    it('exports showAnnotations to window', () => {
      expect(window.showAnnotations).toBeDefined();
    });
  });

  // ===========================================================================
  // Admin Functions
  // ===========================================================================

  describe('Admin Functions', () => {
    it('exports fetchApiVersion to window', () => {
      expect(window.fetchApiVersion).toBeDefined();
    });
  });

  // ===========================================================================
  // Text Reading Initialization Functions
  // ===========================================================================

  describe('Text Reading Initialization Functions', () => {
    it('exports initTTS to window', () => {
      expect(window.initTTS).toBeDefined();
    });

    it('exports toggleReading to window', () => {
      expect(window.toggleReading).toBeDefined();
    });

    it('exports toggle_reading (legacy) to window', () => {
      expect(window.toggle_reading).toBeDefined();
    });

    it('exports saveTextStatus to window', () => {
      expect(window.saveTextStatus).toBeDefined();
    });

    it('exports initTextReading to window', () => {
      expect(window.initTextReading).toBeDefined();
    });

    it('exports initTextReadingHeader to window', () => {
      expect(window.initTextReadingHeader).toBeDefined();
    });
  });

  // ===========================================================================
  // Word DOM Update Functions
  // ===========================================================================

  describe('Word DOM Update Functions', () => {
    it('exports make_tooltip to window', () => {
      expect(window.make_tooltip).toBeDefined();
    });

    it('exports updateNewWordInDOM to window', () => {
      expect(window.updateNewWordInDOM).toBeDefined();
    });

    it('exports updateExistingWordInDOM to window', () => {
      expect(window.updateExistingWordInDOM).toBeDefined();
    });

    it('exports updateWordStatusInDOM to window', () => {
      expect(window.updateWordStatusInDOM).toBeDefined();
    });

    it('exports deleteWordFromDOM to window', () => {
      expect(window.deleteWordFromDOM).toBeDefined();
    });

    it('exports markWordWellKnownInDOM to window', () => {
      expect(window.markWordWellKnownInDOM).toBeDefined();
    });

    it('exports markWordIgnoredInDOM to window', () => {
      expect(window.markWordIgnoredInDOM).toBeDefined();
    });

    it('exports updateMultiWordInDOM to window', () => {
      expect(window.updateMultiWordInDOM).toBeDefined();
    });

    it('exports deleteMultiWordFromDOM to window', () => {
      expect(window.deleteMultiWordFromDOM).toBeDefined();
    });

    it('exports updateBulkWordInDOM to window', () => {
      expect(window.updateBulkWordInDOM).toBeDefined();
    });

    it('exports updateHoverSaveInDOM to window', () => {
      expect(window.updateHoverSaveInDOM).toBeDefined();
    });

    it('exports updateTestWordInDOM to window', () => {
      expect(window.updateTestWordInDOM).toBeDefined();
    });

    it('exports updateLearnStatus to window', () => {
      expect(window.updateLearnStatus).toBeDefined();
    });

    it('exports completeWordOperation to window', () => {
      expect(window.completeWordOperation).toBeDefined();
    });
  });

  // ===========================================================================
  // Bulk Translate Functions
  // ===========================================================================

  describe('Bulk Translate Functions', () => {
    it('exports initBulkTranslate to window', () => {
      expect(window.initBulkTranslate).toBeDefined();
    });

    it('exports markAll to window', () => {
      expect(window.markAll).toBeDefined();
    });

    it('exports markNone to window', () => {
      expect(window.markNone).toBeDefined();
    });

    it('exports changeTermToggles to window', () => {
      expect(window.changeTermToggles).toBeDefined();
    });

    it('exports googleTranslateElementInit to window', () => {
      expect(window.googleTranslateElementInit).toBeDefined();
    });
  });

  // ===========================================================================
  // Word Status AJAX Functions
  // ===========================================================================

  describe('Word Status AJAX Functions', () => {
    it('exports initWordStatusChange to window', () => {
      expect(window.initWordStatusChange).toBeDefined();
    });
  });

  // ===========================================================================
  // Navigation Functions
  // ===========================================================================

  describe('Navigation Functions', () => {
    it('exports quickMenuRedirection to window', () => {
      expect(window.quickMenuRedirection).toBeDefined();
    });
  });
});

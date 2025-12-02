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
import { showExportTemplateHelp, openModal, closeModal } from './ui/modal';
import {
  prepareTextInteractions,
  setUseApiMode,
  isApiModeEnabled,
  changeWordStatus,
  deleteWord,
  markWellKnown,
  markIgnored,
  incrementWordStatus,
  getContextFromElement,
  buildContext
} from './reading/text_events';
import {
  showResultPanel,
  hideResultPanel,
  showErrorInPanel,
  showSuccessInPanel,
  showWordDetails,
  showLoadingInPanel
} from './ui/result_panel';
import {
  createStatusChangeButton,
  createStatusButtonsAll,
  createDeleteButton,
  createWellKnownButton,
  createIgnoreButton,
  createTestStatusButtons,
  buildKnownWordPopupContent,
  buildUnknownWordPopupContent
} from './terms/overlib_interface';
import { goToLastPosition, saveReadingPosition, saveAudioPosition, quickMenuRedirection } from './core/user_interactions';
import { showRightFramesPanel, hideRightFrames, loadModalFrame, loadDictionaryFrame } from './reading/frame_management';
import { overlib, cClick, nd, setCurrentEvent } from './ui/word_popup';
import { setLang, resetAll } from './core/language_settings';
import { markClick, confirmDelete, showAllwordsClick } from './core/ui_utilities';
import { selectToggle, multiActionGo, allActionGo } from './forms/bulk_actions';
import { updateTermTranslation, addTermTranslation, changeTableTestStatus, do_ajax_edit_impr_text } from './terms/term_operations';
import { lwt_audio_controller, setupAudioPlayer, getAudioPlayer } from './media/html5_audio_player';
import { LWT_DATA, LwtDataInterface } from './core/lwt_state';
import { lwtFormCheck } from './forms/unloadformcheck';
import {
  changeTextboxesLanguage,
  clearRightFrameOnUnload,
  initTextEditForm,
  initWordEditForm
} from './forms/form_initialization';
import {
  doHideTranslations,
  doShowTranslations,
  doHideAnnotations,
  doShowAnnotations,
  closeWindow as closeDisplayWindow
} from './reading/annotation_toggle';
import {
  autoTranslate,
  autoRomanization,
  initWordFormAuto
} from './forms/word_form_auto';
import {
  languageWizard,
  languageWizardPopup,
  initLanguageWizard,
  initLanguageWizardPopup
} from './languages/language_wizard';
import {
  languageForm,
  checkTranslatorChanged,
  checkLanguageForm,
  checkDuplicateLanguage,
  initLanguageForm
} from './languages/language_form';
import {
  ttsSettings,
  initTTSSettings
} from './admin/tts_settings';
import { readTextAloud } from './core/user_interactions';
import {
  goBack,
  navigateTo,
  cancelAndNavigate,
  cancelAndGoBack,
  confirmSubmit
} from './core/simple_interactions';
import {
  hideAnnotations as setModeHideAnnotations,
  showAnnotations as setModeShowAnnotations
} from './reading/set_mode_result';
import { fetchApiVersion } from './admin/server_data';
import {
  initTTS,
  toggleReading,
  saveTextStatus,
  initTextReading,
  initTextReadingHeader
} from './reading/text_reading_init';
import { readRawTextAloud } from './core/user_interactions';
import { make_tooltip } from './terms/word_status';
import { cleanupRightFrames } from './reading/frame_management';
import {
  updateNewWordInDOM,
  updateExistingWordInDOM,
  updateWordStatusInDOM,
  deleteWordFromDOM,
  markWordWellKnownInDOM,
  markWordIgnoredInDOM,
  updateMultiWordInDOM,
  deleteMultiWordFromDOM,
  updateBulkWordInDOM,
  updateHoverSaveInDOM,
  updateTestWordInDOM,
  updateLearnStatus,
  completeWordOperation,
  type WordUpdateParams,
  type BulkWordUpdateParams
} from './words/word_dom_updates';
import {
  initBulkTranslate,
  markAll as bulkMarkAll,
  markNone as bulkMarkNone,
  changeTermToggles,
  googleTranslateElementInit
} from './words/bulk_translate';
import {
  initWordStatusChange,
  type WordStatusUpdateData
} from './words/word_status_ajax';
import {
  updateImportMode,
  showImportedTerms
} from './words/word_upload';
import {
  setUtteranceSetting,
  resetTestFrames,
  startWordTest,
  startTestTable
} from './testing/test_header';
import { initTableTest } from './testing/test_table';
import {
  getNewWord,
  prepareTestFrames,
  updateTestsCount,
  handleStatusChangeResult,
  initAjaxTest,
  queryNextTerm,
  doTestFinished
} from './testing/test_ajax';
import {
  word_click_event_do_test_test,
  keydown_event_do_test_test
} from './testing/test_mode';
import {
  deleteTranslation,
  addTranslation,
  getGlosbeTranslation,
  getTranslationFromGlosbeApi
} from './terms/translation_api';
import { speechDispatcher } from './core/user_interactions';
import {
  lwt_wiz_select_test,
  initWizardStep2
} from './feeds/feed_wizard_step2';

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

    // API-based word actions (Phase 4)
    setUseApiMode: typeof setUseApiMode;
    isApiModeEnabled: typeof isApiModeEnabled;
    changeWordStatus: typeof changeWordStatus;
    deleteWord: typeof deleteWord;
    markWellKnown: typeof markWellKnown;
    markIgnored: typeof markIgnored;
    incrementWordStatus: typeof incrementWordStatus;
    getContextFromElement: typeof getContextFromElement;
    buildContext: typeof buildContext;

    // Result panel
    showResultPanel: typeof showResultPanel;
    hideResultPanel: typeof hideResultPanel;
    showErrorInPanel: typeof showErrorInPanel;
    showSuccessInPanel: typeof showSuccessInPanel;
    showWordDetails: typeof showWordDetails;
    showLoadingInPanel: typeof showLoadingInPanel;

    // API-based popup builders
    createStatusChangeButton: typeof createStatusChangeButton;
    createStatusButtonsAll: typeof createStatusButtonsAll;
    createDeleteButton: typeof createDeleteButton;
    createWellKnownButton: typeof createWellKnownButton;
    createIgnoreButton: typeof createIgnoreButton;
    createTestStatusButtons: typeof createTestStatusButtons;
    buildKnownWordPopupContent: typeof buildKnownWordPopupContent;
    buildUnknownWordPopupContent: typeof buildUnknownWordPopupContent;

    // Navigation/menu functions
    quickMenuRedirection: typeof quickMenuRedirection;

    // Frame management
    showRightFramesPanel: typeof showRightFramesPanel;
    hideRightFrames: typeof hideRightFrames;
    loadModalFrame: typeof loadModalFrame;
    loadDictionaryFrame: typeof loadDictionaryFrame;

    // Language settings
    setLang: typeof setLang;
    resetAll: typeof resetAll;

    // UI utilities
    markClick: typeof markClick;
    confirmDelete: typeof confirmDelete;
    showAllwordsClick: typeof showAllwordsClick;

    // Bulk actions
    selectToggle: typeof selectToggle;
    multiActionGo: typeof multiActionGo;
    allActionGo: typeof allActionGo;

    // Term operations
    updateTermTranslation: typeof updateTermTranslation;
    addTermTranslation: typeof addTermTranslation;
    changeTableTestStatus: typeof changeTableTestStatus;
    do_ajax_edit_impr_text: typeof do_ajax_edit_impr_text;

    // Popup functions
    overlib: typeof overlib;
    cClick: typeof cClick;
    nd: typeof nd;
    setCurrentEvent: typeof setCurrentEvent;

    // Audio player
    lwt_audio_controller: typeof lwt_audio_controller;
    setupAudioPlayer: typeof setupAudioPlayer;
    getAudioPlayer: typeof getAudioPlayer;

    // Modal dialogs
    showExportTemplateHelp: typeof showExportTemplateHelp;
    openModal: typeof openModal;
    closeModal: typeof closeModal;

    // Global state object
    LWT_DATA: LwtDataInterface;

    // Form utilities
    lwtFormCheck: typeof lwtFormCheck;
    changeTextboxesLanguage: typeof changeTextboxesLanguage;
    clearRightFrameOnUnload: typeof clearRightFrameOnUnload;
    initTextEditForm: typeof initTextEditForm;
    initWordEditForm: typeof initWordEditForm;

    // Annotation toggles (legacy function names)
    do_hide_t: typeof doHideTranslations;
    do_show_t: typeof doShowTranslations;
    do_hide_a: typeof doHideAnnotations;
    do_show_a: typeof doShowAnnotations;
    doHideTranslations: typeof doHideTranslations;
    doShowTranslations: typeof doShowTranslations;
    doHideAnnotations: typeof doHideAnnotations;
    doShowAnnotations: typeof doShowAnnotations;
    closeDisplayWindow: typeof closeDisplayWindow;

    // Word form auto functions
    autoTranslate: typeof autoTranslate;
    autoRomanization: typeof autoRomanization;
    initWordFormAuto: typeof initWordFormAuto;

    // Language wizard
    language_wizard: typeof languageWizard;
    languageWizard: typeof languageWizard;
    languageWizardPopup: typeof languageWizardPopup;
    initLanguageWizard: typeof initLanguageWizard;
    initLanguageWizardPopup: typeof initLanguageWizardPopup;

    // Language form
    edit_languages_js: typeof languageForm;
    languageForm: typeof languageForm;
    checkTranslatorChanged: typeof checkTranslatorChanged;
    checkLanguageForm: typeof checkLanguageForm;
    check_dupl_lang: typeof checkDuplicateLanguage;
    checkDuplicateLanguage: typeof checkDuplicateLanguage;
    initLanguageForm: typeof initLanguageForm;
    reloadDictURLs: (sourceLg: string, targetLg: string) => void;
    checkLanguageChanged: (value: string) => void;
    multiWordsTranslateChange: (value: string) => void;
    checkTranslatorStatus: (url: string) => void;
    checkLibreTranslateStatus: (url: URL, key: string) => void;
    changeLanguageTextSize: (value: string | number) => void;
    wordCharChange: (value: string) => void;
    addPopUpOption: (url: string, checked: boolean) => string;
    changePopUpState: (elem: HTMLInputElement) => void;
    checkDictionaryChanged: (inputBox: HTMLInputElement) => void;
    checkTranslatorType: (url: string, typeSelect: HTMLSelectElement) => void;
    checkWordChar: (method: string) => void;
    checkVoiceAPI: (apiValue: string) => boolean;

    // TTS Settings
    tts_settings: typeof ttsSettings;
    ttsSettings: typeof ttsSettings;
    initTTSSettings: typeof initTTSSettings;

    // Text-to-speech
    readTextAloud: typeof readTextAloud;

    // Simple interactions (navigation, confirmation)
    goBack: typeof goBack;
    navigateTo: typeof navigateTo;
    cancelAndNavigate: typeof cancelAndNavigate;
    cancelAndGoBack: typeof cancelAndGoBack;
    confirmSubmit: typeof confirmSubmit;

    // Set mode result (annotation toggling)
    hideAnnotations: typeof setModeHideAnnotations;
    showAnnotations: typeof setModeShowAnnotations;

    // Admin utilities
    fetchApiVersion: typeof fetchApiVersion;

    // Text reading initialization
    initTTS: typeof initTTS;
    toggleReading: typeof toggleReading;
    toggle_reading: typeof toggleReading;
    saveTextStatus: typeof saveTextStatus;
    initTextReading: typeof initTextReading;
    initTextReadingHeader: typeof initTextReadingHeader;
    readRawTextAloud: typeof readRawTextAloud;

    // Word DOM updates (for result views)
    make_tooltip: typeof make_tooltip;
    cleanupRightFrames: typeof cleanupRightFrames;
    updateNewWordInDOM: typeof updateNewWordInDOM;
    updateExistingWordInDOM: typeof updateExistingWordInDOM;
    updateWordStatusInDOM: typeof updateWordStatusInDOM;
    deleteWordFromDOM: typeof deleteWordFromDOM;
    markWordWellKnownInDOM: typeof markWordWellKnownInDOM;
    markWordIgnoredInDOM: typeof markWordIgnoredInDOM;
    updateMultiWordInDOM: typeof updateMultiWordInDOM;
    deleteMultiWordFromDOM: typeof deleteMultiWordFromDOM;
    updateBulkWordInDOM: typeof updateBulkWordInDOM;
    updateHoverSaveInDOM: typeof updateHoverSaveInDOM;
    updateTestWordInDOM: typeof updateTestWordInDOM;
    updateLearnStatus: typeof updateLearnStatus;
    completeWordOperation: typeof completeWordOperation;

    // Bulk translate functions
    initBulkTranslate: typeof initBulkTranslate;
    markAll: typeof bulkMarkAll;
    markNone: typeof bulkMarkNone;
    changeTermToggles: typeof changeTermToggles;
    googleTranslateElementInit: typeof googleTranslateElementInit;

    // Word status AJAX
    initWordStatusChange: typeof initWordStatusChange;

    // Word upload
    updateImportMode: typeof updateImportMode;
    showImportedTerms: typeof showImportedTerms;

    // Test header
    setUtteranceSetting: typeof setUtteranceSetting;
    resetTestFrames: typeof resetTestFrames;
    resetFrames: typeof resetTestFrames; // Legacy alias
    startWordTest: typeof startWordTest;
    startTestTable: typeof startTestTable;

    // Test table
    initTableTest: typeof initTableTest;

    // Test AJAX
    get_new_word: typeof getNewWord;
    getNewWord: typeof getNewWord;
    prepare_test_frames: typeof prepareTestFrames;
    prepareTestFrames: typeof prepareTestFrames;
    update_tests_count: typeof updateTestsCount;
    updateTestsCount: typeof updateTestsCount;
    handleStatusChangeResult: typeof handleStatusChangeResult;
    initAjaxTest: typeof initAjaxTest;
    query_next_term: typeof queryNextTerm;
    queryNextTerm: typeof queryNextTerm;
    do_test_finished: typeof doTestFinished;
    doTestFinished: typeof doTestFinished;

    // Test mode
    word_click_event_do_test_test: typeof word_click_event_do_test_test;
    keydown_event_do_test_test: typeof keydown_event_do_test_test;

    // Translation API
    deleteTranslation: typeof deleteTranslation;
    addTranslation: typeof addTranslation;
    getGlosbeTranslation: typeof getGlosbeTranslation;
    getTranslationFromGlosbeApi: typeof getTranslationFromGlosbeApi;
    speechDispatcher: typeof speechDispatcher;

    // Feed wizard step 2
    lwt_wiz_select_test: typeof lwt_wiz_select_test;
    initWizardStep2: typeof initWizardStep2;
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

// API-based word actions (Phase 4)
window.setUseApiMode = setUseApiMode;
window.isApiModeEnabled = isApiModeEnabled;
window.changeWordStatus = changeWordStatus;
window.deleteWord = deleteWord;
window.markWellKnown = markWellKnown;
window.markIgnored = markIgnored;
window.incrementWordStatus = incrementWordStatus;
window.getContextFromElement = getContextFromElement;
window.buildContext = buildContext;

// Result panel
window.showResultPanel = showResultPanel;
window.hideResultPanel = hideResultPanel;
window.showErrorInPanel = showErrorInPanel;
window.showSuccessInPanel = showSuccessInPanel;
window.showWordDetails = showWordDetails;
window.showLoadingInPanel = showLoadingInPanel;

// API-based popup builders
window.createStatusChangeButton = createStatusChangeButton;
window.createStatusButtonsAll = createStatusButtonsAll;
window.createDeleteButton = createDeleteButton;
window.createWellKnownButton = createWellKnownButton;
window.createIgnoreButton = createIgnoreButton;
window.createTestStatusButtons = createTestStatusButtons;
window.buildKnownWordPopupContent = buildKnownWordPopupContent;
window.buildUnknownWordPopupContent = buildUnknownWordPopupContent;

window.quickMenuRedirection = quickMenuRedirection;

window.showRightFramesPanel = showRightFramesPanel;
window.hideRightFrames = hideRightFrames;
window.loadModalFrame = loadModalFrame;
window.loadDictionaryFrame = loadDictionaryFrame;

window.setLang = setLang;
window.resetAll = resetAll;

window.markClick = markClick;
window.confirmDelete = confirmDelete;
window.showAllwordsClick = showAllwordsClick;

window.selectToggle = selectToggle;
window.multiActionGo = multiActionGo;
window.allActionGo = allActionGo;

window.updateTermTranslation = updateTermTranslation;
window.addTermTranslation = addTermTranslation;
window.changeTableTestStatus = changeTableTestStatus;
window.do_ajax_edit_impr_text = do_ajax_edit_impr_text;

window.overlib = overlib;
window.cClick = cClick;
window.nd = nd;
window.setCurrentEvent = setCurrentEvent;

window.lwt_audio_controller = lwt_audio_controller;
window.setupAudioPlayer = setupAudioPlayer;
window.getAudioPlayer = getAudioPlayer;

window.showExportTemplateHelp = showExportTemplateHelp;
window.openModal = openModal;
window.closeModal = closeModal;

// Global state object
window.LWT_DATA = LWT_DATA;

// Form utilities
window.lwtFormCheck = lwtFormCheck;
window.changeTextboxesLanguage = changeTextboxesLanguage;
window.clearRightFrameOnUnload = clearRightFrameOnUnload;
window.initTextEditForm = initTextEditForm;
window.initWordEditForm = initWordEditForm;

// Annotation toggles (legacy function names for backward compatibility)
window.do_hide_t = doHideTranslations;
window.do_show_t = doShowTranslations;
window.do_hide_a = doHideAnnotations;
window.do_show_a = doShowAnnotations;
window.doHideTranslations = doHideTranslations;
window.doShowTranslations = doShowTranslations;
window.doHideAnnotations = doHideAnnotations;
window.doShowAnnotations = doShowAnnotations;
window.closeDisplayWindow = closeDisplayWindow;

// Word form auto functions
window.autoTranslate = autoTranslate;
window.autoRomanization = autoRomanization;
window.initWordFormAuto = initWordFormAuto;

// Language wizard (legacy name and new name)
window.language_wizard = languageWizard;
window.languageWizard = languageWizard;
window.languageWizardPopup = languageWizardPopup;
window.initLanguageWizard = initLanguageWizard;
window.initLanguageWizardPopup = initLanguageWizardPopup;

// Language form (legacy name and new name)
window.edit_languages_js = languageForm;
window.languageForm = languageForm;
window.checkTranslatorChanged = checkTranslatorChanged;
window.checkLanguageForm = checkLanguageForm;
window.check_dupl_lang = checkDuplicateLanguage;
window.checkDuplicateLanguage = checkDuplicateLanguage;
window.initLanguageForm = initLanguageForm;
window.reloadDictURLs = (s, t) => languageForm.reloadDictURLs(s, t);
window.checkLanguageChanged = (v) => languageForm.checkLanguageChanged(v);
window.multiWordsTranslateChange = (v) => languageForm.multiWordsTranslateChange(v);
window.checkTranslatorStatus = (url) => languageForm.checkTranslatorStatus(url);
window.checkLibreTranslateStatus = (url, key) => languageForm.checkLibreTranslateStatus(url, key);
window.changeLanguageTextSize = (v) => languageForm.changeLanguageTextSize(v);
window.wordCharChange = (v) => languageForm.wordCharChange(v);
window.addPopUpOption = (url, checked) => languageForm.addPopUpOption(url, checked);
window.changePopUpState = (elem) => languageForm.changePopUpState(elem);
window.checkDictionaryChanged = (inputBox) => languageForm.checkDictionaryChanged(inputBox);
window.checkTranslatorType = (url, typeSelect) => languageForm.checkTranslatorType(url, typeSelect);
window.checkWordChar = (method) => languageForm.checkWordChar(method);
window.checkVoiceAPI = (apiValue) => languageForm.checkVoiceAPI(apiValue);

// TTS Settings (legacy name and new name)
window.tts_settings = ttsSettings;
window.ttsSettings = ttsSettings;
window.initTTSSettings = initTTSSettings;

// Text-to-speech
window.readTextAloud = readTextAloud;

// Simple interactions (navigation, confirmation)
window.goBack = goBack;
window.navigateTo = navigateTo;
window.cancelAndNavigate = cancelAndNavigate;
window.cancelAndGoBack = cancelAndGoBack;
window.confirmSubmit = confirmSubmit;

// Set mode result (annotation toggling)
window.hideAnnotations = setModeHideAnnotations;
window.showAnnotations = setModeShowAnnotations;

// Admin utilities
window.fetchApiVersion = fetchApiVersion;

// Text reading initialization
window.initTTS = initTTS;
window.toggleReading = toggleReading;
window.toggle_reading = toggleReading;
window.saveTextStatus = saveTextStatus;
window.initTextReading = initTextReading;
window.initTextReadingHeader = initTextReadingHeader;
window.readRawTextAloud = readRawTextAloud;

// Word DOM updates (for result views)
window.make_tooltip = make_tooltip;
window.cleanupRightFrames = cleanupRightFrames;
window.updateNewWordInDOM = updateNewWordInDOM;
window.updateExistingWordInDOM = updateExistingWordInDOM;
window.updateWordStatusInDOM = updateWordStatusInDOM;
window.deleteWordFromDOM = deleteWordFromDOM;
window.markWordWellKnownInDOM = markWordWellKnownInDOM;
window.markWordIgnoredInDOM = markWordIgnoredInDOM;
window.updateMultiWordInDOM = updateMultiWordInDOM;
window.deleteMultiWordFromDOM = deleteMultiWordFromDOM;
window.updateBulkWordInDOM = updateBulkWordInDOM;
window.updateHoverSaveInDOM = updateHoverSaveInDOM;
window.updateTestWordInDOM = updateTestWordInDOM;
window.updateLearnStatus = updateLearnStatus;
window.completeWordOperation = completeWordOperation;

// Bulk translate functions
window.initBulkTranslate = initBulkTranslate;
window.markAll = bulkMarkAll;
window.markNone = bulkMarkNone;
window.changeTermToggles = changeTermToggles;
window.googleTranslateElementInit = googleTranslateElementInit;

// Word status AJAX
window.initWordStatusChange = initWordStatusChange;

// Word upload
window.updateImportMode = updateImportMode;
window.showImportedTerms = showImportedTerms;

// Test header
window.setUtteranceSetting = setUtteranceSetting;
window.resetTestFrames = resetTestFrames;
window.resetFrames = resetTestFrames; // Legacy alias
window.startWordTest = startWordTest;
window.startTestTable = startTestTable;

// Test table
window.initTableTest = initTableTest;

// Test AJAX
window.get_new_word = getNewWord;
window.getNewWord = getNewWord;
window.prepare_test_frames = prepareTestFrames;
window.prepareTestFrames = prepareTestFrames;
window.update_tests_count = updateTestsCount;
window.updateTestsCount = updateTestsCount;
window.handleStatusChangeResult = handleStatusChangeResult;
window.initAjaxTest = initAjaxTest;
window.query_next_term = queryNextTerm;
window.queryNextTerm = queryNextTerm;
window.do_test_finished = doTestFinished;
window.doTestFinished = doTestFinished;

// Test mode
window.word_click_event_do_test_test = word_click_event_do_test_test;
window.keydown_event_do_test_test = keydown_event_do_test_test;

// Translation API
window.deleteTranslation = deleteTranslation;
window.addTranslation = addTranslation;
window.getGlosbeTranslation = getGlosbeTranslation;
window.getTranslationFromGlosbeApi = getTranslationFromGlosbeApi;
window.speechDispatcher = speechDispatcher;

// Feed wizard step 2
window.lwt_wiz_select_test = lwt_wiz_select_test;
window.initWizardStep2 = initWizardStep2;

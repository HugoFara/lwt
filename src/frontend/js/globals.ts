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
import { prepareTextInteractions } from './reading/text_events';
import { goToLastPosition, saveReadingPosition, saveAudioPosition, quickMenuRedirection } from './core/user_interactions';
import { showRightFrames, hideRightFrames } from './reading/frame_management';
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

    // Navigation/menu functions
    quickMenuRedirection: typeof quickMenuRedirection;

    // Frame management
    showRightFrames: typeof showRightFrames;
    hideRightFrames: typeof hideRightFrames;

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

    // Popup functions (overlib replacement)
    overlib: typeof overlib;
    cClick: typeof cClick;
    nd: typeof nd;
    setCurrentEvent: typeof setCurrentEvent;

    // Audio player (HTML5 replacement for jPlayer)
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

window.quickMenuRedirection = quickMenuRedirection;

window.showRightFrames = showRightFrames;
window.hideRightFrames = hideRightFrames;

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

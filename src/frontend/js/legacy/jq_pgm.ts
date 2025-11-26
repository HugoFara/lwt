/**
 * Interaction between LWT and jQuery
 *
 * This file now serves as a re-export hub for backward compatibility.
 * The actual implementations have been split into focused modules:
 *
 * - lwt_state.ts - LWT_DATA and global state
 * - form_validation.ts - Form validation utilities
 * - term_operations.ts - Term/translation handling
 * - text_display.ts - Word counts and barcharts
 * - test_mode.ts - Test event handlers
 * - ui_utilities.ts - DOM manipulation helpers
 * - frame_management.ts - Right frames handling
 * - media_selection.ts - Media file selection
 * - ajax_utilities.ts - Common AJAX operations
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

// Re-export everything from the new modules for backward compatibility

// State management
export {
  LWT_DATA,
  WID,
  TID,
  WBLINK1,
  WBLINK2,
  WBLINK3,
  RTL,
  type LwtDataInterface,
  type LwtLanguage,
  type LwtText,
  type LwtWord,
  type LwtTest,
  type LwtSettings
} from '../core/lwt_state';

// Form validation
export {
  containsCharacterOutsideBasicMultilingualPlane,
  alertFirstCharacterOutsideBasicMultilingualPlane,
  getUTF8Length,
  isInt,
  check,
  textareaKeydown
} from '../forms/form_validation';

// Term operations
export {
  setTransRoman,
  do_ajax_save_impr_text,
  changeImprAnnText,
  changeImprAnnRadio,
  updateTermTranslation,
  addTermTranslation,
  changeTableTestStatus,
  translation_radio,
  edit_term_ann_translations,
  do_ajax_edit_impr_text,
  do_ajax_req_sim_terms,
  do_ajax_show_similar_terms,
  display_example_sentences,
  change_example_sentences_zone,
  do_ajax_show_sentences,
  type TransData
} from '../terms/term_operations';

// Text display
export {
  do_ajax_word_counts,
  set_barchart_item,
  set_word_counts,
  word_count_click,
  lwt
} from '../reading/text_display';

// Test mode
export {
  word_click_event_do_test_test,
  keydown_event_do_test_test
} from '../testing/test_mode';

// UI utilities
export {
  markClick,
  confirmDelete,
  showAllwordsClick,
  noShowAfter3Secs,
  setTheFocus,
  wrapRadioButtons,
  prepareMainAreas
} from '../core/ui_utilities';

// Frame management
export {
  showRightFrames,
  showRightFrames as showRightFramesImpl,
  hideRightFrames,
  cleanupRightFrames,
  cleanupRightFrames as cleanupRightFramesImpl,
  successSound,
  failureSound
} from '../reading/frame_management';

// Media selection
export {
  select_media_path,
  media_select_receive_data,
  do_ajax_update_media_select
} from '../media/media_selection';

// AJAX utilities
export {
  do_ajax_save_setting,
  scrollToAnchor,
  get_position_from_id,
  quick_select_to_input
} from '../core/ajax_utilities';

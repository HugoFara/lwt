/**
 * Check for unsaved changes when unloading window.
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 * @since   2.3.1-fork You should not only include this script to check before unload
 *          but also call ask_before_exiting once.
 * @since   2.10.0-fork This file was refactored in a single object, use it instead
 */

/**
 * Set to 1 if a form was altered (set "dirty"),
 * ask for confirmation before leaving.
 *
 * @deprecated Since 2.10.0, use lwtFormCheck instead
 */
let DIRTY = 0;

/**
 * Keeps track of a modified form.
 */
const lwtFormCheck = {

  dirty: false,

  /**
   * Check the DIRTY status and ask before leaving.
   *
   * @returns {string} Confirmation string
   */
  isDirtyMessage: function () {
    if (lwtFormCheck.dirty) {
      return '** You have unsaved changes! **';
    }
  },

  /**
   * Set the DIRTY variable to 1.
   */
  makeDirty: function () {
    lwtFormCheck.dirty = true;
  },

  /**
   * Set the DIRTY variable to 0.
   */
  resetDirty: function () {
    lwtFormCheck.dirty = false;
  },

  /**
   * Set DIRTY to 1 if tag object changed.
   *
   * @param {*}      _  An event, unused
   * @param {object} ui UI object
   * @returns {true} Always return true
   */
  tagChanged: function (_, ui) {
    if (!ui.duringInitialization) {
      lwtFormCheck.dirty = true;
    }
    return true;
  },

  /**
   * Call this function if you want to ask the user
   * before exiting the form.
   *
   * @returns {undefined}
   */
  askBeforeExit: function () {
    $('#termtags').tagit({
      afterTagAdded: lwtFormCheck.tagChanged,
      afterTagRemoved: lwtFormCheck.tagChanged
    });
    $('#texttags').tagit({
      afterTagAdded: lwtFormCheck.tagChanged,
      afterTagRemoved: lwtFormCheck.tagChanged
    });
    $('input,checkbox,textarea,radio,select')
      .not('#quickmenu').on('change', lwtFormCheck.makeDirty);
    $(':reset,:submit').on('click', lwtFormCheck.resetDirty);
    $(window).on('beforeunload', lwtFormCheck.isDirtyMessage);
  }
};

/**
 * Check the DIRTY status and ask before leaving.
 *
 * @returns {string} Confirmation string
 * @deprecated Since 2.10.0, use return lwtFormCheck.isDirtyMessage instead
 */
function askConfirmIfDirty () {
  return lwtFormCheck.askConfirmIfDirty();
}

/**
 * Set the DIRTY variable to 1.
 * @deprecated Since 2.10.0, use return lwtFormCheck.makeDirty instead
 */
function makeDirty () {
  return lwtFormCheck.makeDirty();
}

/**
 * Set the DIRTY variable to 0.
 * @deprecated Since 2.10.0, use return lwtFormCheck.resetDirty instead
 */
function resetDirty () {
  return lwtFormCheck.resetDirty();
}

/**
 * Set DIRTY to 1 if tag object changed.
 *
 * @param {*}      _  An event, unused
 * @param {object} ui UI object
 * @returns {true} Always return true
 * @deprecated Since 2.10.0, use return lwtFormCheck.tagChanged instead
 */
function tagChanged (_, ui) {
  return lwtFormCheck.tagChanged(_, ui);
}

/**
 * Call this function if you want to ask the user
 * before exiting the form.
 *
 * @returns {undefined}
 * @deprecated Since 2.10.0, use return lwtFormCheck.askBeforeExit instead
 */
function ask_before_exiting () {
  return lwtFormCheck.askBeforeExit();
}

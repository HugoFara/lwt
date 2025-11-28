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

import { setupTagChangeTracking } from '../ui/tagify_tags';

/**
 * Keeps track of a modified form.
 */
export const lwtFormCheck = {

  dirty: false,

  /**
   * Check the DIRTY status and ask before leaving.
   *
   * @returns Confirmation string
   */
  isDirtyMessage: function (): string | undefined {
    if (lwtFormCheck.dirty) {
      return '** You have unsaved changes! **';
    }
    return undefined;
  },

  /**
   * Set the DIRTY variable to 1.
   */
  makeDirty: function (): void {
    lwtFormCheck.dirty = true;
  },

  /**
   * Set the DIRTY variable to 0.
   */
  resetDirty: function (): void {
    lwtFormCheck.dirty = false;
  },

  /**
   * Called when a tag is changed (added or removed).
   *
   * @param duringInit - Whether this change happened during initialization
   */
  tagChanged: function (duringInit: boolean): void {
    if (!duringInit) {
      lwtFormCheck.dirty = true;
    }
  },

  /**
   * Call this function if you want to ask the user
   * before exiting the form.
   */
  askBeforeExit: function (): void {
    // Set up tag change tracking with Tagify
    setupTagChangeTracking(lwtFormCheck.tagChanged);

    $('input,checkbox,textarea,radio,select')
      .not('#quickmenu').on('change', lwtFormCheck.makeDirty);
    $(':reset,:submit').on('click', lwtFormCheck.resetDirty);
    $(window).on('beforeunload', lwtFormCheck.isDirtyMessage);
  }
};

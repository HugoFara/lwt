/**
 * Frame Management - Right frames show/hide/cleanup operations
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import $ from 'jquery';
import { cClick } from '../ui/word_popup';

/**
 * Show the right frames if found, and can load an URL in those frames
 *
 * @param roUrl Upper-right frame URL to load
 * @param ruUrl Lower-right frame URL to load
 * @returns true if frames were found, false otherwise
 */
export function showRightFrames(roUrl?: string, ruUrl?: string): boolean {
  if (roUrl !== undefined) {
    top!.frames['ro' as unknown as number].location.href = roUrl;
  }
  if (ruUrl !== undefined) {
    top!.frames['ru' as unknown as number].location.href = ruUrl;
  }
  if ($('#frames-r').length) {
    $('#frames-r').animate({ right: '5px' });
    return true;
  }
  return false;
}

/**
 * Hide the right frames if found.
 *
 * @returns true if frames were found, false otherwise
 */
export function hideRightFrames(): boolean {
  if ($('#frames-r').length) {
    $('#frames-r').animate({ right: '-100%' });
    return true;
  }
  return false;
}

/**
 * Hide the right frame and any popups.
 *
 * Called from several places: insert_word_ignore.php,
 * set_word_status.php, delete_word.php, etc.
 */
export function cleanupRightFrames(): void {
  const mytimeout = function () {
    const rf = window.parent.document.getElementById('frames-r');
    rf?.click();
  };
  window.parent.setTimeout(mytimeout, 800);

  window.parent.document.getElementById('frame-l')?.focus();
  // Use imported cClick directly since it's available in the module
  window.parent.setTimeout(cClick, 100);
}

/**
 * Play the success sound.
 *
 * @returns Promise on the status of sound
 */
export function successSound(): Promise<void> {
  (document.getElementById('success_sound') as HTMLAudioElement)?.pause();
  (document.getElementById('failure_sound') as HTMLAudioElement)?.pause();
  return (document.getElementById('success_sound') as HTMLAudioElement)?.play();
}

/**
 * Play the failure sound.
 *
 * @returns Promise on the status of sound
 */
export function failureSound(): Promise<void> {
  (document.getElementById('success_sound') as HTMLAudioElement)?.pause();
  (document.getElementById('failure_sound') as HTMLAudioElement)?.pause();
  return (document.getElementById('failure_sound') as HTMLAudioElement)?.play();
}


/**
 * Frame Management - Right frames show/hide/cleanup operations
 *
 * Note: The iframe-based reading interface has been replaced with
 * Bulma + Alpine.js using API-based word operations via word_store
 * and word_modal components.
 *
 * The frame functions (loadModalFrame, loadDictionaryFrame, etc.) are
 * kept for backward compatibility but return false when frames are not
 * present (which is the default in modern templates).
 *
 * Sound functions (successSound, failureSound) are still used.
 * Consider using audio_feedback.ts for new code.
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import { closeParentPopup } from '@modules/vocabulary/components/word_popup';

/**
 * Animate an element's CSS property using requestAnimationFrame.
 *
 * @param el Element to animate
 * @param property CSS property to animate
 * @param targetValue Target value (e.g., '5px')
 * @param duration Animation duration in ms
 */
function animateStyle(
  el: HTMLElement,
  property: 'right' | 'left' | 'top' | 'bottom',
  targetValue: string,
  duration: number = 300
): void {
  const startValue = parseFloat(getComputedStyle(el)[property]) || 0;
  const endValue = parseFloat(targetValue);
  const startTime = performance.now();

  function step(currentTime: number): void {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    // Ease out cubic
    const easeProgress = 1 - Math.pow(1 - progress, 3);
    const currentValue = startValue + (endValue - startValue) * easeProgress;
    el.style[property] = currentValue + 'px';

    if (progress < 1) {
      requestAnimationFrame(step);
    }
  }

  requestAnimationFrame(step);
}

/**
 * Show the right frames panel if found.
 *
 * This function only reveals the panel (animates it into view),
 * without loading any content into the frames.
 *
 * @returns true if frames panel was found and shown, false otherwise
 */
export function showRightFramesPanel(): boolean {
  const framesR = document.getElementById('frames-r');
  if (framesR) {
    animateStyle(framesR, 'right', '5px');
    return true;
  }
  return false;
}

/**
 * Load content in the upper modal frame (ro).
 *
 * The upper frame is used for LWT internal pages like term editing forms,
 * status changes, and word operations. These pages are always from LWT
 * and will work in an iframe.
 *
 * @param url URL to load in the modal frame
 * @returns true if the frame was found and URL loaded, false otherwise
 */
export function loadModalFrame(url: string): boolean {
  if (!top?.frames) return false;
  const frame = top.frames['ro' as unknown as number];
  if (frame) {
    frame.location.href = url;
    return showRightFramesPanel();
  }
  return false;
}

/**
 * Load content in the lower dictionary frame (ru).
 *
 * The lower frame is used for external dictionary lookups. Note that some
 * dictionary websites may block iframe embedding via X-Frame-Options or
 * Content-Security-Policy headers. In such cases, the iframe will show
 * an error or blank page.
 *
 * @param url URL to load in the dictionary iframe
 * @returns true if the frame was found and URL loaded, false otherwise
 */
export function loadDictionaryFrame(url: string): boolean {
  if (!top?.frames) return false;
  const frame = top.frames['ru' as unknown as number];
  if (frame) {
    frame.location.href = url;
    return showRightFramesPanel();
  }
  return false;
}

/**
 * Hide the right frames if found.
 *
 * @returns true if frames were found, false otherwise
 */
export function hideRightFrames(): boolean {
  const framesR = document.getElementById('frames-r');
  if (framesR) {
    // Get the parent width to calculate -100%
    const parentWidth = framesR.parentElement?.offsetWidth || window.innerWidth;
    animateStyle(framesR, 'right', `-${parentWidth}px`);
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
  // Close popup in parent frame via custom event
  setTimeout(() => closeParentPopup(), 100);
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

/**
 * Initialize event delegation for hide-right-frames action.
 * Handles clicks on elements with data-action="hide-right-frames".
 */
export function initHideRightFramesHandler(): void {
  document.addEventListener('click', function (e) {
    const target = e.target as HTMLElement;
    // Check if click is on an element with the data-action attribute
    if (target.matches('[data-action="hide-right-frames"]')) {
      hideRightFrames();
    }
  });
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initHideRightFramesHandler);
} else {
  initHideRightFramesHandler();
}

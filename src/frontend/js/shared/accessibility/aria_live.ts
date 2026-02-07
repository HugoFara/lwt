/**
 * ARIA live region announcer for screen readers.
 *
 * Creates hidden live regions that allow dynamic content changes
 * to be announced to assistive technology users.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

let politeRegion: HTMLElement | null = null;
let assertiveRegion: HTMLElement | null = null;
let announceTimeout: ReturnType<typeof setTimeout> | null = null;

/**
 * Initialize ARIA live regions by appending them to the document body.
 * Idempotent — safe to call multiple times.
 */
export function initAriaLive(): void {
  if (politeRegion) return;

  politeRegion = document.createElement('div');
  politeRegion.setAttribute('aria-live', 'polite');
  politeRegion.setAttribute('aria-atomic', 'true');
  politeRegion.className = 'sr-only';
  politeRegion.id = 'lwt-aria-live-polite';

  assertiveRegion = document.createElement('div');
  assertiveRegion.setAttribute('aria-live', 'assertive');
  assertiveRegion.setAttribute('aria-atomic', 'true');
  assertiveRegion.className = 'sr-only';
  assertiveRegion.id = 'lwt-aria-live-assertive';

  document.body.appendChild(politeRegion);
  document.body.appendChild(assertiveRegion);
}

/**
 * Announce a message to screen readers via an ARIA live region.
 *
 * Clears the region first and sets content after a short delay
 * to force re-announcement of identical messages.
 *
 * @param message  The text to announce
 * @param priority 'polite' for non-urgent, 'assertive' for immediate
 */
export function announce(
  message: string,
  priority: 'polite' | 'assertive' = 'polite'
): void {
  const region = priority === 'assertive' ? assertiveRegion : politeRegion;
  if (!region) return;

  // Clear first to force re-announcement of identical messages
  region.textContent = '';

  if (announceTimeout) {
    clearTimeout(announceTimeout);
  }

  announceTimeout = setTimeout(() => {
    region.textContent = message;
  }, 50);
}

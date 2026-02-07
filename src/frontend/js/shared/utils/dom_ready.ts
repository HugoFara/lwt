/**
 * Utility for safely running code when the DOM is ready.
 *
 * Unlike raw DOMContentLoaded, this works correctly with dynamically
 * imported modules that may load after the event has already fired.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

/**
 * Execute a callback when the DOM is ready.
 *
 * If the document has already finished loading/parsing, the callback
 * runs immediately (synchronously). Otherwise it waits for
 * DOMContentLoaded.
 *
 * @param callback Function to run once the DOM is ready
 */
export function onDomReady(callback: () => void): void {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback, { once: true });
  } else {
    callback();
  }
}

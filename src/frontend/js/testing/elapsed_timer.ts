/**
 * Elapsed Timer - A simple count-up timer for test sessions.
 *
 * @license Unlicense
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @since   3.0.0
 */

/**
 * Displays and updates an elapsed time counter in an HTML element.
 *
 * @param serverNow - Server time now (Unix timestamp in seconds)
 * @param serverStart - Server time when test started (Unix timestamp in seconds)
 * @param elementId - ID of the HTML element to update
 * @param dontRun - If truthy, display initial time but don't start the timer
 */
export function startElapsedTimer(
  serverNow: number,
  serverStart: number,
  elementId: string,
  dontRun: number
): void {
  // Ensure start time isn't in the future
  const adjustedStart = serverNow < serverStart ? serverNow : serverStart;

  // Calculate the baseline: current client time minus server offset
  const clientNowSecs = Math.floor(Date.now() / 1000);
  const beginSecs = clientNowSecs - serverNow + adjustedStart;

  const element = document.getElementById(elementId);
  if (!element) {
    return;
  }

  const updateDisplay = (): void => {
    const nowSecs = Math.floor(Date.now() / 1000);
    let sec = nowSecs - beginSecs;
    let min = Math.floor(sec / 60);
    sec = sec - min * 60;
    const hr = Math.floor(min / 60);
    min = min - hr * 60;

    let display = '';
    if (hr > 0) {
      display += hr < 10 ? '0' + hr : hr;
      display += ':';
    }
    display += min < 10 ? '0' + min : min;
    display += ':';
    display += sec < 10 ? '0' + sec : sec;

    element.textContent = display;
  };

  // Initial display
  updateDisplay();

  // Start the timer if not disabled
  if (!dontRun) {
    setInterval(updateDisplay, 1000);
  }
}

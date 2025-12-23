/**
 * Reading State Module - Manages mutable reading state.
 *
 * This module replaces the LWT_DATA.text.reading_position and related
 * mutable state with explicit getter/setter functions.
 * For backward compatibility, getter functions fall back to reading from
 * the legacy LWT_DATA global when this module hasn't been explicitly set.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.1.0
 */

// Import LWT_DATA type from globals
import type { LwtData } from '../types/globals.d';

/**
 * Current reading position in the text (word index).
 * -1 means no position is set.
 */
let readingPosition = -1;
let isInitialized = false;

/**
 * Get the current reading position.
 * Falls back to LWT_DATA.text.reading_position if not set.
 */
export function getReadingPosition(): number {
  if (!isInitialized) {
    const lwtData = typeof window !== 'undefined' ? (window as { LWT_DATA?: LwtData }).LWT_DATA : undefined;
    if (lwtData?.text?.reading_position !== undefined) {
      return lwtData.text.reading_position;
    }
  }
  return readingPosition;
}

/**
 * Set the current reading position.
 * Also updates LWT_DATA.text.reading_position for backward compatibility.
 *
 * @param position The new reading position (-1 to reset)
 */
export function setReadingPosition(position: number): void {
  readingPosition = position;
  isInitialized = true;

  // Sync to legacy LWT_DATA for backward compatibility
  const lwtData = typeof window !== 'undefined' ? (window as { LWT_DATA?: LwtData }).LWT_DATA : undefined;
  if (lwtData?.text) {
    lwtData.text.reading_position = position;
  }
}

/**
 * Reset the reading position to -1.
 * Also updates LWT_DATA.text.reading_position for backward compatibility.
 */
export function resetReadingPosition(): void {
  readingPosition = -1;
  isInitialized = true;

  // Sync to legacy LWT_DATA for backward compatibility
  const lwtData = typeof window !== 'undefined' ? (window as { LWT_DATA?: LwtData }).LWT_DATA : undefined;
  if (lwtData?.text) {
    lwtData.text.reading_position = -1;
  }
}

/**
 * Check if a reading position is set.
 */
export function hasReadingPosition(): boolean {
  return readingPosition >= 0;
}

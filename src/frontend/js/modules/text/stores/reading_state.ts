/**
 * Reading State Module - Manages mutable reading state.
 *
 * This module provides explicit getter/setter functions for reading position.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.1.0
 */

/**
 * Current reading position in the text (word index).
 * -1 means no position is set.
 */
let readingPosition = -1;

/**
 * Get the current reading position.
 */
export function getReadingPosition(): number {
  return readingPosition;
}

/**
 * Set the current reading position.
 *
 * @param position The new reading position (-1 to reset)
 */
export function setReadingPosition(position: number): void {
  readingPosition = position;
}

/**
 * Reset the reading position to -1.
 */
export function resetReadingPosition(): void {
  readingPosition = -1;
}

/**
 * Check if a reading position is set.
 */
export function hasReadingPosition(): boolean {
  return readingPosition >= 0;
}

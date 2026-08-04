/**
 * Placement for the reading-view term modals.
 *
 * The word popover already anchors itself above or below the clicked word. The
 * Add/Edit modals did not: Bulma centres `.modal-card` in the viewport (and on
 * mobile #253 pinned it to the bottom), so the form regularly landed on top of
 * the very word the user had just clicked — the word whose sentence they need
 * to see while typing a translation.
 *
 * These modals are too tall to anchor tightly to a word the way the popover
 * does, so instead they take the half of the viewport the word is *not* in.
 * That is a best-effort rule, not a guarantee: a word sitting near the middle
 * of a short viewport can still be covered. It removes the common case without
 * introducing the popover's collision maths.
 */

/** Which half of the viewport the modal card should occupy. */
export type ModalPlacement = 'top' | 'bottom';

/**
 * Last word element the reader interacted with.
 *
 * The single-word modal can use the store's `popoverTargetElement`, but the
 * multi-word modal is opened by id and never sees an element. Recording the
 * click here gives both a usable anchor without threading one through the
 * stores.
 */
let lastAnchor: HTMLElement | null = null;

/**
 * Record the word element the user just interacted with.
 *
 * @param element The clicked `.word` / `.mword` element
 */
export function rememberModalAnchor(element: HTMLElement | null): void {
  lastAnchor = element;
}

/**
 * Get the most recently recorded anchor element.
 */
export function getRememberedModalAnchor(): HTMLElement | null {
  return lastAnchor;
}

/**
 * Choose the half of the viewport that keeps the anchor word visible.
 *
 * Falls back to 'bottom' — the pre-existing behaviour — whenever there is no
 * usable anchor, so a missing element never makes placement worse than before.
 *
 * @param anchor The word element the modal was opened for
 *
 * @return 'top' when the word sits in the lower half, otherwise 'bottom'
 */
export function computeModalPlacement(anchor: HTMLElement | null): ModalPlacement {
  if (!anchor || !anchor.isConnected) return 'bottom';

  const rect = anchor.getBoundingClientRect();
  // A hidden or unrendered anchor gives a zero rect, which would always read as
  // "top half" and push the modal around for no reason.
  if (rect.width === 0 && rect.height === 0) return 'bottom';

  const anchorCentre = rect.top + rect.height / 2;
  return anchorCentre > window.innerHeight / 2 ? 'top' : 'bottom';
}

/**
 * Build the class string for a reading modal's outer `.modal` element.
 *
 * @param isOpen    Whether the modal is open
 * @param placement Which half the card should occupy
 */
export function modalPlacementClasses(isOpen: boolean, placement: ModalPlacement): string {
  const classes: string[] = [];
  if (isOpen) classes.push('is-active');
  classes.push(placement === 'top' ? 'is-placed-top' : 'is-placed-bottom');
  return classes.join(' ');
}

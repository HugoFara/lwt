/**
 * Focus trap utility for modal dialogs.
 *
 * Uses the modern `inert` attribute to prevent interaction with
 * background content while a modal is open, and manages focus
 * restoration when the modal closes.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

let previouslyFocused: HTMLElement | null = null;
let inertElements: HTMLElement[] = [];

/**
 * Selector for focusable elements within a container.
 */
const FOCUSABLE_SELECTOR = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])'
].join(', ');

/**
 * Trap focus within the given element.
 *
 * Sets `inert` on all sibling elements of the element's top-level
 * ancestor (to prevent tabbing outside), stores the previously focused
 * element, and moves focus to the first focusable child.
 *
 * @param element The container to trap focus within
 */
export function trapFocus(element: HTMLElement): void {
  // Store current focus for later restoration
  previouslyFocused = document.activeElement as HTMLElement | null;

  // Set inert on sibling elements of the modal's parent context
  // Walk up to find the body-level children that are siblings
  const parent = element.closest('.modal, .lwt-modal-overlay') || element;
  const siblings = Array.from(document.body.children) as HTMLElement[];

  inertElements = [];
  for (const sibling of siblings) {
    if (sibling === parent || sibling.contains(parent) || parent.contains(sibling)) {
      continue;
    }
    if (!sibling.hasAttribute('inert')) {
      sibling.setAttribute('inert', '');
      inertElements.push(sibling);
    }
  }

  // Focus first focusable child
  const focusable = element.querySelector<HTMLElement>(FOCUSABLE_SELECTOR);
  if (focusable) {
    focusable.focus();
  } else {
    // If no focusable child, make the container itself focusable
    element.setAttribute('tabindex', '-1');
    element.focus();
  }
}

/**
 * Release the focus trap.
 *
 * Removes `inert` from all previously marked elements and restores
 * focus to the element that was focused before the trap was activated.
 */
export function releaseFocus(): void {
  // Remove inert from all marked elements
  for (const el of inertElements) {
    el.removeAttribute('inert');
  }
  inertElements = [];

  // Restore focus to previously focused element
  if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
    previouslyFocused.focus();
  }
  previouslyFocused = null;
}

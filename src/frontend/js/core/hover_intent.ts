/**
 * Native HoverIntent implementation - replaces jquery.hoverIntent plugin.
 *
 * This provides delayed hover detection to prevent accidental triggers
 * when the mouse briefly passes over elements.
 *
 * @license Unlicense <http://unlicense.org/>
 */

export interface HoverIntentOptions {
  /** Callback when hover intent is detected */
  over: (this: HTMLElement, event: MouseEvent) => void;
  /** Callback when mouse leaves */
  out: (this: HTMLElement, event: MouseEvent) => void;
  /** Pixel threshold for mouse movement sensitivity (default: 7) */
  sensitivity?: number;
  /** Milliseconds between position checks (default: 100) */
  interval?: number;
  /** CSS selector for delegated event handling */
  selector?: string;
}

interface HoverState {
  x: number;
  y: number;
  pX: number;
  pY: number;
  isHovered: boolean;
  timer: ReturnType<typeof setTimeout> | null;
}

/**
 * Initialize hover intent behavior on a container element.
 * Replaces jQuery hoverIntent plugin with native implementation.
 *
 * @param container The container element to attach events to
 * @param options Configuration options
 * @returns Cleanup function to remove event listeners
 */
export function hoverIntent(
  container: HTMLElement,
  options: HoverIntentOptions
): () => void {
  const sensitivity = options.sensitivity ?? 7;
  const interval = options.interval ?? 100;
  const selector = options.selector;

  // Track state per element using WeakMap
  const stateMap = new WeakMap<HTMLElement, HoverState>();

  function getState(element: HTMLElement): HoverState {
    let state = stateMap.get(element);
    if (!state) {
      state = { x: 0, y: 0, pX: 0, pY: 0, isHovered: false, timer: null };
      stateMap.set(element, state);
    }
    return state;
  }

  function compare(element: HTMLElement, event: MouseEvent): void {
    const state = getState(element);
    if (state.timer) {
      clearTimeout(state.timer);
      state.timer = null;
    }

    // Check if mouse movement was below sensitivity threshold
    if (
      Math.abs(state.pX - state.x) + Math.abs(state.pY - state.y) <
      sensitivity
    ) {
      state.isHovered = true;
      options.over.call(element, event);
    } else {
      // Mouse moved too much, reset and check again
      state.pX = state.x;
      state.pY = state.y;
      state.timer = setTimeout(() => compare(element, event), interval);
    }
  }

  function handleMouseMove(event: MouseEvent): void {
    const target = selector
      ? (event.target as HTMLElement).closest<HTMLElement>(selector)
      : (event.target as HTMLElement);

    if (!target || !container.contains(target)) return;

    const state = getState(target);
    state.x = event.pageX;
    state.y = event.pageY;
  }

  function handleMouseEnter(event: MouseEvent): void {
    const target = selector
      ? (event.target as HTMLElement).closest<HTMLElement>(selector)
      : (event.target as HTMLElement);

    if (!target || !container.contains(target)) return;

    const state = getState(target);

    // Clear any existing timer
    if (state.timer) {
      clearTimeout(state.timer);
    }

    // Initialize position tracking
    state.pX = event.pageX;
    state.pY = event.pageY;

    // Start checking for intent
    state.timer = setTimeout(() => compare(target, event), interval);
  }

  function handleMouseLeave(event: MouseEvent): void {
    const target = selector
      ? (event.target as HTMLElement).closest<HTMLElement>(selector)
      : (event.target as HTMLElement);

    if (!target || !container.contains(target)) return;

    const state = getState(target);

    // Clear pending timer
    if (state.timer) {
      clearTimeout(state.timer);
      state.timer = null;
    }

    // Call out handler if we were in hovered state
    if (state.isHovered) {
      state.isHovered = false;
      options.out.call(target, event);
    }
  }

  // For delegated events, we use mouseover/mouseout instead of mouseenter/mouseleave
  if (selector) {
    container.addEventListener('mouseover', handleMouseEnter);
    container.addEventListener('mouseout', handleMouseLeave);
    container.addEventListener('mousemove', handleMouseMove);
  } else {
    container.addEventListener('mouseenter', handleMouseEnter);
    container.addEventListener('mouseleave', handleMouseLeave);
    container.addEventListener('mousemove', handleMouseMove);
  }

  // Return cleanup function
  return () => {
    if (selector) {
      container.removeEventListener('mouseover', handleMouseEnter);
      container.removeEventListener('mouseout', handleMouseLeave);
      container.removeEventListener('mousemove', handleMouseMove);
    } else {
      container.removeEventListener('mouseenter', handleMouseEnter);
      container.removeEventListener('mouseleave', handleMouseLeave);
      container.removeEventListener('mousemove', handleMouseMove);
    }
  };
}

/**
 * Scroll to a specific position or element.
 * Replaces jQuery scrollTo plugin with native implementation.
 *
 * @param target Element or pixel position to scroll to
 * @param options Scroll behavior options
 */
export function scrollTo(
  target: HTMLElement | number,
  options?: { behavior?: ScrollBehavior }
): void {
  const behavior = options?.behavior ?? 'instant';

  if (typeof target === 'number') {
    window.scrollTo({ top: target, behavior });
  } else {
    // Scroll to element
    target.scrollIntoView({ behavior, block: 'start' });
  }
}

/**
 * Lucide Icons integration for LWT.
 *
 * This module initializes Lucide SVG icons throughout the application,
 * replacing legacy PNG icons with modern, scalable vector icons.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { createIcons, icons } from 'lucide';

/**
 * Initialize all Lucide icons in the document.
 *
 * This finds all elements with `data-lucide` attributes and replaces
 * them with the corresponding SVG icons.
 */
export function initIcons(): void {
  createIcons({ icons });
}

/**
 * Initialize icons within a specific container element.
 *
 * Useful for dynamically loaded content where icons need to be
 * initialized after the content is added to the DOM.
 *
 * @param container - The container element to search for icons
 */
export function initIconsIn(container: Element): void {
  // Find all elements with data-lucide attribute within container
  const iconElements = container.querySelectorAll('[data-lucide]');

  if (iconElements.length > 0) {
    // Re-run createIcons to process new elements
    createIcons({ icons });
  }
}

/**
 * Create a single icon element programmatically.
 *
 * @param name - The Lucide icon name (e.g., 'check', 'x', 'plus')
 * @param options - Optional configuration for the icon
 * @returns The created SVG element, or null if icon not found
 */
export function createIcon(
  name: string,
  options: {
    size?: number;
    class?: string;
    strokeWidth?: number;
    color?: string;
  } = {}
): SVGElement | null {
  const size = options.size ?? 16;
  const strokeWidth = options.strokeWidth ?? 2;
  const className = options.class ?? 'icon';
  const color = options.color ?? 'currentColor';

  // Create a temporary container
  const temp = document.createElement('i');
  temp.setAttribute('data-lucide', name);
  temp.style.width = `${size}px`;
  temp.style.height = `${size}px`;
  temp.className = className;

  // Add to DOM temporarily (required for createIcons to work)
  temp.style.display = 'none';
  document.body.appendChild(temp);

  // Process the icon
  createIcons({
    icons,
    attrs: {
      width: size,
      height: size,
      'stroke-width': strokeWidth,
      stroke: color
    }
  });

  // Get the created SVG
  const svg = temp.querySelector('svg');

  // Clean up
  document.body.removeChild(temp);

  if (svg) {
    svg.classList.add(...className.split(' '));
    return svg;
  }

  return null;
}

/**
 * Replace a legacy PNG icon with a Lucide icon.
 *
 * This is useful for gradual migration - it can replace an existing
 * <img> element with the new Lucide icon.
 *
 * @param imgElement - The img element to replace
 * @param lucideName - The Lucide icon name to use
 */
export function replaceWithLucide(
  imgElement: HTMLImageElement,
  lucideName: string
): void {
  const title = imgElement.getAttribute('title') ?? '';
  const alt = imgElement.getAttribute('alt') ?? title;
  const className = imgElement.className;

  // Create the icon placeholder
  const iconEl = document.createElement('i');
  iconEl.setAttribute('data-lucide', lucideName);
  iconEl.className = `icon ${className}`;
  iconEl.style.width = '16px';
  iconEl.style.height = '16px';

  if (title) {
    iconEl.setAttribute('title', title);
  }
  if (alt) {
    iconEl.setAttribute('aria-label', alt);
  }

  // Copy data attributes
  for (const attr of Array.from(imgElement.attributes)) {
    if (attr.name.startsWith('data-') && attr.name !== 'data-lucide') {
      iconEl.setAttribute(attr.name, attr.value);
    }
  }

  // Replace the element
  imgElement.replaceWith(iconEl);

  // Initialize the new icon
  createIcons({ icons });
}

// Initialize icons when DOM is ready
function init(): void {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initIcons);
  } else {
    initIcons();
  }
}

// Re-initialize after Alpine.js has finished initial render
// Alpine dispatches 'alpine:initialized' after starting
document.addEventListener('alpine:initialized', () => {
  // Use requestAnimationFrame to ensure Alpine has rendered templates
  requestAnimationFrame(() => {
    initIcons();
  });
});

// Also re-initialize after AJAX content loads
// Listen for custom event that can be triggered after dynamic content loads
document.addEventListener('lwt:contentLoaded', () => {
  initIcons();
});

// Expose to window for use in inline scripts
declare global {
  interface Window {
    LWT_Icons: {
      init: typeof initIcons;
      initIn: typeof initIconsIn;
      create: typeof createIcon;
      replace: typeof replaceWithLucide;
    };
  }
}

window.LWT_Icons = {
  init: initIcons,
  initIn: initIconsIn,
  create: createIcon,
  replace: replaceWithLucide
};

// Auto-initialize
init();

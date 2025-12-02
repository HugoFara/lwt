/**
 * Lucide Icon Utilities for LWT.
 *
 * Provides functions to render Lucide SVG icons in JavaScript.
 * Maps legacy PNG icon names to their Lucide equivalents.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

/**
 * Mapping from legacy icon names to Lucide icon names.
 */
const ICON_MAP: Record<string, string> = {
  // Actions - CRUD
  'plus': 'plus',
  'plus-button': 'circle-plus',
  'minus': 'minus',
  'minus-button': 'circle-minus',
  'cross': 'x',
  'cross-button': 'x-circle',
  'tick': 'check',
  'tick-button': 'circle-check',
  'pencil': 'pencil',
  'eraser': 'eraser',
  'broom': 'brush',

  // Documents & Text
  'sticky-note--pencil': 'file-pen-line',

  // Status Indicators
  'status': 'circle-check',
  'status-busy': 'circle-x',
  'exclamation-red': 'circle-alert',

  // Test Results
  'thumb': 'thumbs-up',
  'thumb-up': 'thumbs-up',
  'star': 'star',

  // UI Elements
  'photo-album': 'image',

  // Audio & Media
  'speaker-volume': 'volume-2',

  // Help & Info
  'question-frame': 'help-circle',

  // Loading/Animated
  'waiting': 'loader-2',
  'waiting2': 'loader-2',

  // Placeholder
  'empty': '',
};

/**
 * Icons that should have the spinning animation.
 */
const ANIMATED_ICONS: Record<string, boolean> = {
  'waiting': true,
  'waiting2': true,
};

export interface IconOptions {
  title?: string;
  alt?: string;
  size?: number;
  className?: string;
  id?: string;
  style?: string;
  clickable?: boolean;
}

/**
 * Get the Lucide icon name for a legacy PNG icon.
 *
 * @param legacyName - Legacy PNG icon name (without extension)
 * @returns Lucide icon name, or the input if no mapping exists
 */
export function getLucideIconName(legacyName: string): string {
  return ICON_MAP[legacyName] ?? legacyName;
}

/**
 * Create a Lucide icon element.
 *
 * Uses <i data-lucide="..."> which Lucide.js replaces with SVG.
 *
 * @param name - Icon name (Lucide or legacy PNG name without extension)
 * @param options - Optional attributes for the icon
 * @returns HTMLElement for the icon
 */
export function createIcon(name: string, options: IconOptions = {}): HTMLElement {
  const lucideName = getLucideIconName(name);

  // Handle empty icon (spacer)
  if (lucideName === '') {
    const span = document.createElement('span');
    span.className = 'icon-spacer';
    const width = options.size ?? 16;
    span.style.display = 'inline-block';
    span.style.width = `${width}px`;
    span.style.height = `${width}px`;
    return span;
  }

  const icon = document.createElement('i');
  icon.setAttribute('data-lucide', lucideName);

  // Build CSS classes
  const classes = ['icon'];
  if (options.className) {
    classes.push(options.className);
  }
  if (options.clickable) {
    classes.push('click');
  }
  if (ANIMATED_ICONS[name]) {
    classes.push('icon-spin');
  }
  icon.className = classes.join(' ');

  // Set size
  const size = options.size ?? 16;
  icon.style.width = `${size}px`;
  icon.style.height = `${size}px`;

  // Set optional attributes
  if (options.id) {
    icon.id = options.id;
  }
  if (options.title) {
    icon.title = options.title;
  }
  if (options.alt) {
    icon.setAttribute('aria-label', options.alt);
  }
  if (options.style) {
    icon.style.cssText += options.style;
  }

  return icon;
}

/**
 * Render a Lucide icon as an HTML string.
 *
 * Uses <i data-lucide="..."> which Lucide.js replaces with SVG.
 *
 * @param name - Icon name (Lucide or legacy PNG name without extension)
 * @param options - Optional attributes for the icon
 * @returns HTML string for the icon
 */
export function iconHtml(name: string, options: IconOptions = {}): string {
  return createIcon(name, options).outerHTML;
}

/**
 * Initialize Lucide icons on the page.
 *
 * Call this after dynamically adding icons to the DOM.
 */
export function initLucideIcons(): void {
  // Check if lucide is available globally
  const lucide = (window as unknown as { lucide?: { createIcons: () => void } }).lucide;
  if (lucide) {
    lucide.createIcons();
  }
}

/**
 * Create a loading spinner icon element.
 *
 * @param options - Optional attributes
 * @returns HTMLElement for the spinner
 */
export function createSpinner(options: IconOptions = {}): HTMLElement {
  return createIcon('waiting2', { ...options, className: 'icon-spin' });
}

/**
 * Get HTML string for a loading spinner.
 *
 * @param options - Optional attributes
 * @returns HTML string for the spinner
 */
export function spinnerHtml(options: IconOptions = {}): string {
  return iconHtml('waiting2', { ...options, className: 'icon-spin' });
}

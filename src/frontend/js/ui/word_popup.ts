/**
 * Word Popup - jQuery UI Dialog replacement for overlib
 *
 * This module provides popup dialogs for word interactions,
 * replacing the legacy overlib library with jQuery UI dialogs.
 *
 * @license unlicense
 * @since 3.0.0
 */

import $ from 'jquery';

// Extend jQuery UI dialog types
declare global {
  interface JQuery {
    dialog(options?: DialogOptions | string): JQuery;
    dialog(method: string, option: string, value: unknown): JQuery;
  }
}

interface DialogOptions {
  title?: string;
  width?: number | string;
  height?: number | string;
  modal?: boolean;
  autoOpen?: boolean;
  position?: { my: string; at: string; of: Event | Element | null };
  classes?: Record<string, string>;
  close?: () => void;
  open?: () => void;
}

// Configuration matching old overlib settings
const POPUP_CONFIG = {
  width: 280,
  fgColor: '#FFFFE8',
  closeText: 'Close',
  fontFamily: '"Lucida Grande", Arial, sans-serif, STHeiti, "Arial Unicode MS", MingLiu'
};

// Store the current popup element
let currentPopup: JQuery | null = null;
let currentEvent: Event | null = null;

/**
 * Initialize the popup container in the DOM
 */
function ensurePopupContainer(): JQuery {
  let container = $('#lwt-word-popup');
  if (container.length === 0) {
    container = $('<div id="lwt-word-popup"></div>').appendTo('body');
    container.dialog({
      autoOpen: false,
      width: POPUP_CONFIG.width,
      modal: false,
      classes: {
        'ui-dialog': 'lwt-popup-dialog'
      },
      close: function () {
        currentPopup = null;
        currentEvent = null;
      }
    });
  }
  return container;
}

/**
 * Close any open popup
 */
export function cClick(): void {
  if (currentPopup) {
    try {
      currentPopup.dialog('close');
    } catch {
      // Dialog might already be destroyed
    }
    currentPopup = null;
    currentEvent = null;
  }
}

// Legacy alias
export function nd(): boolean {
  cClick();
  return true;
}

/**
 * Show a popup dialog with content and caption
 *
 * This is the main replacement for the overlib() function.
 *
 * @param content HTML content for the popup body
 * @param _caption Ignored for compatibility (was CAPTION constant)
 * @param title Title for the popup header
 * @returns true for compatibility
 */
export function overlib(content: string, _caption?: unknown, title?: string): boolean {
  // Close any existing popup
  cClick();

  const container = ensurePopupContainer();

  // Set content
  container.html(content);

  // Configure and open dialog
  container.dialog('option', 'title', title || 'Word');

  // Position near mouse/click if we have an event
  if (currentEvent && currentEvent instanceof MouseEvent) {
    container.dialog('option', 'position', {
      my: 'left top',
      at: 'left+' + (currentEvent.clientX + 10) + ' top+' + (currentEvent.clientY + 10),
      of: window
    });
  } else {
    // Default position
    container.dialog('option', 'position', {
      my: 'center',
      at: 'center',
      of: window
    });
  }

  container.dialog('open');
  currentPopup = container;

  return true;
}

/**
 * Store the current event for positioning
 * Call this before overlib() to position the popup near the click
 */
export function setCurrentEvent(event: Event): void {
  currentEvent = event;
}

/**
 * Get click handler that stores event before calling a function
 *
 * @param handler The actual click handler to call
 * @returns A wrapped handler that stores the event
 */
export function withEventPosition<T extends (...args: unknown[]) => unknown>(
  handler: T
): (event: Event, ...args: Parameters<T>) => ReturnType<T> {
  return function (event: Event, ...args: Parameters<T>): ReturnType<T> {
    setCurrentEvent(event);
    return handler(...args) as ReturnType<T>;
  };
}

// Add CSS styles for the popup
const styles = `
.lwt-popup-dialog {
  font-family: ${POPUP_CONFIG.fontFamily};
  font-size: 13px;
}
.lwt-popup-dialog .ui-dialog-titlebar {
  background: #5050A0;
  color: #FFFFFF;
  padding: 5px 10px;
}
.lwt-popup-dialog .ui-dialog-titlebar a {
  color: #FFFF00;
}
.lwt-popup-dialog .ui-dialog-content {
  background: ${POPUP_CONFIG.fgColor};
  padding: 10px;
}
.lwt-popup-dialog .ui-dialog-content a {
  color: #0000FF;
}
.lwt-popup-dialog .ui-dialog-content a:hover {
  color: #FF0000;
}
#lwt-word-popup img {
  vertical-align: middle;
  cursor: pointer;
}
`;

// Inject styles when module loads
if (typeof document !== 'undefined') {
  const styleEl = document.createElement('style');
  styleEl.textContent = styles;
  document.head.appendChild(styleEl);
}

// Export CAPTION constant for compatibility (unused but referenced)
export const CAPTION = 'CAPTION';

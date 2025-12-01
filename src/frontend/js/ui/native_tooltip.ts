/**
 * Native Tooltip - Replaces jQuery UI tooltip with a lightweight native implementation
 *
 * Provides hover tooltips for word elements in the reading interface.
 *
 * @license unlicense
 * @since 3.0.0
 */

import { LWT_DATA } from '../core/lwt_state';

// Tooltip configuration
const TOOLTIP_CONFIG = {
  offsetX: 0,
  offsetY: 10,
  showDelay: 100,
  hideDelay: 100,
  maxWidth: 300
};

// Module state
let tooltipElement: HTMLElement | null = null;
let showTimeout: ReturnType<typeof setTimeout> | null = null;
let hideTimeout: ReturnType<typeof setTimeout> | null = null;
let currentTarget: HTMLElement | null = null;

/**
 * Helper to safely get an HTML attribute value as a string.
 */
function getAttr(el: HTMLElement, attr: string): string {
  return el.getAttribute(attr) || '';
}

/**
 * Generate tooltip content for a word element.
 * Replicates the functionality of jQuery's tooltip_wsty_content.
 *
 * @param element The word element to generate tooltip content for
 * @returns HTML string for the tooltip content
 */
export function generateWordTooltipContent(element: HTMLElement): string {
  const delimiter = LWT_DATA.language?.delimiter || '';
  const re = new RegExp('([' + delimiter + '])(?! )', 'g');

  let title = '';
  const dataText = getAttr(element, 'data_text');

  if (element.classList.contains('mwsty')) {
    title = "<p><b style='font-size:120%'>" + dataText + '</b></p>';
  } else {
    title = "<p><b style='font-size:120%'>" + element.textContent + '</b></p>';
  }

  const roman = getAttr(element, 'data_rom');
  const transAttr = getAttr(element, 'data_trans');
  let trans = transAttr.replace(re, '$1 ');

  let statname = '';
  const status = parseInt(getAttr(element, 'data_status') || '0', 10);

  if (status === 0) statname = 'Unknown [?]';
  else if (status < 5) statname = 'Learning [' + status + ']';
  if (status === 5) statname = 'Learned [5]';
  if (status === 98) statname = 'Ignored [Ign]';
  if (status === 99) statname = 'Well Known [WKn]';

  if (roman !== '') {
    title += '<p><b>Roman.</b>: ' + roman + '</p>';
  }

  if (trans !== '' && trans !== '*') {
    const annAttr = getAttr(element, 'data_ann');
    if (annAttr) {
      const ann = annAttr;
      if (ann !== '' && ann !== '*') {
        const re2 = new RegExp(
          '(.*[' + delimiter + '][ ]{0,1}|^)(' +
          ann.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' +
          delimiter + '].*$| \\[.*$)',
          ''
        );
        trans = trans.replace(re2, '$1<span style="color:red">$2</span>$3');
      }
    }
    title += '<p><b>Transl.</b>: ' + trans + '</p>';
  }

  title += '<p><b>Status</b>: <span class="status' + status + '">' + statname + '</span></p>';

  return title;
}

/**
 * Create the tooltip element if it doesn't exist.
 */
function ensureTooltipElement(): HTMLElement {
  if (tooltipElement) {
    return tooltipElement;
  }

  tooltipElement = document.createElement('div');
  tooltipElement.id = 'lwt-native-tooltip';
  tooltipElement.className = 'lwt-tooltip';
  tooltipElement.setAttribute('role', 'tooltip');
  tooltipElement.style.display = 'none';
  document.body.appendChild(tooltipElement);

  return tooltipElement;
}

/**
 * Position the tooltip near the target element.
 *
 * @param tooltip The tooltip element
 * @param target The target element to position near
 */
function positionTooltip(tooltip: HTMLElement, target: HTMLElement): void {
  const targetRect = target.getBoundingClientRect();
  const tooltipRect = tooltip.getBoundingClientRect();

  // Default position: below and left-aligned with target
  let left = targetRect.left + TOOLTIP_CONFIG.offsetX;
  let top = targetRect.bottom + TOOLTIP_CONFIG.offsetY;

  // Adjust if tooltip would overflow right edge
  if (left + tooltipRect.width > window.innerWidth - 10) {
    left = window.innerWidth - tooltipRect.width - 10;
  }

  // Adjust if tooltip would overflow left edge
  if (left < 10) {
    left = 10;
  }

  // Adjust if tooltip would overflow bottom edge - show above instead
  if (top + tooltipRect.height > window.innerHeight - 10) {
    top = targetRect.top - tooltipRect.height - TOOLTIP_CONFIG.offsetY;
  }

  // Ensure tooltip doesn't go above viewport
  if (top < 10) {
    top = 10;
  }

  tooltip.style.left = `${left + window.scrollX}px`;
  tooltip.style.top = `${top + window.scrollY}px`;
}

/**
 * Show the tooltip for a target element.
 *
 * @param target The element to show the tooltip for
 * @param content HTML content for the tooltip
 */
function showTooltip(target: HTMLElement, content: string): void {
  // Clear any pending hide
  if (hideTimeout) {
    clearTimeout(hideTimeout);
    hideTimeout = null;
  }

  const tooltip = ensureTooltipElement();
  tooltip.innerHTML = content;
  tooltip.style.display = 'block';
  currentTarget = target;

  // Position after content is set (so we can measure)
  positionTooltip(tooltip, target);
}

/**
 * Hide the tooltip.
 */
function hideTooltip(): void {
  if (tooltipElement) {
    tooltipElement.style.display = 'none';
  }
  currentTarget = null;
}

/**
 * Handle mouse enter on a word element.
 */
function handleMouseEnter(event: Event): void {
  const target = event.target as HTMLElement;

  // Only show tooltip for .hword elements
  if (!target.classList.contains('hword')) {
    return;
  }

  // Clear any pending hide
  if (hideTimeout) {
    clearTimeout(hideTimeout);
    hideTimeout = null;
  }

  // Clear any pending show
  if (showTimeout) {
    clearTimeout(showTimeout);
  }

  // Delay showing the tooltip slightly
  showTimeout = setTimeout(() => {
    const content = generateWordTooltipContent(target);
    showTooltip(target, content);
  }, TOOLTIP_CONFIG.showDelay);
}

/**
 * Handle mouse leave on a word element.
 */
function handleMouseLeave(event: Event): void {
  const target = event.target as HTMLElement;

  // Only process for .hword elements
  if (!target.classList.contains('hword')) {
    return;
  }

  // Clear any pending show
  if (showTimeout) {
    clearTimeout(showTimeout);
    showTimeout = null;
  }

  // Delay hiding the tooltip slightly (allows moving to tooltip)
  hideTimeout = setTimeout(() => {
    hideTooltip();
  }, TOOLTIP_CONFIG.hideDelay);
}

/**
 * Initialize native tooltips on a container element.
 * Uses event delegation for efficiency.
 *
 * @param container The container element (or selector) to initialize tooltips on
 */
export function initNativeTooltips(container: HTMLElement | string): void {
  const containerEl = typeof container === 'string'
    ? document.querySelector<HTMLElement>(container)
    : container;

  if (!containerEl) {
    return;
  }

  // Use event delegation for better performance
  containerEl.addEventListener('mouseenter', handleMouseEnter, true);
  containerEl.addEventListener('mouseleave', handleMouseLeave, true);

  // Also handle focus for keyboard accessibility
  containerEl.addEventListener('focusin', (event) => {
    const target = event.target as HTMLElement;
    if (target.classList.contains('hword')) {
      const content = generateWordTooltipContent(target);
      showTooltip(target, content);
    }
  }, true);

  containerEl.addEventListener('focusout', (event) => {
    const target = event.target as HTMLElement;
    if (target.classList.contains('hword')) {
      hideTimeout = setTimeout(() => {
        hideTooltip();
      }, TOOLTIP_CONFIG.hideDelay);
    }
  }, true);
}

/**
 * Remove all tooltips from the DOM.
 * Useful when cleaning up or navigating away.
 */
export function removeAllTooltips(): void {
  if (tooltipElement) {
    tooltipElement.remove();
    tooltipElement = null;
  }

  // Also remove any jQuery UI tooltips that might exist
  document.querySelectorAll('.ui-tooltip').forEach(el => el.remove());
}

/**
 * Check if a tooltip is currently visible.
 */
export function isTooltipVisible(): boolean {
  return tooltipElement !== null && tooltipElement.style.display !== 'none';
}

/**
 * Get the current tooltip target element.
 */
export function getCurrentTooltipTarget(): HTMLElement | null {
  return currentTarget;
}

// CSS styles for the tooltip
const styles = `
.lwt-tooltip {
  position: absolute;
  z-index: 10000;
  max-width: ${TOOLTIP_CONFIG.maxWidth}px;
  padding: 8px 12px;
  background: #FFFFE8;
  border: 1px solid #5050A0;
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  font-family: "Lucida Grande", Arial, sans-serif, STHeiti, "Arial Unicode MS", MingLiu;
  font-size: 13px;
  line-height: 1.4;
  pointer-events: none;
}

.lwt-tooltip p {
  margin: 0 0 4px 0;
}

.lwt-tooltip p:last-child {
  margin-bottom: 0;
}

.lwt-tooltip b {
  color: #333;
}
`;

// Inject styles when module loads
if (typeof document !== 'undefined') {
  const styleEl = document.createElement('style');
  styleEl.textContent = styles;
  document.head.appendChild(styleEl);
}

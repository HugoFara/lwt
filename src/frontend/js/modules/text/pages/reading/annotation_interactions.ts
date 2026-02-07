/**
 * Annotation interactions for text display view.
 *
 * Handles click events on annotations and text in the print/display view.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.0.0
 */

import { onDomReady } from '@shared/utils/dom_ready';

/**
 * Handle click on an annotation (translation) to toggle visibility.
 * Clicking an annotation hides it (by matching background color),
 * clicking again reveals it.
 */
export function clickAnnotation(this: HTMLElement): void {
  const style = this.getAttribute('style');
  if (style !== null && style !== '') {
    this.removeAttribute('style');
  } else {
    this.style.color = '#C8DCF0';
    this.style.backgroundColor = '#C8DCF0';
  }
}

/**
 * Handle click on text (term) to toggle visibility.
 * Clicking text hides it (by matching background color),
 * clicking again reveals it.
 */
export function clickText(this: HTMLElement): void {
  const bc = getComputedStyle(document.body).color;
  const elColor = getComputedStyle(this).color;
  if (elColor !== bc) {
    this.style.color = 'inherit';
    this.style.backgroundColor = '';
  } else {
    this.style.color = '#E5E4E2';
    this.style.backgroundColor = '#E5E4E2';
  }
}

/**
 * Initialize annotation interactions.
 * Binds click handlers to annotation and text elements.
 */
export function initAnnotationInteractions(): void {
  document.querySelectorAll<HTMLElement>('.anntransruby2').forEach((el) => {
    el.addEventListener('click', function(this: HTMLElement) {
      clickAnnotation.call(this);
    });
  });
  document.querySelectorAll<HTMLElement>('.anntermruby').forEach((el) => {
    el.addEventListener('click', function(this: HTMLElement) {
      clickText.call(this);
    });
  });
}

/**
 * Auto-initialize if annotation elements exist on the page.
 */
function autoInit(): void {
  if (document.querySelector('.anntransruby2') || document.querySelector('.anntermruby')) {
    initAnnotationInteractions();
  }
}

// Initialize on DOM ready
onDomReady(autoInit);

// Export to window for potential external use
declare global {
  interface Window {
    clickAnnotation: typeof clickAnnotation;
    clickText: typeof clickText;
    initAnnotationInteractions: typeof initAnnotationInteractions;
  }
}

window.clickAnnotation = clickAnnotation;
window.clickText = clickText;
window.initAnnotationInteractions = initAnnotationInteractions;

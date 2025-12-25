/**
 * Highlight Service - Manages CSS class application for feed wizard highlighting.
 *
 * Encapsulates all DOM class manipulation for testability and separation of concerns.
 *
 * CSS classes used:
 * - .lwt_selected_text - Elements matching confirmed XPath selections
 * - .lwt_marked_text - Elements matching current/preview selection
 * - .lwt_filtered_text - Elements to be filtered out (step 3)
 * - .lwt_highlighted_text - Currently highlighted list item selection
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { xpathQuery, getDescendantsAndSelf } from '../utils/xpath_utils';

/**
 * Service for managing DOM element highlighting in the feed wizard.
 */
export class HighlightService {
  /** Header element containing wizard controls (excluded from highlighting) */
  private headerElement: HTMLElement | null = null;

  /** Set of header descendant elements for quick exclusion checks */
  private headerDescendants: Set<HTMLElement> = new Set();

  /** Track elements we've modified for cleanup */
  private modifiedElements: Set<HTMLElement> = new Set();

  /**
   * Initialize the highlight service with container references.
   *
   * @param headerSelector - CSS selector for the header element (default: '#lwt_header')
   */
  init(headerSelector: string = '#lwt_header'): void {
    this.headerElement = document.querySelector(headerSelector);
    this.headerDescendants.clear();

    if (this.headerElement) {
      getDescendantsAndSelf(this.headerElement).forEach(el => {
        this.headerDescendants.add(el);
      });
    }
  }

  /**
   * Apply marking (preview) to elements matching an XPath expression.
   * Clears any existing marking first.
   *
   * @param xpath - XPath expression to match
   */
  markElements(xpath: string): void {
    this.clearMarking();

    if (!xpath) return;

    const elements = xpathQuery(xpath);
    for (const el of this.filterValidElements(elements)) {
      this.addClassToTree(el, 'lwt_marked_text');
    }
  }

  /**
   * Clear all marking classes from the document.
   */
  clearMarking(): void {
    document.querySelectorAll('.lwt_marked_text').forEach(el => {
      el.classList.remove('lwt_marked_text');
      this.modifiedElements.add(el as HTMLElement);
    });
    this.cleanEmptyClassAttrs();
  }

  /**
   * Apply selection to all elements matching an array of XPath expressions.
   *
   * @param xpaths - Array of XPath expressions
   */
  applySelections(xpaths: string[]): void {
    this.clearSelections();

    if (xpaths.length === 0) return;

    // Combine all xpaths and query once
    const combined = xpaths.filter(x => x.trim()).join(' | ');
    if (!combined) return;

    const elements = xpathQuery(combined);
    for (const el of this.filterValidElements(elements)) {
      this.addClassToTree(el, 'lwt_selected_text');
    }
  }

  /**
   * Clear all selection classes from the document.
   */
  clearSelections(): void {
    document.querySelectorAll('.lwt_selected_text').forEach(el => {
      el.classList.remove('lwt_selected_text');
      this.modifiedElements.add(el as HTMLElement);
    });
    this.cleanEmptyClassAttrs();
  }

  /**
   * Highlight elements matching an XPath (for list item focus).
   * Also applies selection class to descendants.
   *
   * @param xpath - XPath expression to highlight
   */
  highlightListItem(xpath: string): void {
    this.clearHighlighting();

    if (!xpath) return;

    const elements = xpathQuery(xpath);
    for (const el of this.filterValidElements(elements)) {
      el.classList.add('lwt_highlighted_text');
      this.modifiedElements.add(el);

      // Also mark descendants as selected
      this.addClassToTree(el, 'lwt_selected_text');
    }
  }

  /**
   * Clear all highlighting classes from the document.
   */
  clearHighlighting(): void {
    document.querySelectorAll('.lwt_highlighted_text').forEach(el => {
      el.classList.remove('lwt_highlighted_text');
      this.modifiedElements.add(el as HTMLElement);
    });
  }

  /**
   * Apply filter classes to elements matching XPath expressions.
   * Used in step 3 to dim elements that will be filtered out.
   *
   * @param xpaths - Array of XPath expressions to filter
   */
  applyFilters(xpaths: string[]): void {
    if (xpaths.length === 0) return;

    const combined = xpaths.filter(x => x.trim()).join(' | ');
    if (!combined) return;

    const elements = xpathQuery(combined);
    for (const el of this.filterValidElements(elements)) {
      el.classList.add('lwt_filtered_text');
      this.modifiedElements.add(el);
    }
  }

  /**
   * Apply article section filtering for step 3.
   * Marks all elements outside the article section as filtered.
   *
   * @param articleSelector - XPath expression for article section
   */
  applyArticleSectionFilter(articleSelector: string): void {
    this.clearFiltering();

    if (!articleSelector) return;

    // Get all elements in the article section
    const articleElements = xpathQuery(articleSelector);
    const articleSet = new Set<HTMLElement>();

    articleElements.forEach(el => {
      getDescendantsAndSelf(el).forEach(d => articleSet.add(d));
    });

    // Find all content after the header and filter non-article elements
    const lwtLast = document.getElementById('lwt_last');
    if (!lwtLast) return;

    let sibling = lwtLast.nextElementSibling;
    while (sibling) {
      if (sibling instanceof HTMLElement) {
        getDescendantsAndSelf(sibling).forEach(el => {
          if (!articleSet.has(el) && !this.headerDescendants.has(el)) {
            el.classList.add('lwt_filtered_text');
            this.modifiedElements.add(el);
          }
        });
      }
      sibling = sibling.nextElementSibling;
    }
  }

  /**
   * Clear all filter classes from the document.
   */
  clearFiltering(): void {
    document.querySelectorAll('.lwt_filtered_text').forEach(el => {
      el.classList.remove('lwt_filtered_text');
      this.modifiedElements.add(el as HTMLElement);
    });
    this.cleanEmptyClassAttrs();
  }

  /**
   * Clear all wizard-related classes from the document.
   */
  clearAll(): void {
    this.clearMarking();
    this.clearSelections();
    this.clearFiltering();
    this.clearHighlighting();
  }

  /**
   * Toggle image visibility in the feed content.
   *
   * @param hide - Whether to hide images
   */
  toggleImages(hide: boolean): void {
    document.querySelectorAll<HTMLImageElement>('img').forEach(img => {
      // Skip images in the header
      if (this.headerDescendants.has(img)) return;

      img.style.display = hide ? 'none' : '';
    });
  }

  /**
   * Get the feed content container (elements after lwt_last).
   *
   * @returns Array of content container elements
   */
  getContentElements(): HTMLElement[] {
    const elements: HTMLElement[] = [];
    const lwtLast = document.getElementById('lwt_last');

    if (lwtLast) {
      let sibling = lwtLast.nextElementSibling;
      while (sibling) {
        if (sibling instanceof HTMLElement) {
          elements.push(sibling);
        }
        sibling = sibling.nextElementSibling;
      }
    }

    return elements;
  }

  /**
   * Update the margin of lwt_last to account for header height.
   */
  updateLastMargin(): void {
    const lwtLast = document.getElementById('lwt_last');
    if (lwtLast && this.headerElement) {
      lwtLast.style.marginTop = this.headerElement.offsetHeight + 'px';
    }
  }

  // === Private Helper Methods ===

  /**
   * Filter out elements that are within the header.
   */
  private filterValidElements(elements: HTMLElement[]): HTMLElement[] {
    return elements.filter(el => !this.headerDescendants.has(el));
  }

  /**
   * Add a class to an element and all its descendants.
   */
  private addClassToTree(element: HTMLElement, className: string): void {
    element.classList.add(className);
    this.modifiedElements.add(element);

    element.querySelectorAll('*').forEach(child => {
      if (child instanceof HTMLElement) {
        // Don't overwrite selected_text with marked_text
        if (className === 'lwt_marked_text' && child.classList.contains('lwt_selected_text')) {
          return;
        }
        child.classList.add(className);
        this.modifiedElements.add(child);
      }
    });
  }

  /**
   * Remove empty class attributes from modified elements.
   */
  private cleanEmptyClassAttrs(): void {
    this.modifiedElements.forEach(el => {
      if (el.getAttribute('class') === '') {
        el.removeAttribute('class');
      }
    });

    // Also clean any others we might have missed
    document.querySelectorAll('[class=""]').forEach(el => {
      el.removeAttribute('class');
    });
  }
}

/**
 * Singleton instance of the highlight service.
 */
let highlightServiceInstance: HighlightService | null = null;

/**
 * Get the highlight service singleton.
 *
 * @returns The highlight service instance
 */
export function getHighlightService(): HighlightService {
  if (!highlightServiceInstance) {
    highlightServiceInstance = new HighlightService();
  }
  return highlightServiceInstance;
}

/**
 * Initialize the highlight service with default settings.
 * Call this when the wizard page loads.
 */
export function initHighlightService(): HighlightService {
  const service = getHighlightService();
  service.init();
  return service;
}

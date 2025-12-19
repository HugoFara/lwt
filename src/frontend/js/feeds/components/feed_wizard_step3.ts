/**
 * Feed Wizard Step 3 Component - Filter Text.
 *
 * Alpine.js component for the feed wizard step 3 (filter selection).
 * Similar to step 2 but for selecting elements to filter out.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import type { FeedWizardStoreState, FeedItem, XPathOption } from '../types/feed_wizard_types';
import { getFeedWizardStore } from '../stores/feed_wizard_store';
import { getHighlightService, initHighlightService } from '../services/highlight_service';
import {
  xpathQuery,
  generateMarkActionOptions,
  generateAdvancedXPathOptions,
  getAncestorsAndSelf,
  parseSelectionList
} from '../utils/xpath_utils';

/**
 * Step 3 component configuration from PHP.
 */
export interface Step3Config {
  rssUrl: string;
  feedTitle: string;
  feedText: string;
  articleSection: string;
  articleSelector: string;
  filterTags: string;
  feedItems: FeedItem[];
  selectedFeedIndex: number;
  settings: {
    selectionMode: string;
    hideImages: boolean;
    isMinimized: boolean;
  };
  editFeedId: number | null;
  multipleHosts: boolean;
}

/**
 * Step 3 component data interface.
 */
export interface FeedWizardStep3Data {
  // Configuration
  config: Step3Config;

  // UI state
  settingsOpen: boolean;

  // Form data
  selectedFeedIndex: number;
  hostStatus: string;

  // Computed
  readonly store: FeedWizardStoreState;
  readonly filterSelectors: Array<{ id: string; xpath: string; isHighlighted: boolean }>;
  readonly markActionOptions: XPathOption[];
  readonly currentXPath: string;
  readonly isMinimized: boolean;
  readonly selectionMode: string;
  readonly hideImages: boolean;

  // Lifecycle
  init(): void;
  destroy(): void;

  // Event handlers
  handleContentClick(event: MouseEvent): void;
  handleMarkActionChange(event: Event): void;

  // Actions
  filterSelection(): void;
  deleteSelector(id: string): void;
  toggleSelectorHighlight(id: string): void;
  changeSelectMode(): void;
  changeHideImages(): void;
  changeSelectedFeed(): void;
  toggleMinimize(): void;
  goBack(): void;
  goNext(): void;
  cancel(): void;

  // Advanced mode
  openAdvancedMode(element: HTMLElement): void;
  cancelAdvanced(): void;
  getAdvanced(): void;

  // Internal methods
  bindContentClickHandler(): void;
  handleSelectedClick(target: HTMLElement): void;
  generateOptionsForElement(element: HTMLElement): void;
  updateHighlighting(): void;
}

/**
 * Read configuration from JSON script tag.
 */
function readConfig(): Step3Config {
  const configEl = document.getElementById('wizard-step3-config');
  if (!configEl) {
    return {
      rssUrl: '',
      feedTitle: '',
      feedText: '',
      articleSection: '',
      articleSelector: '',
      filterTags: '',
      feedItems: [],
      selectedFeedIndex: 0,
      settings: {
        selectionMode: '0',
        hideImages: true,
        isMinimized: false
      },
      editFeedId: null,
      multipleHosts: false
    };
  }

  try {
    return JSON.parse(configEl.textContent || '{}');
  } catch {
    console.error('Failed to parse wizard step 3 config');
    return {
      rssUrl: '',
      feedTitle: '',
      feedText: '',
      articleSection: '',
      articleSelector: '',
      filterTags: '',
      feedItems: [],
      selectedFeedIndex: 0,
      settings: {
        selectionMode: '0',
        hideImages: true,
        isMinimized: false
      },
      editFeedId: null,
      multipleHosts: false
    };
  }
}

/**
 * Map selection mode string to typed value.
 */
function mapSelectionMode(mode: string): 'smart' | 'all' | 'adv' {
  switch (mode) {
    case 'all': return 'all';
    case 'adv': return 'adv';
    default: return 'smart';
  }
}

/**
 * Feed wizard step 3 component factory.
 */
export function feedWizardStep3Data(): FeedWizardStep3Data {
  const config = readConfig();
  const highlightService = getHighlightService();

  return {
    // Configuration
    config,

    // UI state
    settingsOpen: false,

    // Form data
    selectedFeedIndex: config.selectedFeedIndex || 0,
    hostStatus: '-',

    get store(): FeedWizardStoreState {
      return getFeedWizardStore();
    },

    get filterSelectors() {
      return this.store.filterSelectors;
    },

    get markActionOptions(): XPathOption[] {
      return this.store.markActionOptions;
    },

    get currentXPath(): string {
      return this.store.currentXPath;
    },

    get isMinimized(): boolean {
      return this.store.isMinimized;
    },

    get selectionMode(): string {
      switch (this.store.selectionMode) {
        case 'all': return 'all';
        case 'adv': return 'adv';
        default: return '0';
      }
    },

    get hideImages(): boolean {
      return this.store.hideImages;
    },

    init(): void {
      // Initialize highlight service
      initHighlightService();

      // Parse existing filter selectors from HTML if present
      const existingFilters = parseSelectionList(document.getElementById('lwt_sel'));

      // Configure store
      this.store.configure({
        step: 3,
        rssUrl: this.config.rssUrl,
        feedTitle: this.config.feedTitle,
        feedText: this.config.feedText,
        articleSelector: this.config.articleSelector,
        feedItems: this.config.feedItems,
        selectedFeedIndex: this.config.selectedFeedIndex,
        editFeedId: this.config.editFeedId,
        filterSelectors: existingFilters,
        settings: {
          selectionMode: mapSelectionMode(this.config.settings.selectionMode),
          hideImages: this.config.settings.hideImages,
          isMinimized: this.config.settings.isMinimized
        }
      });

      // Apply article section filtering (dim elements outside article)
      highlightService.applyArticleSectionFilter(this.config.articleSelector);

      // Apply filter selections
      this.updateHighlighting();

      // Apply image visibility
      highlightService.toggleImages(this.store.hideImages);

      // Update last element margin
      highlightService.updateLastMargin();

      // Bind click handler to feed content
      this.bindContentClickHandler();
    },

    destroy(): void {
      highlightService.clearAll();
    },

    /**
     * Bind click handler to all feed content elements.
     */
    bindContentClickHandler(): void {
      const lwtLast = document.getElementById('lwt_last');
      if (!lwtLast) return;

      let sibling = lwtLast.nextElementSibling;
      while (sibling) {
        sibling.addEventListener('click', (e: Event) => {
          this.handleContentClick(e as MouseEvent);
        });
        sibling = sibling.nextElementSibling;
      }
    },

    handleContentClick(event: MouseEvent): void {
      const target = event.target as HTMLElement;
      if (!target) return;

      // Check if clicking on already selected element
      if (target.classList.contains('lwt_selected_text')) {
        this.handleSelectedClick(target);
        return;
      }

      // Check if clicking on filtered (dimmed) element from article section
      if (target.classList.contains('lwt_filtered_text')) {
        event.preventDefault();
        return;
      }

      // Check if clicking on marked (preview) element - clear it
      if (target.classList.contains('lwt_marked_text')) {
        this.store.setCurrentXPath('');
        this.store.setMarkActionOptions([]);
        highlightService.clearMarking();
        return;
      }

      // Generate options for this element
      this.generateOptionsForElement(target);
    },

    handleSelectedClick(target: HTMLElement): void {
      // Find which selector contains this element
      for (const selector of this.store.filterSelectors) {
        const elements = xpathQuery(selector.xpath);
        const containsTarget = elements.some(el =>
          el.contains(target) || target.contains(el) || el === target
        );

        if (containsTarget) {
          if (selector.isHighlighted) {
            this.store.clearHighlight();
          } else {
            this.store.highlightSelector(selector.id);
          }
          this.updateHighlighting();
          break;
        }
      }
    },

    /**
     * Generate mark action options for a clicked element.
     */
    generateOptionsForElement(element: HTMLElement): void {
      // Clear previous
      highlightService.clearMarking();

      // Get ancestors and self for option generation
      const ancestors = getAncestorsAndSelf(element);

      const allOptions: XPathOption[] = [];

      // Generate options for the element and its ancestors
      for (const el of ancestors) {
        if (el.classList.contains('lwt_filtered_text')) continue;

        const options = generateMarkActionOptions(el, this.store.selectionMode);
        allOptions.push(...options);
      }

      if (allOptions.length > 0) {
        this.store.setMarkActionOptions(allOptions);

        // Apply preview highlighting for the first option
        highlightService.markElements(allOptions[0].value);
      }
    },

    handleMarkActionChange(event: Event): void {
      const select = event.target as HTMLSelectElement;
      const xpath = select.value;

      this.store.setCurrentXPath(xpath);

      if (xpath) {
        highlightService.markElements(xpath);
      } else {
        highlightService.clearMarking();
      }
    },

    filterSelection(): void {
      const xpath = this.store.currentXPath;
      if (!xpath) return;

      // Check if advanced mode
      if (this.store.selectionMode === 'adv') {
        // Open advanced modal
        const elements = xpathQuery(xpath);
        if (elements.length > 0) {
          this.openAdvancedMode(elements[0]);
        }
        return;
      }

      // Add to filter list
      this.store.addSelector(xpath, 'filter');

      // Clear current selection state
      highlightService.clearMarking();

      // Update highlighting
      this.updateHighlighting();

      // Update margin
      highlightService.updateLastMargin();
    },

    deleteSelector(id: string): void {
      this.store.removeSelector(id, 'filter');
      this.updateHighlighting();
      highlightService.updateLastMargin();
    },

    toggleSelectorHighlight(id: string): void {
      const selector = this.store.filterSelectors.find(s => s.id === id);
      if (selector?.isHighlighted) {
        this.store.clearHighlight();
      } else {
        this.store.highlightSelector(id);
      }
      this.updateHighlighting();
    },

    /**
     * Update DOM highlighting based on current store state.
     */
    updateHighlighting(): void {
      // Clear selection and highlight classes (keep filter classes)
      highlightService.clearSelections();
      highlightService.clearHighlighting();

      // Re-apply article section filtering
      highlightService.applyArticleSectionFilter(this.config.articleSelector);

      // Apply filter selections as selected (so they're visible)
      const xpaths = this.store.filterSelectors.map(s => s.xpath);
      highlightService.applySelections(xpaths);

      // Apply highlight for focused item
      const highlighted = this.store.filterSelectors.find(s => s.isHighlighted);
      if (highlighted) {
        highlightService.highlightListItem(highlighted.xpath);
      }
    },

    changeSelectMode(): void {
      // Clear any current marking when mode changes
      highlightService.clearMarking();
      this.store.setCurrentXPath('');
      this.store.setMarkActionOptions([]);
    },

    changeHideImages(): void {
      highlightService.toggleImages(this.store.hideImages);
    },

    changeSelectedFeed(): void {
      // Submit form to reload with new feed item
      const form = document.querySelector<HTMLFormElement>('form[name="lwt_form1"]');
      const htmlInput = form?.querySelector<HTMLInputElement>('input[name="html"]');
      const lwtSel = document.getElementById('lwt_sel');

      if (htmlInput && lwtSel) {
        htmlInput.value = lwtSel.innerHTML;
      }

      form?.submit();
    },

    toggleMinimize(): void {
      this.store.isMinimized = !this.store.isMinimized;
      highlightService.updateLastMargin();
    },

    goBack(): void {
      // Go back to step 2, preserving filter selections
      const lwtSel = document.getElementById('lwt_sel');
      const maximInput = document.getElementById('maxim') as HTMLInputElement | null;

      const url = '/feeds/wizard?step=2&article_tags=1' +
        `&maxim=${encodeURIComponent(maximInput?.value || '')}` +
        `&filter_tags=${encodeURIComponent(lwtSel?.innerHTML || '')}` +
        `&select_mode=${encodeURIComponent(this.selectionMode)}` +
        `&hide_images=${encodeURIComponent(this.hideImages ? 'yes' : 'no')}`;

      window.location.href = url;
    },

    goNext(): void {
      const form = document.querySelector<HTMLFormElement>('form[name="lwt_form1"]');
      if (!form) return;

      // Build combined XPath string for filters
      const combinedXPath = this.store.buildSelectorsString('filter');

      // Update hidden inputs
      const htmlInput = form.querySelector<HTMLInputElement>('input[name="html"]');
      if (htmlInput) {
        htmlInput.value = combinedXPath;
      }

      // Update filter tags
      const filterTagsInput = form.querySelector<HTMLInputElement>('input[name="filter_tags"]');
      if (filterTagsInput) {
        filterTagsInput.value = document.getElementById('lwt_sel')?.innerHTML || '';
        filterTagsInput.disabled = false;
      }

      // Update step
      const stepInput = form.querySelector<HTMLInputElement>('input[name="step"]');
      if (stepInput) {
        stepInput.value = '4';
      }

      form.submit();
    },

    cancel(): void {
      window.location.href = '/feeds/edit?del_wiz=1';
    },

    // Advanced mode methods
    openAdvancedMode(element: HTMLElement): void {
      const options = generateAdvancedXPathOptions(
        element,
        this.store.markActionOptions[0]?.tagName?.toLowerCase()
      );

      this.store.openAdvanced(options);
      highlightService.clearMarking();
      highlightService.updateLastMargin();
    },

    cancelAdvanced(): void {
      this.store.closeAdvanced();
      highlightService.updateLastMargin();
    },

    getAdvanced(): void {
      // Get selected option from radio buttons or custom input
      const advEl = document.getElementById('adv');
      const checkedRadio = advEl?.querySelector<HTMLInputElement>('input[type="radio"]:checked');

      let xpath = '';
      if (checkedRadio) {
        xpath = checkedRadio.value;
        // If custom, use the custom input value
        if (!xpath && this.store.customXPathValid) {
          xpath = this.store.customXPath;
        }
      }

      if (xpath) {
        this.store.addSelector(xpath, 'filter');
        this.updateHighlighting();
      }

      this.store.closeAdvanced();
      highlightService.updateLastMargin();
    }
  };
}

/**
 * Initialize the step 3 Alpine component.
 */
export function initFeedWizardStep3Alpine(): void {
  Alpine.data('feedWizardStep3', feedWizardStep3Data);
}

// Register immediately
initFeedWizardStep3Alpine();

// Expose for global access
declare global {
  interface Window {
    feedWizardStep3Data: typeof feedWizardStep3Data;
  }
}

window.feedWizardStep3Data = feedWizardStep3Data;

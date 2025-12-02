/**
 * Feed Wizard Step 2 Component - Select Article Text.
 *
 * Alpine.js component for the feed wizard step 2 (article selection).
 * Handles XPath selection, element highlighting, and feed navigation.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import type { FeedWizardStoreState, WizardConfig, FeedItem, XPathOption } from '../types/feed_wizard_types';
import { getFeedWizardStore } from '../stores/feed_wizard_store';
import { getHighlightService, initHighlightService } from '../services/highlight_service';
import {
  xpathQuery,
  generateMarkActionOptions,
  generateAdvancedXPathOptions,
  getAncestorsAndSelf,
  parseSelectionList
} from '../utils/xpath_utils';
import { iconHtml } from '../../ui/icons';

/**
 * Step 2 component configuration from PHP.
 */
export interface Step2Config {
  rssUrl: string;
  feedTitle: string;
  feedText: string;
  detectedFeed: string;
  feedItems: FeedItem[];
  selectedFeedIndex: number;
  articleTags: string;
  settings: {
    selectionMode: string;
    hideImages: boolean;
    isMinimized: boolean;
  };
  editFeedId: number | null;
  articleSources: string[];
  multipleHosts: boolean;
}

/**
 * Step 2 component data interface.
 */
export interface FeedWizardStep2Data {
  // Configuration
  config: Step2Config;

  // UI state
  settingsOpen: boolean;

  // Form data
  feedName: string;
  articleSource: string;
  selectedFeedIndex: number;
  hostStatus: string;

  // Computed
  readonly store: FeedWizardStoreState;
  readonly canProceed: boolean;
  readonly articleSelectors: Array<{ id: string; xpath: string; isHighlighted: boolean }>;
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
  getSelection(): void;
  deleteSelector(id: string): void;
  toggleSelectorHighlight(id: string): void;
  changeSelectMode(): void;
  changeHideImages(): void;
  changeSelectedFeed(): void;
  changeArticleSection(): void;
  toggleMinimize(): void;
  goBack(): void;
  goNext(): void;
  cancel(): void;

  // Advanced mode
  openAdvancedMode(element: HTMLElement): void;
  selectAdvancedOption(xpath: string): void;
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
function readConfig(): Step2Config {
  const configEl = document.getElementById('wizard-step2-config');
  if (!configEl) {
    return {
      rssUrl: '',
      feedTitle: '',
      feedText: '',
      detectedFeed: '',
      feedItems: [],
      selectedFeedIndex: 0,
      articleTags: '',
      settings: {
        selectionMode: '0',
        hideImages: true,
        isMinimized: false
      },
      editFeedId: null,
      articleSources: [],
      multipleHosts: false
    };
  }

  try {
    return JSON.parse(configEl.textContent || '{}');
  } catch {
    console.error('Failed to parse wizard step 2 config');
    return {
      rssUrl: '',
      feedTitle: '',
      feedText: '',
      detectedFeed: '',
      feedItems: [],
      selectedFeedIndex: 0,
      articleTags: '',
      settings: {
        selectionMode: '0',
        hideImages: true,
        isMinimized: false
      },
      editFeedId: null,
      articleSources: [],
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
 * Feed wizard step 2 component factory.
 */
export function feedWizardStep2Data(): FeedWizardStep2Data {
  const config = readConfig();
  const highlightService = getHighlightService();

  return {
    // Configuration
    config,

    // UI state
    settingsOpen: false,

    // Form data
    feedName: config.feedTitle || '',
    articleSource: config.feedText || '',
    selectedFeedIndex: config.selectedFeedIndex || 0,
    hostStatus: '-',

    get store(): FeedWizardStoreState {
      return getFeedWizardStore();
    },

    get canProceed(): boolean {
      return this.store.articleSelectors.length > 0;
    },

    get articleSelectors() {
      return this.store.articleSelectors;
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

      // Parse existing selections from HTML if present
      const existingSelectors = parseSelectionList(document.getElementById('lwt_sel'));

      // Configure store
      this.store.configure({
        step: 2,
        rssUrl: this.config.rssUrl,
        feedTitle: this.config.feedTitle,
        feedText: this.config.feedText,
        detectedFeed: this.config.detectedFeed,
        feedItems: this.config.feedItems,
        selectedFeedIndex: this.config.selectedFeedIndex,
        editFeedId: this.config.editFeedId,
        articleSelectors: existingSelectors,
        settings: {
          selectionMode: mapSelectionMode(this.config.settings.selectionMode),
          hideImages: this.config.settings.hideImages,
          isMinimized: this.config.settings.isMinimized
        }
      });

      // Apply initial highlighting
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

      // Check if clicking on filtered element
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
      for (const selector of this.store.articleSelectors) {
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

    getSelection(): void {
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

      // Add to selection list
      this.store.addSelector(xpath, 'article');

      // Clear current selection state
      highlightService.clearMarking();

      // Update highlighting
      this.updateHighlighting();

      // Update margin
      highlightService.updateLastMargin();
    },

    deleteSelector(id: string): void {
      this.store.removeSelector(id, 'article');
      this.updateHighlighting();
      highlightService.updateLastMargin();
    },

    toggleSelectorHighlight(id: string): void {
      const selector = this.store.articleSelectors.find(s => s.id === id);
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
      // Clear all highlighting
      highlightService.clearAll();

      // Apply selections
      const xpaths = this.store.articleSelectors.map(s => s.xpath);
      highlightService.applySelections(xpaths);

      // Apply highlight for focused item
      const highlighted = this.store.articleSelectors.find(s => s.isHighlighted);
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

    changeArticleSection(): void {
      // Submit form to reload with new article section
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
      const url = '/feeds/wizard?step=1' +
        `&select_mode=${encodeURIComponent(this.selectionMode)}` +
        `&hide_images=${encodeURIComponent(this.hideImages ? 'yes' : 'no')}`;
      window.location.href = url;
    },

    goNext(): void {
      const form = document.querySelector<HTMLFormElement>('form[name="lwt_form1"]');
      if (!form) return;

      // Build combined XPath string
      const combinedXPath = this.store.buildSelectorsString('article');

      // Update hidden inputs
      const htmlInput = form.querySelector<HTMLInputElement>('input[name="html"]');
      if (htmlInput) {
        htmlInput.name = 'article_selector';
        htmlInput.value = combinedXPath;
      }

      // Update article section for all options
      const articleSectionSelect = form.querySelector<HTMLSelectElement>('select[name="NfArticleSection"]');
      if (articleSectionSelect) {
        articleSectionSelect.querySelectorAll('option').forEach(opt => {
          opt.value = combinedXPath;
        });
      }

      // Update article tags
      const articleTagsInput = form.querySelector<HTMLInputElement>('input[name="article_tags"]');
      if (articleTagsInput) {
        articleTagsInput.value = document.getElementById('lwt_sel')?.innerHTML || '';
        articleTagsInput.disabled = false;
      }

      // Update step
      const stepInput = form.querySelector<HTMLInputElement>('input[name="step"]');
      if (stepInput) {
        stepInput.value = '3';
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

    selectAdvancedOption(xpath: string): void {
      this.store.customXPath = xpath;
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
        this.store.addSelector(xpath, 'article');
        this.updateHighlighting();
      }

      this.store.closeAdvanced();
      highlightService.updateLastMargin();
    }
  };
}

/**
 * Initialize the step 2 Alpine component.
 */
export function initFeedWizardStep2Alpine(): void {
  Alpine.data('feedWizardStep2', feedWizardStep2Data);
}

// Register immediately
initFeedWizardStep2Alpine();

// Expose for global access
declare global {
  interface Window {
    feedWizardStep2Data: typeof feedWizardStep2Data;
  }
}

window.feedWizardStep2Data = feedWizardStep2Data;

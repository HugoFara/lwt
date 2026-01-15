/**
 * Alpine.js Searchable Select Component
 *
 * A filterable dropdown component that replaces standard <select> elements
 * with a searchable interface. Supports keyboard navigation and accessibility.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { setLangAsync } from '@modules/language/stores/language_settings';

/**
 * Option item in the select dropdown.
 */
export interface SearchableSelectOption {
  value: string;
  label: string;
}

/**
 * Configuration for the searchable select component.
 */
export interface SearchableSelectConfig {
  options: SearchableSelectOption[];
  selectedValue: string;
  placeholder: string;
  name: string;
  id: string;
  required: boolean;
  dataAction?: string;
  dataAjax?: boolean;
  dataRedirect?: string;
}

/**
 * Alpine.js magic properties available in component context.
 */
interface AlpineMagics {
  $watch: (prop: string, callback: () => void) => void;
  $nextTick: (callback: () => void) => void;
  $refs: Record<string, HTMLElement>;
  $el: HTMLElement;
}

/**
 * Data structure for the Alpine.js component.
 */
export interface SearchableSelectData {
  // State
  isOpen: boolean;
  searchQuery: string;
  highlightedIndex: number;
  selectedValue: string;
  selectedLabel: string;

  // Config
  options: SearchableSelectOption[];
  placeholder: string;
  name: string;
  id: string;
  required: boolean;
  dataAction: string;
  dataAjax: boolean;
  dataRedirect: string;

  // Computed
  readonly filteredOptions: SearchableSelectOption[];

  // Methods
  init(): void;
  open(): void;
  close(): void;
  toggle(): void;
  selectOption(option: SearchableSelectOption): void;
  handleKeydown(event: KeyboardEvent): void;
  highlightNext(): void;
  highlightPrev(): void;
  selectHighlighted(): void;
  scrollToHighlighted(): void;
  triggerChange(): Promise<void>;
}

/**
 * Full component type including Alpine magics.
 */
type SearchableSelectComponent = SearchableSelectData & AlpineMagics;

/**
 * Initialize the searchable select Alpine.js component.
 * This must be called before Alpine.start().
 */
export function initSearchableSelectAlpine(): void {
  Alpine.data('searchableSelect', (config: SearchableSelectConfig) => {
    // Find the initially selected option's label
    const selectedOption = config.options.find(opt => opt.value === config.selectedValue);
    const initialLabel = selectedOption?.label || '';

    return {
      // State
      isOpen: false,
      searchQuery: '',
      highlightedIndex: 0,
      selectedValue: config.selectedValue,
      selectedLabel: initialLabel,

      // Config
      options: config.options,
      placeholder: config.placeholder,
      name: config.name,
      id: config.id,
      required: config.required,
      dataAction: config.dataAction || '',
      dataAjax: config.dataAjax || false,
      dataRedirect: config.dataRedirect || '/',

      // Computed property for filtered options
      get filteredOptions(): SearchableSelectOption[] {
        const self = this as SearchableSelectComponent;
        if (!self.searchQuery.trim()) {
          return self.options;
        }
        const query = self.searchQuery.toLowerCase();
        return self.options.filter(opt =>
          opt.label.toLowerCase().includes(query)
        );
      },

      init(this: SearchableSelectComponent) {
        // Reset highlighted index when filtered options change
        this.$watch('searchQuery', () => {
          this.highlightedIndex = 0;
        });
      },

      open(this: SearchableSelectComponent) {
        this.isOpen = true;
        this.searchQuery = '';
        this.highlightedIndex = 0;
        // Focus search input after Alpine updates DOM
        this.$nextTick(() => {
          const searchInput = this.$refs.searchInput as HTMLInputElement;
          if (searchInput) {
            searchInput.focus();
          }
        });
      },

      close(this: SearchableSelectComponent) {
        this.isOpen = false;
        this.searchQuery = '';
      },

      toggle(this: SearchableSelectComponent) {
        if (this.isOpen) {
          this.close();
        } else {
          this.open();
        }
      },

      selectOption(this: SearchableSelectComponent, option: SearchableSelectOption) {
        this.selectedValue = option.value;
        this.selectedLabel = option.label;
        this.close();
        this.triggerChange();
      },

      handleKeydown(this: SearchableSelectComponent, event: KeyboardEvent) {
        switch (event.key) {
          case 'ArrowDown':
            event.preventDefault();
            if (!this.isOpen) {
              this.open();
            } else {
              this.highlightNext();
            }
            break;

          case 'ArrowUp':
            event.preventDefault();
            if (this.isOpen) {
              this.highlightPrev();
            }
            break;

          case 'Enter':
            event.preventDefault();
            if (this.isOpen && this.filteredOptions.length > 0) {
              this.selectHighlighted();
            } else if (!this.isOpen) {
              this.open();
            }
            break;

          case 'Escape': {
            event.preventDefault();
            this.close();
            // Return focus to trigger button
            const trigger = this.$refs.trigger as HTMLButtonElement;
            if (trigger) {
              trigger.focus();
            }
            break;
          }

          case 'Tab':
            // Allow default tab behavior but close dropdown
            this.close();
            break;
        }
      },

      highlightNext(this: SearchableSelectComponent) {
        if (this.highlightedIndex < this.filteredOptions.length - 1) {
          this.highlightedIndex++;
          this.scrollToHighlighted();
        }
      },

      highlightPrev(this: SearchableSelectComponent) {
        if (this.highlightedIndex > 0) {
          this.highlightedIndex--;
          this.scrollToHighlighted();
        }
      },

      selectHighlighted(this: SearchableSelectComponent) {
        const option = this.filteredOptions[this.highlightedIndex];
        if (option) {
          this.selectOption(option);
        }
      },

      scrollToHighlighted(this: SearchableSelectComponent) {
        this.$nextTick(() => {
          const container = this.$el?.querySelector('.searchable-select__options');
          const highlighted = container?.querySelector('.is-highlighted');
          if (container && highlighted) {
            highlighted.scrollIntoView({ block: 'nearest' });
          }
        });
      },

      async triggerChange(this: SearchableSelectComponent): Promise<void> {
        // Dispatch change event on hidden input for other listeners
        const hiddenInput = document.getElementById(this.id) as HTMLInputElement;
        if (hiddenInput) {
          hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Handle data-action behaviors
        if (this.dataAction === 'set-lang') {
          if (this.dataAjax) {
            try {
              const response = await setLangAsync(this.selectedValue);
              // Dispatch custom event for components to react to
              document.dispatchEvent(new CustomEvent('lwt:languageChanged', {
                detail: {
                  languageId: this.selectedValue,
                  languageName: this.selectedLabel,
                  response
                }
              }));
              // Redirect after AJAX completes
              if (this.dataRedirect) {
                window.location.href = this.dataRedirect;
              }
            } catch (error) {
              console.error('Failed to change language:', error);
            }
          } else if (this.dataRedirect) {
            window.location.href = this.dataRedirect;
          }
        }
        // For 'change-language' action, the change event on hidden input
        // will be caught by form_initialization.ts
      }
    } as SearchableSelectData;
  });
}

// Expose for global access
declare global {
  interface Window {
    initSearchableSelectAlpine: typeof initSearchableSelectAlpine;
  }
}

window.initSearchableSelectAlpine = initSearchableSelectAlpine;

// Register Alpine data component immediately
initSearchableSelectAlpine();

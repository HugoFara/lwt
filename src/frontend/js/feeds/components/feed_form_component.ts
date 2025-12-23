/**
 * Feed Form Alpine Component - Options toggling and serialization for feed edit/new forms.
 *
 * This component handles:
 * - Checkbox toggling to show/hide and enable/disable associated input fields
 * - Serializing options into a hidden field on form submission
 *
 * @license Unlicense
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

/**
 * Configuration for the feed form component, passed from PHP.
 */
export interface FeedFormConfig {
  editText?: boolean;
  autoUpdate?: boolean;
  autoUpdateValue?: string;
  autoUpdateUnit?: string;
  maxLinks?: boolean;
  maxLinksValue?: string;
  charset?: boolean;
  charsetValue?: string;
  maxTexts?: boolean;
  maxTextsValue?: string;
  tag?: boolean;
  tagValue?: string;
  articleSource?: boolean;
  articleSourceValue?: string;
}

/**
 * Feed form Alpine component data interface.
 */
export interface FeedFormData {
  // Option toggles
  editText: boolean;
  autoUpdate: boolean;
  maxLinks: boolean;
  charset: boolean;
  maxTexts: boolean;
  tag: boolean;
  articleSource: boolean;

  // Option values (bound to inputs via x-model)
  autoUpdateValue: string;
  autoUpdateUnit: string;
  maxLinksValue: string;
  charsetValue: string;
  maxTextsValue: string;
  tagValue: string;
  articleSourceValue: string;

  // Methods
  init(): void;
  serializeOptions(): string;
  handleSubmit(event: Event): void;
}

/**
 * Create the feed form Alpine component.
 *
 * @param config - Initial configuration from PHP
 * @returns Alpine component data object
 */
export function feedFormData(config: FeedFormConfig = {}): FeedFormData {
  return {
    // Option toggles - default to false except editText
    editText: config.editText ?? true,
    autoUpdate: config.autoUpdate ?? false,
    maxLinks: config.maxLinks ?? false,
    charset: config.charset ?? false,
    maxTexts: config.maxTexts ?? false,
    tag: config.tag ?? false,
    articleSource: config.articleSource ?? false,

    // Option values
    autoUpdateValue: config.autoUpdateValue ?? '',
    autoUpdateUnit: config.autoUpdateUnit ?? 'h',
    maxLinksValue: config.maxLinksValue ?? '',
    charsetValue: config.charsetValue ?? '',
    maxTextsValue: config.maxTextsValue ?? '',
    tagValue: config.tagValue ?? '',
    articleSourceValue: config.articleSourceValue ?? '',

    /**
     * Initialize the component.
     * Reads config from JSON script tag if not passed directly.
     */
    init(): void {
      // Try to read config from JSON script tag if available
      const configEl = document.getElementById('feed-form-config');
      if (configEl) {
        try {
          const jsonConfig = JSON.parse(configEl.textContent || '{}') as FeedFormConfig;
          // Merge JSON config with defaults
          this.editText = jsonConfig.editText ?? this.editText;
          this.autoUpdate = jsonConfig.autoUpdate ?? this.autoUpdate;
          this.maxLinks = jsonConfig.maxLinks ?? this.maxLinks;
          this.charset = jsonConfig.charset ?? this.charset;
          this.maxTexts = jsonConfig.maxTexts ?? this.maxTexts;
          this.tag = jsonConfig.tag ?? this.tag;
          this.articleSource = jsonConfig.articleSource ?? this.articleSource;

          this.autoUpdateValue = jsonConfig.autoUpdateValue ?? this.autoUpdateValue;
          this.autoUpdateUnit = jsonConfig.autoUpdateUnit ?? this.autoUpdateUnit;
          this.maxLinksValue = jsonConfig.maxLinksValue ?? this.maxLinksValue;
          this.charsetValue = jsonConfig.charsetValue ?? this.charsetValue;
          this.maxTextsValue = jsonConfig.maxTextsValue ?? this.maxTextsValue;
          this.tagValue = jsonConfig.tagValue ?? this.tagValue;
          this.articleSourceValue = jsonConfig.articleSourceValue ?? this.articleSourceValue;
        } catch {
          // Invalid JSON, use defaults
        }
      }
    },

    /**
     * Serialize feed options into a comma-separated string.
     * Format: "option_name=value,option_name2=value2,..."
     *
     * @returns Serialized options string
     */
    serializeOptions(): string {
      const parts: string[] = [];

      // Edit text option
      if (this.editText) {
        parts.push('edit_text=1');
      }

      // Auto update option (includes unit)
      if (this.autoUpdate && this.autoUpdateValue) {
        parts.push(`autoupdate=${this.autoUpdateValue}${this.autoUpdateUnit}`);
      }

      // Max links option
      if (this.maxLinks && this.maxLinksValue) {
        parts.push(`max_links=${this.maxLinksValue}`);
      }

      // Charset option
      if (this.charset && this.charsetValue) {
        parts.push(`charset=${this.charsetValue}`);
      }

      // Max texts option
      if (this.maxTexts && this.maxTextsValue) {
        parts.push(`max_texts=${this.maxTextsValue}`);
      }

      // Tag option
      if (this.tag && this.tagValue) {
        parts.push(`tag=${this.tagValue}`);
      }

      // Article source option
      if (this.articleSource && this.articleSourceValue) {
        parts.push(`article_source=${this.articleSourceValue}`);
      }

      return parts.join(',') + (parts.length > 0 ? ',' : '');
    },

    /**
     * Handle form submission - serialize options to hidden field.
     *
     * @param event - Submit event
     */
    handleSubmit(event: Event): void {
      const form = event.target as HTMLFormElement;
      const hiddenField = form.querySelector<HTMLInputElement>('input[name="NfOptions"]');
      if (hiddenField) {
        hiddenField.value = this.serializeOptions();
      }
    }
  };
}

/**
 * Initialize the feed form Alpine component.
 */
export function initFeedFormAlpine(): void {
  Alpine.data('feedForm', feedFormData);
}

// Register immediately (before Alpine.start())
initFeedFormAlpine();

// Export to window for debugging
declare global {
  interface Window {
    feedFormData: typeof feedFormData;
  }
}

window.feedFormData = feedFormData;

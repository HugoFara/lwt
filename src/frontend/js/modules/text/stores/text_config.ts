/**
 * Text Configuration Module - Provides access to current text settings.
 *
 * This module provides explicit functions to access text configuration.
 * Configuration must be initialized via initTextConfig() or initTextConfigFromDOM().
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.1.0
 */

export type AnnotationRecord = Record<string, [unknown, string, string]>;

export interface TextConfig {
  id: number;
  annotations: AnnotationRecord | number;
}

const defaultConfig: TextConfig = {
  id: 0,
  annotations: 0
};

let currentConfig: TextConfig = { ...defaultConfig };

/**
 * Initialize text configuration.
 *
 * @param config Text configuration
 */
export function initTextConfig(config: Partial<TextConfig>): void {
  currentConfig = { ...defaultConfig, ...config };
}

/**
 * Initialize text configuration from DOM data attributes.
 *
 * Looks for a #thetext element with data-text-* attributes.
 */
export function initTextConfigFromDOM(): void {
  const thetext = document.getElementById('thetext');
  if (!thetext) return;

  const config: Partial<TextConfig> = {};

  const textId = thetext.dataset.textId;
  if (textId) config.id = parseInt(textId, 10);

  // Annotations are typically loaded from JSON config, not data attributes
  initTextConfig(config);
}

/**
 * Get the current text ID.
 */
export function getTextId(): number {
  return currentConfig.id;
}

/**
 * Set the current text ID.
 */
export function setTextId(id: number): void {
  currentConfig.id = id;
}

/**
 * Get the annotations for the current text.
 */
export function getAnnotations(): AnnotationRecord | number {
  return currentConfig.annotations;
}

/**
 * Set the annotations for the current text.
 */
export function setAnnotations(annotations: AnnotationRecord | number): void {
  currentConfig.annotations = annotations;
}

/**
 * Check if annotations are available (not 0).
 */
export function hasAnnotations(): boolean {
  return currentConfig.annotations !== 0 &&
    typeof currentConfig.annotations === 'object';
}

/**
 * Get a specific annotation by key.
 *
 * @param key The annotation key (usually word order)
 * @returns The annotation tuple or undefined
 */
export function getAnnotation(key: string): [unknown, string, string] | undefined {
  if (typeof currentConfig.annotations === 'object') {
    return currentConfig.annotations[key];
  }
  return undefined;
}

/**
 * Reset to default configuration (for testing).
 */
export function resetTextConfig(): void {
  currentConfig = { ...defaultConfig };
}

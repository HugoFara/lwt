/**
 * Text Configuration Module - Provides access to current text settings.
 *
 * This module replaces direct LWT_DATA.text access with explicit functions.
 * For backward compatibility, getter functions fall back to reading from
 * the legacy LWT_DATA global when this module hasn't been initialized.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.1.0
 */

// Import LWT_DATA type from globals
import type { LwtData } from '../types/globals.d';

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
let isInitialized = false;

/**
 * Get text config from legacy LWT_DATA for backward compatibility.
 */
function getFromLegacy(): TextConfig {
  const lwtData = typeof window !== 'undefined' ? (window as { LWT_DATA?: LwtData }).LWT_DATA : undefined;
  if (lwtData?.text) {
    return {
      id: lwtData.text.id || 0,
      annotations: lwtData.text.annotations || 0
    };
  }
  return defaultConfig;
}

/**
 * Get the effective config (module state or legacy fallback).
 */
function getEffectiveConfig(): TextConfig {
  return isInitialized ? currentConfig : getFromLegacy();
}

/**
 * Initialize text configuration.
 *
 * @param config Text configuration
 */
export function initTextConfig(config: Partial<TextConfig>): void {
  currentConfig = { ...defaultConfig, ...config };
  isInitialized = true;
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
 * Falls back to LWT_DATA.text.id if not initialized.
 */
export function getTextId(): number {
  return getEffectiveConfig().id;
}

/**
 * Set the current text ID.
 */
export function setTextId(id: number): void {
  currentConfig.id = id;
  isInitialized = true;
}

/**
 * Get the annotations for the current text.
 * Falls back to LWT_DATA.text.annotations if not initialized.
 */
export function getAnnotations(): AnnotationRecord | number {
  return getEffectiveConfig().annotations;
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

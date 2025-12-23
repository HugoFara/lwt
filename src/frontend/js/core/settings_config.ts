/**
 * Settings Configuration Module - Provides access to application settings.
 *
 * This module provides explicit functions to access application settings.
 * Configuration must be initialized via initSettingsConfig().
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.1.0
 */

export interface SettingsConfig {
  /** Hover text-to-speech mode: 0=off, 2=hover, 3=click */
  hts: number;
  /** CSS class filter for word status visibility */
  wordStatusFilter: string;
  /** Annotation display mode (1-4) */
  annotationsMode: number;
  /** Use legacy frame-based navigation instead of API mode */
  useFrameMode: boolean;
}

const defaultConfig: SettingsConfig = {
  hts: 0,
  wordStatusFilter: '',
  annotationsMode: 1,
  useFrameMode: false
};

let currentConfig: SettingsConfig = { ...defaultConfig };

/**
 * Initialize settings configuration.
 *
 * @param config Settings configuration
 */
export function initSettingsConfig(config: Partial<SettingsConfig>): void {
  currentConfig = { ...defaultConfig, ...config };
}

/**
 * Get the hover text-to-speech mode.
 * 0 = off, 2 = speak on hover, 3 = speak on click
 */
export function getHtsMode(): number {
  return currentConfig.hts;
}

/**
 * Check if TTS should trigger on hover.
 */
export function isTtsOnHover(): boolean {
  return currentConfig.hts === 2;
}

/**
 * Check if TTS should trigger on click.
 */
export function isTtsOnClick(): boolean {
  return currentConfig.hts === 3;
}

/**
 * Get the word status filter CSS selector.
 */
export function getWordStatusFilter(): string {
  return currentConfig.wordStatusFilter;
}

/**
 * Get the annotation display mode.
 */
export function getAnnotationsMode(): number {
  return currentConfig.annotationsMode;
}

/**
 * Check if legacy frame mode is enabled.
 */
export function isFrameModeEnabled(): boolean {
  return currentConfig.useFrameMode;
}

/**
 * Check if API mode is enabled (default since v3.0.0).
 */
export function isApiModeEnabled(): boolean {
  return !currentConfig.useFrameMode;
}

/**
 * Get the full settings configuration.
 */
export function getSettingsConfig(): Readonly<SettingsConfig> {
  return { ...currentConfig };
}

/**
 * Reset to default configuration (for testing).
 */
export function resetSettingsConfig(): void {
  currentConfig = { ...defaultConfig };
}

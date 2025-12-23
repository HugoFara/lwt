/**
 * Settings Configuration Module - Provides access to application settings.
 *
 * This module replaces direct LWT_DATA.settings access with explicit functions.
 * For backward compatibility, getter functions fall back to reading from
 * the legacy LWT_DATA global when this module hasn't been initialized.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.1.0
 */

// Import LWT_DATA type from globals
import type { LwtData } from '../types/globals.d';

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
let isInitialized = false;

/**
 * Get settings config from legacy LWT_DATA for backward compatibility.
 */
function getFromLegacy(): SettingsConfig {
  const lwtData = typeof window !== 'undefined' ? (window as { LWT_DATA?: LwtData }).LWT_DATA : undefined;
  if (lwtData?.settings) {
    const settings = lwtData.settings as LwtData['settings'] & { use_frame_mode?: boolean };
    return {
      hts: settings.hts || 0,
      wordStatusFilter: settings.word_status_filter || '',
      annotationsMode: settings.annotations_mode || 1,
      useFrameMode: settings.use_frame_mode || false
    };
  }
  return defaultConfig;
}

/**
 * Get the effective config (module state or legacy fallback).
 */
function getEffectiveConfig(): SettingsConfig {
  return isInitialized ? currentConfig : getFromLegacy();
}

/**
 * Initialize settings configuration.
 *
 * @param config Settings configuration
 */
export function initSettingsConfig(config: Partial<SettingsConfig>): void {
  currentConfig = { ...defaultConfig, ...config };
  isInitialized = true;
}

/**
 * Get the hover text-to-speech mode.
 * 0 = off, 2 = speak on hover, 3 = speak on click
 * Falls back to LWT_DATA.settings.hts if not initialized.
 */
export function getHtsMode(): number {
  return getEffectiveConfig().hts;
}

/**
 * Check if TTS should trigger on hover.
 * Falls back to LWT_DATA.settings.hts if not initialized.
 */
export function isTtsOnHover(): boolean {
  return getEffectiveConfig().hts === 2;
}

/**
 * Check if TTS should trigger on click.
 * Falls back to LWT_DATA.settings.hts if not initialized.
 */
export function isTtsOnClick(): boolean {
  return getEffectiveConfig().hts === 3;
}

/**
 * Get the word status filter CSS selector.
 * Falls back to LWT_DATA.settings.word_status_filter if not initialized.
 */
export function getWordStatusFilter(): string {
  return getEffectiveConfig().wordStatusFilter;
}

/**
 * Get the annotation display mode.
 * Falls back to LWT_DATA.settings.annotations_mode if not initialized.
 */
export function getAnnotationsMode(): number {
  return getEffectiveConfig().annotationsMode;
}

/**
 * Check if legacy frame mode is enabled.
 * Falls back to LWT_DATA.settings.use_frame_mode if not initialized.
 */
export function isFrameModeEnabled(): boolean {
  return getEffectiveConfig().useFrameMode;
}

/**
 * Check if API mode is enabled (default since v3.0.0).
 * Falls back to LWT_DATA.settings.use_frame_mode if not initialized.
 */
export function isApiModeEnabled(): boolean {
  return !getEffectiveConfig().useFrameMode;
}

/**
 * Get the full settings configuration.
 * Falls back to LWT_DATA.settings if not initialized.
 */
export function getSettingsConfig(): Readonly<SettingsConfig> {
  return { ...getEffectiveConfig() };
}

/**
 * Reset to default configuration (for testing).
 */
export function resetSettingsConfig(): void {
  currentConfig = { ...defaultConfig };
}

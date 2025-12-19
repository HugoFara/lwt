/**
 * TTS localStorage utilities for LWT.
 *
 * Stores Text-to-Speech settings (voice, rate, pitch) per language
 * in localStorage instead of cookies.
 *
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { deleteCookie } from './cookies';

/**
 * Storage key prefix for TTS settings.
 */
const TTS_STORAGE_PREFIX = 'lwt.tts.';

/**
 * TTS settings for a specific language.
 */
export interface TTSLanguageSettings {
  voice?: string;
  rate?: number;
  pitch?: number;
}

/**
 * Get the localStorage key for a language's TTS settings.
 *
 * @param language Language code (e.g., "en", "fr")
 * @returns Full storage key
 */
function getStorageKey(language: string): string {
  return TTS_STORAGE_PREFIX + language;
}

/**
 * Get TTS settings for a specific language from localStorage.
 *
 * @param language Language code (e.g., "en", "fr")
 * @returns TTS settings object, or empty object if none stored
 */
export function getTTSSettings(language: string): TTSLanguageSettings {
  const key = getStorageKey(language);
  const stored = localStorage.getItem(key);
  if (!stored) {
    return {};
  }
  try {
    return JSON.parse(stored) as TTSLanguageSettings;
  } catch {
    return {};
  }
}

/**
 * Save TTS settings for a specific language to localStorage.
 *
 * @param language Language code (e.g., "en", "fr")
 * @param settings TTS settings to save
 */
export function saveTTSSettings(language: string, settings: TTSLanguageSettings): void {
  const key = getStorageKey(language);
  localStorage.setItem(key, JSON.stringify(settings));
}

/**
 * Update specific TTS settings for a language (merge with existing).
 *
 * @param language Language code (e.g., "en", "fr")
 * @param updates Partial settings to update
 */
export function updateTTSSettings(language: string, updates: Partial<TTSLanguageSettings>): void {
  const existing = getTTSSettings(language);
  saveTTSSettings(language, { ...existing, ...updates });
}

/**
 * Clear TTS settings for a specific language.
 *
 * @param language Language code (e.g., "en", "fr")
 */
export function clearTTSSettings(language: string): void {
  const key = getStorageKey(language);
  localStorage.removeItem(key);
}

/**
 * Get all stored TTS language codes.
 *
 * @returns Array of language codes that have stored TTS settings
 */
export function getAllTTSLanguages(): string[] {
  const languages: string[] = [];
  for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);
    if (key?.startsWith(TTS_STORAGE_PREFIX)) {
      languages.push(key.substring(TTS_STORAGE_PREFIX.length));
    }
  }
  return languages;
}

/**
 * Migration flag key in localStorage.
 */
const MIGRATION_FLAG = 'lwt.tts.migrated';

/**
 * Check if TTS settings have already been migrated from cookies.
 *
 * @returns true if migration has been completed
 */
export function isMigrationComplete(): boolean {
  return localStorage.getItem(MIGRATION_FLAG) === '1';
}

/**
 * Mark migration as complete.
 */
function setMigrationComplete(): void {
  localStorage.setItem(MIGRATION_FLAG, '1');
}

/**
 * Migrate TTS settings from cookies to localStorage.
 *
 * Old cookie format:
 * - tts[{lang}Voice] - voice name
 * - tts[{lang}Rate] - rate value
 * - tts[{lang}Pitch] - pitch value
 * - tts[{lang}RegName] - alternative voice name key
 *
 * This function detects existing TTS cookies, migrates them to localStorage,
 * and removes the old cookies.
 *
 * @returns Number of languages migrated
 */
export function migrateTTSCookies(): number {
  if (isMigrationComplete()) {
    return 0;
  }

  // Parse all cookies to find TTS-related ones
  const cookies = document.cookie.split(';');
  const ttsData: Record<string, TTSLanguageSettings> = {};

  for (const cookie of cookies) {
    const [nameRaw, valueRaw] = cookie.split('=');
    if (!nameRaw || !valueRaw) continue;

    const name = nameRaw.trim();
    const value = decodeURIComponent(valueRaw.trim());

    // Match tts[{lang}{Setting}] pattern
    const match = name.match(/^tts\[([a-z]{2})(\w+)\]$/i);
    if (!match) continue;

    const lang = match[1].toLowerCase();
    const setting = match[2];

    if (!ttsData[lang]) {
      ttsData[lang] = {};
    }

    // Map old cookie names to new settings
    if (setting === 'Voice' || setting === 'RegName') {
      ttsData[lang].voice = value;
    } else if (setting === 'Rate') {
      const rate = parseFloat(value);
      if (!isNaN(rate)) {
        ttsData[lang].rate = rate;
      }
    } else if (setting === 'Pitch') {
      const pitch = parseFloat(value);
      if (!isNaN(pitch)) {
        ttsData[lang].pitch = pitch;
      }
    }
  }

  // Save to localStorage and delete cookies
  let migratedCount = 0;
  for (const lang in ttsData) {
    const settings = ttsData[lang];
    // Only save if there's at least one setting
    if (settings.voice || settings.rate !== undefined || settings.pitch !== undefined) {
      saveTTSSettings(lang, settings);
      migratedCount++;
    }

    // Delete old cookies
    deleteCookie('tts[' + lang + 'Voice]', '/', '');
    deleteCookie('tts[' + lang + 'RegName]', '/', '');
    deleteCookie('tts[' + lang + 'Rate]', '/', '');
    deleteCookie('tts[' + lang + 'Pitch]', '/', '');
  }

  setMigrationComplete();
  return migratedCount;
}

/**
 * Get TTS settings, with automatic migration from cookies if needed.
 *
 * This is the recommended function to use for reading TTS settings.
 * It will automatically migrate from cookies on first access.
 *
 * @param language Language code (e.g., "en", "fr")
 * @returns TTS settings object
 */
export function getTTSSettingsWithMigration(language: string): TTSLanguageSettings {
  // Ensure migration has run
  if (!isMigrationComplete()) {
    migrateTTSCookies();
  }
  return getTTSSettings(language);
}

// Expose functions globally for potential use in inline scripts
declare global {
  interface Window {
    getTTSSettings: typeof getTTSSettings;
    saveTTSSettings: typeof saveTTSSettings;
    migrateTTSCookies: typeof migrateTTSCookies;
  }
}

window.getTTSSettings = getTTSSettings;
window.saveTTSSettings = saveTTSSettings;
window.migrateTTSCookies = migrateTTSCookies;

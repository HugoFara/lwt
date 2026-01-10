/**
 * Offline Text Service for LWT PWA.
 *
 * Handles downloading, storing, and retrieving texts for offline reading.
 *
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import {
  offlineDb,
  type OfflineText,
  type OfflineTextWords,
  type OfflineLanguage,
  isIndexedDBAvailable,
  setMetadata,
  getMetadata,
} from './db';
import { TextsApi, type TextWordsResponse, type TextReadingConfig, type TextWord } from '@modules/text/api/texts_api';
import { apiGet } from '@shared/api/client';

// =============================================================================
// TYPES
// =============================================================================

/**
 * Result of checking if a text is available offline.
 */
export interface OfflineAvailability {
  available: boolean;
  downloadedAt?: Date;
  sizeBytes?: number;
}

/**
 * Download progress callback.
 */
export type DownloadProgressCallback = (progress: number, message: string) => void;

/**
 * Summary of offline texts by language.
 */
export interface OfflineSummary {
  totalTexts: number;
  totalSizeBytes: number;
  byLanguage: Array<{
    langId: number;
    langName: string;
    textCount: number;
    sizeBytes: number;
  }>;
}

/**
 * Language API response type.
 */
interface LanguageResponse {
  id: number;
  name: string;
  abbreviation: string;
  rightToLeft: boolean;
  removeSpaces: boolean;
  dict1Link: string;
  dict2Link: string;
  translatorLink: string;
  textSize: number;
}

// =============================================================================
// SERVICE FUNCTIONS
// =============================================================================

/**
 * Check if offline storage is available.
 */
export function isOfflineStorageAvailable(): boolean {
  return isIndexedDBAvailable();
}

/**
 * Check if a text is available offline.
 */
export async function isTextAvailableOffline(textId: number): Promise<OfflineAvailability> {
  if (!isIndexedDBAvailable()) {
    return { available: false };
  }

  const text = await offlineDb.texts.get(textId);
  if (!text) {
    return { available: false };
  }

  return {
    available: true,
    downloadedAt: text.downloadedAt,
    sizeBytes: text.sizeBytes,
  };
}

/**
 * Get list of all offline text IDs.
 */
export async function getOfflineTextIds(): Promise<number[]> {
  if (!isIndexedDBAvailable()) {
    return [];
  }

  const texts = await offlineDb.texts.toArray();
  return texts.map(t => t.id);
}

/**
 * Download a text for offline reading.
 *
 * @param textId Text ID to download
 * @param onProgress Optional progress callback
 * @returns Promise that resolves when download is complete
 */
export async function downloadTextForOffline(
  textId: number,
  onProgress?: DownloadProgressCallback
): Promise<void> {
  if (!isIndexedDBAvailable()) {
    throw new Error('Offline storage is not available');
  }

  onProgress?.(0, 'Fetching text data...');

  // Fetch text words and config from API
  const response = await TextsApi.getWords(textId);
  if (response.error || !response.data) {
    throw new Error(response.error || 'Failed to fetch text data');
  }

  const { words, config } = response.data;

  onProgress?.(30, 'Fetching language data...');

  // Ensure language is cached
  await ensureLanguageCached(config.langId);

  onProgress?.(50, 'Saving to offline storage...');

  // Calculate approximate size
  const sizeBytes = estimateSize(words, config);

  // Save text metadata
  const offlineText: OfflineText = {
    id: textId,
    langId: config.langId,
    title: config.title,
    audioUri: config.audioUri,
    sourceUri: config.sourceUri,
    config,
    downloadedAt: new Date(),
    lastAccessedAt: new Date(),
    sizeBytes,
  };

  // Save words
  const offlineWords: OfflineTextWords = {
    textId,
    words,
    syncedAt: new Date(),
  };

  onProgress?.(70, 'Writing to database...');

  // Use transaction for atomicity
  await offlineDb.transaction('rw', [offlineDb.texts, offlineDb.textWords], async () => {
    await offlineDb.texts.put(offlineText);
    await offlineDb.textWords.put(offlineWords);
  });

  // Update last sync time
  await setMetadata('lastTextDownload', new Date().toISOString());

  onProgress?.(100, 'Download complete');
}

/**
 * Remove a text from offline storage.
 */
export async function removeTextFromOffline(textId: number): Promise<void> {
  if (!isIndexedDBAvailable()) {
    return;
  }

  await offlineDb.transaction('rw', [offlineDb.texts, offlineDb.textWords], async () => {
    await offlineDb.texts.delete(textId);
    await offlineDb.textWords.delete(textId);
  });
}

/**
 * Get text data from offline storage.
 *
 * @param textId Text ID to retrieve
 * @returns Text words response if available, null otherwise
 */
export async function getOfflineTextData(textId: number): Promise<TextWordsResponse | null> {
  if (!isIndexedDBAvailable()) {
    return null;
  }

  const text = await offlineDb.texts.get(textId);
  const wordsRecord = await offlineDb.textWords.get(textId);

  if (!text || !wordsRecord) {
    return null;
  }

  // Update last accessed time
  await offlineDb.texts.update(textId, {
    lastAccessedAt: new Date(),
  });

  return {
    config: text.config,
    words: wordsRecord.words,
  };
}

/**
 * Get offline text metadata (without words).
 */
export async function getOfflineTextMeta(textId: number): Promise<OfflineText | null> {
  if (!isIndexedDBAvailable()) {
    return null;
  }

  const text = await offlineDb.texts.get(textId);
  return text || null;
}

/**
 * Get all offline texts for a language.
 */
export async function getOfflineTextsByLanguage(langId: number): Promise<OfflineText[]> {
  if (!isIndexedDBAvailable()) {
    return [];
  }

  return offlineDb.texts.where('langId').equals(langId).toArray();
}

/**
 * Get summary of offline storage.
 */
export async function getOfflineSummary(): Promise<OfflineSummary> {
  if (!isIndexedDBAvailable()) {
    return { totalTexts: 0, totalSizeBytes: 0, byLanguage: [] };
  }

  const texts = await offlineDb.texts.toArray();
  const languages = await offlineDb.languages.toArray();

  const langMap = new Map(languages.map(l => [l.id, l.name]));

  // Group by language
  const byLang = new Map<number, { count: number; size: number }>();
  for (const text of texts) {
    const current = byLang.get(text.langId) || { count: 0, size: 0 };
    current.count++;
    current.size += text.sizeBytes;
    byLang.set(text.langId, current);
  }

  return {
    totalTexts: texts.length,
    totalSizeBytes: texts.reduce((sum, t) => sum + t.sizeBytes, 0),
    byLanguage: Array.from(byLang.entries()).map(([langId, data]) => ({
      langId,
      langName: langMap.get(langId) || `Language ${langId}`,
      textCount: data.count,
      sizeBytes: data.size,
    })),
  };
}

/**
 * Ensure a language is cached for offline use.
 */
export async function ensureLanguageCached(langId: number): Promise<void> {
  if (!isIndexedDBAvailable()) {
    return;
  }

  // Check if already cached
  const existing = await offlineDb.languages.get(langId);
  if (existing) {
    return;
  }

  // Fetch from API
  const response = await apiGet<LanguageResponse>(`/languages/${langId}`);
  if (response.error || !response.data) {
    console.warn(`Failed to cache language ${langId}`);
    return;
  }

  const lang = response.data;
  const offlineLang: OfflineLanguage = {
    id: lang.id,
    name: lang.name,
    abbreviation: lang.abbreviation,
    rightToLeft: lang.rightToLeft,
    removeSpaces: lang.removeSpaces,
    dictLinks: {
      dict1: lang.dict1Link,
      dict2: lang.dict2Link,
      translator: lang.translatorLink,
    },
    textSize: lang.textSize,
    downloadedAt: new Date(),
  };

  await offlineDb.languages.put(offlineLang);
}

/**
 * Get cached language data.
 */
export async function getOfflineLanguage(langId: number): Promise<OfflineLanguage | null> {
  if (!isIndexedDBAvailable()) {
    return null;
  }

  const lang = await offlineDb.languages.get(langId);
  return lang || null;
}

/**
 * Check if we're currently offline.
 */
export function isCurrentlyOffline(): boolean {
  return !navigator.onLine;
}

/**
 * Get last sync timestamp.
 */
export async function getLastSyncTime(): Promise<Date | null> {
  const timestamp = await getMetadata<string>('lastTextDownload');
  return timestamp ? new Date(timestamp) : null;
}

// =============================================================================
// INTERNAL HELPERS
// =============================================================================

/**
 * Estimate the size of text data in bytes.
 */
function estimateSize(words: TextWord[], config: TextReadingConfig): number {
  // Rough estimate: stringify and get length
  const wordsJson = JSON.stringify(words);
  const configJson = JSON.stringify(config);
  return wordsJson.length + configJson.length;
}

// =============================================================================
// EXPORTS
// =============================================================================

export type { TextWordsResponse, TextReadingConfig, TextWord };

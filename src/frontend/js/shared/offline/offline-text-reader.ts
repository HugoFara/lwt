/**
 * Offline-Aware Text Reader for LWT PWA.
 *
 * Provides offline-first text reading by checking IndexedDB before
 * making network requests.
 *
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import {
  isOfflineStorageAvailable,
  isTextAvailableOffline,
  getOfflineTextData,
} from './text-service';
import { TextsApi, type TextWordsResponse } from '@modules/text/api/texts_api';

// =============================================================================
// TYPES
// =============================================================================

/**
 * Result of fetching text data with source information.
 */
export interface TextDataResult {
  data: TextWordsResponse;
  source: 'offline' | 'network';
  offlineAvailable: boolean;
}

// =============================================================================
// FUNCTIONS
// =============================================================================

/**
 * Get text words with offline fallback.
 *
 * This function implements an offline-first strategy:
 * 1. If offline and text is cached, return cached data
 * 2. If online, try network first
 * 3. If network fails and text is cached, return cached data
 * 4. Otherwise, throw error
 *
 * @param textId Text ID to fetch
 * @returns Promise with text data and source information
 */
export async function getTextWordsOfflineFirst(textId: number): Promise<TextDataResult> {
  const offlineAvailable = isOfflineStorageAvailable();
  const isOffline = !navigator.onLine;

  // Check offline availability
  let hasOfflineData = false;
  if (offlineAvailable) {
    const availability = await isTextAvailableOffline(textId);
    hasOfflineData = availability.available;
  }

  // If offline, use cached data
  if (isOffline) {
    if (hasOfflineData) {
      const offlineData = await getOfflineTextData(textId);
      if (offlineData) {
        return {
          data: offlineData,
          source: 'offline',
          offlineAvailable: true,
        };
      }
    }
    throw new Error('Text not available offline. Download it first while online.');
  }

  // Online: try network first
  try {
    const response = await TextsApi.getWords(textId);
    if (!response.error && response.data) {
      return {
        data: response.data,
        source: 'network',
        offlineAvailable: hasOfflineData,
      };
    }
    throw new Error(response.error || 'Failed to fetch text');
  } catch (error) {
    // Network failed, try offline fallback
    if (hasOfflineData) {
      const offlineData = await getOfflineTextData(textId);
      if (offlineData) {
        console.warn('Network failed, using offline data');
        return {
          data: offlineData,
          source: 'offline',
          offlineAvailable: true,
        };
      }
    }
    throw error;
  }
}

/**
 * Check if a text can be read (either online or has offline data).
 *
 * @param textId Text ID to check
 * @returns Promise resolving to true if text is readable
 */
export async function canReadText(textId: number): Promise<boolean> {
  // If online, always can read
  if (navigator.onLine) {
    return true;
  }

  // If offline, check if we have cached data
  if (isOfflineStorageAvailable()) {
    const availability = await isTextAvailableOffline(textId);
    return availability.available;
  }

  return false;
}

/**
 * Get a list of readable text IDs when offline.
 *
 * @returns Promise with array of text IDs that can be read offline
 */
export async function getReadableOfflineTextIds(): Promise<number[]> {
  if (!isOfflineStorageAvailable()) {
    return [];
  }

  const { offlineDb } = await import('./db');
  const texts = await offlineDb.texts.toArray();
  return texts.map(t => t.id);
}

// =============================================================================
// EXPORTS
// =============================================================================

export type { TextWordsResponse };

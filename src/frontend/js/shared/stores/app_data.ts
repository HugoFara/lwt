/**
 * Application Data - Centralized data fetching for LWT.
 *
 * This module provides centralized access to application data that was
 * previously passed via inline PHP scripts (STATUSES, TAGS, TEXTTAGS).
 *
 * - STATUSES: re-exported from the canonical status store (`./statuses`)
 * - TAGS/TEXTTAGS: Fetched from API on demand with caching
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

// Word statuses now live in the canonical status store; re-exported here so
// existing `from '@shared/stores/app_data'` imports keep working.
export { statuses } from './statuses';

// Cache for tags data
let termTagsCache: string[] | null = null;
let textTagsCache: string[] | null = null;

/**
 * Fetch term tags from the API.
 * Results are cached after first fetch.
 *
 * @param refresh Force refresh from API even if cached
 * @returns Promise resolving to array of term tag strings
 */
export async function fetchTermTags(refresh = false): Promise<string[]> {
  if (!refresh && termTagsCache !== null) {
    return termTagsCache;
  }

  try {
    const response = await fetch('/api/v1/tags/term');
    if (!response.ok) {
      console.error('Failed to fetch term tags:', response.statusText);
      return termTagsCache ?? [];
    }
    termTagsCache = await response.json();
    return termTagsCache ?? [];
  } catch (error) {
    console.error('Error fetching term tags:', error);
    return termTagsCache ?? [];
  }
}

/**
 * Fetch text tags from the API.
 * Results are cached after first fetch.
 *
 * @param refresh Force refresh from API even if cached
 * @returns Promise resolving to array of text tag strings
 */
export async function fetchTextTags(refresh = false): Promise<string[]> {
  if (!refresh && textTagsCache !== null) {
    return textTagsCache;
  }

  try {
    const response = await fetch('/api/v1/tags/text');
    if (!response.ok) {
      console.error('Failed to fetch text tags:', response.statusText);
      return textTagsCache ?? [];
    }
    textTagsCache = await response.json();
    return textTagsCache ?? [];
  } catch (error) {
    console.error('Error fetching text tags:', error);
    return textTagsCache ?? [];
  }
}

/**
 * Get cached term tags synchronously.
 * Returns empty array if not yet fetched.
 *
 * @returns Cached term tags or empty array
 */
export function getTermTagsSync(): string[] {
  return termTagsCache ?? [];
}

/**
 * Get cached text tags synchronously.
 * Returns empty array if not yet fetched.
 *
 * @returns Cached text tags or empty array
 */
export function getTextTagsSync(): string[] {
  return textTagsCache ?? [];
}

/**
 * Clear all tag caches.
 * Useful when tags are modified and need to be refreshed.
 */
export function clearTagCaches(): void {
  termTagsCache = null;
  textTagsCache = null;
}

/**
 * Initialize tags data by pre-fetching from API.
 * Should be called on page load for pages that need tags.
 *
 * @returns Promise that resolves when both tag types are fetched
 */
export async function initTagsData(): Promise<void> {
  await Promise.all([
    fetchTermTags(),
    fetchTextTags()
  ]);
}


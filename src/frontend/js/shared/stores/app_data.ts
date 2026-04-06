/**
 * Application Data - Centralized data fetching for LWT.
 *
 * This module provides centralized access to application data that was
 * previously passed via inline PHP scripts (STATUSES, TAGS, TEXTTAGS).
 *
 * - STATUSES: Static data hardcoded here (never changes)
 * - TAGS/TEXTTAGS: Fetched from API on demand with caching
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import type { WordStatus } from '@/types/globals';
import { t } from '@shared/i18n/translator';

/**
 * Word statuses — localized labels.
 *
 * Numeric statuses use language-neutral digit abbreviations ("1".."5").
 * For 98/99 there is no good cross-language abbreviation, so the localized
 * full name doubles as both `name` and `abbr`.
 *
 * This is a getter (Proxy) rather than a static object so the translator
 * has time to initialize before the strings are read.
 */
export const statuses: Record<number, WordStatus> = new Proxy({} as Record<number, WordStatus>, {
  get(_target, prop: string | symbol): WordStatus | undefined {
    const key = Number(prop);
    if (Number.isNaN(key)) return undefined;
    const learning = t('common.status_learning');
    const learned = t('common.status_learned');
    const wellKnown = t('common.status_well_known');
    const ignored = t('common.status_ignored');
    switch (key) {
      case 1: return { abbr: '1', name: learning };
      case 2: return { abbr: '2', name: learning };
      case 3: return { abbr: '3', name: learning };
      case 4: return { abbr: '4', name: learning };
      case 5: return { abbr: '5', name: learned };
      case 99: return { abbr: wellKnown, name: wellKnown };
      case 98: return { abbr: ignored, name: ignored };
      default: return undefined;
    }
  },
  ownKeys(): string[] {
    return ['1', '2', '3', '4', '5', '98', '99'];
  },
  getOwnPropertyDescriptor(): PropertyDescriptor {
    return { enumerable: true, configurable: true };
  },
  has(_target, prop: string | symbol): boolean {
    const key = Number(prop);
    return [1, 2, 3, 4, 5, 98, 99].includes(key);
  }
});

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


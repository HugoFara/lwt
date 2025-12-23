/**
 * Local Dictionary API - Type-safe wrapper for local dictionary lookups.
 *
 * Provides functions for looking up terms in local dictionaries and
 * rendering the results in an inline panel.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { apiGet, type ApiResponse } from '../core/api_client';

/**
 * Result from a local dictionary lookup.
 */
export interface LocalDictResult {
  term: string;
  definition: string;
  reading: string | null;
  pos: string | null;
  dictionary: string;
}

/**
 * Response from the local dictionary lookup API.
 */
export interface LocalDictLookupResponse {
  results: LocalDictResult[];
  mode: number;
}

/**
 * Dictionary mode values.
 */
export const DictionaryMode = {
  ONLINE_ONLY: 0,
  LOCAL_FIRST: 1,
  LOCAL_ONLY: 2,
  COMBINED: 3
} as const;

export type DictionaryModeValue = typeof DictionaryMode[keyof typeof DictionaryMode];

/**
 * Cache for dictionary mode per language to avoid repeated API calls.
 */
const modeCache = new Map<number, number>();

/**
 * Look up a term in local dictionaries for a language.
 *
 * @param langId Language ID
 * @param term   Term to look up
 * @returns Promise with lookup results
 */
export async function lookupLocal(
  langId: number,
  term: string
): Promise<ApiResponse<LocalDictLookupResponse>> {
  return apiGet<LocalDictLookupResponse>(
    `/local-dictionaries/lookup`,
    { lang_id: langId, term }
  );
}

/**
 * Get the local dictionary mode for a language.
 *
 * @param langId Language ID
 * @returns Dictionary mode (0-3)
 */
export async function getLocalDictMode(langId: number): Promise<number> {
  // Check cache first
  if (modeCache.has(langId)) {
    return modeCache.get(langId)!;
  }

  // Fetch from API - we only need the mode, so lookup empty term
  const response = await apiGet<LocalDictLookupResponse>(
    `/local-dictionaries/lookup`,
    { lang_id: langId, term: '__mode_check__' }
  );

  const mode = response.data?.mode ?? DictionaryMode.ONLINE_ONLY;
  modeCache.set(langId, mode);

  return mode;
}

/**
 * Clear the mode cache for a language (e.g., after settings change).
 *
 * @param langId Language ID (optional - clears all if not specified)
 */
export function clearModeCache(langId?: number): void {
  if (langId !== undefined) {
    modeCache.delete(langId);
  } else {
    modeCache.clear();
  }
}

/**
 * Check if local dictionaries are enabled for a language.
 *
 * @param langId Language ID
 * @returns True if local dictionaries should be used
 */
export async function hasLocalDictionaries(langId: number): Promise<boolean> {
  const mode = await getLocalDictMode(langId);
  return mode !== DictionaryMode.ONLINE_ONLY;
}

/**
 * Check if online dictionaries should be queried for a language.
 *
 * @param langId Language ID
 * @param localResults Whether local results were found
 * @returns True if online dictionaries should be used
 */
export async function shouldUseOnline(
  langId: number,
  localResults: boolean = false
): Promise<boolean> {
  const mode = await getLocalDictMode(langId);

  switch (mode) {
    case DictionaryMode.LOCAL_ONLY:
      return false;
    case DictionaryMode.LOCAL_FIRST:
      return !localResults; // Only use online if no local results
    case DictionaryMode.COMBINED:
    case DictionaryMode.ONLINE_ONLY:
    default:
      return true;
  }
}

/**
 * Format a local dictionary result for display.
 *
 * @param result Dictionary result
 * @param showDictName Whether to show dictionary name
 * @returns Formatted HTML string
 */
export function formatResult(result: LocalDictResult, showDictName = true): string {
  let html = '<div class="local-dict-entry">';

  // Term with reading
  html += '<div class="local-dict-term">';
  html += `<span class="local-dict-headword">${escapeHtml(result.term)}</span>`;
  if (result.reading) {
    html += ` <span class="local-dict-reading">[${escapeHtml(result.reading)}]</span>`;
  }
  if (result.pos) {
    html += ` <span class="local-dict-pos">(${escapeHtml(result.pos)})</span>`;
  }
  html += '</div>';

  // Definition
  html += `<div class="local-dict-definition">${escapeHtml(result.definition)}</div>`;

  // Dictionary name
  if (showDictName) {
    html += `<div class="local-dict-source">— ${escapeHtml(result.dictionary)}</div>`;
  }

  html += '</div>';
  return html;
}

/**
 * Format multiple results for display in a panel.
 *
 * @param results Dictionary results
 * @returns Formatted HTML string
 */
export function formatResults(results: LocalDictResult[]): string {
  if (results.length === 0) {
    return '<div class="local-dict-empty">No local dictionary results found.</div>';
  }

  return results.map(r => formatResult(r, results.length > 1)).join('<hr class="local-dict-separator">');
}

/**
 * Escape HTML special characters.
 */
function escapeHtml(text: string): string {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

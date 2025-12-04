/**
 * Feeds API client for SPA feed management.
 *
 * Provides typed functions to interact with the feeds REST API.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { apiGet, apiPost, apiPut, apiDelete, ApiResponse } from '../core/api_client';

// ============================================================================
// Types
// ============================================================================

/**
 * Language option for dropdown selects.
 */
export interface Language {
  id: number;
  name: string;
}

/**
 * Feed options parsed from the options string.
 */
export interface FeedOptions {
  edit_text?: string;
  autoupdate?: string;
  max_links?: string;
  max_texts?: string;
  charset?: string;
  tag?: string;
  article_source?: string;
}

/**
 * Feed entity from API.
 */
export interface Feed {
  id: number;
  name: string;
  sourceUri: string;
  langId: number;
  langName: string;
  articleSectionTags: string;
  filterTags: string;
  options: FeedOptions;
  optionsString: string;
  updateTimestamp: number;
  lastUpdate: string;
  articleCount: number;
}

/**
 * Article/feed link entity from API.
 */
export interface Article {
  id: number;
  title: string;
  link: string;
  description: string;
  date: string;
  audio: string;
  hasText: boolean;
  status: 'new' | 'imported' | 'archived' | 'error';
  textId: number | null;
  archivedTextId: number | null;
}

/**
 * Pagination info from API.
 */
export interface Pagination {
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

/**
 * Response for feed list endpoint.
 */
export interface FeedListResponse {
  feeds: Feed[];
  pagination: Pagination;
  languages: Language[];
}

/**
 * Response for articles endpoint.
 */
export interface ArticlesResponse {
  articles: Article[];
  pagination: Pagination;
  feed: {
    id: number;
    name: string;
    langId: number;
  };
}

/**
 * Parameters for fetching feed list.
 */
export interface FeedListParams {
  lang?: number | '';
  query?: string;
  page?: number;
  per_page?: number;
  sort?: number;
}

/**
 * Parameters for fetching articles.
 */
export interface ArticleParams {
  feed_id: number;
  query?: string;
  page?: number;
  per_page?: number;
  sort?: number;
}

/**
 * Data for creating/updating a feed.
 */
export interface FeedData {
  langId: number;
  name: string;
  sourceUri: string;
  articleSectionTags?: string;
  filterTags?: string;
  options?: string;
}

/**
 * Response for feed load operation.
 */
export interface LoadFeedResponse {
  success?: boolean;
  message?: string;
  imported?: number;
  duplicates?: number;
  error?: string;
}

/**
 * Response for import articles operation.
 */
export interface ImportResponse {
  success: boolean;
  imported: number;
  errors: string[];
}

// ============================================================================
// API Functions
// ============================================================================

/**
 * Get paginated list of feeds with filtering.
 */
export async function getFeeds(
  params: FeedListParams = {}
): Promise<ApiResponse<FeedListResponse>> {
  return apiGet<FeedListResponse>('/feeds/list', params as Record<string, string | number | boolean>);
}

/**
 * Get a single feed by ID.
 */
export async function getFeed(feedId: number): Promise<ApiResponse<Feed>> {
  return apiGet<Feed>(`/feeds/${feedId}`);
}

/**
 * Create a new feed.
 */
export async function createFeed(
  data: FeedData
): Promise<ApiResponse<{ success: boolean; feed: Feed; error?: string }>> {
  return apiPost('/feeds', data as unknown as Record<string, unknown>);
}

/**
 * Update an existing feed.
 */
export async function updateFeed(
  feedId: number,
  data: Partial<FeedData>
): Promise<ApiResponse<{ success: boolean; feed: Feed; error?: string }>> {
  return apiPut(`/feeds/${feedId}`, data as unknown as Record<string, unknown>);
}

/**
 * Delete a single feed.
 */
export async function deleteFeed(
  feedId: number
): Promise<ApiResponse<{ success: boolean; deleted: number }>> {
  return apiDelete(`/feeds/${feedId}`);
}

/**
 * Delete multiple feeds.
 */
export async function deleteFeeds(
  feedIds: number[]
): Promise<ApiResponse<{ success: boolean; deleted: number }>> {
  return apiDelete('/feeds');
}

/**
 * Load/update a feed (fetch RSS and import articles).
 */
export async function loadFeed(
  feedId: number,
  name: string,
  sourceUri: string,
  options: string
): Promise<ApiResponse<LoadFeedResponse>> {
  return apiPost(`/feeds/${feedId}/load`, {
    name,
    source_uri: sourceUri,
    options
  });
}

/**
 * Get articles for a feed.
 */
export async function getArticles(
  params: ArticleParams
): Promise<ApiResponse<ArticlesResponse>> {
  return apiGet<ArticlesResponse>('/feeds/articles', params as unknown as Record<string, string | number | boolean>);
}

/**
 * Delete articles from a feed.
 */
export async function deleteArticles(
  feedId: number,
  articleIds: number[] = []
): Promise<ApiResponse<{ success: boolean; deleted: number }>> {
  return apiDelete(`/feeds/articles/${feedId}`);
}

/**
 * Import articles as texts.
 */
export async function importArticles(
  articleIds: number[]
): Promise<ApiResponse<ImportResponse>> {
  return apiPost('/feeds/articles/import', { article_ids: articleIds });
}

/**
 * Reset error articles for a feed.
 */
export async function resetErrorArticles(
  feedId: number
): Promise<ApiResponse<{ success: boolean; reset: number }>> {
  return apiDelete(`/feeds/${feedId}/reset-errors`);
}

// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Build options string from feed options object.
 */
export function buildOptionsString(options: Partial<FeedOptions>): string {
  const parts: string[] = [];

  if (options.edit_text) {
    parts.push(`edit_text=${options.edit_text}`);
  }
  if (options.autoupdate) {
    parts.push(`autoupdate=${options.autoupdate}`);
  }
  if (options.max_links) {
    parts.push(`max_links=${options.max_links}`);
  }
  if (options.max_texts) {
    parts.push(`max_texts=${options.max_texts}`);
  }
  if (options.charset) {
    parts.push(`charset=${options.charset}`);
  }
  if (options.tag) {
    parts.push(`tag=${options.tag}`);
  }
  if (options.article_source) {
    parts.push(`article_source=${options.article_source}`);
  }

  return parts.join(',');
}

/**
 * Parse options string into feed options object.
 */
export function parseOptionsString(optionsString: string): FeedOptions {
  const options: FeedOptions = {};

  if (!optionsString) {
    return options;
  }

  const pairs = optionsString.split(',');
  for (const pair of pairs) {
    const [key, value] = pair.split('=');
    if (key && value !== undefined) {
      const trimmedKey = key.trim() as keyof FeedOptions;
      options[trimmedKey] = value.trim();
    }
  }

  return options;
}

/**
 * Format auto-update interval for display.
 */
export function formatAutoUpdate(interval: string | undefined): string {
  if (!interval) return 'Never';

  if (interval.endsWith('h')) {
    const hours = parseInt(interval, 10);
    return hours === 1 ? 'Every hour' : `Every ${hours} hours`;
  }
  if (interval.endsWith('d')) {
    const days = parseInt(interval, 10);
    return days === 1 ? 'Every day' : `Every ${days} days`;
  }
  if (interval.endsWith('w')) {
    const weeks = parseInt(interval, 10);
    return weeks === 1 ? 'Every week' : `Every ${weeks} weeks`;
  }

  return 'Never';
}

/**
 * Get status badge class for an article.
 */
export function getStatusBadgeClass(status: Article['status']): string {
  switch (status) {
    case 'imported':
      return 'is-success';
    case 'archived':
      return 'is-info';
    case 'error':
      return 'is-danger';
    default:
      return 'is-light';
  }
}

/**
 * Get status label for an article.
 */
export function getStatusLabel(status: Article['status']): string {
  switch (status) {
    case 'imported':
      return 'Imported';
    case 'archived':
      return 'Archived';
    case 'error':
      return 'Error';
    default:
      return 'New';
  }
}

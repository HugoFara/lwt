/**
 * Tag management API client.
 *
 * Backs the term- and text-tag list pages. The flat name lists at
 * `GET /tags/term` and `GET /tags/text` are a different route and stay as they
 * are — Tagify and the term editor consume those.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.4.0
 */

import { apiGet, apiPost, apiPut, apiDelete, type ApiResponse } from '@shared/api/client';

/** Which set of tags a request addresses. */
export type TagType = 'term' | 'text';

/** A tag row as the list endpoint returns it. */
export interface TagRecord {
  id: number;
  text: string;
  comment: string;
  usageCount: number;
  /** Text tags only. */
  archivedUsageCount?: number;
  itemsUrl: string;
  archivedItemsUrl: string;
}

/** Pagination envelope. */
export interface TagPagination {
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

/** A sort choice offered by the list page. */
export interface TagSortOption {
  value: number;
  text: string;
}

/** Envelope returned by `GET /tags/{type}/list`. */
export interface TagListResponse {
  tags: TagRecord[];
  pagination: TagPagination;
  sortOptions: TagSortOption[];
  type: TagType;
  baseUrl: string;
}

/** Envelope returned by `GET /tags/{type}/{id}`. */
export interface TagGetResponse {
  tag?: TagRecord;
  error?: string;
}

/** Envelope returned by the mutating endpoints. */
export interface TagMutationResponse {
  success: boolean;
  id?: number;
  deleted?: number;
  error?: string;
}

/** Query parameters the list endpoint accepts. */
export interface TagListParams {
  query?: string;
  sort?: number;
  page?: number;
}

/** Tag management API methods. */
export const TagsApi = {
  /**
   * List tags of one type, paginated.
   *
   * @param type   `term` or `text`
   * @param params Filter, sort and page
   *
   * @returns Promise with the tags, pagination and sort options
   */
  async list(type: TagType, params: TagListParams = {}): Promise<ApiResponse<TagListResponse>> {
    return apiGet<TagListResponse>(
      `/tags/${type}/list`,
      params as Record<string, string | number | boolean>
    );
  },

  /**
   * Read one tag.
   *
   * @param type `term` or `text`
   * @param id   Tag ID
   *
   * @returns Promise with the tag
   */
  async get(type: TagType, id: number): Promise<ApiResponse<TagGetResponse>> {
    return apiGet<TagGetResponse>(`/tags/${type}/${id}`);
  },

  /**
   * Create a tag.
   *
   * @param type    `term` or `text`
   * @param text    Tag text
   * @param comment Optional comment
   *
   * @returns Promise with the new tag's ID
   */
  async create(type: TagType, text: string, comment: string): Promise<ApiResponse<TagMutationResponse>> {
    return apiPost<TagMutationResponse>(`/tags/${type}`, { text, comment });
  },

  /**
   * Update a tag.
   *
   * @param type    `term` or `text`
   * @param id      Tag ID
   * @param text    Tag text
   * @param comment Comment
   *
   * @returns Promise with the outcome
   */
  async update(
    type: TagType,
    id: number,
    text: string,
    comment: string
  ): Promise<ApiResponse<TagMutationResponse>> {
    return apiPut<TagMutationResponse>(`/tags/${type}/${id}`, { text, comment });
  },

  /**
   * Delete one tag.
   *
   * @param type `term` or `text`
   * @param id   Tag ID
   *
   * @returns Promise with the outcome
   */
  async remove(type: TagType, id: number): Promise<ApiResponse<TagMutationResponse>> {
    return apiDelete<TagMutationResponse>(`/tags/${type}/${id}`);
  },

  /**
   * Delete the selected tags.
   *
   * @param type `term` or `text`
   * @param ids  Tag IDs
   *
   * @returns Promise with how many were deleted
   */
  async removeMany(type: TagType, ids: number[]): Promise<ApiResponse<TagMutationResponse>> {
    return apiDelete<TagMutationResponse>(`/tags/${type}`, { ids });
  },

  /**
   * Delete every tag the current filter selects.
   *
   * Deliberately takes the filter rather than a page of IDs: the page only
   * holds one page's worth, and "delete all" means all matches.
   *
   * @param type  `term` or `text`
   * @param query Current filter
   *
   * @returns Promise with how many were deleted
   */
  async removeAll(type: TagType, query: string): Promise<ApiResponse<TagMutationResponse>> {
    return apiDelete<TagMutationResponse>(`/tags/${type}`, { all: true, query });
  }
};

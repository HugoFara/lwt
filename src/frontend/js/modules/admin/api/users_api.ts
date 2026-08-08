/**
 * Admin user-management API client.
 *
 * Every route here is admin-only and enforced server-side by
 * `UserManagementApiHandler`; nothing on this side is a security boundary.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.4.0
 */

import { apiGet, apiPost, apiPut, apiDelete, type ApiResponse } from '@shared/api/client';

/** A user account as the API returns it. Never carries the password hash. */
export interface AdminUser {
  id: number;
  username: string;
  email: string | null;
  role: 'admin' | 'user';
  isAdmin: boolean;
  isActive: boolean;
  created: string;
  lastLogin: string | null;
  linkedProviders: string[];
  hasPassword: boolean;
}

/** Pagination envelope. */
export interface UserPagination {
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

/** Envelope returned by `GET /admin/users`. */
export interface UserListResponse {
  users: AdminUser[];
  pagination: UserPagination;
  statistics: Record<string, number>;
  currentAdminId: number;
  sort: string;
  dir: string;
  search: string;
}

/** Envelope returned by `GET /admin/users/{id}`. */
export interface UserGetResponse {
  user?: AdminUser;
  currentAdminId?: number;
  error?: string;
}

/** Envelope returned by the mutating endpoints. */
export interface UserMutationResponse {
  success: boolean;
  id?: number;
  error?: string;
  errors?: string[];
}

/** Fields the create and update endpoints accept. */
export interface UserPayload {
  username: string;
  email: string;
  /** Empty on update means "leave the password alone". */
  password: string;
  role: 'admin' | 'user';
  is_active: boolean;
}

/** Query parameters the list endpoint accepts. */
export interface UserListParams {
  page?: number;
  per_page?: number;
  sort?: string;
  dir?: string;
  search?: string;
}

/** Admin user-management API methods. */
export const AdminUsersApi = {
  /**
   * List users.
   *
   * @param params Paging, sorting and search
   *
   * @returns Promise with users, paging and statistics
   */
  async list(params: UserListParams = {}): Promise<ApiResponse<UserListResponse>> {
    return apiGet<UserListResponse>(
      '/admin/users',
      params as Record<string, string | number | boolean>
    );
  },

  /**
   * Read one user.
   *
   * @param id User ID
   *
   * @returns Promise with the user
   */
  async get(id: number): Promise<ApiResponse<UserGetResponse>> {
    return apiGet<UserGetResponse>(`/admin/users/${id}`);
  },

  /**
   * Create a user.
   *
   * @param data User fields
   *
   * @returns Promise with the new user's ID
   */
  async create(data: UserPayload): Promise<ApiResponse<UserMutationResponse>> {
    return apiPost<UserMutationResponse>('/admin/users', data as unknown as Record<string, unknown>);
  },

  /**
   * Update a user.
   *
   * @param id   User ID
   * @param data User fields
   *
   * @returns Promise with the outcome
   */
  async update(id: number, data: UserPayload): Promise<ApiResponse<UserMutationResponse>> {
    return apiPut<UserMutationResponse>(
      `/admin/users/${id}`,
      data as unknown as Record<string, unknown>
    );
  },

  /**
   * Activate or deactivate a user.
   *
   * @param id       User ID
   * @param isActive Desired state
   *
   * @returns Promise with the outcome
   */
  async setStatus(id: number, isActive: boolean): Promise<ApiResponse<UserMutationResponse>> {
    return apiPut<UserMutationResponse>(`/admin/users/${id}/status`, { is_active: isActive });
  },

  /**
   * Promote a user to admin or demote them.
   *
   * @param id   User ID
   * @param role Desired role
   *
   * @returns Promise with the outcome
   */
  async setRole(id: number, role: 'admin' | 'user'): Promise<ApiResponse<UserMutationResponse>> {
    return apiPut<UserMutationResponse>(`/admin/users/${id}/role`, { role });
  },

  /**
   * Delete a user.
   *
   * @param id User ID
   *
   * @returns Promise with the outcome
   */
  async remove(id: number): Promise<ApiResponse<UserMutationResponse>> {
    return apiDelete<UserMutationResponse>(`/admin/users/${id}`);
  }
};

/**
 * Profile and preferences API client.
 *
 * Every route acts on the signed-in user; none of them take an account ID.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.4.0
 */

import { apiGet, apiPut, type ApiResponse } from '@shared/api/client';

/** The signed-in user's own profile. */
export interface Profile {
  username: string;
  email: string | null;
  emailVerified: boolean;
}

/** Envelope returned by `GET /profile`. */
export interface ProfileGetResponse {
  profile?: Profile;
  error?: string;
}

/** Envelope returned by the profile mutations. */
export interface ProfileMutationResponse {
  success: boolean;
  emailChanged?: boolean;
  error?: string;
}

/** Envelope returned by `GET /profile/preferences`. */
export interface PreferencesGetResponse {
  settings: Record<string, string>;
}

/** Profile API methods. */
export const ProfileApi = {
  /**
   * Read the signed-in user's profile.
   *
   * @returns Promise with the profile
   */
  async get(): Promise<ApiResponse<ProfileGetResponse>> {
    return apiGet<ProfileGetResponse>('/profile');
  },

  /**
   * Update username and email.
   *
   * @param username New username
   * @param email    New email
   *
   * @returns Promise with the outcome, flagging whether the email changed
   */
  async update(username: string, email: string): Promise<ApiResponse<ProfileMutationResponse>> {
    return apiPut<ProfileMutationResponse>('/profile', { username, email });
  },

  /**
   * Change the password.
   *
   * The current password is required and verified server-side.
   *
   * @param currentPassword Existing password
   * @param newPassword     Replacement
   * @param confirmPassword Replacement, repeated
   *
   * @returns Promise with the outcome
   */
  async changePassword(
    currentPassword: string,
    newPassword: string,
    confirmPassword: string
  ): Promise<ApiResponse<ProfileMutationResponse>> {
    return apiPut<ProfileMutationResponse>('/profile/password', {
      current_password: currentPassword,
      new_password: newPassword,
      new_password_confirm: confirmPassword
    });
  },

  /**
   * Read the user-scoped settings map.
   *
   * @returns Promise with the settings
   */
  async getPreferences(): Promise<ApiResponse<PreferencesGetResponse>> {
    return apiGet<PreferencesGetResponse>('/profile/preferences');
  },

  /**
   * Save the user-scoped settings map.
   *
   * Keys the server does not recognise as user-scoped are ignored rather than
   * stored, so sending the whole map back is safe.
   *
   * @param settings Settings keyed by name
   *
   * @returns Promise with the outcome
   */
  async savePreferences(
    settings: Record<string, string>
  ): Promise<ApiResponse<ProfileMutationResponse>> {
    return apiPut<ProfileMutationResponse>('/profile/preferences', { settings });
  }
};

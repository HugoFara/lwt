/**
 * Centralized API client for all backend communication.
 *
 * Replaces jQuery AJAX with modern fetch API.
 * Provides type-safe wrappers for GET, POST, PUT, DELETE requests.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

/**
 * Standard response wrapper for all API calls.
 */
export interface ApiResponse<T> {
  data?: T;
  error?: string;
}

/**
 * Configuration for the API client.
 */
export interface ApiClientConfig {
  baseUrl: string;
  defaultHeaders?: Record<string, string>;
}

/**
 * Get the application base path from meta tag.
 */
function getBasePath(): string {
  const meta = document.querySelector('meta[name="lwt-base-path"]');
  return meta ? meta.getAttribute('content') || '' : '';
}

/**
 * In-memory override for the API server root, set via {@link setApiServer}.
 * `null` means "no override — consult localStorage/meta"; a non-empty string is
 * the active override. It is never set to '' (a reset clears it back to `null`).
 */
let overrideApiServer: string | null = null;

/**
 * Safely read the persisted API server. Returns '' when localStorage is
 * unavailable (privacy modes, non-DOM test environments, etc.).
 */
function readStoredApiServer(): string {
  try {
    return localStorage.getItem('lwt.apiServer') || '';
  } catch {
    return '';
  }
}

/**
 * Resolve the configured API server **root** (scheme + host, optionally a
 * sub-path), or '' when the app should talk to its own origin.
 *
 * Precedence:
 *   1. runtime override set via {@link setApiServer}
 *   2. persisted choice in localStorage (`lwt.apiServer`)
 *   3. `<meta name="lwt-api-server">` (optional server-declared default)
 *   4. '' — same-origin (the classic web-app behavior)
 *
 * Returning '' keeps every existing same-origin install byte-for-byte
 * identical; a non-empty value lets a packaged client (e.g. a Capacitor shell
 * for F-Droid) point at a user-chosen LWT server.
 */
function getConfiguredApiServer(): string {
  if (overrideApiServer !== null) {
    return overrideApiServer;
  }
  const stored = readStoredApiServer();
  if (stored) {
    return stored;
  }
  const meta = document.querySelector('meta[name="lwt-api-server"]');
  return meta ? meta.getAttribute('content') || '' : '';
}

/**
 * Compute the full API root (ending in `/api/v1`) for the current request.
 *
 * - Remote server configured -> `https://server[/subpath]/api/v1` (absolute).
 * - Nothing configured        -> `<base-path>/api/v1` (relative, same-origin).
 */
function resolveApiRoot(): string {
  const server = getConfiguredApiServer();
  if (server) {
    return server.replace(/\/+$/, '') + '/api/v1';
  }
  return getBasePath() + '/api/v1';
}

/**
 * Point the API client at a specific LWT server, persisting the choice to
 * localStorage so a packaged client remembers it across launches. Pass
 * `null`/'' to reset — forgetting both the override and the persisted value so
 * resolution falls back to localStorage/meta/same-origin.
 *
 * This function owns URL construction only. Cross-origin use additionally
 * requires the server to send permissive CORS headers and — for cookie
 * sessions — `credentials: 'include'`; token auth avoids the CSRF-meta
 * dependency. See ROADMAP.md (Phase 1) for those follow-ups.
 */
export function setApiServer(server: string | null): void {
  const normalized = (server || '').trim().replace(/\/+$/, '');
  if (!normalized) {
    // Reset: forget the override and any persisted choice.
    overrideApiServer = null;
    try {
      localStorage.removeItem('lwt.apiServer');
    } catch {
      // localStorage unavailable: nothing persisted to clear.
    }
    return;
  }
  overrideApiServer = normalized;
  try {
    localStorage.setItem('lwt.apiServer', normalized);
  } catch {
    // localStorage unavailable: the in-memory override still applies for
    // the rest of this session.
  }
}

/**
 * The API server root the client is currently using, or '' for same-origin.
 */
export function getApiServer(): string {
  return getConfiguredApiServer();
}

/**
 * Read the CSRF token from `<meta name="csrf-token">`. Exported so
 * non-API-client callers (handleRestDelete in texts_grouped_app, etc.)
 * can attach the same `X-CSRF-TOKEN` header that CsrfMiddleware checks
 * on POST/PUT/DELETE/PATCH.
 */
export function getCsrfToken(): string {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') || '' : '';
}

/**
 * Build headers that include CSRF for state-changing requests.
 */
function withCsrf(headers: Record<string, string>): Record<string, string> {
  const token = getCsrfToken();
  if (!token) return headers;
  return { ...headers, 'X-CSRF-TOKEN': token };
}

/**
 * In-memory bearer token, set via {@link setAuthToken}. `null` means "consult
 * localStorage"; '' is never stored (a reset clears it back to `null`).
 */
let authTokenOverride: string | null = null;

/**
 * Safely read the persisted bearer token. Returns '' when localStorage is
 * unavailable.
 */
function readStoredAuthToken(): string {
  try {
    return localStorage.getItem('lwt.apiToken') || '';
  } catch {
    return '';
  }
}

/**
 * The bearer token the client currently sends, or '' when unauthenticated.
 * Precedence: runtime value set via {@link setAuthToken} > localStorage
 * (`lwt.apiToken`).
 */
export function getAuthToken(): string {
  if (authTokenOverride !== null) {
    return authTokenOverride;
  }
  return readStoredAuthToken();
}

/**
 * Store (or clear) the API bearer token obtained from `POST /api/v1/auth/login`
 * (or `/auth/register`, `/auth/refresh`). Persisted to localStorage so a
 * packaged client stays signed in across launches. Pass `null`/'' to clear,
 * e.g. on logout.
 *
 * When set, every request carries `Authorization: Bearer <token>`. This is how
 * a cross-origin client authenticates, since cookies are not sent to a remote
 * server (see {@link setApiServer}); same-origin callers can ignore it and keep
 * using the session cookie.
 */
export function setAuthToken(token: string | null): void {
  const normalized = (token || '').trim();
  if (!normalized) {
    authTokenOverride = null;
    try {
      localStorage.removeItem('lwt.apiToken');
    } catch {
      // localStorage unavailable: nothing persisted to clear.
    }
    return;
  }
  authTokenOverride = normalized;
  try {
    localStorage.setItem('lwt.apiToken', normalized);
  } catch {
    // localStorage unavailable: the in-memory token still applies this session.
  }
}

/**
 * Add the `Authorization: Bearer` header when a token is set. A no-op
 * otherwise, so same-origin cookie-authenticated requests are unchanged.
 */
function withAuth(headers: Record<string, string>): Record<string, string> {
  const token = getAuthToken();
  if (!token) return headers;
  return { ...headers, Authorization: `Bearer ${token}` };
}

/**
 * Get the default API configuration.
 * Lazily reads base path from meta tag.
 */
function getDefaultConfig(): ApiClientConfig {
  return {
    baseUrl: resolveApiRoot(),
    defaultHeaders: {
      'Content-Type': 'application/json',
      Accept: 'application/json'
    }
  };
}

// Use a getter to ensure base path is read after DOM is ready
const defaultConfig: ApiClientConfig = {
  get baseUrl() {
    return getDefaultConfig().baseUrl;
  },
  defaultHeaders: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }
};

/**
 * Build URL with query parameters.
 *
 * @param endpoint API endpoint path
 * @param params   Optional query parameters
 * @returns Complete URL string
 */
function buildUrl(
  endpoint: string,
  params?: Record<string, string | number | boolean | undefined>
): string {
  const url = new URL(
    defaultConfig.baseUrl + endpoint,
    window.location.origin
  );

  if (params) {
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined) {
        url.searchParams.append(key, String(value));
      }
    });
  }

  return url.toString();
}

/**
 * Parse response body as JSON or return empty object.
 *
 * @param response Fetch response object
 * @returns Parsed JSON data or empty object
 */
async function parseResponse<T>(response: Response): Promise<T> {
  const text = await response.text();
  if (!text) {
    return {} as T;
  }
  try {
    return JSON.parse(text) as T;
  } catch {
    // Return the raw text wrapped in an object if not JSON
    return { raw: text } as T;
  }
}

/**
 * Make a GET request to the API.
 *
 * @param endpoint API endpoint (e.g., '/terms/123')
 * @param params   Optional query parameters
 * @returns Promise resolving to ApiResponse with data or error
 *
 * @example
 * const response = await apiGet<Term>('/terms/123');
 * if (response.data) {
 *   console.log(response.data.text);
 * }
 */
export async function apiGet<T>(
  endpoint: string,
  params?: Record<string, string | number | boolean | undefined>
): Promise<ApiResponse<T>> {
  try {
    const response = await fetch(buildUrl(endpoint, params), {
      method: 'GET',
      headers: withAuth(defaultConfig.defaultHeaders ?? {})
    });

    if (!response.ok) {
      const errorData = await parseResponse<{ message?: string }>(response);
      return {
        error:
          errorData.message ||
          `HTTP ${response.status}: ${response.statusText}`
      };
    }

    const data = await parseResponse<T>(response);
    return { data };
  } catch (error) {
    return { error: String(error) };
  }
}

/**
 * Make a POST request to the API.
 *
 * @param endpoint API endpoint
 * @param body     Request body (will be JSON-stringified)
 * @returns Promise resolving to ApiResponse with data or error
 *
 * @example
 * const response = await apiPost<Term>('/terms', { text: 'hello', langId: 1 });
 */
export async function apiPost<T>(
  endpoint: string,
  body: Record<string, unknown>
): Promise<ApiResponse<T>> {
  try {
    const response = await fetch(defaultConfig.baseUrl + endpoint, {
      method: 'POST',
      headers: withAuth(withCsrf(defaultConfig.defaultHeaders ?? {})),
      body: JSON.stringify(body)
    });

    if (!response.ok) {
      const errorData = await parseResponse<{ message?: string }>(response);
      return {
        error:
          errorData.message ||
          `HTTP ${response.status}: ${response.statusText}`
      };
    }

    const data = await parseResponse<T>(response);
    return { data };
  } catch (error) {
    return { error: String(error) };
  }
}

/**
 * Make a PUT request to the API.
 *
 * @param endpoint API endpoint
 * @param body     Request body (will be JSON-stringified)
 * @returns Promise resolving to ApiResponse with data or error
 *
 * @example
 * const response = await apiPut<Term>('/terms/123', { translation: 'bonjour' });
 */
export async function apiPut<T>(
  endpoint: string,
  body: Record<string, unknown>
): Promise<ApiResponse<T>> {
  try {
    const response = await fetch(defaultConfig.baseUrl + endpoint, {
      method: 'PUT',
      headers: withAuth(withCsrf(defaultConfig.defaultHeaders ?? {})),
      body: JSON.stringify(body)
    });

    if (!response.ok) {
      const errorData = await parseResponse<{ message?: string }>(response);
      return {
        error:
          errorData.message ||
          `HTTP ${response.status}: ${response.statusText}`
      };
    }

    const data = await parseResponse<T>(response);
    return { data };
  } catch (error) {
    return { error: String(error) };
  }
}

/**
 * Make a DELETE request to the API.
 *
 * @param endpoint API endpoint
 * @returns Promise resolving to ApiResponse with data or error
 *
 * @example
 * const response = await apiDelete('/terms/123');
 */
export async function apiDelete<T>(
  endpoint: string,
  body?: Record<string, unknown>
): Promise<ApiResponse<T>> {
  try {
    const options: RequestInit = {
      method: 'DELETE',
      headers: withAuth(withCsrf(defaultConfig.defaultHeaders ?? {}))
    };
    if (body) {
      options.body = JSON.stringify(body);
    }
    const response = await fetch(defaultConfig.baseUrl + endpoint, options);

    if (!response.ok) {
      const errorData = await parseResponse<{ message?: string }>(response);
      return {
        error:
          errorData.message ||
          `HTTP ${response.status}: ${response.statusText}`
      };
    }

    const data = await parseResponse<T>(response);
    return { data };
  } catch (error) {
    return { error: String(error) };
  }
}

/**
 * Make a form-urlencoded POST request (for legacy compatibility).
 *
 * Some existing endpoints expect form data rather than JSON.
 * Use this for backward compatibility during migration.
 *
 * @param endpoint API endpoint
 * @param data     Form data as key-value pairs
 * @returns Promise resolving to ApiResponse with data or error
 */
export async function apiPostForm<T>(
  endpoint: string,
  data: Record<string, string | number | boolean>
): Promise<ApiResponse<T>> {
  try {
    const formData = new URLSearchParams();
    Object.entries(data).forEach(([key, value]) => {
      formData.append(key, String(value));
    });

    const response = await fetch(defaultConfig.baseUrl + endpoint, {
      method: 'POST',
      headers: withAuth(withCsrf({
        'Content-Type': 'application/x-www-form-urlencoded',
        Accept: 'application/json'
      })),
      body: formData.toString()
    });

    if (!response.ok) {
      const errorData = await parseResponse<{ message?: string }>(response);
      return {
        error:
          errorData.message ||
          `HTTP ${response.status}: ${response.statusText}`
      };
    }

    const respData = await parseResponse<T>(response);
    return { data: respData };
  } catch (error) {
    return { error: String(error) };
  }
}

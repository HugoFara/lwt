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
 * Get the default API configuration.
 * Lazily reads base path from meta tag.
 */
function getDefaultConfig(): ApiClientConfig {
  return {
    baseUrl: getBasePath() + '/api/v1',
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
      headers: defaultConfig.defaultHeaders
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
      headers: defaultConfig.defaultHeaders,
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
      headers: defaultConfig.defaultHeaders,
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
export async function apiDelete<T>(endpoint: string): Promise<ApiResponse<T>> {
  try {
    const response = await fetch(defaultConfig.baseUrl + endpoint, {
      method: 'DELETE',
      headers: defaultConfig.defaultHeaders
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
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        Accept: 'application/json'
      },
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

/**
 * URL utilities for handling application base path.
 *
 * Provides functions to build URLs that include the APP_BASE_PATH.
 * The base path is read from a meta tag in the HTML head.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

/**
 * Get the application base path from meta tag.
 *
 * @returns The base path (e.g., '/lwt') or empty string if not set
 */
export function getBasePath(): string {
  const meta = document.querySelector('meta[name="lwt-base-path"]');
  return meta ? meta.getAttribute('content') || '' : '';
}

/**
 * Build a URL with the application base path prepended.
 *
 * @param path - The path to append (should start with '/')
 * @returns The full URL with base path
 *
 * @example
 * // If APP_BASE_PATH=/lwt
 * url('/languages') // returns '/lwt/languages'
 * url('/api/v1/texts') // returns '/lwt/api/v1/texts'
 */
export function url(path: string): string {
  const basePath = getBasePath();
  // Ensure path starts with /
  const normalizedPath = path.startsWith('/') ? path : '/' + path;
  return basePath + normalizedPath;
}

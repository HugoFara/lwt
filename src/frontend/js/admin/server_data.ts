/**
 * Server Data - Fetches and displays REST API version information.
 *
 * This module handles the AJAX call to fetch REST API version data
 * on the server data admin page.
 *
 * @license unlicense
 * @since   3.0.0
 */

interface ApiVersionResponse {
  version?: string;
  release_date?: string;
  error?: string;
}

/**
 * Handle the API version response and update the DOM.
 *
 * @param data - The API response data
 */
function handleApiVersionAnswer(data: ApiVersionResponse): void {
  const versionEl = document.getElementById('rest-api-version');
  const dateEl = document.getElementById('rest-api-release-date');

  if ('error' in data && data.error) {
    if (versionEl) {
      versionEl.textContent =
        'Error while getting data from the REST API!' +
        '\nMessage: ' + data.error;
    }
    if (dateEl) {
      dateEl.textContent = '';
    }
  } else {
    if (versionEl) {
      versionEl.textContent = data.version || '';
    }
    if (dateEl) {
      dateEl.textContent = data.release_date || '';
    }
  }
}

/**
 * Fetch the REST API version and display it.
 */
export function fetchApiVersion(): void {
  fetch('api.php/v1/version')
    .then(response => response.json())
    .then(handleApiVersionAnswer)
    .catch(error => {
      handleApiVersionAnswer({ error: error.message });
    });
}

/**
 * Initialize the server data page.
 * Automatically fetches API version if the relevant elements exist.
 */
export function initServerData(): void {
  if (document.getElementById('rest-api-version')) {
    fetchApiVersion();
  }
}

// Auto-initialize on document ready
document.addEventListener('DOMContentLoaded', initServerData);

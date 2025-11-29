/**
 * Server Data - Fetches and displays REST API version information.
 *
 * This module handles the AJAX call to fetch REST API version data
 * on the server data admin page.
 *
 * @license unlicense
 * @since   3.0.0
 */

import $ from 'jquery';

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
  if ('error' in data && data.error) {
    $('#rest-api-version').text(
      'Error while getting data from the REST API!' +
      '\nMessage: ' + data.error
    );
    $('#rest-api-release-date').empty();
  } else {
    $('#rest-api-version').text(data.version || '');
    $('#rest-api-release-date').text(data.release_date || '');
  }
}

/**
 * Fetch the REST API version and display it.
 */
export function fetchApiVersion(): void {
  $.getJSON(
    'api.php/v1/version',
    {},
    handleApiVersionAnswer
  );
}

/**
 * Initialize the server data page.
 * Automatically fetches API version if the relevant elements exist.
 */
export function initServerData(): void {
  if ($('#rest-api-version').length > 0) {
    fetchApiVersion();
  }
}

// Auto-initialize on document ready
$(document).ready(initServerData);

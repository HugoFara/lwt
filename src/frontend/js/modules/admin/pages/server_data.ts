/**
 * Server Data - Alpine.js component for the server data admin page.
 *
 * Displays server information and fetches REST API version data.
 *
 * @license unlicense
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

interface ApiVersionResponse {
  version?: string;
  release_date?: string;
  error?: string;
}

interface ServerDataState {
  apiVersion: string;
  apiReleaseDate: string;
  isLoading: boolean;
  error: string | null;
  init(): void;
  fetchApiVersion(): Promise<void>;
}

/**
 * Alpine.js data component for the server data page.
 * Manages API version fetching with loading and error states.
 */
export function serverDataApp(): ServerDataState {
  return {
    apiVersion: '',
    apiReleaseDate: '',
    isLoading: true,
    error: null,

    /**
     * Initialize the component - automatically called by Alpine.
     */
    init() {
      this.fetchApiVersion();
    },

    /**
     * Fetch the REST API version from the server.
     */
    async fetchApiVersion(): Promise<void> {
      try {
        const response = await fetch('/api/v1/version');
        const data: ApiVersionResponse = await response.json();

        if (data.error) {
          this.error = data.error;
        } else {
          this.apiVersion = data.version || '';
          this.apiReleaseDate = data.release_date || '';
        }
      } catch (e) {
        this.error = (e as Error).message;
      }
      this.isLoading = false;
    }
  };
}

/**
 * Register the Alpine.js component.
 */
export function initServerDataAlpine(): void {
  Alpine.data('serverDataApp', serverDataApp);
}

// Auto-register before Alpine.start() is called
initServerDataAlpine();

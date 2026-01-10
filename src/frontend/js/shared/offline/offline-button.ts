/**
 * Offline Download Button Component for LWT PWA.
 *
 * Alpine.js component for downloading texts for offline reading.
 *
 * @author  HugoFara <Hugo.Farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import {
  isOfflineStorageAvailable,
  isTextAvailableOffline,
  downloadTextForOffline,
  removeTextFromOffline,
  type OfflineAvailability,
} from './text-service';

// =============================================================================
// TYPES
// =============================================================================

/**
 * Offline button component data.
 */
export interface OfflineButtonData {
  textId: number;
  isAvailable: boolean;
  isDownloading: boolean;
  downloadProgress: number;
  statusMessage: string;
  downloadedAt: Date | null;
  sizeBytes: number;
  storageSupported: boolean;

  // Methods
  init(): Promise<void>;
  checkStatus(): Promise<void>;
  toggleOffline(): Promise<void>;
  download(): Promise<void>;
  remove(): Promise<void>;
  formatSize(bytes: number): string;
  formatDate(date: Date | null): string;
}

// =============================================================================
// COMPONENT
// =============================================================================

/**
 * Create offline button Alpine.js component.
 *
 * Usage in HTML:
 * ```html
 * <div x-data="offlineButton(123)">
 *   <button @click="toggleOffline" :disabled="isDownloading">
 *     <span x-show="!isAvailable">Download for Offline</span>
 *     <span x-show="isAvailable">Remove from Offline</span>
 *   </button>
 *   <span x-show="isDownloading" x-text="statusMessage"></span>
 * </div>
 * ```
 */
export function offlineButton(textId: number): OfflineButtonData {
  return {
    textId,
    isAvailable: false,
    isDownloading: false,
    downloadProgress: 0,
    statusMessage: '',
    downloadedAt: null,
    sizeBytes: 0,
    storageSupported: isOfflineStorageAvailable(),

    async init() {
      if (this.storageSupported) {
        await this.checkStatus();
      }
    },

    async checkStatus() {
      const status: OfflineAvailability = await isTextAvailableOffline(this.textId);
      this.isAvailable = status.available;
      this.downloadedAt = status.downloadedAt || null;
      this.sizeBytes = status.sizeBytes || 0;
    },

    async toggleOffline() {
      if (this.isAvailable) {
        await this.remove();
      } else {
        await this.download();
      }
    },

    async download() {
      if (this.isDownloading || !this.storageSupported) return;

      this.isDownloading = true;
      this.downloadProgress = 0;
      this.statusMessage = 'Starting download...';

      try {
        await downloadTextForOffline(this.textId, (progress, message) => {
          this.downloadProgress = progress;
          this.statusMessage = message;
        });

        await this.checkStatus();
        this.statusMessage = 'Downloaded successfully';

        // Dispatch event for other components to react
        window.dispatchEvent(new CustomEvent('offline:text-downloaded', {
          detail: { textId: this.textId },
        }));
      } catch (error) {
        this.statusMessage = `Error: ${error instanceof Error ? error.message : 'Download failed'}`;
        console.error('Offline download error:', error);
      } finally {
        this.isDownloading = false;
      }
    },

    async remove() {
      if (this.isDownloading || !this.storageSupported) return;

      try {
        await removeTextFromOffline(this.textId);
        await this.checkStatus();
        this.statusMessage = '';

        // Dispatch event for other components to react
        window.dispatchEvent(new CustomEvent('offline:text-removed', {
          detail: { textId: this.textId },
        }));
      } catch (error) {
        this.statusMessage = `Error: ${error instanceof Error ? error.message : 'Remove failed'}`;
        console.error('Offline remove error:', error);
      }
    },

    formatSize(bytes: number): string {
      if (bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    },

    formatDate(date: Date | null): string {
      if (!date) return '';
      return date.toLocaleDateString();
    },
  };
}

// =============================================================================
// ALPINE REGISTRATION
// =============================================================================

/**
 * Register the offline button component with Alpine.js.
 */
export function registerOfflineButton(): void {
  Alpine.data('offlineButton', offlineButton);
}

// Auto-register when imported
if (typeof Alpine !== 'undefined') {
  // Register after Alpine is ready
  document.addEventListener('alpine:init', () => {
    registerOfflineButton();
  });

  // Also register immediately if Alpine is already started
  if (Alpine.version) {
    registerOfflineButton();
  }
}

// Expose globally
declare global {
  interface Window {
    offlineButton: typeof offlineButton;
  }
}

window.offlineButton = offlineButton;

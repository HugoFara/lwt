/**
 * Tests for shared/offline/offline-button.ts - Alpine.js offline download button component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock dependencies before importing
vi.mock('../../../src/frontend/js/shared/offline/text-service', () => ({
  isOfflineStorageAvailable: vi.fn().mockReturnValue(true),
  isTextAvailableOffline: vi.fn().mockResolvedValue({ available: false }),
  downloadTextForOffline: vi.fn().mockResolvedValue(undefined),
  removeTextFromOffline: vi.fn().mockResolvedValue(undefined),
}));

vi.mock('alpinejs', () => ({
  default: {
    data: vi.fn(),
    version: '3.0.0',
  },
}));

import Alpine from 'alpinejs';
import {
  offlineButton,
  registerOfflineButton,
} from '../../../src/frontend/js/shared/offline/offline-button';
import {
  isOfflineStorageAvailable,
  isTextAvailableOffline,
  downloadTextForOffline,
  removeTextFromOffline,
} from '../../../src/frontend/js/shared/offline/text-service';

describe('shared/offline/offline-button.ts', () => {
  let dispatchEventSpy: ReturnType<typeof vi.spyOn>;
  let consoleErrorSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    vi.clearAllMocks();
    dispatchEventSpy = vi.spyOn(window, 'dispatchEvent').mockImplementation(() => true);
    consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    vi.mocked(isOfflineStorageAvailable).mockReturnValue(true);
    vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });
  });

  afterEach(() => {
    dispatchEventSpy.mockRestore();
    consoleErrorSpy.mockRestore();
  });

  // ===========================================================================
  // offlineButton Factory Tests
  // ===========================================================================

  describe('offlineButton', () => {
    it('creates component with correct initial state', () => {
      const component = offlineButton(123);

      expect(component.textId).toBe(123);
      expect(component.isAvailable).toBe(false);
      expect(component.isDownloading).toBe(false);
      expect(component.downloadProgress).toBe(0);
      expect(component.statusMessage).toBe('');
      expect(component.downloadedAt).toBeNull();
      expect(component.sizeBytes).toBe(0);
    });

    it('checks storage support on creation', () => {
      vi.mocked(isOfflineStorageAvailable).mockReturnValue(true);
      const component = offlineButton(1);
      expect(component.storageSupported).toBe(true);

      vi.mocked(isOfflineStorageAvailable).mockReturnValue(false);
      const component2 = offlineButton(2);
      expect(component2.storageSupported).toBe(false);
    });

    it('has required methods', () => {
      const component = offlineButton(1);

      expect(typeof component.init).toBe('function');
      expect(typeof component.checkStatus).toBe('function');
      expect(typeof component.toggleOffline).toBe('function');
      expect(typeof component.download).toBe('function');
      expect(typeof component.remove).toBe('function');
      expect(typeof component.formatSize).toBe('function');
      expect(typeof component.formatDate).toBe('function');
    });
  });

  // ===========================================================================
  // init Method Tests
  // ===========================================================================

  describe('init', () => {
    it('checks status when storage is supported', async () => {
      vi.mocked(isOfflineStorageAvailable).mockReturnValue(true);
      vi.mocked(isTextAvailableOffline).mockResolvedValue({
        available: true,
        downloadedAt: new Date('2024-01-01'),
        sizeBytes: 5000,
      });

      const component = offlineButton(123);
      await component.init();

      expect(isTextAvailableOffline).toHaveBeenCalledWith(123);
      expect(component.isAvailable).toBe(true);
      expect(component.sizeBytes).toBe(5000);
    });

    it('does not check status when storage is not supported', async () => {
      vi.mocked(isOfflineStorageAvailable).mockReturnValue(false);

      const component = offlineButton(123);
      await component.init();

      expect(isTextAvailableOffline).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // checkStatus Method Tests
  // ===========================================================================

  describe('checkStatus', () => {
    it('updates component state from service response', async () => {
      const downloadDate = new Date('2024-06-15');
      vi.mocked(isTextAvailableOffline).mockResolvedValue({
        available: true,
        downloadedAt: downloadDate,
        sizeBytes: 10000,
      });

      const component = offlineButton(456);
      await component.checkStatus();

      expect(component.isAvailable).toBe(true);
      expect(component.downloadedAt).toEqual(downloadDate);
      expect(component.sizeBytes).toBe(10000);
    });

    it('handles unavailable text', async () => {
      vi.mocked(isTextAvailableOffline).mockResolvedValue({
        available: false,
      });

      const component = offlineButton(789);
      await component.checkStatus();

      expect(component.isAvailable).toBe(false);
      expect(component.downloadedAt).toBeNull();
      expect(component.sizeBytes).toBe(0);
    });
  });

  // ===========================================================================
  // toggleOffline Method Tests
  // ===========================================================================

  describe('toggleOffline', () => {
    it('calls download when text is not available', async () => {
      vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });

      const component = offlineButton(123);
      component.isAvailable = false;

      const downloadSpy = vi.spyOn(component, 'download').mockResolvedValue();
      await component.toggleOffline();

      expect(downloadSpy).toHaveBeenCalled();
    });

    it('calls remove when text is available', async () => {
      const component = offlineButton(123);
      component.isAvailable = true;

      const removeSpy = vi.spyOn(component, 'remove').mockResolvedValue();
      await component.toggleOffline();

      expect(removeSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // download Method Tests
  // ===========================================================================

  describe('download', () => {
    it('sets downloading state during download', async () => {
      vi.mocked(downloadTextForOffline).mockImplementation(async (_id, onProgress) => {
        onProgress?.(50, 'Downloading...');
      });
      vi.mocked(isTextAvailableOffline).mockResolvedValue({
        available: true,
        downloadedAt: new Date(),
        sizeBytes: 1000,
      });

      const component = offlineButton(123);
      expect(component.isDownloading).toBe(false);

      const downloadPromise = component.download();
      expect(component.isDownloading).toBe(true);

      await downloadPromise;
      expect(component.isDownloading).toBe(false);
    });

    it('updates progress during download', async () => {
      vi.mocked(downloadTextForOffline).mockImplementation(async (_id, onProgress) => {
        onProgress?.(25, 'Starting...');
        onProgress?.(50, 'Fetching...');
        onProgress?.(100, 'Done');
      });
      vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: true });

      const component = offlineButton(123);
      await component.download();

      expect(component.downloadProgress).toBe(100);
    });

    it('dispatches event on successful download', async () => {
      vi.mocked(downloadTextForOffline).mockResolvedValue(undefined);
      vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: true });

      const component = offlineButton(123);
      await component.download();

      expect(dispatchEventSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'offline:text-downloaded',
          detail: { textId: 123 },
        })
      );
    });

    it('does not download if already downloading', async () => {
      const component = offlineButton(123);
      component.isDownloading = true;

      await component.download();

      expect(downloadTextForOffline).not.toHaveBeenCalled();
    });

    it('does not download if storage not supported', async () => {
      vi.mocked(isOfflineStorageAvailable).mockReturnValue(false);

      const component = offlineButton(123);
      await component.download();

      expect(downloadTextForOffline).not.toHaveBeenCalled();
    });

    it('handles download errors gracefully', async () => {
      vi.mocked(downloadTextForOffline).mockRejectedValue(new Error('Network error'));

      const component = offlineButton(123);
      await component.download();

      expect(component.statusMessage).toContain('Network error');
      expect(component.isDownloading).toBe(false);
      expect(consoleErrorSpy).toHaveBeenCalled();
    });

    it('handles non-Error rejection', async () => {
      vi.mocked(downloadTextForOffline).mockRejectedValue('String error');

      const component = offlineButton(123);
      await component.download();

      expect(component.statusMessage).toContain('Download failed');
    });
  });

  // ===========================================================================
  // remove Method Tests
  // ===========================================================================

  describe('remove', () => {
    it('calls removeTextFromOffline service', async () => {
      vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });

      const component = offlineButton(456);
      await component.remove();

      expect(removeTextFromOffline).toHaveBeenCalledWith(456);
    });

    it('dispatches event on successful removal', async () => {
      vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });

      const component = offlineButton(789);
      await component.remove();

      expect(dispatchEventSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'offline:text-removed',
          detail: { textId: 789 },
        })
      );
    });

    it('clears status message on successful removal', async () => {
      vi.mocked(isTextAvailableOffline).mockResolvedValue({ available: false });

      const component = offlineButton(123);
      component.statusMessage = 'Previous message';
      await component.remove();

      expect(component.statusMessage).toBe('');
    });

    it('does not remove if currently downloading', async () => {
      const component = offlineButton(123);
      component.isDownloading = true;

      await component.remove();

      expect(removeTextFromOffline).not.toHaveBeenCalled();
    });

    it('does not remove if storage not supported', async () => {
      vi.mocked(isOfflineStorageAvailable).mockReturnValue(false);

      const component = offlineButton(123);
      await component.remove();

      expect(removeTextFromOffline).not.toHaveBeenCalled();
    });

    it('handles removal errors gracefully', async () => {
      vi.mocked(removeTextFromOffline).mockRejectedValue(new Error('DB error'));

      const component = offlineButton(123);
      await component.remove();

      expect(component.statusMessage).toContain('DB error');
      expect(consoleErrorSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // formatSize Method Tests
  // ===========================================================================

  describe('formatSize', () => {
    it('formats 0 bytes', () => {
      const component = offlineButton(1);
      expect(component.formatSize(0)).toBe('0 B');
    });

    it('formats bytes', () => {
      const component = offlineButton(1);
      expect(component.formatSize(500)).toBe('500 B');
    });

    it('formats kilobytes', () => {
      const component = offlineButton(1);
      expect(component.formatSize(1024)).toBe('1 KB');
      expect(component.formatSize(1536)).toBe('1.5 KB');
    });

    it('formats megabytes', () => {
      const component = offlineButton(1);
      expect(component.formatSize(1048576)).toBe('1 MB');
      expect(component.formatSize(2621440)).toBe('2.5 MB');
    });

    it('formats gigabytes', () => {
      const component = offlineButton(1);
      expect(component.formatSize(1073741824)).toBe('1 GB');
    });
  });

  // ===========================================================================
  // formatDate Method Tests
  // ===========================================================================

  describe('formatDate', () => {
    it('returns empty string for null date', () => {
      const component = offlineButton(1);
      expect(component.formatDate(null)).toBe('');
    });

    it('formats valid date', () => {
      const component = offlineButton(1);
      const date = new Date('2024-06-15');
      const result = component.formatDate(date);
      // Result depends on locale, but should contain date parts
      expect(result).toBeTruthy();
      expect(typeof result).toBe('string');
    });
  });

  // ===========================================================================
  // registerOfflineButton Tests
  // ===========================================================================

  describe('registerOfflineButton', () => {
    it('registers component with Alpine', () => {
      registerOfflineButton();

      expect(Alpine.data).toHaveBeenCalledWith('offlineButton', offlineButton);
    });
  });

  // ===========================================================================
  // Global Exposure Tests
  // ===========================================================================

  describe('Global Exposure', () => {
    it('exposes offlineButton globally on window', () => {
      expect(window.offlineButton).toBe(offlineButton);
    });
  });
});

/**
 * Tests for youtube_import.ts - Fetch text data from YouTube videos
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { getYtTextData, initYouTubeImport } from '../../../src/frontend/js/modules/text/pages/youtube_import';

describe('youtube_import.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // getYtTextData Tests
  // ===========================================================================

  describe('getYtTextData', () => {
    describe('input validation', () => {
      it('shows error when ytVideoId input is missing', () => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
        `;

        getYtTextData();

        expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Missing YouTube video ID input field.');
      });

      it('shows error when video ID is empty', () => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="">
        `;

        getYtTextData();

        expect(document.getElementById('ytDataStatus')!.textContent).toBe('Please enter a YouTube Video ID.');
      });

      it('shows error when video ID is whitespace only', () => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="   ">
        `;

        getYtTextData();

        expect(document.getElementById('ytDataStatus')!.textContent).toBe('Please enter a YouTube Video ID.');
      });
    });

    describe('API success', () => {
      beforeEach(() => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="dQw4w9WgXcQ">
          <input name="TxTitle" value="">
          <input name="TxText" value="">
          <input name="TxSourceURI" value="">
        `;
      });

      it('shows fetching status while loading', () => {
        // Mock fetch to return a pending promise
        global.fetch = vi.fn().mockReturnValue(new Promise(() => {}));

        getYtTextData();

        // Initial status should be set
        expect(document.getElementById('ytDataStatus')!.textContent).toBe('Fetching YouTube data...');
      });

      it('populates form fields on successful API response', async () => {
        const mockResponse = {
          data: {
            success: true,
            data: {
              title: 'Test Video Title',
              description: 'Test video description content',
              source_url: 'https://youtube.com/watch?v=dQw4w9WgXcQ'
            }
          }
        };

        global.fetch = vi.fn().mockResolvedValue({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        });

        getYtTextData();

        // Wait for the promise chain to resolve
        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('Success!');
        });

        expect((document.querySelector('[name=TxTitle]') as HTMLInputElement).value).toBe('Test Video Title');
        expect((document.querySelector('[name=TxText]') as HTMLInputElement).value).toBe('Test video description content');
        expect((document.querySelector('[name=TxSourceURI]') as HTMLInputElement).value).toBe('https://youtube.com/watch?v=dQw4w9WgXcQ');
      });

      it('handles missing data in response', async () => {
        const mockResponse = {
          data: {
            success: true,
            data: undefined
          }
        };

        global.fetch = vi.fn().mockResolvedValue({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        });

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('No video data returned.');
        });
        expect((document.querySelector('[name=TxTitle]') as HTMLInputElement).value).toBe(''); // Unchanged
      });

      it('constructs correct API URL with server proxy', () => {
        global.fetch = vi.fn().mockReturnValue(new Promise(() => {}));

        getYtTextData();

        expect(global.fetch).toHaveBeenCalledWith(
          '/api/v1/youtube/video?video_id=dQw4w9WgXcQ'
        );
      });

      it('encodes video ID in URL', () => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="test&video=id">
        `;

        global.fetch = vi.fn().mockReturnValue(new Promise(() => {}));

        getYtTextData();

        expect(global.fetch).toHaveBeenCalledWith(
          `/api/v1/youtube/video?video_id=${encodeURIComponent('test&video=id')}`
        );
      });
    });

    describe('API errors', () => {
      beforeEach(() => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="video-id">
        `;
      });

      it('shows error for server error response', async () => {
        global.fetch = vi.fn().mockResolvedValue({
          ok: false,
          status: 500
        });

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Server error: 500');
        });
      });

      it('shows error for 403 status', async () => {
        global.fetch = vi.fn().mockResolvedValue({
          ok: false,
          status: 403
        });

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Server error: 403');
        });
      });

      it('shows error for 400 status', async () => {
        global.fetch = vi.fn().mockResolvedValue({
          ok: false,
          status: 400
        });

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Server error: 400');
        });
      });

      it('shows error when API returns success: false', async () => {
        const mockResponse = {
          data: {
            success: false,
            error: 'Invalid video ID'
          }
        };

        global.fetch = vi.fn().mockResolvedValue({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        });

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Invalid video ID');
        });
      });

      it('shows fallback error when API returns success: false without error message', async () => {
        const mockResponse = {
          data: {
            success: false
          }
        };

        global.fetch = vi.fn().mockResolvedValue({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        });

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Failed to fetch YouTube data.');
        });
      });

      it('handles network errors', async () => {
        global.fetch = vi.fn().mockRejectedValue(new Error('Network error'));

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Network error');
        });
      });
    });
  });

  // ===========================================================================
  // initYouTubeImport Tests
  // ===========================================================================

  describe('initYouTubeImport', () => {
    it('binds click handler to fetch-youtube buttons', () => {
      document.body.innerHTML = `
        <div id="ytDataStatus"></div>
        <input id="ytVideoId" value="">
        <button data-action="fetch-youtube">Fetch</button>
      `;

      initYouTubeImport();

      const button = document.querySelector<HTMLButtonElement>('[data-action="fetch-youtube"]')!;
      button.click();

      // Should try to fetch and show validation error
      expect(document.getElementById('ytDataStatus')!.textContent).toBe('Please enter a YouTube Video ID.');
    });

    it('prevents default button action', () => {
      document.body.innerHTML = `
        <div id="ytDataStatus"></div>
        <input id="ytVideoId" value="">
        <button data-action="fetch-youtube">Fetch</button>
      `;

      initYouTubeImport();

      const button = document.querySelector<HTMLButtonElement>('[data-action="fetch-youtube"]')!;
      const clickEvent = new MouseEvent('click', { cancelable: true, bubbles: true });
      button.dispatchEvent(clickEvent);

      expect(clickEvent.defaultPrevented).toBe(true);
    });

    it('handles multiple fetch buttons', () => {
      document.body.innerHTML = `
        <div id="ytDataStatus"></div>
        <input id="ytVideoId" value="">
        <button data-action="fetch-youtube">Fetch 1</button>
        <button data-action="fetch-youtube">Fetch 2</button>
      `;

      initYouTubeImport();

      const buttons = document.querySelectorAll<HTMLButtonElement>('[data-action="fetch-youtube"]');
      expect(buttons.length).toBe(2);

      // Both buttons should work
      buttons.forEach((button) => {
        expect(() => button.click()).not.toThrow();
      });
    });

    it('works with dynamically added buttons (event delegation)', () => {
      document.body.innerHTML = `
        <div id="ytDataStatus"></div>
        <input id="ytVideoId" value="">
        <div id="container"></div>
      `;

      initYouTubeImport();

      // Add button after init
      const newButton = document.createElement('button');
      newButton.setAttribute('data-action', 'fetch-youtube');
      newButton.textContent = 'Dynamic Fetch';
      document.getElementById('container')!.appendChild(newButton);

      newButton.click();

      // Should still work due to event delegation
      expect(document.getElementById('ytDataStatus')!.textContent).toBe('Please enter a YouTube Video ID.');
    });
  });

  // ===========================================================================
  // Form Integration Tests
  // ===========================================================================

  describe('form integration', () => {
    it('trims video ID before use', async () => {
      document.body.innerHTML = `
        <div id="ytDataStatus"></div>
        <input id="ytVideoId" value="  video-id  ">
        <input name="TxSourceURI" value="">
        <input name="TxTitle" value="">
        <input name="TxText" value="">
      `;

      const mockResponse = {
        data: {
          success: true,
          data: {
            title: 'Test',
            description: 'Desc',
            source_url: 'https://youtube.com/watch?v=video-id'
          }
        }
      };

      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      getYtTextData();

      await vi.waitFor(() => {
        expect(document.getElementById('ytDataStatus')!.textContent).toBe('Success!');
      });

      // API should be called with trimmed video ID
      expect(global.fetch).toHaveBeenCalledWith('/api/v1/youtube/video?video_id=video-id');
    });
  });
});

/**
 * Tests for youtube_import.ts - Fetch text data from YouTube videos
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { getYtTextData, initYouTubeImport } from '../../../src/frontend/js/texts/youtube_import';

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
          <input id="ytApiKey" value="api-key">
        `;

        getYtTextData();

        expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Missing YouTube input fields.');
      });

      it('shows error when ytApiKey input is missing', () => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="video-id">
        `;

        getYtTextData();

        expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Missing YouTube input fields.');
      });

      it('shows error when video ID is empty', () => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="">
          <input id="ytApiKey" value="api-key">
        `;

        getYtTextData();

        expect(document.getElementById('ytDataStatus')!.textContent).toBe('Please enter a YouTube Video ID.');
      });

      it('shows error when video ID is whitespace only', () => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="   ">
          <input id="ytApiKey" value="api-key">
        `;

        getYtTextData();

        expect(document.getElementById('ytDataStatus')!.textContent).toBe('Please enter a YouTube Video ID.');
      });

      it('shows error when API key is empty', () => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="video-id">
          <input id="ytApiKey" value="">
        `;

        getYtTextData();

        expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: YouTube API key not configured.');
      });
    });

    describe('API success', () => {
      beforeEach(() => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="dQw4w9WgXcQ">
          <input id="ytApiKey" value="test-api-key">
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
          items: [{
            snippet: {
              title: 'Test Video Title',
              description: 'Test video description content'
            }
          }]
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

      it('handles empty items array', async () => {
        const mockResponse = {
          items: []
        };

        global.fetch = vi.fn().mockResolvedValue({
          ok: true,
          json: () => Promise.resolve(mockResponse)
        });

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('No videos found.');
        });
        expect((document.querySelector('[name=TxTitle]') as HTMLInputElement).value).toBe(''); // Unchanged
      });

      it('constructs correct API URL', () => {
        global.fetch = vi.fn().mockReturnValue(new Promise(() => {}));

        getYtTextData();

        expect(global.fetch).toHaveBeenCalledWith(
          expect.stringContaining('https://www.googleapis.com/youtube/v3/videos')
        );
        expect(global.fetch).toHaveBeenCalledWith(
          expect.stringContaining('id=dQw4w9WgXcQ')
        );
        expect(global.fetch).toHaveBeenCalledWith(
          expect.stringContaining('key=test-api-key')
        );
      });

      it('encodes video ID in URL', () => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="test&video=id">
          <input id="ytApiKey" value="api-key">
        `;

        global.fetch = vi.fn().mockReturnValue(new Promise(() => {}));

        getYtTextData();

        expect(global.fetch).toHaveBeenCalledWith(
          expect.stringContaining(encodeURIComponent('test&video=id'))
        );
      });
    });

    describe('API errors', () => {
      beforeEach(() => {
        document.body.innerHTML = `
          <div id="ytDataStatus"></div>
          <input id="ytVideoId" value="video-id">
          <input id="ytApiKey" value="api-key">
        `;
      });

      it('shows error for 403 status (invalid API key)', async () => {
        global.fetch = vi.fn().mockResolvedValue({
          ok: false,
          status: 403,
          statusText: 'Forbidden'
        });

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Invalid API key or quota exceeded.');
        });
      });

      it('shows error for 400 status (invalid video ID)', async () => {
        global.fetch = vi.fn().mockResolvedValue({
          ok: false,
          status: 400,
          statusText: 'Bad Request'
        });

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Invalid video ID.');
        });
      });

      it('shows generic error for other status codes', async () => {
        global.fetch = vi.fn().mockResolvedValue({
          ok: false,
          status: 500,
          statusText: 'Internal Server Error'
        });

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Internal Server Error');
        });
      });

      it('shows fallback error when statusText is empty', async () => {
        global.fetch = vi.fn().mockResolvedValue({
          ok: false,
          status: 0,
          statusText: ''
        });

        getYtTextData();

        await vi.waitFor(() => {
          expect(document.getElementById('ytDataStatus')!.textContent).toBe('Error: Failed to fetch YouTube data.');
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
        <input id="ytApiKey" value="api-key">
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
        <input id="ytApiKey" value="">
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
        <input id="ytApiKey" value="key">
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
        <input id="ytApiKey" value="key">
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
        <input id="ytApiKey" value="api-key">
        <input name="TxSourceURI" value="">
        <input name="TxTitle" value="">
        <input name="TxText" value="">
      `;

      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({ items: [{ snippet: { title: 'Test', description: 'Desc' } }] })
      });

      getYtTextData();

      await vi.waitFor(() => {
        expect(document.getElementById('ytDataStatus')!.textContent).toBe('Success!');
      });

      // URL should use trimmed video ID
      expect((document.querySelector('[name=TxSourceURI]') as HTMLInputElement).value).toBe('https://youtube.com/watch?v=video-id');
    });

    it('trims API key before use', () => {
      document.body.innerHTML = `
        <div id="ytDataStatus"></div>
        <input id="ytVideoId" value="video-id">
        <input id="ytApiKey" value="  api-key  ">
      `;

      global.fetch = vi.fn().mockReturnValue(new Promise(() => {}));

      getYtTextData();

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('key=api-key')
      );
    });
  });
});

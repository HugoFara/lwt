/**
 * YouTube Import - Fetch text data from YouTube videos.
 *
 * Allows importing video title, description, and source URL from YouTube
 * using the YouTube Data API v3.
 *
 * @license unlicense
 * @since   3.0.0
 */

/**
 * YouTube API response structure.
 */
interface YouTubeVideoSnippet {
  title: string;
  description: string;
}

interface YouTubeVideoItem {
  snippet: YouTubeVideoSnippet;
}

interface YouTubeApiResponse {
  items: YouTubeVideoItem[];
}

/**
 * Set the value of a form input by name attribute.
 */
function setInputByName(name: string, value: string): void {
  const el = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(`[name="${name}"]`);
  if (el) {
    el.value = value;
  }
}

/**
 * Set the status message for YouTube data fetching.
 *
 * @param msg - The status message to display
 */
function setYtDataStatus(msg: string): void {
  const statusEl = document.getElementById('ytDataStatus');
  if (statusEl) {
    statusEl.textContent = msg;
  }
}

/**
 * Handle successful YouTube API response.
 * Populates the text form fields with video data.
 *
 * @param data - The YouTube API response
 * @param videoId - The YouTube video ID
 */
function handleFetchSuccess(data: YouTubeApiResponse, videoId: string): void {
  if (data.items.length === 0) {
    setYtDataStatus('No videos found.');
  } else {
    setYtDataStatus('Success!');
    const snippet = data.items[0].snippet;
    setInputByName('TxTitle', snippet.title);
    setInputByName('TxText', snippet.description);
    setInputByName('TxSourceURI', `https://youtube.com/watch?v=${videoId}`);
  }
}

/**
 * Fetch text data from YouTube using the YouTube Data API v3.
 * Requires a valid API key configured in the application.
 */
export function getYtTextData(): void {
  setYtDataStatus('Fetching YouTube data...');

  const ytVideoIdInput = document.getElementById('ytVideoId') as HTMLInputElement | null;
  const ytApiKeyInput = document.getElementById('ytApiKey') as HTMLInputElement | null;

  if (!ytVideoIdInput || !ytApiKeyInput) {
    setYtDataStatus('Error: Missing YouTube input fields.');
    return;
  }

  const ytVideoId = ytVideoIdInput.value.trim();
  const apiKey = ytApiKeyInput.value.trim();

  if (!ytVideoId) {
    setYtDataStatus('Please enter a YouTube Video ID.');
    return;
  }

  if (!apiKey) {
    setYtDataStatus('Error: YouTube API key not configured.');
    return;
  }

  const url = `https://www.googleapis.com/youtube/v3/videos?part=snippet&id=${encodeURIComponent(ytVideoId)}&key=${encodeURIComponent(apiKey)}`;

  fetch(url)
    .then((response) => {
      if (!response.ok) {
        if (response.status === 403) {
          throw new Error('Invalid API key or quota exceeded.');
        } else if (response.status === 400) {
          throw new Error('Invalid video ID.');
        } else {
          throw new Error(response.statusText || 'Failed to fetch YouTube data.');
        }
      }
      return response.json() as Promise<YouTubeApiResponse>;
    })
    .then((data) => handleFetchSuccess(data, ytVideoId))
    .catch((error: Error) => {
      setYtDataStatus(`Error: ${error.message}`);
    });
}

/**
 * Initialize YouTube import functionality.
 * Binds click handler to the fetch button.
 */
export function initYouTubeImport(): void {
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    const actionEl = target.closest('[data-action="fetch-youtube"]');
    if (actionEl) {
      e.preventDefault();
      getYtTextData();
    }
  });
}

// Auto-initialize on document ready
document.addEventListener('DOMContentLoaded', initYouTubeImport);

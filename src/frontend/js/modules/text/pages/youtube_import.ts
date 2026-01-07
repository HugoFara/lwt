/**
 * YouTube Import - Fetch text data from YouTube videos.
 *
 * Allows importing video title, description, and source URL from YouTube
 * via a server-side proxy to keep the API key secure.
 *
 * @license unlicense
 * @since   3.0.0
 */

/**
 * Server proxy response structure.
 */
interface YouTubeProxyResponse {
  success: boolean;
  data?: {
    title: string;
    description: string;
    source_url: string;
  };
  error?: string;
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
 * Handle successful proxy response.
 * Populates the text form fields with video data.
 *
 * @param data - The proxy response data
 */
function handleFetchSuccess(data: YouTubeProxyResponse['data']): void {
  if (!data) {
    setYtDataStatus('No video data returned.');
    return;
  }
  setYtDataStatus('Success!');
  setInputByName('TxTitle', data.title);
  setInputByName('TxText', data.description);
  setInputByName('TxSourceURI', data.source_url);
}

/**
 * Fetch text data from YouTube via server-side proxy.
 * The API key is kept server-side for security.
 */
export function getYtTextData(): void {
  setYtDataStatus('Fetching YouTube data...');

  const ytVideoIdInput = document.getElementById('ytVideoId') as HTMLInputElement | null;

  if (!ytVideoIdInput) {
    setYtDataStatus('Error: Missing YouTube video ID input field.');
    return;
  }

  const ytVideoId = ytVideoIdInput.value.trim();

  if (!ytVideoId) {
    setYtDataStatus('Please enter a YouTube Video ID.');
    return;
  }

  const url = `/api/v1/youtube/video?video_id=${encodeURIComponent(ytVideoId)}`;

  fetch(url)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Server error: ${response.status}`);
      }
      return response.json() as Promise<{ data: YouTubeProxyResponse }>;
    })
    .then((response) => {
      const result = response.data;
      if (!result.success) {
        throw new Error(result.error || 'Failed to fetch YouTube data.');
      }
      handleFetchSuccess(result.data);
    })
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

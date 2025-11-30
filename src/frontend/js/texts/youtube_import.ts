/**
 * YouTube Import - Fetch text data from YouTube videos.
 *
 * Allows importing video title, description, and source URL from YouTube
 * using the YouTube Data API v3.
 *
 * @license unlicense
 * @since   3.0.0
 */

import $ from 'jquery';

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
 * Set the status message for YouTube data fetching.
 *
 * @param msg - The status message to display
 */
function setYtDataStatus(msg: string): void {
  $('#ytDataStatus').text(msg);
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
    $('[name=TxTitle]').val(snippet.title);
    $('[name=TxText]').val(snippet.description);
    $('[name=TxSourceURI]').val(`https://youtube.com/watch?v=${videoId}`);
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

  $.get(url)
    .done((data: YouTubeApiResponse) => handleFetchSuccess(data, ytVideoId))
    .fail((jqXHR) => {
      if (jqXHR.status === 403) {
        setYtDataStatus('Error: Invalid API key or quota exceeded.');
      } else if (jqXHR.status === 400) {
        setYtDataStatus('Error: Invalid video ID.');
      } else {
        setYtDataStatus(`Error: ${jqXHR.statusText || 'Failed to fetch YouTube data.'}`);
      }
    });
}

/**
 * Initialize YouTube import functionality.
 * Binds click handler to the fetch button.
 */
export function initYouTubeImport(): void {
  $(document).on('click', '[data-action="fetch-youtube"]', function (e) {
    e.preventDefault();
    getYtTextData();
  });
}

// Auto-initialize on document ready
$(document).ready(initYouTubeImport);

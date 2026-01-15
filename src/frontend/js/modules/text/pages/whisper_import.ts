/**
 * Whisper Import - Handle audio/video transcription on text edit form.
 *
 * Uses the NLP microservice Whisper endpoints for speech-to-text transcription.
 *
 * @license unlicense
 * @since   3.0.0
 */

/**
 * Transcription job status from the API.
 */
interface TranscriptionStatus {
  job_id: string;
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled';
  progress: number;
  message: string;
}

/**
 * Transcription result from the API.
 */
interface TranscriptionResult {
  job_id: string;
  text: string;
  language: string;
  duration_seconds: number;
}

/**
 * Language option from the API.
 */
interface LanguageOption {
  code: string;
  name: string;
}

/**
 * Audio/video file extensions supported by Whisper.
 */
const AUDIO_VIDEO_EXTENSIONS = [
  'mp3', 'mp4', 'wav', 'webm', 'ogg', 'm4a', 'mkv', 'flac', 'avi', 'mov', 'wma', 'aac'
];

/**
 * Polling interval in milliseconds.
 */
const POLL_INTERVAL_MS = 2000;

/**
 * Current job ID being tracked.
 */
let currentJobId: string | null = null;

/**
 * Polling timer reference.
 */
let pollTimer: number | null = null;

/**
 * Flag to track if Whisper is available.
 */
let whisperAvailable: boolean | null = null;

/**
 * Check if a file is an audio/video file that needs Whisper transcription.
 */
export function isAudioVideoFile(filename: string): boolean {
  const ext = filename.split('.').pop()?.toLowerCase() ?? '';
  return AUDIO_VIDEO_EXTENSIONS.includes(ext);
}

/**
 * Set the value of a form input by name attribute.
 */
function setInputByName(name: string, value: string): void {
  const el = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(
    `[name="${name}"]`
  );
  if (el) {
    el.value = value;
  }
}

/**
 * Get the value of a form input by name attribute.
 */
function getInputByName(name: string): string {
  const el = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(
    `[name="${name}"]`
  );
  return el?.value ?? '';
}

/**
 * Update the import status message.
 */
function setImportStatus(msg: string, isError = false, isInfo = false): void {
  const statusEl = document.getElementById('importFileStatus');
  if (statusEl) {
    statusEl.textContent = msg;
    statusEl.classList.remove('has-text-danger', 'has-text-success', 'has-text-info');
    if (isError) {
      statusEl.classList.add('has-text-danger');
    } else if (isInfo) {
      statusEl.classList.add('has-text-info');
    } else if (msg) {
      statusEl.classList.add('has-text-success');
    }
  }
}

/**
 * Show the Whisper options panel.
 */
function showWhisperOptions(): void {
  const optionsPanel = document.getElementById('whisperOptions');
  const unavailablePanel = document.getElementById('whisperUnavailable');

  if (whisperAvailable === false) {
    if (optionsPanel) optionsPanel.style.display = 'none';
    if (unavailablePanel) unavailablePanel.style.display = 'block';
    return;
  }

  if (optionsPanel) optionsPanel.style.display = 'block';
  if (unavailablePanel) unavailablePanel.style.display = 'none';
}

/**
 * Hide the Whisper options panel.
 */
function hideWhisperOptions(): void {
  const optionsPanel = document.getElementById('whisperOptions');
  const unavailablePanel = document.getElementById('whisperUnavailable');

  if (optionsPanel) optionsPanel.style.display = 'none';
  if (unavailablePanel) unavailablePanel.style.display = 'none';
}

/**
 * Show the progress indicator.
 */
function showProgress(message: string): void {
  const progressPanel = document.getElementById('whisperProgress');
  const statusText = document.getElementById('whisperStatusText');
  const progressBar = document.getElementById('whisperProgressBar') as HTMLProgressElement;
  const startBtn = document.getElementById('startTranscription') as HTMLButtonElement;

  if (progressPanel) progressPanel.style.display = 'block';
  if (statusText) statusText.textContent = message;
  if (progressBar) progressBar.value = 0;
  if (startBtn) startBtn.disabled = true;
}

/**
 * Hide the progress indicator.
 */
function hideProgress(): void {
  const progressPanel = document.getElementById('whisperProgress');
  const startBtn = document.getElementById('startTranscription') as HTMLButtonElement;

  if (progressPanel) progressPanel.style.display = 'none';
  if (startBtn) startBtn.disabled = false;
}

/**
 * Update the progress display.
 */
function updateProgress(progress: number, message: string): void {
  const statusText = document.getElementById('whisperStatusText');
  const progressBar = document.getElementById('whisperProgressBar') as HTMLProgressElement;

  if (statusText) statusText.textContent = message;
  if (progressBar) progressBar.value = progress;
}

/**
 * Check if Whisper transcription is available.
 */
async function checkWhisperAvailable(): Promise<boolean> {
  if (whisperAvailable !== null) {
    return whisperAvailable;
  }

  try {
    const response = await fetch('/api/v1/whisper/available');
    const data = await response.json();
    whisperAvailable = data.data?.available === true;
    return whisperAvailable;
  } catch {
    whisperAvailable = false;
    return false;
  }
}

/**
 * Load available languages into the dropdown.
 */
async function loadWhisperLanguages(): Promise<void> {
  const select = document.getElementById('whisperLanguage') as HTMLSelectElement;
  if (!select) return;

  try {
    const response = await fetch('/api/v1/whisper/languages');
    const data = await response.json();
    const languages: LanguageOption[] = data.data?.languages ?? [];

    // Keep auto-detect option, add languages
    select.innerHTML = '<option value="">Auto-detect</option>';
    for (const lang of languages) {
      const option = document.createElement('option');
      option.value = lang.code;
      option.textContent = lang.name;
      select.appendChild(option);
    }
  } catch (e) {
    console.error('Failed to load Whisper languages:', e);
  }
}

/**
 * Start the transcription process.
 */
async function startTranscription(): Promise<void> {
  const fileInput = document.getElementById('importFile') as HTMLInputElement;
  const file = fileInput?.files?.[0];

  if (!file) {
    setImportStatus('Please select an audio/video file first.', true);
    return;
  }

  const languageSelect = document.getElementById('whisperLanguage') as HTMLSelectElement;
  const modelSelect = document.getElementById('whisperModel') as HTMLSelectElement;

  const language = languageSelect?.value || '';
  const model = modelSelect?.value || 'small';

  showProgress('Uploading file...');

  try {
    const formData = new FormData();
    formData.append('file', file);
    if (language) formData.append('language', language);
    formData.append('model', model);

    const response = await fetch('/api/v1/whisper/transcribe', {
      method: 'POST',
      body: formData,
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error || 'Failed to start transcription');
    }

    if (!data.data?.job_id) {
      throw new Error('No job_id returned');
    }

    currentJobId = data.data.job_id;
    updateProgress(5, 'Transcription started...');
    startPolling();

  } catch (e) {
    hideProgress();
    setImportStatus(
      `Transcription failed: ${e instanceof Error ? e.message : 'Unknown error'}`,
      true
    );
  }
}

/**
 * Start polling for job status.
 */
function startPolling(): void {
  if (pollTimer !== null) {
    window.clearInterval(pollTimer);
  }

  pollTimer = window.setInterval(async () => {
    if (!currentJobId) {
      stopPolling();
      return;
    }

    try {
      const response = await fetch(`/api/v1/whisper/status/${currentJobId}`);
      const data = await response.json();
      const status: TranscriptionStatus = data.data;

      updateProgress(status.progress, status.message || 'Processing...');

      if (status.status === 'completed') {
        stopPolling();
        await fetchResult();
      } else if (status.status === 'failed') {
        stopPolling();
        hideProgress();
        setImportStatus(`Transcription failed: ${status.message}`, true);
        currentJobId = null;
      } else if (status.status === 'cancelled') {
        stopPolling();
        hideProgress();
        setImportStatus('Transcription was cancelled.', false, true);
        currentJobId = null;
      }
    } catch (e) {
      console.error('Polling error:', e);
    }
  }, POLL_INTERVAL_MS);
}

/**
 * Stop polling for job status.
 */
function stopPolling(): void {
  if (pollTimer !== null) {
    window.clearInterval(pollTimer);
    pollTimer = null;
  }
}

/**
 * Fetch the completed transcription result.
 */
async function fetchResult(): Promise<void> {
  if (!currentJobId) return;

  try {
    const response = await fetch(`/api/v1/whisper/result/${currentJobId}`);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error || 'Failed to get result');
    }

    const result: TranscriptionResult = data.data;

    // Populate form fields
    setInputByName('TxText', result.text);

    // Auto-set title from filename if empty
    const fileInput = document.getElementById('importFile') as HTMLInputElement;
    if (!getInputByName('TxTitle') && fileInput?.files?.[0]) {
      const filename = fileInput.files[0].name;
      const title = filename.replace(/\.[^/.]+$/, ''); // Remove extension
      setInputByName('TxTitle', title);
    }

    // Auto-fill media URI if empty
    const fileExtensions = ['mp3', 'mp4', 'wav', 'webm', 'ogg', 'm4a'];
    if (!getInputByName('TxAudioURI') && fileInput?.files?.[0]) {
      const ext = fileInput.files[0].name.split('.').pop()?.toLowerCase() ?? '';
      if (fileExtensions.includes(ext)) {
        // Suggest saving to media folder
        setImportStatus(
          `Transcription complete! (${formatDuration(result.duration_seconds)}) - Consider copying the audio file to the media folder.`,
          false,
          false
        );
      }
    }

    hideProgress();

    const durationStr = formatDuration(result.duration_seconds);
    setImportStatus(
      `Transcription complete! Processed ${durationStr} of audio.`
    );

    currentJobId = null;

  } catch (e) {
    hideProgress();
    setImportStatus(
      `Failed to fetch result: ${e instanceof Error ? e.message : 'Unknown error'}`,
      true
    );
    currentJobId = null;
  }
}

/**
 * Format duration in seconds to human readable format.
 */
function formatDuration(seconds: number): string {
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  if (mins === 0) {
    return `${secs} second${secs !== 1 ? 's' : ''}`;
  }
  return `${mins} minute${mins !== 1 ? 's' : ''} ${secs} second${secs !== 1 ? 's' : ''}`;
}

/**
 * Cancel the current transcription job.
 */
async function cancelTranscription(): Promise<void> {
  if (!currentJobId) return;

  try {
    await fetch(`/api/v1/whisper/job/${currentJobId}`, { method: 'DELETE' });
  } catch (e) {
    console.error('Failed to cancel job:', e);
  }

  stopPolling();
  hideProgress();
  currentJobId = null;
  setImportStatus('Transcription cancelled.', false, true);
}

/**
 * Handle file selection - show/hide options based on file type.
 */
export function handleFileSelection(file: File | null): void {
  if (!file) {
    hideWhisperOptions();
    return;
  }

  if (isAudioVideoFile(file.name)) {
    // Check if Whisper is available, then show options
    checkWhisperAvailable().then((available) => {
      if (available) {
        showWhisperOptions();
        loadWhisperLanguages();
      } else {
        // Show unavailable message
        const unavailablePanel = document.getElementById('whisperUnavailable');
        if (unavailablePanel) unavailablePanel.style.display = 'block';
      }
    });
    setImportStatus('Audio/video file selected. Use the transcription options below.', false, true);
  } else {
    hideWhisperOptions();
  }
}

/**
 * Initialize Whisper import functionality.
 *
 * Only initializes if the whisper-specific UI elements are present on the page.
 * This prevents unnecessary API calls on pages that don't have the import form.
 */
export function initWhisperImport(): void {
  const startBtn = document.getElementById('startTranscription');
  const cancelBtn = document.getElementById('whisperCancel');

  // Only initialize if whisper UI elements exist on the page
  if (!startBtn && !cancelBtn) {
    return;
  }

  // Handle start transcription
  startBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    startTranscription();
  });

  // Handle cancel
  cancelBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    cancelTranscription();
  });
}

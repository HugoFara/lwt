/**
 * Web Page Import - Fetch text content from any web page URL.
 *
 * Allows importing article title and text from a URL via server-side
 * content extraction, then populates the text creation form.
 *
 * @license unlicense
 * @since   3.0.0
 */

import { onDomReady } from '@shared/utils/dom_ready';

/**
 * Server extraction response structure.
 */
interface ExtractUrlResponse {
  title: string;
  text: string;
  sourceUri: string;
}

/**
 * Set the value of a form input by name attribute.
 */
function setInputByName(name: string, value: string): void {
  const el = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(
    `[name="${name}"]`,
  );
  if (el) {
    el.value = value;
  }
}

/**
 * Set the status message for webpage import.
 */
function setWebpageStatus(msg: string, isError = false): void {
  const el = document.getElementById('webpageImportStatus');
  if (!el) return;
  el.textContent = msg;
  el.classList.remove('has-text-danger', 'has-text-success');
  if (isError) {
    el.classList.add('has-text-danger');
  } else if (msg) {
    el.classList.add('has-text-success');
  }
}

/**
 * Fetch and extract text content from a web page URL.
 */
async function fetchWebpage(): Promise<void> {
  const urlInput = document.getElementById(
    'webpageUrl',
  ) as HTMLInputElement | null;
  if (!urlInput) return;

  const url = urlInput.value.trim();
  if (!url) {
    setWebpageStatus('Please enter a URL.', true);
    return;
  }

  // Validate URL format client-side
  try {
    new URL(url);
  } catch {
    setWebpageStatus('Please enter a valid URL (e.g. https://example.com/article).', true);
    return;
  }

  const btn = document.getElementById(
    'fetchWebpageBtn',
  ) as HTMLButtonElement | null;
  if (btn) {
    btn.disabled = true;
    btn.classList.add('is-loading');
  }

  setWebpageStatus('Fetching page content...');

  try {
    const response = await fetch('/api/v1/texts/extract-url', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ url }),
    });

    const result = await response.json();

    if (!response.ok || result.error) {
      setWebpageStatus(result.error || `Server error: ${response.status}`, true);
      return;
    }

    const data = result.data as ExtractUrlResponse;

    // Populate form fields
    setInputByName('TxTitle', data.title);
    setInputByName('TxText', data.text);
    setInputByName('TxSourceURI', data.sourceUri);

    // Switch to manual/paste mode so the user can see the populated fields
    const formEl = document.querySelector<HTMLFormElement>('form[x-data]');
    if (formEl) {
      // Alpine.js v3: dispatch event to trigger reactive update
      formEl.dispatchEvent(
        new CustomEvent('webpage-imported', { bubbles: true }),
      );
    }

    setWebpageStatus(
      `Imported "${data.title}" — review the text below, then save.`,
    );
  } catch (error: unknown) {
    const msg = error instanceof Error ? error.message : 'Unknown error';
    setWebpageStatus(`Error: ${msg}`, true);
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.classList.remove('is-loading');
    }
  }
}

/**
 * Initialize webpage import functionality.
 * Binds click handler to the fetch button.
 */
export function initWebpageImport(): void {
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    if (target.closest('[data-action="fetch-webpage"]')) {
      e.preventDefault();
      fetchWebpage();
    }
  });
}

// Auto-initialize on document ready
onDomReady(initWebpageImport);

/**
 * Media Selection - Media file selection and path handling
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

interface MediaSelectResponse {
  error?: 'not_a_directory' | 'does_not_exist' | string;
  base_path?: string;
  paths?: string[];
  folders?: string[];
}

/**
 * Return an HTML group of options to add to a select field.
 *
 * @param paths      All paths (files and folders)
 * @param folders    Folders paths, should be a subset of paths
 *
 * @returns List of options to append to the select.
 */
export function createMediaPathOptions(
  paths: string[],
  folders: string[]
): HTMLOptionElement[] {
  const options: HTMLOptionElement[] = [];
  let temp_option = document.createElement('option');
  temp_option.value = '';
  temp_option.text = '[Choose...]';
  options.push(temp_option);
  for (let i = 0; i < paths.length; i++) {
    temp_option = document.createElement('option');
    if (folders.includes(paths[i])) {
      temp_option.setAttribute('disabled', 'disabled');
      temp_option.text = '-- Directory: ' + paths[i] + '--';
    } else {
      temp_option.value = paths[i];
      temp_option.text = paths[i];
    }
    options.push(temp_option);
  }
  return options;
}

/**
 * Process the received data from media selection query
 *
 * @param data Received data as a JSON object
 */
export function handleMediaSelectResponse(data: MediaSelectResponse): void {
  const loadingImg = document.getElementById('mediaSelectLoadingImg');
  const errorEl = document.getElementById('mediaSelectErrorMessage');
  const selectEl = document.querySelector<HTMLSelectElement>('#mediaselect select');

  if (loadingImg) {
    loadingImg.style.display = 'none';
  }

  if (data.error !== undefined) {
    let msg: string;
    if (data.error === 'not_a_directory') {
      msg = '[Error: "../' + data.base_path + '/media" exists, but it is not a directory.]';
    } else if (data.error === 'does_not_exist') {
      msg = '[Directory "../' + data.base_path + '/media" does not yet exist.]';
    } else {
      msg = '[Unknown error!]';
    }
    if (errorEl) {
      errorEl.textContent = msg;
      errorEl.style.display = 'inherit';
    }
  } else {
    const options = createMediaPathOptions(data.paths || [], data.folders || []);
    if (selectEl) {
      selectEl.innerHTML = '';
      for (let i = 0; i < options.length; i++) {
        selectEl.appendChild(options[i]);
      }
      selectEl.style.display = 'inherit';
    }
  }
}

/**
 * Perform an AJAX query to retrieve and display the media files path.
 */
export function refreshMediaSelect(): void {
  const loadingImg = document.getElementById('mediaSelectLoadingImg');
  const errorEl = document.getElementById('mediaSelectErrorMessage');
  const selectEl = document.querySelector<HTMLSelectElement>('#mediaselect select');

  if (errorEl) {
    errorEl.style.display = 'none';
  }
  if (selectEl) {
    selectEl.style.display = 'none';
  }
  if (loadingImg) {
    loadingImg.style.display = 'inherit';
  }

  fetch('/api/v1/media-files')
    .then(response => response.json())
    .then(handleMediaSelectResponse)
    .catch(error => {
      console.error('Failed to fetch media files:', error);
      handleMediaSelectResponse({ error: 'fetch_failed' });
    });
}

/**
 * Auto-initialize media selection from JSON config element.
 */
export function autoInitMediaSelect(): void {
  const configEl = document.querySelector<HTMLScriptElement>('script[data-lwt-media-select-config]');
  if (configEl) {
    try {
      const config = JSON.parse(configEl.textContent || '{}') as MediaSelectResponse;
      handleMediaSelectResponse(config);
    } catch (e) {
      console.error('Failed to parse media select config:', e);
    }
  }
}

/**
 * Handle media directory selection change.
 * Copies selected path to the target field.
 *
 * @param selectEl - The directory select element
 * @param targetFieldName - Name of the form field to update
 */
export function handleMediaDirChange(selectEl: HTMLSelectElement, targetFieldName: string): void {
  const val = selectEl.value;
  if (val !== '') {
    const form = selectEl.form;
    if (form) {
      const targetField = form.elements.namedItem(targetFieldName) as HTMLInputElement | null;
      if (targetField) {
        targetField.value = val;
      }
    }
    selectEl.value = '';
  }
}

/**
 * Initialize media selection event handlers.
 */
function initMediaSelectionEvents(): void {
  // Refresh media selection button
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    const refreshBtn = target.closest('[data-action="refresh-media-select"]');
    if (refreshBtn) {
      e.preventDefault();
      refreshMediaSelect();
    }
  });

  // Media directory select change
  document.addEventListener('change', (e) => {
    const target = e.target as HTMLElement;
    if (target instanceof HTMLSelectElement && target.dataset.action === 'media-dir-select') {
      const targetField = target.dataset.targetField;
      if (targetField) {
        handleMediaDirChange(target, targetField);
      }
    }
  });
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  autoInitMediaSelect();
  initMediaSelectionEvents();
});

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  w.createMediaPathOptions = createMediaPathOptions;
  w.handleMediaSelectResponse = handleMediaSelectResponse;
  w.refreshMediaSelect = refreshMediaSelect;
  w.handleMediaDirChange = handleMediaDirChange;
}

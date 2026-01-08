/**
 * Term Edit Modal - Standalone modal for editing terms via API.
 *
 * Provides a simple modal form for editing terms from the annotation page,
 * using the generic modal component and TermsApi for data loading/saving.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { openModal, closeModal } from '@shared/components/modal';
import {
  TermsApi,
  type TermForEditResponse,
  type TermCreateFullRequest,
  type TermUpdateFullRequest
} from '@modules/vocabulary/api/terms_api';
import { escapeHtml } from '@shared/utils/html_utils';

/** Status definitions for the form */
const STATUSES = [
  { value: 1, label: 'Learning (1)' },
  { value: 2, label: 'Learning (2)' },
  { value: 3, label: 'Learning (3)' },
  { value: 4, label: 'Learning (4)' },
  { value: 5, label: 'Learned' },
  { value: 99, label: 'Well Known' },
  { value: 98, label: 'Ignored' }
];

/** Current form context */
let currentContext: {
  textId: number;
  position: number;
  wordId: number | null;
  isNew: boolean;
  hex: string;
} | null = null;

/**
 * Render the edit form HTML.
 */
function renderForm(data: TermForEditResponse): string {
  const term = data.term;
  const lang = data.language;
  const translation = term.translation === '*' ? '' : term.translation;

  const statusOptions = STATUSES.map(s =>
    `<option value="${s.value}"${term.status === s.value ? ' selected' : ''}>${escapeHtml(s.label)}</option>`
  ).join('');

  const romanizationField = lang.showRomanization ? `
    <div class="field">
      <label class="label">Romanization</label>
      <div class="control">
        <input class="input" type="text" id="term-edit-romanization"
               value="${escapeHtml(term.romanization)}" maxlength="100">
      </div>
    </div>
  ` : '';

  return `
    <form id="term-edit-form">
      <div class="field">
        <label class="label">Term</label>
        <div class="control">
          <input class="input" type="text" value="${escapeHtml(term.text)}" readonly disabled>
        </div>
      </div>

      <div class="field">
        <label class="label">Translation</label>
        <div class="control">
          <textarea class="textarea" id="term-edit-translation" rows="2"
                    maxlength="500">${escapeHtml(translation)}</textarea>
        </div>
      </div>

      ${romanizationField}

      <div class="field">
        <label class="label">Sentence</label>
        <div class="control">
          <textarea class="textarea" id="term-edit-sentence" rows="2"
                    maxlength="1000">${escapeHtml(term.sentence)}</textarea>
        </div>
        <p class="help">Use {curly braces} around the term in the sentence.</p>
      </div>

      <div class="field">
        <label class="label">Status</label>
        <div class="control">
          <div class="select">
            <select id="term-edit-status">
              ${statusOptions}
            </select>
          </div>
        </div>
      </div>

      <div class="field is-grouped">
        <div class="control">
          <button type="submit" class="button is-primary" id="term-edit-save">Save</button>
        </div>
        <div class="control">
          <button type="button" class="button" id="term-edit-cancel">Cancel</button>
        </div>
      </div>

      <div id="term-edit-error" class="notification is-danger" style="display: none;"></div>
    </form>
  `;
}

/**
 * Handle form submission.
 */
async function handleSave(e: Event): Promise<void> {
  e.preventDefault();

  if (!currentContext) return;

  const saveBtn = document.getElementById('term-edit-save') as HTMLButtonElement;
  const errorEl = document.getElementById('term-edit-error');

  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.classList.add('is-loading');
  }
  if (errorEl) {
    errorEl.style.display = 'none';
  }

  const translation = (document.getElementById('term-edit-translation') as HTMLTextAreaElement)?.value || '';
  const romanization = (document.getElementById('term-edit-romanization') as HTMLInputElement)?.value || '';
  const sentence = (document.getElementById('term-edit-sentence') as HTMLTextAreaElement)?.value || '';
  const status = parseInt((document.getElementById('term-edit-status') as HTMLSelectElement)?.value || '1', 10);

  try {
    let response;

    if (currentContext.isNew) {
      const createData: TermCreateFullRequest = {
        textId: currentContext.textId,
        position: currentContext.position,
        translation,
        romanization,
        sentence,
        status,
        tags: []
      };
      response = await TermsApi.createFull(createData);
    } else {
      if (currentContext.wordId === null) {
        throw new Error('Word ID is missing');
      }
      const updateData: TermUpdateFullRequest = {
        translation,
        romanization,
        sentence,
        status,
        tags: []
      };
      response = await TermsApi.updateFull(currentContext.wordId, updateData);
    }

    if (response.error || response.data?.error) {
      throw new Error(response.error || response.data?.error || 'Failed to save');
    }

    // Success - close modal and dispatch event for parent page to refresh
    closeModal();

    // Dispatch event to notify annotation page to refresh
    if (response.data?.term) {
      document.dispatchEvent(new CustomEvent('lwt-term-saved', {
        detail: {
          wordId: response.data.term.id,
          hex: response.data.term.hex,
          text: response.data.term.textLc
        }
      }));
    }
  } catch (error) {
    if (errorEl) {
      errorEl.textContent = error instanceof Error ? error.message : 'Failed to save term';
      errorEl.style.display = 'block';
    }
  }

  if (saveBtn) {
    saveBtn.disabled = false;
    saveBtn.classList.remove('is-loading');
  }
}

/**
 * Open a modal to edit a term.
 *
 * @param textId   Text ID
 * @param position Word position in text
 * @param wordId   Word ID (optional, for existing terms)
 */
export async function openTermEditModal(
  textId: number,
  position: number,
  wordId?: number
): Promise<void> {
  // Show loading modal
  openModal('<div class="has-text-centered"><p>Loading...</p></div>', {
    title: 'Edit Term',
    closeOnEscape: true,
    closeOnOverlayClick: false
  });

  try {
    const response = await TermsApi.getForEdit(textId, position, wordId);

    if (response.error || !response.data) {
      openModal(`<p class="has-text-danger">${escapeHtml(response.error || 'Failed to load term data')}</p>`, {
        title: 'Error'
      });
      return;
    }

    if (response.data.error) {
      openModal(`<p class="has-text-danger">${escapeHtml(response.data.error)}</p>`, {
        title: 'Error'
      });
      return;
    }

    // Store context for save handler
    currentContext = {
      textId,
      position,
      wordId: response.data.term.id,
      isNew: response.data.isNew,
      hex: response.data.term.hex
    };

    // Render form
    const title = response.data.isNew ? 'Add Term' : 'Edit Term';
    openModal(renderForm(response.data), {
      title,
      closeOnEscape: true,
      closeOnOverlayClick: false
    });

    // Attach event listeners
    const form = document.getElementById('term-edit-form');
    const cancelBtn = document.getElementById('term-edit-cancel');

    if (form) {
      form.addEventListener('submit', handleSave);
    }
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => closeModal());
    }
  } catch (error) {
    openModal('<p class="has-text-danger">Failed to load term data</p>', {
      title: 'Error'
    });
  }
}

// Expose for global access (needed for inline onclick handlers)
declare global {
  interface Window {
    openTermEditModal: typeof openTermEditModal;
  }
}

window.openTermEditModal = openTermEditModal;

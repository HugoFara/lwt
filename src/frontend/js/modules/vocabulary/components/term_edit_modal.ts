/**
 * Term Edit Modal - Standalone modal for editing terms via API.
 *
 * Replaces the server-rendered /word/edit and /word/edit-term forms: the
 * /terms/for-edit endpoint already returns every field those pages rendered
 * (lemma, notes, tags, similar terms, dictionary URI), so the modal renders
 * from data instead of from PHP-emitted HTML.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { openModal, closeModal } from '@shared/components/modal';
import {
  TermsApi,
  type TermForEditResponse,
  type SimilarTermForEdit,
  type TermCreateFullRequest,
  type TermUpdateFullRequest
} from '@modules/vocabulary/api/terms_api';
import { escapeHtml } from '@shared/utils/html_utils';
import { getStatusDefinitions } from '@shared/stores/statuses';
import { createTheDictUrl } from '@modules/vocabulary/services/dictionary';
import { t } from '@shared/i18n/translator';

/** Current form context */
let currentContext: {
  textId: number;
  position: number;
  wordId: number | null;
  isNew: boolean;
  hex: string;
  /** Lowercase form the term text must keep — only recasing is allowed. */
  textLc: string;
} | null = null;

/** Field IDs, kept in one place so the render and read paths cannot drift. */
const FIELD = {
  form: 'term-edit-form',
  text: 'term-edit-text',
  translation: 'term-edit-translation',
  romanization: 'term-edit-romanization',
  lemma: 'term-edit-lemma',
  sentence: 'term-edit-sentence',
  notes: 'term-edit-notes',
  tags: 'term-edit-tags',
  status: 'term-edit-status',
  save: 'term-edit-save',
  cancel: 'term-edit-cancel',
  error: 'term-edit-error'
} as const;

/**
 * Read a form field's value, or '' when the field was not rendered.
 *
 * @param id Element ID
 *
 * @returns Trimmed-as-typed value
 */
function fieldValue(id: string): string {
  const el = document.getElementById(id);
  if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement
      || el instanceof HTMLSelectElement) {
    return el.value;
  }
  return '';
}

/**
 * Split a comma-separated tag input into a clean list.
 *
 * @param raw Raw input value
 *
 * @returns Non-empty, de-duplicated tags in entry order
 */
function parseTags(raw: string): string[] {
  const seen = new Set<string>();
  for (const part of raw.split(',')) {
    const tag = part.trim();
    if (tag !== '') {
      seen.add(tag);
    }
  }
  return [...seen];
}

/**
 * Render the similar-terms block, or '' when there are none.
 *
 * @param similar Similar terms from the API
 *
 * @returns HTML fragment
 */
function renderSimilarTerms(similar: SimilarTermForEdit[] | undefined): string {
  if (!similar || similar.length === 0) {
    return '';
  }
  const items = similar.map(term => {
    const translation = term.translation === '*' ? '' : term.translation;
    const suffix = translation === '' ? '' : ` — ${escapeHtml(translation)}`;
    return `<li><a href="/words/${term.id}/edit">${escapeHtml(term.text)}</a>${suffix}</li>`;
  }).join('');

  return `
    <div class="field">
      <span class="label">${escapeHtml(t('vocabulary.form.similar_terms'))}</span>
      <ul class="content is-small">${items}</ul>
    </div>
  `;
}

/**
 * Render the dictionary lookup link, or '' when the language has no URI.
 *
 * @param translateUri Language translation URI template
 * @param term         Term text to look up
 *
 * @returns HTML fragment
 */
function renderDictLink(translateUri: string | undefined, term: string): string {
  if (!translateUri || translateUri.trim() === '') {
    return '';
  }
  const url = createTheDictUrl(translateUri, term);
  return `
    <p class="help">
      <a href="${escapeHtml(url)}" target="_blank" rel="noopener noreferrer">
        ${escapeHtml(t('vocabulary.form.dictionary_lookup'))}
      </a>
    </p>
  `;
}

/**
 * Render the edit form HTML.
 *
 * @param data Term edit payload
 *
 * @returns HTML for the modal body
 */
function renderForm(data: TermForEditResponse): string {
  const term = data.term;
  const lang = data.language;
  const translation = term.translation === '*' ? '' : term.translation;

  const statusOptions = getStatusDefinitions().map(s =>
    `<option value="${s.value}"${term.status === s.value ? ' selected' : ''}>${escapeHtml(s.label)}</option>`
  ).join('');

  const romanizationField = lang.showRomanization ? `
    <div class="field">
      <label class="label" for="${FIELD.romanization}">${escapeHtml(t('vocabulary.common.romanization'))}</label>
      <div class="control">
        <input class="input" type="text" id="${FIELD.romanization}"
               placeholder="${escapeHtml(t('vocabulary.form.placeholder_romanization'))}"
               value="${escapeHtml(term.romanization)}" maxlength="100">
      </div>
    </div>
  ` : '';

  const tagOptions = (data.allTags ?? [])
    .map(tag => `<option value="${escapeHtml(tag)}">`)
    .join('');

  return `
    <form id="${FIELD.form}">
      <div class="field">
        <label class="label" for="${FIELD.text}">${escapeHtml(t('vocabulary.common.term'))}</label>
        <div class="control">
          <input class="input" type="text" id="${FIELD.text}"
                 value="${escapeHtml(term.text)}" maxlength="250"
                 ${term.id === null ? 'readonly disabled' : ''}>
        </div>
        <p class="help">${escapeHtml(t('vocabulary.form.uppercase_only_hint'))}</p>
        ${renderDictLink(lang.translateUri, term.text)}
      </div>

      <div class="field">
        <label class="label" for="${FIELD.translation}">${escapeHtml(t('vocabulary.common.translation'))}</label>
        <div class="control">
          <textarea class="textarea" id="${FIELD.translation}" rows="2"
                    placeholder="${escapeHtml(t('vocabulary.form.placeholder_translation'))}"
                    maxlength="500">${escapeHtml(translation)}</textarea>
        </div>
      </div>

      ${romanizationField}

      <div class="field">
        <label class="label" for="${FIELD.lemma}">${escapeHtml(t('vocabulary.common.lemma'))}</label>
        <div class="control">
          <input class="input" type="text" id="${FIELD.lemma}"
                 placeholder="${escapeHtml(t('vocabulary.form.placeholder_lemma_optional'))}"
                 value="${escapeHtml(term.lemma ?? '')}" maxlength="250">
        </div>
      </div>

      <div class="field">
        <label class="label" for="${FIELD.sentence}">${escapeHtml(t('vocabulary.common.sentence'))}</label>
        <div class="control">
          <textarea class="textarea" id="${FIELD.sentence}" rows="2"
                    maxlength="1000">${escapeHtml(term.sentence)}</textarea>
        </div>
        <p class="help">${escapeHtml(t('vocabulary.form.help_sentence_braces'))}</p>
      </div>

      <div class="field">
        <label class="label" for="${FIELD.notes}">${escapeHtml(t('vocabulary.common.notes'))}</label>
        <div class="control">
          <textarea class="textarea" id="${FIELD.notes}" rows="2"
                    placeholder="${escapeHtml(t('vocabulary.form.placeholder_notes'))}"
                    maxlength="1000">${escapeHtml(term.notes ?? '')}</textarea>
        </div>
      </div>

      <div class="field">
        <label class="label" for="${FIELD.tags}">${escapeHtml(t('vocabulary.common.tags'))}</label>
        <div class="control">
          <input class="input" type="text" id="${FIELD.tags}" list="term-edit-tag-options"
                 value="${escapeHtml((term.tags ?? []).join(', '))}">
          <datalist id="term-edit-tag-options">${tagOptions}</datalist>
        </div>
      </div>

      <div class="field">
        <label class="label" for="${FIELD.status}">${escapeHtml(t('vocabulary.common.status'))}</label>
        <div class="control">
          <div class="select">
            <select id="${FIELD.status}">
              ${statusOptions}
            </select>
          </div>
        </div>
      </div>

      ${renderSimilarTerms(data.similarTerms)}

      <div class="field is-grouped">
        <div class="control">
          <button type="submit" class="button is-primary" id="${FIELD.save}">
            ${escapeHtml(t('vocabulary.common.save'))}
          </button>
        </div>
        <div class="control">
          <button type="button" class="button" id="${FIELD.cancel}">
            ${escapeHtml(t('vocabulary.common.cancel'))}
          </button>
        </div>
      </div>

      <div id="${FIELD.error}" class="notification is-danger" style="display: none;"></div>
    </form>
  `;
}

/**
 * Show an error message inside the open form.
 *
 * @param message Message to display
 */
function showError(message: string): void {
  const errorEl = document.getElementById(FIELD.error);
  if (errorEl) {
    errorEl.textContent = message;
    errorEl.style.display = 'block';
  }
}

/**
 * Handle form submission.
 *
 * @param e Submit event
 */
async function handleSave(e: Event): Promise<void> {
  e.preventDefault();

  if (!currentContext) return;

  const saveBtn = document.getElementById(FIELD.save) as HTMLButtonElement | null;
  const errorEl = document.getElementById(FIELD.error);

  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.classList.add('is-loading');
  }
  if (errorEl) {
    errorEl.style.display = 'none';
  }

  const text = fieldValue(FIELD.text).trim();
  const translation = fieldValue(FIELD.translation);
  const romanization = fieldValue(FIELD.romanization);
  const lemma = fieldValue(FIELD.lemma).trim();
  const sentence = fieldValue(FIELD.sentence);
  const notes = fieldValue(FIELD.notes);
  const tags = parseTags(fieldValue(FIELD.tags));
  const status = parseInt(fieldValue(FIELD.status) || '1', 10);

  // Mirror the server rule so a recase typo is caught before the round trip.
  if (!currentContext.isNew && text !== '' && text.toLowerCase() !== currentContext.textLc) {
    showError(t('vocabulary.form.uppercase_only_hint'));
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.classList.remove('is-loading');
    }
    return;
  }

  try {
    let response;

    if (currentContext.isNew) {
      const createData: TermCreateFullRequest = {
        textId: currentContext.textId,
        position: currentContext.position,
        translation,
        romanization,
        sentence,
        notes,
        lemma,
        status,
        tags
      };
      response = await TermsApi.createFull(createData);
    } else {
      if (currentContext.wordId === null) {
        throw new Error('Word ID is missing');
      }
      const updateData: TermUpdateFullRequest = {
        text,
        translation,
        romanization,
        sentence,
        notes,
        lemma,
        status,
        tags
      };
      response = await TermsApi.updateFull(currentContext.wordId, updateData);
    }

    if (response.error || response.data?.error) {
      throw new Error(response.error || response.data?.error || 'Failed to save');
    }

    // Success - close modal and dispatch event for parent page to refresh
    closeModal();

    // Dispatch event to notify the host page to refresh
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
    showError(error instanceof Error ? error.message : 'Failed to save term');
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
  openModal(`<div class="has-text-centered"><p>${escapeHtml(t('vocabulary.common.loading'))}</p></div>`, {
    title: t('vocabulary.form.edit_term'),
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
      hex: response.data.term.hex,
      textLc: response.data.term.textLc ?? ''
    };

    // Render form
    const title = response.data.isNew
      ? t('vocabulary.form.new_term')
      : t('vocabulary.form.edit_term');
    openModal(renderForm(response.data), {
      title,
      closeOnEscape: true,
      closeOnOverlayClick: false
    });

    // Attach event listeners
    const form = document.getElementById(FIELD.form);
    const cancelBtn = document.getElementById(FIELD.cancel);

    if (form) {
      form.addEventListener('submit', handleSave);
    }
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => closeModal());
    }
  } catch {
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

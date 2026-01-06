/**
 * UI Utilities - DOM manipulation, tooltips, and form wrapping
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import { check } from '@shared/forms/form_validation';
import { changeImprAnnText, changeImprAnnRadio, do_ajax_show_similar_terms } from '@modules/vocabulary/services/term_operations';
import { readRawTextAloud } from './user_interactions';
import { initInlineEdit } from '@shared/components/inline_edit';
import { initTermTags, initTextTags } from '@shared/components/tagify_tags';
import { fetchTermTags, fetchTextTags } from '../stores/app_data';
import { spinnerHtml } from '@shared/icons/icons';

/**
 * Helper to safely get an HTML attribute value as a string.
 */
function getAttr(el: HTMLElement, attr: string): string {
  const val = el.getAttribute(attr);
  return typeof val === 'string' ? val : '';
}

/**
 * Enable or disable the mark action button based on checked items.
 * Enables the button if at least one checkbox with class 'markcheck' is checked.
 */
export function markClick(): void {
  const checkedCount = document.querySelectorAll('input.markcheck:checked').length;
  const markAction = document.getElementById('markaction') as HTMLButtonElement | null;
  if (markAction) {
    if (checkedCount > 0) {
      markAction.removeAttribute('disabled');
    } else {
      markAction.setAttribute('disabled', 'disabled');
    }
  }
}

/**
 * Show a confirmation dialog for delete operations.
 *
 * @returns true if user confirmed deletion, false otherwise
 */
export function confirmDelete(): boolean {
  return confirm('CONFIRM\n\nAre you sure you want to delete?');
}

/**
 * Enable/disable words hint.
 * Function called when clicking on "Show All".
 */
export function showAllwordsClick(): void {
  const showAllEl = document.getElementById('showallwords') as HTMLInputElement | null;
  const showLearningEl = document.getElementById('showlearningtranslations') as HTMLInputElement | null;
  const textEl = document.getElementById('thetextid');

  const showAll = showAllEl?.checked ? '1' : '0';
  const showLeaning = showLearningEl?.checked ? '1' : '0';
  const text = textEl?.textContent || '';
  // Navigate to set mode endpoint and reload
  window.location.href = 'set_text_mode.php?mode=' + showAll + '&showLearning=' + showLeaning +
    '&text=' + text;
}

/**
 * Slide up animation using CSS transitions.
 * Hides an element by animating its height to 0.
 *
 * @param element The element to animate
 * @param duration Animation duration in milliseconds (default 400)
 * @param callback Optional callback when animation completes
 */
function slideUp(element: HTMLElement, duration = 400, callback?: () => void): void {
  // Set initial height explicitly for transition
  element.style.height = element.offsetHeight + 'px';
  element.style.overflow = 'hidden';
  element.style.transition = `height ${duration}ms ease-out, padding ${duration}ms ease-out, margin ${duration}ms ease-out`;

  // Force reflow to ensure initial height is applied
  void element.offsetHeight;

  // Animate to 0
  element.style.height = '0';
  element.style.paddingTop = '0';
  element.style.paddingBottom = '0';
  element.style.marginTop = '0';
  element.style.marginBottom = '0';

  // Clean up after animation
  setTimeout(() => {
    element.style.display = 'none';
    // Reset inline styles
    element.style.height = '';
    element.style.overflow = '';
    element.style.transition = '';
    element.style.paddingTop = '';
    element.style.paddingBottom = '';
    element.style.marginTop = '';
    element.style.marginBottom = '';
    if (callback) callback();
  }, duration);
}

/**
 * Hide the 'nodata' message after 3 seconds.
 * Used to automatically dismiss status messages.
 */
export function noShowAfter3Secs(): void {
  const element = document.getElementById('hide3');
  if (element) {
    slideUp(element);
  }
}

/**
 * Auto-dismiss messages with the 'hide_message' class.
 * Messages fade out after 2.5 seconds with a 1 second animation.
 */
export function initHideMessages(): void {
  const elements = document.querySelectorAll<HTMLElement>('.hide_message');
  elements.forEach(element => {
    setTimeout(() => {
      slideUp(element, 1000);
    }, 2500);
  });
}

/**
 * Set the focus on an element with the "focus" class.
 */
export function setTheFocus(): void {
  const focusEl = document.querySelector<HTMLInputElement | HTMLTextAreaElement>('.setfocus');
  if (focusEl) {
    focusEl.focus();
    focusEl.select();
  }
}

/**
 * Serialize a form into an object with key-value pairs.
 * Replaces jQuery's serializeObject plugin.
 *
 * @param form The form element to serialize
 * @returns Object with form field names as keys and values
 */
export function serializeFormToObject(form: HTMLFormElement): Record<string, unknown> {
  const o: Record<string, unknown> = {};
  const formData = new FormData(form);

  formData.forEach((value, key) => {
    if (o[key] !== undefined) {
      if (!Array.isArray(o[key])) {
        o[key] = [o[key]];
      }
      (o[key] as unknown[]).push(value || '');
    } else {
      o[key] = value || '';
    }
  });

  return o;
}

/**
 * Wrap the radio buttons into stylised elements.
 */
export function wrapRadioButtons(): void {
  let tabIndex = 1;
  const tabElements = document.querySelectorAll<HTMLElement>(
    ':is(input, .wrap_checkbox span, .wrap_radio span, select, ' +
    '#mediaselect span.click, #forwbutt, #backbutt), a:not([name^=rec])'
  );
  tabElements.forEach((el) => {
    el.setAttribute('tabindex', String(tabIndex++));
  });

  document.querySelectorAll<HTMLElement>('.wrap_radio span').forEach((span) => {
    span.addEventListener('keydown', function (e) {
      if (e.keyCode === 32) {
        const radioInput = this.closest('label')?.parentElement?.querySelector('input[type=radio]') as HTMLInputElement | null;
        radioInput?.click();
        e.preventDefault();
      }
    });
  });
}

/**
 * Do a lot of different DOM manipulations
 */
export function prepareMainAreas(): void {
  // Initialize inline editing for editable areas
  initInlineEdit('.edit_area', {
    url: 'inline_edit.php',
    tooltip: 'Click to edit...',
    submitText: 'Save',
    cancelText: 'Cancel',
    rows: 3,
    cols: 35,
    indicator: spinnerHtml({ alt: 'Saving...' })
  });

  // Wrap selects
  document.querySelectorAll<HTMLSelectElement>('select').forEach((select) => {
    const label = document.createElement('label');
    label.className = 'wrap_select';
    select.parentNode?.insertBefore(label, select);
    label.appendChild(select);
  });

  // Disable autocomplete on forms
  document.querySelectorAll<HTMLFormElement>('form').forEach((form) => {
    form.setAttribute('autocomplete', 'off');
  });

  // Handle file inputs
  document.querySelectorAll<HTMLInputElement>('input[type="file"]').forEach((fileInput) => {
    if (fileInput.offsetParent === null) { // Not visible
      const button = document.createElement('button');
      button.className = 'button-file';
      button.textContent = 'Choose File';
      button.type = 'button';

      const fakeFile = document.createElement('span');
      fakeFile.className = 'fakefile';
      fakeFile.style.position = 'relative';

      fileInput.parentNode?.insertBefore(button, fileInput);
      fileInput.parentNode?.insertBefore(fakeFile, fileInput.nextSibling);

      const updateText = () => {
        let txt = fileInput.value.replace('C:\\fakepath\\', '');
        if (txt.length > 85) txt = txt.replace(/.*(.{80})$/, ' ... $1');
        fakeFile.textContent = txt;
      };

      fileInput.addEventListener('change', updateText);
      fileInput.addEventListener('mouseout', updateText);
    }
  });

  // Handle checkboxes
  let cbIndex = 1;
  document.querySelectorAll<HTMLInputElement>('input[type="checkbox"]').forEach((checkbox) => {
    if (!checkbox.id) {
      checkbox.id = 'cb_' + cbIndex++;
    }
    const label = document.createElement('label');
    label.className = 'wrap_checkbox';
    label.setAttribute('for', checkbox.id);
    label.innerHTML = '<span></span>';
    checkbox.parentNode?.insertBefore(label, checkbox.nextSibling);
  });

  // Handle TTS spans
  document.querySelectorAll<HTMLElement>('span[class*="tts_"]').forEach((span) => {
    span.addEventListener('click', function () {
      const classAttr = getAttr(this, 'class');
      const lg = classAttr.replace(/.*tts_([a-zA-Z-]+).*/, '$1');
      const txt = this.textContent || '';
      readRawTextAloud(txt, lg);
    });
  });

  // Blur buttons on mouseup
  document.addEventListener('mouseup', function () {
    document.querySelectorAll<HTMLElement>(
      'button, input[type=button], .wrap_radio span, .wrap_checkbox span'
    ).forEach((el) => {
      (el as HTMLElement).blur();
    });
  });

  // Handle checkbox wrapper keyboard interaction
  document.querySelectorAll<HTMLElement>('.wrap_checkbox span').forEach((span) => {
    span.addEventListener('keydown', function (e) {
      if (e.keyCode === 32) {
        const checkbox = this.closest('label')?.parentElement?.querySelector('input[type=checkbox]') as HTMLInputElement | null;
        checkbox?.click();
        e.preventDefault();
      }
    });
  });

  // Handle radio buttons
  let rbIndex = 1;
  document.querySelectorAll<HTMLInputElement>('input[type="radio"]').forEach((radio) => {
    if (!radio.id) {
      radio.id = 'rb_' + rbIndex++;
    }
    const label = document.createElement('label');
    label.className = 'wrap_radio';
    label.setAttribute('for', radio.id);
    label.innerHTML = '<span></span>';
    radio.parentNode?.insertBefore(label, radio.nextSibling);
  });

  // Handle file button clicks
  document.querySelectorAll<HTMLButtonElement>('.button-file').forEach((button) => {
    button.addEventListener('click', function () {
      const fileInput = this.nextElementSibling as HTMLInputElement | null;
      if (fileInput?.type === 'file') {
        fileInput.click();
      }
      return false;
    });
  });

  // Annotation event handlers
  document.querySelectorAll<HTMLInputElement>('input.impr-ann-text').forEach((input) => {
    input.addEventListener('change', changeImprAnnText);
  });

  document.querySelectorAll<HTMLInputElement>('input.impr-ann-radio').forEach((input) => {
    input.addEventListener('change', changeImprAnnRadio);
  });

  // Form validation
  document.querySelectorAll<HTMLFormElement>('form.validate').forEach((form) => {
    form.addEventListener('submit', check);
  });

  // Mark checkbox clicks
  document.querySelectorAll<HTMLInputElement>('input.markcheck').forEach((input) => {
    input.addEventListener('click', markClick);
  });

  // Confirm delete buttons
  document.querySelectorAll<HTMLElement>('.confirmdelete').forEach((el) => {
    el.addEventListener('click', confirmDelete);
  });

  // Textarea no-return handling
  document.querySelectorAll<HTMLTextAreaElement>('textarea.textarea-noreturn').forEach((textarea) => {
    textarea.addEventListener('keydown', function (e) {
      if (e.keyCode === 13) {
        if (check()) {
          const submitBtn = document.querySelector<HTMLInputElement>('input[type="submit"]:last-of-type');
          submitBtn?.click();
        }
        e.preventDefault();
      }
    });
  });

  // Initialize Tagify for term and text tags
  // Tags are fetched from API asynchronously
  if (document.getElementById('termtags')) {
    fetchTermTags().then(tags => {
      initTermTags(tags);
    });
  }

  if (document.getElementById('texttags')) {
    fetchTextTags().then(tags => {
      initTextTags(tags);
    });
  }

  markClick();
  setTheFocus();

  const simWords = document.getElementById('simwords');
  const langField = document.getElementById('langfield');
  const wordField = document.getElementById('wordfield');
  if (simWords && langField && wordField) {
    wordField.addEventListener('blur', do_ajax_show_similar_terms);
    do_ajax_show_similar_terms();
  }

  window.setTimeout(noShowAfter3Secs, 3000);
  // Auto-dismiss messages with hide_message class
  initHideMessages();
}

window.addEventListener('load', wrapRadioButtons);

document.addEventListener('DOMContentLoaded', prepareMainAreas);

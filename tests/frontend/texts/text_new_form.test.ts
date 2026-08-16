/**
 * Tests for the new-text form component in text_suggestions.ts.
 *
 * The form has two kinds of "in flight": fetching a page from a URL
 * (Gutenberg/GDL/feed) and saving through the API. They are tracked apart
 * because the first one owns a "fetching the page" banner.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

vi.mock('alpinejs', () => ({
  default: { data: vi.fn(), store: vi.fn(), $data: vi.fn() }
}));
vi.mock('@shared/icons/lucide_icons', () => ({ initIcons: vi.fn() }));
vi.mock('@shared/api/client', () => ({ getCsrfToken: vi.fn(() => 'token') }));
vi.mock('@modules/book/api/books_api', () => ({ importEpubForm: vi.fn() }));
vi.mock('../../../src/frontend/js/modules/text/pages/text_form_save', () => ({
  saveTextForm: vi.fn()
}));

import { textNewFormData } from '../../../src/frontend/js/modules/text/pages/text_suggestions';
import { saveTextForm } from '../../../src/frontend/js/modules/text/pages/text_form_save';
import { importEpubForm } from '../../../src/frontend/js/modules/book/api/books_api';

/**
 * Build a submit event whose target is a form, the way Alpine hands one over.
 */
function submitEvent(): { event: Event; form: HTMLFormElement } {
  const form = document.createElement('form');
  document.body.appendChild(form);

  const event = new Event('submit', { cancelable: true });
  Object.defineProperty(event, 'target', { value: form });

  return { event, form };
}

describe('textNewFormData', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    // @ts-expect-error jsdom navigation is stubbed, as elsewhere in the suite
    delete window.location;
    // @ts-expect-error see above
    window.location = { href: '', search: '' } as Location;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  it('marks a save as saving, not as an auto-import', async () => {
    vi.mocked(saveTextForm).mockResolvedValue({ textId: 7, bookId: null, error: '' });

    const component = textNewFormData();
    const { event } = submitEvent();

    component.handleSubmit(event);

    expect(component.saving).toBe(true);
    // The "fetching the page" banner hangs off autoImporting, and a save
    // fetches nothing — leaving it false is the point of the two flags.
    expect(component.autoImporting).toBe(false);
    expect(component.isBusy()).toBe(true);

    await vi.waitFor(() => expect(window.location.href).toBe('/text/7/read'));
  });

  it('keeps the button busy while a URL import is in flight', () => {
    const component = textNewFormData();
    component.autoImporting = true;

    expect(component.isBusy()).toBe(true);
  });

  it('surfaces a failed save and lets the user try again', async () => {
    vi.mocked(saveTextForm).mockResolvedValue({
      textId: null,
      bookId: null,
      error: 'Language not found'
    });

    const component = textNewFormData();
    const { event } = submitEvent();

    component.handleSubmit(event);

    await vi.waitFor(() => expect(component.hasSaveError()).toBe(true));
    expect(component.saveError).toBe('Language not found');
    expect(component.saving).toBe(false);
    expect(component.isBusy()).toBe(false);
    expect(window.location.href).toBe('');
  });

  it('ignores a second submit while the first is in flight', () => {
    vi.mocked(saveTextForm).mockResolvedValue({ textId: 7, bookId: null, error: '' });

    const component = textNewFormData();
    const { event } = submitEvent();

    component.handleSubmit(event);
    component.handleSubmit(event);

    expect(saveTextForm).toHaveBeenCalledTimes(1);
  });

  it('sends an EPUB to the books API and opens the book', async () => {
    vi.mocked(importEpubForm).mockResolvedValue({ bookId: 3, error: '' });

    const component = textNewFormData();
    component.fileType = 'epub';
    const { event } = submitEvent();

    component.handleSubmit(event);

    expect(saveTextForm).not.toHaveBeenCalled();
    await vi.waitFor(() => expect(window.location.href).toBe('/book/3'));
  });
});

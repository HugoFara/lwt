/**
 * Tests for book_detail.ts - Book detail Alpine component.
 */
import { describe, it, expect, beforeEach, vi } from 'vitest';

vi.mock('alpinejs', () => ({
  default: { data: vi.fn() }
}));

vi.mock('../../../src/frontend/js/shared/i18n/translator', () => ({
  t: (key: string) => key
}));

const getMock = vi.fn();
const removeMock = vi.fn();

vi.mock('../../../src/frontend/js/modules/book/api/books_api', () => ({
  BooksApi: {
    get: (...args: unknown[]) => getMock(...args),
    remove: (...args: unknown[]) => removeMock(...args)
  }
}));

import { bookDetailData } from '../../../src/frontend/js/modules/book/pages/book_detail';

const BOOK = {
  id: 4,
  title: 'Frankenstein',
  author: 'Mary Shelley',
  description: null,
  sourceType: 'epub',
  totalChapters: 3,
  currentChapter: 2,
  progress: 66.666
};

const CHAPTERS = [
  { id: 11, num: 1, title: 'Letter 1' },
  { id: 12, num: 2, title: 'Chapter 1' },
  { id: 13, num: 3, title: 'Chapter 2' }
];

describe('book_detail.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML =
      '<script type="application/json" id="book-detail-config">{"bookId":4}</script>';
  });

  it('reads the book ID from the config blob', () => {
    expect(bookDetailData().bookId).toBe(4);
  });

  it('takes both the book and its chapters from a single request', async () => {
    getMock.mockResolvedValue({ data: { success: true, data: { book: BOOK, chapters: CHAPTERS } } });

    const component = bookDetailData();
    await component.load();

    expect(getMock).toHaveBeenCalledTimes(1);
    expect(component.book?.title).toBe('Frankenstein');
    expect(component.chapters).toHaveLength(3);
  });

  it('reports a missing book instead of rendering an empty page', async () => {
    getMock.mockResolvedValue({ data: { success: false, error: 'Book not found' } });

    const component = bookDetailData();
    await component.load();

    expect(component.error).toBe('Book not found');
    expect(component.book).toBeNull();
  });

  it('resumes at the recorded chapter', async () => {
    getMock.mockResolvedValue({ data: { success: true, data: { book: BOOK, chapters: CHAPTERS } } });

    const component = bookDetailData();
    await component.load();

    // currentChapter is 2, which is text 12 — not the first chapter.
    expect(component.continueHref()).toBe('/text/12/read');
  });

  it('falls back to the first chapter when the recorded one is gone', async () => {
    getMock.mockResolvedValue({
      data: { success: true, data: { book: { ...BOOK, currentChapter: 99 }, chapters: CHAPTERS } }
    });

    const component = bookDetailData();
    await component.load();

    expect(component.continueHref()).toBe('/text/11/read');
  });

  it('marks only the current chapter', async () => {
    getMock.mockResolvedValue({ data: { success: true, data: { book: BOOK, chapters: CHAPTERS } } });

    const component = bookDetailData();
    await component.load();

    expect(component.isCurrentChapter(CHAPTERS[1])).toBe(true);
    expect(component.isCurrentChapter(CHAPTERS[0])).toBe(false);
  });

  it('formats progress and the chapter counter', async () => {
    getMock.mockResolvedValue({ data: { success: true, data: { book: BOOK, chapters: CHAPTERS } } });

    const component = bookDetailData();
    await component.load();

    expect(component.progressLabel()).toBe('66.7%');
    expect(component.sourceLabel()).toBe('EPUB');
  });
});

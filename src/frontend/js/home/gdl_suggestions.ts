/**
 * GDL Suggestions - Auto-suggested easy readers from the Global Digital Library.
 *
 * Alpine.js component for the home page. Shows openly-licensed early-grade
 * readers (incl. StoryWeaver) for the current language. Beginner-aware: the
 * row is ordered before the Gutenberg suggestions for low-vocabulary readers
 * and after them for advanced readers (via the flex `order` it exposes).
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.1.0
 */

import Alpine from 'alpinejs';
import { initIcons } from '@shared/icons/lucide_icons';

interface GdlSuggestedBook {
  id: number;
  title: string;
  publisher: string;
  description: string;
  level: string;
  difficultyTier?: 'easy' | 'medium' | 'hard';
  thumbnail: string;
  sourceUri: string;
  epubUrl: string;
}

interface GdlSuggestionsData {
  books: GdlSuggestedBook[];
  hasMore: boolean;
  page: number;
  loading: boolean;
  error: string;
  languageId: number;
  basePath: string;
  importing: number | null;
  beginner: boolean;

  init(): void;
  fetchReaderLevel(): Promise<void>;
  fetchSuggestions(page: number): Promise<void>;
  loadMore(): Promise<void>;
  importBook(book: GdlSuggestedBook): void;
  formatMeta(book: GdlSuggestedBook): string;
  hasLevel(book: GdlSuggestedBook): boolean;
  bookLevel(book: GdlSuggestedBook): string;
  tierClass(book: GdlSuggestedBook): string;
  importingClass(book: GdlSuggestedBook): string;
  isImporting(): boolean;
  showRow(): boolean;
}

export function gdlSuggestionsData(): GdlSuggestionsData {
  return {
    books: [],
    hasMore: false,
    page: 1,
    loading: false,
    error: '',
    languageId: 0,
    basePath: '',
    importing: null,
    beginner: false,

    init() {
      const configEl = document.getElementById('home-warnings-config');
      if (configEl) {
        const config = JSON.parse(configEl.textContent || '{}');
        this.languageId = config.currentLanguageId || 0;
        this.basePath = config.basePath || '';
      }

      if (this.languageId > 0) {
        this.fetchReaderLevel();
        this.fetchSuggestions(1);
      }

      document.addEventListener('lwt:languageChanged', ((event: CustomEvent) => {
        const langId = parseInt(event.detail.languageId, 10);
        if (langId > 0 && langId !== this.languageId) {
          this.languageId = langId;
          this.books = [];
          this.page = 1;
          this.error = '';
          this.fetchReaderLevel();
          this.fetchSuggestions(1);
        }
      }) as EventListener);
    },

    async fetchReaderLevel() {
      if (this.languageId <= 0) return;
      try {
        const params = new URLSearchParams({ language_id: String(this.languageId) });
        const response = await fetch(`/api/v1/texts/reader-level?${params}`);
        const data = await response.json();
        if (response.ok && !data.error) {
          this.beginner = !!data.beginner;
        }
      } catch {
        // Non-fatal: fall back to the default (non-beginner) ordering.
      }
    },

    async fetchSuggestions(page: number) {
      if (this.loading || this.languageId <= 0) return;

      this.loading = true;
      this.error = '';

      try {
        const params = new URLSearchParams({
          language_id: String(this.languageId),
          page: String(page),
        });

        const response = await fetch(`/api/v1/texts/gdl-search?${params}`);
        const data = await response.json();

        if (!response.ok || data.error) {
          this.error = data.error || 'Could not load suggestions.';
          return;
        }

        if (page === 1) {
          this.books = data.results || [];
        } else {
          this.books = this.books.concat(data.results || []);
        }
        this.hasMore = data.next || false;
        this.page = page;
        requestAnimationFrame(() => initIcons());
      } catch {
        this.error = 'Could not reach the server.';
      } finally {
        this.loading = false;
      }
    },

    async loadMore() {
      if (this.loading || !this.hasMore) return;
      await this.fetchSuggestions(this.page + 1);
    },

    importBook(book: GdlSuggestedBook) {
      this.importing = book.id;
      // GDL books are ePUB; the new-text page imports via extract-epub-url.
      const params = new URLSearchParams({
        import_epub_url: book.epubUrl,
        import_title: book.title,
      });
      window.location.href = `${this.basePath}/texts/new?${params}`;
    },

    formatMeta(book: GdlSuggestedBook): string {
      return book.publisher || '';
    },

    hasLevel(book: GdlSuggestedBook): boolean {
      return !!book.level;
    },

    bookLevel(book: GdlSuggestedBook): string {
      return book.level || '';
    },

    tierClass(book: GdlSuggestedBook): string {
      const tier = book.difficultyTier;
      if (tier === 'easy') return 'is-success is-light';
      if (tier === 'hard') return 'is-danger is-light';
      return 'is-warning is-light';
    },

    importingClass(book: GdlSuggestedBook): string {
      return this.importing === book.id ? 'is-loading' : '';
    },

    isImporting(): boolean {
      return this.importing !== null;
    },

    showRow(): boolean {
      return this.books.length > 0 || this.loading;
    },
  };
}

/**
 * Initialize the GDL suggestions Alpine.js component.
 */
export function initGdlSuggestions(): void {
  Alpine.data('gdlSuggestions', gdlSuggestionsData);
}

initGdlSuggestions();

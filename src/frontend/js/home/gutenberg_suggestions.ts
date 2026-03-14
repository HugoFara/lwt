/**
 * Gutenberg Suggestions - Auto-suggested texts from Project Gutenberg.
 *
 * Alpine.js component that fetches and displays popular books
 * for the user's current language, ranked by estimated difficulty.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { initIcons } from '@shared/icons/lucide_icons';

interface SuggestedBook {
  id: number;
  title: string;
  authors: string[];
  languages: string[];
  subjects: string[];
  downloadCount: number;
  textUrl: string;
  difficultyTier?: 'easy' | 'medium' | 'hard';
}

interface PreviewData {
  total_unique_words: number;
  known_words: number;
  unknown_words: number;
  coverage_percent: number;
  difficulty_label: string;
  sample_unknown_words: string[];
}

interface SuggestionsData {
  books: SuggestedBook[];
  hasMore: boolean;
  page: number;
  loading: boolean;
  error: string;
  languageId: number;
  basePath: string;
  importing: number | null;
  previewBookId: number | null;
  previewLoading: boolean;
  previewData: PreviewData | null;
  previewError: string;

  init(): void;
  fetchSuggestions(page: number): Promise<void>;
  loadMore(): Promise<void>;
  previewBook(book: SuggestedBook): Promise<void>;
  importBook(book: SuggestedBook): void;
  formatAuthors(authors: string[]): string;
  tierLabel(tier: string): string;
  tierClass(tier: string): string;
  coverageClass(label: string): string;
}

/**
 * Alpine.js data component for Gutenberg suggestions.
 */
export function gutenbergSuggestionsData(): SuggestionsData {
  return {
    books: [],
    hasMore: false,
    page: 1,
    loading: false,
    error: '',
    languageId: 0,
    basePath: '',
    importing: null,
    previewBookId: null,
    previewLoading: false,
    previewData: null,
    previewError: '',

    init() {
      const configEl = document.getElementById('home-warnings-config');
      if (configEl) {
        const config = JSON.parse(configEl.textContent || '{}');
        this.languageId = config.currentLanguageId || 0;
        this.basePath = config.basePath || '';
      }

      if (this.languageId > 0) {
        this.fetchSuggestions(1);
      }

      // Re-fetch when language changes
      document.addEventListener('lwt:languageChanged', ((event: CustomEvent) => {
        const langId = parseInt(event.detail.languageId, 10);
        if (langId > 0 && langId !== this.languageId) {
          this.languageId = langId;
          this.books = [];
          this.page = 1;
          this.fetchSuggestions(1);
        }
      }) as EventListener);
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

        const response = await fetch(`/api/v1/texts/gutenberg-suggestions?${params}`);
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

    async previewBook(book: SuggestedBook) {
      // Toggle off if already previewing this book
      if (this.previewBookId === book.id) {
        this.previewBookId = null;
        this.previewData = null;
        this.previewError = '';
        return;
      }

      this.previewBookId = book.id;
      this.previewLoading = true;
      this.previewData = null;
      this.previewError = '';

      try {
        const params = new URLSearchParams({
          url: book.textUrl,
          language_id: String(this.languageId),
        });

        const response = await fetch(`/api/v1/texts/library-preview?${params}`);
        const data = await response.json();

        if (this.previewBookId !== book.id) return;

        if (!response.ok || data.error) {
          this.previewError = data.error || 'Could not analyze this text.';
          return;
        }

        this.previewData = data as unknown as PreviewData;
      } catch {
        if (this.previewBookId === book.id) {
          this.previewError = 'Could not reach the server.';
        }
      } finally {
        this.previewLoading = false;
      }
    },

    importBook(book: SuggestedBook) {
      this.importing = book.id;
      const params = new URLSearchParams({
        import_url: book.textUrl,
        import_title: book.title,
      });
      window.location.href = `${this.basePath}/texts/new?${params}`;
    },

    formatAuthors(authors: string[]): string {
      if (authors.length === 0) return 'Unknown author';
      return authors.join(', ');
    },

    tierLabel(tier: string): string {
      return tier === 'easy' ? 'Easy' : tier === 'hard' ? 'Hard' : 'Medium';
    },

    tierClass(tier: string): string {
      if (tier === 'easy') return 'is-success is-light';
      if (tier === 'hard') return 'is-danger is-light';
      return 'is-warning is-light';
    },

    coverageClass(label: string): string {
      if (label === 'easy') return 'is-success';
      if (label === 'hard') return 'is-danger';
      return 'is-warning';
    },
  };
}

/**
 * Initialize the Gutenberg suggestions Alpine.js component.
 */
export function initGutenbergSuggestions(): void {
  Alpine.data('gutenbergSuggestions', gutenbergSuggestionsData);
}

initGutenbergSuggestions();

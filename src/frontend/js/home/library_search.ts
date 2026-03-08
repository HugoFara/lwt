/**
 * Library Search - Search Project Gutenberg for texts to import.
 *
 * Alpine.js component that provides search functionality for the
 * Project Gutenberg catalog, with results displayed on the home page.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

interface GutenbergBook {
  id: number;
  title: string;
  authors: string[];
  languages: string[];
  subjects: string[];
  downloadCount: number;
  textUrl: string;
}

interface SearchResponse {
  data: {
    results: GutenbergBook[];
    count: number;
    next: boolean;
  };
}

interface LibrarySearchData {
  open: boolean;
  query: string;
  results: GutenbergBook[];
  totalCount: number;
  hasMore: boolean;
  page: number;
  loading: boolean;
  error: string;
  searched: boolean;
  importing: number | null;

  search(): Promise<void>;
  loadMore(): Promise<void>;
  importBook(book: GutenbergBook): void;
  formatAuthors(authors: string[]): string;
  formatDownloads(count: number): string;
  close(): void;
}

/**
 * Alpine.js data component for library search.
 */
export function librarySearchData(): LibrarySearchData {
  return {
    open: false,
    query: '',
    results: [],
    totalCount: 0,
    hasMore: false,
    page: 1,
    loading: false,
    error: '',
    searched: false,
    importing: null,

    async search() {
      const q = this.query.trim();
      if (!q) return;

      this.loading = true;
      this.error = '';
      this.page = 1;
      this.results = [];
      this.searched = true;

      try {
        const configEl = document.getElementById('home-warnings-config');
        const config = configEl ? JSON.parse(configEl.textContent || '{}') : {};
        const langId = config.currentLanguageId || 0;

        const params = new URLSearchParams({
          q,
          language_id: String(langId),
          page: '1',
        });

        const response = await fetch(`/api/v1/texts/library-search?${params}`);
        const data: SearchResponse = await response.json();

        if (!response.ok) {
          this.error =
            (data as unknown as { error?: string }).error ||
            'Search failed. Please try again.';
          return;
        }

        this.results = data.data.results;
        this.totalCount = data.data.count;
        this.hasMore = data.data.next;
      } catch {
        this.error = 'Could not reach the server. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    async loadMore() {
      if (this.loading || !this.hasMore) return;

      this.loading = true;
      this.page += 1;

      try {
        const configEl = document.getElementById('home-warnings-config');
        const config = configEl ? JSON.parse(configEl.textContent || '{}') : {};
        const langId = config.currentLanguageId || 0;

        const params = new URLSearchParams({
          q: this.query.trim(),
          language_id: String(langId),
          page: String(this.page),
        });

        const response = await fetch(`/api/v1/texts/library-search?${params}`);
        const data: SearchResponse = await response.json();

        if (response.ok) {
          this.results = this.results.concat(data.data.results);
          this.hasMore = data.data.next;
        }
      } catch {
        // Silently fail on load-more
      } finally {
        this.loading = false;
      }
    },

    importBook(book: GutenbergBook) {
      this.importing = book.id;
      // Navigate to the text creation form with the Gutenberg URL pre-filled
      const configEl = document.getElementById('home-warnings-config');
      const config = configEl ? JSON.parse(configEl.textContent || '{}') : {};
      const basePath = config.basePath || '';

      const params = new URLSearchParams({
        import_url: book.textUrl,
        import_title: book.title,
      });

      window.location.href = `${basePath}/texts/new?${params}`;
    },

    formatAuthors(authors: string[]): string {
      if (authors.length === 0) return 'Unknown author';
      return authors.join(', ');
    },

    formatDownloads(count: number): string {
      if (count >= 1000000) return (count / 1000000).toFixed(1) + 'M';
      if (count >= 1000) return (count / 1000).toFixed(1) + 'K';
      return String(count);
    },

    close() {
      this.open = false;
    },
  };
}

/**
 * Initialize the library search Alpine.js component.
 */
export function initLibrarySearch(): void {
  Alpine.data('librarySearch', librarySearchData);
}

// Register immediately (before Alpine.start())
initLibrarySearch();

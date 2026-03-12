/**
 * Text Suggestions - Gutenberg & Feed browsing on the new text page.
 *
 * Alpine.js components that let users discover texts from Project Gutenberg
 * and their configured RSS feeds, directly on the /texts/new page.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { initIcons } from '@shared/icons/lucide_icons';

// ── Gutenberg browser ───────────────────────────────────────────────

interface GutenbergBook {
  id: number;
  title: string;
  authors: string[];
  languages: string[];
  subjects: string[];
  downloadCount: number;
  textUrl: string;
  difficultyTier?: 'easy' | 'medium' | 'hard';
}

interface GutenbergBrowserData {
  books: GutenbergBook[];
  hasMore: boolean;
  page: number;
  loading: boolean;
  error: string;
  importing: number | null;

  init(): void;
  fetchBooks(page: number): Promise<void>;
  loadMore(): Promise<void>;
  importBook(book: GutenbergBook): void;
  formatAuthors(authors: string[]): string;
  tierLabel(tier: string): string;
  tierClass(tier: string): string;
  bookTierLabel(book: GutenbergBook): string;
  bookTierClass(book: GutenbergBook): string;
  importingClass(book: GutenbergBook): string;
  isImporting(): boolean;
  loadingClass(): string;
  showPlaceholder(): boolean;
}

function getSelectedLanguageId(): number {
  const input = document.getElementById('TxLgID') as HTMLInputElement | null;
  if (!input) return 0;
  return parseInt(input.value, 10) || 0;
}

/**
 * Listen for language changes on the TxLgID hidden input.
 * Uses event delegation on document to avoid timing issues with Alpine init.
 */
function onLanguageChange(callback: (langId: number) => void): void {
  document.addEventListener('change', (e) => {
    const target = e.target as HTMLElement;
    if (target.id === 'TxLgID') {
      const langId = parseInt((target as HTMLInputElement).value, 10) || 0;
      callback(langId);
    }
  });
}

export function gutenbergBrowserData(): GutenbergBrowserData {
  return {
    books: [],
    hasMore: false,
    page: 1,
    loading: false,
    error: '',
    importing: null,

    init() {
      const langId = getSelectedLanguageId();
      if (langId > 0) {
        this.fetchBooks(1);
      }

      // Re-fetch when language selector changes (delegated on document)
      onLanguageChange((newLangId) => {
        this.books = [];
        this.page = 1;
        this.error = '';
        if (newLangId > 0) {
          this.fetchBooks(1);
        }
      });
    },

    async fetchBooks(page: number) {
      const langId = getSelectedLanguageId();
      if (this.loading || langId <= 0) return;

      this.loading = true;
      this.error = '';

      try {
        const params = new URLSearchParams({
          language_id: String(langId),
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
      await this.fetchBooks(this.page + 1);
    },

    importBook(book: GutenbergBook) {
      this.importing = book.id;

      // Fill the URL input and switch to URL import mode
      const urlInput = document.getElementById('webpageUrl') as HTMLInputElement | null;
      if (urlInput) {
        urlInput.value = book.textUrl;
      }

      // Pre-fill title
      const titleInput = document.querySelector<HTMLInputElement>('input[name="TxTitle"]');
      if (titleInput) {
        titleInput.value = book.title;
      }

      // Switch to URL import mode and trigger fetch
      const formEl = document.querySelector<HTMLFormElement>('form[x-data]');
      if (formEl) {
        formEl.dispatchEvent(new CustomEvent('auto-import-url', { bubbles: true }));
      }
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

    bookTierLabel(book: GutenbergBook): string {
      return this.tierLabel(book.difficultyTier || '');
    },

    bookTierClass(book: GutenbergBook): string {
      return this.tierClass(book.difficultyTier || '');
    },

    importingClass(book: GutenbergBook): string {
      return this.importing === book.id ? 'is-loading' : '';
    },

    isImporting(): boolean {
      return this.importing !== null;
    },

    loadingClass(): string {
      return this.loading ? 'is-loading' : '';
    },

    showPlaceholder(): boolean {
      return !this.loading && this.books.length === 0 && !this.error;
    },
  };
}

// ── Feed browser ────────────────────────────────────────────────────

interface FeedSummary {
  id: number;
  name: string;
  langId: number;
  langName: string;
  articleCount: number;
  lastUpdate: string;
}

interface FeedArticle {
  id: number;
  title: string;
  link: string;
  description: string;
  date: string;
  audio: string;
  hasText: boolean;
  status: string;
  textId: number | null;
}

interface FeedBrowserData {
  feeds: FeedSummary[];
  articles: FeedArticle[];
  selectedFeed: FeedSummary | null;
  loadingFeeds: boolean;
  loadingArticles: boolean;
  error: string;
  articlePage: number;
  articleTotalPages: number;

  init(): void;
  fetchFeeds(): Promise<void>;
  selectFeed(feed: FeedSummary): Promise<void>;
  backToFeeds(): void;
  loadArticlePage(page: number): Promise<void>;
  nextPage(): void;
  prevPage(): void;
  importArticle(article: FeedArticle): void;
  statusClass(status: string): string;
  statusLabel(status: string): string;
  feedInfo(feed: FeedSummary): string;
  selectedFeedName(): string;
  showPagination(): boolean;
  canGoPrev(): boolean;
  canGoNext(): boolean;
  isImported(article: FeedArticle): boolean;
  showEmptyFeeds(): boolean;
  showEmptyArticles(): boolean;
}

export function feedBrowserData(): FeedBrowserData {
  return {
    feeds: [],
    articles: [],
    selectedFeed: null,
    loadingFeeds: false,
    loadingArticles: false,
    error: '',
    articlePage: 1,
    articleTotalPages: 1,

    init() {
      this.fetchFeeds();

      // Refetch when language changes (delegated on document)
      onLanguageChange(() => {
        this.selectedFeed = null;
        this.articles = [];
        this.fetchFeeds();
      });
    },

    async fetchFeeds() {
      this.loadingFeeds = true;
      this.error = '';

      try {
        const langId = getSelectedLanguageId();
        const params = new URLSearchParams({ per_page: '100' });
        if (langId > 0) {
          params.set('lang', String(langId));
        }

        const response = await fetch(`/api/v1/feeds/list?${params}`);
        const data = await response.json();

        if (!response.ok || data.error) {
          this.error = data.error || 'Could not load feeds.';
          return;
        }

        this.feeds = data.feeds || [];
        requestAnimationFrame(() => initIcons());
      } catch {
        this.error = 'Could not reach the server.';
      } finally {
        this.loadingFeeds = false;
      }
    },

    async selectFeed(feed: FeedSummary) {
      this.selectedFeed = feed;
      this.articlePage = 1;
      await this.loadArticlePage(1);
    },

    backToFeeds() {
      this.selectedFeed = null;
      this.articles = [];
    },

    async loadArticlePage(page: number) {
      if (!this.selectedFeed) return;
      this.loadingArticles = true;
      this.error = '';

      try {
        const params = new URLSearchParams({
          feed_id: String(this.selectedFeed.id),
          page: String(page),
          per_page: '20',
        });

        const response = await fetch(`/api/v1/feeds/articles?${params}`);
        const data = await response.json();

        if (!response.ok || data.error) {
          this.error = data.error || 'Could not load articles.';
          return;
        }

        this.articles = data.articles || [];
        this.articlePage = data.pagination?.page || page;
        this.articleTotalPages = data.pagination?.total_pages || 1;
        requestAnimationFrame(() => initIcons());
      } catch {
        this.error = 'Could not reach the server.';
      } finally {
        this.loadingArticles = false;
      }
    },

    importArticle(article: FeedArticle) {
      if (article.link) {
        // Fill the URL input and switch to URL import mode
        const urlInput = document.getElementById('webpageUrl') as HTMLInputElement | null;
        if (urlInput) {
          urlInput.value = article.link;
        }

        // Pre-fill title
        const titleInput = document.querySelector<HTMLInputElement>('input[name="TxTitle"]');
        if (titleInput) {
          titleInput.value = article.title;
        }

        // Pre-fill audio if available
        if (article.audio) {
          const audioInput = document.getElementById('TxAudioURI') as HTMLInputElement | null;
          if (audioInput) {
            audioInput.value = article.audio;
          }
        }

        // Pre-fill source URI
        const sourceInput = document.getElementById('TxSourceURI') as HTMLInputElement | null;
        if (sourceInput) {
          sourceInput.value = article.link;
        }

        // Switch to URL import mode and trigger fetch
        const formEl = document.querySelector<HTMLFormElement>('form[x-data]');
        if (formEl) {
          formEl.dispatchEvent(new CustomEvent('auto-import-url', { bubbles: true }));
        }
      }
    },

    statusClass(status: string): string {
      if (status === 'imported') return 'is-success is-light';
      if (status === 'archived') return 'is-info is-light';
      if (status === 'error') return 'is-danger is-light';
      return 'is-light';
    },

    statusLabel(status: string): string {
      if (status === 'imported') return 'Imported';
      if (status === 'archived') return 'Archived';
      if (status === 'error') return 'Error';
      return 'New';
    },

    feedInfo(feed: FeedSummary): string {
      return feed.langName + ' \u00B7 ' + feed.articleCount + ' articles \u00B7 Updated ' + feed.lastUpdate;
    },

    selectedFeedName(): string {
      return this.selectedFeed ? this.selectedFeed.name : '';
    },

    showPagination(): boolean {
      return this.articleTotalPages > 1;
    },

    canGoPrev(): boolean {
      return this.articlePage <= 1;
    },

    canGoNext(): boolean {
      return this.articlePage >= this.articleTotalPages;
    },

    nextPage() {
      this.loadArticlePage(this.articlePage + 1);
    },

    prevPage() {
      this.loadArticlePage(this.articlePage - 1);
    },

    isImported(article: FeedArticle): boolean {
      return article.status === 'imported';
    },

    showEmptyFeeds(): boolean {
      return this.feeds.length === 0 && !this.loadingFeeds;
    },

    showEmptyArticles(): boolean {
      return this.articles.length === 0 && !this.loadingArticles;
    },
  };
}

// ── Text New Form (two-step wizard) ──────────────────────────────────

interface TextNewFormData {
  step: number;
  source: string;
  showAdvanced: boolean;

  selectSource(source: string): void;
  goBack(): void;
  goToReview(): void;
  sourceActive(source: string): string;
  showTextArea(): boolean;
  showFileInfo(): boolean;
  handleFileChange(event: Event): void;
}

export function textNewFormData(): TextNewFormData {
  return {
    step: 1,
    source: '',
    showAdvanced: false,

    selectSource(source: string) {
      this.source = source;
      if (source === 'paste') {
        this.step = 2;
      }
    },

    goBack() {
      this.step = 1;
    },

    goToReview() {
      this.step = 2;
    },

    sourceActive(source: string): string {
      return this.source === source ? 'is-primary is-light' : '';
    },

    showTextArea(): boolean {
      return this.source !== 'file';
    },

    showFileInfo(): boolean {
      return this.source === 'file';
    },

    handleFileChange(event: Event) {
      const input = event.target as HTMLInputElement;
      const wrapper = input.closest('.file') as HTMLElement | null;
      const fileNameEl = wrapper ? wrapper.querySelector('.file-name') : null;
      if (fileNameEl && input.files && input.files.length > 0) {
        fileNameEl.textContent = input.files[0].name;
      }
    },
  };
}

// ── Registration ────────────────────────────────────────────────────

Alpine.data('textNewForm', textNewFormData);
Alpine.data('gutenbergBrowser', gutenbergBrowserData);
Alpine.data('feedBrowser', feedBrowserData);

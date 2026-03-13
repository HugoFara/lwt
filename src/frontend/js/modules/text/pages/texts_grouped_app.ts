/**
 * Texts List App - Alpine.js component for text list (single language).
 *
 * This component manages:
 * - Flat text list for the currently selected language
 * - Pagination with "Show More"
 * - Bulk selection and actions
 * - Text statistics loading
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { initIcons } from '@shared/icons/lucide_icons';
import { apiGet } from '@shared/api/client';
import { TextsApi } from '@modules/text/api/texts_api';
import { confirmDelete } from '@shared/utils/ui_utilities';

/**
 * Text item from API.
 */
interface TextItem {
  id: number;
  title: string;
  has_audio: boolean;
  source_uri: string;
  has_source: boolean;
  annotated: boolean;
  taglist: string;
}

/**
 * Text statistics from API.
 */
interface TextStats {
  total: number;
  saved: number;
  unknown: number;
  unknownPercent: number;
  statusCounts: Record<string, number>;
}

/**
 * Pagination info from API.
 */
interface PaginationInfo {
  current_page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

/**
 * Response from texts/by-language API.
 */
interface TextsByLanguageResponse {
  texts: TextItem[];
  pagination: PaginationInfo;
}

/**
 * Page configuration from PHP.
 */
interface PageConfig {
  activeLanguageId: number;
  statuses?: Record<string, unknown>;
}

export interface TextsGroupedData {
  // State
  loading: boolean;
  loadingMore: boolean;
  texts: TextItem[];
  stats: Map<number, TextStats>;
  pagination: PaginationInfo;
  markedTexts: Set<number>;
  sort: number;
  activeLanguageId: number;

  // Lifecycle
  init(): Promise<void>;

  // Data loading
  loadTexts(page?: number): Promise<void>;
  loadStatisticsForTexts(textIds: number[]): Promise<void>;

  // Pagination
  get hasMore(): boolean;
  loadMore(): Promise<void>;

  // Summary
  get summaryText(): string;

  // Selection
  markAllTexts(checked: boolean): void;
  toggleTextMark(event: Event): void;
  isTextMarked(textId: number): boolean;

  // Actions
  handleMultiAction(event: Event): void;
  handleRestDelete(event: Event, url: string): void;
  handleRestDeleteFromEvent(event: Event): void;
  handlePostAction(event: Event, url: string): void;
  handlePostActionFromEvent(event: Event): void;

  // Sorting
  handleSortChange(event: Event): void;

  // Utility
  parseTags(tagList: string): string[];
  getStatsForText(textId: number): TextStats | undefined;
  getStatusSegments(textId: number): Array<{status: number, percent: string, label: string, count: number}>;

  // Safe accessors for stats (CSP-compatible)
  getStatTotal(textId: number): string;
  getStatSaved(textId: number): string;
  getStatUnknown(textId: number): string;
  getStatUnknownPercent(textId: number): string;
}

/**
 * Read page configuration from the embedded JSON script tag.
 */
function getPageConfig(): PageConfig {
  const configEl = document.getElementById('texts-grouped-config');
  if (configEl) {
    try {
      return JSON.parse(configEl.textContent || '{}');
    } catch {
      // Invalid JSON
    }
  }
  return { activeLanguageId: 0 };
}

export function textsGroupedData(): TextsGroupedData {
  const config = getPageConfig();

  return {
    loading: true,
    loadingMore: false,
    texts: [],
    stats: new Map(),
    pagination: { current_page: 0, per_page: 10, total: 0, total_pages: 0 },
    markedTexts: new Set(),
    sort: 1,
    activeLanguageId: config.activeLanguageId,

    async init() {
      if (this.activeLanguageId > 0) {
        await this.loadTexts();
      }
      this.loading = false;

      setTimeout(() => {
        initIcons();
      }, 0);
    },

    get summaryText(): string {
      return `${this.pagination.total} text${this.pagination.total === 1 ? '' : 's'}`;
    },

    async loadTexts(page: number = 1) {
      if (page > 1) {
        this.loadingMore = true;
      }

      const response = await apiGet<TextsByLanguageResponse>(
        `/texts/by-language/${this.activeLanguageId}`,
        { page, per_page: 10, sort: this.sort }
      );

      if (response.data) {
        if (page === 1) {
          this.texts = response.data.texts;
        } else {
          this.texts.push(...response.data.texts);
        }
        this.pagination = response.data.pagination;

        // Load statistics for the new texts
        const textIds = response.data.texts.map((t) => t.id);
        if (textIds.length > 0) {
          await this.loadStatisticsForTexts(textIds);
        }
      }

      this.loadingMore = false;

      setTimeout(() => {
        initIcons();
      }, 0);
    },

    async loadStatisticsForTexts(textIds: number[]) {
      if (textIds.length === 0) return;

      const response = await TextsApi.getStatistics(textIds);
      if (response.data) {
        const statsData = response.data as unknown as Record<string, TextStats>;
        for (const [textIdStr, stats] of Object.entries(statsData)) {
          const textId = parseInt(textIdStr, 10);
          this.stats.set(textId, stats);
        }
      }
    },

    get hasMore(): boolean {
      return this.pagination.current_page < this.pagination.total_pages;
    },

    async loadMore() {
      if (this.loadingMore) return;
      await this.loadTexts(this.pagination.current_page + 1);
    },

    // Selection
    markAllTexts(checked: boolean) {
      if (checked) {
        this.texts.forEach((t) => this.markedTexts.add(t.id));
      } else {
        this.markedTexts.clear();
      }
    },

    toggleTextMark(event: Event) {
      const el = event.target as HTMLInputElement;
      const textId = parseInt(el.dataset.textId || '0', 10);
      if (el.checked) {
        this.markedTexts.add(textId);
      } else {
        this.markedTexts.delete(textId);
      }
    },

    isTextMarked(textId: number): boolean {
      return this.markedTexts.has(textId);
    },

    // Actions
    handleMultiAction(event: Event) {
      const select = event.target as HTMLSelectElement;
      const action = select.value;
      if (!action) return;

      const markedIds = Array.from(this.markedTexts);
      if (markedIds.length === 0) {
        select.value = '';
        return;
      }

      // Create a temporary form with the marked IDs
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '/texts';

      markedIds.forEach((id) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'marked[]';
        input.value = String(id);
        form.appendChild(input);
      });

      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'markaction';
      actionInput.value = action;
      form.appendChild(actionInput);

      if (action === 'del') {
        if (!confirmDelete()) {
          select.value = '';
          return;
        }
      }

      document.body.appendChild(form);
      form.submit();
    },

    handleRestDelete(event: Event, url: string) {
      event.preventDefault();
      if (confirmDelete()) {
        fetch(url, {
          method: 'DELETE',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
        }).then(() => {
          window.location.reload();
        }).catch((error) => {
          console.error('Delete failed:', error);
          alert('Failed to delete. Please try again.');
        });
      }
    },

    handlePostAction(event: Event, url: string) {
      event.preventDefault();
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = url;
      form.style.display = 'none';
      document.body.appendChild(form);
      form.submit();
    },

    /** CSP-friendly: read data-url from element */
    handleRestDeleteFromEvent(event: Event) {
      const el = event.currentTarget as HTMLElement;
      this.handleRestDelete(event, el.dataset.url || '');
    },

    /** CSP-friendly: read data-url from element */
    handlePostActionFromEvent(event: Event) {
      const el = event.currentTarget as HTMLElement;
      this.handlePostAction(event, el.dataset.url || '');
    },

    // Sorting
    handleSortChange(event: Event) {
      const select = event.target as HTMLSelectElement;
      this.sort = parseInt(select.value, 10) || 1;
      this.texts = [];
      this.stats.clear();
      this.pagination.current_page = 0;
      this.loadTexts();
    },

    // Stats
    getStatsForText(textId: number): TextStats | undefined {
      return this.stats.get(textId);
    },

    // Utility - parse tags from comma-separated string
    parseTags(tagList: string): string[] {
      if (!tagList || tagList.trim() === '') {
        return [];
      }
      return tagList.split(',').map(t => t.trim()).filter(t => t !== '');
    },

    getStatusSegments(textId: number): Array<{status: number, percent: string, label: string, count: number}> {
      const stats = this.getStatsForText(textId);
      if (!stats || stats.total === 0) {
        return [];
      }

      const STATUS_LABELS: Record<number, string> = {
        0: 'Unknown', 1: 'Learning (1)', 2: 'Learning (2)',
        3: 'Learning (3)', 4: 'Learning (4)', 5: 'Learned (5)',
        98: 'Ignored', 99: 'Well Known'
      };
      const STATUS_ORDER = [0, 1, 2, 3, 4, 5, 99, 98];
      const { total, unknown, statusCounts } = stats;

      const segments: Array<{status: number, percent: string, label: string, count: number}> = [];

      for (const status of STATUS_ORDER) {
        const count = status === 0 ? unknown : (statusCounts[String(status)] || 0);
        if (count > 0) {
          const pct = (count / total) * 100;
          segments.push({
            status,
            percent: pct.toFixed(2) + '%',
            label: `${STATUS_LABELS[status]}: ${count} (${pct.toFixed(1)}%)`,
            count
          });
        }
      }

      return segments;
    },

    // Safe accessors for stats (CSP-compatible)
    getStatTotal(textId: number): string {
      const stats = this.getStatsForText(textId);
      return stats ? String(stats.total) : '-';
    },

    getStatSaved(textId: number): string {
      const stats = this.getStatsForText(textId);
      return stats ? String(stats.saved) : '-';
    },

    getStatUnknown(textId: number): string {
      const stats = this.getStatsForText(textId);
      return stats ? String(stats.unknown) : '-';
    },

    getStatUnknownPercent(textId: number): string {
      const stats = this.getStatsForText(textId);
      return stats ? stats.unknownPercent + '%' : '-';
    }
  };
}

/**
 * Simple dropdown toggle component (CSP-compatible replacement for x-data="{ open: false }").
 */
function dropdownToggle() {
  return {
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; }
  };
}

/**
 * Initialize the texts list app Alpine.js component.
 */
export function initTextsGroupedAlpine(): void {
  Alpine.data('textsGroupedApp', textsGroupedData);
  Alpine.data('dropdownToggle', dropdownToggle);
}

// Expose for global access
declare global {
  interface Window {
    textsGroupedData: typeof textsGroupedData;
  }
}

window.textsGroupedData = textsGroupedData;

// Register Alpine data component immediately (before Alpine.start() in main.ts)
initTextsGroupedAlpine();

/**
 * Starter Vocabulary Alpine.js Component (CSP-compliant)
 *
 * Handles the starter vocabulary import flow after language creation:
 * choose options -> import frequency words -> enrich with translations -> done
 *
 * Also supports one-click curated dictionary import as an alternative
 * enrichment method for larger word sets.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { apiPost } from '@shared/api/client';

interface StarterVocabConfig {
  importUrl: string;
  enrichUrl: string;
  csrfToken: string;
  langId: number;
  curatedDictionaries: CuratedDictGroup[];
}

interface ImportResult {
  imported: number;
  skipped: number;
  total: number;
}

interface EnrichStats {
  done: number;
  failed: number;
  total: number;
}

interface CuratedDictSource {
  name: string;
  url: string;
  format: string;
  entries: string;
  license: string;
  notes: string;
  directDownload?: boolean;
}

interface CuratedDictGroup {
  language: string;
  languageName: string;
  sources: CuratedDictSource[];
}

interface CuratedImportResponse {
  success: boolean;
  dictId?: number;
  imported?: number;
  error?: string;
}

function readConfig(): StarterVocabConfig {
  const el = document.getElementById('starter-vocab-config');
  if (el) {
    return JSON.parse(el.textContent || '{}');
  }
  return { importUrl: '', enrichUrl: '', csrfToken: '', langId: 0, curatedDictionaries: [] };
}

Alpine.data('starterVocab', () => {
  const config = readConfig();

  return {
    step: 'choose' as string,
    size: 1000,
    mode: 'translation' as string,
    result: { imported: 0, skipped: 0, total: 0 } as ImportResult,
    enrichStats: { done: 0, failed: 0, total: 0 } as EnrichStats,
    enrichWarning: '',
    enrichProgress: 0,
    errorMessage: '',
    _stopEnrichment: false,

    // Curated dictionary state
    dictSources: config.curatedDictionaries.flatMap(g => g.sources),
    hasDictionaries: config.curatedDictionaries.length > 0,
    dictImportingUrl: '',
    dictImportResult: null as CuratedImportResponse | null,

    sizeClass(value: number): string {
      return this.size === value ? 'button is-primary is-selected' : 'button';
    },

    setSize(value: number): void {
      this.size = value;
    },

    enrichingLabel(): string {
      return this.mode === 'translation'
        ? 'Fetching translations...'
        : 'Fetching definitions...';
    },

    enrichedModeLabel(): string {
      return this.mode === 'translation' ? 'translations' : 'definitions';
    },

    async startImport(): Promise<void> {
      this.step = 'importing';

      try {
        const formData = new FormData();
        formData.append('count', String(this.size));
        formData.append('_csrf_token', config.csrfToken);

        const response = await fetch(config.importUrl, {
          method: 'POST',
          body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
          this.errorMessage = data.error || 'Unknown error occurred.';
          this.step = 'error';
          return;
        }

        this.result = data;

        if (this.mode === 'none' || data.imported === 0) {
          this.step = 'done';
          return;
        }

        this.enrichStats = { done: 0, failed: 0, total: data.imported };
        this._stopEnrichment = false;
        this.step = 'enriching';
        this.enrichNext();
      } catch {
        this.errorMessage = 'Network error. Please check your connection.';
        this.step = 'error';
      }
    },

    async enrichNext(): Promise<void> {
      if (this._stopEnrichment) {
        this.step = 'done';
        return;
      }

      try {
        const formData = new FormData();
        formData.append('mode', this.mode);
        formData.append('_csrf_token', config.csrfToken);

        const response = await fetch(config.enrichUrl, {
          method: 'POST',
          body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
          this.enrichWarning = data.error || 'Enrichment encountered an error.';
          this.step = 'done';
          return;
        }

        this.enrichStats.done = data.total - data.remaining;
        this.enrichStats.total = data.total;
        this.enrichStats.failed += data.failed;
        this.enrichProgress = data.total > 0
          ? Math.round(((data.total - data.remaining) / data.total) * 100)
          : 100;

        if (data.warning) {
          this.enrichWarning = data.warning;
        }

        if (data.remaining > 0 && !this._stopEnrichment) {
          setTimeout(() => this.enrichNext(), 100);
        } else {
          this.step = 'done';
        }
      } catch {
        this.enrichWarning = 'Network error during enrichment.';
        this.step = 'done';
      }
    },

    stopEnrichment(): void {
      this._stopEnrichment = true;
      this.step = 'done';
    },

    retryImport(): void {
      this.step = 'choose';
    },

    // Curated dictionary methods

    isDictImporting(url: string): boolean {
      return this.dictImportingUrl === url;
    },

    dictButtonLabel(url: string): string {
      return this.dictImportingUrl === url ? 'Importing...' : 'Import';
    },

    async importDictionary(source: CuratedDictSource): Promise<void> {
      this.dictImportingUrl = source.url;
      this.dictImportResult = null;

      const response = await apiPost<CuratedImportResponse>(
        '/local-dictionaries/import-curated',
        {
          language_id: config.langId,
          url: source.url,
          format: source.format,
          name: source.name,
        }
      );

      this.dictImportingUrl = '';
      this.dictImportResult = response.data ?? {
        success: false,
        error: response.error || 'Unknown error'
      };
    },

    dictResultClass(): string {
      if (!this.dictImportResult) return '';
      return this.dictImportResult.success
        ? 'notification is-success is-light mb-4'
        : 'notification is-danger is-light mb-4';
    },

    dismissDictResult(): void {
      this.dictImportResult = null;
    },
  };
});

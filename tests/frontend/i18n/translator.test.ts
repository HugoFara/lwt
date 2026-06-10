/**
 * Tests for the frontend i18n translator's API + cache delivery path
 * (shared/i18n/translator.ts), the seam a shell-free client uses to fetch
 * strings instead of reading the server-injected page blob.
 *
 * Each test re-imports the module so the singleton message map starts empty.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

const mockFetch = vi.fn();
const originalFetch = global.fetch;

function okJson(body: unknown) {
  return { ok: true, text: () => Promise.resolve(JSON.stringify(body)) };
}

async function freshTranslator() {
  vi.resetModules();
  return import('../../../src/frontend/js/shared/i18n/translator');
}

describe('shared/i18n/translator.ts — API + cache delivery', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    global.fetch = mockFetch;
    mockFetch.mockResolvedValue(okJson({ locale: 'en', messages: {} }));
    localStorage.clear();
  });

  afterEach(() => {
    localStorage.clear();
    vi.restoreAllMocks();
    global.fetch = originalFetch;
  });

  describe('loadI18nFromApi', () => {
    it('fetches a locale bundle and makes its strings resolvable via t()', async () => {
      const { loadI18nFromApi, t } = await freshTranslator();
      mockFetch.mockResolvedValue(
        okJson({ locale: 'es', messages: { 'common.save': 'Guardar' } })
      );

      const ok = await loadI18nFromApi('es');

      expect(ok).toBe(true);
      expect(String(mockFetch.mock.calls[0][0])).toContain('/api/v1/i18n/es');
      expect(t('common.save')).toBe('Guardar');
    });

    it('caches the fetched bundle in localStorage under its locale', async () => {
      const { loadI18nFromApi } = await freshTranslator();
      mockFetch.mockResolvedValue(
        okJson({ locale: 'es', messages: { 'common.save': 'Guardar' } })
      );

      await loadI18nFromApi('es');

      const cached = localStorage.getItem('lwt.i18n.es');
      expect(cached).not.toBeNull();
      expect(JSON.parse(cached as string)).toEqual({ 'common.save': 'Guardar' });
    });

    it('passes a namespace filter through to the request', async () => {
      const { loadI18nFromApi } = await freshTranslator();
      await loadI18nFromApi('en', ['navbar', 'common']);
      expect(String(mockFetch.mock.calls[0][0])).toContain('namespaces=navbar%2Ccommon');
    });

    it('requests the server-default locale when none is given', async () => {
      const { loadI18nFromApi } = await freshTranslator();
      await loadI18nFromApi();
      const url = String(mockFetch.mock.calls[0][0]);
      expect(url).toContain('/api/v1/i18n');
      expect(url).not.toContain('/i18n/');
    });

    it('returns false and caches nothing when the request fails', async () => {
      const { loadI18nFromApi } = await freshTranslator();
      mockFetch.mockRejectedValue(new Error('offline'));

      const ok = await loadI18nFromApi('es');

      expect(ok).toBe(false);
      // A failed fetch must not persist a bogus/empty bundle.
      expect(localStorage.getItem('lwt.i18n.es')).toBeNull();
    });

    it('interpolates parameters into a fetched string', async () => {
      const { loadI18nFromApi, t } = await freshTranslator();
      mockFetch.mockResolvedValue(
        okJson({ locale: 'en', messages: { 'review.due': '{count} due' } })
      );
      await loadI18nFromApi('en');
      expect(t('review.due', { count: 5 })).toBe('5 due');
    });
  });

  describe('hydrateI18nFromCache', () => {
    it('synchronously applies a previously cached bundle', async () => {
      const { hydrateI18nFromCache, t } = await freshTranslator();
      localStorage.setItem('lwt.i18n.es', JSON.stringify({ 'common.save': 'Guardar' }));

      const applied = hydrateI18nFromCache('es');

      expect(applied).toBe(true);
      expect(t('common.save')).toBe('Guardar');
      // No network needed.
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('returns false when nothing is cached for the locale', async () => {
      const { hydrateI18nFromCache } = await freshTranslator();
      expect(hydrateI18nFromCache('de')).toBe(false);
    });

    it('lets a later API load override cached strings', async () => {
      const { hydrateI18nFromCache, loadI18nFromApi, t } = await freshTranslator();
      localStorage.setItem('lwt.i18n.es', JSON.stringify({ 'common.save': 'old' }));
      hydrateI18nFromCache('es');
      expect(t('common.save')).toBe('old');

      mockFetch.mockResolvedValue(
        okJson({ locale: 'es', messages: { 'common.save': 'Guardar' } })
      );
      await loadI18nFromApi('es');

      expect(t('common.save')).toBe('Guardar');
    });
  });

  describe('initI18n (server-injected blob, unchanged)', () => {
    it('still reads strings from the lwt-i18n script element', async () => {
      const { initI18n, t } = await freshTranslator();
      document.body.innerHTML =
        '<script type="application/json" id="lwt-i18n">'
        + '{"common.save":"Save"}</script>';

      initI18n();

      expect(t('common.save')).toBe('Save');
      document.body.innerHTML = '';
    });
  });
});

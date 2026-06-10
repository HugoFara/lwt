/**
 * Tests for the injectable API server root in shared/api/client.ts.
 *
 * Verifies that the client defaults to same-origin (relative `/api/v1`) and
 * can be redirected at a user-chosen absolute server — the seam the packaged
 * mobile client relies on (ROADMAP.md Phase 1).
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  apiGet,
  apiPost,
  apiPut,
  apiDelete,
  apiPostForm,
  setApiServer,
  getApiServer,
  setAuthToken,
  getAuthToken,
  getAuthTokenExpiry,
  refreshAuthToken,
  maybeRefreshAuthToken
} from '../../../src/frontend/js/shared/api/client';

describe('shared/api/client.ts — injectable API server', () => {
  const mockFetch = vi.fn();
  const originalFetch = global.fetch;

  beforeEach(() => {
    vi.clearAllMocks();
    global.fetch = mockFetch;
    mockFetch.mockResolvedValue({
      ok: true,
      text: () => Promise.resolve('{}')
    });
    // Start each test from a clean, same-origin, unauthenticated state.
    setApiServer(null);
    setAuthToken(null);
    localStorage.clear();
  });

  afterEach(() => {
    // Never leak an override into other test files/suites.
    setApiServer(null);
    setAuthToken(null);
    localStorage.clear();
    vi.restoreAllMocks();
    global.fetch = originalFetch;
  });

  function calledHeaders(): Record<string, string> {
    return (mockFetch.mock.calls[0][1].headers ?? {}) as Record<string, string>;
  }

  function okJson(body: string) {
    return { ok: true, text: () => Promise.resolve(body) };
  }

  function calledUrl(): string {
    return String(mockFetch.mock.calls[0][0]);
  }

  describe('default (no server configured)', () => {
    it('keeps same-origin relative URLs', async () => {
      await apiGet('/terms/1');
      const u = calledUrl();
      // jsdom origin is http://localhost; relative base resolves against it.
      expect(u).toContain('/api/v1/terms/1');
      expect(u).not.toContain('remote.example.org');
    });

    it('reports an empty server root', () => {
      expect(getApiServer()).toBe('');
    });
  });

  describe('with a remote server configured', () => {
    beforeEach(() => {
      setApiServer('https://remote.example.org');
    });

    it('reports the configured server root', () => {
      expect(getApiServer()).toBe('https://remote.example.org');
    });

    it('routes GET to the absolute server', async () => {
      await apiGet('/terms/1');
      expect(calledUrl()).toBe('https://remote.example.org/api/v1/terms/1');
    });

    it('routes POST to the absolute server', async () => {
      await apiPost('/terms', { text: 'hi' });
      expect(calledUrl()).toBe('https://remote.example.org/api/v1/terms');
    });

    it('routes PUT to the absolute server', async () => {
      await apiPut('/terms/1', { status: 3 });
      expect(calledUrl()).toBe('https://remote.example.org/api/v1/terms/1');
    });

    it('routes DELETE to the absolute server', async () => {
      await apiDelete('/terms/1');
      expect(calledUrl()).toBe('https://remote.example.org/api/v1/terms/1');
    });

    it('still appends query parameters', async () => {
      await apiGet('/search', { q: 'cat' });
      expect(calledUrl()).toBe(
        'https://remote.example.org/api/v1/search?q=cat'
      );
    });
  });

  describe('normalization & persistence', () => {
    it('strips a trailing slash to avoid a doubled separator', async () => {
      setApiServer('https://remote.example.org/');
      await apiGet('/terms/1');
      expect(calledUrl()).toBe('https://remote.example.org/api/v1/terms/1');
    });

    it('supports a server mounted under a sub-path', async () => {
      setApiServer('https://host.example/lwt');
      await apiGet('/texts');
      expect(calledUrl()).toBe('https://host.example/lwt/api/v1/texts');
    });

    it('persists the choice to localStorage', () => {
      setApiServer('https://remote.example.org');
      expect(localStorage.getItem('lwt.apiServer')).toBe(
        'https://remote.example.org'
      );
    });

    it('clears persistence and returns to same-origin when reset', async () => {
      setApiServer('https://remote.example.org');
      setApiServer(null);
      expect(localStorage.getItem('lwt.apiServer')).toBeNull();
      expect(getApiServer()).toBe('');
      await apiGet('/terms/1');
      expect(calledUrl()).not.toContain('remote.example.org');
    });

    it('reads a persisted value set before the client is configured', async () => {
      // Simulate a fresh launch: no in-memory override, value already in
      // storage from a previous session.
      setApiServer(null); // ensure override is cleared (consult storage)
      localStorage.setItem('lwt.apiServer', 'https://stored.example.org');
      await apiGet('/terms/1');
      expect(calledUrl()).toBe('https://stored.example.org/api/v1/terms/1');
    });
  });

  describe('bearer token (Authorization header)', () => {
    it('sends no Authorization header when unauthenticated', async () => {
      await apiGet('/terms/1');
      expect(calledHeaders().Authorization).toBeUndefined();
      expect(getAuthToken()).toBe('');
    });

    it('attaches Bearer token to GET requests', async () => {
      setAuthToken('tok-123');
      await apiGet('/terms/1');
      expect(calledHeaders().Authorization).toBe('Bearer tok-123');
    });

    it('attaches Bearer token to POST/PUT/DELETE requests', async () => {
      setAuthToken('tok-123');
      await apiPost('/terms', { text: 'hi' });
      expect(calledHeaders().Authorization).toBe('Bearer tok-123');

      vi.clearAllMocks();
      mockFetch.mockResolvedValue({ ok: true, text: () => Promise.resolve('{}') });
      await apiPut('/terms/1', { status: 3 });
      expect(calledHeaders().Authorization).toBe('Bearer tok-123');

      vi.clearAllMocks();
      mockFetch.mockResolvedValue({ ok: true, text: () => Promise.resolve('{}') });
      await apiDelete('/terms/1');
      expect(calledHeaders().Authorization).toBe('Bearer tok-123');
    });

    it('attaches Bearer token to form posts alongside CSRF', async () => {
      setAuthToken('tok-123');
      await apiPostForm('/terms/1/status', { status: '3' });
      expect(calledHeaders().Authorization).toBe('Bearer tok-123');
    });

    it('persists the token and clears it on reset', async () => {
      setAuthToken('tok-123');
      expect(localStorage.getItem('lwt.apiToken')).toBe('tok-123');
      expect(getAuthToken()).toBe('tok-123');

      setAuthToken(null);
      expect(localStorage.getItem('lwt.apiToken')).toBeNull();
      expect(getAuthToken()).toBe('');
      await apiGet('/terms/1');
      expect(calledHeaders().Authorization).toBeUndefined();
    });

    it('works together with a remote server', async () => {
      setApiServer('https://remote.example.org');
      setAuthToken('tok-123');
      await apiGet('/terms/1');
      expect(calledUrl()).toBe('https://remote.example.org/api/v1/terms/1');
      expect(calledHeaders().Authorization).toBe('Bearer tok-123');
    });
  });

  describe('token expiry storage', () => {
    it('persists and reads the expiry passed to setAuthToken', () => {
      setAuthToken('tok-123', '2999-01-01T00:00:00+00:00');
      const expiry = getAuthTokenExpiry();
      expect(expiry).not.toBeNull();
      expect(expiry?.getUTCFullYear()).toBe(2999);
    });

    it('returns null when no expiry is known', () => {
      setAuthToken('tok-123');
      expect(getAuthTokenExpiry()).toBeNull();
    });

    it('clears the expiry when the token is cleared', () => {
      setAuthToken('tok-123', '2999-01-01T00:00:00+00:00');
      setAuthToken(null);
      expect(getAuthTokenExpiry()).toBeNull();
      expect(localStorage.getItem('lwt.apiTokenExpires')).toBeNull();
    });
  });

  describe('refreshAuthToken', () => {
    it('does nothing without a token', async () => {
      const ok = await refreshAuthToken();
      expect(ok).toBe(false);
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('exchanges a valid token for a new one via /auth/refresh', async () => {
      setAuthToken('old-token', '2999-01-01T00:00:00+00:00');
      mockFetch.mockResolvedValue(
        okJson('{"success":true,"token":"new-token","expires_at":"2999-06-01T00:00:00+00:00"}')
      );

      const ok = await refreshAuthToken();

      expect(ok).toBe(true);
      expect(calledUrl()).toContain('/api/v1/auth/refresh');
      expect(calledHeaders().Authorization).toBe('Bearer old-token');
      expect(getAuthToken()).toBe('new-token');
      expect(getAuthTokenExpiry()?.getUTCMonth()).toBe(5); // June
    });

    it('keeps the old token when refresh fails', async () => {
      setAuthToken('old-token', '2999-01-01T00:00:00+00:00');
      mockFetch.mockResolvedValue({
        ok: false,
        status: 401,
        statusText: 'Unauthorized',
        text: () => Promise.resolve('{"error":"expired"}')
      });

      const ok = await refreshAuthToken();

      expect(ok).toBe(false);
      expect(getAuthToken()).toBe('old-token');
    });
  });

  describe('maybeRefreshAuthToken (proactive)', () => {
    function isoIn(ms: number): string {
      return new Date(Date.now() + ms).toISOString();
    }
    const DAY = 24 * 60 * 60 * 1000;

    it('refreshes a token nearing expiry (inside the 7-day window)', async () => {
      setAuthToken('old-token', isoIn(2 * DAY));
      mockFetch.mockResolvedValue(
        okJson('{"success":true,"token":"new-token","expires_at":"2999-06-01T00:00:00+00:00"}')
      );

      const refreshed = await maybeRefreshAuthToken();

      expect(refreshed).toBe(true);
      expect(getAuthToken()).toBe('new-token');
    });

    it('does not refresh a token that is still far from expiry', async () => {
      setAuthToken('old-token', isoIn(30 * DAY));
      const refreshed = await maybeRefreshAuthToken();
      expect(refreshed).toBe(false);
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('does not refresh an already-expired token (cannot be refreshed)', async () => {
      setAuthToken('old-token', isoIn(-DAY));
      const refreshed = await maybeRefreshAuthToken();
      expect(refreshed).toBe(false);
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('does nothing without a token or known expiry', async () => {
      expect(await maybeRefreshAuthToken()).toBe(false);
      setAuthToken('tok-no-expiry');
      expect(await maybeRefreshAuthToken()).toBe(false);
      expect(mockFetch).not.toHaveBeenCalled();
    });
  });

  describe('401 handling', () => {
    function unauthorized() {
      return {
        ok: false,
        status: 401,
        statusText: 'Unauthorized',
        text: () => Promise.resolve('{"message":"Authentication required"}')
      };
    }

    it('clears the token and fires lwt:auth-expired on a 401 with a token set', async () => {
      setAuthToken('doomed-token');
      const onExpired = vi.fn();
      document.addEventListener('lwt:auth-expired', onExpired);
      mockFetch.mockResolvedValue(unauthorized());

      await apiGet('/terms/1');

      expect(getAuthToken()).toBe('');
      expect(onExpired).toHaveBeenCalledOnce();
      document.removeEventListener('lwt:auth-expired', onExpired);
    });

    it('does not fire when there is no token (same-origin cookie session)', async () => {
      const onExpired = vi.fn();
      document.addEventListener('lwt:auth-expired', onExpired);
      mockFetch.mockResolvedValue(unauthorized());

      await apiGet('/terms/1');

      expect(onExpired).not.toHaveBeenCalled();
      document.removeEventListener('lwt:auth-expired', onExpired);
    });
  });
});

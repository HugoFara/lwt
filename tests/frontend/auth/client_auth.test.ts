/**
 * Tests for the packaged-client "choose server + log in" flow
 * (modules/auth/pages/client_auth.ts).
 *
 * The component is exercised directly (not through Alpine) with a mocked
 * fetch and the real API client, so the full component -> client -> request
 * path is covered.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { clientAuthData } from '../../../src/frontend/js/modules/auth/pages/client_auth';
import {
  setApiServer,
  setAuthToken,
  getApiServer,
  getAuthToken,
  getAuthTokenExpiry,
  setAuthOptional,
  isAuthOptional
} from '../../../src/frontend/js/shared/api/client';

describe('modules/auth/client_auth.ts', () => {
  const mockFetch = vi.fn();
  const originalFetch = global.fetch;

  beforeEach(() => {
    vi.clearAllMocks();
    global.fetch = mockFetch;
    setApiServer(null);
    setAuthToken(null);
    localStorage.clear();
    document.body.innerHTML = '';
  });

  afterEach(() => {
    setApiServer(null);
    setAuthToken(null);
    localStorage.clear();
    document.body.innerHTML = '';
    vi.restoreAllMocks();
    global.fetch = originalFetch;
  });

  function okJson(body: string) {
    return { ok: true, status: 200, text: () => Promise.resolve(body) };
  }

  function status(code: number) {
    return { ok: code >= 200 && code < 300, status: code, text: () => Promise.resolve('') };
  }

  /**
   * Route the mocked fetch by URL: a reachable LWT `/version`, plus a
   * configurable auth-probe (`/languages`) response so a test can model a
   * multi-user (401) or single-user (200) server.
   */
  function mockServer(opts: { languagesStatus: number }) {
    mockFetch.mockImplementation((u: string) => {
      const target = String(u);
      if (target.includes('/api/v1/version')) {
        return Promise.resolve(okJson('{"version":"3.0.2"}'));
      }
      if (target.includes('/api/v1/languages')) {
        return Promise.resolve(status(opts.languagesStatus));
      }
      return Promise.resolve(okJson('{}'));
    });
  }

  function calledUrl(): string {
    return String(mockFetch.mock.calls[0][0]);
  }

  // ---------------------------------------------------------------------------
  // normalizeServerUrl
  // ---------------------------------------------------------------------------

  describe('normalizeServerUrl', () => {
    const c = clientAuthData();

    it('defaults the scheme to https', () => {
      expect(c.normalizeServerUrl('my.server.org')).toBe('https://my.server.org');
    });

    it('keeps an explicit http/https scheme', () => {
      expect(c.normalizeServerUrl('http://localhost:8000')).toBe('http://localhost:8000');
      expect(c.normalizeServerUrl('https://a.example')).toBe('https://a.example');
    });

    it('strips a trailing slash and surrounding space', () => {
      expect(c.normalizeServerUrl('  https://a.example/  ')).toBe('https://a.example');
    });

    it('leaves an empty string empty', () => {
      expect(c.normalizeServerUrl('   ')).toBe('');
    });
  });

  // ---------------------------------------------------------------------------
  // connect (server step)
  // ---------------------------------------------------------------------------

  describe('connect', () => {
    it('errors and does not fetch when the address is blank', async () => {
      const c = clientAuthData();
      c.serverUrl = '   ';
      await c.connect();
      expect(c.error).not.toBe('');
      expect(mockFetch).not.toHaveBeenCalled();
      expect(c.step).toBe('server');
    });

    it('probes /version on the chosen server and advances to login on a multi-user server', async () => {
      mockServer({ languagesStatus: 401 }); // auth required
      const c = clientAuthData();
      c.onAuthenticated = vi.fn();
      c.serverUrl = 'demo.example.org';
      await c.connect();

      expect(calledUrl()).toBe('https://demo.example.org/api/v1/version');
      expect(getApiServer()).toBe('https://demo.example.org');
      expect(c.step).toBe('login');
      expect(isAuthOptional()).toBe(false);
      expect(c.onAuthenticated).not.toHaveBeenCalled();
      expect(c.error).toBe('');
    });

    it('skips login and enters the app when the server needs no auth (single-user)', async () => {
      mockServer({ languagesStatus: 200 }); // every endpoint public
      const c = clientAuthData();
      c.onAuthenticated = vi.fn();
      c.serverUrl = 'demo.example.org';
      await c.connect();

      expect(getApiServer()).toBe('https://demo.example.org');
      expect(c.step).toBe('server'); // never advanced to the login step
      expect(isAuthOptional()).toBe(true);
      expect(c.onAuthenticated).toHaveBeenCalledOnce();
      expect(c.error).toBe('');
    });

    it('rolls back the server and reports an error on an unreachable host', async () => {
      mockFetch.mockRejectedValue(new Error('Failed to fetch'));
      const c = clientAuthData();
      c.serverUrl = 'https://nope.example';
      await c.connect();

      expect(getApiServer()).toBe(''); // rolled back
      expect(c.step).toBe('server');
      expect(c.error).not.toBe('');
    });

    it('rejects a 200 response that is not an LWT version payload', async () => {
      mockFetch.mockResolvedValue(okJson('{"something":"else"}'));
      const c = clientAuthData();
      c.serverUrl = 'https://notlwt.example';
      await c.connect();

      expect(getApiServer()).toBe('');
      expect(c.step).toBe('server');
      expect(c.error).not.toBe('');
    });
  });

  // ---------------------------------------------------------------------------
  // submitLogin (login step)
  // ---------------------------------------------------------------------------

  describe('submitLogin', () => {
    const event = { preventDefault: () => {} } as Event;

    it('errors and does not fetch when fields are empty', async () => {
      const c = clientAuthData();
      await c.submitLogin(event);
      expect(c.error).not.toBe('');
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('stores the token and signals success on valid credentials', async () => {
      mockFetch.mockResolvedValue(
        okJson('{"success":true,"token":"tok-xyz","expires_at":null}')
      );
      const c = clientAuthData();
      c.onAuthenticated = vi.fn();
      c.username = 'alice';
      c.password = 'secret';

      await c.submitLogin(event);

      expect(calledUrl()).toContain('/api/v1/auth/login');
      expect(getAuthToken()).toBe('tok-xyz');
      expect(c.onAuthenticated).toHaveBeenCalledOnce();
      expect(c.password).toBe(''); // cleared after use
    });

    it('surfaces a bad-credentials error without storing a token', async () => {
      mockFetch.mockResolvedValue(
        okJson('{"success":false,"error":"Invalid username or password"}')
      );
      const c = clientAuthData();
      c.onAuthenticated = vi.fn();
      c.username = 'alice';
      c.password = 'wrong';

      await c.submitLogin(event);

      expect(getAuthToken()).toBe('');
      expect(c.error).toBe('Invalid username or password');
      expect(c.onAuthenticated).not.toHaveBeenCalled();
    });

    it('surfaces a transport error', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));
      const c = clientAuthData();
      c.onAuthenticated = vi.fn();
      c.username = 'alice';
      c.password = 'secret';

      await c.submitLogin(event);

      expect(getAuthToken()).toBe('');
      expect(c.error).not.toBe('');
      expect(c.onAuthenticated).not.toHaveBeenCalled();
    });
  });

  // ---------------------------------------------------------------------------
  // init (relaunch / step routing)
  // ---------------------------------------------------------------------------

  describe('init', () => {
    it('skips straight into the app when a token is already stored', () => {
      setAuthToken('persisted-token');
      const c = clientAuthData();
      c.onAuthenticated = vi.fn();
      c.init();
      expect(c.onAuthenticated).toHaveBeenCalledOnce();
    });

    it('jumps to the login step when a server is already configured', () => {
      setApiServer('https://known.example');
      const c = clientAuthData();
      c.onAuthenticated = vi.fn();
      c.init();
      expect(c.step).toBe('login');
      expect(c.serverUrl).toBe('https://known.example');
      expect(c.onAuthenticated).not.toHaveBeenCalled();
    });

    it('skips login on relaunch when the server is known to need no auth', () => {
      setApiServer('https://single.example'); // clears any prior flag…
      setAuthOptional(true); // …then mark this server auth-optional
      const c = clientAuthData();
      c.onAuthenticated = vi.fn();
      c.init();
      expect(c.onAuthenticated).toHaveBeenCalledOnce();
      expect(c.step).toBe('server'); // never routed to login
    });

    it('starts on the server step, prefilled from config, when nothing is set', () => {
      document.body.innerHTML =
        '<script type="application/json" id="client-auth-config">'
        + '{"defaultServer":"https://default.example"}</script>';
      const c = clientAuthData();
      c.init();
      expect(c.step).toBe('server');
      expect(c.serverUrl).toBe('https://default.example');
    });
  });

  // ---------------------------------------------------------------------------
  // back
  // ---------------------------------------------------------------------------

  it('back() clears the chosen server and returns to the server step', () => {
    setApiServer('https://known.example');
    const c = clientAuthData();
    c.step = 'login';
    c.back();
    expect(c.step).toBe('server');
    expect(getApiServer()).toBe('');
  });

  it('login stores the token expiry from the response', async () => {
    mockFetch.mockResolvedValue(
      okJson('{"success":true,"token":"tok","expires_at":"2999-01-01T00:00:00+00:00"}')
    );
    const c = clientAuthData();
    c.onAuthenticated = vi.fn();
    c.username = 'alice';
    c.password = 'secret';
    await c.submitLogin({ preventDefault: () => {} } as Event);

    expect(getAuthToken()).toBe('tok');
    expect(getAuthTokenExpiry()?.getUTCFullYear()).toBe(2999);
  });

  // ---------------------------------------------------------------------------
  // register
  // ---------------------------------------------------------------------------

  describe('register', () => {
    const event = { preventDefault: () => {} } as Event;

    it('showRegister/showLogin toggle the mode and clear errors', () => {
      const c = clientAuthData();
      c.error = 'stale';
      c.showRegister();
      expect(c.authMode).toBe('register');
      expect(c.onRegisterMode).toBe(true);
      expect(c.error).toBe('');

      c.error = 'stale';
      c.showLogin();
      expect(c.authMode).toBe('login');
      expect(c.onLoginMode).toBe(true);
      expect(c.error).toBe('');
    });

    it('errors and does not fetch when fields are missing', async () => {
      const c = clientAuthData();
      c.username = 'alice';
      c.email = '';
      c.password = 'secret123';
      await c.submitRegister(event);
      expect(c.error).not.toBe('');
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('errors when passwords do not match', async () => {
      const c = clientAuthData();
      c.username = 'alice';
      c.email = 'a@b.co';
      c.password = 'secret123';
      c.passwordConfirm = 'different';
      await c.submitRegister(event);
      expect(c.error).toMatch(/match/i);
      expect(mockFetch).not.toHaveBeenCalled();
    });

    it('posts to /auth/register and stores the token on success', async () => {
      mockFetch.mockResolvedValue(
        okJson('{"success":true,"token":"new-acct","expires_at":null}')
      );
      const c = clientAuthData();
      c.onAuthenticated = vi.fn();
      c.username = 'alice';
      c.email = 'a@b.co';
      c.password = 'secret123';
      c.passwordConfirm = 'secret123';

      await c.submitRegister(event);

      // Registration first fetches a captcha challenge, then POSTs to register.
      const urls = mockFetch.mock.calls.map((call) => String(call[0]));
      expect(urls.some((u) => u.includes('/api/v1/auth/altcha-challenge'))).toBe(true);
      expect(urls.some((u) => u.includes('/api/v1/auth/register'))).toBe(true);
      expect(getAuthToken()).toBe('new-acct');
      expect(c.onAuthenticated).toHaveBeenCalledOnce();
    });

    it('shows the recovery code once for an email-less account', async () => {
      mockFetch.mockResolvedValue(
        okJson('{"success":true,"token":"new-acct","expires_at":null,'
          + '"recovery_code":"AAAAA-BBBBB-CCCCC-DDDDD"}')
      );
      const c = clientAuthData();
      c.onAuthenticated = vi.fn();
      c.username = 'alice';
      c.email = '';
      c.password = 'secret123';
      c.passwordConfirm = 'secret123';

      await c.submitRegister(event);

      // Token is stored, but the app shows the code first instead of entering.
      expect(c.onRecoveryStep).toBe(true);
      expect(c.recoveryCode).toBe('AAAAA-BBBBB-CCCCC-DDDDD');
      expect(getAuthToken()).toBe('new-acct');
      expect(c.onAuthenticated).not.toHaveBeenCalled();

      c.continueAfterRecovery();
      expect(c.onAuthenticated).toHaveBeenCalledOnce();
    });

    it('surfaces a server-side validation error', async () => {
      mockFetch.mockResolvedValue(
        okJson('{"success":false,"error":"Username already taken"}')
      );
      const c = clientAuthData();
      c.onAuthenticated = vi.fn();
      c.username = 'taken';
      c.email = 'a@b.co';
      c.password = 'secret123';
      c.passwordConfirm = 'secret123';

      await c.submitRegister(event);

      expect(getAuthToken()).toBe('');
      expect(c.error).toBe('Username already taken');
      expect(c.onAuthenticated).not.toHaveBeenCalled();
    });
  });
});

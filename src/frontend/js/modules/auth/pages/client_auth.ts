/**
 * Client Auth Alpine.js component — "choose server + log in" flow.
 *
 * The entry flow for a packaged client (the planned Capacitor/F-Droid app),
 * which serves its UI locally and talks to a user-chosen LWT server over the
 * REST API. It is a two-step flow in a single component:
 *
 *   1. `server` — enter a server address, validated by probing the public
 *      `/api/v1/version` endpoint; on success the choice is stored via
 *      `setApiServer` (persisted to localStorage).
 *   2. `login`  — username + password posted to `/api/v1/auth/login`; the
 *      returned bearer token is stored via `setAuthToken`, after which every
 *      API call carries `Authorization: Bearer …`.
 *
 * Unlike the server-rendered cookie login (`login.php`), this works
 * cross-origin: cookies are not sent to a remote server, so token auth is the
 * mechanism. The server must allow the client origin via `CORS_ALLOWED_ORIGINS`.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.1.1
 */

import Alpine from 'alpinejs';
import {
  apiGet,
  apiPost,
  setApiServer,
  getApiServer,
  setAuthToken,
  getAuthToken
} from '@shared/api/client';
import { url } from '@shared/utils/url';

interface VersionResponse {
  version?: string;
}

interface LoginResponse {
  success?: boolean;
  token?: string;
  error?: string;
}

interface ClientAuthData {
  step: 'server' | 'login';
  serverUrl: string;
  username: string;
  password: string;
  loading: boolean;
  error: string;
  homeUrl: string;
  readonly onServerStep: boolean;
  readonly onLoginStep: boolean;
  init(): void;
  normalizeServerUrl(input: string): string;
  connect(): Promise<void>;
  back(): void;
  submitLogin(event: Event): Promise<void>;
  onAuthenticated(): void;
}

/**
 * Read optional config injected by the host page:
 * `<script type="application/json" id="client-auth-config">`.
 */
function readConfig(): { defaultServer: string; homeUrl: string } {
  const el = document.getElementById('client-auth-config');
  const fallback = { defaultServer: '', homeUrl: url('/') };
  if (!el || !el.textContent) {
    return fallback;
  }
  try {
    const parsed = JSON.parse(el.textContent) as {
      defaultServer?: string;
      homeUrl?: string;
    };
    return {
      defaultServer: parsed.defaultServer ?? '',
      homeUrl: parsed.homeUrl ?? fallback.homeUrl
    };
  } catch {
    return fallback;
  }
}

/**
 * Alpine.js data component for the packaged-client auth flow.
 */
export function clientAuthData(): ClientAuthData {
  return {
    step: 'server',
    serverUrl: '',
    username: '',
    password: '',
    loading: false,
    error: '',
    homeUrl: '/',

    // CSP-safe step flags for x-show (the @alpinejs/csp evaluator handles
    // property access, not `step === '…'` string comparisons in templates).
    get onServerStep(): boolean {
      return this.step === 'server';
    },

    get onLoginStep(): boolean {
      return this.step === 'login';
    },

    init(): void {
      const config = readConfig();
      this.homeUrl = config.homeUrl;

      // Already signed in (token persisted from a previous launch): skip the
      // whole flow.
      if (getAuthToken()) {
        this.onAuthenticated();
        return;
      }

      // Server already chosen previously: jump straight to the login step.
      const knownServer = getApiServer();
      if (knownServer) {
        this.serverUrl = knownServer;
        this.step = 'login';
        return;
      }

      this.serverUrl = config.defaultServer;
    },

    /**
     * Trim, drop a trailing slash, and default the scheme to https so a user
     * can type just a hostname.
     */
    normalizeServerUrl(input: string): string {
      let value = input.trim().replace(/\/+$/, '');
      if (value !== '' && !/^https?:\/\//i.test(value)) {
        value = 'https://' + value;
      }
      return value;
    },

    async connect(): Promise<void> {
      const server = this.normalizeServerUrl(this.serverUrl);
      if (server === '') {
        this.error = 'Please enter a server address.';
        return;
      }

      this.loading = true;
      this.error = '';
      this.serverUrl = server;

      // Point the client at the candidate server and probe the public version
      // endpoint to confirm it is a reachable LWT server (and that CORS allows
      // this origin).
      setApiServer(server);
      const res = await apiGet<VersionResponse>('/version');
      this.loading = false;

      if (res.error || !res.data || !res.data.version) {
        setApiServer(null); // roll back the bad choice
        this.error =
          'Could not reach an LWT server at that address. Check the URL and '
          + 'that the server allows this app.';
        return;
      }

      this.step = 'login';
    },

    back(): void {
      setApiServer(null);
      this.step = 'server';
      this.error = '';
    },

    async submitLogin(event: Event): Promise<void> {
      event.preventDefault();

      const username = this.username.trim();
      if (username === '' || this.password === '') {
        this.error = 'Enter your username and password.';
        return;
      }

      this.loading = true;
      this.error = '';

      const res = await apiPost<LoginResponse>('/auth/login', {
        username,
        password: this.password
      });
      this.loading = false;

      // Transport-level failure (network/CORS/HTTP error).
      if (res.error) {
        this.error = res.error;
        return;
      }

      // The handler returns HTTP 200 with `success: false` for bad credentials.
      const data = res.data;
      if (!data || data.success !== true || !data.token) {
        this.error = data && data.error ? data.error : 'Login failed.';
        return;
      }

      setAuthToken(data.token);
      this.password = '';
      this.onAuthenticated();
    },

    /**
     * Navigate into the app after a successful login. Overridable (e.g. in
     * tests, or by a host that wants in-app routing instead of a reload).
     */
    onAuthenticated(): void {
      window.location.assign(this.homeUrl);
    }
  };
}

/**
 * Register the component. Must run before Alpine.start().
 */
export function initClientAuthAlpine(): void {
  Alpine.data('clientAuth', clientAuthData);
}

declare global {
  interface Window {
    clientAuthData: typeof clientAuthData;
    initClientAuthAlpine: typeof initClientAuthAlpine;
  }
}

window.clientAuthData = clientAuthData;
window.initClientAuthAlpine = initClientAuthAlpine;

initClientAuthAlpine();

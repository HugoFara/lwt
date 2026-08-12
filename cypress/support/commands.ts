/// <reference types="cypress" />

declare global {
  namespace Cypress {
    interface Chainable {
      /**
       * Install the demo database via the admin interface
       */
      installDemo(): Chainable<void>;

      /**
       * Select a language from the filter dropdown
       */
      selectLanguage(langName: string): Chainable<void>;

      /**
       * Check that a form field with validation class exists and is required
       */
      checkRequiredField(selector: string): Chainable<JQuery<HTMLElement>>;

      /**
       * Issue an API request the way the real client does.
       *
       * State-changing verbs (POST/PUT/DELETE/PATCH) are rejected with 403 by
       * CsrfMiddleware unless they carry either a Bearer token or the
       * `X-CSRF-TOKEN` header. `@shared/api/client` reads that token from
       * `<meta name="csrf-token">`; this command does the same, so a bare
       * `cy.request()` in a spec is almost always a bug.
       */
      apiRequest(
        options: Partial<Cypress.RequestOptions> & { url: string }
      ): Chainable<Cypress.Response<unknown>>;

      /**
       * Read the current session's CSRF token from a rendered page.
       */
      csrfToken(): Chainable<string>;

       /**
        * Wait until Alpine has hydrated the page.
        *
        * Markup carrying x-data is server-rendered, so it exists long before
        * the bundle mounts it. Asserting on x-data alone therefore passes even
        * when Alpine never ran, and interacting before hydration silently
        * misses the component's event listeners.
        */
      waitForAlpine(): Chainable<void>;
    }
  }
}

/** Verbs CsrfMiddleware guards; mirrors its PROTECTED_METHODS. */
const CSRF_PROTECTED = ['POST', 'PUT', 'DELETE', 'PATCH'];

// Database reset via demo install
Cypress.Commands.add('installDemo', () => {
  cy.visit('/admin/install-demo');
  cy.get('form').should('exist');
  cy.get('input[type="submit"], button[type="submit"]').click();
  cy.url().should('include', '/admin/install-demo');
});

// Select language from dropdown
Cypress.Commands.add('selectLanguage', (langName: string) => {
  cy.get('select[name="filterlang"]').select(langName);
});

// Check required field exists
Cypress.Commands.add('checkRequiredField', (selector: string) => {
  return cy.get(selector).should('exist').and('be.visible');
});

// Read the CSRF token for the current session from a rendered page.
// `cy.request` shares the browser's cookie jar, so the token minted here
// belongs to the same session the subsequent request will authenticate under.
Cypress.Commands.add('csrfToken', () => {
  return cy.request('/').then((response) => {
    const match = /<meta name="csrf-token" content="([^"]*)"/.exec(
      String(response.body)
    );
    return match ? match[1] : '';
  });
});

// API request carrying the CSRF header for state-changing verbs.
Cypress.Commands.add('apiRequest', (options) => {
  const method = String(options.method ?? 'GET').toUpperCase();

  if (!CSRF_PROTECTED.includes(method)) {
    return cy.request(options);
  }

  return cy.csrfToken().then((token) => {
    return cy.request({
      ...options,
      headers: { ...(options.headers ?? {}), 'X-CSRF-TOKEN': token },
    });
  });
});

// Hydration gate — see the declaration above for why this is needed.
Cypress.Commands.add('waitForAlpine', () => {
  cy.window({ timeout: 15000 }).should((win) => {
    const w = win as unknown as { Alpine?: unknown; LWT_VITE_LOADED?: boolean };
    expect(w.LWT_VITE_LOADED, 'bundle loaded').to.eq(true);
    expect(Boolean(w.Alpine), 'Alpine started').to.eq(true);
  });

  // Alpine walks the DOM asynchronously after start(); wait for it to have
  // claimed at least one component before letting a spec interact.
  cy.get('[x-data]', { timeout: 15000 }).should(($els) => {
    const claimed = Array.from($els).some((el) =>
      Object.keys(el as unknown as Record<string, unknown>).some((k) => k.startsWith('_x'))
    );
    expect(claimed, 'Alpine mounted a component').to.eq(true);
  });
});

export {};

/// <reference types="cypress" />

/**
 * Regression: the navbar theme toggle must fire its save call when clicked
 * after Alpine has finished starting.
 *
 * Two bugs led to this test:
 *   1. The `@alpinejs/csp` build doesn't bind `@click` reliably on the same
 *      element as `x-data`. We now attach the listener imperatively from
 *      Alpine's `init()`.
 *   2. Alpine.start() runs inside Promise.all(loaders).then() in main.ts, so
 *      a click before Alpine finishes booting will never invoke `init()`.
 */

describe('navbar theme toggle', () => {
  beforeEach(() => {
    cy.visit('/', {
      onBeforeLoad(win) {
        if ('serviceWorker' in win.navigator) {
          win.navigator.serviceWorker.getRegistrations().then((regs) => {
            regs.forEach((r) => r.unregister());
          });
        }
      },
    });
    cy.window().then((win) => {
      if ('caches' in win) {
        win.caches.keys().then((keys) => {
          keys.forEach((k) => win.caches.delete(k));
        });
      }
    });
  });

  it('saves the counterpart theme when the navbar toggle is clicked', () => {
    // Stub the save response so the test is independent of the backend.
    cy.intercept('POST', '**/api/v1/settings', { body: { message: 'ok' } }).as('save');

    // Wait for Alpine.start() to have run (main.ts sets this flag right after).
    cy.window({ timeout: 10000 }).should('have.property', 'LWT_VITE_LOADED', true);

    cy.get('[x-data="themeToggle"]').should('exist').click();

    cy.wait('@save').its('request.body').should((body) => {
      // body is form-encoded
      const params = new URLSearchParams(typeof body === 'string' ? body : '');
      expect(params.get('key')).to.eq('set-theme-dir');
      expect(params.get('value') ?? '').to.match(/^dist\/themes\/.+\/$/);
    });
  });
});

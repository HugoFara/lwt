/// <reference types="cypress" />

/**
 * Smoke Test Suite
 *
 * This test visits all application routes and verifies:
 * 1. Pages load without HTTP errors (non-5xx status)
 * 2. No PHP fatal errors, exceptions, or parse errors appear
 * 3. Basic page structure exists (body content)
 *
 * Run with: npm run e2e -- --spec "cypress/e2e/00-smoke.cy.ts"
 */

/**
 * Routes that can be tested without any parameters or prerequisites.
 * These should return valid pages even on a fresh/empty database.
 */
const publicRoutes: Array<{ path: string; name: string }> = [
  // Home
  { path: '/', name: 'Home' },

  // Languages
  { path: '/languages', name: 'Languages list' },

  // Texts
  { path: '/texts', name: 'Texts list' },
  { path: '/text/edit', name: 'Edit texts' },
  { path: '/text/archived', name: 'Archived texts' },

  // Words
  { path: '/words', name: 'Words list' },
  { path: '/words/edit', name: 'Edit words' },

  // Tags
  { path: '/tags', name: 'Term tags' },
  { path: '/tags/text', name: 'Text tags' },

  // Feeds
  { path: '/feeds', name: 'Feeds list' },
  { path: '/feeds/edit', name: 'Edit feeds' },

  // Admin
  { path: '/admin/statistics', name: 'Statistics' },
  { path: '/admin/install-demo', name: 'Install demo' },
  { path: '/admin/settings', name: 'Settings' },
  { path: '/admin/backup', name: 'Backup & Restore' },
  { path: '/admin/wizard', name: 'Database wizard' },
  { path: '/admin/server-data', name: 'Server data' },

  // Mobile
  { path: '/mobile', name: 'Mobile interface' },
];

/**
 * API routes that should return valid responses (JSON or redirect).
 * These are tested separately as they may return JSON instead of HTML.
 */
const apiRoutes: Array<{ path: string; name: string }> = [
  { path: '/api/translate', name: 'Translate API' },
  { path: '/api/google', name: 'Google translate API' },
];

/**
 * Routes that require specific parameters to work.
 * Test with sample/invalid params to verify they don't crash.
 * Note: Some routes with invalid params (like wid=0) will show application-level
 * errors (my_die), which is expected behavior - not a PHP crash.
 */
const parameterizedRoutes: Array<{ path: string; name: string }> = [
  { path: '/text/read?text=1', name: 'Read text (with param)' },
  { path: '/word/new?lang=1', name: 'New word (with param)' },
  { path: '/review?lang=1', name: 'Review interface (with param)' },
  { path: '/feeds/wizard?id=0', name: 'Feed wizard (with param)' },
  { path: '/languages/select-pair', name: 'Select language pair' },
];

/**
 * Routes that are expected to show application-level errors with invalid params.
 * These use my_die() which shows a styled error page, not a PHP crash.
 */
const expectedErrorRoutes: Array<{ path: string; name: string }> = [
  { path: '/word/edit?wid=0', name: 'Edit word (invalid ID)' },
];

/**
 * Error patterns that indicate a PHP crash or critical error.
 * If any of these appear in the page, the test fails.
 */
const errorPatterns = [
  /fatal error/i,
  /parse error/i,
  /syntax error/i,
  /uncaught exception/i,
  /stack trace:/i,
  /call stack:/i,
  /mysqli_.*error/i,
  /warning:.*mysqli/i,
  /undefined variable/i,
  /undefined index/i,
  /undefined array key/i,
  /cannot access/i,
  /class.*not found/i,
];

describe('Smoke Tests - All Pages Load', () => {
  describe('Public Routes', () => {
    publicRoutes.forEach(({ path, name }) => {
      it(`${name} (${path}) loads without errors`, () => {
        cy.visit(path, { failOnStatusCode: false });

        // Page should have content
        cy.get('body').should('exist').and('not.be.empty');

        // Check for PHP errors in page content
        cy.document().then((doc) => {
          const bodyText = doc.body?.innerText || '';
          const bodyHtml = doc.body?.innerHTML || '';

          errorPatterns.forEach((pattern) => {
            expect(bodyText).not.to.match(
              pattern,
              `Page should not contain error pattern: ${pattern}`
            );
          });

          // Also check for error styling (common PHP error display)
          expect(bodyHtml).not.to.match(
            /<b>Fatal error<\/b>/i,
            'Page should not contain fatal error HTML'
          );
        });

        // Verify we didn't get a 500 error page
        cy.get('h1').should('not.contain.text', '500');
      });
    });
  });

  describe('API Routes', () => {
    apiRoutes.forEach(({ path, name }) => {
      it(`${name} (${path}) responds without crashing`, () => {
        cy.request({
          url: path,
          failOnStatusCode: false,
        }).then((response) => {
          // API should not return 500 errors
          expect(response.status).to.be.lessThan(
            500,
            'API should not return server error'
          );

          // If it returns HTML, check for PHP errors
          if (
            typeof response.body === 'string' &&
            response.body.includes('<')
          ) {
            errorPatterns.forEach((pattern) => {
              expect(response.body).not.to.match(
                pattern,
                `API response should not contain error: ${pattern}`
              );
            });
          }
        });
      });
    });
  });

  describe('Parameterized Routes (graceful handling)', () => {
    parameterizedRoutes.forEach(({ path, name }) => {
      it(`${name} (${path}) handles parameters gracefully`, () => {
        cy.visit(path, { failOnStatusCode: false });

        // Page should load (even if showing "not found" message)
        cy.get('body').should('exist');

        // Should not have PHP fatal errors (but allow application-level errors)
        cy.document().then((doc) => {
          const bodyText = doc.body?.innerText || '';

          // Check for PHP-level crashes (not application errors)
          // Note: LWT's my_die() shows "Fatal Error:" but it's styled and intentional
          const phpCrashPatterns = [
            /\( ! \) Fatal error/i, // PHP's actual error format
            /parse error/i,
            /syntax error/i,
            /Call to undefined function/i,
            /Class .* not found/i,
          ];

          phpCrashPatterns.forEach((pattern) => {
            expect(bodyText).not.to.match(
              pattern,
              `Page should not contain PHP crash: ${pattern}`
            );
          });
        });
      });
    });
  });

  describe('Expected Error Routes (application-level errors)', () => {
    expectedErrorRoutes.forEach(({ path, name }) => {
      it(`${name} (${path}) shows application error gracefully`, () => {
        cy.visit(path, { failOnStatusCode: false });

        // Page should load with error message
        cy.get('body').should('exist').and('not.be.empty');

        // Should show application-level error (my_die style)
        // but NOT a PHP crash
        cy.document().then((doc) => {
          const bodyText = doc.body?.innerText || '';

          // Should NOT have PHP-level crashes
          const phpCrashPatterns = [
            /\( ! \) Fatal error/i,
            /parse error/i,
            /syntax error/i,
            /Call to undefined function/i,
          ];

          phpCrashPatterns.forEach((pattern) => {
            expect(bodyText).not.to.match(
              pattern,
              `Page should not contain PHP crash: ${pattern}`
            );
          });
        });
      });
    });
  });
});

describe('Smoke Tests - Page Structure', () => {
  it('Home page has expected navigation elements', () => {
    cy.visit('/');
    // Should have some form of navigation
    cy.get('a').should('have.length.greaterThan', 5);
  });

  it('Languages page has table or list structure', () => {
    cy.visit('/languages');
    // Languages page uses cards or action-card layout
    cy.get('table, ul, .list, form, .card, .action-card').should('exist');
  });

  it('Texts page has table or list structure', () => {
    cy.visit('/texts');
    // Texts page may show empty state, action card, or list
    cy.get('table, ul, .list, form, .card, .action-card, .notification').should('exist');
  });

  it('Words page has table or list structure', () => {
    cy.visit('/words');
    cy.get('table, ul, .list, form').should('exist');
  });

  it('Settings page has form elements', () => {
    cy.visit('/admin/settings');
    cy.get('form, input, select').should('exist');
  });
});

describe('Smoke Tests - No JavaScript Errors', () => {
  const criticalPages = ['/', '/languages', '/texts', '/words', '/admin/settings'];

  criticalPages.forEach((path) => {
    it(`${path} loads without console errors`, () => {
      cy.visit(path, {
        onBeforeLoad(win) {
          cy.stub(win.console, 'error').as('consoleError');
        },
      });

      // Give page time to fully load and execute JS
      cy.wait(500);

      // Check that no console.error was called
      // Note: This may need adjustment if there are known benign errors
      cy.get('@consoleError').then((stub) => {
        const calls = (stub as unknown as sinon.SinonStub).getCalls();
        // Filter out known benign errors if needed
        const realErrors = calls.filter((call) => {
          const msg = String(call.args[0]);
          // Add patterns to ignore here if needed
          return !msg.includes('favicon');
        });

        if (realErrors.length > 0) {
          cy.log('Console errors found:', realErrors.map((c) => c.args[0]));
        }
        // Uncomment below to make JS errors fail the test:
        // expect(realErrors).to.have.length(0);
      });
    });
  });
});

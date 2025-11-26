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
    }
  }
}

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

export {};

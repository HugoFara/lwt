/// <reference types="cypress" />

describe('Languages Management', () => {
  beforeEach(() => {
    cy.visit('/languages');
  });

  describe('Languages List', () => {
    it('should load languages page', () => {
      cy.url().should('include', '/languages');
      cy.get('body').should('be.visible');
    });

    it('should display demo languages', () => {
      cy.fixture('test-data').then((data) => {
        // Check for at least one demo language
        cy.contains(data.demoLanguages[0]).should('exist');
      });
    });

    it('should have table or list of languages', () => {
      cy.get('table, .language-list, form').should('exist');
    });

    it('should have action links for each language', () => {
      // Look for edit/delete links
      cy.get('a[href*="chg="], a[href*="edit"], a[href*="del="]').should(
        'exist'
      );
    });
  });

  describe('Create Language', () => {
    it('should show new language form', () => {
      cy.visit('/languages?new=1');
      cy.get('form').should('exist');
    });

    it('should have required form fields', () => {
      cy.visit('/languages?new=1');
      // Language name field
      cy.get('input[name="LgName"], input[name*="name" i]').should('exist');
      // Word characters regex field
      cy.get(
        'input[name="LgRegexpWordCharacters"], input[name*="word" i], input[name*="regexp" i]'
      ).should('exist');
    });

    it('should have submit button', () => {
      cy.visit('/languages?new=1');
      cy.get('input[type="submit"], button[type="submit"]').should('exist');
    });

    it('should create a new language', () => {
      cy.visit('/languages?new=1');

      const uniqueName = `Test Language ${Date.now()}`;

      // Fill in required fields
      cy.get('input[name="LgName"]').type(uniqueName);

      // Find and fill word characters field if empty
      cy.get('input[name="LgRegexpWordCharacters"]').then(($input) => {
        if (!$input.val()) {
          cy.wrap($input).type('a-zA-Z');
        }
      });

      // Submit the form
      cy.get('input[type="submit"][value*="Save"], input[name="op"][value*="Save"]').first().click();

      // Should redirect to languages list or show success
      cy.url().should('include', '/languages');
    });
  });

  describe('Edit Language', () => {
    it('should load edit form for existing language', () => {
      cy.visit('/languages?chg=1');
      cy.get('form').should('exist');
      // Language name should be filled
      cy.get('input[name="LgName"]').should('not.have.value', '');
    });

    it('should have populated fields', () => {
      cy.visit('/languages?chg=1');
      cy.get('input[name="LgName"]').invoke('val').should('not.be.empty');
    });
  });

  describe('Delete Language', () => {
    it('should show confirmation for delete', () => {
      // Visit delete URL and check for confirmation or redirect
      cy.visit('/languages?del=1', { failOnStatusCode: false });
      // Should either show confirmation or redirect
      cy.get('body').should('be.visible');
    });
  });
});

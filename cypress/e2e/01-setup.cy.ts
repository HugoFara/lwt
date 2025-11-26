/// <reference types="cypress" />

describe('Database Setup', () => {
  it('should load the install demo page', () => {
    cy.visit('/admin/install-demo');
    cy.get('h1, h2, h3, h4').should('contain.text', 'Install');
  });

  it('should install demo database', () => {
    cy.visit('/admin/install-demo');
    cy.get('form').should('exist');
    cy.get('input[type="submit"], button[type="submit"]').click();
    // After install, should show success or redirect
    cy.url().should('include', '/admin/install-demo');
  });

  it('should have demo languages after install', () => {
    cy.fixture('test-data').then((data) => {
      cy.visit('/languages');
      // Check that at least some demo languages exist
      data.demoLanguages.slice(0, 3).forEach((lang: string) => {
        cy.contains(lang).should('exist');
      });
    });
  });

  it('should have demo texts after install', () => {
    cy.visit('/text/edit');
    // Check that the texts list loads
    cy.get('table, .text-list, form').should('exist');
  });
});

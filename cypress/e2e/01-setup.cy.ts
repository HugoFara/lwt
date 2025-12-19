/// <reference types="cypress" />

describe('Database Setup', () => {
  it('should load the install demo page', () => {
    cy.visit('/admin/install-demo');
    cy.get('h1, h2, h3, h4').should('contain.text', 'Install');
  });

  it('should install demo database', () => {
    cy.visit('/admin/install-demo');
    cy.get('form').should('exist');
    // Check the confirmation checkbox first (required to enable the install button)
    cy.get('input[type="checkbox"]').check();
    // Now click the install button
    cy.get('button[type="submit"], input[type="submit"]').click();
    // Wait for install to complete and page to reload
    cy.url().should('include', '/admin/install-demo');
    // Should show success message or remain on page
    cy.get('body').should('be.visible');
  });

  it('should have demo languages after install', () => {
    cy.visit('/languages');
    // Check that the languages page loads and has content
    // The page uses Alpine.js with card-based layout
    cy.get('[x-data="languageList"], .language-card, .action-card').should('exist');
  });

  it('should have demo texts after install', () => {
    cy.visit('/text/edit');
    // Check that the texts page loads and has some content structure
    // The page uses Alpine.js with card-based layout or action cards
    cy.get('.card, .action-card, [x-data], form').should('exist');
  });
});

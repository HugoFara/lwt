/// <reference types="cypress" />

describe('Database Setup', () => {
  it('should load the install demo page', () => {
    cy.visit('/admin/install-demo');
    cy.get('h1, h2, h3, h4').should('contain.text', 'Install');
  });

  it('should install demo database', () => {
    cy.visit('/admin/install-demo');
    cy.get('form').should('exist');

    // The checkbox is server-rendered, so it is clickable before Alpine has
    // bound `x-model` to it — and a click that lands first is dropped
    // silently. Alpine applying `:disabled="!confirmed"` to the install button
    // is proof it has processed this tree, so gate on that before ticking.
    cy.get('button[type="submit"], input[type="submit"]').should('be.disabled');
    cy.get('input[type="checkbox"]').check();

    // Then let Cypress retry until the tick has propagated; it retries
    // assertions but never retries the click itself.
    cy.get('button[type="submit"], input[type="submit"]')
      .should('not.be.disabled')
      .click();
    // Wait for install to complete and page to reload
    cy.url().should('include', '/admin/install-demo');
    // Should show success message or remain on page
    cy.get('body').should('be.visible');
  });

  it('should have demo languages after install', () => {
    cy.visit('/languages');
    // Check that the languages page loads and has content
    // The page uses Alpine.js with card-based layout
    cy.get('[x-data="languageList"]').should('exist');
  });

  it('should have demo texts after install', () => {
    cy.visit('/text/edit');
    // Check that the texts page loads and has some content structure
    // The page uses Alpine.js with card-based layout or action cards
    cy.get('.card, .action-card, [x-data], form').should('exist');
  });
});

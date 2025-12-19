/// <reference types="cypress" />

describe('Home Page', () => {
  beforeEach(() => {
    cy.visit('/');
  });

  it('should load the home page', () => {
    cy.url().should('include', '/');
    cy.get('body').should('be.visible');
  });

  it('should have correct page title', () => {
    cy.title().should('contain', 'LWT');
  });

  it('should have navigation links', () => {
    // Check for main navigation elements
    cy.contains(/languages?/i).should('exist');
    cy.contains(/texts?/i).should('exist');
    cy.contains(/terms?|words?/i).should('exist');
  });

  it('should have a quick menu or navigation dropdown', () => {
    // Look for dropdown, select, or navigation menu
    cy.get('select, nav, .menu, #quickmenu, .navigation').should('exist');
  });

  it('should navigate to languages page', () => {
    // Click on the Languages link in the navigation menu
    cy.get('a[href="/languages"]').first().click();
    cy.url().should('include', '/languages');
  });

  it('should navigate to texts page', () => {
    // Click on the Texts link in the navigation menu
    cy.get('a[href="/texts"]').first().click();
    cy.url().should('match', /\/text|\/texts/);
  });
});

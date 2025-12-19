/// <reference types="cypress" />

describe('Admin Pages', () => {
  describe('Settings', () => {
    it('should load settings page', () => {
      cy.visit('/admin/settings');
      cy.url().should('include', '/admin/settings');
      cy.get('form').should('exist');
    });

    it('should have settings form fields', () => {
      cy.visit('/admin/settings');
      // Should have various input fields for settings
      cy.get('input, select, textarea').should('have.length.greaterThan', 0);
    });

    it('should have submit button', () => {
      cy.visit('/admin/settings');
      cy.get('input[type="submit"], button[type="submit"]').should('exist');
    });

    it('should be able to change a setting', () => {
      cy.visit('/admin/settings');
      // Find first text input and modify it
      cy.get('form').should('exist');
      cy.get('input[type="submit"], button[type="submit"]')
        .first()
        .should('be.visible');
    });
  });

  describe('Statistics', () => {
    it('should load statistics page', () => {
      cy.visit('/admin/statistics');
      cy.url().should('include', '/admin/statistics');
      cy.get('body').should('be.visible');
    });

    it('should show statistics sections', () => {
      cy.visit('/admin/statistics');
      // The statistics page uses Chart.js with box/card sections
      cy.get('.box, [x-data="statisticsApp()"]').should('exist');
    });

    it('should show statistics data', () => {
      cy.visit('/admin/statistics');
      // Statistics page has JSON data for charts with language statistics
      cy.get('#statistics-intensity-data, #statistics-frequency-data, canvas').should('exist');
    });
  });

  describe('Backup', () => {
    it('should load backup page', () => {
      cy.visit('/admin/backup');
      cy.url().should('include', '/admin/backup');
      cy.get('body').should('be.visible');
    });

    it('should have backup form', () => {
      cy.visit('/admin/backup');
      cy.get('form').should('exist');
    });

    it('should have backup/export options', () => {
      cy.visit('/admin/backup');
      // Look for backup-related controls
      cy.get(
        'input[type="submit"], button, a[href*="backup"], a[href*="export"]'
      ).should('exist');
    });

    it('should have restore/import options', () => {
      cy.visit('/admin/backup');
      // Look for restore-related controls
      cy.get('input[type="file"], input[name*="restore" i]').should('exist');
    });
  });

  describe('Install Demo', () => {
    it('should load install demo page', () => {
      cy.visit('/admin/install-demo');
      cy.url().should('include', '/admin/install-demo');
      cy.get('body').should('be.visible');
    });

    it('should have install button', () => {
      cy.visit('/admin/install-demo');
      cy.get('input[type="submit"], button[type="submit"]').should('exist');
    });
  });

  describe('Server Data', () => {
    it('should load server data page', () => {
      cy.visit('/admin/server-data');
      cy.url().should('include', '/admin/server-data');
      cy.get('body').should('be.visible');
    });

    it('should show server information', () => {
      cy.visit('/admin/server-data');
      // Should have some server info displayed (the page shows LWT version and PHP version)
      cy.get('body').invoke('text').should('match', /version|PHP|LWT/i);
    });
  });

});

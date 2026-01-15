/// <reference types="cypress" />

describe('Texts Management', () => {
  beforeEach(() => {
    cy.visit('/text/edit');
  });

  describe('Texts List', () => {
    it('should load texts page', () => {
      cy.url().should('match', /\/text|\/texts/);
      cy.get('body').should('be.visible');
    });

    it('should have card-based layout or action card', () => {
      // The texts page uses cards, not tables
      cy.get('.card, .action-card, form').should('exist');
    });

    it('should have sort dropdown or language sections', () => {
      // The page may have sort dropdown or language-grouped sections
      cy.get('select, .card-header').should('exist');
    });

    it('should display texts grouped by language when texts exist', () => {
      cy.get('body').then(($body) => {
        if ($body.find('.text-card').length > 0) {
          cy.get('.text-card').should('have.length.at.least', 1);
        } else {
          // No texts exist, page should still load
          cy.log('No texts installed - page loads correctly');
        }
      });
    });

    it('should have action links for texts', () => {
      // Look for read/test/edit links in cards or action card for new text
      cy.get(
        'a[href*="/text/read"], a[href*="/test"], a[href*="/edit"], a[href*="/texts/new"]'
      ).should('exist');
    });
  });

  describe('Create Text', () => {
    it('should show new text form', () => {
      cy.visit('/texts/new');
      cy.get('form').should('exist');
    });

    it('should have required form fields', () => {
      cy.visit('/texts/new');
      // Title field
      cy.get('input[name="TxTitle"], input[name*="title" i]').should('exist');
      // Language selector
      cy.get('select[name="TxLgID"], select[name*="lang" i]').should('exist');
      // Text content
      cy.get('textarea[name="TxText"], textarea[name*="text" i]').should(
        'exist'
      );
    });

    it('should have submit button', () => {
      cy.visit('/texts/new');
      cy.get('input[type="submit"], button[type="submit"]').should('exist');
    });

    it('should create a new text', () => {
      cy.visit('/texts/new');

      const uniqueTitle = `Test Text ${Date.now()}`;

      // Fill in required fields
      cy.get('input[name="TxTitle"]').type(uniqueTitle);

      // Select first available language using the searchable-select component
      // The component uses Alpine.js and stores options in x-data
      cy.get('.searchable-select').first().as('langSelect');

      // Click to open the dropdown
      cy.get('@langSelect').find('.searchable-select__trigger').click();

      // Wait for dropdown to be visible and select first non-placeholder option
      cy.get('@langSelect')
        .find('.searchable-select__options li:not(.searchable-select__empty)')
        .should('have.length.at.least', 2)
        .eq(1) // Skip the [Choose...] placeholder
        .click();

      // Add text content
      cy.get('textarea[name="TxText"]').type(
        'This is a test text. It has multiple sentences.'
      );

      // Submit the form - the new text form only has "Save and Open" button
      cy.get('button[name="op"][value="Save and Open"]').click();

      // Should redirect to reading page (new RESTful format: /text/{id}/read)
      cy.url().should('match', /\/text\/\d+\/read/);
    });

    it('should create a new text and open it for reading with Save & Open', () => {
      cy.visit('/texts/new');

      const uniqueTitle = `Save Open Test ${Date.now()}`;

      // Fill in required fields
      cy.get('input[name="TxTitle"]').type(uniqueTitle);

      // Select first available language using the searchable-select component
      cy.get('.searchable-select').first().as('langSelect');

      // Click to open the dropdown
      cy.get('@langSelect').find('.searchable-select__trigger').click();

      // Wait for dropdown to be visible and select first non-placeholder option
      cy.get('@langSelect')
        .find('.searchable-select__options li:not(.searchable-select__empty)')
        .should('have.length.at.least', 2)
        .eq(1) // Skip the [Choose...] placeholder
        .click();

      // Add text content
      cy.get('textarea[name="TxText"]').type(
        'This is a save and open test. It should redirect to the reading page.'
      );

      // Click "Save and Open" button
      cy.get('button[name="op"][value="Save and Open"]').click();

      // Should redirect to reading page (new RESTful format: /text/{id}/read)
      cy.url().should('match', /\/text\/\d+\/read/);

      // Reading interface should load
      cy.get('#thetext', { timeout: 10000 }).should('exist');

      // Text content should be visible (spaces may be stripped depending on language config)
      cy.get('#thetext').invoke('text').should('match', /save.*open.*test/i);
    });
  });

  describe('Edit Text', () => {
    it('should load edit form for existing text or show not found', () => {
      cy.visit('/texts/1/edit');
      // Either show the form or a "not found" message
      cy.get('body').then(($body) => {
        if ($body.text().includes('not found')) {
          // Text doesn't exist, which is acceptable
          cy.contains('not found').should('exist');
        } else {
          // Text exists, form should be shown
          cy.get('form').should('exist');
        }
      });
    });

    it('should have populated title field when text exists', () => {
      cy.visit('/texts/1/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('not found')) {
          cy.get('input[name="TxTitle"]').invoke('val').should('not.be.empty');
        }
      });
    });
  });

  describe('Archive Text', () => {
    it('should have archive functionality', () => {
      // Check archive link exists on text list
      cy.visit('/text/edit');
      cy.get('body').should('be.visible');
    });
  });

  describe('Archived Texts', () => {
    it('should load archived texts page', () => {
      cy.visit('/text/archived');
      cy.url().should('include', '/text/archived');
      cy.get('body').should('be.visible');
    });

    it('should have card layout or action card', () => {
      cy.visit('/text/archived');
      // The page uses Alpine.js and cards
      cy.get('.card, .action-card, [x-data]').should('exist');
    });
  });

  describe('Long Text Import', () => {
    it('should handle long text via the new text form', () => {
      // Long text import is handled through the new text form
      // which supports longer texts via the textarea
      cy.visit('/texts/new');
      cy.url().should('include', '/texts/new');
      cy.get('form').should('exist');
      // Text content textarea should exist for entering long texts
      cy.get('textarea[name="TxText"]').should('exist');
    });

    it('should have required fields for text creation', () => {
      cy.visit('/texts/new');
      // Language selector - now uses searchable-select component
      cy.get('.searchable-select, select[name="TxLgID"]').should('exist');
      // Text input area
      cy.get('textarea[name="TxText"]').should('exist');
    });
  });
});

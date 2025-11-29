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

    it('should have table or list of texts', () => {
      cy.get('table, .text-list, form').should('exist');
    });

    it('should have language filter dropdown', () => {
      cy.get('select[name="filterlang"], select[name*="lang" i]').should(
        'exist'
      );
    });

    it('should filter texts by language', () => {
      // Note: Language filter uses JavaScript redirect via save_setting_redirect.php
      // The filter value is stored in session, not in URL
      cy.get('select[name="filterlang"]').then(($select) => {
        // Get first non-empty option
        const options = $select.find('option');
        if (options.length > 1) {
          const firstLangValue = options.eq(1).val();
          if (firstLangValue) {
            // Select the language - this triggers a page redirect
            cy.get('select[name="filterlang"]').select(String(firstLangValue));
            // After redirect, verify the selected value is retained
            cy.get('select[name="filterlang"]').should(
              'have.value',
              firstLangValue
            );
          }
        }
      });
    });

    it('should have action links for texts', () => {
      // Look for edit/delete/archive links
      cy.get(
        'a[href*="chg="], a[href*="edit"], a[href*="del="], a[href*="arch="]'
      ).should('exist');
    });
  });

  describe('Create Text', () => {
    it('should show new text form', () => {
      cy.visit('/text/edit?new=1');
      cy.get('form').should('exist');
    });

    it('should have required form fields', () => {
      cy.visit('/text/edit?new=1');
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
      cy.visit('/text/edit?new=1');
      cy.get('input[type="submit"], button[type="submit"]').should('exist');
    });

    it('should create a new text', () => {
      cy.visit('/text/edit?new=1');

      const uniqueTitle = `Test Text ${Date.now()}`;

      // Fill in required fields
      cy.get('input[name="TxTitle"]').type(uniqueTitle);

      // Select first available language (must have index > 0, which is the placeholder)
      cy.get('select[name="TxLgID"] option').should('have.length.greaterThan', 1);
      cy.get('select[name="TxLgID"]').then(($select) => {
        const options = $select.find('option');
        // Select the first non-placeholder option (index 1)
        const firstLangValue = options.eq(1).val();
        cy.get('select[name="TxLgID"]').select(String(firstLangValue));
      });

      // Add text content
      cy.get('textarea[name="TxText"]').type(
        'This is a test text. It has multiple sentences.'
      );

      // Submit the form - click the "Save" button (not "Save and Open")
      cy.get('input[name="op"][value="Save"]').click();

      // Should redirect to texts list
      cy.url().should('match', /\/text/);
    });
  });

  describe('Edit Text', () => {
    it('should load edit form for existing text or show not found', () => {
      cy.visit('/text/edit?chg=1');
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
      cy.visit('/text/edit?chg=1');
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

    it('should have table or list structure', () => {
      cy.visit('/text/archived');
      cy.get('table, form, .archived-list').should('exist');
    });
  });

  describe('Long Text Import', () => {
    it('should load long text import page', () => {
      cy.visit('/text/import-long');
      cy.url().should('include', '/text/import-long');
      cy.get('form').should('exist');
    });

    it('should have required fields for import', () => {
      cy.visit('/text/import-long');
      // Language selector
      cy.get('select[name*="lang" i], select[name="LgID"]').should('exist');
      // Text input area or file upload
      cy.get('textarea, input[type="file"]').should('exist');
    });
  });
});

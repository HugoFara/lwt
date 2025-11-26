/// <reference types="cypress" />

describe('Words Management', () => {
  // Note: /words/edit is the main word list page with filters
  // /words is a helper page for marking all words as well-known
  beforeEach(() => {
    cy.visit('/words/edit');
  });

  describe('Words List', () => {
    it('should load words page', () => {
      cy.url().should('match', /\/words/);
      cy.get('body').should('be.visible');
    });

    it('should have table or list of words', () => {
      cy.get('table, .word-list, form').should('exist');
    });

    it('should have language filter', () => {
      cy.get('select[name="filterlang"], select[name*="lang" i]').should(
        'exist'
      );
    });

    it('should have status filter', () => {
      cy.get(
        'select[name="status"], select[name*="status" i], input[name*="status" i]'
      ).should('exist');
    });

    it('should have search/query input', () => {
      cy.get(
        'input[name="query"], input[name*="search" i], input[type="text"]'
      ).should('exist');
    });

    it('should filter by language', () => {
      // Note: Language filter uses JavaScript redirect via save_setting_redirect.php
      // The filter value is stored in session, not in URL
      cy.get('select[name="filterlang"]').then(($select) => {
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
  });

  describe('Words Edit List', () => {
    it('should load words edit page', () => {
      cy.visit('/words/edit');
      cy.url().should('include', '/words/edit');
      cy.get('body').should('be.visible');
    });

    it('should have bulk selection checkboxes', () => {
      cy.visit('/words/edit');
      cy.get(
        'input[type="checkbox"].markcheck, input[type="checkbox"][name*="marked"]'
      ).should('exist');
    });

    it('should have bulk action dropdown', () => {
      cy.visit('/words/edit');
      cy.get('select#markaction, select[name="markaction"]').should('exist');
    });
  });

  describe('Single Word Edit', () => {
    // Note: Word editing from the list uses /words/edit?chg=X
    // The /word/edit endpoint is for editing from within reading context
    // These tests verify editing works when a word exists in the database

    // Helper to extract word ID from href and visit the correct edit URL
    const visitWordEditPage = () => {
      cy.visit('/words/edit');
      cy.get('a[href*="chg="]')
        .first()
        .then(($link) => {
          const href = $link.attr('href');
          if (href) {
            // Extract the chg parameter value from the href
            // href might be "index.php/words/edit?chg=177" or "/words/edit?chg=177"
            const match = href.match(/chg=(\d+)/);
            if (match) {
              const wordId = match[1];
              cy.visit(`/words/edit?chg=${wordId}`);
            }
          }
        });
    };

    it('should load word edit page', () => {
      visitWordEditPage();
      cy.get('form').should('exist');
    });

    it('should have word text field', () => {
      visitWordEditPage();
      cy.get('input[name="WoText"]').should('exist');
    });

    it('should have translation field', () => {
      visitWordEditPage();
      cy.get('textarea[name="WoTranslation"]').should('exist');
    });

    it('should have status selector', () => {
      visitWordEditPage();
      // Status uses radio buttons in the edit form
      cy.get('input[type="radio"][name="WoStatus"]').should('exist');
    });

    it('should have submit button', () => {
      visitWordEditPage();
      cy.get('input[type="submit"][value="Change"]').should('exist');
    });
  });

  describe('Word Status', () => {
    it('should be able to change word status', () => {
      cy.visit('/words/edit');
      cy.get('a[href*="chg="]')
        .first()
        .then(($link) => {
          const href = $link.attr('href');
          if (href) {
            // Extract word ID and visit the correct URL
            const match = href.match(/chg=(\d+)/);
            if (match) {
              const wordId = match[1];
              cy.visit(`/words/edit?chg=${wordId}`);
              // Find status radio buttons and verify they exist
              cy.get('input[type="radio"][name="WoStatus"]').should(
                'have.length.greaterThan',
                0
              );
            }
          }
        });
    });
  });

  describe('Bulk Operations', () => {
    it('should have select all functionality', () => {
      cy.visit('/words/edit');
      // The page uses "Mark All" and "Mark None" buttons instead of a checkbox
      cy.get('input[type="button"][value="Mark All"]').should('exist');
      cy.get('input[type="button"][value="Mark None"]').should('exist');
    });

    it('should have bulk action options', () => {
      cy.visit('/words/edit');
      cy.get('select#markaction, select[name="markaction"]').then(($select) => {
        // Should have multiple action options
        const options = $select.find('option');
        expect(options.length).to.be.greaterThan(1);
      });
    });
  });
});

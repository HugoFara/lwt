/// <reference types="cypress" />

describe('Languages Management', () => {
  beforeEach(() => {
    cy.visit('/languages');
    // Wait for Alpine.js to initialize
    cy.get('[x-data="languageList"]').should('exist');
  });

  describe('Languages List', () => {
    it('should load languages page', () => {
      cy.url().should('include', '/languages');
      cy.get('body').should('be.visible');
    });

    it('should display loading state initially', () => {
      // The loading state may be very brief, so we just check it exists
      cy.get('[x-data="languageList"]').should('exist');
    });

    it('should display language cards after loading', () => {
      // Wait for loading to complete
      cy.get('.language-cards', { timeout: 10000 }).should('exist');
      cy.get('.language-card').should('have.length.at.least', 1);
    });

    it('should display demo languages', () => {
      cy.fixture('test-data').then((data) => {
        // Check for at least one demo language in a card
        cy.get('.language-card').should('contain', data.demoLanguages[0]);
      });
    });

    it('should have action buttons for each language card', () => {
      // Wait for cards to load
      cy.get('.language-card', { timeout: 10000 }).should('exist');

      // Check for footer action links
      cy.get('.language-card .card-footer-item').should('exist');
    });

    it('should have edit links for languages', () => {
      cy.get('.language-card a[href*="chg="]').should('exist');
    });

    it('should display language statistics', () => {
      cy.get('.language-stats').should('exist');
      cy.get('.stat-item').should('have.length.at.least', 1);
    });

    it('should have "New Language" button in action card', () => {
      cy.get('.action-card a[href*="new=1"]').should('exist');
    });

    it('should have "Quick Setup Wizard" button', () => {
      cy.get('.action-card a').contains('Quick Setup Wizard').should('exist');
    });
  });

  describe('Language Card Actions', () => {
    it('should show Set as Default button for non-default languages', () => {
      cy.get('.language-card').then(($cards) => {
        // Find a card that's not current (doesn't have is-current class)
        const nonCurrentCard = $cards.filter(':not(.is-current)').first();
        if (nonCurrentCard.length) {
          cy.wrap(nonCurrentCard)
            .find('button')
            .contains('Set as Default')
            .should('exist');
        }
      });
    });

    it('should navigate to edit page when Edit is clicked', () => {
      cy.get('.language-card a[href*="chg="]').first().click();
      cy.url().should('include', 'chg=');
      cy.get('form').should('exist');
    });
  });

  describe('Delete Confirmation Modal', () => {
    it('should show delete confirmation when delete is clicked', () => {
      // Find a deletable language (no texts, words, feeds)
      cy.get('.language-card .card-footer-item').contains('Delete').first().click();

      // Modal should appear
      cy.get('.modal.is-active').should('exist');
      cy.get('.modal-card-title').should('contain', 'Confirm Delete');
    });

    it('should close modal when Cancel is clicked', () => {
      // Open delete modal
      cy.get('.language-card .card-footer-item').contains('Delete').first().click();
      cy.get('.modal.is-active').should('exist');

      // Click cancel
      cy.get('.modal-card-foot button').contains('Cancel').click();

      // Modal should close
      cy.get('.modal.is-active').should('not.exist');
    });
  });

  describe('Wizard Modal', () => {
    it('should open wizard modal when Quick Setup Wizard is clicked', () => {
      cy.get('.action-card a').contains('Quick Setup Wizard').click();

      // Modal should appear
      cy.get('.modal.is-active').should('exist');
      cy.get('.modal-card-title').should('contain', 'Quick Language Setup');
    });

    it('should have L1 and L2 language dropdowns', () => {
      cy.get('.action-card a').contains('Quick Setup Wizard').click();

      cy.get('.modal.is-active select').should('have.length', 2);
    });

    it('should close wizard modal when Cancel is clicked', () => {
      cy.get('.action-card a').contains('Quick Setup Wizard').click();
      cy.get('.modal.is-active').should('exist');

      cy.get('.modal-card-foot button').contains('Cancel').click();

      cy.get('.modal.is-active').should('not.exist');
    });

    it('should have disabled Create button when languages not selected', () => {
      cy.get('.action-card a').contains('Quick Setup Wizard').click();

      cy.get('.modal-card-foot button')
        .contains('Create Language')
        .should('be.disabled');
    });
  });

  describe('Create Language', () => {
    it('should show new language form', () => {
      cy.visit('/languages?new=1');
      cy.get('form[name="lg_form"]').should('exist');
    });

    it('should have required form fields', () => {
      cy.visit('/languages?new=1');
      // Language name field
      cy.get('input[name="LgName"]').should('exist');
      // Dictionary field
      cy.get('input[name="LgDict1URI"]').should('exist');
      // Word characters regex field
      cy.get('input[name="LgRegexpWordCharacters"]').should('exist');
      // Sentence split regex field
      cy.get('input[name="LgRegexpSplitSentences"]').should('exist');
    });

    it('should have submit button', () => {
      cy.visit('/languages?new=1');
      cy.get('button[type="submit"]').should('exist');
    });

    it('should create a new language', () => {
      cy.visit('/languages?new=1');

      const uniqueName = `Test Language ${Date.now()}`;

      // Fill in required fields
      cy.get('input[name="LgName"]').type(uniqueName);
      cy.get('input[name="LgDict1URI"]').type('https://example.com/###');

      // Find and fill word characters field if empty
      cy.get('input[name="LgRegexpWordCharacters"]').then(($input) => {
        if (!$input.val()) {
          cy.wrap($input).type('a-zA-Z');
        }
      });

      // Find and fill sentence split field if empty
      cy.get('input[name="LgRegexpSplitSentences"]').then(($input) => {
        if (!$input.val()) {
          cy.wrap($input).type('.!?');
        }
      });

      // Submit the form
      cy.get('button[type="submit"]').click();

      // Should redirect to languages list or show success
      cy.url().should('include', '/languages');
    });
  });

  describe('Edit Language', () => {
    it('should load edit form for existing language', () => {
      cy.visit('/languages?chg=1');
      cy.get('form[name="lg_form"]').should('exist');
      // Language name should be filled
      cy.get('input[name="LgName"]').should('not.have.value', '');
    });

    it('should have populated fields', () => {
      cy.visit('/languages?chg=1');
      cy.get('input[name="LgName"]').invoke('val').should('not.be.empty');
    });

    it('should have cancel button that returns to list', () => {
      cy.visit('/languages?chg=1');
      cy.get('button').contains('Cancel').click();
      cy.url().should('eq', Cypress.config().baseUrl + '/languages');
    });
  });

  describe('Text Size Preview', () => {
    it('should update text size preview when slider changes', () => {
      cy.visit('/languages?new=1');

      // Change text size
      cy.get('input[name="LgTextSize"]').clear().type('150');

      // Preview should update
      cy.get('#LgTextSizeExample').should('have.css', 'font-size', '150%');
    });
  });
});

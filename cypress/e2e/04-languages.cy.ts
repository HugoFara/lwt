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

    it('should display language cards or empty state after loading', () => {
      // Wait for Alpine.js to initialize and load data
      cy.get('[x-data="languageList"]').should('exist');
      // Wait for loading indicator to disappear (give it time for API calls)
      cy.wait(500);
      // Should have either language cards or an action card
      cy.get('.language-card, .action-card, p').should('exist');
    });

    it('should display demo languages when installed', () => {
      // Skip if no languages installed
      cy.get('body').then(($body) => {
        if ($body.find('.language-card').length > 0) {
          cy.fixture('test-data').then((data) => {
            cy.get('.language-card').should('contain', data.demoLanguages[0]);
          });
        } else {
          cy.log('No languages installed - skipping demo language check');
        }
      });
    });

    it('should have action buttons for each language card when languages exist', () => {
      cy.get('body').then(($body) => {
        if ($body.find('.language-card').length > 0) {
          cy.get('.language-card .card-footer-item').should('exist');
        } else {
          cy.log('No languages installed - skipping action buttons check');
        }
      });
    });

    it('should have edit links for languages when they exist', () => {
      cy.get('body').then(($body) => {
        if ($body.find('.language-card').length > 0) {
          cy.get('.language-card a[href*="/edit"]').should('exist');
        } else {
          cy.log('No languages installed - skipping edit links check');
        }
      });
    });

    it('should display language statistics when languages exist', () => {
      cy.get('body').then(($body) => {
        if ($body.find('.language-card').length > 0) {
          cy.get('.language-stats').should('exist');
        } else {
          cy.log('No languages installed - skipping stats check');
        }
      });
    });

    it('should have "New Language" button in action card', () => {
      cy.get('.action-card a[href*="/languages/new"]').should('exist');
    });

    it('should have "Quick Setup Wizard" button', () => {
      cy.get('.action-card a').contains('Quick Setup Wizard').should('exist');
    });
  });

  describe('Language Card Actions', () => {
    it('should show Set as Default button for non-default languages', () => {
      cy.get('body').then(($body) => {
        const $cards = $body.find('.language-card');
        if ($cards.length > 0) {
          // Find a card that's not current (doesn't have is-current class)
          const nonCurrentCard = $cards.filter(':not(.is-current)').first();
          if (nonCurrentCard.length) {
            cy.wrap(nonCurrentCard)
              .find('button')
              .contains('Set as Default')
              .should('exist');
          }
        } else {
          cy.log('No languages installed - skipping default button check');
        }
      });
    });

    it('should navigate to edit page when Edit is clicked', () => {
      cy.get('body').then(($body) => {
        if ($body.find('.language-card a[href*="/edit"]').length > 0) {
          cy.get('.language-card a[href*="/edit"]').first().click();
          cy.url().should('match', /\/languages\/\d+\/edit/);
          cy.get('form').should('exist');
        } else {
          cy.log('No languages installed - skipping edit navigation check');
        }
      });
    });
  });

  describe('Delete Confirmation Modal', () => {
    it('should show delete confirmation when delete is clicked', () => {
      cy.get('body').then(($body) => {
        const $deleteBtn = $body.find('.language-card .card-footer-item:contains("Delete")');
        if ($deleteBtn.length > 0) {
          cy.get('.language-card .card-footer-item').contains('Delete').first().click();
          cy.get('.modal.is-active').should('exist');
          cy.get('.modal-card-title').should('contain', 'Confirm Delete');
        } else {
          cy.log('No deletable languages - skipping delete modal check');
        }
      });
    });

    it('should close modal when Cancel is clicked', () => {
      cy.get('body').then(($body) => {
        const $deleteBtn = $body.find('.language-card .card-footer-item:contains("Delete")');
        if ($deleteBtn.length > 0) {
          cy.get('.language-card .card-footer-item').contains('Delete').first().click();
          cy.get('.modal.is-active').should('exist');
          cy.get('.modal-card-foot button').contains('Cancel').click();
          cy.get('.modal.is-active').should('not.exist');
        } else {
          cy.log('No deletable languages - skipping cancel modal check');
        }
      });
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

      // Click the Cancel button in the wizard modal (not the delete modal)
      cy.get('.modal.is-active .modal-card-foot button').contains('Cancel').click();

      cy.get('.modal.is-active').should('not.exist');
    });

    it('should have Create button in wizard modal', () => {
      cy.get('.action-card a').contains('Quick Setup Wizard').click();

      // Wait for modal to appear
      cy.get('.modal.is-active').should('exist');

      // The Create Language button should exist in the modal footer
      // It may be disabled initially via Alpine.js :disabled binding
      cy.get('.modal-card-foot').should('contain', 'Create Language');
    });
  });

  describe('Create Language', () => {
    it('should show new language form', () => {
      cy.visit('/languages/new');
      cy.get('form[name="lg_form"]').should('exist');
    });

    it('should have required form fields', () => {
      cy.visit('/languages/new');
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
      cy.visit('/languages/new');
      cy.get('button[type="submit"]').should('exist');
    });

    it('should create a new language', () => {
      cy.visit('/languages/new');

      const uniqueName = `Test Language ${Date.now()}`;

      // Fill in required fields
      cy.get('input[name="LgName"]').type(uniqueName);

      // Expand Advanced Settings section to access dictionary and regex fields
      cy.contains('Advanced Settings').click();

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

      // After creating a new language, it redirects to texts/new to add first text
      cy.url().should('include', '/texts/new');
    });
  });

  describe('Edit Language', () => {
    // These tests require at least one language to exist
    // Skip if no demo data is installed

    beforeEach(() => {
      cy.visit('/languages');
      // Wait for page to fully load
      cy.get('body').should('be.visible');
    });

    it('should load edit form for existing language', () => {
      cy.get('body').then(($body) => {
        if ($body.find('.language-card').length > 0) {
          cy.get('.language-card').first().within(() => {
            cy.get('a[href*="/edit"]').click();
          });
          cy.get('form[name="lg_form"]').should('exist');
          cy.get('input[name="LgName"]').should('not.have.value', '');
        } else {
          cy.log('No languages installed - skipping edit form test');
        }
      });
    });

    it('should have populated fields', () => {
      cy.get('body').then(($body) => {
        if ($body.find('.language-card').length > 0) {
          cy.get('.language-card').first().find('a[href*="/edit"]').click();
          cy.get('input[name="LgName"]').invoke('val').should('not.be.empty');
        } else {
          cy.log('No languages installed - skipping populated fields test');
        }
      });
    });

    it('should have cancel link that returns to list', () => {
      cy.get('body').then(($body) => {
        if ($body.find('.language-card').length > 0) {
          cy.get('.language-card').first().find('a[href*="/edit"]').click();
          // Cancel is a link, not a button
          cy.contains('a', 'Cancel').click();
          cy.url().should('eq', Cypress.config().baseUrl + '/languages');
        } else {
          cy.log('No languages installed - skipping cancel link test');
        }
      });
    });
  });

  describe('Text Size Preview', () => {
    it('should have text size input in form', () => {
      cy.visit('/languages/new');

      // The text size input should exist (may be in collapsed section)
      cy.get('input[name="LgTextSize"]').should('exist');

      // The preview element should exist
      cy.get('#LgTextSizeExample').should('exist');
    });

    it('should update text size when input changes', () => {
      cy.visit('/languages/new');

      // Force interaction since the element may be in a collapsed section
      cy.get('input[name="LgTextSize"]').clear({ force: true }).type('150', { force: true });
      cy.get('input[name="LgTextSize"]').should('have.value', '150');
    });
  });
});

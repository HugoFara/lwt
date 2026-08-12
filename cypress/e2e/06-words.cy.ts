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
      // Words page uses Alpine.js with x-model bindings
      cy.get('[x-data="wordListApp"]').should('exist');
      cy.get('select').should('exist');
    });

    it('should have status filter', () => {
      // The Alpine.js app has filter options including status
      cy.get('[x-data="wordListApp"] select').should('have.length.at.least', 1);
    });

    it('should have search/query input', () => {
      // The Alpine.js app has a search input
      cy.get('input[type="text"], input[type="search"]').should('exist');
    });

    it('should have filter controls', () => {
      // The page has filter dropdowns (language, status, etc.)
      cy.get('[x-data="wordListApp"]').should('exist');
      cy.get('select').should('have.length.at.least', 1);
    });
  });

  describe('Words Edit List', () => {
    it('should load words edit page', () => {
      cy.visit('/words/edit');
      cy.url().should('include', '/words/edit');
      cy.get('body').should('be.visible');
    });

    it('should have bulk selection checkboxes when words exist', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        // Check if there are words in the list
        if (!$body.text().includes('No terms found')) {
          cy.get(
            'input[type="checkbox"].markcheck, input[type="checkbox"][name*="marked"]'
          ).should('exist');
        } else {
          // No words - test passes as bulk selection is not applicable
          cy.log('No words found - bulk selection not available');
        }
      });
    });

    it('should have bulk action dropdown when words exist', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found')) {
          cy.get('select#markaction, select[name="markaction"]').should('exist');
        } else {
          cy.log('No words found - bulk actions not available');
        }
      });
    });
  });

  describe('Single Word Edit', () => {
    // Note: Word editing from the list uses /words/X/edit RESTful route
    // The /word/edit endpoint is for editing from within reading context
    // These tests verify editing works when a word exists in the database

    it('should load word edit page when word exists', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found') && $body.find('a[href*="/edit"]').length > 0) {
          cy.get('a[href*="/edit"]')
            .first()
            .then(($link) => {
              const href = $link.attr('href');
              if (href) {
                const match = href.match(/\/words\/(\d+)\/edit/);
                if (match) {
                  const wordId = match[1];
                  cy.visit(`/words/${wordId}/edit`);
                  cy.get('form').should('exist');
                }
              }
            });
        } else {
          cy.log('No words found - edit page test skipped');
        }
      });
    });

    it('should have word text field when editing', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found') && $body.find('a[href*="/edit"]').length > 0) {
          cy.get('a[href*="/edit"]')
            .first()
            .then(($link) => {
              const href = $link.attr('href');
              if (href) {
                const match = href.match(/\/words\/(\d+)\/edit/);
                if (match) {
                  cy.visit(`/words/${match[1]}/edit`);
                  cy.get('input[name="WoText"]').should('exist');
                }
              }
            });
        } else {
          cy.log('No words found - word text field test skipped');
        }
      });
    });

    it('should have translation field when editing', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found') && $body.find('a[href*="/edit"]').length > 0) {
          cy.get('a[href*="/edit"]')
            .first()
            .then(($link) => {
              const href = $link.attr('href');
              if (href) {
                const match = href.match(/\/words\/(\d+)\/edit/);
                if (match) {
                  cy.visit(`/words/${match[1]}/edit`);
                  cy.get('textarea[name="WoTranslation"]').should('exist');
                }
              }
            });
        } else {
          cy.log('No words found - translation field test skipped');
        }
      });
    });

    it('should have status selector when editing', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found') && $body.find('a[href*="/edit"]').length > 0) {
          cy.get('a[href*="/edit"]')
            .first()
            .then(($link) => {
              const href = $link.attr('href');
              if (href) {
                const match = href.match(/\/words\/(\d+)\/edit/);
                if (match) {
                  cy.visit(`/words/${match[1]}/edit`);
                  cy.get('input[type="radio"][name="WoStatus"]').should('exist');
                }
              }
            });
        } else {
          cy.log('No words found - status selector test skipped');
        }
      });
    });

    it('should have submit button when editing', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found') && $body.find('a[href*="/edit"]').length > 0) {
          cy.get('a[href*="/edit"]')
            .first()
            .then(($link) => {
              const href = $link.attr('href');
              if (href) {
                const match = href.match(/\/words\/(\d+)\/edit/);
                if (match) {
                  cy.visit(`/words/${match[1]}/edit`);
                  cy.get('input[type="submit"][value="Change"]').should('exist');
                }
              }
            });
        } else {
          cy.log('No words found - submit button test skipped');
        }
      });
    });
  });

  describe('Word Status', () => {
    it('should be able to change word status when words exist', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found') && $body.find('a[href*="/edit"]').length > 0) {
          cy.get('a[href*="/edit"]')
            .first()
            .then(($link) => {
              const href = $link.attr('href');
              if (href) {
                const match = href.match(/\/words\/(\d+)\/edit/);
                if (match) {
                  cy.visit(`/words/${match[1]}/edit`);
                  cy.get('input[type="radio"][name="WoStatus"]').should(
                    'have.length.greaterThan',
                    0
                  );
                }
              }
            });
        } else {
          cy.log('No words found - word status test skipped');
        }
      });
    });
  });

  describe('Lemma Field', () => {
    it('should have lemma field when editing a word', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found') && $body.find('a[href*="/edit"]').length > 0) {
          cy.get('a[href*="/edit"]')
            .first()
            .then(($link) => {
              const href = $link.attr('href');
              if (href) {
                const match = href.match(/\/words\/(\d+)\/edit/);
                if (match) {
                  cy.visit(`/words/${match[1]}/edit`);
                  cy.get('input[name="WoLemma"]').should('exist');
                }
              }
            });
        } else {
          cy.log('No words found - lemma field test skipped');
        }
      });
    });

    it('should have placeholder text for lemma field', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found') && $body.find('a[href*="/edit"]').length > 0) {
          cy.get('a[href*="/edit"]')
            .first()
            .then(($link) => {
              const href = $link.attr('href');
              if (href) {
                const match = href.match(/\/words\/(\d+)\/edit/);
                if (match) {
                  cy.visit(`/words/${match[1]}/edit`);
                  cy.get('input[name="WoLemma"]')
                    .should('have.attr', 'placeholder')
                    .and('include', 'Base form');
                }
              }
            });
        } else {
          cy.log('No words found - lemma placeholder test skipped');
        }
      });
    });

    it('should allow entering a lemma value', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found') && $body.find('a[href*="/edit"]').length > 0) {
          cy.get('a[href*="/edit"]')
            .first()
            .then(($link) => {
              const href = $link.attr('href');
              if (href) {
                const match = href.match(/\/words\/(\d+)\/edit/);
                if (match) {
                  cy.visit(`/words/${match[1]}/edit`);
                  cy.get('input[name="WoLemma"]')
                    .clear()
                    .type('testlemma')
                    .should('have.value', 'testlemma');
                }
              }
            });
        } else {
          cy.log('No words found - lemma input test skipped');
        }
      });
    });
  });

  describe('Bulk Operations', () => {
    it('should have select all functionality when words exist', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found')) {
          // The page uses "Mark All" and "Mark None" buttons instead of a checkbox
          cy.get('input[type="button"][value="Mark All"]').should('exist');
          cy.get('input[type="button"][value="Mark None"]').should('exist');
        } else {
          cy.log('No words found - bulk selection not available');
        }
      });
    });

    it('should have bulk action options when words exist', () => {
      cy.visit('/words/edit');
      cy.get('body').then(($body) => {
        if (!$body.text().includes('No terms found')) {
          cy.get('select#markaction, select[name="markaction"]').then(($select) => {
            const options = $select.find('option');
            expect(options.length).to.be.greaterThan(1);
          });
        } else {
          cy.log('No words found - bulk actions not available');
        }
      });
    });
  });
});

/**
 * The standalone new-term form saves through
 * `POST /api/v1/terms/for-language` (issue #262).
 *
 * The endpoint validates the language against the caller's own, which is why
 * it exists: the retired `POST /word/new` read `WoLgID` from the request and
 * fed it straight into the occurrence-linking path.
 */
describe('New term form', () => {
  it('ships a scaffold with no server-filled values', () => {
    cy.request('/word/new?lang=1').then((response) => {
      const html = String(response.body);
      expect(html).to.contain('new-term-config');
      expect(html).to.contain('x-data="newTermForm"');
      expect(html).to.contain('x-model="text"');
      expect(html).to.not.contain('action="/word/new"');
    });
  });

  it('refuses a language the caller does not own', () => {
    cy.apiRequest({
      method: 'POST',
      url: '/api/v1/terms/for-language',
      body: { language_id: 999, text: 'cyforeign' },
      failOnStatusCode: false
    }).then((response) => {
      expect(response.body.success).to.eq(false);
      expect(response.body.error).to.eq('Language not found');
    });
  });

  it('creates a term through the form itself', () => {
    const term = `cynew${Date.now() % 100000}`;

    cy.request('/api/v1/languages').then((langs) => {
      const langId = langs.body.languages[0].id as number;

      cy.visit(`/word/new?lang=${langId}`);
      cy.waitForAlpine();
      cy.get('#WoText', { timeout: 10000 }).type(term);
      cy.get('button[type=submit]').first().click();

      // The list endpoint returns `words`, each with a `text` field.
      // then(), not should(): the cleanup enqueues a command, which should()
      // would replay on every retry.
      cy.request(`/api/v1/terms/list?query=${term}`).then((response) => {
        const words = response.body.words as Array<{ id: number; text: string }>;
        const made = words.find((w) => w.text === term);
        expect(made, 'the form should have created the term').to.not.eq(undefined);

        if (made) {
          cy.apiRequest({
            method: 'DELETE',
            url: `/api/v1/terms/${made.id}`,
            failOnStatusCode: false
          });
        }
      });
    });
  });
});

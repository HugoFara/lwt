/// <reference types="cypress" />

/**
 * The bulk-translate table renders from `GET /terms/unknown-for-translate`.
 * The rows still carry the `term[N][...]` field names the save path parses, so
 * these specs check both halves: that the CSP Alpine build renders the rows,
 * and that the 1-based names survive the move from a PHP counter to an x-for
 * index — an off-by-one there would silently save the wrong statuses.
 */
describe('Bulk translate', () => {
  /** A text that has unknown words to translate. */
  const findText = (): Cypress.Chainable<number> => {
    return cy.request('/api/v1/texts/by-language/2').then((response) => {
      const texts = response.body.texts ?? response.body.data ?? [];
      expect(texts.length, 'a text must exist').to.be.greaterThan(0);
      return texts[0].id as number;
    });
  };

  it('ships a scaffold and a config blob, never rendered rows', () => {
    findText().then((tid) => {
      cy.request(`/word/bulk-translate?tid=${tid}&offset=0`).then((response) => {
        const html = String(response.body);
        expect(html).to.contain('bulk-translate-config');
        expect(html).to.contain('x-for="(term, i) in terms"');
        expect(html).to.contain('"textId":');
      });
    });
  });

  it('renders rows from the API with 1-based field names', () => {
    findText().then((tid) => {
      cy.request(
        `/api/v1/terms/unknown-for-translate?text_id=${tid}&offset=0&limit=5`
      ).then((api) => {
        const expected = api.body.terms as Array<{ word: string }>;
        if (expected.length === 0) {
          cy.log('no unknown words in this text; nothing to assert');
          return;
        }

        cy.visit(`/word/bulk-translate?tid=${tid}&offset=0`);
        cy.waitForAlpine();

        cy.get('table tbody tr', { timeout: 10000 }).should('have.length.at.least', 1);

        // First row must carry index 1, not 0 — the save path parses these.
        cy.get('table tbody tr').first().find('input[type=hidden]').first()
          .should('have.attr', 'name', 'term[1][text]');
        cy.get('table tbody tr').first().find('select')
          .should('have.attr', 'name', 'term[1][status]');
        cy.get('table tbody tr').first().find('input.markcheck')
          .should('have.attr', 'name', 'marked[1]');

        // And the rendered term must match what the API returned.
        cy.get('table tbody tr').first().find('.term')
          .should('have.text', expected[0].word);
      });
    });
  });

  it('prefills the translation cell with the lowercased term', () => {
    findText().then((tid) => {
      cy.request(
        `/api/v1/terms/unknown-for-translate?text_id=${tid}&offset=0&limit=1`
      ).then((api) => {
        const terms = api.body.terms as Array<{ word: string }>;
        if (terms.length === 0) {
          return;
        }
        cy.visit(`/word/bulk-translate?tid=${tid}&offset=0`);
        cy.waitForAlpine();
        cy.get('table tbody tr').first().find('td.trans')
          .should('have.text', terms[0].word.toLowerCase());
      });
    });
  });
});

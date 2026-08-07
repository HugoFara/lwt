/// <reference types="cypress" />

/**
 * /word/edit, /word/edit-term and /words/{id}/edit used to render a PHP form
 * that posted back and returned a confirmation page. They now mount the same
 * client-rendered editor the reading view opens in a modal.
 *
 * These specs run in a real browser because the thing most likely to break is
 * CSP evaluation of the Alpine bindings, which no unit test exercises.
 */
describe('Term editor page', () => {
  /**
   * Find any existing term so the specs do not depend on fixed IDs.
   *
   * @returns Chainable term ID
   */
  const anyTermId = () =>
    cy.request('/api/v1/terms/list?per_page=1').then((response) => {
      const words = (response.body as { words?: Array<{ id: number }> }).words ?? [];
      expect(words.length, 'demo data has at least one term').to.be.greaterThan(0);
      return words[0].id;
    });

  it('serves only identifiers, not a server-rendered form', () => {
    anyTermId().then((id) => {
      cy.request(`/words/${id}/edit`).then((response) => {
        const body = String(response.body);
        expect(body).to.contain('term-edit-page-config');
        // The retired forms carried these; nothing should emit them now.
        expect(body).to.not.contain('name="editword"');
        expect(body).to.not.contain('name="newword"');
      });
    });
  });

  it('mounts the editor from the API', () => {
    anyTermId().then((id) => {
      cy.visit(`/words/${id}/edit`);
      cy.waitForAlpine();

      // Rendered by the shared editor, not by PHP.
      cy.get('#term-edit-form', { timeout: 15000 }).should('exist');
      cy.get('#term-edit-translation').should('exist');
      cy.get('#term-edit-status').should('exist');
      cy.get('#term-edit-tags').should('exist');
    });
  });

  it('saves through the API and leaves the editor', () => {
    anyTermId().then((id) => {
      cy.visit(`/words/${id}/edit`);
      cy.waitForAlpine();

      cy.get('#term-edit-translation', { timeout: 15000 }).should('exist');
      cy.get('#term-edit-translation').invoke('val').then((original) => {
        cy.get('#term-edit-translation').clear().type('cypress-probe');
        cy.get('#term-edit-save').click();

        // Navigating away is how the page reports success.
        cy.url({ timeout: 15000 }).should('not.include', '/edit');

        // Restore, so the suite leaves the demo data as it found it.
        cy.apiRequest({
          method: 'PUT',
          url: `/api/v1/terms/${id}`,
          body: { translation: String(original ?? ''), status: 1 }
        });
      });
    });
  });

  it('rejects a term text that is not just a recasing', () => {
    anyTermId().then((id) => {
      cy.visit(`/words/${id}/edit`);
      cy.waitForAlpine();

      cy.get('#term-edit-text', { timeout: 15000 }).should('exist');
      cy.get('#term-edit-text').clear().type('definitely-not-the-same-term');
      cy.get('#term-edit-save').click();

      cy.get('#term-edit-error').should('be.visible');
      cy.url().should('include', '/edit');
    });
  });

  it('no longer accepts a form POST on the legacy edit route', () => {
    cy.apiRequest({
      method: 'POST',
      url: '/word/edit',
      form: true,
      body: { op: 'Save', WoText: 'zz', WoTextLC: 'zz' },
      failOnStatusCode: false
    }).its('status').should('eq', 404);
  });
});

describe('Bulk term save', () => {
  it('saves a batch through the API', () => {
    cy.request('/api/v1/languages').then((response) => {
      const langs = (response.body as { languages: Array<{ id: number }> }).languages;
      const lgId = langs[0].id;

      cy.apiRequest({
        method: 'POST',
        url: '/api/v1/terms/bulk',
        body: { terms: [{ text: 'zzcypressbulk', lg: lgId, status: 1, trans: 'probe' }] }
      }).then((saveResponse) => {
        expect(saveResponse.body).to.have.property('success', true);
        expect(saveResponse.body).to.have.property('saved', 1);
      });

      // Clean up the probe term.
      cy.request(`/api/v1/terms/list?language_id=${lgId}&per_page=500`).then((listResponse) => {
        const words = (listResponse.body as { words: Array<{ id: number; text: string }> }).words;
        const probe = words.find((w) => w.text === 'zzcypressbulk');
        expect(Boolean(probe), 'probe term was saved').to.eq(true);
        cy.apiRequest({
          method: 'DELETE',
          url: `/api/v1/terms/${probe!.id}`,
          failOnStatusCode: false
        });
      });
    });
  });

  it('rejects an empty batch as a handled failure', () => {
    cy.apiRequest({
      method: 'POST',
      url: '/api/v1/terms/bulk',
      body: { terms: [] },
      failOnStatusCode: false
    }).then((response) => {
      expect(response.body).to.have.property('error');
    });
  });
});

/// <reference types="cypress" />

/**
 * The language form renders from `GET /languages/{id}` and saves through
 * `PUT /languages/{id}` (or `POST /languages` when creating).
 *
 * The field set matters more than the markup here: `PUT` accepts 22 fields and
 * `GET` used to return 15, so loading a language and saving it unchanged would
 * silently reset its parser type, dictionary popups, translator language codes
 * and local-dictionary mode. These specs pin the round trip end to end.
 */
describe('Language form', () => {
  const firstLanguageId = (): Cypress.Chainable<number> => {
    return cy.request('/api/v1/languages').then((response) => {
      const languages = response.body.languages ?? [];
      expect(languages.length, 'a language must exist').to.be.greaterThan(0);
      return languages[0].id as number;
    });
  };

  it('ships a scaffold with no server-filled field values', () => {
    firstLanguageId().then((id) => {
      cy.request(`/languages/${id}/edit`).then((response) => {
        const html = String(response.body);
        expect(html).to.contain('language-form-config');
        expect(html).to.contain('x-data="languageEditor"');
        // Values arrive from the API, so the name field must ship empty.
        expect(html).to.contain('x-model="lang.name"');
        expect(html).to.not.match(/id="LgName"[^>]*value="[^"]+"/);
      });
    });
  });

  it('returns every field the update endpoint accepts', () => {
    firstLanguageId().then((id) => {
      cy.request(`/api/v1/languages/${id}`).then((response) => {
        const language = response.body.language;
        [
          'name', 'dict1Uri', 'dict2Uri', 'translatorUri', 'exportTemplate',
          'textSize', 'characterSubstitutions', 'regexpSplitSentences',
          'exceptionsSplitSentences', 'regexpWordCharacters', 'removeSpaces',
          'splitEachChar', 'rightToLeft', 'ttsVoiceApi', 'showRomanization',
          'parserType', 'sourceLang', 'targetLang', 'dict1PopUp', 'dict2PopUp',
          'translatorPopUp', 'localDictMode'
        ].forEach((field) => {
          expect(language, `GET must expose ${field}`).to.have.property(field);
        });
      });
    });
  });

  it('populates the form from the API', () => {
    firstLanguageId().then((id) => {
      cy.request(`/api/v1/languages/${id}`).then((api) => {
        const expected = api.body.language;

        cy.visit(`/languages/${id}/edit`);
        cy.waitForAlpine();

        cy.get('#LgName', { timeout: 10000 }).should('have.value', expected.name);
        cy.get('[name="LgRegexpWordCharacters"]')
          .should('have.value', expected.regexpWordCharacters);
      });
    });
  });

  it('saving an untouched language leaves every field alone', () => {
    // The regression this whole conversion risked: a field the reader omits
    // comes back as a default on the first save.
    firstLanguageId().then((id) => {
      cy.request(`/api/v1/languages/${id}`).then((before) => {
        cy.apiRequest({
          method: 'PUT',
          url: `/api/v1/languages/${id}`,
          body: before.body.language
        }).then((saved) => {
          expect(saved.body.success).to.eq(true);

          cy.request(`/api/v1/languages/${id}`).then((after) => {
            expect(after.body.language).to.deep.eq(before.body.language);
          });
        });
      });
    });
  });

  it('saves an edit made in the form itself', () => {
    firstLanguageId().then((id) => {
      cy.request(`/api/v1/languages/${id}`).then((before) => {
        const original = before.body.language;
        const edited = `${original.characterSubstitutions}|cy=cy`;

        cy.visit(`/languages/${id}/edit`);
        cy.waitForAlpine();
        cy.get('#LgName', { timeout: 10000 }).should('have.value', original.name);

        cy.get('[name="LgCharacterSubstitutions"]').clear().type(edited);
        cy.get('button[type="submit"]').first().click();

        // The component redirects to the list once the PUT resolves.
        cy.location('pathname', { timeout: 10000 }).should('eq', '/languages');

        cy.request(`/api/v1/languages/${id}`).then((after) => {
          expect(after.body.language.characterSubstitutions).to.eq(edited);
          // Everything else must be untouched by the save.
          expect(after.body.language.parserType).to.eq(original.parserType);
          expect(after.body.language.localDictMode).to.eq(original.localDictMode);
          expect(after.body.language.dict1PopUp).to.eq(original.dict1PopUp);

          // Put it back.
          cy.apiRequest({
            method: 'PUT',
            url: `/api/v1/languages/${id}`,
            body: original
          });
        });
      });
    });
  });

  it('accepts PUT rather than rejecting it as a bad method', () => {
    firstLanguageId().then((id) => {
      cy.apiRequest({
        method: 'PUT',
        url: `/api/v1/languages/${id}`,
        body: { name: '' },
        failOnStatusCode: false
      }).then((response) => {
        // 405 would mean the registry rejected PUT before the handler ran.
        expect(response.status).to.not.eq(405);
        expect(response.body.success).to.eq(false);
      });
    });
  });

  it('creates a language from just a name', () => {
    // POST /languages advertises name as the only required field; it used to
    // fail with a database error because LgDict1URI is NOT NULL.
    const name = `Cypress ${Date.now()}`;
    cy.apiRequest({
      method: 'POST',
      url: '/api/v1/languages',
      body: { name },
      failOnStatusCode: false
    }).then((response) => {
      expect(response.status).to.eq(200);
      expect(response.body.success, JSON.stringify(response.body)).to.eq(true);

      cy.apiRequest({
        method: 'DELETE',
        url: `/api/v1/languages/${response.body.id}`,
        failOnStatusCode: false
      });
    });
  });
});

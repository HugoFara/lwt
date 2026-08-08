/// <reference types="cypress" />

/**
 * The profile and preferences pages render from and save through
 * `/api/v1/profile` (issue #262).
 *
 * Every route acts on the signed-in user; none takes an account ID, so there
 * is no object-reference surface. What is worth checking is that the pages
 * ship no server-filled values and that a preferences round trip is lossless.
 */
describe('Profile and preferences', () => {
  it('ships a profile scaffold with no server-filled values', () => {
    cy.request('/profile').then((response) => {
      const html = String(response.body);
      // Single-user installs render a simplified panel instead; only assert
      // the scaffold when the editable form is the one being served.
      if (!html.includes('x-data="profileApp"')) {
        cy.log('single-user profile panel; nothing to assert');
        return;
      }
      expect(html).to.contain('x-model="username"');
      expect(html).to.not.match(/id="profile-username"[^>]*value="[^"]+"/);
    });
  });

  it('ships a preferences scaffold bound to the settings map', () => {
    cy.request('/profile/preferences').then((response) => {
      const html = String(response.body);
      expect(html).to.contain('x-data="preferencesApp"');
      expect(html).to.contain("settingValue('set-");
      expect(html).to.not.contain('action="/profile/preferences"');
    });
  });

  it('returns the preferences as a map', () => {
    cy.request('/api/v1/profile/preferences').then((response) => {
      expect(response.body).to.have.property('settings');
      expect(response.body.settings).to.have.property('set-texts-per-page');
    });
  });

  it('renders the preferences form from the API', () => {
    cy.request('/api/v1/profile/preferences').then((api) => {
      const expected = api.body.settings['set-texts-per-page'];

      cy.visit('/profile/preferences');
      cy.waitForAlpine();
      cy.get('[name="set-texts-per-page"]', { timeout: 10000 })
        .should('have.value', String(expected));
    });
  });

  it('round-trips a preference without losing the others', () => {
    cy.request('/api/v1/profile/preferences').then((before) => {
      const settings = { ...before.body.settings };
      const original = settings['set-texts-per-page'];
      settings['set-texts-per-page'] = '17';

      cy.apiRequest({
        method: 'PUT',
        url: '/api/v1/profile/preferences',
        body: { settings }
      }).then((saved) => {
        expect(saved.body.success).to.eq(true);

        cy.request('/api/v1/profile/preferences').then((after) => {
          expect(after.body.settings['set-texts-per-page']).to.eq('17');
          // Everything else must survive the write.
          Object.keys(before.body.settings).forEach((key) => {
            if (key !== 'set-texts-per-page') {
              expect(after.body.settings[key], key).to.eq(before.body.settings[key]);
            }
          });

          // Put it back.
          settings['set-texts-per-page'] = original;
          cy.apiRequest({
            method: 'PUT',
            url: '/api/v1/profile/preferences',
            body: { settings }
          });
        });
      });
    });
  });

  it('saves a preference through the form itself', () => {
    cy.request('/api/v1/profile/preferences').then((before) => {
      const original = before.body.settings['set-texts-per-page'];

      cy.visit('/profile/preferences');
      cy.waitForAlpine();
      // The sections are collapsed accordions; open them all before typing.
      cy.get('.card-header.is-clickable').click({ multiple: true });
      cy.get('[name="set-texts-per-page"]', { timeout: 10000 })
        .should('be.visible')
        .clear()
        .type('23');
      cy.get('button[type=submit]').first().click();

      cy.request('/api/v1/profile/preferences').should((after) => {
        expect(after.body.settings['set-texts-per-page']).to.eq('23');
      });

      cy.then(() => {
        const settings = { ...before.body.settings };
        settings['set-texts-per-page'] = original;
        cy.apiRequest({
          method: 'PUT',
          url: '/api/v1/profile/preferences',
          body: { settings }
        });
      });
    });
  });

  it('ignores keys that are not user-scoped settings', () => {
    // The endpoint writes only what SettingDefinitions declares as user
    // scope, so an unexpected key is dropped rather than stored.
    cy.apiRequest({
      method: 'PUT',
      url: '/api/v1/profile/preferences',
      body: { settings: { 'not-a-real-setting': 'x' } }
    }).then((response) => {
      expect(response.body.success).to.eq(true);

      cy.request('/api/v1/profile/preferences').then((after) => {
        expect(after.body.settings).to.not.have.property('not-a-real-setting');
      });
    });
  });

  it('rejects an unknown sub-path', () => {
    cy.request({ url: '/api/v1/profile/nonsense', failOnStatusCode: false })
      .then((response) => {
        expect(response.status).to.eq(404);
      });
  });
});

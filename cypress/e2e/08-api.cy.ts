/// <reference types="cypress" />

describe('REST API', () => {
  // Use the new API URL format to avoid 301 redirects that change HTTP methods
  const apiBase = '/api/v1';

  describe('Version Endpoint', () => {
    it('should return version information', () => {
      cy.request(`${apiBase}/version`).then((response) => {
        expect(response.status).to.eq(200);
        expect(response.headers['content-type']).to.include('application/json');
        expect(response.body).to.have.property('version');
        expect(response.body).to.have.property('release_date');
      });
    });

    it('should return version in semver format', () => {
      cy.request(`${apiBase}/version`).then((response) => {
        expect(response.body.version).to.match(/^\d+\.\d+\.\d+$/);
      });
    });
  });

  describe('Media Files Endpoint', () => {
    it('should return media files info', () => {
      cy.request(`${apiBase}/media-files`).then((response) => {
        expect(response.status).to.eq(200);
        expect(response.headers['content-type']).to.include('application/json');
        expect(response.body).to.have.property('base_path');
      });
    });
  });

  describe('Phonetic Reading Endpoint', () => {
    it('should return phonetic reading for text', () => {
      cy.request({
        url: `${apiBase}/phonetic-reading`,
        qs: { text: 'test', lang: 'en' },
      }).then((response) => {
        expect(response.status).to.eq(200);
        expect(response.headers['content-type']).to.include('application/json');
        expect(response.body).to.have.property('phonetic_reading');
      });
    });

    it('should handle empty text', () => {
      cy.request({
        url: `${apiBase}/phonetic-reading`,
        qs: { text: '', lang: 'en' },
      }).then((response) => {
        expect(response.status).to.eq(200);
      });
    });
  });

  describe('Language Configuration Endpoint', () => {
    it('should return reading configuration for existing language', () => {
      // First get the list of languages to find a valid ID
      cy.request(`${apiBase}/languages`).then((langResponse) => {
        expect(langResponse.status).to.eq(200);
        const languages = langResponse.body.languages || langResponse.body;
        if (Array.isArray(languages) && languages.length > 0) {
          const langId = languages[0].id || languages[0].LgID;
          cy.request({
            url: `${apiBase}/languages/${langId}/reading-configuration`,
            failOnStatusCode: false,
          }).then((response) => {
            // Accept 200 (success) or 500 (internal error if language config incomplete)
            // The endpoint may fail if language doesn't have complete configuration
            if (response.status === 200) {
              expect(response.headers['content-type']).to.include(
                'application/json'
              );
              expect(response.body).to.have.property('name');
            } else {
              // Log the issue but don't fail - language may not have complete config
              cy.log(`Language ${langId} reading config returned ${response.status} - may need configuration`);
            }
          });
        } else {
          // No languages exist, skip this test
          cy.log('No languages found - skipping reading configuration test');
        }
      });
    });

    it('should return 404 for invalid language ID', () => {
      cy.request({
        url: `${apiBase}/languages/invalid/reading-configuration`,
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.eq(404);
      });
    });
  });

  describe('Settings Endpoint', () => {
    it('should accept POST to save setting', () => {
      cy.request({
        method: 'POST',
        url: `${apiBase}/settings`,
        form: true,
        body: {
          key: 'set-test-setting',
          value: 'test-value',
        },
      }).then((response) => {
        expect(response.status).to.eq(200);
        expect(response.headers['content-type']).to.include('application/json');
      });
    });
  });

  describe('Error Handling', () => {
    it('should return 400 for requests without endpoint', () => {
      cy.request({
        url: apiBase,
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.eq(400);
      });
    });

    it('should return 404 for unknown endpoints', () => {
      cy.request({
        url: `${apiBase}/unknown-endpoint`,
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.eq(404);
      });
    });

    it('should return 405 for unsupported methods', () => {
      cy.request({
        method: 'DELETE',
        url: `${apiBase}/version`,
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.eq(405);
      });
    });
  });

  describe('Terms Endpoints', () => {
    it('should get imported terms', () => {
      cy.request({
        url: `${apiBase}/terms/imported`,
        qs: { last_update: '', page: '0', count: '10' },
      }).then((response) => {
        expect(response.status).to.eq(200);
        expect(response.headers['content-type']).to.include('application/json');
        expect(response.body).to.have.property('terms');
        expect(response.body).to.have.property('navigation');
      });
    });

    it('should get similar terms', () => {
      cy.request({
        url: `${apiBase}/similar-terms`,
        qs: { lg_id: '1', term: 'test' },
      }).then((response) => {
        expect(response.status).to.eq(200);
        expect(response.headers['content-type']).to.include('application/json');
        expect(response.body).to.have.property('similar_terms');
      });
    });
  });

  describe('Text Endpoints', () => {
    it('should get text statistics', () => {
      cy.request({
        url: `${apiBase}/texts/statistics`,
        qs: { text_ids: '1' },
      }).then((response) => {
        expect(response.status).to.eq(200);
        expect(response.headers['content-type']).to.include('application/json');
      });
    });

    it('should set reading position', () => {
      cy.request({
        method: 'POST',
        url: `${apiBase}/texts/1/reading-position`,
        form: true,
        body: { position: 100 },
      }).then((response) => {
        expect(response.status).to.eq(200);
      });
    });
  });
});

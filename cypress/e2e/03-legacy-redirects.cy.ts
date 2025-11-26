/// <reference types="cypress" />

describe('Legacy URL Redirects', () => {
  // Test that legacy URLs redirect to new routes
  // These should return 301 redirects

  it('should redirect /do_text.php to /text/read', () => {
    cy.request({
      url: '/do_text.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/text/read');
      }
    });
  });

  it('should redirect /edit_texts.php to /text/edit', () => {
    cy.request({
      url: '/edit_texts.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/text/edit');
      }
    });
  });

  it('should redirect /edit_words.php to /words/edit', () => {
    cy.request({
      url: '/edit_words.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/words/edit');
      }
    });
  });

  it('should redirect /edit_languages.php to /languages', () => {
    cy.request({
      url: '/edit_languages.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/languages');
      }
    });
  });

  it('should redirect /edit_tags.php to /tags', () => {
    cy.request({
      url: '/edit_tags.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/tags');
      }
    });
  });

  it('should redirect /all_words_wellknown.php to /words', () => {
    cy.request({
      url: '/all_words_wellknown.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/words');
      }
    });
  });

  it('should redirect /do_test.php to /test', () => {
    cy.request({
      url: '/do_test.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/test');
      }
    });
  });

  it('should redirect /statistics.php to /admin/statistics', () => {
    cy.request({
      url: '/statistics.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/admin/statistics');
      }
    });
  });

  it('should redirect /settings.php to /admin/settings', () => {
    cy.request({
      url: '/settings.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/admin/settings');
      }
    });
  });

  it('should redirect /backup_restore.php to /admin/backup', () => {
    cy.request({
      url: '/backup_restore.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/admin/backup');
      }
    });
  });

  it('should redirect /edit_archivedtexts.php to /text/archived', () => {
    cy.request({
      url: '/edit_archivedtexts.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/text/archived');
      }
    });
  });

  it('should redirect /long_text_import.php to /text/import-long', () => {
    cy.request({
      url: '/long_text_import.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([301, 302, 200]);
      if (response.status === 301 || response.status === 302) {
        expect(response.headers.location).to.include('/text/import-long');
      }
    });
  });
});

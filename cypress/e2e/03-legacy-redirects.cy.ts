/// <reference types="cypress" />

describe('Legacy URL Redirects', () => {
  // Legacy .php URLs were removed in v3 (commit 48825c07)
  // These tests now verify that legacy URLs return 404
  // to confirm the cleanup was successful

  it('should return 404 for removed /do_text.php', () => {
    cy.request({
      url: '/do_text.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('should return 404 for removed /edit_texts.php', () => {
    cy.request({
      url: '/edit_texts.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('should return 404 for removed /edit_words.php', () => {
    cy.request({
      url: '/edit_words.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('should return 404 for removed /edit_languages.php', () => {
    cy.request({
      url: '/edit_languages.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('should return 404 for removed /edit_tags.php', () => {
    cy.request({
      url: '/edit_tags.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('should return 404 for removed /all_words_wellknown.php', () => {
    cy.request({
      url: '/all_words_wellknown.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('should return 404 for removed /do_test.php', () => {
    cy.request({
      url: '/do_test.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('should return 404 for removed /statistics.php', () => {
    cy.request({
      url: '/statistics.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('should return 404 for removed /settings.php', () => {
    cy.request({
      url: '/settings.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('should return 404 for removed /backup_restore.php', () => {
    cy.request({
      url: '/backup_restore.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('should return 404 for removed /edit_archivedtexts.php', () => {
    cy.request({
      url: '/edit_archivedtexts.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('should return 404 for removed /long_text_import.php', () => {
    cy.request({
      url: '/long_text_import.php',
      followRedirect: false,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });
});

/// <reference types="cypress" />

/**
 * The server-rendered feed pages are retired.
 *
 * `/feeds` (browse.php), `/feeds/edit` and `/feeds/multi-load` all redirect to
 * the manager SPA, which renders everything from `/api/v1`. The edit-before-
 * import flow that used to keep browse.php alive now runs on two endpoints:
 * `POST /feeds/articles/extract` reads, `POST /feeds/articles/create-texts`
 * writes.
 */
describe('Feeds', () => {
  describe('retired pages', () => {
    const retired = ['/feeds', '/feeds/edit', '/feeds/multi-load'];

    retired.forEach((path) => {
      it(`${path} lands on the manager`, () => {
        cy.visit(path);
        cy.location('pathname').should('eq', '/feeds/manage');
      });
    });

    it('no longer ships a server-rendered article table', () => {
      cy.request('/feeds/manage').then((response) => {
        const html = String(response.body);
        // The manager is a scaffold: rows arrive from the API, so the only
        // <tr> in the markup are inside x-for templates and headers.
        expect(html).to.contain('x-for="article in articles"');
        expect(html).to.contain('feed-manager-app');
      });
    });

    it('keeps the auto-update loader reachable on its own route', () => {
      // The language page links here; it used to be /feeds?check_autoupdate=1.
      cy.request('/feeds/autoupdate').then((response) => {
        expect(response.status).to.eq(200);
        expect(String(response.body)).to.contain('feed-loader-config');
      });
    });
  });

  describe('article endpoints', () => {
    it('rejects extract with no articles selected', () => {
      cy.apiRequest({
        method: 'POST',
        url: '/api/v1/feeds/articles/extract',
        body: { article_ids: [] },
        failOnStatusCode: false
      }).then((response) => {
        expect(response.body.success).to.eq(false);
        expect(response.body.errors).to.include('No articles selected');
      });
    });

    it('refuses to create texts without a feed', () => {
      cy.apiRequest({
        method: 'POST',
        url: '/api/v1/feeds/articles/create-texts',
        body: { texts: [{ title: 'T', text: 'Body' }] },
        failOnStatusCode: false
      }).then((response) => {
        expect(response.body.success).to.eq(false);
        expect(response.body.created).to.eq(0);
      });
    });

    it('refuses to create texts for a feed the caller does not own', () => {
      // The ownership gate is what stops feed_links — which has no owner
      // column — from being written through by ID guessing.
      cy.apiRequest({
        method: 'POST',
        url: '/api/v1/feeds/articles/create-texts',
        body: {
          feed_id: 999999,
          texts: [{ title: 'T', text: 'Body' }]
        },
        failOnStatusCode: false
      }).then((response) => {
        expect(response.body.success).to.eq(false);
        expect(response.body.error).to.eq('Feed not found');
        expect(response.body.created).to.eq(0);
      });
    });

    it('is registered for POST, not only reachable in theory', () => {
      // A 405 here would mean Endpoints::ROUTES rejected the method before
      // the handler ever ran — the failure mode the books endpoints had.
      cy.apiRequest({
        method: 'POST',
        url: '/api/v1/feeds/articles/create-texts',
        body: {},
        failOnStatusCode: false
      }).then((response) => {
        expect(response.status).to.not.eq(405);
      });
    });
  });

  /**
   * The manual "add a feed" form and the edit form save through /api/v1 rather
   * than posting themselves (#262).
   */
  describe('feed forms', () => {
    it('creates a feed from the manual tab', () => {
      const name = `Manual Feed ${Date.now()}`;
      cy.intercept('POST', '**/api/v1/feeds').as('createFeed');

      cy.visit('/feeds/new');
      cy.contains('a, button, .is-clickable', /manual/i).click();

      cy.get('input[name="NfName"]').should('be.visible').type(name);
      cy.get('input[name="NfSourceURI"]').type('https://example.com/manual.xml');
      cy.get('input[name="NfArticleSectionTags"]').type('//div');
      cy.get('form').filter(':visible').contains('button[type="submit"]', /save/i).click();

      cy.wait('@createFeed').its('response.statusCode').should('eq', 200);
      cy.location('pathname').should('match', /\/feeds\/\d+\/edit/);
      cy.get('input[name="NfName"]').should('have.value', name);
    });

    it('saves an edit through the API', () => {
      const name = `Edit Feed ${Date.now()}`;
      const renamed = `${name} (renamed)`;

      cy.apiRequest({
        method: 'POST',
        url: '/api/v1/feeds',
        body: {
          langId: 1,
          name,
          sourceUri: 'https://example.com/edit.xml',
          articleSectionTags: '//div',
          filterTags: '',
          options: 'edit_text=1'
        }
      }).then((response) => {
        const feedId = response.body.feed.id;
        cy.intercept('PUT', `**/api/v1/feeds/${feedId}`).as('updateFeed');

        cy.visit(`/feeds/${feedId}/edit`);
        cy.get('input[name="NfName"]').should('have.value', name).clear();
        cy.get('input[name="NfName"]').type(renamed);
        cy.contains('button[type="submit"]', /update|save/i).click();

        // A form POST would never produce this request — and /feeds/{id}/edit
        // no longer accepts one.
        cy.wait('@updateFeed').its('response.statusCode').should('eq', 200);
        cy.location('pathname').should('eq', '/feeds/manage');

        cy.visit(`/feeds/${feedId}/edit`);
        cy.get('input[name="NfName"]').should('have.value', renamed);
      });
    });

    it('no longer accepts a form POST on the page routes', () => {
      cy.request({ method: 'POST', url: '/feeds/new', failOnStatusCode: false })
        .its('status')
        .should('eq', 404);
    });

    /**
     * The wizard's last step used to post to /feeds/edit. That route has
     * redirected to the manager since the server-rendered feeds list was
     * retired, so finishing the wizard discarded the feed. Walking the whole
     * wizard needs a live RSS URL, so what is asserted here is that the form
     * no longer targets the route that swallowed it.
     */
    it('does not point the wizard finish at the retired route', () => {
      cy.request('/feeds/wizard?step=4').then((response) => {
        const html = String(response.body);
        expect(html).to.contain('x-data="feedWizardStep4"');
        expect(html).to.not.contain('action="/feeds/edit"');
        expect(html).to.not.contain('name="save_feed"');
      });
    });
  });
});

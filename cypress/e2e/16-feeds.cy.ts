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
});

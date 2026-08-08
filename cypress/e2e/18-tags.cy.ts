/// <reference types="cypress" />

/**
 * The tag lists render from `GET /tags/{type}/list` and mutate through the
 * matching POST/PUT/DELETE routes.
 *
 * The flat name lists at `GET /tags/term` and `GET /tags/text` are a separate
 * route and must keep their old shape — Tagify and the term editor read them,
 * and would break silently if the list endpoint's envelope leaked into them.
 */
describe('Tags', () => {
  const cleanup: Array<{ type: string; id: number }> = [];

  /** Tag text is capped at 20 characters, so keep fixture names short. */
  const stamp = (): string => String(Date.now() % 1000000);

  afterEach(() => {
    while (cleanup.length > 0) {
      const tag = cleanup.pop();
      if (tag) {
        cy.apiRequest({
          method: 'DELETE',
          url: `/api/v1/tags/${tag.type}/${tag.id}`,
          failOnStatusCode: false
        });
      }
    }
  });

  const makeTag = (type: string, text: string): Cypress.Chainable<number> => {
    return cy.apiRequest({
      method: 'POST',
      url: `/api/v1/tags/${type}`,
      body: { text, comment: 'cypress' }
    }).then((response) => {
      expect(response.body.success, JSON.stringify(response.body)).to.eq(true);
      cleanup.push({ type, id: response.body.id });
      return response.body.id as number;
    });
  };

  ['term', 'text'].forEach((type) => {
    const page = type === 'term' ? '/tags' : '/tags/text';

    describe(`${type} tags`, () => {
      it('ships a scaffold, not a rendered table', () => {
        cy.request(page).then((response) => {
          const html = String(response.body);
          expect(html).to.contain('tag-list-config');
          expect(html).to.contain('x-data="tagListApp"');
          expect(html).to.contain('x-for="tag in tags"');
        });
      });

      it('renders rows from the API', () => {
        const text = `cy${stamp()}${type}`;
        makeTag(type, text).then(() => {
          cy.visit(page);
          cy.waitForAlpine();
          cy.get('table tbody tr', { timeout: 10000 }).should('have.length.at.least', 1);
          cy.contains('table tbody tr', text).should('exist');
        });
      });

      it('round-trips a tag through the API', () => {
        const text = `cy${stamp()}${type}r`;
        makeTag(type, text).then((id) => {
          cy.apiRequest({
            method: 'PUT',
            url: `/api/v1/tags/${type}/${id}`,
            body: { text: `${text}b`, comment: 'edited' }
          }).then((updated) => {
            expect(updated.body.success).to.eq(true);

            cy.request(`/api/v1/tags/${type}/${id}`).then((read) => {
              expect(read.body.tag.text).to.eq(`${text}b`);
              expect(read.body.tag.comment).to.eq('edited');
            });
          });
        });
      });
    });
  });

  it('keeps the flat name list separate from the paginated one', () => {
    // Tagify reads this one; an envelope here would break every tag input.
    cy.request('/api/v1/tags/term').then((flat) => {
      expect(flat.body).to.not.have.property('pagination');
    });
    cy.request('/api/v1/tags/term/list').then((paged) => {
      expect(paged.body).to.have.property('pagination');
      expect(paged.body).to.have.property('sortOptions');
      expect(paged.body.type).to.eq('term');
    });
  });

  it('rejects an unknown tag type instead of guessing', () => {
    cy.apiRequest({
      method: 'DELETE',
      url: '/api/v1/tags/nonsense/1',
      failOnStatusCode: false
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('deletes only the selected tags', () => {
    const run = stamp();
    makeTag('term', `cykeep${run}`).then((keepId) => {
      makeTag('term', `cydrop${run}`).then((dropId) => {
        cy.apiRequest({
          method: 'DELETE',
          url: '/api/v1/tags/term',
          body: { ids: [dropId] }
        }).then((response) => {
          expect(response.body.success).to.eq(true);
          expect(response.body.deleted).to.eq(1);

          cy.request({
            url: `/api/v1/tags/term/${keepId}`,
            failOnStatusCode: false
          }).then((kept) => {
            expect(kept.body.tag.id).to.eq(keepId);
          });
        });
      });
    });
  });

  it('delete-all honours the active filter', () => {
    // A filtered "delete all" that ignored the filter would wipe the table.
    const run = stamp();
    makeTag('term', `cyfilt${run}`).then(() => {
      makeTag('term', `cysurv${run}`).then((survivorId) => {
        cy.apiRequest({
          method: 'DELETE',
          url: '/api/v1/tags/term',
          body: { all: true, query: `*cyfilt${run}*` }
        }).then((response) => {
          expect(response.body.success).to.eq(true);

          cy.request(`/api/v1/tags/term/${survivorId}`).then((kept) => {
            expect(kept.body.tag.id, 'a tag outside the filter must survive').to.eq(survivorId);
          });
        });
      });
    });
  });

  it('searches by substring, not exact match', () => {
    const text = `cyfind${stamp()}`;
    makeTag('term', text).then(() => {
      cy.visit('/tags');
      cy.waitForAlpine();
      cy.get('input[type=text]', { timeout: 10000 }).first().type('cyfind');
      cy.contains('button', 'Search').click();
      cy.contains('table tbody tr', text, { timeout: 10000 }).should('exist');
    });
  });
});

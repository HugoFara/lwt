/// <reference types="cypress" />

/**
 * The admin user list and form render from `/api/v1/admin/users`.
 *
 * These routes create accounts and grant the admin role, so the handler
 * enforces the admin role itself — `ApiV1` only checks that a caller is
 * authenticated. In single-user mode there are no roles and the check is
 * skipped, which mirrors `AdminMiddleware` on the pages these replaced.
 */
describe('Admin users', () => {
  it('ships a scaffold, not a rendered table', () => {
    cy.request('/admin/users').then((response) => {
      const html = String(response.body);
      expect(html).to.contain('user-list-config');
      expect(html).to.contain('x-data="userManagement"');
      expect(html).to.contain('x-for="user in users"');
    });
  });

  it('ships a form scaffold with no server-filled values', () => {
    cy.request('/admin/users/new').then((response) => {
      const html = String(response.body);
      expect(html).to.contain('user-form-config');
      expect(html).to.contain('x-data="userForm"');
      expect(html).to.contain('x-model="form.username"');
      expect(html).to.not.match(/id="username"[^>]*value="[^"]+"/);
    });
  });

  it('returns users with the paging envelope and statistics', () => {
    cy.request('/api/v1/admin/users').then((response) => {
      expect(response.body).to.have.property('users');
      expect(response.body).to.have.property('pagination');
      expect(response.body).to.have.property('statistics');
      expect(response.body).to.have.property('currentAdminId');
    });
  });

  it('never returns a password hash', () => {
    // The entity carries one; formatUser() must not pass it through.
    cy.request('/api/v1/admin/users').then((response) => {
      const users = response.body.users as Array<Record<string, unknown>>;
      users.forEach((user) => {
        expect(user).to.not.have.property('password');
        expect(user).to.not.have.property('passwordHash');
        expect(user).to.not.have.property('emailVerificationToken');
      });
    });
  });

  it('renders rows from the API', () => {
    cy.request('/api/v1/admin/users').then((api) => {
      const users = api.body.users as Array<{ username: string }>;
      if (users.length === 0) {
        cy.log('no users to render');
        return;
      }

      cy.visit('/admin/users');
      cy.waitForAlpine();
      cy.get('table tbody tr', { timeout: 10000 }).should('have.length.at.least', 1);
      cy.contains('table tbody tr', users[0].username).should('exist');
    });
  });

  it('rejects a path under /admin that is not users', () => {
    cy.request({
      url: '/api/v1/admin/something-else',
      failOnStatusCode: false
    }).then((response) => {
      expect(response.status).to.eq(404);
    });
  });

  it('registers every method the handler serves', () => {
    // A 405 would mean Endpoints::ROUTES rejected the request before the
    // handler — and therefore before the admin check — ever ran.
    const calls: Array<{ method: string; url: string }> = [
      { method: 'POST', url: '/api/v1/admin/users' },
      { method: 'PUT', url: '/api/v1/admin/users/999999' },
      { method: 'PUT', url: '/api/v1/admin/users/999999/role' },
      { method: 'PUT', url: '/api/v1/admin/users/999999/status' },
      { method: 'DELETE', url: '/api/v1/admin/users/999999' }
    ];

    calls.forEach(({ method, url }) => {
      cy.apiRequest({ method, url, body: {}, failOnStatusCode: false }).then((response) => {
        expect(response.status, `${method} ${url}`).to.not.eq(405);
      });
    });
  });

  it('round-trips a user through the API', () => {
    const username = `cyuser${Date.now() % 1000000}`;

    cy.apiRequest({
      method: 'POST',
      url: '/api/v1/admin/users',
      body: {
        username,
        email: `${username}@example.test`,
        password: 'cypress-password-1',
        role: 'user',
        is_active: true
      },
      failOnStatusCode: false
    }).then((created) => {
      expect(created.body.success, JSON.stringify(created.body)).to.eq(true);
      const id = created.body.id as number;

      cy.request(`/api/v1/admin/users/${id}`).then((read) => {
        expect(read.body.user.username).to.eq(username);
        expect(read.body.user.role).to.eq('user');
        expect(read.body.user.isActive).to.eq(true);
      });

      cy.apiRequest({
        method: 'PUT',
        url: `/api/v1/admin/users/${id}/role`,
        body: { role: 'admin' }
      }).then((promoted) => {
        expect(promoted.body.success).to.eq(true);

        cy.request(`/api/v1/admin/users/${id}`).then((reread) => {
          expect(reread.body.user.isAdmin).to.eq(true);
        });
      });

      cy.apiRequest({
        method: 'DELETE',
        url: `/api/v1/admin/users/${id}`
      }).then((deleted) => {
        expect(deleted.body.success).to.eq(true);
      });
    });
  });

  it('creates a user through the form itself', () => {
    const username = `cyform${Date.now() % 1000000}`;

    cy.visit('/admin/users/new');
    cy.waitForAlpine();

    cy.get('#username').type(username);
    cy.get('#email').type(`${username}@example.test`);
    cy.get('#password').type('cypress-password-1');
    cy.get('button[type=submit]').click();

    cy.location('pathname', { timeout: 10000 }).should('eq', '/admin/users');

    cy.request('/api/v1/admin/users?search=' + username).then((response) => {
      const users = response.body.users as Array<{ id: number; username: string }>;
      const made = users.find((u) => u.username === username);
      expect(made, 'the form should have created the user').to.not.eq(undefined);

      if (made) {
        cy.apiRequest({
          method: 'DELETE',
          url: `/api/v1/admin/users/${made.id}`,
          failOnStatusCode: false
        });
      }
    });
  });
});

/// <reference types="cypress" />

/**
 * Screenshot generation for documentation
 *
 * Run with: npx cypress run --spec cypress/e2e/99-screenshots.cy.ts
 *
 * Screenshots are saved to cypress/screenshots/99-screenshots.cy.ts/
 * After running, copy the screenshots to docs-src/public/assets/images/
 * (the docs/ folder is auto-generated and will be overwritten)
 *
 * To copy all screenshots:
 * cp cypress/screenshots/99-screenshots.cy.ts/*.png docs-src/public/assets/images/
 */

describe('Documentation Screenshots', () => {
  // Ensure demo data is installed before running these tests
  // Run 01-setup.cy.ts first if needed

  describe('Home Screen', () => {
    it('home-screen - Main home page with data', () => {
      cy.visit('/');
      cy.wait(500);
      cy.get('body').should('be.visible');
      cy.screenshot('home-screen', { capture: 'viewport' });
    });
  });

  describe('Languages', () => {
    it('languages-list - List of all languages', () => {
      cy.visit('/languages');
      cy.wait(1000);
      // Wait for language cards to load
      cy.get('.card, [class*="language"]', { timeout: 10000 }).should('exist');
      cy.screenshot('languages-list', { capture: 'viewport' });
    });
  });

  describe('Texts', () => {
    it('texts-list - List of all texts', () => {
      cy.visit('/texts');
      // Wait for text cards to appear (French section auto-expands)
      cy.get('.text-card, [class*="text-item"], a[href*="/text/read"]', { timeout: 30000 }).should('exist');
      // Wait for loading to complete
      cy.wait(3000);
      cy.screenshot('texts-list', { capture: 'viewport' });
    });

    it('adding-text - Text creation form', () => {
      cy.visit('/text/edit?new=1');
      cy.wait(500);

      // Wait for form to load
      cy.get('form').should('exist');
      cy.get('input[name="TxTitle"]').should('be.visible');

      // Fill in some example data for a nicer screenshot
      cy.get('input[name="TxTitle"]').type('Le Petit Prince - Chapitre 1');

      // Select French if available, otherwise first language
      cy.get('select[name="TxLgID"]').then(($select) => {
        const options = $select.find('option');
        let langValue = options.eq(1).val();
        options.each((i, opt) => {
          if (opt.textContent?.toLowerCase().includes('french') ||
              opt.textContent?.toLowerCase().includes('français')) {
            langValue = opt.value;
          }
        });
        cy.get('select[name="TxLgID"]').select(String(langValue));
      });

      // Add sample French text
      cy.get('textarea[name="TxText"]').type(
        `Lorsque j'avais six ans j'ai vu, une fois, une magnifique image, dans un livre sur la Forêt Vierge qui s'appelait "Histoires Vécues". Ça représentait un serpent boa qui avalait un fauve.

On disait dans le livre: "Les serpents boas avalent leur proie tout entière, sans la mâcher. Ensuite ils ne peuvent plus bouger et ils dorment pendant les six mois de leur digestion".`
      );

      cy.wait(300);
      cy.screenshot('adding-text', { capture: 'viewport' });
    });
  });

  describe('Text Tags', () => {
    it('text-tags-list - List of text tags', () => {
      cy.visit('/tags/text');
      cy.wait(1000);
      // Wait for content to load
      cy.get('body').should('be.visible');
      cy.screenshot('text-tags-list', { capture: 'viewport' });
    });
  });

  describe('Reading', () => {
    it('reading-text - Reading interface', () => {
      // Navigate to texts list and click first available text
      cy.visit('/text/edit');
      // Wait for texts to load
      cy.get('a[href*="/text/read"], a[href*="text/read"]', { timeout: 15000 }).should('exist');
      cy.wait(500);

      // Click the first read link
      cy.get('a[href*="/text/read"], a[href*="text/read"]').first().click();
      cy.url().should('include', '/read');

      // Wait for the reading interface to fully load
      cy.get('#thetext', { timeout: 10000 }).should('exist');
      cy.get('#thetext .wsty', { timeout: 10000 }).should('have.length.at.least', 1);

      // Wait for Alpine.js components to initialize
      cy.wait(1000);

      cy.screenshot('reading-text', { capture: 'viewport' });
    });

    it('reading-text-show-all - Reading with Show All enabled', () => {
      cy.visit('/text/edit');
      cy.get('a[href*="/text/read"], a[href*="text/read"]', { timeout: 15000 }).should('exist');
      cy.wait(500);
      cy.get('a[href*="/text/read"], a[href*="text/read"]').first().click();
      cy.url().should('include', '/read');
      cy.get('#thetext', { timeout: 10000 }).should('exist');
      cy.wait(1000);

      // Click Show All button if it exists
      cy.get('body').then(($body) => {
        if ($body.find('button:contains("Show All")').length > 0) {
          cy.contains('button', 'Show All').click();
          cy.wait(500);
        }
      });

      cy.screenshot('reading-text-show-all', { capture: 'viewport' });
    });
  });

  describe('Review', () => {
    it('reviewing-word - Review interface (L2 -> L1)', () => {
      cy.visit('/review?lang=1');
      cy.wait(500);

      // Check if review settings form loaded or if we need to start review
      cy.get('body').then(($body) => {
        if ($body.find('form').length > 0 && $body.find('input[type="submit"]').length > 0) {
          cy.get('input[type="submit"], button[type="submit"]').first().click();
          cy.wait(500);
        }
      });

      cy.wait(500);
      cy.screenshot('reviewing-word', { capture: 'viewport' });
    });

    it('review-settings - Review settings page', () => {
      cy.visit('/review?lang=1');
      cy.wait(500);
      cy.screenshot('review-settings', { capture: 'viewport' });
    });
  });

  describe('Terms', () => {
    it('terms-list - List of terms/vocabulary', () => {
      cy.visit('/words');
      // Wait for table to appear (indicates loading is complete)
      cy.get('table', { timeout: 30000 }).should('exist');
      // Extra wait to ensure all content is rendered
      cy.wait(1500);
      cy.screenshot('terms-list', { capture: 'viewport' });
    });
  });

  describe('Term Tags', () => {
    it('term-tags-list - List of term tags', () => {
      cy.visit('/tags');
      cy.wait(1000);
      cy.get('body').should('be.visible');
      cy.screenshot('term-tags-list', { capture: 'viewport' });
    });
  });

  describe('Feeds', () => {
    it('feed-list - List of RSS feeds', () => {
      // Use the feeds browse page
      cy.visit('/feeds?lang=1');
      cy.wait(2000);
      cy.get('body').should('be.visible');
      cy.screenshot('feed-list', { capture: 'viewport' });
    });

    it('feed-manage - Manage feeds page', () => {
      cy.visit('/feeds?lang=1');
      cy.wait(2000);
      cy.get('body').should('be.visible');
      cy.screenshot('feed-manage', { capture: 'viewport' });
    });

    it('feed-edit - Feed edit form', () => {
      cy.visit('/feeds/edit?new_feed=1');
      cy.wait(1000);
      cy.get('body').should('be.visible');
      cy.screenshot('feed-edit', { capture: 'viewport' });
    });
  });

  describe('Statistics', () => {
    it('statistics - Statistics page', () => {
      cy.visit('/admin/statistics');
      // Wait for page to fully load
      cy.wait(2000);
      cy.get('body').should('be.visible');
      cy.screenshot('statistics', { capture: 'viewport' });
    });
  });

  describe('Text Archive', () => {
    it('text-archive - Archived texts list', () => {
      cy.visit('/text/archived');
      cy.wait(1000);
      cy.get('body').should('be.visible');
      cy.screenshot('text-archive', { capture: 'viewport' });
    });
  });

  describe('Import/Export', () => {
    it('import-terms - Import terms page', () => {
      cy.visit('/word/upload');
      cy.wait(1000);
      cy.get('form').should('exist');
      cy.screenshot('import-terms', { capture: 'viewport' });
    });
  });

  describe('Database', () => {
    it('database-management - Database backup/restore page', () => {
      cy.visit('/admin/backup');
      cy.wait(1000);
      cy.get('body').should('be.visible');
      cy.screenshot('database-management', { capture: 'viewport' });
    });
  });

  describe('Settings', () => {
    it('settings - Settings page', () => {
      cy.visit('/admin/settings');
      cy.wait(1000);
      cy.get('form, .settings', { timeout: 10000 }).should('exist');
      cy.screenshot('settings', { capture: 'viewport' });
    });
  });

  describe('Print', () => {
    it('print-text - Print text page', () => {
      // Go directly to print page for first text
      cy.visit('/text/print?text=1');
      cy.wait(1500);
      cy.get('body').should('be.visible');
      cy.screenshot('print-text', { capture: 'viewport' });
    });
  });
});

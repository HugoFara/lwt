/// <reference types="cypress" />

/**
 * Screenshot generation for README documentation
 *
 * Run with: npx cypress run --spec cypress/e2e/99-screenshots.cy.ts
 *
 * Screenshots are saved to cypress/screenshots/99-screenshots.cy.ts/
 * After running, copy the relevant screenshots to docs/assets/images/
 */

describe('README Screenshots', () => {
  // Ensure demo data is installed before running these tests
  // Run 01-setup.cy.ts first if needed

  it('01 - Text creation form (Adding text)', () => {
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
      // Try to find French, otherwise use first available
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

  it('02 - Reading interface (Learning text)', () => {
    // Navigate to texts list and click first available text
    cy.visit('/text/edit');
    cy.wait(500);

    // Click the first read link
    cy.get('a[href*="/text/read"]').first().click();
    cy.url().should('include', '/text/read');

    // Wait for the reading interface to fully load
    cy.get('#thetext', { timeout: 10000 }).should('exist');
    cy.get('#thetext .wsty', { timeout: 10000 }).should('have.length.at.least', 1);

    // Wait for Alpine.js components to initialize
    cy.wait(1000);

    cy.screenshot('reading-text', { capture: 'viewport' });
  });

  it('03 - Word review interface (Reviewing word)', () => {
    // Navigate to the review interface
    cy.visit('/review?lang=1');
    cy.wait(500);

    // Check if review settings form loaded or if we need to start review
    cy.get('body').then(($body) => {
      if ($body.find('form').length > 0 && $body.find('input[type="submit"]').length > 0) {
        // Review setup form - submit to start review
        cy.get('input[type="submit"], button[type="submit"]').first().click();
        cy.wait(500);
      }
    });

    // Wait for review interface to load
    cy.wait(500);
    cy.screenshot('reviewing-word', { capture: 'viewport' });
  });
});

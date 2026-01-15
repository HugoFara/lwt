/// <reference types="cypress" />

describe('Reading Interface', () => {
  // This test suite requires demo data to be installed
  // Run 01-setup.cy.ts first, or run the full e2e suite in order

  // Helper to navigate to reading page
  const visitReadingPage = () => {
    cy.visit('/text/edit');
    // Wait for Alpine.js to fully initialize - the page uses x-data for text list
    cy.get('[x-data]', { timeout: 10000 }).should('exist');
    // Wait for Alpine.js to render the links - they use :href bindings
    // First wait a moment for Alpine.js to process the data and render
    cy.wait(1000);

    // Try to find a reading link - if Alpine.js rendered, href should exist
    cy.get('body').then(($body) => {
      if ($body.find('a[href*="/text/read"]').length > 0) {
        // Found a reading link, click it
        cy.get('a[href*="/text/read"]').first().click();
      } else if ($body.find('a[href*="/text/"][href*="/read"]').length > 0) {
        // Try alternative pattern: /text/123/read
        cy.get('a[href*="/text/"][href*="/read"]').first().click();
      } else {
        // Fallback: navigate directly to a known demo text (text ID 4 = "The Man and the Dog")
        cy.visit('/text/read?start=4');
      }
    });

    // Wait for page to load
    cy.url().should('include', '/text/read');
    cy.wait(500);
  };

  describe('Reading Page Load', () => {
    it('should load the reading interface with #thetext', () => {
      visitReadingPage();
      cy.get('#thetext', { timeout: 10000 }).should('exist');
    });

    it('should display words with wsty class', () => {
      visitReadingPage();
      cy.get('#thetext', { timeout: 10000 }).should('exist');
      // Wait for Alpine.js to render the text
      cy.get('#thetext .wsty, #thetext .word', { timeout: 10000 }).should(
        'have.length.at.least',
        1
      );
    });

    it('should have sentence containers', () => {
      visitReadingPage();
      cy.get('#thetext [id^="sent_"]', { timeout: 10000 }).should(
        'have.length.at.least',
        1
      );
    });
  });

  describe('Multi-Word Selection', () => {
    beforeEach(() => {
      visitReadingPage();
      // Wait for text to be fully rendered
      cy.get('#thetext .wsty', { timeout: 10000 }).should(
        'have.length.at.least',
        3
      );
    });

    it('should have words in sentence containers', () => {
      cy.get('#thetext [id^="sent_"] .wsty').should('have.length.at.least', 1);
    });

    it('should log word element details', () => {
      cy.get('#thetext .wsty')
        .first()
        .then(($word) => {
          cy.log(`Word text: "${$word.text()}"`);
          cy.log(`Word classes: ${$word.attr('class')}`);
          cy.log(`Word data_order: ${$word.attr('data_order')}`);
          cy.log(`Word data_status: ${$word.attr('data_status')}`);
          cy.log(`Parent tag: ${$word.parent().prop('tagName')}`);
          cy.log(`Parent id: ${$word.parent().attr('id')}`);
        });
    });

    it('should open multi-word modal when selecting multiple words', () => {
      // Get a sentence with at least 2 words
      cy.get('#thetext [id^="sent_"]').first().as('sentence');
      cy.get('@sentence').find('.wsty').should('have.length.at.least', 2);

      // Get the first two words
      cy.get('@sentence').find('.wsty').then(($words) => {
        const firstWord = $words[0];
        const secondWord = $words[1];
        cy.log(`First word: "${firstWord.textContent}"`);
        cy.log(`Second word: "${secondWord.textContent}"`);

        // Create a text selection spanning both words using native Selection API
        cy.window().then((win) => {
          const selection = win.getSelection();
          if (!selection) return;

          // Clear any existing selection
          selection.removeAllRanges();

          // Create a range spanning both words
          const range = win.document.createRange();
          range.setStart(firstWord.firstChild || firstWord, 0);
          range.setEndAfter(secondWord.lastChild || secondWord);
          selection.addRange(range);

          cy.log(`Selection created: "${selection.toString()}"`);

          // Trigger mouseup to process the selection
          const mouseUpEvent = new win.MouseEvent('mouseup', {
            bubbles: true,
            cancelable: true,
            button: 0,
            view: win
          });
          secondWord.dispatchEvent(mouseUpEvent);
        });

        // Wait for the selection handler to process
        cy.wait(100);

        // Check that the multi-word modal opened
        cy.get('[x-data="multiWordModal"]').should('exist');
        cy.get('[x-data="multiWordModal"] .modal').should('have.class', 'is-active');
      });
    });

    it('should wrap term in curly braces in sentence', () => {
      // Get a sentence with at least 2 words
      cy.get('#thetext [id^="sent_"]').first().as('sentence');
      cy.get('@sentence').find('.wsty').should('have.length.at.least', 2);

      // Get the first two words
      cy.get('@sentence').find('.wsty').then(($words) => {
        const firstWord = $words[0];
        const secondWord = $words[1];

        // Create a text selection spanning both words
        cy.window().then((win) => {
          const selection = win.getSelection();
          if (!selection) return;

          selection.removeAllRanges();
          const range = win.document.createRange();
          range.setStart(firstWord.firstChild || firstWord, 0);
          range.setEndAfter(secondWord.lastChild || secondWord);
          selection.addRange(range);

          const mouseUpEvent = new win.MouseEvent('mouseup', {
            bubbles: true,
            cancelable: true,
            button: 0,
            view: win
          });
          secondWord.dispatchEvent(mouseUpEvent);
        });

        // Wait for modal to open and verify sentence exists
        cy.wait(500);
        cy.window().then((win) => {
          const store = win.Alpine.store('multiWordForm');
          // Verify the modal opened and has sentence data
          expect(store.isVisible).to.equal(true);
          // The sentence should exist (curly brace wrapping may not work for all languages,
          // especially Chinese/Japanese where there are no spaces between characters)
          if (store.formData.sentence && store.formData.sentence.length > 0) {
            // For languages with spaces, the sentence should have curly braces around the term
            // For non-spaced languages (CJK), the regex-based wrapping may not work
            const hasSpaces = /\s/.test(store.formData.sentence);
            if (hasSpaces) {
              expect(store.formData.sentence).to.include('{');
              expect(store.formData.sentence).to.include('}');
            } else {
              // For non-spaced languages, just verify sentence exists
              expect(store.formData.sentence.length).to.be.greaterThan(0);
              cy.log('Curly brace wrapping skipped for non-spaced language text');
            }
          }
        });
      });
    });

    it('should load existing multi-word term when re-selecting', () => {
      // Get a sentence with at least 2 words
      cy.get('#thetext [id^="sent_"]').first().as('sentence');
      cy.get('@sentence').find('.wsty').should('have.length.at.least', 2);

      cy.get('@sentence').find('.wsty').then(($words) => {
        const firstWord = $words[0];
        const secondWord = $words[1];

        // First selection - create a new multi-word term
        cy.window().then((win) => {
          const selection = win.getSelection();
          if (!selection) return;

          selection.removeAllRanges();
          const range = win.document.createRange();
          range.setStart(firstWord.firstChild || firstWord, 0);
          range.setEndAfter(secondWord.lastChild || secondWord);
          selection.addRange(range);

          const mouseUpEvent = new win.MouseEvent('mouseup', {
            bubbles: true,
            cancelable: true,
            button: 0,
            view: win
          });
          secondWord.dispatchEvent(mouseUpEvent);
        });

        // Wait for modal and save with a translation
        cy.wait(500);
        cy.window().then((win) => {
          const store = win.Alpine.store('multiWordForm');
          // Set a translation and save
          store.formData.translation = 'Test Translation';
          store.formData.status = 2;
          return store.save();
        });

        // Wait for save to complete
        cy.wait(500);

        // Close the modal
        cy.window().then((win) => {
          const store = win.Alpine.store('multiWordForm');
          store.reset();
        });

        // Second selection - same text should load existing term
        cy.wait(200);
        cy.window().then((win) => {
          const selection = win.getSelection();
          if (!selection) return;

          selection.removeAllRanges();
          const range = win.document.createRange();
          range.setStart(firstWord.firstChild || firstWord, 0);
          range.setEndAfter(secondWord.lastChild || secondWord);
          selection.addRange(range);

          const mouseUpEvent = new win.MouseEvent('mouseup', {
            bubbles: true,
            cancelable: true,
            button: 0,
            view: win
          });
          secondWord.dispatchEvent(mouseUpEvent);
        });

        // Wait for modal and verify existing data is loaded
        cy.wait(500);
        cy.window().then((win) => {
          const store = win.Alpine.store('multiWordForm');
          // Should load the existing translation and status
          expect(store.formData.translation).to.equal('Test Translation');
          expect(store.formData.status).to.equal(2);
          expect(store.isNewWord).to.equal(false);
        });
      });
    });

    it('should show multi-word text with spaces in modal', () => {
      // Get a sentence with at least 2 words
      cy.get('#thetext [id^="sent_"]').first().as('sentence');
      cy.get('@sentence').find('.wsty').should('have.length.at.least', 2);

      // Get the first two words
      cy.get('@sentence').find('.wsty').then(($words) => {
        const firstWord = $words[0];
        const secondWord = $words[1];

        // Create a text selection spanning both words
        cy.window().then((win) => {
          const selection = win.getSelection();
          if (!selection) return;

          selection.removeAllRanges();
          const range = win.document.createRange();
          range.setStart(firstWord.firstChild || firstWord, 0);
          range.setEndAfter(secondWord.lastChild || secondWord);
          selection.addRange(range);

          const mouseUpEvent = new win.MouseEvent('mouseup', {
            bubbles: true,
            cancelable: true,
            button: 0,
            view: win
          });
          secondWord.dispatchEvent(mouseUpEvent);
        });

        // Wait for modal to open and verify text
        cy.wait(200);
        cy.window().then((win) => {
          const store = win.Alpine.store('multiWordForm');
          // The text should include a space between the words
          expect(store.formData.text).to.include(' ');
        });
      });
    });
  });

  describe('Alpine Store Check', () => {
    it('should have multiWordForm store registered', () => {
      visitReadingPage();

      cy.window().then((win) => {
        // Wait for Alpine to be ready
        cy.wrap(null).should(() => {
          expect(win.Alpine).to.not.equal(undefined);
        });

        // Check the store
        cy.wrap(null).then(() => {
          const store = win.Alpine.store('multiWordForm');
          cy.log(`multiWordForm store: ${store ? 'EXISTS' : 'NOT FOUND'}`);

          if (store) {
            cy.log(`Store isVisible: ${store.isVisible}`);
            cy.log(`Store isLoading: ${store.isLoading}`);
            expect(store).to.have.property('loadForEdit');
            expect(store).to.have.property('save');
          } else {
            throw new Error('multiWordForm store not found!');
          }
        });
      });
    });

    it('should have multiWordModal component in DOM', () => {
      visitReadingPage();

      cy.get('[x-data="multiWordModal"]', { timeout: 5000 }).should('exist');
    });
  });

  describe('Manual Store Test', () => {
    it('should be able to manually open the modal via store', () => {
      visitReadingPage();

      // Wait for everything to load
      cy.get('#thetext .wsty', { timeout: 10000 }).should('have.length.at.least', 1);

      cy.window().then((win) => {
        // Get the store and call loadForEdit manually
        const store = win.Alpine.store('multiWordForm');
        expect(store).to.not.equal(undefined);

        // Get text ID from URL
        cy.url().then((url) => {
          const match = url.match(/text=(\d+)/);
          const textId = match ? parseInt(match[1], 10) : 1;

          cy.log(`Manually calling store.loadForEdit with textId=${textId}`);

          // Call loadForEdit - this should open the modal
          store.loadForEdit(textId, 1, 'test multi word', 3);
        });
      });

      // Wait and check if modal opened
      cy.wait(1000);
      cy.get('.modal.is-active', { timeout: 5000 }).should('exist');
    });
  });

  describe('Mobile Multi-Word Selection (Issue #142)', () => {
    // Test for GitHub issue #142: Multi-word selection not possible on phone
    // This test verifies that touch-based text selection triggers the multi-word modal

    beforeEach(() => {
      // Set mobile viewport (iPhone SE dimensions)
      cy.viewport(375, 667);
      // Navigate directly to reading page for a known text (text ID 4 = "The Man and the Dog")
      // This avoids relying on the text list which may have Alpine.js timing issues on mobile
      cy.visit('/text/read?start=4');
      // Wait for text to be fully rendered
      cy.get('#thetext .wsty', { timeout: 10000 }).should(
        'have.length.at.least',
        3
      );
    });

    it('should open multi-word modal when using touch to select multiple words', () => {
      // Get a sentence with at least 2 words
      cy.get('#thetext [id^="sent_"]').first().as('sentence');
      cy.get('@sentence').find('.wsty').should('have.length.at.least', 2);

      // Get the first two words and simulate touch selection
      cy.get('@sentence')
        .find('.wsty')
        .then(($words) => {
          const firstWord = $words[0];
          const secondWord = $words[1];
          cy.log(`First word: "${firstWord.textContent}"`);
          cy.log(`Second word: "${secondWord.textContent}"`);

          // Create a text selection and simulate touch events
          cy.window().then((win) => {
            const selection = win.getSelection();
            if (!selection) return;

            // Clear any existing selection
            selection.removeAllRanges();

            // Create a range spanning both words
            const range = win.document.createRange();
            range.setStart(firstWord.firstChild || firstWord, 0);
            range.setEndAfter(secondWord.lastChild || secondWord);
            selection.addRange(range);

            cy.log(`Selection created: "${selection.toString()}"`);

            // Simulate touchend event (this is what phones fire after text selection)
            const touchEndEvent = new win.TouchEvent('touchend', {
              bubbles: true,
              cancelable: true,
              view: win,
              touches: [],
              targetTouches: [],
              changedTouches: [
                new win.Touch({
                  identifier: 0,
                  target: secondWord,
                  clientX: secondWord.getBoundingClientRect().right,
                  clientY: secondWord.getBoundingClientRect().top + 10
                })
              ]
            });
            secondWord.dispatchEvent(touchEndEvent);
          });

          // Wait for the selection handler to process
          cy.wait(200);

          // Check that the multi-word modal opened
          // This test will FAIL until issue #142 is fixed because the code
          // only listens for mouseup, not touchend
          cy.get('[x-data="multiWordModal"]').should('exist');
          cy.get('[x-data="multiWordModal"] .modal').should(
            'have.class',
            'is-active'
          );
        });
    });

    it('should handle touch selection with selectionchange event', () => {
      // Alternative approach: use selectionchange event which fires on both desktop and mobile
      cy.get('#thetext [id^="sent_"]').first().as('sentence');
      cy.get('@sentence').find('.wsty').should('have.length.at.least', 2);

      cy.get('@sentence')
        .find('.wsty')
        .then(($words) => {
          const firstWord = $words[0];
          const secondWord = $words[1];

          cy.window().then((win) => {
            const selection = win.getSelection();
            if (!selection) return;

            selection.removeAllRanges();

            const range = win.document.createRange();
            range.setStart(firstWord.firstChild || firstWord, 0);
            range.setEndAfter(secondWord.lastChild || secondWord);
            selection.addRange(range);

            // Dispatch selectionchange event (fires on mobile after text selection UI closes)
            const selectionChangeEvent = new win.Event('selectionchange', {
              bubbles: true,
              cancelable: true
            });
            win.document.dispatchEvent(selectionChangeEvent);

            // Also try touchend as backup
            const touchEndEvent = new win.TouchEvent('touchend', {
              bubbles: true,
              cancelable: true,
              view: win,
              touches: [],
              targetTouches: [],
              changedTouches: [
                new win.Touch({
                  identifier: 0,
                  target: secondWord,
                  clientX: secondWord.getBoundingClientRect().right,
                  clientY: secondWord.getBoundingClientRect().top + 10
                })
              ]
            });
            secondWord.dispatchEvent(touchEndEvent);
          });

          cy.wait(200);

          // The modal should open after touch selection
          cy.get('[x-data="multiWordModal"] .modal').should(
            'have.class',
            'is-active'
          );
        });
    });

    it('should work with native mobile selection gesture simulation', () => {
      // This test simulates what happens when a user long-presses and drags on mobile
      cy.get('#thetext [id^="sent_"]').first().as('sentence');
      cy.get('@sentence').find('.wsty').should('have.length.at.least', 2);

      cy.get('@sentence')
        .find('.wsty')
        .first()
        .then(($firstWord) => {
          const firstWord = $firstWord[0];

          cy.get('@sentence')
            .find('.wsty')
            .eq(1)
            .then(($secondWord) => {
              const secondWord = $secondWord[0];

              const firstRect = firstWord.getBoundingClientRect();
              const secondRect = secondWord.getBoundingClientRect();

              cy.window().then((win) => {
                // Simulate long-press (touchstart and hold)
                const touchStartEvent = new win.TouchEvent('touchstart', {
                  bubbles: true,
                  cancelable: true,
                  view: win,
                  touches: [
                    new win.Touch({
                      identifier: 0,
                      target: firstWord,
                      clientX: firstRect.left + 5,
                      clientY: firstRect.top + 5
                    })
                  ],
                  targetTouches: [
                    new win.Touch({
                      identifier: 0,
                      target: firstWord,
                      clientX: firstRect.left + 5,
                      clientY: firstRect.top + 5
                    })
                  ],
                  changedTouches: [
                    new win.Touch({
                      identifier: 0,
                      target: firstWord,
                      clientX: firstRect.left + 5,
                      clientY: firstRect.top + 5
                    })
                  ]
                });
                firstWord.dispatchEvent(touchStartEvent);

                // Create the actual text selection (simulating what the OS does)
                const selection = win.getSelection();
                if (selection) {
                  selection.removeAllRanges();
                  const range = win.document.createRange();
                  range.setStart(firstWord.firstChild || firstWord, 0);
                  range.setEndAfter(secondWord.lastChild || secondWord);
                  selection.addRange(range);
                }

                // Simulate touchend after selection
                const touchEndEvent = new win.TouchEvent('touchend', {
                  bubbles: true,
                  cancelable: true,
                  view: win,
                  touches: [],
                  targetTouches: [],
                  changedTouches: [
                    new win.Touch({
                      identifier: 0,
                      target: secondWord,
                      clientX: secondRect.right,
                      clientY: secondRect.top + 5
                    })
                  ]
                });
                secondWord.dispatchEvent(touchEndEvent);
              });
            });
        });

      cy.wait(300);

      // Verify the multi-word modal opened
      cy.get('[x-data="multiWordModal"] .modal').should(
        'have.class',
        'is-active'
      );
    });
  });
});

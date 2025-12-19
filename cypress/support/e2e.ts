/// <reference types="cypress" />

import './commands';

// Prevent Cypress from failing tests on uncaught exceptions from the app
Cypress.on('uncaught:exception', () => {
  // Return false to prevent the error from failing the test
  return false;
});

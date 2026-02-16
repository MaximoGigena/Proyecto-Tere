// cypress/support/utils.js
export function generateEmailUnico() {
  const timestamp = Date.now();
  const random = Math.floor(Math.random() * 1000);
  return `test-${timestamp}-${random}@test.com`;
}

export function setupAlertStub() {
  cy.window().then((win) => {
    cy.stub(win, 'alert').as('alertStub');
  });
}

// Helper para esperar y verificar múltiples peticiones
export function waitForAllRequests(aliasArray, timeout = 10000) {
  aliasArray.forEach(alias => {
    cy.wait(alias, { timeout });
  });
}
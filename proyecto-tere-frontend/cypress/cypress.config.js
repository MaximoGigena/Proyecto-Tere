const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:5173',
    viewportWidth: 1280,
    viewportHeight: 720,
    defaultCommandTimeout: 10000,
    requestTimeout: 10000,
    video: true,
    screenshotOnRunFailure: true,
    experimentalRunAllSpecs: true,
    allowCypressEnv: true, // Añade esta línea
    setupNodeEvents(on, config) {
      // Implementar event listeners si es necesario
    },
  },
});
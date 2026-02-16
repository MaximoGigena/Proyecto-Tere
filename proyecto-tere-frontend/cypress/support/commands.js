import 'cypress-file-upload';


// cypress/support/commands.js
import RegistroPage from './pages/RegistroPage';
import DatosOpcionalesPage from './pages/DatosOpcionalesPage';
import DatosContactoPage from './pages/DatosContactoPage';
import { generateEmailUnico } from './utils';

// Interceptar peticiones de registro

Cypress.Commands.add('interceptarPeticionesRegistro', () => {
  cy.intercept('GET', '**/sanctum/csrf-cookie').as('csrfCookie');
  cy.intercept('POST', '**/api/registrar-usuario').as('registrarUsuario');
  // CORREGIR: La ruta exacta que muestra el log
  cy.intercept('POST', '**/api/actualizar-datos-opcionales').as('actualizarOpcionales');
  cy.intercept('POST', '**/api/actualizar-datos-contacto').as('actualizarContacto'); // Si existe
});

// En la prueba de error 500:
it('debería manejar error 500 en registro principal', () => {
  // Definir el intercept ANTES de cualquier acción
  cy.intercept('POST', '**/api/registrar-usuario', {
    statusCode: 500,
    body: { message: 'Error interno del servidor' }
  }).as('registrarUsuario');

  cy.visit('http://localhost:5173/registro/usuario', {
    onBeforeLoad(win) {
      cy.stub(win, 'alert').as('alertStub');
    }
  });
  
  cy.wait(1000);
  
  cy.fixture('usuario').then((data) => {
    const emailUnico = generateEmailUnico(); // Asegúrate de importar esta función
    
    // CORRECCIÓN: Crear instancia de RegistroPage aquí
    const registroPage = new RegistroPage();
    
    // Completar formulario
    registroPage
      .completarFormularioObligatorio({
        ...data.usuarioValido,
        email: emailUnico,
        password: 'Password123',
        confirmPassword: 'Password123'
      })
      .subirFotoObligatoria();
    
    cy.wait(1000);
    
    // Enviar y confirmar
    cy.get('button[type="submit"]').click();
    cy.get('[data-testid="modal-confirmacion"]', { timeout: 10000 }).should('be.visible');
    cy.get('[data-testid="modal-confirmacion"] button:contains("Registrar")').click();
    
    // Esperar la petición
    cy.wait('@registrarUsuario', { timeout: 15000 }).then((interception) => {
      expect(interception.response.statusCode).to.eq(500);
    });
    
    // Verificar alerta
    cy.get('@alertStub').should('be.called');
  });
});

// En cypress/support/commands.js
Cypress.Commands.add('debugRegistro', () => {
  cy.intercept('POST', '**/api/registrar-usuario', (req) => {
    cy.log('📤 Petición de registro:', req.body);
    req.continue((res) => {
      cy.log('📥 Respuesta del servidor:', res.body);
    });
  }).as('registrarUsuarioDebug');
});

// Comando para registro rápido hasta datos de contacto
Cypress.Commands.add('registroRapidoHastaContacto', (options) => {
  const registroPage = new RegistroPage();
  
  // Registrar usuario principal
  registroPage.visit();
  cy.wait(1000);
  
  registroPage.completarFormularioObligatorio(options.usuarioValido);
  registroPage.subirFotoObligatoria();
  registroPage.verificarFotoSubida();
  registroPage.enviarFormulario();
  
  // Confirmar registro
  cy.get('[data-testid="modal-confirmacion"]', { timeout: 10000 }).should('be.visible');
  cy.get('[data-testid="modal-confirmacion"] button:contains("Registrar")').click();
  cy.wait('@registrarUsuario', { timeout: 10000 });
  
  // Omitir datos opcionales
  cy.get('.fixed.inset-0.bg-black.bg-opacity-50', { timeout: 20000 }).should('be.visible');
  cy.contains('h3', 'Datos Opcionales').should('be.visible');
  cy.contains('button', 'Continuar con Contacto').click();
  
  // IMPORTANTE: Esperar que se complete la petición de opcionales
  cy.wait('@actualizarOpcionales', { timeout: 10000 });
  
  // Verificar que el overlay de opcionales desaparezca y aparezca el de contacto
  // Añadir un pequeño retraso para asegurar que el DOM se actualice
  cy.wait(500);
  
  cy.get('.fixed.inset-0.bg-black.bg-opacity-50', { timeout: 10000 }).should('be.visible');
  cy.contains('h3', 'Datos de Contacto').should('be.visible');
  
  // Añadir un log para confirmar
  cy.log('✅ En overlay de Datos de Contacto');
});

// Generador de email único
Cypress.Commands.add('generateEmailUnico', () => {
  const timestamp = Date.now();
  const random = Math.floor(Math.random() * 1000);
  return `test-${timestamp}-${random}@test.com`;
});
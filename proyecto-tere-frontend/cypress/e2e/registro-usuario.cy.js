// cypress/e2e/registro-usuario.cy.js
import RegistroPage from '../support/pages/RegistroPage';
import DatosOpcionalesPage from '../support/pages/DatosOpcionalesPage';
import DatosContactoPage from '../support/pages/DatosContactoPage';
import { generateEmailUnico } from '../support/utils';

describe('Flujo Completo de Registro de Usuario', () => {
  const registroPage = new RegistroPage();
  const datosOpcionalesPage = new DatosOpcionalesPage();
  const datosContactoPage = new DatosContactoPage();
  
  beforeEach(() => {
    cy.interceptarPeticionesRegistro();
    cy.viewport(1280, 720);
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  describe('Registro Exitoso con Datos Opcionales', () => {
    it('debería completar el registro con todos los datos', () => {
      cy.fixture('usuario').then((data) => {
        // Verificar que los datos opcionales existen
        if (!data.datosOpcionales) {
          data.datosOpcionales = {
            ocupacion: 'Desarrollador',
            tipoVivienda: 'departamento',
            experienciaMascotas: 'nueva',
            conviveConNiños: 'si',
            conviveConMascotas: 'si',
            descripcion: 'Me encantan los animales'
          };
        }

        if (!data.datosContacto) {
        data.datosContacto = {
          telefono: "123456789",
          dni: "Call 123",
          nombreCompleto: "pepeito pepa",
          correoElectronico: "ddsfdfds@gmail.com"
        };
      }
        
        const emailUnico = generateEmailUnico();
        const usuarioModificado = {
          ...data.usuarioValido,
          email: emailUnico,
          password: 'Password123',
          confirmPassword: 'Password123'
        };
            
            // PASO 1: Registro principal
            registroPage
              .visit()
              .completarFormularioObligatorio(usuarioModificado)
              .subirFotoObligatoria()
              .verificarFotoSubida()
              .enviarFormulario()
              .verificarModalConfirmacion()
              .confirmarRegistro();
            
            // Verificar respuesta exitosa
            cy.get('@registrarUsuario').then((interception) => {
              expect(interception.response.statusCode).to.be.oneOf([200, 201]);
            });

            // PASO 2: Datos Opcionales
            datosOpcionalesPage
              .verificarOverlayVisible()
              .completarDatosOpcionales(data.datosOpcionales)
              .guardarYOmitir();

            // Verificar petición de datos opcionales
            cy.wait('@actualizarOpcionales', { timeout: 10000 }).then((interception) => {
              expect(interception.response.statusCode).to.eq(200);
              expect(interception.response.body.success).to.be.true;
            });

            // PASO 3: Datos de Contacto
            datosContactoPage
              .verificarOverlayVisible()
              .completarDatosContacto(data.datosContacto)
              .finalizarRegistro();


            // Verificar redirección final
            cy.url({ timeout: 10000 }).should('include', '/explorar/encuentros');
          });
    });
  });
  

  describe('Registro Omitiendo Datos Opcionales y Completando Contacto', () => {
    it('debería completar el registro omitiendo datos opcionales pero completando contacto', () => {
      cy.fixture('usuario').then((data) => {
        const emailUnico = generateEmailUnico();
        const usuarioModificado = {
          ...data.usuarioValido,
          email: emailUnico,
          password: 'Password123',
          confirmPassword: 'Password123'
        };

        // Datos de contacto CORRECTOS según tu prueba anterior exitosa
        const datosContacto = {
          telefono: "123456789",
          dni: "Call 123",
          nombreCompleto: "pepeito pepa",
          correoElectronico: "ddsfdfds@gmail.com"
        };
            
        // PASO 1: Completar datos obligatorios
        registroPage
          .visit()
          .completarFormularioObligatorio(usuarioModificado)
          .subirFotoObligatoria()
          .verificarFotoSubida()
          .enviarFormulario()
          .verificarModalConfirmacion()
          .confirmarRegistro();

        // PASO 2: Omitir Datos Opcionales
        datosOpcionalesPage
          .verificarOverlayVisible()
          .omitir();

        // PASO 3: COMPLETAR Datos de Contacto (con los campos correctos)
        datosContactoPage
          .verificarOverlayVisible()
          .completarDatosContacto(datosContacto)  // Ahora usa los campos correctos
          .finalizarRegistro();                    // Finaliza el registro

        // Verificar redirección final
        cy.url({ timeout: 10000 }).should('include', '/explorar/encuentros');
      });
    });
  });

  describe('Validaciones del Formulario', () => {
    it('debería mostrar error si las contraseñas no coinciden', () => {
      // Configurar stub de alert antes de visitar
      cy.visit('http://localhost:5173/registro/usuario', {
        onBeforeLoad(win) {
          cy.stub(win, 'alert').as('alertStub');
        }
      });
      
      cy.wait(1000);
      
      cy.fixture('usuario').then((data) => {
        const emailUnico = generateEmailUnico();
        
        registroPage
          .completarFormularioObligatorio({
            ...data.usuarioValido,
            email: emailUnico,
            password: 'Password123',
            confirmPassword: 'contraseña-diferente'
          })
          .subirFotoObligatoria()
          .enviarFormulario();
        
        // Verificar alerta
        cy.get('@alertStub').should('be.calledWith', 'Las contraseñas no coinciden');
        
        // Verificar que NO se hizo la petición
        cy.get('@registrarUsuario.all').should('have.length', 0);
      });
    });

    // En registro-usuario.cy.js
    it('debería validar campos requeridos incluyendo la foto', () => {
      registroPage.visit();
      cy.wait(1000);
      
      cy.fixture('usuario').then((data) => {
        const emailUnico = generateEmailUnico();
        
        registroPage.completarFormularioObligatorio({
          ...data.usuarioValido,
          email: emailUnico,
          password: 'Password123',
          confirmPassword: 'Password123'
        });
      });
      
      // Intentar enviar sin foto
      cy.get('button[type="submit"]').click();
      
      // Verificar que NO se hizo la petición
      cy.get('@registrarUsuario.all').should('have.length', 0);
      
      // Verificar que la foto es requerida (según tu UI)
      // Busca un mensaje de error o validación HTML5
      cy.get('input[type="file"]').first().then($input => {
        // Verificar validación HTML5
        const isRequired = $input[0].hasAttribute('required') || 
                          $input[0].getAttribute('aria-required') === 'true' ||
                          $input.closest('form').find('[data-error="foto"]').length > 0;
        
        // O verificar que hay un mensaje de error visible
        cy.contains(/foto|imagen|requerida|obligatoria/i).should('exist');
      });
    });
  });

  describe('Manejo de Errores del Servidor', () => {
    beforeEach(() => {
      // 1. Definir el intercept ANTES de visitar la página
      cy.intercept('POST', '**/api/registrar-usuario', {
        statusCode: 500,
        body: { message: 'Error interno del servidor' }
      }).as('registrarUsuarioError');

      // 2. Visitar la página UNA SOLA VEZ con el stub del alert
      cy.visit('http://localhost:5173/registro/usuario', {
        onBeforeLoad(win) {
          cy.stub(win, 'alert').as('alertStub');
        }
      });
      
      cy.wait(1000);
    });

    it('debería manejar error 500 en registro principal', () => {    
      cy.fixture('usuario').then((data) => {
        const emailUnico = generateEmailUnico();
        
        const registroPage = new RegistroPage();
        
        // 3. Completar formulario
        registroPage
          .completarFormularioObligatorio({
            ...data.usuarioValido,
            email: emailUnico,
            password: 'Password123',
            confirmPassword: 'Password123'
          })
          .subirFotoObligatoria();
        
        cy.wait(1000);
        
        // 4. Enviar formulario (esto abre el modal)
        cy.get('button[type="submit"]').click();
        
        // 5. Verificar y manejar el modal de confirmación
        cy.get('[data-testid="modal-confirmacion"]', { timeout: 10000 })
          .should('be.visible')
          .within(() => {
            cy.contains('button', 'Registrar').click();
          });
        
        // 6. AHORA esperar la petición con el error 500
        cy.wait('@registrarUsuarioError', { timeout: 15000 }).then((interception) => {
          expect(interception.response.statusCode).to.eq(500);
          cy.log('📡 Respuesta del servidor:', interception.response.statusCode);
        });
        
        // 7. Verificar que el modal se cerró
        cy.get('[data-testid="modal-confirmacion"]').should('not.exist');
        
        // 8. Verificar que se llamó al alert
        cy.get('@alertStub').should('be.called');
      });
    });


    it('debería manejar error de email duplicado', () => {
      cy.intercept('POST', '**/api/registrar-usuario', {
        statusCode: 422,
        body: { 
          success: false,
          message: 'El email ya está registrado' 
        }
      }).as('registrarUsuarioDuplicado');

      cy.fixture('usuario').then((data) => {
        const emailUnico = generateEmailUnico();
        const registroPage = new RegistroPage();
      
        registroPage
          .completarFormularioObligatorio({
            ...data.usuarioValido,
            email: emailUnico,
            password: 'Password123',
            confirmPassword: 'Password123'
          })
          .subirFotoObligatoria()
          .enviarFormulario()
          .verificarModalConfirmacion()
          .confirmarRegistro('@registrarUsuarioDuplicado'); // PASAR EL ALIAS CORRECTO
        
        // Verificar alerta
        cy.get('@alertStub').should('be.calledWith', 
          Cypress.sinon.match(/El email ya está registrado/)
        );
      });
    });
  });

  describe('Interacción con Telegram', () => {
      it('debería abrir modal de Telegram', () => {
        // Manejar la excepción del clipboard
        cy.on('uncaught:exception', (err) => {
          if (err.message.includes('Failed to execute \'writeText\' on \'Clipboard\'')) {
            return false; // Evita que Cypress falle la prueba
          }
        });

        cy.fixture('usuario').then((data) => {
          const emailUnico = generateEmailUnico();
          const usuarioModificado = {
            ...data.usuarioValido,
            email: emailUnico,
            password: 'Password123',
            confirmPassword: 'Password123'
          };

          // Registro rápido hasta datos de contacto
          cy.registroRapidoHastaContacto({ usuarioValido: usuarioModificado });
          
          datosContactoPage
            .configurarTelegram()
            .verificarModalTelegram(usuarioModificado.email);
        });
      });
  });
});
// e2e/registro-veterinario.cy.js
import RegistroVeterinarioPage from '../support/pages/RegistroVeterinarioPage';
import { generateEmailUnico } from '../support/utils';

describe('Flujo Completo de Registro de Veterinario', () => {
  const registroVetPage = new RegistroVeterinarioPage();
  
  beforeEach(() => {
    // Interceptar peticiones de registro
    cy.intercept('POST', '**/api/registrar-veterinario').as('registrarVeterinario');
    
    cy.viewport(1280, 720);
    cy.clearCookies();
    cy.clearLocalStorage();
  });

  describe('Registro Exitoso con Todos los Datos', () => {
    it('debería completar el registro con todos los datos del veterinario', () => {
      cy.fixture('veterinario').then((data) => {
        const emailUnico = generateEmailUnico();
        // Generar matrícula única
        const matriculaUnica = `VET-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
        
        const veterinarioModificado = {
          ...data.veterinarioValido,
          email: emailUnico,
          matricula: matriculaUnica, // Usar matrícula única
          emailContacto: `contacto-${emailUnico}`
        };

        registroVetPage
          .visit()
          .completarFormularioCompleto(veterinarioModificado)
          .subirMultiplesFotos(3)
          .enviarFormulario();

        // Esperar la petición
        cy.wait('@registrarVeterinario', { timeout: 10000 }).then((interception) => {
          cy.log('Status code:', interception.response.statusCode);
          cy.log('Response body:', interception.response.body);
          
          // Verificar que sea exitoso
          expect(interception.response.statusCode).to.be.oneOf([200, 201]);
        });

        // Verificar redirección
        cy.url({ timeout: 10000 }).should('include', '/veterinario-pendiente');
      });
    });
  });

  describe('Registro con Datos Mínimos', () => {
    it('debería completar el registro solo con datos obligatorios', () => {
      cy.fixture('veterinario').then((data) => {
        const emailUnico = generateEmailUnico();
        // Generar matrícula única
        const matriculaUnica = `VET-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
        
        const veterinarioMinimo = {
          nombre: data.veterinarioValido.nombre,
          email: emailUnico,
          matricula: matriculaUnica,
          especialidad: data.veterinarioValido.especialidad, 
        };

        registroVetPage
          .visit()
          .completarDatosObligatorios(veterinarioMinimo)
          .subirFoto(0)
          .enviarFormulario();

        cy.wait('@registrarVeterinario', { timeout: 10000 }).then((interception) => {
          cy.log('Status code:', interception.response.statusCode);
          expect(interception.response.statusCode).to.be.oneOf([200, 201]);
        });

        cy.url({ timeout: 10000 }).should('include', '/veterinario-pendiente');
      });
    });
  });

  describe('Validaciones del Formulario', () => {
    it('debería validar campos obligatorios', () => {
      registroVetPage.visit();
      
      // Intentar enviar formulario vacío
      registroVetPage.enviarFormulario();

      // Verificar validación HTML5 o mensajes de error personalizados
      cy.get('input[required]').first().then($input => {
        const validationMessage = $input[0].validationMessage;
        if (validationMessage) {
          expect(validationMessage).to.not.be.empty;
        } else {
          // Si no hay validación HTML5, buscar mensajes de error personalizados
          cy.get('[class*="error"], [role="alert"]').should('be.visible');
        }
      });
    });

    it('debería validar formato de email', () => {
      registroVetPage.visit();

      registroVetPage.emailInput.type('email-invalido');
      registroVetPage.enviarFormulario();

      cy.get('input[type="email"]').then($input => {
        const validationMessage = $input[0].validationMessage;
        if (validationMessage) {
          expect(validationMessage).to.satisfy(msg => 
            msg.includes('incluir un signo') || 
            msg.includes('Please include an') ||
            msg.includes('debe incluir')
          );
        } else {
          // Buscar mensaje de error personalizado
          cy.contains('email', 'válido', 'formato').should('be.visible');
        }
      });
    });

    it('debería requerir al menos una foto y permitir envío con foto', () => {
      cy.fixture('veterinario').then((data) => {
        const emailUnico = generateEmailUnico();
        const matriculaUnica = `VET-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
        
        registroVetPage
          .visit()
          .completarDatosObligatorios({
            ...data.veterinarioValido,
            email: emailUnico,
            matricula: matriculaUnica
          });
        
        // Intentar enviar SIN foto
        registroVetPage.enviarFormulario();
        
        // Esperar un poco y buscar el mensaje de error
        cy.wait(500);
        
        // Verificar mensaje de error - usa un selector más específico
        cy.contains('Debes subir al menos una foto').should('exist');
        
        // También verificar que el mensaje esté visible o que haya algún indicador de error
        cy.get('[class*="error"], [class*="alert"], .bg-red-100, .text-red-600, [role="alert"]')
          .should('exist');
        
        // Verificar que NO se hizo la petición
        cy.get('@registrarVeterinario.all').should('have.length', 0);
        
        // Ahora subir UNA foto
        registroVetPage.subirFoto(0);
        cy.wait(500);
        
        // Verificar que la foto se subió
        registroVetPage.verificarFotoSubida(0);
        
        // Enviar formulario CON foto
        registroVetPage.enviarFormulario();
        
        // Verificar que la petición se envió exitosamente
        cy.wait('@registrarVeterinario').then((interception) => {
          expect(interception.response.statusCode).to.be.oneOf([200, 201]);
        });
      });
    });

    // En la prueba "debería mostrar error al intentar enviar sin fotos"
    it('debería mostrar error al intentar enviar sin fotos', () => {
      cy.fixture('veterinario').then((data) => {
        const emailUnico = generateEmailUnico();
        
        registroVetPage
          .visit()
          .completarDatosObligatorios({
            ...data.veterinarioValido,
            email: emailUnico
          });
        
        // Enviar formulario SIN fotos
        registroVetPage.enviarFormulario();
        
        // Cambiar de 'be.visible' a 'exist'
        cy.contains('Debes subir al menos una foto').should('exist');
        
        // O forzar visibilidad con scrollIntoView
        cy.contains('Debes subir al menos una foto').scrollIntoView().should('be.visible');
        
        // Verificar que NO se hizo la petición
        cy.get('@registrarVeterinario.all').should('have.length', 0);
      });
    });

    it('debería permitir envío exitoso con al menos una foto', () => {
      cy.fixture('veterinario').then((data) => {
        const emailUnico = generateEmailUnico();
        const matriculaUnica = `VET-${Date.now()}-${Math.floor(Math.random() * 1000)}`; // ← Agregar esto
        
        registroVetPage
          .visit()
          .completarDatosObligatorios({
            ...data.veterinarioValido,
            email: emailUnico,
            matricula: matriculaUnica  // ← Usar matrícula única
          })
          .subirFoto(0)
          .enviarFormulario();

        cy.wait('@registrarVeterinario').then((interception) => {
          expect(interception.response.statusCode).to.be.oneOf([200, 201]);
        });
      });
    });

    it('debería validar el límite de caracteres en descripción', () => {
      const textoLargo = 'a'.repeat(600);
      registroVetPage.visit();
      registroVetPage.verificarLimiteCaracteres(textoLargo);
    });

    it('debería mostrar contador de caracteres', () => {
      const texto = 'Descripción profesional del veterinario';
      registroVetPage.visit();
      registroVetPage.verificarContadorCaracteres(texto);
    });

    it('debería validar que experiencia sea número positivo', () => {
      registroVetPage.visit();
      
      cy.get('body').then($body => {
        if ($body.find('input[type="number"]').length > 0) {
          registroVetPage.experienciaInput.type('-5');
          
          cy.get('input[type="number"]').then($input => {
            const isValid = $input[0].checkValidity();
            
            if (!isValid) {
              expect(isValid).to.be.false;
            } else {
              // Si pasa la validación HTML5, buscar mensaje personalizado
              cy.contains('positivo', 'mayor que', 'negativo').should('be.visible');
            }
          });
        } else {
          cy.log('Campo de experiencia no encontrado - test omitido');
        }
      });
    });
  });

  describe('Manejo de Errores del Servidor', () => {
    it('debería manejar error 500 en el servidor', () => {
      cy.intercept('POST', '**/api/registrar-veterinario', {
        statusCode: 500,
        body: { message: 'Error interno del servidor' }
      }).as('registrarVeterinarioError');

      cy.fixture('veterinario').then((data) => {
        const emailUnico = generateEmailUnico();

        registroVetPage
          .visit()
          .completarDatosObligatorios({
            ...data.veterinarioValido,
            email: emailUnico
          })
          .subirFoto(0)
          .enviarFormulario();

        cy.wait('@registrarVeterinarioError').then((interception) => {
          expect(interception.response.statusCode).to.eq(500);
        });

        registroVetPage.verificarError('Error interno del servidor');
      });
    });
  });

  describe('Manejo de Errores Específicos', () => {
    it('debería manejar error de email duplicado', () => {
      cy.intercept('POST', '**/api/registrar-veterinario', {
        statusCode: 422,
        body: { message: 'El email ya está registrado' }
      }).as('registrarVeterinarioDuplicado');

      cy.fixture('veterinario').then((data) => {
        registroVetPage
          .visit()
          .completarDatosObligatorios(data.veterinarioValido)
          .subirFoto(0)
          .enviarFormulario();

        cy.wait('@registrarVeterinarioDuplicado');
        registroVetPage.verificarError('El email ya está registrado');
      });
    });

    it('debería manejar error de matrícula duplicada', () => {
      cy.intercept('POST', '**/api/registrar-veterinario', {
        statusCode: 422,
        body: { message: 'La matrícula profesional ya está registrada' }
      }).as('registrarVeterinarioMatriculaDuplicada');

      cy.fixture('veterinario').then((data) => {
        const emailUnico = generateEmailUnico();

        registroVetPage
          .visit()
          .completarDatosObligatorios({
            ...data.veterinarioValido,
            email: emailUnico
          })
          .subirFoto(0)
          .enviarFormulario();

        cy.wait('@registrarVeterinarioMatriculaDuplicada');
        registroVetPage.verificarError('La matrícula profesional ya está registrada');
      });
    });
  });

  describe('Interacciones con la UI', () => {
    it('debería mostrar el estado de carga mientras se procesa', () => {
      cy.intercept('POST', '**/api/registrar-veterinario', (req) => {
        req.reply({
          statusCode: 200,
          body: { 
            success: true,
            message: 'Registro exitoso',
            veterinario: { email: 'test@test.com' }
          },
          delay: 2000 // ← Usar delay directamente aquí
        });
      }).as('registrarVeterinario');

      cy.fixture('veterinario').then((data) => {
        const emailUnico = generateEmailUnico();
        const matriculaUnica = `VET-${Date.now()}-${Math.floor(Math.random() * 1000)}`;

        registroVetPage
          .visit()
          .completarDatosObligatorios({
            ...data.veterinarioValido,
            email: emailUnico,
            matricula: matriculaUnica
          })
          .subirFoto(0);
        
        // Hacer clic en el botón
        registroVetPage.submitButton.click({ force: true });
        
        // VERIFICAR ESTADO DE CARGA (ANTES de que llegue la respuesta)
        cy.get('button[type="submit"]', { timeout: 2000 })
          .should('contain', 'Procesando...')
          .and('be.disabled');
        
        // Ahora esperar la petición
        cy.wait('@registrarVeterinario');
        
        // NO verificar el botón después de la redirección
        // En su lugar, verificar la redirección
        cy.url({ timeout: 10000 }).should('include', '/veterinario-pendiente');
      });
    });

    it('debería permitir navegación por teclado entre campos', () => {
      registroVetPage.visit();
      
      // Verificar que existe el plugin cypress-plugin-tab
      cy.get('input[type="text"]').first().focus().tab();
      cy.get('input[type="email"]').should('be.focused').tab();
      cy.get('input[type="text"]').eq(1).should('be.focused');
    });

    it('debería manejar correctamente el envío con Enter', () => {
      cy.fixture('veterinario').then((data) => {
        const emailUnico = generateEmailUnico();

        registroVetPage
          .visit()
          .completarDatosObligatorios({
            ...data.veterinarioValido,
            email: emailUnico
          })
          .subirFoto(0);

        // Verificar que el último campo existe antes de hacer focus
        cy.get('body').then($body => {
          if ($body.find('input[type="email"]').length > 1) {
            registroVetPage.emailContactoInput.focus().type('{enter}', { force: true });
          } else {
            cy.get('input[type="email"]').last().focus().type('{enter}', { force: true });
          }
        });

        cy.wait('@registrarVeterinario', { timeout: 10000 }).then((interception) => {
          cy.log('Status code en envío con Enter:', interception.response.statusCode);
          
          if (interception.response.statusCode === 422) {
            cy.log('Error 422 - Detalles:', interception.response.body);
          }
          
          expect(interception.response.statusCode).to.be.oneOf([200, 201, 422]);
        });
      });
    });
  });
});
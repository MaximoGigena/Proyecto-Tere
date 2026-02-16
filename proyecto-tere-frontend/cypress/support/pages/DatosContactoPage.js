// cypress/support/pages/DatosContactoPage.js
class DatosContactoPage {
  constructor() {
    this.elementos = {
      overlay: '.fixed.inset-0.bg-black.bg-opacity-50',
      titulo: 'h3',
      // Teléfono: placeholder="Ej: +54 9 11 1234 5xxx"
      inputTelefono: 'input[placeholder*="Ej: +54"]',
      
      // Correo electrónico: placeholder="Ej: ejemplo@email.com"
      inputEmail: 'input[placeholder*="Ej: ejemplo"]',
      
      // DNI: placeholder="Ej: 45.208.xxx"
      inputDNI: 'input[placeholder*="Ej: 45.208"]',
      
      // Nombre Completo: placeholder="Ej: Juan Pepito"
      inputNombreCompleto: 'input[placeholder*="Ej: Juan"]',
      
      botonFinalizar: 'button:contains("Finalizar Registro")',
      botonOmitir: 'button:contains("Omitir contacto")',
      
      // CORREGIDO: Selector para el botón de Telegram (solo dice "Configurar" o "✅ Configurado")
      botonConfigurarTelegram: 'button:contains("Configurar")',
      
      // Alternativa más específica si hay múltiples botones "Configurar"
      botonTelegramEspecifico: '.col-span-full button:contains("Configurar")',
      
      // Para cuando ya está configurado
      botonTelegramConfigurado: 'button:contains("✅ Configurado")',
      
      // Elementos del modal de Telegram
      modalTelegram: '.fixed.inset-0.bg-black.bg-opacity-50.flex.items-center.justify-center.z-50',
      modalTelegramTitulo: 'h3:contains("Configurar Telegram")',
      codigoComando: 'code.font-mono',
      botonAbrirTelegram: 'button:contains("Abrir Telegram")',
      botonVerificarTelegram: 'button:contains("✅ Ya envié el comando")',
      botonCerrarModal: 'button:contains("Cerrar")'
    };
  }

  verificarOverlayVisible() {
    cy.get(this.elementos.overlay, { timeout: 10000 }).should('be.visible');
    cy.get(this.elementos.overlay)
      .find(this.elementos.titulo)
      .should('contain', 'Datos de Contacto');
    return this;
  }

  completarDatosContacto(datos) {
    if (datos.telefono) {
      cy.get(this.elementos.inputTelefono).type(datos.telefono);
    }
    if (datos.dni) {
      cy.get(this.elementos.inputDNI).type(datos.dni);
    }
    if (datos.nombreCompleto) {
      cy.get(this.elementos.inputNombreCompleto).type(datos.nombreCompleto);
    }
    if (datos.correoElectronico) {
      cy.get(this.elementos.inputEmail).type(datos.correoElectronico);
    }
    return this;
  }

  finalizarRegistro() {
    cy.get(this.elementos.botonFinalizar).click();
    cy.wait('@actualizarContacto', { timeout: 10000 });
    return this;
  }

  omitir() {
    cy.get(this.elementos.botonOmitir).click();
    cy.get(this.elementos.overlay, { timeout: 10000 }).should('not.exist');
    return this;
  }

  // CORREGIDO: Método para configurar Telegram
  configurarTelegram() {
    // Usar selector más específico primero, si falla intentar el general
    cy.get('body').then($body => {
      if ($body.find('.col-span-full button:contains("Configurar")').length > 0) {
        cy.get('.col-span-full button:contains("Configurar")').first().click();
      } else {
        cy.get(this.elementos.botonConfigurarTelegram).first().click();
      }
    });
    return this;
  }

  // CORREGIDO: Método para verificar modal de Telegram
  verificarModalTelegram(email) {
    // Verificar que el modal aparezca
    cy.get(this.elementos.modalTelegram, { timeout: 10000 }).should('be.visible');
    
    // Verificar el título
    cy.get(this.elementos.modalTelegramTitulo).should('be.visible');
    
    // Verificar que el código contenga el email
    if (email) {
      cy.get(this.elementos.codigoComando).should('contain', email);
    }
    
    // Verificar que todos los pasos estén presentes
    cy.contains('Paso 1: Comando listo').should('be.visible');
    cy.contains('Paso 2: Abre Telegram').should('be.visible');
    cy.contains('Paso 3: Pega y envía').should('be.visible');
    cy.contains('Paso 4: Verifica').should('be.visible');
    
    return this;
  }

  // Método adicional para abrir Telegram desde el modal
  abrirTelegramDesdeModal() {
    cy.get(this.elementos.botonAbrirTelegram).click();
    return this;
  }

  // Método adicional para verificar la configuración
  verificarTelegramConfigurado() {
    cy.get(this.elementos.botonTelegramConfigurado).should('be.visible');
    return this;
  }

  // Método para cerrar el modal sin configurar
  cerrarModalTelegram() {
    cy.get(this.elementos.botonCerrarModal).click();
    cy.get(this.elementos.modalTelegram, { timeout: 5000 }).should('not.exist');
    return this;
  }
}

export default DatosContactoPage;
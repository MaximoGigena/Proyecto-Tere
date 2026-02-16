// cypress/support/pages/RegistroPage.js
class RegistroPage {
  visit() {
    cy.visit('http://localhost:5173/registro/usuario');
    cy.log('📝 Página de registro visitada');
    return this;
  }

  completarFormularioObligatorio(usuario) {
    if (!usuario.fechaNacimiento) {
      throw new Error('fechaNacimiento es requerido en el objeto usuario');
    }

    cy.log('📝 Completando formulario...');
    
    cy.get('input[placeholder="Nombre del usuario"]').type(usuario.nombre);
    cy.get('input[placeholder="Email"]').type(usuario.email);
    
    // Contraseñas
    cy.get('input[type="password"]').eq(0).clear().type(usuario.password);
    cy.get('input[type="password"]').eq(1).clear().type(usuario.confirmPassword || usuario.password);
    
    // Verificar que las contraseñas coinciden en el frontend
    cy.get('input[type="password"]').eq(0).invoke('val').then(pass1 => {
      cy.get('input[type="password"]').eq(1).invoke('val').then(pass2 => {
        if (pass1 !== pass2) {
          cy.log('⚠️ Las contraseñas NO coinciden:', pass1, pass2);
        } else {
          cy.log('✅ Contraseñas coinciden');
        }
      });
    });
    
    // Fecha de nacimiento
    cy.get('input[placeholder="Día"]').clear().type(String(usuario.fechaNacimiento.dia));
    cy.get('select').select(String(usuario.fechaNacimiento.mes));
    cy.get('input[placeholder="Año"]').clear().type(String(usuario.fechaNacimiento.anio));
    
    return this;
  }

  subirFotoObligatoria(fotoPath = 'cypress/fixtures/judge-holden.jpg') {
    cy.log('📸 Subiendo foto...');
    
    // Hacer clic en el contenedor de la primera foto
    cy.get('.grid-cols-2.md\\:grid-cols-3.gap-4 > div').first().click({ force: true });
    cy.wait(500);
    
    // Subir archivo
    cy.get('input[type="file"]').first().selectFile(fotoPath, {
      force: true,
      action: 'drag-drop'
    });
    
    cy.wait(1000);
    return this;
  }

  verificarFotoSubida() {
    cy.get('.grid-cols-2.md\\:grid-cols-3.gap-4 img', { timeout: 10000 })
      .should('exist')
      .and('be.visible');
    cy.log('✅ Foto verificada');
    return this;
  }

  enviarFormulario() {
    cy.log('🚀 Enviando formulario...');
    
    // Verificar estado antes de enviar
    cy.get('input[type="password"]').eq(0).invoke('val').then(pass1 => {
      cy.get('input[type="password"]').eq(1).invoke('val').then(pass2 => {
        if (pass1 !== pass2) {
          cy.log('❌ ERROR: Las contraseñas NO coinciden antes de enviar');
          // Aquí podría haber una alerta
        }
      });
    });
    
    cy.get('button[type="submit"]').click();
    cy.log('⏳ Esperando respuesta...');
    return this;
  }

  verificarModalConfirmacion() {
    cy.log('🔍 Verificando modal de confirmación...');
    
    // Primero verificar si hay algún modal en la página
    cy.document().then(doc => {
      const modals = doc.querySelectorAll('[role="dialog"], .modal, [data-testid="modal-confirmacion"]');
      cy.log(`📊 Modales encontrados: ${modals.length}`);
    });
    
    // Buscar el modal específico
    cy.get('[data-testid="modal-confirmacion"]', { timeout: 20000 })
      .should('be.visible');
    
    cy.log('✅ Modal encontrado');
    return this;
  }

  // En RegistroPage.js - Modificar confirmarRegistro
  confirmarRegistro(alias = '@registrarUsuario') { // Parámetro opcional
    cy.log('✅ Confirmando registro...');
    
    cy.get('[data-testid="modal-confirmacion"] button:contains("Registrar")')
      .should('be.visible')
      .click();
    
    // Usar el alias proporcionado
    cy.wait(alias, { timeout: 15000 }).then(interception => {
      cy.log('📡 Respuesta del servidor:', interception.response.statusCode);
    });
    
    cy.get('[data-testid="modal-confirmacion"]', { timeout: 15000 })
      .should('not.exist');
    
    return this;
  }
}

export default RegistroPage;
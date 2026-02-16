// DatosOpcionalesPage.js - Selectores corregidos
class DatosOpcionalesPage {
  elementos = {
    overlay: '.fixed.inset-0.bg-black.bg-opacity-50',
    titulo: '.fixed.inset-0.bg-black.bg-opacity-50 h3',
    ocupacionInput: 'input[placeholder*="Ej: Estudiante"]',
    botonContinuar: 'button:contains("Continuar con Contacto")',
    botonOmitir: 'button:contains("Continuar con Contacto")' // Cambiado
  }

  omitir() {
    cy.get(this.elementos.overlay, { timeout: 10000 }).within(() => {
      cy.contains('button', 'Continuar con Contacto')
        .should('be.visible')
        .click();
    });
    return this;
  }

  guardarYOmitir() {
    cy.get(this.elementos.overlay).within(() => {
      cy.contains('button', 'Continuar con Contacto')
        .should('be.visible')
        .click();
    });
    return this;
  }

  verificarOverlayVisible() {
    // ✅ CORREGIDO: Verificación más robusta
    cy.get(this.elementos.overlay, { timeout: 10000 })
      .should('be.visible');
    
    // Verificar que el overlay contiene el título correcto
    cy.get(this.elementos.overlay)
      .find('h3')
      .should('be.visible')
      .and('contain', 'Datos Opcionales');
    
    return this;
  }

  completarDatosOpcionales(datos) {
    // Agregar más tiempo de espera y mejores verificaciones
    cy.get(this.elementos.overlay, { timeout: 10000 }).should('be.visible');
    
    // Usar within para mantener el contexto del overlay
    cy.get(this.elementos.overlay).within(() => {
      cy.get('input[placeholder*="Ej: Estudiante"]', { timeout: 10000 })
        .should('be.visible')
        .type(datos.ocupacion);
      
      cy.get('select:has(option[value="departamento"])')
        .should('be.visible')
        .select(datos.tipoVivienda);
      
      cy.get('select:has(option[value="nueva"])')
        .should('be.visible')
        .select(datos.experienciaMascotas);
      
      cy.get('select:has(option[value="si"])').eq(0)
        .should('be.visible')
        .select(datos.conviveConNiños);
      
      cy.get('select:has(option[value="si"])').eq(1)
        .should('be.visible')
        .select(datos.conviveConMascotas);
      
      cy.get('textarea[placeholder*="Contanos más"]')
        .should('be.visible')
        .type(datos.descripcion);
    });
    
    return this;
  }
}

export default DatosOpcionalesPage;
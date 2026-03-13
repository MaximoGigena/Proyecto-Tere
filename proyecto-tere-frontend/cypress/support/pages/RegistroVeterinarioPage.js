// support/pages/RegistroVeterinarioPage.js
class RegistroVeterinarioPage {
  // Selectores mejorados con múltiples opciones
  get nombreInput() { 
    return cy.get('input[placeholder*="nombre" i], input[placeholder*="name" i], input[type="text"]').first(); 
  }
  
  get emailInput() { 
    return cy.get('input[type="email"]').first(); 
  }
  
  get matriculaInput() { 
    return cy.get('input[placeholder*="matrícula" i], input[placeholder*="license" i], input[type="text"]').eq(1); 
  }
  
  get especialidadInput() { 
    return cy.get('input[placeholder*="especialidad" i], input[placeholder*="specialty" i], input[type="text"]').eq(2); 
  }
  
  get experienciaInput() { 
    // Separa los selectores en lugar de combinarlos
    return cy.get('body').then($body => {
      if ($body.find('input[type="number"]').length) {
        return cy.get('input[type="number"]').first();
      } else {
        return cy.get('input[placeholder*="años"], input[placeholder*="years"]').first();
      }
    });
  }
  
  get descripcionTextarea() { 
    return cy.get('textarea[placeholder*="descripción" i], textarea[placeholder*="description" i], textarea[maxlength], textarea').first(); 
  }
  
  getTelefonoInput() {
    return cy.get('body').then($body => {
      if ($body.find('input[type="tel"]').length) {
        return cy.get('input[type="tel"]').first();
      } else if ($body.find('input[placeholder*="teléfono"]').length > 0) {
        // Usar filter en lugar de selector con [i]
        return cy.get('input[placeholder*="teléfono"]').first();
      } else if ($body.find('input[placeholder*="phone"]').length > 0) {
        return cy.get('input[placeholder*="phone"]').first();
      } else if ($body.find('input[placeholder*="telefono"]').length > 0) {
        return cy.get('input[placeholder*="telefono"]').first();
      }
      return cy.get('input[type="text"]').eq(4); // fallback
    });
  }
  
  
  get emailContactoInput() { 
    return cy.get('input[type="email"]').last(); 
  }

  get fotoContainers() {
    return cy.get('[class*="border-dashed"], [class*="upload"], .foto-container, [data-testid*="foto"]');
  }

  get submitButton() {
    return cy.get('button[type="submit"]').first();
  }

  visit() {
    cy.visit('http://localhost:5173/registro/veterinario');
    // Esperar a que el formulario cargue
    cy.get('form, .form, [role="form"]').should('exist');
    return this;
  }

  completarDatosObligatorios(data) {
    cy.log('Completando datos obligatorios', data);
    
    this.nombreInput.clear({ force: true }).type(data.nombre, { force: true });
    this.emailInput.clear({ force: true }).type(data.email, { force: true });
    this.matriculaInput.clear({ force: true }).type(data.matricula, { force: true });
    
    // Especialidad - solo si existe en data
    if (data.especialidad) {
      this.especialidadInput.clear({ force: true }).type(data.especialidad, { force: true });
    }
    
    // Experiencia
    if (data.experiencia !== undefined && data.experiencia !== null) {
      cy.get('body').then($body => {
        if ($body.find('input[type="number"]').length > 0) {
          cy.get('input[type="number"]').first().clear({ force: true }).type(String(data.experiencia), { force: true });
        } else if ($body.find('input[placeholder*="años"], input[placeholder*="years"]').length > 0) {
          cy.get('input[placeholder*="años"], input[placeholder*="years"]').first().clear({ force: true }).type(String(data.experiencia), { force: true });
        }
      });
    }
    
    // Descripción
    if (data.descripcion) {
      cy.get('body').then($body => {
        if ($body.find('textarea').length > 0) {
          this.descripcionTextarea.clear({ force: true }).type(data.descripcion, { force: true, delay: 0 });
        }
      });
    }
    
    // Teléfono
    if (data.telefono) {
      cy.get('body').then($body => {
        if ($body.find('input[type="tel"], input[placeholder*="teléfono"], input[placeholder*="phone"], input[placeholder*="telefono"]').length > 0) {
          this.getTelefonoInput().clear({ force: true }).type(data.telefono, { force: true });
        }
      });
    }
    
    // Email de contacto
    if (data.emailContacto) {
      cy.get('body').then($body => {
        if ($body.find('input[type="email"]').length > 1) {
          this.emailContactoInput.clear({ force: true }).type(data.emailContacto, { force: true });
        }
      });
    }
    
    return this;
  }

  get telefonoInput() {
    return this.getTelefonoInput();
  }

  completarFormularioCompleto(data) {
    return this.completarDatosObligatorios(data);
  }

  subirFoto(index = 0) {
    cy.log(`Subiendo foto en índice ${index}`);
    
    // Estrategia: intentar diferentes métodos para subir foto
    cy.get('body').then($body => {
      // Método 1: Click en contenedor dashed
      if ($body.find('[class*="border-dashed"]').length > 0) {
        cy.get('[class*="border-dashed"]').eq(index).click({ force: true });
        cy.wait(500);
        cy.get('input[type="file"]').eq(index).selectFile('cypress/fixtures/judge-holden.jpg', { force: true });
      } 
      // Método 2: Input file directo
      else if ($body.find('input[type="file"]').length > 0) {
        cy.get('input[type="file"]').eq(index).selectFile('cypress/fixtures/judge-holden.jpg', { force: true });
      }
      // Método 3: Botón de upload
      else if ($body.find('button:contains("subir"), button:contains("upload")').length > 0) {
        cy.get('button:contains("subir"), button:contains("upload")').eq(index).click({ force: true });
        cy.wait(500);
        cy.get('input[type="file"]').last().selectFile('cypress/fixtures/judge-holden.jpg', { force: true });
      }
    });
    
    return this;
  }

  subirMultiplesFotos(cantidad = 3) {
    for (let i = 0; i < cantidad; i++) {
      this.subirFoto(i);
      this.verificarFotoSubida(i);
    }
    return this;
  }

  quitarFoto(index = 0) {
    cy.log(`Quitando foto en índice ${index}`);
    
    cy.get('body').then($body => {
      // Buscar botón de eliminar en el contenedor de la foto
      if ($body.find('[class*="border-dashed"]').length > 0) {
        cy.get('[class*="border-dashed"]').eq(index).find('button, [role="button"], .eliminar, .remove').click({ force: true });
      } else {
        cy.get('button:contains("eliminar"), button:contains("quitar"), button:contains("remove")').eq(index).click({ force: true });
      }
    });
    
    return this;
  }

  verificarFotoSubida(index = 0) {
    cy.log(`Verificando foto subida en índice ${index}`);
    
    cy.get('body').then($body => {
      if ($body.find('[class*="border-dashed"]').length > 0) {
        cy.get('[class*="border-dashed"]').eq(index).within(() => {
          cy.get('img').should('exist');
        });
      } else {
        cy.get('img[src*="blob"], img[src*="data:image"]').should('exist');
      }
    });
    
    return this;
  }

  enviarFormulario() {
    cy.log('Enviando formulario');
    this.submitButton.click({ force: true });
    return this;
  }

  verificarLoading() {
    cy.log('Verificando estado de loading');
    // Usar el texto exacto de tu componente
    cy.contains('button', 'Procesando...').should('be.visible');
    return this;
  }

  verificarLoadingOculto() {
    cy.log('Verificando que loading haya desaparecido');
    cy.get('button:contains("Procesando"), button:contains("Cargando"), button:contains("Loading")').should('not.exist');
    return this;
  }

  verificarError(mensaje) {
    cy.log(`Verificando mensaje de error: ${mensaje}`);
    
    // Primero, hacer scroll para asegurar visibilidad
    cy.get('[class*="error"], [class*="alert"], .bg-red-100, .text-red-600, [role="alert"]')
      .scrollIntoView()
      .should('be.visible')
      .and('contain', mensaje);
    
    return this;
  }

  verificarLimiteCaracteres(textoLargo) {
    cy.log('Verificando límite de caracteres');
    
    cy.get('body').then($body => {
      if ($body.find('textarea[maxlength]').length > 0) {
        // Si tiene maxlength, verificar que se trunque
        cy.get('textarea[maxlength]').type(textoLargo, { delay: 0 });
        cy.get('textarea[maxlength]').invoke('val').then(val => {
          const maxlength = parseInt($body.find('textarea[maxlength]').attr('maxlength') || '500');
          expect(val.length).to.be.at.most(maxlength);
        });
      } else {
        // Si no tiene maxlength, verificar que aparezca un mensaje de error
        cy.get('textarea').first().type(textoLargo, { delay: 0 });
        cy.contains('límite', 'máximo', 'excede').should('be.visible');
      }
    });
    
    return this;
  }

  verificarContadorCaracteres(texto) {
    cy.log('Verificando contador de caracteres');
    
    this.descripcionTextarea.clear().type(texto, { delay: 0 });
    
    // Tu componente usa este formato específico
    cy.contains(`${texto.length}/500 caracteres`).should('be.visible');
    
    return this;
  }
}

export default RegistroVeterinarioPage;
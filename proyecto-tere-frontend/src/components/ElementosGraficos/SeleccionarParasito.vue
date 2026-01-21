<!-- SeleccionarParasito.vue -->
<template>
  <div class="parasites-tree-container">
    <!-- Título -->
    <div class="mb-4">
      <label class="block font-medium text-gray-700 mb-2">Parásitos tratados</label>
      <p class="text-sm text-gray-500 mb-3">
        Seleccione los tipos de parásitos tratados en la desparasitación
      </p>
    </div>

    <!-- Árbol de parásitos -->
    <div class="parasites-tree bg-white">
      <!-- Parasitos Internos -->
      <div class="parasite-category mb-6">
        <div
          class="category-header p-3 rounded-md cursor-pointer flex items-center justify-between border-l-4 transition-all duration-200"
          :class="internalParasites.selected
            ? 'bg-blue-50 border-blue-500'
            : 'bg-gray-50 border-gray-300 hover:bg-gray-100'
          "
          @click="toggleCategory(internalParasites)"
        >
          <div class="flex items-center">
            <span class="mr-3 w-4 text-center text-gray-600">
              {{ internalParasites.expanded ? '▼' : '►' }}
            </span>
            <span class="font-semibold text-gray-800">
              {{ internalParasites.name }}
            </span>
          </div>
          <div class="flex items-center">
            <span class="text-sm font-medium mr-2"
                  :class="internalParasites.selected ? 'text-blue-600' : 'text-gray-500'">
              {{ getCategoryStatus(internalParasites) }}
            </span>
            <span class="text-blue-600 font-bold">
              {{ getCategoryCheckmark(internalParasites) }}
            </span>
          </div>
        </div>

        <!-- Items de parásitos internos -->
        <div
          v-if="internalParasites.expanded"
          class="category-items ml-8 pl-4 border-l-2 border-dashed border-gray-300 mt-3 mb-2"
        >
          <!-- Parásitos predefinidos -->
          <div
            v-for="parasite in internalParasites.parasites"
            :key="parasite.id"
            class="parasite-item p-2 rounded-md cursor-pointer flex items-center transition-colors duration-150 mb-1"
            :class="parasite.selected
              ? 'bg-blue-100 border border-blue-200'
              : 'hover:bg-gray-50'
            "
            @click="toggleParasite(internalParasites, parasite)"
          >
            <span class="mr-3 text-lg">
              {{ parasite.selected ? '☑' : '☐' }}
            </span>
            <span class="text-gray-700 flex-1">
              {{ parasite.name }}
            </span>
            <span v-if="parasite.description" class="text-xs text-gray-500 ml-2">
              {{ parasite.description }}
            </span>
          </div>

          <!-- Campo "Otro" para internos -->
          <div class="other-section mt-3 pt-3 border-t border-gray-200">
            <div
              class="other-item p-2 rounded-md cursor-pointer flex items-center transition-colors duration-150 mb-2"
              :class="internalParasites.otherSelected
                ? 'bg-blue-100 border border-blue-200'
                : 'hover:bg-gray-50'
              "
              @click="toggleOtherParasite('internal')"
            >
              <span class="mr-3 text-lg">
                {{ internalParasites.otherSelected ? '☑' : '☐' }}
              </span>
              <span class="text-gray-700 flex-1 font-medium">
                Otros parásitos internos
              </span>
              <span class="text-xs text-gray-500 ml-2">
                Especifique otros no listados
              </span>
            </div>

            <!-- Campo de texto para otros internos -->
            <div
              v-if="internalParasites.otherSelected"
              class="other-text-field ml-6 mt-2 mb-3"
            >
              <input
                v-model="internalParasites.otherText"
                type="text"
                class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                placeholder="Ejemplo: Trichinella, Echinococcus, etc."
                @input="updateOtherText('internal')"
              />
              <p class="text-xs text-gray-500 mt-1 ml-1">
                Especifique otros parásitos internos no listados
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Parasitos Externos -->
      <div class="parasite-category">
        <div
          class="category-header p-3 rounded-md cursor-pointer flex items-center justify-between border-l-4 transition-all duration-200"
          :class="externalParasites.selected
            ? 'bg-green-50 border-green-500'
            : 'bg-gray-50 border-gray-300 hover:bg-gray-100'
          "
          @click="toggleCategory(externalParasites)"
        >
          <div class="flex items-center">
            <span class="mr-3 w-4 text-center text-gray-600">
              {{ externalParasites.expanded ? '▼' : '►' }}
            </span>
            <span class="font-semibold text-gray-800">
              {{ externalParasites.name }}
            </span>
          </div>
          <div class="flex items-center">
            <span class="text-sm font-medium mr-2"
                  :class="externalParasites.selected ? 'text-green-600' : 'text-gray-500'">
              {{ getCategoryStatus(externalParasites) }}
            </span>
            <span class="text-green-600 font-bold">
              {{ getCategoryCheckmark(externalParasites) }}
            </span>
          </div>
        </div>

        <!-- Items de parásitos externos -->
        <div
          v-if="externalParasites.expanded"
          class="category-items ml-8 pl-4 border-l-2 border-dashed border-gray-300 mt-3 mb-2"
        >
          <!-- Parásitos predefinidos -->
          <div
            v-for="parasite in externalParasites.parasites"
            :key="parasite.id"
            class="parasite-item p-2 rounded-md cursor-pointer flex items-center transition-colors duration-150 mb-1"
            :class="parasite.selected
              ? 'bg-green-100 border border-green-200'
              : 'hover:bg-gray-50'
            "
            @click="toggleParasite(externalParasites, parasite)"
          >
            <span class="mr-3 text-lg">
              {{ parasite.selected ? '☑' : '☐' }}
            </span>
            <span class="text-gray-700 flex-1">
              {{ parasite.name }}
            </span>
            <span v-if="parasite.description" class="text-xs text-gray-500 ml-2">
              {{ parasite.description }}
            </span>
          </div>

          <!-- Campo "Otro" para externos -->
          <div class="other-section mt-3 pt-3 border-t border-gray-200">
            <div
              class="other-item p-2 rounded-md cursor-pointer flex items-center transition-colors duration-150 mb-2"
              :class="externalParasites.otherSelected
                ? 'bg-green-100 border border-green-200'
                : 'hover:bg-gray-50'
              "
              @click="toggleOtherParasite('external')"
            >
              <span class="mr-3 text-lg">
                {{ externalParasites.otherSelected ? '☑' : '☐' }}
              </span>
              <span class="text-gray-700 flex-1 font-medium">
                Otros parásitos externos
              </span>
              <span class="text-xs text-gray-500 ml-2">
                Especifique otros no listados
              </span>
            </div>

            <!-- Campo de texto para otros externos -->
            <div
              v-if="externalParasites.otherSelected"
              class="other-text-field ml-6 mt-2"
            >
              <input
                v-model="externalParasites.otherText"
                type="text"
                class="w-full border border-gray-300 rounded-md p-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                placeholder="Ejemplo: Chinches, tábanos, etc."
                @input="updateOtherText('external')"
              />
              <p class="text-xs text-gray-500 mt-1 ml-1">
                Especifique otros parásitos externos no listados
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ParasitesTreeSelector',
  props: {
    // Para cargar datos iniciales
    initialSelection: {
      type: Object,
      default: () => ({})
    },
    // Para controlar si se muestra el resumen
    showSummary: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      // Categoría de parásitos internos
      internalParasites: {
        id: 'internal',
        name: 'Parásitos Internos',
        expanded: false,
        selected: false,
        otherSelected: false,
        otherText: '',
        parasites: [
          { 
            id: 'ascaris', 
            name: 'Ascáridos', 
            selected: false,
            description: 'Toxocara, Toxascaris'
          },
          { 
            id: 'tenias', 
            name: 'Tenias', 
            selected: false,
            description: 'Dipylidium, Taenia'
          },
          { 
            id: 'tricuridos', 
            name: 'Tricúridos', 
            selected: false,
            description: 'Trichuris vulpis'
          },
          { 
            id: 'ancilostomas', 
            name: 'Ancilostomas', 
            selected: false,
            description: 'Ancylostoma, Uncinaria'
          },
          { 
            id: 'dirofilaria', 
            name: 'Dirofilaria', 
            selected: false,
            description: 'Gusano del corazón'
          },
          { 
            id: 'coccidios', 
            name: 'Coccidios', 
            selected: false,
            description: 'Isospora, Eimeria'
          },
          { 
            id: 'giardia', 
            name: 'Giardia', 
            selected: false,
            description: 'Giardia lamblia'
          }
        ]
      },
      
      // Categoría de parásitos externos
      externalParasites: {
        id: 'external',
        name: 'Parásitos Externos',
        expanded: false,
        selected: false,
        otherSelected: false,
        otherText: '',
        parasites: [
          { 
            id: 'pulgas', 
            name: 'Pulgas', 
            selected: false,
            description: 'Ctenocephalides spp.'
          },
          { 
            id: 'garrapatas', 
            name: 'Garrapatas', 
            selected: false,
            description: 'Rhipicephalus, Ixodes'
          },
          { 
            id: 'acaros_piel', 
            name: 'Ácaros de la piel', 
            selected: false,
            description: 'Sarna sarcóptica, demodéctica'
          },
          { 
            id: 'acaros_oido', 
            name: 'Ácaros del oído', 
            selected: false,
            description: 'Otodectes cynotis'
          },
          { 
            id: 'piojos', 
            name: 'Piojos', 
            selected: false,
            description: 'Trichodectes, Linognathus'
          },
          { 
            id: 'mosquitos', 
            name: 'Mosquitos', 
            selected: false,
            description: 'Prevención y repelencia'
          },
          { 
            id: 'moscas', 
            name: 'Moscas', 
            selected: false,
            description: 'Estridas, mosca de la arena'
          }
        ]
      }
    }
  },
  computed: {
    // Verificar si hay selección en internos
    hasInternalSelection() {
      return this.internalParasites.parasites.some(p => p.selected) || 
             (this.internalParasites.otherSelected && this.internalParasites.otherText.trim().length > 0);
    },
    
    // Verificar si hay selección en externos
    hasExternalSelection() {
      return this.externalParasites.parasites.some(p => p.selected) || 
             (this.externalParasites.otherSelected && this.externalParasites.otherText.trim().length > 0);
    },
    
    // Texto con la selección completa
    selectedParasitesText() {
      const parts = [];
      
      if (this.hasInternalSelection) {
        parts.push(`Internos: ${this.getInternalSelectionText()}`);
      }
      
      if (this.hasExternalSelection) {
        parts.push(`Externos: ${this.getExternalSelectionText()}`);
      }
      
      if (parts.length === 0) {
        return 'Ningún parásito seleccionado';
      }
      
      return parts.join('; ');
    },
    
    // Obtener todos los parásitos seleccionados
    selectedParasitesList() {
      const selected = [];
      
      // Parásitos internos predefinidos
      this.internalParasites.parasites.forEach(parasite => {
        if (parasite.selected) {
          selected.push({
            id: parasite.id,
            name: parasite.name,
            category: 'internal',
            description: parasite.description,
            type: 'predefined'
          });
        }
      });
      
      // Otros parásitos internos
      if (this.internalParasites.otherSelected && this.internalParasites.otherText.trim()) {
        selected.push({
          id: 'internal_other',
          name: this.internalParasites.otherText,
          category: 'internal',
          description: 'Otros parásitos internos',
          type: 'other'
        });
      }
      
      // Parásitos externos predefinidos
      this.externalParasites.parasites.forEach(parasite => {
        if (parasite.selected) {
          selected.push({
            id: parasite.id,
            name: parasite.name,
            category: 'external',
            description: parasite.description,
            type: 'predefined'
          });
        }
      });
      
      // Otros parásitos externos
      if (this.externalParasites.otherSelected && this.externalParasites.otherText.trim()) {
        selected.push({
          id: 'external_other',
          name: this.externalParasites.otherText,
          category: 'external',
          description: 'Otros parásitos externos',
          type: 'other'
        });
      }
      
      return selected;
    },
    
    // Para compatibilidad con v-model
    value: {
      get() {
        const result = {
          internal: {
            predefined: this.internalParasites.parasites
              .filter(p => p.selected)
              .map(p => ({ id: p.id, name: p.name })),
            other: this.internalParasites.otherSelected ? this.internalParasites.otherText : ''
          },
          external: {
            predefined: this.externalParasites.parasites
              .filter(p => p.selected)
              .map(p => ({ id: p.id, name: p.name })),
            other: this.externalParasites.otherSelected ? this.externalParasites.otherText : ''
          }
        };
        return result;
      },
      set(newValue) {
        if (newValue) {
          this.loadInitialData(newValue);
        }
      }
    }
  },
  watch: {
    // Observar cambios en la selección inicial
    initialSelection: {
      handler(newSelection) {
        if (newSelection && typeof newSelection === 'object') {
          this.loadInitialData(newSelection);
        }
      },
      immediate: true,
      deep: true
    },
    
    // Emitir cambios cuando se modifica la selección
    selectedParasitesList: {
      handler() {
        this.emitSelection();
      },
      deep: true
    }
  },
  mounted() {
    // Expandir ambas categorías por defecto
    this.internalParasites.expanded = false;
    this.externalParasites.expanded = false;
  },
  methods: {
    // Alternar expansión de categoría
    toggleCategory(category) {
      category.expanded = !category.expanded;
    },
    
    // Seleccionar/deseleccionar un parásito predefinido
    toggleParasite(category, parasite) {
      parasite.selected = !parasite.selected;
      this.updateCategorySelection(category);
    },
    
    // Alternar selección de "Otro" en una categoría
    toggleOtherParasite(categoryType) {
      const category = categoryType === 'internal' ? this.internalParasites : this.externalParasites;
      category.otherSelected = !category.otherSelected;
      
      // Si se deselecciona, limpiar el texto
      if (!category.otherSelected) {
        category.otherText = '';
      }
      
      this.updateCategorySelection(category);
      this.emitSelection();
    },
    
    // Actualizar estado de la categoría basado en sus parásitos
    updateCategorySelection(category) {
      const selectedPredefined = category.parasites.filter(p => p.selected).length;
      const hasOther = category.otherSelected && category.otherText.trim().length > 0;
      
      if (selectedPredefined === 0 && !hasOther) {
        category.selected = false;
      } else if (selectedPredefined === category.parasites.length && hasOther) {
        category.selected = true;
      } else {
        category.selected = true; // Parcialmente seleccionado
      }
    },
    
    // Obtener texto de estado de la categoría
    getCategoryStatus(category) {
      const selectedPredefined = category.parasites.filter(p => p.selected).length;
      const hasOther = category.otherSelected && category.otherText.trim().length > 0;
      
      if (selectedPredefined === 0 && !hasOther) {
        return '';
      }
      
      const parts = [];
      if (selectedPredefined > 0) {
        parts.push(`${selectedPredefined} de ${category.parasites.length}`);
      }
      if (hasOther) {
        parts.push('Otro');
      }
      
      return parts.join(' + ');
    },
    
    // Obtener checkmark de la categoría
    getCategoryCheckmark(category) {
      const selectedPredefined = category.parasites.filter(p => p.selected).length;
      const hasOther = category.otherSelected && category.otherText.trim().length > 0;
      
      if (selectedPredefined === 0 && !hasOther) {
        return '';
      } else if (selectedPredefined === category.parasites.length && hasOther) {
        return '✓';
      } else {
        return '•';
      }
    },
    
    // Obtener texto de selección para internos
    getInternalSelectionText() {
      const selectedNames = this.internalParasites.parasites
        .filter(p => p.selected)
        .map(p => p.name);
      
      if (this.internalParasites.otherSelected && this.internalParasites.otherText.trim()) {
        selectedNames.push(`Otro: ${this.internalParasites.otherText}`);
      }
      
      return selectedNames.length > 0 ? selectedNames.join(', ') : 'Ninguno';
    },
    
    // Obtener texto de selección para externos
    getExternalSelectionText() {
      const selectedNames = this.externalParasites.parasites
        .filter(p => p.selected)
        .map(p => p.name);
      
      if (this.externalParasites.otherSelected && this.externalParasites.otherText.trim()) {
        selectedNames.push(`Otro: ${this.externalParasites.otherText}`);
      }
      
      return selectedNames.length > 0 ? selectedNames.join(', ') : 'Ninguno';
    },
    
    // Actualizar texto de "Otro"
    updateOtherText(categoryType) {
      const category = categoryType === 'internal' ? this.internalParasites : this.externalParasites;
      
      // Si hay texto, asegurar que esté seleccionado
      if (category.otherText.trim().length > 0 && !category.otherSelected) {
        category.otherSelected = true;
      }
      
      // Si no hay texto y está seleccionado, mantener seleccionado pero vacío
      this.updateCategorySelection(category);
      this.emitSelection();
    },
    
    // Cargar datos iniciales
    loadInitialData(data) {
      // Limpiar selecciones previas
      this.resetCategory(this.internalParasites);
      this.resetCategory(this.externalParasites);
      
      // Cargar datos para internos
      if (data.internal) {
        // Parásitos predefinidos
        if (data.internal.predefined && Array.isArray(data.internal.predefined)) {
          data.internal.predefined.forEach(parasiteData => {
            const parasite = this.internalParasites.parasites.find(p => p.id === parasiteData.id);
            if (parasite) {
              parasite.selected = true;
            }
          });
        }
        
        // Otros parásitos
        if (data.internal.other) {
          this.internalParasites.otherSelected = true;
          this.internalParasites.otherText = data.internal.other;
        }
      }
      
      // Cargar datos para externos
      if (data.external) {
        // Parásitos predefinidos
        if (data.external.predefined && Array.isArray(data.external.predefined)) {
          data.external.predefined.forEach(parasiteData => {
            const parasite = this.externalParasites.parasites.find(p => p.id === parasiteData.id);
            if (parasite) {
              parasite.selected = true;
            }
          });
        }
        
        // Otros parásitos
        if (data.external.other) {
          this.externalParasites.otherSelected = true;
          this.externalParasites.otherText = data.external.other;
        }
      }
      
      // Actualizar estados de las categorías
      this.updateCategorySelection(this.internalParasites);
      this.updateCategorySelection(this.externalParasites);
    },
    
    // Resetear una categoría
    resetCategory(category) {
      category.parasites.forEach(p => p.selected = false);
      category.otherSelected = false;
      category.otherText = '';
      category.selected = false;
    },
    
    // Emitir evento con la selección actual
    emitSelection() {
      const selectedData = {
        internal: {
          predefined: this.internalParasites.parasites
            .filter(p => p.selected)
            .map(p => ({ id: p.id, name: p.name })),
          other: this.internalParasites.otherSelected ? this.internalParasites.otherText : '',
          summary: this.getInternalSelectionText()
        },
        external: {
          predefined: this.externalParasites.parasites
            .filter(p => p.selected)
            .map(p => ({ id: p.id, name: p.name })),
          other: this.externalParasites.otherSelected ? this.externalParasites.otherText : '',
          summary: this.getExternalSelectionText()
        },
        fullSummary: this.selectedParasitesText,
        allSelected: this.selectedParasitesList
      };
      
      this.$emit('selection-change', selectedData);
      this.$emit('input', this.value); // Para soporte v-model
    },
    
    // Método para obtener datos (puede ser llamado desde el componente padre)
    getSelection() {
      return {
        internal: {
          predefined: this.internalParasites.parasites
            .filter(p => p.selected)
            .map(p => ({ id: p.id, name: p.name })),
          other: this.internalParasites.otherSelected ? this.internalParasites.otherText : '',
          summary: this.getInternalSelectionText()
        },
        external: {
          predefined: this.externalParasites.parasites
            .filter(p => p.selected)
            .map(p => ({ id: p.id, name: p.name })),
          other: this.externalParasites.otherSelected ? this.externalParasites.otherText : '',
          summary: this.getExternalSelectionText()
        },
        fullSummary: this.selectedParasitesText,
        allSelected: this.selectedParasitesList
      };
    },
    
    // Método para resetear toda la selección
    resetSelection() {
      this.resetCategory(this.internalParasites);
      this.resetCategory(this.externalParasites);
      this.emitSelection();
    },
    
    // Método para seleccionar todos los parásitos de una categoría (sin "otros")
    selectAllInCategory(categoryId) {
      const category = categoryId === 'internal' ? this.internalParasites : this.externalParasites;
      category.parasites.forEach(parasite => {
        parasite.selected = true;
      });
      category.selected = true;
      this.emitSelection();
    },
    
    // Método para deseleccionar todos los parásitos de una categoría
    deselectAllInCategory(categoryId) {
      const category = categoryId === 'internal' ? this.internalParasites : this.externalParasites;
      this.resetCategory(category);
      this.emitSelection();
    }
  }
}
</script>

<style scoped>
.parasites-tree-container {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.category-header {
  transition: all 0.2s ease;
  user-select: none;
}

.category-header:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.parasite-item, .other-item {
  transition: all 0.15s ease;
}

.parasite-item:hover, .other-item:hover {
  transform: translateX(2px);
}

.other-section {
  border-top-color: rgba(0, 0, 0, 0.1);
}

.other-text-field input {
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.selection-summary {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-5px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
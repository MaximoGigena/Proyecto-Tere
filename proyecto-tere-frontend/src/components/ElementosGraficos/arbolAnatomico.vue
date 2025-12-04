<template>
  <div class="max-w-[1200px] mx-auto p-5 bg-white rounded-lg font-sans">
    <!-- Header -->
    <div class="text-center mb-6 pb-4 border-b border-gray-300">
      <p class="text-gray-600 text-lg">
        Seleccione manualmente los elementos del examen clínico
      </p>
    </div>

    <!-- Árbol -->
    <div class="flex flex-wrap gap-8">
      <div class="flex-1 min-w-[300px] bg-white rounded-lg p-4">

        <div v-for="system in clinicalSystems" :key="system.id" class="mb-2">

          <!-- Header nodo -->
          <div
            class="p-3 rounded-md cursor-pointer flex items-center border-l-4 transition user-select-none"
            :class="system.selected
              ? 'bg-cyan-100 border-green-500'
              : 'bg-gray-200 border-cyan-500 hover:bg-gray-300'
            "
            @click="toggleSystem(system)"
          >
            <span class="mr-2 w-4 text-center">
              {{ system.expanded ? '▼' : '►' }}
            </span>

            <span class="font-semibold text-gray-700 flex-1">
              {{ system.name }}
            </span>

            <span class="font-bold text-green-600">
              {{ getSystemSelectionStatus(system) }}
            </span>
          </div>

          <!-- Items -->
          <div
            v-if="system.expanded"
            class="ml-4 pl-4 border-l-2 border-dashed border-gray-400 mt-2 mb-3"
          >
            <div
              v-for="item in system.items"
              :key="item.id"
              @click="toggleItem(system, item)"
              class="p-2 pl-4 rounded-md cursor-pointer flex items-center transition"
              :class="item.selected
                ? 'bg-green-100 font-semibold'
                : 'hover:bg-gray-100'
              "
            >
              <span class="mr-2">
                {{ item.selected ? '☑' : '☐' }}
              </span>

              <span class="text-gray-700">
                {{ item.name }}
              </span>
            </div>
          </div>

        </div>

      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ClinicalExaminationTree',
  props: {
    initialData: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      clinicalSystems: [
        {
          id: 1,
          name: '1. Sistema Respiratorio',
          expanded: false,
          selected: false,
          items: [
            { id: 101, name: 'Cavidad nasal', selected: false },
            { id: 102, name: 'Tráquea', selected: false },
            { id: 103, name: 'Bronquios', selected: false },
            { id: 104, name: 'Pulmones', selected: false },
            { id: 105, name: 'Tos / sonidos respiratorios', selected: false },
            { id: 106, name: 'Oxigenación', selected: false }
          ]
        },
        {
          id: 2,
          name: '2. Sistema Cardiovascular',
          expanded: false,
          selected: false,
          items: [
            { id: 201, name: 'Ritmo cardíaco', selected: false },
            { id: 202, name: 'Soplos', selected: false },
            { id: 203, name: 'Pulsos periféricos', selected: false },
            { id: 204, name: 'Mucosas (coloración)', selected: false },
            { id: 205, name: 'Capilaridad', selected: false }
          ]
        },
        {
          id: 3,
          name: '3. Sistema Digestivo',
          expanded: false,
          selected: false,
          items: [
            { id: 301, name: 'Boca', selected: false },
            { id: 302, name: 'Dientes', selected: false },
            { id: 303, name: 'Encías', selected: false },
            { id: 304, name: 'Faringe', selected: false },
            { id: 305, name: 'Esófago', selected: false },
            { id: 306, name: 'Estómago', selected: false },
            { id: 307, name: 'Intestinos', selected: false },
            { id: 308, name: 'Hígado', selected: false },
            { id: 309, name: 'Páncreas', selected: false }
          ]
        },
        {
          id: 4,
          name: '4. Sistema Urinario / Excretor',
          expanded: false,
          selected: false,
          items: [
            { id: 401, name: 'Riñones', selected: false },
            { id: 402, name: 'Vejiga', selected: false },
            { id: 403, name: 'Uretra', selected: false },
            { id: 404, name: 'Micción', selected: false }
          ]
        },
        {
          id: 5,
          name: '5. Sistema Músculo-esquelético',
          expanded: false,
          selected: false,
          items: [
            { id: 501, name: 'Articulaciones', selected: false },
            { id: 502, name: 'Huesos', selected: false },
            { id: 503, name: 'Columna', selected: false },
            { id: 504, name: 'Músculos', selected: false },
            { id: 505, name: 'Marcha / movilidad', selected: false }
          ]
        },
        {
          id: 6,
          name: '6. Sistema Nervioso',
          expanded: false,
          selected: false,
          items: [
            { id: 601, name: 'Estado mental', selected: false },
            { id: 602, name: 'Reflejos', selected: false },
            { id: 603, name: 'Pupilas', selected: false },
            { id: 604, name: 'Coordinación', selected: false },
            { id: 605, name: 'Sensibilidad', selected: false }
          ]
        },
        {
          id: 7,
          name: '7. Sistema Endocrino',
          expanded: false,
          selected: false,
          items: [
            { id: 701, name: 'Tiroides', selected: false },
            { id: 702, name: 'Glándulas adrenales', selected: false },
            { id: 703, name: 'Metabolismo', selected: false },
            { id: 704, name: 'Peso corporal', selected: false }
          ]
        },
        {
          id: 8,
          name: '8. Sistema Reproductor',
          expanded: false,
          selected: false,
          items: [
            { id: 801, name: 'Testículos / próstata', selected: false },
            { id: 802, name: 'Ovarios / útero', selected: false },
            { id: 803, name: 'Ciclos / comportamiento sexual', selected: false }
          ]
        },
        {
          id: 9,
          name: '9. Sistema Inmunológico / Linfático',
          expanded: false,
          selected: false,
          items: [
            { id: 901, name: 'Ganglios linfáticos', selected: false },
            { id: 902, name: 'Reacciones alérgicas', selected: false }
          ]
        },
        {
          id: 10,
          name: '10. Sistema Tegumentario (piel y anexos)',
          expanded: false,
          selected: false,
          items: [
            { id: 1001, name: 'Piel', selected: false },
            { id: 1002, name: 'Pelo', selected: false },
            { id: 1003, name: 'Uñas', selected: false },
            { id: 1004, name: 'Parasitos externos', selected: false },
            { id: 1005, name: 'Heridas', selected: false }
          ]
        },
        {
          id: 11,
          name: '11. Comportamiento',
          expanded: false,
          selected: false,
          items: [
            { id: 1101, name: 'Ansiedad', selected: false },
            { id: 1102, name: 'Agresividad', selected: false },
            { id: 1103, name: 'Cognición', selected: false },
            { id: 1104, name: 'Interacción social', selected: false }
          ]
        },
        {
          id: 12,
          name: '12. Nutrición',
          expanded: false,
          selected: false,
          items: [
            { id: 1201, name: 'Dieta', selected: false },
            { id: 1202, name: 'Apetito', selected: false },
            { id: 1203, name: 'Hidratación', selected: false }
          ]
        }
      ]
    }
  },
  computed: {
    selectedItemsCount() {
      let count = 0;
      this.clinicalSystems.forEach(system => {
        count += system.items.filter(item => item.selected).length;
      });
      return count;
    },
    // Obtener los elementos seleccionados en formato plano
    selectedAreas() {
      const areas = [];
      this.clinicalSystems.forEach(system => {
        system.items.forEach(item => {
          if (item.selected) {
            areas.push({
              id: item.id,
              nombre: item.name,
              sistema: system.name.replace(/^\d+\.\s*/, '') // Quitar el número inicial
            });
          }
        });
      });
      return areas;
    },
    // Obtener solo los nombres de las áreas seleccionadas
    selectedAreaNames() {
      return this.selectedAreas.map(area => area.nombre);
    }
  },
  watch: {
    // Observar cambios en initialData para actualizar el árbol
    initialData: {
      handler(newData) {
        console.log('🌳 initialData cambiado en el árbol:', newData);
        if (newData && Array.isArray(newData)) {
          this.loadInitialData(newData);
        }
      },
      immediate: true, // Ejecutar inmediatamente cuando se monta el componente
      deep: true
    },
    
    selectedAreas: {
      handler(newAreas) {
        // Emitir los elementos seleccionados cuando cambian
        this.$emit('selection-change', {
          areas: newAreas,
          areaNames: this.selectedAreaNames
        });
      },
      deep: true
    }
  },
  mounted() {
    console.log('🌳 Árbol anatómico montado con initialData:', this.initialData);
  },
  methods: {
    toggleSystem(system) {
      system.expanded = !system.expanded;
    },
    
    toggleItem(system, item) {
      item.selected = !item.selected;
      this.updateSystemSelection(system);
      this.emitSelection();
    },
    
    updateSystemSelection(system) {
      const selectedItems = system.items.filter(item => item.selected).length;
      const totalItems = system.items.length;
      
      if (selectedItems === 0) {
        system.selected = false;
      } else if (selectedItems === totalItems) {
        system.selected = true;
      } else {
        system.selected = false;
      }
    },
    
    getSystemSelectionStatus(system) {
      const selectedItems = system.items.filter(item => item.selected).length;
      const totalItems = system.items.length;
      
      if (selectedItems === 0) {
        return '';
      } else if (selectedItems === totalItems) {
        return '✓';
      } else {
        return `(${selectedItems}/${totalItems})`;
      }
    },
    
    // Cargar datos iniciales desde el backend
    loadInitialData(initialData) {
      console.log('🌳 Cargando datos iniciales en el árbol:', initialData);
      
      // Limpiar todas las selecciones primero
      this.clinicalSystems.forEach(system => {
        system.selected = false;
        system.items.forEach(item => {
          item.selected = false;
        });
      });
      
      // Marcar los elementos que estaban previamente seleccionados
      if (Array.isArray(initialData) && initialData.length > 0) {
        initialData.forEach(areaName => {
          let found = false;
          
          // Buscar en todos los sistemas
          for (const system of this.clinicalSystems) {
            for (const item of system.items) {
              if (item.name === areaName) {
                item.selected = true;
                this.updateSystemSelection(system);
                found = true;
                console.log('🌳 Área marcada como seleccionada:', areaName);
                break;
              }
            }
            if (found) break;
          }
          
          if (!found) {
            console.log('🌳 Área no encontrada en el árbol:', areaName);
          }
        });
        
        // Emitir la selección actual después de cargar
        this.$nextTick(() => {
          this.emitSelection();
        });
      }
    },
    
    // Método para obtener los datos seleccionados (puede ser llamado desde el padre)
    getSelectedData() {
      return {
        areas: this.selectedAreas,
        areaNames: this.selectedAreaNames
      };
    },
    
    // Emitir selección
    emitSelection() {
      this.$emit('selection-change', {
        areas: this.selectedAreas,
        areaNames: this.selectedAreaNames
      });
    }
  }
}
</script>
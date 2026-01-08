<!-- SeleccionarDiagnostico.vue -->
<template>
  <!-- Modal overlay simplificado -->
  <div v-if="mostrar" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50">
    <!-- Modal container -->
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Modal header -->
      <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b">
        <div class="flex items-center gap-3">
          <font-awesome-icon :icon="['fas', 'stethoscope']" class="text-2xl text-blue-600" />
          <h2 class="text-2xl font-bold text-gray-900">Seleccionar Diagnósticos</h2>
        </div>
        
        <button
          @click="handleClose"
          class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-100 transition"
        >
          <font-awesome-icon :icon="['fas', 'times']" class="text-xl" />
        </button>
      </div>
      
      <!-- Modal body -->
      <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
        <!-- Header principal -->
        <div class="mb-6 bg-white rounded-xl shadow-sm p-6 border">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <font-awesome-icon :icon="['fas', 'paw']" class="text-xl text-blue-600" />
              <h3 class="text-xl font-bold text-gray-900">Asociar Diagnósticos a la Cirugía</h3>
            </div>
            <div class="flex items-center gap-4">
              <!-- Contador de seleccionados -->
              <div v-if="selectedDiagnostics.length > 0" 
                   class="px-4 py-2 bg-blue-100 text-blue-700 rounded-full font-semibold text-sm">
                {{ selectedDiagnostics.length }} seleccionados
              </div>
            </div>
          </div>
          
          <p class="text-gray-600">
            Seleccione diagnósticos de la mascota o del catálogo general. Puede seleccionar múltiples diagnósticos.
          </p>
        </div>

        <!-- Tabs de navegación -->
        <div class="mb-6 bg-white rounded-xl shadow-sm border">
          <div class="flex border-b border-gray-200">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'px-6 py-4 text-sm font-medium transition-colors relative flex-1 text-center',
                activeTab === tab.id
                  ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50'
                  : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
              ]"
            >
              <div class="flex items-center justify-center gap-2">
                <font-awesome-icon :icon="tab.icon" />
                <span>{{ tab.label }}</span>
                <span v-if="tab.count !== undefined" 
                      :class="[
                        'ml-2 px-2 py-0.5 text-xs rounded-full',
                        activeTab === tab.id 
                          ? 'bg-blue-100 text-blue-600' 
                          : 'bg-gray-100 text-gray-600'
                      ]">
                  {{ tab.count }}
                </span>
              </div>
            </button>
          </div>

          <!-- Contenido de las tabs -->
          <div class="p-6">
            <!-- Tab 1: Diagnósticos de la mascota -->
            <div v-if="activeTab === 'mascota'" class="space-y-6">
              <!-- Filtros y búsqueda -->
              <div class="bg-gray-50 rounded-xl p-4">
                <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                  <div class="flex-1 w-full">
                    <div class="relative">
                      <font-awesome-icon 
                        :icon="['fas', 'search']" 
                        class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                      />
                      <input
                        v-model="searchPet"
                        type="text"
                        placeholder="Buscar en diagnósticos de la mascota..."
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      />
                      <button
                        v-if="searchPet"
                        @click="searchPet = ''"
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                      >
                        <font-awesome-icon :icon="['fas', 'times']" />
                      </button>
                    </div>
                  </div>
                  
                  <div class="flex gap-2">
                    <button
                      @click="toggleSelectionAllPet"
                      class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium"
                    >
                      {{ areAllPetSelected ? 'Deseleccionar todos' : 'Seleccionar todos' }}
                    </button>
                    <button
                      @click="loadPetDiagnostics"
                      class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium"
                    >
                      <font-awesome-icon :icon="['fas', 'sync']" />
                      Actualizar
                    </button>
                  </div>
                </div>
              </div>

              <!-- Estado de carga -->
              <div v-if="loadingPet" class="flex justify-center py-12">
                <div class="inline-flex items-center gap-3">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                  <span class="text-gray-600">Cargando diagnósticos de la mascota...</span>
                </div>
              </div>

              <!-- Sin diagnósticos -->
              <div v-else-if="filteredPetDiagnostics.length === 0" class="text-center py-12 bg-white rounded-xl shadow-sm">
                <div class="w-16 h-16 mx-auto mb-4 text-gray-300">
                  <font-awesome-icon :icon="['fas', 'file-medical']" size="3x" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No hay diagnósticos registrados</h3>
                <p class="text-gray-600 mb-4">Esta mascota no tiene diagnósticos registrados aún.</p>
                <button
                  @click="activeTab = 'catalogo'"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                >
                  <font-awesome-icon :icon="['fas', 'plus']" class="mr-2" />
                  Agregar diagnóstico desde catálogo
                </button>
              </div>

              <!-- Grid de diagnósticos de la mascota -->
              <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="diagnostico in filteredPetDiagnostics"
                  :key="`pet-${diagnostico.id}`"
                  :class="[
                    'bg-white rounded-xl shadow-sm border-2 p-4 cursor-pointer transition-all duration-300 hover:shadow-md group',
                    isPetSelected(diagnostico.id)
                      ? 'border-blue-500 bg-blue-50 transform scale-[1.02]'
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                  @click="togglePetSelection(diagnostico)"
                >
                  <!-- Header con checkbox -->
                  <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-2">
                      <div class="relative">
                        <input
                          type="checkbox"
                          :checked="isPetSelected(diagnostico.id)"
                          @change="togglePetSelection(diagnostico)"
                          class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                        />
                      </div>
                      <span class="text-xs font-semibold px-2 py-1 bg-gray-100 text-gray-700 rounded">
                        ID: {{ diagnostico.id }}
                      </span>
                    </div>
                    
                    <div class="flex gap-2">
                      <span
                        :class="[
                          'px-2 py-1 text-xs font-bold rounded-full',
                          getEvolutionColor(diagnostico.evolucion)
                        ]"
                      >
                        {{ getEvolutionLabel(diagnostico.evolucion) }}
                      </span>
                    </div>
                  </div>

                  <!-- Nombre del diagnóstico -->
                  <h3 class="font-bold text-lg text-gray-900 mb-2 line-clamp-2">
                    {{ diagnostico.nombre }}
                  </h3>

                  <!-- Fecha -->
                  <p class="text-sm text-gray-500 mb-3">
                    <font-awesome-icon :icon="['fas', 'calendar']" class="mr-1" />
                    {{ formatFecha(diagnostico.fecha_diagnostico || diagnostico.created_at) }}
                  </p>

                  <!-- Descripción -->
                  <p class="text-sm text-gray-600 mb-3 line-clamp-3">
                    {{ truncateText(diagnostico.descripcion || diagnostico.observaciones, 120) }}
                  </p>

                  <!-- Síntomas si existen -->
                  <div v-if="diagnostico.sintomas_caracteristicos" class="mb-3">
                    <p class="text-xs font-medium text-gray-500 mb-1">Síntomas:</p>
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="(symptom, index) in getSymptomsArray(diagnostico.sintomas_caracteristicos).slice(0, 3)"
                        :key="index"
                        class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded"
                      >
                        {{ symptom }}
                      </span>
                      <span v-if="getSymptomsArray(diagnostico.sintomas_caracteristicos).length > 3" 
                            class="px-2 py-1 text-gray-500 text-xs">
                        +{{ getSymptomsArray(diagnostico.sintomas_caracteristicos).length - 3 }}
                      </span>
                    </div>
                  </div>

                  <!-- Estado actual -->
                  <div v-if="diagnostico.estado_actual" class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                      <span class="text-xs font-medium text-gray-500">Estado:</span>
                      <span :class="[
                        'px-2 py-1 text-xs font-bold rounded-full',
                        diagnostico.estado_actual === 'activo' ? 'bg-red-100 text-red-700' :
                        diagnostico.estado_actual === 'resuelto' ? 'bg-green-100 text-green-700' :
                        diagnostico.estado_actual === 'cronico' ? 'bg-yellow-100 text-yellow-700' :
                        'bg-gray-100 text-gray-700'
                      ]">
                        {{ getEstadoLabel(diagnostico.estado_actual) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab 2: Catálogo de diagnósticos -->
            <div v-else-if="activeTab === 'catalogo'" class="space-y-6">
              <!-- Panel de búsqueda y filtros -->
              <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <!-- Barra de búsqueda -->
                <div class="relative mb-4">
                  <font-awesome-icon 
                    :icon="['fas', 'search']" 
                    class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                  />
                  <input
                    v-model="searchCatalog"
                    @input="filterCatalogDiagnoses"
                    type="text"
                    placeholder="Ej: fiebre, infeccioso, diabetes..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                  />
                  <button
                    v-if="searchCatalog"
                    @click="clearCatalogSearch"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <font-awesome-icon :icon="['fas', 'times']" />
                  </button>
                </div>

                <!-- Filtros -->
                <div class="grid md:grid-cols-2 gap-4">
                  <!-- Filtros de evolución -->
                  <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Evolución:</p>
                    <div class="flex flex-wrap gap-1">
                      <button
                        v-for="filter in evolutionFilters"
                        :key="filter.id"
                        @click="toggleEvolutionFilter(filter.id)"
                        :class="[
                          'px-3 py-1.5 rounded-full text-xs font-medium transition-all flex items-center gap-1',
                          activeEvolution === filter.id
                            ? 'ring-2 ring-offset-1'
                            : 'hover:shadow-sm',
                          filter.bgColor,
                          filter.textColor,
                          activeEvolution === filter.id ? filter.ringColor : ''
                        ]"
                      >
                        <span class="w-1.5 h-1.5 rounded-full" :class="filter.dotColor"></span>
                        {{ filter.label }}
                      </button>
                    </div>
                  </div>

                  <!-- Filtros de clasificación -->
                  <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Clasificación:</p>
                    <div class="flex flex-wrap gap-1">
                      <button
                        v-for="filter in classificationFilters"
                        :key="filter.id"
                        @click="toggleClassificationFilter(filter.id)"
                        :class="[
                          'px-3 py-1.5 rounded-full text-xs font-medium transition-all flex items-center gap-1',
                          activeClassification === filter.id
                            ? 'ring-2 ring-offset-1'
                            : 'hover:shadow-sm',
                          filter.bgColor,
                          filter.textColor,
                          activeClassification === filter.id ? filter.ringColor : ''
                        ]"
                      >
                        <span class="w-1.5 h-1.5 rounded-full" :class="filter.dotColor"></span>
                        {{ filter.label }}
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Contador y controles -->
                <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                  <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">
                      {{ filteredCatalogDiagnoses.length }} de {{ allCatalogDiagnoses.length }} diagnósticos
                    </span>
                    <button
                      v-if="activeEvolution || activeClassification || searchCatalog"
                      @click="clearCatalogFilters"
                      class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1"
                    >
                      <font-awesome-icon :icon="['fas', 'filter-circle-xmark']" />
                      Limpiar filtros
                    </button>
                  </div>
                  <div class="flex gap-2">
                    <button
                      @click="toggleSelectionAllCatalog"
                      class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-xs font-medium"
                    >
                      {{ areAllCatalogSelected ? 'Deseleccionar todos' : 'Seleccionar todos' }}
                    </button>
                    <button
                      @click="loadCatalogDiagnoses"
                      class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-xs font-medium"
                    >
                      <font-awesome-icon :icon="['fas', 'sync']" />
                      Actualizar
                    </button>
                  </div>
                </div>
              </div>

              <!-- Estado de carga del catálogo -->
              <div v-if="loadingCatalog" class="flex justify-center py-12">
                <div class="inline-flex items-center gap-3">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                  <span class="text-gray-600">Cargando catálogo de diagnósticos...</span>
                </div>
              </div>

              <!-- Sin resultados en catálogo -->
              <div v-else-if="filteredCatalogDiagnoses.length === 0" class="text-center py-12 bg-white rounded-xl shadow-sm">
                <div class="w-16 h-16 mx-auto mb-4 text-gray-300">
                  <font-awesome-icon :icon="['fas', 'search']" size="3x" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron diagnósticos</h3>
                <p class="text-gray-600 mb-4">Intenta con otros términos o limpia los filtros</p>
                <button
                  @click="clearCatalogFilters"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                >
                  Mostrar todos
                </button>
              </div>

              <!-- Grid de catálogo de diagnósticos -->
              <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="diagnosis in filteredCatalogDiagnoses"
                  :key="`catalog-${diagnosis.id}`"
                  @click="toggleCatalogSelection(diagnosis)"
                  :class="[
                    'bg-white rounded-xl shadow-sm border-2 p-4 cursor-pointer transition-all duration-300 hover:shadow-md',
                    isCatalogSelected(diagnosis.id)
                      ? 'border-blue-500 bg-blue-50 transform scale-[1.02]'
                      : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <!-- Header -->
                  <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-2">
                      <div class="relative">
                        <input
                          type="checkbox"
                          :checked="isCatalogSelected(diagnosis.id)"
                          @change="toggleCatalogSelection(diagnosis)"
                          class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                        />
                      </div>
                      <div class="px-2 py-1 bg-blue-100 rounded-lg">
                        <span class="font-mono font-bold text-blue-800 text-xs">ID: {{ diagnosis.id }}</span>
                      </div>
                    </div>
                    <div class="flex items-center gap-2">
                      <span
                        :class="[
                          'px-2 py-1 text-xs font-bold rounded-full',
                          getEvolutionColor(diagnosis.evolucion)
                        ]"
                      >
                        {{ getEvolutionLabel(diagnosis.evolucion) }}
                      </span>
                    </div>
                  </div>

                  <!-- Nombre -->
                  <h3 class="font-bold text-lg text-gray-900 mb-2">{{ diagnosis.nombre }}</h3>

                  <!-- Descripción -->
                  <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                    {{ truncateText(diagnosis.descripcion, 100) }}
                  </p>

                  <!-- Síntomas -->
                  <div class="mb-4">
                    <p class="text-xs font-medium text-gray-700 mb-1">Síntomas principales:</p>
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="(symptom, index) in getSymptomsArray(diagnosis.sintomas_caracteristicos).slice(0, 2)"
                        :key="index"
                        class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded"
                      >
                        {{ symptom }}
                      </span>
                      <span v-if="getSymptomsArray(diagnosis.sintomas_caracteristicos).length > 2" 
                            class="px-2 py-0.5 text-gray-500 text-xs">
                        +{{ getSymptomsArray(diagnosis.sintomas_caracteristicos).length - 2 }}
                      </span>
                    </div>
                  </div>

                  <!-- Etiquetas -->
                  <div class="flex flex-wrap gap-1 mb-3">
                    <span
                      :class="[
                        'px-2 py-0.5 text-xs font-medium rounded',
                        getClassificationColor(diagnosis.clasificacion)
                      ]"
                    >
                      {{ getClassificationLabel(diagnosis.clasificacion, diagnosis.clasificacion_otro) }}
                    </span>
                    <span
                      v-for="(especie, index) in getEspeciesArray(diagnosis.especies).slice(0, 1)"
                      :key="index"
                      class="px-2 py-0.5 bg-green-50 text-green-700 text-xs font-medium rounded"
                    >
                      {{ getEspecieLabel(especie) }}
                    </span>
                  </div>

                  <!-- Botón de selección -->
                  <button
                    @click.stop="toggleCatalogSelection(diagnosis)"
                    :class="[
                      'w-full py-2 rounded-lg text-sm font-medium transition mt-2',
                      isCatalogSelected(diagnosis.id)
                        ? 'bg-green-100 text-green-700 hover:bg-green-200'
                        : 'bg-blue-600 text-white hover:bg-blue-700'
                    ]"
                  >
                    <font-awesome-icon 
                      :icon="isCatalogSelected(diagnosis.id) ? ['fas', 'check'] : ['fas', 'plus']" 
                      class="mr-1" 
                    />
                    {{ isCatalogSelected(diagnosis.id) ? 'Seleccionado' : 'Seleccionar' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Panel lateral de selección -->
        <div v-if="selectedDiagnostics.length > 0" 
             class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl shadow-sm border border-blue-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'list-check']" class="text-blue-600" />
              Diagnósticos seleccionados
            </h3>
            <button
              @click="clearAllSelections"
              class="text-gray-400 hover:text-gray-600 p-1 text-sm"
            >
              <font-awesome-icon :icon="['fas', 'trash']" class="mr-1" />
              Limpiar todos
            </button>
          </div>

          <div class="bg-white rounded-lg p-4 shadow-sm mb-4 max-h-60 overflow-y-auto">
            <div class="space-y-2">
              <div
                v-for="(diagnostico, index) in selectedDiagnostics"
                :key="index"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100"
              >
                <div class="flex items-center gap-3">
                  <button
                    @click="removeSelection(index)"
                    class="text-red-500 hover:text-red-700"
                  >
                    <font-awesome-icon :icon="['fas', 'times']" />
                  </button>
                  <div>
                    <p class="font-medium text-gray-900">{{ diagnostico.nombre }}</p>
                    <p class="text-xs text-gray-500">
                      {{ diagnostico.type === 'pet' ? 'Diagnóstico de mascota' : 'Catálogo' }}
                      • {{ getEvolutionLabel(diagnostico.evolucion) }}
                    </p>
                  </div>
                </div>
                <span
                  :class="[
                    'px-2 py-1 text-xs font-bold rounded-full',
                    getEvolutionColor(diagnostico.evolucion)
                  ]"
                >
                  {{ getEvolutionLabel(diagnostico.evolucion) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal footer -->
      <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center">
        <button
          @click="handleClose"
          class="px-6 py-2 bg-gray-500 text-white rounded-lg font-medium hover:bg-gray-600 transition"
        >
          Cancelar
        </button>
        
        <button
          v-if="selectedDiagnostics.length > 0"
          @click="handleGuardar"
          class="px-6 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition flex items-center gap-2"
        >
          <font-awesome-icon :icon="['fas', 'check']" />
          Usar {{ selectedDiagnostics.length }} diagnóstico(s)
        </button>
        <div v-else class="text-gray-500 text-sm">
          Seleccione al menos un diagnóstico
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { accessToken } = useAuth()

// Definir props para el modal
const props = defineProps({
  mostrar: {
    type: Boolean,
    default: false
  },
  mascotaId: {
    type: [String, Number],
    required: true
  },
  diagnosticosIniciales: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['cerrar', 'guardar'])

// Estados
const activeTab = ref('mascota')
const loadingPet = ref(false)
const loadingCatalog = ref(false)
const searchPet = ref('')
const searchCatalog = ref('')
const activeEvolution = ref(null)
const activeClassification = ref(null)

// Datos
const petDiagnostics = ref([])
const allCatalogDiagnoses = ref([])
const selectedPetDiagnostics = ref([])
const selectedCatalogDiagnoses = ref([])

// Método para cerrar el modal
const handleClose = () => {
  console.log('Cerrando modal de diagnósticos')
  emit('cerrar')
}

// Computed: Todos los diagnósticos seleccionados
const selectedDiagnostics = computed(() => {
  return [
    ...selectedPetDiagnostics.value.map(d => ({ ...d, type: 'pet' })),
    ...selectedCatalogDiagnoses.value.map(d => ({ ...d, type: 'catalog' }))
  ]
})

// Tabs config
const tabs = computed(() => [
  { 
    id: 'mascota', 
    label: 'Diagnósticos de la Mascota', 
    icon: ['fas', 'paw'],
    count: petDiagnostics.value.length
  },
  { 
    id: 'catalogo', 
    label: 'Catálogo de Diagnósticos', 
    icon: ['fas', 'book-medical'],
    count: allCatalogDiagnoses.value.length
  }
])

// Computed: Diagnósticos filtrados de mascota
const filteredPetDiagnostics = computed(() => {
  if (!searchPet.value.trim()) {
    return petDiagnostics.value
  }
  
  const term = searchPet.value.toLowerCase().trim()
  return petDiagnostics.value.filter(diagnostico => {
    return (
      diagnostico.nombre?.toLowerCase().includes(term) ||
      diagnostico.descripcion?.toLowerCase().includes(term) ||
      diagnostico.observaciones?.toLowerCase().includes(term) ||
      diagnostico.sintomas_caracteristicos?.toLowerCase().includes(term)
    )
  })
})

// Computed: Diagnósticos filtrados del catálogo
const filteredCatalogDiagnoses = computed(() => {
  let filtered = [...allCatalogDiagnoses.value]
  
  // Filtro por búsqueda
  if (searchCatalog.value.trim()) {
    const term = searchCatalog.value.toLowerCase().trim()
    filtered = filtered.filter(diagnosis => {
      return (
        diagnosis.nombre?.toLowerCase().includes(term) ||
        diagnosis.descripcion?.toLowerCase().includes(term) ||
        diagnosis.sintomas_caracteristicos?.toLowerCase().includes(term) ||
        diagnosis.clasificacion?.toLowerCase().includes(term) ||
        diagnosis.clasificacion_otro?.toLowerCase().includes(term)
      )
    })
  }
  
  // Filtro por evolución
  if (activeEvolution.value) {
    filtered = filtered.filter(diagnosis => diagnosis.evolucion === activeEvolution.value)
  }
  
  // Filtro por clasificación
  if (activeClassification.value) {
    filtered = filtered.filter(diagnosis => diagnosis.clasificacion === activeClassification.value)
  }
  
  return filtered
})

// Computed: Verificaciones de selección completa
const areAllPetSelected = computed(() => {
  if (filteredPetDiagnostics.value.length === 0) return false
  return filteredPetDiagnostics.value.every(d => 
    selectedPetDiagnostics.value.some(selected => selected.id === d.id)
  )
})

const areAllCatalogSelected = computed(() => {
  if (filteredCatalogDiagnoses.value.length === 0) return false
  return filteredCatalogDiagnoses.value.every(d => 
    selectedCatalogDiagnoses.value.some(selected => selected.id === d.id)
  )
})

// Métodos de utilidad
const formatFecha = (fechaString) => {
  if (!fechaString) return 'No especificada'
  try {
    const fecha = new Date(fechaString)
    return fecha.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch (error) {
    return fechaString
  }
}

const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const getSymptomsArray = (symptoms) => {
  if (!symptoms) return []
  if (Array.isArray(symptoms)) return symptoms
  try {
    return JSON.parse(symptoms)
  } catch {
    return symptoms.split(',').map(s => s.trim()).filter(s => s.length > 0)
  }
}

const getEspeciesArray = (especies) => {
  if (!especies) return []
  if (Array.isArray(especies)) return especies
  try {
    return JSON.parse(especies)
  } catch {
    return especies.split(',').map(e => e.trim()).filter(e => e.length > 0)
  }
}

// Métodos para etiquetas y colores
const getEvolutionLabel = (evolution) => {
  const map = {
    'aguda': 'Aguda',
    'cronica': 'Crónica',
    'recurrente': 'Recurrente',
    'autolimitada': 'Autolimitada',
    'progresiva': 'Progresiva'
  }
  return map[evolution] || evolution
}

const getEvolutionColor = (evolution) => {
  const map = {
    'aguda': 'bg-red-100 text-red-800',
    'cronica': 'bg-yellow-100 text-yellow-800',
    'recurrente': 'bg-blue-100 text-blue-800',
    'autolimitada': 'bg-green-100 text-green-800',
    'progresiva': 'bg-purple-100 text-purple-800'
  }
  return map[evolution] || 'bg-gray-100 text-gray-800'
}

const getClassificationLabel = (classification, otro = null) => {
  const map = {
    'infeccioso': 'Infeccioso',
    'genetico': 'Genético',
    'nutricional': 'Nutricional',
    'ambiental': 'Ambiental',
    'traumatico': 'Traumático',
    'degenerativo': 'Degenerativo',
    'neoplasico': 'Neoplásico',
    'otro': otro || 'Otro'
  }
  return map[classification] || classification
}

const getClassificationColor = (classification) => {
  const map = {
    'infeccioso': 'bg-orange-100 text-orange-800',
    'genetico': 'bg-indigo-100 text-indigo-800',
    'nutricional': 'bg-green-100 text-green-800',
    'ambiental': 'bg-teal-100 text-teal-800',
    'traumatico': 'bg-gray-100 text-gray-800',
    'degenerativo': 'bg-amber-100 text-amber-800',
    'neoplasico': 'bg-pink-100 text-pink-800',
    'otro': 'bg-gray-100 text-gray-800'
  }
  return map[classification] || 'bg-gray-100 text-gray-800'
}

const getEspecieLabel = (especie) => {
  const map = {
    'canino': 'Canino',
    'felino': 'Felino',
    'equino': 'Equino',
    'bovino': 'Bovino',
    'ave': 'Ave',
    'pez': 'Pez',
    'otro': 'Otro'
  }
  return map[especie] || especie
}

const getEstadoLabel = (estado) => {
  const map = {
    'activo': 'Activo',
    'resuelto': 'Resuelto',
    'cronico': 'Crónico',
    'monitoreo': 'En monitoreo'
  }
  return map[estado] || estado
}

// Filtros para catálogo
const evolutionFilters = [
  { 
    id: 'aguda', 
    label: 'Aguda', 
    bgColor: 'bg-red-50',
    textColor: 'text-red-700',
    dotColor: 'bg-red-500',
    ringColor: 'ring-red-300'
  },
  { 
    id: 'cronica', 
    label: 'Crónica', 
    bgColor: 'bg-yellow-50',
    textColor: 'text-yellow-700',
    dotColor: 'bg-yellow-500',
    ringColor: 'ring-yellow-300'
  },
  { 
    id: 'recurrente', 
    label: 'Recurrente', 
    bgColor: 'bg-blue-50',
    textColor: 'text-blue-700',
    dotColor: 'bg-blue-500',
    ringColor: 'ring-blue-300'
  },
  { 
    id: 'autolimitada', 
    label: 'Autolimitada', 
    bgColor: 'bg-green-50',
    textColor: 'text-green-700',
    dotColor: 'bg-green-500',
    ringColor: 'ring-green-300'
  },
  { 
    id: 'progresiva', 
    label: 'Progresiva', 
    bgColor: 'bg-purple-50',
    textColor: 'text-purple-700',
    dotColor: 'bg-purple-500',
    ringColor: 'ring-purple-300'
  }
]

const classificationFilters = [
  { 
    id: 'infeccioso', 
    label: 'Infeccioso', 
    bgColor: 'bg-orange-50',
    textColor: 'text-orange-700',
    dotColor: 'bg-orange-500',
    ringColor: 'ring-orange-300'
  },
  { 
    id: 'genetico', 
    label: 'Genético', 
    bgColor: 'bg-indigo-50',
    textColor: 'text-indigo-700',
    dotColor: 'bg-indigo-500',
    ringColor: 'ring-indigo-300'
  },
  { 
    id: 'nutricional', 
    label: 'Nutricional', 
    bgColor: 'bg-green-50',
    textColor: 'text-green-700',
    dotColor: 'bg-green-500',
    ringColor: 'ring-green-300'
  },
  { 
    id: 'traumatico', 
    label: 'Traumático', 
    bgColor: 'bg-gray-50',
    textColor: 'text-gray-700',
    dotColor: 'bg-gray-500',
    ringColor: 'ring-gray-300'
  },
  { 
    id: 'neoplasico', 
    label: 'Neoplásico', 
    bgColor: 'bg-pink-50',
    textColor: 'text-pink-700',
    dotColor: 'bg-pink-500',
    ringColor: 'ring-pink-300'
  }
]

// Métodos de carga de datos (SIMPLIFICADOS PARA DEBUG)
const loadPetDiagnostics = async () => {
  if (!props.mascotaId) {
    console.error('❌ No hay mascotaId para cargar diagnósticos')
    return
  }

  try {
    loadingPet.value = true
    console.log(`🔍 Intentando cargar diagnósticos para mascota ID: ${props.mascotaId}`)
    
    // URL para debug
    const url = `/api/mascotas/${props.mascotaId}/diagnosticos`
    console.log(`🌐 URL: ${url}`)
    console.log(`🔑 Token: ${accessToken.value ? 'Presente' : 'Ausente'}`)
    
    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    console.log(`📊 Status: ${response.status} ${response.statusText}`)
    
    if (!response.ok) {
      console.error(`❌ Error HTTP: ${response.status}`)
      const errorText = await response.text()
      console.error(`📄 Error response:`, errorText)
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Resultado completo:', result)
    
    if (result.success && result.data) {
      petDiagnostics.value = result.data
      console.log(`✅ Diagnósticos de mascota cargados: ${petDiagnostics.value.length} registros`)
      console.log('📋 Primeros 3 registros:', petDiagnostics.value.slice(0, 3))
    } else {
      petDiagnostics.value = []
      console.log('ℹ️ Respuesta sin datos o success=false:', result.message || 'Sin mensaje')
    }
  } catch (error) {
    console.error('💥 Error cargando diagnósticos de mascota:', error)
    console.error('Detalles del error:', error.message)
    petDiagnostics.value = []
    // Mostrar datos de ejemplo para debug
    console.log('🔄 Usando datos de ejemplo para debug')
    petDiagnostics.value = [
      {
        id: 1,
        nombre: "Fiebre de origen desconocido",
        descripcion: "Fiebre persistente sin causa aparente",
        evolucion: "aguda",
        estado_actual: "activo",
        fecha_diagnostico: "2024-01-15",
        sintomas_caracteristicos: "fiebre, letargo, pérdida de apetito"
      },
      {
        id: 2,
        nombre: "Problema dental",
        descripcion: "Infección en molares",
        evolucion: "cronica",
        estado_actual: "monitoreo",
        fecha_diagnostico: "2024-01-10",
        sintomas_caracteristicos: "mal aliento, dificultad para masticar"
      }
    ]
  } finally {
    loadingPet.value = false
  }
}

const loadCatalogDiagnoses = async () => {
  try {
    loadingCatalog.value = true
    console.log('🔍 Intentando cargar catálogo de diagnósticos...')
    
    // URL para debug
    const url = '/api/tipos-diagnostico'
    console.log(`🌐 URL: ${url}`)
    
    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    console.log(`📊 Status: ${response.status} ${response.statusText}`)
    
    if (!response.ok) {
      console.error(`❌ Error HTTP: ${response.status}`)
      const errorText = await response.text()
      console.error(`📄 Error response:`, errorText)
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Resultado completo del catálogo:', result)
    
    if (result.success && result.data) {
      allCatalogDiagnoses.value = result.data
      console.log(`✅ Catálogo de diagnósticos cargado: ${allCatalogDiagnoses.value.length} registros`)
      console.log('📋 Primeros 3 registros:', allCatalogDiagnoses.value.slice(0, 3))
    } else {
      allCatalogDiagnoses.value = []
      console.log('ℹ️ Respuesta sin datos o success=false:', result.message || 'Sin mensaje')
    }
  } catch (error) {
    console.error('💥 Error cargando catálogo de diagnósticos:', error)
    console.error('Detalles del error:', error.message)
    allCatalogDiagnoses.value = []
    // Mostrar datos de ejemplo para debug
    console.log('🔄 Usando datos de ejemplo para debug')
    allCatalogDiagnoses.value = [
      {
        id: 101,
        nombre: "Panleucopenia Felina",
        descripcion: "Enfermedad viral altamente contagiosa en gatos",
        evolucion: "aguda",
        clasificacion: "infeccioso",
        sintomas_caracteristicos: "fiebre, vómitos, diarrea, depresión",
        especies: "felino"
      },
      {
        id: 102,
        nombre: "Moquillo Canino",
        descripcion: "Enfermedad viral que afecta a perros",
        evolucion: "progresiva",
        clasificacion: "infeccioso",
        sintomas_caracteristicos: "tos, secreción nasal, fiebre, letargo",
        especies: "canino"
      },
      {
        id: 103,
        nombre: "Artritis",
        descripcion: "Inflamación de las articulaciones",
        evolucion: "cronica",
        clasificacion: "degenerativo",
        sintomas_caracteristicos: "cojera, dolor, rigidez",
        especies: "canino,felino"
      }
    ]
  } finally {
    loadingCatalog.value = false
  }
}

// Métodos de selección
const isPetSelected = (id) => {
  return selectedPetDiagnostics.value.some(d => d.id === id)
}

const isCatalogSelected = (id) => {
  return selectedCatalogDiagnoses.value.some(d => d.id === id)
}

const togglePetSelection = (diagnostico) => {
  const index = selectedPetDiagnostics.value.findIndex(d => d.id === diagnostico.id)
  if (index !== -1) {
    selectedPetDiagnostics.value.splice(index, 1)
  } else {
    selectedPetDiagnostics.value.push({
      id: diagnostico.id,
      nombre: diagnostico.nombre,
      descripcion: diagnostico.descripcion,
      evolucion: diagnostico.evolucion,
      clasificacion: diagnostico.clasificacion,
      ...diagnostico
    })
  }
}

const toggleCatalogSelection = (diagnosis) => {
  const index = selectedCatalogDiagnoses.value.findIndex(d => d.id === diagnosis.id)
  if (index !== -1) {
    selectedCatalogDiagnoses.value.splice(index, 1)
  } else {
    selectedCatalogDiagnoses.value.push({
      id: diagnosis.id,
      nombre: diagnosis.nombre,
      descripcion: diagnosis.descripcion,
      evolucion: diagnosis.evolucion,
      clasificacion: diagnosis.clasificacion,
      clasificacion_otro: diagnosis.clasificacion_otro,
      especies: diagnosis.especies,
      sintomas_caracteristicos: diagnosis.sintomas_caracteristicos,
      ...diagnosis
    })
  }
}

const toggleSelectionAllPet = () => {
  if (areAllPetSelected.value) {
    // Deseleccionar todos
    selectedPetDiagnostics.value = selectedPetDiagnostics.value.filter(
      selected => !filteredPetDiagnostics.value.some(pet => pet.id === selected.id)
    )
  } else {
    // Seleccionar todos
    filteredPetDiagnostics.value.forEach(diagnostico => {
      if (!isPetSelected(diagnostico.id)) {
        selectedPetDiagnostics.value.push({
          id: diagnostico.id,
          nombre: diagnostico.nombre,
          descripcion: diagnostico.descripcion,
          evolucion: diagnostico.evolucion,
          clasificacion: diagnostico.clasificacion,
          ...diagnostico
        })
      }
    })
  }
}

const toggleSelectionAllCatalog = () => {
  if (areAllCatalogSelected.value) {
    // Deseleccionar todos
    selectedCatalogDiagnoses.value = selectedCatalogDiagnoses.value.filter(
      selected => !filteredCatalogDiagnoses.value.some(catalog => catalog.id === selected.id)
    )
  } else {
    // Seleccionar todos
    filteredCatalogDiagnoses.value.forEach(diagnosis => {
      if (!isCatalogSelected(diagnosis.id)) {
        selectedCatalogDiagnoses.value.push({
          id: diagnosis.id,
          nombre: diagnosis.nombre,
          descripcion: diagnosis.descripcion,
          evolucion: diagnosis.evolucion,
          clasificacion: diagnosis.clasificacion,
          clasificacion_otro: diagnosis.clasificacion_otro,
          especies: diagnosis.especies,
          sintomas_caracteristicos: diagnosis.sintomas_caracteristicos,
          ...diagnosis
        })
      }
    })
  }
}

// Métodos de filtros del catálogo
const filterCatalogDiagnoses = () => {
  // La lógica está en el computed filteredCatalogDiagnoses
}

const toggleEvolutionFilter = (evolution) => {
  activeEvolution.value = activeEvolution.value === evolution ? null : evolution
}

const toggleClassificationFilter = (classification) => {
  activeClassification.value = activeClassification.value === classification ? null : classification
}

const clearCatalogSearch = () => {
  searchCatalog.value = ''
}

const clearCatalogFilters = () => {
  searchCatalog.value = ''
  activeEvolution.value = null
  activeClassification.value = null
}

// Métodos generales de selección
const removeSelection = (index) => {
  const diagnostico = selectedDiagnostics.value[index]
  if (diagnostico.type === 'pet') {
    const petIndex = selectedPetDiagnostics.value.findIndex(d => d.id === diagnostico.id)
    if (petIndex !== -1) {
      selectedPetDiagnostics.value.splice(petIndex, 1)
    }
  } else {
    const catalogIndex = selectedCatalogDiagnoses.value.findIndex(d => d.id === diagnostico.id)
    if (catalogIndex !== -1) {
      selectedCatalogDiagnoses.value.splice(catalogIndex, 1)
    }
  }
}

const clearAllSelections = () => {
  if (confirm('¿Está seguro de limpiar todas las selecciones?')) {
    selectedPetDiagnostics.value = []
    selectedCatalogDiagnoses.value = []
  }
}

// Manejar guardar selección
const handleGuardar = () => {
  if (selectedDiagnostics.value.length === 0) {
    alert('Por favor seleccione al menos un diagnóstico')
    return
  }
  console.log('💾 Guardando diagnósticos seleccionados:', selectedDiagnostics.value)
  emit('guardar', selectedDiagnostics.value)
  emit('cerrar')
}

// Método para inicializar selecciones desde el componente padre
const inicializarSelecciones = (diagnosticosIniciales) => {
  console.log('🔄 Inicializando selecciones con:', diagnosticosIniciales)
  
  // Limpiar selecciones previas
  selectedPetDiagnostics.value = []
  selectedCatalogDiagnoses.value = []
  
  if (diagnosticosIniciales && diagnosticosIniciales.length > 0) {
    diagnosticosIniciales.forEach(diagnostico => {
      // Intentar determinar si es de mascota o catálogo
      // Por ahora, asumimos que son del catálogo
      selectedCatalogDiagnoses.value.push({
        id: diagnostico.id,
        nombre: diagnostico.nombre,
        descripcion: diagnostico.descripcion,
        evolucion: diagnostico.evolucion,
        clasificacion: diagnostico.clasificacion,
        ...diagnostico
      })
    })
    console.log(`✅ ${diagnosticosIniciales.length} diagnósticos inicializados`)
  }
}

// Inicialización
onMounted(async () => {
  console.log('🚀 Componente SeleccionarDiagnostico montado')
  console.log('📋 Props recibidos:', {
    mostrar: props.mostrar,
    mascotaId: props.mascotaId,
    diagnosticosIniciales: props.diagnosticosIniciales?.length || 0
  })
  
  // Cargar datos si el modal está visible
  if (props.mostrar) {
    await loadPetDiagnostics()
    await loadCatalogDiagnoses()
    
    // Inicializar con diagnósticos pasados como prop
    if (props.diagnosticosIniciales && props.diagnosticosIniciales.length > 0) {
      inicializarSelecciones(props.diagnosticosIniciales)
    }
  }
})

// Watch para limpiar búsqueda cuando cambia la tab
watch(activeTab, () => {
  searchPet.value = ''
  searchCatalog.value = ''
})

// Watch para recargar datos cuando se muestra el modal
watch(() => props.mostrar, (mostrar) => {
  console.log(`👁️ Watch mostrar: ${mostrar}`)
  if (mostrar) {
    // Recargar datos cuando se muestra el modal
    loadPetDiagnostics()
    loadCatalogDiagnoses()
    
    // Inicializar selecciones si hay diagnósticos iniciales
    if (props.diagnosticosIniciales && props.diagnosticosIniciales.length > 0) {
      inicializarSelecciones(props.diagnosticosIniciales)
    }
  }
}, { immediate: true })

// Watch para recargar cuando cambia el mascotaId
watch(() => props.mascotaId, (nuevoId) => {
  console.log(`🔄 Mascota ID cambiado: ${nuevoId}`)
  if (props.mostrar) {
    loadPetDiagnostics()
  }
})
</script>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Mejores estilos para line-clamp */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Estilos para scroll */
.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e0 #f7fafc;
}

.overflow-y-auto::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f7fafc;
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background-color: #cbd5e0;
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background-color: #a0aec0;
}

/* Animaciones para el modal */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.modal-enter-active,
.modal-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* Para asegurar que el modal esté centrado */
.fixed {
  position: fixed;
}

.inset-0 {
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}

.z-\[9999\] {
  z-index: 9999;
}
</style>
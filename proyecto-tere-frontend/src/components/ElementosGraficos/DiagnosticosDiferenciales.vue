<!-- DiagnosticosDiferenciales.vue -->
<template>
  <div class="min-h-screen bg-gray-50 p-4 md:p-6">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
          <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Selector de Diagnóstico Médico
        </h1>
        <p class="text-gray-600 mt-2">Busque y seleccione diagnósticos por síntomas, código o nombre</p>
      </div>

      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Panel izquierdo - Búsqueda y Filtros -->
        <div class="lg:col-span-2">
          <!-- Barra de búsqueda -->
          <div class="bg-white rounded-xl shadow-sm p-5 mb-6 border border-gray-200">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                v-model="searchTerm"
                @input="filterDiagnoses"
                type="text"
                placeholder="Ej: fiebre, J06.9, hipertensión..."
                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
              />
              <button
                v-if="searchTerm"
                @click="clearSearch"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Filtros de urgencia -->
            <div class="mt-6">
              <p class="text-sm font-medium text-gray-700 mb-3">Filtrar por urgencia:</p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="filter in urgencyFilters"
                  :key="filter.id"
                  @click="toggleUrgencyFilter(filter.id)"
                  :class="[
                    'px-4 py-2 rounded-full text-sm font-medium transition-all flex items-center gap-2',
                    activeUrgency === filter.id
                      ? 'ring-2 ring-offset-2'
                      : 'hover:shadow-md',
                    filter.bgColor,
                    filter.textColor,
                    activeUrgency === filter.id ? filter.ringColor : ''
                  ]"
                >
                  <span class="w-2 h-2 rounded-full" :class="filter.dotColor"></span>
                  {{ filter.label }}
                </button>
              </div>
            </div>

            <!-- Contador de resultados -->
            <div class="mt-6 pt-4 border-t border-gray-100">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">
                  {{ filteredDiagnoses.length }} de {{ allDiagnoses.length }} diagnósticos
                </span>
                <button
                  v-if="activeUrgency || searchTerm"
                  @click="clearFilters"
                  class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  Limpiar filtros
                </button>
              </div>
            </div>
          </div>

          <!-- Grid de diagnósticos -->
          <div v-if="filteredDiagnoses.length > 0" class="grid md:grid-cols-2 gap-4">
            <div
              v-for="diagnosis in filteredDiagnoses"
              :key="diagnosis.id"
              @click="selectDiagnosis(diagnosis)"
              :class="[
                'bg-white rounded-xl shadow-sm border-2 p-5 cursor-pointer transition-all duration-300 hover:shadow-md',
                selectedDiagnosis?.id === diagnosis.id
                  ? 'border-blue-500 bg-blue-50 transform scale-[1.02]'
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <!-- Header de la tarjeta -->
              <div class="flex justify-between items-start mb-3">
                <div class="flex items-center gap-3">
                  <div class="px-3 py-1 bg-gray-100 rounded-lg">
                    <span class="font-mono font-bold text-gray-800">{{ diagnosis.code }}</span>
                  </div>
                  <span
                    :class="[
                      'px-2 py-1 text-xs font-bold rounded-full',
                      diagnosis.urgency === 'high' ? 'bg-red-100 text-red-800' :
                      diagnosis.urgency === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-green-100 text-green-800'
                    ]"
                  >
                    {{ getUrgencyLabel(diagnosis.urgency) }}
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <button
                    v-if="selectedDiagnosis?.id === diagnosis.id"
                    class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center"
                  >
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Nombre del diagnóstico -->
              <h3 class="font-bold text-lg text-gray-900 mb-3">{{ diagnosis.name }}</h3>

              <!-- Síntomas -->
              <div class="mb-4">
                <p class="text-sm font-medium text-gray-700 mb-2">Síntomas principales:</p>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="symptom in diagnosis.symptoms"
                    :key="symptom"
                    class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded"
                  >
                    {{ symptom }}
                  </span>
                </div>
              </div>

              <!-- Etiquetas y botón -->
              <div class="flex justify-between items-center">
                <div class="flex gap-2">
                  <span
                    v-for="tag in diagnosis.tags"
                    :key="tag"
                    class="px-2 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded"
                  >
                    {{ tag }}
                  </span>
                </div>
                <button
                  @click.stop="selectDiagnosis(diagnosis)"
                  :class="[
                    'px-4 py-2 rounded-lg text-sm font-medium transition',
                    selectedDiagnosis?.id === diagnosis.id
                      ? 'bg-green-100 text-green-700 hover:bg-green-200'
                      : 'bg-blue-600 text-white hover:bg-blue-700'
                  ]"
                >
                  {{ selectedDiagnosis?.id === diagnosis.id ? 'Seleccionado' : 'Seleccionar' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Sin resultados -->
          <div v-else class="bg-white rounded-xl shadow-sm p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 text-gray-300">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron diagnósticos</h3>
            <p class="text-gray-600 mb-4">Intenta con otros términos o limpia los filtros</p>
            <button
              @click="clearFilters"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
            >
              Mostrar todos
            </button>
          </div>
        </div>

        <!-- Panel derecho - Diagnóstico seleccionado -->
        <div class="lg:col-span-1">
          <!-- Card de diagnóstico seleccionado -->
          <div v-if="selectedDiagnosis" class="sticky top-6">
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl shadow-sm border border-blue-200 p-6 mb-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                  <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Diagnóstico Seleccionado
                </h2>
                <button
                  @click="clearSelection"
                  class="text-gray-400 hover:text-gray-600 p-1"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <div class="bg-white rounded-lg p-5 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                  <div class="px-3 py-2 bg-blue-100 rounded-lg">
                    <span class="font-mono font-bold text-blue-800 text-lg">{{ selectedDiagnosis.code }}</span>
                  </div>
                  <span
                    :class="[
                      'px-3 py-1 text-sm font-bold rounded-full',
                      selectedDiagnosis.urgency === 'high' ? 'bg-red-100 text-red-800' :
                      selectedDiagnosis.urgency === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-green-100 text-green-800'
                    ]"
                  >
                    {{ getUrgencyLabel(selectedDiagnosis.urgency) }}
                  </span>
                </div>

                <h3 class="text-xl font-bold text-gray-900 mb-3">{{ selectedDiagnosis.name }}</h3>

                <div class="space-y-4">
                  <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Síntomas identificados:</p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                      <li v-for="symptom in selectedDiagnosis.symptoms" :key="symptom">
                        {{ symptom }}
                      </li>
                    </ul>
                  </div>

                  <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Categorías:</p>
                    <div class="flex flex-wrap gap-2">
                      <span
                        v-for="tag in selectedDiagnosis.tags"
                        :key="tag"
                        class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full"
                      >
                        {{ tag }}
                      </span>
                    </div>
                  </div>

                  <div class="pt-4 border-t border-gray-200">
                    <button
                      @click="confirmDiagnosis"
                      class="w-full py-3 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition flex items-center justify-center gap-2"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      Confirmar y Agregar
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Panel vacío -->
          <div v-else class="bg-white rounded-xl shadow-sm border border-dashed border-gray-300 p-8 text-center">
            <div class="w-20 h-20 mx-auto mb-4 text-gray-200">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Ningún diagnóstico seleccionado</h3>
            <p class="text-gray-600">Haz clic en una tarjeta para seleccionar un diagnóstico</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'

export default {
  name: 'DiagnosticosDiferenciales',
  
  emits: ['diagnosis-selected', 'diagnosis-confirmed'],
  
  setup(props, { emit }) {
    const searchTerm = ref('')
    const activeUrgency = ref(null)
    const selectedDiagnosis = ref(null)
    const filteredDiagnoses = ref([])
    
    const urgencyFilters = [
      { 
        id: 'high', 
        label: 'Alta Urgencia', 
        bgColor: 'bg-red-50',
        textColor: 'text-red-700',
        dotColor: 'bg-red-500',
        ringColor: 'ring-red-300'
      },
      { 
        id: 'medium', 
        label: 'Urgencia Media', 
        bgColor: 'bg-yellow-50',
        textColor: 'text-yellow-700',
        dotColor: 'bg-yellow-500',
        ringColor: 'ring-yellow-300'
      },
      { 
        id: 'low', 
        label: 'Baja Urgencia', 
        bgColor: 'bg-green-50',
        textColor: 'text-green-700',
        dotColor: 'bg-green-500',
        ringColor: 'ring-green-300'
      }
    ]
    
    const allDiagnoses = [
      {
        id: 1,
        code: 'J06.9',
        name: 'Infección viral respiratoria aguda',
        symptoms: ['Fiebre', 'Tos', 'Congestión nasal', 'Dolor de garganta'],
        urgency: 'medium',
        tags: ['Respiratorio', 'Infeccioso']
      },
      {
        id: 2,
        code: 'I10',
        name: 'Hipertensión esencial',
        symptoms: ['Presión arterial elevada', 'Cefalea', 'Mareos'],
        urgency: 'high',
        tags: ['Cardiovascular', 'Crónico']
      },
      {
        id: 3,
        code: 'M54.5',
        name: 'Lumbalgia',
        symptoms: ['Dolor lumbar', 'Rigidez', 'Limitación movimiento'],
        urgency: 'low',
        tags: ['Musculoesquelético']
      },
      {
        id: 4,
        code: 'F41.1',
        name: 'Trastorno de ansiedad generalizada',
        symptoms: ['Ansiedad', 'Taquicardia', 'Insomnio', 'Inquietud'],
        urgency: 'medium',
        tags: ['Salud Mental']
      },
      {
        id: 5,
        code: 'E11.9',
        name: 'Diabetes mellitus tipo 2',
        symptoms: ['Poliuria', 'Polidipsia', 'Fatiga', 'Visión borrosa'],
        urgency: 'high',
        tags: ['Metabólico', 'Crónico']
      },
      {
        id: 6,
        code: 'J20.9',
        name: 'Bronquitis aguda',
        symptoms: ['Tos productiva', 'Dificultad respiratoria', 'Fiebre baja'],
        urgency: 'medium',
        tags: ['Respiratorio', 'Infeccioso']
      },
      {
        id: 7,
        code: 'K21.9',
        name: 'Enfermedad por reflujo gastroesofágico',
        symptoms: ['Pirosis', 'Regurgitación', 'Dolor torácico'],
        urgency: 'low',
        tags: ['Gastrointestinal']
      },
      {
        id: 8,
        code: 'H66.9',
        name: 'Otitis media aguda',
        symptoms: ['Dolor de oído', 'Fiebre', 'Hipoacusia'],
        urgency: 'medium',
        tags: ['Otorrinolaringológico']
      }
    ]
    
    const filterDiagnoses = () => {
      if (!searchTerm.value.trim() && !activeUrgency.value) {
        filteredDiagnoses.value = [...allDiagnoses]
        return
      }
      
      const term = searchTerm.value.toLowerCase().trim()
      
      filteredDiagnoses.value = allDiagnoses.filter(diagnosis => {
        const matchesSearch = term === '' || 
          diagnosis.name.toLowerCase().includes(term) ||
          diagnosis.code.toLowerCase().includes(term) ||
          diagnosis.symptoms.some(s => s.toLowerCase().includes(term)) ||
          diagnosis.tags.some(t => t.toLowerCase().includes(term))
        
        const matchesUrgency = !activeUrgency.value || 
          diagnosis.urgency === activeUrgency.value
        
        return matchesSearch && matchesUrgency
      })
    }
    
    const toggleUrgencyFilter = (urgency) => {
      activeUrgency.value = activeUrgency.value === urgency ? null : urgency
      filterDiagnoses()
    }
    
    const selectDiagnosis = (diagnosis) => {
      selectedDiagnosis.value = diagnosis
      emit('diagnosis-selected', diagnosis)
    }
    
    const clearSelection = () => {
      selectedDiagnosis.value = null
    }
    
    const clearSearch = () => {
      searchTerm.value = ''
      filterDiagnoses()
    }
    
    const clearFilters = () => {
      searchTerm.value = ''
      activeUrgency.value = null
      filteredDiagnoses.value = [...allDiagnoses]
    }
    
    const confirmDiagnosis = () => {
      if (selectedDiagnosis.value) {
        emit('diagnosis-confirmed', selectedDiagnosis.value)
        selectedDiagnosis.value = null
      }
    }
    
    const getUrgencyLabel = (urgency) => {
      const map = {
        high: 'Alta Urgencia',
        medium: 'Urgencia Media',
        low: 'Baja Urgencia'
      }
      return map[urgency] || 'No especificada'
    }
    
    onMounted(() => {
      filteredDiagnoses.value = [...allDiagnoses]
    })
    
    return {
      searchTerm,
      activeUrgency,
      selectedDiagnosis,
      filteredDiagnoses,
      urgencyFilters,
      allDiagnoses,
      filterDiagnoses,
      toggleUrgencyFilter,
      selectDiagnosis,
      clearSelection,
      clearSearch,
      clearFilters,
      confirmDiagnosis,
      getUrgencyLabel
    }
  }
}
</script>

<style>
/* Smooth transitions */
* {
  transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}
</style>
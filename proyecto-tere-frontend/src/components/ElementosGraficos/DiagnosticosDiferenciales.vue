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
          Selector de Diagnóstico Veterinario
        </h1>
        <p class="text-gray-600 mt-2">Busque y seleccione diagnósticos por síntomas, clasificación o nombre</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-gray-600">Cargando diagnósticos...</span>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
        <div class="flex items-center">
          <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <h3 class="text-lg font-medium text-red-800">Error al cargar diagnósticos</h3>
            <p class="text-red-600 mt-1">{{ error }}</p>
          </div>
        </div>
        <button 
          @click="loadDiagnoses" 
          class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium"
        >
          Reintentar
        </button>
      </div>

      <!-- No autenticado State -->
      <div v-else-if="!isAuthenticated" class="bg-yellow-50 border border-yellow-200 rounded-xl p-8 text-center">
        <div class="w-20 h-20 mx-auto mb-4 text-yellow-400">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <h3 class="text-lg font-medium text-yellow-800 mb-2">Autenticación requerida</h3>
        <p class="text-yellow-600 mb-4">Debes iniciar sesión para acceder a los diagnósticos</p>
        <button 
          @click="redirectToLogin" 
          class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 font-medium"
        >
          Iniciar Sesión
        </button>
      </div>

      <!-- Main Content -->
      <div v-else class="grid lg:grid-cols-3 gap-6">
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
                placeholder="Ej: fiebre, infeccioso, diabetes..."
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

            <!-- Filtros de evolución -->
            <div class="mt-6">
              <p class="text-sm font-medium text-gray-700 mb-3">Filtrar por evolución:</p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="filter in evolutionFilters"
                  :key="filter.id"
                  @click="toggleEvolutionFilter(filter.id)"
                  :class="[
                    'px-4 py-2 rounded-full text-sm font-medium transition-all flex items-center gap-2',
                    activeEvolution === filter.id
                      ? 'ring-2 ring-offset-2'
                      : 'hover:shadow-md',
                    filter.bgColor,
                    filter.textColor,
                    activeEvolution === filter.id ? filter.ringColor : ''
                  ]"
                >
                  <span class="w-2 h-2 rounded-full" :class="filter.dotColor"></span>
                  {{ filter.label }}
                </button>
              </div>
            </div>

            <!-- Filtros de clasificación -->
            <div class="mt-6">
              <p class="text-sm font-medium text-gray-700 mb-3">Filtrar por clasificación:</p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="filter in classificationFilters"
                  :key="filter.id"
                  @click="toggleClassificationFilter(filter.id)"
                  :class="[
                    'px-4 py-2 rounded-full text-sm font-medium transition-all flex items-center gap-2',
                    activeClassification === filter.id
                      ? 'ring-2 ring-offset-2'
                      : 'hover:shadow-md',
                    filter.bgColor,
                    filter.textColor,
                    activeClassification === filter.id ? filter.ringColor : ''
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
                  v-if="activeEvolution || activeClassification || searchTerm"
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
                    <span class="font-mono font-bold text-gray-800">{{ diagnosis.id }}</span>
                  </div>
                  <span
                    :class="[
                      'px-2 py-1 text-xs font-bold rounded-full',
                      getEvolutionColor(diagnosis.evolucion)
                    ]"
                  >
                    {{ getEvolutionLabel(diagnosis.evolucion) }}
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
              <h3 class="font-bold text-lg text-gray-900 mb-2">{{ diagnosis.nombre }}</h3>

              <!-- Descripción breve -->
              <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ truncateText(diagnosis.descripcion, 100) }}</p>

              <!-- Síntomas característicos -->
              <div class="mb-4">
                <p class="text-sm font-medium text-gray-700 mb-2">Síntomas principales:</p>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="symptom in getSymptomsArray(diagnosis.sintomas_caracteristicos)"
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
                    :class="[
                      'px-2 py-1 text-xs font-medium rounded',
                      getClassificationColor(diagnosis.clasificacion)
                    ]"
                  >
                    {{ getClassificationLabel(diagnosis.clasificacion, diagnosis.clasificacion_otro) }}
                  </span>
                  <span
                    v-for="especie in getEspeciesArray(diagnosis.especies)"
                    :key="especie"
                    class="px-2 py-1 bg-green-50 text-green-700 text-xs font-medium rounded"
                  >
                    {{ getEspecieLabel(especie) }}
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
                    <span class="font-mono font-bold text-blue-800 text-lg">ID: {{ selectedDiagnosis.id }}</span>
                  </div>
                  <span
                    :class="[
                      'px-3 py-1 text-sm font-bold rounded-full',
                      getEvolutionColor(selectedDiagnosis.evolucion)
                    ]"
                  >
                    {{ getEvolutionLabel(selectedDiagnosis.evolucion) }}
                  </span>
                </div>

                <h3 class="text-xl font-bold text-gray-900 mb-3">{{ selectedDiagnosis.nombre }}</h3>
                <p class="text-gray-600 mb-4">{{ selectedDiagnosis.descripcion }}</p>

                <div class="space-y-4">
                  <!-- Síntomas característicos -->
                  <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Síntomas característicos:</p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                      <li v-for="symptom in getSymptomsArray(selectedDiagnosis.sintomas_caracteristicos)" :key="symptom">
                        {{ symptom }}
                      </li>
                    </ul>
                  </div>

                  <!-- Exámenes requeridos -->
                  <div v-if="selectedDiagnosis.examenes_requeridos">
                    <p class="text-sm font-medium text-gray-700 mb-2">Exámenes requeridos:</p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                      <li v-for="examen in getExamenesArray(selectedDiagnosis.examenes_requeridos)" :key="examen">
                        {{ examen }}
                      </li>
                    </ul>
                  </div>

                  <!-- Señales clínicas mayores -->
                  <div v-if="selectedDiagnosis.señales_clinicas_mayores">
                    <p class="text-sm font-medium text-gray-700 mb-2">Señales clínicas mayores:</p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                      <li v-for="señal in getSeñalesArray(selectedDiagnosis.señales_clinicas_mayores)" :key="señal">
                        {{ señal }}
                      </li>
                    </ul>
                  </div>

                  <!-- Especies -->
                  <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Especies:</p>
                    <div class="flex flex-wrap gap-2">
                      <span
                        v-for="especie in getEspeciesArray(selectedDiagnosis.especies)"
                        :key="especie"
                        class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full"
                      >
                        {{ getEspecieLabel(especie) }}
                      </span>
                    </div>
                  </div>

                  <!-- Clasificación -->
                  <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Clasificación:</p>
                    <span :class="['px-3 py-1 text-sm font-bold rounded-full', getClassificationColor(selectedDiagnosis.clasificacion)]">
                      {{ getClassificationLabel(selectedDiagnosis.clasificacion, selectedDiagnosis.clasificacion_otro) }}
                    </span>
                  </div>

                  <!-- Botón de confirmación -->
                  <div class="pt-4 border-t border-gray-200">
                    <button
                      @click="confirmDiagnosis"
                      class="w-full py-3 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition flex items-center justify-center gap-2"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      Confirmar Diagnóstico
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

<!-- En tu componente DiagnosticosDiferenciales.vue -->
<script>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'DiagnosticosDiferenciales',
  
  props: {
    // Opcional: recibir diagnósticos ya seleccionados si se usa en modo múltiple
    seleccionados: {
      type: Array,
      default: () => []
    },
    modoMultiSeleccion: {
      type: Boolean,
      default: true
    }
  },
  
  emits: ['diagnosis-selected', 'diagnosis-confirmed', 'seleccion-finalizada'],
  
  setup(props, { emit }) {
    const router = useRouter()
    const searchTerm = ref('')
    const activeEvolution = ref(null)
    const activeClassification = ref(null)
    const selectedDiagnosis = ref(null)
    const filteredDiagnoses = ref([])
    const allDiagnoses = ref([])
    const loading = ref(true)
    const error = ref(null)
    const isAuthenticated = ref(false)
    
    // Para modo múltiple selección
    const seleccionMultiple = ref([])

    // AÑADE ESTA FUNCIÓN
    const clearSelection = () => {
      console.log('🧹 Limpiando selección actual')
      selectedDiagnosis.value = null
      // Si quieres también limpiar selección múltiple
      // seleccionMultiple.value = []
    }
    
    // AÑADE ESTAS FUNCIONES TAMBIÉN (las veo en el template)
    const clearSearch = () => {
      searchTerm.value = ''
      filterDiagnoses()
    }
    
    const clearFilters = () => {
      searchTerm.value = ''
      activeEvolution.value = null
      activeClassification.value = null
      filterDiagnoses()
    }
    
    // Configurar axios con interceptores para incluir el token
    const setupAxios = () => {
      // Obtener token del localStorage o cookie
      const token = localStorage.getItem('token') || getCookie('token')
      
      if (token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
        axios.defaults.withCredentials = true
        axios.defaults.withXSRFToken = true
        
        isAuthenticated.value = true
      } else {
        isAuthenticated.value = false
      }
    }
    
    // Helper para obtener cookies
    const getCookie = (name) => {
      const value = `; ${document.cookie}`
      const parts = value.split(`; ${name}=`)
      if (parts.length === 2) return parts.pop().split(';').shift()
      return null
    }
    
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
    
    // Método para cargar diagnósticos desde la API
    const loadDiagnoses = async () => {
      try {
        loading.value = true
        error.value = null
        
        // Verificar autenticación primero
        setupAxios()
        
        if (!isAuthenticated.value) {
          error.value = 'No estás autenticado. Por favor, inicia sesión.'
          loading.value = false
          return
        }
        
        const response = await axios.get('/api/tipos-diagnostico')
        
        if (response.data.success) {
          allDiagnoses.value = response.data.data
          filteredDiagnoses.value = [...allDiagnoses.value]
        } else {
          throw new Error('Error en la respuesta del servidor')
        }
      } catch (err) {
        console.error('Error al cargar diagnósticos:', err)
        
        if (err.response?.status === 401) {
          error.value = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.'
          isAuthenticated.value = false
          // Limpiar token inválido
          localStorage.removeItem('token')
          delete axios.defaults.headers.common['Authorization']
        } else if (err.response?.status === 403) {
          error.value = 'No tienes permiso para acceder a esta información.'
        } else if (err.response?.data?.message) {
          error.value = err.response.data.message
        } else if (err.message === 'Network Error') {
          error.value = 'Error de conexión. Verifica tu conexión a internet.'
        } else {
          error.value = 'No se pudieron cargar los diagnósticos. Intenta nuevamente.'
        }
      } finally {
        loading.value = false
      }
    }
    
    // Método para redirigir al login
    const redirectToLogin = () => {
      router.push('/login')
    }
    
    // Métodos auxiliares para procesar datos
    const getSymptomsArray = (symptoms) => {
      if (!symptoms) return []
      return symptoms.split(',').map(s => s.trim()).filter(s => s.length > 0)
    }
    
    const getExamenesArray = (examenes) => {
      if (!examenes) return []
      return examenes.split(',').map(e => e.trim()).filter(e => e.length > 0)
    }
    
    const getSeñalesArray = (señales) => {
      if (!señales) return []
      return señales.split(',').map(s => s.trim()).filter(s => s.length > 0)
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
    
    const truncateText = (text, length) => {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
    }
    
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
    
    // Métodos de filtrado
    const filterDiagnoses = () => {
      if (!searchTerm.value.trim() && !activeEvolution.value && !activeClassification.value) {
        filteredDiagnoses.value = [...allDiagnoses.value]
        return
      }
      
      const term = searchTerm.value.toLowerCase().trim()
      
      filteredDiagnoses.value = allDiagnoses.value.filter(diagnosis => {
        // Filtro por búsqueda
        const matchesSearch = term === '' || 
          diagnosis.nombre?.toLowerCase().includes(term) ||
          diagnosis.descripcion?.toLowerCase().includes(term) ||
          (diagnosis.sintomas_caracteristicos && diagnosis.sintomas_caracteristicos.toLowerCase().includes(term)) ||
          (diagnosis.clasificacion && diagnosis.clasificacion.toLowerCase().includes(term)) ||
          (diagnosis.clasificacion_otro && diagnosis.clasificacion_otro.toLowerCase().includes(term))
        
        // Filtro por evolución
        const matchesEvolution = !activeEvolution.value || 
          diagnosis.evolucion === activeEvolution.value
        
        // Filtro por clasificación
        const matchesClassification = !activeClassification.value || 
          diagnosis.clasificacion === activeClassification.value
        
        return matchesSearch && matchesEvolution && matchesClassification
      })
    }
    
    const toggleEvolutionFilter = (evolution) => {
      activeEvolution.value = activeEvolution.value === evolution ? null : evolution
      filterDiagnoses()
    }
    
    const toggleClassificationFilter = (classification) => {
      activeClassification.value = activeClassification.value === classification ? null : classification
      filterDiagnoses()
    }
    
    const selectDiagnosis = (diagnosis) => {
      console.log('🎯 Diagnóstico seleccionado:', diagnosis)
      selectedDiagnosis.value = diagnosis
      
      // Emitir evento con todos los datos del diagnóstico
      emit('diagnosis-selected', {
        id: diagnosis.id,
        nombre: diagnosis.nombre,
        descripcion: diagnosis.descripcion,
        evolucion: diagnosis.evolucion,
        clasificacion: diagnosis.clasificacion,
        clasificacion_otro: diagnosis.clasificacion_otro,
        especies: diagnosis.especies,
        sintomas_caracteristicos: diagnosis.sintomas_caracteristicos,
        examenes_requeridos: diagnosis.examenes_requeridos,
        ...diagnosis
      })
    }
    
    const confirmDiagnosis = () => {
      if (selectedDiagnosis.value) {
        console.log('✅ Diagnóstico confirmado:', selectedDiagnosis.value)
        emit('diagnosis-confirmed', {
          id: selectedDiagnosis.value.id,
          nombre: selectedDiagnosis.value.nombre,
          descripcion: selectedDiagnosis.value.descripcion,
          evolucion: selectedDiagnosis.value.evolucion,
          clasificacion: selectedDiagnosis.value.clasificacion,
          ...selectedDiagnosis.value
        })
      }
    }
    
    // Método para agregar a selección múltiple
    const agregarASeleccionMultiple = (diagnosis) => {
      if (!props.modoMultiSeleccion) return
      
      const existe = seleccionMultiple.value.some(d => d.id === diagnosis.id)
      if (!existe) {
        seleccionMultiple.value.push({
          id: diagnosis.id,
          nombre: diagnosis.nombre,
          descripcion: diagnosis.descripcion,
          evolucion: diagnosis.evolucion,
          clasificacion: diagnosis.clasificacion,
          ...diagnosis
        })
        console.log('📌 Agregado a selección múltiple:', diagnosis.nombre)
      }
    }
    
    // Método para obtener diagnóstico actualmente seleccionado
    const getDiagnosticoSeleccionado = () => {
      return selectedDiagnosis.value ? {
        id: selectedDiagnosis.value.id,
        nombre: selectedDiagnosis.value.nombre,
        descripcion: selectedDiagnosis.value.descripcion,
        evolucion: selectedDiagnosis.value.evolucion,
        clasificacion: selectedDiagnosis.value.clasificacion,
        ...selectedDiagnosis.value
      } : null
    }
    
    // Método para obtener todos los diagnósticos seleccionados
    const getDiagnosticosSeleccionados = () => {
      if (props.modoMultiSeleccion) {
        return [...seleccionMultiple.value]
      } else {
        return selectedDiagnosis.value ? [{
          id: selectedDiagnosis.value.id,
          nombre: selectedDiagnosis.value.nombre,
          descripcion: selectedDiagnosis.value.descripcion,
          evolucion: selectedDiagnosis.value.evolucion,
          clasificacion: selectedDiagnosis.value.clasificacion,
          ...selectedDiagnosis.value
        }] : []
      }
    }
    
    // Método para finalizar selección múltiple
    const finalizarSeleccion = () => {
      if (props.modoMultiSeleccion) {
        emit('seleccion-finalizada', seleccionMultiple.value)
      } else if (selectedDiagnosis.value) {
        emit('seleccion-finalizada', [selectedDiagnosis.value])
      }
    }
    
    // Cargar diagnósticos al montar el componente
    onMounted(() => {
      setupAxios()
      loadDiagnoses()
      
      // Inicializar selección múltiple si se pasan diagnósticos
      if (props.seleccionados && props.seleccionados.length > 0) {
        seleccionMultiple.value = [...props.seleccionados]
      }
    })
    
    return {
      searchTerm,
      activeEvolution,
      activeClassification,
      selectedDiagnosis,
      filteredDiagnoses,
      allDiagnoses,
      loading,
      error,
      isAuthenticated,
      evolutionFilters,
      classificationFilters,
      loadDiagnoses,
      redirectToLogin,
      filterDiagnoses,
      toggleEvolutionFilter,
      toggleClassificationFilter,
      selectDiagnosis,
      clearSelection,
      clearSearch,
      clearFilters,
      confirmDiagnosis,
      agregarASeleccionMultiple,
      getDiagnosticoSeleccionado,
      getDiagnosticosSeleccionados,
      finalizarSeleccion,
      getSymptomsArray,
      getExamenesArray,
      getSeñalesArray,
      getEspeciesArray,
      truncateText,
      getEvolutionLabel,
      getEvolutionColor,
      getClassificationLabel,
      getClassificationColor,
      getEspecieLabel
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

/* Clase para limitar líneas de texto */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
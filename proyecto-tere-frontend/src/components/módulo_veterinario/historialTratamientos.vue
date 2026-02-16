<template>
  <div class="max-w-6xl mx-auto p-6 bg-white rounded-lg shadow-sm">
    <!-- Encabezado -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-2xl font-light text-gray-800">HISTORIAL MÉDICO GENERAL</h1>
        <p class="text-sm text-gray-500 mt-1">Procedimientos realizados por {{ veterinarioNombre }}</p>
      </div>
      <div class="flex items-center space-x-3">
        <button @click="recargarDatos" class="p-2 rounded-full bg-gray-100 hover:bg-gray-200" title="Recargar">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mostrar errores -->
    <div v-if="errorCarga" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
      <div class="flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400 mr-3" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <p class="text-red-700">{{ errorCarga }}</p>
        <button @click="recargarDatos" class="ml-auto text-sm text-red-600 hover:text-red-800 font-medium">
          Reintentar
        </button>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
      <div class="flex items-center space-x-3">
        <div class="relative">
          <select 
            v-model="filtroCategoria" 
            class="appearance-none bg-white border border-gray-300 rounded-md pl-3 pr-8 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
            @change="filtrarProcedimientos"
          >
            <option value="">Todas las categorías</option>
<option v-for="categoria in categorias" :key="categoria" :value="categoria">
              {{ getNombreCategoria(categoria) }}
            </option>
          </select>
        </div>
        <div class="relative">
          <input 
            type="date" 
            v-model="filtroFecha" 
            @change="filtrarProcedimientos"
            class="bg-white border border-gray-300 rounded-md pl-3 pr-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
        </div>
      </div>
      <div class="relative w-full md:w-64">
        <input 
          type="text" 
          v-model="busqueda" 
          @input="filtrarProcedimientos"
          placeholder="Buscar paciente o procedimiento..." 
          class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
        >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
    </div>

    <!-- Estado de carga y mensajes -->
    <div v-if="cargando && !errorCarga" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="text-gray-500 mt-2">Cargando procedimientos...</p>
    </div>

    <div v-else-if="procedimientosFiltrados.length === 0 && !errorCarga" class="text-center py-8">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-gray-500 mt-2">No se encontraron procedimientos</p>
    </div>

    <!-- Lista de procedimientos -->
    <div v-else-if="!errorCarga" class="space-y-4">
      <!-- Tarjeta de procedimiento -->
      <div v-for="procedimiento in procedimientosFiltrados" :key="procedimiento.id" 
           class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow duration-200">
        <div class="flex flex-col md:flex-row">
          <!-- Columna izquierda - Imagen de la mascota -->
          <div class="w-full md:w-32 bg-gray-50 p-4 flex flex-col items-center justify-center border-b md:border-b-0 md:border-r border-gray-200">
            <div v-if="procedimiento.mascota?.foto_url" class="w-16 h-16 rounded-full overflow-hidden mb-2">
              <img 
                :src="procedimiento.mascota.foto_url" 
                :alt="procedimiento.mascota.nombre"
                class="w-full h-full object-cover"
              />
            </div>
            <div v-else class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center mb-2">
              <span class="text-2xl font-bold text-gray-600">
                {{ procedimiento.mascota?.nombre?.charAt(0) || 'M' }}
              </span>
            </div>
            <div class="text-xs text-gray-500 font-medium text-center">
              {{ procedimiento.mascota?.nombre || 'Sin nombre' }}
            </div>
          </div>
          
          <!-- Columna central - Información del procedimiento -->
          <div class="flex-1 p-4">
            <div class="flex justify-between items-start">
              <div>
                <!-- Nombre del procedimiento con tipo -->
                <h3 class="font-medium text-gray-900">
                  {{ procedimiento.nombre_procedimiento || procedimiento.tipo_procedimiento || procedimiento.procesable?.nombre || 'Procedimiento médico' }}
                </h3>
                <div class="flex items-center mt-1 space-x-3">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" :class="getColorClase(procedimiento.categoria)">
                    {{ getNombreCategoria(procedimiento.categoria) }}
                  </span>
                  <span class="text-xs text-gray-500">
                    {{ formatFecha(procedimiento.fecha_aplicacion) }}
                  </span>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                  <span class="font-medium text-gray-700">{{ procedimiento.mascota?.nombre || 'Sin nombre' }}</span>
                  <span class="ml-2">({{ procedimiento.mascota?.especie || 'N/A' }})</span>
                  <span class="ml-4">•</span>
                  <span class="ml-4">Centro: {{ procedimiento.centro_veterinario?.nombre || 'Sin centro' }}</span>
                </p>
              </div>
              <div class="flex items-center space-x-2">
                <span class="font-medium text-gray-700">${{ procedimiento.costo || '0.00' }}</span>
                <button 
                  @click="toggleDetalles(procedimiento.id)" 
                  class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                >
                  {{ procedimiento.mostrarDetalles ? 'Ocultar' : 'Ver detalles' }}
                </button>
              </div>
            </div>
            
            <!-- Detalles expandibles -->
            <div v-if="procedimiento.mostrarDetalles" class="mt-4 pt-4 border-t border-gray-100">
              <!-- Información de la mascota -->
              <div class="mb-4">
                <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Información del paciente</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div>
                    <p class="text-xs text-gray-500">Edad</p>
                    <p class="text-sm font-medium">{{ procedimiento.mascota?.edad || 'No disponible' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Sexo</p>
                    <p class="text-sm font-medium">{{ procedimiento.mascota?.sexo || 'No disponible' }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">Castrado</p>
                    <p class="text-sm font-medium">{{ procedimiento.mascota?.castrado ? 'Sí' : 'No' }}</p>
                  </div>
                </div>
              </div>
              
              <!-- Descripción del procedimiento -->
              <div v-if="procedimiento.procesable?.descripcion || procedimiento.observaciones" class="mb-4">
                <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Descripción</h4>
                <p class="text-sm text-gray-700">{{ procedimiento.procesable?.descripcion || procedimiento.observaciones || 'Sin descripción' }}</p>
              </div>
              
              <!-- Fármacos asociados -->
              <div v-if="procedimiento.farmacosAsociados?.length > 0" class="mb-4">
                <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Medicamentos</h4>
                <div class="space-y-2">
                  <div v-for="(farmaco, farmacoIndex) in procedimiento.farmacosAsociados" :key="farmacoIndex" 
                       class="bg-gray-50 p-3 rounded-lg">
                    <p class="font-medium text-gray-800">{{ farmaco.nombre }}</p>
                    <p class="text-sm text-gray-600 mt-1">
                      Dosis: {{ farmaco.dosis }}
                      <span v-if="farmaco.frecuencia" class="ml-3">Frecuencia: {{ farmaco.frecuencia }}</span>
                      <span v-if="farmaco.via_administracion" class="ml-3">Vía: {{ farmaco.via_administracion }}</span>
                    </p>
                  </div>
                </div>
              </div>
              
              <!-- Detalles específicos del procedimiento -->
              <div v-if="procedimiento.procesable?.detalles && Object.keys(procedimiento.procesable.detalles).length > 0" class="mb-4">
                <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Detalles específicos</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div v-for="(valor, clave) in procedimiento.procesable.detalles" :key="clave" 
                       class="flex justify-between items-center border-b border-gray-100 pb-2">
                    <span class="text-sm text-gray-600 capitalize">{{ clave.replace(/_/g, ' ') }}:</span>
                    <span class="text-sm font-medium">{{ valor }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Botón para ver más detalles -->
              <div class="flex justify-end mt-4">
                <button 
                  @click="verDetallesCompletos(procedimiento.id)"
                  class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium"
                >
                  Ver detalles completos
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Paginación -->
    <div v-if="!cargando && procedimientosFiltrados.length > 0 && !errorCarga" class="mt-8 flex items-center justify-between">
      <div class="text-sm text-gray-500">
        Mostrando <span class="font-medium">{{ procedimientosFiltrados.length }}</span> de 
        <span class="font-medium">{{ procedimientos.length }}</span> procedimientos
      </div>
    </div>

    <!-- Modal para detalles completos -->
    <div v-if="modalDetallesVisible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-start mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Detalles completos del procedimiento</h2>
            <button @click="cerrarModal" class="text-gray-400 hover:text-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div v-if="detallesActuales">
            <!-- Información general -->
            <div class="mb-6">
              <h3 class="text-lg font-medium text-gray-800 mb-4">Información general</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-500">Tipo de procedimiento</p>
                  <p class="font-medium">{{ detallesActuales.tipo_procedimiento }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Fecha</p>
                  <p class="font-medium">{{ detallesActuales.fecha_formateada }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Costo</p>
                  <p class="font-medium">${{ detallesActuales.costo }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Estado</p>
                  <p class="font-medium">{{ detallesActuales.estado || 'Completado' }}</p>
                </div>
              </div>
            </div>
            
            <!-- Información del paciente -->
            <div class="mb-6">
              <h3 class="text-lg font-medium text-gray-800 mb-4">Información del paciente</h3>
              <div class="flex items-start space-x-4">
                <div v-if="detallesActuales.mascota?.fotos?.length" class="w-24 h-24 rounded-lg overflow-hidden flex-shrink-0">
                  <img 
                    :src="detallesActuales.mascota.fotos[0].url" 
                    :alt="detallesActuales.mascota.nombre"
                    class="w-full h-full object-cover"
                  />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-grow">
                  <div>
                    <p class="text-sm text-gray-500">Nombre</p>
                    <p class="font-medium">{{ detallesActuales.mascota?.nombre }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Especie/Raza</p>
                    <p class="font-medium">{{ detallesActuales.mascota?.especie }} - {{ detallesActuales.mascota?.raza || 'No especificada' }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Edad</p>
                    <p class="font-medium">{{ detallesActuales.mascota?.edad }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Sexo</p>
                    <p class="font-medium">{{ detallesActuales.mascota?.sexo }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Castrado</p>
                    <p class="font-medium">{{ detallesActuales.mascota?.castrado ? 'Sí' : 'No' }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-500">Peso</p>
                    <p class="font-medium">{{ detallesActuales.mascota?.peso || 'No registrado' }}</p>
                  </div>
                  <div v-if="detallesActuales.mascota?.tutor" class="md:col-span-2">
                    <p class="text-sm text-gray-500">Tutor</p>
                    <p class="font-medium">{{ detallesActuales.mascota.tutor.nombre }}</p>
                    <p class="text-sm text-gray-600">{{ detallesActuales.mascota.tutor.email }} - {{ detallesActuales.mascota.tutor.telefono || 'Sin teléfono' }}</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Detalles del procedimiento -->
            <div class="mb-6">
              <h3 class="text-lg font-medium text-gray-800 mb-4">Detalles del procedimiento</h3>
              <div v-if="detallesActuales.procedimiento_especifico">
                <div class="mb-4">
                  <p class="text-sm text-gray-500">Tipo específico</p>
                  <p class="font-medium">{{ detallesActuales.procedimiento_especifico.tipo }} - {{ detallesActuales.procedimiento_especifico.nombre }}</p>
                </div>
                <div v-if="detallesActuales.procedimiento_especifico.descripcion">
                  <p class="text-sm text-gray-500">Descripción</p>
                  <p class="text-gray-700">{{ detallesActuales.procedimiento_especifico.descripcion }}</p>
                </div>
              </div>
              <div v-if="detallesActuales.observaciones">
                <p class="text-sm text-gray-500">Observaciones</p>
                <p class="text-gray-700">{{ detallesActuales.observaciones }}</p>
              </div>
            </div>
            
            <!-- Fármacos -->
            <div v-if="detallesActuales.farmacos?.length" class="mb-6">
              <h3 class="text-lg font-medium text-gray-800 mb-4">Medicamentos administrados</h3>
              <div class="space-y-3">
                <div v-for="farmaco in detallesActuales.farmacos" :key="farmaco.id" 
                     class="border border-gray-200 rounded-lg p-4">
                  <div class="flex justify-between items-start">
                    <div>
                      <p class="font-medium text-gray-800">{{ farmaco.nombre }}</p>
                      <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                          <p class="text-xs text-gray-500">Dosis</p>
                          <p class="text-sm font-medium">{{ farmaco.dosis }}</p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">Frecuencia</p>
                          <p class="text-sm font-medium">{{ farmaco.frecuencia || 'No especificada' }}</p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">Vía de administración</p>
                          <p class="text-sm font-medium">{{ farmaco.via_administracion || 'No especificada' }}</p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">Duración</p>
                          <p class="text-sm font-medium">{{ farmaco.duracion || 'No especificada' }}</p>
                        </div>
                      </div>
                      <div v-if="farmaco.observaciones" class="mt-3">
                        <p class="text-xs text-gray-500">Observaciones</p>
                        <p class="text-sm text-gray-700">{{ farmaco.observaciones }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Información del veterinario y centro -->
            <div class="pt-6 border-t border-gray-200">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-if="detallesActuales.centro_veterinario">
                  <h4 class="font-medium text-gray-800 mb-2">Centro veterinario</h4>
                  <p class="text-sm text-gray-700">{{ detallesActuales.centro_veterinario.nombre }}</p>
                  <p class="text-sm text-gray-600">{{ detallesActuales.centro_veterinario.direccion }}</p>
                  <p class="text-sm text-gray-600">{{ detallesActuales.centro_veterinario.telefono }} - {{ detallesActuales.centro_veterinario.email }}</p>
                </div>
                <div v-if="detallesActuales.veterinario">
                  <h4 class="font-medium text-gray-800 mb-2">Veterinario responsable</h4>
                  <p class="text-sm text-gray-700">{{ detallesActuales.veterinario.nombre }}</p>
                  <p class="text-sm text-gray-600">{{ detallesActuales.veterinario.matricula || 'Sin matrícula' }} - {{ detallesActuales.veterinario.especialidad || 'Sin especialidad' }}</p>
                  <p class="text-sm text-gray-600">{{ detallesActuales.veterinario.email }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useRouter } from 'vue-router'
import axios from 'axios'

// Inicializar
const auth = useAuth()
const router = useRouter()

// Estados reactivos
const procedimientos = ref([])
const procedimientosFiltrados = ref([])
const cargando = ref(true)
const filtroCategoria = ref('')
const filtroFecha = ref('')
const busqueda = ref('')
const veterinarioNombre = ref('')
const errorCarga = ref(null)
const modalDetallesVisible = ref(false)
const detallesActuales = ref(null)

// Computed para verificar autenticación - SOLO POR TOKEN
const tieneToken = computed(() => {
  const token = auth.accessToken.value
  console.log('🔐 Token actual:', token ? 'Presente' : 'Ausente')
  return !!token
})

// Categorías disponibles
const categorias = ref([])

// Función para obtener los procedimientos
async function cargarProcedimientos() {
  try {
    cargando.value = true
    errorCarga.value = null
    
    console.log('🔍 Verificando autenticación para cargar procedimientos...')
    console.log('🔐 Token disponible:', tieneToken.value)
    console.log('👤 Usuario cargado:', auth.user.value ? 'Sí' : 'No')
    
    // Verificar autenticación solo por token
    if (!tieneToken.value) {
      errorCarga.value = 'No estás autenticado. Por favor, inicia sesión.'
      console.warn('Usuario no autenticado (sin token), no se pueden cargar procedimientos')
      return
    }
    
    // Configurar headers con el token
    const headers = {
      'Accept': 'application/json',
      'Authorization': `Bearer ${auth.accessToken.value}`
    }
    
    console.log('📋 Headers configurados:', { 
      Authorization: headers.Authorization ? 'Bearer [TOKEN PRESENTE]' : 'No configurado' 
    })
    
    // USAR LA RUTA CORRECTA - la que definiste en el controlador
    const endpoint = '/api/veterinario/procedimientos'
    console.log(`🔄 Cargando procedimientos desde: ${endpoint}`)
    
    const response = await axios.get(endpoint, { 
      headers, 
      timeout: 10000 
    })
    
    console.log('📦 Respuesta recibida:', response.data)
    
    // Procesar la respuesta
    if (response && response.data && response.data.success) {
      let procedimientosData = response.data.data || []
      
      console.log(`📊 ${procedimientosData.length} procedimientos encontrados`)
      
      // Extraer categorías únicas
      const categoriasUnicas = [...new Set(procedimientosData.map(p => p.categoria).filter(Boolean))]
      categorias.value = categoriasUnicas
      
      procedimientos.value = procedimientosData.map(procedimiento => ({
        ...procedimiento,
        mostrarDetalles: false,
        // Asegurar que los datos estén presentes
        mascota: procedimiento.mascota || { nombre: 'Sin nombre', especie: 'N/A' },
        procesable: procedimiento.procesable || { nombre: 'Procedimiento médico' }
      }))
      
      veterinarioNombre.value = response.data.veterinario?.nombre_completo || 
                                auth.user.value?.userable?.nombre_completo || 
                                auth.user.value?.name || 
                                auth.user.value?.email?.split('@')[0] || 
                                'Veterinario'
      
      // Inicializar filtrados
      procedimientosFiltrados.value = [...procedimientos.value]
      
      console.log('✅ Procedimientos cargados exitosamente')
    } else {
      throw new Error(response.data?.message || 'Respuesta inválida del servidor')
    }
    
  } catch (error) {
    console.error('❌ Error al cargar procedimientos:', error)
    
    if (error.response) {
      console.error('Detalles del error:', {
        status: error.response.status,
        data: error.response.data,
        headers: error.response.config?.headers
      })
      
      if (error.response.status === 401) {
        errorCarga.value = 'Sesión expirada. Por favor, vuelve a iniciar sesión.'
        console.log('🔒 Token inválido o expirado, limpiando...')
        auth.accessToken.value = null
        localStorage.removeItem('token')
        setTimeout(() => {
          router.push('/login')
        }, 2000)
      } else if (error.response.status === 403) {
        errorCarga.value = 'No tienes permiso para acceder a estos procedimientos.'
      } else if (error.response.status === 404) {
        errorCarga.value = 'El servicio de procedimientos no está disponible.'
      } else {
        errorCarga.value = error.response.data?.message || 'Error al cargar procedimientos'
      }
    } else if (error.code === 'ECONNABORTED' || error.message.includes('Network Error')) {
      errorCarga.value = 'Error de conexión. Verifica tu internet.'
    } else {
      errorCarga.value = error.message || 'Error desconocido al cargar procedimientos'
    }
    
    // Cargar datos de ejemplo solo en desarrollo
    if (import.meta.env.DEV && procedimientos.value.length === 0) {
      console.log('🧪 Cargando datos de ejemplo para desarrollo...')
      cargarDatosEjemplo()
    }
  } finally {
    cargando.value = false
  }
}

// Función para cargar datos de ejemplo (solo desarrollo)
function cargarDatosEjemplo() {
  console.log('🧪 Cargando datos de ejemplo...')
  
  procedimientos.value = [
    {
      id: 1,
      categoria: 'vacuna',
      nombre_procedimiento: 'Vacuna - Antirrábica',
      fecha_aplicacion: '2023-03-15T10:30:00Z',
      fecha_formateada: '15/03/2023 10:30',
      observaciones: 'Paciente respondió bien, sin reacciones adversas',
      costo: 45.50,
      mascota: { 
        id: 1,
        nombre: 'Max', 
        especie: 'Canino',
        edad: '3 años',
        sexo: 'Macho',
        castrado: true,
        foto_url: null
      },
      centro_veterinario: { 
        id: 1,
        nombre: 'Clínica Veterinaria Central'
      },
      procesable: {
        nombre: 'Vacuna Antirrábica',
        descripcion: 'Aplicación de vacuna antirrábica anual',
        detalles: {
          lote: 'ABC123',
          fabricante: 'VetPharma',
          via_administracion: 'Intramuscular'
        }
      },
      farmacosAsociados: [],
      mostrarDetalles: false
    },
    {
      id: 2,
      categoria: 'cirugia',
      nombre_procedimiento: 'Cirugía - Esterilización',
      fecha_aplicacion: '2023-02-10T14:00:00Z',
      fecha_formateada: '10/02/2023 14:00',
      observaciones: 'Cirugía exitosa, paciente en recuperación',
      costo: 350.00,
      mascota: { 
        id: 2,
        nombre: 'Luna', 
        especie: 'Felino',
        edad: '2 años',
        sexo: 'Hembra',
        castrado: true,
        foto_url: null
      },
      centro_veterinario: { 
        id: 2,
        nombre: 'Hospital Veterinario Norte'
      },
      procesable: {
        nombre: 'Esterilización',
        descripcion: 'Ovariohisterectomía rutinaria',
        detalles: {
          tipo: 'Ovariohisterectomía',
          duracion: '45 minutos',
          anestesia: 'Isofllurano'
        }
      },
      farmacosAsociados: [
        { 
          nombre: 'Antibiótico VetMax', 
          dosis: '1 tableta', 
          frecuencia: '12 horas'
        },
        { 
          nombre: 'Analgésico PetPain', 
          dosis: '0.5 ml', 
          frecuencia: '24 horas'
        }
      ],
      mostrarDetalles: false
    }
  ]
  
  categorias.value = ['vacuna', 'cirugia', 'consulta', 'medicamento']
  procedimientosFiltrados.value = [...procedimientos.value]
  veterinarioNombre.value = auth.user.value?.name || 'Dr. Veterinario'
  errorCarga.value = null
}

// Función para filtrar procedimientos
function filtrarProcedimientos() {
  let filtrados = procedimientos.value

  // Filtrar por categoría
  if (filtroCategoria.value) {
    filtrados = filtrados.filter(p => p.categoria === filtroCategoria.value)
  }

  // Filtrar por fecha
  if (filtroFecha.value) {
    const fechaFiltro = new Date(filtroFecha.value).toDateString()
    filtrados = filtrados.filter(p => {
      if (!p.fecha_aplicacion) return false
      const fechaProc = new Date(p.fecha_aplicacion).toDateString()
      return fechaProc === fechaFiltro
    })
  }

  // Filtrar por búsqueda
  if (busqueda.value) {
    const termino = busqueda.value.toLowerCase()
    filtrados = filtrados.filter(p => 
      (p.mascota?.nombre?.toLowerCase().includes(termino)) ||
      (p.nombre_procedimiento?.toLowerCase().includes(termino)) ||
      (p.tipo_procedimiento?.toLowerCase().includes(termino)) ||
      (p.procesable?.nombre?.toLowerCase().includes(termino)) ||
      (p.observaciones?.toLowerCase().includes(termino))
    )
  }

  procedimientosFiltrados.value = filtrados
}

// Función para mostrar/ocultar detalles
function toggleDetalles(id) {
  const index = procedimientos.value.findIndex(p => p.id === id)
  if (index !== -1) {
    procedimientos.value[index].mostrarDetalles = !procedimientos.value[index].mostrarDetalles
    // Actualizar también en filtrados
    const filtradoIndex = procedimientosFiltrados.value.findIndex(p => p.id === id)
    if (filtradoIndex !== -1) {
      procedimientosFiltrados.value[filtradoIndex].mostrarDetalles = procedimientos.value[index].mostrarDetalles
    }
  }
}

// Función para ver detalles completos
async function verDetallesCompletos(id) {
  try {
    // Verificar token primero
    if (!tieneToken.value) {
      errorCarga.value = 'No estás autenticado. Por favor, inicia sesión.'
      return
    }
    
    const headers = {
      'Accept': 'application/json',
      'Authorization': `Bearer ${auth.accessToken.value}`
    }
    
    console.log(`🔍 Cargando detalles del procedimiento ${id}...`)
    
    const response = await axios.get(`/api/veterinario/procedimientos/${id}/detalles`, { 
      headers, 
      timeout: 10000 
    })
    
    if (response.data.success) {
      detallesActuales.value = response.data.data
      modalDetallesVisible.value = true
      console.log('✅ Detalles cargados exitosamente')
    } else {
      throw new Error(response.data.message)
    }
  } catch (error) {
    console.error('❌ Error al cargar detalles:', error)
    if (error.response?.status === 401) {
      errorCarga.value = 'Sesión expirada. Por favor, vuelve a iniciar sesión.'
      auth.accessToken.value = null
      localStorage.removeItem('token')
      setTimeout(() => {
        router.push('/login')
      }, 2000)
    } else {
      alert('No se pudieron cargar los detalles completos del procedimiento')
    }
  }
}

// Función para cerrar modal
function cerrarModal() {
  modalDetallesVisible.value = false
  detallesActuales.value = null
}

// Función para formatear fecha
function formatFecha(fechaString) {
  if (!fechaString) return 'Fecha no disponible'
  
  try {
    const fecha = new Date(fechaString)
    return fecha.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    console.error('Error formateando fecha:', error)
    return fechaString
  }
}

// Función para obtener nombre de categoría
function getNombreCategoria(categoria) {
  const nombres = {
    vacuna: 'Vacunación',
    cirugia: 'Cirugía',
    consulta: 'Consulta',
    medicamento: 'Medicamento',
    analisis: 'Análisis',
    terapia: 'Terapia',
    desparasitacion: 'Desparasitación',
    revision: 'Revisión',
    diagnostico: 'Diagnóstico',
    preventivo: 'Preventivo',
    clinico: 'Clínico',
    emergencia: 'Emergencia'
  }
  return nombres[categoria] || categoria.charAt(0).toUpperCase() + categoria.slice(1)
}

// Función para obtener clase de color
function getColorClase(categoria) {
  const colores = {
    vacuna: 'bg-green-100 text-green-800',
    cirugia: 'bg-purple-100 text-purple-800',
    consulta: 'bg-blue-100 text-blue-800',
    medicamento: 'bg-yellow-100 text-yellow-800',
    analisis: 'bg-indigo-100 text-indigo-800',
    terapia: 'bg-pink-100 text-pink-800',
    desparasitacion: 'bg-orange-100 text-orange-800',
    revision: 'bg-teal-100 text-teal-800',
    diagnostico: 'bg-red-100 text-red-800',
    preventivo: 'bg-green-100 text-green-800',
    clinico: 'bg-red-100 text-red-800',
    emergencia: 'bg-red-100 text-red-800'
  }
  return colores[categoria] || 'bg-gray-100 text-gray-800'
}

// Función para recargar datos
function recargarDatos() {
  console.log('🔄 Recargando datos...')
  cargarProcedimientos()
}

// Cargar datos al montar el componente
onMounted(async () => {
  console.log('🔄 Componente HistorialTratamientos montado')
  
  // Primero verificar si hay token en localStorage (por si acaso)
  const tokenFromStorage = localStorage.getItem('token')
  if (tokenFromStorage && !auth.accessToken.value) {
    console.log('🔐 Token encontrado en localStorage, asignando...')
    auth.accessToken.value = tokenFromStorage
  }
  
  // Esperar un momento para asegurar que la autenticación esté cargada
  setTimeout(() => {
    console.log('🔍 Estado de autenticación:', {
      token: auth.accessToken.value ? 'Presente' : 'Ausente',
      usuario: auth.user.value ? 'Cargado' : 'No cargado'
    })
    
    if (tieneToken.value) {
      console.log('✅ Token presente, cargando procedimientos...')
      cargarProcedimientos()
    } else {
      errorCarga.value = 'Debes iniciar sesión para ver los procedimientos'
      console.warn('❌ No hay token disponible, no se pueden cargar procedimientos')
      cargando.value = false
    }
  }, 1000) // Esperar 1 segundo para asegurar que todo esté cargado
})

// Watch para cuando cambie el token
watch(() => auth.accessToken.value, (nuevoToken) => {
  console.log('🔄 Token cambiado:', nuevoToken ? 'Nuevo token asignado' : 'Token eliminado')
  
  if (nuevoToken) {
    console.log('🔄 Token actualizado, cargando procedimientos...')
    cargarProcedimientos()
  }
})

// Watch para cuando cambie el usuario (opcional, para actualizar el nombre)
watch(() => auth.user.value, (nuevoUsuario) => {
  if (nuevoUsuario) {
    console.log('👤 Usuario cargado:', nuevoUsuario)
  }
})
</script>

<style scoped>
/* Estilos personalizados */
.procedimiento-card {
  transition: all 0.2s ease;
}

.procedimiento-card:hover {
  transform: translateY(-2px);
}
</style>
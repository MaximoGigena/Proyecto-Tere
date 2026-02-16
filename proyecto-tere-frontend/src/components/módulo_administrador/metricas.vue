<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Métricas del sistema</h1>

    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6">
      <div class="flex items-center">
        <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <h3 class="text-lg font-medium text-red-800">Error al cargar métricas</h3>
          <p class="text-red-600 mt-1">{{ error }}</p>
          <button @click="intentarCargarDatos" class="mt-3 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm">
            Reintentar
          </button>
        </div>
      </div>
    </div>

    <!-- Grids de contadores -->
    <div v-if="!loading && !error" class="space-y-6">
      <!-- Grid: Usuarios registrados -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-700">Usuarios Registrados</h2>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">{{ metricas.usuarios.total }} total</span>
            <span class="text-xs px-2 py-1 rounded-full" 
                  :class="crecimientoUsuarios >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
              {{ crecimientoUsuarios >= 0 ? '+' : '' }}{{ crecimientoUsuarios }}%
            </span>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Total registrados</div>
            <div class="text-2xl font-bold text-blue-600">{{ formatNumber(metricas.usuarios.total) }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Este mes</div>
            <div class="text-2xl font-bold text-green-600">{{ formatNumber(metricas.usuarios.ultimo_mes) }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Activos</div>
            <div class="text-2xl font-bold text-purple-600">{{ formatNumber(estadisticas.usuarios_activos || 0) }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Inactivos</div>
            <div class="text-2xl font-bold text-red-600">{{ formatNumber(estadisticas.usuarios_inactivos || 0) }}</div>
          </div>
        </div>
      </div>

      <!-- Grid: Mascotas registradas -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-700">Mascotas Registradas</h2>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">{{ metricas.mascotas.total }} total</span>
            <span class="text-xs px-2 py-1 rounded-full" 
                  :class="crecimientoMascotas >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
              {{ crecimientoMascotas >= 0 ? '+' : '' }}{{ crecimientoMascotas }}%
            </span>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Total mascotas</div>
            <div class="text-2xl font-bold text-blue-600">{{ formatNumber(metricas.mascotas.total) }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">En adopción</div>
            <div class="text-2xl font-bold text-yellow-600">{{ formatNumber(metricas.mascotas.en_adopcion) }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Este mes</div>
            <div class="text-2xl font-bold text-green-600">{{ formatNumber(metricas.mascotas.ultimo_mes) }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Dadas de baja</div>
            <div class="text-2xl font-bold text-red-600">{{ formatNumber(metricas.mascotas.dadas_de_baja) }}</div>
          </div>
        </div>
      </div>

      <!-- Grid: Veterinarios registrados -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-700">Veterinarios Registrados</h2>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">{{ metricas.veterinarios.total }} total</span>
            <span v-if="metricas.veterinarios.pendientes_aprobacion > 0" 
                  class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">
              {{ metricas.veterinarios.pendientes_aprobacion }} pendientes
            </span>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Total veterinarios</div>
            <div class="text-2xl font-bold text-red-600">{{ formatNumber(metricas.veterinarios.total) }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Activos</div>
            <div class="text-2xl font-bold text-green-600">{{ formatNumber(estadisticas.veterinarios_activos || 0) }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Pendientes</div>
            <div class="text-2xl font-bold text-yellow-600">{{ formatNumber(metricas.veterinarios.pendientes_aprobacion) }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Mascotas atendidas</div>
            <div class="text-2xl font-bold text-purple-600">{{ formatNumber(estadisticas.mascotas_atendidas || 0) }}</div>
          </div>
        </div>
      </div>

      <!-- Grid: Adopciones realizadas -->
      <div class="bg-white shadow rounded-xl p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-700">Adopciones Realizadas</h2>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">{{ metricas.adopciones.total }} total</span>
            <span class="text-xs px-2 py-1 rounded-full" 
                  :class="crecimientoAdopciones >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
              {{ crecimientoAdopciones >= 0 ? '+' : '' }}{{ crecimientoAdopciones }}%
            </span>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Total adopciones</div>
            <div class="text-2xl font-bold text-purple-600">{{ formatNumber(metricas.adopciones.total) }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Este mes</div>
            <div class="text-2xl font-bold text-green-600">{{ formatNumber(metricas.adopciones.ultimo_mes) }}</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">Tasa de éxito</div>
            <div class="text-2xl font-bold text-blue-600">{{ estadisticas.tasa_adopcion || 0 }}%</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-500">En proceso</div>
            <div class="text-2xl font-bold text-yellow-600">{{ formatNumber(estadisticas.adopciones_en_proceso || 0) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Resto del código existente (estadísticas, gráfico, etc.) -->
    <div v-if="!loading && !error" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white shadow rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Estadísticas Generales</h3>
        <div class="space-y-3">
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Total registros:</span>
            <span class="font-semibold">{{ formatNumber(estadisticas.total_registros) }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Tasa de adopción:</span>
            <span class="font-semibold">{{ estadisticas.tasa_adopcion }}%</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600">Mascotas por usuario:</span>
            <span class="font-semibold">{{ estadisticas.ratio_mascotas_usuario }}</span>
          </div>
        </div>
      </div>

      <!-- Gráfico de adopciones -->
      <div class="md:col-span-2 bg-white p-6 rounded-xl shadow">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-700">Adopciones por mes</h2>
          <button @click="cargarAdopciones" class="text-sm text-blue-500 hover:text-blue-700">
            Actualizar
          </button>
        </div>
        <div v-if="cargandoAdopciones" class="h-40 flex items-center justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
        </div>
        <div v-else class="h-40 flex items-end space-x-2">
          <div v-for="mes in adopcionesPorMes" :key="mes.mes" class="flex flex-col items-center flex-1">
            <div class="relative">
              <div class="w-8 bg-purple-500 rounded-t-lg transition-all duration-300 hover:bg-purple-600"
                   :style="{ height: getBarHeight(mes.total) + 'px' }"
                   :title="mes.total + ' adopciones'">
              </div>
              <span class="text-xs text-gray-500 absolute -bottom-6 left-1/2 transform -translate-x-1/2 whitespace-nowrap">
                {{ mes.mes_corto }}
              </span>
            </div>
            <span class="text-xs text-gray-700 mt-8">{{ mes.total }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Sección de Motores de Métricas Especializadas -->
    <MetricEnginesCarousel 
      v-if="!loading && !error" 
      @navigate="irAMotor"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import MetricEnginesCarousel from '@/components/ElementosGraficos/MotoresMetrica.vue'

// Usar el router para navegación
const router = useRouter()

// Usar el composable de autenticación
const { isAuthenticated, accessToken, checkAuth } = useAuth()

const loading = ref(true)
const cargandoAdopciones = ref(false)
const error = ref(null)

// Datos iniciales
const metricas = ref({
  usuarios: { total: 0, ultimo_mes: 0, crecimiento: 0 },
  mascotas: { total: 0, ultimo_mes: 0, crecimiento: 0, en_adopcion: 0, dadas_de_baja: 0 },
  veterinarios: { total: 0, pendientes_aprobacion: 0 },
  adopciones: { total: 0, ultimo_mes: 0, crecimiento: 0 }
})

const estadisticas = ref({
  total_registros: 0,
  tasa_adopcion: 0,
  ratio_mascotas_usuario: 0,
  usuarios_activos: 0,
  usuarios_inactivos: 0,
  veterinarios_activos: 0,
  mascotas_atendidas: 0,
  adopciones_en_proceso: 0
})

const adopcionesPorMes = ref([])

// Variables para el intervalo
let refreshInterval = null

// Computed properties para los porcentajes
const crecimientoUsuarios = computed(() => metricas.value.usuarios.crecimiento || 0)
const crecimientoMascotas = computed(() => metricas.value.mascotas.crecimiento || 0)
const crecimientoAdopciones = computed(() => metricas.value.adopciones.crecimiento || 0)

// Función para formatear números
const formatNumber = (num) => {
  return new Intl.NumberFormat('es-ES').format(num)
}

// Función para calcular altura de barras
const getBarHeight = (value) => {
  const maxValue = Math.max(...adopcionesPorMes.value.map(m => m.total))
  if (maxValue === 0) return 0
  return (value / maxValue) * 120 // 120px es la altura máxima
}

// Función para obtener headers con autenticación
const getAuthHeaders = () => {
  if (!accessToken.value) {
    console.error('No hay token de autenticación disponible')
    return {}
  }
  
  return {
    'Authorization': `Bearer ${accessToken.value}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
}

// Función auxiliar para hacer peticiones autenticadas
const apiGet = async (endpoint) => {
  try {
    const headers = getAuthHeaders()
    
    // Verificar si hay token
    if (!headers.Authorization) {
      throw new Error('No autenticado')
    }
    
    const response = await axios.get(endpoint, { 
      headers,
      timeout: 100000 // 10 segundos timeout
    })
    
    if (response.data.success === false) {
      throw new Error(response.data.message || 'Error en la respuesta del servidor')
    }
    
    return response.data
  } catch (err) {
    console.error(`Error en ${endpoint}:`, err)
    
    // Si es error 401, el token puede estar expirado o ser inválido
    if (err.response?.status === 401) {
      // Intentar revalidar la autenticación
      try {
        const revalidado = await checkAuth()
        if (!revalidado) {
          console.log('Token expirado o inválido, redirigiendo a login...')
          window.location.href = '/login'
        }
      } catch (authError) {
        console.error('Error revalidando autenticación:', authError)
        window.location.href = '/login'
      }
    }
    
    throw err
  }
}

// Cargar métricas principales
const cargarMetricas = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Verificar autenticación
    if (!isAuthenticated.value) {
      const revalidado = await checkAuth()
      if (!revalidado) {
        throw new Error('Usuario no autenticado')
      }
    }
    
    const data = await apiGet('/api/metricas')
    
    if (data.success && data.data) {
      metricas.value = data.data.metricas_principales
      estadisticas.value = data.data.estadisticas_generales
    }
  } catch (err) {
    console.error('Error cargando métricas:', err)
    error.value = err.message || 'Error al cargar métricas'
    
    // Mantener valores por defecto
    metricas.value = {
      usuarios: { total: 0, ultimo_mes: 0, crecimiento: 0 },
      mascotas: { total: 0, ultimo_mes: 0, crecimiento: 0, en_adopcion: 0, dadas_de_baja: 0 },
      veterinarios: { total: 0, pendientes_aprobacion: 0 },
      adopciones: { total: 0, ultimo_mes: 0, crecimiento: 0 }
    }
    
    estadisticas.value = {
      total_registros: 0,
      tasa_adopcion: 0,
      ratio_mascotas_usuario: 0,
      usuarios_activos: 0,
      usuarios_inactivos: 0,
      veterinarios_activos: 0,
      mascotas_atendidas: 0,
      adopciones_en_proceso: 0
    }
  } finally {
    loading.value = false
  }
}

// Cargar estadísticas de adopciones
const cargarAdopciones = async () => {
  try {
    cargandoAdopciones.value = true
    
    if (!isAuthenticated.value) {
      const revalidado = await checkAuth()
      if (!revalidado) {
        throw new Error('No autenticado')
      }
    }
    
    const data = await apiGet('/api/metricas/adopciones')
    
    if (data.success && data.data) {
      adopcionesPorMes.value = data.data.adopciones_por_mes || []
    }
  } catch (err) {
    console.error('Error cargando adopciones:', err)
    adopcionesPorMes.value = []
  } finally {
    cargandoAdopciones.value = false
  }
}

// Función para cargar todos los datos
const cargarTodosLosDatos = async () => {
  try {
    loading.value = true
    error.value = null
    
    await Promise.all([
      cargarMetricas(),
      cargarAdopciones()
    ])
  } catch (error) {
    console.error('Error cargando todos los datos:', error)
  } finally {
    loading.value = false
  }
}

// Función para reintentar
const intentarCargarDatos = () => {
  cargarTodosLosDatos()
}

// Navegación a un motor específico
const irAMotor = (ruta) => {
  router.push(ruta)
}

// Iniciar el intervalo de actualización
const iniciarActualizacionAutomatica = () => {
  // Limpiar intervalo anterior si existe
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
  
  // Crear nuevo intervalo (cada 5 minutos)
  refreshInterval = setInterval(() => {
    if (isAuthenticated.value) {
      cargarMetricas()
    }
  }, 300000) // 5 minutos
}

// Cargar todo al montar el componente
onMounted(async () => {
  try {
    // Verificar si está autenticado
    if (!isAuthenticated.value) {
      console.log('Usuario no autenticado, verificando...')
      const autenticado = await checkAuth()
      
      if (!autenticado) {
        console.error('Usuario no autenticado, redirigiendo...')
        window.location.href = '/login'
        return
      }
    }
    
    // Cargar datos
    await cargarTodosLosDatos()
    
    // Iniciar actualización automática
    iniciarActualizacionAutomatica()
    
  } catch (error) {
    console.error('Error inicializando métricas:', error)
    error.value = 'Error al conectar con el servidor. Por favor, intenta nuevamente.'
  }
})

// Limpiar intervalo al desmontar el componente
onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
    refreshInterval = null
  }
})
</script>

<style scoped>
/* Estilos adicionales si los necesitas */
</style>
<!-- solicitudes.vue-->
<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold mb-4">Solicitudes de Veterinarios Pendientes</h1>

    <!-- Estado de carga -->
    <div v-if="cargando" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      <p class="mt-2 text-gray-600">Cargando solicitudes...</p>
    </div>

    <!-- Mensaje de error -->
    <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
      {{ error }}
    </div>

    <!-- 🔍 Filtros -->
    <div v-if="!cargando && solicitudes.length > 0" class="bg-white shadow p-4 rounded-xl mb-6 flex flex-wrap gap-4">
      <input
        v-model="filtroNombre"
        type="text"
        placeholder="Buscar por nombre"
        class="border rounded px-3 py-2 w-full md:w-1/3"
      />
      <select v-model="filtroEspecialidad" class="border rounded px-3 py-2 w-full md:w-1/3">
        <option value="">Todas las especialidades</option>
        <option v-for="esp in especialidadesUnicas" :key="esp" :value="esp">{{ esp }}</option>
      </select>
    </div>

    <!-- 🧾 Lista de solicitudes filtradas -->
    <div v-if="!cargando && solicitudesFiltradas.length === 0 && solicitudes.length > 0" class="text-gray-500">
      No hay solicitudes que coincidan con los filtros.
    </div>

    <div v-if="!cargando && solicitudes.length === 0" class="text-gray-500 text-center py-8">
      No hay solicitudes pendientes en este momento.
    </div>

    <div v-else-if="!cargando" class="space-y-4">
      <div
        v-for="solicitud in solicitudesFiltradas"
        :key="solicitud.id"
        class="bg-white shadow p-4 rounded-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
      >
        <div class="flex-1">
          <h2 class="text-lg font-semibold">{{ solicitud.nombre_completo }}</h2>
          <p class="text-sm text-gray-600">Email: {{ solicitud.email }}</p>
          <p class="text-sm text-gray-600">Matrícula: {{ solicitud.matricula }}</p>
          <p class="text-sm text-gray-600">Especialidad: {{ solicitud.especialidad }}</p>
          <p class="text-sm text-gray-500">Solicitado: {{ formatFecha(solicitud.fecha_solicitud) }}</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2">
          <button 
            @click="verDetalle(solicitud)" 
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors"
          >
            Ver detalles
          </button>
          <button 
            @click="aprobarSolicitud(solicitud.id)" 
            :disabled="procesando.includes(solicitud.id)"
            class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition-colors disabled:bg-green-300"
          >
            <span v-if="procesando.includes(solicitud.id)">Procesando...</span>
            <span v-else>Aprobar</span>
          </button>
          <button 
            @click="rechazarSolicitud(solicitud.id)" 
            :disabled="procesando.includes(solicitud.id)"
            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors disabled:bg-red-300"
          >
            <span v-if="procesando.includes(solicitud.id)">Procesando...</span>
            <span v-else>Rechazar</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de detalle -->
    <div v-if="detalleVisible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white p-6 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold">Detalles de la Solicitud</h3>
          <button @click="detalleVisible = false" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <h4 class="font-semibold mb-2">Información Personal</h4>
            <p><strong>Nombre:</strong> {{ seleccionado.nombre_completo }}</p>
            <p><strong>Email:</strong> {{ seleccionado.email }}</p>
            <p><strong>Matrícula:</strong> {{ seleccionado.matricula }}</p>
            <p><strong>Especialidad:</strong> {{ seleccionado.especialidad }}</p>
            <p><strong>Años de experiencia:</strong> {{ seleccionado.anos_experiencia || 'No especificado' }}</p>
          </div>
          
          <div>
            <h4 class="font-semibold mb-2">Contacto</h4>
            <p><strong>Teléfono:</strong> {{ seleccionado.telefono || 'No especificado' }}</p>
            <p><strong>Email de contacto:</strong> {{ seleccionado.email_contacto || 'No especificado' }}</p>
          </div>
        </div>
        
        <div class="mt-4">
          <h4 class="font-semibold mb-2">Descripción/Biografía</h4>
          <p class="text-gray-700 bg-gray-50 p-3 rounded">{{ seleccionado.descripcion || 'No proporcionada' }}</p>
        </div>
        
        <div v-if="seleccionado.fotos && seleccionado.fotos.length > 0" class="mt-4">
          <h4 class="font-semibold mb-2">Fotos Adjuntas</h4>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <img 
              v-for="(foto, index) in seleccionado.fotos" 
              :key="index"
              :src="getFotoUrl(foto)" 
              :alt="`Foto ${index + 1}`"
              class="w-full h-24 object-cover rounded cursor-pointer"
              @click="ampliarFoto(getFotoUrl(foto))"
            />
          </div>
        </div>
        
        <button @click="detalleVisible = false" class="mt-6 bg-gray-300 px-4 py-2 rounded hover:bg-gray-400 w-full">
          Cerrar
        </button>
      </div>
    </div>

    <!-- Modal para foto ampliada -->
    <div v-if="fotoAmpliada" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50" @click="fotoAmpliada = null">
      <div class="max-w-4xl max-h-full p-4">
        <img :src="fotoAmpliada" alt="Foto ampliada" class="max-w-full max-h-full object-contain" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthToken } from '@/composables/useAuthToken'
import axios from 'axios'

axios.defaults.baseURL = 'http://localhost:8000'

const { accessToken, isAuthenticated } = useAuthToken()

const solicitudes = ref([])
const cargando = ref(true)
const error = ref('')
const procesando = ref([])

const detalleVisible = ref(false)
const seleccionado = ref({})
const fotoAmpliada = ref(null)

const filtroNombre = ref('')
const filtroEspecialidad = ref('')

// Función para obtener token del localStorage
// Función para verificar autenticación
const verificarAutenticacion = () => {
  if (!isAuthenticated.value) {
    error.value = 'Debes iniciar sesión para acceder a esta página'
    cargando.value = false
    return false
  }
  return true
}

// Peticiones con Bearer token del composable
const axiosAuth = axios.create({
  baseURL: 'http://localhost:8000',
  headers: {
    Authorization: `Bearer ${accessToken.value}`
  }
})

// Obtener solicitudes pendientes
const obtenerSolicitudesPendientes = async () => {
  try {
    cargando.value = true
    error.value = ''

    // Verificar autenticación antes de hacer la petición
    if (!verificarAutenticacion()) return

    const response = await axiosAuth.get('/api/solicitudes-pendientes')
    if (response.data.success) {
      solicitudes.value = response.data.data
    } else {
      throw new Error(response.data.message || 'Error en la respuesta del servidor')
    }
  } catch (err) {
    console.error('Error al obtener solicitudes:', err)
    
    // Manejar errores de autenticación
    if (err.response?.status === 401) {
      error.value = 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.'
      return
    }
    
    error.value = err.response?.data?.message || err.message || 'Error al cargar las solicitudes'
  } finally {
    cargando.value = false
  }
}

const aprobarSolicitud = async (solicitudId) => {
  if (!confirm('¿Estás seguro de que deseas aprobar esta solicitud?')) return
  await procesarSolicitud(solicitudId, 'aprobar')
}

const rechazarSolicitud = async (solicitudId) => {
  if (!confirm('¿Estás seguro de que deseas rechazar esta solicitud?')) return
  await procesarSolicitud(solicitudId, 'rechazar')
}

const procesarSolicitud = async (solicitudId, accion) => {
  try {
    // Verificar autenticación antes de procesar
    if (!verificarAutenticacion()) return
    
    procesando.value.push(solicitudId)
    const endpoint = `/api/solicitudes/${solicitudId}/${accion}`
    const response = await axiosAuth.post(endpoint)
    if (response.data.success) {
      solicitudes.value = solicitudes.value.filter(s => s.id !== solicitudId)
      alert(`Solicitud ${accion} exitosamente`)
    }
  } catch (err) {
    console.error('Error al procesar solicitud:', err)
    
    // Manejar errores de autenticación
    if (err.response?.status === 401) {
      error.value = 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.'
      return
    }
    
    error.value = err.response?.data?.message || err.message || 'Error al procesar la solicitud'
  } finally {
    procesando.value = procesando.value.filter(id => id !== solicitudId)
  }
}
const verDetalle = (solicitud) => {
  seleccionado.value = solicitud
  detalleVisible.value = true
}

const getFotoUrl = (fotoPath) => `http://localhost:8000/storage/${fotoPath}`
const ampliarFoto = (fotoUrl) => { fotoAmpliada.value = fotoUrl }

const formatFecha = (fecha) => {
  return new Date(fecha).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const solicitudesFiltradas = computed(() => {
  return solicitudes.value.filter(s => {
    const coincideNombre = s.nombre_completo.toLowerCase().includes(filtroNombre.value.toLowerCase())
    const coincideEspecialidad = filtroEspecialidad.value === '' || s.especialidad === filtroEspecialidad.value
    return coincideNombre && coincideEspecialidad
  })
})

const especialidadesUnicas = computed(() => {
  const set = new Set(solicitudes.value.map(s => s.especialidad))
  return Array.from(set)
})

onMounted(() => {
  obtenerSolicitudesPendientes()
})
</script>

<style scoped>
/* Estilos opcionales */
</style>


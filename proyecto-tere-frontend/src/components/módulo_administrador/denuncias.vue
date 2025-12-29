<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Denuncias recibidas</h1>

    <!-- Filtro de orden -->
    <div class="bg-white p-4 rounded-xl shadow flex flex-wrap items-center gap-4">
      <!-- Ordenar -->
      <label class="text-sm font-medium text-gray-700">
        Ordenar por:
        <select v-model="orden" class="ml-2 border border-gray-300 rounded px-3 py-1" @change="cargarDenuncias">
          <option value="created_at">Fecha</option>
          <option value="categoria">Razón</option>
          <option value="estado">Estado</option>
        </select>
      </label>

      <!-- Filtrar por categoría -->
      <label class="text-sm font-medium text-gray-700">
        Filtrar por razón:
        <select v-model="filtroCategoria" class="ml-2 border border-gray-300 rounded px-3 py-1" @change="cargarDenuncias">
          <option value="">Todas</option>
          <option v-for="categoria in categorias" :key="categoria" :value="categoria">
            {{ categoria }}
          </option>
        </select>
      </label>

      <!-- Filtrar por estado -->
      <label class="text-sm font-medium text-gray-700">
        Filtrar por estado:
        <select v-model="filtroEstado" class="ml-2 border border-gray-300 rounded px-3 py-1" @change="cargarDenuncias">
          <option value="">Todos</option>
          <option value="pendiente">Pendiente</option>
          <option value="en_revision">En revisión</option>
          <option value="resuelta">Resuelta</option>
          <option value="descarta">Descartada</option>
        </select>
      </label>

      <!-- Botón refrescar -->
      <button 
        @click="cargarDenuncias" 
        class="ml-auto bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition"
        :disabled="cargando"
      >
        <font-awesome-icon :icon="['fas', 'sync']" :class="{'animate-spin': cargando}" />
        {{ cargando ? 'Cargando...' : 'Actualizar' }}
      </button>
    </div>

    <!-- Tabla o detalle -->
    <div class="bg-white p-6 rounded-xl shadow">
      <template v-if="detalleActual">
        <!-- Vista de detalle -->
        <DetalleDenuncia :denuncia="detalleActual" @volver="detalleActual = null" />
      </template>

      <template v-else>
        <!-- Loading state -->
        <div v-if="cargando" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          <p class="mt-2 text-gray-600">Cargando denuncias...</p>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="text-center py-8 text-red-500">
          {{ error }}
          <button @click="cargarDenuncias" class="ml-2 text-blue-500 hover:underline">
            Reintentar
          </button>
        </div>

        <!-- Tabla de denuncias -->
        <template v-else>
          <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs text-white uppercase bg-blue-500">
              <tr>
                <th class="px-4 py-2">Razón</th>
                <th class="px-4 py-2">Subrazón</th>
                <th class="px-4 py-2">Gravedad</th>
                <th class="px-4 py-2">Fecha</th>
                <th class="px-4 py-2">Estado</th>
                <th class="px-4 py-2 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(denuncia, index) in denunciasOrdenadas"
                :key="denuncia.id || index"
                class="border-b hover:bg-gray-50"
              >
                <td class="px-4 py-2">{{ denuncia.razon }}</td>
                <td class="px-4 py-2">{{ denuncia.subrazon }}</td>
                <td class="px-4 py-2">
                  <span :class="{
                    'bg-red-100 text-red-800': denuncia.gravedad === 'Alta',
                    'bg-yellow-100 text-yellow-800': denuncia.gravedad === 'Media',
                    'bg-green-100 text-green-800': denuncia.gravedad === 'Baja'
                  }" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ denuncia.gravedad }}
                  </span>
                </td>
                <td class="px-4 py-2">{{ formatFecha(denuncia.fecha) }}</td>
                <td class="px-4 py-2">
                  <span :class="{
                    'bg-yellow-100 text-yellow-800': denuncia.estado === 'pendiente',
                    'bg-blue-100 text-blue-800': denuncia.estado === 'en_revision',
                    'bg-green-100 text-green-800': denuncia.estado === 'resuelta',
                    'bg-gray-100 text-gray-800': denuncia.estado === 'descarta'
                  }" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ formatEstado(denuncia.estado) }}
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <button
                    @click="verDetalle(denuncia)"
                    class="bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium mr-28 px-3 py-1.5 rounded-lg transition-colors duration-150"
                  >
                    Ver detalles
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="!denunciasOrdenadas.length && !cargando" class="text-center text-gray-400 italic mt-4 py-8">
            Aún no has enviado ninguna denuncia.
          </div>

          <!-- Información de la mascota en tooltip -->
          <div v-if="hoverDenuncia" class="fixed bg-white p-4 rounded-lg shadow-lg border z-50">
            <p><strong>Mascota:</strong> {{ hoverDenuncia.mascota?.nombre || 'No especificada' }}</p>
            <p><strong>Usuario denunciado:</strong> {{ hoverDenuncia.usuario_denunciado?.nombre || 'No especificado' }}</p>
          </div>
        </template>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import DetalleDenuncia from './detalleDenuncia.vue'

const { accessToken } = useAuth()

const orden = ref('created_at')
const detalleActual = ref(null)
const filtroCategoria = ref('')
const filtroEstado = ref('')
const cargando = ref(false)
const error = ref(null)
const denuncias = ref([])
const categorias = ref([])
const hoverDenuncia = ref(null)

// Cargar denuncias desde la API
const cargarDenuncias = async () => {
  try {
    cargando.value = true
    error.value = null
    
    console.log('🔍 Iniciando carga de denuncias...')
    console.log('🔍 Token disponible:', !!accessToken.value)
    
    const params = new URLSearchParams()
    if (filtroCategoria.value) params.append('categoria', filtroCategoria.value)
    if (filtroEstado.value) params.append('estado', filtroEstado.value)
    params.append('orden', orden.value)
    params.append('direccion', 'desc')
    
    const url = `/api/denuncias?${params.toString()}`
    console.log('🔍 URL de petición:', url)
    
    const response = await axios.get(url, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json'
      }
    })
    
    console.log('✅ Respuesta del servidor:', response.data)
    
    if (response.data.success) {
      denuncias.value = response.data.data
      categorias.value = response.data.filters?.categorias || []
      
      console.log(`📊 Denuncias cargadas: ${denuncias.value.length}`)
      console.log('📊 Datos de denuncias:', denuncias.value)
      
      if (response.data.debug) {
        console.log('🔍 Debug info:', response.data.debug)
      }
    } else {
      console.error('❌ Error en respuesta:', response.data.message)
      throw new Error(response.data.message || 'Error al cargar denuncias')
    }
    
  } catch (err) {
    console.error('❌ Error cargando denuncias:', err)
    console.error('❌ Detalles del error:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status
    })
    
    error.value = err.response?.data?.message || err.message || 'Error al cargar las denuncias'
  } finally {
    cargando.value = false
  }
}

// Cargar denuncias al montar el componente
onMounted(() => {
  cargarDenuncias()
})

// Computed para ordenar localmente (opcional, ya que el backend ordena)
const denunciasOrdenadas = computed(() => {
  return [...denuncias.value]
})

// Formatear estado para mostrar
const formatEstado = (estado) => {
  const estados = {
    'pendiente': 'Pendiente',
    'en_revision': 'En revisión',
    'resuelta': 'Resuelta',
    'descarta': 'Descartada'
  }
  return estados[estado] || estado
}

// Formatear fecha
const formatFecha = (fechaStr) => {
  const options = { year: 'numeric', month: 'short', day: 'numeric' }
  return new Date(fechaStr).toLocaleDateString(undefined, options)
}

// Ver detalle de una denuncia
const verDetalle = async (denuncia) => {
  try {
    cargando.value = true
    
    // Si la denuncia ya tiene todos los datos, úsala directamente
    if (denuncia.detalles) {
      detalleActual.value = denuncia
      return
    }
    
    // De lo contrario, cargar desde la API
    const response = await axios.get(`/api/denuncias/${denuncia.id}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.data.success) {
      detalleActual.value = response.data.data
    }
    
  } catch (err) {
    console.error('Error cargando detalle:', err)
    error.value = 'Error al cargar los detalles de la denuncia'
  } finally {
    cargando.value = false
  }
}

// Funciones para hover
const mostrarTooltip = (denuncia) => {
  hoverDenuncia.value = denuncia
}

const ocultarTooltip = () => {
  hoverDenuncia.value = null
}
</script>

<style scoped>
/* Estilos para tooltip */
.fixed {
  transform: translate(-50%, -100%);
  margin-top: -10px;
}
</style>


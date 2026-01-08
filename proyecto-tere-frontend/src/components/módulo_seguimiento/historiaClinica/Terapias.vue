<!-- src/components/historialMedico/Diagnosticos.vue -->
<template>
  <div class="p-4 w-full flex flex-col h-full min-w-[300px]">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'kit-medical']" class="mr-2" />
      Terapias de la Mascota
    </h2>

    <!-- Estado de carga -->
    <div v-if="cargando" class="flex justify-center py-8">
      <div class="inline-flex items-center gap-2">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
        <span class="text-gray-500">Cargando terapias...</span>
      </div>
    </div>

    <!-- Sin terapias -->
    <div v-else-if="terapias.length === 0" class="text-center py-8">
      <p class="text-gray-400">No se han registrado terapias para esta mascota</p>
    </div>

    <!-- Lista de terapias -->
    <div v-else class="space-y-4">
      <div
        v-for="terapia in terapias"
        :key="terapia.id"
        class="relative border rounded-xl p-4 shadow-sm bg-white cursor-pointer hover:shadow-md transition"
        @click="abrirDetallesTerapia(terapia)"
      >
        <!-- Íconos de acción en la esquina superior -->
        <div 
          v-if="$route.path.startsWith('/veterinarios')"
          class="absolute right-3 top-3 flex space-x-2"
        >
          <button
            class="text-gray-500 hover:text-blue-600 transition"
            @click.stop="editarTerapia(terapia)"
          >
            <font-awesome-icon :icon="['fas', 'pen']" />
          </button>
          <button
            class="text-gray-500 hover:text-red-600 transition"
            @click.stop="eliminarTerapia(terapia.id)"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
          <button
            @click.stop="abrirDerivacionTerapia(terapia)"
            class="text-white bg-orange-600 rounded-full px-1 py-1 text-base font-bold shadow-md hover:bg-orange-700 hover:scale-105 transition transform duration-200"
          >
            Derivar
          </button>
        </div>

        <h3 class="text-lg font-bold text-gray-700 mb-2">{{ terapia.tipo_terapia }}</h3>
        
        <div class="grid grid-cols-2 gap-2 mb-2">
          <p class="text-gray-600">
            <strong>Inicio:</strong> {{ formatFecha(terapia.fecha_inicio) }}
          </p>
          <p class="text-gray-600">
            <strong>Estado:</strong> 
            <span :class="getEstadoClass(terapia.estado)">
              {{ getEstadoTexto(terapia.estado) }}
            </span>
          </p>
        </div>
        
        <p class="text-gray-600">
          <strong>Duración:</strong> {{ terapia.duracion_tratamiento }}
        </p>
        <p class="text-gray-600">
          <strong>Frecuencia:</strong> {{ getFrecuenciaTexto(terapia.frecuencia) }}
        </p>
        <p class="text-gray-600">
          <strong>Centro:</strong> {{ terapia.centro_veterinario || 'No especificado' }}
        </p>
        
        <!-- Evolución si existe -->
        <div v-if="terapia.evolucion" class="mt-2 p-2 bg-green-50 rounded">
          <p class="text-sm text-green-700">
            <strong>Evolución:</strong> {{ getEvolucionTexto(terapia.evolucion) }}
          </p>
        </div>

        <!-- Fecha de finalización si existe -->
        <div v-if="terapia.fecha_fin" class="mt-2 p-2 bg-gray-100 rounded">
          <p class="text-sm text-gray-700">
            <strong>Finalizó:</strong> {{ formatFecha(terapia.fecha_fin) }}
          </p>
        </div>
      </div>
    </div>
   
    <div
      v-if="$route.path.startsWith('/veterinarios')"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroTerapia"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Terapia
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const mascotaId = route.params.id
const terapias = ref([])
const cargando = ref(false)
const mostrar = ref(false)

console.log('📍 Route params:', route.params)
console.log('📍 Mascota ID:', mascotaId)

// Cargar terapias al montar el componente
onMounted(async () => {
  await cargarTerapias()
})

// Función para cargar las terapias desde la API
const cargarTerapias = async () => {
  if (!mascotaId) {
    console.error('No hay mascotaId para cargar terapias')
    return
  }

  try {
    cargando.value = true
    
    const response = await fetch(`/api/mascotas/${mascotaId}/terapias`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    
    if (result.success && result.data) {
      terapias.value = result.data
      console.log('✅ Terapias cargadas:', terapias.value)
    } else {
      console.warn('No se encontraron terapias:', result)
      terapias.value = []
    }
  } catch (error) {
    console.error('❌ Error cargando terapias:', error)
    terapias.value = []
  } finally {
    cargando.value = false
  }
}

// Formatear fecha
const formatFecha = (fechaString) => {
  if (!fechaString) return 'No especificada'
  
  const fecha = new Date(fechaString)
  return fecha.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

// Obtener texto de frecuencia
const getFrecuenciaTexto = (frecuencia) => {
  const frecuencias = {
    'diaria': 'Diaria',
    'semanal': 'Semanal',
    'quincenal': 'Quincenal',
    'mensual': 'Mensual',
    'personalizada': 'Personalizada'
  }
  return frecuencias[frecuencia] || frecuencia
}

// Obtener texto de evolución
const getEvolucionTexto = (evolucion) => {
  const evoluciones = {
    'mejoria': 'Mejoría',
    'estable': 'Estable',
    'empeoramiento': 'Empeoramiento'
  }
  return evoluciones[evolucion] || evolucion
}

// Obtener clase CSS para el estado
const getEstadoClass = (estado) => {
  const classes = {
    'en_progreso': 'text-blue-600 font-semibold',
    'completada': 'text-green-600 font-semibold',
    'cancelada': 'text-red-600 font-semibold',
    'pendiente': 'text-yellow-600 font-semibold'
  }
  return classes[estado] || 'text-gray-600'
}

// Obtener texto del estado
const getEstadoTexto = (estado) => {
  const estados = {
    'en_progreso': 'En Progreso',
    'completada': 'Completada',
    'cancelada': 'Cancelada',
    'pendiente': 'Pendiente'
  }
  return estados[estado] || estado
}

const abrirRegistroTerapia = () => {
  if (!mascotaId) {
    alert('Error: No se pudo identificar la mascota')
    return
  }

  router.push({
    path: '/registro/terapia',
    query: {
      from: '/historialClinico/terapias',
      mascotaId: mascotaId
    }
  })
}

const abrirDerivacionTerapia = (terapia) => {
  console.log('Derivar terapia:', terapia)
  // Aquí puedes implementar la lógica de derivación
  // router.push({
  //   path: '/derivacion/terapia',
  //   query: {
  //     terapiaId: terapia.id,
  //     mascotaId: mascotaId
  //   }
  // })
}

// Funciones para futura implementación
const abrirDetallesTerapia = (terapia) => {
  console.log('Abrir detalles de terapia:', terapia)
  // Aquí puedes implementar la lógica para ver detalles
  // router.push({
  //   path: `/terapias/${terapia.id}`,
  //   query: { mascotaId: mascotaId }
  // })
}

const editarTerapia = (terapia) => {
  console.log('Editar terapia:', terapia)
  // Aquí puedes implementar la edición
  // router.push({
  //   path: `/terapias/${terapia.id}/editar`,
  //   query: { mascotaId: mascotaId }
  // })
}

const eliminarTerapia = async (id) => {
  if (!confirm('¿Está seguro de que desea eliminar esta terapia?')) {
    return
  }

  try {
    const response = await fetch(`/api/terapias/${id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    const result = await response.json()
    
    if (result.success) {
      alert('Terapia eliminada exitosamente')
      // Recargar la lista de terapias
      await cargarTerapias()
    } else {
      alert('Error al eliminar la terapia: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error eliminando terapia:', error)
    alert('Error al eliminar la terapia')
  }
}

// Función para recargar terapias cuando sea necesario (ej: después de registrar una nueva)
const recargarTerapias = async () => {
  await cargarTerapias()
}

// Exponer función para que pueda ser llamada desde fuera si es necesario
defineExpose({
  recargarTerapias
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
</style>
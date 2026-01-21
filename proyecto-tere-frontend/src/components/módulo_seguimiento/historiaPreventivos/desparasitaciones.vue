<!-- desparasitaciones.vue -->
<template>
  <div class="p-4 w-full flex flex-col h-full min-w-[300px]">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'capsules']" class="mr-2" />
      Desparasitaciones de la Mascota
    </h2>

    <!-- Estado de carga -->
    <div v-if="cargando" class="flex justify-center py-8">
      <div class="inline-flex items-center gap-2">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
        <span class="text-gray-500">Cargando desparasitaciones...</span>
      </div>
    </div>

    <!-- Sin desparasitaciones -->
    <div v-else-if="desparasitaciones.length === 0" class="text-center py-8">
      <p class="text-gray-400">No se han registrado desparasitaciones para esta mascota</p>
    </div>

    <!-- Lista de desparasitaciones -->
    <div v-else class="space-y-4">
      <div
        v-for="desparasitacion in desparasitaciones"
        :key="desparasitacion.id"
        class="relative border rounded-xl p-4 shadow-sm bg-white cursor-pointer hover:shadow-md transition"
        @click="abrirProcedimiento(desparasitacion)"
      >
        <!-- Íconos de acción en la esquina superior -->
        <div 
          v-if="$route.path.startsWith('/veterinarios')"
          class="absolute right-3 top-3 flex space-x-2"
        >
          <button
            class="text-gray-500 hover:text-blue-600 transition"
            @click.stop="editarDesparasitacion(desparasitacion)"
            title="Editar desparasitación"
          >
            <font-awesome-icon :icon="['fas', 'pen']" />
          </button>
          <button
            class="text-gray-500 hover:text-red-600 transition"
            @click.stop="eliminarDesparasitacion(desparasitacion)"
            title="Eliminar desparasitación"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
          <button
            @click.stop="abrirRegistroDesparasitacion"
            class="text-white bg-orange-600 rounded-full px-1 py-1 text-base font-bold shadow-md hover:bg-orange-700 hover:scale-105 transition transform duration-200"
            title="Derivar a nueva desparasitación"
          >
            Derivar
          </button>
        </div>

        <h3 class="text-lg font-bold text-gray-700 mb-2">{{ desparasitacion.tipo }}</h3>
        <p class="text-gray-600"><strong>Fecha:</strong> {{ formatFecha(desparasitacion.fecha) }}</p>
        <p class="text-gray-600"><strong>Producto:</strong> {{ desparasitacion.nombre_producto }}</p>
        <p class="text-gray-600"><strong>Dosis:</strong> {{ desparasitacion.dosis }}</p>
        <p class="text-gray-600"><strong>Frecuencia:</strong> {{ desparasitacion.frecuencia }}</p>
        <p class="text-gray-600"><strong>Centro:</strong> {{ desparasitacion.centro_veterinario }}</p>
        
        <!-- Peso si existe -->
        <div v-if="desparasitacion.peso" class="mt-1">
          <p class="text-gray-600"><strong>Peso:</strong> {{ desparasitacion.peso }} kg</p>
        </div>
        
        <!-- Próxima fecha si existe -->
        <div v-if="desparasitacion.proxima_fecha" class="mt-2 p-2 bg-blue-50 rounded">
          <p class="text-sm text-blue-700">
            <strong>Próxima aplicación:</strong> {{ formatFecha(desparasitacion.proxima_fecha) }}
          </p>
        </div>

        <!-- Observaciones si existen -->
        <div v-if="desparasitacion.observaciones" class="mt-2 p-2 bg-gray-50 rounded">
          <p class="text-sm text-gray-700">
            <strong>Observaciones:</strong> {{ desparasitacion.observaciones }}
          </p>
        </div>
      </div>
    </div>
   
    <div
      v-if="$route.path.startsWith('/veterinarios')"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroDesparasitacion"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Desparasitación
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
const desparasitaciones = ref([])
const cargando = ref(false)
const eliminando = ref(false)

console.log('📍 Route params:', route.params)
console.log('📍 Mascota ID:', mascotaId)

// Cargar desparasitaciones al montar el componente
onMounted(async () => {
  await cargarDesparasitaciones()
})

// Función para cargar las desparasitaciones desde la API
const cargarDesparasitaciones = async () => {
  if (!mascotaId) {
    console.error('No hay mascotaId para cargar desparasitaciones')
    return
  }

  try {
    cargando.value = true
    
    const response = await fetch(`/api/mascotas/${mascotaId}/desparasitaciones`, {
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
      desparasitaciones.value = result.data
      console.log('✅ Desparasitaciones cargadas:', desparasitaciones.value)
    } else {
      console.warn('No se encontraron desparasitaciones:', result)
      desparasitaciones.value = []
    }
  } catch (error) {
    console.error('❌ Error cargando desparasitaciones:', error)
    desparasitaciones.value = []
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

const abrirRegistroDesparasitacion = () => {
  if (!mascotaId) {
    alert('Error: No se pudo identificar la mascota')
    return
  }

  console.log('📍 Navegando con mascotaId:', mascotaId)
  
  router.push({
    path: '/registro/desparasitacion',
    query: {
      mascotaId: mascotaId,
      from: '/historialPreventivo/desparasitaciones'
    }
  })
}

// Funciones para futura implementación
const abrirProcedimiento = (desparasitacion) => {
  console.log('Abrir detalles de desparasitación:', desparasitacion)
  // Aquí puedes implementar la lógica para ver detalles
}

const editarDesparasitacion = (desparasitacion) => {
  console.log('Editar desparasitación:', desparasitacion)
  
  if (!desparasitacion.id) {
    console.error('La desparasitación no tiene ID')
    return
  }

  // Navegar a la vista de edición con el ID de la desparasitación
  router.push({
    name: 'editarDesparasitacion',
    params: {
      desparasitacionId: desparasitacion.id
    },
    query: {
      mascotaId: mascotaId, // Enviar el ID de la mascota como query param si es necesario
      from: '/historialPreventivo/desparasitación' // Opcional: para regresar después de editar
    }
  })
}

const eliminarDesparasitacion = async (desparasitacion) => {
  if (!desparasitacion || !desparasitacion.id) {
    console.error('No se puede eliminar: datos de desparasitación inválidos')
    return
  }

  // Confirmación de eliminación
  const confirmacion = confirm(
    `¿Está seguro de que desea eliminar la desparasitación de "${desparasitacion.nombre_producto}" realizada el ${formatFecha(desparasitacion.fecha)}?\n\nEsta acción no se puede deshacer.`
  )

  if (!confirmacion) {
    return
  }

  try {
    eliminando.value = true
    console.log('🗑️ Eliminando desparasitación ID:', desparasitacion.id)
    
    const response = await fetch(`/api/desparasitaciones/${desparasitacion.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    const result = await response.json()
    
    if (response.ok && result.success) {
      console.log('✅ Desparasitación eliminada exitosamente:', result)
      
      // Recargar la lista de desparasitaciones
      await cargarDesparasitaciones()
      
      // Mostrar mensaje de éxito
      alert('✅ Desparasitación eliminada exitosamente')
    } else {
      console.error('❌ Error en eliminación:', result)
      alert(`❌ Error al eliminar la desparasitación: ${result.message || 'Error desconocido'}`)
    }
  } catch (error) {
    console.error('❌ Error al eliminar desparasitación:', error)
    alert('❌ Error de conexión al eliminar la desparasitación. Por favor, intente nuevamente.')
  } finally {
    eliminando.value = false
  }
}

// Opcional: Función para manejar mejor el estado de carga durante la eliminación
const eliminarDesparasitacionConLoading = async (desparasitacion) => {
  if (eliminando.value) return
  
  await eliminarDesparasitacion(desparasitacion)
}
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
<!-- src/components/historialMedico/Diagnosticos.vue -->
<template>
  <div class="p-4 w-full flex flex-col h-full min-w-[300px]">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'pills']" class="mr-2" />
      Fármacos administrados a la mascota
    </h2>

    <!-- Estado de carga -->
    <div v-if="cargando" class="flex justify-center py-8">
      <div class="inline-flex items-center gap-2">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
        <span class="text-gray-500">Cargando fármacos...</span>
      </div>
    </div>

    <!-- Sin fármacos -->
    <div v-else-if="farmacos.length === 0" class="text-center py-8">
      <p class="text-gray-400">No se han registrado fármacos para esta mascota</p>
    </div>

    <!-- Lista de fármacos -->
    <div v-else class="space-y-4">
      <div
        v-for="farmaco in farmacos"
        :key="farmaco.id"
        class="relative border rounded-xl p-4 shadow-sm bg-white cursor-pointer hover:shadow-md transition"
        @click="abrirDetallesFarmaco(farmaco)"
      >
        <!-- Íconos de acción en la esquina superior -->
        <div 
          v-if="$route.path.startsWith('/veterinarios')"
          class="absolute right-3 top-3 flex space-x-2"
        >
          <button
            class="text-gray-500 hover:text-blue-600 transition"
            @click.stop="editarFarmaco(farmaco)"
          >
            <font-awesome-icon :icon="['fas', 'pen']" />
          </button>
          <button
            class="text-gray-500 hover:text-red-600 transition"
            @click.stop="eliminarFarmaco(farmaco.id)"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
          <button
            @click.stop="derivarFarmaco(farmaco)"
            class="text-white bg-orange-600 rounded-full px-2 py-1 text-sm font-bold shadow-md hover:bg-orange-700 hover:scale-105 transition transform duration-200 whitespace-nowrap"
          >
            Derivar
          </button>
        </div>

        <h3 class="text-lg font-bold text-gray-700 mb-2">{{ farmaco.nombre }}</h3>
        <p class="text-gray-600">
          <strong>Administrado:</strong> {{ formatFecha(farmaco.fecha_administracion) }}
          <span v-if="farmaco.hora_administracion" class="ml-1">
            ({{ farmaco.hora_administracion }})
          </span>
        </p>
        <p class="text-gray-600"><strong>Dosis:</strong> {{ farmaco.dosis }} {{ farmaco.unidad }}</p>
        <p class="text-gray-600"><strong>Frecuencia:</strong> {{ farmaco.frecuencia }}</p>
        <p class="text-gray-600"><strong>Duración:</strong> {{ farmaco.duracion }}</p>
        <p class="text-gray-600"><strong>Centro:</strong> {{ farmaco.centro_veterinario }}</p>
        
        <!-- Próxima dosis si existe -->
        <div v-if="farmaco.proxima_dosis" class="mt-2 p-2 bg-yellow-50 rounded">
          <p class="text-sm text-yellow-700">
            <strong>Próxima dosis:</strong> {{ formatFecha(farmaco.proxima_dosis) }}
          </p>
        </div>

        <!-- Indicador de reacciones adversas -->
        <div v-if="farmaco.reacciones" class="mt-2 p-2 bg-red-50 rounded">
          <p class="text-sm text-red-700">
            <strong>⚠️ Reacciones adversas:</strong> {{ farmaco.reacciones }}
          </p>
        </div>

        <!-- Indicador de archivos adjuntos -->
        <div v-if="farmaco.archivos_count > 0" class="mt-2 flex items-center">
          <font-awesome-icon :icon="['fas', 'paperclip']" class="text-gray-400 mr-1 text-sm" />
          <span class="text-xs text-gray-500">{{ farmaco.archivos_count }} archivo(s) adjunto(s)</span>
        </div>
      </div>
    </div>
   
    <div
      v-if="$route.path.startsWith('/veterinarios')"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroFarmaco"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Fármaco
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Obtener mascotaId de los parámetros de ruta o query
const mascotaId = computed(() => {
  return route.params.id || route.query.mascotaId
})

const farmacos = ref([])
const cargando = ref(false)

console.log('📍 Route params:', route.params)
console.log('📍 Route query:', route.query)
console.log('📍 Mascota ID:', mascotaId.value)

// Cargar fármacos al montar el componente
onMounted(async () => {
  await cargarFarmacos()
})

// Función para cargar los fármacos desde la API
const cargarFarmacos = async () => {
  if (!mascotaId.value) {
    console.error('No hay mascotaId para cargar fármacos')
    return
  }

  try {
    cargando.value = true
    
    const response = await fetch(`/api/mascotas/${mascotaId.value}/farmacos`, {
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
      farmacos.value = result.data.map(farmaco => ({
        ...farmaco,
        // Asegurar que tengamos un contador de archivos
        archivos_count: farmaco.archivos ? farmaco.archivos.length : 0
      }))
      console.log('✅ Fármacos cargados:', farmacos.value.length)
    } else {
      console.warn('No se encontraron fármacos:', result)
      farmacos.value = []
    }
  } catch (error) {
    console.error('❌ Error cargando fármacos:', error)
    farmacos.value = []
  } finally {
    cargando.value = false
  }
}

// Formatear fecha
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
    console.error('Error formateando fecha:', error)
    return fechaString
  }
}

// Formatear hora si está disponible
const formatHora = (fechaString) => {
  if (!fechaString) return ''
  
  try {
    const fecha = new Date(fechaString)
    return fecha.toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    })
  } catch (error) {
    console.error('Error formateando hora:', error)
    return ''
  }
}

const abrirRegistroFarmaco = () => {
  if (!mascotaId.value) {
    alert('Error: No se pudo identificar la mascota')
    return
  }

  // Determinar la ruta de retorno basada en la ruta actual
  let fromRoute = '/historialClinico/farmacos'
  if (route.path.startsWith('/veterinarios')) {
    fromRoute = '/veterinarios/farmacos'
  }

  router.push({
    path: '/registro/farmaco',
    query: {
      from: fromRoute,
      mascotaId: mascotaId.value
    }
  })
}

// Funciones para interactuar con los fármacos
const abrirDetallesFarmaco = (farmaco) => {
  console.log('Abrir detalles de fármaco:', farmaco)
  // Aquí puedes implementar la lógica para ver detalles completos
  // Por ejemplo: abrir un modal o navegar a una vista de detalles
  router.push({
    path: `/farmacos/${farmaco.id}`,
    query: {
      mascotaId: mascotaId.value,
      from: route.fullPath
    }
  })
}

const editarFarmaco = (farmaco) => {
  console.log('Editar fármaco:', farmaco)
  // Aquí puedes implementar la edición
  router.push({
    path: `/editar/farmaco/${farmaco.id}`,
    query: {
      mascotaId: mascotaId.value,
      from: route.fullPath
    }
  })
}


const eliminarFarmaco = async (id) => {
  if (!confirm('¿Está seguro de que desea eliminar este registro de fármaco?\n\nEsta acción marcará el fármaco como eliminado pero mantendrá los datos en el sistema.')) {
    return
  }

  try {
    // CORRECCIÓN: Usar la ruta correcta con mascotaId
    const response = await fetch(`/api/mascotas/${mascotaId.value}/farmacos/${id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    
    if (result.success) {
      // Remover el fármaco de la lista (filtramos en lugar de refrescar toda la lista)
      farmacos.value = farmacos.value.filter(f => f.id !== id)
      alert('Fármaco eliminado correctamente (baja lógica)')
    } else {
      alert('Error al eliminar el fármaco: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error eliminando fármaco:', error)
    alert('Error al eliminar el fármaco: ' + error.message)
  }
}



const derivarFarmaco = (farmaco) => {
  console.log('Derivar fármaco:', farmaco)
  // Aquí puedes implementar la lógica para derivar el fármaco
  // Por ejemplo: abrir un formulario para compartir con otro veterinario
  alert(`Función de derivación para ${farmaco.nombre} - Por implementar`)
}

// Recargar fármacos si cambian los parámetros
import { watch } from 'vue'

watch(mascotaId, async (newMascotaId) => {
  if (newMascotaId) {
    await cargarFarmacos()
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

/* Estilos para el texto truncado */
.truncate-2-lines {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Estilos para los estados de los fármacos */
.farmaco-completo {
  border-left: 4px solid #10b981; /* Verde para completado */
}

.farmaco-pendiente {
  border-left: 4px solid #f59e0b; /* Amarillo para pendiente */
}

.farmaco-con-reaccion {
  border-left: 4px solid #ef4444; /* Rojo para con reacciones */
}
</style>
<!-- alergias -->
<template>
  <div class="p-4 w-full flex flex-col h-full min-w-[300px]">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'pump-medical']" class="mr-2" />
      Alergias o Sensibilidades
    </h2>

    <!-- Estado de carga -->
    <div v-if="cargando" class="flex justify-center py-8">
      <div class="inline-flex items-center gap-2">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
        <span class="text-gray-500">Cargando alergias...</span>
      </div>
    </div>

    <!-- Sin alergias -->
    <div v-else-if="alergias.length === 0" class="text-center py-8">
      <p class="text-gray-400">No se han registrado alergias para esta mascota</p>
    </div>

    <!-- Lista de alergias -->
    <div v-else class="space-y-4">
      <div
        v-for="alergia in alergias"
        :key="alergia.id"
        class="relative border rounded-xl p-4 shadow-sm bg-white cursor-pointer hover:shadow-md transition"
        @click="abrirDetalleAlergia(alergia)"
      >
        <!-- Íconos de acción en la esquina superior -->
        <div 
          v-if="$route.path.startsWith('/veterinarios')"
          class="absolute right-3 top-3 flex space-x-2"
        >
          <button
            class="text-gray-500 hover:text-blue-600 transition"
            @click.stop="editarAlergia(alergia)"
          >
            <font-awesome-icon :icon="['fas', 'pen']" />
          </button>
          <button
            class="text-gray-500 hover:text-red-600 transition"
            @click.stop="eliminarAlergia(alergia.id)"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
        </div>

        <h3 class="text-lg font-bold text-gray-700 mb-2">{{ alergia.nombre }}</h3>
        <p class="text-gray-600"><strong>Tipo:</strong> {{ alergia.tipo_alergia || 'No especificado' }}</p>
        <p class="text-gray-600"><strong>Gravedad:</strong> {{ alergia.gravedad_label || alergia.gravedad || 'No especificada' }}</p>
        <p class="text-gray-600"><strong>Fecha detección:</strong> {{ formatFecha(alergia.fecha_deteccion) }}</p>

        <!-- Reacción común -->
        <div v-if="alergia.reaccion_comun" class="mt-2">
          <p class="text-gray-600"><strong>Reacción común:</strong></p>
          <p class="text-sm text-gray-500 mt-1">{{ alergia.reaccion_comun }}</p>
        </div>

        <!-- Notas adicionales -->
        <div v-if="alergia.observaciones" class="mt-2 p-2 bg-yellow-50 rounded">
          <p class="text-sm text-yellow-700">
            <strong>Observaciones:</strong> {{ alergia.observaciones }}
          </p>
        </div>
      </div>
    </div>
   
    <div
      v-if="$route.path.startsWith('/veterinarios')"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroAlergia"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Alergia
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
const alergias = ref([])
const cargando = ref(false)

console.log('📍 Route params:', route.params)
console.log('📍 Mascota ID:', mascotaId)

// Cargar alergias al montar el componente
onMounted(async () => {
  await cargarAlergias()
})

// Función para cargar las alergias desde la API
const cargarAlergias = async () => {
  if (!mascotaId) {
    console.error('No hay mascotaId para cargar alergias')
    return
  }

  try {
    cargando.value = true
    
    const response = await fetch(`/api/mascotas/${mascotaId}/alergias`, {
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
      alergias.value = result.data
      console.log('✅ Alergias cargadas:', alergias.value)
    } else {
      console.warn('No se encontraron alergias:', result)
      alergias.value = []
    }
  } catch (error) {
    console.error('❌ Error cargando alergias:', error)
    alergias.value = []
  } finally {
    cargando.value = false
  }
}

// Formatear fecha
const formatFecha = (fechaString) => {
  if (!fechaString) return 'No especificada'
  
  // Si ya viene en formato dd/mm/yyyy del API
  if (typeof fechaString === 'string' && fechaString.includes('/')) {
    return fechaString
  }
  
  // Si viene en formato Y-m-d
  try {
    const fecha = new Date(fechaString)
    if (isNaN(fecha.getTime())) return fechaString
    return fecha.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch (e) {
    return fechaString
  }
}

const abrirRegistroAlergia = () => {
  if (!mascotaId) {
    alert('Error: No se pudo identificar la mascota')
    return
  }

  router.push({
    path: '/registro/alergia',
    query: {
      from: '/historialPreventivo/alergias',
      mascotaId: mascotaId
    }
  })
}

// Funciones para futura implementación
const abrirDetalleAlergia = (alergia) => {
  console.log('Abrir detalles de alergia:', alergia)
  // Aquí puedes implementar la lógica para ver detalles
}

const editarAlergia = (alergia) => {
  console.log('Editar alergia:', alergia)
  // Aquí puedes implementar la edición
  router.push({
    path: `/editar/alergia/${alergia.id}`,
    query: {
      mascotaId: mascotaId
    }
  })
}

const eliminarAlergia = async (id) => {
  if (!confirm('¿Estás seguro de que deseas dar de baja esta alergia?\n\nNota: Se realizará una baja lógica, no se eliminará permanentemente.')) {
    return
  }

  try {
    const response = await fetch(`/api/mascotas/${mascotaId}/alergias/${id}/baja`, {
      method: 'PATCH',
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    
    if (result.success) {
      console.log('✅ Alergia dada de baja:', id)
      // Recargar la lista de alergias
      await cargarAlergias()
      
      // Mostrar mensaje de éxito
      alert('Alergia dada de baja exitosamente')
    } else {
      alert('Error al dar de baja la alergia: ' + (result.message || 'Error desconocido'))
    }
  } catch (error) {
    console.error('❌ Error dando de baja alergia:', error)
    alert('Error al dar de baja la alergia: ' + error.message)
  }
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
<!-- vacunas -->
<template>
  <div class="p-4 w-full flex flex-col h-full min-w-[300px]">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'vial-circle-check']" class="mr-2" />
      Vacunas de la Mascota
    </h2>

    <!-- Estado de carga -->
    <div v-if="cargando" class="flex justify-center py-8">
      <div class="inline-flex items-center gap-2">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
        <span class="text-gray-500">Cargando vacunas...</span>
      </div>
    </div>

    <!-- Sin vacunas -->
    <div v-else-if="vacunas.length === 0" class="text-center py-8">
      <p class="text-gray-400">No se han registrado vacunas para esta mascota</p>
    </div>

    <!-- Lista de vacunas -->
    <div v-else class="space-y-4">
      <div
        v-for="vacuna in vacunas"
        :key="vacuna.id"
        class="relative border rounded-xl p-4 shadow-sm bg-white cursor-pointer hover:shadow-md transition"
        @click="abrirProcedimiento(vacuna)"
      >
        <!-- Íconos de acción en la esquina superior -->
        <div 
          v-if="$route.path.startsWith('/veterinarios')"
          class="absolute right-3 top-3 flex space-x-2"
        >
          <button
            class="text-gray-500 hover:text-blue-600 transition"
            @click.stop="editarVacuna(vacuna)"
          >
            <font-awesome-icon :icon="['fas', 'pen']" />
          </button>
          <button
            class="text-gray-500 hover:text-red-600 transition"
            @click.stop="eliminarVacuna(vacuna.id)"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
          <button
            @click.stop="abrirRegistroVacuna"
            class="text-white bg-orange-600 rounded-full px-1 py-1 text-base font-bold shadow-md hover:bg-orange-700 hover:scale-105 transition transform duration-200"
          >
            Derivar
          </button>
        </div>

        <h3 class="text-lg font-bold text-gray-700 mb-2">{{ vacuna.nombre }}</h3>
        <p class="text-gray-600"><strong>Fecha:</strong> {{ formatFecha(vacuna.fecha_aplicacion) }}</p>
        <p class="text-gray-600"><strong>Dosis:</strong> {{ vacuna.numero_dosis }}</p>
        <p class="text-gray-600"><strong>Lote:</strong> {{ vacuna.lote_serie }}</p>
        <p class="text-gray-600"><strong>Centro:</strong> {{ vacuna.centro_veterinario }}</p>
        
        <!-- Próxima dosis si existe -->
        <div v-if="vacuna.fecha_proxima_dosis" class="mt-2 p-2 bg-blue-50 rounded">
          <p class="text-sm text-blue-700">
            <strong>Próxima dosis:</strong> {{ formatFecha(vacuna.fecha_proxima_dosis) }}
          </p>
        </div>
      </div>
    </div>
   
    <div
      v-if="$route.path.startsWith('/veterinarios')"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroVacuna"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Vacuna
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
const vacunas = ref([])
const cargando = ref(false)
const mostrar = ref(false)

console.log('📍 Route params:', route.params)
console.log('📍 Mascota ID:', mascotaId)

// Cargar vacunas al montar el componente
onMounted(async () => {
  await cargarVacunas()
})

// Función para cargar las vacunas desde la API
const cargarVacunas = async () => {
  if (!mascotaId) {
    console.error('No hay mascotaId para cargar vacunas')
    return
  }

  try {
    cargando.value = true
    
    const response = await fetch(`/api/mascotas/${mascotaId}/vacunas`, {
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
      vacunas.value = result.data
      console.log('✅ Vacunas cargadas:', vacunas.value)
    } else {
      console.warn('No se encontraron vacunas:', result)
      vacunas.value = []
    }
  } catch (error) {
    console.error('❌ Error cargando vacunas:', error)
    vacunas.value = []
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

const abrirRegistroVacuna = () => {
  if (!mascotaId) {
    alert('Error: No se pudo identificar la mascota')
    return
  }

  router.push({
    path: '/registro/vacuna',
    query: {
      from: '/historialPreventivo/vacunas',
      mascotaId: mascotaId
    }
  })
}

// Funciones para futura implementación
const abrirProcedimiento = (vacuna) => {
  console.log('Abrir detalles de vacuna:', vacuna)
  // Aquí puedes implementar la lógica para ver detalles
}

const editarVacuna = (vacuna) => {
  console.log('Editar vacuna:', vacuna)
  // Aquí puedes implementar la edición
}

const eliminarVacuna = (id) => {
  console.log('Eliminar vacuna ID:', id)
  // Aquí puedes implementar la eliminación
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
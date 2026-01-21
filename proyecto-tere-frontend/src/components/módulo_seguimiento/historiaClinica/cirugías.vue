<!-- cirugias.vue-->
<template>
  <div class="p-4 w-full flex flex-col h-full min-w-[300px]">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'hand-holding-medical']" class="mr-2" />
      Procedimientos Quirúrgicos
    </h2>

    <!-- Estado de carga -->
    <div v-if="cargando" class="flex justify-center py-8">
      <div class="inline-flex items-center gap-2">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
        <span class="text-gray-500">Cargando cirugías...</span>
      </div>
    </div>

    <!-- Sin cirugías -->
    <div v-else-if="cirugias.length === 0" class="text-center py-8">
      <p class="text-gray-400">No se han registrado cirugías para esta mascota</p>
    </div>

    <!-- Lista de cirugías -->
    <div v-else class="space-y-4">
      <div
        v-for="cirugia in cirugias"
        :key="cirugia.id"
        class="relative border rounded-xl p-4 shadow-sm bg-white cursor-pointer hover:shadow-md transition"
        @click="abrirProcedimiento(cirugia)"
      >
        <!-- Íconos de acción en la esquina superior -->
        <div 
          v-if="$route.path.startsWith('/veterinarios')"
          class="absolute right-3 top-3 flex space-x-2">
          <button
            class="text-gray-500 hover:text-blue-600 transition"
            @click.stop="editarCirugia(cirugia)"
          >
            <font-awesome-icon :icon="['fas', 'pen']" />
          </button>
          <button
            class="text-gray-500 hover:text-red-600 transition"
            @click.stop="eliminarCirugia(cirugia.id)"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
          <button
            @click.stop="abrirRegistroCirugia"
            class="text-white bg-orange-600 rounded-full px-1 py-1 text-base font-bold shadow-md hover:bg-orange-700 hover:scale-105 transition transform duration-200"
          >
            Derivar
          </button>
        </div>
        
        <h3 class="text-lg font-bold text-gray-700 mb-2">{{ cirugia.tipo_cirugia }}</h3>
        <p class="text-gray-600"><strong>Fecha:</strong> {{ formatFecha(cirugia.fecha) }}</p>
        <p class="text-gray-600"><strong>Resultado:</strong> {{ formatResultado(cirugia.resultado) }}</p>
        <p class="text-gray-600"><strong>Estado:</strong> {{ formatEstado(cirugia.estado) }}</p>
        <p class="text-gray-600"><strong>Centro:</strong> {{ cirugia.centro_veterinario }}</p>
        
        <!-- Mostrar diagnósticos asociados -->
        <div v-if="cirugia.diagnosticos && cirugia.diagnosticos.length > 0" class="mt-2">
          <p class="text-sm font-medium text-gray-700 mb-1">Diagnósticos asociados:</p>
          <div class="flex flex-wrap gap-1">
            <span 
              v-for="diagnostico in cirugia.diagnosticos" 
              :key="diagnostico.id"
              class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full"
            >
              {{ diagnostico.nombre }}
            </span>
          </div>
        </div>

        <!-- Próximo control si existe -->
        <div v-if="cirugia.fecha_control" class="mt-2 p-2 bg-green-50 rounded">
          <p class="text-sm text-green-700">
            <strong>Próximo control:</strong> {{ formatFecha(cirugia.fecha_control) }}
          </p>
        </div>
      </div>
    </div>

    <div
      v-if="$route.path.startsWith('/veterinarios')"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroCirugia"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Cirugía
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
const cirugias = ref([])
const cargando = ref(false)

console.log('📍 Route params:', route.params)
console.log('📍 Mascota ID:', mascotaId)

// Cargar cirugías al montar el componente
onMounted(async () => {
  await cargarCirugias()
})

// Función para cargar las cirugías desde la API
const cargarCirugias = async () => {
  if (!mascotaId) {
    console.error('No hay mascotaId para cargar cirugías')
    return
  }

  try {
    cargando.value = true
    
    const response = await fetch(`/api/mascotas/${mascotaId}/cirugias`, {
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
      cirugias.value = result.data
      console.log('✅ Cirugías cargadas:', cirugias.value)
    } else {
      console.warn('No se encontraron cirugías:', result)
      cirugias.value = []
    }
  } catch (error) {
    console.error('❌ Error cargando cirugías:', error)
    cirugias.value = []
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
      year: 'numeric',
      hour: fechaString.includes('T') ? '2-digit' : undefined,
      minute: fechaString.includes('T') ? '2-digit' : undefined
    })
  } catch (error) {
    console.error('Error formateando fecha:', fechaString, error)
    return fechaString
  }
}

// Formatear resultado
const formatResultado = (resultado) => {
  const resultados = {
    satisfactorio: 'Satisfactorio',
    complicaciones: 'Con complicaciones',
    estable: 'Estable',
    critico: 'Crítico'
  }
  return resultados[resultado] || resultado || 'No especificado'
}

// Formatear estado
const formatEstado = (estado) => {
  const estados = {
    recuperacion: 'En recuperación',
    alta: 'Alta postoperatoria',
    seguimiento: 'Bajo seguimiento',
    hospitalizado: 'Hospitalizado'
  }
  return estados[estado] || estado || 'No especificado'
}

const abrirRegistroCirugia = () => {
  if (!mascotaId) {
    alert('Error: No se pudo identificar la mascota')
    return
  }

  router.push({
    path: '/registro/cirugia',
    query: {
      from: '/historialClinico/cirugias',
      mascotaId: mascotaId
    }
  })
}

const abrirProcedimiento = (cirugia) => {
  // Crear objeto de query preservando los parámetros existentes
  const queryParams = {
    ...route.query,
    from: route.fullPath // Guardamos la ruta completa actual
  }
  
  // Eliminar parámetros que no queremos pasar
  delete queryParams.id
  delete queryParams.currentTab
  
  router.push({
    name: 'procedimiento-detalles',
    params: { id: cirugia.id },
    query: queryParams
  })
}

const editarCirugia = (cirugia) => {
  console.log('Editar cirugía:', cirugia)
  // Redireccionar a la vista de edición

  router.push({
    name: 'editarCirugia',
    params: {
      cirugiaId: cirugia.id
    },
    query: {
      from: '/historialClinico/cirugias',
      mascotaId: mascotaId  // Pasar el ID de la mascota como query param
    }
  })
}

const eliminarCirugia = async (id) => {
  if (!confirm('¿Está seguro de eliminar esta cirugía?')) {
    return
  }

  try {
    const response = await fetch(`/api/mascotas/${mascotaId}/cirugias/${id}`, {
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
      // Eliminar de la lista local
      const index = cirugias.value.findIndex(c => c.id === id)
      if (index !== -1) {
        cirugias.value.splice(index, 1)
      }
      alert('Cirugía eliminada correctamente')
    } else {
      alert('Error al eliminar la cirugía: ' + (result.message || 'Error desconocido'))
    }
  } catch (error) {
    console.error('❌ Error eliminando cirugía:', error)
    alert('Error al eliminar la cirugía: ' + error.message)
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
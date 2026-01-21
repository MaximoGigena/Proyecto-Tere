<template>
  <div class="p-4 w-full flex flex-col h-full min-w-[300px]">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'heart-circle-bolt']" class="mr-2" />
      Procedimientos Paliativos
    </h2>

    <!-- Estado de carga -->
    <div v-if="cargando" class="flex justify-center py-8">
      <div class="inline-flex items-center gap-2">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
        <span class="text-gray-500">Cargando procedimientos paliativos...</span>
      </div>
    </div>

    <!-- Sin procedimientos paliativos -->
    <div v-else-if="paliativos.length === 0" class="text-center py-8">
      <p class="text-gray-400">No se han registrado procedimientos paliativos para esta mascota</p>
    </div>

    <!-- Lista de procedimientos paliativos -->
    <div v-else class="space-y-4">
      <div
        v-for="paliativo in paliativos"
        :key="paliativo.id"
        class="relative border rounded-xl p-4 shadow-sm bg-white cursor-pointer hover:shadow-md transition"
        @click="abrirProcedimiento(paliativo)"
      >
        <!-- Íconos de acción en la esquina superior -->
        <div 
          v-if="$route.path.startsWith('/veterinarios')"
          class="absolute right-3 top-3 flex space-x-2">
          <button
            class="text-gray-500 hover:text-blue-600 transition"
            @click.stop="editarPaliativo(paliativo)"
          >
            <font-awesome-icon :icon="['fas', 'pen']" />
          </button>
          <button
            class="text-gray-500 hover:text-red-600 transition"
            @click.stop="eliminarPaliativo(paliativo.id)"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
          <button
            @click.stop="abrirRegistroPaliativo"
            class="text-white bg-orange-600 rounded-full px-1 py-1 text-base font-bold shadow-md hover:bg-orange-700 hover:scale-105 transition transform duration-200"
          >
            Derivar
          </button>
        </div>
        
        <h3 class="text-lg font-bold text-gray-700 mb-2">{{ paliativo.tipo_procedimiento || 'Procedimiento paliativo' }}</h3>
        <p class="text-gray-600"><strong>Fecha inicio:</strong> {{ formatFecha(paliativo.fecha_inicio) }}</p>
        <p class="text-gray-600"><strong>Resultado:</strong> {{ formatResultado(paliativo.resultado) }}</p>
        <p class="text-gray-600"><strong>Estado mascota:</strong> {{ formatEstado(paliativo.estado) }}</p>
        <p class="text-gray-600"><strong>Centro:</strong> {{ paliativo.centro_veterinario || 'No especificado' }}</p>
        
        <!-- Mostrar frecuencia de seguimiento -->
        <div v-if="paliativo.frecuencia_seguimiento" class="mt-2">
          <p class="text-sm text-gray-600">
            <strong>Frecuencia seguimiento:</strong> {{ paliativo.frecuencia_seguimiento }}
          </p>
        </div>

        <!-- Mostrar diagnósticos asociados -->
        <div v-if="paliativo.diagnosticos && paliativo.diagnosticos.length > 0" class="mt-2">
          <p class="text-sm font-medium text-gray-700 mb-1">Diagnósticos asociados:</p>
          <div class="flex flex-wrap gap-1">
            <span 
              v-for="diagnostico in paliativo.diagnosticos" 
              :key="diagnostico.id"
              class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full"
            >
              {{ diagnostico.nombre }}
            </span>
          </div>
        </div>

        <!-- Fecha de control si existe -->
        <div v-if="paliativo.fecha_control" class="mt-2 p-2 bg-green-50 rounded">
          <p class="text-sm text-green-700">
            <strong>Próximo control:</strong> {{ formatFecha(paliativo.fecha_control) }}
          </p>
        </div>

        <!-- Fármacos asociados si existen -->
        <div v-if="paliativo.farmacos_asociados && paliativo.farmacos_asociados.length > 0" class="mt-2">
          <p class="text-sm font-medium text-gray-700 mb-1">Fármacos asociados:</p>
          <div class="flex flex-wrap gap-1">
            <span 
              v-for="(farmaco, index) in paliativo.farmacos_asociados" 
              :key="index"
              class="px-2 py-0.5 bg-red-100 text-red-800 text-xs rounded-full"
            >
              {{ farmaco.nombre_comercial || farmaco.nombre }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="$route.path.startsWith('/veterinarios')"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroPaliativo"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Paliativo
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const mascotaId = ref(route.params.id || route.query.mascotaId)
const paliativos = ref([])
const cargando = ref(false)

console.log('📍 Route params:', route.params)
console.log('📍 Route query:', route.query)
console.log('📍 Mascota ID:', mascotaId.value)

// Observar cambios en la ruta para actualizar el ID de la mascota
watch(() => route.params.id, (newId) => {
  if (newId) {
    mascotaId.value = newId
    cargarPaliativos()
  }
})

watch(() => route.query.mascotaId, (newId) => {
  if (newId && newId !== mascotaId.value) {
    mascotaId.value = newId
    cargarPaliativos()
  }
})

// Cargar procedimientos paliativos al montar el componente
onMounted(async () => {
  // Si no hay mascotaId en los params, intentar obtenerlo del query
  if (!mascotaId.value) {
    mascotaId.value = route.query.mascotaId
    console.log('📍 Mascota ID from query:', mascotaId.value)
  }
  
  if (mascotaId.value) {
    await cargarPaliativos()
  } else {
    console.error('No se pudo obtener el ID de la mascota')
  }
})

// Función para cargar los procedimientos paliativos desde la API
const cargarPaliativos = async () => {
  if (!mascotaId.value) {
    console.error('No hay mascotaId para cargar procedimientos paliativos')
    return
  }

  try {
    cargando.value = true
    console.log('🔄 Cargando paliativos para mascota ID:', mascotaId.value)
    
    // PRUEBA: Primero intenta cargar con la ruta actual
    let response = await fetch(`/api/mascotas/${mascotaId.value}/paliativos`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })
    
    // Si falla, intenta con otra ruta común
    if (response.status === 404) {
      console.log('⚠️ Endpoint no encontrado, intentando alternativa...')
      response = await fetch(`/api/mascotas/${mascotaId.value}/procedimientos-paliativos/listar`, {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Accept': 'application/json'
        }
      })
    }

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Resultado de API:', result)
    
    if (result.success && result.data) {
      paliativos.value = result.data
      console.log('✅ Procedimientos paliativos cargados:', paliativos.value.length)
    } else {
      console.warn('No se encontraron procedimientos paliativos:', result)
      paliativos.value = []
    }
  } catch (error) {
    console.error('❌ Error cargando procedimientos paliativos:', error)
    // Mostrar datos de ejemplo para desarrollo
    if (import.meta.env.DEV) {
      paliativos.value = [
        {
          id: 1,
          tipo_procedimiento: 'Control de dolor crónico',
          fecha_inicio: new Date().toISOString(),
          resultado: 'mejoria',
          estado: 'dolor_controlado',
          centro_veterinario: 'Clínica Veterinaria Central',
          frecuencia_seguimiento: '2 semanas',
          fecha_control: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString(),
          diagnosticos: [
            { id: 1, nombre: 'Artrosis severa' }
          ],
          farmacos_asociados: [
            { id: 1, nombre_comercial: 'Carprofeno', nombre: 'carprofeno' }
          ]
        }
      ]
      console.log('📋 Usando datos de ejemplo para desarrollo:', paliativos.value)
    } else {
      paliativos.value = []
    }
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
    mejoria: 'Mejoría evidente',
    alivio: 'Alivio parcial',
    estabilizacion: 'Estabilización',
    sin_cambio: 'Sin cambios',
    empeoramiento: 'Empeoramiento'
  }
  return resultados[resultado] || resultado || 'No especificado'
}

// Formatear estado
const formatEstado = (estado) => {
  const estados = {
    estable: 'Estable',
    dolor_controlado: 'Con dolor controlado',
    dolor_parcial: 'Con dolor parcialmente controlado',
    deterioro: 'En deterioro',
    critico: 'Crítico'
  }
  return estados[estado] || estado || 'No especificado'
}

const abrirRegistroPaliativo = () => {
  if (!mascotaId.value) {
    alert('Error: No se pudo identificar la mascota')
    return
  }

  router.push({
    path: '/registro/paliativo',
    query: {
      from: route.fullPath,
      mascotaId: mascotaId.value
    }
  })
}

const abrirProcedimiento = (paliativo) => {
  // Crear objeto de query preservando los parámetros existentes
  const queryParams = {
    ...route.query,
    from: route.fullPath, // Guardamos la ruta completa actual
    mascotaId: mascotaId.value // Incluir el ID de la mascota
  }
  
  // Eliminar parámetros que no queremos pasar
  delete queryParams.id
  delete queryParams.currentTab
  
  router.push({
    name: 'procedimiento-paliativo-detalles',
    params: { id: paliativo.id },
    query: queryParams
  })
}

const editarPaliativo = (paliativo) => {
  console.log('Editar procedimiento paliativo:', paliativo)
  
  // Navegar a la vista de edición usando la ruta correcta
  router.push({
    name: 'EditarPaliativo',
    params: { 
      id: paliativo.id // Asegúrate que coincida con :id en la ruta
    },
    query: {
      mascotaId: mascotaId.value,
      from: route.fullPath
    }
  })
}

const eliminarPaliativo = async (id) => {
  if (!confirm('¿Está seguro de eliminar este procedimiento paliativo?')) {
    return
  }

  try {
    const response = await fetch(`/api/mascotas/${mascotaId.value}/paliativos/${id}`, {
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
      const index = paliativos.value.findIndex(p => p.id === id)
      if (index !== -1) {
        paliativos.value.splice(index, 1)
      }
      alert('Procedimiento paliativo eliminado correctamente')
    } else {
      alert('Error al eliminar el procedimiento paliativo: ' + (result.message || 'Error desconocido'))
    }
  } catch (error) {
    console.error('❌ Error eliminando procedimiento paliativo:', error)
    alert('Error al eliminar el procedimiento paliativo: ' + error.message)
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
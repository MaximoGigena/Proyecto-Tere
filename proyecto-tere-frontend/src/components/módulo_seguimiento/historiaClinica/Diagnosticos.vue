<!-- src/components/historialMedico/Diagnosticos.vue -->
<template>
  <div class="p-4 min-w-[300px] flex flex-col h-full">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'book-medical']" class="mr-2" />
      Diagnosticos de la mascota
    </h2>

    <!-- Estado de carga -->
    <div v-if="cargando" class="flex justify-center py-8">
      <div class="inline-flex items-center gap-2">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
        <span class="text-gray-500">Cargando diagnósticos...</span>
      </div>
    </div>

    <!-- Sin diagnósticos -->
    <div v-else-if="diagnosticos.length === 0" class="text-center py-8">
      <p class="text-gray-400">No se han registrado diagnósticos para esta mascota</p>
    </div>

    <!-- Lista de diagnósticos -->
    <div v-else class="space-y-4 flex-grow">
      <div
        v-for="diagnostico in diagnosticos"
        :key="diagnostico.id"
        class="relative border rounded-xl p-4 shadow-sm bg-white cursor-pointer hover:shadow-md transition"
        @click="abrirDetalles(diagnostico)"
      >
        <!-- Badge de estado -->
        <div class="absolute right-3 top-3">
          <span 
            :class="[
              'px-2 py-1 rounded-full text-xs font-semibold',
              getEstadoColor(diagnostico.estado)
            ]"
          >
            {{ diagnostico.estado_display }}
          </span>
        </div>

        <!-- Íconos de acción para veterinarios -->
        <div 
          v-if="$route.path.startsWith('/veterinarios') && !diagnostico.deleted_at"
          class="absolute right-3 top-10 flex space-x-2"
        >
          <button
            class="text-gray-500 hover:text-blue-600 transition"
            @click.stop="editarDiagnostico(diagnostico)"
          >
            <font-awesome-icon :icon="['fas', 'pen']" />
          </button>
          <button
            class="text-gray-500 hover:text-red-600 transition"
            @click.stop="eliminarDiagnostico(diagnostico)"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
        </div>

        <!-- Indicador de archivado -->
        <div v-if="diagnostico.deleted_at" class="absolute right-3 top-10">
          <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">
            <font-awesome-icon :icon="['fas', 'archive']" class="mr-1" />
            Archivado
          </span>
        </div>

        <h3 class="text-lg font-bold text-gray-700 mb-2">{{ diagnostico.nombre }}</h3>
        
        <div class="grid grid-cols-2 gap-2 mb-2">
          <p class="text-gray-600"><strong>Tipo:</strong> {{ diagnostico.tipo_diagnostico }}</p>
          <p class="text-gray-600"><strong>Fecha:</strong> {{ diagnostico.fecha_diagnostico_formateada }}</p>
        </div>

        <p class="text-gray-600"><strong>Centro:</strong> {{ diagnostico.centro_veterinario }}</p>
        <p class="text-gray-600"><strong>Veterinario:</strong> {{ diagnostico.veterinario }}</p>
        
        <!-- Observaciones (si existen) -->
        <div v-if="diagnostico.observaciones" class="mt-2">
          <p class="text-sm text-gray-500">
            <strong>Observaciones:</strong> {{ truncarTexto(diagnostico.observaciones, 80) }}
          </p>
        </div>

        <!-- Indicador de baja lógica -->
        <div v-if="diagnostico.deleted_at" class="mt-2">
          <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">
            <font-awesome-icon :icon="['fas', 'archive']" class="mr-1" />
            Archivado el {{ formatFecha(diagnostico.deleted_at) }}
          </span>
        </div>
      </div>
    </div>

    <div
      v-if="$route.path.startsWith('/veterinarios')"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroDiagnostico"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Diagnostico
      </button>
    </div>

    <!-- Modal de confirmación para baja lógica -->
    <div v-if="mostrarConfirmacion" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-sm mx-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
          Confirmar baja lógica
        </h3>
        <p class="text-gray-600 mb-6">
          ¿Estás seguro de que deseas archivar este diagnóstico? 
          El diagnóstico se marcará como archivado pero no se eliminará permanentemente.
        </p>
        <div class="flex justify-end space-x-3">
          <button
            @click="cancelarEliminacion"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
          >
            Cancelar
          </button>
          <button
            @click="confirmarEliminacion"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-white"
          >
            Archivar
          </button>
        </div>
      </div>
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

const mascotaId = computed(() => {
  const id = route.params.id
  if (id && typeof id === 'object') {
    return id.id || id.toString()
  }
  return String(id || '').trim()
})

const diagnosticos = ref([])
const cargando = ref(false)
const diagnosticoAEliminar = ref(null)
const mostrarConfirmacion = ref(false)

onMounted(async () => {
  console.log('🚀 onMounted - mascotaId:', mascotaId.value)
  
  if (mascotaId.value && mascotaId.value !== '') {
    await cargarDiagnosticos()
  } else {
    console.error('❌ mascotaId no válido:', mascotaId.value)
  }
})

const cargarDiagnosticos = async () => {
  console.log('📡 Iniciando carga de diagnósticos...')
  
  if (!mascotaId.value || mascotaId.value === '') {
    console.error('❌ No hay mascotaId válido para cargar diagnósticos')
    alert('No se pudo identificar la mascota. Por favor, recarga la página.')
    return
  }

  try {
    cargando.value = true
    
    // Añadir parámetro para incluir diagnósticos archivados
    const url = `/api/mascotas/${encodeURIComponent(mascotaId.value)}/diagnosticos?incluir_archivados=true`
    console.log('📡 URL de API:', url)
    
    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    console.log('📡 Response status:', response.status)
    
    if (!response.ok) {
      const errorText = await response.text()
      console.error('❌ Error response:', errorText)
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('✅ Resultado de API:', result)
    
    if (result.success && result.data) {
      diagnosticos.value = result.data
      console.log('✅ Diagnósticos cargados:', diagnosticos.value.length)
    } else {
      console.warn('⚠️ No se encontraron diagnósticos:', result)
      diagnosticos.value = []
    }
  } catch (error) {
    console.error('❌ Error cargando diagnósticos:', error)
    console.error('❌ Stack trace:', error.stack)
    alert('Error al cargar los diagnósticos. Por favor, intenta nuevamente.')
    diagnosticos.value = []
  } finally {
    cargando.value = false
  }
}

const abrirRegistroDiagnostico = () => {
  if (!mascotaId.value) {
    console.error('❌ No se pudo obtener el ID de la mascota')
    alert('Error: No se pudo identificar la mascota')
    return
  }

  console.log('🔍 Navegando a registro de diagnóstico con mascotaId:', mascotaId.value)
  
  router.push({
    path: '/registro/diagnostico',
    query: {
      mascotaId: mascotaId.value,
      from: '/historialClinico/diagnosticos'
    }
  })
}

const abrirDetalles = (diagnostico) => {
  console.log('Abrir detalles de diagnóstico:', diagnostico)
}

const editarDiagnostico = (diagnostico) => {
  console.log('Editar diagnóstico:', diagnostico)
  
  // Solo permitir editar si no está archivado
  if (diagnostico.deleted_at) {
    alert('No se puede editar un diagnóstico archivado.')
    return
  }
  
  router.push({
    path: `/editar/diagnostico/${diagnostico.id}`,
    query: {
      mascotaId: mascotaId.value,
      from: '/historialClinico/diagnosticos'
    }
  })
}

// Función para solicitar baja lógica
const eliminarDiagnostico = (diagnostico) => {
  // Verificar que no esté ya archivado
  if (diagnostico.deleted_at) {
    alert('Este diagnóstico ya está archivado.')
    return
  }
  
  diagnosticoAEliminar.value = diagnostico
  mostrarConfirmacion.value = true
}

// Función para confirmar baja lógica
const confirmarEliminacion = async () => {
  if (!diagnosticoAEliminar.value) return

  try {
    // Usar el método POST a la ruta del diagnóstico (según tus rutas Laravel)
    const url = `/api/mascotas/${mascotaId.value}/diagnosticos/${diagnosticoAEliminar.value.id}`
    
    console.log('📡 URL de baja lógica:', url)
    
    const response = await fetch(url, {
      method: 'POST', // Usar POST para baja lógica
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    const result = await response.json()
    console.log('✅ Resultado de baja lógica:', result)

    if (result.success) {
      // Recargar la lista de diagnósticos
      await cargarDiagnosticos()
      alert('Diagnóstico archivado exitosamente')
    } else {
      throw new Error(result.message || 'Error al procesar la solicitud')
    }
  } catch (error) {
    console.error('❌ Error archivando diagnóstico:', error)
    alert('Error: ' + error.message)
  } finally {
    cancelarEliminacion()
  }
}

// Cancelar eliminación
const cancelarEliminacion = () => {
  diagnosticoAEliminar.value = null
  mostrarConfirmacion.value = false
}

// Función auxiliar para truncar texto
const truncarTexto = (texto, longitud) => {
  if (!texto) return ''
  return texto.length > longitud ? texto.substring(0, longitud) + '...' : texto
}

// Función para formatear fecha
const formatFecha = (fechaString) => {
  if (!fechaString) return ''
  const fecha = new Date(fechaString)
  return fecha.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

// Función para obtener color según estado
const getEstadoColor = (estado) => {
  const colores = {
    'activo': 'bg-red-100 text-red-800',
    'resuelto': 'bg-green-100 text-green-800',
    'cronico': 'bg-yellow-100 text-yellow-800',
    'seguimiento': 'bg-blue-100 text-blue-800',
    'sospecha': 'bg-gray-100 text-gray-800'
  }
  return colores[estado] || 'bg-gray-100 text-gray-800'
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
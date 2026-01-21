<!-- revisiones -->
<template>
  <div class="p-4 w-full flex flex-col h-full min-w-[300px]">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'x-ray']" class="mr-2" />
      Revisiones de la Mascota
    </h2>

    <!-- Estado de carga -->
    <div v-if="cargando" class="flex justify-center py-8">
      <div class="inline-flex items-center gap-2">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
        <span class="text-gray-500">Cargando revisiones...</span>
      </div>
    </div>

    <!-- Sin revisiones -->
    <div v-else-if="revisiones.length === 0" class="text-center py-8">
      <p class="text-gray-400">No se han registrado revisiones para esta mascota</p>
    </div>

    <!-- Lista de revisiones -->
    <div v-else class="space-y-4">
      <div
        v-for="revision in revisiones"
        :key="revision.id"
        class="relative border rounded-xl p-4 shadow-sm bg-white cursor-pointer hover:shadow-md transition"
        @click="abrirProcedimiento(revision)"
      >
        <!-- Íconos de acción en la esquina superior -->
        <div 
          v-if="$route.path.startsWith('/veterinarios')"
          class="absolute right-3 top-3 flex space-x-2"
        >
          <button
            class="text-gray-500 hover:text-blue-600 transition"
            @click.stop="editarRevision(revision)"
          >
            <font-awesome-icon :icon="['fas', 'pen']" />
          </button>
          <button
            class="text-gray-500 hover:text-red-600 transition"
            @click.stop="eliminarRevision(revision.id)"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
          <button
            @click.stop="abrirRegistroRevision"
            class="text-white bg-orange-600 rounded-full px-1 py-1 text-base font-bold shadow-md hover:bg-orange-700 hover:scale-105 transition transform duration-200"
          >
            Derivar
          </button>
        </div>

        <!-- Indicador de urgencia -->
        <div class="absolute left-3 top-3">
          <span 
            :class="{
              'px-2 py-1 rounded-full text-xs font-bold': true,
              'bg-green-100 text-green-800': revision.nivel_urgencia === 'rutinaria',
              'bg-blue-100 text-blue-800': revision.nivel_urgencia === 'preventiva',
              'bg-yellow-100 text-yellow-800': revision.nivel_urgencia === 'urgencia',
              'bg-red-100 text-red-800': revision.nivel_urgencia === 'emergencia'
            }"
          >
            {{ getUrgenciaLabel(revision.nivel_urgencia) }}
          </span>
        </div>

        <h3 class="text-lg font-bold text-gray-700 mb-2 pr-16">{{ revision.tipo_revision_nombre }}</h3>
        <p class="text-gray-600"><strong>Fecha:</strong> {{ formatFecha(revision.fecha_revision) }}</p>
        
        <p v-if="revision.motivo_consulta" class="text-gray-600">
          <strong>Motivo:</strong> {{ truncateText(revision.motivo_consulta, 50) }}
        </p>
        
        <p v-if="revision.diagnostico" class="text-gray-600">
          <strong>Diagnóstico:</strong> {{ truncateText(revision.diagnostico, 50) }}
        </p>
        
        <p v-if="revision.centro_veterinario_nombre" class="text-gray-600">
          <strong>Centro:</strong> {{ revision.centro_veterinario_nombre }}
        </p>
        
        <!-- Próxima revisión si existe -->
        <div v-if="revision.fecha_proxima_revision" class="mt-2 p-2 bg-blue-50 rounded">
          <p class="text-sm text-blue-700">
            <font-awesome-icon :icon="['fas', 'calendar-check']" class="mr-1" />
            <strong>Próxima revisión:</strong> {{ formatFecha(revision.fecha_proxima_revision) }}
          </p>
        </div>

        <!-- Indicaciones breves -->
        <div v-if="revision.indicaciones_medicas" class="mt-2">
          <p class="text-sm text-gray-600">
            <strong>Indicaciones:</strong> {{ truncateText(revision.indicaciones_medicas, 70) }}
          </p>
        </div>
      </div>
    </div>
   
    <div
      v-if="$route.path.startsWith('/veterinarios')"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroRevision"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Revisión
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
const revisiones = ref([])
const cargando = ref(false)

console.log('📍 Route params:', route.params)
console.log('📍 Mascota ID:', mascotaId)

// Cargar revisiones al montar el componente
onMounted(async () => {
  await cargarRevisiones()
})

// Función para cargar las revisiones desde la API
const cargarRevisiones = async () => {
  if (!mascotaId) {
    console.error('No hay mascotaId para cargar revisiones')
    return
  }

  try {
    cargando.value = true
    
    const response = await fetch(`/api/mascotas/${mascotaId}/revisiones`, {
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
      revisiones.value = result.data
      console.log('✅ Revisiones cargadas:', revisiones.value)
    } else {
      console.warn('No se encontraron revisiones:', result)
      revisiones.value = []
    }
  } catch (error) {
    console.error('❌ Error cargando revisiones:', error)
    revisiones.value = []
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
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Truncar texto largo
const truncateText = (text, maxLength) => {
  if (!text) return ''
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

// Obtener etiqueta de urgencia
const getUrgenciaLabel = (urgencia) => {
  const labels = {
    'rutinaria': 'Rutinaria',
    'preventiva': 'Preventiva',
    'urgencia': 'Urgencia',
    'emergencia': 'Emergencia'
  }
  return labels[urgencia] || urgencia
}

const abrirRegistroRevision = () => {
  if (!mascotaId) {
    alert('Error: No se pudo identificar la mascota')
    return
  }

  router.push({
    path: '/registro/revision',
    query: {
      from: '/historialPreventivo/revisiones',
      mascotaId: mascotaId  // se agrega mascotaId a la ruta
    }
  })
}

// Funciones para futura implementación
const abrirProcedimiento = (revision) => {
  console.log('Abrir detalles de revisión:', revision)
  // Aquí puedes implementar la lógica para ver detalles
  // router.push(`/mascotas/${mascotaId}/revisiones/${revision.id}`)
}

const editarRevision = (revision) => {
  console.log('Editar revisión:', revision)
  
  // Redireccionar a la vista de edición
  router.push({
    name: 'editarRevisión',
    params: {
      revisionId: revision.id
    },
    query: {
      from: '/historialPreventivo/revisiones',
      mascotaId: mascotaId  // Pasar el ID de la mascota como query param
    }
  })
}

const eliminarRevision = async (id) => {
  if (!confirm('¿Estás seguro de que deseas dar de baja esta revisión? Esta acción se puede revertir.')) {
    return;
  }

  try {
    const response = await fetch(`/api/mascotas/${mascotaId}/revisiones/${id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    });

    const result = await response.json();

    if (result.success) {
      alert('Revisión dada de baja exitosamente');
      // Recargar la lista de revisiones
      await cargarRevisiones();
    } else {
      alert('Error al dar de baja la revisión: ' + result.message);
    }
  } catch (error) {
    console.error('Error eliminando revisión:', error);
    alert('Error al conectar con el servidor');
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
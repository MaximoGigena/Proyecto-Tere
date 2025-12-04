<!-- components/módulo_adopciones/OverlayAdvertenciaAdopcion.vue -->
<template>
  <div 
    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-[60] p-4"
    @click.self="cerrar"
  >
    <div class="bg-white rounded-2xl w-full max-w-lg mx-auto shadow-xl max-h-[90vh] overflow-hidden flex flex-col">
      
      <!-- Encabezado -->
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center gap-4 mb-4">
          <div class="relative">
            <img
              :src="mascota.foto"
              alt="foto mascota"
              class="w-16 h-16 object-cover rounded-xl"
            />
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-800">
              Configurar permisos para {{ mascota.nombre }}
            </h2>
            <p class="text-gray-600 text-sm mt-1">
              Selecciona qué información quieres compartir con los adoptantes
            </p>
          </div>
        </div>
      </div>

      <!-- Contenido desplazable -->
      <div class="flex-1 overflow-y-auto p-6">
        
        <!-- Advertencia importante -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-yellow-700 font-semibold">
                Información importante
              </p>
              <p class="text-sm text-yellow-600 mt-1">
                Los datos que compartas serán visibles para posibles adoptantes
              </p>
            </div>
          </div>
        </div>

        <!-- Opción 1: Historial médico -->
        <div class="mb-8">
          <div class="flex items-start mb-4">
            <div class="flex items-center h-5">
              <input
                id="historial-medico"
                v-model="permisos.compartirHistorialMedico"
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
              >
            </div>
            <label for="historial-medico" class="ml-3">
              <span class="text-lg font-medium text-gray-900">
                Compartir historial médico
              </span>
              <p class="text-gray-600 text-sm mt-1">
                Los posibles adoptantes podrán ver:
              </p>
              <ul class="text-gray-600 text-sm mt-2 space-y-1 ml-4 list-disc">
                <li>Vacunas aplicadas</li>
                <li>Enfermedades diagnosticadas</li>
                <li>Cirugías realizadas</li>
                <li>Tratamientos en curso</li>
              </ul>
            </label>
          </div>
        </div>

        <!-- Opción 2: Medios de contacto -->
        <div class="mb-8">
          <div class="flex items-start mb-4">
            <div class="flex items-center h-5">
              <input
                id="medios-contacto"
                v-model="permisos.compartirMediosContacto"
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                @change="handleMediosContactoChange"
              >
            </div>
            <label for="medios-contacto" class="ml-3">
              <span class="text-lg font-medium text-gray-900">
                Compartir medios de contacto
              </span>
              <p class="text-gray-600 text-sm mt-1">
                Los posibles adoptantes podrán contactarte a través de:
              </p>
              
              <!-- Lista de medios disponibles (solo visible si está activado) -->
              <div v-if="permisos.compartirMediosContacto && mediosContacto.length > 0" class="mt-3 space-y-3">
                <div
                  v-for="medio in mediosContacto"
                  :key="medio.id"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                >
                  <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 rounded-lg">
                      <span class="text-blue-600 font-medium">{{ medio.tipo }}</span>
                    </div>
                    <div>
                      <p class="font-medium">{{ medio.valor }}</p>
                      <p class="text-xs text-gray-500">{{ medio.descripcion }}</p>
                    </div>
                  </div>
                  <div class="flex items-center">
                    <input
                      type="checkbox"
                      :id="'medio-' + medio.id"
                      v-model="mediosSeleccionados"
                      :value="medio.id"
                      class="w-4 h-4 text-blue-600 rounded"
                    >
                  </div>
                </div>
              </div>
              
              <div v-else-if="permisos.compartirMediosContacto && mediosContacto.length === 0" class="mt-3 p-4 bg-gray-50 rounded-lg text-center">
                <p class="text-gray-500 text-sm">
                  No tenés medios de contacto registrados.
                </p>
              </div>
            </label>
          </div>
        </div>

      </div>

      <!-- Botones de acción - SIMPLIFICADO -->
      <div class="p-6 border-t border-gray-200">
        <div class="flex gap-3">
          <button
            @click="cerrar"
            class="flex-1 bg-gray-200 text-gray-800 py-3 rounded-xl font-semibold hover:bg-gray-300 transition"
          >
            Cancelar
          </button>
          <button
            @click="continuar"
            class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2"
          >
            <span>Continuar</span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
        <p class="text-center text-gray-500 text-xs mt-3">
          En el siguiente paso confirmarás la publicación
        </p>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const props = defineProps({
  mascota: {
    type: Object,
    required: true,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'confirmar'])

// Estados
const mediosContacto = ref([])
const mediosSeleccionados = ref([])

// Permisos por defecto
const permisos = reactive({
  compartirHistorialMedico: true,
  compartirMediosContacto: false
})

// Cargar medios de contacto del usuario
async function cargarMediosContacto() {
  try {
    const usuarioId = localStorage.getItem('userId') || 1
    
    const response = await fetch(`/api/usuarios/${usuarioId}/medios`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      mediosContacto.value = data
      
      // Seleccionar todos por defecto
      mediosSeleccionados.value = data.map(medio => medio.id)
    }
  } catch (error) {
    console.error('Error cargando medios de contacto:', error)
  }
}

// Cuando se activa/desactiva compartir medios de contacto
function handleMediosContactoChange() {
  if (!permisos.compartirMediosContacto) {
    // Si se desactiva, limpiar selecciones
    mediosSeleccionados.value = []
  } else {
    // Si se activa, seleccionar todos por defecto
    mediosSeleccionados.value = mediosContacto.value.map(medio => medio.id)
  }
}

// Solo continuar a la siguiente vista
function continuar() {
  // Preparar datos para pasar a la siguiente vista
  const datosOferta = {
    mascota: props.mascota,  // <-- INCLUIR LA MASCOTA
    mascotaId: props.mascota.id,
    permisos: {
      ...permisos,
      mediosContactoSeleccionados: permisos.compartirMediosContacto ? mediosSeleccionados.value : []
    }
  }
  
  console.log('📤 Continuando a confirmación final con datos:', datosOferta)
  
  // Pasar los datos al padre
  emit('confirmar', datosOferta)
}

function cerrar() {
  emit('close')
}

// Cargar medios de contacto al montar
onMounted(() => {
  cargarMediosContacto()
})
</script>

<style scoped>
/* Estilos personalizados */
.max-h-\[90vh\] {
  max-height: 90vh;
}

/* Scrollbar personalizada */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
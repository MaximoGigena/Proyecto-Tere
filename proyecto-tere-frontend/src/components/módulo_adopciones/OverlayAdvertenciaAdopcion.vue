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
              :src="mascota.foto_url || mascota.foto"
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
              
              <!-- Lista de medios disponibles con iconos (solo visible si está activado) -->
              <div v-if="permisos.compartirMediosContacto" class="mt-3 space-y-3">
                <div v-if="cargandoMedios" class="text-center py-4">
                  <div class="animate-spin inline-block w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full"></div>
                  <p class="text-sm text-gray-500 mt-2">Cargando medios de contacto...</p>
                </div>
                
                <div v-else-if="mediosContactoDisponibles.length === 0" class="p-4 bg-yellow-50 rounded-lg text-center">
                  <p class="text-yellow-700 text-sm">⚠️ No tenés medios de contacto registrados.</p>
                  <p class="text-xs text-yellow-600 mt-1">Configurá tu perfil para agregar teléfono, email o Telegram.</p>
                </div>

                <!-- WhatsApp -->
                <div 
                  v-for="medio in mediosContactoDisponibles"
                  :key="medio.id"
                  class="flex items-center justify-between p-3 rounded-lg border-2 transition-all cursor-pointer"
                  :class="[
                    mediosSeleccionados.includes(medio.id) 
                      ? medio.id === 1 ? 'border-green-500 bg-green-50' 
                      : medio.id === 2 ? 'border-blue-500 bg-blue-50'
                      : 'border-blue-400 bg-blue-50'
                      : 'border-gray-200 bg-gray-50 hover:border-gray-300'
                  ]"
                  @click="toggleMedio(medio.id)"
                >
                  <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg" :class="medio.id === 1 ? 'bg-green-100' : 'bg-blue-100'">
                      <span class="text-xl" :class="medio.id === 1 ? 'text-green-600' : 'text-blue-600'">
                        {{ medio.icono }}
                      </span>
                    </div>
                    <div>
                      <p class="font-medium text-gray-800">{{ medio.tipo }}</p>
                      <p class="text-sm text-gray-600">{{ medio.valor }}</p>
                    </div>
                  </div>
                  <div class="flex items-center">
                    <input
                      type="checkbox"
                      :checked="mediosSeleccionados.includes(medio.id)"
                      @change="toggleMedio(medio.id)"
                      class="w-5 h-5 rounded"
                      :class="medio.id === 1 ? 'text-green-600' : 'text-blue-600'"
                    >
                  </div>
                </div>
              </div>
            </label>
          </div>
        </div>

      </div>

      <!-- Botones de acción -->
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
            :disabled="!puedeContinuar"
            class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
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
import { ref, reactive, onMounted, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
  mascota: {
    type: Object,
    required: true,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'confirmar'])

// Estados
const mediosContactoDisponibles = ref([])
const mediosSeleccionados = ref([])
const cargandoMedios = ref(false)

// Permisos por defecto
const permisos = reactive({
  compartirHistorialMedico: true,
  compartirMediosContacto: false
})

// Computed para validar si se puede continuar
const puedeContinuar = computed(() => {
  // Si se activó compartir medios de contacto, debe haber al menos uno seleccionado
  if (permisos.compartirMediosContacto) {
    return mediosSeleccionados.value.length > 0
  }
  // Si no se activó, siempre se puede continuar
  return true
})

// Cargar medios de contacto del usuario autenticado
async function cargarMediosContacto() {
  cargandoMedios.value = true
  try {
    const token = localStorage.getItem('token')
    if (!token) {
      console.error('No hay token de autenticación')
      return
    }

    const response = await axios.get('/api/user/medios-contacto', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.data.success) {
      mediosContactoDisponibles.value = response.data.data
      console.log('📞 Medios de contacto cargados:', mediosContactoDisponibles.value)
      
      // Seleccionar todos por defecto cuando se active
      mediosSeleccionados.value = mediosContactoDisponibles.value.map(m => m.id)
    } else {
      console.error('Error en respuesta:', response.data)
    }
  } catch (error) {
    console.error('❌ Error cargando medios de contacto:', error)
    if (error.response) {
      console.error('Respuesta del servidor:', error.response.data)
    }
  } finally {
    cargandoMedios.value = false
  }
}

// Alternar selección de un medio
function toggleMedio(medioId) {
  const index = mediosSeleccionados.value.indexOf(medioId)
  if (index === -1) {
    mediosSeleccionados.value.push(medioId)
  } else {
    mediosSeleccionados.value.splice(index, 1)
  }
}

// Cuando se activa/desactiva compartir medios de contacto
function handleMediosContactoChange() {
  if (!permisos.compartirMediosContacto) {
    // Si se desactiva, limpiar selecciones
    mediosSeleccionados.value = []
  } else {
    // Si se activa, seleccionar todos los medios disponibles
    mediosSeleccionados.value = mediosContactoDisponibles.value.map(m => m.id)
  }
}

// Continuar a la confirmación final
function continuar() {
  // Validar que si se activaron medios, haya al menos uno seleccionado
  if (permisos.compartirMediosContacto && mediosSeleccionados.value.length === 0) {
    console.warn('No se seleccionaron medios de contacto')
    return
  }
  
  // Preparar datos para pasar a la siguiente vista
  const datosOferta = {
    mascota: props.mascota,
    mascotaId: props.mascota.id,
    permisos: {
      compartirHistorialMedico: permisos.compartirHistorialMedico,
      compartirMediosContacto: permisos.compartirMediosContacto,
      mediosContactoSeleccionados: permisos.compartirMediosContacto ? mediosSeleccionados.value : []
    }
  }
  
  console.log('📤 Continuando a confirmación final con datos:', datosOferta)
  
  // Emitir al padre
  emit('confirmar', datosOferta)
}

function cerrar() {
  emit('close')
}

// Cargar medios de contacto al montar el componente
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
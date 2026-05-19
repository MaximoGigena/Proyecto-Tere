<!-- EditarFichaOverlay.vue -->
<template>
  <div 
    v-if="visible" 
    class="fixed inset-0 z-50 flex items-center justify-center"
    @click.self="cerrar"
  >
    <!-- Fondo con blur -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-md"></div>
    
    <!-- Modal -->
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto m-4">
      <!-- Header -->
      <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800">
          <font-awesome-icon :icon="['fas', 'pen']" class="mr-2 text-blue-500" />
          Modificar ficha médica
        </h2>
        <button 
          @click="cerrar"
          class="text-gray-400 hover:text-gray-600 transition"
        >
          <font-awesome-icon :icon="['fas', 'times']" class="text-xl" />
        </button>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="guardarCambios" class="p-6 space-y-6">
        <!-- Color y señas particulares -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <font-awesome-icon :icon="['fas', 'paw']" class="mr-2 text-gray-400" />
            Color y señas particulares
          </label>
          <textarea
            v-model="formData.color_y_senas"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Ej: Blanco con manchas negras, cicatriz en oreja izquierda..."
          ></textarea>
        </div>

        <!-- Peso y tipo sanguíneo -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <font-awesome-icon :icon="['fas', 'weight-scale']" class="mr-2 text-gray-400" />
              Peso (kg)
            </label>
            <input
              v-model.number="formData.peso"
              type="number"
              step="0.1"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="0.0"
            />
            <p class="text-xs text-gray-500 mt-1">
              Última actualización: {{ pesoUltimaActualizacion }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <font-awesome-icon :icon="['fas', 'droplet']" class="mr-2 text-gray-400" />
              Tipo sanguíneo
            </label>
            <select
              v-model="formData.tipo_sanguineo"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">No registrado</option>
              <option value="DEA 1.1 positivo">DEA 1.1 positivo</option>
              <option value="DEA 1.1 negativo">DEA 1.1 negativo</option>
              <option value="DEA 1.2 positivo">DEA 1.2 positivo</option>
              <option value="DEA 3 positivo">DEA 3 positivo</option>
              <option value="DEA 4 positivo">DEA 4 positivo</option>
              <option value="DEA 5 positivo">DEA 5 positivo</option>
              <option value="DEA 7 positivo">DEA 7 positivo</option>
            </select>
          </div>
        </div>

        <!-- Número de chip -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <font-awesome-icon :icon="['fas', 'microchip']" class="mr-2 text-gray-400" />
            Número de chip / identificación
          </label>
          <input
            v-model="formData.numero_chip"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="900123000123456"
          />
        </div>

        <!-- Botones de acción -->
        <div class="flex gap-3 pt-4 border-t">
          <button
            type="button"
            @click="cerrar"
            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="guardando"
            class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <font-awesome-icon v-if="guardando" :icon="['fas', 'spinner']" spin class="mr-2" />
            {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  fichaData: {
    type: Object,
    default: () => ({})
  },
  mascotaId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['close', 'actualizado'])

const { accessToken } = useAuth()
const guardando = ref(false)

// Formulario reactivo
const formData = ref({
  color_y_senas: '',
  peso: null,
  tipo_sanguineo: '',
  numero_chip: '',
  proximo_control: '',
  proxima_vacunacion: ''
})

// Calcular fecha de última actualización del peso
const pesoUltimaActualizacion = ref('')

// Cargar datos cuando se abre el overlay
watch(() => props.visible, (nuevoValor) => {
  if (nuevoValor && props.fichaData) {
    cargarDatosEnFormulario()
  }
})

const cargarDatosEnFormulario = () => {
  const ficha = props.fichaData.ficha_medica || {}
  
  formData.value = {
    color_y_senas: ficha.color_y_senas || '',
    peso: ficha.peso?.valor || null,
    tipo_sanguineo: ficha.tipo_sanguineo || '',
    numero_chip: ficha.numero_chip || '',
    proximo_control: ficha.proximo_control || '',
    proxima_vacunacion: ficha.proxima_vacunacion || ''
  }
  
  pesoUltimaActualizacion.value = ficha.peso?.ultima_actualizacion 
    ? new Date(ficha.peso.ultima_actualizacion).toLocaleDateString('es-ES')
    : 'Nunca'
}

const cerrar = () => {
  emit('close')
}

const guardarCambios = async () => {
  if (!props.mascotaId) {
    console.error('No se tiene ID de mascota')
    return
  }

  guardando.value = true

  try {
    // Construir datos para actualizar
    const datosActualizar = {}
    
    // Comparar y agregar solo los campos que cambiaron
    if (formData.value.color_y_senas !== props.fichaData.ficha_medica?.color_y_senas) {
      datosActualizar.color_y_senas = formData.value.color_y_senas
    }
    
    if (formData.value.tipo_sanguineo !== props.fichaData.ficha_medica?.tipo_sanguineo) {
      datosActualizar.tipo_sanguineo = formData.value.tipo_sanguineo
    }
    
    if (formData.value.numero_chip !== props.fichaData.ficha_medica?.numero_chip) {
      datosActualizar.numero_chip = formData.value.numero_chip
    }
    
    // 👇 IMPORTANTE: Incluir el peso en los datos a actualizar
    if (formData.value.peso !== props.fichaData.ficha_medica?.peso?.valor && formData.value.peso > 0) {
      datosActualizar.peso_actual = formData.value.peso  // ← peso_actual, no peso
    }
    
    // Actualizar todos los campos de una sola vez
    if (Object.keys(datosActualizar).length > 0) {
      // 👇 Cambiar PATCH por PUT
      const response = await axios.put(`/api/mascotas/${props.mascotaId}/ficha-medica`,
        datosActualizar,
        { headers: { Authorization: `Bearer ${accessToken.value}` } }
      )
      
      if (response.data.success) {
        emit('actualizado', response.data.data)
        cerrar()
      }
    } else {
      // Si no hubo cambios, solo cerrar
      cerrar()
    }
    
  } catch (error) {
    console.error('Error guardando cambios:', error)
    alert(error.response?.data?.message || 'Error al guardar los cambios')
  } finally {
    guardando.value = false
  }
}
</script>

<style scoped>
/* Animaciones opcionales */
.fixed {
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

</style>
<template>
  <!-- Fondo oscuro translúcido -->
  <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <!-- Contenedor del modal -->
    <div class="max-w-xl w-full mx-4 bg-white shadow-xl rounded-2xl overflow-hidden">
      <!-- Header con navegación -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-4">
        <button 
          @click="$emit('close')"
          class="text-gray-500 hover:text-gray-700 transition-colors text-xl"
        >
          ←
        </button>
        <h2 class="text-xl font-semibold text-gray-800">Experiencia</h2>
      </div>

      <!-- Contenido -->
      <div class="p-6 space-y-6">
        <!-- Modo oscuro -->
        <div class="space-y-4">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Apariencia</h3>
          
          <div class="flex items-center justify-between py-2">
            <div>
              <p class="font-medium text-gray-800">Modo oscuro</p>
              <p class="text-sm text-gray-500">Cambia la apariencia de la aplicación</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input 
                type="checkbox" 
                v-model="form.modoOscuro" 
                class="sr-only peer"
                @change="guardarCambios"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
            </label>
          </div>

          <div class="flex items-center justify-between py-2">
            <div>
              <p class="font-medium text-gray-800">Animaciones suaves</p>
              <p class="text-sm text-gray-500">Activar transiciones y efectos visuales</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input 
                type="checkbox" 
                v-model="form.animaciones" 
                class="sr-only peer"
                @change="guardarCambios"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
            </label>
          </div>
        </div>

        <!-- Tamaño de fuente -->
        <div class="space-y-4">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Accesibilidad</h3>
          
          <div>
            <p class="font-medium text-gray-800 mb-2">Tamaño de fuente</p>
            <div class="flex gap-2">
              <button 
                v-for="tamano in tamanosFuente" 
                :key="tamano.value"
                @click="form.tamanoFuente = tamano.value; guardarCambios()"
                :class="[
                  'flex-1 py-2 px-3 rounded-lg transition-all',
                  form.tamanoFuente === tamano.value 
                    ? 'bg-teal-600 text-white' 
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                {{ tamano.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- Feedback visual -->
        <div class="pt-4 border-t border-gray-100">
          <div class="bg-green-50 text-green-700 p-3 rounded-lg text-sm" v-if="mostrarFeedback">
            ✓ Cambios guardados
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'

const emit = defineEmits(['close', 'actualizar-config'])

const form = reactive({
  modoOscuro: false,
  animaciones: true,
  tamanoFuente: 'medium'
})

const tamanosFuente = [
  { value: 'small', label: 'Pequeño' },
  { value: 'medium', label: 'Mediano' },
  { value: 'large', label: 'Grande' }
]

const mostrarFeedback = ref(false)
let timeoutFeedback = null

// Cargar configuraciones guardadas
onMounted(() => {
  const saved = localStorage.getItem('config_experiencia')
  if (saved) {
    Object.assign(form, JSON.parse(saved))
  }
})

function guardarCambios() {
  // Guardar en localStorage
  localStorage.setItem('config_experiencia', JSON.stringify(form))
  
  // Emitir evento para actualizar configuración global
  emit('actualizar-config', { experiencia: form })
  
  // Mostrar feedback
  mostrarFeedback.value = true
  if (timeoutFeedback) clearTimeout(timeoutFeedback)
  timeoutFeedback = setTimeout(() => {
    mostrarFeedback.value = false
  }, 2000)
}
</script>
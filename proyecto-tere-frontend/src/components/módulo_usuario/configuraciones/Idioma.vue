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
        <h2 class="text-xl font-semibold text-gray-800">Idioma</h2>
      </div>

      <!-- Contenido -->
      <div class="p-6 space-y-6">
        <!-- Idioma principal -->
        <div class="space-y-4">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Idioma de la aplicación</h3>
          
          <div class="space-y-2">
            <div 
              v-for="idioma in idiomas" 
              :key="idioma.code"
              @click="seleccionarIdioma(idioma.code)"
              class="flex items-center justify-between p-3 rounded-lg cursor-pointer transition-all"
              :class="[
                form.idioma === idioma.code 
                  ? 'bg-teal-50 border-2 border-teal-200' 
                  : 'bg-gray-50 hover:bg-gray-100 border-2 border-transparent'
              ]"
            >
              <div class="flex items-center gap-3">
                <span class="text-2xl">{{ idioma.flag }}</span>
                <div>
                  <p class="font-medium text-gray-800">{{ idioma.nombre }}</p>
                  <p class="text-sm text-gray-500">{{ idioma.nativo }}</p>
                </div>
              </div>
              <div v-if="form.idioma === idioma.code" class="text-teal-600">
                ✓
              </div>
            </div>
          </div>
        </div>

        <!-- Formato de fecha -->
        <div class="space-y-4 pt-4 border-t border-gray-100">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Formato de fecha</h3>
          
          <div class="grid grid-cols-2 gap-3">
            <button
              v-for="formato in formatosFecha"
              :key="formato.value"
              @click="form.formatoFecha = formato.value; guardarCambios()"
              :class="[
                'p-3 rounded-lg text-center transition-all',
                form.formatoFecha === formato.value
                  ? 'bg-teal-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              <div class="text-sm font-medium">{{ formato.label }}</div>
              <div class="text-xs mt-1 opacity-80">{{ formato.ejemplo }}</div>
            </button>
          </div>
        </div>

        <!-- Zona horaria -->
        <div class="space-y-4 pt-4 border-t border-gray-100">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Zona horaria</h3>
          
          <select 
            v-model="form.zonaHoraria"
            @change="guardarCambios"
            class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
          >
            <option v-for="zona in zonasHorarias" :key="zona" :value="zona">
              {{ zona }}
            </option>
          </select>
        </div>

        <!-- Feedback visual -->
        <div class="pt-4">
          <div class="bg-green-50 text-green-700 p-3 rounded-lg text-sm" v-if="mostrarFeedback">
            ✓ Configuración de idioma guardada
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
  idioma: 'es',
  formatoFecha: 'DD/MM/YYYY',
  zonaHoraria: 'America/Argentina/Buenos_Aires'
})

const idiomas = [
  { code: 'es', nombre: 'Español', nativo: 'Español', flag: '🇪🇸' },
  { code: 'en', nombre: 'English', nativo: 'English', flag: '🇺🇸' },
  { code: 'pt', nombre: 'Português', nativo: 'Português', flag: '🇧🇷' },
  { code: 'fr', nombre: 'Français', nativo: 'Français', flag: '🇫🇷' }
]

const formatosFecha = [
  { value: 'DD/MM/YYYY', label: 'DD/MM/AAAA', ejemplo: '31/12/2024' },
  { value: 'MM/DD/YYYY', label: 'MM/DD/AAAA', ejemplo: '12/31/2024' },
  { value: 'YYYY-MM-DD', label: 'AAAA-MM-DD', ejemplo: '2024-12-31' }
]

const zonasHorarias = [
  'America/Argentina/Buenos_Aires',
  'America/Mexico_City',
  'America/Bogota',
  'America/Santiago',
  'Europe/Madrid',
  'Europe/London',
  'UTC'
]

const mostrarFeedback = ref(false)
let timeoutFeedback = null

onMounted(() => {
  const saved = localStorage.getItem('config_idioma')
  if (saved) {
    Object.assign(form, JSON.parse(saved))
  }
})

function seleccionarIdioma(code) {
  form.idioma = code
  guardarCambios()
}

function guardarCambios() {
  localStorage.setItem('config_idioma', JSON.stringify(form))
  emit('actualizar-config', { idioma: form })
  
  mostrarFeedback.value = true
  if (timeoutFeedback) clearTimeout(timeoutFeedback)
  timeoutFeedback = setTimeout(() => {
    mostrarFeedback.value = false
  }, 2000)
}
</script>
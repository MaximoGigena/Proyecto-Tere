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
        <h2 class="text-xl font-semibold text-gray-800">Privacidad y Seguridad</h2>
      </div>

      <!-- Contenido -->
      <div class="p-6 space-y-6">
        <!-- Visibilidad del perfil -->
        <div class="space-y-4">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Visibilidad</h3>
          
          <div class="space-y-3">
            <div 
              v-for="opcion in opcionesVisibilidad"
              :key="opcion.value"
              @click="form.visibilidad = opcion.value; guardarCambios()"
              class="flex items-center justify-between p-3 rounded-lg cursor-pointer transition-all"
              :class="[
                form.visibilidad === opcion.value 
                  ? 'bg-teal-50 border-2 border-teal-200' 
                  : 'bg-gray-50 hover:bg-gray-100 border-2 border-transparent'
              ]"
            >
              <div>
                <p class="font-medium text-gray-800">{{ opcion.label }}</p>
                <p class="text-sm text-gray-500">{{ opcion.descripcion }}</p>
              </div>
              <div v-if="form.visibilidad === opcion.value" class="text-teal-600">
                ✓
              </div>
            </div>
          </div>
        </div>

        <!-- Datos personales -->
        <div class="space-y-4 pt-4 border-t border-gray-100">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Datos personales</h3>
          
          <div class="space-y-3">
            <div class="flex items-center justify-between py-2">
              <div>
                <p class="font-medium text-gray-800">Mostrar email</p>
                <p class="text-sm text-gray-500">Permitir que otros vean tu correo electrónico</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  v-model="form.mostrarEmail" 
                  class="sr-only peer"
                  @change="guardarCambios"
                />
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
              </label>
            </div>

            <div class="flex items-center justify-between py-2">
              <div>
                <p class="font-medium text-gray-800">Perfil público</p>
                <p class="text-sm text-gray-500">Permitir que tu perfil aparezca en búsquedas</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  v-model="form.perfilPublico" 
                  class="sr-only peer"
                  @change="guardarCambios"
                />
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
              </label>
            </div>
          </div>
        </div>

        <!-- Seguridad -->
        <div class="space-y-4 pt-4 border-t border-gray-100">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Seguridad</h3>
          
          <div class="space-y-3">
            <div class="flex items-center justify-between py-2">
              <div>
                <p class="font-medium text-gray-800">Autenticación de dos factores</p>
                <p class="text-sm text-gray-500">Añade una capa extra de seguridad</p>
              </div>
              <button 
                @click="configurar2FA"
                class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition text-sm"
              >
                Configurar
              </button>
            </div>

            <div class="flex items-center justify-between py-2">
              <div>
                <p class="font-medium text-gray-800">Sesiones activas</p>
                <p class="text-sm text-gray-500">Gestiona dispositivos conectados</p>
              </div>
              <button 
                @click="verSesiones"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm"
              >
                Ver sesiones
              </button>
            </div>
          </div>
        </div>

        <!-- Actividad -->
        <div class="space-y-4 pt-4 border-t border-gray-100">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Actividad</h3>
          
          <div class="space-y-3">
            <div class="flex items-center justify-between py-2">
              <div>
                <p class="font-medium text-gray-800">Mostrar estado de actividad</p>
                <p class="text-sm text-gray-500">Permitir que otros vean cuando estás activo</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  v-model="form.mostrarActividad" 
                  class="sr-only peer"
                  @change="guardarCambios"
                />
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
              </label>
            </div>

            <div class="flex items-center justify-between py-2">
              <div>
                <p class="font-medium text-gray-800">Historial de actividad</p>
                <p class="text-sm text-gray-500">Guardar historial de acciones importantes</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  v-model="form.historialActividad" 
                  class="sr-only peer"
                  @change="guardarCambios"
                />
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
              </label>
            </div>
          </div>
        </div>

        <!-- Exportar datos -->
        <div class="pt-4 border-t border-gray-100">
          <button 
            @click="exportarDatos"
            class="w-full py-3 px-4 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition"
          >
            Exportar mis datos
          </button>
          <p class="text-xs text-gray-400 text-center mt-2">
            Descarga una copia de todos tus datos en formato JSON
          </p>
        </div>

        <!-- Feedback visual -->
        <div class="pt-2">
          <div class="bg-green-50 text-green-700 p-3 rounded-lg text-sm" v-if="mostrarFeedback">
            ✓ Configuración de privacidad guardada
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
  visibilidad: 'publico',
  mostrarEmail: false,
  perfilPublico: true,
  mostrarActividad: true,
  historialActividad: true
})

const opcionesVisibilidad = [
  { value: 'publico', label: 'Público', descripcion: 'Cualquier persona puede ver tu perfil' },
  { value: 'amigos', label: 'Solo amigos', descripcion: 'Solo tus contactos pueden ver tu perfil' },
  { value: 'privado', label: 'Privado', descripcion: 'Nadie puede ver tu perfil' }
]

const mostrarFeedback = ref(false)
let timeoutFeedback = null

onMounted(() => {
  const saved = localStorage.getItem('config_privacidad')
  if (saved) {
    Object.assign(form, JSON.parse(saved))
  }
})

function guardarCambios() {
  localStorage.setItem('config_privacidad', JSON.stringify(form))
  emit('actualizar-config', { privacidad: form })
  
  mostrarFeedback.value = true
  if (timeoutFeedback) clearTimeout(timeoutFeedback)
  timeoutFeedback = setTimeout(() => {
    mostrarFeedback.value = false
  }, 2000)
}

function configurar2FA() {
  alert('Funcionalidad de autenticación de dos factores en desarrollo')
}

function verSesiones() {
  alert('Mostrando sesiones activas...')
}

function exportarDatos() {
  const datos = {
    configuraciones: {
      idioma: localStorage.getItem('config_idioma'),
      experiencia: localStorage.getItem('config_experiencia'),
      privacidad: localStorage.getItem('config_privacidad'),
      notificaciones: localStorage.getItem('config_notificaciones')
    },
    fechaExportacion: new Date().toISOString()
  }
  
  const blob = new Blob([JSON.stringify(datos, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `mis-datos-${new Date().toISOString().split('T')[0]}.json`
  a.click()
  URL.revokeObjectURL(url)
  
  alert('Datos exportados exitosamente')
}
</script>
<!-- components/módulo_adopciones/OverlayConfirmacionFinal.vue -->
<template>
  <div 
    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-[70] p-4"
    @click.self="cerrar"
  >
    <div class="bg-white rounded-2xl w-full max-w-md mx-auto shadow-xl">
      
      <!-- Encabezado -->
      <div class="p-6 text-center border-b border-gray-200">
        <div class="mx-auto w-16 h-16 mb-4">
          <img
            :src="mascota.foto"
            alt="foto mascota"
            class="w-full h-full object-cover rounded-full"
          />
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">
          ¿Estás seguro?
        </h2>
        <p class="text-gray-600">
          Vas a publicar a <span class="font-semibold text-blue-600">{{ mascota.nombre }}</span> para adopción
        </p>
      </div>

      <!-- Mensaje de confirmación -->
      <div class="p-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                <span class="font-semibold">Recuerda:</span> Puedes cancelar esta oferta en cualquier momento desde la sección de "Gestión de Adopciones".
              </p>
            </div>
          </div>
        </div>

        <!-- Resumen de permisos -->
        <div class="mb-6" v-if="permisos">
          <p class="text-sm text-gray-600 mb-2">Permisos seleccionados:</p>
          <ul class="text-sm text-gray-700 space-y-1">
            <li class="flex items-center gap-2" v-if="permisos.compartirHistorialMedico">
              <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span>Historial médico visible</span>
            </li>
            <li class="flex items-center gap-2" v-if="permisos.compartirMediosContacto">
              <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span>Medios de contacto compartidos</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Botones de acción -->
      <div class="p-6 border-t border-gray-200">
        <div class="flex gap-3">
          <button
            @click="cerrar"
            class="flex-1 bg-gray-200 text-gray-800 py-3 rounded-xl font-semibold hover:bg-gray-300 transition"
          >
            Volver atrás
          </button>
          <button
            @click="confirmar"
            class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2"
            :disabled="procesando"
          >
            <svg v-if="procesando" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            <span v-else>Sí, publicar ahora</span>
          </button>
        </div>
        <p class="text-center text-gray-500 text-xs mt-3">
          La oferta será visible para posibles adoptantes inmediatamente
        </p>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  mascota: {
    type: Object,
    required: true,
    default: () => ({})
  },
  permisos: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'confirmar'])

const procesando = ref(false)

function confirmar() {
  procesando.value = true
  
  // Simular un pequeño delay para mostrar el spinner
  setTimeout(() => {
    emit('confirmar')
    procesando.value = false
  }, 800)
}

function cerrar() {
  emit('close')
}
</script>
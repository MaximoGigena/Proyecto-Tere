<template>
  <!-- Fondo oscuro translúcido -->
  <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <!-- Contenedor del modal -->
    <div class="max-w-xl w-full mx-4 bg-white shadow-xl rounded-2xl overflow-hidden relative">
      <!-- Botón cerrar -->
      <button
        @click="$router.back()"
        class="absolute top-4 right-4 z-10 text-gray-500 hover:text-gray-700 transition-colors"
      >
        ✕
      </button>

      <!-- Header -->
      <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-6 py-6">
        <h2 class="text-2xl font-bold text-white">Configuración</h2>
        <p class="text-teal-100 text-sm mt-1">Personaliza tu experiencia</p>
      </div>

      <!-- Lista de secciones -->
      <div class="p-4 space-y-1">
        <!-- Notificaciones -->
        <div 
          @click="abrirModal('notificaciones')"
          class="flex items-center gap-4 p-4 rounded-xl transition-all duration-200 cursor-pointer hover:bg-gray-50 group"
        >
          <div class="text-2xl w-10 h-10 flex items-center justify-center bg-gray-100 rounded-full group-hover:bg-white transition-colors">
            🔔
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800">Notificaciones</h3>
            <p class="text-sm text-gray-500">Configura cómo y cuándo recibir notificaciones</p>
          </div>
          <div class="text-gray-400 group-hover:text-teal-600 transition-colors">
            →
          </div>
        </div>

        <!-- Privacidad -->
        <div 
          @click="abrirModal('privacidad')"
          class="flex items-center gap-4 p-4 rounded-xl transition-all duration-200 cursor-pointer hover:bg-gray-50 group"
        >
          <div class="text-2xl w-10 h-10 flex items-center justify-center bg-gray-100 rounded-full group-hover:bg-white transition-colors">
            🔒
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800">Privacidad</h3>
            <p class="text-sm text-gray-500">Controla tu información y seguridad</p>
          </div>
          <div class="text-gray-400 group-hover:text-teal-600 transition-colors">
            →
          </div>
        </div>

        <!-- Idioma -->
        <div 
          @click="abrirModal('idioma')"
          class="flex items-center gap-4 p-4 rounded-xl transition-all duration-200 cursor-pointer hover:bg-gray-50 group"
        >
          <div class="text-2xl w-10 h-10 flex items-center justify-center bg-gray-100 rounded-full group-hover:bg-white transition-colors">
            🌐
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800">Idioma</h3>
            <p class="text-sm text-gray-500">Elige tu idioma preferido</p>
          </div>
          <div class="text-gray-400 group-hover:text-teal-600 transition-colors">
            →
          </div>
        </div>

        <!-- Experiencia -->
        <div 
          @click="abrirModal('experiencia')"
          class="flex items-center gap-4 p-4 rounded-xl transition-all duration-200 cursor-pointer hover:bg-gray-50 group"
        >
          <div class="text-2xl w-10 h-10 flex items-center justify-center bg-gray-100 rounded-full group-hover:bg-white transition-colors">
            🎨
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800">Experiencia</h3>
            <p class="text-sm text-gray-500">Apariencia, modo oscuro y accesibilidad</p>
          </div>
          <div class="text-gray-400 group-hover:text-teal-600 transition-colors">
            →
          </div>
        </div>

        <!-- Cuenta -->
        <div 
          @click="abrirModal('cuenta')"
          class="flex items-center gap-4 p-4 rounded-xl transition-all duration-200 cursor-pointer hover:bg-gray-50 group"
        >
          <div class="text-2xl w-10 h-10 flex items-center justify-center bg-gray-100 rounded-full group-hover:bg-white transition-colors">
            👤
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800">Cuenta</h3>
            <p class="text-sm text-gray-500">Gestiona tu cuenta, cierra sesión o elimina tu cuenta</p>
          </div>
          <div class="text-gray-400 group-hover:text-teal-600 transition-colors">
            →
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modales de configuración -->
  <component 
    :is="modalActual" 
    v-if="modalActual"
    @close="cerrarModal"
    @actualizar-config="actualizarConfiguracion"
  />
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Notificaciones from './configuraciones/Notificaciones.vue'
import Privacidad from './configuraciones/Privacidad.vue'
import Idioma from './configuraciones/Idioma.vue'
import Experiencia from './configuraciones/Experiencia.vue'
import Cuenta from './configuraciones/Cuenta.vue'

const router = useRouter()
const modalActual = ref(null)

const modales = {
  notificaciones: Notificaciones,
  privacidad: Privacidad,
  idioma: Idioma,
  experiencia: Experiencia,
  cuenta: Cuenta
}

function abrirModal(tipo) {
  modalActual.value = modales[tipo]
}

function cerrarModal() {
  modalActual.value = null
}

function actualizarConfiguracion(config) {
  console.log('Configuración actualizada:', config)
  // Aquí puedes emitir un evento o actualizar un store
}
</script>
<template>
  <!-- Fondo oscuro translúcido -->
  <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <!-- Contenedor del modal -->
    <div class="max-w-xl w-full mx-4 bg-white shadow-xl rounded-2xl overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-4">
        <button 
          @click="$emit('close')"
          class="text-gray-500 hover:text-gray-700 transition-colors text-xl"
        >
          ←
        </button>
        <h2 class="text-xl font-semibold text-gray-800">Cuenta</h2>
      </div>

      <div class="p-6 space-y-6">
        <!-- Información de usuario -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center">
              <span class="text-teal-600 text-xl">👤</span>
            </div>
            <div>
              <p class="font-semibold text-gray-800">usuario@ejemplo.com</p>
              <p class="text-sm text-gray-500">Miembro desde 2024</p>
            </div>
          </div>
        </div>

        <!-- Zona de gestión -->
        <div class="space-y-3">
          <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Gestión de cuenta</h3>
          
          <!-- Cambiar contraseña -->
          <button 
            @click="cambiarContrasena"
            class="w-full py-3 px-4 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition flex items-center justify-between"
          >
            <span>Cambiar contraseña</span>
            <span>→</span>
          </button>

          <!-- Cerrar sesión -->
          <button 
            @click="cerrarSesion" 
            :disabled="cerrandoSesion"
            class="w-full py-3 px-4 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition disabled:opacity-50 flex items-center justify-between"
          >
            <span>{{ cerrandoSesion ? 'Cerrando sesión...' : 'Cerrar sesión' }}</span>
            <span>→</span>
          </button>

          <!-- Eliminar cuenta - zona peligrosa -->
          <div class="pt-4">
            <button 
              @click="eliminarCuenta" 
              class="w-full py-3 px-4 bg-red-50 text-red-600 font-semibold rounded-xl hover:bg-red-100 transition"
            >
              Eliminar cuenta
            </button>
            <p class="text-xs text-gray-400 text-center mt-2">
              ⚠️ Esta acción es irreversible
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthToken } from '@/composables/useAuthToken'

const emit = defineEmits(['close'])
const router = useRouter()
const { clearToken, accessToken } = useAuthToken()
const cerrandoSesion = ref(false)

async function cerrarSesion() {
  if (cerrandoSesion.value) return
  
  cerrandoSesion.value = true
  
  try {
    const response = await fetch('http://localhost:8000/api/logout', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    const data = await response.json()

    if (data.success) {
      clearToken()
      router.push('/')
    } else {
      alert('Error al cerrar sesión: ' + data.message)
    }
  } catch (error) {
    console.error('Error:', error)
    clearToken()
    router.push('/')
  } finally {
    cerrandoSesion.value = false
  }
}

function cambiarContrasena() {
  alert('Funcionalidad en desarrollo')
}

function eliminarCuenta() {
  if (confirm("¿Estás seguro de que deseas eliminar tu cuenta? Esta acción no se puede deshacer.")) {
    console.log('Eliminando cuenta...')
    alert('Funcionalidad en desarrollo')
  }
}
</script>
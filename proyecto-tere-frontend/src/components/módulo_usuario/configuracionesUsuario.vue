<template>
  <!-- Fondo oscuro translúcido -->
  <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <!-- Contenedor del modal -->
    <div class="max-w-xl w-full mx-4 bg-white shadow-xl rounded-2xl p-6 space-y-6 relative">
      <!-- Botón cerrar -->
      <button
        @click="$router.back()"
        class="absolute top-3 right-3 text-gray-500 hover:text-gray-700"
      >
        ✕
      </button>

      <h2 class="text-2xl font-bold text-gray-800 text-center">Configuración de Usuario</h2>

      <!-- Preferencias -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-700">Notificaciones</span>
          <input 
            type="checkbox" 
            v-model="form.notificaciones" 
            class="form-checkbox text-teal-600 rounded focus:ring-0" 
          />
        </div>

        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-700">Modo oscuro</span>
          <input 
            type="checkbox" 
            v-model="form.modoOscuro" 
            class="form-checkbox text-teal-600 rounded focus:ring-0" 
          />
        </div>
      </div>

      <!-- Cerrar sesión -->
      <button 
        @click="cerrarSesion" 
        :disabled="cerrandoSesion"
        class="w-full py-2 px-4 bg-gray-200 text-gray-700 font-semibold rounded-xl shadow hover:bg-gray-300 transition disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ cerrandoSesion ? 'Cerrando sesión...' : 'Cerrar sesión' }}
      </button>

      <!-- Eliminar cuenta -->
      <button 
        @click="eliminarCuenta" 
        class="w-full py-2 px-4 bg-red-600 text-white font-semibold rounded-xl shadow hover:bg-red-700 transition"
      >
        Eliminar cuenta
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthToken } from '@/composables/useAuthToken'

const router = useRouter()
const { clearToken, accessToken } = useAuthToken()

const form = reactive({
  notificaciones: true,
  modoOscuro: false
})

const cerrandoSesion = ref(false)

async function cerrarSesion() {
  if (cerrandoSesion.value) return
  
  cerrandoSesion.value = true
  console.log('Cerrando sesión...')

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
      console.log('Sesión cerrada exitosamente')
      
      // Limpiar token del composable y localStorage
      clearToken()
      
      // Redirigir al login
      router.push('/')
    } else {
      console.error('Error al cerrar sesión:', data.message)
      alert('Error al cerrar sesión: ' + data.message)
    }
  } catch (error) {
    console.error('Error de red al cerrar sesión:', error)
    alert('Error de conexión al cerrar sesión')
    
    // Limpiar token localmente aunque falle el servidor
    clearToken()
    router.push('/')
  } finally {
    cerrandoSesion.value = false
  }
}

function eliminarCuenta() {
  if (confirm("¿Estás seguro de que deseas eliminar tu cuenta? Esta acción no se puede deshacer.")) {
    console.log('Eliminando cuenta...')
    // Lógica para eliminar cuenta en backend
  }
}
</script>


<template>
  <div class="min-h-screen bg-white p-6">
    <div class="max-w-6xl mx-auto">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl font-light text-gray-800">PERFIL PROFESIONAL</h1>
      </div>

      <div class="flex flex-col md:flex-row gap-8">
        <!-- Columna izquierda - Foto de perfil -->
        <div class="w-full md:w-1/3 flex flex-col items-center">
          <div class="relative mb-4">
            <div class="w-32 h-32 rounded-full bg-gray-100 border-2 border-gray-200 overflow-hidden">
              <img v-if="perfil.foto" :src="perfil.foto" alt="Foto del veterinario" class="w-full h-full object-cover">
              <div v-else class="w-full h-full flex items-center justify-center bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>
            <button @click="editarPerfil" class="absolute bottom-0 right-0 bg-white p-2 rounded-full shadow-sm border border-gray-200 hover:bg-gray-50">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
              </svg>
            </button>
          </div>
         
          <div class="mt-6 w-full">
            <h3 class="text-sm font-medium text-gray-500 mb-2">INFORMACIÓN ADICIONAL</h3>
            <div class="space-y-3">
              <div>
                <label class="block text-xs text-gray-500">Años de experiencia</label>
                <input v-model="perfil.experiencia" type="text" class="w-full border-b border-gray-200 py-1 text-sm focus:border-blue-500 focus:outline-none">
              </div>
            </div>
          </div>
        </div>

        <!-- Columna derecha - Datos del perfil -->
        <div class="w-full md:w-2/3">
          <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo</label>
                <input v-model="perfil.nombre" type="text" class="w-full border-b border-gray-200 py-2 focus:border-blue-500 focus:outline-none">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número de matrícula</label>
                <input v-model="perfil.matricula" type="text" class="w-full border-b border-gray-200 py-2 focus:border-blue-500 focus:outline-none">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
                <input v-model="perfil.especialidad" type="text" class="w-full border-b border-gray-200 py-2 focus:border-blue-500 focus:outline-none">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input v-model="perfil.telefono" type="text" class="w-full border-b border-gray-200 py-2 focus:border-blue-500 focus:outline-none">
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                <input v-model="perfil.email" type="email" class="w-full border-b border-gray-200 py-2 focus:border-blue-500 focus:outline-none">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Botón flotante de cuenta -->
    <div class="fixed bottom-12 right-36 z-50">
      <button 
        @click="toggleMenu"
        class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-3 shadow-lg transition-all duration-200 flex items-center gap-2"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
        </svg>
        <span class="text-sm font-medium">Cuenta</span>
      </button>

      <!-- Menú desplegable -->
      <div 
        v-if="isMenuOpen"
        class="absolute bottom-full right-0 mb-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden"
      >
          <button 
            @click="cerrarSesion"
            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V7.414a1 1 0 00-.293-.707L12.293 2.293A1 1 0 0011.586 2H3zm3 4a1 1 0 011-1h4a1 1 0 110 2H7a1 1 0 01-1-1zm0 4a1 1 0 011-1h8a1 1 0 110 2H7a1 1 0 01-1-1zm0 4a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1z" clip-rule="evenodd" />
            </svg>
            Cerrar sesión
          </button>
          
          <button 
            @click="handleDeleteAccount"
            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            Eliminar cuenta
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación para eliminar cuenta -->
    <div 
      v-if="showDeleteModal"
      class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
      @click="closeDeleteModal"
    >
      <div 
        class="bg-white rounded-lg max-w-md w-full p-6"
        @click.stop
      >
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Eliminar cuenta</h3>
        <p class="text-gray-600 mb-6">
          ¿Estás seguro de que deseas eliminar tu cuenta? Esta acción no se puede deshacer y perderás todos tus datos.
        </p>
        <div class="flex gap-3 justify-end">
          <button 
            @click="closeDeleteModal"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          >
            Cancelar
          </button>
          <button 
            @click="confirmDeleteAccount"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
          >
            Eliminar cuenta
          </button>
        </div>
      </div>
    </div>
</template>

<script setup>
import { reactive, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { accessToken, isAuthenticated, logout } = useAuth()

const perfil = reactive({
  id: null,
  foto: null,
  nombre: '',
  matricula: '',
  especialidad: '',
  telefono: '',
  email: '',
  experiencia: ''
})

// Estados del menú y modal
const isMenuOpen = ref(false)
const showDeleteModal = ref(false)

// Función para alternar el menú
function toggleMenu() {
  isMenuOpen.value = !isMenuOpen.value
}

// Función para cerrar el menú (opcional, puedes agregar un click fuera)
function closeMenu() {
  isMenuOpen.value = false
}

// Ejemplo con fetch
async function cerrarSesion() {
    try {
        const response = await fetch('/api/veterinario/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Authorization': `Bearer ${localStorage.getItem('token')}` // Si usas token
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Limpiar datos locales
            localStorage.removeItem('token');
            localStorage.removeItem('user_data');
            
            // Redirigir al login
            window.location.href = data.redirect_to || '/';
        } else {
            console.error('Error:', data.message);
        }
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
    }
}

// Función para abrir el modal de eliminar cuenta
function handleDeleteAccount() {
  isMenuOpen.value = false
  showDeleteModal.value = true
}

// Función para cerrar el modal
function closeDeleteModal() {
  showDeleteModal.value = false
}

// Función para confirmar eliminación de cuenta
async function confirmDeleteAccount() {
  try {
    const response = await axios.delete('/api/veterinario/cuenta', {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
        Accept: 'application/json'
      }
    })
    
    if (response.data.success) {
      await logout()
      router.push('/')
    }
  } catch (error) {
    console.error('Error al eliminar cuenta:', error)
    // Aquí puedes mostrar un mensaje de error al usuario
  } finally {
    closeDeleteModal()
  }
}

// Cerrar menú al hacer click fuera (opcional)
function handleClickOutside(event) {
  const menu = document.querySelector('.fixed.bottom-6.right-6')
  if (menu && !menu.contains(event.target)) {
    closeMenu()
  }
}

async function cargarPerfil() {
  try {
    if (!isAuthenticated.value) {
      router.push('/')
      return
    }

    const response = await axios.get('/api/veterinario/perfil', {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
        Accept: 'application/json'
      }
    })

    console.log('Respuesta del perfil:', response.data) 
    
    if (response.data.success) {
      const data = response.data.data
      console.log('Datos del perfil:', data)
      console.log('ID del veterinario:', data.id)

      perfil.id = data.id
      perfil.foto = data.foto
      perfil.nombre = data.nombre
      perfil.matricula = data.matricula
      perfil.especialidad = data.especialidad
      perfil.telefono = data.telefono || ''
      perfil.email = data.email
      perfil.experiencia = data.experiencia || ''
    }
  } catch (error) {
    console.error('Error al cargar perfil:', error)
    if (error.response?.status === 401) {
      logout()
      router.push('/')
    }
  }
}

function editarPerfil() {
  if (perfil.id) {
    router.push({
      name: 'EditarVeterinarioConId',
      params: { id: perfil.id.toString() }
    })
  } else {
    console.error('No se encontró el ID del veterinario')
  }
}

onMounted(() => {
  cargarPerfil()
  // Agregar event listener para cerrar menú al hacer click fuera
  document.addEventListener('click', handleClickOutside)
})

// Limpiar event listener
import { onUnmounted } from 'vue'
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
<!-- views/perfilUsuario -->
<template>
  <div class="w-full h-full flex flex-col relative bg-gray-50"> <!-- Añadido bg-gray-50 para el fondo -->
    <!-- Header -->
    <div class="sticky top-0 z-30 bg-white px-4 py-3 shadow">
      <div class="flex items-center justify-between text-2xl font-bold text-gray-800">
        <span>Perfil de Usuario</span>
        <div class="flex gap-4 text-xl text-gray-600">
          <router-link
            to="/usuarioConfiguracion"
            title="Configuración"
            class="hover:text-black transition"
          >
            <font-awesome-icon :icon="['fas', 'gear']" />
          </router-link>
          <router-link
            :to="editarRuta"
            title="Editar Datos"
            class="hover:text-black transition"
          >
            <font-awesome-icon :icon="['fas', 'user-pen']"/>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Contenido Scrollable -->
    <div class="flex-1 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
      <!-- Contenedor principal con márgenes -->
      <div class="max-w-4xl mx-auto w-full px-4 py-2">
        <!-- Sección de información del usuario -->
        <div class="flex items-center gap-6 p-4 bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
        <img src="https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg" 
            alt="usuario"
            class="w-28 h-28 rounded-full object-cover border-4 border-indigo-500" />
        <div>
          <p class="text-gray-900 font-semibold text-lg hover:underline cursor-pointer">Nombre de usuario</p>
          <p class="text-gray-500 text-sm mt-1 flex items-center gap-2">
            <i class="fa-solid fa-envelope text-indigo-500"></i>
            email@usuario.com
          </p>
        </div>
      </div>


       <!-- Tabs -->
      <div class="flex justify-center border-b text-sm font-medium mb-6">
        <div class="flex items-center gap-48 text-xl font-bold">
           <router-link 
              to="/explorar/perfil/mascotas" 
              class="w-36 px-3 py-2 text-center"
              :class="{
                'border-b-2 border-black text-black': activeTab === 'mascotas',
                'text-gray-400': activeTab !== 'mascotas'
              }"
            >
            Mascotas
          </router-link>
          <router-link 
            to="/explorar/perfil/adopciones" 
            class="w-36 px-3 py-2 text-center"
            :class="{
              'border-b-2 border-black text-black': $route.path.endsWith('/adopciones'),
              'text-gray-400': !$route.path.endsWith('/adopciones')
            }"
          >
            Adopciones
          </router-link>
        </div>
      </div>

        <!-- Contenido dinámico -->
        <div class="w-full min-h-[400px] bg-white rounded-lg shadow-sm p-4 mb-16">
          <router-view></router-view>
        </div>
      </div>
      
      <div class="h-15"></div>
    </div>
  </div>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router'
import { computed, ref, onMounted } from 'vue'
import { useAuthToken } from '@/composables/useAuthToken'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated } = useAuthToken()

const usuario = ref({
  id: null,
  nombre: 'Cargando...',
  email: 'Cargando...',
  foto: null
})

const activeTab = computed(() => {
  return route.meta.activeTab || 'mascotas'
})

// Computed para la ruta de edición
const editarRuta = computed(() => {
  if (usuario.value.id) {
    return {
      path: '/usuarioEdicion',
      query: { id: usuario.value.id }
    }
  }
  return '/usuarioEdicion';
});

// Cargar datos del usuario
const cargarUsuario = async () => {
  try {
    // Primero obtenemos el usuario autenticado
    const response = await axios.get('/api/user', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    const userData = response.data
    console.log('Datos del usuario autenticado:', userData)

    if (userData && userData.userable) {
      // Si el usuario tiene relación userable (polimórfica)
      usuario.value.id = userData.userable.id
      usuario.value.nombre = userData.userable.nombre
      usuario.value.email = userData.email
      
      // Cargar foto de perfil si existe
      if (userData.userable.fotos && userData.userable.fotos.length > 0) {
        usuario.value.foto = `/storage/${userData.userable.fotos[0].ruta_foto}`
      }
    }
  } catch (error) {
    console.error('Error al cargar usuario:', error)
    // En caso de error, mostrar valores por defecto
    usuario.value.nombre = 'Usuario'
    usuario.value.email = 'usuario@ejemplo.com'
  }
}

// En perfilUsuario.vue - MEJORAR ESTE CÓDIGO
onMounted(() => {
  console.log('🔐 PERFIL - Estado de autenticación:', {
    isAuthenticated: isAuthenticated.value,
    accessToken: accessToken.value ? accessToken.value.substring(0, 10) + '...' : 'NO',
    localStorage: {
      auth_token: localStorage.getItem('auth_token'),
      token: localStorage.getItem('token')
    }
  })

  // VERIFICACIÓN MÁS ROBUSTA
  const tokenFromStorage = localStorage.getItem('auth_token')
  if (tokenFromStorage && !accessToken.value) {
    console.log('🔄 Token encontrado en storage pero no en composable, recargando...')
    // Forzar recarga del token
    window.location.reload()
    return
  }

  if (isAuthenticated.value && accessToken.value) {
    cargarUsuario()
  } else {
    console.log('❌ PERFIL - Usuario no autenticado')
    // NO redirigir automáticamente, solo mostrar mensaje
  }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>


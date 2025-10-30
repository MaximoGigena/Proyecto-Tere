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
import { useAuth } from '@/composables/useAuth' // ✅ Usar useAuth en lugar de useAuthToken
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const { 
  user, 
  accessToken, 
  isAuthenticated, 
  checkAuth, 
  isUsuario,
  isVeterinario,
  isAdministrador,
  fetchUser 
} = useAuth() // ✅ Nuevo composable

const usuario = ref({
  id: null,
  nombre: 'Cargando...',
  email: 'Cargando...',
  foto: null
})

const cargando = ref(true)
const error = ref('')

const activeTab = computed(() => {
  return route.meta.activeTab || 'mascotas'
})

// Computed para mostrar el tipo de usuario
const tipoUsuario = computed(() => {
  if (isAdministrador()) return 'Administrador'
  if (isVeterinario()) return 'Veterinario'
  if (isUsuario()) return 'Usuario'
  return 'Invitado'
})

// Computed para la ruta de edición
const editarRuta = computed(() => {
  if (usuario.value.id) {
    return {
      path: '/usuarioEdicion',
      query: { id: usuario.value.id }
    }
  }
  return '/usuarioEdicion'
})

// ✅ Configurar axios con interceptor
const axiosAuth = axios.create({
  baseURL: 'http://localhost:8000'
})

// Interceptor para agregar el token automáticamente
axiosAuth.interceptors.request.use(
  (config) => {
    if (accessToken.value) {
      config.headers.Authorization = `Bearer ${accessToken.value}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// ✅ Función mejorada para cargar datos del usuario
const cargarUsuario = async () => {
  try {
    cargando.value = true
    error.value = ''

    console.log('🔐 PERFIL - Verificando autenticación...')
    
    // Verificar autenticación con el servidor
    const autenticado = await checkAuth()
    
    if (!autenticado) {
      error.value = 'Debes iniciar sesión para ver tu perfil'
      cargando.value = false
      return
    }

    console.log('✅ PERFIL - Usuario autenticado:', user.value)

    // Usar los datos del composable useAuth
    if (user.value && user.value.userable) {
      const userData = user.value
      
      usuario.value.id = userData.userable.id
      usuario.value.nombre = userData.userable.nombre || userData.userable.nombre_completo || 'Usuario'
      usuario.value.email = userData.email
      
      // Cargar foto de perfil si existe
      if (userData.userable.foto) {
        usuario.value.foto = userData.userable.foto.startsWith('http') 
          ? userData.userable.foto 
          : `/storage/${userData.userable.foto}`
      } else if (userData.userable.fotos && userData.userable.fotos.length > 0) {
        // Para compatibilidad con el formato antiguo
        const primeraFoto = userData.userable.fotos[0]
        usuario.value.foto = primeraFoto.ruta_foto
          ? `/storage/${primeraFoto.ruta_foto}`
          : primeraFoto
      }

      console.log('📝 PERFIL - Datos cargados:', usuario.value)
    } else {
      throw new Error('No se encontraron datos del usuario')
    }

  } catch (err) {
    console.error('❌ Error al cargar usuario:', err)
    
    if (err.response?.status === 401) {
      error.value = 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.'
    } else if (err.response?.status === 404) {
      error.value = 'No se encontró la información del usuario.'
    } else {
      error.value = err.response?.data?.message || err.message || 'Error al cargar el perfil'
    }
    
    // En caso de error, mostrar valores por defecto
    usuario.value.nombre = 'Usuario'
    usuario.value.email = 'usuario@ejemplo.com'
  } finally {
    cargando.value = false
  }
}

// ✅ Mejor manejo del mounted
onMounted(async () => {
  console.log('🔐 PERFIL - Estado de autenticación:', {
    isAuthenticated: isAuthenticated.value,
    accessToken: accessToken.value ? accessToken.value.substring(0, 10) + '...' : 'NO',
    user: user.value,
    localStorage: {
      auth_token: localStorage.getItem('auth_token'),
      token: localStorage.getItem('token')
    }
  })

  await cargarUsuario()
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


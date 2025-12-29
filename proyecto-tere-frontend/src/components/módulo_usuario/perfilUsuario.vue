<!-- views/perfilUsuario -->
<template>
  <div class="w-full h-full flex flex-col relative bg-gray-50">
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

        <!-- Mostrar error si existe -->
        <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-red-600">{{ error }}</p>
        </div>

        <!-- Cargando -->
        <div v-if="cargando" class="flex items-center gap-6 p-4 bg-white rounded-2xl shadow-lg">
          <div class="w-28 h-28 rounded-full bg-gray-200 animate-pulse"></div>
          <div class="space-y-3">
            <div class="h-6 w-48 bg-gray-200 rounded animate-pulse"></div>
            <div class="h-4 w-64 bg-gray-200 rounded animate-pulse"></div>
          </div>
        </div>

        <!-- Sección de información del usuario (CARGADA DESDE BASE DE DATOS) -->
        <div v-else class="flex items-center gap-6 p-4 bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
          <!-- Foto de perfil -->
          <div class="relative">
            <img 
              :src="usuario.foto || 'https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg'" 
              :alt="usuario.nombre"
              class="w-28 h-28 rounded-full object-cover border-4 border-indigo-500"
              @error="usuario.foto = 'https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg'"
            />
            <!-- Badge del tipo de usuario -->
            <span class="absolute -bottom-2 -right-2 px-3 py-1 bg-indigo-500 text-white text-xs font-bold rounded-full">
              {{ tipoUsuario }}
            </span>
          </div>
          
          <!-- Información del usuario -->
          <div>
            <!-- Nombre del usuario -->
            <p class="text-gray-900 font-bold text-2xl hover:underline cursor-pointer mb-1">
              {{ usuario.nombre }}
            </p>
            
            <!-- Email -->
            <p class="text-gray-600 text-base mt-2 flex items-center gap-3">
              <i class="fa-solid fa-envelope text-indigo-500 text-lg"></i>
              {{ usuario.email }}
            </p>
            
            <!-- ID del usuario (opcional, para depuración) -->
            <p v-if="usuario.id" class="text-gray-400 text-xs mt-3 flex items-center gap-2">
              <i class="fa-solid fa-id-card"></i>
              ID: {{ usuario.id }}
            </p>
          </div>
        </div>

        <!-- Tabs -->
        <div class="flex justify-center border-b text-sm font-medium mb-6 mt-8">
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
import { useAuth } from '@/composables/useAuth'
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
} = useAuth()

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

// Configurar axios con interceptor
const axiosAuth = axios.create({
  baseURL: 'http://localhost:8000/api'  // ← AGREGAR /api AQUÍ
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

// Función para cargar datos del usuario
const cargarUsuario = async () => {
  try {
    cargando.value = true
    error.value = ''

    console.log('🔐 PERFIL - Verificando autenticación...')
    
    // Verificar autenticación con el servidor
    const autenticado = await checkAuth()
    
    console.log('✅ checkAuth result:', autenticado)
    
    if (!autenticado) {
      error.value = 'Debes iniciar sesión para ver tu perfil'
      cargando.value = false
      return
    }

    console.log('✅ PERFIL - Usuario autenticado:', {
      user: user.value,
      userable_id: user.value?.userable_id,
      userable_type: user.value?.userable_type
    })

    // Obtener el ID correcto del usuario
    const userId = user.value?.userable_id || user.value?.id
    console.log('🔍 ID del usuario a buscar:', userId)
    
    if (!userId) {
      throw new Error('No se pudo determinar el ID del usuario')
    }
    
    console.log('📡 Haciendo petición a API...')
    console.log('📡 URL:', `/usuarios/${userId}`)
    
    // Hacer la petición a la API
    const response = await axiosAuth.get(`/usuarios/${userId}`)
    
    console.log('📡 Respuesta completa de API:', response)
    console.log('📡 Status:', response.status)
    console.log('📡 Data:', response.data)
    
    if (response.data.success && response.data.usuario) {
      const apiUsuario = response.data.usuario
      console.log('✅ Datos del usuario desde API:', apiUsuario)
      
      // Asignar datos básicos
      usuario.value.id = apiUsuario.id
      usuario.value.nombre = apiUsuario.nombre || 'Usuario'
      usuario.value.email = apiUsuario.email || user.value?.email || 'usuario@ejemplo.com'
      
      console.log('📝 Datos asignados:', {
        id: usuario.value.id,
        nombre: usuario.value.nombre,
        email: usuario.value.email
      })
      
      // Manejar foto de perfil
      if (apiUsuario.foto_principal) {
        // Si hay foto_principal directa
        let fotoUrl = apiUsuario.foto_principal
        
        // Si la URL empieza con /storage/, convertir a URL completa
        if (fotoUrl && fotoUrl.startsWith('/storage/')) {
          // Construir URL completa: http://localhost:8000 + /storage/...
          fotoUrl = `http://localhost:8000${fotoUrl}`
        }
        
        usuario.value.foto = fotoUrl
        console.log('📸 Foto principal procesada:', {
          original: apiUsuario.foto_principal,
          final: usuario.value.foto
        })
      } else if (apiUsuario.fotos && apiUsuario.fotos.length > 0) {
        // Buscar foto principal o usar la primera
        const fotoPrincipal = apiUsuario.fotos.find(f => f.es_principal) || apiUsuario.fotos[0]
        console.log('📸 Foto encontrada en array:', fotoPrincipal)
        
        let fotoUrl = null
        
        if (fotoPrincipal.url_foto) {
          fotoUrl = fotoPrincipal.url_foto
        } else if (fotoPrincipal.ruta_foto) {
          // Construir URL completa
          fotoUrl = `http://localhost:8000${fotoPrincipal.ruta_foto}`
        }
        
        // Asegurarse de que la URL sea completa
        if (fotoUrl && fotoUrl.startsWith('/storage/')) {
          fotoUrl = `http://localhost:8000${fotoUrl}`
        }
        
        usuario.value.foto = fotoUrl
        console.log('📸 URL final de foto desde array:', usuario.value.foto)
      } else {
        console.log('📸 No se encontró foto en la respuesta')
        usuario.value.foto = null
      }
    } else {
      console.error('❌ API no devolvió datos válidos:', response.data)
      throw new Error(response.data.message || 'No se pudieron obtener datos del usuario')
    }

  } catch (err) {
    console.error('❌ Error al cargar usuario:', err)
    console.error('❌ Detalles del error:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status
    })
    
    if (err.response?.status === 401) {
      error.value = 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.'
    } else if (err.response?.status === 404) {
      error.value = 'Usuario no encontrado en el sistema.'
    } else {
      error.value = err.response?.data?.message || err.message || 'Error al cargar el perfil'
    }
    
    // En caso de error, mostrar valores por defecto
    usuario.value.nombre = 'Usuario'
    usuario.value.email = 'usuario@ejemplo.com'
    usuario.value.foto = 'https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg'
  } finally {
    cargando.value = false
    console.log('🏁 Carga completada. Datos finales:', usuario.value)
  }
}

// Si necesitas forzar una recarga de datos desde el servidor, puedes usar esta función
const recargarDatosUsuario = async () => {
  try {
    // Forzar la obtención de datos frescos desde el servidor
    await fetchUser()
    await cargarUsuario()
  } catch (err) {
    console.error('Error al recargar datos:', err)
  }
}

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

/* Estilos adicionales para mejorar la visualización */
img {
  object-fit: cover;
}

.router-link-active {
  font-weight: bold;
}
</style>

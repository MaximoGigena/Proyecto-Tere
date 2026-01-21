<!-- views/perfilUsuario.vue -->
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

          <!-- ============= COMPONENTE ATOMIZADO ============= -->
          <TiempoRegistro 
            :fecha-registro="usuario.created_at"
            :tiempo-texto="usuario.tiempo_registro"
            :dias-registrado="usuario.dias_registrado"
            :show-days="true"
            custom-class="ml-auto"
          />
          <!-- ================================================ -->
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

// ============= IMPORTAR EL COMPONENTE =============
import TiempoRegistro from '@/components/ElementosGraficos/EdadUsuario.vue'
// ==================================================

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

// ============= SIMPLIFICAMOS EL USUARIO =============
const usuario = ref({
  id: null,
  nombre: 'Cargando...',
  email: 'Cargando...',
  foto: null,
  created_at: null,           // La fecha de creación del backend
  tiempo_registro: null,      // El texto ya formateado del backend
  dias_registrado: null       // Los días exactos del backend (opcional)
})

const cargando = ref(true)
const error = ref('')

const activeTab = computed(() => {
  return route.meta.activeTab || 'mascotas'
})

// ============= ELIMINAMOS EL COMPUTED DE TIEMPO_REGISTRADO =============
// ¡Ya no es necesario porque el componente se encarga!
// =======================================================================

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
  baseURL: 'http://localhost:8000/api'
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
    console.log('🔐 User completo:', JSON.stringify(user.value, null, 2))
    
    const autenticado = await checkAuth()
    
    console.log('✅ checkAuth result:', autenticado)
    
    if (!autenticado) {
      error.value = 'Debes iniciar sesión para ver tu perfil'
      cargando.value = false
      return
    }

    // DEPURACIÓN DETALLADA
    console.log('🔍 DEPURACIÓN DE USER OBJECT:', {
      user: user.value,
      userable_id: user.value?.userable_id,
      id: user.value?.id,
      userable_type: user.value?.userable_type,
      isUsuario: isUsuario()
    })

    const userId = user.value?.userable_id || user.value?.id
    console.log('🔍 ID del usuario a buscar:', userId)
    
    if (!userId) {
      throw new Error('No se pudo determinar el ID del usuario')
    }
    
    console.log('📡 Haciendo petición a API...')
    
    const response = await axiosAuth.get(`/usuarios/${userId}`)
    
    if (response.data.success && response.data.usuario) {
      const apiUsuario = response.data.usuario
      console.log('✅ Datos del usuario desde API:', apiUsuario)
      
      // Asignar datos básicos
      usuario.value.id = apiUsuario.id
      usuario.value.nombre = apiUsuario.nombre || 'Usuario'
      usuario.value.email = apiUsuario.email || user.value?.email || 'usuario@ejemplo.com'
      
      // DATOS PARA EL COMPONENTE DE TIEMPO
      usuario.value.created_at = apiUsuario.created_at
      usuario.value.tiempo_registro = apiUsuario.tiempo_registro || null
      usuario.value.dias_registrado = apiUsuario.dias_registrado || 0
      
      // FOTO DE PERFIL - usar foto_principal directamente
      if (apiUsuario.foto_principal) {
        usuario.value.foto = apiUsuario.foto_principal
        console.log('📸 Foto principal asignada:', usuario.value.foto)
      } else {
        // Intentar con la primera foto si hay alguna
        if (apiUsuario.fotos && apiUsuario.fotos.length > 0) {
          const primeraFoto = apiUsuario.fotos.find(f => f.url) || apiUsuario.fotos[0]
          usuario.value.foto = primeraFoto.url || 
                              (primeraFoto.ruta_foto ? `http://localhost:8000/storage/${primeraFoto.ruta_foto}` : null)
        } else {
          usuario.value.foto = 'https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg'
        }
      }
      
      console.log('📅 Datos de tiempo recibidos:', {
        created_at: usuario.value.created_at,
        tiempo_registro: usuario.value.tiempo_registro,
        dias_registrado: usuario.value.dias_registrado
      })
      
    } else {
      throw new Error(response.data.message || 'No se pudieron obtener datos del usuario')
    }

  } catch (err) {
    console.error('❌ Error al cargar usuario:', err)
    
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
    usuario.value.tiempo_registro = 'Recién registrado'
  } finally {
    cargando.value = false
    console.log('🏁 Carga completada. Datos finales:', usuario.value)
  }
}

// Si necesitas forzar una recarga de datos desde el servidor, puedes usar esta función
const recargarDatosUsuario = async () => {
  try {
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

img {
  object-fit: cover;
}

.router-link-active {
  font-weight: bold;
}
</style>
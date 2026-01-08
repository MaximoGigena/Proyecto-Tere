<template>    
 <div class="bg-white backdrop-blur-md border border-gray-200 rounded-2xl 
           overflow-y-auto max-h-[83vh] w-full shadow-2xl 
           transition-all duration-300 relative p-2"
  >
  <div
    ref="scrollContainer"
    class="flex-1 overflow-y-auto overflow-x-overlay [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden px-2"
  >
  
    <!-- Cargando estado -->
    <div v-if="loading" class="flex justify-center items-center min-h-[50vh]">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
        <p class="mt-4 text-gray-600">Cargando perfil...</p>
      </div>
    </div>

    <!-- Error estado -->
    <div v-else-if="error" class="flex justify-center items-center min-h-[50vh]">
      <div class="text-center text-red-600">
        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-4xl mb-4" />
        <p class="text-lg">{{ error }}</p>
        <button @click="fetchPerfil" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
          Reintentar
        </button>
      </div>
    </div>

    <!-- Contenido del perfil -->
    <div v-else>
      <!-- Imagen principal del perfil -->
      <div class="relative w-full min-h-[75vh] rounded-4xl overflow-hidden">
        <img 
            :src="perfil.foto_principal || perfil.defaultImage" 
            :alt="perfil.nombre" 
            class="w-full h-130 object-cover rounded-4xl" 
            @error="handleImageError"
        />
      
        <!-- Info del usuario -->
        <div class="absolute top-5 left-4 bg-white px-3 py-1 rounded-md shadow text-sm font-semibold w-fit">
          Nombre: {{ perfil.nombre }}
        </div>

        <div class="absolute top-13 left-4 bg-blue-500 text-white text-xs px-2 py-1 rounded-md w-fit">
          Edad: {{ perfil.edad ? perfil.edad + ' años' : 'No especificada' }}
        </div>
          
        <button  
          v-if="$route.query.from === 'chats-list' || $route.query.from === 'chat-room'"
          @click="router.back()"
          class="absolute right-3 top-3 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
        >
          <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
        </button>

        <button
          @click="mostrarReporte = true"
          class="absolute top-15 right-3 z-30 text-red-700 bg-white bg-opacity-80 rounded-full px-3 pt-0 py-1 text-4xl hover:bg-red-100 hover:text-red-800 hover:shadow-lg hover:scale-110 transition w-10 h-10 font-bold"
        >
          !
        </button>

        <ReportarUsuario 
          v-if="mostrarReporte" 
          :usuario-id="perfil.id" 
          @close="mostrarReporte = false" 
        />
      </div>

      <!-- Descripción -->
      <div class="px-4 pt-4 pb-6 bg-white space-y-4">
        <div class="space-y-2">
          <h2 class="text-4xl font-bold text-gray-800">Sobre mí</h2>
          <p class="text-lg font-semibold text-gray-800">
            {{ perfil.descripcion || 'Este usuario no ha agregado una descripción todavía.' }}
          </p>
        </div>
      </div>

      <!-- Etiquetas del usuario -->
      <div class="flex flex-wrap gap-3">
        <!-- Experiencia -->
        <div v-if="perfil.experiencia" class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Experiencia">
          <div class="mr-2">
            <font-awesome-icon :icon="['fas', 'star']" class="text-yellow-500 text-sm"/>
          </div>
          <span class="text-gray-700">{{ perfil.experiencia }}</span>
        </div>
        
        <!-- Tipo de vivienda -->
        <div v-if="perfil.tipoVivienda" class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Tipo de vivienda">
          <font-awesome-icon :icon="['fas', 'home']" class="text-gray-500 mr-2" />
          <span class="text-gray-700">{{ perfil.tipoVivienda }}</span>
        </div>
        
        <!-- Ocupación -->
        <div v-if="perfil.ocupacion" class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Ocupación">
          <font-awesome-icon :icon="['fas', 'briefcase']" class="text-gray-500 mr-2" />
          <span class="text-gray-700">{{ perfil.ocupacion }}</span>
        </div>
        
        <!-- Convivencia con niños -->
        <div v-if="perfil.convivenciaNiños" class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Convivencia con niños">
          <font-awesome-icon :icon="['fas', 'child']" class="text-gray-500 mr-2" />
          <span class="text-gray-700">{{ perfil.convivenciaNiños === 'si' ? 'Con niños' : 'Sin niños' }}</span>
        </div>
        
        <!-- Convivencia con mascotas -->
        <div v-if="perfil.convivenciaMascotas" class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Convivencia con mascotas">
          <font-awesome-icon :icon="['fas', 'paw']" class="text-gray-500 mr-2"/>
          <span class="text-gray-700">{{ perfil.convivenciaMascotas === 'si' ? 'Con mascotas' : 'Sin mascotas' }}</span>
        </div>
        
        <!-- Ubicación -->
        <div v-if="perfil.ubicacion" class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Ubicación">
          <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="text-gray-500 mr-2"/>
          <span class="text-gray-700">{{ perfil.ubicacion }}</span>
        </div>
      </div>

      <!-- Galería de fotos -->
      <div v-if="perfil.fotos && perfil.fotos.length > 0" class="mt-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 px-4">Galería de fotos</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 px-4">
          <div 
            v-for="(foto, index) in perfil.fotos" 
            :key="index"
            class="relative overflow-hidden rounded-xl h-48 cursor-pointer hover:opacity-90 transition"
            @click="verFoto(foto)"
          >
            <!-- En la galería de fotos -->
              <img 
                  :src="foto.url || foto" 
                  :alt="'Foto ' + (index + 1) + ' de ' + perfil.nombre"
                  class="w-full h-full object-cover"
                  @error="handleImageError"
              />
            <div v-if="foto.es_principal" class="absolute top-2 left-2 bg-yellow-500 text-white text-xs px-2 py-1 rounded">
              Principal
            </div>
          </div>
        </div>
      </div>

      <!-- Información de contacto (solo para el propio usuario) -->
      <div v-if="esMiPerfil && perfil.contacto" class="mt-6 px-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Contacto de emergencia</h2>
        <div class="bg-gray-50 rounded-xl p-4 space-y-3">
          <div class="flex items-center">
            <font-awesome-icon :icon="['fas', 'user']" class="text-gray-500 mr-3" />
            <span class="text-gray-700">{{ perfil.contacto.nombre_completo || perfil.nombre }}</span>
          </div>
          <div class="flex items-center">
            <font-awesome-icon :icon="['fas', 'phone']" class="text-gray-500 mr-3" />
            <span class="text-gray-700">{{ perfil.contacto.telefono || 'No especificado' }}</span>
          </div>
          <div class="flex items-center">
            <font-awesome-icon :icon="['fas', 'envelope']" class="text-gray-500 mr-3" />
            <span class="text-gray-700">{{ perfil.contacto.email || 'No especificado' }}</span>
          </div>
          <div v-if="perfil.contacto.dni" class="flex items-center">
            <font-awesome-icon :icon="['fas', 'id-card']" class="text-gray-500 mr-3" />
            <span class="text-gray-700">DNI: {{ perfil.contacto.dni }}</span>
          </div>
        </div>
      </div>

      <!-- Ubicación -->
      <div class="px-4 pt-4 pb-6 bg-white space-y-4">
        <div class="space-y-2">
          <h2 class="text-4xl font-bold text-gray-800">Ubicación</h2>
          <p class="text-lg font-semibold text-gray-800">
            {{ perfil.ubicacion || 'Ubicación no especificada' }}
          </p>
        </div>
      </div>
      
      <div class="h-1"></div>
    </div>
  </div>
 </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import ReportarUsuario from '@/components/módulo_usuario/reportarUsuario.vue'

const { accessToken, isAuthenticated, checkAuth, user } = useAuth()
const route = useRoute()
const router = useRouter()
const scrollContainer = ref(null)
const mostrarReporte = ref(false)
const loading = ref(false)
const error = ref(null)
const perfil = ref({
  id: null,
  nombre: '',
  edad: null,
  descripcion: '',
  experiencia: '',
  tipoVivienda: '',
  ocupacion: '',
  convivenciaNiños: '',
  convivenciaMascotas: '',
  ubicacion: '',
  foto_principal: '',
  fotos: [],
  contacto: null,
  defaultImage: 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png'
})

// Verificar si es el perfil del usuario autenticado
const esMiPerfil = computed(() => {
  // Verificar si el user tiene la estructura con userable
  if (user && user.value && user.value.userable) {
    return user.value.userable.id === perfil.value.id
  }
  // O si el user tiene el id directamente
  if (user && user.value && user.value.id) {
    return user.value.id === perfil.value.id
  }
  return false
})

// Fetch del perfil
const fetchPerfil = async () => {
  const userId = route.params.userId
  
  if (!userId) {
    error.value = 'ID de usuario no especificado'
    return
  }

  loading.value = true
  error.value = null

  try {
    // Usa accessToken del composable useAuth
    const token = accessToken.value
    
    if (!token) {
      // Si no hay token en el composable, intenta obtenerlo del localStorage
      const fallbackToken = localStorage.getItem('access_token')
      if (!fallbackToken) {
        throw new Error('No estás autenticado. Por favor, inicia sesión.')
      }
    }

    console.log('Solicitando perfil para usuario ID:', userId)
    console.log('Token disponible:', token ? 'Sí' : 'No')

    const response = await fetch(`http://localhost:8000/api/usuarios/${userId}`, {
      headers: {
        'Authorization': `Bearer ${token || localStorage.getItem('access_token')}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    console.log('Respuesta del servidor:', response.status, response.statusText)

    if (response.status === 401) {
      // Token expirado o inválido
      error.value = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.'
      // Opcional: redirigir al login
      // router.push('/login')
      return
    }

    if (!response.ok) {
      const errorText = await response.text()
      console.error('Error response:', errorText)
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()
    console.log('Datos recibidos del API:', data)
    
    if (data.success && data.usuario) {
      // Mapear los datos del backend a nuestra estructura
      perfil.value = {
        id: data.usuario.id,
        nombre: data.usuario.nombre,
        edad: data.usuario.edad,
        descripcion: data.usuario.caracteristicas?.descripción || '',
        experiencia: data.usuario.caracteristicas?.experiencia || '',
        tipoVivienda: data.usuario.caracteristicas?.tipoVivienda || '',
        ocupacion: data.usuario.caracteristicas?.ocupacion || '',
        convivenciaNiños: data.usuario.caracteristicas?.convivenciaNiños || '',
        convivenciaMascotas: data.usuario.caracteristicas?.convivenciaMascotas || '',
        ubicacion: data.usuario.ubicacion || 'Ubicación no especificada',
        foto_principal: data.usuario.foto_principal || perfil.value.defaultImage,
        fotos: data.usuario.fotos || [],
        contacto: data.usuario.contacto || null
      }
      
      console.log('Perfil mapeado exitosamente:', perfil.value)
    } else {
      throw new Error(data.message || 'Error al cargar el perfil')
    }
  } catch (err) {
    console.error('Error fetching perfil:', err)
    error.value = `No se pudo cargar el perfil: ${err.message}`
    
    // Solo mostrar datos de ejemplo en desarrollo si no es error de autenticación
    if (process.env.NODE_ENV === 'development' && !err.message.includes('autenticado') && !err.message.includes('sesión')) {
      console.log('Usando datos de ejemplo para desarrollo')
      perfil.value = {
        id: userId,
        nombre: `Usuario ${userId}`,
        edad: '30',
        descripcion: 'Amante de los animales con experiencia en cuidado de mascotas.',
        experiencia: 'Experto',
        tipoVivienda: 'Casa con patio',
        ocupacion: 'Ingeniero',
        convivenciaNiños: 'si',
        convivenciaMascotas: 'si',
        ubicacion: 'Buenos Aires, Argentina',
        foto_principal: 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png',
        fotos: [
          {
            url_foto: 'https://cdn.pixabay.com/photo/2020/07/16/07/36/man-5410019_960_720.jpg',
            es_principal: true
          }
        ],
        contacto: {
          nombre_completo: 'Usuario Ejemplo',
          telefono: '+5491122334455',
          email: 'ejemplo@mail.com',
          dni: '12345678'
        }
      }
      error.value = null
    }
  } finally {
    loading.value = false
  }
}

// Ver foto en grande
const verFoto = (foto) => {
  console.log('Ver foto:', foto)
  // Puedes implementar un modal o lightbox aquí
}

// Manejar errores de imagen
const handleImageError = (event) => {
    console.log('Error cargando imagen:', event.target.src)
    console.log('Usando default image:', perfil.value.defaultImage)
    event.target.src = perfil.value.defaultImage
}

// Cargar perfil al montar el componente
onMounted(() => {
  document.body.style.overflow = 'hidden'
  fetchPerfil()
})

// Recargar cuando cambia el userId en la ruta
watch(() => route.params.userId, (newId) => {
  if (newId) {
    fetchPerfil()
  }
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<style scoped>
/* Estilos de scroll */
body {
  overflow-y: scroll;
  scrollbar-width: thin;
  scrollbar-color: transparent transparent;
}

::-webkit-scrollbar {
  display: none;
}

body:hover::-webkit-scrollbar {
  display: block;
}

::-webkit-scrollbar-thumb {
  background-color: transparent;
}

::-webkit-scrollbar-track {
  background: transparent;
}

.scroll-container::-webkit-scrollbar {
  width: 0px;
  background: transparent;
}
</style>
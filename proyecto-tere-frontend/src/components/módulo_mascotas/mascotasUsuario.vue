<!-- mascotasUsuario.vue -->
<template>
  <div class="space-y-6">
    <!-- Lista de mascotas -->
    <MascotaCard
      v-for="mascota in mascotas"
      :key="mascota.id"
      :mascota="mascota"
      @click="abrirDetalleMascota(mascota.id)"
      @editar="editarMascota"
      @eliminar="eliminarMascota"
    />

    <!-- Estados de carga -->
    <div v-if="loading" class="text-center py-8 flex flex-col items-center">
      <div class="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-lg font-medium">Cargando mascotas...</p>
    </div>

    <div v-else-if="mascotas.length === 0" class="text-center py-8">
      <p>No tienes mascotas registradas</p>
    </div>

    <!-- Botón para agregar mascota -->
    <button
      @click="abrirRegistroMascota"
      class="fixed bottom-14 left-1/2 transform -translate-x-1/2 text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full px-6 py-3 text-base md:text-lg font-bold shadow-lg hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl transition-all duration-200 hover:scale-105 z-50 whitespace-nowrap"
    >
      + Agregar Mascota
    </button>

    <!-- Modal Overlay para el detalle de la mascota -->
    <ModalOverlay 
      :visible="showMascotaModal" 
      @close="cerrarModalMascota"
    >
      <contenidoMascota 
        :mascotaId="selectedMascotaId"
        :esTarjetaActiva="true" 
        @close="cerrarModalMascota"
      />
    </ModalOverlay>

    <div class="relative">
      <router-view />
      <router-view name="overlay" />
    </div>
  </div>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router'
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import MascotaCard from '@/components/módulo_mascotas/tarjetaMascota.vue'
import { useAuth } from '@/composables/useAuth'
import ModalOverlay from '@/components/módulo_adopciones/ModalOverlay.vue'
import contenidoMascota from '@/components/módulo_mascotas/contenidoMascota.vue'

const router = useRouter()
const route = useRoute()

const { 
  user, 
  accessToken, 
  isAuthenticated, 
  checkAuth,
  logout 
} = useAuth()

const mascotas = ref([])
const loading = ref(true)
const error = ref('')

// Estado para el modal
const showMascotaModal = ref(false)
const selectedMascotaId = ref(null)

const abrirDetalleMascota = (id) => {
  if (!isAuthenticated.value) {
    error.value = 'Debes iniciar sesión para ver los detalles'
    return
  }
  
  selectedMascotaId.value = id
  showMascotaModal.value = true
}

const cerrarModalMascota = () => {
  showMascotaModal.value = false
  selectedMascotaId.value = null
  
  // Limpiar cualquier query param relacionado
  if (router.currentRoute.value.query.modal === 'true') {
    router.replace({ query: {} })
  }
}

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

// Interceptor para manejar errores de autenticación
axiosAuth.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      console.warn('Token inválido o expirado, cerrando sesión...')
      logout()
    }
    return Promise.reject(error)
  }
)

// Logs para ver el estado del token
console.log('[MascotasUsuario] Estado inicial del token:', {
  accessToken: accessToken.value ? `${accessToken.value.substring(0, 10)}...` : 'null',
  isAuthenticated: isAuthenticated.value,
  user: user.value
})

// Watcher para monitorear cambios en el token
watch(accessToken, (newToken, oldToken) => {
  console.log('[MascotasUsuario] Token cambiado:', {
    oldToken: oldToken ? `${oldToken.substring(0, 10)}...` : 'null',
    newToken: newToken ? `${newToken.substring(0, 10)}...` : 'null'
  })
})

watch(isAuthenticated, (newAuthStatus) => {
  console.log('[MascotasUsuario] Estado autenticación cambiado:', newAuthStatus)
})

// ✅ Función mejorada para cargar mascotas
const cargarMascotas = async () => {
  try {
    loading.value = true
    error.value = ''
    
    console.log('[MascotasUsuario] Verificando autenticación...')
    
    const autenticado = await checkAuth()
    
    if (!autenticado) {
      error.value = 'Debes iniciar sesión para ver tus mascotas'
      loading.value = false
      return
    }

    console.log('[MascotasUsuario] Usuario autenticado, realizando petición a /api/mascotas', {
      tokenPresente: !!accessToken.value,
      tokenInicio: accessToken.value ? `${accessToken.value.substring(0, 10)}...` : 'null',
      usuario: user.value?.email
    })

    const response = await axiosAuth.get('/api/mascotas')

    console.log('[DEBUG] Respuesta completa:', response.data)
    if (response.data.mascotas && response.data.mascotas.length > 0) {
      console.log('[DEBUG] Primera mascota:', JSON.stringify(response.data.mascotas[0], null, 2))
      if (response.data.mascotas[0].fotos && response.data.mascotas[0].fotos.length > 0) {
        console.log('[DEBUG] Primera foto:', JSON.stringify(response.data.mascotas[0].fotos[0], null, 2))
      }
    }

    console.log('[MascotasUsuario] Respuesta del servidor:', {
      success: response.data.success,
      cantidadMascotas: response.data.mascotas?.length || 0
    })

   if (response.data.success) {
      mascotas.value = response.data.mascotas.map(mascota => {
        console.log('[MascotasUsuario] Procesando mascota:', {
          id: mascota.id,
          nombre: mascota.nombre,
          edad_formateada: mascota.edad_formateada,
          cantidadFotos: mascota.fotos?.length || 0
        })
        
        // ✅ CORRECCIÓN: Usar optimized_urls para obtener la mejor versión
      // En mascotasUsuario.vue, modifica la sección donde asignas la imagen
        if (mascota.fotos && mascota.fotos.length > 0) {
          const primeraFoto = mascota.fotos[0];
          
          // ✅ CAMBIA EL ORDEN DE PRIORIDAD - Usar URL directa de storage primero
          if (primeraFoto.url && primeraFoto.url.startsWith('/storage/')) {
            // Usar URL directa de storage (más confiable)
            mascota.imagen = `http://localhost:8000${primeraFoto.url}`;
            console.log('[MascotasUsuario] Usando URL directa de storage:', mascota.imagen)
          } 
          else if (primeraFoto.optimized_urls && primeraFoto.optimized_urls.original) {
            // Usar URL optimizada como fallback
            mascota.imagen = primeraFoto.optimized_urls.original.startsWith('/') 
              ? `http://localhost:8000${primeraFoto.optimized_urls.original}`
              : primeraFoto.optimized_urls.original;
            console.log('[MascotasUsuario] Usando URL optimizada original:', mascota.imagen)
          }
          else if (primeraFoto.url) {
            mascota.imagen = primeraFoto.url.startsWith('/') 
              ? `http://localhost:8000${primeraFoto.url}`
              : primeraFoto.url;
            console.log('[MascotasUsuario] Usando URL simple:', mascota.imagen)
          }
          else {
            mascota.imagen = 'https://cdn.pixabay.com/photo/2017/08/18/06/49/capybara-2653996_1280.jpg';
          }
        } else if (!mascota.imagen) {
          mascota.imagen = 'https://cdn.pixabay.com/photo/2017/08/18/06/49/capybara-2653996_1280.jpg';
          console.log('[MascotasUsuario] Usando imagen por defecto para mascota:', mascota.nombre)
        }
        
        return mascota;
      })
      
      console.log('[MascotasUsuario] Mascotas cargadas exitosamente:', mascotas.value.length)
    } else {
      throw new Error(response.data.message || 'Error en la respuesta del servidor')
    }
  } catch (err) {
    console.error('[MascotasUsuario] Error al cargar mascotas:', {
      error: err.message,
      status: err.response?.status,
      data: err.response?.data
    })

    if (err.response?.status === 401) {
      error.value = 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.'
    } else if (err.response?.status === 403) {
      error.value = 'No tienes permisos para acceder a esta información.'
    } else if (err.response?.status === 404) {
      error.value = 'No se encontraron mascotas.'
    } else {
      error.value = err.response?.data?.message || err.message || 'Error al cargar las mascotas'
    }
  } finally {
    loading.value = false
    console.log('[MascotasUsuario] Carga de mascotas finalizada')
  }
}

onMounted(async () => {
  await cargarMascotas()
  
  // ✅ Verificar si debemos abrir una mascota específica
  const abrirMascotaId = route.query.abrir_mascota
  
  if (abrirMascotaId && !loading.value && mascotas.value.length > 0) {
    const mascota = mascotas.value.find(m => m.id == abrirMascotaId)
    
    if (mascota) {
      abrirDetalleMascota(abrirMascotaId)
    }
    
    // Limpiar query params
    router.replace({ query: { ...route.query, abrir_mascota: undefined } })
  }
})

const abrirRegistroMascota = () => {
  if (!isAuthenticated.value) {
    error.value = 'Debes iniciar sesión para registrar una mascota'
    return
  }
  
  router.push({
    path: '/explorar/perfil/registro',
    query: {
      from: '/explorar/perfil/mascotas'
    }
  });
};

const editarMascota = (id) => {
  if (!isAuthenticated.value) {
    error.value = 'Debes iniciar sesión para editar mascotas'
    return
  }
  
  router.push({ 
    path: `/explorar/perfil/mascotas/editar/${id}`,
    query: {
      from: '/explorar/perfil/mascotas'
    }
  })
}

const eliminarMascota = (id) => {
  if (!isAuthenticated.value) {
    error.value = 'Debes iniciar sesión para eliminar mascotas'
    return
  }
  
  router.push({
    name: "darBajaMascota",
    params: { id },
    query: { from: "/explorar/perfil/mascotas" }
  })
}

watch(isAuthenticated, async (newVal) => {
  if (newVal) {
    console.log('[MascotasUsuario] Usuario re-autenticado, recargando mascotas...')
    await cargarMascotas()
  }
})
</script>

<style scoped>
.bg-transparent {
  background-color: transparent;
  /* Opcional: asegura que no afecte el layout */
  position: static;
  z-index: 0;
}
</style>
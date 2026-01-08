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
      class="fixed bottom-14 left-1/2  transform -translate-x-1/2 text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full px-6 py-3 text-base md:text-lg font-bold shadow-lg hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl transition-all duration-200 hover:scale-105 z-50 whitespace-nowrap"
    >
      + Agregar Mascota
    </button>

    <div class="relative">
      <router-view />
      <router-view name="overlay" />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import MascotaCard from '@/components/módulo_mascotas/tarjetaMascota.vue'
import { useAuth } from '@/composables/useAuth' // ✅ Usar useAuth en lugar de useAuthToken

const router = useRouter()
const { 
  user, 
  accessToken, 
  isAuthenticated, 
  checkAuth,
  logout 
} = useAuth() // ✅ Nuevo composable

const mascotas = ref([])
const loading = ref(true)
const error = ref('')

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
    
    // ✅ Verificar autenticación con el servidor
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

    console.log('[MascotasUsuario] Respuesta del servidor:', {
      success: response.data.success,
      cantidadMascotas: response.data.mascotas?.length || 0
    })

    if (response.data.success) {
      mascotas.value = response.data.mascotas.map(mascota => {
      console.log('[MascotasUsuario] Procesando mascota:', {
        id: mascota.id,
        nombre: mascota.nombre,
        edad_formateada: mascota.edad_formateada, // ← Agregar esto para debug
        cantidadFotos: mascota.fotos?.length || 0
      })
      
      // Solo agregar la URL de imagen si no existe
      if (!mascota.imagen && mascota.fotos && mascota.fotos.length > 0) {
        mascota.imagen = mascota.fotos[0].url;
        console.log('[MascotasUsuario] URL de imagen asignada:', mascota.imagen)
      } else if (!mascota.imagen) {
        mascota.imagen = 'https://cdn.pixabay.com/photo/2017/08/18/06/49/capybara-2653996_1280.jpg';
        console.log('[MascotasUsuario] Usando imagen por defecto para mascota:', mascota.nombre)
      }
      
      // Devolver la mascota completa, no sobrescribir
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

    // ✅ Manejo mejorado de errores
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

// ✅ Mejor manejo del mounted
onMounted(async () => {
  await cargarMascotas()
})

const abrirRegistroMascota = () => {
  // ✅ Verificar autenticación antes de redirigir
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

const abrirDetalleMascota = (id) => {
  // ✅ Verificar autenticación antes de redirigir
  if (!isAuthenticated.value) {
    error.value = 'Debes iniciar sesión para ver los detalles'
    return
  }
  
  router.push({
    path: `/explorar/perfil/mascota/${id}`,
    query: {
      from: '/explorar/perfil/mascotas'
    }
  });
};

const bgColors = [
  'bg-orange-200 hover:bg-orange-400',
  'bg-yellow-200 hover:bg-yellow-400',
  'bg-purple-200 hover:bg-purple-400',
  'bg-red-200 hover:bg-red-400',
  'bg-sky-200 hover:bg-sky-400',
  'bg-fuchsia-200 hover:bg-fuchsia-400',
  'bg-emerald-200 hover:bg-emerald-400'
];

const editarMascota = (id) => {
  // ✅ Verificar autenticación antes de redirigir
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
  // ✅ Verificar autenticación antes de redirigir
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

// ✅ Recargar mascotas cuando cambie el estado de autenticación
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
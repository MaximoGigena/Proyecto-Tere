<!-- views/ExplorarEncuentros.vue -->
<template>
  <div
  ref="animatedBg"
  class="bg-cover bg-repeat bg-center min-h-screen flex items-center justify-center">

   <div class="w-11/12 max-w-2xl bg-white h-screen shadow-lg relative flex flex-col">
      <!-- Contenido principal (siempre visible) -->
      <div class="relative w-full h-full">
        <!-- Contenido principal (siempre visible) -->
        <router-view v-slot="{ Component }">
              <component :is="Component" />
            </router-view>

            <!-- Vista overlay -->
          <router-view name="overlay" v-slot="{ Component, route }">
            <transition name="fade">
              <div v-if="Component" class="fixed inset-0 z-50 bg-black/55 flex justify-center items-center">
                <!-- Wrapper que limita el ancho -->
                <div class="w-11/12 max-w-2xl">
                  <component :is="Component" :key="route.fullPath" />
                </div>
              </div>
            </transition>
          </router-view>
      </div>

      <!-- Navegación inferior -->
      <div class="absolute bottom-0 w-full bg-white border-t py-2 text-gray-600 flex justify-around z-20">
        <router-link
          v-for="item in navItems"
          :key="item.id"
          :to="item.path"
          class="flex flex-col items-center px-4 py-1 rounded-md transition-all duration-300 relative group"
          :class="isActive(item) ? 'text-emerald-600' : 'text-gray-600 hover:text-emerald-500'"
        >
          <!-- Contenedor del icono con círculo -->
          <div class="relative flex justify-center mb-0.5">
            <!-- Círculo de fondo solo para el icono activo -->
            <div 
              v-if="isActive(item)"
              class="absolute inset-0 bg-emerald-100 rounded-full transition-all duration-300"
              style="width: 40px; height: 40px; left: 50%; transform: translateX(-50%); top: -4px;"
            ></div>
            
            <!-- Icono con efecto de hinchado -->
            <div class="relative z-10 transition-all duration-300 flex items-center justify-center" 
                style="width: 32px; height: 32px;"
                :class="isActive(item) ? 'scale-125' : 'group-hover:scale-110'">
              <font-awesome-icon :icon="['fas', item.icon]" class="text-xl" />
            </div>
          </div>
          
          <!-- Label -->
          <span class="text-xs relative z-10 font-medium" 
                :class="isActive(item) ? 'text-emerald-600' : 'text-gray-600 group-hover:text-emerald-500'">
            {{ item.label }}
          </span>
          
          <!-- Indicador de chats (sin cambios) -->
          <span
            v-if="item.id === 'chats'"
            class="absolute top-0 right-3 bg-red-500 text-white text-xs px-1.5 rounded-full z-20"
          >●</span>
        </router-link>
      </div>
    </div>

    <!-- Banner de donaciones -->
    <transition name="slide-down">
      <div
        v-if="showDonationBanner"
        class="fixed bottom-20 left-5 z-40 w-80 p-5 rounded-2xl shadow-xl 
              bg-gradient-to-br from-gray-900 via-gray-700 to-gray-800 
              text-white border border-white/20 backdrop-blur-md 
              animate-fade-in-up"
      >
        <div class="flex items-center justify-center gap-2 mb-2">
          <font-awesome-icon
            :icon="['fas', 'hand-holding-heart']"
            class="text-white text-2xl drop-shadow"
          />
          <h2 class="text-lg font-bold">Apoyá nuestra causa</h2>
        </div>

        <p class="text-sm opacity-90 mb-4 text-center">
          Todas las donaciones son usadas para mejorar la plataforma
        </p>

        <button
          @click="irADonaciones"
          class="w-full px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 
                text-white font-semibold shadow-lg transition backdrop-blur-sm"
        >
          Donar
        </button>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { RouterLink } from 'vue-router'
import huellas from '@/assets/huellas.png'
import axios from 'axios'
import { useAuthToken } from '@/composables/useAuthToken'
import { watch } from 'vue'

const animatedBg = ref(null)
const route = useRoute()
const router = useRouter()
const { accessToken, isAuthenticated, setToken } = useAuthToken()
const activo = ref('encuentros')
const scrollContainer = ref(null)

// Constantes para sessionStorage
const LOCATION_SESSION_KEY = 'location_requested_in_session'
const LOCATION_SAVED_KEY = 'location_saved_in_session'

// Estados de ubicación
const locationRequestedInSession = ref(false)
const locationError = ref(null)
const isSavingLocation = ref(false)
const locationSaved = ref(false)

// Inicializar estados desde sessionStorage
const initLocationState = () => {
  const savedRequested = sessionStorage.getItem(LOCATION_SESSION_KEY)
  const savedLocation = sessionStorage.getItem(LOCATION_SAVED_KEY)
  
  locationRequestedInSession.value = savedRequested === 'true'
  locationSaved.value = savedLocation === 'true'
  
  console.log('📍 Estado inicial de ubicación:', {
    solicitadaEnSesion: locationRequestedInSession.value,
    ubicacionGuardada: locationSaved.value
  })
}

// Llamar a inicialización
initLocationState()

// Función principal para solicitar ubicación al login
async function solicitarUbicacionAlLogin() {
  // Verificar condiciones
  if (!isAuthenticated.value) {
    console.log('❌ Usuario no autenticado, no se solicita ubicación')
    return false
  }
  
  if (locationRequestedInSession.value) {
    console.log('⚠️ Ubicación ya solicitada en esta sesión')
    return false
  }
  
  if (locationSaved.value) {
    console.log('✅ Ya hay ubicación guardada')
    locationRequestedInSession.value = true
    sessionStorage.setItem(LOCATION_SESSION_KEY, 'true')
    return false
  }
  
  console.log('🎯 Iniciando solicitud de ubicación para esta sesión...')
  
  // Marcar como solicitada inmediatamente
  locationRequestedInSession.value = true
  sessionStorage.setItem(LOCATION_SESSION_KEY, 'true')
  locationError.value = null
  
  // Pequeño delay para asegurar que todo esté listo
  await new Promise(resolve => setTimeout(resolve, 500))
  
  return await obtenerYGuardarUbicacion()
}

// Función para obtener y guardar ubicación
async function obtenerYGuardarUbicacion() {
  if (!navigator.geolocation) {
    console.error('Geolocalización no soportada')
    locationError.value = 'Tu navegador no soporta geolocalización'
    mostrarNotificacion('Tu navegador no soporta geolocalización', 'error')
    return false
  }
  
  try {
    // Verificar estado del permiso
    const permissionStatus = await navigator.permissions.query({ name: 'geolocation' })
    console.log('📡 Estado del permiso de ubicación:', permissionStatus.state)
    
    if (permissionStatus.state === 'denied') {
      mostrarInstruccionesUbicacion()
      return false
    }
    
    // Obtener posición
    console.log('📍 Solicitando posición al navegador...')
    const position = await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      })
    })
    
    // Guardar en servidor
    return await guardarUbicacionServidor(position)
    
  } catch (error) {
    console.error('❌ Error obteniendo ubicación:', error)
    manejarErrorUbicacion(error)
    return false
  }
}

// Función para guardar ubicación en el servidor
async function guardarUbicacionServidor(position) {
  if (isSavingLocation.value) {
    console.log('⏳ Ya se está guardando la ubicación')
    return false
  }
  
  isSavingLocation.value = true
  
  try {
    const { latitude, longitude, accuracy } = position.coords
    console.log('📤 Enviando ubicación al servidor:', { latitude, longitude, accuracy })
    
    // Obtener token CSRF
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
    
    const response = await axios.post('/api/guardar-ubicacion', {
      latitude,
      longitude,
      accuracy
    }, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      withCredentials: true
    })
    
    if (response.data) {
      locationSaved.value = true
      sessionStorage.setItem(LOCATION_SAVED_KEY, 'true')
      
      const city = response.data.geo_data?.city || response.data.data?.city
      const mensaje = city ? `📍 Ubicación guardada en ${city}` : '📍 Ubicación guardada correctamente'
      mostrarNotificacion(mensaje, 'success')
      console.log('✅ Ubicación guardada exitosamente')
      return true
    }
    
  } catch (error) {
    console.error('❌ Error al guardar ubicación:', error)
    
    if (error.response?.status === 401) {
      mostrarNotificacion('Sesión expirada. Por favor inicia sesión nuevamente.', 'error')
      router.push('/login')
    } else if (error.response?.status === 422) {
      mostrarNotificacion('Datos de ubicación inválidos', 'error')
    } else {
      mostrarNotificacion('Error al guardar la ubicación', 'error')
    }
    
    return false
  } finally {
    isSavingLocation.value = false
  }
}

// Verificar si el usuario ya tiene ubicación guardada
async function verificarUbicacionExistente() {
  if (!isAuthenticated.value) return false
  
  try {
    console.log('🔍 Verificando ubicación existente...')
    const response = await axios.get('/api/ubicacion', {
      headers: { Authorization: `Bearer ${accessToken.value}` }
    })
    
    if (response.data?.data) {
      locationSaved.value = true
      sessionStorage.setItem(LOCATION_SAVED_KEY, 'true')
      console.log('📍 Ubicación existente encontrada')
      return true
    }
  } catch (error) {
    if (error.response?.status !== 404) {
      console.error('Error verificando ubicación:', error)
    }
  }
  
  return false
}

// Manejar token desde URL
async function handleTokenFromUrl() {
  const token = route.query.token
  const userId = route.query.user_id
  
  if (token && userId) {
    try {
      console.log('🔐 Procesando token de URL...')
      
      // Guardar token
      setToken(token)
      
      // Verificar usuario
      const response = await axios.get(`/api/users/${userId}`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      
      console.log('✅ Usuario autenticado:', response.data)
      
      // Limpiar URL
      const cleanUrl = window.location.pathname
      window.history.replaceState({}, document.title, cleanUrl)
      
      // Esperar a que el token se establezca completamente
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      // Verificar ubicación existente
      const tieneUbicacion = await verificarUbicacionExistente()
      
      if (!tieneUbicacion && !locationRequestedInSession.value) {
        console.log('🔄 Usuario sin ubicación, solicitando...')
        setTimeout(() => {
          solicitarUbicacionAlLogin()
        }, 1500)
      } else if (tieneUbicacion) {
        console.log('✅ Usuario ya tiene ubicación guardada')
      }
      
    } catch (error) {
      console.error('❌ Error procesando token:', error)
      mostrarNotificacion('Error en autenticación. Por favor intenta nuevamente.', 'error')
    }
  } else if (isAuthenticated.value && !locationRequestedInSession.value) {
    // Usuario ya autenticado sin token en URL
    console.log('👤 Usuario ya autenticado')
    const tieneUbicacion = await verificarUbicacionExistente()
    
    if (!tieneUbicacion) {
      console.log('🔄 Usuario autenticado sin ubicación, solicitando...')
      setTimeout(() => {
        solicitarUbicacionAlLogin()
      }, 1500)
    }
  }
}

// Inicializar componente
function initializeComponent() {
  document.body.style.overflow = 'hidden'
  
  if (animatedBg.value) {
    animatedBg.value.style.backgroundImage = `url(${huellas})`
    animatedBg.value.style.animation = 'moverHuellas 120s linear infinite'
    animatedBg.value.style.backgroundPosition = '0 0'
  }
  
  if (route.path === '/encuentros') {
    router.replace('/explorar/encuentros')
  }
}

// Items de navegación
const navItems = [
  { id: 'cerca', label: 'Cerca', icon: 'fa-location-dot', path: '/explorar/cerca' },
  { id: 'encuentros', label: 'Encuentros', icon: 'fa-paw', path: '/explorar/encuentros' },
  { id: 'chats', label: 'Chats', icon: 'fa-comment', path: '/explorar/chats' },
  { id: 'perfil', label: 'Perfil', icon: 'fa-user', path: '/explorar/perfil/mascotas', base: '/explorar/perfil'},
]

const isActive = (item) => {
  if (item.base) {
    return route.path.startsWith(item.base)
  }
  return route.path.startsWith(item.path.replace(/\/$/, ''))
}

// Reintentar ubicación manualmente
function reintentarUbicacion() {
  if (locationRequestedInSession.value && !locationSaved.value) {
    // Reset para reintentar
    locationRequestedInSession.value = false
    sessionStorage.removeItem(LOCATION_SESSION_KEY)
    solicitarUbicacionAlLogin()
  } else if (!locationRequestedInSession.value) {
    solicitarUbicacionAlLogin()
  } else {
    mostrarNotificacion('La ubicación ya fue procesada en esta sesión', 'info')
  }
}

// Mostrar notificación
function mostrarNotificacion(mensaje, tipo = 'info') {
  const notificacion = document.createElement('div')
  notificacion.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg text-white ${
    tipo === 'success' ? 'bg-emerald-500' : tipo === 'error' ? 'bg-red-500' : 'bg-blue-500'
  } transition-opacity duration-500`
  notificacion.textContent = mensaje
  
  document.body.appendChild(notificacion)
  
  setTimeout(() => {
    notificacion.style.opacity = '0'
    setTimeout(() => {
      if (document.body.contains(notificacion)) {
        document.body.removeChild(notificacion)
      }
    }, 500)
  }, 3000)
}

// Manejar errores de ubicación
function manejarErrorUbicacion(error) {
  console.error('Error de ubicación:', error)
  
  if (error.code === 1 || error.code === error.PERMISSION_DENIED) {
    if (!localStorage.getItem('location-permission-denied-shown')) {
      mostrarInstruccionesUbicacion()
      localStorage.setItem('location-permission-denied-shown', 'true')
    }
  } else if (error.code === 2 || error.code === error.POSITION_UNAVAILABLE) {
    mostrarNotificacion('No se pudo obtener la ubicación. Verifica el GPS de tu dispositivo.', 'error')
  } else if (error.code === 3 || error.code === error.TIMEOUT) {
    mostrarNotificacion('Tiempo agotado al obtener la ubicación. Intenta nuevamente.', 'error')
  }
}

// Mostrar instrucciones para activar ubicación
function mostrarInstruccionesUbicacion() {
  const mensaje = 
    '🌍 Permiso de ubicación bloqueado\n\n' +
    'Para usar la función "Cerca" y ver mascotas cerca de ti:\n\n' +
    '1. Haz clic en el icono de candado (🔒) en la barra de direcciones\n' +
    '2. Selecciona "Configuración de sitios" o "Permisos"\n' +
    '3. Busca "Ubicación" y cámbialo a "Permitir"\n' +
    '4. Recarga la página\n\n' +
    '¿Quieres continuar sin activar la ubicación?'
  
  const continuar = confirm(mensaje)
  
  if (!continuar) {
    alert('Por favor, activa la ubicación siguiendo las instrucciones y recarga la página.')
  }
}

// Limpiar estado de ubicación (útil para pruebas)
const clearLocationSessionState = () => {
  sessionStorage.removeItem(LOCATION_SESSION_KEY)
  sessionStorage.removeItem(LOCATION_SAVED_KEY)
  locationRequestedInSession.value = false
  locationSaved.value = false
  localStorage.removeItem('location-permission-denied-shown')
  console.log('🗑️ Estado de ubicación limpiado')
}

// Banner de donaciones
const showDonationBanner = ref(false)
let bannerInterval = null

function irADonaciones() {
  router.push('/Donaciones')
}

// Watch para cambios en query params
watch(() => route.query, async (newQuery) => {
  if (newQuery.token) {
    await handleTokenFromUrl()
  }
})

// Lifecycle hooks
onMounted(async () => {
  // Inicializar fondo y UI
  initializeComponent()
  
  // Manejar autenticación y ubicación
  await handleTokenFromUrl()
  
  // Configurar banner de donaciones
  bannerInterval = setInterval(() => {
    showDonationBanner.value = true
    setTimeout(() => {
      showDonationBanner.value = false
    }, 25000)
  }, 40000)
})

onUnmounted(() => {
  document.body.style.overflow = 'auto'
  if (bannerInterval) clearInterval(bannerInterval)
})

// Exponer métodos útiles
defineExpose({
  clearLocationSessionState,
  reintentarUbicacion
})
</script>

<style>
  @keyframes moverHuellas {
    0% {
      background-position: 0 0;
    }
    100% {
      background-position: 0 1024px;
    }
  }

  .animate-huellas {
    animation: moverHuellas 120s linear infinite;
  }

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.5s ease;
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(40px);
}

.slide-down-enter-to {
  opacity: 1;
  transform: translateY(0);
}

.slide-down-leave-from {
  opacity: 1;
  transform: translateY(0);
}

.slide-down-leave-to {
  opacity: 0;
  transform: translateY(100px);
}

/* 🔥 Estilos para mostrar estado de ubicación */
.location-status {
  position: fixed;
  top: 10px;
  right: 10px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  z-index: 1000;
  display: flex;
  align-items: center;
  gap: 6px;
}

.location-status.loading {
  background: rgba(59, 130, 246, 0.9);
}

.location-status.error {
  background: rgba(239, 68, 68, 0.9);
}

.location-status.success {
  background: rgba(34, 197, 94, 0.9);
}
</style>
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
      <div class="absolute bottom-0 w-full bg-white border-t py-3 text-gray-600 flex justify-around z-20">
        <router-link
          v-for="item in navItems"
          :key="item.id"
          :to="item.path"
          class="flex flex-col items-center px-4 py-1 rounded-md transition relative"
          :class="isActive(item) ? 'bg-white text-black' : 'text-gray-600 hover:text-black'"
        >
          <font-awesome-icon :icon="['fas', item.icon]" class="text-xl" />
          <span class="text-xs">{{ item.label }}</span>
          <span
            v-if="item.id === 'chats'"
            class="absolute top-1 right-3 bg-red-500 text-white text-xs px-1.5 rounded-full"
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
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue'
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

// Estado para controlar si ya solicitamos ubicación
const hasRequestedLocation = ref(false)
const locationError = ref(null)
const isSavingLocation = ref(false)

// Verificar si hay token en la URL al montar el componente
onMounted(async () => {
  await handleTokenFromUrl()
  initializeComponent()
})

// También verificar cuando cambia la ruta
watch(() => route.query, async (newQuery) => {
  if (newQuery.token) {
    await handleTokenFromUrl()
  }
})

async function handleTokenFromUrl() {
  const token = route.query.token
  const userId = route.query.user_id
  
  if (token && userId) {
    try {
      console.log('Token encontrado en URL, procesando...')
      
      // Guardar token usando el composable
      setToken(token)
      
      // Obtener información del usuario
      const response = await axios.get(`/api/users/${userId}`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      
      console.log('Usuario autenticado:', response.data)
      
      // Limpiar la URL removiendo los parámetros del token
      const cleanUrl = window.location.pathname
      window.history.replaceState({}, document.title, cleanUrl)
      
      // 🔥 SOLICITAR UBICACIÓN DESPUÉS DE AUTENTICAR
      setTimeout(() => {
        solicitarUbicacionInicial();
      }, 1000);
      
    } catch (error) {
      console.error('Error procesando token:', error)
      alert('Error en autenticación. Por favor intenta nuevamente.')
    }
  } else if (isAuthenticated.value) {
    // Si ya está autenticado por otro medio, también solicitar ubicación
    setTimeout(() => {
      solicitarUbicacionInicial();
    }, 1000);
  }
}

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

// 🔥 FUNCIÓN PRINCIPAL: Solicitar ubicación inicial
async function solicitarUbicacionInicial() {
  // Evitar múltiples solicitudes
  if (hasRequestedLocation.value || !isAuthenticated.value) {
    return;
  }
  
  console.log('📍 Iniciando solicitud de ubicación...');
  
  // Pequeño delay para mejor UX
  setTimeout(async () => {
    await pedirYGuardarUbicacion();
  }, 1500);
}

// 🔥 FUNCIÓN MODIFICADA: Usar solicitud nativa del navegador
async function pedirYGuardarUbicacion() {
  if (hasRequestedLocation.value) return;
  
  hasRequestedLocation.value = true;
  locationError.value = null;
  
  console.log('📍 Iniciando proceso de ubicación...');
  
  // Verificar autenticación
  if (!isAuthenticated.value) {
    console.log('Usuario no autenticado, no se solicita ubicación');
    return;
  }

  if (!navigator.geolocation) {
    locationError.value = 'Tu navegador no soporta geolocalización.';
    alert('Tu navegador no soporta geolocalización.');
    return;
  }

  try {
    // 1. Primero verificar el estado del permiso
    const permissionStatus = await navigator.permissions.query({ name: 'geolocation' });
    console.log('Estado del permiso:', permissionStatus.state);

    if (permissionStatus.state === 'denied') {
      // Si ya fue denegado, mostrar instrucciones
      mostrarInstruccionesUbicacion();
      return;
    }

    if (permissionStatus.state === 'granted') {
      // Si ya está permitido, obtener ubicación directamente
      await obtenerYGuardarUbicacion();
      return;
    }

    // 2. Si está en "prompt", usar la solicitud nativa del navegador
    // Esta será la solicitud que aparece en el margen superior derecho
    console.log('Solicitando permiso de ubicación...');
    
    const position = await new Promise((resolve, reject) => {
      // 🔥 ESTA LLAMADA ACTIVA LA SOLICITUD NATIVA DEL NAVEGADOR
      navigator.geolocation.getCurrentPosition(
        resolve,
        reject,
        {
          enableHighAccuracy: true,
          timeout: 15000,
          maximumAge: 0 // Siempre obtener ubicación fresca
        }
      );
    });

    // 3. Si el usuario acepta la solicitud nativa, proceder
    await obtenerYGuardarUbicacion(position);

  } catch (error) {
    console.error('Error al obtener ubicación:', error);
    manejarErrorUbicacion(error);
  } finally {
    hasRequestedLocation.value = false;
  }
}

// 🔥 FUNCIÓN PARA OBTENER Y GUARDAR UBICACIÓN
async function obtenerYGuardarUbicacion(position = null) {
  isSavingLocation.value = true;
  
  try {
    let latitude, longitude, accuracy;
    
    if (position) {
      // Usar la posición ya obtenida
      ({ latitude, longitude, accuracy } = position.coords);
    } else {
      // Obtener nueva posición
      const newPosition = await new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: true,
          timeout: 10000
        });
      });
      ({ latitude, longitude, accuracy } = newPosition.coords);
    }

    console.log('Ubicación obtenida:', { latitude, longitude, accuracy });

    // Obtener token CSRF
    await axios.get('/sanctum/csrf-cookie', {
      withCredentials: true
    });

    // Enviar ubicación al servidor con reverse geocoding
    console.log('Enviando ubicación al servidor...');
    const response = await axios.post('/api/guardar-ubicacion', {
      latitude,
      longitude,
      accuracy
    }, {
      withCredentials: true,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization': `Bearer ${accessToken.value}`
      }
    });

    console.log('📍 Ubicación guardada con reverse geocoding:', response.data);
    
    // Mostrar mensaje con la ciudad detectada
    const city = response.data.geo_data?.city || response.data.data?.city;
    if (city) {
      //alert(`¡Perfecto! Ubicación guardada en ${city}. Ahora puedes ver mascotas cerca de ti.`);
    } else {
      //alert('¡Ubicación guardada correctamente! Ahora puedes ver mascotas cerca de ti.');
    }

  } catch (error) {
    console.error('Error al guardar ubicación en el servidor:', error);
    manejarErrorUbicacion(error);
  } finally {
    isSavingLocation.value = false;
  }
}

// 🔥 FUNCIÓN PARA MANEJAR ERRORES
function manejarErrorUbicacion(error) {
  console.error('Error completo:', error);
  
  if (error.code === 1 || error.code === error.PERMISSION_DENIED) {
    // Permiso denegado por el usuario
    mostrarInstruccionesUbicacion();
  } else if (error.code === 2 || error.code === error.POSITION_UNAVAILABLE) {
    alert('No se pudo obtener la ubicación. Verifica que el GPS esté activado.');
  } else if (error.code === 3 || error.code === error.TIMEOUT) {
    alert('Tiempo agotado al obtener la ubicación. Verifica tu conexión y GPS.');
  } else if (error.response?.status === 401) {
    alert('Sesión expirada. Por favor inicia sesión nuevamente.');
    router.push('/login');
  } else {
    alert('Error al obtener tu ubicación. Intenta nuevamente.');
  }
}

// 🔥 FUNCIÓN PARA MOSTRAR INSTRUCCIONES CUANDO EL PERMISO ES DENEGADO
function mostrarInstruccionesUbicacion() {
  const mensaje = 
    'Has bloqueado el permiso de ubicación. Para usar la función "Cerca" y ver mascotas cerca de ti:\n\n' +
    '1. Haz clic en el icono de candado (🔒) en la barra de direcciones\n' +
    '2. Selecciona "Configuración de sitios" o "Permisos"\n' +
    '3. Busca "Ubicación" y cámbialo a "Permitir"\n' +
    '4. Recarga la página\n\n' +
    'O también puedes:\n' +
    '• En Chrome: Configuración → Privacidad y seguridad → Configuración de sitios → Ubicación\n' +
    '• En Firefox: Opciones → Privacidad y seguridad → Permisos → Ubicación\n' +
    '• En Safari: Preferencias → Sitios web → Ubicación';
  
  const continuar = confirm(mensaje + '\n\n¿Quieres continuar sin activar la ubicación?');
  
  if (!continuar) {
    // Si el usuario quiere activarla, mostrar instrucciones más detalladas
    alert('Sigue las instrucciones anteriores para activar la ubicación, luego recarga la página.');
  }
}

// 🔥 FUNCIÓN PARA REINTENTAR (se puede llamar desde otro componente)
function reintentarUbicacion() {
  hasRequestedLocation.value = false;
  pedirYGuardarUbicacion();
}

onUnmounted(() => {
  document.body.style.overflow = 'auto'
})

// 🔥 CONTROL DEL BANNER DE DONACIONES
const showDonationBanner = ref(false)
let bannerInterval = null

onMounted(() => {
  // Mostrar el banner cada 40s
  bannerInterval = setInterval(() => {
    showDonationBanner.value = true
    // Ocultarlo después de 25s
    setTimeout(() => {
      showDonationBanner.value = false
    }, 25000)
  }, 40000)
})

onUnmounted(() => {
  if (bannerInterval) clearInterval(bannerInterval)
})

function irADonaciones() {
  router.push('/Donaciones')
}
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
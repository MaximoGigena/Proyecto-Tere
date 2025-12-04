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
        class="fixed bottom-20 right-5 z-40 w-80 p-5 rounded-2xl shadow-xl 
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
import { ref, onMounted, onUnmounted, nextTick} from 'vue'
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
      
      // Aquí podrías guardar el usuario en un store si lo necesitas
      console.log('Usuario autenticado:', response.data)
      
      // Limpiar la URL removiendo los parámetros del token
      const cleanUrl = window.location.pathname
      window.history.replaceState({}, document.title, cleanUrl)
      
    } catch (error) {
      console.error('Error procesando token:', error)
      alert('Error en autenticación. Por favor intenta nuevamente.')
    }
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



onUnmounted(() => {
  document.body.style.overflow = 'auto'
})

// ... el resto de tus funciones existentes (pedirYGuardarUbicacion, etc.) ...
async function pedirYGuardarUbicacion() {
  console.log('📍 Iniciando proceso de ubicación...');
  
  // Verificar autenticación usando el composable
  if (!isAuthenticated.value) {
    console.log('Usuario no autenticado, no se solicita ubicación');
    return;
  }

  if (!navigator.geolocation) {
    alert('Tu navegador no soporta geolocalización.');
    return;
  }

  try {
    // 1. Verificar permisos
    const permissionStatus = await navigator.permissions.query({ name: 'geolocation' });
    console.log('Estado del permiso:', permissionStatus.state);

    if (permissionStatus.state === 'denied') {
      alert('Has bloqueado el permiso de ubicación. Para usar esta función:\n\n1. Haz clic en el icono de candado en la barra de direcciones\n2. Selecciona "Configuración de sitios"\n3. Busca "Ubicación" y cámbialo a "Permitir"\n4. Recarga la página');
      return;
    }

    if (permissionStatus.state === 'prompt') {
      const acepta = confirm('Para mostrarte mascotas cerca de ti, necesitamos acceder a tu ubicación. ¿Permites que TERE use tu ubicación?');
      if (!acepta) {
        console.log('Usuario rechazó la ubicación');
        return;
      }
    }

    // 2. Obtener token CSRF
    console.log('Obteniendo token CSRF...');
    await axios.get('/sanctum/csrf-cookie', {
      withCredentials: true
    });

    // 3. Obtener ubicación
    console.log('Obteniendo ubicación...');
    const position = await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(
        resolve,
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 300000
        }
      );
    });

    const { latitude, longitude, accuracy } = position.coords;
    console.log('Ubicación obtenida:', { latitude, longitude, accuracy });

    // 4. Enviar ubicación al servidor usando el token del composable
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
        'Authorization': `Bearer ${accessToken.value}` // Usar token del composable
      }
    });

    console.log('Ubicación guardada:', response.data);
    alert('¡Ubicación guardada correctamente! Ahora puedes ver mascotas cerca de ti.');

  } catch (error) {
    console.error('Error completo:', error);
    
    if (error.code === error.PERMISSION_DENIED || error.code === 1) {
    } else if (error.code === error.TIMEOUT || error.code === 3) {
      alert('Tiempo agotado al obtener la ubicación. Verifica tu conexión y GPS.');
    } else if (error.code === error.POSITION_UNAVAILABLE || error.code === 2) {
      alert('No se pudo obtener la ubicación. Verifica que el GPS esté activado.');
    } else if (error.response?.status === 401) {
      alert('Sesión expirada. Por favor inicia sesión nuevamente.');
      router.push('/login');
    }
  }
}

 // Verificar autenticación usando el composable
function checkAuth() {
  if (!isAuthenticated.value) {
    console.log('Usuario no autenticado, redirigiendo a login');
    router.push('/login');
    return false;
  }
  return true;
}
  
  console.log('Usuario autenticado, solicitando ubicación...');
  
  // Pequeño delay para mejor UX
  setTimeout(() => {
    pedirYGuardarUbicacion();
  }, 1000);

// 🔥 LLAMAR LA FUNCIÓN CUANDO EL COMPONENTE ESTÉ MONTADO
onMounted(async () => {
  await nextTick();
  
  // Verificar autenticación usando el composable
  if (!isAuthenticated.value) {
    console.log('Usuario no autenticado, no se solicita ubicación');
    // Redirigir a login si no está autenticado
    router.push('/login');
    return;
  }
  
  console.log('Usuario autenticado, solicitando ubicación...');
  
  // Pequeño delay para mejor UX
  setTimeout(() => {
    pedirYGuardarUbicacion();
  }, 1000);
});


//control del banner de donaciones
const showDonationBanner = ref(false)
let bannerInterval = null

onMounted(() => {
  // Mostrar el banner cada 60s
  bannerInterval = setInterval(() => {
    showDonationBanner.value = true
    // Ocultarlo después de 8s
    setTimeout(() => {
      showDonationBanner.value = false
    },25000)
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
  transform: translateY(100px); /* Desliza hacia abajo, fuera de la pantalla */
}

</style>

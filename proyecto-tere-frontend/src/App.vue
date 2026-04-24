<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios' // 🔥 Asegúrate de importar axios
import api from '@/services/api'
import { useAuth } from '@/composables/useAuth'
import './assets/styles.css'

const router = useRouter()
const route = useRoute() // 🔥 IMPORTANTE: Importar useRoute para detectar la ruta actual
const { checkAuth, processTokenFromUrl } = useAuth()

// Estado para la notificación de bienvenida
const showWelcomeNotification = ref(false)
const welcomeMessage = ref('')
let welcomeTimeout = null

// 🔥 FLAG EN MEMORIA (Opción 1) - Reemplaza sessionStorage
const welcomeShownForExplorar = ref(false)

// 🔥 Computed para detectar si estamos en ruta de explorar
const isExplorarRoute = computed(() => route.path.includes('/explorar'))

// Función para mostrar mensaje de bienvenida
function mostrarBienvenida(nombreUsuario) {
  console.log('🎉 [APP] MOSTRANDO BIENVENIDA - Nombre recibido:', nombreUsuario)
  
  if (welcomeTimeout) {
    clearTimeout(welcomeTimeout)
  }
  
  welcomeMessage.value = `✨ ¡Bienvenido ${nombreUsuario}! ✨`
  showWelcomeNotification.value = true
  
  welcomeTimeout = setTimeout(() => {
    showWelcomeNotification.value = false
  }, 6000)
}

// Función para obtener el nombre del usuario - CORREGIDA
async function obtenerNombreUsuario(userId, token) {
  try {
    // 🔥 Usar axios directamente con la ruta correcta (sin duplicar /api)
    const response = await axios.get(`/api/usuarios/${userId}`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    console.log('📦 Respuesta de API:', response.data)
    
    const nombreUsuario = response.data.usuario?.nombre || 
                         response.data.nombre || 
                         'Usuario'
    
    console.log('👤 [APP] Nombre de usuario obtenido:', nombreUsuario)
    return nombreUsuario
  } catch (error) {
    console.error('❌ [APP] Error obteniendo nombre del usuario:', error)
    console.error('❌ Detalles del error:', error.response?.data)
    return 'Usuario'
  }
}

// Interceptor global (tu código existente)
api.interceptors.response.use(
  response => response,
  error => {
    console.log('🔍 Interceptor global - Error detectado:', error.response?.status)
    
    if (error.response?.status === 403) {
      const data = error.response.data
      const isSuspended = data.code === 'ACCOUNT_SUSPENDED' || 
                         data.message?.includes('suspend') ||
                         data.redirect_to === '/cuenta-suspendida'
      
      if (isSuspended) {
        console.log('🚨 Usuario suspendido detectado en interceptor')
        if (data.data) {
          localStorage.setItem('suspension_data', JSON.stringify(data.data))
        } else {
          localStorage.setItem('suspension_data', JSON.stringify({
            razon: data.message || 'Cuenta suspendida',
            estado: 'suspendido'
          }))
        }
        if (router.currentRoute.value.path !== '/cuenta-suspendida') {
          console.log('🔄 Redirigiendo a cuenta-suspendida')
          router.replace('/cuenta-suspendida')
        }
      }
    }
    
    return Promise.reject(error)
  }
)

onMounted(async () => {
  console.log('🔐 APP - Verificando autenticación...')
  
  const token = localStorage.getItem('auth_token')
  console.log('📦 Token en localStorage:', token ? 'SÍ' : 'NO')
  
  // Verificar suspensión
  const suspensionData = localStorage.getItem('suspension_data')
  if (suspensionData) {
    try {
      const data = JSON.parse(suspensionData)
      if (data.estado === 'suspendido' || data.esta_suspendido) {
        console.log('🚫 Usuario suspendido, redirigiendo...')
        if (router.currentRoute.value.path !== '/cuenta-suspendida') {
          router.replace('/cuenta-suspendida')
          return
        }
      }
    } catch (e) {
      console.error('Error parsing suspension data:', e)
    }
  }
  
  // Procesar token desde URL
  const hasToken = await processTokenFromUrl()
  console.log('🔍 hasToken devuelve:', hasToken)
  
  // 🔥 NUEVA CONDICIÓN USANDO FLAG EN MEMORIA (Opción 1)
  // Mostrar bienvenida SOLO si:
  // 1. Hay token
  // 2. Estamos en ruta de explorar
  // 3. No se ha mostrado ya en esta sesión de memoria
  console.log('🎯 Verificando condiciones para bienvenida:', {
    hasToken: !!token,
    isExplorarRoute: isExplorarRoute.value,
    welcomeShownInMemory: welcomeShownForExplorar.value
  })
  
  if (token && isExplorarRoute.value && !welcomeShownForExplorar.value) {
    console.log('🎉 Preparando bienvenida para explorar...')
    
    let userId = localStorage.getItem('user_id')
    let userName = localStorage.getItem('user_name')
    
    console.log('📋 Datos en localStorage:', { userId, userName })
    
    if (userName) {
      mostrarBienvenida(userName)
      welcomeShownForExplorar.value = true // 🔥 Marcar como mostrado en memoria
    } else if (userId && token) {
      const nombre = await obtenerNombreUsuario(userId, token)
      localStorage.setItem('user_name', nombre)
      mostrarBienvenida(nombre)
      welcomeShownForExplorar.value = true // 🔥 Marcar como mostrado en memoria
    } else {
      mostrarBienvenida('Usuario')
      welcomeShownForExplorar.value = true // 🔥 Marcar como mostrado en memoria
    }
  } else {
    console.log('❌ NO se mostrará bienvenida porque:', {
      token: !!token,
      isExplorar: isExplorarRoute.value,
      alreadyShown: welcomeShownForExplorar.value
    })
  }
})

// 🔥 OPCIONAL: Resetear el flag cuando el usuario sale de la ruta de explorar
// Esto permite que si vuelve a entrar, se muestre nuevamente
watch(() => route.path, (newPath, oldPath) => {
  // Si salimos de explorar, reseteamos el flag
  if (oldPath?.includes('/explorar') && !newPath.includes('/explorar')) {
    console.log('🔄 Saliendo de explorar, reseteando flag de bienvenida')
    welcomeShownForExplorar.value = false
  }
})
</script>

<template>
  <!-- Notificación de bienvenida en App.vue (esquina superior izquierda) -->
  <transition name="fade-slide">
    <div 
      v-if="showWelcomeNotification"
      class="fixed top-4 left-4 z-50 bg-gradient-to-r from-blue-500 to-blue-600 
             text-white px-5 py-3 rounded-2xl shadow-lg backdrop-blur-sm
             border border-white/20 animate-bounce-in"
      style="max-width: 320px;"
    >
      <div class="flex items-center gap-3">
        <font-awesome-icon :icon="['fas', 'hands-clapping']" class="text-xl" />
        <span class="font-medium">{{ welcomeMessage }}</span>
      </div>
    </div>
  </transition>
  
  <router-view />
  <router-view name="overlay" />
</template>

<style scoped>
header {
  line-height: 1.5;
  max-height: 100vh;
}

.logo {
  display: block;
  margin: 0 auto 2rem;
}

nav {
  width: 100%;
  font-size: 12px;
  text-align: center;
  margin-top: 2rem;
}

nav a.router-link-exact-active {
  color: var(--color-text);
}

nav a.router-link-exact-active:hover {
  background-color: transparent;
}

nav a {
  display: inline-block;
  padding: 0 1rem;
  border-left: 1px solid var(--color-border);
}

nav a:first-of-type {
  border: 0;
}

@media (min-width: 1024px) {
  header {
    display: flex;
    place-items: center;
    padding-right: calc(var(--section-gap) / 2);
  }

  .logo {
    margin: 0 2rem 0 0;
  }

  header .wrapper {
    display: flex;
    place-items: flex-start;
    flex-wrap: wrap;
  }

  nav {
    text-align: left;
    margin-left: -1rem;
    font-size: 1rem;

    padding: 1rem 0;
    margin-top: 1rem;
  }
}

/* Animaciones para la notificación */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateX(-100px) scale(0.3);
}

.fade-slide-enter-to {
  opacity: 1;
  transform: translateX(0) scale(1);
}

.fade-slide-leave-from {
  opacity: 1;
  transform: translateX(0) scale(1);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateX(-50px) scale(0.5);
}

@keyframes bounceIn {
  0% {
    opacity: 0;
    transform: translateX(-100px) scale(0.3);
  }
  50% {
    opacity: 0.9;
    transform: translateX(10px) scale(1.05);
  }
  80% {
    transform: translateX(-5px) scale(0.95);
  }
  100% {
    opacity: 1;
    transform: translateX(0) scale(1);
  }
}

.animate-bounce-in {
  animation: bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
}
</style>
<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuth } from '@/composables/useAuth'
import './assets/styles.css'

const router = useRouter()
const { checkAuth, processTokenFromUrl } = useAuth()

// Interceptor global para manejar errores de suspensión
api.interceptors.response.use(
  response => response,
  error => {
    console.log('🔍 Interceptor global - Error detectado:', error.response?.status)
    
    // Si es error 403, verificar si es por suspensión
    if (error.response?.status === 403) {
      const data = error.response.data
      const isSuspended = data.code === 'ACCOUNT_SUSPENDED' || 
                         data.message?.includes('suspend') ||
                         data.redirect_to === '/cuenta-suspendida'
      
      if (isSuspended) {
        console.log('🚨 Usuario suspendido detectado en interceptor')
        // Guardar datos de suspensión
        if (data.data) {
          localStorage.setItem('suspension_data', JSON.stringify(data.data))
        } else {
          localStorage.setItem('suspension_data', JSON.stringify({
            razon: data.message || 'Cuenta suspendida',
            estado: 'suspendido'
          }))
        }
        // Redirigir inmediatamente
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
  
  // Verificar si hay token en localStorage
  const token = localStorage.getItem('auth_token')
  console.log('📦 Token en localStorage:', token ? 'SÍ' : 'NO')
  
  // Verificar si ya hay datos de suspensión
  const suspensionData = localStorage.getItem('suspension_data')
  if (suspensionData) {
    try {
      const data = JSON.parse(suspensionData)
      if (data.estado === 'suspendido' || data.esta_suspendido) {
        console.log('🚫 Usuario ya marcado como suspendido, redirigiendo...')
        if (router.currentRoute.value.path !== '/cuenta-suspendida') {
          router.replace('/cuenta-suspendida')
          return
        }
      }
    } catch (e) {
      console.error('Error parsing suspension data:', e)
    }
  }
  
  // Procesar token SIEMPRE al cargar la app
  const hasToken = await processTokenFromUrl()
  if (hasToken) {
    console.log('✅ Token procesado desde URL')
  }
})
</script>

<template>
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
</style>

// composables/useAuthToken.ts - VERSIÓN CORREGIDA
// composables/useAuthToken.ts - VERSIÓN DEFINITIVA
import { ref, computed } from 'vue'

export const useAuthToken = () => {
  const tokenData = ref<{ accessToken: string } | null>(null)

  const setToken = (accessToken: string) => {
    tokenData.value = { accessToken }
    localStorage.setItem('auth_token', JSON.stringify(tokenData.value))
    localStorage.setItem('token', accessToken) // Mantener compatibilidad
  }

  const loadTokenFromStorage = () => {
    console.log('🔄 Cargando token desde localStorage...')
    
    // DEBUG: Ver todo el localStorage
    const allStorage = { ...localStorage }
    console.log('📦 Contenido completo de localStorage:', allStorage)

    // PRIMERO: Buscar en auth_token (formato nuevo)
    const storedAuthToken = localStorage.getItem('auth_token')
    console.log('🔍 auth_token encontrado:', storedAuthToken)
    
    if (storedAuthToken) {
      try {
        const parsed = JSON.parse(storedAuthToken)
        if (parsed && parsed.accessToken) {
          tokenData.value = parsed
          console.log('✅ Token cargado desde auth_token:', parsed.accessToken.substring(0, 10) + '...')
          return
        }
      } catch (e) {
        console.log('⚠️ auth_token no es JSON, usando como string')
        tokenData.value = { accessToken: storedAuthToken }
        return
      }
    }

    // SEGUNDO: Buscar en token (formato viejo)
    const storedToken = localStorage.getItem('token')
    console.log('🔍 token encontrado:', storedToken ? storedToken.substring(0, 10) + '...' : 'null')
    
    if (storedToken) {
      console.log('🔄 Migrando token de formato antiguo...')
      tokenData.value = { accessToken: storedToken }
      // Actualizar al nuevo formato
      localStorage.setItem('auth_token', JSON.stringify(tokenData.value))
      console.log('✅ Token migrado exitosamente')
      return
    }

    console.log('❌ No se encontró token en localStorage')
  }

  const clearToken = () => {
    tokenData.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('token')
  }

  const accessToken = computed(() => tokenData.value?.accessToken || '')
  const isAuthenticated = computed(() => !!tokenData.value?.accessToken)

  // Cargar inmediatamente al importar
  loadTokenFromStorage()

  return {
    accessToken,
    isAuthenticated,
    setToken,
    clearToken,
    tokenData: computed(() => tokenData.value)
  }
}
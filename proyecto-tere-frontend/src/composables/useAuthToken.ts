// composables/useAuthToken.ts
import { ref, computed } from 'vue'

/**
 * Solo manejamos un accessToken de Sanctum.
 * No hay refresh ni expiración automática.
 */
interface TokenData {
  accessToken: string
}

export const useAuthToken = () => {
  // Estado reactivo del token
  const tokenData = ref<TokenData | null>(null)

  // Guardar token (ej: tras login Google o registro)
  const setToken = (accessToken: string) => {
    tokenData.value = { accessToken }
    localStorage.setItem('auth_token', JSON.stringify(tokenData.value))
  }

  // Cargar token desde localStorage (persistencia entre recargas)
   const loadTokenFromStorage = () => {
      const stored = localStorage.getItem('auth_token')
      if (stored) {
        try {
          const parsed = JSON.parse(stored)
          if (parsed && parsed.accessToken) {
            tokenData.value = parsed
          }
        } catch (e) {
          // Si viene viejo como string plano → migramos
          tokenData.value = { accessToken: stored }
          localStorage.setItem('auth_token', JSON.stringify(tokenData.value))
        }
      }
    }


  // Borrar token (logout)
  const clearToken = () => {
    tokenData.value = null
    localStorage.removeItem('auth_token')
  }

  // Computed helpers
  const accessToken = computed(() => tokenData.value?.accessToken || '')
  const isAuthenticated = computed(() => !!tokenData.value?.accessToken)

  // Autocargar al iniciar
  loadTokenFromStorage()

  return {
    accessToken,
    isAuthenticated,
    setToken,
    clearToken,
    tokenData: computed(() => tokenData.value) // solo lectura
  }
}

// composables/useAuthToken.ts
import { ref, computed } from 'vue'

export const useAuthToken = () => {
  const tokenData = ref<{ accessToken: string } | null>(null)

  const setToken = (accessToken: string) => {
    tokenData.value = { accessToken }
    localStorage.setItem('auth_token', JSON.stringify(tokenData.value))
    localStorage.setItem('token', accessToken)
  }

  const loadTokenFromStorage = () => {
    const storedAuthToken = localStorage.getItem('auth_token')
    
    if (storedAuthToken) {
      try {
        const parsed = JSON.parse(storedAuthToken)
        if (parsed && parsed.accessToken) {
          tokenData.value = parsed
          return
        }
      } catch (e) {
        tokenData.value = { accessToken: storedAuthToken }
        return
      }
    }

    const storedToken = localStorage.getItem('token')
    if (storedToken) {
      tokenData.value = { accessToken: storedToken }
      localStorage.setItem('auth_token', JSON.stringify(tokenData.value))
      return
    }

    tokenData.value = null
  }

  const clearToken = () => {
    tokenData.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('token')
  }

  const accessToken = computed(() => tokenData.value?.accessToken || '')
  const isAuthenticated = computed(() => !!tokenData.value?.accessToken)

  loadTokenFromStorage()

  return {
    accessToken,
    isAuthenticated,
    setToken,
    clearToken,
    loadTokenFromStorage
  }
}
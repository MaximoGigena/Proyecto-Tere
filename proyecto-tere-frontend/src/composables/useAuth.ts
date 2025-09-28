// composables/useAuth.ts
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthToken } from './useAuthToken'

export const useAuth = () => {
  const router = useRouter()
  const { setToken, clearToken, accessToken, isAuthenticated } = useAuthToken()
  const user = ref(null)
  const loading = ref(false)

  // Procesar token desde URL (fragment identifier)
  const processTokenFromUrl = async () => {
    const hash = window.location.hash.substring(1)
    const params = new URLSearchParams(hash)
    
    const token = params.get('token')
    const userId = params.get('user_id')

    if (token && userId) {
      try {
        setToken(token)
        
        // Limpiar la URL
        window.history.replaceState({}, document.title, window.location.pathname)
        
        // Obtener información del usuario
        await fetchUser(userId)
        
        return true
      } catch (error) {
        console.error('Error procesando token:', error)
        clearToken()
        return false
      }
    }
    return false
  }

  // Obtener información del usuario
  const fetchUser = async (userId?: string) => {
    try {
      loading.value = true
      
      const id = userId || user.value?.id
      if (!id) throw new Error('No user ID available')

      const response = await axios.get(`http://localhost:8000/api/users/${id}`, {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Accept': 'application/json'
        }
      })

      user.value = response.data
      return response.data
    } catch (error) {
      console.error('Error fetching user:', error)
      clearToken()
      throw error
    } finally {
      loading.value = false
    }
  }

  // Verificar autenticación al iniciar la app
  const checkAuth = async () => {
    if (isAuthenticated.value) {
      try {
        await fetchUser()
        return true
      } catch (error) {
        return false
      }
    }
    return false
  }

  // Logout
  const logout = () => {
    clearToken()
    user.value = null
    router.push('/login')
  }

  return {
    user,
    loading,
    isAuthenticated,
    accessToken,
    processTokenFromUrl,
    fetchUser,
    checkAuth,
    logout
  }
}
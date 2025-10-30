// composables/useAuth.ts
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios, { AxiosError } from 'axios'
import { useAuthToken } from './useAuthToken.js'

// ✅ Interfaces accesibles fuera de la función
export interface AuthUser {
  id: string
  email: string
  userable_type: string
  userable_id: string
  userable: any
  nombre?: string
  estado?: string
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface LoginResponse {
  success: boolean
  message?: string
}

export const useAuth = () => {
  const router = useRouter()
  const { setToken, clearToken, accessToken, isAuthenticated } = useAuthToken()
  const user = ref<AuthUser | null>(null)
  const loading = ref(false)

  // Procesar token desde URL (fragment identifier)
  const processTokenFromUrl = async (): Promise<boolean> => {
    const hash = window.location.hash.substring(1)
    const params = new URLSearchParams(hash)

    const token = params.get('token')
    const userId = params.get('user_id')

    if (token && userId) {
      try {
        setToken(token)
        window.history.replaceState({}, document.title, window.location.pathname)
        await fetchUser()
        return true
      } catch (error) {
        console.error('Error procesando token:', error)
        clearToken()
        return false
      }
    }
    return false
  }

  // Obtener información del usuario autenticado
  const fetchUser = async (): Promise<AuthUser | null> => {
    try {
      loading.value = true

      const response = await axios.get('/api/user', {
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
          Accept: 'application/json',
        },
      })

      user.value = response.data
      return response.data
    } catch (error) {
      console.error('Error fetching user:', error)

      if (error instanceof AxiosError && error.response?.status === 404) {
        console.log('Endpoint /api/user no encontrado, intentando /api/check-auth')
        try {
          const altResponse = await axios.get('/api/check-auth', {
            headers: {
              Authorization: `Bearer ${accessToken.value}`,
              Accept: 'application/json',
            },
          })
          user.value = altResponse.data
          return altResponse.data
        } catch (altError) {
          console.error('Error en endpoint alternativo:', altError)
        }
      }

      clearToken()
      throw error
    } finally {
      loading.value = false
    }
  }

  // Helpers de tipo de usuario
  const isUsuario = (): boolean => user.value?.userable_type === 'App\\Models\\Usuario'
  const isVeterinario = (): boolean => user.value?.userable_type === 'App\\Models\\Veterinario'
  const isAdministrador = (): boolean => user.value?.userable_type === 'App\\Models\\Administrador'

  // Verificar si el usuario está aprobado
  const isAprobado = (): boolean => {
    if (!user.value) return false

    if (isVeterinario() && user.value.userable) {
      return user.value.userable.estado === 'aprobado'
    }

    return user.value.estado === 'activo'
  }

  // Verificar si está pendiente
  const isPendiente = (): boolean => {
    if (!user.value) return false

    if (isVeterinario() && user.value.userable) {
      return user.value.userable.estado === 'pendiente'
    }

    return user.value.estado === 'pendiente'
  }

  // Verificar autenticación al iniciar la app
  const checkAuth = async (): Promise<boolean> => {
    if (isAuthenticated.value) {
      try {
        await fetchUser()
        return true
      } catch (error) {
        console.error('Error en checkAuth:', error)
        return false
      }
    }
    return false
  }

  // Login
  const login = async (credentials: LoginCredentials): Promise<LoginResponse> => {
    try {
      loading.value = true

      let endpoint = '/api/login'

      if (router.currentRoute.value.path.includes('/veterinario')) {
        endpoint = '/api/veterinarios/login'
      } else if (router.currentRoute.value.path.includes('/admin')) {
        endpoint = '/api/admin/login'
      }

      const response = await axios.post(endpoint, credentials)

      if (response.data.success && response.data.data.token) {
        setToken(response.data.data.token)
        await fetchUser()
        return { success: true }
      }

      return { success: false, message: 'Respuesta inesperada del servidor' }
    } catch (error) {
      console.error('Error en login:', error)

      let errorMessage = 'Error en el login'
      if (error instanceof AxiosError && error.response?.data?.message) {
        errorMessage = error.response.data.message
      } else if (error instanceof Error) {
        errorMessage = error.message
      }

      return {
        success: false,
        message: errorMessage,
      }
    } finally {
      loading.value = false
    }
  }

  // Logout
  const logout = (): void => {
    clearToken()
    user.value = null
    if (isVeterinario()) {
      router.push('/veterinario/login')
    } else if (isAdministrador()) {
      router.push('/admin/login')
    } else {
      router.push('/login')
    }
  }

  // Redirigir según rol
  const redirectByRole = (): void => {
    if (!user.value) return

    if (isAdministrador()) {
      router.push('/admin/dashboard')
    } else if (isVeterinario()) {
      if (isAprobado()) {
        router.push('/veterinario/dashboard')
      } else if (isPendiente()) {
        router.push('/veterinario/pendiente')
      }
    } else if (isUsuario()) {
      router.push('/dashboard')
    }
  }

  return {
    user,
    loading,
    isAuthenticated,
    accessToken,

    isUsuario,
    isVeterinario,
    isAdministrador,
    isAprobado,
    isPendiente,

    processTokenFromUrl,
    fetchUser,
    checkAuth,
    login,
    logout,
    redirectByRole,
  }
}

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

  // Función para verificar si es error de suspensión
  const isSuspensionError = (error: any): boolean => {
    if (!error.response || error.response.status !== 403) return false
    
    const data = error.response.data
    return (
      data.code === 'ACCOUNT_SUSPENDED' ||
      (data.message && data.message.includes('suspend')) ||
      data.redirect_to === '/cuenta-suspendida' ||
      data.data?.estado === 'suspendido'
    )
  }

  // Función para manejar error de suspensión
  const handleSuspensionError = (error: any): void => {
    console.log('🚨 Handle suspension error called')
    
    const data = error.response?.data
    
    // Guardar datos de suspensión en localStorage
    if (data?.data) {
      localStorage.setItem('suspension_data', JSON.stringify(data.data))
    } else {
      localStorage.setItem('suspension_data', JSON.stringify({
        razon: data?.message || 'Cuenta suspendida',
        estado: 'suspendido',
        fecha_fin: data?.data?.fecha_fin || null,
        es_permanente: data?.data?.es_permanente || false,
        puede_apelar: data?.data?.puede_apelar || false
      }))
    }
    
    // Redirigir a cuenta suspendida si no estamos ya allí
    if (router.currentRoute.value.path !== '/cuenta-suspendida') {
      console.log('🔄 Redirigiendo a cuenta-suspendida desde useAuth')
      router.replace('/cuenta-suspendida')
    }
  }

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
        
        // Verificar si es error de suspensión
        if (isSuspensionError(error)) {
          handleSuspensionError(error)
          return false // No limpiar token en caso de suspensión
        }
        
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

      // Verificar si es error de suspensión
      if (isSuspensionError(error)) {
        console.log('📋 Suspensión detectada en fetchUser')
        handleSuspensionError(error)
        return null // No limpiar token en caso de suspensión
      }

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
          
          // Verificar si es error de suspensión en endpoint alternativo
          if (isSuspensionError(altError)) {
            handleSuspensionError(altError)
            return null
          }
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

  // Verificar si está suspendido
  const isSuspendido = (): boolean => {
    if (!user.value) return false
    
    // Verificar en localStorage primero
    const suspensionData = localStorage.getItem('suspension_data')
    if (suspensionData) {
      try {
        const data = JSON.parse(suspensionData)
        if (data.estado === 'suspendido' || data.esta_suspendido) {
          return true
        }
      } catch (e) {
        console.error('Error parsing suspension data:', e)
      }
    }
    
    // Verificar en user data
    return user.value.estado === 'suspendido' || user.value.estado === 'bloqueado'
  }

  // Verificar autenticación al iniciar la app
  const checkAuth = async (): Promise<boolean> => {
    // Primero verificar si hay datos de suspensión
    const suspensionData = localStorage.getItem('suspension_data')
    if (suspensionData) {
      try {
        const data = JSON.parse(suspensionData)
        if (data.estado === 'suspendido' && router.currentRoute.value.path !== '/cuenta-suspendida') {
          console.log('🔄 checkAuth detecta usuario suspendido, redirigiendo...')
          router.replace('/cuenta-suspendida')
          return false
        }
      } catch (e) {
        console.error('Error parsing suspension data in checkAuth:', e)
      }
    }
    
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
        
        // Intentar obtener usuario
        try {
          await fetchUser()
          return { success: true }
        } catch (error) {
          // Si fetchUser falla por suspensión, manejar sin limpiar token
          if (isSuspensionError(error)) {
            return { 
              success: false, 
              message: 'Tu cuenta está suspendida. Redirigiendo...' 
            }
          }
          throw error
        }
      }

      return { success: false, message: 'Respuesta inesperada del servidor' }
    } catch (error) {
      console.error('Error en login:', error)
      
      // Verificar si es error de suspensión
      if (isSuspensionError(error)) {
        handleSuspensionError(error)
        return { 
          success: false, 
          message: 'Tu cuenta está suspendida' 
        }
      }

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
    // Limpiar datos de suspensión también
    localStorage.removeItem('suspension_data')
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

    // Verificar si está suspendido antes de redirigir
    if (isSuspendido()) {
      router.push('/cuenta-suspendida')
      return
    }

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

  // Método para verificar suspensión y redirigir si es necesario
  const checkAndRedirectIfSuspended = (): boolean => {
    if (isSuspendido() && router.currentRoute.value.path !== '/cuenta-suspendida') {
      router.replace('/cuenta-suspendida')
      return true
    }
    return false
  }

  const obtenerUbicacionUsuario = async () => {
    try {
      if (!isAuthenticated.value) {
        throw new Error('Usuario no autenticado')
      }

      const response = await axios.get('/api/user/location', {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Content-Type': 'application/json'
        }
      })

      return response.data.data || response.data
    } catch (error) {
      console.error('Error obteniendo ubicación del usuario:', error)
      return null
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
    isSuspendido, // Nuevo método exportado

    processTokenFromUrl,
    fetchUser,
    checkAuth,
    login,
    logout,
    redirectByRole,
    checkAndRedirectIfSuspended, // Nuevo método exportado
    handleSuspensionError, // Exportar para uso en otros lugares

    obtenerUbicacionUsuario
  }
}

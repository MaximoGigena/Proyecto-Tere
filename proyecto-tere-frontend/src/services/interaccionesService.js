// @/services/interaccionesService.js
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'

export const useInteracciones = () => {
  const { accessToken } = useAuth()

  const registrarInteraccion = async (data) => {
    try {
      const response = await axios.post('/api/adopciones/registrar-interaccion', data, {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${accessToken.value}`
        }
      })
      
      return response.data
    } catch (error) {
      console.error('Error registrando interacción:', error)
      throw error
    }
  }

  return {
    registrarInteraccion
  }
}
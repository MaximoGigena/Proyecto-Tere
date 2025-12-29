import { ref } from 'vue'
import axios from 'axios'
import { useAuth } from './useAuth'

export function useInteracciones() {
  const { accessToken } = useAuth()
  const procesando = ref(false)
  const error = ref(null)

  const registrarInteraccion = async (data) => {
    try {
      procesando.value = true
      error.value = null
      
      const response = await axios.post('/api/adopciones/registrar-interaccion', {
        mascota_id: data.mascota_id,
        oferta_id: data.oferta_id,
        tipo_interaccion: data.tipo_interaccion
      }, {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Content-Type': 'application/json'
        }
      })

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error registrando interacción:', err)
      throw err
    } finally {
      procesando.value = false
    }
  }

  return {
    registrarInteraccion,
    procesando,
    error
  }
}
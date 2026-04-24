// composables/useUsuarioModificacion.ts
import { ref, reactive, type Ref } from 'vue'
import axios, { type AxiosError } from 'axios'
import { useAuthToken } from './useAuthToken'

// Interfaces para tipado fuerte
export interface FechaNacimiento {
  dia: number | null
  mes: string
  anio: number | null
}

export interface UsuarioBasico {
  nombre: string
  email: string
  edad: number | null
  fechaNacimiento: FechaNacimiento
}

export interface DatosOpcionales {
  ocupacion: string
  tipoVivienda: string
  experienciaMascotas: string
  conviveConNiños: string
  conviveConMascotas: string
  descripcion: string
}

export interface DatosContacto {
  dni: string
  telefono_contacto: string
  email_contacto: string
  nombre_completo: string
}

export interface UsuarioModificacion extends UsuarioBasico, DatosOpcionales, DatosContacto {
  foto_perfil?: string | null
  user_id?: number | null
}

export interface DatosBasicosActualizar {
  nombre: string
  email: string
  edad?: number | null
  fechaNacimiento?: FechaNacimiento
  fotoPerfil?: File | null
  password?: string
}

export interface ApiResponse<T = any> {
  success: boolean
  data?: T
  usuario?: T  // La respuesta puede tener usuario directamente
  message?: string
  error?: string
  debug_info?: any
}

// Interfaz para la respuesta del controlador show()
export interface UsuarioShowResponse {
  id: number
  user_id: number
  nombre: string
  email: string
  edad: number | null
  ubicacion?: string | null
  tiempo_registro?: string
  dias_registrado?: number
  created_at?: string
  foto_principal?: string | null
  caracteristicas?: {
    ocupacion?: string
    tipoVivienda?: string
    experiencia?: string
    convivenciaNiños?: string
    convivenciaMascotas?: string
    descripción?: string
  } | null
  contacto?: {
    dni?: string
    telefono?: string
    email?: string
    nombre_completo?: string
  } | null
  fotos?: Array<{
    ruta_foto: string
    url: string
    es_principal: boolean
  }>
}

export function useUsuarioModificacion() {
  const { accessToken, isAuthenticated } = useAuthToken()
  
  // Refs con tipos explícitos
  const usuarioId = ref<number | null>(null) // ID del Usuario (tabla usuarios)
  const userId = ref<number | null>(null) // ID del User (tabla users)
  const cargando = ref<boolean>(false)
  const error = ref<string | null>(null)

  // Estado del usuario para modificación con tipado fuerte
  const usuarioModificacion = reactive<UsuarioModificacion>({
    nombre: '',
    email: '',
    edad: null,
    fechaNacimiento: {
      dia: null,
      mes: '',
      anio: null,
    },
    // Datos opcionales
    ocupacion: '',
    tipoVivienda: '',
    experienciaMascotas: '',
    conviveConNiños: '',
    conviveConMascotas: '',
    descripcion: '',
    // Datos de contacto
    dni: '',
    telefono_contacto: '',
    email_contacto: '',
    nombre_completo: '',
    // Foto de perfil
    foto_perfil: null,
    user_id: null
  })

  /**
   * Cargar datos del usuario para modificación
   * @param id - ID del User (tabla users) que viene de la URL
   * @returns Promise<boolean> - True si se cargó correctamente
   */
  const cargarDatosUsuario = async (id: number): Promise<boolean> => {
    cargando.value = true
    error.value = null
    
    try {
      console.log('📥 Cargando usuario con User ID:', id)
      console.log('🔑 Token usado:', accessToken.value?.substring(0, 30) + '...')
      
      const response = await axios.get<ApiResponse<UsuarioShowResponse>>(`/api/usuarios/${id}`, {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Accept': 'application/json'
        }
      })

      console.log('📥 Respuesta completa del servidor:', response.data)

      // ✅ CORRECCIÓN: La respuesta tiene 'usuario' directamente en la raíz
      // No está dentro de 'data'
      if (response.data.success && response.data.usuario) {
        const datos = response.data.usuario as UsuarioShowResponse
        
        console.log('✅ Datos del usuario extraídos:', {
          id: datos.id,
          user_id: datos.user_id,
          nombre: datos.nombre,
          email: datos.email,
          tiene_caracteristicas: !!datos.caracteristicas,
          tiene_contacto: !!datos.contacto
        })
        
        // Guardar ambos IDs
        userId.value = datos.user_id || id
        usuarioId.value = datos.id
        
        // Cargar datos básicos
        usuarioModificacion.nombre = datos.nombre || ''
        usuarioModificacion.email = datos.email || ''
        usuarioModificacion.edad = datos.edad || null
        usuarioModificacion.user_id = datos.user_id
        
        // Guardar foto de perfil si existe
        if (datos.foto_principal) {
          usuarioModificacion.foto_perfil = datos.foto_principal
          console.log('📸 Foto de perfil cargada:', datos.foto_principal)
        }
        
        // Nota: La fecha de nacimiento no viene en esta respuesta
        // Si necesitas fecha de nacimiento, tendrías que agregarla al controlador
        
        // Cargar datos opcionales desde características
        if (datos.caracteristicas) {
          console.log('📋 Características encontradas:', datos.caracteristicas)
          usuarioModificacion.ocupacion = datos.caracteristicas.ocupacion || ''
          usuarioModificacion.tipoVivienda = datos.caracteristicas.tipoVivienda || ''
          usuarioModificacion.experienciaMascotas = datos.caracteristicas.experiencia || ''
          usuarioModificacion.conviveConNiños = datos.caracteristicas.convivenciaNiños || ''
          usuarioModificacion.conviveConMascotas = datos.caracteristicas.convivenciaMascotas || ''
          usuarioModificacion.descripcion = datos.caracteristicas.descripción || ''
        } else {
          console.log('⚠️ No hay características para este usuario')
        }
        
        // Cargar datos de contacto
        if (datos.contacto) {
          console.log('📞 Contacto encontrado:', datos.contacto)
          usuarioModificacion.dni = datos.contacto.dni || ''
          usuarioModificacion.telefono_contacto = datos.contacto.telefono || ''
          usuarioModificacion.email_contacto = datos.contacto.email || ''
          usuarioModificacion.nombre_completo = datos.contacto.nombre_completo || ''
        } else {
          console.log('⚠️ No hay datos de contacto para este usuario')
        }
        
        console.log('✅ Datos cargados exitosamente en usuarioModificacion:', {
          usuarioId: usuarioId.value,
          userId: userId.value,
          nombre: usuarioModificacion.nombre,
          email: usuarioModificacion.email,
          ocupacion: usuarioModificacion.ocupacion,
          telefono: usuarioModificacion.telefono_contacto
        })
        
        return true
      } else {
        console.error('❌ Respuesta sin datos válidos:', response.data)
        error.value = response.data.message || 'No se encontraron datos del usuario'
        return false
      }
    } catch (err) {
      console.error('❌ Error al cargar datos del usuario:', err)
      const axiosError = err as AxiosError<ApiResponse>
      error.value = axiosError.response?.data?.message || 'Error al cargar los datos del usuario'
      console.error('❌ Detalle del error:', axiosError.response?.data)
      return false
    } finally {
      cargando.value = false
    }
  }

  /**
   * Actualizar datos básicos del usuario
   * @param datosBasicos - Datos básicos a actualizar
   * @returns Promise<boolean> - True si se actualizó correctamente
   */
  // composables/useUsuarioModificacion.ts

   const actualizarDatosBasicos = async (datosBasicos: DatosBasicosActualizar): Promise<boolean> => {
        if (!usuarioId.value) {
            throw new Error('No hay usuario seleccionado para actualizar')
        }
        
        try {
            console.log('🔧 Actualizando datos básicos del Usuario ID:', usuarioId.value)
            
            const formData = new FormData()
            
            // ✅ Agregar _method para simular PUT
            formData.append('_method', 'PUT')
            formData.append('nombre', datosBasicos.nombre)
            
            if (datosBasicos.edad) {
                formData.append('edad', datosBasicos.edad.toString())
            }
            
            if (datosBasicos.fechaNacimiento) {
                const { anio, mes, dia } = datosBasicos.fechaNacimiento
                if (anio && mes && dia) {
                    const fechaCompleta = `${anio}-${mes}-${dia}`
                    formData.append('fecha_nacimiento', fechaCompleta)
                }
            }
            
            if (datosBasicos.fotoPerfil) {
                formData.append('foto_perfil', datosBasicos.fotoPerfil)
            }
            
            if (datosBasicos.password) {
                formData.append('password', datosBasicos.password)
            }

            // ✅ Usar POST en lugar de PUT
            const response = await axios.post<ApiResponse>(
                `/api/usuarios/${usuarioId.value}`,
                formData,
                {
                    headers: {
                        'Authorization': `Bearer ${accessToken.value}`,
                        'Accept': 'application/json',
                        'Content-Type': 'multipart/form-data'
                    }
                }
            )

            console.log('✅ Respuesta actualización básicos:', response.data)
            return response.data.success
            
        } catch (err) {
            console.error('❌ Error al actualizar datos básicos:', err)
            throw err
        }
    }

  /**
   * Actualizar datos opcionales del usuario
   * @param datos - Datos opcionales a actualizar
   * @returns Promise<boolean> - True si se actualizó correctamente
   */
  const actualizarDatosOpcionales = async (datos: Partial<DatosOpcionales>): Promise<boolean> => {
    // Usar usuarioId (ID del Usuario)
    if (!usuarioId.value) {
      throw new Error('No hay usuario seleccionado para actualizar')
    }
    
    try {
      console.log('🔧 Actualizando datos opcionales del Usuario ID:', usuarioId.value)
      console.log('🔧 Datos opcionales:', datos)
      
      const response = await axios.post<ApiResponse>(
        `/api/actualizar-datos-opcionales`,
        {
            usuario_id: usuarioId.value, // 👈 AÑADIR ESTO
            ...datos
        },
        {
          headers: {
            'Authorization': `Bearer ${accessToken.value}`,
            'Accept': 'application/json'
          }
        }
      )
      
      console.log('✅ Respuesta datos opcionales:', response.data)
      return response.data.success
    } catch (err) {
      console.error('❌ Error al actualizar datos opcionales:', err)
      throw err
    }
  }

  /**
   * Actualizar datos de contacto del usuario
   * @param datos - Datos de contacto a actualizar
   * @returns Promise<boolean> - True si se actualizó correctamente
   */
  const actualizarDatosContacto = async (datos: Partial<DatosContacto>): Promise<boolean> => {
    // Usar usuarioId (ID del Usuario)
    if (!usuarioId.value) {
      throw new Error('No hay usuario seleccionado para actualizar')
    }
    
    try {
      console.log('🔧 Actualizando datos de contacto del Usuario ID:', usuarioId.value)
      console.log('🔧 Datos de contacto:', datos)
      
      const response = await axios.post<ApiResponse>(
        `/api/actualizar-datos-contacto`,
        {
            usuario_id: usuarioId.value, // 👈 AÑADIR ESTO
            ...datos
        },
        {
          headers: {
            'Authorization': `Bearer ${accessToken.value}`,
            'Accept': 'application/json'
          }
        }
      )
      
      console.log('✅ Respuesta datos de contacto:', response.data)
      return response.data.success
    } catch (err) {
      console.error('❌ Error al actualizar datos de contacto:', err)
      throw err
    }
  }

  /**
   * Limpiar todos los datos del formulario
   */
  const limpiarDatos = (): void => {
    usuarioModificacion.nombre = ''
    usuarioModificacion.email = ''
    usuarioModificacion.edad = null
    usuarioModificacion.fechaNacimiento = {
      dia: null,
      mes: '',
      anio: null,
    }
    usuarioModificacion.ocupacion = ''
    usuarioModificacion.tipoVivienda = ''
    usuarioModificacion.experienciaMascotas = ''
    usuarioModificacion.conviveConNiños = ''
    usuarioModificacion.conviveConMascotas = ''
    usuarioModificacion.descripcion = ''
    usuarioModificacion.dni = ''
    usuarioModificacion.telefono_contacto = ''
    usuarioModificacion.email_contacto = ''
    usuarioModificacion.nombre_completo = ''
    usuarioModificacion.foto_perfil = null
    usuarioModificacion.user_id = null
    usuarioId.value = null
    userId.value = null
  }

  /**
   * Verificar si hay datos cargados
   */
  const tieneDatosCargados = (): boolean => {
    return usuarioId.value !== null
  }

  /**
   * Obtener el ID del usuario para usar en las URLs
   */
  const getUsuarioId = (): number | null => {
    return usuarioId.value
  }

  /**
   * Obtener el ID del User para autenticación
   */
  const getUserId = (): number | null => {
    return userId.value
  }

  /**
   * Obtener resumen de datos del usuario (útil para debugging)
   */
  const obtenerResumenDatos = (): Partial<UsuarioModificacion> => {
    return {
      nombre: usuarioModificacion.nombre,
      email: usuarioModificacion.email,
      ocupacion: usuarioModificacion.ocupacion,
      telefono_contacto: usuarioModificacion.telefono_contacto
    }
  }

  return {
    // Refs
    usuarioId: readonly(usuarioId) as Ref<number | null>,
    userId: readonly(userId) as Ref<number | null>,
    cargando: readonly(cargando) as Ref<boolean>,
    error: readonly(error) as Ref<string | null>,
    
    // Estado reactivo
    usuarioModificacion,
    
    // Métodos
    cargarDatosUsuario,
    actualizarDatosBasicos,
    actualizarDatosOpcionales,
    actualizarDatosContacto,
    limpiarDatos,
    tieneDatosCargados,
    getUsuarioId,
    getUserId,
    obtenerResumenDatos
  }
}

// Helper para crear una función readonly (útil para evitar mutaciones accidentales)
function readonly<T>(ref: Ref<T>): Ref<T> {
  return ref as Ref<T>
}
<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div v-if="loading" class="text-center">
      <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto"></div>
      <p class="mt-4 text-gray-600">Verificando estado de la cuenta...</p>
    </div>
    
    <div v-else-if="error" class="text-center">
      <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center mx-auto">
        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.962-.833-2.732 0L4.206 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-gray-800 mt-6">Error al cargar</h1>
      <p class="text-gray-600 mt-2">{{ error }}</p>
      <button @click="cerrarSesion" class="mt-6 px-6 py-2 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition">
        Cerrar sesión
      </button>
    </div>
    
    <div v-else class="max-w-md w-full bg-white rounded-2xl shadow-xl p-6 text-center space-y-6">
      <!-- Ícono -->
      <div class="flex justify-center">
        <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center">
          <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728" />
          </svg>
        </div>
      </div>

      <!-- Título -->
      <h1 class="text-2xl font-bold text-gray-800">
        Cuenta suspendida
      </h1>

      <!-- Mensaje principal -->
      <p class="text-gray-600">
        Tu cuenta ha sido suspendida por incumplir las normas de uso de la plataforma.
      </p>

      <!-- Detalles de la suspensión -->
      <div class="bg-gray-50 rounded-xl p-4 text-left space-y-3">
        <!-- Tipo de sanción -->
        <div class="flex items-start">
          <svg class="w-5 h-5 text-gray-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <div>
            <span class="font-semibold text-gray-700">Tipo de sanción:</span>
            <span :class="getTipoColor(sancion.tipo)" class="ml-2 font-semibold">
              {{ formatTipoSancion(sancion.tipo) }}
            </span>
          </div>
        </div>

        <!-- Nivel de gravedad -->
        <div class="flex items-start">
          <svg class="w-5 h-5 text-gray-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.962-.833-2.732 0L4.206 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <div>
            <span class="font-semibold text-gray-700">Nivel de gravedad:</span>
            <span :class="getNivelColor(sancion.nivel)" class="ml-2 font-semibold">
              {{ sancion.nivel ? formatNivel(sancion.nivel) : 'No especificado' }}
            </span>
          </div>
        </div>

        <!-- Motivo -->
        <div class="flex items-start">
          <svg class="w-5 h-5 text-gray-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <span class="font-semibold text-gray-700">Motivo:</span>
            <p class="mt-1 text-gray-600">{{ sancion.razon || 'Infracción a los términos y condiciones.' }}</p>
          </div>
        </div>

        <!-- Período de suspensión -->
          <div class="flex items-start">
            <svg class="w-5 h-5 text-gray-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <span class="font-semibold text-gray-700">Período de suspensión:</span>
              <div class="mt-1 space-y-2">
                <div>
                  <p class="text-gray-600">
                    <span class="font-medium">Inicio:</span> 
                    {{ sancion.fecha_inicio_formateada }}
                  </p>
                </div>
                
                <div v-if="sancion.es_permanente">
                  <p class="text-red-600 font-semibold">
                    <span class="font-medium">Fin:</span> 
                    Suspensión permanente
                  </p>
                </div>
                <div v-else-if="sancion.fecha_fin_formateada">
                  <p class="text-gray-600">
                    <span class="font-medium">Fin estimado:</span> 
                    {{ sancion.fecha_fin_formateada }}
                  </p>
                </div>
                
                <div v-if="sancion.duracion_dias">
                  <p class="text-gray-600">
                    <span class="font-medium">Duración total:</span> 
                    {{ sancion.duracion_dias }} día{{ sancion.duracion_dias > 1 ? 's' : '' }}
                  </p>
                </div>
                
                <div v-if="sancion.dias_restantes !== null">
                  <p class="text-gray-600">
                    <span class="font-medium">Tiempo restante:</span> 
                    <span :class="[
                      'ml-2 font-semibold',
                      sancion.dias_restantes === 0 ? 'text-green-600' :
                      sancion.dias_restantes <= 3 ? 'text-amber-600' :
                      'text-blue-600'
                    ]">
                      {{ sancion.dias_restantes }} día{{ sancion.dias_restantes !== 1 ? 's' : '' }}
                    </span>
                  </p>
                  <div v-if="sancion.dias_restantes > 0" class="mt-1">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                      <div :class="[
                        'h-2.5 rounded-full',
                        sancion.dias_restantes <= 3 ? 'bg-amber-500' : 'bg-blue-600'
                      ]" :style="{ width: calcularPorcentajeRestante() + '%' }"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                      {{ calcularPorcentajeRestante().toFixed(0) }}% del tiempo completado
                    </p>
                  </div>
                  <div v-else-if="sancion.dias_restantes === 0" class="mt-2">
                    <p class="text-green-600 font-semibold">
                      ✅ Tu suspensión ha finalizado. Refresca la página o contacta a soporte.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

        <!-- Estado -->
        <div class="flex items-start">
          <svg class="w-5 h-5 text-gray-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <span class="font-semibold text-gray-700">Estado:</span>
            <span :class="getEstadoColor(sancion.estado)" class="ml-2 font-semibold">
              {{ sancion.estado ? formatEstado(sancion.estado) : 'ACTIVA' }}
            </span>
          </div>
        </div>

        <!-- Restricciones -->
        <div class="flex items-start" v-if="sancion.restricciones && sancion.restricciones.length > 0">
          <svg class="w-5 h-5 text-gray-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
          </svg>
          <div>
            <span class="font-semibold text-gray-700">Restricciones aplicadas:</span>
            <ul class="mt-1 text-gray-600 list-disc list-inside">
              <li v-for="(restriccion, index) in sancion.restricciones_formateadas" :key="index">
                {{ restriccion }}
              </li>
            </ul>
          </div>
        </div>

        <!-- Descripción -->
        <div class="flex items-start" v-if="sancion.descripcion">
          <svg class="w-5 h-5 text-gray-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <span class="font-semibold text-gray-700">Descripción:</span>
            <p class="mt-1 text-gray-600">{{ sancion.descripcion }}</p>
          </div>
        </div>

        <!-- ID de sanción -->
        <div class="pt-2 border-t border-gray-200">
          <p class="text-xs text-gray-500">
            ID de sanción: {{ sancion.id || 'N/A' }}
          </p>
        </div>
      </div>

      <!-- Advertencia -->
      <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
        <div class="flex">
          <svg class="w-5 h-5 text-amber-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.962-.833-2.732 0L4.206 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <p class="text-sm text-amber-700">
            <span class="font-semibold">Importante:</span> 
            Durante este período no podrás acceder a las funciones de la aplicación. 
            {{ sancion.es_permanente ? 'Esta suspensión es permanente.' : 'Para cualquier consulta, contacta al soporte.' }}
          </p>
        </div>
      </div>

      <!-- Acciones -->
      <div class="space-y-3">
        <button v-if="sancion.puede_apelar" @click="contactarSoporte" class="w-full py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition flex items-center justify-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          Contactar soporte o apelar
        </button>

        <button @click="cerrarSesion" class="w-full py-2 rounded-xl bg-gray-200 text-gray-700 font-semibold hover:bg-gray-300 transition flex items-center justify-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Cerrar sesión
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const loading = ref(true)
const error = ref<string | null>(null)
const sancion = ref({
  id: '',
  tipo: '',
  nivel: '',
  razon: '',
  descripcion: '',
  duracion_dias: null as number | null,
  fecha_inicio: '',
  fecha_inicio_formateada: '',
  fecha_fin: '',
  fecha_fin_formateada: '',
  es_permanente: false,
  puede_apelar: false,
  estado: '',
  restricciones: [] as string[],
  restricciones_formateadas: [] as string[],
  dias_restantes: null as number | null
})

// Función para formatear tipo de sanción
const formatTipoSancion = (tipo: string): string => {
  const tipos: Record<string, string> = {
    'ADVERTENCIA': 'Advertencia',
    'SUSPENSION_TEMPORAL': 'Suspensión Temporal',
    'SUSPENSION_INDEFINIDA': 'Suspensión Indefinida',
    'BLOQUEO_TEMPORAL': 'Bloqueo Temporal',
    'BLOQUEO_PERMANENTE': 'Bloqueo Permanente',
    'LIMITACION_FUNCIONES': 'Limitación de Funciones'
  }
  return tipos[tipo] || tipo
}

// Función para formatear nivel
const formatNivel = (nivel: string): string => {
  const niveles: Record<string, string> = {
    'LEVE': 'Leve',
    'MODERADO': 'Moderado',
    'GRAVE': 'Grave',
    'MUY_GRAVE': 'Muy Grave'
  }
  return niveles[nivel] || nivel
}

// Función para formatear estado
const formatEstado = (estado: string): string => {
  const estados: Record<string, string> = {
    'ACTIVA': 'Activa',
    'CUMPLIDA': 'Cumplida',
    'REVOCADA': 'Revocada',
    'EXPIRADA': 'Expirada'
  }
  return estados[estado] || estado
}

// Función para formatear restricciones
const formatRestricciones = (restricciones: string[]): string[] => {
  const restriccionesFormateadas: Record<string, string> = {
    'CREAR_OFERTAS': 'Crear ofertas',
    'SOLICITAR_ADOPCION': 'Solicitar adopción',
    'PUBLICAR_COMENTARIOS': 'Publicar comentarios',
    'ENVIAR_MENSAJES': 'Enviar mensajes',
    'SUBIR_MASCOTAS': 'Subir mascotas',
    'ACCESO_PLATAFORMA': 'Acceso a la plataforma'
  }
  
  return restricciones.map(r => restriccionesFormateadas[r] || r)
}

// Colores según tipo
const getTipoColor = (tipo: string): string => {
  const colores: Record<string, string> = {
    'ADVERTENCIA': 'text-amber-600',
    'SUSPENSION_TEMPORAL': 'text-orange-600',
    'SUSPENSION_INDEFINIDA': 'text-red-600',
    'BLOQUEO_TEMPORAL': 'text-red-600',
    'BLOQUEO_PERMANENTE': 'text-red-700',
    'LIMITACION_FUNCIONES': 'text-blue-600'
  }
  return colores[tipo] || 'text-gray-600'
}

// Colores según nivel
const getNivelColor = (nivel: string): string => {
  const colores: Record<string, string> = {
    'LEVE': 'text-green-600',
    'MODERADO': 'text-amber-600',
    'GRAVE': 'text-orange-600',
    'MUY_GRAVE': 'text-red-600'
  }
  return colores[nivel] || 'text-gray-600'
}

// Colores según estado
const getEstadoColor = (estado: string): string => {
  const colores: Record<string, string> = {
    'ACTIVA': 'text-red-600',
    'CUMPLIDA': 'text-green-600',
    'REVOCADA': 'text-blue-600',
    'EXPIRADA': 'text-gray-600'
  }
  return colores[estado] || 'text-gray-600'
}

// Calcular días restantes
const calcularDiasRestantes = (fechaFin: string): number | null => {
  if (!fechaFin) return null
  const hoy = new Date()
  const fin = new Date(fechaFin)
  const diffTime = fin.getTime() - hoy.getTime()
  const dias = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return dias > 0 ? dias : 0
}

// Formatear fecha completa
const formatearFechaCompleta = (fecha: string): string => {
  if (!fecha) return ''
  const fechaObj = new Date(fecha)
  return fechaObj.toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(async () => {
  try {
    console.log('🔄 Iniciando carga de datos de sanción...')
    
    // Intentar obtener datos del localStorage primero
    const storedSuspension = localStorage.getItem('suspension_data')
    if (storedSuspension) {
      console.log('📦 Datos de suspensión encontrados en localStorage:', storedSuspension)
      const suspensionData = JSON.parse(storedSuspension)
      procesarDatosSancion(suspensionData)
      localStorage.removeItem('suspension_data')
      if (sancion.value.razon) {
        console.log('✅ Datos cargados desde localStorage')
        loading.value = false
        return
      }
    }

    // Verificar si hay token
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    if (!token) {
      console.log('❌ No hay token de autenticación')
      error.value = 'No estás autenticado. Serás redirigido al login.'
      setTimeout(() => {
        cerrarSesion()
      }, 2000)
      return
    }

    console.log('🔍 Solicitando información de sanción activa...')
    
    try {
      // Verificar conexión con la API primero
      console.log('🌐 Verificando conexión con API...')
      
      const response = await api.get('/usuario/sancion-activa-detallada', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      
      console.log('📥 Respuesta de API recibida:', {
        status: response.status,
        statusText: response.statusText,
        data: response.data
      })
      
      if (response.data && response.data.success) {
        console.log('✅ Información de sanción cargada desde API:', response.data.data)
        procesarDatosSancion(response.data.data)
      } else {
        console.log('⚠️ Respuesta no exitosa:', response.data)
        error.value = response.data?.message || 'No se pudo obtener información de la sanción'
      }
    } catch (apiError: any) {
      console.error('❌ Error al obtener sanción:', {
        message: apiError.message,
        response: apiError.response,
        status: apiError.response?.status,
        data: apiError.response?.data
      })
      
      // Mostrar error específico según el tipo
      if (apiError.response) {
        if (apiError.response.status === 401) {
          error.value = 'Sesión expirada. Serás redirigido al login.'
          setTimeout(() => {
            cerrarSesion()
          }, 2000)
        } else if (apiError.response.status === 404) {
          error.value = 'No se encontraron sanciones activas para tu cuenta.'
        } else if (apiError.response.status === 403) {
          error.value = 'No tienes permisos para acceder a esta información.'
        } else {
          error.value = `Error del servidor (${apiError.response.status}): ${apiError.response.data?.message || 'Intenta más tarde'}`
        }
      } else if (apiError.request) {
        error.value = 'No se pudo conectar con el servidor. Verifica tu conexión a internet.'
      } else {
        error.value = 'Error al realizar la solicitud: ' + apiError.message
      }
    }
    
  } catch (err: any) {
    console.error('⚠️ Error general inesperado:', err)
    error.value = 'Error inesperado: ' + (err.message || 'Contacta al soporte')
  } finally {
    loading.value = false
    console.log('🏁 Carga finalizada')
  }
})

function procesarDatosSancion(data: any) {
  console.log('📊 Procesando datos de sanción:', data)
  
  if (!data) {
    console.log('❌ No hay datos para procesar')
    return
  }
  
  const fechaInicio = data.fecha_inicio ? new Date(data.fecha_inicio) : null
  const fechaFin = data.fecha_fin ? new Date(data.fecha_fin) : null
  
  sancion.value = {
    id: data.id || '',
    tipo: data.tipo || '',
    nivel: data.nivel || '',
    razon: data.razon || data.motivo || 'Infracción a los términos y condiciones.',
    descripcion: data.descripcion || '',
    duracion_dias: data.duracion_dias || null,
    fecha_inicio: data.fecha_inicio || '',
    fecha_inicio_formateada: fechaInicio ? 
      formatearFechaCompleta(data.fecha_inicio) : 'No especificada',
    fecha_fin: data.fecha_fin || '',
    fecha_fin_formateada: fechaFin ? 
      formatearFechaCompleta(data.fecha_fin) : '',
    es_permanente: data.es_permanente || data.tipo === 'BLOQUEO_PERMANENTE' || !data.fecha_fin,
    puede_apelar: data.puede_apelar || (data.tipo !== 'BLOQUEO_PERMANENTE' && data.estado === 'ACTIVA'),
    estado: data.estado || 'ACTIVA',
    restricciones: data.restricciones || [],
    restricciones_formateadas: formatRestricciones(data.restricciones || []),
    dias_restantes: data.dias_restantes !== undefined ? data.dias_restantes : 
                   (fechaFin ? calcularDiasRestantes(data.fecha_fin) : null)
  }
  
  console.log('✅ Sanción procesada:', sancion.value)
}

function cerrarSesion() {
  console.log('👋 Cerrando sesión...')
  localStorage.removeItem('token')
  localStorage.removeItem('auth_token')
  localStorage.removeItem('suspension_data')
  sessionStorage.removeItem('auth_token')
  router.replace('/')
}

function contactarSoporte() {
  console.log('📞 Contactando soporte...')
  router.push('/soporte')
}

// Calcular porcentaje de tiempo completado
const calcularPorcentajeRestante = (): number => {
  if (!sancion.value.duracion_dias || !sancion.value.dias_restantes || sancion.value.es_permanente) {
    return 0
  }
  
  const diasCompletados = sancion.value.duracion_dias - sancion.value.dias_restantes
  return (diasCompletados / sancion.value.duracion_dias) * 100
}
</script>
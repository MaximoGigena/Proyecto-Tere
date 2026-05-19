<!-- fichaMedica.vue - VERSIÓN CORREGIDA CON USE AUTH -->
<template>
  <div class="bg-white p-6 h-full">
    <div v-if="cargando" class="flex justify-center items-center h-full">
      <div class="text-center">
        <font-awesome-icon :icon="['fas', 'spinner']" spin class="text-4xl text-blue-500 mb-2" />
        <p class="text-gray-600">Cargando ficha médica...</p>
      </div>
    </div>

    <div v-else-if="!isAuthenticated" class="flex justify-center items-center h-full">
      <div class="text-center text-red-600">
        <font-awesome-icon :icon="['fas', 'lock']" class="text-4xl mb-2" />
        <p>No estás autenticado. Por favor, inicia sesión nuevamente.</p>
        <button @click="redirigirALogin" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
          Ir al login
        </button>
      </div>
    </div>

    <div v-else-if="error" class="flex justify-center items-center h-full">
      <div class="text-center text-red-600">
        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-4xl mb-2" />
        <p>{{ error }}</p>
        <button @click="cargarFichaMedica" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
          Reintentar
        </button>
      </div>
    </div>

    <div v-else class="w-full h-full bg-white rounded-lg overflow-hidden flex flex-col">
      <!-- Cabecera con información de la mascota -->
      <div class="mb-4 p-4 bg-gray-50 rounded-lg">
        <div class="flex items-center">
          <img 
            :src="fichaMedicaData.mascota?.foto_principal_url || 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=100&h=100&fit=crop'"
            class="w-12 h-12 rounded-full object-cover mr-3"
            :alt="fichaMedicaData.mascota?.nombre || 'Mascota'"
          />
          <div>
            <h1 class="text-xl font-bold">{{ fichaMedicaData.mascota?.nombre || 'Cargando...' }}</h1>
            <p class="text-sm text-gray-600">
              {{ fichaMedicaData.mascota?.especie || '' }} • {{ fichaMedicaData.mascota?.raza || '' }}
            </p>
            <p class="text-xs text-gray-500">
              Edad: {{ fichaMedicaData.mascota?.edad_formateada || 'No disponible' }}
            </p>
            <p class="text-xs text-gray-500">
              Ficha médica • Actualizada: {{ formatearFecha(fichaMedicaData.ficha_medica?.ultima_actualizacion_ficha) }}
            </p>
          </div>
        </div>
      </div>

     

      <!-- Visualización de la ficha médica -->
      <div class="space-y-4">
        <!-- Íconos de acción en la esquina superior -->
        <div 
          v-if="$route.path.startsWith('/veterinarios') && tienePermisoEditar"
          class="absolute top-28 right-8 z-10"
        >
          <button
            class="bg-red-500 text-white p-2 rounded-full shadow-lg hover:bg-red-600 transition-all hover:scale-105"
            @click="abrirOverlayEdicion"
            title="Modificar ficha médica"
          >
            <font-awesome-icon :icon="['fas', 'pen']" class="text-xl" />
          </button>
        </div>

        <!-- Color y señas particulares -->
        <div class="p-4 bg-gray-50 rounded-lg">
          <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
            <font-awesome-icon :icon="['fas', 'paw']" class="mr-2" />
            Color y señas particulares
          </label>
          <p class="text-gray-800">{{ fichaMedicaData.ficha_medica?.color_y_senas || 'No registrado' }}</p>
        </div>

        <!-- Peso y tipo sanguíneo en grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
              <font-awesome-icon :icon="['fas', 'weight-scale']" class="mr-2" />
              Peso
            </label>
            <div class="flex items-center justify-between">
              <p class="text-gray-800">
                <span class="text-2xl font-semibold">{{ fichaMedicaData.ficha_medica?.peso?.valor || '?' }}</span> kg
              </p>
            </div>
            <p v-if="fichaMedicaData.ficha_medica?.peso?.ultima_actualizacion" class="text-xs text-gray-400 mt-1">
              Última actualización: {{ fichaMedicaData.ficha_medica.peso.ultima_actualizacion }}
            </p>
          </div>

          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
              <font-awesome-icon :icon="['fas', 'droplet']" class="mr-2" />
              Tipo sanguíneo
            </label>
            <p class="text-gray-800">
              <span class="font-mono bg-gray-200 px-2 py-0.5 rounded">{{ fichaMedicaData.ficha_medica?.tipo_sanguineo || 'No registrado' }}</span>
            </p>
          </div>
        </div>

        <!-- Número de chip -->
        <div class="p-4 bg-gray-50 rounded-lg">
          <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
            <font-awesome-icon :icon="['fas', 'microchip']" class="mr-2" />
            Número de chip / identificación
          </label>
          <p class="text-gray-800 font-mono text-sm">{{ fichaMedicaData.ficha_medica?.numero_chip || 'No registrado' }}</p>
        </div>
      </div>   
    </div>


    <!-- Overlay de edición -->
    <EditarFichaOverlay
      :visible="overlayVisible"
      :ficha-data="fichaMedicaData"
      :mascota-id="mascotaId"
      @close="cerrarOverlayEdicion"
      @actualizado="onFichaActualizada"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import EditarFichaOverlay from '@/components/ElementosGraficos/EditarFichaOverlay.vue'

// Props
const props = defineProps({
  mascotaId: {
    type: [Number, String],
    default: null
  },
  isOverlay: {
    type: Boolean,
    default: false
  },
  tienePermisoHistorial: {
    type: Boolean,
    default: true
  },
  puedeContactarTutor: {
    type: Boolean,
    default: false
  },
  nombreMascota: {
    type: String,
    default: ''
  }
})

const route = useRoute()
const router = useRouter()

const overlayVisible = ref(false)

// ✅ Usar el composable de autenticación
const { isAuthenticated, accessToken, checkAuth, logout, user, loading: authLoading } = useAuth()

// Estados
const cargando = ref(true)
const error = ref(null)
const fichaMedicaData = ref({
  mascota: {},
  ficha_medica: {}
})

const modalPesoVisible = ref(false)
const nuevoPeso = ref(null)

// ✅ Referencia para el interceptor
let requestInterceptor = null
let responseInterceptor = null

// ✅ Computed para obtener el ID de la mascota
const mascotaId = computed(() => {
  if (props.mascotaId) return props.mascotaId
  if (route.params.id) return route.params.id
  if (route.query.id) return route.query.id
  return null
})

// ✅ Computed para permisos de edición
const tienePermisoEditar = computed(() => {
  return props.tienePermisoHistorial === true
})

// ✅ Configurar interceptores UNA SOLA VEZ
const setupAxiosInterceptors = () => {
  // Limpiar interceptores existentes si los hay
  if (requestInterceptor) {
    axios.interceptors.request.eject(requestInterceptor)
  }
  if (responseInterceptor) {
    axios.interceptors.response.eject(responseInterceptor)
  }

  // Interceptor de request usando el token del composable
  requestInterceptor = axios.interceptors.request.use(
    (config) => {
      // Usar el token reactivo del composable
      if (accessToken.value) {
        config.headers.Authorization = `Bearer ${accessToken.value}`
        console.log('🔑 Token agregado a la petición:', config.url)
      } else {
        console.warn('⚠️ No hay token disponible para:', config.url)
      }
      return config
    },
    (error) => Promise.reject(error)
  )

  // Interceptor de response
  responseInterceptor = axios.interceptors.response.use(
    (response) => response,
    async (error) => {
      if (error.response?.status === 401) {
        console.warn('⚠️ Error 401 detectado')
        
        // Limpiar todo
        await logout()
        
        // Redirigir al login si no estamos allí
        if (router.currentRoute.value.path !== '/login') {
          router.push({
            path: '/login',
            query: { redirect: router.currentRoute.value.fullPath }
          })
        }
      }
      return Promise.reject(error)
    }
  )
}

// ✅ Función para cargar ficha médica (CORREGIDA)
const cargarFichaMedica = async () => {
  try {
    cargando.value = true
    error.value = null
    
    // Esperar a que la autenticación esté lista
    if (authLoading.value) {
      console.log('⏳ Esperando verificación de autenticación...')
      await new Promise(resolve => {
        const checkInterval = setInterval(() => {
          if (!authLoading.value) {
            clearInterval(checkInterval)
            resolve()
          }
        }, 100)
      })
    }
    
    const id = mascotaId.value
    
    if (!id) {
      throw new Error('ID de mascota no especificado')
    }
    
    // VERIFICAR AUTENTICACIÓN PRIMERO
    if (!isAuthenticated.value || !accessToken.value) {
      console.error('❌ Usuario no autenticado o sin token')
      error.value = 'No estás autenticado. Por favor, inicia sesión.'
      
      // Redirigir después de 2 segundos
      setTimeout(() => {
        router.push('/login')
      }, 2000)
      return
    }
    
    console.log('✅ Usuario autenticado, cargando ficha médica...')
    console.log('🔑 Token presente:', !!accessToken.value)
    console.log('📝 Mascota ID:', id)
    
    // Hacer la petición con el token explícito (por si acaso)
    const response = await axios.get(`/api/mascotas/${id}/ficha-medica`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`
      }
    })
    
    console.log('✅ Respuesta recibida:', response.status)
    
    if (response.data.success) {
      fichaMedicaData.value = response.data.data
    } else {
      throw new Error(response.data.message || 'Error al cargar ficha médica')
    }
    
  } catch (err) {
    console.error('❌ Error detallado:', err)
    
    if (err.response?.status === 401) {
      error.value = 'Tu sesión ha expirado. Iniciando sesión nuevamente...'
      await logout()
      setTimeout(() => {
        router.push('/login')
      }, 1500)
    } else if (err.response?.status === 403) {
      error.value = 'No tienes permiso para ver esta ficha médica'
    } else if (err.response?.status === 404) {
      error.value = 'No se encontró la ficha médica para esta mascota'
    } else {
      error.value = err.response?.data?.message || err.message || 'Error al cargar la ficha médica'
    }
  } finally {
    cargando.value = false
  }
}

// ✅ Actualizar peso (CORREGIDO)
const actualizarPeso = async () => {
  if (!nuevoPeso.value || nuevoPeso.value <= 0) {
    alert('Por favor ingrese un peso válido')
    return
  }
  
  const id = mascotaId.value
  
  if (!id) {
    alert('Error: No se puede identificar la mascota')
    return
  }
  
  if (!accessToken.value) {
    alert('Sesión expirada. Por favor, inicia sesión nuevamente.')
    router.push('/login')
    return
  }
  
  try {
    // Usar el mismo endpoint PUT de ficha médica
    const response = await axios.put(`/api/mascotas/${id}/ficha-medica`, {
      peso_actual: nuevoPeso.value  // ← peso_actual, no peso
    }, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`
      }
    })
    
    if (response.data.success) {
      // Actualizar los datos locales
      if (fichaMedicaData.value.ficha_medica) {
        fichaMedicaData.value.ficha_medica.peso = {
          valor: response.data.data.ficha_medica.peso.valor,
          formateado: response.data.data.ficha_medica.peso.formateado,
          ultima_actualizacion: response.data.data.ficha_medica.peso.ultima_actualizacion
        }
      }
      
      cerrarModalPeso()
      alert('Peso actualizado correctamente')
    }
  } catch (err) {
    console.error('Error actualizando peso:', err)
    
    if (err.response?.status === 401) {
      alert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.')
      router.push('/login')
    } else {
      alert(err.response?.data?.message || 'Error al actualizar el peso')
    }
  }
}

// ✅ Otras funciones auxiliares
const redirigirALogin = () => {
  router.push('/login')
}

const abrirModalPeso = () => {
  nuevoPeso.value = fichaMedicaData.value.ficha_medica?.peso?.valor || ''
  modalPesoVisible.value = true
}

const cerrarModalPeso = () => {
  modalPesoVisible.value = false
  nuevoPeso.value = null
}

const formatearFecha = (fechaStr) => {
  if (!fechaStr) return 'Fecha no disponible'
  
  // Intentar parsear diferentes formatos
  let fecha
  
  // Si viene en formato d/m/Y H:i (ej: 25/12/2024 14:30)
  if (fechaStr.match(/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/)) {
    const [fechaParte, horaParte] = fechaStr.split(' ')
    const [dia, mes, anio] = fechaParte.split('/')
    const [hora, minuto] = horaParte.split(':')
    // Crear fecha en formato ISO
    fecha = new Date(anio, mes - 1, dia, hora, minuto)
  } 
  // Si ya viene en formato ISO
  else {
    fecha = new Date(fechaStr)
  }
  
  // Verificar si la fecha es válida
  if (isNaN(fecha.getTime())) {
    console.warn('Fecha inválida:', fechaStr)
    return 'Fecha no disponible'
  }
  
  // Formatear para mostrar
  return fecha.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// ✅ Funciones para el overlay
const abrirOverlayEdicion = () => {
  overlayVisible.value = true
}

const cerrarOverlayEdicion = () => {
  overlayVisible.value = false
}

// ✅ Callback cuando se actualiza la ficha
const onFichaActualizada = (datosActualizados) => {
  console.log('Ficha actualizada:', datosActualizados)
  // Recargar los datos para mostrar los cambios
  cargarFichaMedica()
}

// ✅ Lifecycle
onMounted(async () => {
  console.log('📋 Componente FichaMedica montado')
  console.log('🔍 Estado inicial - isAuthenticated:', isAuthenticated.value)
  console.log('🔍 Token disponible:', !!accessToken.value)
  
  // Configurar interceptores
  setupAxiosInterceptors()
  
  // Esperar a que checkAuth termine si es necesario
  if (authLoading.value) {
    console.log('⏳ Auth loading, esperando...')
  }
  
  // Cargar datos
  await cargarFichaMedica()
})

// Limpiar interceptores al desmontar
onUnmounted(() => {
  if (requestInterceptor) {
    axios.interceptors.request.eject(requestInterceptor)
  }
  if (responseInterceptor) {
    axios.interceptors.response.eject(responseInterceptor)
  }
})
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>
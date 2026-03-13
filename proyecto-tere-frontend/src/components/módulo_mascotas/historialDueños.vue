<!-- historialDueños.vue -->
<template>
  <div class="bg-white p-6 h-full">
    <div class="w-full h-full bg-white rounded-lg overflow-hidden flex-col">
      <!-- Cabecera con información de la mascota -->
      <div v-if="mascotaInfo" class="mb-4 p-4 bg-gray-50 rounded-lg">
        <div class="flex items-center">
          <img 
            v-if="mascotaInfo.foto" 
            :src="mascotaInfo.foto" 
            class="w-12 h-12 rounded-full object-cover mr-3"
            :alt="mascotaInfo.nombre"
          />
          <div>
            <h1 class="text-xl font-bold">{{ mascotaInfo.nombre }}</h1>
            <p class="text-sm text-gray-600">
              {{ mascotaInfo.especie }} • {{ mascotaInfo.edad_formateada }}
            </p>
            <p class="text-xs text-gray-500">
              {{ mascotaInfo.cantidad_tutores }} {{ mascotaInfo.cantidad_tutores === 1 ? 'tutor' : 'tutores' }} en su historial
            </p>
          </div>
        </div>
      </div>

      <!-- Componente de ordenamiento -->
      <OrdenDropdown @cambioOrden="ordenAsc = $event" />

      <!-- Estado de carga -->
      <div v-if="cargando" class="flex justify-center items-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500"></div>
      </div>

      <!-- Mensaje si no hay historial -->
      <div v-else-if="owners.length === 0" class="text-center py-8 text-gray-500">
        No hay historial de tutores para esta mascota
      </div>

      <!-- Lista de tutores -->
      <div
        v-for="(owner, index) in ownersOrdenados"
        :key="index"
        class="p-4 border-b last:border-b-0 hover:bg-gray-50 transition-colors"
        :class="{ 'bg-orange-50': owner.es_actual }"
      >
        <div class="flex items-start">
          <!-- Avatar del tutor -->
          <div class="flex-shrink-0 mr-3">
            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
              <img 
                v-if="owner.foto" 
                :src="owner.foto" 
                class="w-full h-full object-cover"
                :alt="owner.nombre"
              />
              <font-awesome-icon v-else :icon="['fas', 'user']" class="text-gray-400" />
            </div>
          </div>

          <!-- Información del tutor -->
          <div class="flex-grow">
            <div class="flex items-center mb-1">
              <h2 class="font-semibold text-lg">{{ owner.nombre }}</h2>
              <span 
                v-if="owner.es_actual" 
                class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 text-xs rounded-full"
              >
                Tutor actual
              </span>
              <span 
                v-if="owner.es_primer_tutor" 
                class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full"
              >
                Primer tutor
              </span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-sm text-gray-600 mb-2">
              <div>
                <span class="font-medium">Fecha de Adopción:</span> 
                {{ owner.adopcion }}
              </div>
              <div>
                <span class="font-medium">Fecha de Desligo:</span> 
                <span :class="{ 'font-semibold text-green-600': owner.desligo === 'Presente' }">
                  {{ owner.desligo }}
                </span>
              </div>
            </div>

            <!-- Motivo de la transferencia (si aplica) -->
            <div v-if="owner.motivo && !owner.es_actual" class="text-xs text-gray-500 mb-2">
              <span class="font-medium">Motivo:</span> {{ owner.motivo }}
            </div>

            <!-- Botones de contacto dinámicos -->
            <div v-if="owner.medios_contacto && owner.medios_contacto.length > 0" class="space-y-2">
              <div class="flex justify-end space-x-2">
                <button
                  v-for="medio in owner.medios_contacto"
                  :key="medio.id"
                  @click="toggleContacto(owner, medio)"
                  :class="[
                    'px-3 py-1 rounded flex items-center transition-colors text-white',
                    getColorClasses(medio.color || getColorByTipo(medio.tipo)),
                    { 'ring-2 ring-offset-2 ring-gray-600': contactoVisible[owner.id] === medio.id }
                  ]"
                >
                  <span class="mr-1">{{ medio.tipo }}</span>
                  <font-awesome-icon :icon="getIcono(medio.tipo)" />
                </button>
              </div>

              <!-- Desplegable con el dato de contacto -->
              <div 
                v-if="contactoVisible[owner.id]" 
                class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-200 animate-fadeIn"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <span class="text-sm font-medium text-gray-700 mr-2">
                      {{ getMedioSeleccionado(owner).tipo }}:
                    </span>
                    <span class="text-sm text-gray-900 font-mono bg-white px-2 py-1 rounded border border-gray-200">
                      {{ getMedioSeleccionado(owner).valor }}
                    </span>
                  </div>
                  <div class="flex space-x-2">
                    <button 
                      v-if="getMedioSeleccionado(owner).tipo === 'WhatsApp'"
                      @click="abrirWhatsApp(getMedioSeleccionado(owner).valor)"
                      class="text-green-600 hover:text-green-700 p-1 rounded hover:bg-green-50"
                      title="Abrir WhatsApp"
                    >
                      <font-awesome-icon :icon="['fab', 'whatsapp']" size="lg" />
                    </button>
                    <button 
                      v-if="getMedioSeleccionado(owner).tipo === 'Email'"
                      @click="abrirEmail(getMedioSeleccionado(owner).valor)"
                      class="text-orange-600 hover:text-orange-700 p-1 rounded hover:bg-orange-50"
                      title="Enviar Email"
                    >
                      <font-awesome-icon :icon="['fas', 'envelope']" size="lg" />
                    </button>
                    <button 
                      @click="copiarAlPortapapeles(getMedioSeleccionado(owner).valor)"
                      class="text-gray-500 hover:text-gray-700 p-1 rounded hover:bg-gray-100"
                      title="Copiar"
                    >
                      <font-awesome-icon :icon="['far', 'copy']" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Mensaje cuando no hay medios disponibles pero el tutor es actual -->
            <div v-else-if="owner.es_actual" class="text-sm text-gray-400 italic text-right">
              No hay medios de contacto disponibles
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import OrdenDropdown from '@/components/módulo_mascotas/OrdenDropdown.vue'
import axios from 'axios'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

// Para notificaciones (opcional, necesitas instalar vue-toastification)
// import { useToast } from 'vue-toastification'

const props = defineProps({
  mascotaId: {
    type: [Number, String],
    required: true
  }
})

const route = useRoute()
const { accessToken, isAuthenticated, checkAndRedirectIfSuspended, isSuspendido } = useAuth()
// const toast = useToast() // Descomentar si usas toast

const ordenAsc = ref(true)
const owners = ref([])
const cargando = ref(true)
const mascotaInfo = ref(null)
const error = ref(null)
const contactoVisible = ref({}) // Estado para controlar qué medio está desplegado

// Configurar axios para incluir el token en todas las peticiones
const axiosInstance = axios.create()

// Interceptor para añadir el token a cada petición
axiosInstance.interceptors.request.use(
  (config) => {
    if (accessToken.value) {
      config.headers.Authorization = `Bearer ${accessToken.value}`
    }
    config.headers.Accept = 'application/json'
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Interceptor para manejar errores de autenticación
axiosInstance.interceptors.response.use(
  (response) => response,
  (error) => {
    // Si es error 401 (No autorizado) o 403 (Prohibido)
    if (error.response?.status === 401 || error.response?.status === 403) {
      console.error('Error de autenticación:', error.response?.data)
      
      // Verificar si es un error de suspensión
      const auth = useAuth()
      if (auth.isSuspensionError(error)) {
        auth.handleSuspensionError(error)
      }
    }
    return Promise.reject(error)
  }
)

// Cargar historial desde el backend
const cargarHistorial = async () => {
  // Verificar si el usuario está suspendido
  if (isSuspendido.value) {
    checkAndRedirectIfSuspended()
    return
  }

  cargando.value = true
  error.value = null
  
  try {
    const id = props.mascotaId
    
    if (!id) {
      throw new Error('No se proporcionó ID de mascota')
    }
    
    console.log('Cargando historial para mascota ID:', id)
    
    // Usar la instancia de axios configurada
    const response = await axiosInstance.get(`/api/mascotas/${id}/historial-tutores`)
    
    if (response.data.success) {
      owners.value = response.data.data.historial
      mascotaInfo.value = {
        nombre: response.data.data.mascota_nombre,
        especie: response.data.data.especie || 'Mascota',
        edad_formateada: response.data.data.edad_formateada || '',
        foto: response.data.data.foto_principal,
        cantidad_tutores: response.data.data.cantidad_tutores
      }
    } else {
      error.value = response.data.message || 'Error al cargar el historial'
    }
  } catch (err) {
    console.error('Error cargando historial:', err)
    
    // Manejar específicamente errores 401
    if (err.response?.status === 401) {
      error.value = 'No autorizado. Por favor, inicia sesión nuevamente.'
    } else {
      error.value = err.response?.data?.message || 'Error de conexión'
    }
  } finally {
    cargando.value = false
  }
}

// Ordenar por fecha de adopción
const ownersOrdenados = computed(() => {
  return [...owners.value].sort((a, b) => {
    const parseFecha = (fechaStr) => {
      if (fechaStr === 'Presente') return new Date(9999, 11, 31)
      const [dia, mes, año] = fechaStr.split('/').map(Number)
      return new Date(año, mes - 1, dia)
    }
    
    const fechaA = parseFecha(a.adopcion)
    const fechaB = parseFecha(b.adopcion)
    
    return ordenAsc.value ? fechaA - fechaB : fechaB - fechaA
  })
})

// Obtener color por tipo de medio (para compatibilidad con datos antiguos)
const getColorByTipo = (tipo) => {
  const colores = {
    'WhatsApp': 'green',
    'Email': 'orange',
    'Telegram': 'blue'
  }
  return colores[tipo] || 'gray'
}

// Obtener clases de color según el medio
const getColorClasses = (color) => {
  const colors = {
    green: 'bg-green-500 hover:bg-green-600',
    orange: 'bg-orange-500 hover:bg-orange-600',
    blue: 'bg-blue-500 hover:bg-blue-600',
    gray: 'bg-gray-500 hover:bg-gray-600'
  }
  return colors[color] || 'bg-gray-500 hover:bg-gray-600'
}

// Obtener icono según el tipo de medio
const getIcono = (tipo) => {
  const iconos = {
    'WhatsApp': ['fab', 'whatsapp'],
    'Email': ['fas', 'envelope'],
    'Telegram': ['fab', 'telegram']
  }
  return iconos[tipo] || ['fas', 'envelope']
}

// Toggle para mostrar/ocultar el dato de contacto
const toggleContacto = (owner, medio) => {
  // Verificar autenticación antes de mostrar contacto
  if (!isAuthenticated.value) {
    error.value = 'Debes iniciar sesión para ver los datos de contacto'
    return
  }
  
  // Si ya está visible este medio, lo ocultamos
  if (contactoVisible.value[owner.id] === medio.id) {
    contactoVisible.value[owner.id] = null
  } else {
    // Mostramos el nuevo medio
    contactoVisible.value[owner.id] = medio.id
  }
}

// Obtener el medio seleccionado para un owner
const getMedioSeleccionado = (owner) => {
  const medioId = contactoVisible.value[owner.id]
  return owner.medios_contacto.find(m => m.id === medioId) || owner.medios_contacto[0]
}

// Copiar al portapapeles
const copiarAlPortapapeles = (texto) => {
  navigator.clipboard.writeText(texto).then(() => {
    // Mostrar notificación de éxito
    // toast.success('¡Copiado al portapapeles!') // Descomentar si usas toast
    alert('¡Copiado al portapapeles!') // Alternativa simple
  }).catch(() => {
    // toast.error('Error al copiar') // Descomentar si usas toast
    alert('Error al copiar')
  })
}

// Abrir WhatsApp
const abrirWhatsApp = (telefono) => {
  // Limpiar el número (eliminar espacios, guiones, etc.)
  const numeroLimpio = telefono.replace(/\D/g, '')
  window.open(`https://wa.me/${numeroLimpio}`, '_blank')
}

// Abrir Email
const abrirEmail = (email) => {
  window.location.href = `mailto:${email}`
}

// Función para contactar (mantenida por compatibilidad, pero ya no se usa directamente)
const contactar = (tutor, medio) => {
  if (!tutor.contactable) return
  
  // Verificar autenticación antes de contactar
  if (!isAuthenticated.value) {
    error.value = 'Debes iniciar sesión para contactar al tutor'
    return
  }
  
  console.log(`Contactar a ${tutor.nombre} por ${medio}`)
}

// Cargar datos al montar el componente
onMounted(() => {
  cargarHistorial()
})

// Recargar si cambia el ID de la mascota
watch(() => props.mascotaId, (newId) => {
  if (newId) {
    contactoVisible.value = {} // Resetear estado
    cargarHistorial()
  }
})

// Verificar estado de suspensión periódicamente
watch(isSuspendido, (nuevoEstado) => {
  if (nuevoEstado) {
    checkAndRedirectIfSuspended()
  }
})
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.2s ease-out;
}
</style>
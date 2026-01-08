<template>
  <div class="fixed inset-0 z-40 bg-black/70 flex items-center justify-center">
    <!-- CONTENEDOR 1 -->
    <div v-if="!mostrarRazones && !razonSeleccionada && !causaSeleccionada" class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">¿Pasó algo?</h2>
      <button @click="cerrar" class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
      <div
        @click="mostrarRazones = true"
        class="group flex items-center justify-between bg-white hover:bg-red-100 transition rounded-xl px-4 py-3 cursor-pointer mt-3 w-full"
      >
        <p class="text-red-600 font-bold text-sm tracking-wide">Denunciar mascota</p>
        <span class="text-black text-2xl font-bold transition-transform duration-150 group-hover:scale-110">&gt;</span>
      </div>
    </div>

    <!-- CONTENEDOR 2 -->
    <div v-else-if="mostrarRazones && !razonSeleccionada && !causaSeleccionada" class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">Denunciar Mascota</h2>
      <p class="text-sm text-left text-gray-600 mt-1">
        Queremos que nuestra comunidad sea un espacio seguro para los usuarios y las mascotas. Tus denuncias son anónimas.
      </p>
      <button @click="cerrar" class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
      <div
        v-for="razon in razones"
        :key="razon"
        class="group flex items-center justify-between bg-white hover:bg-red-100 transition rounded-xl px-4 py-3 cursor-pointer mt-3 w-full"
        @click="seleccionarRazon(razon)"
      >
        <p class="text-gray-800 font-medium text-sm">{{ razon }}</p>
        <span class="text-black text-xl font-bold transition-transform duration-150 group-hover:scale-110">&gt;</span>
      </div>
    </div>

    <!-- CONTENEDOR 3 -->
    <div v-else-if="razonSeleccionada && !causaSeleccionada" class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">{{ razonSeleccionada }}</h2>
      <p class="text-sm text-left text-gray-600 mt-1 mb-2">
        Seleccioná el motivo específico de la denuncia.
      </p>
      <button @click="cerrar" class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
      <div
        v-for="causa in causasEspecificas[razonSeleccionada] || []"
        :key="causa"
        class="group flex items-center justify-between bg-white hover:bg-red-100 transition rounded-xl px-4 py-3 cursor-pointer mt-3 w-full"
        @click="seleccionarCausa(causa)"
      >
        <p class="text-gray-800 font-medium text-sm">{{ causa }}</p>
        <span class="text-black text-xl font-bold transition-transform duration-150 group-hover:scale-110">&gt;</span>
      </div>
      <div class="mt-4 text-center">
        <button @click="razonSeleccionada = null" class="text-xs text-blue-500 hover:underline">Volver</button>
      </div>
    </div>

    <!-- CONTENEDOR 4 -->
    <div v-else class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">Descripción opcional</h2>
      <p class="text-sm text-left text-gray-600 mt-1 mb-2">
        Podés darnos más detalles sobre esta denuncia. Esto nos ayuda a evaluar mejor el caso.
      </p>
      <button @click="cerrar" class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
      <textarea
        v-model="descripcion"
        rows="4"
        placeholder="Escribí aquí..."
        class="w-full mt-2 text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-300 resize-none"
      ></textarea>

      <button
        @click="enviarDenuncia"
        class="w-full bg-red-600 text-white font-bold rounded-xl py-2 mt-4 hover:bg-red-700 transition"
      >
        Enviar denuncia
      </button>

      <div class="mt-2 text-center">
        <button @click="causaSeleccionada = null" class="text-xs text-blue-500 hover:underline">Volver</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import { useRoute } from 'vue-router'

const props = defineProps({
  mascotaId: {
    type: [String, Number],
    default: null
  },
  ofertaId: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['close'])

const route = useRoute()
const { accessToken } = useAuth()

const mostrarRazones = ref(false)
const razonSeleccionada = ref(null)
const causaSeleccionada = ref(null)
const descripcion = ref('')
const enviando = ref(false)
const error = ref(null)

const razones = [
  'Maltrato Animal',
  'Perfil falso',
  'Contenido inapropiado',
  'Estafa o uso comercial',
  'Mascota ilegal',
]

const causasEspecificas = {
  'Maltrato Animal': ['Heridas visibles', 'Condiciones insalubres', 'Violencia en fotos/videos', 'Negligencia', 'Abandono', 'Explotación', 'Otro'],
  'Perfil falso': ['Fotos robadas', 'Fotos/Videos generadas por IA', 'Información falsa', 'Oferta sospechosa', 'Otro'],
  'Contenido inapropiado': ['Lenguaje ofensivo', 'Contenido sexual', 'Violencia explícita', 'Discriminación', 'Otro'],
  'Estafa o uso comercial': ['Venta encubierta', 'Publicidad engañosa', 'Cobro por servicios falsos', 'Intento de fraude', 'Otro'],
  'Mascota ilegal': ['Especie prohibida', 'Falta de permisos', 'Tráfico ilegal', 'Otro'],
}

const cerrar = () => {
  emit('close')
}

const seleccionarRazon = (razon) => {
  razonSeleccionada.value = razon
}

const seleccionarCausa = (causa) => {
  causaSeleccionada.value = causa
}

// Obtener ID de mascota u oferta según la ruta
const getIdentificadorDenuncia = () => {
  // Prioridad 1: Props pasados directamente
  if (props.mascotaId) {
    return { mascota_id: props.mascotaId }
  }
  if (props.ofertaId) {
    return { oferta_id: props.ofertaId }
  }
  
  // Prioridad 2: Parámetros de ruta
  const params = route.params
  const query = route.query
  
  // Para la modalidad swipe (encuentros)
  if (route.path.startsWith('/explorar/encuentros')) {
    // Intentar obtener del query o params
    if (query.mascota_id) {
      return { mascota_id: query.mascota_id }
    }
    if (query.oferta_id) {
      return { oferta_id: query.oferta_id }
    }
    if (params.id) {
      // En swipe, generalmente es oferta_id
      return { oferta_id: params.id }
    }
  }
  
  // Para la vista "cerca"
  if (route.path.startsWith('/explorar/cerca/') && params.id) {
    return { oferta_id: params.id }
  }
  
  // Para otras rutas
  if (query.mascota_id) {
    return { mascota_id: query.mascota_id }
  }
  if (query.oferta_id) {
    return { oferta_id: query.oferta_id }
  }
  
  console.error('No se pudo identificar la mascota u oferta para denunciar')
  return null
}

const enviarDenuncia = async () => {
  if (!razonSeleccionada.value || !causaSeleccionada.value) {
    error.value = 'Por favor selecciona una razón y causa específica'
    return
  }

  const identificador = getIdentificadorDenuncia()
  if (!identificador) {
    error.value = 'No se pudo identificar la mascota u oferta para denunciar'
    return
  }

  enviando.value = true
  error.value = null

  try {
    const payload = {
      ...identificador,
      categoria: razonSeleccionada.value,
      subcategoria: causaSeleccionada.value,
      descripcion: descripcion.value
    }

    console.log('🔍 Enviando denuncia con payload:', payload)
    console.log('🔍 Token de acceso disponible:', !!accessToken.value)
    console.log('🔍 Ruta actual:', route.fullPath)
    console.log('🔍 Parámetros de ruta:', route.params)
    console.log('🔍 Query params:', route.query)

    const response = await axios.post('/api/denuncias', payload, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      timeout: 10000 // 10 segundos timeout
    })

    console.log('✅ Respuesta del servidor:', response.data)

    if (response.data.success) {
      // Mostrar notificación de éxito
      mostrarNotificacion('Denuncia enviada correctamente. Será revisada por nuestro equipo.', 'success')
      
      // Resetear formulario
      resetFormulario()
      
      // Cerrar modal
      cerrar()
    } else {
      error.value = response.data.message || 'Error al enviar la denuncia'
      console.error('❌ Error en respuesta:', response.data)
    }
  } catch (err) {
    console.error('❌ Error completo al enviar denuncia:', err)
    console.error('❌ URL de la petición:', err.config?.url)
    console.error('❌ Código de estado:', err.response?.status)
    console.error('❌ Datos del error:', err.response?.data)
    console.error('❌ Headers de respuesta:', err.response?.headers)
    
    if (err.response?.data?.errors) {
      error.value = Object.values(err.response.data.errors).flat().join(', ')
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else if (err.code === 'ECONNABORTED') {
      error.value = 'Tiempo de espera agotado. Intenta nuevamente.'
    } else if (err.code === 'ERR_NETWORK') {
      error.value = 'Error de conexión. Verifica tu internet.'
    } else {
      error.value = 'Error inesperado. Intenta nuevamente.'
    }
  } finally {
    enviando.value = false
  }
}

const resetFormulario = () => {
  mostrarRazones.value = false
  razonSeleccionada.value = null
  causaSeleccionada.value = null
  descripcion.value = ''
  error.value = null
}

const mostrarNotificacion = (mensaje, tipo = 'info') => {
  // Implementa tu sistema de notificaciones aquí
  console.log(`${tipo.toUpperCase()}: ${mensaje}`)
  // Ejemplo con alert temporal:
  alert(mensaje)
}
</script>
<template>
  <div class="fixed inset-0 z-40 bg-black/70 flex items-center justify-center">
    <!-- CONTENEDOR 1 - Menú principal -->
    <div v-if="!mostrarRazones && !razonSeleccionada && !causaSeleccionada" class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">¿Pasó algo?</h2>
      <button @click="cerrar" class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
      <div
        @click="mostrarRazones = true"
        class="group flex items-center justify-between bg-white hover:bg-red-100 transition rounded-xl px-4 py-3 cursor-pointer mt-3 w-full"
      >
        <p class="text-red-600 font-bold text-sm tracking-wide">Denunciar usuario</p>
        <span class="text-black text-2xl font-bold transition-transform duration-150 group-hover:scale-110">&gt;</span>
      </div>
    </div>

    <!-- CONTENEDOR 2 - Categorías de denuncia -->
    <div v-else-if="mostrarRazones && !razonSeleccionada && !causaSeleccionada" class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">Denunciar Usuario</h2>
      <p class="text-sm text-left text-gray-600 mt-1">
        Queremos mantener una comunidad segura y respetuosa. Tus denuncias son confidenciales y nos ayudan a mejorar.
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

    <!-- CONTENEDOR 3 - Motivos específicos -->
    <div v-else-if="razonSeleccionada && !causaSeleccionada" class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">{{ razonSeleccionada }}</h2>
      <p class="text-sm text-left text-gray-600 mt-1 mb-2">
        Por favor, especificá el motivo de tu denuncia.
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

    <!-- CONTENEDOR 4 - Descripción opcional -->
    <div v-else class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">Detalles adicionales</h2>
      <p class="text-sm text-left text-gray-600 mt-1 mb-2">
        ¿Querés agregar más información sobre esta denuncia? (opcional)
      </p>
      <button @click="cerrar" class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
      
      <div class="mt-2 text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">
        <p><span class="font-semibold">Categoría:</span> {{ razonSeleccionada }}</p>
        <p><span class="font-semibold">Motivo:</span> {{ causaSeleccionada }}</p>
      </div>

      <textarea
        v-model="descripcion"
        rows="4"
        placeholder="Ej: Enlace a perfiles falsos, capturas de conversaciones, etc."
        class="w-full mt-3 text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-300 resize-none"
      ></textarea>

      <!-- Mensaje de error -->
      <div v-if="errorMessage" class="mt-2 text-sm text-red-600 bg-red-50 p-2 rounded-lg">
        {{ errorMessage }}
      </div>

      <!-- Mensaje de autenticación -->
      <div v-if="!isAuthenticated" class="mt-2 text-sm text-yellow-600 bg-yellow-50 p-2 rounded-lg">
        Debes iniciar sesión para realizar una denuncia
      </div>

      <button
        @click="enviarDenuncia"
        class="w-full bg-red-600 text-white font-bold rounded-xl py-2 mt-4 hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="enviando || !isAuthenticated"
      >
        {{ enviando ? 'Enviando...' : 'Enviar denuncia' }}
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

const props = defineProps({
  usuarioId: {
    type: [String, Number],
    required: true
  },
  usuarioNombre: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['close', 'denunciaEnviada'])

// Usar el composable de autenticación
const { isAuthenticated, accessToken, checkAndRedirectIfSuspended } = useAuth()

// Estados
const mostrarRazones = ref(false)
const razonSeleccionada = ref(null)
const causaSeleccionada = ref(null)
const descripcion = ref('')
const enviando = ref(false)
const errorMessage = ref('')

// Mapeo de nuestras categorías a las del backend
const mapeoCategorias = {
  'Comportamiento inapropiado': 'Contenido inapropiado',
  'Suplantación de identidad': 'Perfil falso',
  'Contenido inapropiado': 'Contenido inapropiado',
  'Estafa o fraude': 'Estafa o uso comercial',
  'Spam o publicidad': 'Contenido inapropiado',
  'Acoso o bullying': 'Contenido inapropiado'
}

// Categorías principales para denunciar usuarios
const razones = [
  'Comportamiento inapropiado',
  'Suplantación de identidad',
  'Contenido inapropiado',
  'Estafa o fraude',
  'Spam o publicidad',
  'Acoso o bullying',
]

// Motivos específicos por categoría
const causasEspecificas = {
  'Comportamiento inapropiado': [
    'Lenguaje ofensivo o grosero',
    'Discriminación (racismo, xenofobia, etc.)',
    'Amenazas o intimidación',
    'Conducta sexual inapropiada',
    'Otro'
  ],
  'Suplantación de identidad': [
    'Se hace pasar por otra persona',
    'Usa fotos de otra persona sin permiso',
    'Perfil falso con información inventada',
    'Otro'
  ],
  'Contenido inapropiado': [
    'Publicaciones con violencia explícita',
    'Contenido sexual no permitido',
    'Promueve el odio o la discriminación',
    'Información falsa o engañosa',
    'Otro'
  ],
  'Estafa o fraude': [
    'Intento de estafa económica',
    'Solicita dinero de forma fraudulenta',
    'Promete servicios falsos',
    'Venta de productos inexistentes',
    'Otro'
  ],
  'Spam o publicidad': [
    'Publicidad no deseada',
    'Mensajes masivos o repetitivos',
    'Promoción de sitios web externos',
    'Enlaces sospechosos o phishing',
    'Otro'
  ],
  'Acoso o bullying': [
    'Acoso persistente',
    'Comentarios maliciosos repetidos',
    'Difusión de información privada',
    'Acoso en mensajes privados',
    'Otro'
  ],
}

const cerrar = () => {
  emit('close')
  // Reset al cerrar
  setTimeout(() => {
    mostrarRazones.value = false
    razonSeleccionada.value = null
    causaSeleccionada.value = null
    descripcion.value = ''
    errorMessage.value = ''
  }, 200)
}

const seleccionarRazon = (razon) => {
  razonSeleccionada.value = razon
}

const seleccionarCausa = (causa) => {
  causaSeleccionada.value = causa
}

const enviarDenuncia = async () => {
  // Verificar autenticación
  if (!isAuthenticated.value) {
    errorMessage.value = 'Debes iniciar sesión para realizar una denuncia'
    return
  }

  if (!razonSeleccionada.value || !causaSeleccionada.value) return

  enviando.value = true
  errorMessage.value = ''

  try {
    // Mapear la categoría a las del backend
    const categoriaBackend = mapeoCategorias[razonSeleccionada.value] || 'Contenido inapropiado'
    
    const denunciaData = {
      tipo: 'usuario',
      usuario_denunciado_id: props.usuarioId,
      categoria: categoriaBackend,
      subcategoria: causaSeleccionada.value,
      descripcion: descripcion.value || `Denuncia por ${razonSeleccionada.value}: ${causaSeleccionada.value}`
    }

    console.log('Enviando denuncia:', denunciaData)
    
    // Llamada real a la API con el token de autenticación
    const response = await axios.post('/api/denuncias', denunciaData, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })
    
    if (response.data.success) {
      // Emitir evento de éxito con los datos de la denuncia
      emit('denunciaEnviada', {
        ...denunciaData,
        id: response.data.data?.id,
        fecha: new Date().toISOString()
      })
      
      // Cerrar modal
      cerrar()
    } else {
      errorMessage.value = response.data.message || 'Error al enviar la denuncia'
    }
    
  } catch (error) {
    console.error('Error al enviar denuncia:', error)
    
    // Manejar error 401 (No autenticado)
    if (error.response?.status === 401) {
      errorMessage.value = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.'
      // Opcional: redirigir al login después de unos segundos
      setTimeout(() => {
        window.location.href = '/login'
      }, 3000)
    }
    // Manejar error 403 (Prohibido - posiblemente cuenta suspendida)
    else if (error.response?.status === 403) {
      errorMessage.value = 'No tienes permiso para realizar esta acción.'
      // Verificar si es por suspensión
      checkAndRedirectIfSuspended()
    }
    else if (error.response?.data?.errors) {
      // Errores de validación
      const errores = Object.values(error.response.data.errors).flat()
      errorMessage.value = errores[0] || 'Error de validación'
    } else if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message
    } else {
      errorMessage.value = 'Error al conectar con el servidor'
    }
  } finally {
    enviando.value = false
  }
}
</script>
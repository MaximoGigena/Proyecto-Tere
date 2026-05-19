<!-- contenidoMascota.vue - Versión CORREGIDA -->
<template> 
  <div class="bg-white backdrop-blur-md border border-white rounded-2xl overflow-y-auto max-h-[80vh] w-full shadow-2xl transition-all duration-300 relative mx-0">
    
    <!-- ✅ CONTENEDOR PRINCIPAL CON POSITION RELATIVE Y Z-INDEX ALTO -->
    <div ref="scrollContainer" class="flex-1 overflow-y-auto overflow-x-overlay [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
      
      <!-- Estado de carga -->
      <div v-if="cargando" class="flex flex-col items-center justify-center min-h-[60vh]">
        <div class="relative">
          <div class="w-16 h-16 border-4 ml-20 border-gray-200 border-t-blue-500 rounded-full animate-spin"></div>
          <p class="mt-4 text-gray-600 font-medium">Cargando datos de la mascota...</p>
        </div>
      </div>

      <!-- Estado de error -->
      <div v-else-if="error" class="text-center py-8">
        <p class="text-red-500">{{ error }}</p>
        <button @click="cargarMascota" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded">
          Reintentar
        </button>
      </div>

      <!-- Contenido principal -->
      <div v-else-if="mascotaComputed" class="relative">
        
        <!-- ✅ BOTONES FLOTANTES - Colocados FUERA del div de imagen para mejor control -->
        
        <button  
          v-if="route.fullPath.includes('/perfil/mascotas') || route.fullPath.includes('/perfil/mascota')"
          @click="handleClose"
          class="fixed right-4 top-5 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition z-50 shadow-lg"
        >
          <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
        </button>

        <!-- Botón de cierre para explorar/cerca (CORREGIDO - ya no requiere params.id) -->
        <button  
          v-if="route.path.startsWith('/explorar/cerca') && (route.params.id || route.query.oferta_id || props.ofertaId)"
          @click="handleClose"
          class="fixed right-4 top-5 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition z-50 shadow-lg"
        >
          <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
        </button>

        <!-- Botón de reporte para explorar/cerca (CORREGIDO) -->
        <button
          v-if="route.path.startsWith('/explorar/cerca') && (route.params.id || route.query.oferta_id || props.ofertaId)"
          @click="mostrar = true"
          class="fixed right-4 top-16 z-50 text-red-700 bg-white bg-opacity-90 rounded-full w-10 h-10 flex items-center justify-center text-2xl font-bold hover:bg-red-100 hover:text-red-800 hover:shadow-lg hover:scale-110 transition shadow-lg"
        >
          !
        </button>

        <!-- Botón de reporte para explorar/encuentros -->
        <button
          v-if="route.path.startsWith('/explorar/encuentros')"
          @click="mostrar = true"
          class="fixed right-4 top-6 z-50 text-red-700 bg-white bg-opacity-90 rounded-full w-10 h-10 flex items-center justify-center text-2xl font-bold hover:bg-red-100 hover:text-red-800 hover:shadow-lg hover:scale-110 transition shadow-lg"
        >
          !
        </button>

        <!-- Imagen principal -->
        <div class="relative w-full rounded-4xl overflow-hidden" :class="{'min-h-[60vh]': galleryImages.length <= 1, 'min-h-[76vh]': galleryImages.length > 1}">
          <div v-if="galleryImages[0]" class="relative w-full rounded-4xl overflow-hidden">
              <img
                :src="currentImageSrc"
                :srcset="getSrcSet(galleryImages[0])"
                :sizes="getSizes()"
                :alt="mascotaComputed.nombre || 'Foto principal'"
                class="w-full h-full object-cover rounded-4xl bg-gray-100 transition-opacity duration-300"
                :class="{'opacity-100': !imageLoading, 'opacity-0': imageLoading, 'max-h-[60vh]': galleryImages.length <= 1, 'max-h-[80vh]': galleryImages.length > 1}"
                loading="eager"
                decoding="async"
                @load="onImageLoad"
                @click="openGallery(0)"
                @error="onImageError"
              />
          </div>
          <div v-else class="w-full h-[60vh] bg-gray-200 flex items-center justify-center rounded-4xl">
            <font-awesome-icon :icon="['fas', 'image']" class="text-6xl text-gray-400" />
          </div>
                  
          <!-- Info mascota (esto sí va dentro del contenedor relativo) -->
          <div class="absolute top-5 left-4 flex flex-col gap-2 z-20">
            <div class="bg-white px-3 py-1 rounded-md shadow text-sm font-semibold w-fit">
              <span>Nombre: {{ mascotaComputed.nombre || 'Sin nombre' }}</span>
            </div>
            
            <div 
              class="px-3 py-1 rounded-md shadow text-sm font-semibold w-fit flex items-center gap-2"
              :class="{
                'bg-blue-100 text-blue-800 border border-blue-300': mascotaComputed.sexo?.toLowerCase() === 'macho',
                'bg-pink-100 text-pink-800 border border-pink-300': mascotaComputed.sexo?.toLowerCase() === 'hembra',
                'bg-white text-gray-800 border border-gray-300': !['macho', 'hembra'].includes(mascotaComputed.sexo?.toLowerCase() || '')
              }"
            >
              <font-awesome-icon 
                v-if="mascotaComputed.sexo?.toLowerCase() === 'macho'"
                :icon="['fas', 'mars']" 
                class="text-blue-600"
              />
              <font-awesome-icon 
                v-else-if="mascotaComputed.sexo?.toLowerCase() === 'hembra'"
                :icon="['fas', 'venus']" 
                class="text-pink-600"
              />
              <span>Sexo: {{ mascotaComputed.sexo || 'No especificado' }}</span>
            </div>

            <div class="bg-blue-500 text-white text-xs px-2 py-1 rounded-md w-fit">
              Edad: {{ edadDisplay }}
            </div>

            <div 
              v-if="mascotaComputed.castrado !== null && mascotaComputed.castrado !== undefined"
              class="text-white text-xs px-2 py-1 rounded-md w-fit"
              :class="{'bg-green-500': mascotaComputed.castrado, 'bg-yellow-500': !mascotaComputed.castrado}"
            >
              {{ castradoLabel }}
            </div>
          </div>

          <!-- Contenedor de reporte -->
          <PasoAlgo 
            v-if="mostrar" 
            @close="mostrar = false"
            :mascotaId="mascotaComputed.id"
            :ofertaId="props.ofertaActual?.id_oferta || route.params.id"
          />
        </div>

        <!-- Resto del contenido (Descripción, características, etc.) - se mantiene igual -->
        <div class="px-4 pt-4 pb-6 bg-white space-y-4">
          <div class="space-y-2">
            <h2 class="text-4xl font-bold text-gray-800">Descripción</h2>
            <p class="text-lg font-semibold text-gray-800">
              {{ mascotaComputed.caracteristicas?.descripcion || 'Sin descripción' }}
            </p>
          </div>
        </div>

        <div v-if="galleryImages[1]" class="relative w-full rounded-4xl overflow-hidden">
          <img
            :src="getOptimizedImage(galleryImages[1], 'medium')"
            :srcset="getSrcSet(galleryImages[1])"
            :sizes="getSizes()"
            alt="Foto secundaria 1"
            class="w-full h-full object-cover rounded-4xl bg-gray-100 transition-opacity duration-300"
            @click="openGallery(1)"
            @error="(e) => onImageError(e, galleryImages[1])"
            loading="lazy"
          />
        </div>

        <CaracteristicasMascota :mascota="mascotaComputed" />
            
        <div v-if="galleryImages[2]" class="relative w-full rounded-4xl overflow-hidden mt-4">
          <img
            :src="getOptimizedImage(galleryImages[2], 'medium')"
            :srcset="getSrcSet(galleryImages[2])"
            :sizes="getSizes()"
            alt="Foto secundaria 2"
            class="w-full h-full object-cover rounded-4xl bg-gray-100 transition-opacity duration-300"
            @click="openGallery(2)"
            @error="(e) => onImageError(e, galleryImages[2])"
            loading="lazy"
          />
        </div>

        
        <div class="flex justify-center mt-4">
          <button class="bg-purple-300 hover:bg-purple-600 text-white text-2xl font-bold py-4 px-8 rounded-md transition-all duration-300" @click="goToHistorial">
            Historiales
          </button>
        </div>

        <div v-if="galleryImages.length > 3" class="grid grid-cols-2 sm:grid-cols-3 gap-3 rounded-2xl overflow-hidden mt-10">
          <div v-for="(img, idx) in galleryImages.slice(3)" :key="idx" class="relative group cursor-pointer">
            <img
              :src="getOptimizedImage(img, 'thumbnail')"
              :srcset="getSrcSet(img)"
              :sizes="getSizes()"
              :alt="`Foto ${idx + 4} de ${mascotaComputed.nombre || 'mascota'}`"
              class="w-full h-48 object-cover rounded-2xl transform group-hover:scale-105 transition duration-300 bg-gray-100"
              @click="openGallery(idx + 3)"
              @error="(e) => onImageError(e, img)"
              loading="lazy"
            />
          </div>
        </div> 
        
        <div class="px-4 pt-4 pb-6 bg-white space-y-4">
          <div class="space-y-2">
            <h2 class="text-4xl font-bold text-gray-800">Ubicación Actual</h2>
            
            <div v-if="cargandoUbicacion" class="text-gray-500">
              <span class="animate-pulse">Cargando ubicación...</span>
            </div>
            
            <p v-else class="text-lg font-semibold text-gray-800">
              {{ ubicacionDisplay }}
            </p>
          </div>
        </div>
          
        <!-- Contenedor de botones (al final) -->
        <div 
          v-if="showButtonsContainer"
          :class="[
            'flex justify-center gap-14 z-20 transition-all duration-700 ease-out pb-20',
            showButtonsContainer ? 'block' : 'hidden',
            { 'opacity-0 translate-y-10': !mostrarBotones, 'opacity-100 translate-y-0': mostrarBotones }
          ]"
          ref="botonesAnimados"
        >
          <button 
            v-if="debeMostrarBotonSolicitud"
            class="bg-white border border-black w-16 h-16 rounded-full shadow-lg flex items-center justify-center transition duration-300 hover:bg-green-50"
            @click="abrirAdvertencia"
            :disabled="verificandoSolicitud"
          >
            <font-awesome-icon 
              :icon="['fas','heart']" 
              class="text-black text-4xl hover:text-green-400"
              :class="{'opacity-50 cursor-not-allowed': verificandoSolicitud}"
            />
          </button>

          <BotonesSwipe
            v-if="route.path.startsWith('/explorar/encuentros')"
            ref="botonesSwipeRef"
            :mascotaId="mascotaComputed.id"
            :ofertaId="props.ofertaActual?.id_oferta || route.params.id"
            :mostrarBotones="mostrarBotones"
            :mostrarInstrucciones="true"
            :contenedorElement="contenedorPrincipal"
            :mostrarAdvertencia="true" 
            @like="onLike"
            @dislike="onDislike"
            @swipe-start="onSwipeStart"
            @swipe-end="onSwipeEnd"
            @swipe-cancel="onSwipeCancel"
            @swipe-animation="onSwipeAnimation"
            @mostrar-advertencia="onMostrarAdvertencia" 
          />
        </div>
      </div>
    </div>
  </div>
  
  <!-- Contenedor de advertencia (fuera del scroll) -->
  <transition name="slide-up">
    <div 
      v-if="mostrarAdvertencia"
      class="fixed inset-0 z-[100] bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl max-h-[70vh] h-[70vh]">
        <AdvertenciaAdopcion 
          ref="advertenciaRef" 
          @close="onAdopcionCancel"
          @success="onAdopcionSuccess"
          @error="onAdopcionError"
        />
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import { useInteracciones } from '@/composables/useInteracciones'
import PasoAlgo from '@/components/módulo_mascotas/reportarMascota.vue'
import AdvertenciaAdopcion from '@/components/módulo_adopciones/advertenciaParaAdoptantes.vue'
import CaracteristicasMascota from '@/components/ElementosGraficos/CaracteristicasMascota.vue'
import BotonesSwipe from '@/components/ElementosGraficos/BotonesSwipe.vue'

const API_URL = 'http://localhost:8000'

const advertenciaRef = ref(null)
const botonesSwipeRef = ref(null)

const router = useRouter()
const route = useRoute()

const ubicacionUsuario = ref(null)
const cargandoUbicacion = ref(false)

// Estados para optimización
const imageLoading = ref(true)
const imageCache = new Map() // Caché de imágenes en memoria
const imageLoadAttempts = ref(0)
const currentImageSrc = ref('') 

const tieneSolicitudActiva = ref(false)
const verificandoSolicitud = ref(false)

const { accessToken, isAuthenticated, checkAuth } = useAuth()

const mascota = ref(null)
const cargando = ref(true)
const error = ref(null)

const scrollContainer = ref(null)
const mostrar = ref(false)

const mostrarBotones = ref(false)
const botonesAnimados = ref(null)
const showButtonsContainer = ref(false)

// Nuevas variables para el swipe
const contenedorPrincipal = ref(null)
const swipeTransform = ref('')
const swipeClass = ref('')
const procesandoSwipe = ref(false)

// Estado para controlar la animación
const mostrarAdvertencia = ref(false)

const { registrarInteraccion } = useInteracciones()

// Accede a los parámetros de la ruta
const id = computed(() => route.params.id)
const from = computed(() => route.query.from)
const enModoTarjetaActiva = computed(() => props.esTarjetaActiva)

const props = defineProps({
  mascotaId: {
    type: [Number, String],
    default: null
  },
  ofertaId: {
    type: [Number, String],
    default: null
  },
  ofertaActual: {
    type: Object,
    default: null
  },
  esTarjetaActiva: {  // ← NUEVA PROP
    type: Boolean,
    default: false
  }
})

/**
 * Obtener imagen optimizada por tamaño
 */
const getOptimizedImage = (imageUrl, size = 'medium') => {
  if (!imageUrl) return ''
  
  const cacheKey = `${imageUrl}_${size}`
  if (imageCache.has(cacheKey)) {
    return imageCache.get(cacheKey)
  }
  
  // URLs externas
  if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
    imageCache.set(cacheKey, imageUrl)
    return imageUrl
  }
  
  // 🔥 SOLUCIÓN: Usar URL directa de storage
  let cleanPath = imageUrl
  
  // Limpiar la ruta
  if (cleanPath.startsWith('/storage/')) {
    cleanPath = cleanPath.replace('/storage/', '')
  }
  if (cleanPath.startsWith('storage/')) {
    cleanPath = cleanPath.replace('storage/', '')
  }
  cleanPath = cleanPath.replace(/^\/+/, '')
  
  // Construir URL directa (requiere php artisan storage:link)
  const directUrl = `${API_URL}/storage/${cleanPath}`
  
  imageCache.set(cacheKey, directUrl)
  return directUrl
}

/**
 * Generar srcset para responsive images
 */
const getSrcSet = (imageUrl) => {
  if (!imageUrl) return ''
  
  // Para URLs externas o problemáticas, no generar srcset
  if (imageUrl.startsWith('http') || !imageUrl.includes('/storage/')) {
    return ''
  }
  
  const sizes = ['thumbnail', 'small', 'medium', 'large']
  const srcset = sizes
    .map(size => `${getOptimizedImage(imageUrl, size)} ${getSizeWidth(size)}w`)
    .filter(src => src) // Filtrar URLs vacías
    .join(', ')
  
  return srcset || ''
}

/**
 * Obtener ancho correspondiente a cada tamaño
 */
const getSizeWidth = (size) => {
  const widths = {
    thumbnail: 150,
    small: 400,
    medium: 800,
    large: 1200
  }
  return widths[size] || 800
}

/**
 * Obtener sizes attribute para responsive
 */
const getSizes = () => {
  return '(max-width: 640px) 100vw, (max-width: 768px) 80vw, 800px'
}

/**
 * Cuando la imagen termina de cargar
 */
const onImageLoad = () => {
  imageLoading.value = false
}

// ✅ Mejorar la función onImageError
const onImageError = (event) => {
  console.warn('Error cargando imagen:', event.target?.src)
  
  imageLoadAttempts.value++
  
  if (imageLoadAttempts.value === 1 && galleryImages.value[0]) {
    // Primer fallback: usar URL original sin optimización
    const originalUrl = getOriginalImageUrl(galleryImages.value[0])
    if (originalUrl && originalUrl !== event.target.src) {
      event.target.src = originalUrl
    }
  } else if (imageLoadAttempts.value === 2) {
    // Segundo fallback: imagen por defecto local (si tienes)
    event.target.src = `${API_URL}/storage/default-pet.jpg`
  } else if (imageLoadAttempts.value === 3) {
    // Tercer fallback: placeholder de placeholder.picsum
    event.target.src = 'https://picsum.photos/id/100/800/800'
  }
  
  event.target.style.display = 'block'
}
/**
 * Precargar la siguiente imagen (para swipe)
 */
const preloadNextImage = (currentIndex) => {
  const nextIndex = currentIndex + 1
  if (galleryImages.value[nextIndex]) {
    const img = new Image()
    img.src = getOptimizedImage(galleryImages.value[nextIndex], 'small')
  }
}

/**
 * Cargar imágenes de la galería de forma progresiva
 */
const loadGalleryImagesProgressively = async () => {
  if (!galleryImages.value.length) return
  
  // Cargar primera imagen (principal) en tamaño medium
  imageLoading.value = true
  const mainImg = new Image()
  mainImg.src = getOptimizedImage(galleryImages.value[0], 'medium')
  mainImg.onload = () => {
    imageLoading.value = false
  }
  
  // Precargar siguientes imágenes en tamaño small
  for (let i = 1; i < Math.min(galleryImages.value.length, 4); i++) {
    const img = new Image()
    img.src = getOptimizedImage(galleryImages.value[i], 'small')
  }
}

const getOriginalImageUrl = (imageUrl) => {
  if (!imageUrl) return ''
  
  if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
    return imageUrl
  }
  
  let cleanPath = imageUrl
  if (cleanPath.startsWith('/storage/')) {
    cleanPath = cleanPath.replace('/storage/', '')
  }
  if (cleanPath.startsWith('storage/')) {
    cleanPath = cleanPath.replace('storage/', '')
  }
  cleanPath = cleanPath.replace(/^\/+/, '')
  
  return `${API_URL}/storage/${cleanPath}`
}

// Define emits
const emit = defineEmits(['like', 'dislike', 'close', 'next', 'prev', 'swipe-completed'])

// Variables adicionales
const ubicacionTutorMascota = ref(null) // Ubicación del dueño de la mascota (sea yo u otro)
const esMiMascota = ref(false) // Flag para saber si es mi mascota o no

// Función para cargar la ubicación del TUTOR de la mascota
async function cargarUbicacionTutor() {
  try {
    cargandoUbicacion.value = true
    
    // Determinar quién es el tutor de la mascota
    const usuarioIdDeLaMascota = mascota.value?.usuario_id || mascota.value?.usuario?.id
    
    if (!usuarioIdDeLaMascota) {
      console.warn('No se encontró usuario_id en la mascota')
      ubicacionTutorMascota.value = null
      return
    }
    
    // Verificar si el tutor es el usuario actual
    const token = accessToken.value
    let usuarioActualId = null
    
    if (token) {
      try {
        // Obtener ID del usuario actual
        const userResponse = await axios.get('/api/user/actual', {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        usuarioActualId = userResponse.data.user?.userable_id || userResponse.data.usuario?.id
        esMiMascota.value = (usuarioActualId == usuarioIdDeLaMascota)
      } catch (err) {
        console.warn('No se pudo obtener usuario actual:', err)
      }
    }
    
    // ✅ LÓGICA PRINCIPAL
    if (esMiMascota.value) {
      // Caso 1: ES MI MASCOTA → Cargar MI ubicación actual (usuario logueado)
      console.log('📍 Es mi mascota, cargo mi ubicación actual')
      await cargarMiUbicacionActual()
    } else {
      // Caso 2: MASCOTA DE OTRO → Cargar ubicación registrada de ESE usuario
      console.log('📍 Mascota de otro usuario, cargo ubicación del tutor:', usuarioIdDeLaMascota)
      await cargarUbicacionDeUsuario(usuarioIdDeLaMascota)
    }
    
  } catch (error) {
    console.error('Error cargando ubicación del tutor:', error)
    ubicacionTutorMascota.value = null
  } finally {
    cargandoUbicacion.value = false
  }
}

const debeMostrarBotonSolicitud = computed(() => {
  // Caso 1: Estamos en /explorar/cerca (con o sin slash)
  const enExplorarCerca = route.path === '/explorar/cerca' || 
                          route.path.startsWith('/explorar/cerca/')
  
  
  // Condición final: 
  // - Debe estar en contexto de adopción (cerca O tarjeta activa)
  // - No debe tener solicitud activa
  // - No debe estar verificando
  // - La mascota debe existir y no ser demo
  const contextoValido = enExplorarCerca
  
  return contextoValido && 
         !tieneSolicitudActiva.value && 
         !verificandoSolicitud.value &&
         mascota.value?.id &&
         mascota.value?.id !== 'demo-burro'
})

// Función para cargar MI ubicación actual
async function cargarMiUbicacionActual() {
  try {
    const token = accessToken.value
    if (!token) {
      ubicacionTutorMascota.value = null
      return
    }
    
    const response = await axios.get('/api/user/ubicacion-actual', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    
    if (response.data.success && response.data.ubicacion) {
      ubicacionTutorMascota.value = response.data.ubicacion
      console.log('✅ Mi ubicación actual cargada:', ubicacionTutorMascota.value)
    } else {
      ubicacionTutorMascota.value = null
    }
  } catch (err) {
    console.warn('Error cargando mi ubicación actual:', err.message)
    ubicacionTutorMascota.value = null
  }
}

// Función para cargar la ubicación registrada de cualquier usuario
async function cargarUbicacionDeUsuario(usuarioId) {
  try {
    const token = accessToken.value
    const response = await axios.get(`/api/usuarios/${usuarioId}/ubicacion`, {
      headers: token ? { 'Authorization': `Bearer ${token}` } : {}
    })
    
    if (response.data.success && response.data.ubicacion) {
      ubicacionTutorMascota.value = response.data.ubicacion
      console.log('✅ Ubicación del tutor cargada:', ubicacionTutorMascota.value)
    } else if (mascota.value?.ubicacion_texto) {
      // Fallback: usar ubicacion_texto de la mascota si existe
      ubicacionTutorMascota.value = {
        city: mascota.value.ubicacion_texto.split(',')[0]?.trim(),
        state: mascota.value.ubicacion_texto.split(',')[1]?.trim(),
        country: mascota.value.ubicacion_texto.split(',')[2]?.trim(),
        es_fallback: true
      }
    } else {
      ubicacionTutorMascota.value = null
    }
  } catch (err) {
    console.warn(`Error cargando ubicación del usuario ${usuarioId}:`, err.message)
    
    // Fallback: usar ubicacion_texto de la mascota
    if (mascota.value?.ubicacion_texto) {
      ubicacionTutorMascota.value = {
        city: mascota.value.ubicacion_texto.split(',')[0]?.trim(),
        state: mascota.value.ubicacion_texto.split(',')[1]?.trim(),
        country: mascota.value.ubicacion_texto.split(',')[2]?.trim(),
        es_fallback: true
      }
    } else {
      ubicacionTutorMascota.value = null
    }
  }
}

// Computed para mostrar la ubicación
const ubicacionDisplay = computed(() => {
  if (!ubicacionTutorMascota.value) {
    return 'Ubicación no disponible'
  }
  
  const { city, state, country, es_fallback } = ubicacionTutorMascota.value
  
  if (es_fallback) {
    // Si es fallback, mostrar como está
    return [city, state, country].filter(Boolean).join(', ') || 'Ubicación no disponible'
  }
  
  // Mostrar ubicación formateada
  const partes = []
  if (city && city !== 'Ubicación no disponible') partes.push(city)
  if (state && state !== city) partes.push(state)
  if (country) partes.push(country)
  
  return partes.length > 0 ? partes.join(', ') : 'Ubicación no disponible'
})

// Función para cargar la mascota (modificada para priorizar props)
async function cargarMascota() {
  console.log('🚀 INICIANDO cargarMascota')
  console.log('📊 Props recibidas:', {
    mascotaId: props.mascotaId,
    ofertaId: props.ofertaId,
    ofertaActual: props.ofertaActual
  })
  
  cargando.value = true
  error.value = null
  mascota.value = null
  
  try {
    // ✅ PRIORIDAD 0: Usar ofertaActual si ya tiene la mascota (modo swipe)
    if (props.ofertaActual && props.ofertaActual.mascota) {
      console.log('📌 [SWIPE] Usando mascota de ofertaActual:', props.ofertaActual.mascota.nombre)
      mascota.value = props.ofertaActual.mascota
      cargando.value = false
      return
    }
    
    // ✅ NUEVO: Detectar si venimos del módulo de adopciones
    const esContextoAdopcion = route.path.startsWith('/explorar/cerca/') || 
                                route.path.startsWith('/explorar/encuentros') ||
                                props.ofertaId ||
                                props.ofertaActual
    
    // PRIORIDAD 1: Usar ofertaId (contexto adopción)
    if (props.ofertaId && esContextoAdopcion) {
      console.log('📌 [ADOPCIÓN] Cargando desde ofertaId:', props.ofertaId)
      const response = await axios.get(`/api/adopciones/ofertas/${props.ofertaId}`, {
        headers: { 'Authorization': `Bearer ${accessToken.value}` }
      })
      
      if (response.data.success) {
        mascota.value = response.data.data?.mascota || response.data.mascota
        console.log('✅ Mascota cargada desde oferta:', mascota.value?.nombre)
        cargando.value = false
        return
      }
    }
    
    // PRIORIDAD 2: Usar mascotaId pero con endpoint de adopción
    if (props.mascotaId && esContextoAdopcion) {
      console.log('📌 [ADOPCIÓN] Buscando oferta activa para mascotaId:', props.mascotaId)
      
      // Buscar la oferta activa de esta mascota
      const ofertaResponse = await axios.get(`/api/adopciones/buscar-por-mascota/${props.mascotaId}`, {
        headers: { 'Authorization': `Bearer ${accessToken.value}` }
      })
      
      if (ofertaResponse.data.success && ofertaResponse.data.data?.id_oferta) {
        const ofertaIdEncontrada = ofertaResponse.data.data.id_oferta
        const response = await axios.get(`/api/adopciones/ofertas/${ofertaIdEncontrada}`, {
          headers: { 'Authorization': `Bearer ${accessToken.value}` }
        })
        
        if (response.data.success) {
          mascota.value = response.data.data?.mascota || response.data.mascota
          console.log('✅ Mascota cargada vía oferta encontrada:', mascota.value?.nombre)
          cargando.value = false
          return
        }
      }
    }
    
    // PRIORIDAD 3: Usar solo cuando NO es contexto adopción (ej: mis mascotas)
    if (props.mascotaId && !esContextoAdopcion) {
      console.log('📌 [PROPIETARIO] Cargando con mascotaId:', props.mascotaId)
      const response = await axios.get(`/api/mascotas/${props.mascotaId}`, {
        headers: { 'Authorization': `Bearer ${accessToken.value}` }
      })
      
      if (response.data.success) {
        mascota.value = response.data.data || response.data.mascota
        console.log('✅ Mascota cargada exitosamente:', mascota.value?.nombre)
        cargando.value = false
        return
      }
    }
    
    if (mascota.value) {
      await nextTick()
      await loadGalleryImagesProgressively()
    }

    throw new Error('No se encontró información de la mascota')
    
  } catch (err) {
    console.error('❌ Error detallado cargando mascota:', err)
    console.error('❌ Respuesta del error:', err.response?.data)
    error.value = err.response?.data?.message || err.message || 'No se pudo cargar la información de la mascota'
    mascota.value = null
  } finally {
    cargando.value = false
    console.log('🏁 FIN cargarMascota - cargando:', cargando.value, 'mascota:', !!mascota.value)
  }
}

// Función para cargar la ubicación del usuario
async function cargarUbicacionUsuario() {
  try {
    cargandoUbicacion.value = true
    
    // ✅ PRIMERO: Intentar obtener ubicación actual del usuario autenticado
    const token = accessToken.value
    if (token) {
      try {
        const response = await axios.get('/api/user/ubicacion-actual', {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (response.data.success && response.data.ubicacion) {
          ubicacionUsuario.value = response.data.ubicacion
          console.log('✅ Ubicación del tutor cargada:', ubicacionUsuario.value)
          return
        }
      } catch (err) {
        console.warn('No se pudo obtener ubicación del usuario:', err.message)
      }
    }
    
    // ✅ SEGUNDO: Fallback a la ubicación de la mascota (si existe)
    if (mascota.value?.ubicacion) {
      ubicacionUsuario.value = mascota.value.ubicacion
      return
    }
    
    if (mascota.value?.ubicacion_texto) {
      ubicacionUsuario.value = {
        city: mascota.value.ubicacion_texto.split(',')[0]?.trim(),
        state: mascota.value.ubicacion_texto.split(',')[1]?.trim(),
        country: mascota.value.ubicacion_texto.split(',')[2]?.trim()
      }
      return
    }
    
    // ✅ TERCERO: Fallback genérico
    ubicacionUsuario.value = {
      city: 'Ubicación no disponible',
      state: null,
      country: null
    }
    
  } catch (error) {
    console.error('Error cargando ubicación:', error)
    ubicacionUsuario.value = {
      city: 'Ubicación no disponible',
      state: null,
      country: null
    }
  } finally {
    cargandoUbicacion.value = false
  }
}

// Computed
const mascotaComputed = computed(() => mascota.value)

const galleryImages = computed(() => {
  if (!mascota.value || cargando.value) {
    return []
  }
  
  const fotos = mascota.value?.fotos || []
  
  const urls = fotos.map(f => {
    if (f.url) return f.url
    if (f.ruta_foto) {
      if (f.ruta_foto.startsWith('http')) return f.ruta_foto
      return `/storage/${f.ruta_foto.replace('storage/', '')}`
    }
    return null
  }).filter(url => url !== null)
  
  return urls
})

const edadDisplay = computed(() => {
  const masc = mascota.value
  if (masc?.edad_formateada && masc.edad_formateada !== 'Edad no disponible') {
    return masc.edad_formateada
  }
  if (masc?.edad && masc?.unidad_edad) {
    return `${masc.edad} ${masc.unidad_edad}`
  }
  return 'Edad no disponible'
})

const castradoLabel = computed(() => {
  const castrado = mascota.value?.castrado
  if (castrado === null || castrado === undefined) {
    return 'Castración: No especificado'
  }
  return castrado ? 'Castrado/a' : 'No castrado/a'
})

// Verificar solicitud activa
async function verificarSolicitudActiva() {
  const mascotaId = mascota.value?.id
  
  if (!mascotaId || mascotaId === 'demo-burro') {
    tieneSolicitudActiva.value = false
    return
  }
  
  try {
    verificandoSolicitud.value = true
    const response = await axios.get(`/api/solicitudes/verificar-activa/${mascotaId}`, {
      headers: { 'Authorization': `Bearer ${accessToken.value}` }
    })
    
    if (response.data.success) {
      tieneSolicitudActiva.value = response.data.data.tieneSolicitudActiva
    }
  } catch (error) {
    console.error('Error verificando solicitud:', error)
    tieneSolicitudActiva.value = false
  } finally {
    verificandoSolicitud.value = false
  }
}

// Funciones de interacción
async function onLike(data) {
  try {
    await registrarInteraccion({
      mascota_id: data.mascotaId,
      oferta_id: data.ofertaId || props.ofertaActual?.id_oferta,
      tipo_interaccion: 'like'
    })
    
    emit('like', data)
    
    if (route.path.startsWith('/explorar/cerca/')) {
      abrirAdvertencia()
    } else if (route.path.startsWith('/explorar/encuentros')) {
      emit('next')
    }
  } catch (error) {
    console.error('Error registrando like:', error)
  }
}

async function onDislike(data) {
  try {
    await registrarInteraccion({
      mascota_id: data.mascotaId,
      oferta_id: data.ofertaId || props.ofertaActual?.id_oferta,
      tipo_interaccion: 'dislike'
    })
    
    emit('dislike', data)
    
    if (route.path.startsWith('/explorar/encuentros')) {
      emit('next')
    }
  } catch (error) {
    console.error('Error registrando dislike:', error)
  }
}

function onSwipeStart(tipo) {
  procesandoSwipe.value = true
}

function onSwipeEnd(tipo) {
  // El reset se maneja en onLike/onDislike
}

function onSwipeCancel(tipo) {
  procesandoSwipe.value = false
  resetSwipeAnimation()
}

function onSwipeAnimation(animation) {
  swipeTransform.value = animation.transform
  swipeClass.value = animation.opacity
}

function resetSwipeAnimation() {
  swipeTransform.value = ''
  swipeClass.value = ''
}

async function onMostrarAdvertencia(data) {
  if (scrollContainer.value) {
    scrollContainer.value.scrollTop = 0
  }
  
  mostrarAdvertencia.value = true
  
  await nextTick()
  await new Promise(resolve => setTimeout(resolve, 100))
  
  if (advertenciaRef.value && typeof advertenciaRef.value.open === 'function') {
    const ofertaId = data.ofertaId || props.ofertaActual?.id_oferta
    const mascotaId = data.mascotaId || mascota.value?.id
    
    if (ofertaId) {
      await advertenciaRef.value.open(ofertaId, null)
    } else if (mascotaId && mascotaId !== 'demo-burro') {
      await advertenciaRef.value.open(null, mascotaId)
    } else {
      mostrarAdvertencia.value = false
      procesandoSwipe.value = false
    }
  } else {
    mostrarAdvertencia.value = false
    procesandoSwipe.value = false
  }
}

async function abrirAdvertencia() {
  if (tieneSolicitudActiva.value) {
    return
  }
  
  if (scrollContainer.value) {
    scrollContainer.value.scrollTop = 0
  }
  
  mostrarAdvertencia.value = true
  
  setTimeout(() => {
    if (advertenciaRef.value && typeof advertenciaRef.value.open === 'function') {
      const ofertaId = props.ofertaId || route.params.id
      const mascotaId = props.mascotaId || mascota.value?.id
      advertenciaRef.value.open(ofertaId, mascotaId)
    }
  }, 100)
}

async function onAdopcionSuccess(data) {
  tieneSolicitudActiva.value = true
  mostrarAdvertencia.value = false
  
  if (route.path.startsWith('/explorar/encuentros')) {
    try {
      const interaccionData = {
        mascota_id: mascota.value?.id,
        oferta_id: props.ofertaActual?.id_oferta,
        tipo_interaccion: 'like'
      }
      
      if (interaccionData.mascota_id || interaccionData.oferta_id) {
        await registrarInteraccion(interaccionData)
      }
      
      emit('swipe-completed', { tipo: 'like', data })
      emit('next')
    } catch (err) {
      console.error('Error:', err)
      emit('next')
    }
  }
}

function onAdopcionCancel() {
  mostrarAdvertencia.value = false
  procesandoSwipe.value = false
  resetSwipeAnimation()
  emit('swipe-cancel', 'like')
}

function onAdopcionError(error) {
  console.error('Error en adopción:', error)
  mostrarAdvertencia.value = false
}

function handleClose() {
  // Limpiar observer
  if (observer && botonesAnimados.value) {
    observer.unobserve(botonesAnimados.value)
    observer.disconnect()
    observer = null
  }
  
  // Emitir evento close para que el padre maneje la navegación
  emit('close')
  
  // Navegación condicional específica como en la versión anterior
  if (route.fullPath.includes('/perfil/mascotas')) {
    router.push('/explorar/perfil/mascotas')
  } else if (route.path.startsWith('/explorar/cerca') && (route.params.id || route.query.oferta_id || props.ofertaId)) {
    router.push('/explorar/cerca')
  } else if (route.path.startsWith('/explorar/encuentros')) {
    // Para encuentros, solo emitir close sin navegar
    // El padre manejará la navegación
  } else {
    // Fallback seguro
    router.back()
  }
}

async function goToHistorial() {
  const mascotaId = mascota.value?.id
  
  if (!mascotaId || mascotaId === 'demo-burro') {
    return
  }
  
  // ✅ Determinar si el usuario actual es el tutor de esta mascota
  const esTutor = esMiMascota.value // Esta variable ya la tienes en tu componente
  
  // ✅ Guardar el contexto COMPLETO para poder volver exactamente
  const contexto = {
    // Información de la mascota
    mascotaId: mascotaId,
    mascotaNombre: mascota.value?.nombre,
    
    // Información de oferta (si existe)
    ofertaId: props.ofertaId || route.params.id || null,
    
    // Contexto de origen
    origenRuta: route.path,
    origenNombre: route.name,
    
    // Parámetros de query relevantes
    queryParams: { ...route.query },
    
    // Parámetros de props
    propsData: {
      mascotaId: mascotaId,
      ofertaId: props.ofertaId || route.params.id || null,
      ofertaActual: props.ofertaActual ? { id_oferta: props.ofertaActual.id_oferta } : null
    },
    
    timestamp: Date.now(),
    returnWithState: true
  }
  
  // Guardar en sessionStorage
  sessionStorage.setItem('contenido_mascota_contexto', JSON.stringify(contexto))

  // ✅ Guardar también las URLs optimizadas en caché
  if (galleryImages.value.length) {
    sessionStorage.setItem(`mascota_${mascotaId}_images`, JSON.stringify(galleryImages.value))
  }
  
  // ✅ Navegar al historial con el permiso adecuado
  router.push({
    name: 'tutores',
    params: { id: mascotaId },
    query: {
      from: 'contenido_mascota',
      ofertaId: contexto.ofertaId,
      permisoHistorial: esTutor ? '1' : '0', // ✅ Si es tutor = '1', si no = '0'
      puedeContactar: esTutor ? '0' : '1',   // ✅ Si es tutor no necesita contactar
      nombreMascota: mascota.value?.nombre || 'la mascota',
      ts: Date.now()
    }
  })
}

function onImgError(event) {
  event.target.style.display = 'none'
}

const openGallery = (index) => {
  const mascotaId = mascota.value?.id
  const ofertaId = props.ofertaId || route.params.id
  
  if (!mascotaId && !ofertaId) return
  
  router.push({
    name: 'galeria-mascota-imagen',
    params: { id: mascotaId || ofertaId, imageIndex: index },
    query: {
      images: JSON.stringify(galleryImages.value),
      from: route.name
    }
  })
}

let observer = null

const initObserver = () => {
  if (observer) {
    observer.disconnect()
    observer = null
  }
  
  if (!botonesAnimados.value) return
  
  observer = new IntersectionObserver(
    ([entry]) => {
      if (entry) {
        mostrarBotones.value = entry.isIntersecting
      }
    },
    { threshold: 0.3, rootMargin: '0px 0px -50px 0px' }
  )
  
  observer.observe(botonesAnimados.value)
}

function mostrarNotificacion(mensaje, tipo) {
  console.log(`${tipo}: ${mensaje}`)
}

// Watchers
watch(() => props.esTarjetaActiva, async (isActive) => {
  if (isActive && mascota.value && !ubicacionTutorMascota.value) {
    await cargarUbicacionTutor()
  }
}, { immediate: true })

watch(() => props.ofertaActual, async (newVal, oldVal) => {
  if (newVal && newVal !== oldVal && newVal.mascota) {
    mascota.value = newVal.mascota
    await cargarUbicacionTutor() // ✅ Cambiado
    if (!route.path.startsWith('/explorar/encuentros')) {
      await verificarSolicitudActiva()
    }
  }
}, { deep: true })

watch(mostrarAdvertencia, (newVal) => {
  if (scrollContainer.value) {
    scrollContainer.value.style.overflow = newVal ? 'hidden' : 'auto'
  }
})

watch(mascota, async (newVal) => {
  if (newVal && !cargando.value) {
    await nextTick()
    await loadGalleryImagesProgressively()
    
    showButtonsContainer.value = true
    if (botonesAnimados.value) {
      initObserver()
    }
  }
})


// Lifecycle
onMounted(async () => {
  document.body.style.overflow = 'hidden'
  
  await cargarMascota()
  if (props.esTarjetaActiva) {
    await cargarUbicacionTutor()
  }
  
  // ✅ Watch seguro aquí, después de que todo está inicializado
  watch(() => galleryImages.value[0], (newUrl) => {
    if (newUrl) {
      imageLoadAttempts.value = 0
      currentImageSrc.value = getOptimizedImage(newUrl, 'medium')
    }
  }, { immediate: true })
  
  showButtonsContainer.value = true
  await nextTick()
  
  if (botonesAnimados.value) {
    initObserver()
  } else {
    setTimeout(() => {
      if (botonesAnimados.value) initObserver()
    }, 100)
  }
  
  if (!route.path.startsWith('/explorar/encuentros') && mascota.value) {
    await verificarSolicitudActiva()
  }
})


onUnmounted(() => {
  if (observer) {
    observer.disconnect()
    observer = null
  }
  document.body.style.overflow = ''
  
  // Limpiar caché de imágenes (opcional, para liberar memoria)
  imageCache.clear()
})
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(100%);
}

/* Transición suave para imágenes */
img {
  transition: opacity 0.3s ease-in-out;
  will-change: transform; /* Optimiza rendimiento */
}

/* Lazy loading placeholder */
img[loading="lazy"] {
  background-color: #f3f4f6;
}

/* Imagen principal con prioridad */
.relative img:first-child {
  content-visibility: auto; /* Chrome optimiza renderizado */
}
</style>
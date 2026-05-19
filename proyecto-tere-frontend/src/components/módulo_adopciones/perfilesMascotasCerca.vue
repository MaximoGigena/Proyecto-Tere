<!-- perfilesMascotasCerca.vue -->
<template>
  <div class="flex flex-col h-screen bg-white relative">
    <!-- Header fijo -->
    <div class="sticky top-0 z-30 bg-white px-4 py-1 flex items-center justify-between shadow-sm">
      <h1 class="text-2xl font-bold text-gray-800">Mascotas cerca de ti</h1>
      <button
        @click="mostrarOverlay = !mostrarOverlay"
        class="inline-flex whitespace-nowrap items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-700 hover:text-white transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300"
      >
        <span class="font-medium text-sm sm:text-base">Filtrar Mascotas</span>
        <font-awesome-icon :icon="['fas', 'filter']" class="text-lg sm:text-xl" />
        <span 
          v-if="filtrosActivos" 
          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"
        >
          !
        </span>
      </button>
    </div>

    <!-- Contenido scrollable -->
    <div ref="scrollContainer" class="flex-1 overflow-y-auto px-4 pt-4">
      <!-- Loading state -->
      <div v-if="cargando" class="flex justify-center items-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
      
      <!-- Error state -->
      <div v-else-if="error" class="text-center p-8">
        <p class="text-red-500">{{ error }}</p>
        <button @click="cargarOfertas" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
          Reintentar
        </button>
      </div>
      
      <!-- SI NO ESTÁ CARGANDO Y NO HAY ERROR -->
      <div v-else>        
        <!-- No results state -->
        <div v-if="ofertas.length === 0" class="text-center p-8">
          <p class="text-gray-500">No hay mascotas disponibles para adopción en este momento</p>
          <button v-if="filtrosActivos" @click="limpiarTodosFiltros" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Limpiar filtros
          </button>
        </div>
        
        <!-- Ofertas disponibles -->
        <div v-else class="grid grid-cols-3 gap-6 justify-items-center pb-28">
          <div
            v-for="(oferta) in ofertas"
            :key="oferta.id_oferta"
            class="text-center relative cursor-pointer"
            :data-mascota-id="oferta.mascota.id"
            @click="abrirModalMascota(oferta.mascota.id, oferta.id_oferta)"
          >
            <!-- 🔥 BADGE DE SOLICITUD ACTIVA -->
            <div 
              v-if="solicitudesActivasMap.get(oferta.mascota.id)?.tieneSolicitudActiva"
              class="absolute -top-2 -right-2 z-10 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg animate-pulse"
            >
              ¡Solicitud enviada!
            </div>
            
            <div class="block group">
              <!-- Imagen de la mascota -->
              <div class="relative overflow-hidden rounded-lg shadow group-hover:shadow-lg transition-shadow duration-200">
                <img
                  :key="`img-${oferta.id_oferta}-${imageVersion}`"
                  :src="obtenerFotoMascota(oferta.mascota)"
                  :alt="oferta.mascota.nombre"
                  class="w-[220px] h-[220px] object-cover transform group-hover:scale-105 transition-transform duration-300"
                  @error="(e) => manejarErrorImagen(e, oferta)"
                />
                <!-- Badge de especie -->
                <div class="absolute top-2 left-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-full">
                  <span class="text-xs font-medium text-gray-800 capitalize">
                    {{ oferta.mascota.especie }}
                  </span>
                </div>
              </div>
              
              <!-- Información de la mascota -->
              <div class="mt-3">
                <p class="text-sm text-gray-800">
                  {{ oferta.mascota.edad_formateada || 'Edad no disponible' }} / 
                  {{ oferta.mascota.sexo === 'macho' ? 'Macho' : 'Hembra' }}
                </p>
                <p class="text-lg font-semibold text-gray-900 mt-1">{{ oferta.mascota.nombre || 'Mascota sin nombre' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Overlay para el detalle de la mascota -->
    <ModalOverlay 
      :visible="showMascotaModal" 
      @close="cerrarModalMascota"
    >
      <contenidoMascota 
        :mascotaId="selectedMascotaId"
        :ofertaId="selectedOfertaId"
        :esTarjetaActiva="true"
        @close="cerrarModalMascota"
      />
    </ModalOverlay>
    
    <!-- Overlay de filtros -->
    <transition name="fade-overlay">
      <div 
        v-show="mostrarOverlay" 
        class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4"
        @click.self="cerrarOverlayFondo"
      >
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
          <div class="p-4 max-w-xl mx-auto">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold text-gray-800">Filtrar Mascotas</h2>
              <button @click="cerrarOverlay" class="text-gray-500 hover:text-gray-700">
                <font-awesome-icon :icon="['fas', 'times']" class="text-3xl" />
              </button>
            </div>
            <FiltrosComponente 
              @cerrar="cerrarOverlay" 
              @filtrar="aplicarFiltros"
              :key="overlayKey"  
            />
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import FiltrosComponente from './filtrosAdopciones.vue'
import ModalOverlay from '@/components/módulo_adopciones/ModalOverlay.vue'
import contenidoMascota from '@/components/módulo_mascotas/contenidoMascota.vue'

const route = useRoute()
const router = useRouter()
const mostrarOverlay = ref(false)
const cargando = ref(false)
const error = ref(null)
const ofertas = ref([])
const filtrosActuales = ref({})
const ubicacionUsuario = ref(null)
const ubicacionCargada = ref(false)

// Estado para el modal de mascota
const showMascotaModal = ref(false)
const selectedMascotaId = ref(null)
const selectedOfertaId = ref(null)

// 🔥 NUEVO: Mapa de solicitudes activas
const solicitudesActivasMap = ref(new Map())
const verificandoSolicitudes = ref(false)

// Cache para solicitudes
const CACHE_DURATION = 5 * 60 * 1000 // 5 minutos
const solicitudesCache = ref(new Map())

const imageVersion = ref(0)

// Función para forzar recarga de imágenes
const forceRefreshImages = () => {
  imageVersion.value++
}

// Computed para verificar si hay filtros activos
const filtrosActivos = computed(() => {
  return Object.keys(filtrosActuales.value).length > 0
})

// Computed para determinar qué endpoint usar
const endpointAAUsar = computed(() => {
  const filtros = filtrosActuales.value;
  
  if (filtros.latitud && filtros.longitud) {
    return '/api/adopciones/jerarquia-ubicacion';
  }
  
  if (ubicacionCargada.value) {
    return '/api/adopciones/proximidad';
  }
  
  return '/api/adopciones/ofertas-disponibles';
});

// Función para abrir el modal de mascota
const abrirModalMascota = (mascotaId, ofertaId) => {
  selectedMascotaId.value = mascotaId
  selectedOfertaId.value = ofertaId
  showMascotaModal.value = true
}

// Función para cerrar el modal de mascota
const cerrarModalMascota = () => {
  showMascotaModal.value = false
  selectedMascotaId.value = null
  selectedOfertaId.value = null
}


// ============================================
// 🔥 FUNCIÓN PRINCIPAL: Verificar solicitudes en lote
// ============================================
async function verificarSolicitudesEnLote(mascotasIds) {
  if (!mascotasIds.length || verificandoSolicitudes.value) return
  
  console.log('🔍 Verificando solicitudes para', mascotasIds.length, 'mascotas')
  
  const ahora = Date.now()
  const idsAVerificar = mascotasIds.filter(id => {
    const cache = solicitudesCache.value.get(id)
    return !cache || (ahora - cache.timestamp) > CACHE_DURATION
  })
  
  if (!idsAVerificar.length) {
    console.log('✅ Usando datos en cache para todas las mascotas')
    return
  }
  
  console.log('🔄 Verificando', idsAVerificar.length, 'mascotas nuevas')
  
  try {
    verificandoSolicitudes.value = true
    const token = localStorage.getItem('token') || sessionStorage.getItem('token')
    
    const response = await axios.post('/api/solicitudes/verificar-multiples',
      { mascotas_ids: idsAVerificar },
      { headers: { 'Authorization': `Bearer ${token}` } }
    )
    
    if (response.data.success) {
      const resultados = response.data.data.resultados
      
      const nuevoMapa = new Map(solicitudesActivasMap.value)
      
      Object.values(resultados).forEach(resultado => {
        nuevoMapa.set(resultado.mascotaId, {
          ...resultado,
          timestamp: Date.now()
        })
        
        solicitudesCache.value.set(resultado.mascotaId, {
          ...resultado,
          timestamp: Date.now()
        })
      })
      
      solicitudesActivasMap.value = nuevoMapa
      console.log('✅ Mapa de solicitudes actualizado:', solicitudesActivasMap.value.size)
    }
  } catch (error) {
    console.error('❌ Error verificando solicitudes en lote:', error)
  } finally {
    verificandoSolicitudes.value = false
  }
}


// ============================================
// 🔥 MODIFICAR cargarOfertas para incluir verificación
// ============================================
const cargarOfertas = async () => {
  cargando.value = true
  error.value = null
  
  try {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token')
    
    if (!token) {
      throw new Error('No hay token de autenticación')
    }
    
    console.log('📍 Cargando ofertas...')
    console.log('📍 Filtros actuales:', filtrosActuales.value)
    
    const endpoint = endpointAAUsar.value;
    let params = { ...filtrosActuales.value };
    
    const response = await axios.get(endpoint, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      },
      params: params
    })
    
    if (response.data.success) {
      ofertas.value = response.data.data || []
      console.log(`✅ Cargadas ${ofertas.value.length} ofertas`)

      if (ofertas.value.length > 0) {
          console.log('🔍 Primera oferta:', JSON.stringify(ofertas.value[0], null, 2))
          
          // Verificar específicamente las fotos
          const primeraMascota = ofertas.value[0].mascota
          console.log('📸 Fotos de primera mascota:', primeraMascota.fotos)
          console.log('📸 optimized_urls de primera foto:', primeraMascota.fotos?.[0]?.optimized_urls)
          console.log('📸 foto_principal_url:', primeraMascota.foto_principal_url)
      }

      
      if (ofertas.value.length > 0) {
        const mascotasIds = ofertas.value
          .map(oferta => oferta.mascota?.id)
          .filter(id => id && id !== 'demo-burro')
        
        await verificarSolicitudesEnLote(mascotasIds)
      }
    } else {
      throw new Error(response.data.message || 'Error al cargar ofertas')
    }
  } catch (err) {
    console.error('❌ Error al cargar ofertas:', err)
    error.value = err.response?.data?.message || err.message || 'Error al cargar las ofertas'
  } finally {
    cargando.value = false
  }
}


// ============================================
// 🔥 NUEVO: Verificar cuando el usuario hace scroll (opcional)
// ============================================

function handleScroll() {
  if (scrollTimeout) clearTimeout(scrollTimeout)
  
  scrollTimeout = setTimeout(() => {
    if (!scrollContainer.value || ofertas.value.length === 0) return
    
    // Encontrar elementos visibles
    const elementos = document.querySelectorAll('[data-mascota-id]')
    const idsVisibles = []
    
    elementos.forEach(el => {
      const rect = el.getBoundingClientRect()
      const isVisible = rect.top < window.innerHeight && rect.bottom > 0
      
      if (isVisible) {
        const mascotaId = el.dataset.mascotaId
        if (mascotaId && !solicitudesActivasMap.value.has(mascotaId)) {
          idsVisibles.push(parseInt(mascotaId))
        }
      }
    })
    
    if (idsVisibles.length > 0) {
      verificarSolicitudesEnLote(idsVisibles)
    }
  }, 200)
}

// ============================================
// 🔥 FUNCIÓN PARA ACTUALIZAR DESPUÉS DE CREAR SOLICITUD
// ============================================
function actualizarSolicitud(mascotaId, tieneSolicitud) {
  const nuevoMapa = new Map(solicitudesActivasMap.value)
  nuevoMapa.set(mascotaId, {
    mascotaId,
    tieneSolicitudActiva: tieneSolicitud,
    solicitud: tieneSolicitud ? { id: Date.now() } : null,
    timestamp: Date.now()
  })
  solicitudesActivasMap.value = nuevoMapa
  
  // Actualizar cache
  solicitudesCache.value.set(mascotaId, {
    mascotaId,
    tieneSolicitudActiva: tieneSolicitud,
    timestamp: Date.now()
  })
}

// ============================================
// FUNCIONES EXISTENTES (sin cambios)
// ============================================
const obtenerUbicacionUsuario = async () => {
  try {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token')
    
    if (!token) {
      throw new Error('No hay token de autenticación')
    }
    
    console.log('📍 Intentando obtener ubicación del usuario...')
    
    try {
      const response = await axios.get('/api/user/location', {
        headers: { 'Authorization': `Bearer ${token}` }
      })
      
      if (response.data.success && response.data.data) {
        ubicacionUsuario.value = {
          latitude: response.data.data.latitude,
          longitude: response.data.data.longitude,
          city: response.data.data.city,
          state: response.data.data.state,
          country: response.data.data.country
        }
        ubicacionCargada.value = true
        return true
      }
    } catch (err) {
      console.log('📍 /api/user/location falló, intentando alternativa...')
    }
    
    try {
      const userResponse = await axios.get('/api/user', {
        headers: { 'Authorization': `Bearer ${token}` }
      })
      
      if (userResponse.data && userResponse.data.ubicacionActual) {
        ubicacionUsuario.value = {
          latitude: userResponse.data.ubicacionActual.latitude,
          longitude: userResponse.data.ubicacionActual.longitude,
          city: userResponse.data.ubicacionActual.city,
          state: userResponse.data.ubicacionActual.state,
          country: userResponse.data.ubicacionActual.country
        }
        ubicacionCargada.value = true
        return true
      }
    } catch (err) {
      console.log('📍 No se pudo obtener ubicación del perfil:', err.message)
    }
    
    return false
  } catch (err) {
    console.warn('⚠️ Error general obteniendo ubicación:', err.message)
    return false
  }
}



const solicitarPermisosUbicacion = () => {
  if (!navigator.geolocation) {
    console.error('Geolocalización no soportada por el navegador')
    error.value = 'Tu navegador no soporta geolocalización'
    return false
  }
  
  console.log('📍 Solicitando permisos de ubicación...')
  
  navigator.geolocation.getCurrentPosition(
    async (position) => {
      try {
        const token = localStorage.getItem('token') || sessionStorage.getItem('token')
        const { latitude, longitude, accuracy } = position.coords
        
        let locationName = {}
        try {
          const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=10`
          )
          const data = await response.json()
          
          locationName = {
            city: data.address?.city || data.address?.town || data.address?.village,
            state: data.address?.state,
            country: data.address?.country,
            country_code: data.address?.country_code
          }
        } catch (e) {
          locationName = {
            city: 'Tanti',
            state: 'Córdoba',
            country: 'Argentina',
            country_code: 'AR'
          }
        }
        
        const ubicacionData = {
          latitude,
          longitude,
          accuracy,
          ...locationName
        }
        
        await axios.post('/api/guardar-ubicacion', ubicacionData, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        ubicacionUsuario.value = {
          latitude,
          longitude,
          city: locationName.city,
          state: locationName.state,
          country: locationName.country
        }
        
        localStorage.setItem('user_location', JSON.stringify(ubicacionUsuario.value))
        localStorage.setItem('user_location_timestamp', new Date().getTime().toString())
        ubicacionCargada.value = true
        
        await cargarOfertas()
      } catch (err) {
        console.error('❌ Error al guardar ubicación:', err)
        error.value = 'Error al guardar la ubicación: ' + err.message
      }
    },
    (error) => {
      console.error('❌ Error al obtener ubicación:', error.message)
      
      let mensajeError = 'No se pudo obtener tu ubicación. '
      switch(error.code) {
        case error.PERMISSION_DENIED:
          mensajeError += 'Permiso denegado por el usuario.'
          break
        case error.POSITION_UNAVAILABLE:
          mensajeError += 'La información de ubicación no está disponible.'
          break
        case error.TIMEOUT:
          mensajeError += 'La solicitud de ubicación expiró.'
          break
        default:
          mensajeError += 'Error desconocido: ' + error.message
      }
      
      error.value = mensajeError
      cargarOfertas()
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    }
  )
  
  return true
}

// Agrega esto después de cargar las ofertas
watch(ofertas, (newOfertas) => {
  if (newOfertas.length > 0) {
    // Pequeño delay para asegurar que el DOM se actualice
    setTimeout(() => {
      forceRefreshImages()
    }, 100)
  }
}, { deep: true })

const aplicarFiltros = (nuevosFiltros) => {
  const filtrosReales = {}
  
  for (const key in nuevosFiltros) {
    const valor = nuevosFiltros[key]
    
    if (Array.isArray(valor)) {
      filtrosReales[key] = [...valor]
    } else if (valor && typeof valor === 'object') {
      filtrosReales[key] = { ...valor }
    } else {
      filtrosReales[key] = valor
    }
  }
  
  filtrosActuales.value = { ...filtrosReales }
  localStorage.setItem('ultimos_filtros', JSON.stringify(filtrosActuales.value))
  cargarOfertas()
  mostrarOverlay.value = false
}

const removerFiltro = (filtro) => {
  const { [filtro]: _, ...restoFiltros } = filtrosActuales.value
  filtrosActuales.value = restoFiltros
  localStorage.setItem('ultimos_filtros', JSON.stringify(filtrosActuales.value))
  cargarOfertas()
}

const limpiarTodosFiltros = () => {
  filtrosActuales.value = {}
  localStorage.removeItem('ultimos_filtros')
  cargarOfertas()
}

const cargarFiltrosGuardados = () => {
  try {
    const filtrosGuardados = localStorage.getItem('ultimos_filtros')
    if (filtrosGuardados) {
      filtrosActuales.value = JSON.parse(filtrosGuardados)
    }
  } catch (e) {
    console.warn('⚠️ Error cargando filtros guardados:', e)
  }
}

const obtenerFotoMascota = (mascota) => {
  if (!mascota) return 'https://cdn.pixabay.com/photo/2020/06/11/20/06/dog-5288071_1280.jpg'
  
  // ✅ Usar la URL del backend (Laravel) en lugar del frontend (Vite)
  const backendUrl = 'http://localhost:8000' // Cambia esto si tu backend está en otro puerto
  
  if (mascota.fotos && Array.isArray(mascota.fotos)) {
    const fotoPrincipal = mascota.fotos.find(f => f.es_principal === true || f.es_principal === 1)
    
    if (fotoPrincipal) {
      let url = null
      
      if (fotoPrincipal.url) {
        // Si la URL ya es absoluta, usarla; si no, construir con backendUrl
        if (fotoPrincipal.url.startsWith('http')) {
          url = fotoPrincipal.url
        } else {
          url = `${backendUrl}${fotoPrincipal.url}`
        }
      } else if (fotoPrincipal.ruta_foto) {
        url = `${backendUrl}/storage/${fotoPrincipal.ruta_foto}`
      }
      
      if (url) {
        console.log('✅ URL generada para', mascota.nombre, ':', url)
        return url
      }
    }
    
    if (mascota.fotos.length > 0) {
      const primeraFoto = mascota.fotos[0]
      let url = null
      
      if (primeraFoto.url) {
        if (primeraFoto.url.startsWith('http')) {
          url = primeraFoto.url
        } else {
          url = `${backendUrl}${primeraFoto.url}`
        }
      } else if (primeraFoto.ruta_foto) {
        url = `${backendUrl}/storage/${primeraFoto.ruta_foto}`
      }
      
      if (url) {
        console.log('✅ Usando primera foto para', mascota.nombre, ':', url)
        return url
      }
    }
  }
  
  // Imagen por defecto
  const imagenesPorDefecto = {
    'perro': 'https://cdn.pixabay.com/photo/2020/06/11/20/06/dog-5288071_1280.jpg',
    'gato': 'https://cdn.pixabay.com/photo/2017/11/09/21/41/cat-2934720_1280.jpg',
    'caballo': 'https://cdn.pixabay.com/photo/2016/01/15/11/05/horse-1141296_1280.jpg'
  }
  
  const especie = (mascota.especie || '').toLowerCase()
  return imagenesPorDefecto[especie] || imagenesPorDefecto['perro']
}

const manejarErrorImagen = (event, oferta) => {
  console.log(`❌ Error cargando imagen para: ${oferta.mascota.nombre}`, event.target.src)
  
  // No hacer nada si ya es la imagen por defecto
  if (event.target.src.includes('pixabay.com')) return
  
  const mascota = oferta.mascota
  const backendUrl = 'http://localhost:8000'
  const posiblesUrls = []
  
  if (mascota.fotos && mascota.fotos.length > 0) {
    const fotoPrincipal = mascota.fotos.find(f => f.es_principal) || mascota.fotos[0]
    
    if (fotoPrincipal.url) {
      if (fotoPrincipal.url.startsWith('http')) {
        posiblesUrls.push(fotoPrincipal.url)
      } else {
        posiblesUrls.push(`${backendUrl}${fotoPrincipal.url}`)
        posiblesUrls.push(fotoPrincipal.url) // URL relativa como fallback
      }
    }
    
    if (fotoPrincipal.ruta_foto) {
      posiblesUrls.push(`${backendUrl}/storage/${fotoPrincipal.ruta_foto}`)
      posiblesUrls.push(`/storage/${fotoPrincipal.ruta_foto}`)
    }
  }
  
  // Encontrar la primera URL que no sea la actual
  const nextUrl = posiblesUrls.find(url => url !== event.target.src)
  if (nextUrl) {
    console.log(`🔄 Intentando URL alternativa para ${mascota.nombre}:`, nextUrl)
    event.target.src = nextUrl
    return
  }
  
  // Si todo falla, usar imagen por defecto
  const imagenesPorDefecto = {
    'perro': 'https://cdn.pixabay.com/photo/2020/06/11/20/06/dog-5288071_1280.jpg',
    'gato': 'https://cdn.pixabay.com/photo/2017/11/09/21/41/cat-2934720_1280.jpg',
    'caballo': 'https://cdn.pixabay.com/photo/2016/01/15/11/05/horse-1141296_1280.jpg'
  }
  
  const especie = (mascota.especie || '').toLowerCase()
  event.target.src = imagenesPorDefecto[especie] || imagenesPorDefecto['perro']
}

const determinarRangoEtario = (especie, dias) => {
  if (dias === null || dias === undefined) return 'Edad no disponible'
  
  const especieLower = (especie || 'otro').toLowerCase()
  
  const rangos = {
    'canino': {
      'cachorro': { min: 0, max: 365 },
      'joven': { min: 366, max: 1095 },
      'adulto': { min: 1096, max: 3285 },
      'abuelo': { min: 3286, max: 999999 }
    },
    'felino': {
      'cachorro': { min: 0, max: 365 },
      'joven': { min: 366, max: 1460 },
      'adulto': { min: 1461, max: 3650 },
      'abuelo': { min: 3651, max: 999999 }
    },
    'equino': {
      'cachorro': { min: 0, max: 730 },
      'joven': { min: 731, max: 1825 },
      'adulto': { min: 1826, max: 5475 },
      'abuelo': { min: 5476, max: 999999 }
    }
  }
  
  const rangosEspecie = rangos[especieLower] || {
    'cachorro': { min: 0, max: 365 },
    'joven': { min: 366, max: 1095 },
    'adulto': { min: 1096, max: 3285 },
    'abuelo': { min: 3286, max: 999999 }
  }
  
  for (const [rango, limites] of Object.entries(rangosEspecie)) {
    if (dias >= limites.min && dias <= limites.max) {
      return rango.charAt(0).toUpperCase() + rango.slice(1)
    }
  }
  
  return 'Adulto'
}

const obtenerInfoEdad = (mascota) => {
  if (mascota.edad_relacion) {
    const edad = mascota.edad_relacion
    const rango = determinarRangoEtario(mascota.especie, edad.dias)
    
    return {
      formateada: edad.edad_formateada || 'Edad no disponible',
      rango: rango,
      dias: edad.dias,
      años: edad.años,
      meses: edad.meses
    }
  }
  
  return {
    formateada: 'Edad no disponible',
    rango: 'Desconocido',
    dias: null
  }
}

const cerrarOverlayFondo = () => {
  mostrarOverlay.value = false
  overlayKey.value++
}

const cerrarOverlay = () => {
  mostrarOverlay.value = false
  overlayKey.value++
}

const overlayKey = ref(0)
const scrollContainer = ref(null)
let scrollTimeout = null

// ============================================
// LIFECYCLE HOOKS
// ============================================
onMounted(async () => {
  console.log('📍 Componente montado, iniciando carga...')
  
  cargarFiltrosGuardados()
  
  const tieneUbicacion = await obtenerUbicacionUsuario()
  
  if (!tieneUbicacion && !filtrosActuales.value.latitud) {
    setTimeout(async () => {
      if (confirm('Para mostrar mascotas cerca de ti, necesitamos tu ubicación. ¿Quieres permitir el acceso a tu ubicación?')) {
        solicitarPermisosUbicacion()
      } else {
        await cargarOfertas()
      }
    }, 1000)
  } else {
    await cargarOfertas()
  }
  
  if (scrollContainer.value) {
    scrollContainer.value.addEventListener('scroll', handleScroll)
  }

  // ✅ Verificar si debemos abrir una mascota específica al cargar
  const abrirMascotaId = route.query.abrir_mascota
  const ofertaIdFromQuery = route.query.oferta_id
  
  if (abrirMascotaId) {
    // Esperar a que carguen las ofertas
    const checkOfertas = setInterval(() => {
      if (!cargando.value && ofertas.value.length > 0) {
        clearInterval(checkOfertas)
        
        // Buscar la oferta que contiene esta mascota
        const oferta = ofertas.value.find(o => o.mascota?.id == abrirMascotaId)
        
        if (oferta) {
          // Abrir el modal con esta mascota
          abrirModalMascota(abrirMascotaId, oferta.id_oferta)
        } else if (ofertaIdFromQuery) {
          // Si no encontramos la oferta pero tenemos oferta_id
          abrirModalMascota(abrirMascotaId, ofertaIdFromQuery)
        }
        
        // Limpiar query params
        router.replace({ query: { ...route.query, abrir_mascota: undefined, oferta_id: undefined } })
      }
    }, 100)
  }
})

// ============================================
// WATCHERS
// ============================================
// 🔥 Escuchar eventos de creación de solicitudes
watch(() => route.query, (newQuery) => {
  if (newQuery.solicitud_creada === '1' && newQuery.mascota_id) {
    actualizarSolicitud(parseInt(newQuery.mascota_id), true)
    
    // Limpiar query
    router.replace({ query: { ...route.query, solicitud_creada: undefined } })
  }
}, { deep: true })

// Exportar función para que otros componentes puedan usarla
defineExpose({
  actualizarSolicitud
})
</script>

<style scoped>
.fade-overlay-enter-active,
.fade-overlay-leave-active {
  transition: opacity 0.3s ease;
}

.fade-overlay-enter-from,
.fade-overlay-leave-to {
  opacity: 0;
}

.fade-overlay-enter-to,
.fade-overlay-leave-from {
  opacity: 1;
}
</style>
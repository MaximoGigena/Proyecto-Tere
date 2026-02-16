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
        <!-- Mostrar indicador si hay filtros activos -->
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
        <!-- Filtros activos (siempre visible cuando hay filtros) -->
        <div v-if="filtrosActivos" class="mb-4 p-3 bg-blue-50 rounded-lg">
          <div class="flex items-center justify-between">
            <div class="flex flex-wrap gap-2">
              <span v-for="(value, key) in filtrosActuales" :key="key" 
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                {{ key }}: {{ Array.isArray(value) ? value.join(', ') : value }}
                <button @click="removerFiltro(key)" class="ml-2 text-blue-600 hover:text-blue-800">
                  ×
                </button>
              </span>
            </div>
            <button @click="limpiarTodosFiltros" class="text-sm text-blue-600 hover:text-blue-800">
              Limpiar todos
            </button>
          </div>
        </div>
        
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
            class="text-center"
          >
            <router-link :to="{
              path: `/explorar/cerca/${oferta.id_oferta}`,
              query: { 
                from: 'cerca',
                mascota_id: oferta.mascota.id,
                oferta_id: oferta.id_oferta
              }
            }" class="block group">
              <!-- Imagen de la mascota -->
              <div class="relative overflow-hidden rounded-lg shadow group-hover:shadow-lg transition-shadow duration-200">
                <img
                  :src="oferta.mascota.foto_principal_url || 'https://cdn.pixabay.com/photo/2020/06/11/20/06/dog-5288071_1280.jpg'"
                  :alt="oferta.mascota.nombre"
                  class="w-[220px] h-[220px] object-cover transform group-hover:scale-105 transition-transform duration-300"
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
                  {{ oferta.mascota.rango_etario }} / 
                  {{ oferta.mascota.sexo === 'macho' ? 'Macho' : 'Hembra' }}
                </p>
                <p class="text-lg font-semibold text-gray-900 mt-1">{{ oferta.mascota.nombre }}</p>
              </div>
            </router-link>
          </div>
        </div>
      </div>
    </div>
    <!-- Overlay de filtros -->
    <transition name="fade">
      <div 
        v-if="mostrarOverlay" 
        class="fixed inset-0 z-40 bg-black/50 flex items-center justify-center p-4"
        @click.self="mostrarOverlay = false"
      >
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
          <div class="p-4 max-w-xl mx-auto">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold text-gray-800">Filtrar Mascotas</h2>
              <button @click="mostrarOverlay = false" class="text-gray-500 hover:text-gray-700">
                <font-awesome-icon :icon="['fas', 'times']" class="text-3xl" />
              </button>
            </div>
            <!-- Componente de filtros -->
            <FiltrosComponente 
              v-if="mostrarOverlay"  
              @cerrar="cerrarOverlaySuave" 
              @filtrar="aplicarFiltros"
              :key="overlayKey"  
            />
          </div>
        </div>
      </div>
    </transition>
    
     <!-- Overlay para mostrar el perfil de la mascota -->
    <!-- Eliminamos este overlay porque se maneja con router -->
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import FiltrosComponente from './filtrosAdopciones.vue'

const route = useRoute()
const router = useRouter()
const mostrarOverlay = ref(false)
const cargando = ref(false)
const error = ref(null)
const ofertas = ref([])
const filtrosActuales = ref({})
const ubicacionUsuario = ref(null)
const ubicacionCargada = ref(false)

// Computed para verificar si hay filtros activos
const filtrosActivos = computed(() => {
  return Object.keys(filtrosActuales.value).length > 0
})

// Computed para determinar qué endpoint usar
const endpointAAUsar = computed(() => {
  const filtros = filtrosActuales.value;
  
  console.log('📍 Determinando endpoint basado en filtros:', filtros);
  
  // Si hay latitud y longitud en los filtros, usar jerarquía de ubicación
  if (filtros.latitud && filtros.longitud) {
    console.log('📍 Usando JERARQUÍA DE UBICACIÓN (hay ubicación específica)');
    return '/api/adopciones/jerarquia-ubicacion';
  }
  
  // Si no hay ubicación específica pero el usuario tiene ubicación, usar proximidad
  if (ubicacionCargada.value) {
    console.log('📍 Usando PROXIMIDAD (ubicación del usuario)');
    return '/api/adopciones/proximidad';
  }
  
  // Si no hay ubicación del usuario, usar ofertas normales
  console.log('📍 Usando OFERTAS NORMALES (sin ubicación)');
  return '/api/adopciones/ofertas-disponibles';
});

// Función para obtener la ubicación del usuario
const obtenerUbicacionUsuario = async () => {
  try {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token')
    
    if (!token) {
      throw new Error('No hay token de autenticación')
    }
    
    console.log('📍 Intentando obtener ubicación del usuario...')
    
    // PRIMERO: Intentar con la ruta correcta
    try {
      const response = await axios.get('/api/user/location', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      
      console.log('📍 Respuesta de /api/user/location:', response.data)
      
      if (response.data.success && response.data.data) {
        ubicacionUsuario.value = {
          latitude: response.data.data.latitude,
          longitude: response.data.data.longitude,
          city: response.data.data.city,
          state: response.data.data.state,
          country: response.data.data.country
        }
        
        ubicacionCargada.value = true
        console.log('📍 Ubicación obtenida:', ubicacionUsuario.value)
        return true
      }
    } catch (err) {
      console.log('📍 /api/user/location falló, intentando alternativa...', err.message)
    }
    
    // SEGUNDO: Intentar directamente desde el perfil del usuario
    try {
      const userResponse = await axios.get('/api/user', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      })
      
      console.log('📍 Respuesta de /api/user:', userResponse.data)
      
      if (userResponse.data && userResponse.data.ubicacionActual) {
        ubicacionUsuario.value = {
          latitude: userResponse.data.ubicacionActual.latitude,
          longitude: userResponse.data.ubicacionActual.longitude,
          city: userResponse.data.ubicacionActual.city,
          state: userResponse.data.ubicacionActual.state,
          country: userResponse.data.ubicacionActual.country
        }
        
        ubicacionCargada.value = true
        console.log('📍 Ubicación obtenida del perfil:', ubicacionUsuario.value)
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

// Función para solicitar permisos de ubicación
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
        
        console.log('📍 Ubicación obtenida del navegador:', { latitude, longitude, accuracy })
        
        // Obtener nombre de la ubicación usando reverse geocoding
        let locationName = {}
        try {
          const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=10`
          )
          const data = await response.json()
          
          console.log('📍 Datos de geocoding:', data)
          
          locationName = {
            city: data.address?.city || data.address?.town || data.address?.village,
            state: data.address?.state,
            country: data.address?.country,
            country_code: data.address?.country_code
          }
        } catch (e) {
          console.warn('📍 No se pudo obtener nombre de ubicación:', e.message)
          // Datos por defecto para Córdoba
          locationName = {
            city: 'Tanti',
            state: 'Córdoba',
            country: 'Argentina',
            country_code: 'AR'
          }
        }
        
        // Guardar ubicación en el backend usando la ruta existente
        const ubicacionData = {
          latitude,
          longitude,
          accuracy,
          ...locationName
        }
        
        console.log('📍 Enviando datos de ubicación:', ubicacionData)
        
        await axios.post('/api/guardar-ubicacion', ubicacionData, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        console.log('✅ Ubicación guardada en backend')
        
        // Actualizar cache local
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
        
        // Recargar ofertas con la nueva ubicación
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
          break;
        case error.POSITION_UNAVAILABLE:
          mensajeError += 'La información de ubicación no está disponible.'
          break;
        case error.TIMEOUT:
          mensajeError += 'La solicitud de ubicación expiró.'
          break;
        default:
          mensajeError += 'Error desconocido: ' + error.message
      }
      
      error.value = mensajeError
      
      // Intentar cargar ofertas sin ubicación
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
    
    // Determinar el endpoint basado en los filtros
    const endpoint = endpointAAUsar.value;
    console.log('📍 Endpoint seleccionado:', endpoint)
    
    // Preparar parámetros según el endpoint
    let params = { ...filtrosActuales.value };
    
    if (endpoint === '/api/adopciones/proximidad') {
      // Para proximidad: eliminar parámetros de ubicación específica
      delete params.latitud;
      delete params.longitud;
      delete params.ubicacion;
      delete params.ciudad;
      delete params.provincia;
      delete params.radio_km;
      
      // Solo enviar distancia máxima si está especificada
      if (!params.distancia_maxima) {
        delete params.distancia_maxima;
      }
      
      console.log('📍 Parámetros para proximidad:', params)
      
    } else if (endpoint === '/api/adopciones/jerarquia-ubicacion') {
      // Para jerarquía de ubicación: incluir parámetro para mostrar ofertas cerca del usuario
      params.incluir_cerca_usuario = true;
      
      console.log('📍 Parámetros para jerarquía:', params)
      
    } else {
      // Para ofertas normales: limpiar parámetros innecesarios
      console.log('📍 Parámetros para ofertas normales:', params)
    }
    
    // Hacer la petición
    const response = await axios.get(endpoint, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      },
      params: params
    })
    
    console.log('📍 RESPUESTA API:', response.data)
    
    if (response.data.success) {
      ofertas.value = response.data.data || []
      console.log(`✅ CARGADAS ${ofertas.value.length} OFERTAS`)
      
      // Mostrar información de jerarquía si existe
      if (response.data.jerarquia) {
        console.log('📊 Jerarquía de ubicación:', response.data.jerarquia)
        
        // Mostrar información detallada de cada oferta
        ofertas.value.forEach((oferta, index) => {
          console.log(`📍 Oferta ${index}:`, {
            nombre: oferta.mascota?.nombre,
            distancia: oferta.distancia,
            distancia_km: oferta.distancia_km,
            ubicacion: oferta.mascota?.ubicacion_texto,
            jerarquia: oferta.jerarquia?.label,
            nivel: oferta.jerarquia?.nivel,
            distancia_desde_seleccionada: oferta.jerarquia?.distancia_desde_seleccionada
          })
        })
      } else if (response.data.estadisticas) {
        console.log('📊 Estadísticas:', response.data.estadisticas)
      }
      
      // Si no hay ofertas
      if (ofertas.value.length === 0) {
        let mensaje = 'No hay mascotas disponibles para adopción ';
        
        if (filtrosActuales.value.ubicacion) {
          mensaje += `en ${filtrosActuales.value.ubicacion}`;
        } else if (ubicacionCargada.value) {
          mensaje += 'cerca de ti';
        }
        
        mensaje += ' con los filtros actuales.';
        error.value = mensaje;
      }
    } else {
      throw new Error(response.data.message || 'Error al cargar ofertas')
    }
  } catch (err) {
    console.error('❌ Error al cargar ofertas:', err)
    console.error('Detalles:', err.response?.data)
    
    // Si la ruta de jerarquía no existe, intentar con proximidad
    if (err.response?.status === 404 && endpointAAUsar.value === '/api/adopciones/jerarquia-ubicacion') {
      console.log('📍 Ruta de jerarquía no encontrada, intentando con proximidad...')
      // Intentar con proximidad como fallback
      try {
        const token = localStorage.getItem('token') || sessionStorage.getItem('token')
        const params = { ...filtrosActuales.value };
        delete params.latitud;
        delete params.longitud;
        delete params.ubicacion;
        delete params.ciudad;
        delete params.provincia;
        delete params.radio_km;
        
        const response = await axios.get('/api/adopciones/proximidad', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          },
          params: params
        })
        
        if (response.data.success) {
          ofertas.value = response.data.data || []
          console.log(`✅ CARGADAS ${ofertas.value.length} OFERTAS (fallback a proximidad)`)
          error.value = null
        }
      } catch (fallbackErr) {
        error.value = fallbackErr.response?.data?.message || fallbackErr.message || 'Error al cargar las ofertas'
      }
    } else if (err.response?.status === 401) {
      error.value = 'Tu sesión ha expirado. Por favor, vuelve a iniciar sesión.'
    } else {
      error.value = err.response?.data?.message || err.message || 'Error al cargar las ofertas'
    }
  } finally {
    cargando.value = false
  }
}

const aplicarFiltros = (nuevosFiltros) => {
  console.log('🎯 NUEVOS FILTROS RECIBIDOS:', nuevosFiltros)
  
  // Extraer valores REALES de los proxies de Vue
  const filtrosReales = {}
  
  for (const key in nuevosFiltros) {
    const valor = nuevosFiltros[key]
    
    if (Array.isArray(valor)) {
      // Si es array (puede ser proxy), extraer valores
      filtrosReales[key] = [...valor]
    } else if (valor && typeof valor === 'object') {
      // Si es objeto (proxy), extraer propiedades
      filtrosReales[key] = { ...valor }
    } else {
      filtrosReales[key] = valor
    }
  }
  
  console.log('🎯 FILTROS REALES:', filtrosReales)
  
  // Actualizar filtros actuales con valores REALES, no proxies
  filtrosActuales.value = { ...filtrosReales }
  
  // Guardar filtros en localStorage para persistencia
  localStorage.setItem('ultimos_filtros', JSON.stringify(filtrosActuales.value));
  
  // Recargar ofertas
  cargarOfertas()
  mostrarOverlay.value = false
}

const removerFiltro = (filtro) => {
  console.log('🗑️ Eliminando filtro:', filtro)
  
  // Eliminar el filtro específico
  const { [filtro]: _, ...restoFiltros } = filtrosActuales.value
  filtrosActuales.value = restoFiltros
  
  // Actualizar localStorage
  localStorage.setItem('ultimos_filtros', JSON.stringify(filtrosActuales.value));
  
  console.log('📍 Filtros después de eliminar:', filtrosActuales.value)
  
  // Recargar ofertas
  cargarOfertas()
}

const limpiarTodosFiltros = () => {
  console.log('🗑️ Limpiando todos los filtros')
  
  filtrosActuales.value = {}
  localStorage.removeItem('ultimos_filtros');
  
  cargarOfertas()
}

// Cargar filtros guardados al inicio
const cargarFiltrosGuardados = () => {
  try {
    const filtrosGuardados = localStorage.getItem('ultimos_filtros');
    if (filtrosGuardados) {
      filtrosActuales.value = JSON.parse(filtrosGuardados);
      console.log('📋 Filtros cargados desde localStorage:', filtrosActuales.value);
    }
  } catch (e) {
    console.warn('⚠️ Error cargando filtros guardados:', e);
  }
}

onMounted(async () => {
  console.log('📍 Componente montado, iniciando carga...')
  
  // Cargar filtros guardados
  cargarFiltrosGuardados();
  
  // Primero intentar obtener ubicación existente
  const tieneUbicacion = await obtenerUbicacionUsuario()
  
  if (!tieneUbicacion && !filtrosActuales.value.latitud) {
    console.log('📍 No se obtuvo ubicación, preguntando al usuario...')
    
    // Esperar un momento antes de preguntar
    setTimeout(async () => {
      if (confirm('Para mostrar mascotas cerca de ti, necesitamos tu ubicación. ¿Quieres permitir el acceso a tu ubicación?')) {
        console.log('📍 Usuario aceptó, solicitando permisos...')
        solicitarPermisosUbicacion()
      } else {
        console.log('📍 Usuario rechazó, cargando ofertas sin ubicación...')
        // Si no quiere, cargar ofertas sin filtro de proximidad
        await cargarOfertas()
      }
    }, 1000)
  } else {
    console.log('📍 Ubicación obtenida o filtros existentes, cargando ofertas...')
    // Si ya tiene ubicación o filtros, cargar ofertas
    await cargarOfertas()
  }
})

// Función para cerrar el overlay
const cerrarOverlay = () => {
  // Si estamos en una ruta con ID, volver a la lista
  if (route.params.id) {
    router.push('/explorar/cerca')
  }
}

// Función para cerrar overlay suavemente
const cerrarOverlaySuave = () => {
  mostrarOverlay.value = false
}

// Key para forzar recarga del componente de filtros
const overlayKey = ref(0)
</script>
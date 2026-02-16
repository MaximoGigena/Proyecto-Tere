<template>
  <div class="p-4 max-w-xl mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Especie -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Especie</label>
        <button
          @click="mostrarTaxonomias = !mostrarTaxonomias"
          class="w-full text-left bg-white border rounded p-2 hover:bg-gray-100"
        >
          {{ filtros.especie.length ? filtros.especie.join(', ') : 'Seleccionar taxonomías' }}
        </button>

        <!-- Selector de taxonomías (condicional) -->
        <div v-if="mostrarTaxonomias" class="mt-2 border rounded p-3 bg-gray-50">
          <div class="grid gap-2">
            <div
              v-for="taxonomia in taxonomias"
              :key="taxonomia"
              class="flex items-center gap-2"
            >
              <input
                type="checkbox"
                :id="taxonomia"
                :value="taxonomia"
                v-model="filtros.especie"
                class="accent-green-600"
              />
              <label :for="taxonomia" class="text-gray-800">{{ taxonomia }}</label>
            </div>
          </div>

          <div class="flex justify-center mt-3">
            <button @click="mostrarTaxonomias = false" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-all">
              Listo
            </button>
          </div>
        </div>
      </div>

      <!-- Edad -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Edad (rango etario)</label>
        <button
          @click="mostrarEdad = !mostrarEdad"
          class="w-full text-left bg-white border rounded p-2 hover:bg-gray-100"
        >
          {{ filtros.edad.length ? filtros.edad.join(', ') : 'Seleccionar edad' }}
        </button>

        <div v-if="mostrarEdad" class="mt-2 border rounded p-3 bg-gray-50">
          <div v-for="opcion in opcionesEdad" :key="opcion" class="flex items-center gap-2 mb-1">
            <input
              type="checkbox"
              :id="opcion"
              :value="opcion"
              v-model="filtros.edad"
              class="accent-blue-600"
            />
            <label :for="opcion" class="text-sm text-gray-700">{{ opcion }}</label>
          </div>

          <div class="flex justify-center mt-3">
            <button @click="mostrarEdad = false" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-all">
              Listo
            </button>
          </div>
        </div>
      </div>

      <!-- Sexo -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Sexo</label>
        <button
          @click="mostrarSexo = !mostrarSexo"
          class="w-full text-left bg-white border rounded p-2 hover:bg-gray-100"
        >
          {{ filtros.sexo.length ? filtros.sexo.join(', ') : 'Seleccionar sexo' }}
        </button>

        <div v-if="mostrarSexo" class="mt-2 border rounded p-3 bg-gray-50">
          <div v-for="opcion in opcionesSexo" :key="opcion" class="flex items-center gap-2 mb-1">
            <input
              type="checkbox"
              :id="'sexo-' + opcion"
              :value="opcion"
              v-model="filtros.sexo"
              class="accent-blue-600"
            />
            <label :for="'sexo-' + opcion" class="text-sm text-gray-700 capitalize">{{ opcion }}</label>
          </div>

          <div class="flex justify-center mt-3">
            <button @click="mostrarSexo = false" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-all">
              Listo
            </button>
          </div>
        </div>
      </div>
      
      <!-- Filtro de Ubicación -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Ubicación</label>
        <button
          @click="mostrarUbicacion = !mostrarUbicacion"
          class="w-full text-left bg-white border rounded p-2 hover:bg-gray-100"
        >
          {{ filtros.ubicacion ? filtros.ubicacion : 'Seleccionar ubicación' }}
        </button>

        <div v-if="mostrarUbicacion" class="mt-2 border rounded p-3 bg-gray-50">
          <!-- Campo de búsqueda principal -->
          <div class="relative">
            <input
              ref="ubicacionInput"
              type="text"
              v-model="busquedaUbicacion"
              @input="onUbicacionInput"
              @keydown="manejarTeclado"
              class="w-full rounded border p-2 pr-10"
              placeholder="Ej: Buenos Aires, Córdoba, Rosario..."
              :disabled="buscandoUbicacion"
            />
            <div v-if="buscandoUbicacion" class="absolute right-3 top-3">
              <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
            </div>
          </div>

          <!-- Sección de sugerencias -->
          <div class="mt-3">
            <!-- Título de sugerencias -->
            <div v-if="sugerenciasUbicacion.length > 0" class="mb-2">
              <p class="text-sm font-medium text-gray-600">Resultados aproximados:</p>
            </div>
            
            <!-- Lista de sugerencias -->
            <div v-if="sugerenciasUbicacion.length > 0" class="max-h-60 overflow-y-auto border rounded-md shadow-sm">
              <div
                v-for="(sugerencia, index) in sugerenciasUbicacion"
                :key="index"
                @click="seleccionarUbicacion(sugerencia)"
                class="p-3 cursor-pointer hover:bg-blue-50 border-b last:border-b-0 transition-colors"
                :class="{ 'bg-blue-50': indiceSeleccionado === index }"
              >
                <div class="font-medium text-gray-800">{{ sugerencia.display }}</div>
                <div class="text-xs text-gray-500 mt-1 flex items-center">
                  <span class="mr-2">📍</span>
                  <span>Lat: {{ sugerencia.lat.toFixed(4) }}, Lon: {{ sugerencia.lon.toFixed(4) }}</span>
                </div>
              </div>
            </div>

            <!-- Mensaje si no hay resultados -->
            <div v-else-if="busquedaUbicacion.length >= 3 && !buscandoUbicacion && !mostrandoResultados" class="p-4 text-center border rounded">
              <p class="text-gray-500">No se encontraron resultados para "{{ busquedaUbicacion }}"</p>
              <p class="text-sm text-gray-400 mt-1">Intenta con otro nombre o dirección</p>
            </div>

            <!-- Información sobre uso -->
            <div v-if="busquedaUbicacion.length < 3 && !buscandoUbicacion" class="mt-3 p-3 bg-gray-50 rounded border">
              <p class="text-sm text-gray-600 flex items-center">
                <span class="mr-2">💡</span>
                Escribe al menos 3 caracteres para ver sugerencias
              </p>
            </div>
          </div>

          <!-- Botón para buscar manualmente -->
          <div v-if="busquedaUbicacion.length >= 3 && !buscandoUbicacion" class="mt-4">
            <button
              @click="buscarUbicacionManual"
              class="w-full px-4 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-300 rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
            >
              Buscar "{{ busquedaUbicacion }}"
            </button>
          </div>
        </div>
      </div>

      <!-- Mostrar coordenadas seleccionadas -->
      <div v-if="filtros.coordenadas" class="md:col-span-2 p-3 bg-green-50 rounded border border-green-200">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm font-medium text-green-800">Ubicación seleccionada:</p>
            <p class="text-green-700">{{ filtros.ubicacion }}</p>
            <p class="text-xs text-green-600 mt-1">
              📍 Lat: {{ filtros.coordenadas.lat }}, Lon: {{ filtros.coordenadas.lon }}
            </p>
          </div>
          <button 
            @click="limpiarUbicacion"
            class="text-red-600 hover:text-red-800 text-sm"
            title="Eliminar ubicación"
          >
            ✕
          </button>
        </div>
      </div>
    </div>

    <!-- Botones -->
    <div class="flex justify-center gap-2 mt-6">
      <button @click="limpiarFiltros" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Limpiar</button>
      <button @click="aplicarFiltros" :disabled="aplicandoFiltros" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-blue-300">
        {{ aplicandoFiltros ? 'Procesando...' : 'Aplicar filtros' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, nextTick, computed, watch, onUnmounted } from 'vue' // <-- Agregar watch y onUnmounted
import axios from 'axios'
import { useAuth } from '@/composables/useAuth' // Ajusta la ruta según tu estructura

// Usar el composable de autenticación
const auth = useAuth()

// Verificar que auth esté disponible
if (!auth) {
  console.error('No se pudo cargar el composable de autenticación')
}

// Acceder al token con protección contra null
const accessToken = computed(() => {
  return auth?.accessToken?.value || null
})

// Crear axiosInstance con manejo de errores
const axiosInstance = computed(() => {
  const instance = axios.create({
    timeout: 10000, // Timeout de 10 segundos
    headers: {}
  })
  
  // Interceptor para agregar el token a todas las peticiones
  instance.interceptors.request.use(config => {
    const token = accessToken.value
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  })
  
  return instance
})

// Emitir eventos
const emit = defineEmits(['cerrar', 'filtrar'])

// Estado de los filtros visibles
const mostrarTaxonomias = ref(false)
const mostrarEdad = ref(false)
const mostrarSexo = ref(false)
const mostrarUbicacion = ref(false)

// Estados de carga
const buscandoUbicacion = ref(false)
const aplicandoFiltros = ref(false)
const mostrandoResultados = ref(false)

// Referencia al input
const ubicacionInput = ref(null)

// Índice para navegación con teclado
const indiceSeleccionado = ref(-1)

// Filtros seleccionados
const filtros = reactive({
  especie: [],
  edad: [],
  sexo: [],
  ubicacion: '',
  coordenadas: null,
  radio: 10
})

// Datos base
const taxonomias = [
  'Caninos', 'Felinos', 'Equinos', 'Bovinos', 'Aves', 'Peces', 'Otro'
]
const opcionesEdad = ['Cachorro', 'Joven', 'Adulto', 'Abuelo']
const opcionesSexo = ['Macho', 'Hembra']

// Ubicación - autocompletado
const busquedaUbicacion = ref('')
const sugerenciasUbicacion = ref([])
let timeoutId = null

// Variable para rastrear si el componente está montado
const isComponentMounted = ref(true)

// Limpiar timeouts cuando el componente se desmonte
onUnmounted(() => {
  isComponentMounted.value = false
  clearTimeout(timeoutId)
})

// Debounce para evitar muchas llamadas API
function onUbicacionInput() {
  if (!isComponentMounted.value) return
  
  clearTimeout(timeoutId)
  
  // Resetear selección por teclado
  indiceSeleccionado.value = -1
  
  if (busquedaUbicacion.value.length < 3) {
    sugerenciasUbicacion.value = []
    mostrandoResultados.value = false
    return
  }
  
  timeoutId = setTimeout(() => {
    if (isComponentMounted.value) {
      buscarSugerenciasUbicacion()
    }
  }, 500)
}

// Manejar navegación con teclado
function manejarTeclado(event) {
  if (!sugerenciasUbicacion.value.length) return
  
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      indiceSeleccionado.value = Math.min(
        indiceSeleccionado.value + 1,
        sugerenciasUbicacion.value.length - 1
      )
      break
      
    case 'ArrowUp':
      event.preventDefault()
      indiceSeleccionado.value = Math.max(indiceSeleccionado.value - 1, -1)
      break
      
    case 'Enter':
      event.preventDefault()
      if (indiceSeleccionado.value >= 0) {
        // Seleccionar con teclado
        seleccionarUbicacion(sugerenciasUbicacion.value[indiceSeleccionado.value])
      } else {
        // Buscar lo que está escrito
        buscarUbicacionManual()
      }
      break
      
    case 'Escape':
      sugerenciasUbicacion.value = []
      indiceSeleccionado.value = -1
      break
  }
}

async function buscarSugerenciasUbicacion() {
  if (!isComponentMounted.value || busquedaUbicacion.value.length < 3) return
  
  // Verificar autenticación
  if (!auth?.isAuthenticated?.value) {
    alert('Por favor, inicia sesión para usar la búsqueda de ubicaciones')
    busquedaUbicacion.value = ''
    return
  }
  
  buscandoUbicacion.value = true
  mostrandoResultados.value = false
  
  try {
    // Verificar que axiosInstance.value esté disponible
    if (!axiosInstance.value) {
      throw new Error('Instancia de axios no disponible')
    }
    
    const response = await axiosInstance.value.get('/api/geocoding/autocomplete', {
      params: {
        query: busquedaUbicacion.value,
        limit: 8
      }
    })
    
    // Verificar que el componente siga montado antes de actualizar el estado
    if (isComponentMounted.value) {
      sugerenciasUbicacion.value = response.data
      mostrandoResultados.value = true
      indiceSeleccionado.value = -1
    }
    
  } catch (error) {
    if (!isComponentMounted.value) return
    
    console.error('Error al buscar sugerencias:', error)
    
    // Manejar error 401 específicamente
    if (error.response?.status === 401) {
      console.log('Token inválido o expirado. Redirigiendo a login...')
      auth?.logout?.()
    }
    
    sugerenciasUbicacion.value = []
  } finally {
    if (isComponentMounted.value) {
      buscandoUbicacion.value = false
    }
  }
}

async function buscarUbicacionManual() {
  if (!isComponentMounted.value || !busquedaUbicacion.value.trim()) return
  
  // Verificar autenticación
  if (!auth?.isAuthenticated?.value) {
    alert('Por favor, inicia sesión para usar la búsqueda de ubicaciones')
    return
  }
  
  buscandoUbicacion.value = true
  
  try {
    // Verificar que axiosInstance.value esté disponible
    if (!axiosInstance.value) {
      throw new Error('Instancia de axios no disponible')
    }
    
    const response = await axiosInstance.value.get('/api/geocoding/geocode', {
      params: {
        ubicacion: busquedaUbicacion.value,
        limit: 1
      }
    })
    
    if (response.data.success && response.data.results.length > 0) {
      const resultado = response.data.results[0]
      seleccionarUbicacion({
        display: resultado.display_name,
        lat: resultado.lat,
        lon: resultado.lon
      })
    } else {
      if (isComponentMounted.value) {
        alert(`No se pudo encontrar la ubicación "${busquedaUbicacion.value}". Intenta con un término más específico.`)
      }
    }
  } catch (error) {
    if (!isComponentMounted.value) return
    
    console.error('Error al buscar ubicación:', error)
    
    // Manejar error de autenticación
    if (error.response?.status === 401) {
      alert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.')
      auth?.logout?.()
    } else {
      alert('Ocurrió un error al buscar la ubicación. Por favor, intenta nuevamente.')
    }
  } finally {
    if (isComponentMounted.value) {
      buscandoUbicacion.value = false
    }
  }
}

function seleccionarUbicacion(sugerencia) {
  if (!isComponentMounted.value) return
  
  filtros.ubicacion = sugerencia.display
  filtros.coordenadas = {
    lat: sugerencia.lat,
    lon: sugerencia.lon
  }
  
  // Limpiar búsqueda y sugerencias
  busquedaUbicacion.value = ''
  sugerenciasUbicacion.value = []
  indiceSeleccionado.value = -1
  mostrandoResultados.value = false
  
  // Cerrar el panel de ubicación automáticamente
  mostrarUbicacion.value = false
  
  console.log('Ubicación seleccionada:', {
    nombre: filtros.ubicacion,
    coordenadas: filtros.coordenadas
  })
}

function limpiarUbicacion() {
  filtros.ubicacion = ''
  filtros.coordenadas = null
  busquedaUbicacion.value = ''
  sugerenciasUbicacion.value = []
  indiceSeleccionado.value = -1
  mostrandoResultados.value = false
}

// Acciones
function limpiarFiltros() {
  filtros.especie = []
  filtros.edad = []
  filtros.sexo = []
  limpiarUbicacion()
  
  // Aplicar filtros limpiados inmediatamente
  aplicarFiltros()
}

// Función auxiliar para extraer ciudad de la ubicación - MÁS ARRIBA
function extraerCiudadDeUbicacion(ubicacionTexto) {
  if (!ubicacionTexto) return null;
  
  // Lógica simple para extraer ciudad
  // Puedes mejorar esto según tus necesidades
  const partes = ubicacionTexto.split(',');
  
  // Tomar la primera parte que no sea vacía
  for (let parte of partes) {
    const parteLimpia = parte.trim();
    if (parteLimpia && !parteLimpia.includes('Departamento') && 
        !parteLimpia.includes('Provincia') && 
        !parteLimpia.includes('Argentina') &&
        !/^\d+$/.test(parteLimpia)) {
      return parteLimpia;
    }
  }
  
  return partes.length > 0 ? partes[0].trim() : null;
}

async function aplicarFiltros() {
  if (!isComponentMounted.value) return
  
  aplicandoFiltros.value = true
  
  console.log('Filtros aplicados:', { ...filtros })
  
  // Preparar filtros para enviar a la API
  const filtrosParaEnviar = {}
  
  // Especies: enviar como array explícito
  if (filtros.especie.length) {
    filtrosParaEnviar.especie = JSON.stringify(
      filtros.especie.map(esp => esp.toLowerCase())
    )
  }
  
  // Sexo: también como array JSON
  if (filtros.sexo.length) {
    filtrosParaEnviar.sexo = JSON.stringify(
      filtros.sexo.map(s => s.toLowerCase())
    )
  }
  
  // Edad: enviar como array JSON
  if (filtros.edad.length) {
    filtrosParaEnviar.rangos_edad = JSON.stringify(
      filtros.edad.map(rango => rango.toLowerCase())
    )
  }
  
  // Ubicación y coordenadas
  if (filtros.coordenadas) {
    filtrosParaEnviar.ubicacion = filtros.ubicacion
    filtrosParaEnviar.latitud = filtros.coordenadas.lat
    filtrosParaEnviar.longitud = filtros.coordenadas.lon
    filtrosParaEnviar.radio_km = filtros.radio
  }

  const ciudad = extraerCiudadDeUbicacion(filtros.ubicacion);
    if (ciudad) {
      filtrosParaEnviar.ciudad = ciudad;
    }

  try {
    emit('filtrar', filtrosParaEnviar);
    emit('cerrar');
  } catch (error) {
    console.error('Error al aplicar filtros:', error);
    alert('Error al aplicar los filtros. Intenta nuevamente.');
  } finally {
    aplicandoFiltros.value = false;
  }
}

// Focus en el input cuando se abre el panel
watch(mostrarUbicacion, (nuevoValor) => {
  if (nuevoValor && isComponentMounted.value) {
    nextTick(() => {
      if (ubicacionInput.value && isComponentMounted.value) {
        ubicacionInput.value.focus()
      }
    })
  }
})
</script>
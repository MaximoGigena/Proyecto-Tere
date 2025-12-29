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
              @cerrar="mostrarOverlay = false" 
              @filtrar="aplicarFiltros"
            />
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import FiltrosComponente from './filtrosAdopciones.vue'

const mostrarOverlay = ref(false)
const cargando = ref(false)
const error = ref(null)
const ofertas = ref([])
const filtrosActuales = ref({})

// Computed para verificar si hay filtros activos
const filtrosActivos = computed(() => {
  return Object.keys(filtrosActuales.value).length > 0
})

const cargarOfertas = async () => {
  cargando.value = true
  error.value = null
  
  try {
    const token = localStorage.getItem('token') || sessionStorage.getItem('token')
    
    if (!token) {
      throw new Error('No hay token de autenticación')
    }
    
    // 🔥 ARREGLO CRÍTICO: Extraer valores REALES de los Proxy
    const construirParams = (obj) => {
      const params = new URLSearchParams()
      
      for (const key in obj) {
        const value = obj[key]
        
        // Si es un Proxy de array, extraer sus valores
        if (Array.isArray(value) || (value && typeof value === 'object' && '0' in value)) {
          // Extraer valores del Proxy
          const arrayValores = [...value]
          if (arrayValores.length > 0) {
            // Para arrays simples como ['macho'], axios los maneja bien
            params.append(key, arrayValores.join(','))
          }
        } 
        // Si hay rangos de edad, convertirlos a JSON
        else if (key === 'rangos_edad' && value) {
          const edadesArray = [...value]
          params.append(key, JSON.stringify(edadesArray))
        }
        // Para valores simples
        else if (value && value !== '') {
          params.append(key, value)
        }
      }
      
      return params
    }
    
    // Construir parámetros CORRECTAMENTE
    const params = construirParams(filtrosActuales.value)
    console.log('PARAMS REALES CONSTRUIDOS:', Object.fromEntries(params))
    
    const response = await axios.get(`/api/adopciones/ofertas-disponibles?${params.toString()}`, {
       headers: {
         'Authorization': `Bearer ${token}`,
         'Accept': 'application/json'
       }
     })
    
    console.log('RESPUESTA API:', response.data)
    
    if (response.data.success) {
      ofertas.value = response.data.data || []
      console.log(`✅ CARGADAS ${ofertas.value.length} OFERTAS`)
      
      // Debug adicional
      if (ofertas.value.length > 0) {
        console.log('Primera oferta cargada:', ofertas.value[0].mascota?.nombre)
      }
    } else {
      throw new Error(response.data.message || 'Error al cargar ofertas')
    }
  } catch (err) {
    console.error('❌ Error al cargar ofertas:', err)
    console.error('Detalles:', err.response?.data)
    error.value = err.response?.data?.message || err.message || 'Error al cargar las ofertas'
    
    if (err.response?.status === 401) {
      error.value = 'Tu sesión ha expirado. Por favor, vuelve a iniciar sesión.'
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
  
  // Recargar ofertas
  cargarOfertas()
  mostrarOverlay.value = false
}

const removerFiltro = (filtro) => {
  // Eliminar el filtro específico
  const { [filtro]: _, ...restoFiltros } = filtrosActuales.value
  filtrosActuales.value = restoFiltros
  
  // Recargar ofertas
  cargarOfertas()
}

const limpiarTodosFiltros = () => {
  filtrosActuales.value = {}
  cargarOfertas()
}

onMounted(() => {
  cargarOfertas()
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}

.group:hover .group-hover\:shadow-lg {
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>
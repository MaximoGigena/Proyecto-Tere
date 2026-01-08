<template>
  <!-- Modal Overlay -->
  <div 
    v-if="mostrar" 
    class="fixed inset-0 z-[100] overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4"
    @click.self="cerrarModal"
  >
    <!-- Modal Container -->
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-hidden transform transition-all">
      <!-- Header -->
      <div class="border-b px-6 py-4 flex items-center justify-between bg-gray-50">
        <div>
          <h3 class="text-xl font-bold text-gray-900">Asociar Fármaco</h3>
          <p class="text-sm text-gray-500 mt-1">Seleccioná el fármaco a asociar al procedimiento</p>
        </div>
        <button 
          @click="cerrarModal"
          class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100"
          :disabled="cargando"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto max-h-[calc(90vh-8rem)]">
        <!-- Estado de carga -->
        <div v-if="cargando" class="flex flex-col items-center justify-center py-8">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
          <p class="text-gray-600">Cargando fármacos...</p>
        </div>

        <!-- Error al cargar -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
          <div class="flex items-center">
            <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-red-800">{{ error }}</p>
          </div>
          <button 
            @click="cargarFarmacos"
            class="mt-2 text-sm text-red-600 hover:text-red-800 underline"
          >
            Reintentar
          </button>
        </div>

        <!-- Contenido principal -->
        <div v-else>
          <!-- Separador -->
          <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-gray-500">Seleccionar fármaco existente</span>
            </div>
          </div>

          <!-- Tipo de fármaco -->
          <div class="space-y-2 mb-4">
            <label class="text-sm font-medium text-gray-700">Filtrar por categoría</label>
            <select
              v-model="categoriaFiltro"
              class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
            >
              <option value="">Todas las categorías</option>
              <option v-for="categoria in categoriasUnicas" :key="categoria" :value="categoria">
                {{ obtenerTextoDeCategoria(categoria) }}
              </option>
            </select>
          </div>

          <!-- Fármaco específico -->
          <div class="space-y-2 mb-4" v-if="farmacosFiltrados.length > 0">
            <label class="text-sm font-medium text-gray-700">Seleccionar fármaco *</label>
            
            <!-- Búsqueda rápida -->
            <div class="mb-2">
              <input
                v-model="busquedaFarmaco"
                type="text"
                placeholder="Buscar por nombre comercial o genérico..."
                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
              />
            </div>

            <select
              v-model="selectedDrug"
              class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
            >
              <option disabled value="">Seleccionar fármaco</option>
              <optgroup v-for="categoria in categoriasAgrupadas" :key="categoria.nombre" :label="categoria.nombre">
                <option 
                  v-for="farmaco in categoria.farmacos" 
                  :key="farmaco.id" 
                  :value="farmaco"
                >
                  {{ farmaco.nombre_comercial }} ({{ farmaco.nombre_generico }})
                </option>
              </optgroup>
            </select>
            
            <p class="text-xs text-gray-500 mt-1">
              Mostrando {{ farmacosFiltrados.length }} de {{ tiposFarmaco.length }} fármacos
            </p>
          </div>

          <!-- Mensaje si no hay fármacos -->
         <div v-if="!cargando && !error && farmacosFiltrados.length === 0" class="mb-4">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <div>
                    <p class="text-sm text-yellow-800 font-medium">
                    {{ esVeterinario ? 'Tu catálogo está vacío' : 'No hay fármacos disponibles' }}
                    </p>
                    <p class="text-xs text-yellow-700 mt-1">
                    {{ 
                        esVeterinario 
                        ? 'Aún no has registrado fármacos en tu catálogo personal'
                        : 'Solo veterinarios pueden registrar y ver fármacos'
                    }}
                    </p>
                </div>
                </div>
                <button 
                v-if="esVeterinario"
                @click="abrirRegistroFarmaco"
                class="mt-3 w-full inline-flex items-center justify-center gap-2 bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-yellow-700 transition-colors"
                >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Registrar primer fármaco
                </button>
            </div>
          </div>

          <!-- Información del fármaco seleccionado -->
          <div v-if="selectedDrug" class="space-y-4 border-t pt-4">
            <!-- Resumen del fármaco -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-start justify-between mb-2">
                <div>
                  <h4 class="font-bold text-blue-900">{{ selectedDrug.nombre_comercial }}</h4>
                  <p class="text-sm text-blue-700">{{ selectedDrug.nombre_generico }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full capitalize">
                  {{ obtenerTextoDeCategoria(selectedDrug.categoria) }}
                </span>
              </div>
              
              <div class="grid grid-cols-2 gap-2 text-xs text-blue-600 mt-2">
                <div>
                  <span class="font-medium">Dosis sugerida:</span>
                  <span class="ml-1">{{ selectedDrug.dosis }} {{ selectedDrug.unidad }} / {{ selectedDrug.frecuencia_unidad }}</span>
                </div>
                <div>
                  <span class="font-medium">Vía:</span>
                  <span class="ml-1 capitalize">{{ selectedDrug.via_administracion }}</span>
                </div>
              </div>
              
              <div v-if="selectedDrug.especies && selectedDrug.especies.length" class="mt-2">
                <span class="text-xs font-medium text-blue-600">Especies:</span>
                <span class="ml-2 text-xs text-blue-700">
                  {{ obtenerTextoDeEspecies(selectedDrug.especies) }}
                </span>
              </div>
            </div>

            <!-- Datos clínicos personalizados -->
            <div class="space-y-4">
              <h5 class="font-medium text-gray-700 border-b pb-2">Personalizar prescripción</h5>
              
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700">
                  Dosis prescrita *
                </label>

                <div class="flex items-center gap-2">
                  <!-- Cantidad -->
                  <input 
                    v-model="dose"
                    type="number"
                    placeholder="Ej: 5"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-3 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500
                          focus:border-blue-500 transition-all"
                  />

                  <!-- Unidad -->
                  <select
                    v-model="doseUnit"
                    class="rounded-lg border border-gray-300 px-3 py-3 text-sm
                          bg-white focus:outline-none focus:ring-2
                          focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option v-for="unit in weightUnits" :key="unit" :value="unit">
                      {{ unit }}
                    </option>
                  </select>
                </div>

                <p class="text-xs text-gray-400">
                  Dosis estándar: {{ selectedDrug.dosis }} {{ selectedDrug.unidad }}
                </p>
              </div>
            </div>

            <div class="space-y-2 mb-4">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input
                  v-model="esDosisUnica"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:border-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">
                  ¿Es una dosis única?
                </span>
              </label>
              <p class="text-xs text-gray-400 ml-6">
                Marca esta casilla si el fármaco se administra solo una vez
              </p>
            </div>


               <!-- Campos de duración - mostrar solo si NO es dosis única -->
              <div v-if="!esDosisUnica" class="space-y-2">
                <label class="text-sm font-medium text-gray-700">
                  Duración de la dosis
                </label>

                <div class="flex items-center gap-2">
                  <!-- Cantidad -->
                  <input
                    v-model="duracionValor"
                    type="number"
                    min="0"
                    placeholder="Ej: 7"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-3 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500
                          focus:border-blue-500 transition-all"
                  />

                  <!-- Unidad de tiempo -->
                  <select
                    v-model="duracionUnidad"
                    class="rounded-lg border border-gray-300 px-3 py-3 text-sm
                          bg-white focus:outline-none focus:ring-2
                          focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="min">Min</option>
                    <option value="h">Horas</option>
                    <option value="d">Días</option>
                  </select>
                </div>
              </div>

              <!-- Campos de frecuencia - mostrar solo si NO es dosis única -->
              <div v-if="!esDosisUnica" class="space-y-2">
                <label class="text-sm font-medium text-gray-700">
                  Frecuencia de Administración  
                </label>

                <div class="flex items-center gap-2">
                  <!-- Cantidad -->
                  <input
                    v-model="frecuenciaValor"
                    type="number"
                    min="0"
                    placeholder="Ej: 7"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-3 text-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500
                          focus:border-blue-500 transition-all"
                  />

                  <!-- Unidad de tiempo -->
                  <select
                    v-model="frecuenciaUnidad" 
                    class="rounded-lg border border-gray-300 px-3 py-3 text-sm
                          bg-white focus:outline-none focus:ring-2
                          focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="h">Horas</option>
                    <option value="d">Días</option>
                  </select>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="border-t px-6 py-4 bg-gray-50">
        <div class="flex items-center justify-between">
          <button
            @click="resetearFormulario"
            type="button"
            :disabled="cargando"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Limpiar
          </button>
          
          <div class="flex items-center gap-3">
            <button
              @click="cerrarModal"
              type="button"
              :disabled="cargando"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Cancelar
            </button>
            
            <button
              @click="emitDrug"
              :disabled="!canSubmit || cargando"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <svg v-if="canSubmit && !cargando" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              <span v-if="cargando">Procesando...</span>
              <span v-else>Asociar Fármaco</span>
            </button>
          </div>
        </div>
        
        <!-- Indicador de campos obligatorios -->
        <div class="mt-3 pt-3 border-t border-gray-200">
          <p class="text-xs text-gray-500">
            <span class="text-red-500">*</span> Campos obligatorios
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
  mostrar: {
    type: Boolean,
    default: false
  },
  mascotaId: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['cerrar', 'add'])

const router = useRouter()
const { accessToken, isAuthenticated, checkAuth, user } = useAuth()

// Estados reactivos
// Estados reactivos
const tiposFarmaco = ref([])
const cargando = ref(false)
const error = ref(null)
const selectedDrug = ref(null)
const categoriaFiltro = ref('')
const busquedaFarmaco = ref('')
const dose = ref('')
const doseUnit = ref('mg')
const weightUnits = [
  'mg',
  'g',
  'µg',
  'ng'
]
const duracionValor = ref('')
const frecuenciaUnidad = ref('d') 
const duracionUnidad = ref('d') 
const frecuenciaValor = ref('') 
const frequency = ref('')
const duracion = ref('')
const notes = ref('')

const esDosisUnica = ref(true)


// Watch para esDosisUnica - ocultar/mostrar campos de duración y frecuencia
watch(esDosisUnica, (nuevoValor) => {
  if (nuevoValor) {
    // Si es dosis única, limpiar los campos de duración y frecuencia
    duracionValor.value = ''
    frecuenciaValor.value = ''
    frecuenciaUnidad.value = 'd'
    duracionUnidad.value = 'd'
  }
}, { immediate: false })


// Verificar si el usuario es veterinario - CON VALIDACIÓN DE NULL
const esVeterinario = computed(() => {
  console.log('🔍 Verificando si es veterinario, user:', user.value)
  
  if (!user.value) {
    console.log('⚠️ User es null, intentando cargar...')
    // No intentar cargar aquí, se manejará en cargarFarmacos
    return false
  }
  
  if (!user.value.userable_type) {
    console.log('⚠️ userable_type no está definido:', user.value)
    return false
  }
  
  const isVet = user.value.userable_type === 'App\\Models\\Veterinario'
  console.log('🏥 Verificación de veterinario:', {
    userable_type: user.value.userable_type,
    isVeterinario: isVet
  })
  return isVet
})

// Función para cargar el usuario si es necesario
const cargarUsuarioSiEsNecesario = async () => {
  try {
    console.log('🔄 Verificando autenticación y usuario...')
    
    // Si hay token pero no usuario, verificar autenticación
    if (accessToken.value && !user.value) {
      console.log('🔐 Hay token pero no usuario, verificando...')
      const isAuth = await checkAuth()
      console.log('✅ Resultado de checkAuth:', isAuth)
      
      if (isAuth && !user.value) {
        console.log('🔄 Auth ok pero user null, forzando fetchUser')
        await fetchUser()
      }
    }
    
    console.log('👤 Estado final del usuario:', {
      user: user.value,
      userable_type: user.value?.userable_type,
      esVeterinario: esVeterinario.value
    })
    
    return !!user.value
  } catch (err) {
    console.error('❌ Error cargando usuario:', err)
    return false
  }
}

// Cargar fármacos desde la API
const cargarFarmacos = async () => {
  try {
    console.log('🚀 Iniciando carga de fármacos...')
    
    cargando.value = true
    error.value = null
    tiposFarmaco.value = []
    
    // 1. Cargar usuario si es necesario
    const usuarioCargado = await cargarUsuarioSiEsNecesario()
    
    if (!usuarioCargado) {
      error.value = 'No se pudo cargar la información del usuario. Por favor, vuelva a iniciar sesión.'
      return
    }
    
    // 2. Verificar que sea veterinario
    if (!esVeterinario.value) {
      console.log('❌ Usuario no es veterinario:', {
        user: user.value,
        userable_type: user.value?.userable_type
      })
      error.value = 'Solo los veterinarios pueden acceder al catálogo de fármacos.'
      return
    }
    
    // 3. Hacer la petición
    console.log('🌐 Haciendo petición a /api/tipos-farmaco')
    
    const response = await fetch('/api/tipos-farmaco', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })
    
    console.log('📡 Respuesta HTTP:', {
      status: response.status,
      statusText: response.statusText,
      ok: response.ok
    })
    
    if (!response.ok) {
      const errorText = await response.text()
      console.error('❌ Error del servidor:', errorText)
      
      let errorMessage = `Error ${response.status}: ${response.statusText}`
      try {
        const errorJson = JSON.parse(errorText)
        errorMessage = errorJson.message || errorJson.error || errorMessage
      } catch {
        errorMessage = errorText.substring(0, 200) || errorMessage
      }
      
      throw new Error(errorMessage)
    }
    
    const result = await response.json()
    console.log('📦 Resultado completo de la API:', {
      success: result.success,
      message: result.message,
      count: result.count || 0,
      dataLength: result.data?.length || 0
    })
    
    if (result.success) {
      tiposFarmaco.value = result.data || []
      console.log(`✅ ${tiposFarmaco.value.length} fármacos cargados`)
      
      if (tiposFarmaco.value.length === 0) {
        console.log('ℹ️ El veterinario no tiene fármacos registrados')
        // No establecer error, solo mostrar mensaje en UI
      }
    } else {
      console.warn('⚠️ La API devolvió success: false', result)
      error.value = result.message || 'Error en la respuesta del servidor'
    }
  } catch (err) {
    console.error('❌ Error crítico cargando fármacos:', err)
    console.error('🔍 Stack trace:', err.stack)
    error.value = err.message || 'Error al cargar el catálogo de fármacos'
    tiposFarmaco.value = []
  } finally {
    cargando.value = false
    console.log('🏁 Carga de fármacos finalizada')
  }
}


// Categorías únicas para el filtro
const categoriasUnicas = computed(() => {
  const categorias = [...new Set(tiposFarmaco.value.map(f => f.categoria))]
  console.log('📊 Categorías únicas encontradas:', categorias)
  return categorias.sort()
})

// Fármacos filtrados por categoría y búsqueda
const farmacosFiltrados = computed(() => {
  let filtrados = tiposFarmaco.value

  // Filtrar por categoría
  if (categoriaFiltro.value) {
    filtrados = filtrados.filter(f => f.categoria === categoriaFiltro.value)
  }

  // Filtrar por búsqueda
  if (busquedaFarmaco.value.trim()) {
    const termino = busquedaFarmaco.value.toLowerCase().trim()
    filtrados = filtrados.filter(f => 
      f.nombre_comercial.toLowerCase().includes(termino) ||
      f.nombre_generico.toLowerCase().includes(termino) ||
      (f.composicion && f.composicion.toLowerCase().includes(termino))
    )
  }

  return filtrados
})

// Agrupar fármacos por categoría para el select
const categoriasAgrupadas = computed(() => {
  const grupos = {}
  
  farmacosFiltrados.value.forEach(farmaco => {
    const categoria = farmaco.categoria
    if (!grupos[categoria]) {
      grupos[categoria] = {
        nombre: obtenerTextoDeCategoria(categoria),
        farmacos: []
      }
    }
    grupos[categoria].farmacos.push(farmaco)
  })
  
  // Convertir a array y ordenar
  return Object.values(grupos).sort((a, b) => a.nombre.localeCompare(b.nombre))
})

// Validación de formulario
const canSubmit = computed(() => {
  // Verificar que haya un fármaco seleccionado
  if (!selectedDrug.value) {
    console.log('❌ No hay fármaco seleccionado')
    return false
  }
  
  // Verificar que la dosis no esté vacía (puede ser 0, pero debe ser un número válido)
  if (dose.value === '' || dose.value === null || dose.value === undefined) {
    console.log('❌ Dosis vacía')
    return false
  }
  
  // Si NO es dosis única, validar que tenga frecuencia
  if (!esDosisUnica.value) {
    // Verificar que frecuenciaValor no esté vacío y sea un número válido
    if (!frecuenciaValor.value || frecuenciaValor.value === '' || frecuenciaValor.value === null || frecuenciaValor.value === undefined) {
      console.log('❌ Frecuencia vacía cuando NO es dosis única')
      return false
    }
  }
  
  console.log('✅ Validación aprobada:', {
    selectedDrug: !!selectedDrug.value,
    dose: dose.value,
    esDosisUnica: esDosisUnica.value,
    frecuenciaValor: frecuenciaValor.value,
    tieneFrecuencia: !esDosisUnica.value ? !!frecuenciaValor.value : 'N/A'
  })
  
  return true
})


// Convertir categoría a texto legible
const obtenerTextoDeCategoria = (categoria) => {
  const map = {
    'analgesico': 'Analgésico',
    'antibiotico': 'Antibiótico',
    'antiparasitario': 'Antiparasitario',
    'antiinflamatorio': 'Antiinflamatorio',
    'antifungico': 'Antifúngico',
    'antiviral': 'Antiviral',
    'anestesico': 'Anestésico',
    'otro': 'Otro'
  }
  return map[categoria] || categoria
}

// Convertir especies a texto
const obtenerTextoDeEspecies = (especies) => {
  if (!especies || !Array.isArray(especies)) return 'No especificado'
  
  const map = {
    'canino': 'Canino',
    'felino': 'Felino',
    'equino': 'Equino',
    'bovino': 'Bovino',
    'ave': 'Ave',
    'pez': 'Pez',
    'otro': 'Otro'
  }
  
  return especies.map(especie => map[especie] || especie).join(', ')
}

// Cargar fármacos cuando se abre el modal
watch(() => props.mostrar, (nuevoValor) => {
  if (nuevoValor) {
    console.log('🎯 Modal abierto, cargando fármacos...')
    console.log('📋 Props recibidos:', {
      mostrar: props.mostrar,
      mascotaId: props.mascotaId
    })
    resetearFormulario()
    
    // Usar nextTick para asegurar que el DOM esté listo
    nextTick(() => {
      cargarFarmacos()
    })
  }
})

// Agregar watch para debug
watch(() => user.value, (nuevoUsuario) => {
  console.log('👤 Usuario actualizado:', nuevoUsuario)
  console.log('🏥 ¿Es veterinario?:', esVeterinario.value)
}, { immediate: true })

// Agregar watch para accessToken
watch(() => accessToken.value, (nuevoToken) => {
  console.log('🔑 Token actualizado:', nuevoToken ? 'Presente' : 'Ausente')
}, { immediate: true })

// Enviar datos del fármaco
function emitDrug() {
  if (!canSubmit.value) return
  
  // Si es dosis única, no incluir duración ni frecuencia
  let duracion = ''
  let frecuencia = ''
  
  if (!esDosisUnica.value) {
    // Solo incluir duración y frecuencia si NO es dosis única
    duracion = duracionValor.value ? `${duracionValor.value} ${duracionUnidad.value}` : ''
    frecuencia = frecuenciaValor.value ? `${frecuenciaValor.value} ${frecuenciaUnidad.value}` : ''
  }
  
  console.log('📤 Enviando datos del fármaco:', {
    drug: selectedDrug.value,
    dose: dose.value,
    doseUnit: doseUnit.value,
    frequency: frecuencia,
    duracion: duracion,
    esDosisUnica: esDosisUnica.value,
    notes: notes.value
  })
  
  emit('add', {
    drug: {
      id: selectedDrug.value.id,
      nombre_comercial: selectedDrug.value.nombre_comercial,
      nombre_generico: selectedDrug.value.nombre_generico,
      categoria: selectedDrug.value.categoria,
      categoria_texto: obtenerTextoDeCategoria(selectedDrug.value.categoria),
      dosis_estandar: selectedDrug.value.dosis,
      unidad: selectedDrug.value.unidad,
      frecuencia_estandar: selectedDrug.value.frecuencia,
      via_administracion: selectedDrug.value.via_administracion
    },
    dose: dose.value,
    doseUnit: doseUnit.value,
    frequency: frecuencia,
    duracion: duracion,
    esDosisUnica: esDosisUnica.value,
    notes: notes.value,
    timestamp: new Date().toISOString()
  })
  
  resetearFormulario()
  cerrarModal()
}

function resetearFormulario() {
  selectedDrug.value = null
  categoriaFiltro.value = ''
  busquedaFarmaco.value = ''
  dose.value = ''
  doseUnit.value = 'mg'
  duracionValor.value = ''
  duracionUnidad.value = 'd'
  frecuenciaValor.value = '' // <-- Limpiar esta también
  frecuenciaUnidad.value = 'd'
  notes.value = ''
  esDosisUnica.value = true // Resetear a true
}

function cerrarModal() {
  if (cargando.value) return
  resetearFormulario()
  emit('cerrar')
}

// Navegar al registro de nuevo fármaco
function abrirRegistroFarmaco() {
  if (!esVeterinario.value) {
    alert('Solo veterinarios pueden registrar fármacos.')
    return
  }
  
  const routeData = {
    path: '/registro/registroFarmaco',
    query: {
      from: `/mascotas/${props.mascotaId}/cirugias/crear`,
      mascotaId: props.mascotaId,
      returnTo: 'registroCirugia'
    }
  }
  
  // Cerrar modal primero
  cerrarModal()
  
  // Usar setTimeout para asegurar que el modal se cierra antes de navegar
  setTimeout(() => {
    router.push(routeData)
  }, 100)
}

// Cerrar con tecla Escape
const handleEscape = (e) => {
  if (e.key === 'Escape' && props.mostrar && !cargando.value) {
    cerrarModal()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleEscape)
  
  // Log para debug
  console.log('🔄 Modal de selección de fármacos montado')
  console.log('📋 Props iniciales:', {
    mostrar: props.mostrar,
    mascotaId: props.mascotaId
  })
  console.log('🔍 Estado de autenticación completo:', {
    user: user.value,
    isAuthenticated: isAuthenticated.value,
    accessToken: accessToken.value ? 'Presente' : 'Ausente',
    localStorageUser: localStorage.getItem('user'),
    localStorageToken: localStorage.getItem('token') ? 'Presente' : 'Ausente'
  })
  
  // Forzar verificación si el modal ya está abierto al montar
  if (props.mostrar) {
    console.log('📂 Modal ya está abierto al montar, cargando fármacos...')
    nextTick(() => {
      cargarFarmacos()
    })
  }
})
</script>

<style scoped>
/* Animaciones suaves */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

/* Scrollbar personalizado */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

/* Estilos para grupos de opciones */
optgroup {
  font-weight: 600;
  color: #4b5563;
  background-color: #f9fafb;
}

optgroup option {
  font-weight: normal;
  color: #374151;
  padding-left: 20px;
}
</style>

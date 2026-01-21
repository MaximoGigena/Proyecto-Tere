<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar' : 'Registrar' }} Tipo de Desparasitación</h1>

    <form @submit.prevent="esEdicion ? actualizarDesparasitacion() : registrarDesparasitacion()" class="space-y-4">
      <!-- DATOS OBLIGATORIOS -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Obligatorios</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna izquierda -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Nombre del desparasitante</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="desparasitacion.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Nombre comercial o genérico"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <!-- COMPONENTE DE SELECCIÓN DE PARÁSITOS (ÁRBOL JERÁRQUICO) -->
          <div>
            <SeleccionarParasito 
              ref="parasitosSelector"
              :initial-selection="parasitosInitialSelection"
              @selection-change="handleParasitosChange"
              @input="handleParasitosInput"
            />
          </div>

          <div>
            <label class="block font-medium">Vía de administración</label>
            <select v-model="desparasitacion.via" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="oral">Oral</option>
              <option value="topica">Tópica</option>
              <option value="inyectable">Inyectable</option>
              <option value="otra">Otra</option>
            </select>
          </div>

          <div>
            <label class="block font-medium mb-2">Especie objetivo</label>
            <CarruselEspecieVeterinario v-model="especiesSeleccionadas" />
            <p v-if="!especiesSeleccionadas.length" class="text-sm text-gray-500 mt-1">
              Seleccione una o más especies objetivo
            </p>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Edad mínima de aplicación</label>
            <div class="flex">
              <input 
                v-model="desparasitacion.edadMinima" 
                type="number" 
                min="0" 
                step="0.5" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Edad" 
              />
              <select 
                v-model="desparasitacion.edadUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
              >
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Frecuencia estándar</label>
            <div class="flex">
              <input 
                v-model="desparasitacion.frecuencia" 
                type="number" 
                min="1" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Cantidad" 
              />
              <select 
                v-model="desparasitacion.frecuenciaUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
              >
                <option value="dias">Días</option>
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- DATOS OPCIONALES -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Opcionales</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
        <!-- RECOMENDACIONES PROFESIONALES -->
        <div>
          <label class="block font-medium">Recomendaciones profesionales</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputRecomendaciones" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Administrar con alimento, Evitar baño por 48h, etc."
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('recomendaciones')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors"
            >
              <font-awesome-icon :icon="['fas', 'plus']" />
            </button>
          </div>
          <div v-if="recomendacionesAgregadas.length > 0" class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
            <div v-for="(recomendacion, index) in recomendacionesAgregadas" :key="index" 
                class="flex items-center justify-between bg-green-50 p-2 rounded text-sm">
              <span>{{ recomendacion }}</span>
              <button 
                type="button"
                @click="eliminarItem('recomendaciones', index)"
                class="text-red-500 hover:text-red-700"
              >
                <font-awesome-icon :icon="['fas', 'times']" />
              </button>
            </div>
          </div>
        </div>

        <!-- RIESGOS CONOCIDOS -->
        <div>
          <label class="block font-medium">Riesgos conocidos</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputRiesgos" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Vómitos ocasionales, Letargo temporal, Reacción alérgica, etc."
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('riesgos')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors"
            >
              <font-awesome-icon :icon="['fas', 'plus']" />
            </button>
          </div>
          <div v-if="riesgosAgregados.length > 0" class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
            <div v-for="(riesgo, index) in riesgosAgregados" :key="index" 
                class="flex items-center justify-between bg-green-50 p-2 rounded text-sm">
              <span>{{ riesgo }}</span>
              <button 
                type="button"
                @click="eliminarItem('riesgos', index)"
                class="text-red-500 hover:text-red-700"
              >
                <font-awesome-icon :icon="['fas', 'times']" />
              </button>
            </div>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Dosis recomendada</label>
          <input 
            v-model="desparasitacion.dosis" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Ej: 1 comprimido, 0.5 ml, etc."
          />
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="button" @click="cancelar" class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors">Cancelar</button>
        <button type="submit" :disabled="submitting" class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:opacity-50">
          {{ submitting ? 'Guardando...' : (esEdicion ? 'Actualizar' : '+ Tipo') }}
        </button>
      </div>
    </form>
  </div>

  <!-- Modal de Confirmación de Modificación -->
  <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
      <div class="text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <font-awesome-icon :icon="['fas', 'check']" class="text-green-600 text-2xl" />
        </div>
        
        <h3 class="text-xl font-bold text-gray-800 mb-2">¡Modificación Exitosa!</h3>
        
        <p class="text-gray-600 mb-6">
          El tipo de desparasitación <strong>"{{ desparasitacion.nombre }}"</strong> ha sido actualizado correctamente.
        </p>

        <div class="flex justify-center gap-3">
          <button 
            @click="irALista" 
            class="bg-blue-500 text-white px-6 py-2 rounded-full hover:bg-blue-600 transition-colors"
          >
            Ver Lista
          </button>
          <button 
            @click="continuarEditando" 
            class="bg-gray-500 text-white px-6 py-2 rounded-full hover:bg-gray-600 transition-colors"
          >
            Seguir Editando
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import CarruselEspecieVeterinario from '@/components/ElementosGraficos/CarruselEspecieVeterinario.vue'
import SeleccionarParasito from '@/components/ElementosGraficos/SeleccionarParasito.vue' // Asegúrate de que la ruta sea correcta

// Inputs temporales para agregar elementos
const inputRecomendaciones = ref('')
const inputRiesgos = ref('')

// Arrays para almacenar elementos agregados
const recomendacionesAgregadas = ref([])
const riesgosAgregados = ref([])

const especiesSeleccionadas = ref([])

// Referencia al componente de selección de parásitos
const parasitosSelector = ref(null)

// Datos iniciales para el componente SeleccionarParasito
const parasitosInitialSelection = ref({
  internal: {
    predefined: [],
    other: ''
  },
  external: {
    predefined: [],
    other: ''
  }
})

// Watch para sincronizar arrays con los campos de la desparasitación
watch([recomendacionesAgregadas, riesgosAgregados], () => {
  desparasitacion.recomendaciones = recomendacionesAgregadas.value.length > 0 
    ? recomendacionesAgregadas.value.join(', ') 
    : ''
  
  desparasitacion.riesgos = riesgosAgregados.value.length > 0 
    ? riesgosAgregados.value.join(', ') 
    : ''
}, { deep: true })

watch(especiesSeleccionadas, (val) => {
  desparasitacion.especies = [...val]
}, { deep: true, flush: 'post' })

const router = useRouter()
const route = useRoute()
const { accessToken, checkAuth, redirectByRole } = useAuth()

const loading = ref(true)
const submitting = ref(false)
const showModal = ref(false)
const esEdicion = ref(false)
const tipoId = ref(null)

// Ahora usamos el formato que espera el componente SeleccionarParasito
const desparasitacion = reactive({
  nombre: '',
  // Cambiamos la estructura para compatibilidad con el árbol de parásitos
  parasitosData: {
    internal: {
      predefined: [],
      other: ''
    },
    external: {
      predefined: [],
      other: ''
    }
  },
  via: '',
  especies: [],
  edadMinima: '',
  edadUnidad: 'semanas',
  frecuencia: '',
  frecuenciaUnidad: 'meses',
  recomendaciones: '',
  riesgos: '',
  dosis: ''
})

// Manejar cambios en la selección de parásitos
const handleParasitosChange = (selectedData) => {
  console.log('Parásitos seleccionados:', selectedData)
  
  // Actualizar los datos en el formulario principal
  desparasitacion.parasitosData = {
    internal: {
      predefined: selectedData.internal.predefined || [],
      other: selectedData.internal.other || ''
    },
    external: {
      predefined: selectedData.external.predefined || [],
      other: selectedData.external.other || ''
    }
  }
}

// Para compatibilidad con v-model
const handleParasitosInput = (value) => {
  desparasitacion.parasitosData = value
}

// Función para agregar elementos
const agregarItem = (tipo) => {
  let inputValue, arrayDestino
  
  switch(tipo) {
    case 'recomendaciones':
      inputValue = inputRecomendaciones.value.trim()
      arrayDestino = recomendacionesAgregadas
      break
    case 'riesgos':
      inputValue = inputRiesgos.value.trim()
      arrayDestino = riesgosAgregados
      break
    default:
      return
  }
  
  if (inputValue) {
    const existe = arrayDestino.value.some(item => 
      item.toLowerCase() === inputValue.toLowerCase()
    )
    
    if (!existe) {
      arrayDestino.value.push(inputValue)
      
      switch(tipo) {
        case 'recomendaciones': inputRecomendaciones.value = ''; break
        case 'riesgos': inputRiesgos.value = ''; break
      }
    } else {
      alert('Este elemento ya ha sido agregado')
    }
  }
}

// Función para eliminar elementos
const eliminarItem = (tipo, index) => {
  switch(tipo) {
    case 'recomendaciones':
      recomendacionesAgregadas.value.splice(index, 1)
      break
    case 'riesgos':
      riesgosAgregados.value.splice(index, 1)
      break
  }
}

// Computed para validar el formulario (actualizada para el nuevo formato)
const isFormValid = computed(() => {
  // Verificar si hay al menos un parásito seleccionado
  const hasInternalParasitos = 
    (desparasitacion.parasitosData.internal.predefined?.length > 0) ||
    (desparasitacion.parasitosData.internal.other?.trim() !== '')
  
  const hasExternalParasitos = 
    (desparasitacion.parasitosData.external.predefined?.length > 0) ||
    (desparasitacion.parasitosData.external.other?.trim() !== '')
  
  const hasParasitos = hasInternalParasitos || hasExternalParasitos
  
  return desparasitacion.nombre.trim() !== '' &&
         hasParasitos &&
         desparasitacion.via !== '' &&
         desparasitacion.especies.length > 0 &&
         desparasitacion.edadMinima !== '' &&
         desparasitacion.frecuencia !== ''
})

// Verificar si es edición
const verificarEdicion = () => {
  if (route.params.id) {
    esEdicion.value = true
    tipoId.value = route.params.id
    cargarDatosDesparasitacion()
  }
}

// Cargar datos para edición
const cargarDatosDesparasitacion = async () => {
  try {
    loading.value = true
    const response = await fetch(`/api/tipos-desparasitacion/${tipoId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('Error al cargar los datos')
    }

    const data = await response.json()

    if (data.success) {
      const tipo = data.data
      
      // Mapear los datos del API al formulario
      desparasitacion.nombre = tipo.nombre || ''
      desparasitacion.via = tipo.via_administracion || ''
      desparasitacion.edadMinima = tipo.edad_minima || ''
      desparasitacion.edadUnidad = tipo.edad_unidad || 'semanas'
      desparasitacion.frecuencia = tipo.frecuencia || ''
      desparasitacion.frecuenciaUnidad = tipo.frecuencia_unidad || 'meses'
      desparasitacion.recomendaciones = tipo.recomendaciones || ''
      desparasitacion.riesgos = tipo.riesgos || ''
      desparasitacion.dosis = tipo.dosis_recomendada || ''
      
      // Convertir strings del backend a arrays
      recomendacionesAgregadas.value = desparasitacion.recomendaciones 
        ? desparasitacion.recomendaciones.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      riesgosAgregados.value = desparasitacion.riesgos 
        ? desparasitacion.riesgos.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      // Cargar especies en el carrusel
      if (Array.isArray(tipo.especies)) {
        especiesSeleccionadas.value = [...tipo.especies]
      }

      // Cargar datos de parásitos (convertir del formato antiguo al nuevo)
      if (Array.isArray(tipo.parasitos)) {
        // Convertir el array simple al formato del árbol
        const internalPredefined = []
        const externalPredefined = []
        let internalOther = ''
        let externalOther = ''
        
        // Mapear los parásitos antiguos a los nuevos IDs
        tipo.parasitos.forEach(parasito => {
          // Mapeo de valores antiguos a IDs del árbol
          const mapping = {
            'internos': { id: 'ascaris', category: 'internal' },
            'externos': { id: 'pulgas', category: 'external' },
            'pulgas': { id: 'pulgas', category: 'external' },
            'garrapatas': { id: 'garrapatas', category: 'external' },
            'otros': { custom: true }
          }
          
          const map = mapping[parasito]
          if (map) {
            if (map.custom) {
              // Para "otros", usar el campo otros_parasitos
              const otrosText = tipo.otros_parasitos || ''
              // Determinar si es interno o externo basado en el texto
              if (otrosText.toLowerCase().includes('interno')) {
                internalOther = otrosText
              } else {
                externalOther = otrosText
              }
            } else {
              if (map.category === 'internal') {
                internalPredefined.push({ id: map.id, name: '' })
              } else {
                externalPredefined.push({ id: map.id, name: '' })
              }
            }
          }
        })
        
        // Preparar datos iniciales para el componente
        parasitosInitialSelection.value = {
          internal: {
            predefined: internalPredefined,
            other: internalOther
          },
          external: {
            predefined: externalPredefined,
            other: externalOther
          }
        }
        
        // También actualizar el formulario principal
        desparasitacion.parasitosData = { ...parasitosInitialSelection.value }
      }

      console.log('📝 Datos cargados para edición:')
      console.log('🪱 Parásitos cargados:', desparasitacion.parasitosData)
    }
  } catch (error) {
    console.error('Error cargando datos:', error)
    alert('Error al cargar los datos del tipo de desparasitación')
  } finally {
    loading.value = false
  }
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los cambios no guardados se perderán.')) {
    router.back()
  }
}

// Preparar datos para enviar al backend
const prepareParasitosDataForBackend = () => {
  const result = {
    parasitos: [], // Array simple para compatibilidad
    otros_parasitos: ''
  }
  
  // Procesar parásitos internos
  if (desparasitacion.parasitosData.internal.predefined?.length > 0) {
    // Convertir IDs del árbol a los valores esperados por el backend
    desparasitacion.parasitosData.internal.predefined.forEach(p => {
      const mapToOld = {
        'ascaris': 'internos',
        'tenias': 'internos',
        'tricuridos': 'internos',
        'ancilostomas': 'internos',
        'dirofilaria': 'internos',
        'coccidios': 'internos',
        'giardia': 'internos'
      }
      if (mapToOld[p.id]) {
        if (!result.parasitos.includes(mapToOld[p.id])) {
          result.parasitos.push(mapToOld[p.id])
        }
      }
    })
  }
  
  // Procesar parásitos externos
  if (desparasitacion.parasitosData.external.predefined?.length > 0) {
    desparasitacion.parasitosData.external.predefined.forEach(p => {
      const mapToOld = {
        'pulgas': 'pulgas',
        'garrapatas': 'garrapatas',
        'acaros_piel': 'externos',
        'acaros_oido': 'externos',
        'piojos': 'externos',
        'mosquitos': 'externos',
        'moscas': 'externos'
      }
      if (mapToOld[p.id]) {
        if (!result.parasitos.includes(mapToOld[p.id])) {
          result.parasitos.push(mapToOld[p.id])
        }
      }
    })
  }
  
  // Procesar "otros" parásitos
  const otrosTexts = []
  if (desparasitacion.parasitosData.internal.other?.trim()) {
    otrosTexts.push(`Internos: ${desparasitacion.parasitosData.internal.other}`)
  }
  if (desparasitacion.parasitosData.external.other?.trim()) {
    otrosTexts.push(`Externos: ${desparasitacion.parasitosData.external.other}`)
  }
  
  if (otrosTexts.length > 0) {
    result.parasitos.push('otros')
    result.otros_parasitos = otrosTexts.join('; ')
  }
  
  return result
}

const registrarDesparasitacion = async () => {
  if (!isFormValid.value) {
    alert('Por favor complete todos los campos obligatorios')
    return
  }

  try {
    submitting.value = true

    // Preparar datos de parásitos para el backend
    const parasitosData = prepareParasitosDataForBackend()

    // Preparar los datos para enviar al servidor
    const payload = {
      nombre: desparasitacion.nombre,
      parasitos: parasitosData.parasitos,
      otros_parasitos: parasitosData.otros_parasitos,
      via_administracion: desparasitacion.via,
      especies: desparasitacion.especies,
      edad_minima: parseFloat(desparasitacion.edadMinima),
      edad_unidad: desparasitacion.edadUnidad,
      frecuencia: parseInt(desparasitacion.frecuencia),
      frecuencia_unidad: desparasitacion.frecuenciaUnidad,
      recomendaciones: desparasitacion.recomendaciones,
      riesgos: desparasitacion.riesgos,
      dosis_recomendada: desparasitacion.dosis
    }

    console.log('Datos a enviar:', payload)

    // Enviar datos al servidor con el token de autenticación
    const response = await fetch('/api/tipos-desparasitacion', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(payload)
    })

    const data = await response.json()

    if (data.success) {
      alert('Tipo de desparasitación registrado correctamente')
      router.push('/veterinarios/tipos/desparasitaciones')
    } else {
      if (data.errors) {
        const errorMessages = Object.values(data.errors).flat().join('\n')
        alert('Error al registrar: ' + errorMessages)
      } else {
        alert('Error al registrar: ' + data.message)
      }
    }
  } catch (error) {
    console.error('Error:', error)
    
    if (error.response?.status === 401) {
      alert('Su sesión ha expirado. Por favor, inicie sesión nuevamente.')
      router.push('/veterinario/login')
    } else {
      alert('Error al conectar con el servidor')
    }
  } finally {
    submitting.value = false
  }
}

const actualizarDesparasitacion = async () => {
  if (!isFormValid.value) {
    alert('Por favor complete todos los campos obligatorios')
    return
  }

  try {
    submitting.value = true

    // Preparar datos de parásitos para el backend
    const parasitosData = prepareParasitosDataForBackend()

    // Preparar los datos para enviar al servidor
    const payload = {
      nombre: desparasitacion.nombre,
      parasitos: parasitosData.parasitos,
      otros_parasitos: parasitosData.otros_parasitos,
      via_administracion: desparasitacion.via,
      especies: desparasitacion.especies,
      edad_minima: parseFloat(desparasitacion.edadMinima),
      edad_unidad: desparasitacion.edadUnidad,
      frecuencia: parseInt(desparasitacion.frecuencia),
      frecuencia_unidad: desparasitacion.frecuenciaUnidad,
      recomendaciones: desparasitacion.recomendaciones,
      riesgos: desparasitacion.riesgos,
      dosis_recomendada: desparasitacion.dosis
    }

    console.log('Actualizando datos:', payload)

    // Enviar datos al servidor con el token de autenticación
    const response = await fetch(`/api/tipos-desparasitacion/${tipoId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(payload)
    })

    const data = await response.json()

    if (data.success) {
      showModal.value = true
    } else {
      if (data.errors) {
        const errorMessages = Object.values(data.errors).flat().join('\n')
        alert('Error al actualizar: ' + errorMessages)
      } else {
        alert('Error al actualizar: ' + data.message)
      }
    }
  } catch (error) {
    console.error('Error:', error)
    
    if (error.response?.status === 401) {
      alert('Su sesión ha expirado. Por favor, inicie sesión nuevamente.')
      router.push('/veterinario/login')
    } else {
      alert('Error al conectar con el servidor')
    }
  } finally {
    submitting.value = false
  }
}

const irALista = () => {
  showModal.value = false
  router.push('/veterinarios/tipos/desparasitaciones')
}

const continuarEditando = () => {
  showModal.value = false
}

// Verificar autenticación al cargar el componente
onMounted(async () => {
  try {
    await checkAuth()
    verificarEdicion()
  } catch (error) {
    console.error('Error verificando autenticación:', error)
    router.push('/veterinario/login')
  } finally {
    loading.value = false
  }
})
</script>
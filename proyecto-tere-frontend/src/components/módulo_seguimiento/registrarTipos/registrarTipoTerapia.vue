<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Tipo de Terapia' : 'Registrar Tipo de Terapia' }}</h1>

    <form @submit.prevent="guardarTerapia" class="space-y-4">
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
            <label class="block font-medium">Nombre del tipo de terapia</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="terapia.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Fisioterapia postquirúrgica, Quimioterapia"
                  :disabled="cargando"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Descripción general</label>
            <textarea 
              v-model="terapia.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción detallada de la terapia"
              :disabled="cargando"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium mb-2">Especie objetivo</label>
            <CarruselEspecieVeterinario v-model="especiesSeleccionadas" :disabled="cargando" />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Duración típica del tratamiento</label>
            <div class="flex">
              <input 
                v-model="terapia.duracionValor" 
                type="number" 
                min="1" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Cantidad"
                :disabled="cargando"
              />
              <select 
                v-model="terapia.duracionUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
                :disabled="cargando"
              >
                <option value="sesiones">Sesiones</option>
                <option value="dias">Días</option>
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Frecuencia sugerida</label>
            <select v-model="terapia.frecuencia" required class="w-full border rounded p-2" :disabled="cargando">
              <option value="">Seleccione una opción</option>
              <option value="diaria">Diaria</option>
              <option value="semanal">Semanal</option>
              <option value="quincenal">Quincenal</option>
              <option value="mensual">Mensual</option>
              <option value="personalizada">Personalizada</option>
            </select>
          </div>

          <!-- REQUISITOS O CONDICIONES PREVIAS - MODIFICADO -->
          <div>
            <label class="block font-medium">Requisitos o condiciones previas</label>
            <div class="flex gap-2">
              <textarea 
                v-model="inputRequisitos" 
                rows="3" 
                class="w-full border rounded p-2" 
                placeholder="Condiciones que debe cumplir el paciente para esta terapia (una por línea)"
                :disabled="cargando"
              ></textarea>
              <button 
                type="button"
                @click="agregarItem('requisitos')"
                class="bg-blue-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-blue-600 transition-colors disabled:bg-blue-300"
                :disabled="cargando || !inputRequisitos.trim()"
              >
                <font-awesome-icon :icon="['fas', 'plus']" />
              </button>
            </div>
            <!-- Lista de requisitos agregados -->
            <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
              <div v-if="requisitosAgregados.length > 0" class="mt-2 space-y-1">
                <div v-for="(requisito, index) in requisitosAgregados" :key="index" 
                    class="flex items-center justify-between bg-blue-50 p-2 rounded text-sm">
                  <span>{{ requisito }}</span>
                  <button 
                    type="button"
                    @click="eliminarItem('requisitos', index)"
                    class="text-red-500 hover:text-red-700"
                    :disabled="cargando"
                  >
                    <font-awesome-icon :icon="['fas', 'times']" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- INDICACIONES CLÍNICAS COMUNES - MODIFICADO -->
          <div>
            <label class="block font-medium">Indicaciones clínicas comunes</label>
            <div class="flex gap-2">
              <textarea 
                v-model="inputIndicaciones" 
                rows="3" 
                class="w-full border rounded p-2" 
                placeholder="Casos o condiciones donde se recomienda esta terapia (una por línea)"
                :disabled="cargando"
              ></textarea>
              <button 
                type="button"
                @click="agregarItem('indicaciones')"
                class="bg-blue-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-blue-600 transition-colors disabled:bg-blue-300"
                :disabled="cargando || !inputIndicaciones.trim()"
              >
                <font-awesome-icon :icon="['fas', 'plus']" />
              </button>
            </div>
            <!-- Lista de indicaciones agregadas -->
            <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
              <div v-if="indicacionesAgregadas.length > 0" class="mt-2 space-y-1">
                <div v-for="(indicacion, index) in indicacionesAgregadas" :key="index" 
                    class="flex items-center justify-between bg-blue-50 p-2 rounded text-sm">
                  <span>{{ indicacion }}</span>
                  <button 
                    type="button"
                    @click="eliminarItem('indicaciones', index)"
                    class="text-red-500 hover:text-red-700"
                    :disabled="cargando"
                  >
                    <font-awesome-icon :icon="['fas', 'times']" />
                  </button>
                </div>
              </div>
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
        <!-- CONTRAINDICACIONES - MODIFICADO -->
        <div>
          <label class="block font-medium">Contraindicaciones</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputContraindicaciones" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Situaciones donde no aplicar esta terapia (una por línea)"
              :disabled="cargando"
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('contraindicaciones')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors disabled:bg-green-300"
              :disabled="cargando || !inputContraindicaciones.trim()"
            >
              <font-awesome-icon :icon="['fas', 'plus']" />
            </button>
          </div>
          <!-- Lista de contraindicaciones agregadas -->
          <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
            <div v-if="contraindicacionesAgregadas.length > 0" class="mt-2 space-y-1">
              <div v-for="(contraindicacion, index) in contraindicacionesAgregadas" :key="index" 
                  class="flex items-center justify-between bg-green-50 p-2 rounded text-sm">
                <span>{{ contraindicacion }}</span>
                <button 
                  type="button"
                  @click="eliminarItem('contraindicaciones', index)"
                  class="text-red-500 hover:text-red-700"
                  :disabled="cargando"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- RIESGOS O EFECTOS SECUNDARIOS - MODIFICADO -->
        <div>
          <label class="block font-medium">Riesgos o efectos secundarios</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputRiesgos" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Posibles efectos adversos conocidos (una por línea)"
              :disabled="cargando"
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('riesgos')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors disabled:bg-green-300"
              :disabled="cargando || !inputRiesgos.trim()"
            >
              <font-awesome-icon :icon="['fas', 'plus']" />
            </button>
          </div>
          <!-- Lista de riesgos agregados -->
          <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
            <div v-if="riesgosAgregados.length > 0" class="mt-2 space-y-1">
              <div v-for="(riesgo, index) in riesgosAgregados" :key="index" 
                  class="flex items-center justify-between bg-green-50 p-2 rounded text-sm">
                <span>{{ riesgo }}</span>
                <button 
                  type="button"
                  @click="eliminarItem('riesgos', index)"
                  class="text-red-500 hover:text-red-700"
                  :disabled="cargando"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- RECOMENDACIONES CLÍNICAS - MODIFICADO -->
        <div class="col-span-full">
          <label class="block font-medium">Recomendaciones clínicas</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputRecomendaciones" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Consejos para la aplicación de esta terapia (una por línea)"
              :disabled="cargando"
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('recomendaciones')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors disabled:bg-green-300"
              :disabled="cargando || !inputRecomendaciones.trim()"
            >
              <font-awesome-icon :icon="['fas', 'plus']" />
            </button>
          </div>
          <!-- Lista de recomendaciones agregadas -->
          <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
            <div v-if="recomendacionesAgregadas.length > 0" class="mt-2 space-y-1">
              <div v-for="(recomendacion, index) in recomendacionesAgregadas" :key="index" 
                  class="flex items-center justify-between bg-green-50 p-2 rounded text-sm">
                <span>{{ recomendacion }}</span>
                <button 
                  type="button"
                  @click="eliminarItem('recomendaciones', index)"
                  class="text-red-500 hover:text-red-700"
                  :disabled="cargando"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Observaciones adicionales</label>
          <textarea 
            v-model="terapia.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante sobre esta terapia"
            :disabled="cargando"
          ></textarea>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button 
          type="button" 
          @click="cancelar" 
          class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors disabled:bg-gray-300"
          :disabled="cargando"
        >
          Cancelar
        </button>
        <button 
          type="submit" 
          class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
          :disabled="cargando || !formularioValido"
        >
          {{ esEdicion ? 'Guardar' : '+ Tipo' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import CarruselEspecieVeterinario from '@/components/ElementosGraficos/CarruselEspecieVeterinario.vue'

const especiesSeleccionadas = ref([])

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const esEdicion = ref(false)
const terapiaId = ref(null)
const cargando = ref(false)

// Inputs temporales para agregar elementos
const inputRequisitos = ref('')
const inputIndicaciones = ref('')
const inputContraindicaciones = ref('')
const inputRiesgos = ref('')
const inputRecomendaciones = ref('')

// Arrays para almacenar elementos agregados
const requisitosAgregados = ref([])
const indicacionesAgregadas = ref([])
const contraindicacionesAgregadas = ref([])
const riesgosAgregados = ref([])
const recomendacionesAgregadas = ref([])

const terapia = reactive({
  nombre: '',
  descripcion: '',
  especie: '',
  duracionValor: '',
  duracionUnidad: 'sesiones',
  frecuencia: '',
  requisitos: '',
  indicaciones: '',
  contraindicaciones: '',
  riesgos: '',
  recomendaciones: '',
  observaciones: ''
})

// Computed para validar el formulario
const formularioValido = computed(() => {
  return terapia.nombre.trim() !== '' &&
         terapia.descripcion.trim() !== '' &&
         especiesSeleccionadas.value.length > 0 &&
         terapia.duracionValor !== '' &&
         terapia.frecuencia !== '' &&
         requisitosAgregados.value.length > 0 && // Validar que haya al menos un requisito
         indicacionesAgregadas.value.length > 0   // Validar que haya al menos una indicación
})

// Función para agregar elementos
const agregarItem = (tipo) => {
  let inputValue, arrayDestino
  
  switch(tipo) {
    case 'requisitos':
      inputValue = inputRequisitos.value.trim()
      arrayDestino = requisitosAgregados
      break
    case 'indicaciones':
      inputValue = inputIndicaciones.value.trim()
      arrayDestino = indicacionesAgregadas
      break
    case 'contraindicaciones':
      inputValue = inputContraindicaciones.value.trim()
      arrayDestino = contraindicacionesAgregadas
      break
    case 'riesgos':
      inputValue = inputRiesgos.value.trim()
      arrayDestino = riesgosAgregados
      break
    case 'recomendaciones':
      inputValue = inputRecomendaciones.value.trim()
      arrayDestino = recomendacionesAgregadas
      break
    default:
      return
  }
  
  if (inputValue) {
    // Verificar si ya existe (case insensitive)
    const existe = arrayDestino.value.some(item => 
      item.toLowerCase() === inputValue.toLowerCase()
    )
    
    if (!existe) {
      arrayDestino.value.push(inputValue)
      
      // Limpiar el input
      switch(tipo) {
        case 'requisitos': inputRequisitos.value = ''; break
        case 'indicaciones': inputIndicaciones.value = ''; break
        case 'contraindicaciones': inputContraindicaciones.value = ''; break
        case 'riesgos': inputRiesgos.value = ''; break
        case 'recomendaciones': inputRecomendaciones.value = ''; break
      }
    } else {
      alert('Este elemento ya ha sido agregado')
    }
  }
}

// Función para eliminar elementos
const eliminarItem = (tipo, index) => {
  switch(tipo) {
    case 'requisitos':
      requisitosAgregados.value.splice(index, 1)
      break
    case 'indicaciones':
      indicacionesAgregadas.value.splice(index, 1)
      break
    case 'contraindicaciones':
      contraindicacionesAgregadas.value.splice(index, 1)
      break
    case 'riesgos':
      riesgosAgregados.value.splice(index, 1)
      break
    case 'recomendaciones':
      recomendacionesAgregadas.value.splice(index, 1)
      break
  }
}

// Watch para sincronizar arrays con los campos del formulario
watch([requisitosAgregados, indicacionesAgregadas, contraindicacionesAgregadas, riesgosAgregados, recomendacionesAgregadas], () => {
  // Convertir arrays a strings separados por comas para el backend
  terapia.requisitos = requisitosAgregados.value.length > 0 
    ? requisitosAgregados.value.join(', ') 
    : ''
  
  terapia.indicaciones = indicacionesAgregadas.value.length > 0 
    ? indicacionesAgregadas.value.join(', ') 
    : ''
  
  terapia.contraindicaciones = contraindicacionesAgregadas.value.length > 0 
    ? contraindicacionesAgregadas.value.join(', ') 
    : ''
  
  terapia.riesgos = riesgosAgregados.value.length > 0 
    ? riesgosAgregados.value.join(', ') 
    : ''
  
  terapia.recomendaciones = recomendacionesAgregadas.value.length > 0 
    ? recomendacionesAgregadas.value.join(', ') 
    : ''
}, { deep: true })

// Verificar autenticación y cargar datos si es edición
onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      console.error('Debe iniciar sesión para acceder a esta página')
      setTimeout(() => {
        router.push('/')
      }, 2000)
      return
    }
  }

  // Verificar si estamos en modo edición
  if (route.params.id) {
    esEdicion.value = true
    terapiaId.value = route.params.id
    await cargarTerapia()
  }
})

// Cargar datos de la terapia para edición
const cargarTerapia = async () => {
  try {
    cargando.value = true
    const response = await fetch(`/api/tipos-terapia/${terapiaId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      if (data.success) {
        const terapiaData = data.data
        // Asignar los datos cargados al reactive object
        Object.assign(terapia, {
          nombre: terapiaData.nombre || '',
          descripcion: terapiaData.descripcion || '',
          especie: terapiaData.especie || '',
          duracionValor: terapiaData.duracion_valor || '',
          duracionUnidad: terapiaData.duracion_unidad || 'sesiones',
          frecuencia: terapiaData.frecuencia || '',
          requisitos: terapiaData.requisitos || '',
          indicaciones: terapiaData.indicaciones_clinicas || '',
          contraindicaciones: terapiaData.contraindicaciones || '',
          riesgos: terapiaData.riesgos_efectos_secundarios || '',
          recomendaciones: terapiaData.recomendaciones_clinicas || '',
          observaciones: terapiaData.observaciones || ''
        })

        // Cargar especies seleccionadas
        especiesSeleccionadas.value = terapiaData.especies || []

        // Cargar arrays desde strings del backend
        requisitosAgregados.value = terapia.requisitos 
          ? terapia.requisitos.split(',').map(s => s.trim()).filter(s => s !== '')
          : []
        
        indicacionesAgregadas.value = terapia.indicaciones 
          ? terapia.indicaciones.split(',').map(s => s.trim()).filter(s => s !== '')
          : []
        
        contraindicacionesAgregadas.value = terapia.contraindicaciones 
          ? terapia.contraindicaciones.split(',').map(s => s.trim()).filter(s => s !== '')
          : []
        
        riesgosAgregados.value = terapia.riesgos 
          ? terapia.riesgos.split(',').map(s => s.trim()).filter(s => s !== '')
          : []
        
        recomendacionesAgregadas.value = terapia.recomendaciones 
          ? terapia.recomendaciones.split(',').map(s => s.trim()).filter(s => s !== '')
          : []
        
        console.log('Arrays cargados:', {
          requisitos: requisitosAgregados.value,
          indicaciones: indicacionesAgregadas.value,
          contraindicaciones: contraindicacionesAgregadas.value,
          riesgos: riesgosAgregados.value,
          recomendaciones: recomendacionesAgregadas.value
        })

      }
    } else {
      throw new Error('Error al cargar los datos de la terapia')
    }
  } catch (error) {
    console.error('Error al cargar terapia:', error)
    alert('Error al cargar los datos de la terapia')
  } finally {
    cargando.value = false
  }
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const guardarTerapia = async () => {
  try {
    // Validar datos obligatorios usando el computed property
    if (!formularioValido.value) {
      alert('Por favor complete todos los campos obligatorios')
      return
    }

    cargando.value = true

    // Preparar datos para enviar
    const datosEnvio = {
      nombre: terapia.nombre.trim(),
      descripcion: terapia.descripcion.trim(),
      especies: especiesSeleccionadas.value,
      duracion_valor: parseInt(terapia.duracionValor),
      duracion_unidad: terapia.duracionUnidad,
      frecuencia: terapia.frecuencia,
      requisitos: terapia.requisitos,
      indicaciones_clinicas: terapia.indicaciones,
      contraindicaciones: terapia.contraindicaciones || '',
      riesgos_efectos_secundarios: terapia.riesgos || '',
      recomendaciones_clinicas: terapia.recomendaciones || '',
      observaciones: terapia.observaciones || ''
    }

    console.log('Datos a enviar:', datosEnvio)

    // Determinar método y URL según si es creación o edición
    const method = esEdicion.value ? 'PUT' : 'POST'
    const url = esEdicion.value ? `/api/tipos-terapia/${terapiaId.value}` : '/api/tipos-terapia'

    // Enviar datos al servidor
    const response = await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    })

    const data = await response.json()

    if (response.ok && data.success) {
      alert(`Tipo de terapia ${esEdicion.value ? 'actualizado' : 'registrado'} correctamente`)
      
      // Limpiar formulario después de registro exitoso (solo si no es edición)
      if (!esEdicion.value) {
        // Limpiar formulario
        Object.keys(terapia).forEach(key => {
          if (key === 'duracionUnidad') {
            terapia[key] = 'sesiones'
          } else {
            terapia[key] = ''
          }
        })
        
        // Limpiar arrays
        requisitosAgregados.value = []
        indicacionesAgregadas.value = []
        contraindicacionesAgregadas.value = []
        riesgosAgregados.value = []
        recomendacionesAgregadas.value = []
        especiesSeleccionadas.value = []
        
        // Limpiar inputs temporales
        inputRequisitos.value = ''
        inputIndicaciones.value = ''
        inputContraindicaciones.value = ''
        inputRiesgos.value = ''
        inputRecomendaciones.value = ''
      }
      
      router.push('/veterinarios/tipos/terapias')
    } else {
      if (data.errors) {
        const errores = Object.values(data.errors).flat().join('\n')
        alert(`Error al ${esEdicion.value ? 'actualizar' : 'registrar'}: ${errores}`)
      } else {
        alert(`Error al ${esEdicion.value ? 'actualizar' : 'registrar'}: ${data.message || 'Error desconocido'}`)
      }
    }
  } catch (error) {
    console.error('Error:', error)
    alert('Error al conectar con el servidor')
  } finally {
    cargando.value = false
  }
}
</script>
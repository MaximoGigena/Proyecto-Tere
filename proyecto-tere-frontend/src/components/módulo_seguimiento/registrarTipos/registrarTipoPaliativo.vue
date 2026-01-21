<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar' : 'Registrar' }} Procedimiento Paliativo</h1>

    <form @submit.prevent="esEdicion ? actualizarProcedimiento() : registrarProcedimiento()" class="space-y-4">
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
            <label class="block font-medium">Nombre del procedimiento paliativo</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="procedimiento.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Control del dolor crónico"
                  :disabled="loading"
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
              v-model="procedimiento.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción detallada del procedimiento"
              :disabled="loading"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium mb-2">Especie objetivo</label>
            <CarruselEspecieVeterinario v-model="especiesSeleccionadas" :disabled="loading" />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Objetivo terapéutico principal</label>
            <select v-model="procedimiento.objetivo" required class="w-full border rounded p-2" :disabled="loading">
              <option value="">Seleccione una opción</option>
              <option value="alivio_dolor">Alivio del dolor</option>
              <option value="mejora_movilidad">Mejora de movilidad</option>
              <option value="soporte_respiratorio">Soporte respiratorio</option>
              <option value="soporte_nutricional">Soporte nutricional</option>
              <option value="acompañamiento">Acompañamiento final</option>
              <option value="otro">Otro</option>
            </select>
            <input 
              v-if="procedimiento.objetivo === 'otro'"
              v-model="procedimiento.objetivoOtro"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique el objetivo"
              :disabled="loading"
              required
            />
          </div>

          <div>
            <label class="block font-medium">Frecuencia o duración sugerida</label>
            <div class="flex">
              <input v-model="procedimiento.frecuenciaValor" type="number" min="1" required class="w-1/2 border rounded-l p-2" placeholder="Cantidad" :disabled="loading" />
              <select v-model="procedimiento.frecuenciaUnidad" required class="w-1/2 border rounded-r p-2" :disabled="loading">
                <option value="horas">Horas</option>
                <option value="dias">Días</option>
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
                <option value="sesiones">Sesiones</option>
              </select>
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
                placeholder="Condiciones o síntomas que indican este procedimiento (una por línea)"
                :disabled="loading"
              ></textarea>
              <button 
                type="button"
                @click="agregarItem('indicaciones')"
                class="bg-blue-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-blue-600 transition-colors disabled:bg-blue-300"
                :disabled="loading || !inputIndicaciones.trim()"
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
                    :disabled="loading"
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
              placeholder="Situaciones donde no aplicar este procedimiento (una por línea)"
              :disabled="loading"
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('contraindicaciones')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors disabled:bg-green-300"
              :disabled="loading || !inputContraindicaciones.trim()"
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
                  :disabled="loading"
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
              placeholder="Posibles efectos adversos (una por línea)"
              :disabled="loading"
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('riesgos')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors disabled:bg-green-300"
              :disabled="loading || !inputRiesgos.trim()"
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
                  :disabled="loading"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Recursos necesarios</label>
          <div class="flex gap-2 items-center mb-1">
            <input 
              v-model="recursoTemporal" 
              type="text" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Medicamento X, Equipo de oxígeno, etc."
              @keyup.enter="agregarRecurso"
              :disabled="loading"
            />
            <button 
              type="button"
              class="bg-green-500 text-white px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap disabled:bg-green-300"
              @click="agregarRecurso"
              :disabled="loading || !recursoTemporal.trim()"
            >
              + Agregar
            </button>
          </div>
          <div class="flex flex-wrap gap-2 mt-2">
            <div 
              v-for="(recurso, index) in procedimiento.recursos" 
              :key="index"
              class="bg-gray-100 px-3 py-1 rounded-full flex items-center gap-2"
            >
              {{ recurso }}
              <button 
                type="button"
                @click="eliminarRecurso(index)"
                class="text-red-500 hover:text-red-700"
                :disabled="loading"
              >
                <font-awesome-icon :icon="['fas', 'times']" />
              </button>
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
              placeholder="Recomendaciones para el equipo médico (una por línea)"
              :disabled="loading"
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('recomendaciones')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors disabled:bg-green-300"
              :disabled="loading || !inputRecomendaciones.trim()"
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
                  :disabled="loading"
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
            v-model="procedimiento.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante"
            :disabled="loading"
          ></textarea>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button 
          type="button" 
          @click="cancelar" 
          class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors disabled:bg-gray-300"
          :disabled="loading"
        >
          Cancelar
        </button>
        <button 
          type="submit" 
          class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
          :disabled="loading || !formularioValido"
        >
          {{ esEdicion ? 'Actualizar' : '+' }} Tipo
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import CarruselEspecieVeterinario from '@/components/ElementosGraficos/CarruselEspecieVeterinario.vue'

const especiesSeleccionadas = ref([])

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const esEdicion = ref(false)
const procedimientoId = ref(null)
const loading = ref(false)

// Inputs temporales para agregar elementos
const inputIndicaciones = ref('')
const inputContraindicaciones = ref('')
const inputRiesgos = ref('')
const inputRecomendaciones = ref('')
const recursoTemporal = ref('')

// Arrays para almacenar elementos agregados
const indicacionesAgregadas = ref([])
const contraindicacionesAgregadas = ref([])
const riesgosAgregados = ref([])
const recomendacionesAgregadas = ref([])

const procedimiento = reactive({
  nombre: '',
  descripcion: '',
  especies: [],
  objetivo: '',
  objetivoOtro: '',
  indicaciones: '',
  frecuenciaValor: '',
  frecuenciaUnidad: 'dias',
  contraindicaciones: '',
  riesgos: '',
  recursos: [],
  recomendaciones: '',
  observaciones: ''
})

// Computed para validar el formulario
const formularioValido = computed(() => {
  return procedimiento.nombre.trim() !== '' &&
         procedimiento.descripcion.trim() !== '' &&
         especiesSeleccionadas.value.length > 0 &&
         procedimiento.objetivo !== '' &&
         procedimiento.frecuenciaValor !== '' &&
         indicacionesAgregadas.value.length > 0 && // Validar que haya al menos una indicación
         (!(procedimiento.objetivo === 'otro') || procedimiento.objetivoOtro.trim() !== '') // Validar objetivo "otro"
})

// Función para agregar elementos
const agregarItem = (tipo) => {
  let inputValue, arrayDestino
  
  switch(tipo) {
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
watch([indicacionesAgregadas, contraindicacionesAgregadas, riesgosAgregados, recomendacionesAgregadas], () => {
  // Convertir arrays a strings separados por comas para el backend
  procedimiento.indicaciones = indicacionesAgregadas.value.length > 0 
    ? indicacionesAgregadas.value.join(', ') 
    : ''
  
  procedimiento.contraindicaciones = contraindicacionesAgregadas.value.length > 0 
    ? contraindicacionesAgregadas.value.join(', ') 
    : ''
  
  procedimiento.riesgos = riesgosAgregados.value.length > 0 
    ? riesgosAgregados.value.join(', ') 
    : ''
  
  procedimiento.recomendaciones = recomendacionesAgregadas.value.length > 0 
    ? recomendacionesAgregadas.value.join(', ') 
    : ''
}, { deep: true })

// Watch para sincronizar especiesSeleccionadas con procedimiento.especies
watch(especiesSeleccionadas, (newEspecies) => {
  procedimiento.especies = [...newEspecies]
})

// Función para recursos (mantenemos el formato actual)
const agregarRecurso = () => {
  if (recursoTemporal.value.trim() !== '') {
    procedimiento.recursos.push(recursoTemporal.value.trim())
    recursoTemporal.value = ''
  }
}

const eliminarRecurso = (index) => {
  procedimiento.recursos.splice(index, 1)
}

// Verificar autenticación y cargar datos si es edición
onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      setTimeout(() => {
        router.push('/')
      }, 2000)
      return
    }
  }
  
  // Verificar si hay un ID en la ruta (modo edición)
  if (route.params.id) {
    esEdicion.value = true
    procedimientoId.value = route.params.id
    await cargarPaliativo()
  }
})

// Función para cargar los datos del procedimiento
const cargarPaliativo = async () => {
  try {
    loading.value = true
    const response = await fetch(`/api/tipos-procedimiento-paliativo/${procedimientoId.value}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      }
    })

    if (!response.ok) {
      throw new Error('Error al cargar el procedimiento')
    }

    const result = await response.json()

    if (result.success) {
      const datos = result.data
      
      // Mapear los datos del API al formulario
      procedimiento.nombre = datos.nombre || ''
      procedimiento.descripcion = datos.descripcion || ''
      procedimiento.especies = datos.especies || []
      procedimiento.objetivo = datos.objetivo_terapeutico || ''
      procedimiento.objetivoOtro = datos.objetivo_otro || ''
      procedimiento.indicaciones = datos.indicaciones_clinicas || ''
      procedimiento.frecuenciaValor = datos.frecuencia_valor || ''
      procedimiento.frecuenciaUnidad = datos.frecuencia_unidad || 'dias'
      procedimiento.contraindicaciones = datos.contraindicaciones || ''
      procedimiento.riesgos = datos.riesgos_efectos_secundarios || ''
      procedimiento.recursos = datos.recursos_necesarios || []
      procedimiento.recomendaciones = datos.recomendaciones_clinicas || ''
      procedimiento.observaciones = datos.observaciones || ''

      // Sincronizar especies seleccionadas después de cargar los datos
      if (datos.especies && Array.isArray(datos.especies)) {
        especiesSeleccionadas.value = [...datos.especies]
      }
      
      // Cargar arrays desde strings del backend
      indicacionesAgregadas.value = procedimiento.indicaciones 
        ? procedimiento.indicaciones.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      contraindicacionesAgregadas.value = procedimiento.contraindicaciones 
        ? procedimiento.contraindicaciones.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      riesgosAgregados.value = procedimiento.riesgos 
        ? procedimiento.riesgos.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      recomendacionesAgregadas.value = procedimiento.recomendaciones 
        ? procedimiento.recomendaciones.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      console.log('Arrays cargados:', {
        indicaciones: indicacionesAgregadas.value,
        contraindicaciones: contraindicacionesAgregadas.value,
        riesgos: riesgosAgregados.value,
        recomendaciones: recomendacionesAgregadas.value
      })
      
    } else {
      throw new Error(result.message || 'Error al cargar los datos')
    }

  } catch (error) {
    console.error('Error al cargar el procedimiento:', error)
    alert('Error al cargar el procedimiento: ' + error.message)
  } finally {
    loading.value = false
  }
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const registrarProcedimiento = async () => {
  try {
    // Validar formulario
    if (!formularioValido.value) {
      alert('Por favor complete todos los campos obligatorios')
      return
    }

    loading.value = true

    // Preparar datos según el modelo
    const datosEnvio = {
      nombre: procedimiento.nombre.trim(),
      descripcion: procedimiento.descripcion.trim(),
      especies: procedimiento.especies,
      objetivo_terapeutico: procedimiento.objetivo,
      objetivo_otro: procedimiento.objetivo === 'otro' ? procedimiento.objetivoOtro.trim() : null,
      frecuencia_valor: parseInt(procedimiento.frecuenciaValor),
      frecuencia_unidad: procedimiento.frecuenciaUnidad,
      indicaciones_clinicas: procedimiento.indicaciones,
      contraindicaciones: procedimiento.contraindicaciones || null,
      riesgos_efectos_secundarios: procedimiento.riesgos || null,
      recursos_necesarios: procedimiento.recursos,
      recomendaciones_clinicas: procedimiento.recomendaciones || null,
      observaciones: procedimiento.observaciones || null,
    };

    console.log('Datos a enviar:', datosEnvio)

    // Enviar al backend
    const response = await fetch('/api/tipos-procedimiento-paliativo', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.message || 'Error al registrar el procedimiento');
    }

    if (result.success) {
      alert('Procedimiento paliativo registrado correctamente');
      
      // Limpiar formulario
      Object.keys(procedimiento).forEach(key => {
        if (key === 'recursos') {
          procedimiento[key] = []
        } else if (key === 'frecuenciaUnidad') {
          procedimiento[key] = 'dias'
        } else {
          procedimiento[key] = ''
        }
      })
      
      // Limpiar arrays
      indicacionesAgregadas.value = []
      contraindicacionesAgregadas.value = []
      riesgosAgregados.value = []
      recomendacionesAgregadas.value = []
      especiesSeleccionadas.value = []
      
      // Limpiar inputs temporales
      inputIndicaciones.value = ''
      inputContraindicaciones.value = ''
      inputRiesgos.value = ''
      inputRecomendaciones.value = ''
      recursoTemporal.value = ''
      
      router.push('/veterinarios/tipos/paliativos');
    } else {
      throw new Error(result.message);
    }

  } catch (error) {
    console.error('Error:', error);
    alert('Error al registrar el procedimiento: ' + error.message);
  } finally {
    loading.value = false
  }
};

const actualizarProcedimiento = async () => {
  try {
    // Validar formulario
    if (!formularioValido.value) {
      alert('Por favor complete todos los campos obligatorios')
      return
    }

    loading.value = true

    // Preparar datos según el modelo
    const datosEnvio = {
      nombre: procedimiento.nombre.trim(),
      descripcion: procedimiento.descripcion.trim(),
      especies: procedimiento.especies,
      objetivo_terapeutico: procedimiento.objetivo,
      objetivo_otro: procedimiento.objetivo === 'otro' ? procedimiento.objetivoOtro.trim() : null,
      frecuencia_valor: parseInt(procedimiento.frecuenciaValor),
      frecuencia_unidad: procedimiento.frecuenciaUnidad,
      indicaciones_clinicas: procedimiento.indicaciones,
      contraindicaciones: procedimiento.contraindicaciones || null,
      riesgos_efectos_secundarios: procedimiento.riesgos || null,
      recursos_necesarios: procedimiento.recursos,
      recomendaciones_clinicas: procedimiento.recomendaciones || null,
      observaciones: procedimiento.observaciones || null,
    };

    console.log('Datos a enviar:', datosEnvio)

    // Enviar al backend con PUT
    const response = await fetch(`/api/tipos-procedimiento-paliativo/${procedimientoId.value}`, {
      method: 'PUT',
      headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.message || 'Error al actualizar el procedimiento');
    }

    if (result.success) {
      alert('Procedimiento actualizado correctamente');
      router.push('/veterinarios/tipos/paliativos');
    } else {
      throw new Error(result.message);
    }

  } catch (error) {
    console.error('Error:', error);
    alert('Error al actualizar el procedimiento: ' + error.message);
  } finally {
    loading.value = false
  }
};
</script>
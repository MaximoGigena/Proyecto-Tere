<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar' : 'Registrar' }} Tipo de Diagnóstico</h1>

    <form @submit.prevent="confirmarAccion" class="space-y-4">
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
            <label class="block font-medium">Nombre del diagnóstico</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="diagnostico.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Moquillo canino, Insuficiencia renal crónica"
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
              v-model="diagnostico.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción detallada del diagnóstico"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium mb-2">Especie objetivo</label>
            <CarruselEspecieVeterinario v-model="especiesSeleccionadas" />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">

          <div>
            <label class="block font-medium">Clasificación</label>
            <select v-model="diagnostico.clasificacion" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="infeccioso">Infeccioso</option>
              <option value="genetico">Genético</option>
              <option value="nutricional">Nutricional</option>
              <option value="ambiental">Ambiental</option>
              <option value="traumatico">Traumático</option>
              <option value="degenerativo">Degenerativo</option>
              <option value="neoplasico">Neoplásico</option>
              <option value="otro">Otro</option>
            </select>
            <input 
              v-if="diagnostico.clasificacion === 'otro'"
              v-model="diagnostico.clasificacionOtro"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique la clasificación"
            />
          </div>

          <div>
            <label class="block font-medium">Evolución típica</label>
            <select v-model="diagnostico.evolucion" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="aguda">Aguda</option>
              <option value="cronica">Crónica</option>
              <option value="recurrente">Recurrente</option>
              <option value="autolimitada">Autolimitada</option>
              <option value="progresiva">Progresiva</option>
            </select>
          </div>
          
          <!-- CRITERIOS DIAGNÓSTICOS PRINCIPALES - SECCIÓN MODIFICADA -->
          <div class="space-y-4">
            <label class="block font-medium">Criterios diagnósticos principales</label>
            
            <!-- Síntomas característicos -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Síntomas característicos (Signos clínicos)
              </label>
              <div class="flex gap-2">
                <textarea 
                  v-model="inputSintomas" 
                  rows="2" 
                  class="w-full border rounded p-2 text-sm" 
                  placeholder="fiebre, diarrea, jadeo excesivo, cojera, etc."
                ></textarea>
                <button 
                  type="button"
                  @click="agregarItem('sintomas')"
                  class="bg-blue-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-blue-600 transition-colors"
                >
                  <font-awesome-icon :icon="['fas', 'plus']" />
                </button>
              </div>
              <!-- Lista de síntomas agregados -->
               <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
                <div v-if="sintomasAgregados.length > 0" class="mt-2 space-y-1">
                  <div v-for="(sintoma, index) in sintomasAgregados" :key="index" 
                      class="flex items-center justify-between bg-blue-50 p-2 rounded text-sm">
                    <span>{{ sintoma }}</span>
                    <button 
                      type="button"
                      @click="eliminarItem('sintomas', index)"
                      class="text-red-500 hover:text-red-700"
                    >
                      <font-awesome-icon :icon="['fas', 'times']" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Exámenes requeridos -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Exámenes requeridos / Estudios complementarios
              </label>
              <div class="flex gap-2">
                <textarea 
                  v-model="inputExamenes" 
                  rows="2" 
                  class="w-full border rounded p-2 text-sm" 
                  placeholder="Hemograma, Radiografía, Ecografía, etc."
                ></textarea>
                <button 
                  type="button"
                  @click="agregarItem('examenes')"
                  class="bg-blue-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-blue-600 transition-colors"
                >
                  <font-awesome-icon :icon="['fas', 'plus']" />
                </button>
              </div>
              <!-- Lista de exámenes agregados -->
              <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
                <div v-if="examenesAgregados.length > 0" class="mt-2 space-y-1">
                  <div v-for="(examen, index) in examenesAgregados" :key="index" 
                      class="flex items-center justify-between bg-blue-50 p-2 rounded text-sm">
                    <span>{{ examen }}</span>
                    <button 
                      type="button"
                      @click="eliminarItem('examenes', index)"
                      class="text-red-500 hover:text-red-700"
                    >
                      <font-awesome-icon :icon="['fas', 'times']" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Señales clínicas mayores -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Señales clínicas mayores (Major criteria)
              </label>
              <div class="flex gap-2">
                <textarea 
                  v-model="inputSeñalesMayores" 
                  rows="2"  
                  class="w-full border rounded p-2 text-sm" 
                  placeholder="Lesión visible, Pérdida de conciencia, etc."
                ></textarea>
                <button 
                  type="button"
                  @click="agregarItem('señalesMayores')"
                  class="bg-blue-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-blue-600 transition-colors"
                >
                  <font-awesome-icon :icon="['fas', 'plus']" />
                </button>
              </div>
              <!-- Lista de señales mayores agregadas -->
              <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
                <div v-if="señalesMayoresAgregadas.length > 0" class="mt-2 space-y-1">
                  <div v-for="(senal, index) in señalesMayoresAgregadas" :key="index" 
                      class="flex items-center justify-between bg-blue-50 p-2 rounded text-sm">
                    <span>{{ senal }}</span>
                    <button 
                      type="button"
                      @click="eliminarItem('señalesMayores', index)"
                      class="text-red-500 hover:text-red-700"
                    >
                      <font-awesome-icon :icon="['fas', 'times']" />
                    </button>
                  </div>
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

      <!-- NUEVAS SECCIONES AGREGADAS -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
        <!-- Señales clínicas menores -->
        <div>
          <label class="block font-medium">Señales clínicas menores (Minor criteria)</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputSeñalesMenores" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Letargo, Disminución del apetito, Cambios en comportamiento"
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('señalesMenores')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors"
            >
              <font-awesome-icon :icon="['fas', 'plus']" />
            </button>
          </div>
          <!-- Lista de señales menores agregadas -->
           <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
            <div v-if="señalesMenoresAgregadas.length > 0" class="mt-2 space-y-1">
              <div v-for="(senal, index) in señalesMenoresAgregadas" :key="index" 
                  class="flex items-center justify-between bg-green-50 p-2 rounded text-sm">
                <span>{{ senal }}</span>
                <button 
                  type="button"
                  @click="eliminarItem('señalesMenores', index)"
                  class="text-red-500 hover:text-red-700"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>  
        </div>

        <!-- Criterios de exclusión -->
        <div>
          <label class="block font-medium">Criterios de exclusión</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputCriteriosExclusion" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Condiciones que, si están presentes, descartan este diagnóstico."
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('criteriosExclusion')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors"
            >
              <font-awesome-icon :icon="['fas', 'plus']" />
            </button>
          </div>
          <!-- Lista de criterios de exclusión agregados -->
          <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
            <div v-if="criteriosExclusionAgregados.length > 0" class="mt-2 space-y-1">
              <div v-for="(criterio, index) in criteriosExclusionAgregados" :key="index" 
                  class="flex items-center justify-between bg-green-50 p-2 rounded text-sm">
                <span>{{ criterio }}</span>
                <button 
                  type="button"
                  @click="eliminarItem('criteriosExclusion', index)"
                  class="text-red-500 hover:text-red-700"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tratamiento sugerido estándar -->
        <div>
          <label class="block font-medium">Tratamiento sugerido estándar</label>
          <textarea 
            v-model="diagnostico.tratamiento_sugerido" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Protocolo de tratamiento recomendado"
          ></textarea>
        </div>

        <!-- Riesgos o complicaciones asociadas -->
        <div>
          <label class="block font-medium">Riesgos o complicaciones asociadas</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputRiesgos" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Complicaciones comunes de este diagnóstico"
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('riesgos')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors"
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
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Recomendaciones clínicas -->
        <div class="col-span-full">
          <label class="block font-medium">Recomendaciones clínicas</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputRecomendaciones" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Recomendaciones para el manejo clínico"
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('recomendaciones')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors"
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
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Observaciones adicionales -->
        <div class="col-span-full">
          <label class="block font-medium">Observaciones adicionales</label>
          <textarea 
            v-model="diagnostico.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante sobre este diagnóstico"
          ></textarea>
        </div>       
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="button" @click="cancelar" class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors">Cancelar</button>
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors">
          {{ esEdicion ? 'Guardar' : '+ Tipo' }}
        </button>
      </div>
    </form>

    <!-- Modal de Confirmación -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">
          {{ esEdicion ? 'Confirmar Edición' : 'Confirmar Registro' }}
        </h3>
        <p class="mb-6">
          {{ esEdicion 
            ? '¿Está seguro que desea guardar los cambios realizados en este diagnóstico?' 
            : '¿Está seguro que desea registrar este nuevo tipo de diagnóstico?' 
          }}
        </p>
        <div class="flex justify-end gap-4">
          <button 
            @click="showModal = false" 
            class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors"
          >
            Cancelar
          </button>
          <button 
            @click="ejecutarAccion" 
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors"
          >
            {{ esEdicion ? 'Guardar' : 'Registrar' }}
          </button>
        </div>
      </div>
    </div>
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

const showModal = ref(false)
const esEdicion = computed(() => {
  return route.params.id !== undefined || route.query.modo === 'edicion'
})

// Inputs temporales para agregar elementos
const inputSintomas = ref('')
const inputExamenes = ref('')
const inputSeñalesMayores = ref('')
const inputSeñalesMenores = ref('')
const inputCriteriosExclusion = ref('')
const inputRiesgos = ref('')
const inputRecomendaciones = ref('')

// Arrays para almacenar elementos agregados
const sintomasAgregados = ref([])
const examenesAgregados = ref([])
const señalesMayoresAgregadas = ref([])
const señalesMenoresAgregadas = ref([])
const criteriosExclusionAgregados = ref([])
const riesgosAgregados = ref([])
const recomendacionesAgregadas = ref([])

const diagnostico = reactive({
  nombre: '',
  descripcion: '',
  especies: [],
  clasificacion: '',
  clasificacion_otro: '',
  // Nuevos campos para criterios diagnósticos (obligatorios)
  sintomas_caracteristicos: '',
  examenes_requeridos: '',
  señales_clinicas_mayores: '',
  // Nuevos campos opcionales
  señales_clinicas_menores: '',
  criterios_exclusion: '',
  evolucion: '',
  tratamiento_sugerido: '', 
  recomendaciones_clinicas: '', 
  riesgos_complicaciones: '', 
  observaciones: ''
})

// Función para agregar elementos
const agregarItem = (tipo) => {
  let inputValue, arrayDestino
  
  switch(tipo) {
    case 'sintomas':
      inputValue = inputSintomas.value.trim()
      arrayDestino = sintomasAgregados
      break
    case 'examenes':
      inputValue = inputExamenes.value.trim()
      arrayDestino = examenesAgregados
      break
    case 'señalesMayores':
      inputValue = inputSeñalesMayores.value.trim()
      arrayDestino = señalesMayoresAgregadas
      break
    case 'señalesMenores':
      inputValue = inputSeñalesMenores.value.trim()
      arrayDestino = señalesMenoresAgregadas
      break
    case 'criteriosExclusion':
      inputValue = inputCriteriosExclusion.value.trim()
      arrayDestino = criteriosExclusionAgregados
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
        case 'sintomas': inputSintomas.value = ''; break
        case 'examenes': inputExamenes.value = ''; break
        case 'señalesMayores': inputSeñalesMayores.value = ''; break
        case 'señalesMenores': inputSeñalesMenores.value = ''; break
        case 'criteriosExclusion': inputCriteriosExclusion.value = ''; break
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
    case 'sintomas':
      sintomasAgregados.value.splice(index, 1)
      break
    case 'examenes':
      examenesAgregados.value.splice(index, 1)
      break
    case 'señalesMayores':
      señalesMayoresAgregadas.value.splice(index, 1)
      break
    case 'señalesMenores':
      señalesMenoresAgregadas.value.splice(index, 1)
      break
    case 'criteriosExclusion':
      criteriosExclusionAgregados.value.splice(index, 1)
      break
    case 'riesgos':
      riesgosAgregados.value.splice(index, 1)
      break
    case 'recomendaciones':
      recomendacionesAgregadas.value.splice(index, 1)
      break
  }
}

// Watch para sincronizar especiesSeleccionadas con diagnostico.especies
watch(especiesSeleccionadas, (newEspecies) => {
  diagnostico.especies = [...newEspecies]
})

// Watch para sincronizar arrays con los campos del diagnóstico
watch([sintomasAgregados, examenesAgregados, señalesMayoresAgregadas, 
       señalesMenoresAgregadas, criteriosExclusionAgregados,
       riesgosAgregados, recomendacionesAgregadas], () => {
  // Convertir arrays a strings separados por comas para el backend
  // Asegurarse de que solo se asigne si hay elementos
  diagnostico.sintomas_caracteristicos = sintomasAgregados.value.length > 0 
    ? sintomasAgregados.value.join(', ') 
    : ''
  
  diagnostico.examenes_requeridos = examenesAgregados.value.length > 0 
    ? examenesAgregados.value.join(', ') 
    : ''
  
  diagnostico.señales_clinicas_mayores = señalesMayoresAgregadas.value.length > 0 
    ? señalesMayoresAgregadas.value.join(', ') 
    : ''
  
  diagnostico.señales_clinicas_menores = señalesMenoresAgregadas.value.length > 0 
    ? señalesMenoresAgregadas.value.join(', ') 
    : ''
  
  diagnostico.criterios_exclusion = criteriosExclusionAgregados.value.length > 0 
    ? criteriosExclusionAgregados.value.join(', ') 
    : ''
  
  diagnostico.riesgos_complicaciones = riesgosAgregados.value.length > 0 
    ? riesgosAgregados.value.join(', ') 
    : ''
  
  diagnostico.recomendaciones_clinicas = recomendacionesAgregadas.value.length > 0 
    ? recomendacionesAgregadas.value.join(', ') 
    : ''
  
  // Debug: mostrar los valores convertidos
  console.log('Valores convertidos para backend:')
  console.log('sintomas_caracteristicos:', diagnostico.sintomas_caracteristicos)
  console.log('examenes_requeridos:', diagnostico.examenes_requeridos)
  console.log('señales_clinicas_mayores:', diagnostico.señales_clinicas_mayores)
}, { deep: true }) // Añadido deep: true para detectar cambios en arrays

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

  // Si está en modo edición, cargar los datos existentes
  if (esEdicion.value) {
    await cargarDiagnostico()
  }
})

const cargarDiagnostico = async () => {
  try {
    const diagnosticoId = route.params.id
    if (diagnosticoId) {
      console.log('Cargando diagnóstico con ID:', diagnosticoId)
      
      const response = await fetch(`/api/tipos-diagnostico/${diagnosticoId}`, {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Accept': 'application/json'
        }
      })
      
      if (response.ok) {
        const result = await response.json()
        console.log('Datos recibidos del backend:', result)
        
        if (result.success && result.data) {
          const datos = result.data
          
          // Asignar los datos al reactive object
          Object.assign(diagnostico, {
            nombre: datos.nombre || '',
            descripcion: datos.descripcion || '',
            especies: datos.especies || [], 
            clasificacion: datos.clasificacion || '',
            clasificacion_otro: datos.clasificacion_otro || '',
            // Campos para criterios diagnósticos
            sintomas_caracteristicos: datos.sintomas_caracteristicos || '',
            examenes_requeridos: datos.examenes_requeridos || '',
            señales_clinicas_mayores: datos.señales_clinicas_mayores || '',
            // Nuevos campos opcionales
            señales_clinicas_menores: datos.señales_clinicas_menores || '',
            criterios_exclusion: datos.criterios_exclusion || '',
            evolucion: datos.evolucion || '',
            tratamiento_sugerido: datos.tratamiento_sugerido || '',
            recomendaciones_clinicas: datos.recomendaciones_clinicas || '',
            riesgos_complicaciones: datos.riesgos_complicaciones || '',
            observaciones: datos.observaciones || ''
          })

          // Convertir strings del backend a arrays - CORREGIDO
          // Usar split(',') y luego filtrar elementos no vacíos
          sintomasAgregados.value = diagnostico.sintomas_caracteristicos 
            ? diagnostico.sintomas_caracteristicos.split(',').map(s => s.trim()).filter(s => s !== '')
            : []
          
          examenesAgregados.value = diagnostico.examenes_requeridos 
            ? diagnostico.examenes_requeridos.split(',').map(s => s.trim()).filter(s => s !== '')
            : []
          
          señalesMayoresAgregadas.value = diagnostico.señales_clinicas_mayores 
            ? diagnostico.señales_clinicas_mayores.split(',').map(s => s.trim()).filter(s => s !== '')
            : []
          
          señalesMenoresAgregadas.value = diagnostico.señales_clinicas_menores 
            ? diagnostico.señales_clinicas_menores.split(',').map(s => s.trim()).filter(s => s !== '')
            : []
          
          criteriosExclusionAgregados.value = diagnostico.criterios_exclusion 
            ? diagnostico.criterios_exclusion.split(',').map(s => s.trim()).filter(s => s !== '')
            : []
          
          riesgosAgregados.value = diagnostico.riesgos_complicaciones 
            ? diagnostico.riesgos_complicaciones.split(',').map(s => s.trim()).filter(s => s !== '')
            : []
          
          recomendacionesAgregadas.value = diagnostico.recomendaciones_clinicas 
            ? diagnostico.recomendaciones_clinicas.split(',').map(s => s.trim()).filter(s => s !== '')
            : []

          // Sincronizar especies seleccionadas
          if (datos.especies && Array.isArray(datos.especies)) {
            especiesSeleccionadas.value = [...datos.especies]
          }
          
          console.log('Arrays cargados desde backend:')
          console.log('sintomasAgregados:', sintomasAgregados.value)
          console.log('examenesAgregados:', examenesAgregados.value)
          console.log('señalesMayoresAgregadas:', señalesMayoresAgregadas.value)
        }
      }
    }
  } catch (error) {
    console.error('Error al cargar diagnóstico:', error)
  }
}

const confirmarAccion = () => {
  // Validar campos obligatorios de criterios diagnósticos
  let errores = []
  
  if (sintomasAgregados.value.length === 0) {
    errores.push('Debe especificar al menos un síntoma característico')
  }
  
  if (examenesAgregados.value.length === 0) {
    errores.push('Debe especificar al menos un examen requerido')
  }
  
  if (señalesMayoresAgregadas.value.length === 0) {
    errores.push('Debe especificar al menos una señal clínica mayor')
  }
  
  if (!diagnostico.especies || diagnostico.especies.length === 0) {
    errores.push('Debe seleccionar al menos una especie objetivo')
  }

  // Validar otros campos obligatorios
  if (!diagnostico.nombre.trim()) {
    errores.push('El nombre del diagnóstico es obligatorio')
  }
  
  if (!diagnostico.descripcion.trim()) {
    errores.push('La descripción general es obligatoria')
  }
  
  if (!diagnostico.clasificacion) {
    errores.push('La clasificación es obligatoria')
  }
  
  if (diagnostico.clasificacion === 'otro' && !diagnostico.clasificacion_otro?.trim()) {
    errores.push('Debe especificar la clasificación cuando selecciona "Otro"')
  }
  
  if (!diagnostico.evolucion) {
    errores.push('La evolución típica es obligatoria')
  }

  if (errores.length > 0) {
    alert('Por favor complete los siguientes campos obligatorios:\n\n• ' + errores.join('\n• '))
    return
  }
  
  // Mostrar resumen de lo que se va a enviar
  console.log('Datos preparados para enviar:', diagnostico)
  console.log('Síntomas agregados:', sintomasAgregados.value)
  console.log('Exámenes agregados:', examenesAgregados.value)
  console.log('Señales mayores agregadas:', señalesMayoresAgregadas.value)
  
  showModal.value = true
}

const ejecutarAccion = async () => {
  showModal.value = false
  
  if (esEdicion.value) {
    await editarDiagnostico()
  } else {
    await registrarDiagnostico()
  }
}

const cancelar = () => {
  const mensaje = esEdicion.value 
    ? '¿Está seguro que desea cancelar? Los cambios no guardados se perderán.' 
    : '¿Está seguro que desea cancelar? Los datos no guardados se perderán.'
  
  if (confirm(mensaje)) {
    router.back()
  }
}

const registrarDiagnostico = async () => {
  try {

    // DEBUG: Verificar que los datos están correctos antes de enviar
    console.log('DEBUG - Arrays antes de enviar:')
    console.log('sintomasAgregados:', sintomasAgregados.value)
    console.log('examenesAgregados:', examenesAgregados.value)
    console.log('señalesMayoresAgregadas:', señalesMayoresAgregadas.value)
    console.log('Valores en diagnostico objeto:')
    console.log('sintomas_caracteristicos:', diagnostico.sintomas_caracteristicos)
    console.log('examenes_requeridos:', diagnostico.examenes_requeridos)
    console.log('señales_clinicas_mayores:', diagnostico.señales_clinicas_mayores)
    
    const datosEnvio = {
      ...diagnostico,
      clasificacion_otro: diagnostico.clasificacion === 'otro' ? diagnostico.clasificacion_otro : null,
      // Campos opcionales
      señales_clinicas_menores: diagnostico.señales_clinicas_menores || null,
      criterios_exclusion: diagnostico.criterios_exclusion || null,
      tratamiento_sugerido: diagnostico.tratamiento_sugerido || null,
      riesgos_complicaciones: diagnostico.riesgos_complicaciones || null,
      recomendaciones_clinicas: diagnostico.recomendaciones_clinicas || null,
      observaciones: diagnostico.observaciones || null
    }

    console.log('Datos a enviar (registro):', datosEnvio);

    const response = await fetch('/api/tipos-diagnostico', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Error response:', errorText);
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    console.log('Respuesta del servidor:', result);

    if (result.success) {
      alert('Tipo de diagnóstico registrado correctamente');
      router.push('/veterinarios/tipos/diagnosticos');
    } else {
      if (result.errors) {
        const errores = Object.values(result.errors).join('\n');
        alert('Errores de validación:\n' + errores);
      } else {
        alert('Error: ' + result.message);
      }
    }

  } catch (error) {
    console.error('Error completo:', error);
    alert('Error al registrar el diagnóstico: ' + error.message);
  }
}

const editarDiagnostico = async () => {
  try {
    const datosEnvio = {
      ...diagnostico,
      clasificacion_otro: diagnostico.clasificacion === 'otro' ? diagnostico.clasificacion_otro : null,
      // Campos opcionales
      señales_clinicas_menores: diagnostico.señales_clinicas_menores || null,
      criterios_exclusion: diagnostico.criterios_exclusion || null,
      tratamiento_sugerido: diagnostico.tratamiento_sugerido || null,
      riesgos_complicaciones: diagnostico.riesgos_complicaciones || null,
      recomendaciones_clinicas: diagnostico.recomendaciones_clinicas || null,
      observaciones: diagnostico.observaciones || null
    }

    console.log('Datos a enviar (edición):', datosEnvio);

    const diagnosticoId = route.params.id
    const response = await fetch(`/api/tipos-diagnostico/${diagnosticoId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Error response:', errorText);
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    console.log('Respuesta del servidor:', result);

    if (result.success) {
      alert('Tipo de diagnóstico actualizado correctamente');
      router.push('/veterinarios/tipos/diagnosticos');
    } else {
      if (result.errors) {
        const errores = Object.values(result.errors).join('\n');
        alert('Errores de validación:\n' + errores);
      } else {
        alert('Error: ' + result.message);
      }
    }

  } catch (error) {
    console.error('Error completo:', error);
    alert('Error al actualizar el diagnóstico: ' + error.message);
  }
}
</script>
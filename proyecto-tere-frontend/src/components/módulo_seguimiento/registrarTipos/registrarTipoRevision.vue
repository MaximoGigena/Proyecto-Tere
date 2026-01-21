<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar' : 'Registrar' }} Tipo de Revisión Médica</h1>

    <form @submit.prevent="esEdicion ? abrirModalConfirmacion() : registrarRevision()" class="space-y-4">
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
            <label class="block font-medium">Nombre del tipo de revisión</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="revision.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Chequeo general, Control postquirúrgico"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Descripción clínica breve</label>
            <textarea 
              v-model="revision.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción del propósito y alcance de la revisión"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Frecuencia recomendada</label>
            <select v-model="revision.frecuencia_recomendada" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="anual">Anual</option>
              <option value="semestral">Semestral</option>
              <option value="trimestral">Trimestral</option>
              <option value="mensual">Mensual</option>
              <option value="post_procedimiento">Post-procedimiento</option>
              <option value="personalizada">Personalizada</option>
            </select>
            <input 
              v-if="revision.frecuencia_recomendada === 'personalizada'"
              v-model="revision.frecuencia_personalizada"
              type="text"
              required
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique la frecuencia"
            />
          </div>
          
          <!-- INDICADORES CLÍNICOS - MODIFICADO -->
          <div>
            <label class="block font-medium">Indicadores clave esperables</label>
            <div class="flex gap-2">
              <textarea 
                v-model="inputIndicadores" 
                rows="3" 
                class="w-full border rounded p-2" 
                placeholder="Ej: Temperatura normal, Frecuencia cardíaca estable, Peso adecuado, etc."
              ></textarea>
              <button 
                type="button"
                @click="agregarItem('indicadores')"
                class="bg-blue-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-blue-600 transition-colors"
              >
                <font-awesome-icon :icon="['fas', 'plus']" />
              </button>
            </div>
            <!-- Lista de indicadores agregados -->
            <div v-if="indicadoresAgregados.length > 0" class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
              <div v-for="(indicador, index) in indicadoresAgregados" :key="index" 
                  class="flex items-center justify-between bg-blue-50 p-2 rounded text-sm">
                <span>{{ indicador }}</span>
                <button 
                  type="button"
                  @click="eliminarItem('indicadores', index)"
                  class="text-red-500 hover:text-red-700"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
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
          <!-- ÁREAS A REVISAR (ÁRBOL ANATÓMICO) -->
          <div class="my-8">
            <div class="flex items-center mb-6">
              <div class="flex-grow border-t border-gray-600"></div>
              <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
                Áreas a Revisar
                <span v-if="areasSeleccionadas.length > 0" class="text-sm text-green-600 ml-2">
                  ({{ areasSeleccionadas.length }} seleccionadas)
                </span>
              </h5>
              <div class="flex-grow border-t border-gray-600"></div>
            </div>
            
            <!-- Componente de árbol anatómico -->
            <ClinicalExaminationTree 
              ref="arbolAnatomicoRef"
              :initial-data="revision.areas_revisar"
              @selection-change="handleArbolSelectionChange"
            />
            
            <!-- Área adicional -->
            <div class="mt-6">
              <label class="block font-medium mb-2">Otra área a revisar (opcional)</label>
              <input 
                v-model="revision.otra_area"
                type="text"
                class="w-full border rounded p-2"
                placeholder="Agregue otras áreas no incluidas en el árbol anatómico"
              />
              <p class="text-sm text-gray-500 mt-1">
                Use este campo para agregar áreas específicas que no estén en la lista anterior
              </p>
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
        <div>
          <label class="block font-medium">Edad sugerida para la revisión</label>
          <div class="flex">
            <input 
              v-model="revision.edad_sugerida" 
              type="number" 
              min="0" 
              step="0.5" 
              class="w-1/2 border rounded-l p-2" 
              placeholder="Edad" 
            />
            <select 
              v-model="revision.edad_unidad" 
              class="w-1/2 border rounded-r p-2"
            >
              <option value="semanas">Semanas</option>
              <option value="meses">Meses</option>
              <option value="años">Años</option>
            </select>
          </div>
        </div>
        
        <!-- RECOMENDACIONES PROFESIONALES - MODIFICADO -->
        <div>
          <label class="block font-medium">Recomendaciones profesionales</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputRecomendaciones" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Realizar en ayuno, Monitorear constantes vitales, etc."
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

        <!-- RIESGOS CLÍNICOS ASOCIADOS - MODIFICADO -->
        <div>
          <label class="block font-medium">Riesgos clínicos asociados</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputRiesgos" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Estrés durante el examen, Reacción alérgica, Complicaciones respiratorias, etc."
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
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="button" @click="cancelar" class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors">Cancelar</button>
        <button type="submit" :disabled="loading" class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300">
          {{ loading ? (esEdicion ? 'Actualizando...' : 'Registrando...') : (esEdicion ? 'Actualizar' : '+ Tipo') }}
        </button>
      </div>
    </form>

    <!-- Modal de confirmación para edición -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="text-center">
          <h3 class="text-xl font-bold text-gray-800 mb-4">Confirmar Actualización</h3>
          <p class="text-gray-600 mb-6">
            ¿Está seguro que desea actualizar este tipo de revisión médica? 
            Los cambios serán permanentes.
          </p>
          <div class="flex justify-center gap-4">
            <button 
              @click="cerrarModal" 
              class="bg-gray-500 text-white font-bold px-6 py-2 rounded-full hover:bg-gray-700 transition-colors"
            >
              Cancelar
            </button>
            <button 
              @click="confirmarActualizacion" 
              :disabled="loading"
              class="bg-blue-500 text-white font-bold px-6 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
            >
              {{ loading ? 'Actualizando...' : 'Confirmar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch} from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import CarruselEspecieVeterinario from '@/components/ElementosGraficos/CarruselEspecieVeterinario.vue'
import ClinicalExaminationTree from '@/components/ElementosGraficos/arbolAnatomico.vue' 

// Inputs temporales para agregar elementos
const inputIndicadores = ref('')
const inputRecomendaciones = ref('')
const inputRiesgos = ref('')

// Arrays para almacenar elementos agregados
const indicadoresAgregados = ref([])
const recomendacionesAgregadas = ref([])
const riesgosAgregados = ref([])

const especiesSeleccionadas = ref([])
const areasSeleccionadas = ref([])
const arbolAnatomicoRef = ref(null)

const router = useRouter()
const route = useRoute()
const { isVeterinario, isAprobado, accessToken, checkAuth, user } = useAuth()
const loading = ref(false)
const mostrarModal = ref(false)

// Determinar si estamos en modo edición
const esEdicion = computed(() => {
  return route.name === 'editarTipoRevision' || route.params.id
})

const revision = reactive({
  nombre: '',
  descripcion: '',
  frecuencia_recomendada: '',
  frecuencia_personalizada: '',
  areas_revisar: [],
  otra_area: '',
  indicadores_clave: '', // Ahora será un string separado por comas
  edad_sugerida: null,
  edad_unidad: 'meses',
  recomendaciones_profesionales: '', // Ahora será un string separado por comas
  riesgos_clinicos: '' // Ahora será un string separado por comas
})

// Función para agregar elementos
const agregarItem = (tipo) => {
  let inputValue, arrayDestino
  
  switch(tipo) {
    case 'indicadores':
      inputValue = inputIndicadores.value.trim()
      arrayDestino = indicadoresAgregados
      break
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
    // Verificar si ya existe (case insensitive)
    const existe = arrayDestino.value.some(item => 
      item.toLowerCase() === inputValue.toLowerCase()
    )
    
    if (!existe) {
      arrayDestino.value.push(inputValue)
      
      // Limpiar el input
      switch(tipo) {
        case 'indicadores': inputIndicadores.value = ''; break
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
    case 'indicadores':
      indicadoresAgregados.value.splice(index, 1)
      break
    case 'recomendaciones':
      recomendacionesAgregadas.value.splice(index, 1)
      break
    case 'riesgos':
      riesgosAgregados.value.splice(index, 1)
      break
  }
}

// Watch para sincronizar arrays con los campos de la revisión
watch([indicadoresAgregados, recomendacionesAgregadas, riesgosAgregados], () => {
  // Convertir arrays a strings separados por comas para el backend
  revision.indicadores_clave = indicadoresAgregados.value.length > 0 
    ? indicadoresAgregados.value.join(', ') 
    : ''
  
  revision.recomendaciones_profesionales = recomendacionesAgregadas.value.length > 0 
    ? recomendacionesAgregadas.value.join(', ') 
    : ''
  
  revision.riesgos_clinicos = riesgosAgregados.value.length > 0 
    ? riesgosAgregados.value.join(', ') 
    : ''
  
  console.log('Valores convertidos para backend:')
  console.log('indicadores_clave:', revision.indicadores_clave)
  console.log('recomendaciones_profesionales:', revision.recomendaciones_profesionales)
  console.log('riesgos_clinicos:', revision.riesgos_clinicos)
}, { deep: true })

// Función para manejar cambios en la selección del árbol
const handleArbolSelectionChange = (data) => {
  areasSeleccionadas.value = data.areas
  revision.areas_revisar = data.areaNames
  console.log('Áreas seleccionadas:', revision.areas_revisar)
}

// Agrega esto después de la definición de la función handleArbolSelectionChange
watch(() => revision.areas_revisar, (newAreas) => {
  console.log('🔄 revision.areas_revisar cambió:', newAreas)
  // Actualizar el conteo de áreas seleccionadas
  areasSeleccionadas.value = newAreas.map(area => ({
    nombre: area,
    id: null,
    sistema: ''
  }))
}, { deep: true })

onMounted(async () => {
  try {
    loading.value = true
    console.log('Iniciando componente, modo edición:', esEdicion.value)
    
    const estaAutenticado = await checkAuth()
    
    if (!estaAutenticado) {
      console.log('Usuario no autenticado, redirigiendo al login')
      router.push('/veterinario/login')
      return
    }

    if (!isVeterinario() || !isAprobado()) {
      console.log('Usuario no autorizado')
      alert('No tienes permisos para acceder a esta funcionalidad')
      router.push('/dashboard')
      return
    }

    // Si estamos en modo edición, cargar los datos existentes
    if (esEdicion.value && route.params.id) {
      console.log('Cargando datos para edición con ID:', route.params.id)
      await cargarDatosEdicion()
    }

  } catch (error) {
    console.error('Error en mounted:', error)
    router.push('/veterinario/login')
  } finally {
    loading.value = false
  }
})

const cargarDatosEdicion = async () => {
  try {
    console.log('Cargando datos para edición, ID:', route.params.id)
    
    const response = await fetch(`/api/tipos-revision/${route.params.id}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const result = await response.json()
      console.log('Datos recibidos del servidor:', result)
      
      if (result.success && result.data) {
        const datos = result.data
        
        // Asignar los datos uno por uno
        revision.nombre = datos.nombre || ''
        revision.descripcion = datos.descripcion || ''
        revision.frecuencia_recomendada = datos.frecuencia_recomendada || ''
        revision.frecuencia_personalizada = datos.frecuencia_personalizada || ''
        revision.otra_area = datos.otra_area || ''
        revision.edad_sugerida = datos.edad_sugerida || null
        revision.edad_unidad = datos.edad_unidad || 'meses'
        
        // Convertir strings del backend a arrays
        revision.indicadores_clave = datos.indicadores_clave || ''
        revision.recomendaciones_profesionales = datos.recomendaciones_profesionales || ''
        revision.riesgos_clinicos = datos.riesgos_clinicos || ''
        
        // Convertir strings a arrays
        indicadoresAgregados.value = revision.indicadores_clave 
          ? revision.indicadores_clave.split(',').map(s => s.trim()).filter(s => s !== '')
          : []
        
        recomendacionesAgregadas.value = revision.recomendaciones_profesionales 
          ? revision.recomendaciones_profesionales.split(',').map(s => s.trim()).filter(s => s !== '')
          : []
        
        riesgosAgregados.value = revision.riesgos_clinicos 
          ? revision.riesgos_clinicos.split(',').map(s => s.trim()).filter(s => s !== '')
          : []
        
        // Cargar las especies objetivo
        if (datos.especies_objetivo && Array.isArray(datos.especies_objetivo)) {
          especiesSeleccionadas.value = datos.especies_objetivo
        } else if (datos.especies && Array.isArray(datos.especies)) {
          especiesSeleccionadas.value = datos.especies
        } else if (datos.especie_objetivo) {
          especiesSeleccionadas.value = [datos.especie_objetivo]
        }
        
        // Cargar las áreas de revisión
        if (datos.areas_revisar && Array.isArray(datos.areas_revisar)) {
          revision.areas_revisar = datos.areas_revisar
          areasSeleccionadas.value = datos.areas_revisar.map(area => ({
            nombre: area,
            id: null,
            sistema: ''
          }))
          
          console.log('📋 Áreas cargadas para el árbol:', datos.areas_revisar)
          console.log('📋 Número de áreas:', datos.areas_revisar.length)
          
          // Forzar una actualización después de cargar los datos
          await new Promise(resolve => setTimeout(resolve, 100))
          
          // Si el árbol está disponible, llamar a loadInitialData directamente
          if (arbolAnatomicoRef.value) {
            console.log('📋 Llamando a loadInitialData directamente')
            arbolAnatomicoRef.value.loadInitialData(datos.areas_revisar)
          }
        } else {
          revision.areas_revisar = []
          areasSeleccionadas.value = []
        }
        
        console.log('✅ Datos asignados al formulario:', revision)
        console.log('✅ Especies cargadas:', especiesSeleccionadas.value)
        console.log('✅ Indicadores cargados:', indicadoresAgregados.value)
        console.log('✅ Recomendaciones cargadas:', recomendacionesAgregadas.value)
        console.log('✅ Riesgos cargados:', riesgosAgregados.value)
      } else {
        throw new Error(result.message || 'Error en la respuesta del servidor')
      }
    } else {
      const errorData = await response.json().catch(() => ({ message: 'Error desconocido' }))
      throw new Error(errorData.message || `Error HTTP: ${response.status}`)
    }
  } catch (error) {
    console.error('Error cargando datos:', error)
    alert(`Error al cargar los datos del tipo de revisión: ${error.message}`)
  }
}

const abrirModalConfirmacion = () => {
  mostrarModal.value = true
}

const cerrarModal = () => {
  mostrarModal.value = false
}

const confirmarActualizacion = async () => {
  await actualizarRevision()
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const registrarRevision = async () => {
  try {
    loading.value = true

    // Validaciones básicas
    if (!revision.nombre.trim()) {
      alert('El nombre es obligatorio')
      return
    }

    if (!revision.descripcion.trim()) {
      alert('La descripción es obligatoria')
      return
    }

    if (!revision.frecuencia_recomendada) {
      alert('La frecuencia recomendada es obligatoria')
      return
    }

    if (revision.frecuencia_recomendada === 'personalizada' && !revision.frecuencia_personalizada.trim()) {
      alert('Debe especificar la frecuencia personalizada')
      return
    }

    // Validar que se hayan seleccionado áreas
    if (revision.areas_revisar.length === 0 && !revision.otra_area.trim()) {
      alert('Debe seleccionar al menos un área a revisar')
      return
    }

    // Validar que se hayan seleccionado especies
    if (especiesSeleccionadas.value.length === 0) {
      alert('Debe seleccionar al menos una especie objetivo')
      return
    }

    // Preparar datos para enviar
    const datosEnvio = {
      nombre: revision.nombre,
      descripcion: revision.descripcion,
      frecuencia_recomendada: revision.frecuencia_recomendada,
      frecuencia_personalizada: revision.frecuencia_recomendada === 'personalizada' ? revision.frecuencia_personalizada : null,
      areas_revisar: revision.areas_revisar,
      otra_area: revision.otra_area || null,
      indicadores_clave: revision.indicadores_clave || null,
      especies_objetivo: especiesSeleccionadas.value,
      edad_sugerida: revision.edad_sugerida ? parseFloat(revision.edad_sugerida) : null,
      edad_unidad: revision.edad_sugerida ? revision.edad_unidad : null,
      recomendaciones_profesionales: revision.recomendaciones_profesionales || null,
      riesgos_clinicos: revision.riesgos_clinicos || null
    }

    console.log('Enviando datos al servidor:', datosEnvio)

    const response = await fetch('/api/tipos-revision', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    })

    const data = await response.json()

    if (!response.ok) {
      console.error('Error del servidor:', data)
      
      if (data.errors) {
        const errores = Object.values(data.errors).flat().join('\n')
        throw new Error(`Errores de validación:\n${errores}`)
      }
      
      throw new Error(data.message || 'Error al registrar el tipo de revisión')
    }

    if (data.success) {
      alert('Tipo de revisión registrado correctamente')
      router.push('/veterinarios/tipos/revisiones')
    } else {
      throw new Error(data.message || 'Error desconocido')
    }

  } catch (error) {
    console.error('Error:', error)
    alert(`Error al registrar el tipo de revisión: ${error.message}`)
  } finally {
    loading.value = false
  }
}

const actualizarRevision = async () => {
  try {
    loading.value = true

    // Validaciones básicas
    if (!revision.nombre.trim()) {
      alert('El nombre es obligatorio')
      mostrarModal.value = false
      return
    }

    if (!revision.descripcion.trim()) {
      alert('La descripción es obligatoria')
      mostrarModal.value = false
      return
    }

    if (!revision.frecuencia_recomendada) {
      alert('La frecuencia recomendada es obligatoria')
      mostrarModal.value = false
      return
    }

    if (revision.frecuencia_recomendada === 'personalizada' && !revision.frecuencia_personalizada.trim()) {
      alert('Debe especificar la frecuencia personalizada')
      mostrarModal.value = false
      return
    }

    // Validar que se hayan seleccionado áreas
    if (revision.areas_revisar.length === 0 && !revision.otra_area.trim()) {
      alert('Debe seleccionar al menos un área a revisar')
      mostrarModal.value = false
      return
    }

    // Validar que se hayan seleccionado especies
    if (especiesSeleccionadas.value.length === 0) {
      alert('Debe seleccionar al menos una especie objetivo')
      mostrarModal.value = false
      return
    }

    // Preparar datos para enviar
    const datosEnvio = {
      nombre: revision.nombre,
      descripcion: revision.descripcion,
      frecuencia_recomendada: revision.frecuencia_recomendada,
      frecuencia_personalizada: revision.frecuencia_recomendada === 'personalizada' ? revision.frecuencia_personalizada : null,
      areas_revisar: revision.areas_revisar,
      otra_area: revision.otra_area || null,
      indicadores_clave: revision.indicadores_clave || null,
      especies_objetivo: especiesSeleccionadas.value,
      edad_sugerida: revision.edad_sugerida ? parseFloat(revision.edad_sugerida) : null,
      edad_unidad: revision.edad_sugerida ? revision.edad_unidad : null,
      recomendaciones_profesionales: revision.recomendaciones_profesionales || null,
      riesgos_clinicos: revision.riesgos_clinicos || null
    }

    console.log('Actualizando tipo de revisión:', datosEnvio)

    const response = await fetch(`/api/tipos-revision/${route.params.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    })

    const data = await response.json()

    if (!response.ok) {
      console.error('Error del servidor:', data)
      
      if (data.errors) {
        const errores = Object.values(data.errors).flat().join('\n')
        throw new Error(`Errores de validación:\n${errores}`)
      }
      
      throw new Error(data.message || 'Error al actualizar el tipo de revisión')
    }

    if (data.success) {
      alert('Tipo de revisión actualizado correctamente')
      router.push('/veterinarios/tipos/revisiones')
    } else {
      throw new Error(data.message || 'Error desconocido')
    }

  } catch (error) {
    console.error('Error:', error)
    alert(`Error al actualizar el tipo de revisión: ${error.message}`)
  } finally {
    loading.value = false
    mostrarModal.value = false
  }
}
</script>
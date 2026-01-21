<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esModoEdicion ? 'Modificar' : 'Registrar' }} Tipo de Alergia/Sensibilidad</h1>

    <form @submit.prevent="esModoEdicion ? actualizarAlergia() : registrarAlergia()" class="space-y-4">
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
            <label class="block font-medium">Nombre del tipo de alergia/sensibilidad</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="alergia.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Alergia a la penicilina, Sensibilidad alimentaria"
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
              v-model="alergia.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción de la alergia/sensibilidad"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Categoría</label>
            <select v-model="alergia.categoria" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="medicamento">Medicamento</option>
              <option value="alimento">Alimento</option>
              <option value="ambiental">Ambiental</option>
              <option value="contacto">Por contacto</option>
              <option value="otra">Otra</option>
            </select>
            <input 
              v-if="alergia.categoria === 'otra'"
              v-model="alergia.categoriaOtro"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique la categoría"
            />
          </div>
          
          <!-- REACCIONES COMUNES ASOCIADAS - MODIFICADO -->
          <div>
            <label class="block font-medium">Reacciones comunes asociadas</label>
            <div class="flex gap-2">
              <textarea 
                v-model="inputReacciones" 
                rows="2" 
                class="w-full border rounded p-2" 
                placeholder="Ej: Urticaria, Prurito, Edema facial, Dificultad respiratoria, etc."
              ></textarea>
              <button 
                type="button"
                @click="agregarItem('reacciones')"
                class="bg-blue-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-blue-600 transition-colors"
              >
                <font-awesome-icon :icon="['fas', 'plus']" />
              </button>
            </div>
            <!-- Lista de reacciones agregadas -->
            <div v-if="reaccionesAgregadas.length > 0" class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
              <div v-for="(reaccion, index) in reaccionesAgregadas" :key="index" 
                  class="flex items-center justify-between bg-blue-50 p-2 rounded text-sm">
                <span>{{ reaccion }}</span>
                <button 
                  type="button"
                  @click="eliminarItem('reacciones', index)"
                  class="text-red-500 hover:text-red-700"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Nivel de riesgo típico</label>
            <select v-model="alergia.riesgo" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="leve">Leve</option>
              <option value="moderado">Moderado</option>
              <option value="grave">Grave</option>
              <option value="muy_grave">Muy grave</option>
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
          <!-- ÁREAS AFECTADAS (ÁRBOL ANATÓMICO) - SEGÚN ESTÁNDAR -->
          <div class="my-8">
            <div class="flex items-center mb-6">
              <div class="flex-grow border-t border-gray-600"></div>
              <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
                Áreas Afectadas
                <span v-if="areasSeleccionadas.length > 0" class="text-sm text-green-600 ml-2">
                  ({{ areasSeleccionadas.length }} seleccionadas)
                </span>
              </h5>
              <div class="flex-grow border-t border-gray-600"></div>
            </div>
            
            <!-- Componente de árbol anatómico -->
            <ClinicalExaminationTree 
              ref="arbolAnatomicoRef"
              :initial-data="alergia.areas"
              @selection-change="handleArbolSelectionChange"
            />
            
            <!-- Área adicional -->
            <div class="mt-6">
              <label class="block font-medium mb-2">Otra área afectada (opcional)</label>
              <input 
                v-model="alergia.otraArea"
                type="text"
                class="w-full border rounded p-2"
                placeholder="Agregue otras áreas afectadas no incluidas en el árbol anatómico"
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
          <label class="block font-medium">Tratamiento recomendado estándar</label>
          <textarea 
            v-model="alergia.tratamiento" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Protocolo de tratamiento recomendado"
          ></textarea>
        </div>
        
        <!-- RECOMENDACIONES CLÍNICAS - MODIFICADO -->
        <div>
          <label class="block font-medium">Recomendaciones clínicas</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputRecomendaciones" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Evitar exposición, Mantener epinefrina a mano, Monitorear signos vitales, etc."
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
        
        <!-- SUSTANCIAS/FACTORES DESENCADENANTES - MODIFICADO -->
        <div>
          <label class="block font-medium">Sustancias/factores desencadenantes</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputDesencadenantes" 
              rows="2" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Polen, Ácaros, Penicilina, Proteína de leche, etc."
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('desencadenantes')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors"
            >
              <font-awesome-icon :icon="['fas', 'plus']" />
            </button>
          </div>
          <!-- Lista de desencadenantes agregados -->
          <div v-if="desencadenantesAgregados.length > 0" class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
            <div v-for="(desencadenante, index) in desencadenantesAgregados" :key="index" 
                class="flex items-center justify-between bg-green-50 p-2 rounded text-sm">
              <span>{{ desencadenante }}</span>
              <button 
                type="button"
                @click="eliminarItem('desencadenantes', index)"
                class="text-red-500 hover:text-red-700"
              >
                <font-awesome-icon :icon="['fas', 'times']" />
              </button>
            </div>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Conducta recomendada ante exposición</label>
          <textarea 
            v-model="alergia.conducta" 
            rows="3" 
            maxlength="500" 
            class="w-full border rounded p-2 resize-none"
          ></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ alergia.conducta.length }}/500 caracteres</p>
        </div>

        <div class="col-span-full">
          <div class="gap-2 items-center mb-1">
            <label class="block font-medium mb-1">Observaciones adicionales</label>
            <textarea 
            v-model="alergia.observaciones" 
            rows="3" 
            maxlength="500" 
            class="w-full border rounded p-2 resize-none"
          ></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ alergia.observaciones.length }}/500 caracteres</p>
          </div>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="button" @click="cancelar" class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors">Cancelar</button>
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors">
          {{ esModoEdicion ? 'Actualizar' : 'Registrar' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import CarruselEspecieVeterinario from '@/components/ElementosGraficos/CarruselEspecieVeterinario.vue'
import ClinicalExaminationTree from '@/components/ElementosGraficos/arbolAnatomico.vue'

// Inputs temporales para agregar elementos
const inputReacciones = ref('')
const inputRecomendaciones = ref('')
const inputDesencadenantes = ref('')

// Arrays para almacenar elementos agregados
const reaccionesAgregadas = ref([])
const recomendacionesAgregadas = ref([])
const desencadenantesAgregados = ref([])

const especiesSeleccionadas = ref([])
const areasSeleccionadas = ref([])
const arbolAnatomicoRef = ref(null)

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, user } = useAuth()

const esModoEdicion = computed(() => route.params.id !== undefined)

const alergiaId = ref(null)

const alergia = reactive({
  nombre: '',
  descripcion: '',
  categoria: '',
  categoriaOtro: '',
  reaccion: '', // Ahora será un string separado por comas
  riesgo: '',
  areas: [], // Array de nombres de áreas anatómicas
  otraArea: '',
  tratamiento: '',
  recomendaciones: '', // Ahora será un string separado por comas
  especie: 'todos',
  desencadenante: '', // Ahora será un string separado por comas
  conducta: '',
  observaciones: ''
})

// Función para agregar elementos
const agregarItem = (tipo) => {
  let inputValue, arrayDestino
  
  switch(tipo) {
    case 'reacciones':
      inputValue = inputReacciones.value.trim()
      arrayDestino = reaccionesAgregadas
      break
    case 'recomendaciones':
      inputValue = inputRecomendaciones.value.trim()
      arrayDestino = recomendacionesAgregadas
      break
    case 'desencadenantes':
      inputValue = inputDesencadenantes.value.trim()
      arrayDestino = desencadenantesAgregados
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
        case 'reacciones': inputReacciones.value = ''; break
        case 'recomendaciones': inputRecomendaciones.value = ''; break
        case 'desencadenantes': inputDesencadenantes.value = ''; break
      }
    } else {
      alert('Este elemento ya ha sido agregado')
    }
  }
}

// Función para eliminar elementos
const eliminarItem = (tipo, index) => {
  switch(tipo) {
    case 'reacciones':
      reaccionesAgregadas.value.splice(index, 1)
      break
    case 'recomendaciones':
      recomendacionesAgregadas.value.splice(index, 1)
      break
    case 'desencadenantes':
      desencadenantesAgregados.value.splice(index, 1)
      break
  }
}

// Watch para sincronizar arrays con los campos de la alergia
watch([reaccionesAgregadas, recomendacionesAgregadas, desencadenantesAgregados], () => {
  // Convertir arrays a strings separados por comas para el backend
  alergia.reaccion = reaccionesAgregadas.value.length > 0 
    ? reaccionesAgregadas.value.join(', ') 
    : ''
  
  alergia.recomendaciones = recomendacionesAgregadas.value.length > 0 
    ? recomendacionesAgregadas.value.join(', ') 
    : ''
  
  alergia.desencadenante = desencadenantesAgregados.value.length > 0 
    ? desencadenantesAgregados.value.join(', ') 
    : ''
  
  console.log('Valores convertidos para backend:')
  console.log('reaccion:', alergia.reaccion)
  console.log('recomendaciones:', alergia.recomendaciones)
  console.log('desencadenante:', alergia.desencadenante)
}, { deep: true })

// Función para manejar cambios en la selección del árbol
const handleArbolSelectionChange = (data) => {
  const newAreaNames = data.areaNames
  
  // Comparar para evitar bucles
  if (JSON.stringify(alergia.areas) !== JSON.stringify(newAreaNames)) {
    alergia.areas = newAreaNames
    areasSeleccionadas.value = data.areas
    console.log('Áreas seleccionadas:', alergia.areas)
  }
}

// Agregar watch para sincronización
watch(() => alergia.areas, (newAreas) => {
  console.log('🔄 alergia.areas cambió:', newAreas)
  
  // Solo actualizar si realmente hay cambios
  if (JSON.stringify(areasSeleccionadas.value.map(a => a.nombre)) !== JSON.stringify(newAreas)) {
    areasSeleccionadas.value = newAreas.map(area => ({
      nombre: area,
      id: null,
      sistema: ''
    }))
  }
}, { deep: true })

// Cargar datos cuando esté en modo edición
onMounted(async () => {
  if (esModoEdicion.value) {
    await cargarAlergia()
  }
})

const cargarAlergia = async () => {
  try {
    if (!isAuthenticated.value) {
      alert('Debe estar autenticado para modificar un tipo de alergia')
      return
    }

    const id = route.params.id
    const response = await axios.get(`/api/tipos-alergia/${id}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`
      }
    })

    if (response.data.success) {
      const datos = response.data.data
      alergiaId.value = datos.id
      
      // Mapear los datos del servidor al objeto reactivo
      alergia.nombre = datos.nombre || ''
      alergia.descripcion = datos.descripcion || ''
      alergia.categoria = datos.categoria || ''
      alergia.categoriaOtro = datos.categoria_otro || ''
      alergia.riesgo = datos.nivel_riesgo || ''
      alergia.otraArea = datos.otra_area || ''
      alergia.tratamiento = datos.tratamiento_recomendado || ''
      alergia.conducta = datos.conducta_recomendada || ''
      alergia.observaciones = datos.observaciones_adicionales || ''
      
      // Convertir strings del backend a arrays
      alergia.reaccion = datos.reaccion_comun || ''
      alergia.recomendaciones = datos.recomendaciones_clinicas || ''
      alergia.desencadenante = datos.desencadenante || ''
      
      // Convertir strings a arrays
      reaccionesAgregadas.value = alergia.reaccion 
        ? alergia.reaccion.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      recomendacionesAgregadas.value = alergia.recomendaciones 
        ? alergia.recomendaciones.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      desencadenantesAgregados.value = alergia.desencadenante 
        ? alergia.desencadenante.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      // Cargar las especies objetivo
      if (datos.especies && Array.isArray(datos.especies)) {
        especiesSeleccionadas.value = datos.especies
      } else if (datos.especie_objetivo) {
        especiesSeleccionadas.value = [datos.especie_objetivo]
      }
      
      // Cargar las áreas afectadas - IMPORTANTE
      if (datos.areas_afectadas && Array.isArray(datos.areas_afectadas)) {
        alergia.areas = datos.areas_afectadas
        areasSeleccionadas.value = datos.areas_afectadas.map(area => ({
          nombre: area,
          id: null,
          sistema: ''
        }))
        
        console.log('📋 Áreas cargadas para el árbol:', datos.areas_afectadas)
        console.log('📋 Número de áreas:', datos.areas_afectadas.length)
        
        // Forzar una actualización después de cargar los datos
        await new Promise(resolve => setTimeout(resolve, 100))
        
        // Si el árbol está disponible, llamar a loadInitialData directamente
        if (arbolAnatomicoRef.value) {
          console.log('📋 Llamando a loadInitialData directamente')
          arbolAnatomicoRef.value.loadInitialData(datos.areas_afectadas)
        }
      } else {
        alergia.areas = []
        areasSeleccionadas.value = []
      }
      
      console.log('✅ Datos cargados:')
      console.log('✅ Reacciones cargadas:', reaccionesAgregadas.value)
      console.log('✅ Recomendaciones cargadas:', recomendacionesAgregadas.value)
      console.log('✅ Desencadenantes cargados:', desencadenantesAgregados.value)
      
    } else {
      throw new Error(response.data.message || 'Error al cargar los datos')
    }
  } catch (error) {
    console.error('Error al cargar alergia:', error)
    alert(error.response?.data?.message || 'Error al cargar el tipo de alergia')
    router.back()
  }
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const registrarAlergia = async () => {
  try {
    if (!isAuthenticated.value) {
      alert('Debe estar autenticado para registrar un tipo de alergia')
      return
    }

    // Validaciones
    if (reaccionesAgregadas.value.length === 0) {
      alert('Debe agregar al menos una reacción común asociada')
      return
    }

    if (alergia.areas.length === 0 && !alergia.otraArea.trim()) {
      alert('Debe seleccionar al menos un área afectada')
      return
    }

    if (especiesSeleccionadas.value.length === 0) {
      alert('Debe seleccionar al menos una especie objetivo')
      return
    }

    // Combinar áreas del árbol con "otra área" si existe
    let todasLasAreas = [...alergia.areas]
    if (alergia.otraArea && alergia.otraArea.trim() !== '') {
      todasLasAreas.push(alergia.otraArea.trim())
    }

    const payload = {
      nombre: alergia.nombre,
      descripcion: alergia.descripcion,
      categoria: alergia.categoria,
      categoria_otro: alergia.categoria === 'otra' ? alergia.categoriaOtro : null,
      reaccion_comun: alergia.reaccion,
      nivel_riesgo: alergia.riesgo,
      areas_afectadas: todasLasAreas,
      otra_area: alergia.otraArea || null,
      tratamiento_recomendado: alergia.tratamiento || null,
      recomendaciones_clinicas: alergia.recomendaciones || null,
      especies: especiesSeleccionadas.value.length > 0 ? especiesSeleccionadas.value : ['todos'],
      desencadenante: alergia.desencadenante || null,
      conducta_recomendada: alergia.conducta || null,
      observaciones_adicionales: alergia.observaciones || null
    }

    console.log('Datos a enviar:', payload)
    
    const response = await axios.post('/api/tipos-alergia', payload, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json'
      }
    })

    if (response.data.success) {
      alert('Tipo de alergia/sensibilidad registrado correctamente')
      router.push('/veterinarios/tipos/alergias')
    } else {
      throw new Error(response.data.message || 'Error al registrar')
    }
    
  } catch (error) {
    console.error('Error al registrar alergia:', error)
    
    if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat()
      alert('Errores de validación:\n' + errors.join('\n'))
    } else {
      alert(error.response?.data?.message || 'Error al registrar el tipo de alergia')
    }
  }
}

const actualizarAlergia = async () => {
  try {
    if (!isAuthenticated.value) {
      alert('Debe estar autenticado para modificar un tipo de alergia')
      return
    }

    // Validaciones
    if (reaccionesAgregadas.value.length === 0) {
      alert('Debe agregar al menos una reacción común asociada')
      return
    }

    if (alergia.areas.length === 0 && !alergia.otraArea.trim()) {
      alert('Debe seleccionar al menos un área afectada')
      return
    }

    if (especiesSeleccionadas.value.length === 0) {
      alert('Debe seleccionar al menos una especie objetivo')
      return
    }

    // Combinar áreas del árbol con "otra área" si existe
    let todasLasAreas = [...alergia.areas]
    if (alergia.otraArea && alergia.otraArea.trim() !== '') {
      todasLasAreas.push(alergia.otraArea.trim())
    }

    const payload = {
      nombre: alergia.nombre,
      descripcion: alergia.descripcion,
      categoria: alergia.categoria,
      categoria_otro: alergia.categoria === 'otra' ? alergia.categoriaOtro : null,
      reaccion_comun: alergia.reaccion,
      nivel_riesgo: alergia.riesgo,
      areas_afectadas: todasLasAreas,
      otra_area: alergia.otraArea || null,
      tratamiento_recomendado: alergia.tratamiento || null,
      recomendaciones_clinicas: alergia.recomendaciones || null,
      especies: especiesSeleccionadas.value.length > 0 ? especiesSeleccionadas.value : ['todos'],
      desencadenante: alergia.desencadenante || null,
      conducta_recomendada: alergia.conducta || null,
      observaciones_adicionales: alergia.observaciones || null
    }

    console.log('Datos a actualizar:', payload)
    
    const response = await axios.put(`/api/tipos-alergia/${alergiaId.value}`, payload, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json'
      }
    })

    if (response.data.success) {
      alert('Tipo de alergia/sensibilidad actualizado correctamente')
      router.push('/veterinarios/tipos/alergias')
    } else {
      throw new Error(response.data.message || 'Error al actualizar')
    }
    
  } catch (error) {
    console.error('Error al actualizar alergia:', error)
    
    if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat()
      alert('Errores de validación:\n' + errors.join('\n'))
    } else {
      alert(error.response?.data?.message || 'Error al actualizar el tipo de alergia')
    }
  }
}
</script>
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar' : 'Registrar' }} Tipo de Fármaco</h1>

    <!-- Mensajes de éxito/error -->
    <div v-if="mensaje.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
      {{ mensaje.text }}
    </div>
    <div v-if="mensaje.error" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
      {{ mensaje.text }}
    </div>

    <form @submit.prevent="esEdicion ? actualizarFarmaco() : registrarFarmaco()" class="space-y-4">
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
            <label class="block font-medium">Nombre comercial</label>
            <input 
              v-model="farmaco.nombre_comercial" 
              type="text" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Ej: Rimadyl, Baytril, etc."
              :disabled="loading"
            />
          </div>

          <div>
            <label class="block font-medium">Nombre genérico</label>
            <input 
              v-model="farmaco.nombre_generico" 
              type="text" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Ej: Carprofeno, Enrofloxacina"
              :disabled="loading"
            />
          </div>

          <!-- COMPOSICIÓN/PRINCIPIOS ACTIVOS - MODIFICADO -->
          <div>
            <label class="block font-medium">Composición/principios activos</label>
            <div class="flex gap-2">
              <textarea 
                v-model="inputComposicion" 
                rows="2" 
                class="w-full border rounded p-2" 
                placeholder="Principio activo y concentración (una por línea)"
                :disabled="loading"
              ></textarea>
              <button 
                type="button"
                @click="agregarItem('composicion')"
                class="bg-blue-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-blue-600 transition-colors disabled:bg-blue-300"
                :disabled="loading || !inputComposicion.trim()"
              >
                <font-awesome-icon :icon="['fas', 'plus']" />
              </button>
            </div>
            <!-- Lista de principios activos agregados -->
            <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
              <div v-if="composicionAgregada.length > 0" class="mt-2 space-y-1">
                <div v-for="(principio, index) in composicionAgregada" :key="index" 
                    class="flex items-center justify-between bg-blue-50 p-2 rounded text-sm">
                  <span>{{ principio }}</span>
                  <button 
                    type="button"
                    @click="eliminarItem('composicion', index)"
                    class="text-red-500 hover:text-red-700"
                    :disabled="loading"
                  >
                    <font-awesome-icon :icon="['fas', 'times']" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Categoría terapéutica</label>
            <select 
              v-model="farmaco.categoria" 
              required 
              class="w-full border rounded p-2"
              :disabled="loading"
              @change="farmaco.categoria_otro = ''"
            >
              <option value="">Seleccione una opción</option>
              <option value="analgesico">Analgésico</option>
              <option value="antibiotico">Antibiótico</option>
              <option value="antiparasitario">Antiparasitario</option>
              <option value="antiinflamatorio">Antiinflamatorio</option>
              <option value="antifungico">Antifúngico</option>
              <option value="antiviral">Antiviral</option>
              <option value="anestesico">Anestésico</option>
              <option value="otro">Otro</option>
            </select>
            <input 
              v-if="farmaco.categoria === 'otro'"
              v-model="farmaco.categoria_otro"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique la categoría"
              :disabled="loading"
              required
            />
          </div>

          <div>
            <label class="block font-medium mb-2">Especie objetivo</label>
            <CarruselEspecieVeterinario v-model="especiesSeleccionadas" :disabled="loading" />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Dosis terapéutica recomendada</label>
            <div class="flex">
              <input 
                v-model="farmaco.dosis" 
                type="number" 
                min="0.1" 
                step="0.1" 
                required 
                class="w-3/4 border rounded-l p-2" 
                placeholder="Cantidad"
                :disabled="loading"
              />
              <select 
                v-model="farmaco.unidad" 
                required 
                class="w-1/4 border rounded-r p-2"
                :disabled="loading"
              >
                <option value="mg">mg</option>
                <option value="ml">ml</option>
                <option value="UI">UI</option>
                <option value="mcg">mcg</option>
                <option value="gotas">gotas</option>
              </select>
            </div>
            <div class="flex mt-2">
              <select 
                v-model="farmaco.frecuencia_unidad" 
                required 
                class="w-1/2 border rounded-l p-2"
                :disabled="loading"
              >
                <option value="kg">por kg</option>
                <option value="dosis">por dosis</option>
              </select>
              <input 
                v-model="farmaco.frecuencia" 
                type="text" 
                required 
                class="w-1/2 border rounded-r p-2" 
                placeholder="Ej: cada 8h, 1 vez al día"
                :disabled="loading"
              />
            </div>
          </div>

          <div>
            <label class="block font-medium">Vía de administración</label>
            <select 
              v-model="farmaco.via_administracion" 
              required 
              class="w-full border rounded p-2"
              :disabled="loading"
            >
              <option value="">Seleccione una opción</option>
              <option value="oral">Oral</option>
              <option value="subcutanea">Subcutánea</option>
              <option value="intramuscular">Intramuscular</option>
              <option value="intravenosa">Intravenosa</option>
              <option value="topica">Tópica</option>
              <option value="oftalmica">Oftálmica</option>
              <option value="otica">Ótica</option>
              <option value="otra">Otra</option>
            </select>
          </div>

          <!-- INDICACIONES CLÍNICAS COMUNES - MODIFICADO -->
          <div>
            <label class="block font-medium">Indicaciones clínicas comunes</label>
            <div class="flex gap-2">
              <textarea 
                v-model="inputIndicaciones" 
                rows="3" 
                class="w-full border rounded p-2" 
                placeholder="Condiciones o enfermedades donde se indica este fármaco (una por línea)"
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
              placeholder="Situaciones donde no debe usarse este fármaco (una por línea)"
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

        <!-- INTERACCIONES MEDICAMENTOSAS - MODIFICADO -->
        <div>
          <label class="block font-medium">Interacciones medicamentosas</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputInteracciones" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Fármacos que no deben combinarse con este (una por línea)"
              :disabled="loading"
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('interacciones')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors disabled:bg-green-300"
              :disabled="loading || !inputInteracciones.trim()"
            >
              <font-awesome-icon :icon="['fas', 'plus']" />
            </button>
          </div>
          <!-- Lista de interacciones agregadas -->
          <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
            <div v-if="interaccionesAgregadas.length > 0" class="mt-2 space-y-1">
              <div v-for="(interaccion, index) in interaccionesAgregadas" :key="index" 
                  class="flex items-center justify-between bg-green-50 p-2 rounded text-sm">
                <span>{{ interaccion }}</span>
                <button 
                  type="button"
                  @click="eliminarItem('interacciones', index)"
                  class="text-red-500 hover:text-red-700"
                  :disabled="loading"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- REACCIONES ADVERSAS FRECUENTES - MODIFICADO -->
        <div>
          <label class="block font-medium">Reacciones adversas frecuentes</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputReacciones" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Efectos secundarios comunes (una por línea)"
              :disabled="loading"
            ></textarea>
            <button 
              type="button"
              @click="agregarItem('reacciones')"
              class="bg-green-500 text-white w-10 h-10 rounded flex items-center justify-center hover:bg-green-600 transition-colors disabled:bg-green-300"
              :disabled="loading || !inputReacciones.trim()"
            >
              <font-awesome-icon :icon="['fas', 'plus']" />
            </button>
          </div>
          <!-- Lista de reacciones agregadas -->
          <div class="mt-3 border border-gray-200 rounded-lg p-2 space-y-1 bg-white shadow-sm">
            <div v-if="reaccionesAgregadas.length > 0" class="mt-2 space-y-1">
              <div v-for="(reaccion, index) in reaccionesAgregadas" :key="index" 
                  class="flex items-center justify-between bg-green-50 p-2 rounded text-sm">
                <span>{{ reaccion }}</span>
                <button 
                  type="button"
                  @click="eliminarItem('reacciones', index)"
                  class="text-red-500 hover:text-red-700"
                  :disabled="loading"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div>
          <label class="block font-medium">Fabricante/Laboratorio</label>
          <input 
            v-model="farmaco.fabricante" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Nombre del laboratorio"
            :disabled="loading"
          />
        </div>

        <!-- RECOMENDACIONES CLÍNICAS - MODIFICADO -->
        <div class="col-span-full">
          <label class="block font-medium">Recomendaciones clínicas</label>
          <div class="flex gap-2">
            <textarea 
              v-model="inputRecomendaciones" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Consejos para su administración y manejo (una por línea)"
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
            v-model="farmaco.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante sobre este fármaco"
            :disabled="loading"
          ></textarea>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button 
          type="button" 
          @click="cancelar" 
          class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors"
          :disabled="loading"
        >
          {{ loading ? 'Cargando...' : 'Cancelar' }}
        </button>
        <button 
          type="submit" 
          class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
          :disabled="loading || !formularioValido"
        >
          {{ loading ? (esEdicion ? 'Editando...' : 'Registrando...') : (esEdicion ? 'Editar Tipo' : '+ Tipo') }}
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

const loading = ref(false)
const mensaje = reactive({
  success: false,
  error: false,
  text: ''
})

// Determinar si estamos en modo edición
const esEdicion = computed(() => {
  return route.query.editar || route.params.id
})

// Obtener el ID del fármaco a editar
const farmacoId = computed(() => {
  return route.query.editar || route.params.id
})

// Inputs temporales para agregar elementos
const inputComposicion = ref('')
const inputIndicaciones = ref('')
const inputContraindicaciones = ref('')
const inputInteracciones = ref('')
const inputReacciones = ref('')
const inputRecomendaciones = ref('')

// Arrays para almacenar elementos agregados
const composicionAgregada = ref([])
const indicacionesAgregadas = ref([])
const contraindicacionesAgregadas = ref([])
const interaccionesAgregadas = ref([])
const reaccionesAgregadas = ref([])
const recomendacionesAgregadas = ref([])

// Ajusté los nombres para que coincidan con el modelo del backend
const farmaco = reactive({
  nombre_comercial: '',
  nombre_generico: '',
  composicion: '',
  categoria: '',
  categoria_otro: '',
  especies: [],
  dosis: '',
  unidad: 'mg',
  frecuencia_unidad: 'kg',
  frecuencia: '',
  via_administracion: '',
  indicaciones_clinicas: '',
  contraindicaciones: '',
  interacciones_medicamentosas: '',
  reacciones_adversas: '',
  fabricante: '',
  recomendaciones_clinicas: '',
  observaciones: ''
})

// Computed para validar el formulario
const formularioValido = computed(() => {
  return farmaco.nombre_comercial.trim() !== '' &&
         farmaco.nombre_generico.trim() !== '' &&
         composicionAgregada.value.length > 0 && // Validar que haya al menos un principio activo
         farmaco.categoria !== '' &&
         especiesSeleccionadas.value.length > 0 &&
         farmaco.dosis !== '' &&
         farmaco.frecuencia !== '' &&
         farmaco.via_administracion !== '' &&
         indicacionesAgregadas.value.length > 0 && // Validar que haya al menos una indicación
         (!(farmaco.categoria === 'otro') || farmaco.categoria_otro.trim() !== '') // Validar categoría "otro"
})

// Función para agregar elementos
const agregarItem = (tipo) => {
  let inputValue, arrayDestino
  
  switch(tipo) {
    case 'composicion':
      inputValue = inputComposicion.value.trim()
      arrayDestino = composicionAgregada
      break
    case 'indicaciones':
      inputValue = inputIndicaciones.value.trim()
      arrayDestino = indicacionesAgregadas
      break
    case 'contraindicaciones':
      inputValue = inputContraindicaciones.value.trim()
      arrayDestino = contraindicacionesAgregadas
      break
    case 'interacciones':
      inputValue = inputInteracciones.value.trim()
      arrayDestino = interaccionesAgregadas
      break
    case 'reacciones':
      inputValue = inputReacciones.value.trim()
      arrayDestino = reaccionesAgregadas
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
        case 'composicion': inputComposicion.value = ''; break
        case 'indicaciones': inputIndicaciones.value = ''; break
        case 'contraindicaciones': inputContraindicaciones.value = ''; break
        case 'interacciones': inputInteracciones.value = ''; break
        case 'reacciones': inputReacciones.value = ''; break
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
    case 'composicion':
      composicionAgregada.value.splice(index, 1)
      break
    case 'indicaciones':
      indicacionesAgregadas.value.splice(index, 1)
      break
    case 'contraindicaciones':
      contraindicacionesAgregadas.value.splice(index, 1)
      break
    case 'interacciones':
      interaccionesAgregadas.value.splice(index, 1)
      break
    case 'reacciones':
      reaccionesAgregadas.value.splice(index, 1)
      break
    case 'recomendaciones':
      recomendacionesAgregadas.value.splice(index, 1)
      break
  }
}

// Watch para sincronizar arrays con los campos del formulario
watch([composicionAgregada, indicacionesAgregadas, contraindicacionesAgregadas, 
       interaccionesAgregadas, reaccionesAgregadas, recomendacionesAgregadas], () => {
  // Convertir arrays a strings separados por comas para el backend
  farmaco.composicion = composicionAgregada.value.length > 0 
    ? composicionAgregada.value.join(', ') 
    : ''
  
  farmaco.indicaciones_clinicas = indicacionesAgregadas.value.length > 0 
    ? indicacionesAgregadas.value.join(', ') 
    : ''
  
  farmaco.contraindicaciones = contraindicacionesAgregadas.value.length > 0 
    ? contraindicacionesAgregadas.value.join(', ') 
    : ''
  
  farmaco.interacciones_medicamentosas = interaccionesAgregadas.value.length > 0 
    ? interaccionesAgregadas.value.join(', ') 
    : ''
  
  farmaco.reacciones_adversas = reaccionesAgregadas.value.length > 0 
    ? reaccionesAgregadas.value.join(', ') 
    : ''
  
  farmaco.recomendaciones_clinicas = recomendacionesAgregadas.value.length > 0 
    ? recomendacionesAgregadas.value.join(', ') 
    : ''
}, { deep: true })

// Watch para sincronizar especiesSeleccionadas con farmaco.especies
watch(especiesSeleccionadas, (newEspecies) => {
  farmaco.especies = [...newEspecies]
})

onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      mostrarMensaje('Debe iniciar sesión para acceder a esta página', 'error')
      setTimeout(() => {
        router.push('/')
      }, 2000)
      return
    }
  }

  // Si estamos en modo edición, cargar los datos existentes
  if (esEdicion.value) {
    await cargarFarmaco()
  }
})

// Cargar datos del fármaco para edición
const cargarFarmaco = async () => {
  try {
    loading.value = true
    const response = await fetch(`/api/tipos-farmaco/${farmacoId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Error al cargar el fármaco')
    }

    if (data.success) {
      // Llenar el formulario con los datos del fármaco
      Object.keys(farmaco).forEach(key => {
        if (data.data[key] !== undefined && data.data[key] !== null) {
          farmaco[key] = data.data[key]
        }
      })

      // Sincronizar especies seleccionadas
      if (data.data.especies && Array.isArray(data.data.especies)) {
        especiesSeleccionadas.value = [...data.data.especies]
      }

      // Cargar arrays desde strings del backend
      composicionAgregada.value = farmaco.composicion 
        ? farmaco.composicion.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      indicacionesAgregadas.value = farmaco.indicaciones_clinicas 
        ? farmaco.indicaciones_clinicas.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      contraindicacionesAgregadas.value = farmaco.contraindicaciones 
        ? farmaco.contraindicaciones.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      interaccionesAgregadas.value = farmaco.interacciones_medicamentosas 
        ? farmaco.interacciones_medicamentosas.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      reaccionesAgregadas.value = farmaco.reacciones_adversas 
        ? farmaco.reacciones_adversas.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      recomendacionesAgregadas.value = farmaco.recomendaciones_clinicas 
        ? farmaco.recomendaciones_clinicas.split(',').map(s => s.trim()).filter(s => s !== '')
        : []
      
      console.log('Arrays cargados:', {
        composicion: composicionAgregada.value,
        indicaciones: indicacionesAgregadas.value,
        contraindicaciones: contraindicacionesAgregadas.value,
        interacciones: interaccionesAgregadas.value,
        reacciones: reaccionesAgregadas.value,
        recomendaciones: recomendacionesAgregadas.value
      })

    } else {
      throw new Error(data.message || 'Error al cargar el fármaco')
    }

  } catch (error) {
    console.error('Error cargando fármaco:', error)
    mostrarMensaje(error.message || 'Error al cargar los datos del fármaco', 'error')
    setTimeout(() => {
      router.push('/veterinarios/tipos/farmacos')
    }, 3000)
  } finally {
    loading.value = false
  }
}

const mostrarMensaje = (texto, tipo = 'success') => {
  mensaje.text = texto
  mensaje.success = tipo === 'success'
  mensaje.error = tipo === 'error'
  
  setTimeout(() => {
    mensaje.success = false
    mensaje.error = false
    mensaje.text = ''
  }, 5000)
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const registrarFarmaco = async () => {
  // Validaciones usando el computed property
  if (!formularioValido.value) {
    mostrarMensaje('Por favor complete todos los campos obligatorios', 'error')
    return
  }

  loading.value = true

  try {
    const response = await fetch('/api/tipos-farmaco', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(farmaco)
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Error al registrar el fármaco')
    }

    if (data.success) {
      mostrarMensaje('Tipo de fármaco registrado exitosamente')
      
      // Limpiar formulario
      Object.keys(farmaco).forEach(key => {
        if (key !== 'unidad' && key !== 'frecuencia_unidad') {
          farmaco[key] = ''
        }
      })
      farmaco.unidad = 'mg'
      farmaco.frecuencia_unidad = 'kg'
      
      // Limpiar arrays
      composicionAgregada.value = []
      indicacionesAgregadas.value = []
      contraindicacionesAgregadas.value = []
      interaccionesAgregadas.value = []
      reaccionesAgregadas.value = []
      recomendacionesAgregadas.value = []
      especiesSeleccionadas.value = []
      
      // Limpiar inputs temporales
      inputComposicion.value = ''
      inputIndicaciones.value = ''
      inputContraindicaciones.value = ''
      inputInteracciones.value = ''
      inputReacciones.value = ''
      inputRecomendaciones.value = ''
      
      // Redirigir después de 2 segundos
      setTimeout(() => {
        router.push('/veterinarios/tipos/farmacos')
      }, 2000)
    } else {
      throw new Error(data.message || 'Error al registrar el fármaco')
    }

  } catch (error) {
    console.error('Error al registrar fármaco:', error)
    
    if (error.message.includes('validation') || error.message.includes('validación')) {
      mostrarMensaje('Error en los datos del formulario. Por favor verifique la información.', 'error')
    } else {
      mostrarMensaje(error.message || 'Error al registrar el fármaco. Intente nuevamente.', 'error')
    }
  } finally {
    loading.value = false
  }
}

const actualizarFarmaco = async () => {
  // Validaciones usando el computed property
  if (!formularioValido.value) {
    mostrarMensaje('Por favor complete todos los campos obligatorios', 'error')
    return
  }

  loading.value = true

  try {
    const response = await fetch(`/api/tipos-farmaco/${farmacoId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(farmaco)
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Error al editar el fármaco')
    }

    if (data.success) {
      mostrarMensaje('Tipo de fármaco editado exitosamente')
      
      // Redirigir después de 2 segundos
      setTimeout(() => {
        router.push('/veterinarios/tipos/farmacos')
      }, 2000)
    } else {
      throw new Error(data.message || 'Error al editar el fármaco')
    }

  } catch (error) {
    console.error('Error al editar fármaco:', error)
    
    if (error.message.includes('validation') || error.message.includes('validación')) {
      mostrarMensaje('Error en los datos del formulario. Por favor verifique la información.', 'error')
    } else {
      mostrarMensaje(error.message || 'Error al editar el fármaco. Intente nuevamente.', 'error')
    }
  } finally {
    loading.value = false
  }
}
</script>
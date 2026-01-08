<!-- registrarDiagnostico -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Diagnóstico</h1>

    <!-- Mostrar error si no hay mascotaId -->
    <div v-if="!mascotaId" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
      <p class="font-bold">Error</p>
      <p>No se pudo identificar la mascota. Por favor, regrese a la página anterior.</p>
      <button @click="volverAtras" class="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
        Volver atrás
      </button>
    </div>

    <!-- Mostrar error de carga de mascota -->
    <div v-if="errorCargandoMascota && mascotaId" class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
      <p class="font-bold">Advertencia</p>
      <p>{{ errorCargandoMascota }}</p>
      <p class="text-sm mt-1">Puede continuar registrando el diagnóstico, pero algunas funciones pueden no estar disponibles.</p>
    </div>

    <form v-if="mascotaId" @submit.prevent="registrarDiagnostico" class="space-y-4">
      <!-- DATOS OBLIGATORIOS -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Obligatorios</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna izquierda -->
        <div class="space-y-4">
          <!-- Selección de Tipo de Diagnóstico -->
          <div>
            <label class="block font-medium mb-1">Tipo de Diagnóstico</label>
            <div class="flex gap-2">
              <select
                v-model="diagnostico.tipo_diagnostico_id"
                required
                class="w-full border rounded p-2"
                @change="onTipoDiagnosticoChange"
                :disabled="cargandoDatos"
              >
                <option value="">Seleccione un tipo de diagnóstico</option>
                <option
                  v-for="tipo in tiposDiagnostico"
                  :key="tipo.id"
                  :value="tipo.id"
                >
                  {{ tipo.nombre }}
                </option>
              </select>

              <!-- Botón de + Tipo -->
              <button 
                type="button"
                @click="abrirRegistroTipoDiagnostico"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
                :disabled="cargandoDatos"
              >
                + Tipo 
              </button>
            </div>
          </div>

          <div>
            <label class="block font-medium">Nombre del diagnóstico</label>
            <input 
              v-model="diagnostico.nombre" 
              type="text" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Ej: Insuficiencia renal, parvovirus, etc."
              :disabled="cargandoDatos"
            />
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">Centro Veterinario donde se realizó</label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="diagnostico.centro_veterinario_id"
                class="w-full border rounded p-2 bg-gray-50"
              >
                <div class="font-semibold">
                  {{ obtenerNombreCentroSeleccionado() }}
                </div>
                <div class="text-sm text-gray-600">
                  {{ obtenerDireccionCentroSeleccionado() }}
                </div>
              </div>
              
              <div 
                v-else
                class="w-full border rounded p-2 text-gray-400 italic"
              >
                Ningún centro veterinario seleccionado
              </div>

              <button 
                type="button"
                @click="abrirOverlayCentros"
                class="bg-green-500 text-white text-xl px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap"
                :disabled="cargandoDatos"
              >
                + Centro
              </button>
            </div>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Fecha de diagnóstico</label>
            <input 
              v-model="diagnostico.fecha_diagnostico"
              type="date" 
              required 
              class="w-full border rounded p-2"
              :disabled="cargandoDatos"
            />
          </div>

          <div>
            <label class="block font-medium">Estado/evolución</label>
            <select 
              v-model="diagnostico.estado" 
              required 
              class="w-full border rounded p-2"
              :disabled="cargandoDatos"
            >
              <option value="">Seleccione una opción</option>
              <option value="activo">Activo</option>
              <option value="resuelto">Resuelto</option>
              <option value="cronico">Crónico</option>
              <option value="seguimiento">En seguimiento</option>
              <option value="sospecha">Sospecha</option>
            </select>
          </div>
        </div>
      </div>

      <!-- DATOS OPCIONALES -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Opcionales</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 gap-8 mt-6">
        <div>
          <label class="block font-medium mb-1">Diagnósticos diferenciales considerados</label>
          <div class="flex gap-4">
            <textarea 
              v-model="diagnostico.diferenciales" 
              rows="3" 
              class="border rounded p-2 resize-none w-128" 
              placeholder="Liste otros diagnósticos considerados"
              readonly
              :disabled="cargandoDatos"
            >
            </textarea>
            <button 
              type="button"
              @click="abrirSelectorDiferenciales"
              class="bg-orange-500 text-white px-4 rounded font-bold hover:bg-orange-700 transition-colors whitespace-nowrap"
              :disabled="cargandoDatos"
            >
              + Diagnóstico
            </button>
          </div>
          
          <!-- Etiquetas de diagnósticos seleccionados -->
          <div v-if="diagnosticosSeleccionados.length > 0" class="mt-3">
            <div class="flex flex-wrap gap-2">
              <div 
                v-for="(diag, index) in diagnosticosSeleccionados" 
                :key="diag.id || index"
                class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm flex items-center gap-2"
              >
                <span>ID: {{ diag.id }} - {{ diag.nombre }}</span>
                <button 
                  type="button"
                  @click="eliminarDiagnosticoDiferencial(index)"
                  class="text-blue-600 hover:text-blue-800"
                  :disabled="cargandoDatos"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div>
          <label class="block font-medium mb-1">Exámenes complementarios utilizados</label>
          <textarea 
            v-model="diagnostico.examenes" 
            rows="3" 
            class="w-full border rounded p-2 resize-none" 
            placeholder="Ej: Hemograma, radiografía, ecografía..."
            :disabled="cargandoDatos"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium mb-1">Conducta terapéutica sugerida</label>
          <textarea 
            v-model="diagnostico.conducta" 
            rows="4" 
            class="w-full border rounded p-2 resize-none" 
            placeholder="Indique el tratamiento recomendado"
            :disabled="cargandoDatos"
          ></textarea>
        </div>

        <!-- Archivos adjuntos -->
        <div class="col-span-full">
          <label class="block font-medium mb-2">Archivos adjuntos</label>
          <div class="flex flex-wrap gap-x-2 gap-y-2">
            <div
              v-for="(archivo, index) in archivos"
              :key="index"
              class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-20 w-20"
              @click="!archivo.preview && activarInput(index)"
              :class="{ 'opacity-50': cargandoDatos }"
            >
              <!-- Botón eliminar -->
              <button
                type="button"
                @click.stop="quitarArchivo(index)"
                v-if="archivo.preview"
                class="absolute top-0.5 right-0.5 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700"
                :disabled="cargandoDatos"
              >
                <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-lg" />
              </button>

              <!-- Input oculto -->
              <input
                :ref="el => inputsArchivo[index] = el"
                type="file"
                @change="handleArchivo($event, index)"
                class="hidden"
                :disabled="cargandoDatos"
              />

              <!-- Vista previa -->
              <div v-if="archivo.preview" class="h-full flex flex-col">
                <img
                  v-if="esImagen(archivo.archivo)"
                  :src="archivo.preview"
                  alt="Preview"
                  class="w-full h-full object-cover rounded-md mx-auto flex-grow"
                />
                <div v-else class="h-full flex items-center justify-center p-1">
                  <font-awesome-icon :icon="['fas', 'file']" class="text-3xl text-gray-500" />
                </div>
                <div class="text-[10px] truncate px-1">{{ archivo.archivo.name }}</div>
              </div>

              <!-- Indicador visual si no hay archivo -->
              <div v-else class="text-green-400 flex flex-col justify-center items-center h-full">
                <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-2xl mb-0.5" />
                <div class="text-[10px] text-gray-400">Agregar</div>
              </div>
            </div>
          </div>
          <p class="text-xs text-gray-500 mt-1">Puede adjuntar recetas, imágenes del medicamento, informes, etc.</p>
        </div>
      </div>

      <!-- Selección del medio de envío -->
      <div class="mt-8">
        <CarruselMedioEnvio 
          v-if="usuarioId && !cargandoDatos" 
          :usuario-id="usuarioId" 
          @update:medio="diagnostico.medio_envio = $event" 
        />
        
        <div v-else class="text-center py-4">
          <p v-if="cargandoDatos" class="text-gray-500">Cargando información...</p>
          <p v-else-if="errorCargandoMascota" class="text-yellow-600">
            No se pudo cargar la información del dueño. Algunas funciones pueden no estar disponibles.
          </p>
          <p v-else class="text-gray-500">Cargando información del dueño...</p>
        </div>

        <div v-if="diagnostico.medio_envio" class="mt-4 text-center text-gray-700">
          <span class="font-semibold">Medio seleccionado:</span>
          <span class="ml-1 text-blue-600 font-medium">
            {{ obtenerNombreMedio(diagnostico.medio_envio) }}
          </span>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button
          type="button"
          @click="cancelar"
          class="bg-gray-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-gray-700 transition-colors"
          :disabled="procesando"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="procesando || cargandoDatos"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300 disabled:cursor-not-allowed"
        >
          {{ procesando ? 'Registrando...' : 'Registrar Diagnóstico' }}
        </button>
      </div>
    </form>

    <!-- Overlay para selector de diagnósticos diferenciales -->
    <div v-if="mostrarSelectorDiferenciales" class="fixed inset-0 z-50 overflow-y-auto">
      <!-- Overlay de fondo -->
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
      
      <!-- Contenedor del modal -->
      <div class="flex min-h-full items-center justify-center p-4">
        <!-- Contenido del modal -->
        <div class="relative bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
          <!-- Header del modal -->
          <div class="sticky top-0 z-10 bg-white border-b px-6 py-4 flex justify-between items-center">
            <div>
              <h2 class="text-2xl font-bold text-gray-900">Seleccionar Diagnósticos Diferenciales</h2>
              <p class="text-gray-600">Seleccione uno o más diagnósticos para agregar como diferenciales</p>
            </div>
            <button
              @click="cerrarSelectorDiferenciales"
              class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-100"
            >
              <font-awesome-icon :icon="['fas', 'times']" class="text-xl" />
            </button>
          </div>

          <!-- Componente de selector -->
          <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
            <DiagnosticosDiferenciales
              ref="selectorDiferenciales"
              @diagnosis-selected="agregarDiagnosticoDiferencial"
              @diagnosis-confirmed="confirmarDiagnosticoDiferencial"
            />
          </div>

          <!-- Footer del modal -->
          <div class="sticky bottom-0 bg-gray-50 border-t px-6 py-4 flex justify-between items-center">
            <div>
              <span class="text-sm text-gray-600">
                {{ diagnosticosSeleccionados.length }} diagnóstico(s) seleccionado(s)
              </span>
            </div>
            <div class="flex gap-3">
              <button
                @click="cerrarSelectorDiferenciales"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
              >
                Cancelar
              </button>
              <button
                @click="finalizarSeleccionDiferenciales"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                Agregar seleccionados
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Componente externo del overlay para centros veterinarios -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="diagnostico.centro_veterinario_id"
      @cerrar="mostrarOverlayCentros = false"
      @seleccionar="seleccionarCentro"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import DiagnosticosDiferenciales from '@/components/ElementosGraficos/DiagnosticosDiferenciales.vue'
import SeleccionCentroVeterinario from '@/components/ElementosGraficos/SeleccionCentroVeterinario.vue'
import CarruselMedioEnvio from '@/components/ElementosGraficos/CarruselMedioEnvio.vue'

const router = useRouter()
const route = useRoute()

// Obtener mascotaId de la query, si no está en query, intentar de params
const mascotaId = computed(() => {
  return route.query.mascotaId || route.params.mascotaId || route.params.id
})

console.log('🔍 Route query:', route.query)
console.log('🔍 Route params:', route.params)
console.log('🔍 Mascota ID obtenido:', mascotaId.value)

const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Estados reactivos
const tiposDiagnostico = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const procesando = ref(false)
const cargandoDatos = ref(true) // Nuevo estado para manejar carga general
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)

// Estados para el selector de diferenciales
const mostrarSelectorDiferenciales = ref(false)
const selectorDiferenciales = ref(null)
const diagnosticosSeleccionados = ref([])

// Datos del formulario
const diagnostico = reactive({
  tipo_diagnostico_id: '',
  nombre: '',
  fecha_diagnostico: '',
  estado: '',
  centro_veterinario_id: '',
  diferenciales: '',
  examenes: '',
  conducta: '',
  medio_envio: '',
  observaciones: ''
})

// Obtener ID del usuario dueño de la mascota
const usuarioId = computed(() => {
  return mascotaData.value?.usuario_id || null
})

// Función para obtener nombre del medio seleccionado
const obtenerNombreMedio = (medioId) => {
  const medios = {
    email: 'Email',
    whatsapp: 'WhatsApp',
    telegram: 'Telegram'
  }
  return medios[medioId] || medioId
}

// Obtener nombre del centro seleccionado
const obtenerNombreCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === diagnostico.centro_veterinario_id)
  return centro ? centro.nombre : 'Centro no encontrado'
}

// Obtener dirección del centro seleccionado
const obtenerDireccionCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === diagnostico.centro_veterinario_id)
  return centro ? centro.direccion : ''
}

// Volver atrás si no hay mascotaId
const volverAtras = () => {
  router.back()
}

// Cargar datos de la mascota para obtener el usuario_id
const cargarDatosMascota = async () => {
  try {
    console.log('🔄 Cargando datos de mascota con ID:', mascotaId.value)
    
    // Si no hay mascotaId, no intentar cargar
    if (!mascotaId.value) {
      console.warn('⚠️  No hay mascotaId para cargar datos')
      errorCargandoMascota.value = 'No se pudo identificar la mascota'
      return
    }

    const response = await fetch(`/api/mascotas/${mascotaId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Respuesta completa de mascota:', result)
    
    if (result.success && result.data) {
      mascotaData.value = result.data
      console.log('✅ Datos de mascota cargados:', mascotaData.value)
      console.log('👤 Usuario ID encontrado:', mascotaData.value.usuario_id)
      errorCargandoMascota.value = null
    } else {
      console.warn('❌ No se encontraron datos de mascota:', result)
      mascotaData.value = null
      errorCargandoMascota.value = result.message || 'Error al cargar datos de la mascota'
    }
  } catch (error) {
    console.error('❌ Error cargando datos de mascota:', error)
    mascotaData.value = null
    errorCargandoMascota.value = error.message
  }
}

// Cargar tipos de diagnóstico
const cargarTiposDiagnostico = async () => {
  try {
    const response = await fetch('/api/tipos-diagnostico', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    
    if (result.success && result.data) {
      tiposDiagnostico.value = result.data
      console.log('🏥 Tipos de diagnóstico cargados:', tiposDiagnostico.value.length)
    } else {
      console.warn('No se encontraron tipos de diagnóstico:', result)
      tiposDiagnostico.value = []
    }
  } catch (error) {
    console.error('Error cargando tipos de diagnóstico:', error)
    // No mostrar alerta aquí para no interrumpir el flujo
    tiposDiagnostico.value = []
  }
}

// Cargar centros veterinarios
const cargarCentrosVeterinarios = async () => {
  try {
    const response = await fetch('/api/centros-veterinarios', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    centrosVeterinarios.value = result.data || []
    console.log('🏥 Centros veterinarios cargados:', centrosVeterinarios.value.length)
  } catch (error) {
    console.error('Error cargando centros veterinarios:', error)
    // No mostrar alerta aquí para no interrumpir el flujo
    centrosVeterinarios.value = []
  }
}

const onTipoDiagnosticoChange = () => {
  const tipoSeleccionado = tiposDiagnostico.value.find(t => t.id == diagnostico.tipo_diagnostico_id)
  if (tipoSeleccionado) {
    console.log('Tipo seleccionado:', tipoSeleccionado)
  }
}

// Abrir overlay externo para centros
const abrirOverlayCentros = () => {
  mostrarOverlayCentros.value = true
}

// Seleccionar centro desde overlay
const seleccionarCentro = (centro) => {
  diagnostico.centro_veterinario_id = centro.id
  mostrarOverlayCentros.value = false
}

// Funciones para diagnóstico diferencial
const abrirSelectorDiferenciales = () => {
  mostrarSelectorDiferenciales.value = true
}

const cerrarSelectorDiferenciales = () => {
  mostrarSelectorDiferenciales.value = false
}

const agregarDiagnosticoDiferencial = (diagnosticoSeleccionado) => {
  console.log('🔥 Diagnóstico recibido en agregarDiagnosticoDiferencial:')
  console.log('  - ID:', diagnosticoSeleccionado?.id)
  console.log('  - Nombre:', diagnosticoSeleccionado?.nombre)
  
  if (!diagnosticoSeleccionado || !diagnosticoSeleccionado.id) {
    console.error('❌ Error: diagnóstico no es un objeto válido o no tiene ID')
    return
  }
  
  const existe = diagnosticosSeleccionados.value.some(d => d.id === diagnosticoSeleccionado.id)
  if (!existe) {
    diagnosticosSeleccionados.value.push({
      id: diagnosticoSeleccionado.id,
      nombre: diagnosticoSeleccionado.nombre || 'Sin nombre',
      descripcion: diagnosticoSeleccionado.descripcion || '',
      evolucion: diagnosticoSeleccionado.evolucion || '',
      clasificacion: diagnosticoSeleccionado.clasificacion || '',
    })
    
    console.log('✅ Diagnóstico agregado:', diagnosticosSeleccionados.value)
    actualizarTextareaDiferenciales()
  } else {
    console.log('⚠️  Diagnóstico ya existe en la selección')
  }
}

const confirmarDiagnosticoDiferencial = (diagnosticoSeleccionado) => {
  console.log('✅ Diagnóstico confirmado:', diagnosticoSeleccionado)
  agregarDiagnosticoDiferencial(diagnosticoSeleccionado)
}

const eliminarDiagnosticoDiferencial = (index) => {
  console.log('🗑️  Eliminando diagnóstico en índice:', index)
  diagnosticosSeleccionados.value.splice(index, 1)
  actualizarTextareaDiferenciales()
}

const finalizarSeleccionDiferenciales = () => {
  console.log('🏁 Finalizando selección de diferenciales')
  if (selectorDiferenciales.value && selectorDiferenciales.value.getDiagnosticosSeleccionados) {
    const seleccionActual = selectorDiferenciales.value.getDiagnosticosSeleccionados()
    if (seleccionActual && seleccionActual.length > 0) {
      seleccionActual.forEach(d => agregarDiagnosticoDiferencial(d))
    }
  }
  cerrarSelectorDiferenciales()
}

const actualizarTextareaDiferenciales = () => {
  console.log('📝 Actualizando textarea de diferenciales')
  if (diagnosticosSeleccionados.value.length > 0) {
    diagnostico.diferenciales = diagnosticosSeleccionados.value
      .map(d => `ID: ${d.id} - ${d.nombre}`)
      .join('\n')
  } else {
    diagnostico.diferenciales = ''
  }
  console.log('📋 Contenido actualizado:', diagnostico.diferenciales)
}

const abrirRegistroTipoDiagnostico = () => {
  if (!mascotaId.value) {
    alert('No se puede registrar un nuevo tipo sin identificar la mascota')
    return
  }
  
  router.push({
    path: '/registro/registroTipoDiagnostico',
    query: {
      from: '/registro/diagnostico',
      mascotaId: mascotaId.value
    }
  })
}

// Gestión de archivos
const archivos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null
})))

const inputsArchivo = ref([])

const esImagen = (archivo) => {
  if (!archivo) return false
  return archivo.type.startsWith('image/')
}

const handleArchivo = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    archivos.value[index].archivo = file
    archivos.value[index].preview = esImagen(file) ? URL.createObjectURL(file) : null
  }
}

const activarInput = (index) => {
  inputsArchivo.value[index]?.click()
}

const quitarArchivo = (index) => {
  archivos.value[index].archivo = null
  archivos.value[index].preview = null
}

// Registrar diagnóstico
const registrarDiagnostico = async () => {
  if (procesando.value || cargandoDatos.value) return

  try {
    procesando.value = true

    // Validar campos obligatorios
     if (!diagnostico.tipo_diagnostico_id || !diagnostico.nombre || !diagnostico.fecha_diagnostico || !diagnostico.estado) {
      alert('Por favor complete todos los campos obligatorios')
      procesando.value = false
      return
    }

    console.log('📤 Enviando datos a servidor:', diagnostico)

    const formData = new FormData()
    
    // Preparar datos para enviar
    const datosEnvio = {
      ...diagnostico,
      mascota_id: mascotaId.value,
      diagnosticosDiferenciales: diagnosticosSeleccionados.value.map(d => d.id)
    }

    for (const campo in datosEnvio) {
      if (datosEnvio[campo] !== null && datosEnvio[campo] !== '') {
        formData.append(campo, datosEnvio[campo])
      }
    }

    archivos.value.forEach((archivo, i) => {
      if (archivo.archivo) {
        formData.append(`archivo${i + 1}`, archivo.archivo)
      }
    })
    
    console.log('✅ Datos a enviar:', Object.fromEntries(formData))
    console.log('✅ IDs de diagnósticos diferenciales:', diagnosticosSeleccionados.value.map(d => d.id))

    const response = await fetch(`/api/mascotas/${mascotaId.value}/diagnosticos`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: formData
    })

    console.log('📨 Status:', response.status)
    
    const responseText = await response.text()
    console.log('📄 Respuesta cruda:', responseText)

    if (!responseText.trim()) {
      throw new Error('El servidor devolvió una respuesta vacía')
    }

    let result
    try {
      result = JSON.parse(responseText)
    } catch (parseError) {
      console.error('No se pudo parsear como JSON:', responseText)
      throw new Error('El servidor no devolvió JSON válido. Respuesta: ' + responseText.substring(0, 100))
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      // Obtener el valor actual de mascotaId (no la computed property)
      const currentMascotaId = mascotaId.value
      
      console.log('✅ Diagnóstico registrado, navegando con mascotaId:', currentMascotaId)
      
      // Usar .value para obtener el valor primitivo
      router.push({
        name: 'veterinario-diagnosticos',
        params: { id: currentMascotaId },  // ¡Aquí está el cambio!
        query: {
          from: 'registroDiagnostico',
          currentTab: 'Clinico',
          ts: Date.now()
        }
      })
    } else {
      alert('Error al registrar el diagnóstico: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al registrar el diagnóstico: ' + error.message)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  if (mascotaId.value) {
    const currentMascotaId = mascotaId.value  // Obtener el valor
    
    router.push({
      name: 'veterinario-diagnosticos',
      params: { id: currentMascotaId },  // ¡Aquí también!
      query: {
        from: 'cancelarRegistroDiagnostico',
        currentTab: 'Clinico',
        ts: Date.now()
      }
    })
  } else {
    router.back()
  }
}

// Verificar autenticación y cargar datos
onMounted(async () => {
  console.log('🚀 Iniciando componente RegistrarDiagnostico')
  
  // Verificar si hay mascotaId
  if (!mascotaId.value) {
    console.error('❌ No se pudo obtener el ID de la mascota')
    cargandoDatos.value = false
    return
  }
  
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      router.push('/login')
      return
    }
  }

  try {
    // Cargar datos en paralelo para mayor eficiencia
    const promises = [
      cargarDatosMascota(),
      cargarTiposDiagnostico(),
      cargarCentrosVeterinarios()
    ]

    await Promise.allSettled(promises)
    
    // Establecer fecha actual como predeterminada
    const hoy = new Date().toISOString().split('T')[0]
    diagnostico.fecha_diagnostico = hoy  // Cambiado aquí
    
    console.log('✅ Componente completamente cargado')
    console.log('👤 Usuario ID final:', usuarioId.value)
    console.log('📋 Tipos de diagnóstico cargados:', tiposDiagnostico.value.length)
    console.log('🏥 Centros veterinarios cargados:', centrosVeterinarios.value.length)
  } catch (error) {
    console.error('❌ Error durante la carga inicial:', error)
  } finally {
    cargandoDatos.value = false
  }
})
</script>
<!-- registrarTerapia.vue CON MANEJO DE ERRORES MEJORADO -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <!-- TÍTULO DINÁMICO PARA EDICIÓN/REGISTRO -->
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Terapia' : 'Registrar Terapia' }}</h1>

    <form @submit.prevent="procesarFormulario" class="space-y-4">
      <!-- DATOS OBLIGATORIOS -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Obligatorios</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna izquierda -->
        <div class="space-y-4">
          <!-- Selección de Tipo de Terapia -->
          <div>
            <label class="block font-medium">Tipo de terapia aplicada</label>
            <div class="flex gap-2">
              <select
                v-model="terapia.tipo_terapia_id"
                required
                class="w-full border rounded p-2"
                @change="onTipoTerapiaChange"
              >
                <option value="">Seleccione un tipo de terapia</option>
                <option
                  v-for="tipo in tiposTerapia"
                  :key="tipo.id"
                  :value="tipo.id"
                >
                  {{ tipo.nombre }}
                </option>
              </select>

              <!-- Botón de + Tipo -->
              <button
                type="button"
                @click="abrirRegistroTipoTerapia"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo
              </button>
            </div>
          </div>

          <div>
            <label class="block font-medium">Fecha de inicio de la terapia</label>
            <input v-model="terapia.fecha_inicio" type="date" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Frecuencia de las sesiones</label>
            <select v-model="terapia.frecuencia" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="diaria">Diaria</option>
              <option value="semanal">Semanal</option>
              <option value="quincenal">Quincenal</option>
              <option value="mensual">Mensual</option>
              <option value="personalizada">Personalizada</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Duración estimada del tratamiento</label>
            <input v-model="terapia.duracion_tratamiento" type="text" required class="w-full border rounded p-2" placeholder="Ej: 3 meses, 10 sesiones, etc." />
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">
              Centro Veterinario donde se realizó
            </label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="terapia.centro_veterinario_id"
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
              >
                + Centro
              </button>
            </div>
          </div>
        </div>

        <!-- Columna derecha - Archivos -->
        <div>
          <label class="block font-medium mb-2">Archivos adjuntos</label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div
              v-for="(archivo, index) in archivos"
              :key="index"
              class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-full aspect-square"
              @click="!archivo.preview && !esEdicion && activarInput(index)"
            >
              <!-- Solo mostrar botón de eliminar si no estamos en modo edición o si hay preview -->
              <button 
                type="button" 
                @click.stop="quitarArchivo(index)" 
                v-if="archivo.preview && !esEdicion" 
                class="absolute top-1 right-1 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700 mt-35 -mr-2"
              >
                <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-3xl" />
              </button>

              <!-- Mostrar archivo existente en modo edición -->
              <div v-if="esEdicion && archivo.existente" class="h-full flex flex-col items-center justify-center p-2">
                <font-awesome-icon :icon="['fas', 'file']" class="text-5xl text-gray-500 mb-2" />
                <div class="text-xs truncate px-1">{{ archivo.nombre }}</div>
                <div class="text-xs text-gray-500 mt-1">Archivo existente</div>
              </div>

              <!-- Mostrar preview de archivo nuevo -->
              <div v-else-if="archivo.preview" class="h-full flex flex-col">
                <img v-if="esImagen(archivo.archivo)" :src="archivo.preview" alt="Preview" class="w-full h-full object-cover rounded-md border-gray-300 mx-auto flex-grow" />
                <div v-else class="h-full flex items-center justify-center p-2">
                  <font-awesome-icon :icon="['fas', 'file']" class="text-5xl text-gray-500" />
                </div>
                <div class="text-xs truncate px-1">{{ archivo.archivo.name }}</div>
              </div>

              <!-- Input para agregar archivo (solo en registro o para agregar nuevos en edición) -->
              <div v-else class="text-green-400 mt-14">
                <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-4xl mb-2" />
                <div class="text-gray-400">Agregar archivo</div>
              </div>

              <!-- Input file solo si no es modo edición o para agregar nuevos -->
              <input 
                v-if="!esEdicion || !archivo.existente"
                :ref="el => inputsArchivo[index] = el" 
                type="file" 
                @change="handleArchivo($event, index)" 
                class="hidden" 
                accept="image/*,.pdf,.doc,.docx" 
              />
            </div>
          </div>
          <p v-if="esEdicion" class="text-sm text-gray-500 mt-2">
            Nota: En modo edición no se pueden modificar los archivos existentes. Para cambiar archivos, elimine y registre una nueva terapia.
          </p>
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
          <label class="block font-medium">Fecha de finalización</label>
          <input v-model="terapia.fecha_fin" type="date" class="w-full border rounded p-2" :min="terapia.fecha_inicio" />
        </div>

        <div>
          <label class="block font-medium">Evolución observada</label>
          <select v-model="terapia.evolucion" class="w-full border rounded p-2">
            <option value="">Seleccione una opción</option>
            <option value="mejoria">Mejoría</option>
            <option value="estable">Estable</option>
            <option value="empeoramiento">Empeoramiento</option>
          </select>
        </div>

        <div>
          <label class="block font-medium mb-1">Observaciones adicionales</label>
          <textarea v-model="terapia.observaciones" rows="4" maxlength="500" class="w-full border rounded p-2 resize-none" placeholder="Observaciones generales sobre la terapia"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ terapia.observaciones.length }}/500 caracteres</p>
        </div>

        <div>
          <label class="block font-medium mb-1">Recomendaciones para el tutor</label>
          <textarea v-model="terapia.recomendaciones_tutor" rows="4" maxlength="500" class="w-full border rounded p-2 resize-none" placeholder="Instrucciones específicas para el dueño"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ terapia.recomendaciones_tutor.length }}/500 caracteres</p>
        </div>
      </div>

      <!-- Selección del medio de envío -->
      <div class="mt-8">
        <CarruselMedioEnvio 
          v-if="usuarioId" 
          :usuario-id="usuarioId" 
          :modo-edicion="esEdicion"
          :medio-seleccionado-inicial="terapia.medio_envio"
          @update:medio="terapia.medio_envio = $event" 
        />
        
        <div v-else class="text-center py-4">
          <p class="text-gray-500">Cargando información del dueño...</p>
        </div>

        <div v-if="terapia.medio_envio" class="mt-4 text-center text-gray-700">
          <span class="font-semibold">
            {{ esEdicion ? 'Medio de envío utilizado:' : 'Medio seleccionado:' }}
          </span>
          <span class="ml-1 text-blue-600 font-medium">
            {{ obtenerNombreMedio(terapia.medio_envio) }}
          </span>
          <p v-if="esEdicion" class="text-sm text-gray-500 mt-1">
            (En modo edición el medio de envío no se puede cambiar)
          </p>
        </div>
      </div>

      <!-- Sección de errores de validación (IGUAL QUE EN VACUNA) -->
      <div v-if="mostrarErrores && Object.keys(erroresValidacion).length > 0" 
          class="mt-6 p-4 bg-red-50 border-l-4 border-red-500">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">
              Problemas de validación
            </h3>
            <div class="mt-2 text-sm text-red-700">
              <ul class="list-disc pl-5 space-y-1">
                <li v-for="(erroresCampo, campo) in erroresValidacion" :key="campo">
                  <template v-if="campo !== '_debug'">
                    <span v-for="error in erroresCampo" :key="error" class="block">
                      {{ error }}
                    </span>
                  </template>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button
          type="button"
          @click="cancelar"
          class="bg-gray-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-gray-700 transition-colors"
        >
          Cancelar
        </button>
        <button
          type="button"
          @click="mostrarModalConfirmacion"
          :disabled="procesando || !formularioValido"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
        >
          {{ procesando ? 'Procesando...' : (esEdicion ? 'Actualizar Terapia' : 'Registrar Terapia') }}
        </button>
      </div>
    </form>

    <!-- Componente externo del overlay -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="terapia.centro_veterinario_id"
      @cerrar="mostrarOverlayCentros = false"
      @seleccionar="seleccionarCentro"
    />

    <!-- MODAL DE CONFIRMACION -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 max-h-[80vh] overflow-y-auto">
        <!-- Título dinámico del modal -->
        <h3 class="text-xl font-bold mb-4">
          {{ esEdicion ? 'Confirmar Actualización' : 'Confirmar Registro' }} de Terapia
        </h3>
        
        <div class="mb-6 space-y-3">
          <p class="text-gray-700">
            <span class="font-semibold">Tipo de terapia:</span> {{ obtenerNombreTipoTerapia() }}
          </p>
          <p class="text-gray-700">
            <span class="font-semibold">Fecha inicio:</span> {{ formatFecha(terapia.fecha_inicio) }}
          </p>
          <p class="text-gray-700">
            <span class="font-semibold">Frecuencia:</span> {{ obtenerNombreFrecuencia(terapia.frecuencia) }}
          </p>
          <p class="text-gray-700">
            <span class="font-semibold">Duración:</span> {{ terapia.duracion_tratamiento }}
          </p>
          
          <p v-if="terapia.centro_veterinario_id" class="text-gray-700">
            <span class="font-semibold">Centro veterinario:</span> {{ obtenerNombreCentroSeleccionado() }}
          </p>
          
          <p v-if="terapia.fecha_fin" class="text-gray-700">
            <span class="font-semibold">Fecha finalización:</span> {{ formatFecha(terapia.fecha_fin) }}
          </p>
          
          <p v-if="terapia.evolucion" class="text-gray-700">
            <span class="font-semibold">Evolución:</span> {{ obtenerNombreEvolucion(terapia.evolucion) }}
          </p>
          
          <p v-if="terapia.observaciones" class="text-gray-700">
            <span class="font-semibold">Observaciones:</span> 
            <span class="block text-sm mt-1 bg-gray-50 p-2 rounded">{{ terapia.observaciones.substring(0, 100) }}{{ terapia.observaciones.length > 100 ? '...' : '' }}</span>
          </p>
          
          <p v-if="terapia.recomendaciones_tutor" class="text-gray-700">
            <span class="font-semibold">Recomendaciones:</span>
            <span class="block text-sm mt-1 bg-gray-50 p-2 rounded">{{ terapia.recomendaciones_tutor.substring(0, 100) }}{{ terapia.recomendaciones_tutor.length > 100 ? '...' : '' }}</span>
          </p>
          
          <p v-if="terapia.medio_envio" class="text-gray-700">
            <span class="font-semibold">Medio de envío:</span> {{ obtenerNombreMedio(terapia.medio_envio) }}
          </p>
          
          <!-- Archivos en modo edición -->
          <div v-if="esEdicion && archivosExistente.length > 0" class="text-gray-700">
            <span class="font-semibold">Archivos existentes:</span>
            <ul class="text-sm mt-1 list-disc list-inside">
              <li v-for="(archivo, index) in archivosExistente" :key="index">
                {{ archivo.nombre }}
              </li>
            </ul>
            <p class="text-xs text-gray-500 mt-1">(Los archivos existentes se mantendrán)</p>
          </div>
          
          <!-- Archivos nuevos -->
          <div v-if="archivosAdjuntos.length > 0" class="text-gray-700">
            <span class="font-semibold">{{ esEdicion ? 'Nuevos archivos a agregar:' : 'Archivos adjuntos:' }}</span>
            <ul class="text-sm mt-1 list-disc list-inside">
              <li v-for="(archivo, index) in archivosAdjuntos" :key="index">
                {{ archivo.archivo.name }} ({{ formatFileSize(archivo.archivo.size) }})
              </li>
            </ul>
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <button
            @click="cerrarModal"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Cancelar
          </button>
          <button
            @click="confirmarAccion"
            :disabled="procesando"
            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors disabled:bg-blue-300"
          >
            {{ procesando ? 'Procesando...' : (esEdicion ? 'Actualizar' : 'Registrar') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import SeleccionCentroVeterinario from '@/components/ElementosGraficos/SeleccionCentroVeterinario.vue'
import CarruselMedioEnvio from '@/components/ElementosGraficos/CarruselMedioEnvio.vue'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const props = defineProps({
  terapiaId: {
    type: String,
    default: null
  }
})

// Determinar si es edición o registro
const esEdicion = computed(() => {
  return route.name === 'editarTerapia' || !!route.params.terapiaId
})

const terapiaId = computed(() => {
  return props.terapiaId || route.params.terapiaId || null
})

const mascotaId = computed(() => {
  return route.query.mascotaId || route.params.mascotaId || null
})

console.log('🔍 Route params:', route.params)
console.log('🔍 Route query:', route.query)
console.log('🔍 Es edición:', esEdicion.value)
console.log('🔍 Terapia ID:', terapiaId.value)
console.log('🔍 Mascota ID:', mascotaId.value)

// Estados reactivos
const tiposTerapia = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)
const mostrarModal = ref(false)

// AGREGADO: Variables para manejo de errores (IGUAL QUE EN VACUNA)
const erroresValidacion = ref({})
const mostrarErrores = ref(false)

// Datos del formulario
const terapia = reactive({
  tipo_terapia_id: '',
  fecha_inicio: '',
  frecuencia: '',
  duracion_tratamiento: '',
  centro_veterinario_id: null,
  costo: null,
  fecha_fin: '',
  evolucion: '',
  observaciones: '',  // Asegurar que sea string vacío, no null
  recomendaciones_tutor: '',  // Asegurar que sea string vacío, no null
  medio_envio: ''
})

// Archivos adjuntos (estructura modificada para soportar edición)
const archivos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null,
  existente: false,
  nombre: '',
  id: null
})))
const inputsArchivo = ref([])

// Computed para validación del formulario
const formularioValido = computed(() => {
  const camposObligatorios = terapia.tipo_terapia_id && 
    terapia.fecha_inicio && 
    terapia.frecuencia && 
    terapia.duracion_tratamiento
  
  // Para registro, el medio de envío es obligatorio
  if (!esEdicion.value) {
    return camposObligatorios && terapia.medio_envio
  }
  
  // Para edición, solo los campos básicos son obligatorios
  return camposObligatorios
})

// Obtener archivos adjuntos (nuevos)
const archivosAdjuntos = computed(() => {
  return archivos.value.filter(a => a.archivo !== null && !a.existente)
})

// Obtener archivos existentes (en modo edición)
const archivosExistente = computed(() => {
  return archivos.value.filter(a => a.existente)
})

// Obtener ID del usuario dueño de la mascota
const usuarioId = computed(() => {
  return mascotaData.value?.usuario_id || null
})

// AGREGADO: Función para manejar errores de validación (IGUAL QUE EN VACUNA)
const mostrarErrorValidacion = (error) => {
  mostrarErrores.value = true
  // Crear un array temporal para los errores
  const erroresArray = []
  
  // Verificar si es un error del servidor con estructura de validación
  if (error.response?.status === 422 && error.response.data?.errors) {
    erroresValidacion.value = error.response.data.errors
    
    // Construir mensaje amigable
    for (const campo in error.response.data.errors) {
      const mensajes = error.response.data.errors[campo]
      mensajes.forEach(mensaje => {
        erroresArray.push(`• ${mensaje}`)
      })
    }
  } else if (error.message) {
    // Si es un error genérico
    erroresArray.push(`• ${error.message}`)
  } else {
    erroresArray.push('• Ocurrió un error desconocido')
  }
  
  // Mostrar alerta con mejor formato
  const mensajeFinal = erroresArray.join('\n')
  alert(`❌ Error de validación:\n\n${mensajeFinal}`)
}

// Función para obtener nombre del medio seleccionado
const obtenerNombreMedio = (medioId) => {
  const medios = {
    email: 'Email',
    whatsapp: 'WhatsApp',
    telegram: 'Telegram'
  }
  return medios[medioId] || medioId
}

// Función para obtener nombre del tipo de terapia
const obtenerNombreTipoTerapia = () => {
  const tipo = tiposTerapia.value.find(t => t.id == terapia.tipo_terapia_id)
  return tipo ? tipo.nombre : 'No seleccionado'
}

// Función para obtener nombre de la frecuencia
const obtenerNombreFrecuencia = (frecuencia) => {
  const frecuencias = {
    diaria: 'Diaria',
    semanal: 'Semanal',
    quincenal: 'Quincenal',
    mensual: 'Mensual',
    personalizada: 'Personalizada'
  }
  return frecuencias[frecuencia] || frecuencia
}

// Función para obtener nombre de la evolución
const obtenerNombreEvolucion = (evolucion) => {
  const evoluciones = {
    mejoria: 'Mejoría',
    estable: 'Estable',
    empeoramiento: 'Empeoramiento'
  }
  return evoluciones[evolucion] || evolucion
}

// Formatear fecha
const formatFecha = (fecha) => {
  if (!fecha) return 'No especificada'
  return new Date(fecha).toLocaleDateString('es-ES')
}

// Formatear tamaño de archivo
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Obtener nombre del centro seleccionado
const obtenerNombreCentroSeleccionado = () => {
  if (!terapia.centro_veterinario_id) return 'No seleccionado'
  
  const centro = centrosVeterinarios.value.find(c => c.id === terapia.centro_veterinario_id)
  return centro ? centro.nombre : 'Centro no encontrado'
}

// Obtener dirección del centro seleccionado
const obtenerDireccionCentroSeleccionado = () => {
  if (!terapia.centro_veterinario_id) return ''
  
  const centro = centrosVeterinarios.value.find(c => c.id === terapia.centro_veterinario_id)
  return centro ? centro.direccion : ''
}

// Cargar datos de la mascota
const cargarDatosMascota = async () => {
  try {
    console.log('🔄 Cargando datos de mascota con ID:', mascotaId.value)
    
    if (!mascotaId.value) {
      console.warn('⚠️ No hay mascotaId para cargar datos')
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
    
    if (result.success && result.data) {
      mascotaData.value = result.data
      console.log('✅ Datos de mascota cargados')
      errorCargandoMascota.value = null
    } else {
      mascotaData.value = null
      errorCargandoMascota.value = result.message || 'Error al cargar datos de la mascota'
    }
  } catch (error) {
    console.error('❌ Error cargando datos de mascota:', error)
    mascotaData.value = null
    errorCargandoMascota.value = error.message
  }
}

// Cargar tipos de terapia
const cargarTiposTerapia = async () => {
  try {
    const response = await fetch('/api/tipos-terapia', {
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
      tiposTerapia.value = result.data
      console.log('💉 Tipos de terapia cargados:', tiposTerapia.value.length)
    } else {
      console.warn('No se encontraron tipos de terapia:', result)
      tiposTerapia.value = []
    }
  } catch (error) {
    console.error('Error cargando tipos de terapia:', error)
    alert('Error al cargar los tipos de terapia: ' + error.message)
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
    alert('Error al cargar los centros veterinarios')
  }
}

const cargarTerapiaExistente = async () => {
  if (!terapiaId.value) return
  
  try {
    console.log('🔄 Cargando datos de terapia con ID:', terapiaId.value)
    
    const response = await fetch(`/api/terapias/${terapiaId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Respuesta de terapia:', result)
    
    if (result.success && result.data) {
      const datos = result.data
      
      // Actualizar el objeto terapia asegurando valores por defecto
      terapia.tipo_terapia_id = datos.tipo_terapia_id || ''
      terapia.fecha_inicio = datos.fecha_inicio?.split('T')[0] || ''
      terapia.frecuencia = datos.frecuencia || ''
      terapia.duracion_tratamiento = datos.duracion_tratamiento || ''
      terapia.centro_veterinario_id = datos.centro_veterinario_id || null
      terapia.costo = datos.costo || null
      terapia.fecha_fin = datos.fecha_fin?.split('T')[0] || ''
      terapia.evolucion = datos.evolucion || ''
      terapia.observaciones = datos.observaciones || datos.proceso_medico?.observaciones || ''  // Siempre string
      terapia.recomendaciones_tutor = datos.recomendaciones_tutor || ''  // Siempre string
      terapia.medio_envio = datos.medio_envio || ''
      
      console.log('✅ Datos cargados en formulario:', { ...terapia })
      
    } else {
      console.warn('❌ No se encontraron datos de terapia:', result)
      alert('No se pudo cargar la terapia a editar')
    }
  } catch (error) {
    console.error('❌ Error cargando datos de terapia:', error)
    alert('Error al cargar la terapia: ' + error.message)
  }
}

const onTipoTerapiaChange = () => {
  const tipoSeleccionado = tiposTerapia.value.find(t => t.id == terapia.tipo_terapia_id)
  if (tipoSeleccionado) {
    console.log('Tipo seleccionado:', tipoSeleccionado)
  }
}

// Abrir overlay externo
const abrirOverlayCentros = () => {
  mostrarOverlayCentros.value = true
}

// Seleccionar centro desde overlay
const seleccionarCentro = (centro) => {
  terapia.centro_veterinario_id = centro.id
  mostrarOverlayCentros.value = false
}

// Navegar al registro de nuevo tipo
const abrirRegistroTipoTerapia = () => {
  const query = {
    from: esEdicion.value ? `/editar/terapia/${terapiaId.value}` : `/registro/terapia/${mascotaId.value}`,
    mascotaId: mascotaId.value
  }
  
  router.push({
    path: '/registro/registroTipoTerapia',
    query
  })
}

// Funciones para manejar archivos
const esImagen = (archivo) => {
  if (!archivo) return false
  return archivo.type.startsWith('image/')
}

const handleArchivo = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    // Validar tamaño del archivo (máximo 5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert('El archivo no debe superar los 5MB')
      return
    }
    
    // Solo permitir reemplazar si no es un archivo existente
    if (!archivos.value[index].existente) {
      archivos.value[index].archivo = file
      archivos.value[index].preview = esImagen(file) ? URL.createObjectURL(file) : null
      archivos.value[index].existente = false
    } else {
      alert('No se puede reemplazar un archivo existente en modo edición')
    }
  }
}

const activarInput = (index) => {
  if (!esEdicion.value || !archivos.value[index].existente) {
    inputsArchivo.value[index]?.click()
  }
}

const quitarArchivo = (index) => {
  // En modo edición, no permitir eliminar archivos existentes
  if (esEdicion.value && archivos.value[index].existente) {
    alert('No se pueden eliminar archivos existentes en modo edición')
    return
  }
  
  if (archivos.value[index].preview) {
    URL.revokeObjectURL(archivos.value[index].preview)
  }
  archivos.value[index].archivo = null
  archivos.value[index].preview = null
  archivos.value[index].existente = false
  archivos.value[index].nombre = ''
}

// Mostrar modal de confirmación
const mostrarModalConfirmacion = () => {
  if (!formularioValido.value) {
    alert('Por favor complete todos los campos obligatorios')
    return
  }
  
  mostrarModal.value = true
}

// Cerrar modal
const cerrarModal = () => {
  mostrarModal.value = false
}

// Confirmar acción (registrar o actualizar)
const confirmarAccion = () => {
  if (esEdicion.value) {
    actualizarTerapia()
  } else {
    registrarTerapia()
  }
}

// Procesar formulario (ahora solo muestra el modal)
const procesarFormulario = () => {
  mostrarModalConfirmacion()
}

// MODIFICADO: Registrar terapia con manejo de errores mejorado (SIMILAR A VACUNA)
const registrarTerapia = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()

    // Limpiar errores previos
    erroresValidacion.value = {}
    mostrarErrores.value = false

    const formData = new FormData()
    
    // Agregar datos de la terapia
    for (const campo in terapia) {
      if (terapia[campo] !== null && terapia[campo] !== undefined && terapia[campo] !== '') {
        formData.append(campo, terapia[campo])
      }
    }

    // Agregar archivos adjuntos
    archivos.value.forEach((archivo, index) => {
      if (archivo.archivo) {
        formData.append(`archivos[${index}]`, archivo.archivo)
      }
    })

    console.log('📤 Enviando datos de terapia a servidor para REGISTRO:', {
      mascotaId: mascotaId.value,
      terapia: { ...terapia },
      archivosCount: archivos.value.filter(a => a.archivo).length
    })

    const response = await fetch(`/api/mascotas/${mascotaId.value}/terapias`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: formData
    })

    const responseText = await response.text()
    console.log('📨 Status:', response.status, 'Respuesta:', responseText)

    let result
    try {
      result = JSON.parse(responseText)
    } catch (parseError) {
      console.error('No se pudo parsear como JSON:', responseText)
      throw new Error('El servidor no devolvió JSON válido')
    }

    // Manejar específicamente el error 422 (Validación)
    if (response.status === 422) {
      mostrarErrorValidacion({ response: { status: 422, data: result } })
      return
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      // Limpiar URLs de previsualización
      archivos.value.forEach(archivo => {
        if (archivo.preview) {
          URL.revokeObjectURL(archivo.preview)
        }
      })
      
      // Mostrar mensaje de éxito incluyendo información del envío si existe
      let mensajeExito = '✅ Terapia registrada exitosamente'
      if (result.data?.envio_exitoso === true) {
        mensajeExito += ' y certificado enviado'
      } else if (result.data?.envio_exitoso === false) {
        mensajeExito += ' (pero hubo un problema al enviar el certificado)'
      }
      
      alert(mensajeExito)
      
      // Redirigir al historial de terapias
      router.push({
        name: 'veterinario-terapias',
        params: { id: mascotaId.value },
        query: {
          from: 'registroTerapia',
          currentTab: 'Terapias',
          ts: Date.now()
        }
      })
    } else {
      mostrarErrorValidacion({ message: result.message || 'Error al registrar la terapia' })
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    mostrarErrorValidacion(error)
  } finally {
    procesando.value = false
  }
}

// MODIFICADO: Actualizar terapia con manejo de errores mejorado
const actualizarTerapia = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()

    // Limpiar errores previos
    erroresValidacion.value = {}
    mostrarErrores.value = false

    // Preparar payload como JSON (no FormData)
    const payload = {
      tipo_terapia_id: terapia.tipo_terapia_id,
      fecha_inicio: terapia.fecha_inicio,
      frecuencia: terapia.frecuencia,
      duracion_tratamiento: terapia.duracion_tratamiento,
      centro_veterinario_id: terapia.centro_veterinario_id,
      costo: terapia.costo,
      fecha_fin: terapia.fecha_fin,
      evolucion: terapia.evolucion,
      observaciones: terapia.observaciones,
      recomendaciones_tutor: terapia.recomendaciones_tutor,
      medio_envio: terapia.medio_envio,
    }

    console.log('📤 Actualizando terapia con ID:', terapiaId.value)
    console.log('📤 Datos a enviar:', payload)

    const response = await fetch(`/api/terapias/${terapiaId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(payload)
    })

    const responseText = await response.text()
    console.log('📨 Status:', response.status, 'Respuesta:', responseText)

    let result
    try {
      result = JSON.parse(responseText)
    } catch (parseError) {
      console.error('No se pudo parsear como JSON:', responseText)
      throw new Error('El servidor no devolvió JSON válido')
    }

    // Manejar específicamente el error 422 (Validación)
    if (response.status === 422) {
      mostrarErrorValidacion({ response: { status: 422, data: result } })
      return
    }

    if (!response.ok) {
      const errorMessage = result.message || 
                          (result.errors ? Object.values(result.errors).flat().join(', ') : 'Error en la operación')
      throw new Error(errorMessage)
    }

    if (result.success) {
      // Limpiar URLs de previsualización de archivos nuevos
      archivos.value.forEach(archivo => {
        if (archivo.preview) {
          URL.revokeObjectURL(archivo.preview)
        }
      })
      
      alert('✅ Terapia actualizada exitosamente')
      
      const mascotaIdParaRedireccion = mascotaId.value || result.data?.proceso_medico?.mascota_id
      
      if (mascotaIdParaRedireccion) {
        router.push({
          name: 'veterinario-terapias',
          params: { id: mascotaIdParaRedireccion },
          query: {
            from: 'editarTerapia',
            currentTab: 'Terapias',
            ts: Date.now()
          }
        })
      } else {
        router.push({ name: 'veterinario-terapias', params: { id: '0' } })
      }
    } else {
      mostrarErrorValidacion({ message: result.message || 'Error al actualizar la terapia' })
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    mostrarErrorValidacion(error)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  // Limpiar URLs de previsualización
  archivos.value.forEach(archivo => {
    if (archivo.preview) {
      URL.revokeObjectURL(archivo.preview)
    }
  })
  
  const mascotaIdParaRedireccion = mascotaId.value
  
  if (mascotaIdParaRedireccion) {
    router.push({
      name: 'veterinario-terapias',
      params: { id: mascotaIdParaRedireccion },
      query: {
        from: esEdicion.value ? 'cancelarEditarTerapia' : 'cancelarRegistroTerapia',
        currentTab: 'Terapias',
        ts: Date.now()
      }
    })
  } else {
    router.push({ name: 'veterinario-terapias', params: { id: '0' } })
  }
}

// Verificar autenticación y cargar datos
onMounted(async () => {
  console.log('🚀 Iniciando componente RegistrarTerapia')
  
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      router.push('/login')
      return
    }
  }

  // Si es edición, cargar datos de la terapia primero
  if (esEdicion.value) {
    await cargarTerapiaExistente()
  }

  // Cargar datos en orden
  if (mascotaId.value) {
    await cargarDatosMascota()
    
    if (errorCargandoMascota.value) {
      console.error('❌ Error al cargar mascota:', errorCargandoMascota.value)
      alert('Error al cargar datos de la mascota: ' + errorCargandoMascota.value)
      return
    }
  }

  await Promise.all([
    cargarTiposTerapia(),
    cargarCentrosVeterinarios()
  ])

  // Establecer fecha actual como predeterminada solo si es registro nuevo y no hay fecha
  if (!esEdicion.value && !terapia.fecha_inicio) {
    const hoy = new Date().toISOString().split('T')[0]
    terapia.fecha_inicio = hoy
  }
  
  console.log('✅ Componente completamente cargado')
})
</script>
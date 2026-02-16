<!-- registrarFarmaco.vue - Versión con manejo de errores mejorado -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Administración de Fármaco' : 'Registrar Administración de Fármaco' }}</h1>

    <!-- Sección de errores de validación -->
    <div v-if="mostrarErrores && Object.keys(erroresValidacion).length > 0" 
        class="mt-4 mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r">
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
          <!-- Selección de Tipo de Fármaco -->
          <div>
            <label class="block font-medium mb-1">Tipo de fármaco</label>
            <div class="flex gap-2">
              <select
                v-model="farmaco.tipo_farmaco_id"
                required
                class="w-full border rounded p-2"
                @change="onTipoFarmacoChange"
                :class="{ 'border-red-500': tieneError('tipo_farmaco_id') }"
              >
                <option value="">Seleccione un tipo de fármaco</option>
                <option
                  v-for="tipo in tiposFarmaco"
                  :key="tipo.id"
                  :value="tipo.id"
                >
                  {{ tipo.nombre_comercial }} ({{ tipo.nombre_generico }})
                </option>
              </select>

              <!-- Botón de + Tipo -->
              <button 
                type="button"
                @click="abrirRegistroTipoFarmaco"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo 
              </button>
            </div>
            <div v-if="tieneError('tipo_farmaco_id')" class="text-red-500 text-sm mt-1">
              {{ obtenerErrorCampo('tipo_farmaco_id') }}
            </div>
          </div>

          <div>
            <label class="block font-medium">Fecha de administración</label>
            <input 
              v-model="farmaco.fecha_administracion" 
              type="datetime-local" 
              required 
              class="w-full border rounded p-2"
              :class="{ 'border-red-500': tieneError('fecha_administracion') }"
            />
            <div v-if="tieneError('fecha_administracion')" class="text-red-500 text-sm mt-1">
              {{ obtenerErrorCampo('fecha_administracion') }}
            </div>
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">
              Centro Veterinario donde se realizó
            </label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="farmaco.centro_veterinario_id"
                class="w-full border rounded p-2 bg-gray-50"
                :class="{ 'border-red-500': tieneError('centro_veterinario_id') }"
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
                :class="{ 'border-red-500': tieneError('centro_veterinario_id') }"
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
            <div v-if="tieneError('centro_veterinario_id')" class="text-red-500 text-sm mt-1">
              {{ obtenerErrorCampo('centro_veterinario_id') }}
            </div>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Frecuencia de administración</label>
            <input 
              v-model="farmaco.frecuencia" 
              type="text" 
              required 
              class="w-full border rounded p-2"
              :class="{ 'border-red-500': tieneError('frecuencia') }"
              placeholder="Ej: Cada 8 h, 1 vez al día, etc." 
            />
            <div v-if="tieneError('frecuencia')" class="text-red-500 text-sm mt-1">
              {{ obtenerErrorCampo('frecuencia') }}
            </div>
          </div>

          <div>
            <label class="block font-medium">Duración del tratamiento</label>
            <input 
              v-model="farmaco.duracion" 
              type="text" 
              required 
              class="w-full border rounded p-2"
              :class="{ 'border-red-500': tieneError('duracion') }"
              placeholder="Ej: 7 días, 2 semanas, etc." 
            />
            <div v-if="tieneError('duracion')" class="text-red-500 text-sm mt-1">
              {{ obtenerErrorCampo('duracion') }}
            </div>
          </div>

          <div>
            <label class="block font-medium">Dosis administrada</label>
            <div class="flex">
              <input 
                v-model="farmaco.dosis" 
                type="text" 
                required 
                class="w-3/4 border rounded-l p-2"
                :class="{ 'border-red-500': tieneError('dosis') }"
                placeholder="Cantidad" 
              />
              <select 
                v-model="farmaco.unidad" 
                required 
                class="w-1/4 border rounded-r p-2"
                :class="{ 'border-red-500': tieneError('unidad') }"
              >
                <option value="mg">mg</option>
                <option value="ml">ml</option>
                <option value="UI">UI</option>
                <option value="comp">comp.</option>
                <option value="gotas">gotas</option>
              </select>
            </div>
            <div v-if="tieneError('dosis') || tieneError('unidad')" class="text-red-500 text-sm mt-1">
              {{ obtenerErrorCampo('dosis') || obtenerErrorCampo('unidad') }}
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
          <label class="block font-medium">Fecha próxima dosis (si aplica)</label>
          <input 
            v-model="farmaco.proxima_dosis" 
            type="datetime-local" 
            class="w-full border rounded p-2"
            :class="{ 'border-red-500': tieneError('proxima_dosis') }"
          />
          <div v-if="tieneError('proxima_dosis')" class="text-red-500 text-sm mt-1">
            {{ obtenerErrorCampo('proxima_dosis') }}
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Reacciones adversas observadas</label>
          <textarea 
            v-model="farmaco.reacciones" 
            rows="3" 
            class="w-full border rounded p-2 resize-none"
            :class="{ 'border-red-500': tieneError('reacciones') }"
            placeholder="Describa cualquier efecto no deseado"
          ></textarea>
          <div v-if="tieneError('reacciones')" class="text-red-500 text-sm mt-1">
            {{ obtenerErrorCampo('reacciones') }}
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Recomendaciones para el tutor</label>
          <textarea 
            v-model="farmaco.recomendaciones" 
            rows="3" 
            class="w-full border rounded p-2 resize-none"
            :class="{ 'border-red-500': tieneError('recomendaciones') }"
          ></textarea>
          <div v-if="tieneError('recomendaciones')" class="text-red-500 text-sm mt-1">
            {{ obtenerErrorCampo('recomendaciones') }}
          </div>
        </div>

       <!-- Archivos adjuntos -->
        <div class="col-span-full">
          <label class="block font-medium mb-2">Archivos adjuntos</label>
          <div class="flex flex-wrap gap-x-2 gap-y-2">
            <div
              v-for="(archivo, index) in archivos"
              :key="index"
              class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-20 w-20"
              @click="!archivo.preview && !esEdicion && activarInput(index)"
              :class="{ 
                'opacity-50 cursor-not-allowed': esEdicion,
                'border-red-500': tieneError('archivos')
              }"
            >
              <!-- Botón eliminar -->
              <button
                type="button"
                @click.stop="quitarArchivo(index)"
                v-if="archivo.preview && !esEdicion"
                class="absolute top-0.5 right-0.5 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700"
              >
                <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-lg" />
              </button>

              <!-- Input oculto -->
              <input
                :ref="el => inputsArchivo[index] = el"
                type="file"
                @change="handleArchivo($event, index)"
                class="hidden"
                :disabled="esEdicion"
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
              <div v-else-if="!esEdicion" class="text-green-400 flex flex-col justify-center items-center h-full">
                <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-2xl mb-0.5" />
                <div class="text-[10px] text-gray-400">Agregar</div>
              </div>

              <!-- Indicador para archivos cargados en edición -->
              <div v-else-if="esEdicion && archivo.archivo" class="h-full flex flex-col items-center justify-center p-1">
                <font-awesome-icon :icon="['fas', 'file']" class="text-3xl text-gray-500 mb-1" />
                <div class="text-[10px] text-gray-600 text-center px-1">Archivo existente</div>
                <div class="text-[8px] text-gray-400 truncate w-full px-1">{{ archivo.archivo.name || 'Archivo' }}</div>
              </div>

              <!-- Espacio vacío en edición -->
              <div v-else class="h-full flex items-center justify-center text-gray-300">
                <font-awesome-icon :icon="['fas', 'square']" class="text-2xl" />
              </div>
            </div>
          </div>
          <p v-if="tieneError('archivos')" class="text-red-500 text-sm mt-1">
            {{ obtenerErrorCampo('archivos') }}
          </p>
          <p class="text-xs text-gray-500 mt-1">Puede adjuntar recetas, imágenes del medicamento, informes, etc.</p>
          <p v-if="esEdicion" class="text-sm text-gray-500 mt-1 italic">
            Nota: En modo edición no se pueden modificar los archivos adjuntos existentes.
          </p>
        </div>
      </div>

      <!-- Selección del medio de envío -->
      <div class="mt-8">
        <!-- Para ambos casos (registro y edición) mostramos el carrusel -->
        <div v-if="usuarioId">
          <CarruselMedioEnvio 
            :usuario-id="usuarioId" 
            :modo-edicion="esEdicion"
            :medio-seleccionado-inicial="farmaco.medio_envio"
            @update:medio="farmaco.medio_envio = $event"
            :class="{ 'border-red-500': tieneError('medio_envio') }"
          />
          
          <div v-if="tieneError('medio_envio')" class="text-red-500 text-sm mt-1">
            {{ obtenerErrorCampo('medio_envio') }}
          </div>
          
          <div v-if="farmaco.medio_envio" class="mt-4 text-center text-gray-700">
            <span class="font-semibold">
              {{ esEdicion ? 'Medio de envío utilizado:' : 'Medio seleccionado:' }}
            </span>
            <span class="ml-1 text-blue-600 font-medium">
              {{ obtenerNombreMedio(farmaco.medio_envio) }}
            </span>
            <p v-if="esEdicion" class="text-sm text-gray-500 mt-1">
              (En modo edición el medio de envío no se puede cambiar)
            </p>
          </div>
        </div>
        
        <div v-else class="text-center py-4">
          <p class="text-gray-500">Cargando información del dueño...</p>
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
          {{ procesando ? 'Procesando...' : (esEdicion ? 'Actualizar Fármaco' : 'Registrar Fármaco') }}
        </button>
      </div>
    </form>

    <!-- Componente externo del overlay -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="farmaco.centro_veterinario_id"
      @cerrar="mostrarOverlayCentros = false"
      @seleccionar="seleccionarCentro"
    />

    <!-- Modal de confirmación -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 max-h-[80vh] overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">
          {{ esEdicion ? 'Confirmar Actualización' : 'Confirmar Registro' }}
        </h3>
        
        <div class="mb-6 space-y-3">
          <p class="text-gray-700">
            <span class="font-semibold">Tipo de fármaco:</span> {{ obtenerNombreTipoFarmaco() }}
          </p>
          <p class="text-gray-700">
            <span class="font-semibold">Fecha administración:</span> {{ formatFechaHora(farmaco.fecha_administracion) }}
          </p>
          <p class="text-gray-700">
            <span class="font-semibold">Frecuencia:</span> {{ farmaco.frecuencia }}
          </p>
          <p class="text-gray-700">
            <span class="font-semibold">Duración:</span> {{ farmaco.duracion }}
          </p>
          <p class="text-gray-700">
            <span class="font-semibold">Dosis:</span> {{ farmaco.dosis }} {{ farmaco.unidad }}
          </p>
          <p v-if="farmaco.centro_veterinario_id" class="text-gray-700">
            <span class="font-semibold">Centro veterinario:</span> {{ obtenerNombreCentroSeleccionado() }}
          </p>
          <p v-if="farmaco.proxima_dosis" class="text-gray-700">
            <span class="font-semibold">Próxima dosis:</span> {{ formatFechaHora(farmaco.proxima_dosis) }}
          </p>
          <p v-if="farmaco.reacciones" class="text-gray-700">
            <span class="font-semibold">Reacciones adversas:</span> {{ farmaco.reacciones }}
          </p>
          <p v-if="farmaco.recomendaciones" class="text-gray-700">
            <span class="font-semibold">Recomendaciones:</span> {{ farmaco.recomendaciones }}
          </p>
          <p v-if="farmaco.medio_envio" class="text-gray-700">
            <span class="font-semibold">Medio de envío:</span> {{ obtenerNombreMedio(farmaco.medio_envio) }}
          </p>
          <p v-if="archivosCargados > 0" class="text-gray-700">
            <span class="font-semibold">Archivos adjuntos:</span> {{ archivosCargados }} archivo(s)
          </p>
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
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import SeleccionCentroVeterinario from '@/components/ElementosGraficos/SeleccionCentroVeterinario.vue'
import CarruselMedioEnvio from '@/components/ElementosGraficos/CarruselMedioEnvio.vue'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
  farmacoId: {
    type: [String, Number],
    default: null
  }
})

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Estados reactivos
const tiposFarmaco = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)
const mostrarModal = ref(false)

// Agrega estas variables para manejo de errores
const erroresValidacion = ref({})
const mostrarErrores = ref(false)

// Determinar si es edición o registro
const esEdicion = computed(() => {
  return route.name === 'editarFarmaco' || !!route.params.farmacoId || !!props.farmacoId
})

const farmacoId = computed(() => {
  return props.farmacoId || route.params.farmacoId || null
})

const mascotaId = computed(() => {
  return route.query.mascotaId || route.params.mascotaId || null
})

console.log('🔍 Es edición fármaco:', esEdicion.value)
console.log('🔍 Fármaco ID:', farmacoId.value)
console.log('🔍 Mascota ID:', mascotaId.value)

// Datos del formulario
const farmaco = reactive({
  tipo_farmaco_id: '',
  fecha_administracion: '',
  dosis: '',
  unidad: 'mg',
  frecuencia: '',
  duracion: '',
  centro_veterinario_id: '',
  proxima_dosis: '',
  reacciones: '',
  recomendaciones: '',
  medio_envio: ''
})

const archivos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null,
  esExistente: false // Para diferenciar archivos nuevos de existentes
})))

const inputsArchivo = ref([])

// Helper functions para manejo de errores
const tieneError = (campo) => {
  return erroresValidacion.value[campo] && erroresValidacion.value[campo].length > 0
}

const obtenerErrorCampo = (campo) => {
  return erroresValidacion.value[campo] ? erroresValidacion.value[campo][0] : ''
}

// Función mejorada para mostrar errores
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
  } else if (error.response?.data?.errors) {
    // Alternativa: si los errores vienen en data.errors directamente
    erroresValidacion.value = error.response.data.errors
    
    for (const campo in error.response.data.errors) {
      const mensajes = error.response.data.errors[campo]
      mensajes.forEach(mensaje => {
        erroresArray.push(`• ${mensaje}`)
      })
    }
  } else if (error.message) {
    // Si es un error genérico
    erroresArray.push(`• ${error.message}`)
    erroresValidacion.value = {
      _general: [error.message]
    }
  } else {
    erroresArray.push('• Ocurrió un error desconocido')
    erroresValidacion.value = {
      _general: ['Ocurrió un error desconocido']
    }
  }
  
  // Mostrar alerta con mejor formato
  const mensajeFinal = erroresArray.join('\n')
  alert(`❌ Error de validación:\n\n${mensajeFinal}`)
}

// Limpiar errores cuando se cambia un campo
const limpiarErrores = () => {
  erroresValidacion.value = {}
  mostrarErrores.value = false
}

// Computed para validación del formulario
const formularioValido = computed(() => {
  const camposObligatorios = farmaco.tipo_farmaco_id && 
    farmaco.fecha_administracion && 
    farmaco.frecuencia && 
    farmaco.duracion && 
    farmaco.dosis && 
    farmaco.unidad
    
  // Para registro, el medio de envío es obligatorio
  if (!esEdicion.value) {
    return camposObligatorios && farmaco.medio_envio
  }
  
  // Para edición, solo los campos básicos son obligatorios
  return camposObligatorios
})

// Contador de archivos cargados
const archivosCargados = computed(() => {
  return archivos.value.filter(archivo => archivo.archivo || archivo.esExistente).length
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
  const centro = centrosVeterinarios.value.find(c => c.id === farmaco.centro_veterinario_id)
  return centro ? centro.nombre : 'Centro no encontrado'
}

// Obtener dirección del centro seleccionado
const obtenerDireccionCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === farmaco.centro_veterinario_id)
  return centro ? centro.direccion : ''
}

// Obtener nombre del tipo de fármaco
const obtenerNombreTipoFarmaco = () => {
  const tipo = tiposFarmaco.value.find(t => t.id == farmaco.tipo_farmaco_id)
  return tipo ? `${tipo.nombre_comercial} (${tipo.nombre_generico})` : 'No seleccionado'
}

// Formatear fecha y hora
const formatFechaHora = (fechaHora) => {
  if (!fechaHora) return 'No especificada'
  const fecha = new Date(fechaHora)
  return fecha.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Cargar datos de la mascota para obtener el usuario_id
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

// Cargar tipos de fármaco
const cargarTiposFarmaco = async () => {
  try {
    console.log('🔄 Cargando tipos de fármaco desde:', '/api/tipos-farmaco');
    console.log('🔑 Token disponible:', !!accessToken.value);
    
    const response = await fetch('/api/tipos-farmaco', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });

    console.log('📡 Estado de respuesta:', response.status, response.statusText);
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('❌ Error en respuesta:', errorText);
      throw new Error(`Error ${response.status}: ${response.statusText}`);
    }

    const result = await response.json();
    console.log('📦 Resultado completo:', result);
    
    if (result.success && result.data) {
      tiposFarmaco.value = result.data;
      console.log('✅ Tipos de fármaco cargados:', tiposFarmaco.value.length, 'registros');
    } else {
      console.warn('⚠️ No se encontraron datos en la respuesta:', result);
      tiposFarmaco.value = [];
    }
  } catch (error) {
    console.error('❌ Error cargando tipos de fármaco:', error);
    mostrarErrorValidacion(error);
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
    mostrarErrorValidacion(error)
  }
}

// Cargar datos de fármaco existente (para edición)
const cargarFarmacoExistente = async () => {
  if (!farmacoId.value || !mascotaId.value) return
  
  try {
    console.log('🔄 Cargando datos de fármaco con ID:', farmacoId.value, 'para mascota:', mascotaId.value)
    
    // CORRECCIÓN: Usar la ruta correcta con mascotaId
    const response = await fetch(`/api/mascotas/${mascotaId.value}/farmacos/${farmacoId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Respuesta de fármaco:', result)
    
    if (result.success && result.data) {
      const datosFarmaco = result.data
      
      // Actualizar el objeto farmaco con los datos existentes
      Object.assign(farmaco, {
          tipo_farmaco_id: datosFarmaco.tipo_farmaco_id,
          fecha_administracion: datosFarmaco.fecha_administracion ? 
              new Date(datosFarmaco.fecha_administracion).toISOString().slice(0, 16) : '',
          dosis: datosFarmaco.dosis,
          unidad: datosFarmaco.unidad, // Asegúrate que viene como 'unidad'
          frecuencia: datosFarmaco.frecuencia,
          duracion: datosFarmaco.duracion, // Asegúrate que viene como 'duracion'
          centro_veterinario_id: datosFarmaco.centro_veterinario_id,
          proxima_dosis: datosFarmaco.proxima_dosis ? 
              new Date(datosFarmaco.proxima_dosis).toISOString().slice(0, 16) : '',
          reacciones: datosFarmaco.reacciones || '',
          recomendaciones: datosFarmaco.recomendaciones || '',
          medio_envio: datosFarmaco.medio_envio || '',
      })
      
      // Cargar archivos existentes si los hay
      if (datosFarmaco.archivos && Array.isArray(datosFarmaco.archivos)) {
        datosFarmaco.archivos.forEach((archivoData, index) => {
          if (index < archivos.value.length) {
            archivos.value[index] = {
              archivo: { name: archivoData.nombre || `Archivo ${index + 1}` },
              preview: archivoData.url || null,
              esExistente: true
            }
          }
        })
      }
      
      console.log('✅ Datos de fármaco cargados:', farmaco)
    } else {
      console.warn('❌ No se encontraron datos de fármaco:', result)
      mostrarErrorValidacion({ 
        message: 'No se pudo cargar el fármaco a editar: ' + (result.message || 'Error desconocido')
      })
      
      // Redirigir a la página anterior
      if (mascotaId.value) {
        router.push({
          name: 'veterinario-farmacos',
          params: { id: mascotaId.value }
        })
      }
    }
  } catch (error) {
    console.error('❌ Error cargando datos de fármaco:', error)
    mostrarErrorValidacion(error)
    
    // Redirigir a la página anterior
    if (mascotaId.value) {
      router.push({
        name: 'veterinario-farmacos',
        params: { id: mascotaId.value }
      })
    }
  }
}

const onTipoFarmacoChange = () => {
  limpiarErrores()
  const tipoSeleccionado = tiposFarmaco.value.find(t => t.id == farmaco.tipo_farmaco_id)
  if (tipoSeleccionado) {
    console.log('Tipo de fármaco seleccionado:', tipoSeleccionado)
  }
}

// Abrir overlay externo
const abrirOverlayCentros = () => {
  mostrarOverlayCentros.value = true
}

// Seleccionar centro desde overlay
const seleccionarCentro = (centro) => {
  farmaco.centro_veterinario_id = centro.id
  mostrarOverlayCentros.value = false
  limpiarErrores()
}

// Navegar al registro de nuevo tipo
const abrirRegistroTipoFarmaco = () => {
  const query = {
    from: esEdicion.value ? `/editar/farmaco/${farmacoId.value}` : `/registro/farmaco/${mascotaId.value}`,
    mascotaId: mascotaId.value
  }
  
  router.push({
    path: '/registro/registroTipoFarmaco',
    query
  })
}

const esImagen = (archivo) => {
  if (!archivo) return false
  return archivo.type ? archivo.type.startsWith('image/') : false
}

const handleArchivo = (event, index) => {
  if (esEdicion.value) return // No permitir cambios en modo edición
  
  limpiarErrores()
  const file = event.target.files[0]
  if (file) {
    archivos.value[index].archivo = file
    archivos.value[index].preview = esImagen(file) ? URL.createObjectURL(file) : null
    archivos.value[index].esExistente = false
  }
}

const activarInput = (index) => {
  if (!esEdicion.value) {
    inputsArchivo.value[index]?.click()
  }
}

const quitarArchivo = (index) => {
  if (esEdicion.value) return // No permitir cambios en modo edición
  
  if (archivos.value[index].preview) {
    URL.revokeObjectURL(archivos.value[index].preview)
  }
  archivos.value[index].archivo = null
  archivos.value[index].preview = null
  archivos.value[index].esExistente = false
  limpiarErrores()
}

// Mostrar modal de confirmación
const mostrarModalConfirmacion = () => {
  limpiarErrores()
  
  if (!formularioValido.value) {
    mostrarErrorValidacion({ 
      message: 'Por favor complete todos los campos obligatorios' 
    })
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
    actualizarFarmaco()
  } else {
    registrarFarmaco()
  }
}

// Procesar formulario (ahora solo muestra el modal)
const procesarFormulario = () => {
  mostrarModalConfirmacion()
}

// Registrar fármaco
const registrarFarmaco = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()
    
    // Limpiar errores previos
    limpiarErrores()

    // Preparar FormData para enviar archivos
    const formData = new FormData()
    
    // Agregar datos del formulario
    Object.keys(farmaco).forEach(key => {
      if (farmaco[key] !== null && farmaco[key] !== '') {
        formData.append(key, farmaco[key])
      }
    })

    // Agregar archivos nuevos
    archivos.value.forEach((archivo, index) => {
      if (archivo.archivo && !archivo.esExistente) {
        formData.append(`archivos[${index}]`, archivo.archivo)
      }
    })

    console.log('📤 Enviando datos a servidor para registro:', Object.fromEntries(formData))
    console.log('📤 Mascota ID:', mascotaId.value)

    if (!mascotaId.value) {
      throw new Error('No se encontró el ID de la mascota')
    }

    const response = await fetch(`/api/mascotas/${mascotaId.value}/farmacos`, {
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
      // Mostrar mensaje de éxito incluyendo información del envío si existe
      let mensajeExito = '✅ Fármaco registrado exitosamente'
      if (result.data?.envio_exitoso === true) {
        mensajeExito += ' y receta enviada'
      } else if (result.data?.envio_exitoso === false) {
        mensajeExito += ' (pero hubo un problema al enviar la receta)'
      }
      
      alert(mensajeExito)
      
      // Redirigir a la lista de fármacos
      router.push({
        name: 'veterinario-farmacos',
        params: { id: mascotaId.value },
        query: {
          from: 'registroFarmaco',
          currentTab: 'Clinico',
          ts: Date.now()
        }
      })
    } else {
      mostrarErrorValidacion({ message: result.message || 'Error al registrar el fármaco' })
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    mostrarErrorValidacion(error)
  } finally {
    procesando.value = false
  }
}

// Actualizar fármaco existente
const actualizarFarmaco = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()
    
    // Limpiar errores previos
    limpiarErrores()

    console.log('📤 Actualizando fármaco con ID:', farmacoId.value, 'para mascota:', mascotaId.value)
    console.log('📤 Datos a enviar:', farmaco)

    if (!mascotaId.value) {
      throw new Error('No se encontró el ID de la mascota')
    }

    // CORRECCIÓN: Usar la ruta correcta con mascotaId
    const response = await fetch(`/api/mascotas/${mascotaId.value}/farmacos/${farmacoId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(farmaco)
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
      throw new Error('El servidor no devolvió JSON válido.')
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
      alert('✅ Fármaco actualizado exitosamente')
      
      const mascotaIdParaRedireccion = mascotaId.value || result.data?.mascota_id || result.data?.procesoMedico?.mascota_id
      
      if (mascotaIdParaRedireccion) {
        router.push({
          name: 'veterinario-farmacos',
          params: { id: mascotaIdParaRedireccion },
          query: {
            from: 'editarFarmaco',
            currentTab: 'Clinico',
            ts: Date.now()
          }
        })
      } else {
        router.push({ name: 'veterinario-farmacos', params: { id: '0' } })
      }
    } else {
      mostrarErrorValidacion({ message: result.message || 'Error al actualizar el fármaco' })
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    mostrarErrorValidacion(error)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  const mascotaIdParaRedireccion = mascotaId.value
  
  if (mascotaIdParaRedireccion) {
    router.push({
      name: 'veterinario-farmacos',
      params: { id: mascotaIdParaRedireccion },
      query: {
        from: esEdicion.value ? 'cancelarEditarFarmaco' : 'cancelarRegistroFarmaco',
        currentTab: 'Clinico',
        ts: Date.now()
      }
    })
  } else {
    router.push({ name: 'veterinario-farmacos', params: { id: '0' } })
  }
}

// Verificar autenticación y cargar datos
onMounted(async () => {
  console.log('🚀 Iniciando componente RegistrarFarmaco')
  
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      router.push('/login')
      return
    }
  }

  // Si es edición, cargar datos del fármaco primero
  if (esEdicion.value) {
    await cargarFarmacoExistente()
  }

  // Cargar datos en orden
  if (mascotaId.value) {
    await cargarDatosMascota()
    
    if (errorCargandoMascota.value) {
      console.error('❌ Error al cargar mascota:', errorCargandoMascota.value)
      mostrarErrorValidacion({ 
        message: 'Error al cargar datos de la mascota: ' + errorCargandoMascota.value
      })
      return
    }
  }

  await Promise.all([
    cargarTiposFarmaco(),
    cargarCentrosVeterinarios()
  ])

  // Establecer fecha y hora actual como predeterminada solo si es registro nuevo y no hay fecha
  if (!esEdicion.value && !farmaco.fecha_administracion) {
    const ahora = new Date()
    const offset = ahora.getTimezoneOffset() * 60000
    const localISOTime = new Date(ahora.getTime() - offset).toISOString().slice(0, 16)
    farmaco.fecha_administracion = localISOTime
  }
  
  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
})
</script>
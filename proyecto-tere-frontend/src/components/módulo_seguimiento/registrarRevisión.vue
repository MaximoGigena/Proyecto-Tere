<!-- RegistrarRevision.vue - Versión final con manejo de errores como vacunas -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <!-- TÍTULO DINÁMICO -->
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Revisión Médica' : 'Registrar Revisión Médica' }}</h1>

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
          <!-- Selección de Tipo de Revisión -->
          <div>
            <label class="block font-medium">Tipo de revisión aplicada</label>
            <div class="flex gap-2">
              <select
                v-model="revision.tipo_revision_id"
                required
                class="w-full border rounded p-2"
                @change="onTipoRevisionChange"
                :class="{ 'border-red-500': erroresValidacion.tipo_revision_id }"
              >
                <option value="">Seleccione un tipo de revisión</option>
                <option
                  v-for="tipo in tiposRevision"
                  :key="tipo.id"
                  :value="tipo.id"
                >
                  {{ tipo.nombre }}
                </option>
              </select>

              <!-- Botón de + Tipo -->
              <button 
                type="button"
                @click="abrirRegistroTipoRevision"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo 
              </button>
            </div>
            <!-- Mostrar error específico para tipo_revision_id -->
            <div v-if="erroresValidacion.tipo_revision_id" class="text-red-600 text-sm mt-1">
              <span v-for="error in erroresValidacion.tipo_revision_id" :key="error" class="block">
                {{ error }}
              </span>
            </div>
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">
              Centro Veterinario donde se realizó
            </label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="revision.centro_veterinario_id"
                class="w-full border rounded p-2 bg-gray-50"
                :class="{ 'border-red-500': erroresValidacion.centro_veterinario_id }"
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
                :class="{ 'border-red-500': erroresValidacion.centro_veterinario_id }"
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
            <!-- Mostrar error específico para centro_veterinario_id -->
            <div v-if="erroresValidacion.centro_veterinario_id" class="text-red-600 text-sm mt-1">
              <span v-for="error in erroresValidacion.centro_veterinario_id" :key="error" class="block">
                {{ error }}
              </span>
            </div>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Fecha de revisión</label>
            <input 
              v-model="revision.fecha_revision" 
              type="datetime-local" 
              required 
              class="w-full border rounded p-2"
              :class="{ 'border-red-500': erroresValidacion.fecha_revision }"
            />
            <!-- Mostrar error específico para fecha_revision -->
            <div v-if="erroresValidacion.fecha_revision" class="text-red-600 text-sm mt-1">
              <span v-for="error in erroresValidacion.fecha_revision" :key="error" class="block">
                {{ error }}
              </span>
            </div>
          </div>

          <div>
            <label class="block font-medium">Nivel de urgencia</label>
            <select 
              v-model="revision.nivel_urgencia" 
              required 
              class="w-full border rounded p-2"
              :class="{ 'border-red-500': erroresValidacion.nivel_urgencia }"
            >
              <option value="rutinaria">Rutinaria</option>
              <option value="preventiva">Preventiva</option>
              <option value="urgencia">Urgencia</option>
              <option value="emergencia">Emergencia</option>
            </select>
            <!-- Mostrar error específico para nivel_urgencia -->
            <div v-if="erroresValidacion.nivel_urgencia" class="text-red-600 text-sm mt-1">
              <span v-for="error in erroresValidacion.nivel_urgencia" :key="error" class="block">
                {{ error }}
              </span>
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
          <label class="block font-medium">Motivo de la consulta</label>
          <input 
            v-model="revision.motivo_consulta" 
            type="text" 
            class="w-full border rounded p-2"
            :class="{ 'border-red-500': erroresValidacion.motivo_consulta }"
          />
          <!-- Mostrar error específico para motivo_consulta -->
          <div v-if="erroresValidacion.motivo_consulta" class="text-red-600 text-sm mt-1">
            <span v-for="error in erroresValidacion.motivo_consulta" :key="error" class="block">
              {{ error }}
            </span>
          </div>
        </div>

        <div>
          <label class="block font-medium">Diagnóstico (si aplica)</label>
          <div class="space-y-3">
            <!-- Campo de texto para diagnóstico manual -->
            <div class="flex gap-2">
              <input 
                v-model="revision.diagnostico" 
                type="text" 
                class="w-full border rounded p-2"
                :class="{ 'border-red-500': erroresValidacion.diagnostico }"
                placeholder="Diagnóstico identificado (texto libre)" 
              />
              <button 
                type="button"
                @click="mostrarSeleccionDiagnostico = true"
                class="bg-orange-500 text-white px-4 py-2 rounded font-bold hover:bg-orange-700 transition-colors whitespace-nowrap flex items-center gap-2"
              >
                + Asociar Diagnóstico
              </button>
            </div>
            <!-- Mostrar error específico para diagnostico -->
            <div v-if="erroresValidacion.diagnostico" class="text-red-600 text-sm mt-1">
              <span v-for="error in erroresValidacion.diagnostico" :key="error" class="block">
                {{ error }}
              </span>
            </div>
            
            <!-- Mostrar diagnósticos seleccionados -->
            <div v-if="diagnosticosSeleccionados.length > 0" class="mt-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
              <h4 class="font-bold text-blue-800 mb-2 flex items-center gap-2">
                Diagnósticos asociados ({{ diagnosticosSeleccionados.length }})
                <span class="text-sm font-normal text-gray-600">
                  {{ revision.diagnosticos_ids.length }} IDs almacenados
                </span>
              </h4>
              
              <!-- Mensaje de depuración (solo desarrollo) -->
              <div v-if="diagnosticosSeleccionados.length === 0 && revision.diagnosticos_ids.length > 0" 
                  class="mb-2 p-2 bg-yellow-50 text-yellow-800 text-sm rounded">
                ⚠️ Hay {{ revision.diagnosticos_ids.length }} IDs de diagnósticos pero no se pudieron cargar los detalles.
                Los IDs son: {{ revision.diagnosticos_ids.join(', ') }}
              </div>
              
              <div class="flex flex-wrap gap-2">
                <div 
                  v-for="(diag, index) in diagnosticosSeleccionados" 
                  :key="diag.id || index" 
                  class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-full border border-blue-300 shadow-sm"
                >
                  <span class="font-medium text-gray-800">
                    {{ diag.nombre || 'Diagnóstico sin nombre' }}
                    <span v-if="diag.id" class="text-xs text-gray-500 ml-1">(ID: {{ diag.id }})</span>
                  </span>
                  <span 
                    v-if="diag.evolucion"
                    :class="[
                      'px-2 py-0.5 text-xs font-bold rounded-full',
                      getEvolutionColor(diag.evolucion)
                    ]"
                  >
                    {{ getEvolutionLabel(diag.evolucion) }}
                  </span>
                  <button 
                    type="button"
                    @click="eliminarDiagnostico(index)"
                    class="text-red-500 hover:text-red-700 ml-1"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div>
          <label class="block font-medium">Fecha sugerida para próxima revisión</label>
          <input 
            v-model="revision.fecha_proxima_revision" 
            type="date" 
            class="w-full border rounded p-2"
            :class="{ 'border-red-500': erroresValidacion.fecha_proxima_revision }"
          />
          <!-- Mostrar error específico para fecha_proxima_revision -->
          <div v-if="erroresValidacion.fecha_proxima_revision" class="text-red-600 text-sm mt-1">
            <span v-for="error in erroresValidacion.fecha_proxima_revision" :key="error" class="block">
              {{ error }}
            </span>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Indicaciones o conducta médica sugerida</label>
          <textarea 
            v-model="revision.indicaciones_medicas" 
            rows="3" 
            maxlength="500" 
            class="w-full border rounded p-2 resize-none"
            :class="{ 'border-red-500': erroresValidacion.indicaciones_medicas }"
          ></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ revision.indicaciones_medicas.length }}/500 caracteres</p>
          <!-- Mostrar error específico para indicaciones_medicas -->
          <div v-if="erroresValidacion.indicaciones_medicas" class="text-red-600 text-sm mt-1">
            <span v-for="error in erroresValidacion.indicaciones_medicas" :key="error" class="block">
              {{ error }}
            </span>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Recomendaciones al tutor</label>
          <textarea 
            v-model="revision.recomendaciones_tutor" 
            rows="3" 
            maxlength="500" 
            class="w-full border rounded p-2 resize-none"
            :class="{ 'border-red-500': erroresValidacion.recomendaciones_tutor }"
          ></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ revision.recomendaciones_tutor.length }}/500 caracteres</p>
          <!-- Mostrar error específico para recomendaciones_tutor -->
          <div v-if="erroresValidacion.recomendaciones_tutor" class="text-red-600 text-sm mt-1">
            <span v-for="error in erroresValidacion.recomendaciones_tutor" :key="error" class="block">
              {{ error }}
            </span>
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
              @click="!archivo.preview && activarInput(index)"
            >
              <!-- Botón eliminar -->
              <button
                type="button"
                @click.stop="quitarArchivo(index)"
                v-if="archivo.preview"
                class="absolute top-0.5 right-0.5 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700"
              >
                ×
              </button>

              <!-- Input oculto -->
              <input
                :ref="el => inputsArchivo[index] = el"
                type="file"
                @change="handleArchivo($event, index)"
                class="hidden"
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
                  📄
                </div>
                <div class="text-[10px] truncate px-1">{{ archivo.archivo.name }}</div>
              </div>

              <!-- Indicador visual si no hay archivo -->
              <div v-else class="text-green-400 flex flex-col justify-center items-center h-full">
                ➕
                <div class="text-[10px] text-gray-400">Agregar</div>
              </div>
            </div>
          </div>
          <!-- Mostrar error específico para archivos -->
          <div v-if="erroresValidacion.archivos" class="text-red-600 text-sm mt-1">
            <span v-for="error in erroresValidacion.archivos" :key="error" class="block">
              {{ error }}
            </span>
          </div>
          <p class="text-xs text-gray-500 mt-1">Puede adjuntar recetas, imágenes del medicamento, informes, etc.</p>
        </div>

        <!-- Selección del medio de envío -->
        <div class="col-span-full mt-4">
          <div v-if="usuarioId">
            <!-- CARRUSEL CON MODO EDICIÓN -->
            <CarruselMedioEnvio 
              :usuario-id="usuarioId" 
              :modo-edicion="esEdicion"
              :medio-seleccionado-inicial="revision.medio_envio"
              @update:medio="revision.medio_envio = $event" 
            />
            
            <div v-if="revision.medio_envio" class="mt-4 text-center text-gray-700">
              <span class="font-semibold">
                {{ esEdicion ? 'Medio de envío utilizado:' : 'Medio seleccionado para envío de informe:' }}
              </span>
              <span class="ml-1 text-blue-600 font-medium">
                {{ obtenerNombreMedio(revision.medio_envio) }}
              </span>
              <!-- TEXTO DE MODO EDICIÓN -->
              <p v-if="esEdicion" class="text-sm text-gray-500 mt-1">
                (En modo edición el medio de envío no se puede cambiar)
              </p>
            </div>
            <!-- Mostrar error específico para medio_envio -->
            <div v-if="erroresValidacion.medio_envio" class="text-red-600 text-sm mt-1 text-center">
              <span v-for="error in erroresValidacion.medio_envio" :key="error" class="block">
                {{ error }}
              </span>
            </div>
          </div>
          
          <div v-else class="text-center py-4">
            <p class="text-gray-500">Cargando información del dueño...</p>
          </div>
        </div>
      </div>

      <!-- Sección de errores de validación (COMO EN VACUNAS) -->
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
          <!-- TEXTO DINÁMICO DEL BOTÓN -->
          {{ procesando ? 'Procesando...' : (esEdicion ? 'Actualizar Revisión' : 'Registrar Revisión') }}
        </button>
      </div>
    </form>

    <!-- Componente externo del overlay -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="revision.centro_veterinario_id"
      @cerrar="mostrarOverlayCentros = false"
      @seleccionar="seleccionarCentro"
    />

    <!-- Overlay para seleccionar diagnósticos -->
    <SeleccionarDiagnostico
      v-if="mostrarSeleccionDiagnostico"
      :mostrar="mostrarSeleccionDiagnostico"
      :mascota-id="mascotaId"
      :diagnosticos-iniciales="diagnosticosSeleccionados"
      @cerrar="mostrarSeleccionDiagnostico = false"
      @guardar="guardarDiagnosticosSeleccionados"
    />

    <!-- Modal de confirmación -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <!-- TÍTULO DINÁMICO DEL MODAL -->
        <h3 class="text-xl font-bold mb-4">
          {{ esEdicion ? 'Confirmar Actualización' : 'Confirmar Registro' }}
        </h3>
        
        <div class="mb-6">
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Tipo de revisión:</span> {{ obtenerNombreTipoRevision() }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Fecha:</span> {{ formatFecha(revision.fecha_revision) }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Nivel de urgencia:</span> {{ revision.nivel_urgencia }}
          </p>
          <p v-if="revision.centro_veterinario_id" class="text-gray-700 mb-2">
            <span class="font-semibold">Centro veterinario:</span> {{ obtenerNombreCentroSeleccionado() }}
          </p>
          <p v-if="revision.motivo_consulta" class="text-gray-700 mb-2">
            <span class="font-semibold">Motivo consulta:</span> {{ revision.motivo_consulta }}
          </p>
          <p v-if="revision.diagnostico" class="text-gray-700 mb-2">
            <span class="font-semibold">Diagnóstico:</span> {{ revision.diagnostico }}
          </p>
          <p v-if="revision.fecha_proxima_revision" class="text-gray-700 mb-2">
            <span class="font-semibold">Próxima revisión:</span> {{ formatFecha(revision.fecha_proxima_revision) }}
          </p>
          <p v-if="revision.medio_envio" class="text-gray-700">
            <span class="font-semibold">Medio de envío:</span> {{ obtenerNombreMedio(revision.medio_envio) }}
          </p>
        </div>

        <div class="flex justify-end gap-3">
          <button
            @click="cerrarModal"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Cancelar
          </button>
          <!-- BOTÓN DINÁMICO DEL MODAL -->
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
import SeleccionCentroVeterinario from '@/components/ElementosGraficos/SeleccionCentroVeterinario.vue'
import CarruselMedioEnvio from '@/components/ElementosGraficos/CarruselMedioEnvio.vue'
import SeleccionarDiagnostico from '@/components/ElementosGraficos/SeleccionarDiagnostico.vue'
import { useAuth } from '@/composables/useAuth'

// PROPS
const props = defineProps({
  revisionId: {
    type: [String, Number],
    default: null
  }
})

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Estados reactivos - AGREGAMOS MANEJO DE ERRORES
const tiposRevision = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const mostrarSeleccionDiagnostico = ref(false)
const diagnosticosSeleccionados = ref([])
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)
const mostrarModal = ref(false)

// AÑADIMOS ESTOS ESTADOS PARA MANEJO DE ERRORES (IGUAL QUE VACUNAS)
const erroresValidacion = ref({})
const mostrarErrores = ref(false)

// DETERMINAR SI ES EDICIÓN O REGISTRO
const esEdicion = computed(() => {
  return route.name === 'editarRevision' || !!route.params.revisionId || !!props.revisionId
})

// OBTENER IDS
const revisionId = computed(() => {
  return props.revisionId || route.params.revisionId || null
})

const mascotaId = computed(() => {
  return route.query.mascotaId || route.params.mascotaId || null
})

// Datos del formulario
const revision = reactive({
  tipo_revision_id: '',
  fecha_revision: '',
  nivel_urgencia: 'rutinaria',
  motivo_consulta: '',
  diagnostico: '', 
  fecha_proxima_revision: '',
  indicaciones_medicas: '',
  recomendaciones_tutor: '',
  centro_veterinario_id: '',
  medio_envio: '',
  diagnosticos_ids: [],
  observaciones: '',
  costo: null,
})

const archivos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null
})))

const inputsArchivo = ref([])

// Computed para validación del formulario
const formularioValido = computed(() => {
  const camposObligatorios = revision.tipo_revision_id && 
    revision.fecha_revision && 
    revision.nivel_urgencia
    
  if (!esEdicion.value) {
    return camposObligatorios && revision.medio_envio
  }
  
  return camposObligatorios
})

// Obtener ID del usuario dueño de la mascota
const usuarioId = computed(() => {
  return mascotaData.value?.usuario_id || null
})

// ==================== FUNCIONES PARA MANEJO DE ERRORES (IGUAL QUE VACUNAS) ====================
const mostrarErrorValidacion = (error) => {
  mostrarErrores.value = true
  const erroresArray = []
  
  // Limpiar errores previos
  erroresValidacion.value = {}
  
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

const limpiarErrores = () => {
  erroresValidacion.value = {}
  mostrarErrores.value = false
}

// Función para obtener mensajes de error de un campo específico
const obtenerErroresCampo = (campo) => {
  return erroresValidacion.value[campo] || []
}

// ==================== FUNCIONES AUXILIARES ====================
const guardarDiagnosticosSeleccionados = (diagnosticos) => {
  diagnosticosSeleccionados.value = diagnosticos.map(d => ({
    id: d.id,
    nombre: d.nombre || d.diagnostico_nombre || 'Diagnóstico sin nombre',
    tipo: d.tipo || d.type || 'general',
    evolucion: d.evolucion || d.diagnostico_evolucion || 'aguda'
  }))
  
  revision.diagnosticos_ids = diagnosticosSeleccionados.value.map(d => d.id)
  
  if (diagnosticosSeleccionados.value.length > 0) {
    revision.diagnostico = diagnosticosSeleccionados.value.map(d => d.nombre).join(', ')
  } else {
    revision.diagnostico = ''
  }
  
  mostrarSeleccionDiagnostico.value = false
}

const eliminarDiagnostico = (index) => {
  diagnosticosSeleccionados.value.splice(index, 1)
  revision.diagnosticos_ids = diagnosticosSeleccionados.value.map(d => d.id)
  
  if (diagnosticosSeleccionados.value.length > 0) {
    revision.diagnostico = diagnosticosSeleccionados.value.map(d => d.nombre).join(', ')
  } else {
    revision.diagnostico = ''
  }
}

const getEvolutionLabel = (evolution) => {
  if (!evolution) return 'Sin evolución'
  
  const map = {
    'aguda': 'Aguda',
    'cronica': 'Crónica',
    'recurrente': 'Recurrente',
    'autolimitada': 'Autolimitada',
    'progresiva': 'Progresiva'
  }
  return map[evolution] || evolution
}

const getEvolutionColor = (evolution) => {
  if (!evolution) return 'bg-gray-100 text-gray-800'
  
  const map = {
    'aguda': 'bg-red-100 text-red-800',
    'cronica': 'bg-yellow-100 text-yellow-800',
    'recurrente': 'bg-blue-100 text-blue-800',
    'autolimitada': 'bg-green-100 text-green-800',
    'progresiva': 'bg-purple-100 text-purple-800'
  }
  return map[evolution] || 'bg-gray-100 text-gray-800'
}

const obtenerNombreMedio = (medioId) => {
  const medios = {
    email: 'Email',
    whatsapp: 'WhatsApp',
    telegram: 'Telegram'
  }
  return medios[medioId] || medioId
}

const obtenerNombreCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === revision.centro_veterinario_id)
  return centro ? centro.nombre : 'Centro no encontrado'
}

const obtenerDireccionCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === revision.centro_veterinario_id)
  return centro ? centro.direccion : ''
}

const obtenerNombreTipoRevision = () => {
  const tipo = tiposRevision.value.find(t => t.id == revision.tipo_revision_id)
  return tipo ? tipo.nombre : 'No seleccionado'
}

const formatFecha = (fecha) => {
  if (!fecha) return 'No especificada'
  if (fecha.includes('T')) {
    return new Date(fecha).toLocaleString('es-ES')
  }
  return new Date(fecha).toLocaleDateString('es-ES')
}

// ==================== FUNCIONES DE CARGA DE DATOS ====================
const cargarDatosMascota = async () => {
  try {
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

const cargarTiposRevision = async () => {
  try {
    const response = await fetch('/api/tipos-revision', {
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
      tiposRevision.value = result.data
    } else {
      tiposRevision.value = []
    }
  } catch (error) {
    console.error('Error cargando tipos de revisión:', error)
    alert('Error al cargar los tipos de revisión: ' + error.message)
  }
}

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
  } catch (error) {
    console.error('Error cargando centros veterinarios:', error)
    alert('Error al cargar los centros veterinarios')
  }
}

const cargarDetallesDiagnosticos = async (diagnosticosIds) => {
  if (!diagnosticosIds || diagnosticosIds.length === 0) {
    return
  }
  
  try {
    const response = await fetch(`/api/diagnosticos?ids=${diagnosticosIds.join(',')}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const result = await response.json()
      if (result.success && result.data && Array.isArray(result.data)) {
        diagnosticosSeleccionados.value = result.data.map(d => ({
          id: d.id,
          nombre: d.nombre || 'Diagnóstico',
          tipo: d.tipo || 'general',
          evolucion: d.evolucion || 'aguda'
        }))
        
        if (diagnosticosSeleccionados.value.length > 0) {
          revision.diagnostico = diagnosticosSeleccionados.value.map(d => d.nombre).join(', ')
        }
      } else {
        diagnosticosSeleccionados.value = diagnosticosIds.map(id => ({
          id: id,
          nombre: `Diagnóstico #${id}`,
          tipo: 'desconocido',
          evolucion: 'aguda'
        }))
      }
    } else {
      diagnosticosSeleccionados.value = diagnosticosIds.map(id => ({
        id: id,
        nombre: `Diagnóstico #${id}`,
        tipo: 'desconocido',
        evolucion: 'aguda'
      }))
    }
  } catch (error) {
    console.error('❌ Error cargando detalles de diagnósticos:', error)
    diagnosticosSeleccionados.value = diagnosticosIds.map(id => ({
      id: id,
      nombre: `Diagnóstico #${id}`,
      tipo: 'desconocido',
      evolucion: 'aguda'
    }))
  }
}

// ==================== FUNCIONES DEL FORMULARIO ====================
const onTipoRevisionChange = () => {
  const tipoSeleccionado = tiposRevision.value.find(t => t.id == revision.tipo_revision_id)
  if (tipoSeleccionado) {
    console.log('Tipo de revisión seleccionado:', tipoSeleccionado)
  }
}

const abrirOverlayCentros = () => {
  mostrarOverlayCentros.value = true
}

const seleccionarCentro = (centro) => {
  revision.centro_veterinario_id = centro.id
  mostrarOverlayCentros.value = false
}

const abrirRegistroTipoRevision = () => {
  const query = {
    from: esEdicion.value ? `/editar/revision/${revisionId.value}` : `/registro/revision/${mascotaId.value}`,
    mascotaId: mascotaId.value
  }
  
  router.push({
    path: '/registro/registroTipoRevision',
    query
  })
}

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

const mostrarModalConfirmacion = () => {
  if (!formularioValido.value) {
    alert('Por favor complete todos los campos obligatorios')
    return
  }
  
  mostrarModal.value = true
}

const cerrarModal = () => {
  mostrarModal.value = false
}

const confirmarAccion = () => {
  if (esEdicion.value) {
    actualizarRevision()
  } else {
    registrarRevision()
  }
}

const procesarFormulario = () => {
  mostrarModalConfirmacion()
}

// ==================== FUNCIONES DE REGISTRO/ACTUALIZACIÓN CON MANEJO DE ERRORES ====================
const registrarRevision = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()
    
    // Limpiar errores previos
    limpiarErrores()

    if (!mascotaId.value) {
      throw new Error('No se encontró el ID de la mascota')
    }

    // Crear FormData
    const formData = new FormData()
    
    // Agregar datos de la revisión
    Object.keys(revision).forEach(key => {
      if (revision[key] !== null && revision[key] !== undefined) {
        formData.append(key, revision[key])
      }
    })

    // Agregar archivos
    archivos.value.forEach((archivo, i) => {
      if (archivo.archivo) {
        formData.append(`archivos[]`, archivo.archivo)
      }
    })

    // Agregar mascota_id
    formData.append('mascota_id', mascotaId.value)

    if (diagnosticosSeleccionados.value.length > 0) {
      formData.append('diagnosticos_info', JSON.stringify(
        diagnosticosSeleccionados.value.map(d => ({
          id: d.id,
          nombre: d.nombre,
          tipo: d.type
        }))
      ))
    }

    const response = await fetch(`/api/mascotas/${mascotaId.value}/revisiones`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: formData
    })
    
    const responseText = await response.text()

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

    // Manejar específicamente el error 422 (Validación) - IGUAL QUE EN VACUNAS
    if (response.status === 422) {
      mostrarErrorValidacion({ response: { status: 422, data: result } })
      return
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      // Mostrar mensaje de éxito incluyendo información del envío si existe
      let mensajeExito = '✅ Revisión registrada exitosamente'
      if (result.data?.envio_exitoso === true) {
        mensajeExito += ' e informe enviado'
      } else if (result.data?.envio_exitoso === false) {
        mensajeExito += ' (pero hubo un problema al enviar el informe)'
      }
      
      alert(mensajeExito)
      
      // Redirigir a la lista de revisiones
      router.push({
        name: 'veterinario-revisiones',
        params: { id: mascotaId.value },
        query: {
          from: 'registroRevision',
          currentTab: 'Preventivo',
          ts: Date.now()
        }
      })
    } else {
      mostrarErrorValidacion({ message: result.message || 'Error al registrar la revisión' })
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    mostrarErrorValidacion(error)
  } finally {
    procesando.value = false
  }
}

const actualizarRevision = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()
    
    // Limpiar errores previos
    limpiarErrores()

    if (!mascotaId.value || !revisionId.value) {
      throw new Error('No se encontró el ID de la mascota o de la revisión')
    }

    const formData = new FormData()
    formData.append('_method', 'PUT')
    
    // Agregar todos los campos
    formData.append('tipo_revision_id', revision.tipo_revision_id)
    formData.append('fecha_revision', revision.fecha_revision)
    formData.append('nivel_urgencia', revision.nivel_urgencia)
    
    if (revision.motivo_consulta) formData.append('motivo_consulta', revision.motivo_consulta)
    if (revision.diagnostico) formData.append('diagnostico', revision.diagnostico)
    if (revision.fecha_proxima_revision) formData.append('fecha_proxima_revision', revision.fecha_proxima_revision)
    if (revision.indicaciones_medicas) formData.append('indicaciones_medicas', revision.indicaciones_medicas)
    if (revision.recomendaciones_tutor) formData.append('recomendaciones_tutor', revision.recomendaciones_tutor)
    if (revision.centro_veterinario_id) formData.append('centro_veterinario_id', revision.centro_veterinario_id)
    if (revision.observaciones) formData.append('observaciones', revision.observaciones)
    if (revision.costo) formData.append('costo', revision.costo)
    if (revision.medio_envio) formData.append('medio_envio', revision.medio_envio)
    
    if (revision.diagnosticos_ids && revision.diagnosticos_ids.length > 0) {
      formData.append('diagnosticos_ids', JSON.stringify(revision.diagnosticos_ids))
    } else {
      formData.append('diagnosticos_ids', '[]')
    }

    archivos.value.forEach((archivo, i) => {
      if (archivo.archivo) {
        formData.append(`archivos_nuevos[]`, archivo.archivo)
      }
    })

    const response = await fetch(`/api/mascotas/${mascotaId.value}/revisiones/${revisionId.value}`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      },
      body: formData
    })
    
    const responseText = await response.text()

    let result
    try {
      result = JSON.parse(responseText)
    } catch (parseError) {
      console.error('No se pudo parsear como JSON:', responseText)
      throw new Error('El servidor no devolvió JSON válido.')
    }

    // Manejar específicamente el error 422 (Validación) - IGUAL QUE EN VACUNAS
    if (response.status === 422) {
      mostrarErrorValidacion({ response: { status: 422, data: result } })
      return
    }

    if (!response.ok) {
      throw new Error(result.message || `Error ${response.status}: ${response.statusText}`)
    }

    if (result.success) {
      alert('✅ Revisión actualizada exitosamente')
      router.push({
        name: 'veterinario-revisiones',
        params: { id: mascotaId.value },
        query: {
          from: 'editarRevision',
          currentTab: 'Preventivo',
          ts: Date.now()
        }
      })
    } else {
      mostrarErrorValidacion({ message: result.message || 'Error al actualizar la revisión' })
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
      name: 'veterinario-revisiones',
      params: { id: mascotaIdParaRedireccion },
      query: {
        from: esEdicion.value ? 'cancelarEditarRevision' : 'cancelarRegistroRevision',
        currentTab: 'Preventivo',
        ts: Date.now()
      }
    })
  } else {
    router.push({ name: 'veterinario-revisiones', params: { id: '0' } })
  }
}

const formatDateTimeForInput = (dateTimeString) => {
  if (!dateTimeString) return ''
  
  if (dateTimeString.includes('T')) {
    const date = new Date(dateTimeString)
    return date.toISOString().slice(0, 16)
  }
  
  const date = new Date(dateTimeString)
  return date.toISOString().slice(0, 16)
}

// ==================== CARGA INICIAL ====================
onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      router.push('/login')
      return
    }
  }

  // Si es edición, cargar datos de la revisión primero
  if (esEdicion.value) {
    await cargarRevisionExistente()
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
    cargarTiposRevision(),
    cargarCentrosVeterinarios()
  ])

  // Establecer fecha actual como predeterminada solo si es registro nuevo y no hay fecha
  if (!esEdicion.value && !revision.fecha_revision) {
    const hoy = new Date().toISOString().slice(0, 16)
    revision.fecha_revision = hoy
  }
})

// Función para cargar revisión existente (mantén tu función original aquí)
const cargarRevisionExistente = async () => {
  if (!revisionId.value || !mascotaId.value) {
    console.error('❌ No hay revisionId o mascotaId para cargar datos')
    return
  }
  
  try {
    const response = await fetch(`/api/mascotas/${mascotaId.value}/revisiones/${revisionId.value}`, {
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
      const datosRevision = result.data
      
      Object.assign(revision, {
        tipo_revision_id: datosRevision.tipo_revision_id || '',
        fecha_revision: datosRevision.fecha_revision ? formatDateTimeForInput(datosRevision.fecha_revision) : '',
        nivel_urgencia: datosRevision.nivel_urgencia || 'rutinaria',
        motivo_consulta: datosRevision.motivo_consulta || '',
        diagnostico: datosRevision.diagnostico || '',
        fecha_proxima_revision: datosRevision.fecha_proxima_revision ? datosRevision.fecha_proxima_revision.split('T')[0] : '',
        indicaciones_medicas: datosRevision.indicaciones_medicas || '',
        recomendaciones_tutor: datosRevision.recomendaciones_tutor || '',
        centro_veterinario_id: datosRevision.centro_veterinario_id || '',
        observaciones: datosRevision.observaciones || '',
        costo: datosRevision.costo || null,
        medio_envio: datosRevision.medio_envio || '',
        diagnosticos_ids: datosRevision.diagnosticos_ids || []
      })
      
      diagnosticosSeleccionados.value = []
      
      // Cargar diagnósticos si existen
      if (datosRevision.diagnosticos_ids && Array.isArray(datosRevision.diagnosticos_ids) && datosRevision.diagnosticos_ids.length > 0) {
        revision.diagnosticos_ids = datosRevision.diagnosticos_ids
        await cargarDetallesDiagnosticos(datosRevision.diagnosticos_ids)
      } else if (datosRevision.diagnostico && datosRevision.diagnostico.trim() !== '') {
        const diagnosticosTexto = datosRevision.diagnostico.split(',').map(d => d.trim()).filter(d => d)
        
        diagnosticosSeleccionados.value = diagnosticosTexto.map((nombre, index) => ({
          id: null,
          nombre: nombre,
          tipo: 'manual',
          evolucion: 'aguda'
        }))
        
        revision.diagnosticos_ids = []
      }
      
    } else {
      console.warn('❌ No se encontraron datos de revisión:', result)
      alert('No se pudo cargar la revisión a editar: ' + (result.message || 'Error desconocido'))
      
      if (mascotaId.value) {
        router.push({
          name: 'veterinario-revisiones',
          params: { id: mascotaId.value }
        })
      }
    }
  } catch (error) {
    console.error('❌ Error cargando datos de revisión:', error)
    alert('Error al cargar la revisión: ' + error.message)
    
    if (mascotaId.value) {
      router.push({
        name: 'veterinario-revisiones',
        params: { id: mascotaId.value }
      })
    }
  }
}
</script>
<!-- registrarDiagnostico.vue con manejo de errores mejorado -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <!-- Título dinámico según modo -->
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Diagnóstico' : 'Registrar Diagnóstico' }}</h1>

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
                :class="{ 'border-red-500': erroresValidacion.tipo_diagnostico_id }"
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
            <div v-if="erroresValidacion.tipo_diagnostico_id" class="text-red-600 text-sm mt-1">
              {{ erroresValidacion.tipo_diagnostico_id[0] }}
            </div>
          </div>

          <div>
            <label class="block font-medium">Nombre del diagnóstico</label>
            <input 
              v-model="diagnostico.nombre" 
              type="text" 
              required 
              class="w-full border rounded p-2" 
              :class="{ 'border-red-500': erroresValidacion.nombre }"
              placeholder="Ej: Insuficiencia renal, parvovirus, etc."
              :disabled="cargandoDatos"
            />
            <div v-if="erroresValidacion.nombre" class="text-red-600 text-sm mt-1">
              {{ erroresValidacion.nombre[0] }}
            </div>
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">Centro Veterinario donde se realizó</label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="diagnostico.centro_veterinario_id"
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
            <div v-if="erroresValidacion.centro_veterinario_id" class="text-red-600 text-sm mt-1">
              {{ erroresValidacion.centro_veterinario_id[0] }}
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
              :class="{ 'border-red-500': erroresValidacion.fecha_diagnostico }"
              :disabled="cargandoDatos"
            />
            <div v-if="erroresValidacion.fecha_diagnostico" class="text-red-600 text-sm mt-1">
              {{ erroresValidacion.fecha_diagnostico[0] }}
            </div>
          </div>

          <div>
            <label class="block font-medium">Estado/evolución</label>
            <select 
              v-model="diagnostico.estado" 
              required 
              class="w-full border rounded p-2"
              :class="{ 'border-red-500': erroresValidacion.estado }"
              :disabled="cargandoDatos"
            >
              <option value="">Seleccione una opción</option>
              <option value="activo">Activo</option>
              <option value="resuelto">Resuelto</option>
              <option value="cronico">Crónico</option>
              <option value="seguimiento">En seguimiento</option>
              <option value="sospecha">Sospecha</option>
            </select>
            <div v-if="erroresValidacion.estado" class="text-red-600 text-sm mt-1">
              {{ erroresValidacion.estado[0] }}
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

      <div class="grid grid-cols-1 gap-8 mt-6">
        <div>
          <label class="block font-medium mb-1">Diagnósticos diferenciales considerados</label>
          <div class="flex gap-4">
            <textarea 
              v-model="diagnostico.diferenciales" 
              rows="3" 
              class="border rounded p-2 resize-none w-128" 
              :class="{ 'border-red-500': erroresValidacion.diagnosticos_diferenciales_seleccionados }"
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
          <div v-if="erroresValidacion.diagnosticos_diferenciales_seleccionados" class="text-red-600 text-sm mt-1">
            {{ erroresValidacion.diagnosticos_diferenciales_seleccionados[0] }}
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
          <div v-if="erroresValidacion.examenes" class="text-red-600 text-sm mt-1">
            {{ erroresValidacion.examenes[0] }}
          </div>
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
          <div v-if="erroresValidacion.conducta" class="text-red-600 text-sm mt-1">
            {{ erroresValidacion.conducta[0] }}
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
              :class="{ 'opacity-50': cargandoDatos, 'border-red-500': erroresValidacion.archivos }"
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
          <div v-if="erroresValidacion.archivos" class="text-red-600 text-sm mt-1">
            {{ erroresValidacion.archivos[0] }}
          </div>
        </div>
      </div>

      <!-- Selección del medio de envío -->
      <div class="mt-8">
        <CarruselMedioEnvio 
          v-if="usuarioId && !cargandoDatos" 
          :usuario-id="usuarioId" 
          :modo-edicion="esEdicion"
          :medio-seleccionado-inicial="diagnostico.medio_envio"
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
          <span class="font-semibold">{{ esEdicion ? 'Medio de envío utilizado:' : 'Medio seleccionado:' }}</span>
          <span class="ml-1 text-blue-600 font-medium">
            {{ obtenerNombreMedio(diagnostico.medio_envio) }}
          </span>
          <p v-if="esEdicion" class="text-sm text-gray-500 mt-1">
            (En modo edición el medio de envío no se puede cambiar)
          </p>
        </div>
        <div v-if="erroresValidacion.medio_envio" class="text-red-600 text-sm mt-1 text-center">
          {{ erroresValidacion.medio_envio[0] }}
        </div>
      </div>

      <!-- Sección de errores de validación -->
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
          :disabled="procesando"
        >
          Cancelar
        </button>
        <button
          type="button"
          @click="mostrarModalConfirmacion"
          :disabled="procesando || cargandoDatos || !formularioValido"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300 disabled:cursor-not-allowed"
        >
          {{ procesando ? 'Procesando...' : (esEdicion ? 'Actualizar Diagnóstico' : 'Registrar Diagnóstico') }}
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

    <!-- Modal de confirmación -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4">
          {{ esEdicion ? 'Confirmar Actualización' : 'Confirmar Registro' }}
        </h3>
        
        <div class="mb-6 max-h-60 overflow-y-auto">
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Tipo de diagnóstico:</span> {{ obtenerNombreTipoDiagnostico() }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Nombre:</span> {{ diagnostico.nombre }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Fecha diagnóstico:</span> {{ formatFecha(diagnostico.fecha_diagnostico) }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Estado:</span> {{ obtenerNombreEstado(diagnostico.estado) }}
          </p>
          <p v-if="diagnostico.centro_veterinario_id" class="text-gray-700 mb-2">
            <span class="font-semibold">Centro veterinario:</span> {{ obtenerNombreCentroSeleccionado() }}
          </p>
          <p v-if="diagnosticosSeleccionados.length > 0" class="text-gray-700 mb-2">
            <span class="font-semibold">Diagnósticos diferenciales:</span> 
            <span class="block text-sm mt-1 text-gray-600">
              {{ diagnosticosSeleccionados.map(d => d.nombre).join(', ') }}
            </span>
          </p>
          <p v-if="diagnostico.examenes" class="text-gray-700 mb-2">
            <span class="font-semibold">Exámenes complementarios:</span> 
            <span class="block text-sm mt-1 text-gray-600">
              {{ diagnostico.examenes }}
            </span>
          </p>
          <p v-if="diagnostico.conducta" class="text-gray-700 mb-2">
            <span class="font-semibold">Conducta terapéutica:</span> 
            <span class="block text-sm mt-1 text-gray-600">
              {{ diagnostico.conducta }}
            </span>
          </p>
          <p v-if="diagnostico.medio_envio" class="text-gray-700">
            <span class="font-semibold">Medio de envío:</span> {{ obtenerNombreMedio(diagnostico.medio_envio) }}
          </p>
          <p v-if="archivosAdjuntosCount > 0" class="text-gray-700 mt-2">
            <span class="font-semibold">Archivos adjuntos:</span> {{ archivosAdjuntosCount }} archivo(s)
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
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import DiagnosticosDiferenciales from '@/components/ElementosGraficos/DiagnosticosDiferenciales.vue'
import SeleccionCentroVeterinario from '@/components/ElementosGraficos/SeleccionCentroVeterinario.vue'
import CarruselMedioEnvio from '@/components/ElementosGraficos/CarruselMedioEnvio.vue'

const props = defineProps({
  diagnosticoId: {
    type: [String, Number],
    default: null
  }
})

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
const cargandoDatos = ref(true)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)

// Estados para el selector de diferenciales
const mostrarSelectorDiferenciales = ref(false)
const selectorDiferenciales = ref(null)
const diagnosticosSeleccionados = ref([])

// Estado para modal de confirmación
const mostrarModal = ref(false)

// Estados para manejo de errores (siguiendo el estándar de vacunas)
const erroresValidacion = ref({})
const mostrarErrores = ref(false)

// Determinar si es edición o registro
const esEdicion = computed(() => {
  return route.name === 'editarDiagnostico' || !!route.params.diagnosticoId || !!props.diagnosticoId
})

const diagnosticoId = computed(() => {
  return props.diagnosticoId || route.params.diagnosticoId || null
})

console.log('🔍 Es edición:', esEdicion.value)
console.log('🔍 Diagnóstico ID:', diagnosticoId.value)

// Computed para validación del formulario
const formularioValido = computed(() => {
  const camposObligatorios = diagnostico.tipo_diagnostico_id && 
    diagnostico.nombre && 
    diagnostico.fecha_diagnostico && 
    diagnostico.estado
  
  // Para registro, el medio de envío es obligatorio
  if (!esEdicion.value) {
    return camposObligatorios && diagnostico.medio_envio
  }
  
  // Para edición, solo los campos básicos son obligatorios
  return camposObligatorios
})

// Contador de archivos adjuntos
const archivosAdjuntosCount = computed(() => {
  return archivos.value.filter(archivo => archivo.archivo !== null).length
})

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

// Obtener nombre del estado
const obtenerNombreEstado = (estadoId) => {
  const estados = {
    activo: 'Activo',
    resuelto: 'Resuelto',
    cronico: 'Crónico',
    seguimiento: 'En seguimiento',
    sospecha: 'Sospecha'
  }
  return estados[estadoId] || estadoId
}

// Obtener nombre del tipo de diagnóstico
const obtenerNombreTipoDiagnostico = () => {
  const tipo = tiposDiagnostico.value.find(t => t.id == diagnostico.tipo_diagnostico_id)
  return tipo ? tipo.nombre : 'No seleccionado'
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

// Formatear fecha
const formatFecha = (fecha) => {
  if (!fecha) return 'No especificada'
  return new Date(fecha).toLocaleDateString('es-ES')
}

// Función mejorada para mostrar errores (siguiendo el estándar de vacunas)
const mostrarErrorValidacion = (error) => {
  mostrarErrores.value = true
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

// Limpiar errores
const limpiarErrores = () => {
  erroresValidacion.value = {}
  mostrarErrores.value = false
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
    centrosVeterinarios.value = []
  }
}

// Cargar datos de diagnóstico existente (para edición)
const cargarDiagnosticoExistente = async () => {
  if (!diagnosticoId.value || !mascotaId.value) return
  
  try {
    console.log('🔄 Cargando datos de diagnóstico con ID:', diagnosticoId.value)
    console.log('📍 URL completa:', `/api/mascotas/${mascotaId.value}/diagnosticos/${diagnosticoId.value}`)
    
    const response = await fetch(`/api/mascotas/${mascotaId.value}/diagnosticos/${diagnosticoId.value}`, {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Accept': 'application/json'
        }
      });

      console.log('📊 Response status:', response.status)
      console.log('📊 Response status text:', response.statusText)

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Respuesta completa de diagnóstico:', result)
    
    if (result.success && result.data) {
      const datosDiagnostico = result.data
      
      // DEBUG: Log detallado
      console.log('🔍 DEBUG - Estructura de datos recibida:', {
        centro_veterinario_id: datosDiagnostico.proceso_medico?.centro_veterinario_id,
        centro_veterinario_datos: datosDiagnostico.centro_veterinario,
        examenes_complementarios: datosDiagnostico.examenes_complementarios,
        conducta_terapeutica: datosDiagnostico.conducta_terapeutica,
        procedimiento_diagnosticos: datosDiagnostico.procedimiento_diagnosticos,
        diagnosticos_diferenciales: datosDiagnostico.diagnosticos_diferenciales
      })
      
      // Actualizar el objeto diagnostico con los datos existentes
      Object.assign(diagnostico, {
        tipo_diagnostico_id: datosDiagnostico.tipo_diagnostico_id,
        nombre: datosDiagnostico.nombre,
        fecha_diagnostico: datosDiagnostico.fecha_diagnostico?.split('T')[0] || '',
        estado: datosDiagnostico.estado,
        // CORRECCIÓN: Usar el ID del centro veterinario
        centro_veterinario_id: datosDiagnostico.centro_veterinario_id || 
                             datosDiagnostico.proceso_medico?.centro_veterinario_id || '',
        // CORRECCIÓN: Usar campos correctos
        examenes: datosDiagnostico.examenes_complementarios || '',
        conducta: datosDiagnostico.conducta_terapeutica || '',
        medio_envio: datosDiagnostico.medio_envio || '',
        observaciones: datosDiagnostico.observaciones || ''
      })
      
      // Cargar diagnósticos diferenciales
      diagnosticosSeleccionados.value = []
      
      // Opción 1: Usar procedimiento_diagnosticos
      if (datosDiagnostico.procedimiento_diagnosticos && 
          Array.isArray(datosDiagnostico.procedimiento_diagnosticos)) {
        datosDiagnostico.procedimiento_diagnosticos.forEach(pd => {
          if (pd.diagnostico_id) {
            diagnosticosSeleccionados.value.push({
              id: pd.diagnostico_id,
              nombre: pd.nombre_diagnostico || 'Sin nombre',
              relevancia: pd.relevancia
            })
          }
        })
      }
      
      // Opción 2: Usar diagnosticos_diferenciales (si existe)
      else if (datosDiagnostico.diagnosticos_diferenciales && 
               Array.isArray(datosDiagnostico.diagnosticos_diferenciales)) {
        datosDiagnostico.diagnosticos_diferenciales.forEach(d => {
          diagnosticosSeleccionados.value.push({
            id: d.id,
            nombre: d.nombre || 'Sin nombre'
          })
        })
      }
      
      actualizarTextareaDiferenciales()
      
      console.log('✅ Datos de diagnóstico cargados:', diagnostico)
      console.log('✅ Centro veterinario ID:', diagnostico.centro_veterinario_id)
      console.log('✅ Exámenes:', diagnostico.examenes)
      console.log('✅ Conducta:', diagnostico.conducta)
      console.log('✅ Diagnósticos diferenciales cargados:', diagnosticosSeleccionados.value)
    } else {
      console.warn('❌ No se encontraron datos de diagnóstico:', result)
      mostrarErrorValidacion({ message: 'No se pudo cargar el diagnóstico a editar: ' + (result.message || 'Error desconocido') })
      
      if (mascotaId.value) {
        router.push({
          name: 'veterinario-diagnosticos',
          params: { id: mascotaId.value }
        })
      }
    }
  } catch (error) {
    console.error('❌ Error cargando datos de diagnóstico:', error)
    console.error('🔍 Error details:', {
      message: error.message,
      name: error.name
    })
    mostrarErrorValidacion({ message: 'Error al cargar el diagnóstico: ' + error.message })
    
    if (mascotaId.value) {
      router.push({
        name: 'veterinario-diagnosticos',
        params: { id: mascotaId.value }
      })
    }
  }
}

const onTipoDiagnosticoChange = () => {
  const tipoSeleccionado = tiposDiagnostico.value.find(t => t.id == diagnostico.tipo_diagnostico_id)
  if (tipoSeleccionado) {
    console.log('Tipo seleccionado:', tipoSeleccionado)
  }
  // Limpiar error del campo cuando el usuario interactúa con él
  if (erroresValidacion.value.tipo_diagnostico_id) {
    delete erroresValidacion.value.tipo_diagnostico_id
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
  // Limpiar error del campo cuando se selecciona un centro
  if (erroresValidacion.value.centro_veterinario_id) {
    delete erroresValidacion.value.centro_veterinario_id
  }
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
  
  // Limpiar error del campo cuando se agrega un diagnóstico
  if (erroresValidacion.value.diagnosticos_diferenciales_seleccionados) {
    delete erroresValidacion.value.diagnosticos_diferenciales_seleccionados
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
    mostrarErrorValidacion({ message: 'No se puede registrar un nuevo tipo sin identificar la mascota' })
    return
  }
  
  const query = {
    from: esEdicion.value ? `/editar/diagnostico/${diagnosticoId.value}` : `/registro/diagnostico/${mascotaId.value}`,
    mascotaId: mascotaId.value
  }
  
  router.push({
    path: '/registro/registroTipoDiagnostico',
    query
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
  // Limpiar error del campo cuando se sube un archivo
  if (erroresValidacion.value.archivos) {
    delete erroresValidacion.value.archivos
  }
}

const activarInput = (index) => {
  inputsArchivo.value[index]?.click()
}

const quitarArchivo = (index) => {
  archivos.value[index].archivo = null
  archivos.value[index].preview = null
}

// Procesar formulario (solo muestra el modal de confirmación)
const procesarFormulario = () => {
  mostrarModalConfirmacion()
}

// Modal functions
const mostrarModalConfirmacion = () => {
  limpiarErrores()
  
  if (!formularioValido.value) {
    mostrarErrorValidacion({ message: 'Por favor complete todos los campos obligatorios' })
    return
  }
  
  mostrarModal.value = true
}

const cerrarModal = () => {
  mostrarModal.value = false
}

const confirmarAccion = () => {
  if (esEdicion.value) {
    actualizarDiagnostico()
  } else {
    registrarDiagnostico()
  }
}

// Registrar diagnóstico
const registrarDiagnostico = async () => {
  if (procesando.value || cargandoDatos.value) return

  try {
    procesando.value = true
    cerrarModal()
    limpiarErrores()

    console.log('📤 Enviando datos a servidor:', diagnostico)

    const formData = new FormData()
    
    // Preparar datos para enviar
    const datosEnvio = {
      ...diagnostico,
      mascota_id: mascotaId.value,
    }

    // Agregar diagnósticos diferenciales como JSON string
    if (diagnosticosSeleccionados.value.length > 0) {
      formData.append('diagnosticos_diferenciales_seleccionados', JSON.stringify(
        diagnosticosSeleccionados.value.map(d => ({
          id: d.id,
          nombre: d.nombre,
          relevancia: d.relevancia || 'media'
        }))
      ))
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

    // Manejar específicamente el error 422 (Validación)
    if (response.status === 422) {
      mostrarErrorValidacion({ response: { status: 422, data: result } })
      return
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      // Obtener el valor actual de mascotaId
      const currentMascotaId = mascotaId.value
      
      console.log('✅ Diagnóstico registrado, navegando con mascotaId:', currentMascotaId)
      
      // Mostrar mensaje de éxito incluyendo información del envío si existe
      let mensajeExito = '✅ Diagnóstico registrado exitosamente'
      if (result.data?.envio_exitoso === true) {
        mensajeExito += ' y certificado enviado'
      } else if (result.data?.envio_exitoso === false) {
        mensajeExito += ' (pero hubo un problema al enviar el certificado)'
      }
      
      alert(mensajeExito)
      
      // Usar .value para obtener el valor primitivo
      router.push({
        name: 'veterinario-diagnosticos',
        params: { id: currentMascotaId },
        query: {
          from: 'registroDiagnostico',
          currentTab: 'Clinico',
          ts: Date.now()
        }
      })
    } else {
      mostrarErrorValidacion({ message: result.message || 'Error al registrar el diagnóstico' })
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    mostrarErrorValidacion(error)
  } finally {
    procesando.value = false
  }
}

// Actualizar diagnóstico existente
const actualizarDiagnostico = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()
    limpiarErrores()

    console.log('📤 Actualizando diagnóstico con ID:', diagnosticoId.value, 'para mascota:', mascotaId.value)
    console.log('📤 Datos a enviar:', diagnostico)

    // Preparar datos para enviar - CORRECCIÓN: Usar JSON en lugar de FormData
    const datosEnvio = {
      ...diagnostico,
      mascota_id: mascotaId.value,
    }

    // Agregar diagnósticos diferenciales si existen
    if (diagnosticosSeleccionados.value.length > 0) {
      datosEnvio.diagnosticos_diferenciales_seleccionados = diagnosticosSeleccionados.value.map(d => ({
        id: d.id,
        nombre: d.nombre,
        relevancia: d.relevancia || 'media'
      }))
    }

    console.log('✅ Datos a enviar:', datosEnvio)
    console.log('✅ IDs de diagnósticos diferenciales:', diagnosticosSeleccionados.value.map(d => d.id))

    // CORRECCIÓN: Usar la ruta correcta con mascotaId y enviar como JSON
    const response = await fetch(`/api/mascotas/${mascotaId.value}/diagnosticos/${diagnosticoId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
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
      alert('✅ Diagnóstico actualizado exitosamente')
      
      const mascotaIdParaRedireccion = mascotaId.value || result.data?.mascota_id || result.data?.procesoMedico?.mascota_id
      
      if (mascotaIdParaRedireccion) {
        router.push({
          name: 'veterinario-diagnosticos',
          params: { id: mascotaIdParaRedireccion },
          query: {
            from: 'editarDiagnostico',
            currentTab: 'Clinico',
            ts: Date.now()
          }
        })
      } else {
        router.push({ name: 'veterinario-diagnosticos', params: { id: '0' } })
      }
    } else {
      mostrarErrorValidacion({ message: result.message || 'Error al actualizar el diagnóstico' })
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
      name: 'veterinario-diagnosticos',
      params: { id: mascotaIdParaRedireccion },
      query: {
        from: esEdicion.value ? 'cancelarEditarDiagnostico' : 'cancelarRegistroDiagnostico',
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

  // Si es edición, cargar datos del diagnóstico primero
  if (esEdicion.value) {
    await cargarDiagnosticoExistente()
  }

  try {
    // Cargar datos en paralelo para mayor eficiencia
    const promises = [
      cargarDatosMascota(),
      cargarTiposDiagnostico(),
      cargarCentrosVeterinarios()
    ]

    await Promise.allSettled(promises)
    
    // Establecer fecha actual como predeterminada solo si es registro nuevo y no hay fecha
    if (!esEdicion.value && !diagnostico.fecha_diagnostico) {
      const hoy = new Date().toISOString().split('T')[0]
      diagnostico.fecha_diagnostico = hoy
    }
    
    console.log('✅ Componente completamente cargado')
    console.log('👤 Usuario ID final:', usuarioId.value)
    console.log('📋 Tipos de diagnóstico cargados:', tiposDiagnostico.value.length)
    console.log('🏥 Centros veterinarios cargados:', centrosVeterinarios.value.length)
  } catch (error) {
    console.error('❌ Error durante la carga inicial:', error)
    mostrarErrorValidacion(error)
  } finally {
    cargandoDatos.value = false
  }
})
</script>
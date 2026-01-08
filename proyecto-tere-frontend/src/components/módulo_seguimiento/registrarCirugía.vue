<!-- registrarCirugía.vue -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Cirugía</h1>

    <form @submit.prevent="registrarCirugia" class="space-y-4">
      <!-- DATOS OBLIGATORIOS -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Obligatorios</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna izquierda -->
        <div class="space-y-4">
          <!-- Selección de Tipo de Cirugía -->
          <div>
            <label class="block font-medium">Tipo de cirugía realizada</label>
            <div class="flex gap-2">
              <select
                v-model="cirugia.tipo_cirugia_id"
                required
                class="w-full border rounded p-2"
                @change="onTipoCirugiaChange"
              >
                <option value="">Seleccione un tipo de cirugía</option>
                <option
                  v-for="tipo in tiposCirugia"
                  :key="tipo.id"
                  :value="tipo.id"
                >
                  {{ tipo.nombre }}
                </option>
              </select>

              <!-- Botón de + Tipo -->
              <button
                type="button"
                @click="abrirRegistroTipoCirugia"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo
              </button>
            </div>
          </div>

          <div>
            <label class="block font-medium">Fecha de la cirugía</label>
            <input v-model="cirugia.fecha" type="datetime-local" required class="w-full border rounded p-2" />
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">
              Centro Veterinario donde se realizó el procedimiento
            </label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="cirugia.centro_veterinario_id"
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

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Resultado inmediato</label>
            <select v-model="cirugia.resultado" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="satisfactorio">Satisfactorio</option>
              <option value="complicaciones">Complicaciones</option>
              <option value="estable">Estable</option>
              <option value="critico">Crítico</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Estado actual</label>
            <select v-model="cirugia.estado" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="recuperacion">En recuperación</option>
              <option value="alta">Alta postoperatoria</option>
              <option value="seguimiento">Bajo seguimiento</option>
              <option value="hospitalizado">Hospitalizado</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Diagnóstico o causa</label>
            <div class="space-y-2">
              <!-- Input para mostrar los diagnósticos seleccionados -->
              <div class="flex items-center gap-2">
                <input 
                  :value="diagnosticosSeleccionadosTexto" 
                  type="text" 
                  readonly
                  class="w-full border rounded p-2 bg-gray-50 cursor-default" 
                  placeholder="Seleccione uno o más diagnósticos" 
                />
                <button 
                  type="button"
                  @click="mostrarModalDiagnosticos = true"
                  class="bg-orange-500 text-white px-4 py-2 rounded font-bold hover:bg-orange-700 transition-colors whitespace-nowrap"
                >
                  + Asociar Diagnóstico
                </button>
              </div>
              
              <!-- Mostrar diagnósticos seleccionados como tags -->
              <div v-if="diagnosticosSeleccionados.length > 0" class="flex flex-wrap gap-2">
                <div 
                  v-for="diagnostico in diagnosticosSeleccionados" 
                  :key="diagnostico.id"
                  class="flex items-center gap-1 bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm"
                >
                  <span>{{ diagnostico.nombre }}</span>
                  <button 
                    type="button"
                    @click="eliminarDiagnostico(diagnostico.id)"
                    class="text-blue-600 hover:text-blue-800"
                  >
                    ×
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
        <div>
          <label class="block font-medium">Fecha estimada de control</label>
          <input v-model="cirugia.fecha_control" type="date" class="w-full border rounded p-2" />
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Medicación postquirúrgica</label>
          

          <!-- Fármacos asociados -->
          <div v-if="farmacosAsociados.length > 0" class="mb-6">
            <div class="flex items-center justify-between mb-3">
              <label class="block font-medium text-gray-700">Fármacos asociados:</label>
              <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                {{ farmacosAsociados.length }} fármaco(s)
              </span>
            </div>
            
            <div class="space-y-3">
              <div 
                v-for="(farmaco, index) in farmacosAsociados" 
                :key="farmaco.drug.id + '-' + index"
                class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm hover:shadow-md transition-shadow"
              >
                <div class="flex justify-between items-start mb-3">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <h4 class="font-bold text-gray-900">{{ farmaco.drug.nombre_comercial || farmaco.drug.name }}</h4>
                      <span class="px-2 py-1 text-xs font-semibold rounded-full capitalize"
                        :class="{
                          'bg-blue-100 text-blue-800': farmaco.drug.categoria === 'antibiotico',
                          'bg-green-100 text-green-800': farmaco.drug.categoria === 'analgesico',
                          'bg-purple-100 text-purple-800': farmaco.drug.categoria === 'antiinflamatorio',
                          'bg-red-100 text-red-800': farmaco.drug.categoria === 'anestesico',
                          'bg-yellow-100 text-yellow-800': farmaco.drug.categoria === 'sedante',
                          'bg-gray-100 text-gray-800': farmaco.drug.categoria === 'otro'
                        }"
                      >
                        {{ farmaco.drug.categoria_texto || farmaco.drug.categoria }}
                      </span>
                    </div>
                    <div class="text-sm text-gray-600 flex items-center gap-4">
                      <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">{{ farmaco.dose }} {{ farmaco.drug.unidad }}</span>
                      </span>
                      <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">{{ farmaco.frequency }}</span>
                      </span>
                    </div>
                  </div>
                  
                  <div class="flex items-center gap-2">
                    <!-- Selector de etapa de aplicación -->
                    <div class="relative">
                      <select 
                        v-model="farmaco.etapa_aplicacion"
                        @change="actualizarEtapaFarmaco(index, $event.target.value)"
                        class="appearance-none bg-white border border-gray-300 rounded-lg px-3 py-1.5 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors cursor-pointer hover:bg-gray-50"
                      >
                        <option value="">Etapa</option>
                        <option value="prequirurgica">Prequirúrgica</option>
                        <option value="transquirurgica">Transquirúrgica</option>
                        <option value="postquirurgica_inmediata">Postquirúrgica inmediata</option>
                        <option value="postquirurgica_tardia">Postquirúrgica tardía</option>
                      </select>
                      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                      </div>
                    </div>
                    
                    <!-- Indicador visual de la etapa seleccionada -->
                    <div 
                      v-if="farmaco.etapa_aplicacion"
                      class="px-2 py-1 text-xs font-medium rounded-full"
                      :class="{
                        'bg-blue-100 text-blue-800': farmaco.etapa_aplicacion === 'prequirurgica',
                        'bg-purple-100 text-purple-800': farmaco.etapa_aplicacion === 'transquirurgica',
                        'bg-green-100 text-green-800': farmaco.etapa_aplicacion === 'postquirurgica_inmediata',
                        'bg-yellow-100 text-yellow-800': farmaco.etapa_aplicacion === 'postquirurgica_tardia'
                      }"
                      :title="obtenerTextoEtapa(farmaco.etapa_aplicacion)"
                    >
                      {{ obtenerEtapaAbreviada(farmaco.etapa_aplicacion) }}
                    </div>
                    
                    <!-- Botón eliminar -->
                    <button 
                      @click="eliminarFarmaco(index)"
                      class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50 transition-colors"
                      type="button"
                      title="Eliminar fármaco"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
                
                <!-- Información adicional del fármaco -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-600 mb-2">
                  <div v-if="farmaco.drug.nombre_generico">
                    <span class="font-medium">Genérico:</span>
                    <span class="ml-1">{{ farmaco.drug.nombre_generico }}</span>
                  </div>
                  <div v-if="farmaco.duracion">
                    <span class="font-medium">Duración:</span>
                    <span class="ml-1">{{ farmaco.duracion }}</span>
                  </div>
                </div>
                
                <!-- Observaciones -->
                <div v-if="farmaco.notes" class="mt-2 pt-2 border-t border-gray-100">
                  <p class="text-sm text-gray-700">
                    <span class="font-medium text-gray-600">Observaciones:</span>
                    <span class="ml-2">{{ farmaco.notes }}</span>
                  </p>
                </div>
                
                <!-- Indicador de etapa aplicada (solo si está seleccionada) -->
                <div v-if="farmaco.etapa_aplicacion" class="mt-2 pt-2 border-t border-gray-100">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm text-gray-600">
                      <span class="font-medium">Aplicación:</span>
                      <span class="ml-1">{{ obtenerTextoEtapa(farmaco.etapa_aplicacion) }}</span>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Botón para abrir modal -->
          <div class="flex items-center gap-4">
            <button 
              type="button"
              @click="mostrarModalMedicacion = true"
              class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2.5 rounded-lg font-semibold hover:bg-red-700 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Asociar Fármaco
            </button>
            
            <div v-if="farmacosAsociados.length === 0" class="text-sm text-gray-500 italic">
              No se han asociado fármacos aún
            </div>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Descripción del procedimiento</label>
          <textarea v-model="cirugia.descripcion" rows="4" maxlength="1000" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ cirugia.descripcion?.length || 0 }}/1000 caracteres</p>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Recomendaciones al tutor</label>
          <textarea v-model="cirugia.recomendaciones" rows="3" maxlength="500" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ cirugia.recomendaciones?.length || 0 }}/500 caracteres</p>
        </div>

        <!-- Selección del medio de envío -->
        <div class="col-span-full mt-4">
          <CarruselMedioEnvio 
            v-if="usuarioId" 
            :usuario-id="usuarioId" 
            @update:medio="cirugia.medio_envio = $event" 
          />
          
          <div v-else class="text-center py-4">
            <p class="text-gray-500">Cargando información del dueño...</p>
          </div>

          <div v-if="cirugia.medio_envio" class="mt-4 text-center text-gray-700">
            <span class="font-semibold">Medio seleccionado:</span>
            <span class="ml-1 text-blue-600 font-medium">
              {{ obtenerNombreMedio(cirugia.medio_envio) }}
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
                <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-lg" />
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

      <div class="pt-4 flex items-center justify-center gap-4">
        <button
          type="button"
          @click="cancelar"
          class="bg-gray-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-gray-700 transition-colors"
        >
          Cancelar
        </button>
        <button 
          type="submit" 
          :disabled="procesando"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
        >
          {{ procesando ? 'Registrando...' : 'Registrar Cirugía' }}
        </button>
      </div>
    </form>

    <!-- Modal de selección de diagnósticos -->
    <SeleccionarDiagnosticoModal
      v-if="mostrarModalDiagnosticos"
      :mostrar="mostrarModalDiagnosticos"
      :mascota-id="mascotaId"
      :diagnosticos-iniciales="diagnosticosSeleccionados"
      @cerrar="mostrarModalDiagnosticos = false"
      @guardar="guardarDiagnosticos"
    />

    <!-- Componente externo del overlay de centros -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="cirugia.centro_veterinario_id"
      @cerrar="mostrarOverlayCentros = false"
      @seleccionar="seleccionarCentro"
    />

    <!-- Modal para selección de fármacos -->
    <AsociarFarmacoModal
      v-if="mostrarModalMedicacion"
      :mostrar="mostrarModalMedicacion"
      :mascota-id="mascotaId"
      @cerrar="mostrarModalMedicacion = false"
      @add="agregarFarmaco"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import SeleccionarDiagnosticoModal from '@/components/ElementosGraficos/SeleccionarDiagnostico.vue'
import SeleccionCentroVeterinario from '@/components/ElementosGraficos/SeleccionCentroVeterinario.vue'
import AsociarFarmacoModal from '@/components/ElementosGraficos/SeleccionarFarmaco.vue'
import CarruselMedioEnvio from '@/components/ElementosGraficos/CarruselMedioEnvio.vue'

const router = useRouter()
const route = useRoute()
const mascotaId = route.query.mascotaId

console.log('🔍 Route query:', route.query)
console.log('🔍 Mascota ID from query:', mascotaId)

const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Estados reactivos
const tiposCirugia = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const mostrarModalDiagnosticos = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)

// Diagnósticos seleccionados
const diagnosticosSeleccionados = ref([])

// Datos del formulario
const cirugia = reactive({
  tipo_cirugia_id: '',
  fecha: '',
  centro_veterinario_id: '',
  resultado: '',
  estado: '',
  fecha_control: '',
  descripcion: '',
  medicacion: '',
  recomendaciones: '',
  medio_envio: ''
})

// Estados reactivos
const mostrarModalMedicacion = ref(false)
const farmacosAsociados = ref([])

// Métodos para manejar las etapas de aplicación
const obtenerTextoEtapa = (etapa) => {
  const etapas = {
    'prequirurgica': 'Prequirúrgica',
    'transquirurgica': 'Transquirúrgica',
    'postquirurgica_inmediata': 'Postquirúrgica inmediata',
    'postquirurgica_tardia': 'Postquirúrgica tardía'
  }
  return etapas[etapa] || etapa
}

const obtenerEtapaAbreviada = (etapa) => {
  const abreviaciones = {
    'prequirurgica': 'Pre',
    'transquirurgica': 'Trans',
    'postquirurgica_inmediata': 'Post Inm.',
    'postquirurgica_tardia': 'Post Tar.'
  }
  return abreviaciones[etapa] || etapa
}

// Métodos para manejar fármacos
const agregarFarmaco = (farmacoData) => {
  // Agregar etapa por defecto si no viene
  const farmacoConEtapa = {
    ...farmacoData,
    etapa_aplicacion: '' // Dejar vacío para que el usuario seleccione
  }
  
  farmacosAsociados.value.push(farmacoConEtapa)
  
  // Actualizar campo de medicación
  actualizarCampoMedicacion()
}

const actualizarEtapaFarmaco = (index, nuevaEtapa) => {
  farmacosAsociados.value[index].etapa_aplicacion = nuevaEtapa
  
  // Actualizar automáticamente el campo de medicación
  actualizarCampoMedicacion()
}

const actualizarCampoMedicacion = () => {
  let medicacionText = ''
  
  farmacosAsociados.value.forEach(farmaco => {
    const etapa = farmaco.etapa_aplicacion ? ` [${obtenerEtapaAbreviada(farmaco.etapa_aplicacion)}]` : ''
    const farmacoText = `${farmaco.drug.nombre_comercial} - ${farmaco.dose} ${farmaco.drug.unidad}, ${farmaco.frequency}${etapa}`
    
    if (farmaco.notes) {
      medicacionText += `• ${farmacoText} (${farmaco.notes})\n`
    } else {
      medicacionText += `• ${farmacoText}\n`
    }
  })
  
  cirugia.medicacion = medicacionText.trim()
}

const eliminarFarmaco = (index) => {
  farmacosAsociados.value.splice(index, 1)
  
  // Actualizar campo de medicación
  actualizarCampoMedicacion()
}

// Validación para fármacos
const validarFarmacos = () => {
  const farmacosSinEtapa = farmacosAsociados.value.filter(f => !f.etapa_aplicacion)
  
  if (farmacosSinEtapa.length > 0) {
    return {
      valido: false,
      mensaje: `Hay ${farmacosSinEtapa.length} fármaco(s) sin etapa de aplicación seleccionada. Por favor, seleccione una etapa para cada fármaco.`
    }
  }
  
  return { valido: true }
}

// Debug auth (temporal)
const debugAuth = async () => {
  const { user, accessToken, isAuthenticated, checkAuth, fetchUser } = useAuth()
  
  console.log('🔍 DEBUG AUTH:')
  console.log('Token:', accessToken.value)
  console.log('isAuthenticated:', isAuthenticated.value)
  console.log('User:', user.value)
  console.log('LocalStorage Token:', localStorage.getItem('token'))
  console.log('LocalStorage User:', localStorage.getItem('user'))
  
  // Intentar recargar usuario
  try {
    console.log('🔄 Intentando checkAuth...')
    const authResult = await checkAuth()
    console.log('checkAuth result:', authResult)
    
    if (authResult && !user.value) {
      console.log('🔄 Forzando fetchUser...')
      await fetchUser()
      console.log('User después de fetchUser:', user.value)
    }
  } catch (err) {
    console.error('❌ Error en debug:', err)
  }
}

// Computed para mostrar en el input
const diagnosticosSeleccionadosTexto = computed(() => {
  if (diagnosticosSeleccionados.value.length === 0) {
    return ''
  } else if (diagnosticosSeleccionados.value.length === 1) {
    return diagnosticosSeleccionados.value[0].nombre
  } else {
    return `${diagnosticosSeleccionados.value.length} diagnósticos seleccionados`
  }
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
  const centro = centrosVeterinarios.value.find(c => c.id === cirugia.centro_veterinario_id)
  return centro ? centro.nombre : 'Centro no encontrado'
}

// Obtener dirección del centro seleccionado
const obtenerDireccionCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === cirugia.centro_veterinario_id)
  return centro ? centro.direccion : ''
}

// Cargar datos de la mascota para obtener el usuario_id
const cargarDatosMascota = async () => {
  try {
    console.log('🔄 Cargando datos de mascota con ID:', mascotaId)
    
    const response = await fetch(`/api/mascotas/${mascotaId}`, {
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

// Cargar tipos de cirugía
const cargarTiposCirugia = async () => {
  try {
    const response = await fetch('/api/tipos-cirugia', {
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
      tiposCirugia.value = result.data
      console.log('🔪 Tipos de cirugía cargados:', tiposCirugia.value.length)
    } else {
      console.warn('No se encontraron tipos de cirugía:', result)
      tiposCirugia.value = []
    }
  } catch (error) {
    console.error('Error cargando tipos de cirugía:', error)
    alert('Error al cargar los tipos de cirugía: ' + error.message)
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

const onTipoCirugiaChange = () => {
  const tipoSeleccionado = tiposCirugia.value.find(t => t.id == cirugia.tipo_cirugia_id)
  if (tipoSeleccionado) {
    console.log('Tipo seleccionado:', tipoSeleccionado)
  }
}

// Abrir overlay externo de centros
const abrirOverlayCentros = () => {
  mostrarOverlayCentros.value = true
}

// Seleccionar centro desde overlay
const seleccionarCentro = (centro) => {
  cirugia.centro_veterinario_id = centro.id
  mostrarOverlayCentros.value = false
}

// Métodos para manejar diagnósticos
const guardarDiagnosticos = (nuevosDiagnosticos) => {
  diagnosticosSeleccionados.value = nuevosDiagnosticos
}

const eliminarDiagnostico = (id) => {
  const index = diagnosticosSeleccionados.value.findIndex(d => d.id === id)
  if (index !== -1) {
    diagnosticosSeleccionados.value.splice(index, 1)
  }
}

// Navegar al registro de nuevo tipo
const abrirRegistroTipoCirugia = () => {
  router.push({
    path: '/registro/registroTipoCirugia',
    query: {
      from: `/mascotas/${mascotaId}/cirugias/crear`,
      mascotaId
    }
  })
}

// Registrar cirugía
const registrarCirugia = async () => {
  if (procesando.value) return

  try {
    procesando.value = true

    // Validar que se seleccionó un medio de envío
    if (!cirugia.medio_envio) {
      alert('Por favor seleccione un medio de envío para el informe')
      return
    }

    // Validar campos obligatorios
    if (!cirugia.tipo_cirugia_id || !cirugia.fecha || !cirugia.resultado || !cirugia.estado) {
      alert('Por favor complete todos los campos obligatorios')
      return
    }

    // Validar fármacos (etapas de aplicación)
    const validacionFarmacos = validarFarmacos()
    if (!validacionFarmacos.valido) {
      alert(validacionFarmacos.mensaje)
      return
    }

    // Preparar datos para enviar
    const datosCirugia = {
      ...cirugia,
      diagnosticos: diagnosticosSeleccionados.value.map(d => d.id),
      farmacos_asociados: farmacosAsociados.value.map(f => ({
        farmaco_id: f.drug.id,
        dosis: f.dose,
        frecuencia: f.frequency,
        duracion: f.duracion,
        observaciones: f.notes,
        etapa_aplicacion: f.etapa_aplicacion // Incluir la etapa de aplicación
      })),
      mascota_id: mascotaId
    }

    console.log('📤 Enviando datos a servidor:', datosCirugia)

    const response = await fetch(`/api/mascotas/${mascotaId}/cirugias`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosCirugia)
    })

    console.log('📨 Status:', response.status)
    
    const responseText = await response.text()
    console.log('📄 Respuesta cruda:', responseText)

    if (!responseText.trim()) {
      throw new Error('El servidor devolvió una respuesta vacía (posible redirección)')
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
      router.push({
        name: 'veterinario-cirugias',
        params: { id: mascotaId },
        query: {
          from: 'registroCirugia',
          currentTab: 'Cirugías',
          ts: Date.now()
        }
      })
    } else {
      alert('Error al registrar la cirugía: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al registrar la cirugía: ' + error.message)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  router.push({
    name: 'veterinario-cirugias',
    params: { id: mascotaId },
    query: {
      from: 'cancelarRegistroCirugia',
      currentTab: 'Cirugías',
      ts: Date.now()
    }
  })
}

// Archivos adjuntos (manteniendo la funcionalidad existente)
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

// Contador de caracteres
const actualizarContadorCaracteres = (campo, max) => {
  const valor = cirugia[campo] || ''
  if (valor.length > max) {
    cirugia[campo] = valor.substring(0, max)
  }
}

// Verificar autenticación y cargar datos
onMounted(async () => {
  console.log('🚀 Iniciando componente RegistrarCirugia')
  
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      router.push('/login')
      return
    }
  }

  // Cargar datos en orden
  await cargarDatosMascota() // Primero cargar datos de mascota para obtener usuario_id
  
  if (errorCargandoMascota.value) {
    console.error('❌ Error al cargar mascota:', errorCargandoMascota.value)
    alert('Error al cargar datos de la mascota: ' + errorCargandoMascota.value)
    return
  }

  await cargarTiposCirugia()
  await cargarCentrosVeterinarios()

  // Establecer fecha y hora actual como predeterminada
  const ahora = new Date()
  const offset = ahora.getTimezoneOffset()
  ahora.setMinutes(ahora.getMinutes() - offset)
  cirugia.fecha = ahora.toISOString().slice(0, 16)
  
  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
})
</script>
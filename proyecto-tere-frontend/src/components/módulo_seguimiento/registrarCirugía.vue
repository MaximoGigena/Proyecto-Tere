<!-- registrarCirugía.vue - Versión unificada con registro y edición -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Cirugía' : 'Registrar Cirugía' }}</h1>

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
            
              <!-- Mostrar diagnósticos seleccionados -->
              <div v-if="diagnosticosSeleccionados.length > 0" class="mt-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h4 class="font-bold text-blue-800 mb-2 flex items-center gap-2">
                  Diagnósticos asociados ({{ diagnosticosSeleccionados.length }})
                </h4>
                
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
                      @click="eliminarDiagnostico(diag.id || index)"
                      class="text-red-500 hover:text-red-700 ml-1"
                    >
                      ×
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
            :modo-edicion="esEdicion"
            :medio-seleccionado-inicial="cirugia.medio_envio"
            @update:medio="cirugia.medio_envio = $event" 
          />
          
          <div v-else class="text-center py-4">
            <p class="text-gray-500">Cargando información del dueño...</p>
          </div>

          <div v-if="cirugia.medio_envio" class="mt-4 text-center text-gray-700">
            <span class="font-semibold">
              {{ esEdicion ? 'Medio de envío utilizado:' : 'Medio seleccionado:' }}
            </span>
            <span class="ml-1 text-blue-600 font-medium">
              {{ obtenerNombreMedio(cirugia.medio_envio) }}
            </span>
            <p v-if="esEdicion" class="text-sm text-gray-500 mt-1">
              (En modo edición el medio de envío no se puede cambiar)
            </p>
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
          type="button"
          @click="mostrarModalConfirmacion"
          :disabled="procesando || !formularioValido"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
        >
          {{ procesando ? 'Procesando...' : (esEdicion ? 'Actualizar Cirugía' : 'Registrar Cirugía') }}
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

    <!-- Modal de confirmación -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4">
          {{ esEdicion ? 'Confirmar Actualización' : 'Confirmar Registro' }}
        </h3>
        
        <div class="mb-6">
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Tipo de cirugía:</span> {{ obtenerNombreTipoCirugia() }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Fecha:</span> {{ formatFechaHora(cirugia.fecha) }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Resultado:</span> {{ formatResultado(cirugia.resultado) }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Estado:</span> {{ formatEstado(cirugia.estado) }}
          </p>
          <p v-if="cirugia.centro_veterinario_id" class="text-gray-700 mb-2">
            <span class="font-semibold">Centro veterinario:</span> {{ obtenerNombreCentroSeleccionado() }}
          </p>
          <p v-if="diagnosticosSeleccionados.length > 0" class="text-gray-700 mb-2">
            <span class="font-semibold">Diagnósticos:</span> {{ diagnosticosSeleccionados.length }}
          </p>
          <p v-if="farmacosAsociados.length > 0" class="text-gray-700 mb-2">
            <span class="font-semibold">Fármacos asociados:</span> {{ farmacosAsociados.length }}
          </p>
          <p v-if="cirugia.fecha_control" class="text-gray-700 mb-2">
            <span class="font-semibold">Control:</span> {{ formatFecha(cirugia.fecha_control) }}
          </p>
          <p v-if="cirugia.medio_envio" class="text-gray-700">
            <span class="font-semibold">Medio de envío:</span> {{ obtenerNombreMedio(cirugia.medio_envio) }}
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
import SeleccionarDiagnosticoModal from '@/components/ElementosGraficos/SeleccionarDiagnostico.vue'
import SeleccionCentroVeterinario from '@/components/ElementosGraficos/SeleccionCentroVeterinario.vue'
import AsociarFarmacoModal from '@/components/ElementosGraficos/SeleccionarFarmaco.vue'
import CarruselMedioEnvio from '@/components/ElementosGraficos/CarruselMedioEnvio.vue'

// Definir props
const props = defineProps({
  cirugiaId: {
    type: [String, Number],
    default: null
  }
})

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Estados reactivos
const tiposCirugia = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const mostrarModalDiagnosticos = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)
const mostrarModal = ref(false)

// Determinar si es edición o registro
const esEdicion = computed(() => {
  return route.name === 'editarCirugia' || !!route.params.cirugiaId
})

const cirugiaId = computed(() => {
  return route.params.cirugiaId || null
})

const mascotaId = computed(() => {
  return route.query.mascotaId || route.params.mascotaId || null
})

console.log('🔍 Route params:', route.params)
console.log('🔍 Route query:', route.query)
console.log('🔍 Es edición:', esEdicion.value)
console.log('🔍 Cirugía ID:', cirugiaId.value)
console.log('🔍 Mascota ID:', mascotaId.value)

// Computed para validación del formulario
const formularioValido = computed(() => {
  const camposObligatorios = cirugia.tipo_cirugia_id && 
    cirugia.fecha && 
    cirugia.resultado && 
    cirugia.estado
    
  // Para registro, el medio de envío es obligatorio
  if (!esEdicion.value) {
    return camposObligatorios && cirugia.medio_envio
  }
  
  // Para edición, solo los campos básicos son obligatorios
  return camposObligatorios
})

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
  medio_envio: '',
  diagnosticos_ids: [] // ← AÑADE ESTO
})

// Estados reactivos
const mostrarModalMedicacion = ref(false)
const farmacosAsociados = ref([])

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

// Obtener nombre del tipo de cirugía
const obtenerNombreTipoCirugia = () => {
  const tipo = tiposCirugia.value.find(t => t.id == cirugia.tipo_cirugia_id)
  return tipo ? tipo.nombre : 'No seleccionado'
}

// Formatear fecha
const formatFecha = (fecha) => {
  if (!fecha) return 'No especificada'
  return new Date(fecha).toLocaleDateString('es-ES')
}

// Formatear fecha y hora
const formatFechaHora = (fechaHora) => {
  if (!fechaHora) return 'No especificada'
  const fecha = new Date(fechaHora)
  return fecha.toLocaleDateString('es-ES') + ' ' + fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
}

// Formatear resultado
const formatResultado = (resultado) => {
  const resultados = {
    satisfactorio: 'Satisfactorio',
    complicaciones: 'Complicaciones',
    estable: 'Estable',
    critico: 'Crítico'
  }
  return resultados[resultado] || resultado
}

// Formatear estado
const formatEstado = (estado) => {
  const estados = {
    recuperacion: 'En recuperación',
    alta: 'Alta postoperatoria',
    seguimiento: 'Bajo seguimiento',
    hospitalizado: 'Hospitalizado'
  }
  return estados[estado] || estado
}

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

// Cargar datos de cirugía existente para edición
// Cargar datos de cirugía existente para edición
const cargarCirugiaExistente = async () => {
  if (!cirugiaId.value) return
  
  try {
    console.log('🔄 Cargando datos de cirugía con ID:', cirugiaId.value)
    console.log('🔄 Mascota ID:', mascotaId.value)
    
    const response = await fetch(`/api/mascotas/${mascotaId.value}/cirugias/${cirugiaId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Respuesta completa de cirugía:', result)
    
    if (result.success && result.data) {
      const datosCirugia = result.data
      
      // Debug: ver estructura completa
      console.log('🔍 Estructura completa de datosCirugia:', datosCirugia)
      console.log('🔍 Keys disponibles:', Object.keys(datosCirugia))
      
      // Mapear los datos correctamente - los nombres del backend son diferentes
      Object.assign(cirugia, {
        tipo_cirugia_id: datosCirugia.tipo_cirugia_id,
        fecha: datosCirugia.fecha_cirugia ? 
               new Date(datosCirugia.fecha_cirugia).toISOString().slice(0, 16) : 
               '', // Para datetime-local
        centro_veterinario_id: datosCirugia.proceso_medico?.centro_veterinario_id || 
                              datosCirugia.centro_veterinario_id || 
                              datosCirugia.procesoMedico?.centro_veterinario_id,
        resultado: datosCirugia.resultado,
        estado: datosCirugia.estado_actual || datosCirugia.estado,
        fecha_control: datosCirugia.fecha_control_estimada?.split('T')[0] || '',
        descripcion: datosCirugia.descripcion_procedimiento || datosCirugia.descripcion || '',
        medicacion: datosCirugia.medicacion_postquirurgica || datosCirugia.medicacion || '',
        recomendaciones: datosCirugia.recomendaciones_tutor || datosCirugia.recomendaciones || '',
        medio_envio: datosCirugia.proceso_medico?.medio_envio || 
                    datosCirugia.medio_envio || 
                    datosCirugia.procesoMedico?.medio_envio || '',
      })
      
      // Debug: ver qué se cargó
      console.log('✅ Datos mapeados:', cirugia)
      
      // Cargar diagnósticos asociados
      // ✅ DIAGNÓSTICOS - ESTRATEGIA MEJORADA COMO EN REVISIONES
      // Limpiar array primero
      diagnosticosSeleccionados.value = []

      // ESTRATEGIA 1: Verificar si hay diagnósticos en diferentes propiedades
      let diagnosticosEncontrados = null

      // Buscar en diferentes propiedades posibles
      if (datosCirugia.diagnosticos && Array.isArray(datosCirugia.diagnosticos)) {
        diagnosticosEncontrados = datosCirugia.diagnosticos
      } else if (datosCirugia.diagnosticos_relacionados) {
        diagnosticosEncontrados = datosCirugia.diagnosticos_relacionados
      } else if (datosCirugia.diagnosticos_associados) {
        diagnosticosEncontrados = datosCirugia.diagnosticos_associados
      } else if (datosCirugia.diagnosticos_data) {
        diagnosticosEncontrados = datosCirugia.diagnosticos_data
      }

      if (diagnosticosEncontrados && Array.isArray(diagnosticosEncontrados) && diagnosticosEncontrados.length > 0) {
        console.log('🩺 Diagnosticos encontrados en propiedad:', diagnosticosEncontrados)
        
        // Mapear los diagnósticos al formato que espera el componente
        diagnosticosSeleccionados.value = diagnosticosEncontrados.map(d => ({
          id: d.id || d.diagnostico_id || null,
          nombre: d.nombre || d.diagnostico_nombre || 'Diagnóstico sin nombre',
          tipo: d.tipo || d.diagnostico_tipo || d.type || 'general',
          evolucion: d.evolucion || d.diagnostico_evolucion || 'aguda'
        }))
        
        console.log('✅ Diagnósticos mapeados:', diagnosticosSeleccionados.value)
        
      } else if (datosCirugia.diagnosticos_ids && Array.isArray(datosCirugia.diagnosticos_ids) && datosCirugia.diagnosticos_ids.length > 0) {
        // ✅ ESTRATEGIA 2: Si hay IDs pero no datos completos, cargar los detalles
        console.log('🆔 IDs de diagnósticos encontrados:', datosCirugia.diagnosticos_ids)
        
        // Cargar detalles de los diagnósticos por sus IDs
        await cargarDetallesDiagnosticos(datosCirugia.diagnosticos_ids)
        
      } else if (datosCirugia.diagnostico_causa && datosCirugia.diagnostico_causa.trim() !== '') {
        // ✅ ESTRATEGIA 3: Si hay texto en el campo diagnóstico/causa
        console.log('📝 Texto de diagnóstico/causa encontrado:', datosCirugia.diagnostico_causa)
        
        // Si el texto contiene comas, son múltiples diagnósticos
        const diagnosticosTexto = datosCirugia.diagnostico_causa.split(',').map(d => d.trim()).filter(d => d)
        
        diagnosticosSeleccionados.value = diagnosticosTexto.map((nombre, index) => ({
          id: null, // No tiene ID porque es texto libre
          nombre: nombre,
          tipo: 'manual',
          evolucion: 'aguda'
        }))
        
        console.log('✅ Diagnósticos creados desde texto:', diagnosticosSeleccionados.value)
      }

      console.log('🩺 Diagnósticos seleccionados finales:', diagnosticosSeleccionados.value)
      
      // Cargar fármacos asociados - CORREGIR la estructura de datos
      if (datosCirugia.farmacos_asociados && Array.isArray(datosCirugia.farmacos_asociados)) {
        farmacosAsociados.value = datosCirugia.farmacos_asociados.map(f => {
          console.log('🔍 Fármaco del backend:', f)
          
          return {
            drug: {
              id: f.tipo_farmaco_id || f.farmaco?.id || f.id,
              nombre_comercial: f.farmaco_nombre_comercial || 
                               f.farmaco?.nombre_comercial || 
                               f.nombre_comercial || 
                               f.nombre || 
                               'Fármaco',
              nombre_generico: f.farmaco_nombre_generico || 
                              f.farmaco?.nombre_generico || 
                              f.nombre_generico,
              categoria: f.farmaco?.categoria || f.categoria,
              unidad: f.unidad_dosis || f.unidad || 'mg'
            },
            dose: f.dosis_prescrita || f.dosis || f.dose || '0',
            frequency: f.frecuencia_completa || 
                       f.frecuencia || 
                       f.frequency || 
                       (f.frecuencia_valor && f.frecuencia_unidad ? 
                        `${f.frecuencia_valor} ${f.frecuencia_unidad}` : ''),
            duracion: f.duracion_completa || 
                      f.duracion || 
                      (f.duracion_valor && f.duracion_unidad ? 
                       `${f.duracion_valor} ${f.duracion_unidad}` : ''),
            notes: f.observaciones || f.notes || '',
            etapa_aplicacion: f.etapa_aplicacion || ''
          }
        })
        
        console.log('✅ Fármacos cargados (estructura):', farmacosAsociados.value)
        
        // Actualizar campo de medicación
        actualizarCampoMedicacion()
      } else {
        console.log('ℹ️ No hay fármacos asociados o estructura incorrecta:', datosCirugia.farmacos_asociados)
      }
      
      console.log('🎯 Estado final del formulario:', cirugia)
      
    } else {
      console.warn('❌ No se encontraron datos de cirugía:', result)
      alert('No se pudo cargar la cirugía a editar: ' + (result.message || 'Error desconocido'))
      
      // Redirigir a la página anterior
      if (mascotaId.value) {
        router.push({
          name: 'veterinario-cirugias',
          params: { id: mascotaId.value }
        })
      }
    }
  } catch (error) {
    console.error('❌ Error cargando datos de cirugía:', error)
    alert('Error al cargar la cirugía: ' + error.message)
    
    // Redirigir a la página anterior
    if (mascotaId.value) {
      router.push({
        name: 'veterinario-cirugias',
        params: { id: mascotaId.value }
      })
    }
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
  console.log('💾 Guardando diagnósticos seleccionados:', nuevosDiagnosticos)
  
  // Normalizar los datos para asegurar consistencia
  diagnosticosSeleccionados.value = nuevosDiagnosticos.map(d => ({
    id: d.id,
    nombre: d.nombre || d.diagnostico_nombre || 'Diagnóstico sin nombre',
    tipo: d.tipo || d.type || 'general',
    evolucion: d.evolucion || d.diagnostico_evolucion || 'aguda'
  }))
  
  console.log('✅ Diagnósticos guardados:', diagnosticosSeleccionados.value)
}

const eliminarDiagnostico = (idOrIndex) => {
  console.log('🗑️ Eliminando diagnóstico:', idOrIndex)
  
  let index = -1
  if (typeof idOrIndex === 'number') {
    // Si es un índice numérico
    index = idOrIndex
  } else {
    // Si es un ID
    index = diagnosticosSeleccionados.value.findIndex(d => d.id === idOrIndex)
  }
  
  if (index !== -1) {
    diagnosticosSeleccionados.value.splice(index, 1)
    console.log('✅ Diagnósticos actuales:', diagnosticosSeleccionados.value)
  }
}

// Navegar al registro de nuevo tipo
const abrirRegistroTipoCirugia = () => {
  const query = {
    from: esEdicion.value ? `/editar/cirugia/${cirugiaId.value}` : `/registro/cirugia/${mascotaId.value}`,
    mascotaId: mascotaId.value
  }
  
  router.push({
    path: '/registro/registroTipoCirugia',
    query
  })
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
    // Verificar que drug existe
    if (!farmaco.drug) {
      console.warn('⚠️ Fármaco sin estructura drug:', farmaco)
      return
    }
    
    const etapa = farmaco.etapa_aplicacion ? ` [${obtenerEtapaAbreviada(farmaco.etapa_aplicacion)}]` : ''
    const farmacoText = `${farmaco.drug.nombre_comercial || 'Fármaco'} - ${farmaco.dose} ${farmaco.drug.unidad || 'mg'}, ${farmaco.frequency}${etapa}`
    
    if (farmaco.notes) {
      medicacionText += `• ${farmacoText} (${farmaco.notes})\n`
    } else {
      medicacionText += `• ${farmacoText}\n`
    }
  })
  
  cirugia.medicacion = medicacionText.trim()
  console.log('📝 Medicación actualizada:', cirugia.medicacion)
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

// Mostrar modal de confirmación
const mostrarModalConfirmacion = () => {
  if (!formularioValido.value) {
    alert('Por favor complete todos los campos obligatorios')
    return
  }
  
  // Validar fármacos
  const validacionFarmacos = validarFarmacos()
  if (!validacionFarmacos.valido) {
    alert(validacionFarmacos.mensaje)
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
    actualizarCirugia()
  } else {
    registrarCirugia()
  }
}

// Procesar formulario (ahora solo muestra el modal)
const procesarFormulario = () => {
  mostrarModalConfirmacion()
}

// Registrar cirugía
const registrarCirugia = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()

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
      mascota_id: mascotaId.value
    }

    console.log('📤 Enviando datos a servidor:', datosCirugia)

    const response = await fetch(`/api/mascotas/${mascotaId.value}/cirugias`, {
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
      throw new Error('El servidor devolvió una respuesta vacía')
    }

    let result
    try {
      result = JSON.parse(responseText)
    } catch (parseError) {
      console.error('No se pudo parsear como JSON:', responseText)
      throw new Error('El servidor no devolvió JSON válido.')
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      alert('✅ Cirugía registrada exitosamente')
      router.push({
        name: 'veterinario-cirugias',
        params: { id: mascotaId.value },
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

// Actualizar cirugía existente
const actualizarCirugia = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()

    console.log('📤 Actualizando cirugía con ID:', cirugiaId.value)
    console.log('📤 Mascota ID:', mascotaId.value)
    console.log('📤 Datos a enviar:', cirugia)

    // Preparar datos para enviar
    const datosCirugia = {
      ...cirugia,
      medicacion: cirugia.medicacion, // Este campo ya existe en cirugia
      diagnosticos: diagnosticosSeleccionados.value.map(d => d.id),
      farmacos_asociados: farmacosAsociados.value.map(f => ({
        farmaco_id: f.drug.id,
        dosis: f.dose,
        frecuencia: f.frequency,
        duracion: f.duracion,
        observaciones: f.notes,
        etapa_aplicacion: f.etapa_aplicacion
      }))
    }

    // La ruta correcta es: /api/mascotas/{mascotaId}/cirugias/{cirugiaId}
    const response = await fetch(`/api/mascotas/${mascotaId.value}/cirugias/${cirugiaId.value}`, {
      method: 'PUT',
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
      throw new Error('El servidor devolvió una respuesta vacía')
    }

    let result
    try {
      result = JSON.parse(responseText)
    } catch (parseError) {
      console.error('No se pudo parsear como JSON:', responseText)
      throw new Error('El servidor no devolvió JSON válido.')
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      alert('✅ Cirugía actualizada exitosamente')
      
      const mascotaIdParaRedireccion = mascotaId.value || result.data?.mascota_id || result.data?.procesoMedico?.mascota_id
      
      if (mascotaIdParaRedireccion) {
        router.push({
          name: 'veterinario-cirugias',
          params: { id: mascotaIdParaRedireccion },
          query: {
            from: 'editarCirugia',
            currentTab: 'Cirugías',
            ts: Date.now()
          }
        })
      } else {
        router.push({ name: 'veterinario-cirugias', params: { id: '0' } })
      }
    } else {
      alert('Error al actualizar la cirugía: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al actualizar la cirugía: ' + error.message)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  const mascotaIdParaRedireccion = mascotaId.value
  
  if (mascotaIdParaRedireccion) {
    router.push({
      name: 'veterinario-cirugias',
      params: { id: mascotaIdParaRedireccion },
      query: {
        from: esEdicion.value ? 'cancelarEditarCirugia' : 'cancelarRegistroCirugia',
        currentTab: 'Cirugías',
        ts: Date.now()
      }
    })
  } else {
    router.push({ name: 'veterinario-cirugias', params: { id: '0' } })
  }
}

// Agrega esta función para cargar detalles de diagnósticos por sus IDs
const cargarDetallesDiagnosticos = async (diagnosticosIds) => {
  if (!diagnosticosIds || diagnosticosIds.length === 0) {
    console.log('ℹ️ No hay IDs de diagnósticos para cargar')
    return
  }
  
  try {
    console.log('🔍 Cargando detalles de diagnósticos con IDs:', diagnosticosIds)
    
    // Hacer una petición para obtener detalles de estos diagnósticos
    const response = await fetch(`/api/diagnosticos?ids=${diagnosticosIds.join(',')}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const result = await response.json()
      if (result.success && result.data && Array.isArray(result.data)) {
        console.log('📦 Datos de diagnósticos recibidos:', result.data)
        
        // Mapear los diagnósticos al formato esperado
        diagnosticosSeleccionados.value = result.data.map(d => ({
          id: d.id,
          nombre: d.nombre || 'Diagnóstico',
          tipo: d.tipo || 'general',
          evolucion: d.evolucion || 'aguda'
        }))
        
        console.log('✅ Detalles de diagnósticos cargados:', diagnosticosSeleccionados.value)
        
        // Actualizar el campo de texto con los nombres concatenados
        if (diagnosticosSeleccionados.value.length > 0) {
          cirugia.diagnostico_causa = diagnosticosSeleccionados.value.map(d => d.nombre).join(', ')
        }
      } else {
        console.warn('⚠️ No se pudieron cargar detalles de diagnósticos:', result)
        
        // Si no podemos cargar los detalles, al menos mostrar los IDs como placeholder
        diagnosticosSeleccionados.value = diagnosticosIds.map(id => ({
          id: id,
          nombre: `Diagnóstico #${id}`,
          tipo: 'desconocido',
          evolucion: 'aguda'
        }))
      }
    } else {
      console.warn('⚠️ Error al cargar detalles de diagnósticos:', response.status)
      
      // Crear placeholders con los IDs
      diagnosticosSeleccionados.value = diagnosticosIds.map(id => ({
        id: id,
        nombre: `Diagnóstico #${id}`,
        tipo: 'desconocido',
        evolucion: 'aguda'
      }))
    }
  } catch (error) {
    console.error('❌ Error cargando detalles de diagnósticos:', error)
    
    // Crear placeholders con los IDs
    diagnosticosSeleccionados.value = diagnosticosIds.map(id => ({
      id: id,
      nombre: `Diagnóstico #${id}`,
      tipo: 'desconocido',
      evolucion: 'aguda'
    }))
  }
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

// Verificar autenticación y cargar datos
onMounted(async () => {
  console.log('🚀 Iniciando componente RegistrarCirugia')

  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
  console.log('🩺 Diagnósticos seleccionados:', diagnosticosSeleccionados.value)
  console.log('📋 IDs de diagnósticos:', cirugia.diagnosticos_ids || [])
    
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      router.push('/login')
      return
    }
  }

  // Si es edición, cargar datos de la cirugía primero
  if (esEdicion.value) {
    await cargarCirugiaExistente()
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
    cargarTiposCirugia(),
    cargarCentrosVeterinarios()
  ])

  // Establecer fecha y hora actual como predeterminada solo si es registro nuevo y no hay fecha
  if (!esEdicion.value && !cirugia.fecha) {
    const ahora = new Date()
    const offset = ahora.getTimezoneOffset()
    ahora.setMinutes(ahora.getMinutes() - offset)
    cirugia.fecha = ahora.toISOString().slice(0, 16)
  }
  
  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
})

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
</script>
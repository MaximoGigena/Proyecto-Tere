<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Procedimiento Paliativo' : 'Registrar Procedimiento Paliativo' }}</h1>

    <form @submit.prevent="mostrarModalConfirmacion" class="space-y-4">
      <!-- DATOS OBLIGATORIOS -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Obligatorios</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna izquierda -->
        <div class="space-y-4">
          <!-- Selección de Tipo de Procedimiento Paliativo -->
          <div>
            <label class="block font-medium">Tipo de procedimiento paliativo</label>
            <div class="flex gap-2">
              <select
                v-model="paliativo.tipo_procedimiento_id"
                required
                class="w-full border rounded p-2"
                @change="onTipoPaliativoChange"
              >
                <option value="">Seleccione un tipo de procedimiento</option>
                  <option
                    v-for="tipo in tiposProcedimiento || []"
                    :key="tipo.id"
                    :value="tipo.id"
                  >
                    {{ tipo.nombre }}
                  </option>
              </select>

              <!-- Botón de + Tipo -->
              <button
                type="button"
                @click="abrirRegistroTipoPaliativo"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo
              </button>
            </div>
          </div>

          <div>
            <label class="block font-medium">Fecha de aplicación/inicio</label>
            <input v-model="paliativo.fecha_inicio" type="datetime-local" required class="w-full border rounded p-2" />
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">
              Centro Veterinario donde se realizó el procedimiento
            </label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="paliativo.centro_veterinario_id"
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
            <label class="block font-medium">Resultado/efecto observado</label>
            <select v-model="paliativo.resultado" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="mejoria">Mejoría evidente</option>
              <option value="alivio">Alivio parcial</option>
              <option value="estabilizacion">Estabilización</option>
              <option value="sin_cambio">Sin cambios</option>
              <option value="empeoramiento">Empeoramiento</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Estado de la mascota</label>
            <select v-model="paliativo.estado" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="estable">Estable</option>
              <option value="dolor_controlado">Con dolor controlado</option>
              <option value="dolor_parcial">Con dolor parcialmente controlado</option>
              <option value="deterioro">En deterioro</option>
              <option value="critico">Crítico</option>
            </select>
          </div>

          <!-- Diagnóstico base -->
          <div>
            <label class="block font-medium">Diagnóstico base</label>
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
        <!-- Frecuencia de seguimiento mejorada -->
        <div>
          <label class="block font-medium">Frecuencia de seguimiento</label>
          <div class="flex gap-2">
            <input 
              v-model="paliativo.frecuencia_valor" 
              type="number" 
              min="1" 
              class="w-1/3 border rounded-l p-2" 
              placeholder="Cantidad" 
            />
            <select 
              v-model="paliativo.frecuencia_unidad" 
              class="w-1/3 border rounded-r p-2"
            >
              <option value="horas">Horas</option>
              <option value="dias">Días</option>
              <option value="semanas">Semanas</option>
              <option value="meses">Meses</option>
            </select>
            <input 
              v-model="paliativo.fecha_control" 
              type="date" 
              class="w-1/3 border rounded p-2" 
              placeholder="Fecha de control"
            />
          </div>
        </div>

        <!-- Medicación complementaria con fármacos -->
        <div class="col-span-full">
          <label class="block font-medium mb-1">Medicación complementaria</label>
          
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

          <!-- Textarea para notas adicionales -->
          <div class="mt-4">
            <label class="block font-medium mb-1">Notas adicionales sobre medicación</label>
            <textarea 
              v-model="paliativo.medicacion_notas" 
              rows="2" 
              maxlength="500" 
              class="w-full border rounded p-2 resize-none"
              placeholder="Observaciones adicionales sobre la medicación..."
            ></textarea>
            <p class="text-sm text-gray-500 text-right mt-1">{{ paliativo.medicacion_notas?.length || 0 }}/500 caracteres</p>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Descripción del procedimiento</label>
          <textarea v-model="paliativo.descripcion" rows="4" maxlength="1000" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ paliativo.descripcion?.length || 0 }}/1000 caracteres</p>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Recomendaciones para el tutor</label>
          <textarea v-model="paliativo.recomendaciones" rows="3" maxlength="500" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ paliativo.recomendaciones?.length || 0 }}/500 caracteres</p>
        </div>

        <!-- Selección del medio de envío -->
        <div class="col-span-full mt-4">
          <CarruselMedioEnvio 
            v-if="usuarioId" 
            :usuario-id="usuarioId" 
            @update:medio="paliativo.medio_envio = $event" 
          />
          
          <div v-else class="text-center py-4">
            <p class="text-gray-500">Cargando información del dueño...</p>
          </div>

          <div v-if="paliativo.medio_envio" class="mt-4 text-center text-gray-700">
            <span class="font-semibold">Medio seleccionado:</span>
            <span class="ml-1 text-blue-600 font-medium">
              {{ obtenerNombreMedio(paliativo.medio_envio) }}
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
          :disabled="procesando || !formularioValido"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
        >
          {{ procesando ? 'Registrando...' : (esEdicion ? 'Actualizar Procedimiento' : 'Registrar Procedimiento') }}
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
      :centroSeleccionado="paliativo.centro_veterinario_id"
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

    <!-- Modal de confirmación (NUEVO) -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <h3 class="text-2xl font-bold mb-6 text-center text-gray-800">
          {{ esEdicion ? 'Confirmar Actualización' : 'Confirmar Registro' }} de Procedimiento Paliativo
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <!-- Columna izquierda -->
          <div class="space-y-3">
            <div>
              <span class="font-semibold text-gray-700">Tipo de procedimiento:</span>
              <p class="mt-1 text-gray-900">{{ obtenerNombreTipoProcedimiento() }}</p>
            </div>
            
            <div>
              <span class="font-semibold text-gray-700">Fecha aplicación/inicio:</span>
              <p class="mt-1 text-gray-900">{{ formatFecha(paliativo.fecha_inicio) }}</p>
            </div>
            
            <div v-if="paliativo.centro_veterinario_id">
              <span class="font-semibold text-gray-700">Centro veterinario:</span>
              <p class="mt-1 text-gray-900">{{ obtenerNombreCentroSeleccionado() }}</p>
              <p class="text-sm text-gray-600">{{ obtenerDireccionCentroSeleccionado() }}</p>
            </div>
            
            <div>
              <span class="font-semibold text-gray-700">Resultado/efecto:</span>
              <p class="mt-1 text-gray-900 capitalize">{{ obtenerTextoResultado() }}</p>
            </div>
            
            <div>
              <span class="font-semibold text-gray-700">Estado mascota:</span>
              <p class="mt-1 text-gray-900 capitalize">{{ obtenerTextoEstado() }}</p>
            </div>
          </div>

          <!-- Columna derecha -->
          <div class="space-y-3">
            <div v-if="diagnosticosSeleccionados.length > 0">
              <span class="font-semibold text-gray-700">Diagnóstico(s) base:</span>
              <div class="mt-1 flex flex-wrap gap-1">
                <span 
                  v-for="diagnostico in diagnosticosSeleccionados" 
                  :key="diagnostico.id"
                  class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-sm"
                >
                  {{ diagnostico.nombre }}
                </span>
              </div>
            </div>
            
            <div v-if="paliativo.frecuencia_valor">
              <span class="font-semibold text-gray-700">Frecuencia seguimiento:</span>
              <p class="mt-1 text-gray-900">
                {{ paliativo.frecuencia_valor }} {{ paliativo.frecuencia_unidad }}
              </p>
            </div>
            
            <div v-if="paliativo.fecha_control">
              <span class="font-semibold text-gray-700">Fecha de control:</span>
              <p class="mt-1 text-gray-900">{{ formatFecha(paliativo.fecha_control) }}</p>
            </div>
            
            <div v-if="farmacosAsociados.length > 0">
              <span class="font-semibold text-gray-700">Fármacos asociados:</span>
              <div class="mt-1 space-y-1">
                <div 
                  v-for="(farmaco, index) in farmacosAsociados" 
                  :key="index"
                  class="text-sm text-gray-700"
                >
                  • {{ farmaco.drug.nombre_comercial || farmaco.drug.name }} 
                  ({{ farmaco.dose }} {{ farmaco.drug.unidad }}, {{ farmaco.frequency }})
                </div>
              </div>
            </div>
            
            <div v-if="paliativo.medio_envio">
              <span class="font-semibold text-gray-700">Medio de envío:</span>
              <p class="mt-1 text-blue-600 font-medium">{{ obtenerNombreMedio(paliativo.medio_envio) }}</p>
            </div>
          </div>
        </div>

        <!-- Sección de descripción y recomendaciones -->
        <div v-if="paliativo.descripcion || paliativo.recomendaciones" class="mb-6 border-t pt-4">
          <div v-if="paliativo.descripcion" class="mb-3">
            <span class="font-semibold text-gray-700 block mb-1">Descripción del procedimiento:</span>
            <p class="text-gray-700 bg-gray-50 p-3 rounded">{{ paliativo.descripcion }}</p>
          </div>
          
          <div v-if="paliativo.recomendaciones">
            <span class="font-semibold text-gray-700 block mb-1">Recomendaciones para el tutor:</span>
            <p class="text-gray-700 bg-gray-50 p-3 rounded">{{ paliativo.recomendaciones }}</p>
          </div>
        </div>

        <!-- Archivos adjuntos -->
        <div v-if="archivosAdjuntosCount > 0" class="mb-6 border-t pt-4">
          <span class="font-semibold text-gray-700">Archivos adjuntos:</span>
          <p class="text-gray-600 text-sm mt-1">{{ archivosAdjuntosCount }} archivo(s)</p>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
          <button
            @click="cerrarModal"
            class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors"
          >
            Cancelar
          </button>
          <button
            @click="confirmarAccion"
            :disabled="procesando"
            class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:bg-blue-300"
          >
            {{ procesando ? (esEdicion ? 'Actualizando...' : 'Registrando...') : (esEdicion ? 'Confirmar Actualización' : 'Confirmar Registro') }}
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

const router = useRouter()
const route = useRoute()
const mascotaId = route.query.mascotaId

const props = defineProps({
  id: String // Recibe el parámetro de la ruta
})

console.log('🔍 Route query:', route.query)
console.log('🔍 Mascota ID from query:', mascotaId)

const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Determinar si es edición o registro
const esEdicion = computed(() => {
  return route.name === 'EditarPaliativo' || !!paliativoId.value
})

const paliativoId = computed(() => {
  return props.id || route.params.paliativoId || null
})

// Estados reactivos
const tiposProcedimiento = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const mostrarModalDiagnosticos = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)
const mostrarModal = ref(false) // NUEVO: estado para controlar el modal

// Diagnósticos seleccionados
const diagnosticosSeleccionados = ref([])

// Datos del formulario
const paliativo = reactive({
  tipo_procedimiento_id: '',
  fecha_inicio: '',
  centro_veterinario_id: '',
  diagnostico: '',
  resultado: '',
  estado: '',
  frecuencia_valor: '',
  frecuencia_unidad: 'dias',
  fecha_control: '',
  descripcion: '',
  medicacion_notas: '',
  recomendaciones: '',
  medio_envio: ''
})

// Estados reactivos para medicación
const mostrarModalMedicacion = ref(false)
const farmacosAsociados = ref([])

// Computed para validación del formulario
const formularioValido = computed(() => {
  const camposObligatorios = paliativo.tipo_procedimiento_id && 
    paliativo.fecha_inicio && 
    paliativo.resultado && 
    paliativo.estado
    
  // Para registro, el medio de envío es obligatorio
  if (!esEdicion.value) {
    return camposObligatorios && paliativo.medio_envio
  }
  
  // Para edición, solo los campos básicos son obligatorios
  return camposObligatorios
})

// Computed para contar archivos adjuntos
const archivosAdjuntosCount = computed(() => {
  return archivos.value.filter(a => a.archivo !== null).length
})

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

// Métodos para manejar fármacos
const agregarFarmaco = (farmacoData) => {
  farmacosAsociados.value.push(farmacoData)
  actualizarCampoMedicacion()
}

const actualizarCampoMedicacion = () => {
  let medicacionText = ''
  
  farmacosAsociados.value.forEach(farmaco => {
    const farmacoText = `${farmaco.drug.nombre_comercial} - ${farmaco.dose} ${farmaco.drug.unidad}, ${farmaco.frequency}`
    
    if (farmaco.notes) {
      medicacionText += `• ${farmacoText} (${farmaco.notes})\n`
    } else {
      medicacionText += `• ${farmacoText}\n`
    }
  })
  
  paliativo.medicacion_notas = medicacionText.trim()
}

const eliminarFarmaco = (index) => {
  farmacosAsociados.value.splice(index, 1)
  actualizarCampoMedicacion()
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

// Obtener nombre del tipo de procedimiento - verificado
const obtenerNombreTipoProcedimiento = () => {
  const tipo = tiposProcedimiento.value.find(t => t.id == paliativo.tipo_procedimiento_id)
  console.log('🔍 Buscando tipo procedimiento:', {
    id: paliativo.tipo_procedimiento_id,
    tipos: tiposProcedimiento.value,
    encontrado: tipo
  })
  return tipo ? tipo.nombre : 'No seleccionado'
}

// Obtener dirección del centro veterinario seleccionado
const obtenerDireccionCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === paliativo.centro_veterinario_id)
  console.log('🔍 Buscando dirección de centro veterinario:', {
    id: paliativo.centro_veterinario_id,
    centros: centrosVeterinarios.value,
    encontrado: centro
  })
  return centro ? centro.direccion : 'Dirección no disponible'
}

// Obtener centro veterinario - desde proceso_medico
const obtenerNombreCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === paliativo.centro_veterinario_id)
  console.log('🔍 Buscando centro veterinario:', {
    id: paliativo.centro_veterinario_id,
    centros: centrosVeterinarios.value,
    encontrado: centro
  })
  return centro ? centro.nombre : 'Centro no encontrado'
}


// Obtener texto del resultado
const obtenerTextoResultado = () => {
  const opciones = {
    'mejoria': 'Mejoría evidente',
    'alivio': 'Alivio parcial',
    'estabilizacion': 'Estabilización',
    'sin_cambio': 'Sin cambios',
    'empeoramiento': 'Empeoramiento'
  }
  return opciones[paliativo.resultado] || paliativo.resultado
}

// Obtener texto del estado
const obtenerTextoEstado = () => {
  const opciones = {
    'estable': 'Estable',
    'dolor_controlado': 'Con dolor controlado',
    'dolor_parcial': 'Con dolor parcialmente controlado',
    'deterioro': 'En deterioro',
    'critico': 'Crítico'
  }
  return opciones[paliativo.estado] || paliativo.estado
}

// Formatear fecha
const formatFecha = (fecha) => {
  if (!fecha) return 'No especificada'
  
  // Si es una fecha con hora (datetime-local)
  if (fecha.includes('T')) {
    const [datePart, timePart] = fecha.split('T')
    const time = timePart ? timePart.substring(0, 5) : ''
    const date = new Date(datePart + 'T' + timePart)
    return date.toLocaleDateString('es-ES') + (time ? ` ${time}` : '')
  }
  
  // Si es solo fecha (date)
  return new Date(fecha).toLocaleDateString('es-ES')
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



// Cargar tipos de procedimiento paliativo
const cargarTiposProcedimiento = async () => {
  try {
    console.log('🔄 Cargando tipos de procedimiento paliativo...')
    
    const response = await fetch('/api/tipos-procedimiento-paliativo', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })
    
    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Resultado completo:', result)
    
    // Manejar diferentes estructuras de respuesta
    let datosArray = []
    
    // Caso 1: Respuesta con paginación (Laravel paginate())
    if (result.success && result.data && result.data.data && Array.isArray(result.data.data)) {
      datosArray = result.data.data
      console.log('📊 Usando datos de paginación')
    }
    // Caso 2: Respuesta directa sin paginación
    else if (result.success && result.data && Array.isArray(result.data)) {
      datosArray = result.data
      console.log('📊 Usando datos directos')
    }
    // Caso 3: Respuesta es directamente un array
    else if (Array.isArray(result)) {
      datosArray = result
      console.log('📊 Respuesta es array directo')
    }
    // Caso 4: Respuesta inesperada, usar vacío
    else {
      console.warn('❌ Formato de respuesta no reconocido:', result)
      datosArray = []
    }
    
    tiposProcedimiento.value = datosArray
    console.log('✅ Tipos de procedimiento cargados:', tiposProcedimiento.value.length)
    
  } catch (error) {
    console.error('❌ Error cargando tipos de procedimiento:', error)
    tiposProcedimiento.value = []
    
    // Datos de ejemplo para desarrollo
    if (import.meta.env.DEV) {
      tiposProcedimiento.value = [
        { id: 1, nombre: 'Control de dolor crónico' },
        { id: 2, nombre: 'Soporte respiratorio' },
        { id: 3, nombre: 'Cuidados de heridas' },
        { id: 4, nombre: 'Soporte nutricional' },
        { id: 5, nombre: 'Terapia física' },
        { id: 6, nombre: 'Cuidados paliativos oncológicos' }
      ]
    }
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

// Cargar datos de procedimiento existente (para edición)
const cargarPaliativoExistente = async () => {
  if (!paliativoId.value || !mascotaId) return
  
  try {
    console.log('🔄 Cargando datos de procedimiento paliativo con ID:', paliativoId.value)
    console.log('📌 Mascota ID:', mascotaId)
    console.log('🔄 Cargando datos de procedimiento paliativo con ID:', paliativoId.value)
    console.log('📌 Mascota ID:', mascotaId)
    console.log('🔗 URL completa:', `/api/mascotas/${mascotaId}/paliativos/${paliativoId.value}`)
    console.log('🔐 Token disponible:', !!accessToken.value)
    
    
    const response = await fetch(`/api/mascotas/${mascotaId}/paliativos/${paliativoId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Respuesta COMPLETA de procedimiento paliativo:', JSON.stringify(result, null, 2))
    
    if (result.success && result.data) {
      const datosPaliativo = result.data
      
      // VERIFICAR la estructura de los datos recibidos
      console.log('🔍 Estructura de datos recibidos:', {
        datosPaliativo,
        tieneTipoPaliativo: datosPaliativo.tipo_paliativo,
        tieneProcesoMedico: datosPaliativo.proceso_medico,
        tieneCentroVeterinario: datosPaliativo.proceso_medico?.centro_veterinario,
        tieneDiagnosticos: datosPaliativo.diagnosticos_asociados,
        tieneEstado: datosPaliativo.estado_mascota
      })
      
      // Actualizar el objeto paliativo con los datos existentes
      // IMPORTANTE: Usar los nombres correctos de campos
      Object.assign(paliativo, {
        tipo_procedimiento_id: datosPaliativo.tipo_paliativo_id || datosPaliativo.tipo_procedimiento_id,
        fecha_inicio: datosPaliativo.fecha_inicio ? 
          datosPaliativo.fecha_inicio.replace('T', ' ').substring(0, 16) : '',
        // El centro veterinario está en proceso_medico, no directamente en paliativo
        centro_veterinario_id: datosPaliativo.proceso_medico?.centro_veterinario_id || datosPaliativo.centro_veterinario_id,
        resultado: datosPaliativo.resultado,
        estado: datosPaliativo.estado_mascota || datosPaliativo.estado, // ❗ Importante: usar estado_mascota
        frecuencia_valor: datosPaliativo.frecuencia_valor || '',
        frecuencia_unidad: datosPaliativo.frecuencia_unidad || 'dias',
        fecha_control: datosPaliativo.fecha_control?.split('T')[0] || '',
        descripcion: datosPaliativo.observaciones || datosPaliativo.descripcion || '',
        medicacion_notas: datosPaliativo.medicacion_complementaria || datosPaliativo.medicacion_notas || '',
        recomendaciones: datosPaliativo.recomendaciones_tutor || datosPaliativo.recomendaciones || '',
        medio_envio: datosPaliativo.medio_envio || '',
      })
      
      console.log('✅ Paliativo cargado en formulario:', paliativo)
      
      // Cargar diagnósticos asociados
      if (datosPaliativo.diagnosticos_asociados && Array.isArray(datosPaliativo.diagnosticos_asociados)) {
        // Transformar la estructura si es necesario
        diagnosticosSeleccionados.value = datosPaliativo.diagnosticos_asociados.map(d => ({
          id: d.diagnostico_id || d.id,
          nombre: d.nombre || d.diagnostico_nombre,
          type: d.diagnostico_type || 'tipo' // Ajustar según tu estructura
        }))
        console.log('✅ Diagnosticos cargados:', diagnosticosSeleccionados.value)
      } else {
        console.log('⚠️ No hay diagnósticos asociados en la respuesta')
      }
      
      // Cargar fármacos asociados
      if (datosPaliativo.farmacos_asociados && Array.isArray(datosPaliativo.farmacos_asociados)) {
        farmacosAsociados.value = datosPaliativo.farmacos_asociados
        actualizarCampoMedicacion()
        console.log('✅ Fármacos cargados:', farmacosAsociados.value.length)
      }
      
    } else {
      console.warn('❌ No se encontraron datos de procedimiento:', result)
      alert('No se pudo cargar el procedimiento a editar: ' + (result.message || 'Error desconocido'))
    }
  } catch (error) {
    console.error('❌ Error cargando datos de procedimiento:', error)
    alert('Error al cargar el procedimiento: ' + error.message)
  }
}

const onTipoPaliativoChange = () => {
  const tipoSeleccionado = tiposProcedimiento.value.find(t => t.id == paliativo.tipo_procedimiento_id)
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
  paliativo.centro_veterinario_id = centro.id
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
const abrirRegistroTipoPaliativo = () => {
  router.push({
    path: '/registro/registroTipoPaliativo',
    query: {
      from: esEdicion.value ? `/editar/paliativo/${paliativoId.value}` : `/registro/paliativo/${mascotaId}`,
      mascotaId
    }
  })
}

// Función para mostrar el modal de confirmación
const mostrarModalConfirmacion = () => {
  if (!formularioValido.value) {
    alert('Por favor complete todos los campos obligatorios')
    return
  }
  
  mostrarModal.value = true
}

// Función para cerrar el modal
const cerrarModal = () => {
  mostrarModal.value = false
}

// Confirmar acción (registrar o actualizar)
const confirmarAccion = () => {
  if (esEdicion.value) {
    actualizarPaliativo()
  } else {
    registrarPaliativo()
  }
}

// Registrar procedimiento paliativo
const registrarPaliativo = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()

    // Validar que se seleccionó un medio de envío
    if (!paliativo.medio_envio) {
      alert('Por favor seleccione un medio de envío para el informe')
      procesando.value = false
      return
    }

    // Preparar datos para enviar
    const datosPaliativo = {
      ...paliativo,
      diagnosticos: diagnosticosSeleccionados.value.map(d => ({
        id: d.id,
        type: d.type === 'pet' 
          ? 'App\\Models\\ProcedimientosMedicos\\Diagnostico'
          : 'App\\Models\\TiposProcedimientos\\TipoDiagnostico'
      })),
      farmacos_asociados: farmacosAsociados.value.map(f => ({
        farmaco_id: f.drug.id,
        dosis: f.dose,
        frecuencia: f.frequency,
        duracion: f.duracion,
        observaciones: f.notes,
        momento_aplicacion: 'mantenimiento'
      })),
      mascota_id: mascotaId,
      frecuencia_seguimiento: paliativo.frecuencia_valor ? 
        `${paliativo.frecuencia_valor} ${paliativo.frecuencia_unidad}` : null
    }

    console.log('📤 Enviando datos a servidor:', datosPaliativo)

    const response = await fetch(`/api/mascotas/${mascotaId}/paliativos`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosPaliativo)
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
        name: 'veterinario-paliativos',
        params: { id: mascotaId },
        query: {
          from: 'registroPaliativo',
          currentTab: 'Paliativos',
          ts: Date.now()
        }
      })
    } else {
      alert('Error al registrar el procedimiento paliativo: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al registrar el procedimiento paliativo: ' + error.message)
  } finally {
    procesando.value = false
  }
}

// Actualizar procedimiento paliativo existente
const actualizarPaliativo = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()

    console.log('📤 Actualizando procedimiento con ID:', paliativoId.value)
    console.log('🔍 Diagnosticos seleccionados:', diagnosticosSeleccionados.value)
    
    // Preparar datos para enviar - Asegurarse de incluir siempre los diagnósticos
    const datosPaliativo = {
      ...paliativo,
      diagnosticos: diagnosticosSeleccionados.value.map(d => ({
        id: d.id,
        type: d.type === 'pet' 
          ? 'App\\Models\\ProcedimientosMedicos\\Diagnostico'
          : 'App\\Models\\TiposProcedimientos\\TipoDiagnostico'
      })),
      farmacos_asociados: farmacosAsociados.value.map(f => ({
        farmaco_id: f.drug.id,
        dosis: f.dose,
        frecuencia: f.frequency,
        duracion: f.duracion,
        observaciones: f.notes,
        momento_aplicacion: 'mantenimiento'
      })),
      frecuencia_seguimiento: paliativo.frecuencia_valor ? 
        `${paliativo.frecuencia_valor} ${paliativo.frecuencia_unidad}` : null
    }

    console.log('📤 Datos a enviar (incluyendo diagnosticos):', datosPaliativo)

    const response = await fetch(`/api/mascotas/${mascotaId}/paliativos/${paliativoId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosPaliativo)
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
      alert('✅ Procedimiento paliativo actualizado exitosamente')
      
      // Redirigir después de actualizar
      if (mascotaId) {
        router.push({
          name: 'veterinario-paliativos',
          params: { id: mascotaId },
          query: {
            from: 'editarPaliativo',
            currentTab: 'Paliativos',
            ts: Date.now()
          }
        })
      } else {
        router.push({ name: 'veterinario-paliativos', params: { id: '0' } })
      }
    } else {
      alert('Error al actualizar el procedimiento: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al actualizar el procedimiento: ' + error.message)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  const mascotaIdParaRedireccion = mascotaId
  
  if (mascotaIdParaRedireccion) {
    router.push({
      name: 'veterinario-paliativos',
      params: { id: mascotaIdParaRedireccion },
      query: {
        from: esEdicion.value ? 'cancelarEditarPaliativo' : 'cancelarRegistroPaliativo',
        currentTab: 'Paliativos',
        ts: Date.now()
      }
    })
  } else {
    router.push({ name: 'veterinario-paliativos', params: { id: '0' } })
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
  console.log('🚀 Iniciando componente RegistrarPaliativo')
  console.log('🔍 Es edición:', esEdicion.value)
  console.log('🔍 Paliativo ID:', paliativoId.value)
  
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      router.push('/login')
      return
    }
  }

  // Si es edición, cargar datos del procedimiento primero
  if (esEdicion.value) {
    await cargarPaliativoExistente()
  }

  // Cargar datos en orden
  if (mascotaId) {
    await cargarDatosMascota()
    
    if (errorCargandoMascota.value) {
      console.error('❌ Error al cargar mascota:', errorCargandoMascota.value)
      alert('Error al cargar datos de la mascota: ' + errorCargandoMascota.value)
      return
    }
  }

  await Promise.all([
    cargarTiposProcedimiento(),
    cargarCentrosVeterinarios()
  ])

  // Establecer fecha y hora actual como predeterminada solo si es registro nuevo y no hay fecha
  if (!esEdicion.value && !paliativo.fecha_inicio) {
    const ahora = new Date()
    const offset = ahora.getTimezoneOffset()
    ahora.setMinutes(ahora.getMinutes() - offset)
    paliativo.fecha_inicio = ahora.toISOString().slice(0, 16)
  }
  
  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
})
</script>
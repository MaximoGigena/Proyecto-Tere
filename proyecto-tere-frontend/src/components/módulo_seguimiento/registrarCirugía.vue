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
          <div>
            <label class="block font-medium">Tipo de cirugía realizada</label>
           <div class="flex gap-2">
              <!-- Contenedor relativo para el input con ícono -->
              <div class="relative w-full">
                <input 
                  v-model="cirugia.tipo" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Castración, Ortopédica, extirpación de tumor, etc."
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>

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

          <div class="flex gap-2 items-center mb-1">
            <label class="block font-medium mb-1">Centro Veterinario donde se realizo el procedimiento</label>
            <button 
                  type="button"
                  class="bg-green-500 text-white text-xl px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap"
                >
                  + Centro
            </button>
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
          <input v-model="cirugia.fechaControl" type="date" class="w-full border rounded p-2" />
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Descripción del procedimiento</label>
          <textarea v-model="cirugia.descripcion" rows="4" maxlength="1000" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ cirugia.descripcion.length }}/1000 caracteres</p>
        </div>        

        <div class="col-span-full">
      <label class="block font-medium mb-1">Medicación postquirúrgica</label>
      
      <div class="flex gap-4 items-center">
        <textarea 
          v-model="cirugia.medicacion" 
          rows="3" 
          class="w-64 border rounded p-2 resize-none" 
          placeholder="Indique la medicación postquirúrgica.">
        </textarea>
        
        <button 
          type="button"
          class="bg-red-500 text-white px-4 py-2 rounded font-bold hover:bg-red-700 transition-colors whitespace-nowrap">
          + Asociar Medicación
        </button>
      </div>
  </div>



        <div class="col-span-full">
          <label class="block font-medium mb-1">Recomendaciones al tutor</label>
          <textarea v-model="cirugia.recomendaciones" rows="3" maxlength="500" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ cirugia.recomendaciones.length }}/500 caracteres</p>
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
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors">Registrar Cirugía</button>
      </div>
    </form>
  </div>

  <!-- Modal de selección de diagnósticos -->
  <SeleccionarDiagnosticoModal
    v-if="mostrarModalDiagnosticos"
    :diagnosticos-iniciales="diagnosticosSeleccionados"
    @cerrar="mostrarModalDiagnosticos = false"
    @guardar="guardarDiagnosticos"
  />
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import SeleccionarDiagnosticoModal from '@/components/ElementosGraficos/SeleccionarDiagnostico.vue'

const router = useRouter()
const route = useRoute()

// Estado del modal
const mostrarModalDiagnosticos = ref(false)

// Diagnósticos seleccionados
const diagnosticosSeleccionados = ref([])

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

// Métodos para manejar diagnósticos
const guardarDiagnosticos = (nuevosDiagnosticos) => {
  diagnosticosSeleccionados.value = nuevosDiagnosticos
  // También puedes actualizar el campo de cirugia.diagnostico si lo necesitas
  if (nuevosDiagnosticos.length > 0) {
    cirugia.diagnostico = nuevosDiagnosticos.map(d => d.nombre).join(', ')
  } else {
    cirugia.diagnostico = ''
  }
}

const eliminarDiagnostico = (id) => {
  const index = diagnosticosSeleccionados.value.findIndex(d => d.id === id)
  if (index !== -1) {
    diagnosticosSeleccionados.value.splice(index, 1)
  }
}

// Resto del código original (sin cambios)...
const abrirRegistroTipoCirugia = () => {
  router.push({
    path: '/registro/registroTipoCirugia',
    query: {
      from: '/historialClinico/cirugias/registro/cirugia'
    }
  });
};

const cirugia = reactive({
  tipo: '',
  fecha: '',
  diagnostico: '',
  centro: '',
  resultado: '',
  estado: '',
  fechaControl: '',
  descripcion: '',
  observaciones: '',
  medicacion: '',
  recomendaciones: ''
})

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

const registrarCirugia = () => {
  const formData = new FormData()
  for (const campo in cirugia) {
    if (cirugia[campo] !== null) {
      formData.append(campo, cirugia[campo])
    }
  }

  archivos.value.forEach((archivo, i) => {
    if (archivo.archivo) {
      formData.append(`archivo${i + 1}`, archivo.archivo)
    }
  })
  
  console.log('Datos a enviar:', formData)
  // Aquí iría la lógica para enviar los datos al servidor
}
</script>
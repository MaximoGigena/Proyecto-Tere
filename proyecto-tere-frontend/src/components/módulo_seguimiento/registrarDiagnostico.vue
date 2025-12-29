<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Diagnóstico</h1>

    <form @submit.prevent="registrarDiagnostico" class="space-y-4">
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
            <label class="block font-medium mb-1">Tipo de Diagnostico</label>
            <div class="flex gap-2">
              <!-- Contenedor relativo para el input con ícono -->
              <div class="relative w-full">
                <input 
                  v-model="diagnostico.tipoOtro" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Condición, Enfermedad, Sindrome, etc."
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>

              <!-- Botón de + Tipo -->
              <button 
                type="button"
                @click="abrirRegistroTipoDiagnostico"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo 
              </button>
            </div>
          </div>

          <div>
            <label class="block font-medium">Nombre del diagnóstico</label>
            <input v-model="diagnostico.nombre" type="text" required class="w-full border rounded p-2" placeholder="Ej: Insuficiencia renal, parvovirus, etc." />
          </div>

          <div class="flex gap-2 items-center mb-1">
            <label class="block font-medium mb-1">Centro Veterinario donde se realizo</label>
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
            <label class="block font-medium">Fecha de diagnóstico</label>
            <input v-model="diagnostico.fecha" type="date" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Estado/evolución</label>
            <select v-model="diagnostico.estado" required class="w-full border rounded p-2">
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
            >
            </textarea>
            <button 
              type="button"
              @click="abrirSelectorDiferenciales"
              class="bg-orange-500 text-white px-4 rounded font-bold hover:bg-orange-700 transition-colors whitespace-nowrap"
            >
              + Diagnostico 
            </button>
          </div>
          
          <!-- Etiquetas de diagnósticos seleccionados -->
          <div v-if="diagnosticosSeleccionados.length > 0" class="mt-3">
            <div class="flex flex-wrap gap-2">
              <div 
                v-for="(diag, index) in diagnosticosSeleccionados" 
                :key="index"
                class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm flex items-center gap-2"
              >
                <span>{{ diag.code }} - {{ diag.name }}</span>
                <button 
                  type="button"
                  @click="eliminarDiagnosticoDiferencial(index)"
                  class="text-blue-600 hover:text-blue-800"
                >
                  <font-awesome-icon :icon="['fas', 'times']" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div>
          <label class="block font-medium mb-1">Exámenes complementarios utilizados</label>
          <textarea v-model="diagnostico.examenes" rows="3" class="w-full border rounded p-2 resize-none" placeholder="Ej: Hemograma, radiografía, ecografía..."></textarea>
        </div>

        <div>
          <label class="block font-medium mb-1">Conducta terapéutica sugerida</label>
          <textarea v-model="diagnostico.conducta" rows="4" class="w-full border rounded p-2 resize-none" placeholder="Indique el tratamiento recomendado"></textarea>
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
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors">Registrar Diagnóstico</button>
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
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import DiagnosticosDiferenciales from '@/components/ElementosGraficos/DiagnosticosDiferenciales.vue' // Ajusta la ruta según tu estructura

const router = useRouter()
const route = useRoute()

// Estados para el selector de diferenciales
const mostrarSelectorDiferenciales = ref(false)
const selectorDiferenciales = ref(null)
const diagnosticosSeleccionados = ref([])

const abrirSelectorDiferenciales = () => {
  mostrarSelectorDiferenciales.value = true
  // Limpiar selección previa si se desea
  // diagnosticosSeleccionados.value = []
}

const cerrarSelectorDiferenciales = () => {
  mostrarSelectorDiferenciales.value = false
}

const agregarDiagnosticoDiferencial = (diagnostico) => {
  // Evitar duplicados
  if (!diagnosticosSeleccionados.value.some(d => d.id === diagnostico.id)) {
    diagnosticosSeleccionados.value.push(diagnostico)
  }
}

const confirmarDiagnosticoDiferencial = (diagnostico) => {
  agregarDiagnosticoDiferencial(diagnostico)
}

const eliminarDiagnosticoDiferencial = (index) => {
  diagnosticosSeleccionados.value.splice(index, 1)
  actualizarTextareaDiferenciales()
}

const finalizarSeleccionDiferenciales = () => {
  actualizarTextareaDiferenciales()
  cerrarSelectorDiferenciales()
}

const actualizarTextareaDiferenciales = () => {
  if (diagnosticosSeleccionados.value.length > 0) {
    diagnostico.diferenciales = diagnosticosSeleccionados.value
      .map(d => `${d.code} - ${d.name}`)
      .join('\n')
  } else {
    diagnostico.diferenciales = ''
  }
}

const abrirRegistroTipoDiagnostico = () => {
  router.push({
    path: '/registro/registroTipoDiagnostico',
    query: {
      from: '/historialClinico/diagnosticos/registro/diagnostico'
    }
  });
};

const diagnostico = reactive({
  tipo: '',
  tipoOtro: '',
  nombre: '',
  fecha: '',
  estado: '',
  observaciones: '',
  diferenciales: '',
  examenes: '',
  conducta: ''
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

const registrarDiagnostico = () => {
  const formData = new FormData()
  
  // Preparar datos para enviar
  const datosEnvio = {
    ...diagnostico,
    tipoDiagnostico: diagnostico.tipo === 'otro' ? diagnostico.tipoOtro : diagnostico.tipo,
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
  
  console.log('Datos a enviar:', Object.fromEntries(formData))
  console.log('Diagnósticos diferenciales seleccionados:', diagnosticosSeleccionados.value)
  
  // Aquí iría la lógica para enviar los datos al servidor
}
</script>
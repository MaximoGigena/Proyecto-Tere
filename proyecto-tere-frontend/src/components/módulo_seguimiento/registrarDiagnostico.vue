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
            <label class="block font-medium">Tipo de diagnóstico</label>
            <select v-model="diagnostico.tipo" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="condicion">Condición</option>
              <option value="enfermedad">Enfermedad</option>
              <option value="sindrome">Síndrome</option>
              <option value="lesion">Lesión</option>
              <option value="otro">Otro</option>
            </select>
            <input v-if="diagnostico.tipo === 'otro'" v-model="diagnostico.tipoOtro" type="text" class="w-full border rounded p-2 mt-2" placeholder="Especifique el tipo" />
          </div>

          <div>
            <label class="block font-medium">Nombre del diagnóstico</label>
            <input v-model="diagnostico.nombre" type="text" required class="w-full border rounded p-2" placeholder="Ej: Insuficiencia renal, parvovirus, etc." />
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

      <div class="col-span-full mt-4">
        <label class="block font-medium mb-1">Observaciones clínicas</label>
        <textarea v-model="diagnostico.observaciones" rows="5" required class="w-full border rounded p-2 resize-none"></textarea>
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
          <textarea v-model="diagnostico.diferenciales" rows="3" class="w-full border rounded p-2 resize-none" placeholder="Liste otros diagnósticos considerados"></textarea>
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
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div
              v-for="(archivo, index) in archivos"
              :key="index"
              class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-full aspect-square"
              @click="!archivo.preview && activarInput(index)"
            >
              <button type="button" @click.stop="quitarArchivo(index)" v-if="archivo.preview" class="absolute top-1 right-1 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700 mt-35 -mr-2">
                <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-3xl" />
              </button>

              <input :ref="el => inputsArchivo[index] = el" type="file" @change="handleArchivo($event, index)" class="hidden" />

              <div v-if="archivo.preview" class="h-full flex flex-col">
                <img v-if="esImagen(archivo.archivo)" :src="archivo.preview" alt="Preview" class="w-full h-full object-cover rounded-md border-gray-300 mx-auto flex-grow" />
                <div v-else class="h-full flex items-center justify-center p-2">
                  <font-awesome-icon :icon="['fas', 'file']" class="text-5xl text-gray-500" />
                </div>
                <div class="text-xs truncate px-1">{{ archivo.archivo.name }}</div>
              </div>

              <div v-else class="text-green-400 mt-14">
                <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-4xl mb-2" />
                <div class="text-gray-400">Agregar archivo</div>
              </div>
            </div>
          </div>
          <p class="text-sm text-gray-500 mt-1">Puede adjuntar informes, estudios por imagen, notas clínicas, etc.</p>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors">Registrar Diagnóstico</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

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
    tipoDiagnostico: diagnostico.tipo === 'otro' ? diagnostico.tipoOtro : diagnostico.tipo
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
  
  console.log('Datos a enviar:', formData)
  // Aquí iría la lógica para enviar los datos al servidor
}
</script>
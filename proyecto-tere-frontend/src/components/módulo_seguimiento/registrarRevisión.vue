<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Revisión Médica</h1>

    <form @submit.prevent="registrarRevision" class="space-y-4">
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
            <label class="block font-medium">Tipo de revisión médica</label>
            <select v-model="revision.tipo" required class="w-full border rounded p-2">
              <option value="">Seleccione un tipo</option>
              <option value="rutinaria">Rutinaria</option>
              <option value="preventiva">Preventiva</option>
              <option value="urgencia">Urgencia</option>
              <option value="seguimiento">Seguimiento</option>
              <option value="postoperatorio">Postoperatorio</option>
            </select>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Fecha de revisión</label>
            <input v-model="revision.fecha" type="datetime-local" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Nivel de urgencia</label>
            <select v-model="revision.urgencia" required class="w-full border rounded p-2">
              <option value="rutinaria">Rutinaria</option>
              <option value="preventiva">Preventiva</option>
              <option value="urgencia">Urgencia</option>
              <option value="emergencia">Emergencia</option>
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

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
        <div>
          <label class="block font-medium">Motivo de la consulta</label>
          <input v-model="revision.motivo" type="text" class="w-full border rounded p-2" />
        </div>

        <div>
          <label class="block font-medium">Diagnóstico (si aplica)</label>
          <input v-model="revision.diagnostico" type="text" class="w-full border rounded p-2" />
        </div>

        <div>
          <label class="block font-medium">Fecha sugerida para próxima revisión</label>
          <input v-model="revision.proximaRevision" type="date" class="w-full border rounded p-2" />
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Indicaciones o conducta médica sugerida</label>
          <textarea v-model="revision.indicaciones" rows="3" maxlength="500" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ revision.indicaciones.length }}/500 caracteres</p>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Observaciones adicionales</label>
          <textarea v-model="revision.observaciones" rows="3" maxlength="500" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ revision.observaciones.length }}/500 caracteres</p>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Recomendaciones al tutor</label>
          <textarea v-model="revision.recomendaciones" rows="3" maxlength="500" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ revision.recomendaciones.length }}/500 caracteres</p>
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
          <p class="text-sm text-gray-500 mt-1">Puede adjuntar fotos clínicas, estudios, recetas, etc.</p>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors">Registrar Revisión</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const revision = reactive({
  tipo: '',
  fecha: '',
  urgencia: 'rutinaria',
  motivo: '',
  diagnostico: '',
  proximaRevision: '',
  indicaciones: '',
  observaciones: '',
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

const registrarRevision = () => {
  const formData = new FormData()
  for (const campo in revision) {
    if (revision[campo] !== null) {
      formData.append(campo, revision[campo])
    }
  }

  archivos.value.forEach((archivo, i) => {
    if (archivo.archivo) {
      formData.append(`archivo${i + 1}`, archivo.archivo)
    }
  })
  
  // Aquí iría la lógica para enviar los datos al servidor
  console.log('Datos a enviar:', formData)
}
</script>
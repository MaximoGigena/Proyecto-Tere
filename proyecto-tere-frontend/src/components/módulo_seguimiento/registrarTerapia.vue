<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Terapia</h1>

    <form @submit.prevent="registrarTerapia" class="space-y-4">
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
            <label class="block font-medium">Tipo de terapia aplicada</label>
            <input v-model="terapia.tipo" type="text" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Fecha de inicio de la terapia</label>
            <input v-model="terapia.fechaInicio" type="date" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Frecuencia de las sesiones</label>
            <select v-model="terapia.frecuencia" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="diaria">Diaria</option>
              <option value="semanal">Semanal</option>
              <option value="quincenal">Quincenal</option>
              <option value="mensual">Mensual</option>
              <option value="personalizada">Personalizada</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Duración estimada del tratamiento</label>
            <input v-model="terapia.duracion" type="text" required class="w-full border rounded p-2" placeholder="Ej: 3 meses, 10 sesiones, etc." />
          </div>
        </div>

        <!-- Columna derecha - Archivos -->
        <div>
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
          <label class="block font-medium">Fecha de finalización</label>
          <input v-model="terapia.fechaFin" type="date" class="w-full border rounded p-2" />
        </div>

        <div>
          <label class="block font-medium">Evolución observada</label>
          <select v-model="terapia.evolucion" class="w-full border rounded p-2">
            <option value="">Seleccione una opción</option>
            <option value="mejoria">Mejoría</option>
            <option value="estable">Estable</option>
            <option value="empeoramiento">Empeoramiento</option>
          </select>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Observaciones clínicas</label>
          <textarea v-model="terapia.observaciones" rows="4" maxlength="500" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ terapia.observaciones.length }}/500 caracteres</p>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Recomendaciones para el tutor</label>
          <textarea v-model="terapia.recomendaciones" rows="4" maxlength="500" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ terapia.recomendaciones.length }}/500 caracteres</p>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors">Registrar Terapia</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const terapia = reactive({
  tipo: '',
  fechaInicio: '',
  frecuencia: '',
  duracion: '',
  fechaFin: '',
  evolucion: '',
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

const registrarTerapia = () => {
  const formData = new FormData()
  for (const campo in terapia) {
    if (terapia[campo] !== null) {
      formData.append(campo, terapia[campo])
    }
  }

  archivos.value.forEach((archivo, i) => {
    if (archivo.archivo) {
      formData.append(`archivo${i + 1}`, archivo.archivo)
    }
  })
}
</script>
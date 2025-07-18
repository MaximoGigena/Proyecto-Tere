<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Administración de Fármaco</h1>

    <form @submit.prevent="registrarFarmaco" class="space-y-4">
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
            <label class="block font-medium mb-1">Tipo de fármaco</label>
            <div class="flex gap-2">
              <!-- Contenedor relativo para el input con ícono -->
              <div class="relative w-full">
                <input 
                  v-model="farmaco.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Nombre comercial o principio activo"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>

              <!-- Botón de + Tipo -->
              <button 
                type="button"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo 
              </button>
            </div>
          </div>


          <div>
            <label class="block font-medium">Fecha de administración</label>
            <input v-model="farmaco.fecha" type="datetime-local" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Dosis administrada</label>
            <div class="flex">
              <input v-model="farmaco.dosis" type="text" required class="w-3/4 border rounded-l p-2" placeholder="Cantidad" />
              <select v-model="farmaco.unidad" required class="w-1/4 border rounded-r p-2">
                <option value="mg">mg</option>
                <option value="ml">ml</option>
                <option value="UI">UI</option>
                <option value="comp">comp.</option>
                <option value="gotas">gotas</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Frecuencia de administración</label>
            <input v-model="farmaco.frecuencia" type="text" required class="w-full border rounded p-2" placeholder="Ej: Cada 8 h, 1 vez al día, etc." />
          </div>

          <div>
            <label class="block font-medium">Duración del tratamiento</label>
            <input v-model="farmaco.duracion" type="text" required class="w-full border rounded p-2" placeholder="Ej: 7 días, 2 semanas, etc." />
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
          <label class="block font-medium">Fecha próxima dosis (si aplica)</label>
          <input v-model="farmaco.proximaDosis" type="datetime-local" class="w-full border rounded p-2" />
        </div>

        <div class="col-span-full">
          <div class="flex gap-2 items-center mb-1">
            <label class="block font-medium">Observaciones clínicas</label>
            <button 
              type="button"
              class="bg-green-500 text-white text-xl px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap"
            >
              + Observación
            </button>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Reacciones adversas observadas</label>
          <textarea v-model="farmaco.reacciones" rows="3" class="w-full border rounded p-2 resize-none" placeholder="Describa cualquier efecto no deseado"></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Recomendaciones para el tutor</label>
          <textarea v-model="farmaco.recomendaciones" rows="3" class="w-full border rounded p-2 resize-none"></textarea>
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
            <!-- Botón eliminar -->
            <button
              type="button"
              @click.stop="quitarArchivo(index)"
              v-if="archivo.preview"
              class="absolute top-1 right-1 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700 mt-35 -mr-2"
            >
              <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-3xl" />
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
                class="w-full h-full object-cover rounded-md border-gray-300 mx-auto flex-grow"
              />
              <div v-else class="h-full flex items-center justify-center p-2">
                <font-awesome-icon :icon="['fas', 'file']" class="text-5xl text-gray-500" />
              </div>
              <div class="text-xs truncate px-1">{{ archivo.archivo.name }}</div>
            </div>

            <!-- Indicador visual si no hay archivo -->
            <div v-else class="text-green-400 mt-14">
              <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-4xl mb-2" />
              <div class="text-gray-400">Agregar archivo</div>
            </div>
          </div>
        </div>
        <p class="text-sm text-gray-500 mt-1">Puede adjuntar recetas, imágenes del medicamento, informes, etc.</p>
      </div>

      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors">Registrar Fármaco</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const farmaco = reactive({
  nombre: '',
  fecha: '',
  dosis: '',
  unidad: 'mg',
  via: '',
  frecuencia: '',
  duracion: '',
  proximaDosis: '',
  observaciones: '',
  reacciones: '',
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

const registrarFarmaco = () => {
  const formData = new FormData()
  
  // Preparar datos para enviar
  const datosEnvio = {
    ...farmaco,
    dosisCompleta: `${farmaco.dosis} ${farmaco.unidad}`
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
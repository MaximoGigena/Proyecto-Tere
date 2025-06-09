<template>
  <div class="max-w-6xl mx-auto p-6"> <!-- Aumenté el max-width a 6xl para más espacio -->
    <h1 class="text-2xl font-bold mb-4">Registrar nueva mascota</h1>

    <button
      @click="cerrar"
      class="mb-4 text-sm text-gray-500 hover:text-gray-700 underline"
    >
      ← Cancelar y volver
    </button>

    <form @submit.prevent="registrarMascota" class="space-y-4">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8"> <!-- Contenedor grid de dos columnas -->
        <!-- Columna izquierda - Formulario -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Nombre</label>
            <input
              v-model="mascota.nombre"
              type="text"
              required
              class="w-full border rounded p-2 focus:outline-none focus:ring"
            />
          </div>

          <div>
            <label class="block font-medium">Especie</label>
            <select
              v-model="mascota.especie"
              required
              class="w-full border rounded p-2"
            >
              <option disabled value="">Seleccionar</option>
              <option value="perro">Perro</option>
              <option value="gato">Gato</option>
              <option value="otro">Otro</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Edad</label>
            <div class="flex gap-2"> <!-- Contenedor flex con espacio entre elementos -->
                <input
                v-model.number="mascota.edad"
                type="number"
                min="0"
                class="flex-1 border rounded p-2"
                />
                <select
                v-model="mascota.unidadEdad" 
                required
                class="flex-1 border rounded p-2" 
                >
                <option value="Dias">Días</option>
                <option value="Meses">Meses</option>
                <option value="Años">Años</option>
                </select>
             </div>
            </div>

                    <div>
                        <label class="block font-medium">Sexo</label>
                        <select
                        v-model="mascota.sexo"
                        required
                        class="w-full border rounded p-2"
                        >
                        <option disabled value="">Seleccionar</option>
                        <option value="macho">Macho</option>
                        <option value="hembra">Hembra</option>
                        </select>
                    </div>
                    </div>

        <!-- Columna derecha - Fotos -->
        <div>
          <label class="block font-medium mb-2">Fotos (hasta 6)</label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div
              v-for="(foto, index) in fotos"
              :key="index"
              class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-full aspect-square"
              @click="!foto.preview && activarInput(index)" 
            >
              <!-- Botón eliminar -->
              <button
                type="button"
                @click.stop="quitarFoto(index)" 
                v-if="foto.preview"
                class="absolute top-1 right-1 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700 mt-35 -mr-2"
              >
                <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-3xl" />
              </button>

              <!-- Input oculto con ref dinámico -->
              <input
                :ref="el => inputsFoto[index] = el"
                type="file"
                accept="image/*"
                @change="handleFoto($event, index)"
                class="hidden"
              />

              <!-- Vista previa -->
              <div v-if="foto.preview" class="h-full flex flex-col">
                <img
                  :src="foto.preview"
                  alt="Preview"
                  class="w-full h-full object-cover rounded-md border-gray-300 mx-auto flex-grow"
                />
              </div>

              <!-- Indicador visual si no hay foto -->
              <div v-else class="text-green-400 mt-14">
                <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-4xl mb-2" />
                <div class="text-gray-400">Agregar foto</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="pt-4">
        <button
          type="submit"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
          Registrar mascota
        </button>
      </div>
    </form>
  </div>
</template>


<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const cerrar = () => {
  if (route.query.from) {
    router.push(route.query.from)
  } else {
    router.back()
  }
}

const mascota = ref({
  nombre: '',
  especie: '',
  edad: null,
  unidadEdad: 'Años', // Valor por defecto
  sexo: '',
  foto: null,
})

const fotos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null
})))

const handleFoto = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    fotos.value[index].archivo = file
    fotos.value[index].preview = URL.createObjectURL(file)
  }
}

const inputsFoto = ref([])

const activarInput = (index) => {
  inputsFoto.value[index]?.click()
}


const quitarFoto = (index) => {
  fotos.value[index].archivo = null
  fotos.value[index].preview = null
}

const registrarMascota = () => {
  const formData = new FormData()
  for (const campo in mascota.value) {
    if (mascota.value[campo] !== null)
      formData.append(campo, mascota.value[campo])
  }

  fotos.value.forEach((foto, i) => {
    if (foto.archivo) {
      formData.append(`foto${i + 1}`, foto.archivo)
    }
  })

  // Simulación de envío:
  console.log("Formulario enviado:")
  for (let pair of formData.entries()) {
    console.log(pair[0], pair[1])
  }

  
}
</script>


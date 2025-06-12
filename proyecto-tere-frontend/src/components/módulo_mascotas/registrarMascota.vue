<template>
   <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
      <div class="max-w-6xl mx-auto flex items-center">
        <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
      </div>
   </div>
  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto "> <!-- Aumenté el max-width a 6xl para más espacio -->
    <h1 class="text-4xl font-bold mb-4">Registrar nueva mascota</h1>

    <form @submit.prevent="registrarMascota" class="space-y-4">
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
          Datos Obligatorios
        </h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>
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
          <label class="block font-medium mb-2">Sube al menos 1 foto de tu mascota </label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div
              v-for="(foto, index) in fotos"
              :key="index"
              required
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

      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
          Datos Opcionales
        </h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>
      <p>Si bien estos datos son opcionales, completarlos nos ayuda a conocer mejor a tu mascota y pueden ser muy útiles para veterinarios y otros usuarios.</p>
       <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
        <div>
            <label class="block font-medium">Tamaño</label>
            <select
              v-model="mascota.tamaño"
              class="w-full border rounded p-2"
            >
              <option disabled value="">Seleccionar</option>
              <option value="pequeño">Pequeño</option>
              <option value="mediano">Mediano</option>
              <option value="grande">Grande</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Pelaje</label>
            <select
              v-model="mascota.pelaje"
              class="w-full border rounded p-2"
            >
              <option disabled value="">Seleccionar</option>
              <option value="pequeño">Corto</option>
              <option value="mediano">Medio</option>
              <option value="grande">Largo</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Alimentación</label>
            <select
              v-model="mascota.alimentacion"
              class="w-full border rounded p-2"
            >
              <option disabled value="">Seleccionar</option>
              <option value="Alimentación comercial">Alimentación comercial (balanceados o piensos)</option>
              <option value="Dieta natural">Dieta natural (casera o BARF)</option>
              <option value="Dieta mixta">Dieta mixta (combinación de alimento comercial y natural)</option>
              <option value="Dietas especiales">Dietas especiales (por salud)</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Nivel de Energia</label>
            <select
              v-model="mascota.energia"
              class="w-full border rounded p-2"
            >
              <option disabled value="">Seleccionar</option>
              <option value="Bajo">Bajo</option>
              <option value="Medio">Medio</option>
              <option value="Alto">Alto</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Comportamiento frente a otros animales </label>
            <select
              v-model="mascota.comportamientoAnimales"
              class="w-full border rounded p-2"
            >
              <option disabled value="">Seleccionar</option>
              <option value="Soial">Social (Amistoso o Tolerante)</option>
              <option value="Territorial"> Territorial o Dominante</option>
              <option value="Depredador">Depredador (Instinto de Caza)</option>
              <option value="Temeroso">Temeroso o Evasivo</option>
              <option value="Agresivo">Agresivo (Defensivo o por Estrés)</option>
              <option value="Indeterminado">Indeterminado (Nunca tuvo contacto con otros animales)</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Comportamiento frente a los niños</label>
            <select
              v-model="mascota.comportamientoNiños"
              class="w-full border rounded p-2"
            >
              <option disabled value="">Seleccionar</option>
              <option value="Paciente">Paciente y Tolerante</option>
              <option value="Juguetón"> Juguetón y Energético</option>
              <option value="Temeroso">Temeroso o Evasivo</option>
              <option value="Estresado">Sobrecargado o Estresado</option>
              <option value="Agresivo">Agresivo (Defensivo o por Estrés)</option>
              <option value="Indeterminado">Indeterminado (Nunca tuvo contacto con niños)</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Personalidad</label>
            <select
              v-model="mascota.personalidad"
              class="w-full border rounded p-2"
            >
              <option disabled value="">Seleccionar</option>
              <option value="Amigable">Sociable y Amigable</option>
              <option value="Reservado">Independiente y Reservado</option>
              <option value="Curioso">Curioso y Explorador</option>
              <option value="Nervioso">Nervioso o Ansioso</option>
              <option value="Territorial">Dominante o Territorial</option>
              <option value="Tranquilo">Tranquilo y Dormilón</option>
              <option value="Protector">Protector y Vigilante</option>
            </select>
          </div>
          <div class="col-span-full">
            <label class="block font-medium mb-1">Descripción</label>
            <textarea
              v-model="mascota.descripcion"
              rows="4"
              maxlength="500"
              placeholder="Contanos más sobre tu mascota: su historia, personalidad, hábitos, etc."
              class="w-full border rounded p-2 resize-none focus:outline-none focus:ring"
            ></textarea>
            <p class="text-sm text-gray-500 text-right mt-1">
              {{ mascota.descripcion.length }}/500 caracteres
            </p>
          </div>
        </div>
      
      <div class="pt-4 flex justify-end items-center gap-4">
         <!-- Botón Cancelar (mismo formato y altura) -->
        <button
          @click="confirmarCancelar"
          class="bg-red-500 text-white px-4 py-2 rounded-full hover:bg-red-700 flex items-center gap-2 transition-colors"
        >
          <span>←</span>
          Cancelar y volver
        </button>
        <!-- Botón Registrar mascota -->
        <button
          type="submit"
          class="bg-blue-500 text-white px-4 py-2 rounded-full hover:bg-blue-700 transition-colors"
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

function confirmarCancelar() {
  if (window.confirm("¿Estás seguro de que deseas cancelar y volver?")) {
    cerrar();
  }
}

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
  tamaño: '',
  pelaje: '', 
  alimentacion: '',
  energia: '',
  comportamientoAnimales: '',
  comportamientoNiños: '',
  personalidad: '',
  descripcion: ''
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


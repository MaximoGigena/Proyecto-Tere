<template>
   <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
      <div class="max-w-6xl mx-auto flex items-center">
        <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
      </div>
   </div>
  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto "> <!-- Aumenté el max-width a 6xl para más espacio -->
    <h1 class="text-4xl font-bold mb-4">Registrar Usuario</h1>

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
                <label class="block font-medium">Gmail</label>
                <input
                  v-model="mascota.nombre"
                  type="text"
                  required
                  class="w-full border rounded p-2 focus:outline-none focus:ring"
                />
              </div>  

              <div>
                <label class="block font-medium mb-1">Fecha de nacimiento</label>
                <div class="flex gap-2">
                    <!-- Día -->
                    <input
                    v-model.number="usuario.fechaNacimiento.dia"
                    type="number"
                    min="1"
                    max="31"
                    placeholder="Día"
                    class="w-1/3 border rounded p-2"
                    />

                    <!-- Mes -->
                    <select
                    v-model="usuario.fechaNacimiento.mes"
                    class="w-1/3 border rounded p-2"
                    >
                    <option disabled value="">Mes</option>
                    <option value="1">Enero</option>
                    <option value="2">Febrero</option>
                    <option value="3">Marzo</option>
                    <option value="4">Abril</option>
                    <option value="5">Mayo</option>
                    <option value="6">Junio</option>
                    <option value="7">Julio</option>
                    <option value="8">Agosto</option>
                    <option value="9">Septiembre</option>
                    <option value="10">Octubre</option>
                    <option value="11">Noviembre</option>
                    <option value="12">Diciembre</option>
                    </select>

                    <!-- Año -->
                    <input
                    v-model.number="usuario.fechaNacimiento.anio"
                    type="number"
                    min="1930"
                    :max="new Date().getFullYear()"
                    placeholder="Año"
                    class="w-1/3 border rounded p-2"
                    />
                </div>
            </div>
        </div>

        <!-- Columna derecha - Fotos -->
        <div>
          <label class="block font-medium mb-2">Sube al menos 1 foto de tu persona </label>
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

        <!-- Descripción -->
        <p>
        Si bien estos datos son opcionales, completarlos nos ayuda a conocer mejor tus intereses, hábitos y estilo de vida, lo que puede mejorar la experiencia en la plataforma.
        </p>

        <!-- Formulario -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
            <div>
                <label class="block font-medium">Ocupación</label>
                <input
                v-model="usuario.ocupacion"
                type="text"
                class="w-full border rounded p-2"
                placeholder="Ej: Estudiante, Médico, Freelancer"
                />
            </div>

            <div>
                <label class="block font-medium">Tipo de vivienda</label>
                <select
                v-model="usuario.tipoVivienda"
                class="w-full border rounded p-2"
                >
                <option disabled value="">Seleccionar</option>
                <option value="departamento">Departamento</option>
                <option value="casa">Casa</option>
                <option value="campo">Casa en el campo</option>
                <option value="otro">Otro</option>
                </select>
            </div>

            <div>
                <label class="block font-medium">Experiencia con mascotas</label>
                <select
                v-model="usuario.experienciaMascotas"
                class="w-full border rounded p-2"
                >
                <option disabled value="">Seleccionar</option>
                <option value="nueva">Nunca tuve mascotas</option>
                <option value="poca">Tuve pocas veces</option>
                <option value="media">Tengo experiencia moderada</option>
                <option value="alta">Experto/a en cuidado animal</option>
                </select>
            </div>

            <div>
                <label class="block font-medium">Convive con niños</label>
                <select
                v-model="usuario.conviveConNiños"
                class="w-full border rounded p-2"
                >
                <option disabled value="">Seleccionar</option>
                <option value="si">Sí</option>
                <option value="no">No</option>
                </select>
            </div>

            <div>
                <label class="block font-medium">Convive con otras mascotas</label>
                <select
                v-model="usuario.conviveConMascotas"
                class="w-full border rounded p-2"
                >
                <option disabled value="">Seleccionar</option>
                <option value="si">Sí</option>
                <option value="no">No</option>
                </select>
            </div>

            <div class="col-span-full">
                <label class="block font-medium mb-1">Descripción</label>
                <textarea
                v-model="usuario.descripcion"
                rows="4"
                maxlength="500"
                placeholder="Contanos más sobre vos: tu estilo de vida, motivaciones para adoptar, experiencia con animales, etc."
                class="w-full border rounded p-2 resize-none focus:outline-none focus:ring"
                ></textarea>
                <p class="text-sm text-gray-500 text-right mt-1">
                {{ usuario.descripcion.length }}/500 caracteres
                </p>
            </div>
        </div>
        
        <div class="flex items-center my-6">
            <div class="flex-grow border-t border-gray-600"></div>
            <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
                Datos de Contacto 
            </h5>
            <div class="flex-grow border-t border-gray-600"></div>
        </div>
        
          <p>Estos datos nos permiten ponernos en contacto con vos en caso de que un veterinario u otro usuario necesite comunicarse por una consulta o seguimiento, Tus datos van a permanecer anonimos y lejos del alcance de los demas usuarios. (Son opcionales, pero te agradecemos cualquier colaboración).</p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
            <!-- Teléfono -->
            <div>
            <label class="block font-medium">Teléfono</label>
            <input
                v-model="usuario.telefono"
                type="tel"
                class="w-full border rounded p-2"
                placeholder="Ej: +54 9 11 1234 5xxx"
            />
            </div>

            <!-- Correo electrónico -->
            <div>
            <label class="block font-medium">Correo electrónico</label>
            <input
                v-model="usuario.email"
                type="email"
                class="w-full border rounded p-2"
                placeholder="Ej: ejemplo@email.com"
            />
            </div>

            <!-- Dirección -->
            <div>
            <label class="block font-medium">DNI</label>
            <input
                v-model="usuario.direccion"
                type="text"
                class="w-full border rounded p-2"
                placeholder="Ej: 45.208.xxx"
            />
            </div>

            <!-- Localidad -->
            <div>
            <label class="block font-medium">Nombre Completo</label>
            <input
                v-model="usuario.localidad"
                type="text"
                class="w-full border rounded p-2"
                placeholder="Ej: Juan Pepito"
            />
            </div>
        </div>
        

      
      <div class="pt-4 flex items-center justify-center gap-4">
        <!-- Botón Registrar usuario -->
        <button
            type="submit"
            class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 text-center rounded-full hover:bg-blue-700 transition-colors"
        >
            Registrar Usuario
        </button>
      </div>
    </form>
  </div>
</template>


<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { reactive } from 'vue'



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
  sexo: '',
  foto: null,
  tamaño: '',
 
  descripcion: '',
  telefono: '',
  email: '',
  direccion: '',
  localidad: '',
})

const usuario = reactive({
  fechaNacimiento: {
  dia: null,
  mes: '',
  anio: null,
 },
  ocupacion: '',
  tipoVivienda: '',
  experienciaMascotas: '',
  preferenciasAdopcion: '',
  conviveConNiños: '',
  conviveConMascotas: '',
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

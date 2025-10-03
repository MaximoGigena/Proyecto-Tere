<template>
   <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
      <div class="max-w-6xl mx-auto flex items-center">
        <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
      </div>
   </div>
  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar' : 'Registrar' }} mascota</h1>

    <form @submit.prevent="esEdicion ? actualizarMascota() : registrarMascota()" class="space-y-4">
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
          Datos Obligatorios
        </h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
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
                <label class="block font-medium">Edad</label>
                <div class="flex gap-2">
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

              <div>
                <label class="block font-medium mb-2">Especie</label>

                <div class="flex items-center justify-center gap-4">
                  <!-- Botón anterior -->
                  <button
                    type="button"
                    @click="prevEspecie"
                    class="p-2 rounded-full bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white transition-all duration-300 transform hover:scale-110 shadow-lg"
                  >
                    ‹
                  </button>

                  <!-- Contenedor del carrusel con animación -->
                  <div class="relative overflow-hidden w-32 h-32">
                    <div 
                      class="flex transition-transform duration-500 ease-in-out"
                      :style="{ transform: `translateX(-${especieIndex * 100}%)` }"
                    >
                      <!-- Icono y nombre con colores y animación -->
                      <div
                        v-for="(especie, index) in especies"
                        :key="especie.value"
                        class="flex-shrink-0 w-32 h-32 flex flex-col items-center justify-center p-4 rounded-2xl transition-all duration-300 transform cursor-pointer min-w-32"
                        :class="[
                          getColorClasses(index),
                          mascota.especie === especie.value 
                            ? 'scale-110 shadow-2xl ring-4 ring-white ring-opacity-50' 
                            : 'scale-100 opacity-70 hover:opacity-90 hover:scale-105'
                        ]"
                        @click="seleccionarEspecie(especie.value)"
                      >
                        <font-awesome-icon 
                          :icon="especie.icon" 
                          class="text-4xl mb-2 text-white drop-shadow-md" 
                        />
                        <span class="text-white font-semibold text-sm drop-shadow-md text-center">{{ especie.label }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Botón siguiente -->
                  <button
                    type="button"
                    @click="nextEspecie"
                    class="p-2 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600 text-white transition-all duration-300 transform hover:scale-110 shadow-lg"
                  >
                    ›
                  </button>
                </div>

                <!-- Indicadores del carrusel -->
                <div class="flex justify-center mt-4 space-x-2">
                  <button
                    v-for="(especie, index) in especies"
                    :key="index"
                    @click="especieIndex = index"
                    class="w-3 h-3 rounded-full transition-all duration-300"
                    :class="[
                      index === especieIndex 
                        ? getDotColor(index) + ' transform scale-125' 
                        : 'bg-gray-300'
                    ]"
                  ></button>
                </div>
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

      <!-- Resto del código se mantiene igual -->
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
              <option value="corto">Corto</option>
              <option value="medio">Medio</option>
              <option value="largo">Largo</option>

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
              <option value="Social">Social (Amistoso o Tolerante)</option>
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
          {{ esEdicion ? 'Actualizar' : 'Registrar' }} mascota
        </button>
      </div>
    </form>
  </div>
</template>


<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthToken } from '@/composables/useAuthToken' // Importar el composable
import axios from "axios"

axios.defaults.withCredentials = true
axios.defaults.baseURL = 'http://localhost:8000'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated } = useAuthToken() // Usar el composable

const especies = [
  { value: 'perro', label: 'Perro', icon: ['fas', 'dog'] },
  { value: 'gato', label: 'Gato', icon: ['fas', 'cat'] },
  { value: 'otro', label: 'Otro', icon: ['fas', 'paw'] }
]

const especieIndex = ref(0)

// Función para obtener clases de color según el índice
const getColorClasses = (index) => {
  const colors = [
    'bg-gradient-to-br from-blue-500 to-purple-600', // Perro
    'bg-gradient-to-br from-pink-500 to-red-600',    // Gato
    'bg-gradient-to-br from-green-500 to-teal-600'   // Otro
  ]
  return colors[index] || colors[0]
}

// Función para obtener color de los dots indicadores
const getDotColor = (index) => {
  const colors = [
    'bg-gradient-to-r from-blue-500 to-purple-500', // Perro
    'bg-gradient-to-r from-pink-500 to-red-500',    // Gato
    'bg-gradient-to-r from-green-500 to-teal-500'   // Otro
  ]
  return colors[index] || colors[0]
}

const seleccionarEspecie = (value) => {
  mascota.value.especie = value
  // Encontrar el índice de la especie seleccionada
  const index = especies.findIndex(e => e.value === value)
  if (index !== -1) {
    especieIndex.value = index
  }
}

const prevEspecie = () => {
  especieIndex.value = (especieIndex.value - 1 + especies.length) % especies.length
  mascota.value.especie = especies[especieIndex.value].value
}

const nextEspecie = () => {
  especieIndex.value = (especieIndex.value + 1) % especies.length
  mascota.value.especie = especies[especieIndex.value].value
}

const cargando = ref(false)
const mensaje = ref('')
const mensajeExito = ref(false)

// Determinar si estamos en modo edición
const esEdicion = computed(() => route.name === 'editar-mascota' || !!route.params.id)
const mascotaId = ref(null)

// Función para verificar autenticación
const verificarAutenticacion = () => {
  if (!isAuthenticated.value) {
    alert("Debes iniciar sesión.")
    router.push('/')
    return false
  }
  return true
}

// Cargar datos de la mascota si estamos editando
onMounted(async () => {
  if (!verificarAutenticacion()) return

  // Si ya hay una especie seleccionada (carrusel de especies), actualizar el índice
  if (mascota.value.especie) {
    const index = especies.findIndex(e => e.value === mascota.value.especie)
    if (index !== -1) especieIndex.value = index
  }

  // Determinar si estamos en modo edición
  const esEdicionMode = route.name === 'editar-mascota' || !!route.params.id
  
  if (esEdicionMode) {
    mascotaId.value = route.params.id
    await cargarMascota()
  }
})


// Cargar datos de la mascota para editar
const cargarMascota = async () => {
  try {
    cargando.value = true
    const response = await axios.get(`/api/mascotas/${mascotaId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`, // Usar accessToken del composable
      }
    })

    if (response.data.success) {
      const mascotaData = response.data.mascota
      
      // Llenar el formulario con los datos existentes
      mascota.value = {
        nombre: mascotaData.nombre,
        especie: mascotaData.especie,
        edad: mascotaData.edad,
        unidadEdad: mascotaData.unidad_edad,
        sexo: mascotaData.sexo,
        tamaño: mascotaData.caracteristicas?.tamano || '',
        pelaje: mascotaData.caracteristicas?.pelaje || '',
        alimentacion: mascotaData.caracteristicas?.alimentacion || '',
        energia: mascotaData.caracteristicas?.energia || '',
        comportamientoAnimales: mascotaData.caracteristicas?.comportamiento_animales || '',
        comportamientoNiños: mascotaData.caracteristicas?.comportamiento_ninos || '',
        personalidad: mascotaData.caracteristicas?.personalidad || '',
        descripcion: mascotaData.caracteristicas?.descripcion || ''
      }

      // Cargar fotos existentes
      if (mascotaData.fotos && mascotaData.fotos.length > 0) {
        // Resetear el array de fotos primero
        fotos.value = Array.from({ length: 6 }, () => ({
          archivo: null,
          preview: null,
          esExistente: false,
          paraEliminar: false,
          id: null
        }))
        
        mascotaData.fotos.forEach((foto, index) => {
          if (index < 6) {
            fotos.value[index] = {
              archivo: null,
              preview: foto.url,
              id: foto.id,
              esExistente: true,
              paraEliminar: false
            }
            console.log('Foto cargada:', foto.url);
          }
        })
      }
    } else {
      throw new Error(response.data.message || 'Error al cargar la mascota')
    }
  } catch (error) {
    console.error('Error al cargar mascota:', error)
    
    // Manejar errores de autenticación
    if (error.response?.status === 401) {
      alert('Tu sesión ha expirado. Por favor inicia sesión nuevamente.')
      router.push('/login')
      return
    }
    
    alert('Error al cargar los datos de la mascota')
    router.back()
  } finally {
    cargando.value = false
  }
}


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
  unidadEdad: 'Años',
  sexo: '',
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
  if (fotos.value[index].esExistente) {
    // Marcar para eliminación en lugar de quitarla inmediatamente
    fotos.value[index].paraEliminar = true
    fotos.value[index].preview = null
  } else {
    fotos.value[index].archivo = null
    fotos.value[index].preview = null
  }
}

// Función para actualizar mascota
const actualizarMascota = async () => {
  // Verificar autenticación antes de proceder
  if (!verificarAutenticacion()) return
  
  cargando.value = true
  mensaje.value = ''
  mensajeExito.value = false

  try {
    const formData = new FormData()

    // Datos obligatorios
    formData.append('nombre', mascota.value.nombre)
    formData.append('especie', mascota.value.especie)
    formData.append('edad', mascota.value.edad)
    formData.append('unidad_edad', mascota.value.unidadEdad)
    formData.append('sexo', mascota.value.sexo)
    formData.append('_method', 'PUT')

    // Opcionales
    if (mascota.value.tamaño) formData.append('tamano', mascota.value.tamaño)
    if (mascota.value.pelaje) formData.append('pelaje', mascota.value.pelaje)
    if (mascota.value.alimentacion) formData.append('alimentacion', mascota.value.alimentacion)
    if (mascota.value.energia) formData.append('energia', mascota.value.energia)
    if (mascota.value.comportamientoAnimales) formData.append('comportamiento_animales', mascota.value.comportamientoAnimales)
    if (mascota.value.comportamientoNiños) formData.append('comportamiento_ninos', mascota.value.comportamientoNiños)
    if (mascota.value.personalidad) formData.append('personalidad', mascota.value.personalidad)
    if (mascota.value.descripcion) formData.append('descripcion', mascota.value.descripcion)

    // Fotos nuevas
    fotos.value.forEach((foto, index) => {
      if (foto.archivo && !foto.paraEliminar) {
        formData.append('nuevas_fotos[]', foto.archivo)
      }
    })

    // Fotos a eliminar
    fotos.value.forEach((foto) => {
      if (foto.paraEliminar && foto.id) {
        formData.append('fotos_eliminar[]', foto.id)
      }
    })

    const response = await axios.post(`/api/mascotas/${mascotaId.value}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`, // Usar accessToken del composable
      }
    })

    if (response.data.success) {
      mensaje.value = 'Mascota actualizada correctamente'
      mensajeExito.value = true
      setTimeout(() => router.push({ name: 'mis-mascotas' }), 2000)
    }

  } catch (error) {
    console.error('Error al actualizar:', error)
    
    // Manejar errores de autenticación
    if (error.response?.status === 401) {
      mensaje.value = 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.'
      router.push('/login')
      return
    }
    
    if (error.response?.status === 422) {
      const errores = error.response.data.errors
      let mensajeError = 'Errores de validación:\n'
      
      for (const campo in errores) {
        mensajeError += `- ${campo}: ${errores[campo].join(', ')}\n`
      }
      
      mensaje.value = mensajeError
      alert(mensajeError)
    } else {
      mensaje.value = error.response?.data?.message || error.message || 'Error al actualizar la mascota.'
    }
  } finally {
    cargando.value = false
  }
}


const registrarMascota = async () => {
  // Verificar autenticación antes de proceder
  if (!verificarAutenticacion()) return
  
  cargando.value = true
  mensaje.value = ''
  mensajeExito.value = false

  try {
    console.log('Iniciando registro de mascota...')
    const formData = new FormData()

    // Datos obligatorios
    formData.append('nombre', mascota.value.nombre)
    formData.append('especie', mascota.value.especie)
    formData.append('edad', mascota.value.edad)
    formData.append('unidad_edad', mascota.value.unidadEdad)
    formData.append('sexo', mascota.value.sexo)

    // Opcionales
    if (mascota.value.tamaño) formData.append('tamano', mascota.value.tamaño)
    if (mascota.value.pelaje) formData.append('pelaje', mascota.value.pelaje)
    if (mascota.value.alimentacion) formData.append('alimentacion', mascota.value.alimentacion)
    if (mascota.value.energia) formData.append('energia', mascota.value.energia)
    if (mascota.value.comportamientoAnimales) formData.append('comportamiento_animales', mascota.value.comportamientoAnimales)
    if (mascota.value.comportamientoNiños) formData.append('comportamiento_ninos', mascota.value.comportamientoNiños)
    if (mascota.value.personalidad) formData.append('personalidad', mascota.value.personalidad)
    if (mascota.value.descripcion) formData.append('descripcion', mascota.value.descripcion)

    // Fotos
    fotos.value.forEach((foto) => {
      if (foto.archivo) {
        formData.append('fotos[]', foto.archivo)
      }
    })

    // DEBUG: Mostrar lo que se envía
    for (let [key, value] of formData.entries()) {
      console.log(key, value)
    }

    const response = await axios.post('/api/mascotas', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`, // Usar accessToken del composable
      }
    })

    if (response.data.success) {
      mensaje.value = 'Mascota registrada correctamente'
      mensajeExito.value = true
      setTimeout(() => router.push({ name: 'mis-mascotas' }), 2000)
    }

  } catch (error) {
    console.error('Error completo:', error)
    
    // Manejar errores de autenticación
    if (error.response?.status === 401) {
      mensaje.value = 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.'
      router.push('/login')
      return
    }
    
    if (error.response?.status === 422) {
      console.error('Errores de validación DETALLADOS:', error.response.data)
      const errores = error.response.data.errors
      let mensajeError = 'Errores de validación:\n'
      
      for (const campo in errores) {
        mensajeError += `- ${campo}: ${errores[campo].join(', ')}\n`
      }
      
      mensaje.value = mensajeError
      alert(mensajeError)
    } else {
      mensaje.value = error.response?.data?.message || error.message || 'Error al registrar la mascota.'
    }
  } finally {
    cargando.value = false
  }
}
</script>


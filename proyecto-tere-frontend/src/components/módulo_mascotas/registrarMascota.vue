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
                <label class="block font-medium mb-2">Fecha de Nacimiento Aproximada</label>
                <div class="flex gap-2">
                  <!-- Día -->
                  <div class="flex-1">
                    <label class="block text-sm text-gray-600 mb-1">Día</label>
                    <input
                      v-model.number="fechaNacimiento.dia"
                      type="number"
                      min="1"
                      max="31"
                      placeholder="DD"
                      required
                      class="w-full border rounded p-2 text-center"
                      @blur="validarFecha"
                    />
                  </div>

                  <!-- Mes -->
                  <div class="flex-1">
                    <label class="block text-sm text-gray-600 mb-1">Mes</label>
                    <select
                      v-model="fechaNacimiento.mes"
                      required
                      class="w-full border rounded p-2"
                      @change="validarFecha"
                    >
                      <option disabled value="">MM</option>
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
                  </div>

                  <!-- Año -->
                  <div class="flex-1">
                    <label class="block text-sm text-gray-600 mb-1">Año</label>
                    <input
                      v-model.number="fechaNacimiento.anio"
                      type="number"
                      min="1900"
                      :max="new Date().getFullYear()"
                      placeholder="AAAA"
                      required
                      class="w-full border rounded p-2 text-center"
                      @blur="validarFecha"
                    />
                  </div>
                </div>
                <p class="text-sm text-gray-600 mt-1">
                  <span v-if="fechaValida && fechaFormateada">Fecha seleccionada: {{ fechaFormateada }}</span>
                  <span v-else-if="errorFecha" class="text-red-500">{{ errorFecha }}</span>
                  <span v-else>Formato: día/mes/año</span>
                </p>
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
                 <EspecieSelector v-model="mascota.especie" />
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
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthToken } from '@/composables/useAuthToken'
import axios from "axios"
import EspecieSelector from '@/components/ElementosGraficos/CarruselEspecie.vue'

axios.defaults.withCredentials = true
axios.defaults.baseURL = 'http://localhost:8000'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated } = useAuthToken()

const cargando = ref(false)
const mensaje = ref('')
const mensajeExito = ref(false)

const mascota = ref({
  nombre: '',
  especie: 'canino',
  fechaNacimiento: '', // String en formato dd/mm/yyyy
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

// Watcher para sincronizar especieIndex cuando cambia mascota.especie
watch(() => mascota.value.especie, (nuevoValor) => {
  console.log('Especie cambiada a:', nuevoValor)
}, { immediate: true })

// Variables para manejar la fecha separada
const fechaNacimiento = ref({
  dia: '',
  mes: '',
  anio: ''
})

const fechaValida = ref(false)
const errorFecha = ref('')
const fechaFormateada = ref('')

// Determinar si estamos en modo edición
const esEdicion = computed(() => route.name === 'editar-mascota' || !!route.params.id)
const mascotaId = ref(null)

// Función para validar la fecha
const validarFecha = () => {
  const { dia, mes, anio } = fechaNacimiento.value
  
  // Validar que todos los campos estén completos
  if (!dia || !mes || !anio) {
    errorFecha.value = 'Todos los campos de fecha son requeridos'
    fechaValida.value = false
    fechaFormateada.value = ''
    mascota.value.fechaNacimiento = ''
    return false
  }

  // Convertir a números
  const diaNum = parseInt(dia)
  const mesNum = parseInt(mes)
  const anioNum = parseInt(anio)

  // Validar rangos básicos
  if (diaNum < 1 || diaNum > 31) {
    errorFecha.value = 'Día inválido (debe ser entre 1 y 31)'
    fechaValida.value = false
    fechaFormateada.value = ''
    mascota.value.fechaNacimiento = ''
    return false
  }

  if (mesNum < 1 || mesNum > 12) {
    errorFecha.value = 'Mes inválido'
    fechaValida.value = false
    fechaFormateada.value = ''
    mascota.value.fechaNacimiento = ''
    return false
  }

  const añoActual = new Date().getFullYear()
  if (anioNum < 1900 || anioNum > añoActual) {
    errorFecha.value = `Año inválido (debe ser entre 1900 y ${añoActual})`
    fechaValida.value = false
    fechaFormateada.value = ''
    mascota.value.fechaNacimiento = ''
    return false
  }

  // Validar días según el mes
  const diasEnMes = new Date(anioNum, mesNum, 0).getDate()
  if (diaNum > diasEnMes) {
    errorFecha.value = `Día inválido para ${getNombreMes(mesNum)} (máximo ${diasEnMes} días)`
    fechaValida.value = false
    fechaFormateada.value = ''
    mascota.value.fechaNacimiento = ''
    return false
  }

  // Validar que no sea fecha futura
  const fecha = new Date(anioNum, mesNum - 1, diaNum)
  const hoy = new Date()
  hoy.setHours(0, 0, 0, 0)
  
  if (fecha > hoy) {
    errorFecha.value = 'La fecha no puede ser futura'
    fechaValida.value = false
    fechaFormateada.value = ''
    mascota.value.fechaNacimiento = ''
    return false
  }

  // Si pasa todas las validaciones
  errorFecha.value = ''
  fechaValida.value = true
  
  // Formatear fecha como dd/mm/yyyy
  const diaFormateado = String(diaNum).padStart(2, '0')
  const mesFormateado = String(mesNum).padStart(2, '0')
  fechaFormateada.value = `${diaFormateado}/${mesFormateado}/${anioNum}`
  mascota.value.fechaNacimiento = fechaFormateada.value
  
  return true
}

// Función para obtener nombre del mes
const getNombreMes = (mes) => {
  const meses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ]
  return meses[mes - 1] || ''
}

// Función para parsear fecha string a objeto
const parsearFechaString = (fechaString) => {
  if (!fechaString) return { dia: '', mes: '', anio: '' }
  
  // Intentar formato dd/mm/yyyy
  const partes = fechaString.split('/')
  if (partes.length === 3) {
    return {
      dia: parseInt(partes[0]),
      mes: parseInt(partes[1]),
      anio: parseInt(partes[2])
    }
  }
  
  // Intentar formato YYYY-MM-DD (para compatibilidad)
  const partesISO = fechaString.split('-')
  if (partesISO.length === 3) {
    return {
      dia: parseInt(partesISO[2]),
      mes: parseInt(partesISO[1]),
      anio: parseInt(partesISO[0])
    }
  }
  
  return { dia: '', mes: '', anio: '' }
}

// Watcher para cuando se cargan datos de edición
watch(() => mascota.value.fechaNacimiento, (nuevoValor) => {
  if (nuevoValor) {
    fechaNacimiento.value = parsearFechaString(nuevoValor)
    // Validar automáticamente cuando se carga la fecha
    setTimeout(() => validarFecha(), 100)
  } else {
    fechaNacimiento.value = { dia: '', mes: '', anio: '' }
    fechaValida.value = false
    fechaFormateada.value = ''
  }
})

const edadMascota = computed(() => {
    if (!mascota.value.fechaNacimiento || !fechaValida.value) return null;
    
    // Parsear fecha en formato dd/mm/yyyy
    const partes = mascota.value.fechaNacimiento.split('/')
    if (partes.length !== 3) return null;
    
    const dia = parseInt(partes[0])
    const mes = parseInt(partes[1]) - 1
    const anio = parseInt(partes[2])
    
    const nacimiento = new Date(anio, mes, dia)
    const hoy = new Date()
    
    if (isNaN(nacimiento.getTime())) return null;
    
    const diffTime = Math.abs(hoy - nacimiento)
    const diffDias = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    
    if (diffDias < 30) {
        return { valor: diffDias, unidad: 'Dias' }
    } else if (diffDias < 365) {
        return { valor: Math.floor(diffDias / 30), unidad: 'Meses' }
    } else {
        return { valor: Math.floor(diffDias / 365), unidad: 'Años' }
    }
})

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
        'Authorization': `Bearer ${accessToken.value}`,
      }
    })

    if (response.data.success) {
      const mascotaData = response.data.mascota
      
      // Llenar el formulario con los datos existentes
      mascota.value = {
        nombre: mascotaData.nombre,
        especie: mascotaData.especie,
        fechaNacimiento: mascotaData.fecha_nacimiento, // Usar el campo correcto de la API
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
          }
        })
      }
    } else {
      throw new Error(response.data.message || 'Error al cargar la mascota')
    }
  } catch (error) {
    console.error('Error al cargar mascota:', error)
    
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
    fotos.value[index].paraEliminar = true
    fotos.value[index].preview = null
  } else {
    fotos.value[index].archivo = null
    fotos.value[index].preview = null
  }
}

// Función para validar antes de enviar
const validarAntesDeEnviar = () => {
  if (!validarFecha()) {
    alert('Por favor corrige la fecha de nacimiento')
    return false
  }
  return true
}

// Función para actualizar mascota
const actualizarMascota = async () => {
  if (!verificarAutenticacion() || !validarAntesDeEnviar()) return
  
  cargando.value = true
  mensaje.value = ''
  mensajeExito.value = false

  try {
    const formData = new FormData()

    // Datos obligatorios
    formData.append('nombre', mascota.value.nombre)
    formData.append('especie', mascota.value.especie)
    formData.append('fecha_nacimiento', mascota.value.fechaNacimiento) // String dd/mm/yyyy
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
        'Authorization': `Bearer ${accessToken.value}`,
      }
    })

    if (response.data.success) {
      mensaje.value = 'Mascota actualizada correctamente'
      mensajeExito.value = true
      setTimeout(() => router.push({ name: 'mis-mascotas' }), 2000)
    }

  } catch (error) {
    console.error('Error al actualizar:', error)
    
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
  if (!verificarAutenticacion() || !validarAntesDeEnviar()) return
  
  cargando.value = true
  mensaje.value = ''
  mensajeExito.value = false

  try {
    const formData = new FormData()

    // Datos obligatorios
    formData.append('nombre', mascota.value.nombre)
    formData.append('especie', mascota.value.especie)
    formData.append('fecha_nacimiento', mascota.value.fechaNacimiento) // String dd/mm/yyyy
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

    const response = await axios.post('/api/mascotas', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`,
      }
    })

    if (response.data.success) {
      mensaje.value = 'Mascota registrada correctamente'
      mensajeExito.value = true
      setTimeout(() => router.push({ name: 'mis-mascotas' }), 2000)
    }

  } catch (error) {
    console.error('Error completo:', error)
    
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
      mensaje.value = error.response?.data?.message || error.message || 'Error al registrar la mascota.'
    }
  } finally {
    cargando.value = false
  }
}
</script>


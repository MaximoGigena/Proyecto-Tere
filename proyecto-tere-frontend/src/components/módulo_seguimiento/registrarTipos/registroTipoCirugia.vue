<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Tipo de Cirugía' : 'Registrar Tipo de Cirugía' }}</h1>

    <!-- Mensajes de éxito/error -->
    <div v-if="mensaje.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
      {{ mensaje.text }}
    </div>
    <div v-if="mensaje.error" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
      {{ mensaje.text }}
    </div>

    <form @submit.prevent="esEdicion ? actualizarCirugia() : registrarCirugia()" class="space-y-4">
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
            <label class="block font-medium">Nombre del tipo de cirugía</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="cirugia.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Esterilización, Limpieza dental, Extirpación de tumor"
                  :disabled="loading"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Descripción general</label>
            <textarea 
              v-model="cirugia.descripcion" 
              rows="4" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción detallada del procedimiento quirúrgico"
              :disabled="loading"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium mb-2">Especie objetivo</label>
            <CarruselEspecieVeterinario v-model="especiesSeleccionadas" />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Frecuencia esperada</label>
            <select v-model="cirugia.frecuencia" required class="w-full border rounded p-2" :disabled="loading">
              <option value="">Seleccione una opción</option>
              <option value="unica">Única vez</option>
              <option value="potencial_repetible">Potencialmente repetible</option>
              <option value="multiple">Múltiples veces</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Duración estimada</label>
            <div class="flex">
              <input 
                v-model="cirugia.duracion" 
                type="number" 
                min="1" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Tiempo" 
                :disabled="loading"
              />
              <select 
                v-model="cirugia.duracion_unidad" 
                required 
                class="w-1/2 border rounded-r p-2"
                :disabled="loading"
              >
                <option value="minutos">Minutos</option>
                <option value="horas">Horas</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Riesgos comunes asociados</label>
            <textarea 
              v-model="cirugia.riesgos" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Complicaciones potenciales de este procedimiento"
              :disabled="loading"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Recomendaciones preoperatorias</label>
            <textarea 
              v-model="cirugia.recomendaciones_preoperatorias" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Preparación necesaria antes de la cirugía"
              :disabled="loading"
            ></textarea>
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
          <label class="block font-medium">Recomendaciones postoperatorias</label>
          <textarea 
            v-model="cirugia.recomendaciones_postoperatorias" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Cuidados después de la cirugía"
            :disabled="loading"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Requerimientos de anestesia</label>
          <textarea 
            v-model="cirugia.requerimientos_anestesia" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Tipo de anestesia recomendada"
            :disabled="loading"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Equipamiento quirúrgico necesario</label>
          <div class="flex gap-4">
            <textarea 
              v-model="equipamientoTemporal" 
              rows="2" 
              class="w-64 border rounded p-2 resize-none" 
              placeholder="Liste el equipamiento necesario"
              :disabled="loading"
            ></textarea>
            <button 
              type="button"
              @click="agregarEquipamiento"
              class="bg-green-500 text-white px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap disabled:bg-green-300"
              :disabled="loading || !equipamientoTemporal.trim()"
            >
              + Agregar
            </button>
          </div>
          <div class="flex flex-wrap gap-2 mt-2">
            <div 
              v-for="(item, index) in cirugia.equipamiento" 
              :key="index"
              class="bg-gray-100 px-3 py-1 rounded-full flex items-center gap-2"
            >
              {{ item }}
              <button 
                type="button"
                @click="eliminarEquipamiento(index)"
                class="text-red-500 hover:text-red-700"
                :disabled="loading"
              >
                <font-awesome-icon :icon="['fas', 'times']" />
              </button>
            </div>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Observaciones adicionales</label>
          <textarea 
            v-model="cirugia.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante sobre este procedimiento"
            :disabled="loading"
          ></textarea>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button 
          type="button" 
          @click="cancelar" 
          class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors disabled:bg-gray-300"
          :disabled="loading"
        >
          Cancelar
        </button>
        <button 
          type="submit" 
          class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300 flex items-center gap-2"
          :disabled="loading || !formularioValido"
        >
          <span v-if="loading" class="inline-block animate-spin">⟳</span>
          {{ loading ? (esEdicion ? 'Actualizando...' : 'Registrando...') : (esEdicion ? 'Actualizar' : '+ Tipo') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import CarruselEspecieVeterinario from '@/components/ElementosGraficos/CarruselEspecieVeterinario.vue'
import axios from 'axios'

const especiesSeleccionadas = ref([])

const router = useRouter()
const route = useRoute() // AÑADIDO: para obtener el ID de la ruta
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const loading = ref(false)
const esEdicion = ref(false)
const cirugiaId = ref(null) // AÑADIDO: para almacenar el ID en edición

const props = defineProps({
  id: {
    type: [String, Number],
    default: null
  }
})

const mensaje = reactive({
  success: false,
  error: false,
  text: ''
})

const cirugia = reactive({
  nombre: '',
  descripcion: '',
  especie: '',
  frecuencia: '',
  duracion: '',
  duracion_unidad: 'minutos',
  riesgos: '',
  recomendaciones_preoperatorias: '',
  recomendaciones_postoperatorias: '',
  requerimientos_anestesia: '',
  equipamiento: [],
  observaciones: ''
})

const equipamientoTemporal = ref('')

// Computed para validar el formulario
const formularioValido = computed(() => {
  return cirugia.nombre.trim() !== '' &&
         cirugia.descripcion.trim() !== '' &&
         especiesSeleccionadas.value.length > 0 && // ← Cambio aquí
         cirugia.frecuencia !== '' &&
         cirugia.duracion !== '' &&
         cirugia.riesgos.trim() !== '' &&
         cirugia.recomendaciones_preoperatorias.trim() !== ''
})


// Verificar permisos y cargar datos si es edición
onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      mostrarMensaje('Debe iniciar sesión para acceder a esta página', 'error')
      setTimeout(() => {
        router.push('/')
      }, 2000)
      return
    }
  }

  // AÑADIDO: Verificar si estamos en modo edición
  if (route.params.id) {
    esEdicion.value = true
    cirugiaId.value = route.params.id
    await cargarDatosCirugia()
  }
})

// AÑADIDO: Función para cargar datos en modo edición
const cargarDatosCirugia = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/tipos-cirugia/${cirugiaId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (response.data.success) {
      const datos = response.data.data
      
      // Llenar el formulario con los datos existentes
      Object.keys(cirugia).forEach(key => {
        if (datos[key] !== undefined && datos[key] !== null) {
          if (key === 'equipamiento' && Array.isArray(datos[key])) {
            cirugia[key] = datos[key]
          } else {
            cirugia[key] = datos[key]
          }
        }
      })

      // AÑADIDO: Cargar las especies seleccionadas
      if (datos.especies && Array.isArray(datos.especies)) {
        especiesSeleccionadas.value = datos.especies
      } else if (datos.especie) {
        // Si viene como string individual (backward compatibility)
        especiesSeleccionadas.value = [datos.especie]
      }
      
      console.log('Especies cargadas:', especiesSeleccionadas.value) // Para debug
    } else {
      mostrarMensaje('Error al cargar los datos de la cirugía', false)
    }
  } catch (error) {
    console.error('Error al cargar datos:', error)
    mostrarMensaje('Error al cargar los datos de la cirugía', false)
  } finally {
    loading.value = false
  }
}

const agregarEquipamiento = () => {
  if (equipamientoTemporal.value.trim() !== '') {
    cirugia.equipamiento.push(equipamientoTemporal.value.trim())
    equipamientoTemporal.value = ''
  }
}

const eliminarEquipamiento = (index) => {
  cirugia.equipamiento.splice(index, 1)
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const mostrarMensaje = (texto, esExito = true) => {
  mensaje.success = esExito
  mensaje.error = !esExito
  mensaje.text = texto
  
  setTimeout(() => {
    mensaje.success = false
    mensaje.error = false
    mensaje.text = ''
  }, 5000)
}

// AÑADIDO: Función para actualizar
const actualizarCirugia = async () => {
  if (!formularioValido.value) {
    mostrarMensaje('Por favor complete todos los campos obligatorios.', false)
    return
  }

  loading.value = true

  try {
    const datosEnvio = {
      nombre: cirugia.nombre.trim(),
      descripcion: cirugia.descripcion.trim(),
      especies: especiesSeleccionadas.value,
      frecuencia: cirugia.frecuencia,
      duracion: parseInt(cirugia.duracion),
      duracion_unidad: cirugia.duracion_unidad,
      riesgos: cirugia.riesgos.trim(),
      recomendaciones_preoperatorias: cirugia.recomendaciones_preoperatorias.trim(),
      recomendaciones_postoperatorias: cirugia.recomendaciones_postoperatorias?.trim() || null,
      requerimientos_anestesia: cirugia.requerimientos_anestesia?.trim() || null,
      equipamiento: cirugia.equipamiento.length > 0 ? cirugia.equipamiento : null,
      observaciones: cirugia.observaciones?.trim() || null
    }

    const response = await axios.put(`/api/tipos-cirugia/${cirugiaId.value}`, datosEnvio, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })

    if (response.data.success) {
      mostrarMensaje('Tipo de cirugía actualizado exitosamente')
      
      setTimeout(() => {
        router.push('/veterinarios/tipos/cirugias')
      }, 2000)
    } else {
      mostrarMensaje(response.data.message || 'Error al actualizar el tipo de cirugía', false)
    }

  } catch (error) {
    console.error('Error al actualizar tipo de cirugía:', error)
    
    let errorMessage = 'Error al actualizar el tipo de cirugía'
    
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    } else if (error.response?.data?.errors) {
      const firstError = Object.values(error.response.data.errors)[0]
      errorMessage = Array.isArray(firstError) ? firstError[0] : firstError
    } else if (error.message) {
      errorMessage = error.message
    }
    
    mostrarMensaje(errorMessage, false)
  } finally {
    loading.value = false
  }
}

// MODIFICADO: Registrar cirugía (mantiene la misma lógica)
const registrarCirugia = async () => {
  if (!formularioValido.value) {
    mostrarMensaje('Por favor complete todos los campos obligatorios.', false)
    return
  }

  loading.value = true

  try {
    const datosEnvio = {
      nombre: cirugia.nombre.trim(),
      descripcion: cirugia.descripcion.trim(),
      especies: especiesSeleccionadas.value,
      frecuencia: cirugia.frecuencia,
      duracion: parseInt(cirugia.duracion),
      duracion_unidad: cirugia.duracion_unidad,
      riesgos: cirugia.riesgos.trim(),
      recomendaciones_preoperatorias: cirugia.recomendaciones_preoperatorias.trim(),
      recomendaciones_postoperatorias: cirugia.recomendaciones_postoperatorias?.trim() || null,
      requerimientos_anestesia: cirugia.requerimientos_anestesia?.trim() || null,
      equipamiento: cirugia.equipamiento.length > 0 ? cirugia.equipamiento : null,
      observaciones: cirugia.observaciones?.trim() || null
    }

     console.log('Datos a enviar:', datosEnvio)

    const response = await axios.post('/api/tipos-cirugia', datosEnvio, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })

    if (response.data.success) {
      mostrarMensaje('Tipo de cirugía registrado exitosamente')
      
      // Limpiar formulario después de éxito
      Object.keys(cirugia).forEach(key => {
        if (key === 'equipamiento') {
          cirugia[key] = []
        } else if (key === 'duracion_unidad') {
          cirugia[key] = 'minutos'
        } else {
          cirugia[key] = ''
        }
      })
      especiesSeleccionadas.value = []
      
      setTimeout(() => {
        router.push('/veterinarios/tipos/cirugias')
      }, 2000)
    } else {
      mostrarMensaje(response.data.message || 'Error al registrar el tipo de cirugía', false)
    }

  } catch (error) {
    console.error('Error al registrar tipo de cirugía:', error)
    
    let errorMessage = 'Error al registrar el tipo de cirugía'
    
    if (error.response?.data?.errors) {
      console.log('Errores de validación:', error.response.data.errors)
      const errores = error.response.data.errors
      const primerError = Object.values(errores)[0]
      mostrarMensaje(Array.isArray(primerError) ? primerError[0] : primerError, false)
    } else if (error.response?.data?.message) {
      mostrarMensaje(error.response.data.message, false)
    } else {
      mostrarMensaje('Error al registrar el tipo de cirugía', false)
    }
  } finally {
    loading.value = false
  }
}
</script>
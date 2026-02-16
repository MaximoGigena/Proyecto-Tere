<!-- registrarVacuna.vue - Versión final con registro y edición -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img
        src="@/assets/Logo_Pagina_Oscura.png"
        alt="Logo TERE"
        class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625"
      />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Vacunación' : 'Registrar Vacunación' }}</h1>

    <form @submit.prevent="procesarFormulario" class="space-y-4">
      <!-- DATOS OBLIGATORIOS -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5
          class="px-4 text-center font-bold text-gray-800 whitespace-nowrap"
        >
          Datos Obligatorios
        </h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna izquierda -->
        <div class="space-y-4">
          <!-- Selección de Tipo de Vacuna -->
          <div>
            <label class="block font-medium">Tipo de vacuna aplicada</label>
            <div class="flex gap-2">
              <select
                v-model="vacuna.tipo_vacuna_id"
                required
                class="w-full border rounded p-2"
                @change="onTipoVacunaChange"
              >
                <option value="">Seleccione un tipo de vacuna</option>
                <option
                  v-for="tipo in tiposVacuna"
                  :key="tipo.id"
                  :value="tipo.id"
                >
                  {{ tipo.nombre }}
                </option>
              </select>

              <!-- Botón de + Tipo -->
              <button
                type="button"
                @click="abrirRegistroTipoVacuna"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo
              </button>
            </div>
          </div>

          <div>
            <label class="block font-medium">Fecha de aplicación</label>
            <input
              v-model="vacuna.fecha_aplicacion"
              type="date"
              required
              class="w-full border rounded p-2"
            />
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">
              Centro Veterinario donde se realizó
            </label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="vacuna.centro_veterinario_id"
                class="w-full border rounded p-2 bg-gray-50"
              >
                <div class="font-semibold">
                  {{ obtenerNombreCentroSeleccionado() }}
                </div>
                <div class="text-sm text-gray-600">
                  {{ obtenerDireccionCentroSeleccionado() }}
                </div>
              </div>
              
              <div 
                v-else
                class="w-full border rounded p-2 text-gray-400 italic"
              >
                Ningún centro veterinario seleccionado
              </div>

              <button
                type="button"
                @click="abrirOverlayCentros"
                class="bg-green-500 text-white text-xl px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap"
              >
                + Centro
              </button>
            </div>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Número de dosis</label>
            <input
              v-model="vacuna.numero_dosis"
              type="text"
              required
              class="w-full border rounded p-2"
              placeholder="Ej: 1° dosis, Refuerzo anual, etc."
            />
          </div>

          <div>
            <label class="block font-medium">Lote / Serie del frasco</label>
            <input
              v-model="vacuna.lote_serie"
              type="text"
              required
              class="w-full border rounded p-2"
              placeholder="Ej: LOTE-12345"
            />
          </div>
        </div>
      </div>

      <!-- DATOS OPCIONALES -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5
          class="px-4 text-center font-bold text-gray-800 whitespace-nowrap"
        >
          Datos Opcionales
        </h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
        <div>
          <label class="block font-medium"
            >Fecha próxima dosis (si aplica)</label
          >
          <input
            v-model="vacuna.fecha_proxima_dosis"
            type="date"
            class="w-full border rounded p-2"
            :min="vacuna.fecha_aplicacion"
          />
        </div>
      </div>

      <!-- Selección del medio de envío -->
      <div class="mt-8">
        <!-- Para ambos casos (registro y edición) mostramos el carrusel -->
        <div v-if="usuarioId">
          <CarruselMedioEnvio 
            :usuario-id="usuarioId" 
            :modo-edicion="esEdicion"
            :medio-seleccionado-inicial="vacuna.medio_envio"
            @update:medio="vacuna.medio_envio = $event"
          />
          
          <div v-if="vacuna.medio_envio" class="mt-4 text-center text-gray-700">
            <span class="font-semibold">
              {{ esEdicion ? 'Medio de envío utilizado:' : 'Medio seleccionado:' }}
            </span>
            <span class="ml-1 text-blue-600 font-medium">
              {{ obtenerNombreMedio(vacuna.medio_envio) }}
            </span>
            <p v-if="esEdicion" class="text-sm text-gray-500 mt-1">
              (En modo edición el medio de envío no se puede cambiar)
            </p>
          </div>
        </div>
        
        <div v-else class="text-center py-4">
          <p class="text-gray-500">Cargando información del dueño...</p>
        </div>
      </div>
      <div class="pt-4 flex items-center justify-center gap-4">
        <button
          type="button"
          @click="cancelar"
          class="bg-gray-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-gray-700 transition-colors"
        >
          Cancelar
        </button>
        <button
          type="button"
          @click="mostrarModalConfirmacion"
          :disabled="procesando || !formularioValido"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
        >
          {{ procesando ? 'Procesando...' : (esEdicion ? 'Actualizar Vacunación' : 'Registrar Vacunación') }}
        </button>
      </div>
    </form>

    <!-- Sección de errores de validación -->
    <div v-if="mostrarErrores && Object.keys(erroresValidacion).length > 0" 
        class="mt-6 p-4 bg-red-50 border-l-4 border-red-500">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            Problemas de validación
          </h3>
          <div class="mt-2 text-sm text-red-700">
            <ul class="list-disc pl-5 space-y-1">
              <li v-for="(erroresCampo, campo) in erroresValidacion" :key="campo">
                <template v-if="campo !== '_debug'">
                  <span v-for="error in erroresCampo" :key="error" class="block">
                    {{ error }}
                  </span>
                </template>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Componente externo del overlay -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="vacuna.centro_veterinario_id"
      @cerrar="mostrarOverlayCentros = false"
      @seleccionar="seleccionarCentro"
    />

    <!-- Modal de confirmación -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4">
          {{ esEdicion ? 'Confirmar Actualización' : 'Confirmar Registro' }}
        </h3>
        
        <div class="mb-6">
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Tipo de vacuna:</span> {{ obtenerNombreTipoVacuna() }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Fecha aplicación:</span> {{ formatFecha(vacuna.fecha_aplicacion) }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Número de dosis:</span> {{ vacuna.numero_dosis }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Lote/Serie:</span> {{ vacuna.lote_serie }}
          </p>
          <p v-if="vacuna.centro_veterinario_id" class="text-gray-700 mb-2">
            <span class="font-semibold">Centro veterinario:</span> {{ obtenerNombreCentroSeleccionado() }}
          </p>
          <p v-if="vacuna.fecha_proxima_dosis" class="text-gray-700 mb-2">
            <span class="font-semibold">Próxima dosis:</span> {{ formatFecha(vacuna.fecha_proxima_dosis) }}
          </p>
          <p v-if="vacuna.medio_envio" class="text-gray-700">
            <span class="font-semibold">Medio de envío:</span> {{ obtenerNombreMedio(vacuna.medio_envio) }}
          </p>
        </div>

        <div class="flex justify-end gap-3">
          <button
            @click="cerrarModal"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Cancelar
          </button>
          <button
            @click="confirmarAccion"
            :disabled="procesando"
            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors disabled:bg-blue-300"
          >
            {{ procesando ? 'Procesando...' : (esEdicion ? 'Actualizar' : 'Registrar') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import SeleccionCentroVeterinario from '@/components/ElementosGraficos/SeleccionCentroVeterinario.vue'
import CarruselMedioEnvio from '@/components/ElementosGraficos/CarruselMedioEnvio.vue'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
  vacunaId: {
    type: [String, Number],
    default: null
  }
})



const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Estados reactivos
const tiposVacuna = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)
const mostrarModal = ref(false)

// Determinar si es edición o registro
const esEdicion = computed(() => {
  return route.name === 'editarVacuna' || !!route.params.vacunaId
})

const vacunaId = computed(() => {
  return props.vacunaId || route.params.vacunaId || null
})

const mascotaId = computed(() => {
  return route.query.mascotaId || route.params.mascotaId || null
})

console.log('🔍 Route params:', route.params)
console.log('🔍 Route query:', route.query)
console.log('🔍 Es edición:', esEdicion.value)
console.log('🔍 Vacuna ID:', vacunaId.value)
console.log('🔍 Mascota ID:', mascotaId.value)

// Datos del formulario
const vacuna = reactive({
  tipo_vacuna_id: '',
  fecha_aplicacion: '',
  numero_dosis: '',
  lote_serie: '',
  centro_veterinario_id: '',
  fecha_proxima_dosis: '',
  medio_envio: '',
})

// Agrega esta variable reactiva para manejar errores
const erroresValidacion = ref({})
const mostrarErrores = ref(false)

// Función mejorada para mostrar errores
const mostrarErrorValidacion = (error) => {
  mostrarErrores.value = true
  // Crear un array temporal para los errores
  const erroresArray = []
  
  // Verificar si es un error del servidor con estructura de validación
  if (error.response?.status === 422 && error.response.data?.errors) {
    erroresValidacion.value = error.response.data.errors
    
    // Construir mensaje amigable
    for (const campo in error.response.data.errors) {
      const mensajes = error.response.data.errors[campo]
      mensajes.forEach(mensaje => {
        erroresArray.push(`• ${mensaje}`)
      })
    }
  } else if (error.message) {
    // Si es un error genérico
    erroresArray.push(`• ${error.message}`)
  } else {
    erroresArray.push('• Ocurrió un error desconocido')
  }
  
  // Mostrar alerta con mejor formato
  const mensajeFinal = erroresArray.join('\n')
  alert(`❌ Error de validación:\n\n${mensajeFinal}`)
}

// Computed para validación del formulario
const formularioValido = computed(() => {
  const camposObligatorios = vacuna.tipo_vacuna_id && 
    vacuna.fecha_aplicacion && 
    vacuna.numero_dosis && 
    vacuna.lote_serie
    
  // Para registro, el medio de envío es obligatorio
  if (!esEdicion.value) {
    return camposObligatorios && vacuna.medio_envio
  }
  
  // Para edición, solo los campos básicos son obligatorios
  return camposObligatorios
})

// Obtener ID del usuario dueño de la mascota
const usuarioId = computed(() => {
  return mascotaData.value?.usuario_id || null
})

// Función para obtener nombre del medio seleccionado
const obtenerNombreMedio = (medioId) => {
  const medios = {
    email: 'Email',
    whatsapp: 'WhatsApp',
    telegram: 'Telegram'
  }
  return medios[medioId] || medioId
}

// Obtener nombre del centro seleccionado
const obtenerNombreCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === vacuna.centro_veterinario_id)
  return centro ? centro.nombre : 'Centro no encontrado'
}

// Obtener dirección del centro seleccionado
const obtenerDireccionCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === vacuna.centro_veterinario_id)
  return centro ? centro.direccion : ''
}

// Obtener nombre del tipo de vacuna
const obtenerNombreTipoVacuna = () => {
  const tipo = tiposVacuna.value.find(t => t.id == vacuna.tipo_vacuna_id)
  return tipo ? tipo.nombre : 'No seleccionado'
}

// Formatear fecha
const formatFecha = (fecha) => {
  if (!fecha) return 'No especificada'
  return new Date(fecha).toLocaleDateString('es-ES')
}

// Cargar datos de la mascota para obtener el usuario_id
const cargarDatosMascota = async () => {
  try {
    console.log('🔄 Cargando datos de mascota con ID:', mascotaId.value)
    
    if (!mascotaId.value) {
      console.warn('⚠️ No hay mascotaId para cargar datos')
      return
    }
    
    const response = await fetch(`/api/mascotas/${mascotaId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Respuesta completa de mascota:', result)
    
    if (result.success && result.data) {
      mascotaData.value = result.data
      console.log('✅ Datos de mascota cargados:', mascotaData.value)
      console.log('👤 Usuario ID encontrado:', mascotaData.value.usuario_id)
      errorCargandoMascota.value = null
    } else {
      console.warn('❌ No se encontraron datos de mascota:', result)
      mascotaData.value = null
      errorCargandoMascota.value = result.message || 'Error al cargar datos de la mascota'
    }
  } catch (error) {
    console.error('❌ Error cargando datos de mascota:', error)
    mascotaData.value = null
    errorCargandoMascota.value = error.message
  }
}

// Cargar tipos de vacuna
const cargarTiposVacuna = async () => {
  try {
    const response = await fetch('/api/tipos-vacuna', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    
    if (result.success && result.data) {
      tiposVacuna.value = result.data
      console.log('💉 Tipos de vacuna cargados:', tiposVacuna.value.length)
    } else {
      console.warn('No se encontraron tipos de vacuna:', result)
      tiposVacuna.value = []
    }
  } catch (error) {
    console.error('Error cargando tipos de vacuna:', error)
    alert('Error al cargar los tipos de vacuna: ' + error.message)
  }
}

// Cargar centros veterinarios
const cargarCentrosVeterinarios = async () => {
  try {
    const response = await fetch('/api/centros-veterinarios', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    centrosVeterinarios.value = result.data || []
    console.log('🏥 Centros veterinarios cargados:', centrosVeterinarios.value.length)
  } catch (error) {
    console.error('Error cargando centros veterinarios:', error)
    alert('Error al cargar los centros veterinarios')
  }
}

// Cargar datos de vacuna existente (para edición)
const cargarVacunaExistente = async () => {
  if (!vacunaId.value) return
  
  try {
    console.log('🔄 Cargando datos de vacuna con ID:', vacunaId.value)
    
    const response = await fetch(`/api/vacunas/${vacunaId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Respuesta de vacuna:', result)
    
    if (result.success && result.data) {
      const datosVacuna = result.data
      
      // Actualizar el objeto vacuna con los datos existentes
      Object.assign(vacuna, {
        tipo_vacuna_id: datosVacuna.tipo_vacuna_id,
        fecha_aplicacion: datosVacuna.fecha_aplicacion?.split('T')[0] || '',
        numero_dosis: datosVacuna.numero_dosis,
        lote_serie: datosVacuna.lote_serie,
        centro_veterinario_id: datosVacuna.centro_veterinario_id,
        fecha_proxima_dosis: datosVacuna.fecha_proxima_dosis?.split('T')[0] || '',
        medio_envio: datosVacuna.medio_envio || '',
      })
      
      console.log('✅ Datos de vacuna cargados:', vacuna)
    } else {
      console.warn('❌ No se encontraron datos de vacuna:', result)
      alert('No se pudo cargar la vacuna a editar: ' + (result.message || 'Error desconocido'))
      
      // Redirigir a la página anterior
      if (mascotaId.value) {
        router.push({
          name: 'veterinario-vacunas',
          params: { id: mascotaId.value }
        })
      }
    }
  } catch (error) {
    console.error('❌ Error cargando datos de vacuna:', error)
    alert('Error al cargar la vacuna: ' + error.message)
    
    // Redirigir a la página anterior
    if (mascotaId.value) {
      router.push({
        name: 'veterinario-vacunas',
        params: { id: mascotaId.value }
      })
    }
  }
}

const onTipoVacunaChange = () => {
  const tipoSeleccionado = tiposVacuna.value.find(t => t.id == vacuna.tipo_vacuna_id)
  if (tipoSeleccionado) {
    console.log('Tipo seleccionado:', tipoSeleccionado)
  }
}

// Abrir overlay externo
const abrirOverlayCentros = () => {
  mostrarOverlayCentros.value = true
}

// Seleccionar centro desde overlay
const seleccionarCentro = (centro) => {
  vacuna.centro_veterinario_id = centro.id
  mostrarOverlayCentros.value = false
}

// Navegar al registro de nuevo tipo
const abrirRegistroTipoVacuna = () => {
  const query = {
    from: esEdicion.value ? `/editar/vacuna/${vacunaId.value}` : `/registro/vacuna/${mascotaId.value}`,
    mascotaId: mascotaId.value
  }
  
  router.push({
    path: '/registro/registroTipoVacuna',
    query
  })
}

// Mostrar modal de confirmación
const mostrarModalConfirmacion = () => {
  if (!formularioValido.value) {
    alert('Por favor complete todos los campos obligatorios')
    return
  }
  
  mostrarModal.value = true
}

// Cerrar modal
const cerrarModal = () => {
  mostrarModal.value = false
}

// Confirmar acción (registrar o actualizar)
const confirmarAccion = () => {
  if (esEdicion.value) {
    actualizarVacuna()
  } else {
    registrarVacunacion()
  }
}

// Procesar formulario (ahora solo muestra el modal)
const procesarFormulario = () => {
  mostrarModalConfirmacion()
}

// Registrar vacunación
const registrarVacunacion = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()
    
    // Limpiar errores previos
    erroresValidacion.value = {}
    mostrarErrores.value = false

    console.log('📤 Enviando datos a servidor para registro:', vacuna)
    console.log('📤 Mascota ID:', mascotaId.value)

    if (!mascotaId.value) {
      throw new Error('No se encontró el ID de la mascota')
    }

    const response = await fetch(`/api/mascotas/${mascotaId.value}/vacunas`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(vacuna)
    })

    console.log('📨 Status:', response.status)
    
    const responseText = await response.text()
    console.log('📄 Respuesta cruda:', responseText)

    if (!responseText.trim()) {
      throw new Error('El servidor devolvió una respuesta vacía')
    }

    let result
    try {
      result = JSON.parse(responseText)
    } catch (parseError) {
      console.error('No se pudo parsear como JSON:', responseText)
      throw new Error('El servidor no devolvió JSON válido.')
    }

    // Manejar específicamente el error 422 (Validación)
    if (response.status === 422) {
      mostrarErrorValidacion({ response: { status: 422, data: result } })
      return
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      // Mostrar mensaje de éxito incluyendo información del envío si existe
      let mensajeExito = '✅ Vacuna registrada exitosamente'
      if (result.data?.envio_exitoso === true) {
        mensajeExito += ' y certificado enviado'
      } else if (result.data?.envio_exitoso === false) {
        mensajeExito += ' (pero hubo un problema al enviar el certificado)'
      }
      
      alert(mensajeExito)
      
      // Redirigir a la lista de vacunas
      router.push({
        name: 'veterinario-vacunas',
        params: { id: mascotaId.value },
        query: {
          from: 'registroVacuna',
          currentTab: 'Preventivo',
          ts: Date.now()
        }
      })
    } else {
      mostrarErrorValidacion({ message: result.message || 'Error al registrar la vacuna' })
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    mostrarErrorValidacion(error)
  } finally {
    procesando.value = false
  }
}

// Actualizar vacunación existente
const actualizarVacuna = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()

    console.log('📤 Actualizando vacuna con ID:', vacunaId.value)
    console.log('📤 Datos a enviar:', vacuna)

    const response = await fetch(`/api/vacunas/${vacunaId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(vacuna)
    })

    console.log('📨 Status:', response.status)
    
    const responseText = await response.text()
    console.log('📄 Respuesta cruda:', responseText)

    if (!responseText.trim()) {
      throw new Error('El servidor devolvió una respuesta vacía')
    }

    let result
    try {
      result = JSON.parse(responseText)
    } catch (parseError) {
      console.error('No se pudo parsear como JSON:', responseText)
      throw new Error('El servidor no devolvió JSON válido.')
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      alert('✅ Vacuna actualizada exitosamente')
      
      const mascotaIdParaRedireccion = mascotaId.value || result.data?.mascota_id || result.data?.procesoMedico?.mascota_id
      
      if (mascotaIdParaRedireccion) {
        router.push({
          name: 'veterinario-vacunas',
          params: { id: mascotaIdParaRedireccion },
          query: {
            from: 'editarVacuna',
            currentTab: 'Preventivo',
            ts: Date.now()
          }
        })
      } else {
        router.push({ name: 'veterinario-vacunas', params: { id: '0' } })
      }
    } else {
      alert('Error al actualizar la vacuna: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al actualizar la vacuna: ' + error.message)
  } finally {
    procesando.value = false
  }
}

// Ejemplo de validación antes de mostrar el formulario
async function validarTipoVacuna(mascotaId, tipoVacunaId) {
    try {
        const response = await axios.get(
            `/api/mascotas/${mascotaId}/validar-vacuna/${tipoVacunaId}`
        );
        
        if (response.data.valido) {
            // Mostrar formulario
            mostrarFormularioVacuna();
        } else {
            // Mostrar errores
            alert(response.data.errors.join('\n'));
        }
    } catch (error) {
        console.error('Error en validación:', error);
    }
}

// Obtener lista de vacunas válidas para un select
async function cargarVacunasValidas(mascotaId) {
    try {
        const response = await axios.get(
            `/api/mascotas/${mascotaId}/tipos-vacuna-validos`
        );
        
        // Mostrar solo las válidas en el select
        const select = document.getElementById('tipo_vacuna_id');
        select.innerHTML = '';
        
        response.data.data.validos.forEach(vacuna => {
            const option = document.createElement('option');
            option.value = vacuna.id;
            option.textContent = vacuna.nombre;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error cargando vacunas válidas:', error);
    }
}

const cancelar = () => {
  const mascotaIdParaRedireccion = mascotaId.value
  
  if (mascotaIdParaRedireccion) {
    router.push({
      name: 'veterinario-vacunas',
      params: { id: mascotaIdParaRedireccion },
      query: {
        from: esEdicion.value ? 'cancelarEditarVacuna' : 'cancelarRegistroVacuna',
        currentTab: 'Preventivo',
        ts: Date.now()
      }
    })
  } else {
    router.push({ name: 'veterinario-vacunas', params: { id: '0' } })
  }
}

// Verificar autenticación y cargar datos
onMounted(async () => {
  console.log('🚀 Iniciando componente RegistrarVacuna')
  
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      router.push('/login')
      return
    }
  }

  // Si es edición, cargar datos de la vacuna primero
  if (esEdicion.value) {
    await cargarVacunaExistente()
  }

  // Cargar datos en orden
  if (mascotaId.value) {
    await cargarDatosMascota()
    
    if (errorCargandoMascota.value) {
      console.error('❌ Error al cargar mascota:', errorCargandoMascota.value)
      alert('Error al cargar datos de la mascota: ' + errorCargandoMascota.value)
      return
    }
  }

  await Promise.all([
    cargarTiposVacuna(),
    cargarCentrosVeterinarios()
  ])

  // Establecer fecha actual como predeterminada solo si es registro nuevo y no hay fecha
  if (!esEdicion.value && !vacuna.fecha_aplicacion) {
    const hoy = new Date().toISOString().split('T')[0]
    vacuna.fecha_aplicacion = hoy
  }
  
  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
})
</script>
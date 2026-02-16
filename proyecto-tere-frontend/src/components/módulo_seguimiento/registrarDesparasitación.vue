<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Desparasitación' : 'Registrar Desparasitación' }}</h1>

    <form @submit.prevent="procesarFormulario" class="space-y-4">
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
            <label class="block font-medium">Fecha de la desparasitación</label>
            <input v-model="desparasitacion.fecha" type="date" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Tipo de desparasitación</label>
            <div class="flex gap-2">
              <select
                v-model="desparasitacion.tipo_desparasitacion_id"
                required
                class="w-full border rounded p-2"
                @change="onTipoDesparasitacionChange"
              >
                <option value="">Seleccione un tipo de desparasitación</option>
                <option
                  v-for="tipo in tiposDesparasitacion"
                  :key="tipo.id"
                  :value="tipo.id"
                >
                  {{ tipo.nombre }}
                </option>
              </select>

              <!-- Botón de + Tipo -->
              <button 
                type="button"
                @click="abrirRegistroTipoDesparasitacion"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo 
              </button>
            </div>
          </div>

          <div>
            <label class="block font-medium">Nombre del desparasitante</label>
            <input v-model="desparasitacion.nombre_producto" type="text" required class="w-full border rounded p-2" placeholder="Nombre comercial o genérico" />
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">
              Centro Veterinario donde se realizó
            </label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="desparasitacion.centro_veterinario_id"
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
            <label class="block font-medium">Dosis aplicada</label>
            <input v-model="desparasitacion.dosis" type="text" required class="w-full border rounded p-2" placeholder="Ej: 1 comprimido, 0.5 ml, etc." />
          </div>

          <div>
            <label class="block font-medium">Frecuencia de renovación</label>
            <div class="flex">
              <input v-model="desparasitacion.frecuencia_valor" type="number" min="1" required class="w-1/2 border rounded-l p-2" placeholder="Cantidad" />
              <select v-model="desparasitacion.frecuencia_unidad" required class="w-1/2 border rounded-r p-2">
                <option value="dias">Días</option>
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
              </select>
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
          <label class="block font-medium">Peso de la mascota (kg)</label>
          <input v-model="desparasitacion.peso" type="number" step="0.1" min="0" class="w-full border rounded p-2" />
        </div>

        <div>
          <label class="block font-medium">Próxima fecha sugerida</label>
          <input v-model="desparasitacion.proxima_fecha" type="date" class="w-full border rounded p-2" />
        </div>
      </div>

      <div class="mt-4">
        <label class="block font-medium">Observaciones</label>
        <textarea 
          v-model="desparasitacion.observaciones" 
          class="w-full border rounded p-2" 
          rows="3" 
          placeholder="Observaciones adicionales..."
        ></textarea>
      </div>

      <!-- Selección del medio de envío -->
      <div class="mt-8">
        <CarruselMedioEnvio 
          v-if="usuarioId" 
          :usuario-id="usuarioId"
          :modo-edicion="esEdicion"
          :medio-seleccionado-inicial="desparasitacion.medio_envio"
          @update:medio="desparasitacion.medio_envio = $event" 
        />
        
        <div v-else class="text-center py-4">
          <p class="text-gray-500">Cargando información del dueño...</p>
        </div>

        <div v-if="desparasitacion.medio_envio" class="mt-4 text-center text-gray-700">
          <span class="font-semibold">
            {{ esEdicion ? 'Medio de envío utilizado:' : 'Medio seleccionado:' }}
          </span>
          <span class="ml-1 text-blue-600 font-medium">
            {{ obtenerNombreMedio(desparasitacion.medio_envio) }}
          </span>
          <p v-if="esEdicion" class="text-sm text-gray-500 mt-1">
            (En modo edición el medio de envío no se puede cambiar)
          </p>
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
          {{ procesando ? 'Procesando...' : (esEdicion ? 'Actualizar Desparasitación' : 'Registrar Desparasitación') }}
        </button>
      </div>
    </form>

    <!-- Agrega esto justo después del título principal y antes del formulario -->
    <div 
      v-if="Object.keys(erroresValidacion).length > 0" 
      class="mb-6 rounded-md bg-red-50 p-4 border border-red-200"
    >
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
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
          <div class="mt-4">
            <div class="-mx-2 -my-1.5 flex">
              <button
                type="button"
                @click="limpiarErrores"
                class="ml-3 bg-red-50 px-2 py-1.5 rounded-md text-sm font-medium text-red-800 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-red-50 focus:ring-red-600"
              >
                Entendido
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Componente externo del overlay -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="desparasitacion.centro_veterinario_id"
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
            <span class="font-semibold">Tipo de desparasitación:</span> {{ obtenerNombreTipoDesparasitacion() }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Fecha:</span> {{ formatFecha(desparasitacion.fecha) }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Producto:</span> {{ desparasitacion.nombre_producto }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Dosis:</span> {{ desparasitacion.dosis }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Frecuencia:</span> {{ desparasitacion.frecuencia_valor }} {{ obtenerUnidadTexto(desparasitacion.frecuencia_unidad) }}
          </p>
          <p v-if="desparasitacion.centro_veterinario_id" class="text-gray-700 mb-2">
            <span class="font-semibold">Centro veterinario:</span> {{ obtenerNombreCentroSeleccionado() }}
          </p>
          <p v-if="desparasitacion.peso" class="text-gray-700 mb-2">
            <span class="font-semibold">Peso:</span> {{ desparasitacion.peso }} kg
          </p>
          <p v-if="desparasitacion.proxima_fecha" class="text-gray-700 mb-2">
            <span class="font-semibold">Próxima fecha:</span> {{ formatFecha(desparasitacion.proxima_fecha) }}
          </p>
          <p v-if="desparasitacion.observaciones" class="text-gray-700 mb-2">
            <span class="font-semibold">Observaciones:</span> {{ desparasitacion.observaciones }}
          </p>
          <p v-if="desparasitacion.medio_envio" class="text-gray-700">
            <span class="font-semibold">Medio de envío:</span> {{ obtenerNombreMedio(desparasitacion.medio_envio) }}
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
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import SeleccionCentroVeterinario from '@/components/ElementosGraficos/SeleccionCentroVeterinario.vue'
import CarruselMedioEnvio from '@/components/ElementosGraficos/CarruselMedioEnvio.vue'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Estados reactivos
const tiposDesparasitacion = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)
const mostrarModal = ref(false)

// En la sección de estados reactivos, agrega:
const erroresValidacion = ref({})

const props = defineProps({
  desparasitacionId: {
    type: [String, Number],
    default: null
  },
  mascotaId: {
    type: [String, Number],
    default: null
  }
})

// Determinar si es edición o registro
const esEdicion = computed(() => {
  return !!props.desparasitacionId || !!route.params.desparasitacionId
})

const desparasitacionId = computed(() => {
  return route.params.desparasitacionId || null
})

const mascotaId = computed(() => {
  console.log('🔍 Route query:', route.query)
  console.log('🔍 Route params:', route.params)
  
  const id = props.mascotaId || route.query.mascotaId || route.params.mascotaId || route.params.id
  
  console.log('📌 Mascota ID encontrado:', id)
  return id
})

// Datos del formulario
const desparasitacion = reactive({
  tipo_desparasitacion_id: '',
  fecha: '',
  nombre_producto: '',
  dosis: '',
  frecuencia_valor: '',
  frecuencia_unidad: 'dias',
  peso: null,
  proxima_fecha: '',
  observaciones: '',
  centro_veterinario_id: '',
  medio_envio: '',
})

// Computed para validación del formulario
const formularioValido = computed(() => {
  const camposObligatorios = desparasitacion.tipo_desparasitacion_id && 
    desparasitacion.fecha && 
    desparasitacion.nombre_producto && 
    desparasitacion.dosis && 
    desparasitacion.frecuencia_valor && 
    desparasitacion.frecuencia_unidad
  
  // Para registro, el medio de envío es obligatorio
  if (!esEdicion.value) {
    return camposObligatorios && desparasitacion.medio_envio
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
  const centro = centrosVeterinarios.value.find(c => c.id === desparasitacion.centro_veterinario_id)
  return centro ? centro.nombre : 'Centro no encontrado'
}

// Obtener dirección del centro seleccionado
const obtenerDireccionCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === desparasitacion.centro_veterinario_id)
  return centro ? centro.direccion : ''
}

// Obtener nombre del tipo de desparasitación
const obtenerNombreTipoDesparasitacion = () => {
  const tipo = tiposDesparasitacion.value.find(t => t.id == desparasitacion.tipo_desparasitacion_id)
  return tipo ? tipo.nombre : 'No seleccionado'
}

// Obtener texto de la unidad de frecuencia
const obtenerUnidadTexto = (unidad) => {
  const unidades = {
    dias: 'días',
    semanas: 'semanas',
    meses: 'meses'
  }
  return unidades[unidad] || unidad
}

// Formatear fecha
const formatFecha = (fecha) => {
  if (!fecha) return 'No especificada'
  return new Date(fecha).toLocaleDateString('es-ES')
}

// Cargar datos de la mascota para obtener el usuario_id
const cargarDatosMascota = async () => {
  try {
    // VALIDAR QUE mascotaId EXISTA
    if (!mascotaId.value) {
      throw new Error('No se pudo obtener el ID de la mascota')
    }

    console.log('🔄 Cargando datos de mascota con ID:', mascotaId.value)
    
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

// Cargar tipos de desparasitación
const cargarTiposDesparasitacion = async () => {
  try {
    const response = await fetch('/api/tipos-desparasitacion', {
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
      tiposDesparasitacion.value = result.data
      console.log('💊 Tipos de desparasitación cargados:', tiposDesparasitacion.value.length)
    } else {
      console.warn('No se encontraron tipos de desparasitación:', result)
      tiposDesparasitacion.value = []
    }
  } catch (error) {
    console.error('Error cargando tipos de desparasitación:', error)
    alert('Error al cargar los tipos de desparasitación: ' + error.message)
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

// Cargar datos de desparasitación existente (para edición)
const cargarDesparasitacionExistente = async () => {
  if (!desparasitacionId.value) return
  
  try {
    console.log('🔄 Cargando datos de desparasitación con ID:', desparasitacionId.value)
    
    const response = await fetch(`/api/desparasitaciones/${desparasitacionId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Respuesta de desparasitación:', result)
    
    if (result.success && result.data) {
      const datosDesparasitacion = result.data
      
      // Actualizar el objeto desparasitacion con los datos existentes
      Object.assign(desparasitacion, {
        tipo_desparasitacion_id: datosDesparasitacion.tipo_desparasitacion_id,
        fecha: datosDesparasitacion.fecha?.split('T')[0] || '',
        nombre_producto: datosDesparasitacion.nombre_producto,
        dosis: datosDesparasitacion.dosis,
        frecuencia_valor: datosDesparasitacion.frecuencia_valor,
        frecuencia_unidad: datosDesparasitacion.frecuencia_unidad,
        peso: datosDesparasitacion.peso,
        proxima_fecha: datosDesparasitacion.proxima_fecha?.split('T')[0] || '',
        observaciones: datosDesparasitacion.observaciones,
        centro_veterinario_id: datosDesparasitacion.centro_veterinario_id,
        medio_envio: datosDesparasitacion.medio_envio || '',
      })
      
      console.log('✅ Datos de desparasitación cargados:', desparasitacion)
    } else {
      console.warn('❌ No se encontraron datos de desparasitación:', result)
      alert('No se pudo cargar la desparasitación a editar: ' + (result.message || 'Error desconocido'))
      
      // Redirigir a la página anterior
      if (mascotaId.value) {
        router.push({
          name: 'veterinario-desparasitaciones',
          params: { id: mascotaId.value }
        })
      }
    }
  } catch (error) {
    console.error('❌ Error cargando datos de desparasitación:', error)
    alert('Error al cargar la desparasitación: ' + error.message)
    
    // Redirigir a la página anterior
    if (mascotaId.value) {
      router.push({
        name: 'veterinario-desparasitaciones',
        params: { id: mascotaId.value }
      })
    }
  }
}

// Abrir overlay externo
const abrirOverlayCentros = () => {
  mostrarOverlayCentros.value = true
}

// Seleccionar centro desde overlay
const seleccionarCentro = (centro) => {
  desparasitacion.centro_veterinario_id = centro.id
  mostrarOverlayCentros.value = false
}

// Navegar al registro de nuevo tipo
const abrirRegistroTipoDesparasitacion = () => {
  const query = {
    from: esEdicion.value ? `/editar/desparasitacion/${desparasitacionId.value}` : `/registro/desparasitacion/${mascotaId.value}`,
    mascotaId: mascotaId.value
  }
  
  router.push({
    path: '/registro/registroTipoDesparasitacion',
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
    actualizarDesparasitacion()
  } else {
    registrarDesparasitacion()
  }
}

// Procesar formulario (ahora solo muestra el modal)
const procesarFormulario = () => {
  mostrarModalConfirmacion()
}

// Registrar desparasitación
const registrarDesparasitacion = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()

    // Validar que se seleccionó un medio de envío
    if (!desparasitacion.medio_envio) {
      alert('Por favor seleccione un medio de envío para el certificado')
      return
    }

    console.log('📤 Enviando datos a servidor para registro:', desparasitacion)
    console.log('📤 Mascota ID:', mascotaId.value)

    if (!mascotaId.value) {
      throw new Error('No se encontró el ID de la mascota')
    }

    const response = await fetch(`/api/mascotas/${mascotaId.value}/desparasitaciones`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(desparasitacion)
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

    // Manejo específico de error 422 (Validación)
    if (response.status === 422) {
      mostrarErroresValidacion(result.errors)
      return
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      router.push({
        name: 'veterinario-desparasitaciones',
        params: { id: mascotaId.value },
        query: {
          from: 'registroDesparasitacion',
          currentTab: 'Preventivo',
          ts: Date.now()
        }
      })
    } else {
      mostrarMensajeError('Error al registrar la desparasitación: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    mostrarMensajeError('Error al registrar la desparasitación: ' + error.message)
  } finally {
    procesando.value = false
  }
}

// Función para mostrar errores de validación de forma amigable
// Función para mostrar errores de validación de forma amigable
const mostrarErroresValidacion = (errors) => {
  console.log('📝 Errores de validación recibidos:', errors)
  
  // Almacenar los errores en la variable reactiva
  erroresValidacion.value = errors
  
  // También puedes hacer scroll al primer campo con error
  const primerCampoConError = Object.keys(errors)[0]
  if (primerCampoConError) {
    const elementoError = document.querySelector(`[name="${primerCampoConError}"]`) || 
                          document.querySelector(`[v-model*="${primerCampoConError}"]`)
    if (elementoError) {
      // Scroll suave a la posición del formulario
      const formulario = document.querySelector('form')
      if (formulario) {
        formulario.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
      
      setTimeout(() => {
        elementoError.scrollIntoView({ behavior: 'smooth', block: 'center' })
        elementoError.focus()
        
        // Agregar clase de error al elemento
        elementoError.classList.add('border-red-500', 'ring-2', 'ring-red-200')
        
        // Remover la clase después de 3 segundos
        setTimeout(() => {
          elementoError.classList.remove('border-red-500', 'ring-2', 'ring-red-200')
        }, 3000)
      }, 300)
    }
  }
}

// También agrega una función para limpiar los errores cuando el usuario cambia algo
const limpiarErrores = () => {
  erroresValidacion.value = {}
}

// Modifica onTipoDesparasitacionChange para limpiar errores cuando cambia el tipo
const onTipoDesparasitacionChange = () => {
  limpiarErrores()
  const tipoSeleccionado = tiposDesparasitacion.value.find(t => t.id == desparasitacion.tipo_desparasitacion_id)
  if (tipoSeleccionado) {
    console.log('Tipo seleccionado:', tipoSeleccionado)
  }
}

// También agrega watchers para limpiar errores cuando cambian otros campos
// Puedes agregar esto al final del script
watch(() => desparasitacion.tipo_desparasitacion_id, limpiarErrores)
watch(() => desparasitacion.nombre_producto, limpiarErrores)
watch(() => desparasitacion.dosis, limpiarErrores)
// Agrega más watchers para otros campos si es necesario

// Actualizar desparasitación existente
const actualizarDesparasitacion = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()

    console.log('📤 Actualizando desparasitación con ID:', desparasitacionId.value)
    console.log('📤 Datos a enviar:', desparasitacion)

    const response = await fetch(`/api/desparasitaciones/${desparasitacionId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(desparasitacion)
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
      alert('✅ Desparasitación actualizada exitosamente')
      
      const mascotaIdParaRedireccion = mascotaId.value || result.data?.mascota_id || result.data?.procesoMedico?.mascota_id
      
      if (mascotaIdParaRedireccion) {
        router.push({
          name: 'veterinario-desparasitaciones',
          params: { id: mascotaIdParaRedireccion },
          query: {
            from: 'editarDesparasitacion',
            currentTab: 'Preventivo',
            ts: Date.now()
          }
        })
      } else {
        router.push({ name: 'veterinario-desparasitaciones', params: { id: '0' } })
      }
    } else {
      alert('Error al actualizar la desparasitación: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al actualizar la desparasitación: ' + error.message)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  const mascotaIdParaRedireccion = mascotaId.value
  
  if (mascotaIdParaRedireccion) {
    router.push({
      name: 'veterinario-desparasitaciones',
      params: { id: mascotaIdParaRedireccion },
      query: {
        from: esEdicion.value ? 'cancelarEditarDesparasitacion' : 'cancelarRegistroDesparasitacion',
        currentTab: 'Preventivo',
        ts: Date.now()
      }
    })
  } else {
    router.push({ name: 'veterinario-desparasitaciones', params: { id: '0' } })
  }
}

// Verificar autenticación y cargar datos
onMounted(async () => {
  console.log('🚀 Iniciando componente RegistrarDesparasitacion')
  
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      router.push('/login')
      return
    }
  }

  // Si es edición, cargar datos de la desparasitación primero
  if (esEdicion.value) {
    await cargarDesparasitacionExistente()
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
    cargarTiposDesparasitacion(),
    cargarCentrosVeterinarios()
  ])

  // Establecer fecha actual como predeterminada solo si es registro nuevo y no hay fecha
  if (!esEdicion.value && !desparasitacion.fecha) {
    const hoy = new Date().toISOString().split('T')[0]
    desparasitacion.fecha = hoy
  }
  
  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
})
</script>
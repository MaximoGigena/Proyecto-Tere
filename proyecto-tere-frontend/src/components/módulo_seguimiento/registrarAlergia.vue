<!-- registrarAlergia.vue - Versión final con registro y edición -->
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
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Alergia/Sensibilidad' : 'Registrar Alergia/Sensibilidad' }}</h1>

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
          <!-- Selección de Tipo de Alergia -->
          <div>
            <label class="block font-medium">Tipo de alergia/sensibilidad</label>
            <div class="flex gap-2">
              <select
                v-model="alergia.tipo_alergia_id"
                required
                class="w-full border rounded p-2"
                @change="onTipoAlergiaChange"
              >
                <option value="">Seleccione un tipo de alergia</option>
                <option
                  v-for="tipo in tiposAlergia"
                  :key="tipo.id"
                  :value="tipo.id"
                >
                  {{ tipo.nombre }}
                </option>
              </select>

              <!-- Botón de + Tipo -->
              <button
                type="button"
                @click="abrirRegistroTipoAlergia"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo
              </button>
            </div>
          </div>

          <div>
            <label class="block font-medium">Fecha de detección/diagnóstico</label>
            <input
              v-model="alergia.fecha_deteccion"
              type="date"
              required
              class="w-full border rounded p-2"
              :max="hoy"
            />
          </div>

          <div>
            <label class="block font-medium">Gravedad estimada</label>
            <select
              v-model="alergia.gravedad"
              required
              class="w-full border rounded p-2"
            >
              <option value="">Seleccione una opción</option>
              <option value="leve">Leve</option>
              <option value="moderada">Moderada</option>
              <option value="grave">Grave</option>
            </select>
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">
              Centro Veterinario donde se realizó
            </label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="alergia.centro_veterinario_id"
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
            <label class="block font-medium">Reacción común observada</label>
            <input
              v-model="alergia.reaccion_comun"
              type="text"
              required
              class="w-full border rounded p-2"
              placeholder="Ej: Urticaria, vómitos, shock anafiláctico"
            />
          </div>

          <div>
            <label class="block font-medium">Sustancia/factor desencadenante</label>
            <input
              v-model="alergia.desencadenante"
              type="text"
              class="w-full border rounded p-2"
              placeholder="Si se conoce"
            />
          </div>

          <div>
            <label class="block font-medium">Estado actual</label>
            <select
              v-model="alergia.estado"
              required
              class="w-full border rounded p-2"
            >
              <option value="">Seleccione una opción</option>
              <option value="activa">Activa</option>
              <option value="superada">Superada</option>
              <option value="seguimiento">Bajo seguimiento</option>
            </select>
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

      <div class="grid grid-cols-1 gap-8 mt-6">
        <div>
          <label class="block font-medium mb-1">Conducta recomendada ante exposición</label>
          <textarea
            v-model="alergia.conducta_recomendada"
            rows="3"
            maxlength="500"
            class="w-full border rounded p-2 resize-none"
            placeholder="Recomendaciones específicas en caso de exposición al alérgeno"
          ></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">
            {{ alergia.conducta_recomendada?.length || 0 }}/500 caracteres
          </p>
        </div>

        <div>
          <label class="block font-medium mb-1">Recomendaciones para el tutor</label>
          <textarea
            v-model="alergia.recomendaciones_tutor"
            rows="3"
            maxlength="500"
            class="w-full border rounded p-2 resize-none"
            placeholder="Instrucciones para el dueño sobre manejo y prevención"
          ></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">
            {{ alergia.recomendaciones_tutor?.length || 0 }}/500 caracteres
          </p>
        </div>

        <div>
          <label class="block font-medium mb-1">Observaciones adicionales</label>
          <textarea
            v-model="alergia.observaciones"
            rows="2"
            maxlength="300"
            class="w-full border rounded p-2 resize-none"
            placeholder="Cualquier información adicional relevante"
          ></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">
            {{ alergia.observaciones?.length || 0 }}/300 caracteres
          </p>
        </div>
      </div>

      <!-- Selección del medio de envío -->
      <div class="mt-8">
        <!-- Para ambos casos (registro y edición) mostramos el carrusel -->
        <div v-if="usuarioId">
          <CarruselMedioEnvio 
            :usuario-id="usuarioId" 
            :modo-edicion="esEdicion"
            :medio-seleccionado-inicial="alergia.medio_envio"
            @update:medio="alergia.medio_envio = $event"
          />
          
          <div v-if="alergia.medio_envio" class="mt-4 text-center text-gray-700">
            <span class="font-semibold">
              {{ esEdicion ? 'Medio de envío utilizado:' : 'Medio seleccionado:' }}
            </span>
            <span class="ml-1 text-blue-600 font-medium">
              {{ obtenerNombreMedio(alergia.medio_envio) }}
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
          {{ procesando ? 'Procesando...' : (esEdicion ? 'Actualizar Alergia' : 'Registrar Alergia') }}
        </button>
      </div>
    </form>

    <!-- Componente externo del overlay -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="alergia.centro_veterinario_id"
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
            <span class="font-semibold">Tipo de alergia:</span> {{ obtenerNombreTipoAlergia() }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Fecha detección:</span> {{ formatFecha(alergia.fecha_deteccion) }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Gravedad:</span> {{ formatGravedad(alergia.gravedad) }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Reacción común:</span> {{ alergia.reaccion_comun }}
          </p>
          <p class="text-gray-700 mb-2">
            <span class="font-semibold">Estado:</span> {{ formatEstado(alergia.estado) }}
          </p>
          <p v-if="alergia.desencadenante" class="text-gray-700 mb-2">
            <span class="font-semibold">Desencadenante:</span> {{ alergia.desencadenante }}
          </p>
          <p v-if="alergia.centro_veterinario_id" class="text-gray-700 mb-2">
            <span class="font-semibold">Centro veterinario:</span> {{ obtenerNombreCentroSeleccionado() }}
          </p>
          <p v-if="alergia.medio_envio" class="text-gray-700">
            <span class="font-semibold">Medio de envío:</span> {{ obtenerNombreMedio(alergia.medio_envio) }}
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
  alergiaId: {
    type: [String, Number],
    default: null
  }
})

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Estados reactivos
const tiposAlergia = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)
const hoy = new Date().toISOString().split('T')[0]
const mostrarModal = ref(false)

// Determinar si es edición o registro
const esEdicion = computed(() => {
  return route.name === 'editarAlergia' || !!route.params.alergiaId
})

const alergiaId = computed(() => {
  return props.alergiaId || route.params.alergiaId || null
})

const mascotaId = computed(() => {
  return route.query.mascotaId || route.params.mascotaId || null
})

console.log('🔍 Route params:', route.params)
console.log('🔍 Route query:', route.query)
console.log('🔍 Es edición:', esEdicion.value)
console.log('🔍 Alergia ID:', alergiaId.value)
console.log('🔍 Mascota ID:', mascotaId.value)

// Datos del formulario
const alergia = reactive({
  tipo_alergia_id: '',
  fecha_deteccion: '',
  gravedad: '',
  reaccion_comun: '',
  estado: '',
  desencadenante: '',
  centro_veterinario_id: '',
  conducta_recomendada: '',
  recomendaciones_tutor: '',
  observaciones: '',
  medio_envio: '',
})

// Computed para validación del formulario
const formularioValido = computed(() => {
  const camposObligatorios = alergia.tipo_alergia_id && 
    alergia.fecha_deteccion && 
    alergia.gravedad && 
    alergia.reaccion_comun && 
    alergia.estado
    
  // Para registro, el medio de envío es obligatorio
  if (!esEdicion.value) {
    return camposObligatorios && alergia.medio_envio
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
  const centro = centrosVeterinarios.value.find(c => c.id === alergia.centro_veterinario_id)
  return centro ? centro.nombre : 'Centro no encontrado'
}

// Obtener dirección del centro seleccionado
const obtenerDireccionCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === alergia.centro_veterinario_id)
  return centro ? centro.direccion : ''
}

// Obtener nombre del tipo de alergia
const obtenerNombreTipoAlergia = () => {
  const tipo = tiposAlergia.value.find(t => t.id == alergia.tipo_alergia_id)
  return tipo ? tipo.nombre : 'No seleccionado'
}

// Formatear fecha
const formatFecha = (fecha) => {
  if (!fecha) return 'No especificada'
  return new Date(fecha).toLocaleDateString('es-ES')
}

// Formatear gravedad
const formatGravedad = (gravedad) => {
  const opciones = {
    leve: 'Leve',
    moderada: 'Moderada',
    grave: 'Grave'
  }
  return gravedad ? (opciones[gravedad] || gravedad) : 'No especificada'
}

// Formatear estado
const formatEstado = (estado) => {
  const opciones = {
    activa: 'Activa',
    superada: 'Superada',
    seguimiento: 'Bajo seguimiento'
  }
  return estado ? (opciones[estado] || estado) : 'No especificado'
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

// Cargar tipos de alergia
const cargarTiposAlergia = async () => {
  try {
    const response = await fetch('/api/tipos-alergia', {
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
      tiposAlergia.value = result.data
      console.log('🤧 Tipos de alergia cargados:', tiposAlergia.value.length)
    } else {
      console.warn('No se encontraron tipos de alergia:', result)
      tiposAlergia.value = []
    }
  } catch (error) {
    console.error('Error cargando tipos de alergia:', error)
    alert('Error al cargar los tipos de alergia: ' + error.message)
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

// Cargar datos de alergia existente (para edición)
const cargarAlergiaExistente = async () => {
  if (!alergiaId.value) return
  
  try {
    console.log('🔄 Cargando datos de alergia con ID:', alergiaId.value)
    console.log('🔍 Mascota ID:', mascotaId.value)

    // Necesitamos tanto el ID de la mascota como el de la alergia
    if (!mascotaId.value) {
      console.error('❌ No se encontró el ID de la mascota para cargar la alergia')
      return
    }

    const response = await fetch(`/api/mascotas/${mascotaId.value}/alergias/${alergiaId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('📦 Respuesta de alergia:', result)
    
    if (result.success && result.data) {
      const datosAlergia = result.data
      
      // Actualizar el objeto alergia con los datos existentes
      Object.assign(alergia, {
        tipo_alergia_id: datosAlergia.tipo_alergia_id,
        fecha_deteccion: datosAlergia.fecha_deteccion?.split('T')[0] || '',
        gravedad: datosAlergia.gravedad,
        reaccion_comun: datosAlergia.reaccion_comun,
        estado: datosAlergia.estado,
        desencadenante: datosAlergia.desencadenante || '',
        centro_veterinario_id: datosAlergia.centro_veterinario_id,
        conducta_recomendada: datosAlergia.conducta_recomendada || '',
        recomendaciones_tutor: datosAlergia.recomendaciones_tutor || '',
        observaciones: datosAlergia.observaciones || '',
        medio_envio: datosAlergia.medio_envio || '',
      })
      
      console.log('✅ Datos de alergia cargados:', alergia)
    } else {
      console.warn('❌ No se encontraron datos de alergia:', result)
      alert('No se pudo cargar la alergia a editar: ' + (result.message || 'Error desconocido'))
      
      // Redirigir a la página anterior
      if (mascotaId.value) {
        router.push({
          name: 'veterinario-alergias',
          params: { id: mascotaId.value }
        })
      }
    }
  } catch (error) {
    console.error('❌ Error cargando datos de alergia:', error)
    alert('Error al cargar la alergia: ' + error.message)
    
    // Redirigir a la página anterior
    if (mascotaId.value) {
      router.push({
        name: 'veterinario-alergias',
        params: { id: mascotaId.value }
      })
    }
  }
}

const onTipoAlergiaChange = () => {
  const tipoSeleccionado = tiposAlergia.value.find(t => t.id == alergia.tipo_alergia_id)
  if (tipoSeleccionado) {
    console.log('Tipo de alergia seleccionado:', tipoSeleccionado)
  }
}

// Abrir overlay externo
const abrirOverlayCentros = () => {
  mostrarOverlayCentros.value = true
}

// Seleccionar centro desde overlay
const seleccionarCentro = (centro) => {
  alergia.centro_veterinario_id = centro.id
  mostrarOverlayCentros.value = false
}

// Navegar al registro de nuevo tipo
const abrirRegistroTipoAlergia = () => {
  const query = {
    from: esEdicion.value ? `/editar/alergia/${alergiaId.value}` : `/registro/alergia/${mascotaId.value}`,
    mascotaId: mascotaId.value
  }
  
  router.push({
    path: '/registro/registroTipoAlergia',
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
    actualizarAlergia()
  } else {
    registrarAlergia()
  }
}

// Procesar formulario (ahora solo muestra el modal)
const procesarFormulario = () => {
  mostrarModalConfirmacion()
}

// Registrar alergia
const registrarAlergia = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()

    console.log('📤 Enviando datos a servidor para registro:', alergia)
    console.log('📤 Mascota ID:', mascotaId.value)

    if (!mascotaId.value) {
      throw new Error('No se encontró el ID de la mascota')
    }

    const response = await fetch(`/api/mascotas/${mascotaId.value}/alergias`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(alergia)
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
      alert('✅ Alergia registrada exitosamente')
      router.push({
        name: 'veterinario-alergias',
        params: { id: mascotaId.value },
        query: {
          from: 'registroAlergia',
          currentTab: 'Preventivo',
          ts: Date.now()
        }
      })
    } else {
      alert('Error al registrar la alergia: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al registrar la alergia: ' + error.message)
  } finally {
    procesando.value = false
  }
}

// Actualizar alergia existente
const actualizarAlergia = async () => {
  if (procesando.value) return

  try {
    procesando.value = true
    cerrarModal()

    console.log('📤 Actualizando alergia con ID:', alergiaId.value)
    console.log('📤 Mascota ID:', mascotaId.value)
    console.log('📤 Datos a enviar:', alergia)

    if (!mascotaId.value) {
      throw new Error('No se encontró el ID de la mascota para actualizar')
    }

    const response = await fetch(`/api/mascotas/${mascotaId.value}/alergias/${alergiaId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(alergia)
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
      alert('✅ Alergia actualizada exitosamente')
      
      router.push({
        name: 'veterinario-alergias',
        params: { id: mascotaId.value },
        query: {
          from: 'editarAlergia',
          currentTab: 'Preventivo',
          ts: Date.now()
        }
      })
    } else {
      alert('Error al actualizar la alergia: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al actualizar la alergia: ' + error.message)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  const mascotaIdParaRedireccion = mascotaId.value
  
  if (mascotaIdParaRedireccion) {
    router.push({
      name: 'veterinario-alergias',
      params: { id: mascotaIdParaRedireccion },
      query: {
        from: esEdicion.value ? 'cancelarEditarAlergia' : 'cancelarRegistroAlergia',
        currentTab: 'Preventivo',
        ts: Date.now()
      }
    })
  } else {
    router.push({ name: 'veterinario-alergias', params: { id: '0' } })
  }
}

// Verificar autenticación y cargar datos
onMounted(async () => {
  console.log('🚀 Iniciando componente RegistrarAlergia')
  
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      router.push('/login')
      return
    }
  }

  // Si es edición, cargar datos de la alergia primero
  if (esEdicion.value) {
    await cargarAlergiaExistente()
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
    cargarTiposAlergia(),
    cargarCentrosVeterinarios()
  ])

  // Establecer fecha actual como predeterminada solo si es registro nuevo y no hay fecha
  if (!esEdicion.value && !alergia.fecha_deteccion) {
    alergia.fecha_deteccion = hoy
  }
  
  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
})
</script>
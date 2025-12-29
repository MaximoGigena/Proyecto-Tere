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
    <h1 class="text-4xl font-bold mb-4">Registrar Alergia/Sensibilidad</h1>

    <form @submit.prevent="registrarAlergia" class="space-y-4">
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
        <CarruselMedioEnvio 
          v-if="usuarioId" 
          :usuario-id="usuarioId" 
          @update:medio="alergia.medio_envio = $event" 
        />
        
        <div v-else class="text-center py-4">
          <p class="text-gray-500">Cargando información del dueño...</p>
        </div>

        <div v-if="alergia.medio_envio" class="mt-4 text-center text-gray-700">
          <span class="font-semibold">Medio seleccionado:</span>
          <span class="ml-1 text-blue-600 font-medium">
            {{ obtenerNombreMedio(alergia.medio_envio) }}
          </span>
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
          type="submit"
          :disabled="procesando"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
        >
          {{ procesando ? 'Registrando...' : 'Registrar Alergia' }}
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
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import SeleccionCentroVeterinario from '@/components/ElementosGraficos/SeleccionCentroVeterinario.vue'
import CarruselMedioEnvio from '@/components/ElementosGraficos/CarruselMedioEnvio.vue'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const route = useRoute()
const mascotaId = route.query.mascotaId

console.log('🔍 Route query:', route.query)
console.log('🔍 Mascota ID from query:', mascotaId)

const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Estados reactivos
const tiposAlergia = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)
const hoy = new Date().toISOString().split('T')[0]

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

// Cargar datos de la mascota para obtener el usuario_id
const cargarDatosMascota = async () => {
  try {
    console.log('🔄 Cargando datos de mascota con ID:', mascotaId)
    
    const response = await fetch(`/api/mascotas/${mascotaId}`, {
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
  router.push({
    path: '/registro/registroTipoAlergia',
    query: {
      from: `/historialPreventivo/mascota/${mascotaId}/alergias/crear`,
      mascotaId
    }
  })
}

// Registrar alergia
const registrarAlergia = async () => {
  if (procesando.value) return

  try {
    procesando.value = true

    // Validar que se seleccionó un medio de envío
    if (!alergia.medio_envio) {
      alert('Por favor seleccione un medio de envío para el registro')
      return
    }

    // Validar campos obligatorios
    if (!alergia.tipo_alergia_id || !alergia.fecha_deteccion || !alergia.gravedad || 
        !alergia.reaccion_comun || !alergia.estado) {
      alert('Por favor complete todos los campos obligatorios')
      return
    }

    console.log('📤 Enviando datos a servidor:', alergia)

    const response = await fetch(`/api/mascotas/${mascotaId}/alergias`, {
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
      throw new Error('El servidor devolvió una respuesta vacía (posible redirección)')
    }

    let result
    try {
      result = JSON.parse(responseText)
    } catch (parseError) {
      console.error('No se pudo parsear como JSON:', responseText)
      throw new Error('El servidor no devolvió JSON válido. Respuesta: ' + responseText.substring(0, 100))
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      alert('Alergia registrada exitosamente')
      router.push({
        name: 'veterinario-alergias',
        params: { id: mascotaId },
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

const cancelar = () => {
  router.push({
    name: 'veterinario-alergias', 
    params: { id: mascotaId },
    query: {
      from: 'cancelarRegistroAlergia',
      currentTab: 'Preventivo',
      ts: Date.now()
    }
  })
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

  // Cargar datos en orden
  await cargarDatosMascota() // Primero cargar datos de mascota para obtener usuario_id
  
  if (errorCargandoMascota.value) {
    console.error('❌ Error al cargar mascota:', errorCargandoMascota.value)
    alert('Error al cargar datos de la mascota: ' + errorCargandoMascota.value)
    return
  }

  await cargarTiposAlergia()
  await cargarCentrosVeterinarios()

  // Establecer fecha actual como predeterminada
  alergia.fecha_deteccion = hoy
  
  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
})
</script>
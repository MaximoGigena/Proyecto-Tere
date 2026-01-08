<!-- registrarVacuna -->
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
    <h1 class="text-4xl font-bold mb-4">Registrar Vacunación</h1>

    <form @submit.prevent="registrarVacunacion" class="space-y-4">
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
        <CarruselMedioEnvio 
          v-if="usuarioId" 
          :usuario-id="usuarioId" 
          @update:medio="vacuna.medio_envio = $event" 
        />
        
        <div v-else class="text-center py-4">
          <p class="text-gray-500">Cargando información del dueño...</p>
        </div>

        <div v-if="vacuna.medio_envio" class="mt-4 text-center text-gray-700">
          <span class="font-semibold">Medio seleccionado:</span>
          <span class="ml-1 text-blue-600 font-medium">
            {{ obtenerNombreMedio(vacuna.medio_envio) }}
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
          {{ procesando ? 'Registrando...' : 'Registrar Vacunación' }}
        </button>
      </div>
    </form>

    <!-- Componente externo del overlay -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="vacuna.centro_veterinario_id"
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
const tiposVacuna = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)

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
  router.push({
    path: '/registro/registroTipoVacuna',
    query: {
      from: `/mascotas/${mascotaId}/vacunas/crear`,
      mascotaId
    }
  })
}

// Registrar vacunación
const registrarVacunacion = async () => {
  if (procesando.value) return

  try {
    procesando.value = true

    // Validar que se seleccionó un medio de envío
    if (!vacuna.medio_envio) {
      alert('Por favor seleccione un medio de envío para el certificado')
      return
    }

    if (!vacuna.tipo_vacuna_id || !vacuna.fecha_aplicacion || !vacuna.numero_dosis || !vacuna.lote_serie) {
      alert('Por favor complete todos los campos obligatorios')
      return
    }

    console.log('📤 Enviando datos a servidor:', vacuna)

    const response = await fetch(`/api/mascotas/${mascotaId}/vacunas`, {
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
      router.push({
        name: 'veterinario-vacunas',
        params: { id: mascotaId },
        query: {
          from: 'registroVacuna',
          currentTab: 'Preventivo',
          ts: Date.now()
        }
      })
    } else {
      alert('Error al registrar la vacuna: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al registrar la vacuna: ' + error.message)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  router.push({
    name: 'veterinario-vacunas', 
    params: { id: mascotaId },
    query: {
      from: 'cancelarRegistroVacuna',
      currentTab: 'Preventivo',
      ts: Date.now()
    }
  })
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

  // Cargar datos en orden
  await cargarDatosMascota() // Primero cargar datos de mascota para obtener usuario_id
  
  if (errorCargandoMascota.value) {
    console.error('❌ Error al cargar mascota:', errorCargandoMascota.value)
    alert('Error al cargar datos de la mascota: ' + errorCargandoMascota.value)
    return
  }

  await cargarTiposVacuna()
  await cargarCentrosVeterinarios()

  // Establecer fecha actual como predeterminada
  const hoy = new Date().toISOString().split('T')[0]
  vacuna.fecha_aplicacion = hoy
  
  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
})
</script>

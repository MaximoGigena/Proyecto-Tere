<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Desparasitación</h1>

    <form @submit.prevent="registrarDesparasitacion" class="space-y-4">
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
          @update:medio="desparasitacion.medio_envio = $event" 
        />
        
        <div v-else class="text-center py-4">
          <p class="text-gray-500">Cargando información del dueño...</p>
        </div>

        <div v-if="desparasitacion.medio_envio" class="mt-4 text-center text-gray-700">
          <span class="font-semibold">Medio seleccionado:</span>
          <span class="ml-1 text-blue-600 font-medium">
            {{ obtenerNombreMedio(desparasitacion.medio_envio) }}
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
          {{ procesando ? 'Registrando...' : 'Registrar Desparasitación' }}
        </button>
      </div>
    </form>

    <!-- Componente externo del overlay -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="desparasitacion.centro_veterinario_id"
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

// OBTENER mascotaId DE FORMA ROBUSTA
const mascotaId = computed(() => {
  console.log('🔍 Route query:', route.query)
  console.log('🔍 Route params:', route.params)
  
  // Intenta obtener de diferentes formas
  const id = route.query.mascotaId || route.params.mascotaId || route.params.id
  
  console.log('📌 Mascota ID encontrado:', id)
  return id
})

const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Estados reactivos
const tiposDesparasitacion = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)

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

// Cargar datos de la mascota para obtener el usuario_id
const cargarDatosMascota = async () => {
  try {
    // VALIDAR QUE mascotaId EXISTA
    if (!mascotaId.value) {
      throw new Error('No se pudo obtener el ID de la mascota')
    }

    console.log('🔄 Cargando datos de mascota con ID:', mascotaId.value)
    
    const response = await fetch(`/api/mascotas/${mascotaId.value}`, { // 👈 USAR .value
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

const onTipoDesparasitacionChange = () => {
  const tipoSeleccionado = tiposDesparasitacion.value.find(t => t.id == desparasitacion.tipo_desparasitacion_id)
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
  desparasitacion.centro_veterinario_id = centro.id
  mostrarOverlayCentros.value = false
}

// Navegar al registro de nuevo tipo
const abrirRegistroTipoDesparasitacion = () => {
  router.push({
    path: '/registro/registroTipoDesparasitacion',
    query: {
      from: `/mascotas/${mascotaId.value}/desparasitaciones/crear`, // 👈 USAR .value
      mascotaId: mascotaId.value // 👈 USAR .value
    }
  })
}

// Registrar desparasitación
const registrarDesparasitacion = async () => {
  if (procesando.value) return

  try {
    procesando.value = true

    // Validar que se seleccionó un medio de envío
    if (!desparasitacion.medio_envio) {
      alert('Por favor seleccione un medio de envío para el certificado')
      return
    }

    if (!desparasitacion.tipo_desparasitacion_id || !desparasitacion.fecha || 
        !desparasitacion.nombre_producto || !desparasitacion.dosis || 
        !desparasitacion.frecuencia_valor || !desparasitacion.frecuencia_unidad) {
      alert('Por favor complete todos los campos obligatorios')
      return
    }

    console.log('📤 Enviando datos a servidor:', desparasitacion)

    const response = await fetch(`/api/mascotas/${mascotaId.value}/desparasitaciones`, { // 👈 USAR .value
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
        name: 'veterinario-desparasitaciones',
        params: { id: mascotaId.value }, // 👈 USAR .value
        query: {
          from: 'registroDesparasitacion',
          currentTab: 'Preventivo',
          ts: Date.now()
        }
      })
    } else {
      alert('Error al registrar la desparasitación: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al registrar la desparasitación: ' + error.message)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  // USAR EL MISMO PATRÓN QUE EN REGISTRO EXITOSO
  router.push({
    name: 'veterinario-desparasitaciones', // 👈 Mismo nombre de ruta
    params: { id: mascotaId.value },
    query: {
      from: 'cancelarRegistroDesparasitacion',
      currentTab: 'Preventivo', // 👈 Mismo que registro exitoso
      ts: Date.now()
    }
  })
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

  // VALIDAR QUE mascotaId EXISTA ANTES DE CARGAR
  if (!mascotaId.value) {
    console.error('❌ No se pudo obtener el ID de la mascota')
    alert('Error: No se pudo identificar la mascota. Por favor, regrese al historial e intente nuevamente.')
    router.back()
    return
  }

  // Cargar datos en orden
  await cargarDatosMascota() // Primero cargar datos de mascota para obtener usuario_id
  
  if (errorCargandoMascota.value) {
    console.error('❌ Error al cargar mascota:', errorCargandoMascota.value)
    alert('Error al cargar datos de la mascota: ' + errorCargandoMascota.value)
    return
  }

  await cargarTiposDesparasitacion()
  await cargarCentrosVeterinarios()

  // Establecer fecha actual como predeterminada
  const hoy = new Date().toISOString().split('T')[0]
  desparasitacion.fecha = hoy
  
  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
})
</script>
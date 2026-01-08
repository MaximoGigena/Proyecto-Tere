<!-- registrarTerapia.vue -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Terapia</h1>

    <form @submit.prevent="registrarTerapia" class="space-y-4">
      <!-- DATOS OBLIGATORIOS -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Obligatorios</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna izquierda -->
        <div class="space-y-4">
          <!-- Selección de Tipo de Terapia -->
          <div>
            <label class="block font-medium">Tipo de terapia aplicada</label>
            <div class="flex gap-2">
              <select
                v-model="terapia.tipo_terapia_id"
                required
                class="w-full border rounded p-2"
                @change="onTipoTerapiaChange"
              >
                <option value="">Seleccione un tipo de terapia</option>
                <option
                  v-for="tipo in tiposTerapia"
                  :key="tipo.id"
                  :value="tipo.id"
                >
                  {{ tipo.nombre }}
                </option>
              </select>

              <!-- Botón de + Tipo -->
              <button
                type="button"
                @click="abrirRegistroTipoTerapia"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo
              </button>
            </div>
          </div>

          <div>
            <label class="block font-medium">Fecha de inicio de la terapia</label>
            <input v-model="terapia.fecha_inicio" type="date" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Frecuencia de las sesiones</label>
            <select v-model="terapia.frecuencia" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="diaria">Diaria</option>
              <option value="semanal">Semanal</option>
              <option value="quincenal">Quincenal</option>
              <option value="mensual">Mensual</option>
              <option value="personalizada">Personalizada</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Duración estimada del tratamiento</label>
            <input v-model="terapia.duracion_tratamiento" type="text" required class="w-full border rounded p-2" placeholder="Ej: 3 meses, 10 sesiones, etc." />
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">
              Centro Veterinario donde se realizó
            </label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="terapia.centro_veterinario_id"
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

        <!-- Columna derecha - Archivos -->
        <div>
          <label class="block font-medium mb-2">Archivos adjuntos</label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div
              v-for="(archivo, index) in archivos"
              :key="index"
              class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-full aspect-square"
              @click="!archivo.preview && activarInput(index)"
            >
              <button type="button" @click.stop="quitarArchivo(index)" v-if="archivo.preview" class="absolute top-1 right-1 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700 mt-35 -mr-2">
                <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-3xl" />
              </button>

              <input :ref="el => inputsArchivo[index] = el" type="file" @change="handleArchivo($event, index)" class="hidden" accept="image/*,.pdf,.doc,.docx" />

              <div v-if="archivo.preview" class="h-full flex flex-col">
                <img v-if="esImagen(archivo.archivo)" :src="archivo.preview" alt="Preview" class="w-full h-full object-cover rounded-md border-gray-300 mx-auto flex-grow" />
                <div v-else class="h-full flex items-center justify-center p-2">
                  <font-awesome-icon :icon="['fas', 'file']" class="text-5xl text-gray-500" />
                </div>
                <div class="text-xs truncate px-1">{{ archivo.archivo.name }}</div>
              </div>

              <div v-else class="text-green-400 mt-14">
                <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-4xl mb-2" />
                <div class="text-gray-400">Agregar archivo</div>
              </div>
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
          <label class="block font-medium">Fecha de finalización</label>
          <input v-model="terapia.fecha_fin" type="date" class="w-full border rounded p-2" :min="terapia.fecha_inicio" />
        </div>

        <div>
          <label class="block font-medium">Evolución observada</label>
          <select v-model="terapia.evolucion" class="w-full border rounded p-2">
            <option value="">Seleccione una opción</option>
            <option value="mejoria">Mejoría</option>
            <option value="estable">Estable</option>
            <option value="empeoramiento">Empeoramiento</option>
          </select>
        </div>

        <div>
          <label class="block font-medium mb-1">Observaciones adicionales</label>
          <textarea v-model="terapia.observaciones" rows="4" maxlength="500" class="w-full border rounded p-2 resize-none" placeholder="Observaciones generales sobre la terapia"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ terapia.observaciones.length }}/500 caracteres</p>
        </div>

        <div>
          <label class="block font-medium mb-1">Recomendaciones para el tutor</label>
          <textarea v-model="terapia.recomendaciones_tutor" rows="4" maxlength="500" class="w-full border rounded p-2 resize-none" placeholder="Instrucciones específicas para el dueño"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ terapia.recomendaciones_tutor.length }}/500 caracteres</p>
        </div>
      </div>

      <!-- Selección del medio de envío -->
      <div class="mt-8">
        <CarruselMedioEnvio 
          v-if="usuarioId" 
          :usuario-id="usuarioId" 
          @update:medio="terapia.medio_envio = $event" 
        />
        
        <div v-else class="text-center py-4">
          <p class="text-gray-500">Cargando información del dueño...</p>
        </div>

        <div v-if="terapia.medio_envio" class="mt-4 text-center text-gray-700">
          <span class="font-semibold">Medio seleccionado:</span>
          <span class="ml-1 text-blue-600 font-medium">
            {{ obtenerNombreMedio(terapia.medio_envio) }}
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
          {{ procesando ? 'Registrando...' : 'Registrar Terapia' }}
        </button>
      </div>
    </form>

    <!-- Componente externo del overlay -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="terapia.centro_veterinario_id"
      @cerrar="mostrarOverlayCentros = false"
      @seleccionar="seleccionarCentro"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import SeleccionCentroVeterinario from '@/components/ElementosGraficos/SeleccionCentroVeterinario.vue'
import CarruselMedioEnvio from '@/components/ElementosGraficos/CarruselMedioEnvio.vue'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Obtener el ID de la mascota desde la ruta
const mascotaId = route.query.mascotaId || route.params.mascotaId

// Estados reactivos
const tiposTerapia = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)

// Datos del formulario
const terapia = reactive({
  tipo_terapia_id: '',
  fecha_inicio: '',
  frecuencia: '',
  duracion_tratamiento: '',
  centro_veterinario_id: null,
  costo: null,
  fecha_fin: '',
  evolucion: '',
  observaciones: '',
  recomendaciones_tutor: '',
  medio_envio: ''
})

// Archivos adjuntos
const archivos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null
})))
const inputsArchivo = ref([])

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
  const centro = centrosVeterinarios.value.find(c => c.id === terapia.centro_veterinario_id)
  return centro ? centro.nombre : 'Centro no encontrado'
}

// Obtener dirección del centro seleccionado
const obtenerDireccionCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === terapia.centro_veterinario_id)
  return centro ? centro.direccion : ''
}

// Cargar datos de la mascota
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
    
    if (result.success && result.data) {
      mascotaData.value = result.data
      console.log('✅ Datos de mascota cargados')
      errorCargandoMascota.value = null
    } else {
      mascotaData.value = null
      errorCargandoMascota.value = result.message || 'Error al cargar datos de la mascota'
    }
  } catch (error) {
    console.error('❌ Error cargando datos de mascota:', error)
    mascotaData.value = null
    errorCargandoMascota.value = error.message
  }
}

// Cargar tipos de terapia
const cargarTiposTerapia = async () => {
  try {
    const response = await fetch('/api/tipos-terapia', {
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
      tiposTerapia.value = result.data
      console.log('💉 Tipos de terapia cargados:', tiposTerapia.value.length)
    } else {
      console.warn('No se encontraron tipos de terapia:', result)
      tiposTerapia.value = []
    }
  } catch (error) {
    console.error('Error cargando tipos de terapia:', error)
    alert('Error al cargar los tipos de terapia: ' + error.message)
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

const onTipoTerapiaChange = () => {
  const tipoSeleccionado = tiposTerapia.value.find(t => t.id == terapia.tipo_terapia_id)
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
  terapia.centro_veterinario_id = centro.id
  mostrarOverlayCentros.value = false
}

// Navegar al registro de nuevo tipo
const abrirRegistroTipoTerapia = () => {
  router.push({
    path: '/registro/registroTipoTerapia',
    query: {
      from: `/mascotas/${mascotaId}/terapias/crear`,
      mascotaId
    }
  })
}

// Funciones para manejar archivos
const esImagen = (archivo) => {
  if (!archivo) return false
  return archivo.type.startsWith('image/')
}

const handleArchivo = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    // Validar tamaño del archivo (máximo 5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert('El archivo no debe superar los 5MB')
      return
    }
    
    archivos.value[index].archivo = file
    archivos.value[index].preview = esImagen(file) ? URL.createObjectURL(file) : null
  }
}

const activarInput = (index) => {
  inputsArchivo.value[index]?.click()
}

const quitarArchivo = (index) => {
  if (archivos.value[index].preview) {
    URL.revokeObjectURL(archivos.value[index].preview)
  }
  archivos.value[index].archivo = null
  archivos.value[index].preview = null
}

// Registrar terapia
const registrarTerapia = async () => {
  if (procesando.value) return

  try {
    procesando.value = true

    // Validar campos obligatorios
    if (!terapia.tipo_terapia_id || !terapia.fecha_inicio || !terapia.frecuencia || !terapia.duracion_tratamiento) {
      alert('Por favor complete todos los campos obligatorios')
      return
    }

    // Validar que se seleccionó un medio de envío
    if (!terapia.medio_envio) {
      alert('Por favor seleccione un medio de envío para el reporte de terapia')
      return
    }

    const formData = new FormData()
    
    // Agregar datos de la terapia
    for (const campo in terapia) {
      if (terapia[campo] !== null && terapia[campo] !== undefined && terapia[campo] !== '') {
        formData.append(campo, terapia[campo])
      }
    }

    // Agregar archivos adjuntos
    archivos.value.forEach((archivo, index) => {
      if (archivo.archivo) {
        formData.append(`archivos[${index}]`, archivo.archivo)
      }
    })

    console.log('📤 Enviando datos de terapia a servidor:', {
      mascotaId,
      terapia: { ...terapia },
      archivosCount: archivos.value.filter(a => a.archivo).length
    })

    const response = await fetch(`/api/mascotas/${mascotaId}/terapias`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: formData
    })

    const responseText = await response.text()
    console.log('📨 Status:', response.status, 'Respuesta:', responseText)

    let result
    try {
      result = JSON.parse(responseText)
    } catch (parseError) {
      console.error('No se pudo parsear como JSON:', responseText)
      throw new Error('El servidor no devolvió JSON válido')
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      // Limpiar URLs de previsualización
      archivos.value.forEach(archivo => {
        if (archivo.preview) {
          URL.revokeObjectURL(archivo.preview)
        }
      })
      
      alert('Terapia registrada exitosamente')
      
      // Redirigir al historial de terapias
      router.push({
        name: 'veterinario-terapias',
        params: { id: mascotaId },
        query: {
          from: 'registroTerapia',
          currentTab: 'Terapias',
          ts: Date.now()
        }
      })
    } else {
      alert('Error al registrar la terapia: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al registrar la terapia: ' + error.message)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  // Limpiar URLs de previsualización
  archivos.value.forEach(archivo => {
    if (archivo.preview) {
      URL.revokeObjectURL(archivo.preview)
    }
  })
  
  router.push({
    name: 'veterinario-terapias',
    params: { id: mascotaId },
    query: {
      from: 'cancelarRegistroTerapia',
      currentTab: 'Terapias',
      ts: Date.now()
    }
  })
}

// Verificar autenticación y cargar datos
onMounted(async () => {
  console.log('🚀 Iniciando componente RegistrarTerapia')
  
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      alert('Debe iniciar sesión para acceder a esta página')
      router.push('/login')
      return
    }
  }

  if (!mascotaId) {
    alert('No se especificó la mascota')
    router.back()
    return
  }

  // Cargar datos en orden
  await cargarDatosMascota()
  
  if (errorCargandoMascota.value) {
    console.error('❌ Error al cargar mascota:', errorCargandoMascota.value)
    alert('Error al cargar datos de la mascota: ' + errorCargandoMascota.value)
    return
  }

  await cargarTiposTerapia()
  await cargarCentrosVeterinarios()

  // Establecer fecha actual como predeterminada
  const hoy = new Date().toISOString().split('T')[0]
  terapia.fecha_inicio = hoy
  
  console.log('✅ Componente completamente cargado')
})
</script>
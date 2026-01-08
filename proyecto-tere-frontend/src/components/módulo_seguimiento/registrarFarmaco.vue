<!-- registarFarmaco -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Administración de Fármaco</h1>

    <form @submit.prevent="registrarFarmaco" class="space-y-4">
      <!-- DATOS OBLIGATORIOS -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Obligatorios</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna izquierda -->
        <div class="space-y-4">
          <!-- Selección de Tipo de Fármaco -->
          <div>
            <label class="block font-medium mb-1">Tipo de fármaco</label>
            <div class="flex gap-2">
              <select
                v-model="farmaco.tipo_farmaco_id"
                required
                class="w-full border rounded p-2"
                @change="onTipoFarmacoChange"
              >
                <option value="">Seleccione un tipo de fármaco</option>
                <option
                  v-for="tipo in tiposFarmaco"
                  :key="tipo.id"
                  :value="tipo.id"
                >
                  {{ tipo.nombre_comercial }} ({{ tipo.nombre_generico }})
                </option>
              </select>

              <!-- Botón de + Tipo -->
              <button 
                type="button"
                @click="abrirRegistroTipoFarmaco"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo 
              </button>
            </div>
          </div>

          <div>
            <label class="block font-medium">Fecha de administración</label>
            <input v-model="farmaco.fecha_administracion" type="datetime-local" required class="w-full border rounded p-2" />
          </div>

          <!-- Centro Veterinario -->
          <div>
            <label class="block font-medium mb-2">
              Centro Veterinario donde se realizó
            </label>
            <div class="flex gap-2 items-center">
              <div 
                v-if="farmaco.centro_veterinario_id"
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
            <label class="block font-medium">Frecuencia de administración</label>
            <input v-model="farmaco.frecuencia" type="text" required class="w-full border rounded p-2" placeholder="Ej: Cada 8 h, 1 vez al día, etc." />
          </div>

          <div>
            <label class="block font-medium">Duración del tratamiento</label>
            <input v-model="farmaco.duracion" type="text" required class="w-full border rounded p-2" placeholder="Ej: 7 días, 2 semanas, etc." />
          </div>

          <div>
            <label class="block font-medium">Dosis administrada</label>
            <div class="flex">
              <input v-model="farmaco.dosis" type="text" required class="w-3/4 border rounded-l p-2" placeholder="Cantidad" />
              <select v-model="farmaco.unidad" required class="w-1/4 border rounded-r p-2">
                <option value="mg">mg</option>
                <option value="ml">ml</option>
                <option value="UI">UI</option>
                <option value="comp">comp.</option>
                <option value="gotas">gotas</option>
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
          <label class="block font-medium">Fecha próxima dosis (si aplica)</label>
          <input v-model="farmaco.proxima_dosis" type="datetime-local" class="w-full border rounded p-2" />
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Reacciones adversas observadas</label>
          <textarea v-model="farmaco.reacciones" rows="3" class="w-full border rounded p-2 resize-none" placeholder="Describa cualquier efecto no deseado"></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Recomendaciones para el tutor</label>
          <textarea v-model="farmaco.recomendaciones" rows="3" class="w-full border rounded p-2 resize-none"></textarea>
        </div>

       <!-- Archivos adjuntos -->
        <div class="col-span-full">
          <label class="block font-medium mb-2">Archivos adjuntos</label>
          <div class="flex flex-wrap gap-x-2 gap-y-2">
            <div
              v-for="(archivo, index) in archivos"
              :key="index"
              class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-20 w-20"
              @click="!archivo.preview && activarInput(index)"
            >
              <!-- Botón eliminar -->
              <button
                type="button"
                @click.stop="quitarArchivo(index)"
                v-if="archivo.preview"
                class="absolute top-0.5 right-0.5 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700"
              >
                <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-lg" />
              </button>

              <!-- Input oculto -->
              <input
                :ref="el => inputsArchivo[index] = el"
                type="file"
                @change="handleArchivo($event, index)"
                class="hidden"
              />

              <!-- Vista previa -->
              <div v-if="archivo.preview" class="h-full flex flex-col">
                <img
                  v-if="esImagen(archivo.archivo)"
                  :src="archivo.preview"
                  alt="Preview"
                  class="w-full h-full object-cover rounded-md mx-auto flex-grow"
                />
                <div v-else class="h-full flex items-center justify-center p-1">
                  <font-awesome-icon :icon="['fas', 'file']" class="text-3xl text-gray-500" />
                </div>
                <div class="text-[10px] truncate px-1">{{ archivo.archivo.name }}</div>
              </div>

              <!-- Indicador visual si no hay archivo -->
              <div v-else class="text-green-400 flex flex-col justify-center items-center h-full">
                <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-2xl mb-0.5" />
                <div class="text-[10px] text-gray-400">Agregar</div>
              </div>
            </div>
          </div>
          <p class="text-xs text-gray-500 mt-1">Puede adjuntar recetas, imágenes del medicamento, informes, etc.</p>
        </div>
      </div>

      <!-- Selección del medio de envío -->
      <div class="mt-8">
        <CarruselMedioEnvio 
          v-if="usuarioId" 
          :usuario-id="usuarioId" 
          @update:medio="farmaco.medio_envio = $event" 
        />
        
        <div v-else class="text-center py-4">
          <p class="text-gray-500">Cargando información del dueño...</p>
        </div>

        <div v-if="farmaco.medio_envio" class="mt-4 text-center text-gray-700">
          <span class="font-semibold">Medio seleccionado:</span>
          <span class="ml-1 text-blue-600 font-medium">
            {{ obtenerNombreMedio(farmaco.medio_envio) }}
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
          {{ procesando ? 'Registrando...' : 'Registrar Fármaco' }}
        </button>
      </div>
    </form>

    <!-- Componente externo del overlay -->
    <SeleccionCentroVeterinario
      v-if="mostrarOverlayCentros"
      :mostrar="mostrarOverlayCentros"
      :centros="centrosVeterinarios"
      :centroSeleccionado="farmaco.centro_veterinario_id"
      @cerrar="mostrarOverlayCentros = false"
      @seleccionar="seleccionarCentro"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
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
const tiposFarmaco = ref([])
const centrosVeterinarios = ref([])
const mostrarOverlayCentros = ref(false)
const procesando = ref(false)
const mascotaData = ref(null)
const errorCargandoMascota = ref(null)

// Datos del formulario
const farmaco = reactive({
  tipo_farmaco_id: '',
  fecha_administracion: '',
  dosis: '',
  unidad: 'mg',
  frecuencia: '',
  duracion: '',
  centro_veterinario_id: '',
  proxima_dosis: '',
  reacciones: '',
  recomendaciones: '',
  medio_envio: ''
})

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
  const centro = centrosVeterinarios.value.find(c => c.id === farmaco.centro_veterinario_id)
  return centro ? centro.nombre : 'Centro no encontrado'
}

// Obtener dirección del centro seleccionado
const obtenerDireccionCentroSeleccionado = () => {
  const centro = centrosVeterinarios.value.find(c => c.id === farmaco.centro_veterinario_id)
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

// Cargar tipos de fármaco
const cargarTiposFarmaco = async () => {
  try {
    console.log('🔄 Cargando tipos de fármaco desde:', '/api/tipos-farmaco');
    console.log('🔑 Token disponible:', !!accessToken.value);
    
    const response = await fetch('/api/tipos-farmaco', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });

    console.log('📡 Estado de respuesta:', response.status, response.statusText);
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('❌ Error en respuesta:', errorText);
      throw new Error(`Error ${response.status}: ${response.statusText}`);
    }

    const result = await response.json();
    console.log('📦 Resultado completo:', result);
    
    if (result.success && result.data) {
      tiposFarmaco.value = result.data;
      console.log('✅ Tipos de fármaco cargados:', tiposFarmaco.value.length, 'registros');
      console.log('📋 Ejemplo del primer tipo:', tiposFarmaco.value[0]);
    } else {
      console.warn('⚠️ No se encontraron datos en la respuesta:', result);
      tiposFarmaco.value = [];
    }
  } catch (error) {
    console.error('❌ Error cargando tipos de fármaco:', error);
    console.error('🔍 Stack trace:', error.stack);
    
    // Mostrar alerta más específica
    alert('Error al cargar los tipos de fármaco: ' + error.message + 
          '\n\nVerifica:\n1. Que estés autenticado\n2. Que el servidor esté funcionando\n3. Que la ruta /api/tipos-farmaco sea correcta');
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

const onTipoFarmacoChange = () => {
  const tipoSeleccionado = tiposFarmaco.value.find(t => t.id == farmaco.tipo_farmaco_id)
  if (tipoSeleccionado) {
    console.log('Tipo de fármaco seleccionado:', tipoSeleccionado)
  }
}

// Abrir overlay externo
const abrirOverlayCentros = () => {
  mostrarOverlayCentros.value = true
}

// Seleccionar centro desde overlay
const seleccionarCentro = (centro) => {
  farmaco.centro_veterinario_id = centro.id
  mostrarOverlayCentros.value = false
}

// Navegar al registro de nuevo tipo
const abrirRegistroTipoFarmaco = () => {
  router.push({
    path: '/registro/registroTipoFarmaco',
    query: {
      from: `/mascotas/${mascotaId}/farmacos/crear`,
      mascotaId
    }
  })
}

const esImagen = (archivo) => {
  if (!archivo) return false
  return archivo.type.startsWith('image/')
}

const handleArchivo = (event, index) => {
  const file = event.target.files[0]
  if (file) {
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

// Registrar fármaco
const registrarFarmaco = async () => {
  if (procesando.value) return

  try {
    procesando.value = true

    // Validar que se seleccionó un medio de envío
    if (!farmaco.medio_envio) {
      alert('Por favor seleccione un medio de envío para el registro')
      return
    }

    // Validaciones básicas
    if (!farmaco.tipo_farmaco_id || !farmaco.fecha_administracion || !farmaco.frecuencia || 
        !farmaco.duracion || !farmaco.dosis || !farmaco.unidad) {
      alert('Por favor complete todos los campos obligatorios')
      return
    }

    // Preparar FormData para enviar archivos
    const formData = new FormData()
    
    // Agregar datos del formulario
    Object.keys(farmaco).forEach(key => {
      if (farmaco[key] !== null && farmaco[key] !== '') {
        formData.append(key, farmaco[key])
      }
    })

    // Agregar archivos
    archivos.value.forEach((archivo, index) => {
      if (archivo.archivo) {
        formData.append(`archivos[${index}]`, archivo.archivo)
      }
    })

    console.log('📤 Enviando datos a servidor:', Object.fromEntries(formData))

    const response = await fetch(`/api/mascotas/${mascotaId}/farmacos`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: formData
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
      throw new Error('El servidor no devolvió JSON válido')
    }

    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }

    if (result.success) {
      router.push({
        name: 'veterinario-farmacos',
        params: { id: mascotaId },  // ¡Aquí está el cambio!
        query: {
          from: 'registroFarmaco',
          currentTab: 'Clinico',
          ts: Date.now()
        }
      })
    } else {
      alert('Error al registrar el fármaco: ' + result.message)
    }
  } catch (error) {
    console.error('❌ Error completo:', error)
    alert('Error al registrar el fármaco: ' + error.message)
  } finally {
    procesando.value = false
  }
}

const cancelar = () => {
  router.push({
        name: 'veterinario-farmacos',
        params: { id: mascotaId },  // ¡Aquí está el cambio!
        query: {
          from: 'registroFarmaco',
          currentTab: 'Clinico',
          ts: Date.now()
        }
      })
}

// Verificar autenticación y cargar datos
onMounted(async () => {
  console.log('🚀 Iniciando componente RegistrarFarmaco')
  
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

  await cargarTiposFarmaco()
  await cargarCentrosVeterinarios()

  // Establecer fecha y hora actual como predeterminada
  const ahora = new Date()
  const offset = ahora.getTimezoneOffset() * 60000
  const localISOTime = new Date(ahora.getTime() - offset).toISOString().slice(0, 16)
  farmaco.fecha_administracion = localISOTime
  
  console.log('✅ Componente completamente cargado')
  console.log('👤 Usuario ID final:', usuarioId.value)
})
</script>
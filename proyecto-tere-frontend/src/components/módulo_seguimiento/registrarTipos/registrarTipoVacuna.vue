<!--registrarTipoVacuna.vue-->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Tipo de Vacuna' : 'Registrar Tipo de Vacuna' }}</h1>

    <form @submit.prevent="esEdicion ? actualizarVacuna() : registrarVacunacion()" class="space-y-4">
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
            <label class="block font-medium">Nombre de la vacuna</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="vacuna.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Rabia, Moquillo, Pentavalente"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Enfermedades que previene</label>
            <textarea 
              v-model="vacuna.enfermedades" 
              rows="2" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Liste las enfermedades que cubre esta vacuna"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium mb-2">Especie objetivo</label>
            <CarruselEspecieVeterinario v-model="especiesSeleccionadas" />
            <p v-if="!especiesSeleccionadas.length" class="text-sm text-gray-500 mt-1">
              Seleccione una o más especies objetivo
            </p>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Edad mínima de aplicación</label>
            <div class="flex">
              <input 
                v-model="vacuna.edadMinima" 
                type="number" 
                min="0" 
                step="0.5" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Edad" 
              />
              <select 
                v-model="vacuna.edadUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
              >
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
                <option value="años">Años</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Dosis recomendada</label>
            <div class="flex">
              <input 
                v-model="vacuna.dosis" 
                type="number" 
                min="0.1" 
                step="0.1" 
                required 
                class="w-3/4 border rounded-l p-2" 
                placeholder="Cantidad" 
              />
              <select 
                v-model="vacuna.dosisUnidad" 
                required 
                class="w-1/4 border rounded-r p-2"
              >
                <option value="ml">ml</option>
                <option value="dosis">dosis</option>
                <option value="gotas">gotas</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Vía de administración</label>
            <select v-model="vacuna.via" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="subcutanea">Subcutánea</option>
              <option value="intramuscular">Intramuscular</option>
              <option value="oral">Oral</option>
              <option value="nasal">Nasal</option>
            </select>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-4">
        <div>
          <label class="block font-medium">Frecuencia de aplicación</label>
          <select v-model="vacuna.frecuencia" required class="w-full border rounded p-2">
            <option value="">Seleccione una opción</option>
            <option value="unica">Única vez</option>
            <option value="anual">Anual</option>
            <option value="semestral">Cada 6 meses</option>
            <option value="personalizada">Personalizada</option>
          </select>
          <input 
            v-if="vacuna.frecuencia === 'personalizada'"
            v-model="vacuna.frecuenciaPersonalizada"
            type="text"
            class="w-full border rounded p-2 mt-2"
            placeholder="Especifique el esquema (ej: cada 3 meses)"
          />
        </div>

        <div>
          <label class="block font-medium">Obligatoriedad</label>
          <select v-model="vacuna.obligatoria" required class="w-full border rounded p-2">
            <option value="">Seleccione una opción</option>
            <option value="obligatoria">Obligatoria</option>
            <option value="opcional">Opcional</option>
            <option value="depende">Depende de la región</option>
          </select>
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
          <label class="block font-medium">Intervalo entre dosis (si aplica)</label>
          <input 
            v-model="vacuna.intervaloDosis" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Ej: 21 días, 1 mes, etc."
          />
        </div>

        <div>
          <label class="block font-medium">Fabricante o laboratorio</label>
          <input 
            v-model="vacuna.fabricante" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Nombre del laboratorio"
          />
        </div>

        <div>
          <label class="block font-medium">Riesgos conocidos</label>
          <textarea 
            v-model="vacuna.riesgos" 
            rows="2" 
            class="w-full border rounded p-2" 
            placeholder="Efectos adversos reportados"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Recomendaciones profesionales</label>
          <textarea 
            v-model="vacuna.recomendaciones" 
            rows="2" 
            class="w-full border rounded p-2" 
            placeholder="Consejos para su aplicación"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Lote / Serie del frasco</label>
          <input 
            v-model="vacuna.lote" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Número de lote"
          />
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="button" @click="cancelar" class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors">Cancelar</button>
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors">
          {{ esEdicion ? 'Actualizar' : '+ Tipo' }}
        </button>
      </div>
    </form>
  </div>

  <!-- Modal de confirmación -->
  <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
      <h3 class="text-xl font-bold mb-4">
        {{ esEdicion ? 'Confirmar Actualización' : 'Confirmar Registro' }}
      </h3>
      <p class="mb-6">
        {{ esEdicion 
          ? '¿Está seguro que desea actualizar los datos de este tipo de vacuna?' 
          : '¿Está seguro que desea registrar este nuevo tipo de vacuna?' 
        }}
      </p>
      <div class="flex justify-end gap-4">
        <button 
          @click="showModal = false" 
          class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors"
        >
          Cancelar
        </button>
        <button 
          @click="confirmarAccion" 
          class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors"
        >
          {{ esEdicion ? 'Actualizar' : 'Registrar' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Mensajes de éxito/error -->
  <div v-if="message" class="fixed top-20 right-4 z-50 max-w-sm">
    <div :class="[
      'p-4 rounded-lg shadow-lg',
      messageType === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    ]">
      {{ message }}
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import CarruselEspecieVeterinario from '@/components/ElementosGraficos/CarruselEspecieVeterinario.vue'

const especiesSeleccionadas = ref([])

// Solo para debug, no para lógica de negocio
watch(especiesSeleccionadas, (val) => {
  console.log('🔄 Especies seleccionadas cambiaron:', val)
}, { deep: true, flush: 'post' }) // 'flush: post' ayuda a evitar efectos secundarios

const route = useRoute()
const router = useRouter()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const loading = ref(false)
const message = ref('')
const messageType = ref('')
const showModal = ref(false)

const id = route.params.id || null
const esEdicion = computed(() => id !== null)

const vacuna = reactive({
  nombre: '',
  enfermedades: '',
  especie: '',
  edadMinima: '',
  edadUnidad: 'semanas',
  dosis: '',
  dosisUnidad: 'ml',
  via: '',
  frecuencia: '',
  frecuenciaPersonalizada: '',
  obligatoria: '',
  intervaloDosis: '',
  fabricante: '',
  riesgos: '',
  recomendaciones: '',
  lote: ''
})

// Verificar autenticación y cargar datos si es edición
onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      showMessage('Debe iniciar sesión para acceder a esta página', 'error')
      setTimeout(() => {
        router.push('/')
      }, 2000)
      return
    }
  }

  // Si estamos en modo edición, cargar los datos existentes
  if (esEdicion.value) {
    await cargarVacuna()
  } else {
    // Inicializar como array vacío
    especiesSeleccionadas.value = []
  }
})

const cargarVacuna = async () => {
  try {
    loading.value = true
    console.log('🔄 Cargando vacuna con ID:', id)
    
    const response = await fetch(`/api/tipos-vacuna/${id}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    console.log('📡 Respuesta del servidor:', response.status, response.statusText)

    if (!response.ok) {
      const errorText = await response.text()
      console.error('❌ Error en respuesta:', errorText)
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    console.log('✅ Datos recibidos del backend:', result)

    if (result.success && result.data) {
      const data = result.data
      
      // Mapear los datos del backend al formato del frontend
      Object.assign(vacuna, {
        nombre: data.nombre || '',
        enfermedades: data.enfermedades || '',
        // especie: data.especie || '', // ❌ ESTO ESTÁ MAL
        edadMinima: data.edad_minima || '',
        edadUnidad: data.edad_unidad || 'semanas',
        dosis: data.dosis || '',
        dosisUnidad: data.dosis_unidad || 'ml',
        via: data.via_administracion || '',
        frecuencia: data.frecuencia || '',
        frecuenciaPersonalizada: data.frecuencia_personalizada || '',
        obligatoria: data.obligatoriedad || '',
        intervaloDosis: data.intervalo_dosis || '',
        fabricante: data.fabricante || '',
        riesgos: data.riesgos || '',
        recomendaciones: data.recomendaciones || '',
        lote: data.lote || ''
      })

      // ✅ CORRECCIÓN: Convertir el JSON de especies a array
      if (data.especies) {
        console.log('🐾 Especies recibidas del backend:', data.especies)
        
        // Si ya es un array, úsalo directamente
        if (Array.isArray(data.especies)) {
          especiesSeleccionadas.value = data.especies
        } else {
          // Si es string JSON, parsearlo
          try {
            especiesSeleccionadas.value = JSON.parse(data.especies)
          } catch (e) {
            console.error('❌ Error parseando especies JSON:', e)
            especiesSeleccionadas.value = []
          }
        }
      } else {
        especiesSeleccionadas.value = []
      }

      console.log('📝 Datos mapeados al formulario:', vacuna)
      console.log('🐾 Especies cargadas en el carrusel:', especiesSeleccionadas.value)
    } else {
      throw new Error(result.message || 'No se pudieron cargar los datos de la vacuna')
    }

  } catch (error) {
    console.error('❌ Error al cargar vacuna:', error)
    showMessage(`Error al cargar los datos de la vacuna: ${error.message}`, 'error')
  } finally {
    loading.value = false
  }
}

const showMessage = (text, type = 'success') => {
  message.value = text
  messageType.value = type
  setTimeout(() => {
    message.value = ''
  }, 5000)
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const prepararEnvio = () => {
  // Validaciones básicas
  if (!vacuna.nombre || !vacuna.enfermedades || !especiesSeleccionadas.value.length || 
      !vacuna.edadMinima || !vacuna.dosis || !vacuna.via || 
      !vacuna.frecuencia || !vacuna.obligatoria) {
    showMessage('Por favor complete todos los campos obligatorios', 'error')
    return null
  }

  console.log('🐾 Especies seleccionadas para enviar:', especiesSeleccionadas.value)

  // Convertir el Proxy de Vue a un array simple
  const especiesArray = [...especiesSeleccionadas.value]

  // Mapear los datos para que coincidan con el controlador Laravel
  return {
    nombre: vacuna.nombre,
    enfermedades: vacuna.enfermedades,
    especies: especiesArray, // ← Envía el array de especies
    edad_minima: parseFloat(vacuna.edadMinima),
    edad_unidad: vacuna.edadUnidad,
    dosis: parseFloat(vacuna.dosis),
    dosis_unidad: vacuna.dosisUnidad,
    via_administracion: vacuna.via,
    frecuencia: vacuna.frecuencia,
    frecuencia_personalizada: vacuna.frecuencia === 'personalizada' ? vacuna.frecuenciaPersonalizada : null,
    obligatoriedad: vacuna.obligatoria,
    intervalo_dosis: vacuna.intervaloDosis || null,
    fabricante: vacuna.fabricante || null,
    riesgos: vacuna.riesgos || null,
    recomendaciones: vacuna.recomendaciones || null,
    lote: vacuna.lote || null
  }
}

const registrarVacunacion = async () => {
  const datosEnvio = prepararEnvio()
  if (!datosEnvio) return
  
  showModal.value = true
}

const actualizarVacuna = async () => {
  const datosEnvio = prepararEnvio()
  if (!datosEnvio) return
  
  showModal.value = true
}

const confirmarAccion = async () => {
  try {
    loading.value = true
    showModal.value = false

    const datosEnvio = prepararEnvio()
    if (!datosEnvio) return

    console.log('📤 Datos a enviar:', datosEnvio)

    let response
    if (esEdicion.value) {
      // Modo edición - PUT
      response = await fetch(`/api/tipos-vacuna/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${accessToken.value}`
        },
        body: JSON.stringify(datosEnvio)
      })
    } else {
      // Modo registro - POST
      response = await fetch('/api/tipos-vacuna', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${accessToken.value}`
        },
        body: JSON.stringify(datosEnvio)
      })
    }

    console.log('📨 Respuesta del servidor:', response.status, response.statusText)

    const result = await response.json();
    console.log('✅ Respuesta procesada:', result);
    
    if (!response.ok) {
      throw new Error(result.message || 'Error en la operación')
    }
    
    const mensajeExito = esEdicion.value 
      ? 'Tipo de vacuna actualizado correctamente' 
      : 'Tipo de vacuna registrado correctamente'
    
    showMessage(mensajeExito, 'success')
    
    // Redirigir después de 2 segundos para que el usuario vea el mensaje
    setTimeout(() => {
      router.push('/veterinarios/tipos/vacunas')
    }, 2000)

  } catch (error) {
    console.error('❌ Error en la operación:', error)
    
    let errorMessage = esEdicion.value 
      ? 'Error al actualizar el tipo de vacuna' 
      : 'Error al registrar el tipo de vacuna'
    
    if (error.message.includes('unique')) {
      errorMessage = 'Ya existe un tipo de vacuna con este nombre'
    } else if (error.message) {
      errorMessage = error.message
    }
    
    showMessage(errorMessage, 'error')
  } finally {
    loading.value = false
  }
}
</script>
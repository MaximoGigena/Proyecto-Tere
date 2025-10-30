<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar' : 'Registrar' }} Tipo de Desparasitación</h1>

    <form @submit.prevent="esEdicion ? actualizarDesparasitacion() : registrarDesparasitacion()" class="space-y-4">
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
            <label class="block font-medium">Nombre del desparasitante</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="desparasitacion.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Nombre comercial o genérico"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Parásitos tratados</label>
            <div class="flex flex-wrap gap-2">
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="desparasitacion.parasitos" value="internos" class="rounded">
                <span>Internos</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="desparasitacion.parasitos" value="externos" class="rounded">
                <span>Externos</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="desparasitacion.parasitos" value="pulgas" class="rounded">
                <span>Pulgas</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="desparasitacion.parasitos" value="garrapatas" class="rounded">
                <span>Garrapatas</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="desparasitacion.parasitos" value="otros" class="rounded">
                <span>Otros</span>
              </label>
            </div>
            <input 
              v-if="desparasitacion.parasitos.includes('otros')"
              v-model="desparasitacion.otrosParasitos"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique otros parásitos"
            >
          </div>

          <div>
            <label class="block font-medium">Vía de administración</label>
            <select v-model="desparasitacion.via" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="oral">Oral</option>
              <option value="topica">Tópica</option>
              <option value="inyectable">Inyectable</option>
              <option value="otra">Otra</option>
            </select>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Especies a las que aplica</label>
            <select v-model="desparasitacion.especies" multiple class="w-full border rounded p-2 h-[120px]">
              <option value="canino">Canino</option>
              <option value="felino">Felino</option>
              <option value="ave">Ave</option>
              <option value="roedor">Roedor</option>
              <option value="exotico">Exótico</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Edad mínima de aplicación</label>
            <div class="flex">
              <input 
                v-model="desparasitacion.edadMinima" 
                type="number" 
                min="0" 
                step="0.5" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Edad" 
              />
              <select 
                v-model="desparasitacion.edadUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
              >
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Frecuencia estándar</label>
            <div class="flex">
              <input 
                v-model="desparasitacion.frecuencia" 
                type="number" 
                min="1" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Cantidad" 
              />
              <select 
                v-model="desparasitacion.frecuenciaUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
              >
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
          <label class="block font-medium">Recomendaciones profesionales</label>
          <textarea 
            v-model="desparasitacion.recomendaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Consejos para su aplicación"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Riesgos conocidos</label>
          <textarea 
            v-model="desparasitacion.riesgos" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Efectos adversos reportados"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Dosis recomendada</label>
          <input 
            v-model="desparasitacion.dosis" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Ej: 1 comprimido, 0.5 ml, etc."
          />
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="button" @click="cancelar" class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors">Cancelar</button>
        <button type="submit" :disabled="submitting" class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:opacity-50">
          {{ submitting ? 'Guardando...' : (esEdicion ? 'Actualizar' : '+ Tipo') }}
        </button>
      </div>
    </form>
  </div>

  <!-- Modal de Confirmación de Modificación -->
  <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
      <div class="text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <font-awesome-icon :icon="['fas', 'check']" class="text-green-600 text-2xl" />
        </div>
        
        <h3 class="text-xl font-bold text-gray-800 mb-2">¡Modificación Exitosa!</h3>
        
        <p class="text-gray-600 mb-6">
          El tipo de desparasitación <strong>"{{ desparasitacion.nombre }}"</strong> ha sido actualizado correctamente.
        </p>

        <div class="flex justify-center gap-3">
          <button 
            @click="irALista" 
            class="bg-blue-500 text-white px-6 py-2 rounded-full hover:bg-blue-600 transition-colors"
          >
            Ver Lista
          </button>
          <button 
            @click="continuarEditando" 
            class="bg-gray-500 text-white px-6 py-2 rounded-full hover:bg-gray-600 transition-colors"
          >
            Seguir Editando
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const route = useRoute()
const { accessToken, checkAuth, redirectByRole } = useAuth()

const loading = ref(true)
const submitting = ref(false)
const showModal = ref(false)
const esEdicion = ref(false)
const tipoId = ref(null)

const desparasitacion = reactive({
  nombre: '',
  parasitos: [],
  otrosParasitos: '',
  via: '',
  especies: [],
  edadMinima: '',
  edadUnidad: 'semanas',
  frecuencia: '',
  frecuenciaUnidad: 'meses',
  recomendaciones: '',
  riesgos: '',
  dosis: ''
})

// Computed para validar el formulario
const isFormValid = computed(() => {
  return desparasitacion.nombre.trim() !== '' &&
         desparasitacion.parasitos.length > 0 &&
         desparasitacion.via !== '' &&
         desparasitacion.especies.length > 0 &&
         desparasitacion.edadMinima !== '' &&
         desparasitacion.frecuencia !== '' &&
         (!desparasitacion.parasitos.includes('otros') || desparasitacion.otrosParasitos.trim() !== '')
})

// Verificar si es edición
const verificarEdicion = () => {
  if (route.params.id) {
    esEdicion.value = true
    tipoId.value = route.params.id
    cargarDatosDesparasitacion()
  }
}

// Cargar datos para edición
const cargarDatosDesparasitacion = async () => {
  try {
    loading.value = true
    const response = await fetch(`/api/tipos-desparasitacion/${tipoId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('Error al cargar los datos')
    }

    const data = await response.json()

    if (data.success) {
      const tipo = data.data
      
      // Mapear los datos del API al formulario
      desparasitacion.nombre = tipo.nombre || ''
      desparasitacion.parasitos = Array.isArray(tipo.parasitos) ? tipo.parasitos : []
      desparasitacion.otrosParasitos = tipo.otros_parasitos || ''
      desparasitacion.via = tipo.via_administracion || ''
      desparasitacion.especies = Array.isArray(tipo.especies) ? tipo.especies : []
      desparasitacion.edadMinima = tipo.edad_minima || ''
      desparasitacion.edadUnidad = tipo.edad_unidad || 'semanas'
      desparasitacion.frecuencia = tipo.frecuencia || ''
      desparasitacion.frecuenciaUnidad = tipo.frecuencia_unidad || 'meses'
      desparasitacion.recomendaciones = tipo.recomendaciones || ''
      desparasitacion.riesgos = tipo.riesgos || ''
      desparasitacion.dosis = tipo.dosis_recomendada || ''
    }
  } catch (error) {
    console.error('Error cargando datos:', error)
    alert('Error al cargar los datos del tipo de desparasitación')
  } finally {
    loading.value = false
  }
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los cambios no guardados se perderán.')) {
    router.back()
  }
}

const redirectToDashboard = () => {
  redirectByRole()
}

const registrarDesparasitacion = async () => {
  if (!isFormValid.value) {
    alert('Por favor complete todos los campos obligatorios')
    return
  }

  try {
    submitting.value = true

    // Preparar los datos para enviar al servidor
    const payload = {
      nombre: desparasitacion.nombre,
      parasitos: desparasitacion.parasitos,
      otros_parasitos: desparasitacion.otrosParasitos,
      via_administracion: desparasitacion.via,
      especies: desparasitacion.especies,
      edad_minima: parseFloat(desparasitacion.edadMinima),
      edad_unidad: desparasitacion.edadUnidad,
      frecuencia: parseInt(desparasitacion.frecuencia),
      frecuencia_unidad: desparasitacion.frecuenciaUnidad,
      recomendaciones: desparasitacion.recomendaciones,
      riesgos: desparasitacion.riesgos,
      dosis_recomendada: desparasitacion.dosis
    }

    console.log('Datos a enviar:', payload)

    // Enviar datos al servidor con el token de autenticación
    const response = await fetch('/api/tipos-desparasitacion', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(payload)
    })

    const data = await response.json()

    if (data.success) {
      alert('Tipo de desparasitación registrado correctamente')
      router.push('/veterinarios/tipos/desparasitaciones')
    } else {
      // Mostrar errores de validación
      if (data.errors) {
        const errorMessages = Object.values(data.errors).flat().join('\n')
        alert('Error al registrar: ' + errorMessages)
      } else {
        alert('Error al registrar: ' + data.message)
      }
    }
  } catch (error) {
    console.error('Error:', error)
    
    if (error.response?.status === 401) {
      alert('Su sesión ha expirado. Por favor, inicie sesión nuevamente.')
      router.push('/veterinario/login')
    } else {
      alert('Error al conectar con el servidor')
    }
  } finally {
    submitting.value = false
  }
}

const actualizarDesparasitacion = async () => {
  if (!isFormValid.value) {
    alert('Por favor complete todos los campos obligatorios')
    return
  }

  try {
    submitting.value = true

    // Preparar los datos para enviar al servidor
    const payload = {
      nombre: desparasitacion.nombre,
      parasitos: desparasitacion.parasitos,
      otros_parasitos: desparasitacion.otrosParasitos,
      via_administracion: desparasitacion.via,
      especies: desparasitacion.especies,
      edad_minima: parseFloat(desparasitacion.edadMinima),
      edad_unidad: desparasitacion.edadUnidad,
      frecuencia: parseInt(desparasitacion.frecuencia),
      frecuencia_unidad: desparasitacion.frecuenciaUnidad,
      recomendaciones: desparasitacion.recomendaciones,
      riesgos: desparasitacion.riesgos,
      dosis_recomendada: desparasitacion.dosis
    }

    console.log('Actualizando datos:', payload)

    // Enviar datos al servidor con el token de autenticación
    const response = await fetch(`/api/tipos-desparasitacion/${tipoId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(payload)
    })

    const data = await response.json()

    if (data.success) {
      // Mostrar modal de éxito
      showModal.value = true
    } else {
      // Mostrar errores de validación
      if (data.errors) {
        const errorMessages = Object.values(data.errors).flat().join('\n')
        alert('Error al actualizar: ' + errorMessages)
      } else {
        alert('Error al actualizar: ' + data.message)
      }
    }
  } catch (error) {
    console.error('Error:', error)
    
    if (error.response?.status === 401) {
      alert('Su sesión ha expirado. Por favor, inicie sesión nuevamente.')
      router.push('/veterinario/login')
    } else {
      alert('Error al conectar con el servidor')
    }
  } finally {
    submitting.value = false
  }
}

const irALista = () => {
  showModal.value = false
  router.push('/veterinarios/tipos/desparasitaciones')
}

const continuarEditando = () => {
  showModal.value = false
  // El usuario puede seguir editando si lo desea
}

// Verificar autenticación al cargar el componente
onMounted(async () => {
  try {
    await checkAuth()
    verificarEdicion()
  } catch (error) {
    console.error('Error verificando autenticación:', error)
    router.push('/veterinario/login')
  } finally {
    loading.value = false
  }
})
</script>
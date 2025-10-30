<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar' : 'Registrar' }} Tipo de Revisión Médica</h1>

    <form @submit.prevent="esEdicion ? abrirModalConfirmacion() : registrarRevision()" class="space-y-4">
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
            <label class="block font-medium">Nombre del tipo de revisión</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="revision.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Chequeo general, Control postquirúrgico"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Descripción clínica breve</label>
            <textarea 
              v-model="revision.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción del propósito y alcance de la revisión"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Frecuencia recomendada</label>
            <select v-model="revision.frecuencia_recomendada" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="anual">Anual</option>
              <option value="semestral">Semestral</option>
              <option value="trimestral">Trimestral</option>
              <option value="mensual">Mensual</option>
              <option value="post_procedimiento">Post-procedimiento</option>
              <option value="personalizada">Personalizada</option>
            </select>
            <input 
              v-if="revision.frecuencia_recomendada === 'personalizada'"
              v-model="revision.frecuencia_personalizada"
              type="text"
              required
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique la frecuencia"
            />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Áreas para revisar</label>
            <div class="flex flex-wrap gap-2">
              <label v-for="area in areasRevisar" :key="area.value" class="flex items-center space-x-2">
                <input type="checkbox" v-model="revision.areas_revisar" :value="area.value" class="rounded">
                <span>{{ area.label }}</span>
              </label>
            </div>
            <input 
              v-model="revision.otra_area"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Otra área a revisar (especificar)"
            />
          </div>

          <div>
            <label class="block font-medium">Indicadores clave esperables</label>
            <textarea 
              v-model="revision.indicadores_clave" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Peso, temperatura, pulso, etc."
            ></textarea>
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
          <label class="block font-medium">Especie objetivo</label>
          <select v-model="revision.especie_objetivo" class="w-full border rounded p-2">
            <option value="todos">Todos</option>
            <option value="canino">Canino</option>
            <option value="felino">Felino</option>
            <option value="ave">Ave</option>
            <option value="roedor">Roedor</option>
            <option value="exotico">Exótico</option>
          </select>
        </div>

        <div>
          <label class="block font-medium">Edad sugerida para la revisión</label>
          <div class="flex">
            <input 
              v-model="revision.edad_sugerida" 
              type="number" 
              min="0" 
              step="0.5" 
              class="w-1/2 border rounded-l p-2" 
              placeholder="Edad" 
            />
            <select 
              v-model="revision.edad_unidad" 
              class="w-1/2 border rounded-r p-2"
            >
              <option value="semanas">Semanas</option>
              <option value="meses">Meses</option>
              <option value="años">Años</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-medium">Recomendaciones profesionales</label>
          <textarea 
            v-model="revision.recomendaciones_profesionales" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Consejos para realizar esta revisión"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Riesgos clínicos asociados</label>
          <textarea 
            v-model="revision.riesgos_clinicos" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Posibles complicaciones o riesgos"
          ></textarea>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="button" @click="cancelar" class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors">Cancelar</button>
        <button type="submit" :disabled="loading" class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300">
          {{ loading ? (esEdicion ? 'Actualizando...' : 'Registrando...') : (esEdicion ? 'Actualizar' : '+ Tipo') }}
        </button>
      </div>
    </form>

    <!-- Modal de confirmación para edición -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="text-center">
          <h3 class="text-xl font-bold text-gray-800 mb-4">Confirmar Actualización</h3>
          <p class="text-gray-600 mb-6">
            ¿Está seguro que desea actualizar este tipo de revisión médica? 
            Los cambios serán permanentes.
          </p>
          <div class="flex justify-center gap-4">
            <button 
              @click="cerrarModal" 
              class="bg-gray-500 text-white font-bold px-6 py-2 rounded-full hover:bg-gray-700 transition-colors"
            >
              Cancelar
            </button>
            <button 
              @click="confirmarActualizacion" 
              :disabled="loading"
              class="bg-blue-500 text-white font-bold px-6 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
            >
              {{ loading ? 'Actualizando...' : 'Confirmar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const route = useRoute()
const { isVeterinario, isAprobado, accessToken, checkAuth, user } = useAuth()
const loading = ref(false)
const mostrarModal = ref(false)

// Determinar si estamos en modo edición
const esEdicion = computed(() => {
  return route.name === 'editarTipoRevision' || route.params.id
})

const areasRevisar = [
  { value: 'piel', label: 'Piel' },
  { value: 'ojos', label: 'Ojos' },
  { value: 'oidos', label: 'Oídos' },
  { value: 'boca', label: 'Boca' },
  { value: 'corazon', label: 'Corazón' },
  { value: 'pulmones', label: 'Pulmones' },
  { value: 'abdomen', label: 'Abdomen' },
  { value: 'articulaciones', label: 'Articulaciones' },
  { value: 'comportamiento', label: 'Comportamiento' },
  { value: 'nutricion', label: 'Nutrición' }
]

const revision = reactive({
  nombre: '',
  descripcion: '',
  frecuencia_recomendada: '',
  frecuencia_personalizada: '',
  areas_revisar: [],
  otra_area: '',
  indicadores_clave: '',
  especie_objetivo: 'todos',
  edad_sugerida: null,
  edad_unidad: 'meses',
  recomendaciones_profesionales: '',
  riesgos_clinicos: ''
})

onMounted(async () => {
  try {
    loading.value = true
    console.log('Iniciando componente, modo edición:', esEdicion.value)
    
    const estaAutenticado = await checkAuth()
    
    if (!estaAutenticado) {
      console.log('Usuario no autenticado, redirigiendo al login')
      router.push('/veterinario/login')
      return
    }

    if (!isVeterinario() || !isAprobado()) {
      console.log('Usuario no autorizado')
      alert('No tienes permisos para acceder a esta funcionalidad')
      router.push('/dashboard')
      return
    }

    console.log('Usuario autorizado, token:', accessToken.value ? 'Presente' : 'Ausente')

    // Si estamos en modo edición, cargar los datos existentes
    if (esEdicion.value && route.params.id) {
      console.log('Cargando datos para edición con ID:', route.params.id)
      await cargarDatosEdicion()
    } else {
      console.log('Modo creación o ID no disponible')
    }

  } catch (error) {
    console.error('Error en mounted:', error)
    router.push('/veterinario/login')
  } finally {
    loading.value = false
  }
})

const cargarDatosEdicion = async () => {
  try {
    console.log('Cargando datos para edición, ID:', route.params.id)
    
    const response = await fetch(`/api/tipos-revision/${route.params.id}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const result = await response.json()
      console.log('Datos recibidos del servidor:', result)
      
      if (result.success && result.data) {
        const datos = result.data
        
        // Asignar los datos uno por uno para mantener la reactividad
        revision.nombre = datos.nombre || ''
        revision.descripcion = datos.descripcion || ''
        revision.frecuencia_recomendada = datos.frecuencia_recomendada || ''
        revision.frecuencia_personalizada = datos.frecuencia_personalizada || ''
        revision.areas_revisar = Array.isArray(datos.areas_revisar) ? datos.areas_revisar : []
        revision.otra_area = datos.otra_area || ''
        revision.indicadores_clave = datos.indicadores_clave || ''
        revision.especie_objetivo = datos.especie_objetivo || 'todos'
        revision.edad_sugerida = datos.edad_sugerida || null
        revision.edad_unidad = datos.edad_unidad || 'meses'
        revision.recomendaciones_profesionales = datos.recomendaciones_profesionales || ''
        revision.riesgos_clinicos = datos.riesgos_clinicos || ''
        
        console.log('Datos asignados al formulario:', revision)
      } else {
        throw new Error(result.message || 'Error en la respuesta del servidor')
      }
    } else {
      const errorData = await response.json().catch(() => ({ message: 'Error desconocido' }))
      throw new Error(errorData.message || `Error HTTP: ${response.status}`)
    }
  } catch (error) {
    console.error('Error cargando datos:', error)
    alert(`Error al cargar los datos del tipo de revisión: ${error.message}`)
  }
}

const abrirModalConfirmacion = () => {
  mostrarModal.value = true
}

const cerrarModal = () => {
  mostrarModal.value = false
}

const confirmarActualizacion = async () => {
  await actualizarRevision()
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const registrarRevision = async () => {
  try {
    loading.value = true

    console.log('Datos actuales de revision:', revision)

    // Validaciones básicas
    if (!revision.nombre.trim()) {
      alert('El nombre es obligatorio')
      return
    }

    if (!revision.descripcion.trim()) {
      alert('La descripción es obligatoria')
      return
    }

    if (!revision.frecuencia_recomendada) {
      alert('La frecuencia recomendada es obligatoria')
      return
    }

    if (revision.frecuencia_recomendada === 'personalizada' && !revision.frecuencia_personalizada.trim()) {
      alert('Debe especificar la frecuencia personalizada')
      return
    }

    if (revision.areas_revisar.length === 0 && !revision.otra_area.trim()) {
      alert('Debe seleccionar al menos un área a revisar')
      return
    }

    // Preparar datos para enviar
    const datosEnvio = {
      nombre: revision.nombre,
      descripcion: revision.descripcion,
      frecuencia_recomendada: revision.frecuencia_recomendada,
      frecuencia_personalizada: revision.frecuencia_recomendada === 'personalizada' ? revision.frecuencia_personalizada : null,
      areas_revisar: revision.areas_revisar,
      otra_area: revision.otra_area || null,
      indicadores_clave: revision.indicadores_clave || null,
      especie_objetivo: revision.especie_objetivo,
      edad_sugerida: revision.edad_sugerida ? parseFloat(revision.edad_sugerida) : null,
      edad_unidad: revision.edad_sugerida ? revision.edad_unidad : null,
      recomendaciones_profesionales: revision.recomendaciones_profesionales || null,
      riesgos_clinicos: revision.riesgos_clinicos || null
    }

    console.log('Enviando datos al servidor:', datosEnvio)

    const response = await fetch('/api/tipos-revision', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    })

    const data = await response.json()

    if (!response.ok) {
      console.error('Error del servidor:', data)
      
      // Mostrar errores de validación específicos
      if (data.errors) {
        const errores = Object.values(data.errors).flat().join('\n')
        throw new Error(`Errores de validación:\n${errores}`)
      }
      
      throw new Error(data.message || 'Error al registrar el tipo de revisión')
    }

    if (data.success) {
      alert('Tipo de revisión registrado correctamente')
      router.push('/veterinarios/tipos/revisiones')
    } else {
      throw new Error(data.message || 'Error desconocido')
    }

  } catch (error) {
    console.error('Error:', error)
    alert(`Error al registrar el tipo de revisión: ${error.message}`)
  } finally {
    loading.value = false
  }
}

const actualizarRevision = async () => {
  try {
    loading.value = true

    // Validaciones básicas (las mismas que para registro)
    if (!revision.nombre.trim()) {
      alert('El nombre es obligatorio')
      mostrarModal.value = false
      return
    }

    if (!revision.descripcion.trim()) {
      alert('La descripción es obligatoria')
      mostrarModal.value = false
      return
    }

    if (!revision.frecuencia_recomendada) {
      alert('La frecuencia recomendada es obligatoria')
      mostrarModal.value = false
      return
    }

    if (revision.frecuencia_recomendada === 'personalizada' && !revision.frecuencia_personalizada.trim()) {
      alert('Debe especificar la frecuencia personalizada')
      mostrarModal.value = false
      return
    }

    if (revision.areas_revisar.length === 0 && !revision.otra_area.trim()) {
      alert('Debe seleccionar al menos un área a revisar')
      mostrarModal.value = false
      return
    }

    // Preparar datos para enviar
    const datosEnvio = {
      nombre: revision.nombre,
      descripcion: revision.descripcion,
      frecuencia_recomendada: revision.frecuencia_recomendada,
      frecuencia_personalizada: revision.frecuencia_recomendada === 'personalizada' ? revision.frecuencia_personalizada : null,
      areas_revisar: revision.areas_revisar,
      otra_area: revision.otra_area || null,
      indicadores_clave: revision.indicadores_clave || null,
      especie_objetivo: revision.especie_objetivo,
      edad_sugerida: revision.edad_sugerida ? parseFloat(revision.edad_sugerida) : null,
      edad_unidad: revision.edad_sugerida ? revision.edad_unidad : null,
      recomendaciones_profesionales: revision.recomendaciones_profesionales || null,
      riesgos_clinicos: revision.riesgos_clinicos || null
    }

    console.log('Actualizando tipo de revisión:', datosEnvio)

    const response = await fetch(`/api/tipos-revision/${route.params.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    })

    const data = await response.json()

    if (!response.ok) {
      console.error('Error del servidor:', data)
      
      if (data.errors) {
        const errores = Object.values(data.errors).flat().join('\n')
        throw new Error(`Errores de validación:\n${errores}`)
      }
      
      throw new Error(data.message || 'Error al actualizar el tipo de revisión')
    }

    if (data.success) {
      alert('Tipo de revisión actualizado correctamente')
      router.push('/veterinarios/tipos/revisiones')
    } else {
      throw new Error(data.message || 'Error desconocido')
    }

  } catch (error) {
    console.error('Error:', error)
    alert(`Error al actualizar el tipo de revisión: ${error.message}`)
  } finally {
    loading.value = false
    mostrarModal.value = false
  }
}
</script>
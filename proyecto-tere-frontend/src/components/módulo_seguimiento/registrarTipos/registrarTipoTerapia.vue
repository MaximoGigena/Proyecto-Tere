<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Tipo de Terapia' : 'Registrar Tipo de Terapia' }}</h1>

    <form @submit.prevent="guardarTerapia" class="space-y-4">
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
            <label class="block font-medium">Nombre del tipo de terapia</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="terapia.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Fisioterapia postquirúrgica, Quimioterapia"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Descripción general</label>
            <textarea 
              v-model="terapia.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción detallada de la terapia"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Especie objetivo</label>
            <select v-model="terapia.especie" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="canino">Canino</option>
              <option value="felino">Felino</option>
              <option value="ave">Ave</option>
              <option value="roedor">Roedor</option>
              <option value="exotico">Exótico</option>
              <option value="todos">Todos</option>
            </select>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Duración típica del tratamiento</label>
            <div class="flex">
              <input 
                v-model="terapia.duracionValor" 
                type="number" 
                min="1" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Cantidad" 
              />
              <select 
                v-model="terapia.duracionUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
              >
                <option value="sesiones">Sesiones</option>
                <option value="dias">Días</option>
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Frecuencia sugerida</label>
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
            <label class="block font-medium">Requisitos o condiciones previas</label>
            <textarea 
              v-model="terapia.requisitos" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Condiciones que debe cumplir el paciente para esta terapia"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Indicaciones clínicas comunes</label>
            <textarea 
              v-model="terapia.indicaciones" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Casos o condiciones donde se recomienda esta terapia"
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
          <label class="block font-medium">Contraindicaciones</label>
          <textarea 
            v-model="terapia.contraindicaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Situaciones donde no aplicar esta terapia"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Riesgos o efectos secundarios</label>
          <textarea 
            v-model="terapia.riesgos" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Posibles efectos adversos conocidos"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Recomendaciones clínicas</label>
          <textarea 
            v-model="terapia.recomendaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Consejos para la aplicación de esta terapia"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Observaciones adicionales</label>
          <textarea 
            v-model="terapia.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante sobre esta terapia"
          ></textarea>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="button" @click="cancelar" class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors">Cancelar</button>
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors">
          {{ esEdicion ? 'Guardar' : '+ Tipo' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const esEdicion = ref(false)
const terapiaId = ref(null)
const cargando = ref(false)

const terapia = reactive({
  nombre: '',
  descripcion: '',
  especie: '',
  duracionValor: '',
  duracionUnidad: 'sesiones',
  frecuencia: '',
  requisitos: '',
  indicaciones: '',
  contraindicaciones: '',
  riesgos: '',
  recomendaciones: '',
  observaciones: ''
})

// Verificar autenticación y cargar datos si es edición
onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      console.error('Debe iniciar sesión para acceder a esta página')
      setTimeout(() => {
        router.push('/')
      }, 2000)
      return
    }
  }

  // Verificar si estamos en modo edición
  if (route.params.id) {
    esEdicion.value = true
    terapiaId.value = route.params.id
    await cargarTerapia()
  }
})

// Cargar datos de la terapia para edición
const cargarTerapia = async () => {
  try {
    cargando.value = true
    const response = await fetch(`/api/tipos-terapia/${terapiaId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      if (data.success) {
        const terapiaData = data.data
        // Asignar los datos cargados al reactive object
        Object.assign(terapia, {
          nombre: terapiaData.nombre || '',
          descripcion: terapiaData.descripcion || '',
          especie: terapiaData.especie || '',
          duracionValor: terapiaData.duracion_valor || '',
          duracionUnidad: terapiaData.duracion_unidad || 'sesiones',
          frecuencia: terapiaData.frecuencia || '',
          requisitos: terapiaData.requisitos || '',
          indicaciones: terapiaData.indicaciones_clinicas || '',
          contraindicaciones: terapiaData.contraindicaciones || '',
          riesgos: terapiaData.riesgos_efectos_secundarios || '',
          recomendaciones: terapiaData.recomendaciones_clinicas || '',
          observaciones: terapiaData.observaciones || ''
        })
      }
    } else {
      throw new Error('Error al cargar los datos de la terapia')
    }
  } catch (error) {
    console.error('Error al cargar terapia:', error)
    alert('Error al cargar los datos de la terapia')
  } finally {
    cargando.value = false
  }
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const guardarTerapia = async () => {
  try {
    // Validar datos obligatorios
    if (!terapia.nombre || !terapia.descripcion || !terapia.especie || !terapia.duracionValor || !terapia.frecuencia || !terapia.requisitos || !terapia.indicaciones) {
      alert('Por favor complete todos los campos obligatorios')
      return
    }

    cargando.value = true

    // Preparar datos para enviar
    const datosEnvio = {
      nombre: terapia.nombre,
      descripcion: terapia.descripcion,
      especie: terapia.especie,
      duracion_valor: parseInt(terapia.duracionValor),
      duracion_unidad: terapia.duracionUnidad,
      frecuencia: terapia.frecuencia,
      requisitos: terapia.requisitos,
      indicaciones_clinicas: terapia.indicaciones,
      contraindicaciones: terapia.contraindicaciones || '',
      riesgos_efectos_secundarios: terapia.riesgos || '',
      recomendaciones_clinicas: terapia.recomendaciones || '',
      observaciones: terapia.observaciones || ''
    }

    // Determinar método y URL según si es creación o edición
    const method = esEdicion.value ? 'PUT' : 'POST'
    const url = esEdicion.value ? `/api/tipos-terapia/${terapiaId.value}` : '/api/tipos-terapia'

    // Enviar datos al servidor
    const response = await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    })

    const data = await response.json()

    if (response.ok && data.success) {
      alert(`Tipo de terapia ${esEdicion.value ? 'actualizado' : 'registrado'} correctamente`)
      router.push('/veterinarios/tipos/terapias')
    } else {
      if (data.errors) {
        const errores = Object.values(data.errors).flat().join('\n')
        alert(`Error al ${esEdicion.value ? 'actualizar' : 'registrar'}: ${errores}`)
      } else {
        alert(`Error al ${esEdicion.value ? 'actualizar' : 'registrar'}: ${data.message || 'Error desconocido'}`)
      }
    }
  } catch (error) {
    console.error('Error:', error)
    alert('Error al conectar con el servidor')
  } finally {
    cargando.value = false
  }
}
</script>
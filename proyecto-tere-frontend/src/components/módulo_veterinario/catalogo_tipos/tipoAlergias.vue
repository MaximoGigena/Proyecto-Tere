<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
      Gestión de Tipos de Alergias
    </h2>

    <div class="max-w-4xl mx-auto p-6">
      <div class="border p-4 rounded-lg shadow bg-white max-w-8xl mx-auto">
        <!-- 🌀 ANIMACIÓN DE CARGA -->
        <div v-if="loading" class="flex flex-wrap -m-2 animate-pulse">
          <div v-for="n in 6" :key="n" class="w-1/3 p-2">
            <div class="p-6 border rounded-2xl bg-gray-100 shadow-sm h-48 flex flex-col justify-between">
              <div>
                <div class="h-4 bg-gray-300 rounded w-2/3 mb-3"></div>
                <div class="h-3 bg-gray-300 rounded w-1/3 mb-2"></div>
                <div class="h-3 bg-gray-200 rounded w-full mb-1"></div>
                <div class="h-3 bg-gray-200 rounded w-5/6"></div>
              </div>
              <div class="flex justify-between items-center mt-3">
                <div class="h-4 bg-gray-300 rounded w-16"></div>
                <div class="flex gap-2">
                  <div class="w-5 h-5 bg-gray-300 rounded-full"></div>
                  <div class="w-5 h-5 bg-gray-300 rounded-full"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 📋 LISTA DE ALERGIAS -->
        <div v-else-if="tiposAlergias.length" class="flex flex-wrap -m-2">
          <transition-group name="fade" tag="div" class="flex flex-wrap -m-2 w-full">
            <div
              v-for="tipo in tiposAlergias"
              :key="tipo.id"
              class="w-1/3 p-2"
            >
              <div
                class="p-6 border rounded-2xl bg-white shadow-sm hover:shadow-md transition flex flex-col justify-between h-full"
              >
                <!-- Contenido de la tarjeta -->
                <div>
                  <div class="flex justify-between items-start">
                    <h3 class="text-lg font-semibold text-gray-800">
                      {{ tipo.nombre }}
                    </h3>
                    <span class="text-xs text-gray-400">#{{ tipo.id }}</span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">
                    {{ tipo.descripcion }}
                  </p>
                </div>

                <div class="mt-3 flex justify-between items-center">
                  <span
                    class="text-xs px-2 py-1 rounded-full font-medium"
                    :class="tipo.activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                  >
                    {{ tipo.activo ? 'Activo' : 'Inactivo' }}
                  </span>

                  <div class="flex gap-2 text-gray-500">
                    <button
                      @click="editarTipo(tipo)"
                      class="hover:text-blue-600 transition"
                    >
                      <Edit class="w-5 h-5" />
                    </button>
                    <button
                      @click="eliminarTipo(tipo)"
                      class="hover:text-red-600 transition"
                    >
                      <Trash2 class="w-5 h-5" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </transition-group>
        </div>

        <!-- 🧩 MENSAJE SIN REGISTROS -->
        <div v-else class="text-center text-gray-500 italic py-10">
          No hay tipos de alergias registrados.
        </div>
      </div>
    </div>

    <!-- ➕ BOTÓN FLOTANTE -->
    <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50">
      <button
        @click="abrirRegistro()"
        class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-full shadow-lg hover:bg-blue-700 transition"
      >
        + Nuevo Tipo
      </button>
    </div>
  </div>
</template>


<script setup>
import { ref, onMounted } from 'vue'
import { Edit, Trash2 } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { accessToken, isAuthenticated } = useAuth()

const tiposAlergias = ref([])
const loading = ref(false)
const error = ref(null)

// Filtros
const filtros = ref({
  search: '',
  categoria: 'todas',
  riesgo: 'todos'
})

// Timer para búsqueda
let timerBusqueda = null

// Mapeo de etiquetas
const categoriasMap = {
  medicamento: 'Medicamento',
  alimento: 'Alimento',
  ambiental: 'Ambiental',
  contacto: 'Por contacto',
  otra: 'Otra'
}

const nivelesRiesgoMap = {
  leve: 'Leve',
  moderado: 'Moderado',
  grave: 'Grave',
  muy_grave: 'Muy grave'
}

const especiesMap = {
  canino: 'Canino',
  felino: 'Felino',
  ave: 'Ave',
  roedor: 'Roedor',
  exotico: 'Exótico',
  todos: 'Todos'
}

// Clases CSS para los niveles de riesgo
const claseRiesgo = (nivel) => {
  const clases = {
    leve: 'bg-green-100 text-green-800',
    moderado: 'bg-yellow-100 text-yellow-800',
    grave: 'bg-orange-100 text-orange-800',
    muy_grave: 'bg-red-100 text-red-800'
  }
  return clases[nivel] || 'bg-gray-100 text-gray-800'
}

// Helper functions
const obtenerEtiquetaCategoria = (categoria, categoriaOtro) => {
  return categoria === 'otra' && categoriaOtro ? categoriaOtro : categoriasMap[categoria] || categoria
}

const obtenerEtiquetaRiesgo = (riesgo) => {
  return nivelesRiesgoMap[riesgo] || riesgo
}

const obtenerEtiquetaEspecie = (especie) => {
  return especiesMap[especie] || especie
}

// Cargar tipos de alergia desde la API
const cargarTiposAlergias = async () => {
  if (!isAuthenticated.value) {
    error.value = 'Debe estar autenticado para ver los tipos de alergia'
    return
  }

  try {
    loading.value = true
    error.value = null

    const params = new URLSearchParams()
    
    if (filtros.value.search) {
      params.append('search', filtros.value.search)
    }
    if (filtros.value.categoria !== 'todas') {
      params.append('categoria', filtros.value.categoria)
    }
    if (filtros.value.riesgo !== 'todos') {
      params.append('nivel_riesgo', filtros.value.riesgo)
    }

    const response = await axios.get(`/api/tipos-alergia?${params.toString()}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json'
      }
    })

    if (response.data.success) {
      tiposAlergias.value = response.data.data
    } else {
      throw new Error(response.data.message || 'Error al cargar los tipos de alergia')
    }

  } catch (err) {
    console.error('Error al cargar tipos de alergia:', err)
    error.value = err.response?.data?.message || 'Error al cargar los tipos de alergia'
  } finally {
    loading.value = false
  }
}

// Búsqueda con debounce
const buscarTipos = () => {
  clearTimeout(timerBusqueda)
  timerBusqueda = setTimeout(() => {
    cargarTiposAlergias()
  }, 500)
}

// Navegación
const abrirRegistro = () => {
  router.push('/registro/registroTipoAlergia')
}

const editarTipo = (tipo) => {
  // Aquí puedes implementar la edición
  // Por ahora redirige al formulario de registro con el ID
  router.push(`/registro/registroTipoAlergia/${tipo.id}`)
}

const eliminarTipo = async (tipo) => {
  if (!confirm(`¿Está seguro que desea eliminar el tipo de alergia "${tipo.nombre}"?`)) {
    return
  }

  try {
    const response = await axios.delete(`/api/tipos-alergia/${tipo.id}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Content-Type': 'application/json'
      }
    })

    if (response.data.success) {
      // Recargar la lista
      await cargarTiposAlergias()
      alert('Tipo de alergia eliminado correctamente')
    } else {
      throw new Error(response.data.message || 'Error al eliminar')
    }

  } catch (error) {
    console.error('Error al eliminar tipo de alergia:', error)
    alert(error.response?.data?.message || 'Error al eliminar el tipo de alergia')
  }
}

// Cargar datos al montar el componente
onMounted(() => {
  cargarTiposAlergias()
})
</script>

<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
      Gestión de Tipos de Fármacos
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

        <!-- 📋 LISTA DE FÁRMACOS -->
        <div v-else-if="tiposFarmacos.length" class="flex flex-wrap -m-2">
          <transition-group name="fade" tag="div" class="flex flex-wrap -m-2 w-full">
            <div
              v-for="tipo in tiposFarmacos"
              :key="tipo.id"
              class="w-1/3 p-2"
            >
              <div
                class="p-6 border rounded-2xl bg-white shadow-sm hover:shadow-md transition flex flex-col justify-between h-full"
              >
                <div>
                  <div class="flex justify-between items-start">
                    <h3 class="text-lg font-semibold text-gray-800">
                      {{ tipo.nombre_comercial }}
                    </h3>
                    <span class="text-xs text-gray-400">#{{ tipo.id }}</span>
                  </div>

                  <div class="mt-2">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                      Nombre genérico:
                    </p>
                    <div class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                      <p v-if="tipo.nombre_generico" class="text-xs">
                        {{ tipo.nombre_generico }}
                      </p>
                      <p v-else class="text-xs text-gray-500 italic">
                        No especificado
                      </p>
                    </div>
                  </div>

                  <div class="mt-3">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                      Indicaciones clínicas:
                    </p>
                    <div class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                      <p v-if="tipo.indicaciones_clinicas" class="text-xs line-clamp-3">
                        {{ tipo.indicaciones_clinicas }}
                      </p>
                      <p v-else class="text-xs text-gray-500 italic">
                        No se especificaron indicaciones
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Categoría + Estado + Acciones -->
                <div class="mt-3 flex justify-between items-center">
                  <div class="flex flex-col gap-1">
                    <span class="text-xs px-2 py-1 rounded-full font-medium bg-blue-100 text-blue-700">
                      {{ formatCategoria(tipo.categoria, tipo.categoria_otro) }}
                    </span>
                    <span
                      class="text-xs px-2 py-1 rounded-full font-medium"
                      :class="tipo.activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                    >
                      {{ tipo.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                  </div>

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
          No hay tipos de fármacos registrados.
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
import { useAuth } from '@/composables/useAuth'
import axios from 'axios'

const router = useRouter()
const { accessToken, isAuthenticated } = useAuth()

const tiposFarmacos = ref([])
const loading = ref(false)
const error = ref(null)

// Formatear categoría para mostrar
const formatCategoria = (categoria, categoriaOtro) => {
  const categoriasMap = {
    'analgesico': 'Analgésico',
    'antibiotico': 'Antibiótico',
    'antiparasitario': 'Antiparasitario',
    'antiinflamatorio': 'Antiinflamatorio',
    'antifungico': 'Antifúngico',
    'antiviral': 'Antiviral',
    'anestesico': 'Anestésico',
    'otro': categoriaOtro || 'Otro'
  }
  return categoriasMap[categoria] || categoria
}

// Cargar tipos de fármacos desde la API
const cargarTiposFarmacos = async () => {
  try {
    loading.value = true
    error.value = null

    // Verificar autenticación y permisos
    if (!isAuthenticated.value) {
      throw new Error('No estás autenticado')
    }

    const response = await axios.get('/api/tipos-farmaco', {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
        Accept: 'application/json',
      },
    })

    if (response.data.success) {
      tiposFarmacos.value = response.data.data
    } else {
      throw new Error(response.data.message || 'Error al cargar los tipos de fármacos')
    }
  } catch (err) {
    console.error('Error cargando tipos de fármacos:', err)
    error.value = err.response?.data?.message || err.message || 'Error desconocido'
    
    // Si es error de autenticación, redirigir al login
    if (err.response?.status === 401) {
      router.push('/login')
    }
  } finally {
    loading.value = false
  }
}

// Eliminar tipo de fármaco
const eliminarTipo = async (tipo) => {
  if (!confirm(`¿Estás seguro de que deseas eliminar el fármaco "${tipo.nombre_comercial}"?`)) {
    return
  }

  try {
    const response = await axios.delete(`/api/tipos-farmaco/${tipo.id}`, {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
        Accept: 'application/json',
      },
    })

    if (response.data.success) {
      // Recargar la lista
      await cargarTiposFarmacos()
    } else {
      throw new Error(response.data.message || 'Error al eliminar el fármaco')
    }
  } catch (err) {
    console.error('Error eliminando fármaco:', err)
    alert(err.response?.data?.message || err.message || 'Error al eliminar el fármaco')
  }
}

const abrirRegistro = () => {
  router.push('/registro/registroTipoFarmaco')
}

const editarTipo = (tipo) => {
  // Navegar a la página de edición con el ID del fármaco
  router.push(`/registro/registroTipoFarmaco?editar=${tipo.id}`)
}

// Cargar los datos cuando el componente se monte
onMounted(() => {
  cargarTiposFarmacos()
})
</script>


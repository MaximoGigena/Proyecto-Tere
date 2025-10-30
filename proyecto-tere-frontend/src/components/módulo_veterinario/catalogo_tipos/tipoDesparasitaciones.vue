<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
      Gestión de Tipos de Desparasitaciones
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

        <!-- 📋 LISTA DE DESPARASITACIONES -->
        <div v-else-if="tiposDesparasitaciones.length" class="flex flex-wrap -m-2">
          <transition-group name="fade" tag="div" class="flex flex-wrap -m-2 w-full">
            <div
              v-for="tipo in tiposDesparasitaciones"
              :key="tipo.id"
              class="w-1/3 p-2"
            >
              <div
                class="p-6 border rounded-2xl bg-white shadow-sm hover:shadow-md transition flex flex-col justify-between h-full"
              >
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
                      @click="darDeBajaTipo(tipo)"
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
          No hay tipos de desparasitaciones registrados.
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
import { ref, onMounted, computed } from 'vue'
import { Edit, Trash2 } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { accessToken, isVeterinario, isAprobado, fetchUser, isAuthenticated } = useAuth()

const tiposDesparasitaciones = ref([])
const loading = ref(false)
const error = ref(null)
const userLoaded = ref(false)

// Computed para verificar permisos
const hasPermissions = computed(() => {
  return isVeterinario() && isAprobado()
})

// Formatear parásitos para mostrar
const formatParasitos = (parasitos) => {
  if (!parasitos) return 'No especificados'
  if (Array.isArray(parasitos)) {
    return parasitos.join(', ')
  }
  return typeof parasitos === 'string' ? parasitos : 'No especificados'
}

// Formatear especies para mostrar
const formatEspecies = (especies) => {
  if (!especies) return 'No especificadas'
  if (Array.isArray(especies)) {
    return especies.join(', ')
  }
  return typeof especies === 'string' ? especies : 'No especificadas'
}

// Verificar y cargar usuario
const initializeAuth = async () => {
  try {
    loading.value = true
    if (!isAuthenticated.value) {
      error.value = 'No estás autenticado. Por favor, inicia sesión.'
      return false
    }

    // Asegurarse de que el usuario esté cargado
    if (!isVeterinario()) {
      await fetchUser()
    }

    if (!hasPermissions.value) {
      error.value = 'No tienes permisos para acceder a esta sección. Debes ser un veterinario aprobado.'
      return false
    }

    userLoaded.value = true
    return true
  } catch (err) {
    console.error('Error inicializando autenticación:', err)
    error.value = 'Error de autenticación. Por favor, reinicia sesión.'
    return false
  } finally {
    loading.value = false
  }
}

// Cargar tipos de desparasitaciones desde la API
const cargarTiposDesparasitaciones = async () => {
  try {
    loading.value = true
    error.value = null

    // Verificar autenticación primero
    const authReady = await initializeAuth()
    if (!authReady) {
      return
    }

    console.log('🔐 Token enviado:', accessToken.value ? 'SÍ' : 'NO')

    const response = await axios.get('/api/tipos-desparasitacion', {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    })

    if (response.data.success) {
      tiposDesparasitaciones.value = response.data.data
      console.log('✅ Tipos de desparasitaciones cargados:', response.data.data.length)
    } else {
      throw new Error(response.data.message || 'Error al cargar los tipos de desparasitaciones')
    }
  } catch (err) {
    console.error('❌ Error cargando tipos de desparasitaciones:', err)
    
    if (err.response?.status === 401) {
      error.value = 'Error de autenticación. Tu sesión ha expirado. Por favor, inicia sesión nuevamente.'
    } else if (err.response?.status === 403) {
      error.value = 'No tienes permisos para acceder a esta funcionalidad.'
    } else {
      error.value = err.response?.data?.message || err.message || 'Error desconocido al cargar los tipos de desparasitaciones'
    }
  } finally {
    loading.value = false
  }
}

const abrirRegistro = () => {
  if (hasPermissions.value) {
    router.push('/registro/registroTipoDesparasitacion')
  }
}

const editarTipo = (tipo) => {
  if (hasPermissions.value) {
    router.push(`/registro/registroTipoDesparasitacion/${tipo.id}`)
  }
}

// Dar de baja lógica
const darDeBajaTipo = async (tipo) => {
  if (!hasPermissions.value) return

  if (!confirm(`¿Estás seguro de que deseas eliminar el tipo de desparasitación "${tipo.nombre}"? Esta acción es permanente.`)) {
    return
  }

  try {
    const response = await axios.delete(`/api/tipos-desparasitacion/${tipo.id}`, {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    })

    if (response.data.success) {
      // Eliminar localmente del array
      tiposDesparasitaciones.value = tiposDesparasitaciones.value.filter(t => t.id !== tipo.id)
      console.log('✅ Tipo de desparasitación eliminado correctamente')
    } else {
      throw new Error(response.data.message || 'Error al eliminar')
    }
  } catch (err) {
    console.error('Error eliminando tipo de desparasitación:', err)
    alert('Error al eliminar el tipo de desparasitación: ' + (err.response?.data?.message || err.message))
  }
}

// Cargar datos al montar el componente
onMounted(() => {
  cargarTiposDesparasitaciones()
})
</script>

<style>
/* Animación fade */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>


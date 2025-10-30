<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
      Gestión de Tipos de Terapias
    </h2>

    <div class="max-w-4xl mx-auto p-6">
      <div class="border p-4 rounded-lg shadow bg-white max-w-8xl mx-auto">
        <!-- 🌀 ANIMACIÓN DE CARGA (acepta `cargando` o `loading`) -->
        <div v-if="cargando || loading" class="flex flex-wrap -m-2 animate-pulse">
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

        <!-- 📋 LISTA DE TERAPIAS -->
        <div v-else-if="tiposTerapias.length" class="flex flex-wrap -m-2">
          <transition-group name="fade" tag="div" class="flex flex-wrap -m-2 w-full">
            <div
              v-for="tipo in tiposTerapias"
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

                  <div class="mt-2">
                    <p class="text-sm font-medium text-gray-700 mb-1">
                      Descripción:
                    </p>
                    <div class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                      <template v-if="tipo.descripcion && tipo.descripcion.length > 0">
                        <p class="text-xs">
                          {{ tipo.descripcion }}
                        </p>
                      </template>
                      <template v-else>
                        <p class="text-xs text-gray-500 italic">
                          No se especificó descripción
                        </p>
                      </template>
                    </div>
                  </div>

                  <!-- Datos adicionales (si existen) -->
                  <div v-if="tipo.especie || tipo.duracion_valor || tipo.frecuencia" class="mt-3 space-y-1 text-xs text-gray-500">
                    <div v-if="tipo.especie" class="flex items-center gap-1">
                      <span class="font-medium">Especie:</span>
                      <span>{{ tipo.especie }}</span>
                    </div>
                    <div v-if="tipo.duracion_valor" class="flex items-center gap-1">
                      <span class="font-medium">Duración:</span>
                      <span>{{ tipo.duracion_valor }} {{ tipo.duracion_unidad }}</span>
                    </div>
                    <div v-if="tipo.frecuencia" class="flex items-center gap-1">
                      <span class="font-medium">Frecuencia:</span>
                      <span>{{ tipo.frecuencia }}</span>
                    </div>
                  </div>
                </div>

                <div class="mt-3 flex justify-between items-center">
                  <span
                    class="text-xs px-2 py-1 rounded-full font-medium"
                    :class=" tipo.activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' "
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
          No hay tipos de terapias registrados.
        </div>
      </div>
    </div>

    <!-- ➕ BOTÓN FLOTANTE (siempre visible, igual que tipoVacunas.vue) -->
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

const router = useRouter()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const tiposTerapias = ref([])
const cargando = ref(true)
const error = ref(null)

// Cargar tipos de terapia desde la API
const cargarTiposTerapia = async () => {
  try {
    cargando.value = true
    error.value = null

    // Verificar autenticación primero
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

    const response = await fetch('/api/tipos-terapia', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()
    
    if (data.success) {
      tiposTerapias.value = data.data || []
    } else {
      throw new Error(data.message || 'Error al cargar los tipos de terapia')
    }
  } catch (err) {
    console.error('Error al cargar tipos de terapia:', err)
    error.value = err.message
    alert('Error al cargar los tipos de terapia: ' + err.message)
  } finally {
    cargando.value = false
  }
}

// Eliminar tipo de terapia
const eliminarTipo = async (tipo) => {
  if (!confirm(`¿Está seguro que desea eliminar el tipo de terapia "${tipo.nombre}"? Esta acción no se puede deshacer.`)) {
    return
  }

  try {
    const response = await fetch(`/api/tipos-terapia/${tipo.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    })

    const data = await response.json()

    if (response.ok && data.success) {
      // Eliminar del array local
      tiposTerapias.value = tiposTerapias.value.filter(t => t.id !== tipo.id)
      alert('Tipo de terapia eliminado correctamente')
    } else {
      throw new Error(data.message || 'Error al eliminar el tipo de terapia')
    }
  } catch (error) {
    console.error('Error al eliminar tipo de terapia:', error)
    alert('Error al eliminar el tipo de terapia: ' + error.message)
  }
}

// Editar tipo de terapia
const editarTipo = (tipo) => {
  // Navegar a la vista de edición con el ID del tipo
  router.push(`/registro/registroTipoTerapia/${tipo.id}`)
}

const abrirRegistro = () => {
  router.push('/registro/registroTipoTerapia')
}

// Cargar datos cuando el componente se monta
onMounted(() => {
  cargarTiposTerapia()
})
</script>


<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
      Gestión de Tipos de Diagnósticos
    </h2>

    <div class="max-w-4xl mx-auto p-6">
      <div class="border p-4 rounded-lg shadow bg-white max-w-8xl mx-auto">
        <!-- 🌀 ANIMACIÓN DE CARGA -->
        <div v-if="cargando" class="flex flex-wrap -m-2 animate-pulse">
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

        <!-- 📋 LISTA DE DIAGNÓSTICOS -->
        <div v-else-if="tiposDiagnosticos.length" class="flex flex-wrap -m-2">
          <transition-group name="fade" tag="div" class="flex flex-wrap -m-2 w-full">
            <div
              v-for="tipo in tiposDiagnosticos"
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

                  <!-- Información adicional -->
                  <div class="mt-3 space-y-1">
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                      <span class="font-medium">Clasificación:</span>
                      <span>{{ tipo.clasificacion_texto || tipo.clasificacion }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                      <span class="font-medium">Especie:</span>
                      <span>{{ tipo.especie_texto || tipo.especie }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                      <span class="font-medium">Evolución:</span>
                      <span>{{ tipo.evolucion_texto || tipo.evolucion }}</span>
                    </div>
                  </div>
                </div>

                <!-- Estado y acciones -->
                <div class="mt-3 flex justify-between items-center">
                  <span
                    class="text-xs px-2 py-1 rounded-full font-medium"
                    :class="
                      tipo.activo
                        ? 'bg-green-100 text-green-700'
                        : 'bg-red-100 text-red-700'
                    "
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
          No hay tipos de diagnósticos registrados.
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

const router = useRouter()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const tiposDiagnosticos = ref([])
const cargando = ref(true)
const error = ref(null)

// Cargar tipos de diagnóstico al montar el componente
const cargarTiposDiagnosticos = async () => {
  try {
    cargando.value = true
    error.value = null

    const response = await fetch('/api/tipos-diagnostico', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()

    if (result.success) {
      tiposDiagnosticos.value = result.data
    } else {
      throw new Error(result.message || 'Error al cargar los datos')
    }

  } catch (err) {
    console.error('Error cargando tipos de diagnóstico:', err)
    error.value = err.message
    // Mostrar alerta de error
    alert('Error al cargar los tipos de diagnóstico: ' + err.message)
  } finally {
    cargando.value = false
  }
}

// Verificar autenticación y cargar datos
onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      console.error('Debe iniciar sesión para acceder a esta página')
      router.push('/')
      return
    }
  }
  
  await cargarTiposDiagnosticos()
})

const abrirRegistro = () => {
  router.push('/registro/registroTipoDiagnostico')
}

const editarTipo = (tipo) => {
  // Navegar a la vista de edición o abrir modal
  router.push(`/registro/registroTipoDiagnostico/${tipo.id}`)
}

const eliminarTipo = async (tipo) => {
  if (!confirm(`¿Está seguro que desea eliminar el tipo de diagnóstico "${tipo.nombre}"?`)) {
    return
  }

  try {
    const response = await fetch(`/api/tipos-diagnostico/${tipo.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()

    if (result.success) {
      // Remover el tipo de la lista local
      tiposDiagnosticos.value = tiposDiagnosticos.value.filter(t => t.id !== tipo.id)
      alert('Tipo de diagnóstico eliminado correctamente')
    } else {
      throw new Error(result.message || 'Error al eliminar')
    }

  } catch (err) {
    console.error('Error eliminando tipo de diagnóstico:', err)
    alert('Error al eliminar el tipo de diagnóstico: ' + err.message)
  }
}

// Función para recargar los datos si es necesario
const recargarDatos = () => {
  cargarTiposDiagnosticos()
}
</script>

<!--tipoVacunas.vue-->
<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
      Gestión de Tipos de Vacunas
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

        <!-- 📋 LISTA DE VACUNAS -->
        <div v-else-if="tiposVacunas.length" class="flex flex-wrap -m-2">
          <transition-group name="fade" tag="div" class="flex flex-wrap -m-2 w-full">
            <div
              v-for="tipo in tiposVacunas"
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
                      Vacuna contra:
                    </p>
                    <div class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                      <template
                        v-if="formatearEnfermedades(tipo.enfermedades).length > 0"
                      >
                        <ul class="list-disc list-inside space-y-1">
                          <li
                            v-for="(enfermedad, index) in formatearEnfermedades(tipo.enfermedades)"
                            :key="index"
                            class="text-xs"
                          >
                            {{ enfermedad }}
                          </li>
                        </ul>
                      </template>
                      <template v-else>
                        <p class="text-xs text-gray-500 italic">
                          No se especificaron enfermedades
                        </p>
                      </template>
                    </div>
                  </div>
                </div>

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
          No hay tipos de vacunas registrados.
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
const { accessToken, isVeterinario, isAprobado, fetchUser } = useAuth()

const tiposVacunas = ref([])
const loading = ref(true)
const error = ref(null)

// Función para formatear las enfermedades en una lista
const formatearEnfermedades = (enfermedades) => {
  if (!enfermedades) return []
  
  // Dividir por comas, puntos y comas, o saltos de línea
  return enfermedades
    .split(/[,;|\n]/)
    .map(e => e.trim())
    .filter(e => e.length > 0)
    .map(e => e.charAt(0).toUpperCase() + e.slice(1).toLowerCase())
}

// Cargar tipos de vacunas desde la API (solo activos)
const cargarTiposVacunas = async () => {
  if (!isVeterinario() || !isAprobado()) {
    error.value = 'No tienes permisos para acceder a esta sección. Debes ser un veterinario aprobado.'
    return
  }

  loading.value = true
  error.value = null
  
  try {
    const response = await fetch('/api/tipos-vacuna', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization': `Bearer ${accessToken.value}`
      },
      credentials: 'include'
    })

    if (!response.ok) {
      if (response.status === 401) {
        await fetchUser()
        return await cargarTiposVacunas()
      }
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    
    if (result.success) {
      // Filtrar solo los activos (aunque el backend ya debería hacerlo)
      tiposVacunas.value = result.data.filter(tipo => tipo.activo)
    } else {
      throw new Error(result.message || 'Error al cargar los tipos de vacuna')
    }
  } catch (err) {
    console.error('Error cargando tipos de vacuna:', err)
    error.value = err.message
  } finally {
    loading.value = false
  }
}

// Eliminar tipo de vacuna (baja lógica)
const eliminarTipo = async (tipo) => {
  if (!tipo.activo) {
    alert('Este tipo de vacuna ya está eliminado.')
    return
  }

  if (!confirm(`¿Estás seguro de que deseas eliminar el tipo de vacuna "${tipo.nombre}"?\n\nEsta acción no se puede deshacer.`)) {
    return
  }

  try {
    const response = await fetch(`/api/tipos-vacuna/${tipo.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization': `Bearer ${accessToken.value}`
      },
      credentials: 'include'
    })

    if (!response.ok) {
      if (response.status === 401) {
        await fetchUser()
        return await eliminarTipo(tipo)
      }
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()
    
    if (result.success) {
      // Remover el elemento de la lista localmente para mejor UX
      tiposVacunas.value = tiposVacunas.value.filter(t => t.id !== tipo.id)
      
      // También podrías recargar la lista completa para asegurar consistencia
      // await cargarTiposVacunas()
      
      alert('Tipo de vacuna eliminado correctamente')
    } else {
      throw new Error(result.message || 'Error al eliminar el tipo de vacuna')
    }
  } catch (err) {
    console.error('Error eliminando tipo de vacuna:', err)
    alert(`Error al eliminar el tipo de vacuna: ${err.message}`)
  }
}


const abrirRegistro = () => {
  router.push('/registro/registroTipoVacuna')
}

const editarTipo = (tipo) => {
  // Aquí puedes redirigir a una página de edición o abrir un modal
  router.push(`/registro/registroTipoVacuna/${tipo.id}`)
}

// Cargar los datos cuando el componente se monta
onMounted(async () => {
  // Asegurarse de que el usuario esté cargado antes de hacer la petición
  if (!isVeterinario() || !isAprobado()) {
    await fetchUser()
  }
  await cargarTiposVacunas()
})
</script>
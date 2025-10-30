<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
      Gestión de Tipos de Paliativos
    </h2>

    <div class="max-w-4xl mx-auto p-6">
      <div class="border p-4 rounded-lg shadow bg-white max-w-8xl mx-auto">
        <!-- 🌀 ANIMACIÓN DE CARGA -->
        <div v-if="cargando" class="flex flex-wrap -m-2 animate-pulse">
          <div v-for="n in 6" :key="n" class="w-1/3 p-2">
            <div
              class="p-6 border rounded-2xl bg-gray-100 shadow-sm h-48 flex flex-col justify-between"
            >
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

        <!-- 📋 LISTA DE PALIATIVOS -->
        <div v-else-if="tiposPaliativos.length" class="flex flex-wrap -m-2">
          <transition-group name="fade" tag="div" class="flex flex-wrap -m-2 w-full">
            <div
              v-for="tipo in tiposPaliativos"
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
                    <div class="flex items-center gap-1 text-xs text-gray-500">
                      <span class="font-medium">Especie:</span>
                      <span>{{ obtenerTextoEspecie(tipo.especie) }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-xs text-gray-500">
                      <span class="font-medium">Objetivo:</span>
                      <span>{{ obtenerTextoObjetivo(tipo.objetivo_terapeutico, tipo.objetivo_otro) }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-xs text-gray-500">
                      <span class="font-medium">Frecuencia:</span>
                      <span>{{ tipo.frecuencia_valor }} {{ obtenerTextoFrecuencia(tipo.frecuencia_unidad) }}</span>
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
                      title="Editar"
                    >
                      <Edit class="w-5 h-5" />
                    </button>
                    <button
                      @click="eliminarTipo(tipo)"
                      class="hover:text-red-600 transition"
                      title="Eliminar"
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
          No hay tipos de paliativos registrados.
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

const tiposPaliativos = ref([])
const cargando = ref(false)
const error = ref(null)

// Cargar procedimientos paliativos desde la API
const cargarProcedimientosPaliativos = async () => {
  try {
    cargando.value = true
    error.value = null

    const response = await fetch('/api/tipos-procedimiento-paliativo', {
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
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()

    if (result.success) {
      tiposPaliativos.value = result.data.data || result.data
    } else {
      throw new Error(result.message || 'Error al cargar los procedimientos')
    }

  } catch (err) {
    console.error('Error al cargar procedimientos paliativos:', err)
    error.value = err.message
  } finally {
    cargando.value = false
  }
}

// Función para obtener texto legible de la especie
const obtenerTextoEspecie = (especie) => {
  const especies = {
    'canino': 'Canino',
    'felino': 'Felino',
    'ave': 'Ave',
    'roedor': 'Roedor',
    'exotico': 'Exótico',
    'todos': 'Todas las especies'
  }
  return especies[especie] || especie
}

// Función para obtener texto legible del objetivo
const obtenerTextoObjetivo = (objetivo, objetivoOtro) => {
  const objetivos = {
    'alivio_dolor': 'Alivio del dolor',
    'mejora_movilidad': 'Mejora de movilidad',
    'soporte_respiratorio': 'Soporte respiratorio',
    'soporte_nutricional': 'Soporte nutricional',
    'acompañamiento': 'Acompañamiento final',
    'otro': objetivoOtro || 'Otro objetivo'
  }
  return objetivos[objetivo] || objetivo
}

// Función para obtener texto legible de la frecuencia
const obtenerTextoFrecuencia = (unidad) => {
  const unidades = {
    'horas': 'horas',
    'dias': 'días',
    'semanas': 'semanas',
    'meses': 'meses',
    'sesiones': 'sesiones'
  }
  return unidades[unidad] || unidad
}

const abrirRegistro = () => {
  router.push('/registro/registroTipoPaliativo')
}

const editarTipo = (tipo) => {
  // Redirigir a la página de edición o abrir modal
  router.push(`/registro/registroTipoPaliativo/${tipo.id}`)
}

const eliminarTipo = async (tipo) => {
  if (!confirm(`¿Está seguro que desea eliminar el procedimiento "${tipo.nombre}"?`)) {
    return
  }

  try {
    const response = await fetch(`/api/tipos-procedimiento-paliativo/${tipo.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization': `Bearer ${accessToken.value}`
      },
      credentials: 'include'
    })

    const result = await response.json()

    if (result.success) {
      // Eliminar del array local
      tiposPaliativos.value = tiposPaliativos.value.filter(t => t.id !== tipo.id)
      alert('Procedimiento eliminado exitosamente')
    } else {
      throw new Error(result.message || 'Error al eliminar el procedimiento')
    }

  } catch (error) {
    console.error('Error al eliminar:', error)
    alert('Error al eliminar el procedimiento: ' + error.message)
  }
}

// Cargar los datos cuando el componente se monta
onMounted(() => {

  cargarProcedimientosPaliativos()
})
</script>


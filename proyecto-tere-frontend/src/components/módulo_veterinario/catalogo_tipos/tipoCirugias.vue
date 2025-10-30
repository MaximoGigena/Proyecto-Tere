<template>
  <div>
    <!-- Título principal -->
    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
      Gestión de Tipos de Cirugías
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

        <!-- 📋 LISTA DE TIPOS DE CIRUGÍAS -->
        <div v-else-if="tiposCirugias.length" class="flex flex-wrap -m-2">
          <transition-group name="fade" tag="div" class="flex flex-wrap -m-2 w-full">
            <div
              v-for="tipo in tiposCirugias"
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
                  <p class="text-sm text-gray-600 mt-1 line-clamp-3">
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
                    <button @click="editarTipo(tipo)" class="hover:text-blue-600 transition">
                      <Edit class="w-5 h-5" />
                    </button>
                    <button @click="eliminarTipo(tipo)" class="hover:text-red-600 transition">
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
          No hay tipos de cirugías registrados.
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
import { reactive } from 'vue'
import axios from 'axios'

const router = useRouter()
const { accessToken, isAuthenticated } = useAuth()

const tiposCirugias = ref([])
const loading = ref(false)
const eliminandoId = ref(null) 

const mensaje = reactive({
  success: false,
  error: false,
  text: ''
})

// Función para mostrar mensajes
const mostrarMensaje = (texto, esExito = true) => {
  mensaje.success = esExito
  mensaje.error = !esExito
  mensaje.text = texto
  
  setTimeout(() => {
    mensaje.success = false
    mensaje.error = false
    mensaje.text = ''
  }, 5000)
}


// Cargar tipos de cirugía desde la API
const cargarTiposCirugia = async () => {
  try {
    loading.value = true
    
    const response = await axios.get('/api/tipos-cirugia', {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
        Accept: 'application/json',
      }
    })

    // Ajusta según la estructura de tu respuesta API
    if (response.data.success) {
      tiposCirugias.value = response.data.data
    } else {
      tiposCirugias.value = response.data
    }
  } catch (error) {
    console.error('Error al cargar tipos de cirugía:', error)
    
    // Manejo de errores específicos
    if (error.response?.status === 401) {
      console.error('No autorizado - token inválido o expirado')
    } else if (error.response?.status === 404) {
      console.error('Endpoint no encontrado')
    }
    
    tiposCirugias.value = []
  } finally {
    loading.value = false
  }
}

const abrirRegistro = () => {
  router.push('/registro/registroTipoCirugia')
}

const editarTipo = tipo => {
  router.push(`/registro/registroTipoCirugia/${tipo.id}`)
}

const eliminarTipo = async tipo => {
  if (!confirm(`¿Está seguro que desea eliminar el tipo de cirugía "${tipo.nombre}"?\n\nEsta acción no se puede deshacer.`)) {
    return
  }

  try {
    eliminandoId.value = tipo.id
    
    const response = await axios.delete(`/api/tipos-cirugia/${tipo.id}`, {
      headers: {
        Authorization: `Bearer ${accessToken.value}`,
        Accept: 'application/json',
      }
    })

    if (response.data.success) {
      mostrarMensaje(`Tipo de cirugía "${tipo.nombre}" eliminado exitosamente`)
      
      // Recargar la lista después de eliminar
      await cargarTiposCirugia()
    } else {
      mostrarMensaje(response.data.message || 'Error al eliminar el tipo de cirugía', false)
    }
    
  } catch (error) {
    console.error('Error al eliminar tipo de cirugía:', error)
    
    let errorMessage = 'Error al eliminar el tipo de cirugía'
    
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    } else if (error.response?.status === 403) {
      errorMessage = 'No tiene permisos para eliminar tipos de cirugía'
    } else if (error.response?.status === 404) {
      errorMessage = 'El tipo de cirugía no fue encontrado'
    }
    
    mostrarMensaje(errorMessage, false)
  } finally {
    eliminandoId.value = null
  }
}


// Cargar datos cuando el componente se monta
onMounted(() => {
  if (isAuthenticated.value) {
    cargarTiposCirugia()
  }
})
</script>

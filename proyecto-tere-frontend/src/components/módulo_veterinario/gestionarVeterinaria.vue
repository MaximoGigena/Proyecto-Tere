<template>
  <div class="p-6 max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Columna Derecha (ahora izquierda): Lista de centros -->
    <div>
      <h2 class="text-xl font-semibold mb-4">Centros Veterinarios</h2>
      
      <!-- Estado de carga -->
      <div v-if="loading" class="text-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
        <p class="mt-2 text-gray-600">Cargando centros...</p>
      </div>
      
      <!-- Lista de centros -->
      <div v-else class="space-y-4">
        <div 
          v-for="centro in listaCentros" 
          :key="centro.id" 
          @click="seleccionarCentro(centro)"
          class="border-2 border-dashed border-gray-400 rounded-xl p-4 cursor-pointer hover:bg-gray-50 relative"
          :class="{ 'border-blue-500 bg-blue-50': centroSeleccionado?.id === centro.id }"
        >
          <p class="font-bold">{{ centro.nombre }}</p>
          <p class="text-sm text-gray-600">Dirección: {{ centro.direccion }}</p>
          <p class="text-sm text-gray-600">Tel: {{ centro.telefono }}</p>
          <p class="text-sm text-gray-600" v-if="centro.horarios_atencion">Horarios: {{ centro.horarios_atencion }}</p>
          
          <!-- Estado del centro -->
          <span 
            class="absolute top-2 right-2 text-xs px-2 py-1 rounded-full"
            :class="getEstadoClasses(centro.estado || 'pendiente')"
          >
            {{ getEstadoTexto(centro.estado || 'pendiente') }}
          </span>

          <!-- Botones de acción -->
          <div class="absolute bottom-2 right-3 flex items-center gap-3">
            <button @click.stop="editarCentro(centro)" class="text-gray-700 hover:text-gray-900">
              <i class="fas fa-pen"></i>
            </button>
            <button @click.stop="eliminarCentro(centro.id)" class="text-red-500 hover:text-red-700">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        
        <!-- Mensaje cuando no hay centros -->
        <div v-if="listaCentros.length === 0" class="text-center py-8 text-gray-500">
          No hay centros veterinarios registrados
        </div>
      </div>

      <!-- Botón nuevo centro -->
      <div class="mt-6">
        <button 
          @click="registrarNuevoCentro" 
          class="bg-green-600 text-white px-4 py-2 rounded-xl hover:bg-green-700 w-full"
        >
          + Nuevo Centro
        </button>
      </div>
    </div>

    <!-- Contenido del centro seleccionado (derecha en desktop) -->
    <div class="border-l border-gray-300 pl-6">
      <!-- Aquí renderizamos contenidoCentro.vue dinámicamente -->
      <ContenidoCentro 
        v-if="centroSeleccionado" 
        :centro="centroSeleccionado" 
        @centro-actualizado="cargarCentros"
      />
      
      <!-- Mensaje cuando no hay centro seleccionado -->
      <div v-else class="text-gray-400 text-center mt-10">
        Seleccioná un centro para ver los detalles
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import ContenidoCentro from './contenidoCentro.vue'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { accessToken } = useAuth()

const listaCentros = ref([])
const centroSeleccionado = ref(null)
const loading = ref(false)
const errorMessage = ref('')

// Cargar centros al montar el componente
onMounted(() => {
  cargarCentros()
})

// Función para cargar centros desde la API
async function cargarCentros() {
  loading.value = true
  errorMessage.value = ''
  
  try {
    const response = await fetch('http://localhost:8000/api/centros-veterinarios', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
    })

    if (!response.ok) {
      throw new Error('Error al cargar los centros veterinarios')
    }

    const data = await response.json()
    
    if (data.success) {
      listaCentros.value = data.data
    } else {
      throw new Error(data.message || 'Error al obtener los centros')
    }
    
  } catch (error) {
    console.error('Error:', error)
    errorMessage.value = error.message
  } finally {
    loading.value = false
  }
}

function seleccionarCentro(centro) {
  centroSeleccionado.value = centro
}

function editarCentro(centro) {
  router.push(`/registro/registroCentroVeterinario/${centro.id}`) 
}

async function eliminarCentro(centroId) {
  if (!confirm('¿Estás seguro de que deseas eliminar este centro?')) {
    return
  }

  try {
    const response = await fetch(`http://localhost:8000/api/centros-veterinarios/${centroId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
    })

    const data = await response.json()
    
    if (!response.ok) {
      throw new Error(data.message || 'Error al eliminar el centro')
    }

    if (data.success) {
      // Eliminar el centro de la lista localmente en lugar de recargar todo
      const index = listaCentros.value.findIndex(centro => centro.id === centroId)
      if (index !== -1) {
        listaCentros.value.splice(index, 1)
      }
      
      // Deseleccionar si era el centro seleccionado
      if (centroSeleccionado.value?.id === centroId) {
        centroSeleccionado.value = null
      }
      
      alert('Centro eliminado exitosamente')
    }
    
  } catch (error) {
    console.error('Error:', error)
    alert(error.message || 'Error al eliminar el centro')
  }
}

function registrarNuevoCentro() {
  router.push('/registro/registroCentroVeterinario') 
}

// Funciones auxiliares para mostrar el estado
function getEstadoClasses(estado) {
  const classes = {
    'aprobado': 'bg-green-100 text-green-800',
    'pendiente': 'bg-yellow-100 text-yellow-800',
    'rechazado': 'bg-red-100 text-red-800'
  }
  return classes[estado] || 'bg-gray-100 text-gray-800'
}

function getEstadoTexto(estado) {
  const textos = {
    'aprobado': 'Aprobado',
    'pendiente': 'Pendiente',
    'rechazado': 'Rechazado'
  }
  return textos[estado] || estado
}
</script>




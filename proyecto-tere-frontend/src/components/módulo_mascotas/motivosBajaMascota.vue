<!-- components/módulo_mascotas/motivosBajaMascota.vue -->
<template>
  <!-- Fondo oscuro -->
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <!-- Caja del modal -->
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-6 relative">
      
      <!-- Header -->
      <div class="flex items-center mb-6">
        <button 
          @click="volver"
          class="text-gray-600 hover:text-gray-800 mr-3"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <h1 class="text-2xl font-bold text-gray-800">Dar de baja mascota</h1>
      </div>

      <!-- Info de la mascota -->
      <div class="bg-gray-100 rounded-lg p-4 mb-6">
        <div class="flex items-center space-x-4">
          <img 
            :src="mascota.imagen" 
            :alt="mascota.nombre"
            class="w-16 h-16 rounded-full object-cover"
          >
          <div>
            <h2 class="text-lg font-semibold">{{ mascota.nombre }}</h2>
            <p class="text-gray-600">{{ mascota.edad }}, {{ mascota.sexo }}</p>
          </div>
        </div>
      </div>

      <!-- Formulario -->
      <div class="space-y-4">
        <label class="block text-sm font-medium text-gray-700">
          Selecciona el motivo de baja *
        </label>
        <select
          v-model="motivoSeleccionado"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400"
          required
        >
          <option value="" disabled>-- Seleccionar motivo --</option>
          <option v-for="motivo in motivos" :key="motivo.id" :value="motivo.id">
            {{ motivo.descripcion }}
          </option>
        </select>

        <label class="block text-sm font-medium text-gray-700">
          Observaciones (opcional)
        </label>
        <textarea
          v-model="observacion"
          rows="3"
          placeholder="Agrega cualquier observación relevante..."
          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400"
        ></textarea>

        <!-- Botones -->
        <div class="flex space-x-3 pt-4">
          <button
            @click="volver"
            class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition"
          >
            Cancelar
          </button>
          <button
            @click="confirmarBaja"
            :disabled="!motivoSeleccionado || loading"
            class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            <span v-if="loading">Procesando...</span>
            <span v-else>Confirmar baja</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthToken } from '@/composables/useAuthToken'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const { accessToken, isAuthenticated } = useAuthToken() 
const loading = ref(false)

const mascota = ref({
  id: route.params.id,
  nombre: '',
  edad: '',
  sexo: '',
  imagen: ''
})

const motivos = ref([])
const motivoSeleccionado = ref('')
const observacion = ref('')

// Función para verificar autenticación
const verificarAutenticacion = () => {
  if (!isAuthenticated.value) {
    alert("Debes iniciar sesión.")
    router.push('/login')
    return false
  }
  return true
}

// Cargar datos de la mascota y motivos
onMounted(async () => {
  if (!verificarAutenticacion()) return
  
  await cargarDatosMascota()
  await cargarMotivosBaja()
})

const cargarDatosMascota = async () => {
  try {
    const response = await axios.get(`/api/mascotas/${route.params.id}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}` // Usar accessToken del composable
      }
    })

    if (response.data.success) {
      const m = response.data.mascota
      mascota.value = {
        id: m.id,
        nombre: m.nombre,
        edad: `${m.edad} ${m.unidad_edad}`,
        sexo: m.sexo === 'macho' ? 'Macho' : 'Hembra',
        imagen: m.fotos && m.fotos.length > 0 
          ? m.fotos[0].url 
          : 'https://cdn.pixabay.com/photo/2017/08/18/06/49/capybara-2653996_1280.jpg'
      }
    }
  } catch (error) {
    console.error('Error al cargar mascota:', error)
    
    // Manejar errores de autenticación
    if (error.response?.status === 401) {
      alert('Tu sesión ha expirado. Por favor inicia sesión nuevamente.')
      router.push('/login')
      return
    }
    
    alert('Error al cargar los datos de la mascota')
    volver()
  }
}
const cargarMotivosBaja = async () => {
  try {
    const response = await axios.get('/api/mascotas/motivos/baja', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}` // Usar accessToken del composable
      }
    })

    if (response.data.success) {
      motivos.value = response.data.motivos
    } else {
      // Fallback a motivos por defecto
      motivos.value = [
        { id: 1, descripcion: "Fallecimiento de la mascota" },
        { id: 2, descripcion: "Extraviada" },
        { id: 3, descripcion: "Adoptada por otra persona" },
        { id: 4, descripcion: "Traslado de domicilio" },
        { id: 5, descripcion: "Problemas de convivencia" },
      ]
    }
  } catch (error) {
    console.error('Error al cargar motivos:', error)
    
    // Manejar errores de autenticación
    if (error.response?.status === 401) {
      alert('Tu sesión ha expirado. Por favor inicia sesión nuevamente.')
      router.push('/login')
      return
    }
    
    // Fallback a motivos por defecto
    motivos.value = [
      { id: 1, descripcion: "Fallecimiento de la mascota" },
      { id: 2, descripcion: "Extraviada" },
      { id: 3, descripcion: "Adoptada por otra persona" },
      { id: 4, descripcion: "Traslado de domicilio" },
      { id: 5, descripcion: "Problemas de convivencia" },
    ]
  }
}

const confirmarBaja = async () => {
  // Verificar autenticación antes de proceder
  if (!verificarAutenticacion()) return
  
  if (!motivoSeleccionado.value) {
    alert('Por favor selecciona un motivo de baja')
    return
  }

  loading.value = true

  try {
    const response = await axios.post(`/api/mascotas/${route.params.id}/baja`, {
      motivo_baja_id: parseInt(motivoSeleccionado.value),
      observacion: observacion.value
    }, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}` // Usar accessToken del composable
      }
    })

    if (response.data.success) {
      alert('Mascota dada de baja correctamente')
      // Volver a la lista de mascotas
      router.push(route.query.from || '/explorar/perfil/mascotas')
    } else {
      alert(response.data.message || 'Error al dar de baja la mascota')
    }
  } catch (error) {
    console.error('Error al dar de baja:', error)
    
    // Manejar errores de autenticación
    if (error.response?.status === 401) {
      alert('Tu sesión ha expirado. Por favor inicia sesión nuevamente.')
      router.push('/login')
      return
    }
    
    if (error.response?.status === 409) {
      alert('Esta mascota ya está dada de baja')
      router.push(route.query.from || '/explorar/perfil/mascotas')
    } else if (error.response?.data?.message) {
      alert('Error: ' + error.response.data.message)
    } else {
      alert('Error al dar de baja la mascota. Por favor intenta nuevamente.')
    }
  } finally {
    loading.value = false
  }
}

const volver = () => {
  router.push(route.query.from || '/explorar/perfil/mascotas')
}
</script>
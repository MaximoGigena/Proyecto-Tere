<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <!-- Contenedor principal -->
    <div class="relative flex">
      
      <!-- Contenedor blanco del contenido -->
      <div class="relative bg-white rounded-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <!-- Encabezado -->
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-2xl font-bold text-gray-800">{{ procedimiento.nombre }}</h3>
            <p class="text-gray-600">{{ procedimiento.fecha }}</p>
          </div>
          <button 
            @click="cerrar"
            class="text-gray-500 hover:text-gray-700 text-5xl -mt-6"
          >
            &times;
          </button>
        </div>

        <!-- Vista actual -->
        <router-view :procedimiento="procedimiento" :procedimientoId="procedimiento.id" />
      </div>

      <!-- Nav exterior a la derecha del contenedor blanco -->
      <nav class="ml-1 self-start sticky top-6 flex flex-col gap-3 bg-white shadow-md rounded-lg p-2 border border-gray-200 z-30">
        
        <!-- Detalles -->
        <router-link
          :to="{ name: 'procedimiento-detalles', params: { id: $route.params.id }, query: { from: $route.query.from } }"
          class="group relative p-2 rounded-md transition"
          :class="$route.name === 'procedimiento-detalles' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:text-blue-600'"
        >
          <i class="fas fa-file-alt text-2xl"></i>
          <span
            class="absolute left-full ml-3 top-1/2 transform -translate-y-1/2 text-sm bg-gray-800 text-white px-2 py-1 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50"
          >
            Detalles del procedimiento
          </span>
        </router-link>

        <!-- Observaciones -->
        <router-link
          :to="{ name: 'procedimiento-observaciones', params: { id: $route.params.id }, query: { from: $route.query.from } }"
          class="group relative p-2 rounded-md transition"
          :class="$route.name === 'procedimiento-observaciones' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:text-blue-600'"
        >
          <i class="fas fa-notes-medical text-2xl"></i>
          <span
            class="absolute left-full ml-3 top-1/2 transform -translate-y-1/2 text-sm bg-gray-800 text-white px-2 py-1 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50"
          >
            Observaciones del procedimiento
          </span>
        </router-link>

        <!-- Derivaciones -->
        <router-link
          :to="{ name: 'procedimiento-derivaciones', params: { id: $route.params.id }, query: { from: $route.query.from } }"
          class="group relative p-2 rounded-md transition"
          :class="$route.name === 'procedimiento-derivaciones' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:text-blue-600'"
        >
          <i class="fas fa-share-square text-2xl"></i>
          <span
            class="absolute left-full ml-3 top-1/2 transform -translate-y-1/2 text-sm bg-gray-800 text-white px-2 py-1 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50"
          >
            Derivaciones del procedimiento
          </span>
        </router-link>

      </nav>
    </div>
  </div>
</template>



<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import html2canvas from 'html2canvas'

const vistaAnteriorURL = ref('')

onMounted(async () => {
  const parentView = document.querySelector('.contenedor-vista-padre')
  if (parentView) {
    try {
      const canvas = await html2canvas(parentView, {
        scale: 0.5,
        useCORS: true,
        allowTaint: true,
        logging: false,
        backgroundColor: null
      })
      vistaAnteriorURL.value = canvas.toDataURL('image/png')
    } catch (error) {
      console.error('Error al capturar vista:', error)
    }
  }
})

const route = useRoute()
const router = useRouter()

const procedimiento = ref({
  id: route.params.id,
  nombre: '',
  fecha: '',
})

watch(
  () => route.query.from,
  (newFrom) => {
    if (!newFrom && route.name !== 'procedimiento-detalles') {
      router.replace({
        name: route.name,
        params: route.params,
        query: { ...route.query, from: route.meta.originalFrom }
      })
    }
  },
  { immediate: true }
)

onMounted(async () => {
  if (route.query.from && !route.meta.originalFrom) {
    route.meta.originalFrom = route.query.from
  }

  procedimiento.value = {
    id: route.params.id,
    nombre: 'Esterilización',
    fecha: '2025-06-12',
    veterinario: 'Dr. Juan Pérez',
    paciente: 'Max (Canino - Labrador)',
    anestesia: 'Isoflurano',
    duracion: '2 horas',
    estado: 'Completado',
    observaciones: 'El paciente respondió bien a la anestesia...'
  }
})

const cerrar = () => {
  const returnRoute = route.query.from || route.meta.originalFrom || '/historialClinico/cirugias'
  const decodedRoute = returnRoute.startsWith('/') ? returnRoute : `/${returnRoute}`

  if (isValidRoute(decodedRoute)) {
    router.push(decodedRoute)
  } else {
    router.push('/historialClinico/cirugias')
  }
}

const isValidRoute = (path) => {
  return path && !path.includes('undefined') && !path.includes('null')
}
</script>

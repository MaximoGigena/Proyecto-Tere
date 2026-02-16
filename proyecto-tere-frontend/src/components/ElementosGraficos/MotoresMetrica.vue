<template>
  <div class="mt-8">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-semibold text-gray-800">Motores de Métricas Especializadas</h2>
      <span class="text-sm text-gray-500">{{ currentEngineIndex + 1 }} / {{ motores.length }}</span>
    </div>
    
    <div class="bg-white shadow-lg rounded-xl p-6">
      <!-- Carrusel de botones -->
      <div class="relative">
        <!-- Botón anterior -->
        <button 
          @click="prevEngine" 
          class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-4 z-10 bg-white rounded-full p-2 shadow-md hover:shadow-lg transition-shadow"
          :disabled="currentEngineIndex === 0"
          :class="currentEngineIndex === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
        >
          <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        
        <!-- Botón siguiente -->
        <button 
          @click="nextEngine" 
          class="absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-4 z-10 bg-white rounded-full p-2 shadow-md hover:shadow-lg transition-shadow"
          :disabled="currentEngineIndex === motores.length - 1"
          :class="currentEngineIndex === motores.length - 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
        >
          <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
        
        <!-- Contenedor del carrusel -->
        <div class="overflow-hidden px-8">
          <div 
            class="flex transition-transform duration-300 ease-in-out" 
            :style="{ transform: `translateX(-${currentEngineIndex * 100}%)` }"
          >
            <div 
              v-for="(motor, index) in motores" 
              :key="motor.id"
              class="w-full flex-shrink-0 px-2"
            >
              <div class="text-center">
                <!-- Icono del motor -->
                <div class="mb-4">
                  <div class="inline-flex items-center justify-center w-16 h-16 rounded-full" 
                       :class="motor.color">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path v-if="motor.icon === 'users'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 1.197a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                      <path v-else-if="motor.icon === 'paw'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                      <path v-else-if="motor.icon === 'medical'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                      <path v-else-if="motor.icon === 'heart-handshake'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z M14 15l-3 3m0 0l-3-3m3 3V6" />
                      <path v-else-if="motor.icon === 'clipboard-check'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                      <path v-else-if="motor.icon === 'activity'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                      <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                  </div>
                </div>
                
                <!-- Nombre y descripción -->
                <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ motor.nombre }}</h3>
                <p class="text-gray-600 mb-4">{{ motor.descripcion }}</p>
                
                <!-- Botón de acceso -->
                <button 
                  @click="$emit('navigate', motor.ruta)" 
                  class="px-6 py-3 rounded-lg font-medium transition-all duration-300 transform hover:scale-105"
                  :class="motor.btnColor"
                >
                  <span class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Acceder al Motor
                  </span>
                </button>
                
                <!-- Indicadores de características -->
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                  <span v-for="tag in motor.tags" :key="tag" 
                        class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700">
                    {{ tag }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Indicadores de navegación -->
      <div class="flex justify-center mt-6 space-x-2">
        <button 
          v-for="(motor, index) in motores" 
          :key="motor.id"
          @click="goToEngine(index)"
          class="w-3 h-3 rounded-full transition-all duration-300"
          :class="index === currentEngineIndex ? 'bg-blue-500 w-6' : 'bg-gray-300 hover:bg-gray-400'"
          :aria-label="`Ir a ${motor.nombre}`"
        ></button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineEmits(['navigate'])

// Índice actual del carrusel
const currentEngineIndex = ref(0)

// Lista de motores de métricas especializadas - RUTAS ACTUALIZADAS
const motores = ref([
  {
    id: 1,
    nombre: 'Métricas de Usuarios',
    descripcion: 'Análisis detallado de comportamiento, crecimiento y segmentación de usuarios',
    detalles: 'Este motor proporciona análisis completo de la base de usuarios: tasas de registro, actividad, retención, y segmentación por comportamiento.',
    icon: 'users',
    color: 'bg-gradient-to-r from-blue-500 to-cyan-500',
    btnColor: 'bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white shadow-md',
    ruta: '/metricas/usuarios', // ← ESTA RUTA ES CORRECTA
    tags: ['Registro', 'Actividad', 'Segmentación', 'Retención']
  },
  {
    id: 2,
    nombre: 'Métricas de Mascotas',
    descripcion: 'Estadísticas detalladas sobre mascotas registradas y su estado',
    detalles: 'Análisis completo del registro de mascotas: distribución por tipos, estado de adopción, edades, y seguimiento de cambios de estado.',
    icon: 'paw',
    color: 'bg-gradient-to-r from-green-500 to-emerald-500',
    btnColor: 'bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white shadow-md',
    ruta: '/metricas/mascotas', // ← ESTA RUTA ES CORRECTA
    tags: ['Registro', 'Adopción', 'Tipos', 'Estados']
  },
  {
    id: 3,
    nombre: 'Métricas de Veterinarios',
    descripcion: 'Análisis de profesionales veterinarios y su actividad en el sistema',
    detalles: 'Seguimiento de veterinarios registrados, aprobaciones pendientes, actividad clínica, y métricas de desempeño profesional.',
    icon: 'medical',
    color: 'bg-gradient-to-r from-red-500 to-pink-500',
    btnColor: 'bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white shadow-md',
    ruta: '/metricas/veterinarios', // ← ESTA RUTA ES CORRECTA
    tags: ['Registro', 'Aprobación', 'Actividad', 'Desempeño']
  },
  {
    id: 4,
    nombre: 'Métricas de Adopciones',
    descripcion: 'Seguimiento y análisis completo del proceso de adopción',
    detalles: 'Métricas detalladas de todo el proceso de adopción: tasas de éxito, tiempo promedio, abandonos, y seguimiento post-adopción.',
    icon: 'heart-handshake',
    color: 'bg-gradient-to-r from-purple-500 to-indigo-500',
    btnColor: 'bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white shadow-md',
    ruta: '/metricas/adopciones', // ← ESTA RUTA ES CORRECTA
    tags: ['Proceso', 'Tasas', 'Tiempos', 'Seguimiento']
  },
  {
    id: 5,
    nombre: 'Métricas de Seguimiento médico',
    descripcion: 'Análisis de historiales médicos y tratamientos veterinarios',
    detalles: 'Seguimiento completo de historiales médicos, tratamientos, vacunas, y estadísticas de salud de las mascotas registradas.',
    icon: 'clipboard-check',
    color: 'bg-gradient-to-r from-teal-500 to-blue-500',
    btnColor: 'bg-gradient-to-r from-teal-500 to-blue-500 hover:from-teal-600 hover:to-blue-600 text-white shadow-md',
    ruta: '/metricas/seguimiento-medico', // ← ESTA RUTA ES CORRECTA
    tags: ['Historial', 'Tratamientos', 'Vacunas', 'Salud']
  },
  {
    id: 6,
    nombre: 'Métricas de Actividad del sistema',
    descripcion: 'Monitoreo en tiempo real de la actividad y uso del sistema',
    detalles: 'Análisis de logs, métricas de rendimiento, uso de API, y seguimiento de la actividad general del sistema en tiempo real.',
    icon: 'activity',
    color: 'bg-gradient-to-r from-orange-500 to-amber-500',
    btnColor: 'bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white shadow-md',
    ruta: '/metricas/actividad-sistema', // ← ESTA RUTA ES CORRECTA
    tags: ['Tiempo Real', 'Logs', 'Rendimiento', 'Monitoreo']
  }
])

// Navegación del carrusel (funciones se mantienen igual)
const nextEngine = () => {
  if (currentEngineIndex.value < motores.value.length - 1) {
    currentEngineIndex.value++
  }
}

const prevEngine = () => {
  if (currentEngineIndex.value > 0) {
    currentEngineIndex.value--
  }
}

const goToEngine = (index) => {
  currentEngineIndex.value = index
}
</script>
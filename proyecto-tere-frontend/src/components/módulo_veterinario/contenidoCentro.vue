<template>    
 <div class="bg-white backdrop-blur-md border border-gray-200 rounded-2xl 
           overflow-y-auto max-h-[83vh] w-full shadow-2xl 
           transition-all duration-300 relative p-2"
  >
  <div
    ref="scrollContainer"
    class="flex-1 overflow-y-auto overflow-x-overlay [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden px-2"
  >
  
    <!-- Imagen principal del centro veterinario -->
    <div class="relative w-full min-h-[75vh] rounded-4xl overflow-hidden">
      <img :src="centro.img" :alt="centro.nombre" class="w-full h-130 object-cover rounded-4xl" />
    
      <!-- Info del centro -->
      <div class="absolute top-5 left-4 bg-white px-3 py-1 rounded-md shadow text-sm font-semibold w-fit">
        Nombre: {{ centro.nombre }}
      </div>

      <div class="absolute top-13 left-4 bg-blue-500 text-white text-xs px-2 py-1 rounded-md w-fit">
        Años de experiencia: {{ centro.experiencia || 'No especificada' }}
      </div>
        
      <button  
            v-if="$route.query.from === 'centros-list'"
            @click="router.back()"
            class="absolute right-3 top-3 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
            <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
        </button>

      <button
        @click="mostrarReporte = true"
        class="absolute top-15 right-3 z-30 text-red-700 bg-white bg-opacity-80 rounded-full px-3 pt-0 py-1 text-4xl hover:bg-red-100 hover:text-red-800 hover:shadow-lg hover:scale-110 transition w-10 h-10 font-bold"
      >
        !
      </button>

      <ReportarCentro v-if="mostrarReporte" @close="mostrarReporte = false" />
    </div>

    <!-- Descripción -->
    <div class="px-4 pt-4 pb-6 bg-white space-y-4">
      <div class="space-y-2">
        <h2 class="text-4xl font-bold text-gray-800">Sobre el Centro</h2>
        <p class="text-lg font-semibold text-gray-800">
          {{ centro.descripcion || 'Este centro veterinario no ha agregado una descripción todavía.' }}
        </p>
      </div>
    </div>

    <!-- Etiquetas del centro -->
    <div class="flex flex-wrap gap-3">
      <!-- Servicios -->
      <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Servicios">
        <div class="mr-2">
          <font-awesome-icon :icon="['fas', 'briefcase-medical']" class="text-green-500 text-sm"/>
        </div>
        <span class="text-gray-700">{{ centro.servicios.join(', ') || 'No especificado' }}</span>
      </div>
      
      <!-- Especialidades -->
      <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Especialidades">
        <font-awesome-icon :icon="['fas', 'stethoscope']" class="text-gray-500 mr-2" />
        <span class="text-gray-700">{{ centro.especialidades.join(', ') || 'No especificadas' }}</span>
      </div>
      
      <!-- Ubicación -->
      <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Ubicación">
        <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="text-gray-500 mr-2"/>
        <span class="text-gray-700">{{ centro.ubicacion || 'No especificada' }}</span>
      </div>
    </div>

    <!-- Galería de fotos -->
    <div class="relative w-full min-h-[80vh] rounded-4xl overflow-hidden mt-4" v-if="centro.fotos && centro.fotos.length">
      <img :src="centro.fotos[0]" :alt="'Foto de ' + centro.nombre" class="w-full h-130 object-cover rounded-4xl" />
    </div>
    
    <!-- Ubicación detallada -->
    <div class="px-4 pt-4 pb-6 bg-white space-y-4">
      <div class="space-y-2">
        <h2 class="text-4xl font-bold text-gray-800">Ubicación</h2>
        <p class="text-lg font-semibold text-gray-800">
          {{ centro.ubicacion || 'Ubicación no especificada' }}
        </p>
      </div>
    </div>
    
    <div class="h-1"></div>
  </div>
 </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
//import ReportarCentro from '@/components/módulo_veterinario/reportarCentro.vue'

const route = useRoute()
const router = useRouter()
const scrollContainer = ref(null)
const mostrarReporte = ref(false)

// Datos del centro veterinario (simulados, en una app real vendrían de API)
const centro = computed(() => {
  return {
    id: route.params.centroId,
    nombre: `Centro Veterinario ${route.params.centroId}`,
    img: 'https://img1.wsimg.com/isteam/ip/44ae0605-d16c-4b00-a458-3d33c6ca1cee/IMG_1356.jpeg/:/rs=w:1160,h:870',
    descripcion: 'Centro veterinario especializado en atención integral de mascotas. Contamos con profesionales altamente capacitados y tecnología de punta.',
    experiencia: '15 años',
    servicios: ['Consultas', 'Vacunación', 'Cirugías'],
    especialidades: ['Medicina general', 'Cirugía', 'Dermatología'],
    ubicacion: 'Buenos Aires, Argentina',
    fotos: [
      'https://static.royacdn.com/Site-03cac2dc-09a0-4c36-a90a-ce124cf33a6f/Assets/office_2.jpg',
      'https://static.royacdn.com/Site-03cac2dc-09a0-4c36-a90a-ce124cf33a6f/Assets/office_3.png'
    ]
  }
})

// Manejo del scroll
onMounted(() => {
  document.body.style.overflow = 'hidden'
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<style>
/* Scroll igual que en versión original */
body {
  overflow-y: scroll;
  scrollbar-width: thin;
  scrollbar-color: transparent transparent;
}

::-webkit-scrollbar {
  display: none;
}

body:hover::-webkit-scrollbar {
  display: block;
}

::-webkit-scrollbar-thumb {
  background-color: transparent;
}

::-webkit-scrollbar-track {
  background: transparent;
}

.scroll-container::-webkit-scrollbar {
  width: 0px;
  background: transparent;
}
</style>

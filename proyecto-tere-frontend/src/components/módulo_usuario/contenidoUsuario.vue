<template>    
 <div class="bg-white backdrop-blur-md border border-gray-200 rounded-2xl 
           overflow-y-auto max-h-[83vh] w-full shadow-2xl 
           transition-all duration-300 relative p-2"
  >
  <div
    ref="scrollContainer"
    class="flex-1 overflow-y-auto overflow-x-overlay [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden px-2"
  >
  
    <!-- Imagen principal del perfil -->
    <div class="relative w-full min-h-[75vh] rounded-4xl overflow-hidden">
      <img :src="perfil.img" :alt="perfil.nombre" class="w-full h-130 object-cover rounded-4xl" />
    
      <!-- Info del usuario -->
      <div class="absolute top-5 left-4 bg-white px-3 py-1 rounded-md shadow text-sm font-semibold w-fit">
        Nombre: {{ perfil.nombre }}
      </div>

      <div class="absolute top-13 left-4 bg-blue-500 text-white text-xs px-2 py-1 rounded-md w-fit">
        Edad: {{ perfil.edad || 'No especificada' }}
      </div>
        
      <button  
            v-if="$route.query.from === 'chats-list'"
            @click="router.back()"
            class="absolute right-3 top-3 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
            <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
            
        </button>

        <button  
            v-if="$route.query.from === 'chat-room'"
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


      <ReportarUsuario v-if="mostrarReporte" @close="mostrarReporte = false" />
    </div>

    <!-- Descripción -->
    <div class="px-4 pt-4 pb-6 bg-white space-y-4">
      <div class="space-y-2">
        <h2 class="text-4xl font-bold text-gray-800">Sobre mí</h2>
        <p class="text-lg font-semibold text-gray-800">
          {{ perfil.descripcion || 'Este usuario no ha agregado una descripción todavía.' }}
        </p>
      </div>
    </div>

    <!-- Etiquetas del usuario -->
    <div class="flex flex-wrap gap-3">
      <!-- Experiencia -->
      <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Experiencia">
        <div class="mr-2">
          <font-awesome-icon :icon="['fas', 'star']" class="text-yellow-500 text-sm"/>
        </div>
        <span class="text-gray-700">{{ perfil.experiencia || 'Principiante' }}</span>
      </div>
      
      <!-- Tipo de cuidador -->
      <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Tipo de cuidador">
        <font-awesome-icon :icon="['fas', 'home']" class="text-gray-500 mr-2" />
        <span class="text-gray-700">{{ perfil.tipoCuidador || 'No especificado' }}</span>
      </div>
      
      <!-- Mascotas -->
      <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Mascotas">
        <font-awesome-icon :icon="['fas', 'paw']" class="text-gray-500 mr-2"/>
        <span class="text-gray-700">{{ perfil.mascotas || '0' }} mascotas</span>
      </div>
      
      <!-- Ubicación -->
      <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Ubicación">
        <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="text-gray-500 mr-2"/>
        <span class="text-gray-700">{{ perfil.ubicacion || 'No especificada' }}</span>
      </div>
    </div>

    <!-- Galería de fotos -->
    <div class="relative w-full min-h-[80vh] rounded-4xl overflow-hidden mt-4" v-if="perfil.fotos && perfil.fotos.length">
      <img :src="perfil.fotos[0]" :alt="'Foto de ' + perfil.nombre" class="w-full h-130 object-cover rounded-4xl" />
    </div>
    
    <!-- Ubicación -->
    <div class="px-4 pt-4 pb-6 bg-white space-y-4">
      <div class="space-y-2">
        <h2 class="text-4xl font-bold text-gray-800">Ubicación</h2>
        <p class="text-lg font-semibold text-gray-800">
          {{ perfil.ubicacion || 'Ubicación no especificada' }}
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
import { RouterLink } from 'vue-router'
import ReportarUsuario from '@/components/módulo_usuario/reportarUsuario.vue'

const route = useRoute()
const router = useRouter()
const scrollContainer = ref(null)
const mostrarReporte = ref(false)

// Datos del perfil (en una app real vendrían de una API)
const perfil = computed(() => {
  return {
    id: route.params.userId,
    nombre: `Usuario ${route.params.userId}`,
    img: 'https://cdn.pixabay.com/photo/2020/07/16/07/36/man-5410019_960_720.jpg',
    edad: '30 años',
    descripcion: 'Amante de los animales con experiencia en cuidado de mascotas. Comprometido con el bienestar animal y la adopción responsable.',
    experiencia: 'Experto',
    tipoCuidador: 'Hogar temporal',
    mascotas: '3',
    ubicacion: 'Buenos Aires, Argentina',
    fotos: [
      'https://cdn.pixabay.com/photo/2020/12/29/22/57/donkey-5871800_960_720.jpg',
      'https://cdn.pixabay.com/photo/2024/09/09/17/22/donkey-9035452_1280.jpg'
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
/* Estilos de scroll (igual que en tu versión original) */
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
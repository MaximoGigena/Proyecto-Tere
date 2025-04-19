<template>
  <div class="w-full h-full flex flex-col relative">
    <!-- Header sticky dentro del scroll -->
    <div class="sticky top-0 z-30 bg-white px-4 py-1 flex items-center justify-between">
      <div class="text-2xl font-bold text-gray-800 pointer-events-none">
        Explorando mascotas
      </div>
      <button class="text-gray-700 hover:text-black transition mr-8">
        <i class="fa-solid fa-filter text-2xl"></i>
      </button>
    </div>

    <!-- Contenido scrollable -->
    <div
      ref="scrollContainer"
      class="flex-1 overflow-y-auto custom-scrollbar px-2"
    >
      <!-- Imagen principal -->
      <div
        class="relative w-full min-h-[81vh] bg-cover bg-center bg-no-repeat rounded-4xl"
        :style="{ backgroundImage: `url(${burro})` }"
      >
        <!-- Info encima -->
        <div class="absolute top-5 left-4 bg-white px-3 py-1 rounded-md shadow text-sm font-semibold w-fit">
          Nombre, <span class="font-normal">sexo</span>
        </div>

        <div class="absolute top-16 left-4 bg-blue-500 text-white text-xs px-2 py-1 rounded-md w-fit">
          Algún interés de la mascota
        </div>

        <button
          @click="mostrar = true"
          class="absolute top-4 right-4 z-30 text-black bg-white bg-opacity-80 rounded-full px-3 py-1 text-lg shadow hover:bg-opacity-100 transition"
        >
          ...
        </button>

        <PasoAlgo v-if="mostrar" @close="mostrar = false" />
      </div>

      <!-- Descripción -->
      <div class="px-4 pt-4 pb-6 bg-white space-y-4">
        <div class="space-y-2">
          <p v-for="i in 30" :key="i" class="text-gray-700">
            Intereses o descripción larga aquí...
          </p>
        </div>
      </div>
    </div>

    <!-- Botones flotantes fijos abajo -->
    <div class="sticky bottom-24 flex justify-center gap-14 z-20">
      <button class="bg-white border border-black w-16 h-16 rounded-full shadow-lg flex items-center justify-center transition duration-300">
        <i class="fa-solid fa-xmark text-black text-3xl  hover:text-red-400"></i>
      </button>
      <button class="bg-white border border-black w-16 h-16 rounded-full shadow-lg flex items-center justify-center transition duration-300">
        <i class="fa-solid fa-heart text-black text-3xl hover:text-green-400 "></i>
      </button>
    </div>
  </div>
</template>


  
  <script setup>
  import { ref, onMounted, onUnmounted } from 'vue'
  import burro from '@/assets/burro.png'
  import PasoAlgo from '../components/reportarMascota.vue'
  
  const scrollContainer = ref(null)
  const mostrar = ref(false)
  
  onMounted(() => {
    document.body.style.overflow = 'hidden'
  })
  onUnmounted(() => {
    document.body.style.overflow = 'auto'
  })
  console.log('🚀 perfilMascota.vue se está montando')

  onMounted(() => {
    console.log('🔧 perfilMascota.vue onMounted ejecutado')
  })

  </script>
  
<style>
  .custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.15) transparent;
}
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(0, 0, 0, 0.15);
  border-radius: 10px;
}
  </style>
  
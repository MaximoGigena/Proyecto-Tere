<template>
  <div class="flex flex-col h-screen bg-white relative">
    <!-- Header fijo -->
    <div class="sticky top-0 z-30 bg-white px-4 py-1 flex items-center justify-between shadow-sm">
      <h1 class="text-2xl font-bold text-gray-800">Mascotas cerca de ti</h1>
       <button
        @click="mostrarOverlay = !mostrarOverlay"
        class="inline-flex whitespace-nowrap items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-700 hover:text-white transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300"
      >
        <span class="font-medium text-sm sm:text-base">Filtrar Mascotas</span>
        <font-awesome-icon :icon="['fas', 'filter']" class="text-lg sm:text-xl" />
      </button>
    </div>

    <!-- Contenido scrollable -->
    <div ref="scrollContainer" class="flex-1 overflow-y-auto px-4 pt-4">
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pb-28">
        <div
          v-for="(mascota, index) in mascotas"
          :key="index"
          class="text-center"
        >
          <router-link :to="{
            path: `/explorar/cerca/${index}`,
            query: { from: 'cerca' }
          }" class="block">
            <img
              :src="mascota.img"
              alt="Mascota"
              class="w-full h-[220px] object-cover rounded-lg shadow"
            />
            <p class="text-sm text-gray-800 mt-1">{{ mascota.info }}</p>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Overlay de filtros -->
    <transition name="fade">
      <div 
        v-if="mostrarOverlay" 
        class="fixed inset-0 z-40 bg-black/50  flex items-center justify-center p-4"
        @click.self="mostrarOverlay = false"
      >
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
          <div class="p-4 max-w-xl mx-auto">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold text-gray-800">Filtrar Mascotas</h2>
              <button @click="mostrarOverlay = false" class="text-gray-500 hover:text-gray-700">
                <font-awesome-icon :icon="['fas', 'times']" class="text-3xl" />
              </button>
            </div>
            
            <!-- Aquí va el contenido de los filtros -->
            <FiltrosComponente @cerrar="mostrarOverlay = false" />
          </div>
        </div>
      </div>
    </transition>
    
  </div>
</template>

<script setup>
import { ref } from 'vue'
import FiltrosComponente from './filtrosAdopciones.vue' // Asegúrate de que la ruta sea correcta

const mostrarOverlay = ref(false)

const mascotas = [
  { img: 'https://cdn.pixabay.com/photo/2020/06/11/20/06/dog-5288071_1280.jpg', info: '2 años / Hembra' },
  { img: 'https://cdn.pixabay.com/photo/2021/08/10/18/32/cat-6536684_1280.jpg', info: '1 año / Macho' },
  { img: 'https://cdn.pixabay.com/photo/2024/02/26/19/39/monochrome-image-8598798_960_720.jpg', info: '3 años / Hembra' },
  { img: 'https://cdn.pixabay.com/photo/2020/09/17/13/59/cat-5579221_1280.jpg', info: '5 meses / Macho' },
  { img: 'https://cdn.pixabay.com/photo/2022/04/21/22/50/animal-7148487_1280.jpg', info: '4 años / Hembra' },
  { img: 'https://cdn.pixabay.com/photo/2020/06/30/22/34/dog-5357794_960_720.jpg', info: '6 años / Macho' },
  { img: 'https://cdn.pixabay.com/photo/2018/03/31/06/31/dog-3277414_960_720.jpg', info: '1 año / Hembra' },
  { img: 'https://cdn.pixabay.com/photo/2020/02/05/06/24/cat-4820202_1280.jpg', info: '7 meses / Macho' },
  { img: 'https://cdn.pixabay.com/photo/2024/01/05/10/53/rabbit-8489271_960_720.png', info: '2 años / Hembra' },
]
</script>

<style scoped>

</style>


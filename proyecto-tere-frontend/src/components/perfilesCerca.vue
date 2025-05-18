<template>
  <div class="flex flex-col h-screen bg-white relative">
    <!-- Header fijo -->
    <div class="sticky top-0 z-30 bg-white px-4 py-1 flex items-center justify-between shadow-sm">
      <h1 class="text-2xl font-bold text-gray-800">Mascotas cerca de ti</h1>
      <button class="text-gray-700 hover:text-black transition">
        <font-awesome-icon :icon="['fas', 'filter']" class="text-2xl" />
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

    
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'


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


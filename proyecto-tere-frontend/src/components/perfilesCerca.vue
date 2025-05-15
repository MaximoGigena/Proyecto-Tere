<template>
  <div class="flex flex-col h-screen bg-white-100 relative">
    <div class="sticky top-0 z-30 bg-white px-4 py-1 flex items-center justify-between">
      <div class="text-2xl font-bold text-gray-800 pointer-events-none">
        Mascotas cerca de ti
      </div>
      <button class="text-gray-700 hover:text-black transition mr-8">
        <font-awesome-icon :icon="['fas', 'filter']" class="text-2xl" />
      </button>
    </div>
    <div ref="scrollContainer" class="relative w-full mt-12 flex-1 overflow-y-auto">
      <div class="bg-white-100 flex items-center justify-center min-h-screen">
        <div class="p-3 border-2 border-transparent">
          <div class="grid grid-cols-3 gap-5">
            <div v-for="(mascota, index) in mascotas" :key="index" class="text-center flex-1 overflow-y-auto w-full">
              <router-link :to="{ path: '/mascota/' + index, query: { from: 'cerca' } }">
                <img :src="mascota.img" alt="Mascota" class="w-[180px] h-[220px] object-cover rounded-lg" />
                <p class="text-sm text-gray-800 mt-1">{{ mascota.info }}</p>
              </router-link>
            </div>
            <div class="h-28"></div>
          </div>
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
  
<!-- perfil mascota-->
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
      class="flex-1 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden px-2"
    >
      <!-- Imagen principal -->
      <div class="relative w-full min-h-[80vh] rounded-4xl overflow-hidden">
        <img :src="burro" alt="Burro" class="w-full h-130 object-cover rounded-4xl" />
      

        <!-- Info encima -->
        <div class="absolute top-5 left-4 bg-white px-3 py-1 rounded-md shadow text-sm font-semibold w-fit">
          Nombre: N/N, <span class="font-normal">sexo: hembra</span>
        </div>

        <div class="absolute top-13 left-4 bg-blue-500 text-white text-xs px-2 py-1 rounded-md w-fit">
          Edad: 2 años
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
          <h2 class="text-4xl font-bold text-gray-800">Descripción</h2>
          <p class="text-lg font-semibold text-gray-800">
            Con sus largas orejas y su mirada dulce, este burro tiene un encanto irresistible. Es un animal inteligente y curioso, que disfruta de la compañía y de explorar su entorno. Aunque a veces pueda parecer un poco terco, en realidad es muy noble y agradecido.
            Este burro se adapta bien a diferentes entornos, siempre y cuando tenga espacio para moverse, un refugio seguro y una alimentación adecuada. Le encanta recibir mimos y cepillados, y te recompensará con su afecto y su presencia tranquila.
          </p>
        </div>
      </div>
      <!-- Etiquetas de la Mascota -->
      <div class="flex flex-wrap gap-3">
            <!-- Personalidad -->
            <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Personalidad">
                <div class=" mr-2">
                    <i class="fas fa-heart text-pink-500 text-sm"></i>
                </div>
                <span class="text-gray-700">Amigable</span>
            </div>
            
            <!-- nivel de energía -->
            <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Energia">
                <i class="fa-solid fa-bolt text-gray-500 mr-2"></i>
                <span class="text-gray-700">Alto</span>
            </div>
            
            <!-- Tamaño -->
            <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Tamaño">
                <i class="fa-solid fa-ruler-combined text-gray-500 mr-2"></i>
                <span class="text-gray-700">Grande</span>
            </div>
            
            <!-- Alimentación -->
            <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Alimentación">
                <i class="fa-solid fa-utensils text-gray-500 mr-2"></i>
                <span class="text-gray-700">saludable</span>
            </div>
            
            <!-- Ejercicio -->
            <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Ejercicio">
                <i class="fa-solid fa-dumbbell text-gray-500 mr-2"></i>
                <span class="text-gray-700">Regularmente</span>
            </div>
            
            <!-- fecundación -->
            <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Capacidad Reproductiva">
                <i class="fa-solid fa-seedling text-gray-500 mr-2"></i>
                <span class="text-gray-700">Esteril</span>
            </div>
            
            <!-- Afinidad a niños -->
            <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="interacción con niños">
                <i class="fa-solid fa-baby-carriage text-gray-500 mr-2"></i>
                <span class="text-gray-700">Compañera</span>
            </div>
            
            <!-- Frecuencia de visita veterinaria -->
            <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Frecuencia médica">
                <i class="fa-solid fa-star-of-life text-gray-500 mr-2"></i>
                <span class="text-gray-700">Baja</span>
            </div>
        </div>
        <div class="relative w-full min-h-[80vh] rounded-4xl overflow-hidden mt-4">
        <img :src="'https://cdn.pixabay.com/photo/2024/09/09/17/22/donkey-9035452_1280.jpg'" alt="Burro" class="w-full h-130 object-cover rounded-4xl" />
        </div>
        <!-- Historial Mascota -->
        <div class="mt-6 flex justify-center">
              <button class="bg-purple-300 hover:bg-purple-600 text-white text-2xl font-bold py-5 px-10 rounded-md" @click="goToHistorial">
                Historiales
              </button>
        </div>
        <div class="relative w-full min-h-[80vh] rounded-4xl overflow-hidden mt-4">
        <img :src="'https://cdn.pixabay.com/photo/2020/12/29/22/57/donkey-5871800_960_720.jpg'" alt="Burro" class="w-full h-130 object-cover rounded-4xl" />
        </div>
        <div class="px-4 pt-4 pb-6 bg-white space-y-4">
        <div class="space-y-2">
          <h2 class="text-4xl font-bold text-gray-800">Ubicación Actual</h2>
          <p class="text-lg font-semibold text-gray-800">
            Argentina, Misiones, Apóstoles 
          </p>
        </div>
      </div>
        <div class="h-28"></div>
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
  import { useRouter } from 'vue-router'

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
  

  const router = useRouter()

  const goToHistorial = () => {
    router.push('/revisar/dueños')
  }

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
  
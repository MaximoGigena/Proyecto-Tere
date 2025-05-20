<!-- perfil mascota-->
<template>
  <div class="w-full h-full flex flex-col relative overflow-auto">
    <!-- Header sticky dentro del scroll -->
    <div class="sticky top-0 z-30 bg-white px-4 py-1 flex items-center justify-between">
      <div class="text-2xl font-bold text-gray-800 pointer-events-none">
        Explorando mascotas
      </div>
      <button class="text-gray-700 hover:text-black transition mr-8">
        <font-awesome-icon :icon="['fas', 'filter']" class="text-2xl"/>
      </button>
    </div>

    

    <!-- Contenido scrollable -->
   <ContenidoMascota/>

    <!-- Botones flotantes fijos abajo -->
    <div class="sticky bottom-24 flex justify-center gap-14 z-20">
      <button class="bg-white border border-black w-16 h-16 rounded-full shadow-lg flex items-center justify-center transition duration-300">
        <font-awesome-icon :icon="['fas', 'xmark']"  class="text-black text-5xl  hover:text-red-400"/>
      </button>
      <button class="bg-white border border-black w-16 h-16 rounded-full shadow-lg flex items-center justify-center transition duration-300">
        <font-awesome-icon :icon="['fas', 'heart']" class=" text-black text-4xl hover:text-green-400 "/>
      </button>
    </div>
  </div>
</template>


  
  <script setup>
  import { ref, onMounted, onUnmounted } from 'vue'
  import burro from '@/assets/burro.png'
  import PasoAlgo from '../components/reportarMascota.vue'
  import { useRouter } from 'vue-router'
  import { useRoute } from 'vue-router';

  const scrollContainer = ref(null)
  const mostrar = ref(false)
  const route = useRoute()
  
  onMounted(() => {
    document.body.style.overflow = 'hidden'
  })
 
  

  const router = useRouter()

  const goToHistorial = () => {
    router.push('/revisar/propietarios')
  }

  // mostrar el boton de cerrar por cercania
  const mostrarBotonVolver = route.query.from === 'cerca'

import SvgIcon from '@jamescoyle/vue-icon';
import { mdiReproduction } from '@mdi/js';
import ContenidoMascota from './contenidoMascota.vue';

const path = mdiReproduction;


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
  
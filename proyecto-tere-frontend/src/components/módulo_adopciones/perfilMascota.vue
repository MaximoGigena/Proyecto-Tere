<!-- perfil mascota-->
<template>
  <div class="w-full h-full flex flex-col relative overflow-auto">
    <!-- Header sticky dentro del scroll -->
    <div class="sticky top-0 z-30 bg-white px-4 py-1 flex items-center justify-between">
      <div class="text-2xl font-bold text-gray-800 pointer-events-none">
        Explorando mascotas
      </div>
      <button
        @click="mostrarFiltros = true"
        class="inline-flex whitespace-nowrap items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-700 hover:text-white transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300"
      >
        <span class="font-medium text-sm sm:text-base">Filtrar Mascotas</span>
        <font-awesome-icon :icon="['fas', 'filter']" class="text-lg sm:text-xl" />
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

      <!-- Overlay de filtros -->
    
      <transition name="fade">
        <div 
          v-if="mostrarFiltros" 
          class="fixed inset-0 z-40 bg-black/50  flex items-center justify-center p-4"
          @click.self="mostrarFiltros = false"
        >
          <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="p-4 max-w-xl mx-auto">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Filtrar Mascotas</h2>
                <button @click="mostrarFiltros = false" class="text-gray-500 hover:text-gray-700">
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
import { useRoute, useRouter } from 'vue-router'
import ContenidoMascota from '@/components/módulo_mascotas/contenidoMascota.vue'
import FiltrosComponente from './filtrosAdopciones.vue' // Asegúrate de que la ruta sea correcta

const mostrarFiltros = ref(false)
const route = useRoute()
const router = useRouter()

// mostrar el boton de cerrar por cercania
const mostrarBotonVolver = route.query.from === 'cerca'

const goToHistorial = () => {
  router.push('/revisar/propietarios')
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
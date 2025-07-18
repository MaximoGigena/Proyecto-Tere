<!-- revisiones -->
<template>
  <div class="p-4 w-full flex flex-col h-full min-W-[300px]">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'x-ray']" class="mr-2" />
      Revisiones de la Mascota
    </h2>
    <p class="flex-grow">Contenido del las Revisiones..</p>
   
    <div
      v-if="$route.query.from === 'veterinarios'"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroRevision"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Revisión
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router' // Importa useRouter

const router = useRouter() // Define el router
const mostrar = ref(false)

const abrirRegistroRevision = () => {
  router.push({
    path: '/registro/revision',
    query: {
      from: '/historialPreventivo/revisiones'
    }
  });
};
</script>
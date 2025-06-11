<!-- src/components/historialMedico/Diagnosticos.vue -->
<template>
  <div class="p-4 min-w-[300px] flex flex-col h-full">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'pills']" class="mr-2" />
      Fármacos administrados a la mascota
    </h2>
    <p class="flex-grow">Contenido del recetario de fármacos...</p>

    <div
      v-if="$route.query.from === 'veterinarios'"
      class="mt-4 flex justify-center"
    >
      <button
        @click="mostrar = true"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Fármaco
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'

const mostrar = ref(false)
</script>


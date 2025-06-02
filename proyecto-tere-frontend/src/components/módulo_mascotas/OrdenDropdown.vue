<!-- components/OrdenDropdown.vue -->
<template>
    <div class="p-4 border-b">
      <button
        @click="ordenAsc = !ordenAsc"
        class="flex items-center gap-2 px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100 transition"
      >
        <span>{{ ordenAsc ? 'Fecha ascendente' : 'Fecha descendente' }}</span>
        <svg
          v-if="ordenAsc"
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <!-- Flecha arriba -->
          <path
            fill-rule="evenodd"
            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
            clip-rule="evenodd"
          />
        </svg>
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <!-- Flecha abajo -->
          <path
            fill-rule="evenodd"
            d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
    </div>
  </template>
  
  <script setup>
  import { ref, watch, defineEmits } from 'vue'

  const emit = defineEmits(['cambioOrden'])

  const ordenAsc = ref(true)
  
  watch(ordenAsc, (nuevoValor) => {
    emit('cambioOrden', nuevoValor)
  })
  </script>
  
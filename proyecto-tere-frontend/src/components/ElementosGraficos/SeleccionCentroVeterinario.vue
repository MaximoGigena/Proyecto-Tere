<!-- SeleccionCentroVeterinario.vue -->
<template>
  <div 
    v-if="mostrar" 
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click="cerrarOverlay"
  >
    <div 
      class="bg-white p-6 rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto"
      @click.stop
    >
      <h3 class="text-2xl font-bold mb-4">Seleccionar Centro Veterinario</h3>
      
      <div v-if="centros.length" class="space-y-3">
        <div 
          v-for="centro in centros" 
          :key="centro.id"
          class="border rounded p-3 cursor-pointer hover:bg-gray-50"
          :class="{ 'bg-blue-50 border-blue-300': centroSeleccionado === centro.id }"
          @click="seleccionarCentro(centro)"
        >
          <h4 class="font-semibold">{{ centro.nombre }}</h4>
          <p class="text-sm text-gray-600">{{ centro.direccion }}</p>
          <p class="text-sm text-gray-500">{{ centro.telefono }}</p>
        </div>
      </div>

      <div v-else class="text-gray-500 text-center py-6">
        No hay centros veterinarios registrados.
      </div>

      <div class="mt-4 flex justify-end">
        <button 
          @click="cerrarOverlay"
          class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"
        >
          Cerrar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  mostrar: Boolean,              // Controla si se ve o no el overlay
  centros: Array,                // Lista de centros veterinarios
  centroSeleccionado: [String, Number] // Centro actualmente seleccionado
})

const emit = defineEmits(['cerrar', 'seleccionar'])

// Emitir evento de cierre
const cerrarOverlay = () => {
  emit('cerrar')
}

// Emitir evento con el centro seleccionado
const seleccionarCentro = (centro) => {
  emit('seleccionar', centro)
}
</script>

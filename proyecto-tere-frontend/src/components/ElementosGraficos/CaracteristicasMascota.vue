<!-- CaracteristicasMascota.vue -->
<template>
  <!-- Características -->
  <div class="caracteristicas-container">
    <!-- Etiquetas de la Mascota -->
    <div class="flex flex-wrap gap-3 mt-2 justify-center">
      <!-- Personalidad -->
      <div 
        v-if="mascota.caracteristicas?.personalidad" 
        class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" 
        title="Personalidad"
      >
        <div class="mr-2">
          <font-awesome-icon :icon="['fas', 'heart']" class="text-pink-500 text-sm"/>
        </div>
        {{ mascota.caracteristicas.personalidad }}
      </div>
      
      <!-- Nivel de energía -->
      <div 
        v-if="mascota.caracteristicas?.energia" 
        class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" 
        title="Energía"
      >
        <font-awesome-icon :icon="['fas', 'bolt']" class="text-gray-500 mr-2" />
        {{ mascota.caracteristicas.energia }}
      </div>
      
      <!-- Tamaño -->
      <div 
        v-if="mascota.caracteristicas?.tamano" 
        class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" 
        title="Tamaño"
      >
        <font-awesome-icon :icon="['fas', 'ruler-combined']" class="text-gray-500 mr-2"/>
        {{ mascota.caracteristicas.tamano }}
      </div>
      
      <!-- Alimentación -->
      <div 
        v-if="mascota.caracteristicas?.alimentacion" 
        class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" 
        title="Alimentación"
      >
        <font-awesome-icon :icon="['fas', 'bowl-food']" class="text-gray-500 mr-2"/>
        {{ mascota.caracteristicas.alimentacion }}
      </div>
      
      <!-- Ejercicio -->
      <div 
        v-if="mascota.caracteristicas?.ejercicio" 
        class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" 
        title="Ejercicio"
      >
        <font-awesome-icon :icon="['fas', 'dumbbell']" class="text-gray-500 mr-2"/>
        {{ mascota.caracteristicas.ejercicio }}
      </div>
      
      <!-- Afinidad a niños -->
      <div 
        v-if="mascota.caracteristicas?.comportamiento_ninos" 
        class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" 
        title="Interacción con niños"
      >
        <font-awesome-icon :icon="['fas', 'baby-carriage']" class="text-gray-500 mr-2"/>
        {{ mascota.caracteristicas.comportamiento_ninos }}
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  mascota: {
    type: Object,
    required: true,
    default: () => ({})
  }
})
</script>

<style scoped>
.caracteristicas-container {
  width: 100%;
  padding: 1.5rem 1rem;
}
</style>
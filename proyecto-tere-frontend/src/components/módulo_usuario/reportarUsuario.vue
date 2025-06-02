<template>
  <div class="fixed inset-0 z-40 bg-black bg-opacity-30 flex items-center justify-center">
    <!-- Contenedor blanco -->
    <div class="relative bg-white rounded-2xl shadow-xl p-6 w-80">
      <!-- Botón cerrar -->
      <button
        @click="cerrar"
        class="absolute top-2 right-3 text-black text-lg font-bold hover:scale-110 transition"
      >
        x
      </button>

      <!-- Contenido -->
      <div class="text-center">
        <h2 class="text-2xl font-extrabold">¿Pasó Algo?</h2>
        <p class="text-red-600 font-bold text-sm mt-1">Reportar Mascota</p>
      </div>

      <!-- Botón flecha -->
      <button
        @click="accion"
        class="absolute bottom-2 right-3 text-black text-xl font-bold hover:scale-110 transition"
      >
        &gt;
      </button>
    </div>
  </div>
</template>
  
<script setup>
 const emit = defineEmits(['close'])

  const cerrar = () => {
    emit('close')
  }

  const accion = () => {
    console.log('Acción siguiente')
  }
</script>
  
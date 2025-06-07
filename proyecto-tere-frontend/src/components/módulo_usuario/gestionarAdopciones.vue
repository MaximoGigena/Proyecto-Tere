<!-- views/gestionarAdopciones.vue -->
<template>
  <div class="p-4 max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Gestión de Adopciones</h1>

    <!-- Listado de mascotas en adopción -->
    <div v-if="mascotas.length" class="space-y-4">
      <div
        v-for="mascota in mascotas"
        :key="mascota.id"
        class="bg-white rounded-xl shadow p-4 flex items-center justify-between"
      >
        <div class="flex items-center gap-4">
          <img :src="mascota.foto" alt="foto mascota" class="w-16 h-16 object-cover rounded-full" />
          <div>
            <h2 class="font-bold">{{ mascota.nombre }}</h2>
            <p class="text-sm text-gray-500">Estado: {{ mascota.estadoAdopcion }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="verSolicitudes(mascota)" class="px-3 py-1 rounded-lg bg-blue-500 text-white hover:bg-blue-600 text-sm">Ver solicitudes</button>
          <button @click="cancelarAdopcion(mascota.id)" class="px-3 py-1 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm">Cancelar</button>
        </div>
      </div>
    </div>
    <div v-else class="text-gray-600">No tenés mascotas en proceso de adopción.</div>
    <button
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200 mx-auto block mt-8"
      >
        + Adopción
      </button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// Simulación de datos cargados
const mascotas = ref([])

onMounted(() => {
  // Esto sería un fetch a la API real en producción
  mascotas.value = [
    {
      id: 1,
      nombre: 'Luna',
      foto: 'https://cdn.pixabay.com/photo/2025/01/23/19/21/french-bulldog-9355276_1280.jpg',
      estadoAdopcion: 'En proceso',
    },
    {
      id: 2,
      nombre: 'Rocky',
      foto: 'https://cdn.pixabay.com/photo/2019/06/29/10/53/pet-4305994_1280.jpg',
      estadoAdopcion: 'Publicada',
    },
  ]
})

function verSolicitudes(mascota) {
  // Podés usar un modal o redirigir a otra vista
  console.log('Ver solicitudes para', mascota.nombre)
}

function cancelarAdopcion(idMascota) {
  if (confirm('¿Estás seguro de cancelar esta adopción?')) {
    mascotas.value = mascotas.value.filter(m => m.id !== idMascota)
  }
}
</script>



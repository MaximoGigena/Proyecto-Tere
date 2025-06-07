<!-- views/PerfilMascotas.vue -->
<template>
  <div class="space-y-6">
    <MascotaCard
      v-for="(mascota, index) in mascotas"
      :key="mascota.id"
      :mascota="mascota"
      :bgColor="bgColors[index % bgColors.length]"
      @click="abrirDetalleMascota(mascota.id)"
    />
    <button
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200 mx-auto block"
      >
        + Mascota
      </button>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import MascotaCard from '@/components/módulo_mascotas/tarjetaMascota.vue'

const router = useRouter()

const abrirDetalleMascota = (id) => {
  router.push({
    path: `/explorar/perfil/mascota/${id}`,
    query: {
      from: '/explorar/perfil/mascotas' // Ruta exacta a la que quieres volver
    }
  });
};

const mascotas = [
  {
    id: 1,
    nombre: 'Lola',
    edad: '2 años',
    sexo: 'Hembra',
    imagen: 'https://cdn.pixabay.com/photo/2025/04/08/16/46/pyjama-9521835_1280.jpg'
  },
  {
    id: 2,
    nombre: 'Milo',
    edad: '4 años',
    sexo: 'Macho',
    imagen: 'https://cdn.pixabay.com/photo/2025/04/09/23/23/cat-9525000_1280.jpg'
  },
  {
    id: 3,
    nombre: 'Capy',
    edad: '1 año',
    sexo: 'Macho',
    imagen: 'https://cdn.pixabay.com/photo/2017/08/18/06/49/capybara-2653996_1280.jpg'
  },
];

const bgColors = [
  'bg-orange-200 hover:bg-orange-400',
  'bg-yellow-200 hover:bg-yellow-400',
  'bg-purple-200 hover:bg-purple-400',
  'bg-red-200 hover:bg-red-400',
  'bg-sky-200 hover:bg-sky-400',
  'bg-fuchsia-200 hover:bg-fuchsia-400',
  'bg-emerald-200 hover:bg-emerald-400'
];
</script>

<style scoped>
.bg-transparent {
  background-color: transparent;
  /* Opcional: asegura que no afecte el layout */
  position: static;
  z-index: 0;
}
</style>
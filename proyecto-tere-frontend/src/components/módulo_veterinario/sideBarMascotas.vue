<template>
  <div class="fixed left-0 top-0 h-screen w-92 bg-white shadow-lg border-r overflow-y-auto z-50 p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold">Buscar Mascotas</h1>
      <button
        @click="abrirFiltro"
        class="inline-flex whitespace-nowrap items-center gap-2 px-5 py-2.5 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-700 hover:text-white transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300"
      >
        <span class="font-medium text-sm sm:text-base">Filtrar Mascota</span>
        <font-awesome-icon :icon="['fas', 'filter']" class="text-lg sm:text-xl" />
      </button>
  </div>

    <input
      v-model="busqueda"
      type="text"
      placeholder="Buscar por nombre..."
      class="w-full p-2 border rounded mb-4"
    />

    <ul>
      <li
        v-for="mascota in mascotasFiltradas"
        :key="mascota.id"
        class="p-3 border rounded mb-2 flex items-center gap-4"
      >
        <img
          :src="mascota.foto"
          alt="Foto de la mascota"
          class="w-16 h-16 object-cover rounded-full border"
        />
        <div class="flex-1">
          <p class="font-semibold">{{ mascota.nombre }}</p>
          <p class="text-sm text-gray-500">Dueño: {{ mascota.duenio }}</p>
        </div>
        <button
          @click="verHistorial(mascota)"
          class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
        >
          Ver Historial
        </button>
      </li>
    </ul>
    <router-view name="overlay" v-slot="{ Component }">
      <transition name="slide-up">
        <component :is="Component" />
      </transition>
    </router-view>
  </div>
</template>

<script>
import { useRoute, useRouter } from 'vue-router';
import { ref } from 'vue';


export default {
  name: 'VeterinarioBuscarMascotas',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const mostrarOverlay = ref(false);

    //los filtros para los veterinarios
    function abrirFiltro() {
      mostrarOverlay.value = true;
      router.push({ name: 'buscarMascotasConFiltros' });
    }


    function verHistorial(mascota) {
      const query = {
        ...route.query,
        from: route.name || 'buscarMascotas',
        originalParams: JSON.stringify(route.params),
        id: mascota.id,
        currentTab: 'propietarios',
        ts: Date.now()
      };

      router.replace({
        name: 'veterinario-propietarios', // Usamos el nombre de la ruta overlay
        params: { id: mascota.id },
        query,
      });
    }

    return {
      route,
      verHistorial,
      abrirFiltro,
    };
  },
  data() {
    return {
      busqueda: '',
      mostrarOverlay: false,
      mascotas: [
        {
          id: 1,
          nombre: 'Tere',
          duenio: 'Juan Pérez',
          foto: 'https://placedog.net/100/100?id=1',
        },
        {
          id: 2,
          nombre: 'Max',
          duenio: 'Laura Gómez',
          foto: 'https://placedog.net/100/100?id=2',
        },
        {
          id: 3,
          nombre: 'Simba',
          duenio: 'Carlos Díaz',
          foto: 'https://cdn.pixabay.com/photo/2016/12/22/17/22/pet-1925868_1280.jpg?id=3',
        },
        {
          id: 4,
          nombre: 'Luna',
          duenio: 'Juan Carlos',
          foto: 'https://cdn.pixabay.com/photo/2021/11/02/16/12/dog-6763589_960_720.jpg?id=4',
        },
        {
          id: 5,
          nombre: 'Magie',
          duenio: 'Laura Gimemez',
          foto: 'https://cdn.pixabay.com/photo/2023/02/24/12/09/cat-7810878_1280.jpg?id=5',
        },
        {
          id: 6,
          nombre: 'timony',
          duenio: 'Carlos Menem',
          foto: 'https://cdn.pixabay.com/photo/2019/02/11/11/53/cat-3989479_1280.jpg?id=6',
        },
      ],
    };
  },
  computed: {
    mascotasFiltradas() {
      return this.mascotas.filter((m) =>
        m.nombre.toLowerCase().includes(this.busqueda.toLowerCase())
      );
    },
  },
};
</script>

<style> 
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

.slide-up-enter-to,
.slide-up-leave-from {
  transform: translateY(0);
  opacity: 1;
}

</style>
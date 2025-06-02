<!-- views/PerfilUsuario.vue -->
<template>
  <div class="w-full h-full flex flex-col relative">
    <!-- Header -->
    <div class="sticky top-0 z-30 bg-white px-4 py-3 shadow">
      <div class="flex items-center justify-between text-2xl font-bold text-gray-800">
        <span>Perfil de Usuario</span>
        <div class="flex gap-4 text-xl text-gray-600">
          <button title="Configuración" class="hover:text-black transition">
            <font-awesome-icon :icon="['fas', 'gear']" />
          </button>
          <button title="Cambiar perfil" class="hover:text-black transition">
            <font-awesome-icon :icon="['fas', 'user-pen']"/>
          </button>
        </div>
      </div>
    </div>

    <!-- Contenido Scrollable -->
    <div class="flex-1 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
      <!-- Usuario -->
      <div class="flex items-center gap-4 my-6">
        <img src="https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg" alt="usuario"
          class="w-32 h-32 rounded-full object-cover ml-4" />
        <div>
          <p class="text-black-700 font-medium underline">Nombre de usuario</p>
          <p class="text-sm text-gray-600">Locación</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex justify-center border-b text-sm font-medium mb-6">
        <div class="flex items-center gap-48 text-xl font-bold">
          <button class="w-36 px-3 py-2 border-b-2 border-black">Mascotas</button>
          <button class="w-36 px-3 py-2 text-gray-400 cursor-default">Seguridad</button>
        </div>
      </div>

        <!-- Lista de mascotas -->
        <div class="w-full flex justify-center mb-6">
          <div class="space-y-20 max-w-sm w-full">
            <MascotaCard
              v-for="(mascota, index) in mascotas"
              :key="mascota.id"
              :mascota="mascota"
              :bgColor="bgColors[index % bgColors.length]"
              @click="abrirDetalleMascota(mascota.id)"
            />
          </div>
        </div>
      </div>
      <div class="h-15"></div>
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
      from: 'perfil',
      originalParams: JSON.stringify({})  // Agregamos aunque sea vacío
    }
  })
}


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
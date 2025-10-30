<template>
  <div class="w-full h-full flex flex-col relative">
    <!-- Header sticky dentro del scroll -->
    <div class="sticky top-0 z-30 bg-white px-4 py-2">
      <!-- Primera fila: título y botón -->
      <div class="flex items-center justify-between px-2">
        <div class="text-2xl font-bold text-gray-800 pointer-events-none">
          Chats
        </div>
        <button class="text-gray-700 hover:text-black transition">
          <font-awesome-icon :icon="['fas', 'bell']" class="text-2xl"/>
        </button>
      </div>

      <!-- Segunda fila: título solicitudes -->
      <h2 class="text-xl font-semibold mt-4 mb-2 px-2 text-gray-800 tracking-wide">
        3 Solicitudes de Adopción
      </h2>

     <!-- Tercera fila: SolicitudAdopción con flechas -->
      <div class="relative mt-3 px-3">
        <!-- Flecha izquierda -->
        <button
          @click="scrollLeft"
          class="absolute left-4 top-1/2 -translate-y-1/2 z-10 p-3 bg-gray-800/80 hover:bg-gray-700/90 backdrop-blur-sm rounded-full transition-all duration-300 shadow-lg hover:shadow-xl border border-gray-600/30 hover:scale-110 group"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white group-hover:text-amber-400 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
            

        <!-- Lista scrollable -->
        <div
          ref="listaPerfiles"
          class="flex gap-3 overflow-x-auto scrollbar-thin scrollbar-thumb-green-300/40 scrollbar-track-transparent"
        >
          <router-link
            v-for="(perfil, index) in perfiles"
            :key="index"
            @click="abrirPerfilUsuario(perfil.id)"
            :to="{
              name: 'user-profile-list',
              params: { userId: perfil.id },
              query: { from: 'chats-list' }
            }"
            :class="[
              'flex flex-col items-center shadow-sm hover:shadow-md transition rounded-xl p-3 min-w-[110px]',
              perfil.bgClass
            ]"
          >
            <div class="relative">
              <img
                :src="perfil.img"
                class="w-16 h-16 rounded-full object-cover border border-white"
                :alt="perfil.nombre"
              />
              <span
                class="absolute -bottom-1 left-1/2 -translate-x-1/2 text-[9px] px-1.5 py-0.5 text-white rounded-full shadow-sm"
                :class="perfil.badgeClass"
              >
                Solicitud
              </span>
            </div>
            <span class="mt-2 text-xs font-medium text-white text-center">
              {{ perfil.nombre }}
            </span>
          </router-link>
        </div>

        <!-- Flecha derecha -->
        <button
          @click="scrollRight"
          class="absolute right-4 top-1/2 -translate-y-1/2 z-10 p-3 bg-gray-800/80 hover:bg-gray-700/90 backdrop-blur-sm rounded-full transition-all duration-300 shadow-lg hover:shadow-xl border border-gray-600/30 hover:scale-110 group"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white group-hover:text-amber-400 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

    </div>
    
    <div class="sticky z-30 bg-white px4 py-2 border-b border-gray-600"></div>
    
    <!-- Contenedor de scroll -->
    <div
      ref="scrollContainer"
      class="relative w-full flex-1 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden px-4"
    >
      <div class="h-2"></div>
      <!-- Notificación de chats -->
      <div class="flex items-center gap-12">
        <!-- Notificación de solicitudes con botón de filtro adentro -->
        <div class="relative flex items-center justify-between border border-black rounded-full 
            bg-white text-black w-full px-4">
  
          <!-- Grupo izquierdo (sobre + texto) -->
          <div class="flex items-center gap-4">
            <!-- Icono de sobre -->
            <div class="w-16 h-16 flex items-center -ml-4 justify-center rounded-full bg-black border-2 border-white">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 4H4C2.897 4 2 4.897 2 6v12c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V6c0-1.103-.897-2-2-2zm0 2v.511l-8 5-8-5V6h16zm-16 12V9.489l7.386 4.616a1 1 0 0 0 1.228 0L20 9.489V18H4z"/>
              </svg>
            </div>

            <!-- Texto de notificación -->
            <span class="text-lg font-semibold text-black">
              Tienes 3 solicitudes de adopción
            </span>
          </div>

          <!-- Botón de filtro (grupo derecho separado) -->
          <div class="relative">
            <button
              @click="open = !open"
              class="flex items-center gap-2 px-4 py-2 rounded-full 
                    bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 
                    text-white font-medium shadow-lg transition-transform duration-300 hover:scale-105"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 019 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
              </svg>
              <span class="text-sm">{{ selectedFilter || "Tu filtro actual" }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Dropdown -->
            <transition name="fade">
              <div v-if="open" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl z-50">
                <ul class="py-2">
                  <li
                    v-for="(filter, index) in filters"
                    :key="index"
                    @click="selectFilter(filter)"
                    class="px-4 py-2 cursor-pointer hover:bg-indigo-100 rounded-lg transition-colors"
                  >
                    {{ filter }}
                  </li>
                </ul>
              </div>
            </transition>
          </div>
        </div>

      </div>

      <div class="h-2"></div>

      <!-- Lista de chats -->
      <div v-for="(chat, index) in chats" :key="index" class="relative">
        <div class="flex justify-between items-center gap-3 mb-2 min-h-[72px] transition duration-200 hover:bg-blue-100 rounded-xl">
          <router-link
            :to="{
              path: `/explorar/chats/${index + 1}`,
              query: {
                nombre: chat.nombre,
                img: chat.img,
                from: 'chats'
              }
            }"
            class="flex-1 flex items-start gap-3 py-4 pl-4"
          >
            <img :src="chat.img" class="w-16 h-16 rounded-full object-cover" :alt="chat.nombre">
            <div>
              <p class="font-semibold text-lg">{{ chat.nombre }}</p>
              <p class="text-sm text-gray-700">{{ chat.mensaje }}</p>
            </div>
          </router-link>
          
          <button
            @click.stop="toggleFavorite(chat)"
            class="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 p-2 transition duration-200"
            :class="chat.favorito ? 'text-green-400' : 'text-gray-400 hover:text-gray-500'"
          >
            <font-awesome-icon :icon="['fas', 'star']" class="text-2xl"/>
          </button>
        </div>
      </div>
      
      <div class="h-28"></div>
    </div>
  </div>
</template>
  
<script setup>
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useRoute } from 'vue-router';
import { ref } from "vue";

const route = useRoute();
const router = useRouter();

const listaPerfiles = ref(null);

function scrollLeft() {
  listaPerfiles.value.scrollBy({
    left: -150,
    behavior: 'smooth'
  });
}

function scrollRight() {
  listaPerfiles.value.scrollBy({
    left: 150,
    behavior: 'smooth'
  });
}

const open = ref(false);
const selectedFilter = ref(null);
const filters = ref(["Opción 1", "Opción 2", "Opción 3", "Opción 4"]);

function selectFilter(filter) {
  selectedFilter.value = filter;
  open.value = false;
}

// Lista de combinaciones (tarjeta + badge)
const palettes = [
  { bgClass: 'bg-gradient-to-r from-blue-400 to-orange-300', badgeClass: 'bg-blue-600' },
  { bgClass: 'bg-gradient-to-r from-green-400 to-yellow-300', badgeClass: 'bg-green-600' },
  { bgClass: 'bg-gradient-to-r from-purple-400 to-pink-300', badgeClass: 'bg-purple-600' },
  { bgClass: 'bg-gradient-to-r from-teal-400 to-cyan-300', badgeClass: 'bg-teal-600' },
  { bgClass: 'bg-gradient-to-r from-indigo-400 to-blue-300', badgeClass: 'bg-indigo-600' },
  { bgClass: 'bg-gradient-to-r from-red-400 to-orange-400', badgeClass: 'bg-red-600' },
  { bgClass: 'bg-gradient-to-r from-red-400 to-blue-400', badgeClass: 'bg-red-600' },
  { bgClass: 'bg-gradient-to-r from-yellow-400 to-pink-400', badgeClass: 'bg-yellow-600' },
  { bgClass: 'bg-gradient-to-r from-green-400 to-blue-400', badgeClass: 'bg-green-600' },
  { bgClass: 'bg-gradient-to-r from-purple-400 to-indigo-400', badgeClass: 'bg-purple-600' },
  { bgClass: 'bg-gradient-to-r from-purple-400 to-yellow-400', badgeClass: 'bg-purple-600' }
];


// Función para obtener una paleta aleatoria
const getRandomPalette = () => palettes[Math.floor(Math.random() * palettes.length)];

const perfiles = reactive([
  {
    id: 1,
    nombre: 'La Abu',
    img: 'https://cdn.pixabay.com/photo/2020/01/15/19/45/witch-4768770_1280.jpg',
    ...getRandomPalette()
  },
  {
    id: 2,
    nombre: 'Mohamed',
    img: 'https://cdn.pixabay.com/photo/2020/07/16/07/36/man-5410019_960_720.jpg',
    ...getRandomPalette()
  },
  {
    id: 3,
    nombre: 'Mauricio',
    img: 'https://cdn.pixabay.com/photo/2020/05/16/16/41/man-5178199_1280.jpg',
    ...getRandomPalette()
  },
  {
    id: 4,
    nombre: 'Michael',
    img: 'https://cdn.pixabay.com/photo/2025/08/23/07/48/sadhu-9791446_960_720.jpg',
    ...getRandomPalette()
  },
  {
    id: 5,
    nombre: 'Pepa',
    img: 'https://cdn.pixabay.com/photo/2025/05/08/14/54/vietnam-9587582_1280.jpg',
    ...getRandomPalette()
  },
  {
    id: 6,
    nombre: 'Sofia',
    img: 'https://cdn.pixabay.com/photo/2025/04/28/20/02/woman-9565637_1280.jpg',
    ...getRandomPalette()
  },
  {
    id: 7,
    nombre: 'Maxi',
    img: 'https://cdn.pixabay.com/photo/2025/03/26/01/56/man-9493528_1280.jpg',
    ...getRandomPalette()
  },
  {
    id: 8,
    nombre: 'Maca',
    img: 'https://cdn.pixabay.com/photo/2025/02/25/21/08/ai-generated-9431603_1280.jpg',
    ...getRandomPalette()
  },
]);

const chats = reactive([
  {
    id: 1, // Añadido ID
    nombre: 'Josefina, dolores del mes',
    mensaje: 'Te molesta si me como a tu perro?',
    img: 'https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg',
    favorito: false
  },
  {
    id: 2, // Añadido ID
    nombre: 'Pepita, juana',
    mensaje: 'Hola, estoy interesado en adoptar a tu perro',
    img: 'https://cdn.pixabay.com/photo/2020/01/27/17/04/cigar-4797760_960_720.jpg',
    favorito: false
  },
  {
    id: 3, // Añadido ID
    nombre: 'Maria, Rosa',
    mensaje: 'Hola, estoy interesado en adoptar a tu perro',
    img: 'https://cdn.pixabay.com/photo/2025/04/15/15/06/woman-9535611_1280.jpg',
    favorito: false
  },
  {
    id: 4, // Añadido ID
    nombre: 'Doña, juana',
    mensaje: 'Hola, estoy interesado en adoptar a tu perro',
    img: 'https://cdn.pixabay.com/photo/2025/04/15/19/41/woman-9536174_960_720.jpg',
    favorito: false
  },
])

// Función para navegar al chat
const goToChat = (chatId) => {
  const chat = chats.find(c => c.id === chatId);
  router.push({
    path: `/explorar/chats/${chatId}`,
    query: {
      ...route.query,
      nombre: chat.nombre,
      img: chat.img
    }
  });
};

// Función para ver perfil
const abrirPerfilUsuario = (userId) => {
  router.push({
    name: 'user-profile-list',
    params: { userId },
    query: { from: 'chats-list' }
  });
};

// Función para manejar favoritos
const toggleFavorite = (chat) => {
  chat.favorito = !chat.favorito;
  console.log(`${chat.nombre} favorito:`, chat.favorito);
};
</script>

<style>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
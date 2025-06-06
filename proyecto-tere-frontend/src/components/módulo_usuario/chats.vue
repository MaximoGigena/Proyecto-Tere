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
      <h2 class="text-xl font-semibold mt-2 underline px-2">
        3 Solicitudes de Adopción
      </h2>

      <!-- Tercera fila: perfiles -->
      <div class="flex items-center gap-4 mt-1 px-2 overflow-x-auto">
        <router-link 
          v-for="(perfil, index) in perfiles" 
          :key="index" 
          @click="abrirPerfilUsuario(perfil.id)"
          :to="{
            name: 'user-profile-list',  // Nombre corregido
            params: { userId: perfil.id },
            query: { from: 'chats-list' } 
          }"
          class="flex flex-col items-center hover:opacity-80 transition-opacity"
        >
          <img :src="perfil.img" class="w-16 h-16 rounded-full object-cover" :alt="perfil.nombre" />
          <span class="text-sm mt-1">{{ perfil.nombre }}</span>
        </router-link>
      </div>
    </div>
    
    <div class="sticky z-30 bg-white px4 py-2 border-b border-gray-600"></div>

    <div
      ref="scrollContainer"
      class="relative w-full flex-1 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
    >
        <!-- Filtro -->
          <div class="flex items-center mt-2 gap-2 mb-3 ml-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 019 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
            </svg>
            <span>Hola, soy un filtro, y este es tu filtro actual</span>
          </div>
    
          <!-- Notificación de chats -->
          <div class="flex items-center gap-2 mb-5 ml-4">
            <img src="https://icons.veryicon.com/png/o/internet--web/billion-square-cloud/mail-213.png"
              class="w-16 h-16 rounded-full object-cover" alt="Josefina">
            <span>tienes 3 solicitudes de chat y 2 chat en desarrollo</span>
          </div>
      
      <!-- Lista de chats -->
      <router-link
        v-for="(chat, index) in chats"
        :key="index"
        @click="goToChat(chat.id)"
        :to="{
          path: `/explorar/chats/${chat.id}`,
          query: {
            nombre: chat.nombre,
            img: chat.img,
            from: 'chats'
          }
        }"
        class="flex justify-between items-center gap-3 mb-2 min-h-[72px] ml-4 transition duration-200 hover:bg-blue-100"
      >
        <div class="flex items-start gap-3">
          <img :src="chat.img" class="w-16 h-16 rounded-full object-cover" :alt="chat.nombre">
          <div>
            <p class="font-semibold text-lg">{{ chat.nombre }}</p>
            <p class="text-sm text-gray-700">{{ chat.mensaje }}</p>
          </div>
        </div>
        <button
          @click.stop="toggleFavorite(chat)"
          class="mt-1 mr-8 transition duration-200"
          :class="chat.favorito ? 'text-green-400' : 'text-gray-400 hover:text-gray-500'"
        >
          <font-awesome-icon :icon="['fas', 'star']" class="text-2xl"/>
        </button>
      </router-link>
      
      <div class="h-28"></div>
    </div>  
  </div>
</template>
  
<script setup>
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useRoute } from 'vue-router'

const route = useRoute()

const router = useRouter();

const perfiles = reactive([ // Cambiado a reactive para poder modificar
  {
    id: 1, // Añade ID
    nombre: 'La Abu',
    img: 'https://cdn.pixabay.com/photo/2020/01/15/19/45/witch-4768770_1280.jpg',
  },
  {
    id: 2,
    nombre: 'Mohamed',
    img: 'https://cdn.pixabay.com/photo/2020/07/16/07/36/man-5410019_960_720.jpg',
  },
  {
    id: 3,
    nombre: 'Mauricio',
    img: 'https://cdn.pixabay.com/photo/2020/05/16/16/41/man-5178199_1280.jpg',
  },
]);

const chats = reactive([
    {
      nombre: 'Josefina, dolores del mes',
      mensaje: 'Te molesta si me como a tu perro?',
      img: 'https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg',
    },
    {
      nombre: 'Pepita, juana',
      mensaje: 'Hola, estoy interesado en adoptar a tu perro',
      img: 'https://cdn.pixabay.com/photo/2020/01/27/17/04/cigar-4797760_960_720.jpg',
    },
    {
      nombre: 'Maria, Rosa',
      mensaje: 'Hola, estoy interesado en adoptar a tu perro',
      img: 'https://cdn.pixabay.com/photo/2025/04/15/15/06/woman-9535611_1280.jpg',
    },
    {
      nombre: 'Doña, juana',
      mensaje: 'Hola, estoy interesado en adoptar a tu perro',
      img: 'https://cdn.pixabay.com/photo/2025/04/15/19/41/woman-9536174_960_720.jpg',
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

// Función para ver perfil (corregida)
const abrirPerfilUsuario = (userId) => {
  router.push({
    name: 'user-profile-list',
    params: { userId },
    query: { from: 'chats-list' } // <-- Esto mantendrá ChatsLista como default
  });
};

console.log('Rutas disponibles:', router.getRoutes());

// Función para manejar favoritos
const toggleFavorite = (chat) => {
  chat.favorito = !chat.favorito;
  console.log(`${chat.nombre} favorito:`, chat.favorito);
};
</script>

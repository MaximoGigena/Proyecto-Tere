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
          <i class="fa-solid fa-bell text-2xl"></i>
        </button>
      </div>

      <!-- Segunda fila: título solicitudes -->
      <h2 class="text-xl font-semibold mt-2 underline px-2">
        3 Solicitudes de Adopción
      </h2>

      <!-- Tercera fila: perfiles -->
      <div class="flex items-center gap-4 mt-1 px-2 overflow-x-auto">
        <div v-for="(perfil, index) in perfiles" :key="index" class="flex flex-col items-center">
          <img :src="perfil.img" class="w-16 h-16 rounded-full object-cover" :alt="perfil.nombre" />
          <span class="text-sm mt-1">{{ perfil.nombre }}</span>
        </div>
      </div>
    </div>
    <div class="sticky z-30 bg-white px4 py-2 border-b border-gray-600"></div>

    <div
      ref="scrollContainer"
      class="relative w-full  flex-1 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
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
          <div v-for="(chat, index) in chats" :key="index" class="flex justify-between items-center gap-3 mb-2 min-h-[72px] ml-4 transition duration-200 hover:bg-blue-100 cursor-pointer">
            <div class="flex items-start gap-3">
              <img :src="chat.img" class="w-16 h-16 rounded-full object-cover" :alt="chat.nombre">
              <div>
                <p class="font-semibold text-lg">{{ chat.nombre }}</p>
                <p class="text-sm text-gray-700">{{ chat.mensaje }}</p>
              </div>
            </div>
            <button
              @click="chat.favorito = !chat.favorito; console.log(chat.nombre + ' favorito:', chat.favorito)"
              class="mt-1 mr-8 transition duration-200"
              :class="chat.favorito ? 'text-green-400' : 'text-gray-400 hover:text-gray-500'"
            >
              <i :class="chat.favorito ? 'fa-solid fa-star text-2xl' : 'fa-regular fa-star text-2xl'"></i>
            </button>
          </div>
        <div class="h-28"></div>
      </div>  
  </div>
</template>
  
  <script setup>
import { reactive } from 'vue';

  const perfiles = [
    {
      nombre: 'La Abu',
      img: 'https://cdn.pixabay.com/photo/2020/01/15/19/45/witch-4768770_1280.jpg',
    },
    {
      nombre: 'Mohamed',
      img: 'https://cdn.pixabay.com/photo/2020/07/16/07/36/man-5410019_960_720.jpg',
    },
    {
      nombre: 'Mauricio',
      img: 'https://cdn.pixabay.com/photo/2020/05/16/16/41/man-5178199_1280.jpg',
    },
  ]
  
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
  </script>
  
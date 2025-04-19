<template>
  <div class="">
    <div class="w-full h-full flex flex-col relative">
        <!-- Header sticky dentro del scroll -->
        <div class="sticky top-0 z-30 bg-white px-4 py-2"></div>
          <!-- Primera fila: título y botón -->
          <div class="flex items-start justify-between mb-4 mr-4 ml-4 text-2xl font-bold text-gray-800">
            Perfil de Usuario
              <div class="flex gap-3 text-xl text-gray-600 ml-24">
                <button title="Configuración" class="hover:text-black transition">
                  <i class="fas fa-cog"></i>
                </button>
                <button title="Cambiar perfil" class="hover:text-black transition">
                  <i class="fa-solid fa-user-pen"></i>
                </button>
              </div>
            </div>
        

            <!-- Segunda fila: título Usuario  -->
            <div class="flex items-center gap-3 mb-4 mr-4 ml-4">
              <img
                src="https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg"
                alt="usuario"
                class="w-32 h-32 rounded-full object-cover"
              />
              <div>
                <p class="text-black-700 font-medium underline">Nombre de usuario</p>
                <p class="text-sm text-gray-600">Locación</p>
              </div>
            </div>
        </div>

        <!-- scroll --> 
        <div
          ref="scrollContainer"
          class="relative w-full mt-0 flex-1 overflow-y-auto custom-scrollbar px-2"
        >
          
      
            <!-- Tabs -->
            <div class="flex justify-center border-b text-sm font-medium mb-3">
                <div class="flex items-center gap-48 text-xl font-bold">
                    <button class="w-36 px-3 py-2 border-b-2 border-black">Mascotas</button>
                    <button class="w-36 px-3 py-2 text-gray-400 cursor-default">Seguridad</button>
                </div>
            </div>
      
            <!-- Mascotas -->
            <div class="w-full flex justify-center">
              <div class="space-y-10 max-w-sm w-full">
                <!-- Mascota 1 -->
                <div class="flex items-center bg-orange-200 rounded-lg px-4 py-2 pl-22 relative shadow mt-2">
                  <img
                    src="https://cdn.pixabay.com/photo/2025/04/08/16/46/pyjama-9521835_1280.jpg"
                    alt="mascota"
                    class="w-25 h-25 rounded-full border-4 border-blue-300 object-cover absolute -left-4 top-1/2 -translate-y-1/2"
                  />
                  <div class="text-sm">
                    <p class="font-semibold">Nombre de la Mascota</p>
                    <p>Edad</p>
                    <p>Sexo</p>
                  </div>
                </div>
      
                <!-- Mascota 2 -->
                <div class="flex items-center bg-yellow-200 rounded-lg px-4 py-2 pl-22 relative shadow">
                  <img
                    src="https://cdn.pixabay.com/photo/2025/04/09/23/23/cat-9525000_1280.jpg"
                    alt="mascota"
                    class="w-25 h-25 rounded-full border-4 border-pink-300 object-cover absolute -left-4 top-1/2 -translate-y-1/2"
                  />
                  <div class="text-sm">
                    <p class="font-semibold">Nombre de la Mascota</p>
                    <p>Edad</p>
                    <p>Sexo</p>
                  </div>
                </div>
              </div>
            </div>
      
            <!-- Botón agregar -->
            <div class="mt-6 flex justify-center">
              <button class="bg-blue-300 hover:bg-blue-400 text-white text-2xl font-bold py-5 px-10 rounded-full">
                + Mascota
              </button>
            </div>
    </div>
  </div>
</template>
  
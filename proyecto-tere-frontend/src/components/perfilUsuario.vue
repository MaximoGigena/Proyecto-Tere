<template>
  <div class="w-full h-full flex flex-col relative">

    <!-- ✅ Header sticky fuera del scroll -->
    <div class="sticky top-0 z-30 bg-white px-4 py-3 shadow">
      <!-- Primera fila: título y botones -->
      <div class="flex items-center justify-between text-2xl font-bold text-gray-800">
        <span>Perfil de Usuario</span>
        <div class="flex gap-4 text-xl text-gray-600">
          <button title="Configuración" class="hover:text-black transition">
            <i class="fas fa-cog"></i>
          </button>
          <button title="Cambiar perfil" class="hover:text-black transition">
            <i class="fa-solid fa-user-pen"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- ✅ Scrollable content -->
    <div class="flex-1 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">

      <!-- Segunda fila: usuario -->
      <div class="flex items-center gap-4 my-6">
        <img
          src="https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg"
          alt="usuario"
          class="w-32 h-32 rounded-full object-cover ml-4"
        />
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

      <!-- Mascotas -->
      <div class="w-full flex justify-center mb-6">
              <div class="space-y-20 max-w-sm w-full">
                <!-- Mascota 1 -->
                <div class="relative flex items-center bg-orange-200 rounded-lg px-16 py-8 pl-22 shadow mt-12 transition duration-300 hover:bg-orange-400 hover:shadow-lg group">
                  <!-- Ícono en la esquina -->
                    <button
                      title="Editar mascota"
                      class="absolute top-2 right-2 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-pen-to-square text-2xl"></i>
                    </button>
                    <button
                      title="Historial de mascota"
                      class="absolute top-2 right-10 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-list-check text-2xl"></i>
                    </button>
                  <img
                    src="https://cdn.pixabay.com/photo/2025/04/08/16/46/pyjama-9521835_1280.jpg"
                    alt="mascota"
                    class="w-45 h-45 rounded-full border-4 border-blue-300 object-cover absolute -left-24 top-1/2 -translate-y-1/2"
                  />
                  <div class="text-sm">
                    <p class="font-semibold">Nombre de la Mascota</p>
                    <p>Edad</p>
                    <p>Sexo</p>
                  </div>
                </div>
      
                <!-- Mascota 2 -->
                <div class="relative flex items-center bg-yellow-200 rounded-lg px-16 py-8 pl-22 shadow mt-12 transition duration-300 hover:bg-yellow-400 hover:shadow-lg group">
                   <!-- Ícono en la esquina -->
                   <button
                      title="Editar mascota"
                      class="absolute top-2 right-2 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-pen-to-square text-2xl"></i>
                    </button>
                    <button
                      title="Editar mascota"
                      class="absolute top-2 right-10 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-list-check text-2xl"></i>
                    </button>
                  <img
                    src="https://cdn.pixabay.com/photo/2025/04/09/23/23/cat-9525000_1280.jpg"
                    alt="mascota"
                    class="w-45 h-45 rounded-full border-4 border-pink-300 object-cover absolute -left-24 top-1/2 -translate-y-1/2"
                  />
                  <div class="text-sm">
                    <p class="font-semibold">Nombre de la Mascota</p>
                    <p>Edad</p>
                    <p>Sexo</p>
                  </div>
                </div>
                <!-- Mascota 3 -->
                <div class="relative flex items-center bg-purple-200 rounded-lg px-16 py-8 pl-22 shadow mt-12 transition duration-300 hover:bg-purple-400 hover:shadow-lg group">
                   <!-- Ícono en la esquina -->
                   <button
                      title="Editar mascota"
                      class="absolute top-2 right-2 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-pen-to-square text-2xl"></i>
                    </button>
                    <button
                      title="Editar mascota"
                      class="absolute top-2 right-10 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-list-check text-2xl"></i>
                    </button>
                  <img
                    src="https://cdn.pixabay.com/photo/2017/08/18/06/49/capybara-2653996_1280.jpg"
                    alt="mascota"
                    class="w-45 h-45 rounded-full border-4 border-yellow-300 object-cover absolute -left-24 top-1/2 -translate-y-1/2"
                  />
                  <div class="text-sm">
                    <p class="font-semibold">Nombre de la Mascota</p>
                    <p>Edad</p>
                    <p>Sexo</p>
                  </div>
                </div>

                <!-- Mascota 4 -->
                <div class="relative flex items-center bg-red-200 rounded-lg px-16 py-8 pl-22 shadow mt-12 transition duration-300 hover:bg-red-400 hover:shadow-lg group">
                   <!-- Ícono en la esquina -->
                   <button
                      title="Editar mascota"
                      class="absolute top-2 right-2 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-pen-to-square text-2xl"></i>
                    </button>
                    <button
                      title="Editar mascota"
                      class="absolute top-2 right-10 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-list-check text-2xl"></i>
                    </button>
                  <img
                    src="https://cdn.pixabay.com/photo/2015/10/18/20/54/baby-myna-birds-995196_1280.jpg"
                    alt="mascota"
                    class="w-45 h-45 rounded-full border-4 border-green-300 object-cover absolute -left-24 top-1/2 -translate-y-1/2"
                  />
                  <div class="text-sm">
                    <p class="font-semibold">Nombre de la Mascota</p>
                    <p>Edad</p>
                    <p>Sexo</p>
                  </div>
                </div>
                <!-- Mascota 5 -->
                <div class="relative flex items-center bg-sky-200 rounded-lg px-16 py-8 pl-22 shadow mt-12 transition duration-300 hover:bg-sky-400 hover:shadow-lg group">
                   <!-- Ícono en la esquina -->
                   <button
                      title="Editar mascota"
                      class="absolute top-2 right-2 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-pen-to-square text-2xl"></i>
                    </button>
                    <button
                      title="Editar mascota"
                      class="absolute top-2 right-10 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-list-check text-2xl"></i>
                    </button>
                  <img
                    src="https://cdn.pixabay.com/photo/2018/07/31/06/58/turtle-3574371_1280.jpg"
                    alt="mascota"
                    class="w-45 h-45 rounded-full border-4 border-orange-300 object-cover absolute -left-24 top-1/2 -translate-y-1/2"
                  />
                  <div class="text-sm">
                    <p class="font-semibold">Nombre de la Mascota</p>
                    <p>Edad</p>
                    <p>Sexo</p>
                  </div>
                </div>
                <!-- Mascota 6 -->
                <div class="relative flex items-center bg-fuchsia-200 rounded-lg px-16 py-8 pl-22 shadow mt-12 transition duration-300 hover:bg-fuchsia-400 hover:shadow-lg group">
                   <!-- Ícono en la esquina -->
                   <button
                      title="Editar mascota"
                      class="absolute top-2 right-2 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-pen-to-square text-2xl"></i>
                    </button>
                    <button
                      title="Editar mascota"
                      class="absolute top-2 right-10 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-list-check text-2xl"></i>
                    </button>
                  <img
                    src="https://cdn.pixabay.com/photo/2017/10/02/19/45/horse-2809961_960_720.jpg"
                    alt="mascota"
                    class="w-45 h-45 rounded-full border-4 border-yellow-800 object-cover absolute -left-24 top-1/2 -translate-y-1/2"
                  />
                  <div class="text-sm">
                    <p class="font-semibold">Nombre de la Mascota</p>
                    <p>Edad</p>
                    <p>Sexo</p>
                  </div>
                </div>
                 <!-- Mascota 7 -->
                 <div class="relative flex items-center bg-emerald-200 rounded-lg px-16 py-8 pl-22 shadow mt-12 transition duration-300 hover:bg-emerald-400 hover:shadow-lg group">
                   <!-- Ícono en la esquina -->
                   <button
                      title="Editar mascota"
                      class="absolute top-2 right-2 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-pen-to-square text-2xl"></i>
                    </button>
                    <button
                      title="Editar mascota"
                      class="absolute top-2 right-10 text-gray-500 hover:text-white transition duration-200"
                    >
                      <i class="fa-solid fa-list-check text-2xl"></i> 
                    </button>
                  <img
                    src="https://cdn.pixabay.com/photo/2016/05/26/04/08/tarantula-1416353_1280.jpg"
                    alt="mascota"
                    class="w-45 h-45 rounded-full border-4 border-red-400 object-cover absolute -left-24 top-1/2 -translate-y-1/2"
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
        <div class="h-28"></div>
    </div>
  </div>
</template>

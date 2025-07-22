<template>
  <div class="flex h-screen">
    <!-- Contenido principal primero -->
    <main class="flex-1 bg-gray-100 p-6 overflow-y-auto">
      <router-view />
    </main>

    <!-- Navbar lateral derecha -->
    <nav class="w-20 bg-gray-900 text-white flex flex-col items-center py-6 space-y-6 shadow-lg">
      <!-- Logo Veterinario -->
      <div class="text-2xl font-bold">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </div>

      <!-- Botón Pacientes -->
      <router-link to="/veterinarios/busqueda" class="group relative">
        <font-awesome-icon
          :icon="['fas', 'magnifying-glass']" size="2x"
          class="h-7 w-7 text-gray-400 group-hover:text-green-400 transition"
        />
        <span class="absolute right-12 top-1/2 transform -translate-y-1/2 text-sm bg-gray-700 px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
          Busqueda de pacientes
        </span>
      </router-link>

      <!-- Botón Tipos -->
      <router-link to="/veterinarios/tipos" class="group relative">
        <font-awesome-icon
          :icon="['fas', 'file-medical']" size="2x"
          class="h-7 w-7 text-gray-400 group-hover:text-green-400 transition"
        />
        <span class="absolute right-12 top-1/2 transform -translate-y-1/2 text-sm bg-gray-700 px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
          Tipos de tratamientos
        </span>
      </router-link>

      <!-- Botón Historial -->
      <router-link to="/veterinarios/historial" class="group relative">
        <font-awesome-icon
          :icon="['fas', 'clock-rotate-left']" size="2x"
          class="h-7 w-7 text-gray-400 group-hover:text-green-400 transition"
        />
        <span class="absolute right-12 top-1/2 transform -translate-y-1/2 text-sm bg-gray-700 px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
          Historial de tratamientos
        </span>
      </router-link>

      <!-- Botón Rendimiento -->
      <router-link to="/veterinarios/rendimiento" class="group relative">
        <font-awesome-icon
          :icon="['fas', 'chart-line']" size="2x"
          class="h-7 w-7 text-gray-400 group-hover:text-green-400 transition"
        />
        <span class="absolute right-12 top-1/2 transform -translate-y-1/2 text-sm bg-gray-700 px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
          Rendimiento
        </span>
      </router-link>

      <!-- Botón Notificicaciones -->
      <router-link to="/veterinarios/notificaciones" class="group relative">
        <font-awesome-icon
          :icon="['fas', 'bell']" size="2x"
          class="h-7 w-7 text-gray-400 group-hover:text-green-400 transition"
        />
        <span class="absolute right-12 top-1/2 transform -translate-y-1/2 text-sm bg-gray-700 px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
          notificaciones
        </span>
      </router-link>

      <!-- Botón Usuario -->
      <router-link to="/veterinarios/perfil" class="group relative">
        <font-awesome-icon
          :icon="['fas', 'circle-user']" size="2x"
          class="h-7 w-7 text-gray-400 group-hover:text-green-400 transition"
        />
        <span class="absolute right-12 top-1/2 transform -translate-y-1/2 text-sm bg-gray-700 px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
          Perfil Usuario
        </span>
      </router-link>
    </nav>
  </div>
</template>

<script setup>
// No necesita lógica extra
</script>

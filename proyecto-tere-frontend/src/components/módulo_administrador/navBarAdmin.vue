<!-- src\components\módulo_administrador\navBarAdmin.vue -->
<template>
  <div class="flex h-screen">
    <!-- Navbar lateral izquierda -->
    <nav class="w-20 bg-gray-900 text-white flex flex-col items-center py-6 space-y-6 shadow-lg">
      <!-- Logo o ícono principal -->
      <div class="text-2xl font-bold">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path d="M12 4v16m8-8H4" />
        </svg>
      </div>

      <!-- Botón de Reportes -->
      <router-link to="/admin/reportes" class="group relative">
        <font-awesome-icon
            :icon="['fas', 'inbox']" size="2x" 
            class="h-7 w-7 text-gray-400 group-hover:text-blue-400 transition"
            />
        <span class="absolute left-12 top-1/2 transform -translate-y-1/2 text-sm bg-gray-700 px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
          Reportes
        </span>
      </router-link>

      <!-- Botón de Métricas -->
      <router-link to="/admin/metricas" class="group relative">
        <font-awesome-icon
            :icon="['fas', 'square-poll-vertical']" size="2x" 
            class="h-7 w-7 text-gray-400 group-hover:text-blue-400 transition"
            />
        <span class="absolute left-12 top-1/2 transform -translate-y-1/2 text-sm bg-gray-700 px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
          Métricas
        </span>
      </router-link>

      <!-- Botón de Auditorías -->
        <router-link to="/admin/auditorias" class="group relative">
        <font-awesome-icon
            :icon="['fas', 'address-book']" size="2x" 
            class="h-7 w-7 text-gray-400 group-hover:text-blue-400 transition"
            />
        <span class="absolute left-12 top-1/2 transform -translate-y-1/2 text-sm bg-gray-700 px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
            Auditorías
        </span>
        </router-link>
    </nav>

    <!-- Contenido del admin (zona de vistas) -->
    <main class="flex-1 bg-gray-100 p-6 overflow-y-auto">
      <router-view />
    </main>
  </div>
</template>

<script setup>
// No necesita lógica extra
</script>

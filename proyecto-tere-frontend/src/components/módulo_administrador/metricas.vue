<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Métricas del sistema</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Tarjeta: Usuarios registrados -->
      <div class="bg-white shadow rounded-xl p-6">
        <h2 class="text-sm text-gray-500">Usuarios registrados</h2>
        <p class="text-3xl font-semibold text-blue-500">1,234</p>
      </div>

      <!-- Tarjeta: Mascotas registradas -->
      <div class="bg-white shadow rounded-xl p-6">
        <h2 class="text-sm text-gray-500">Mascotas registradas</h2>
        <p class="text-3xl font-semibold text-green-500">842</p>
      </div>

      <!-- Tarjeta: Adopciones realizadas -->
      <div class="bg-white shadow rounded-xl p-6">
        <h2 class="text-sm text-gray-500">Adopciones realizadas</h2>
        <p class="text-3xl font-semibold text-purple-500">312</p>
      </div>

      <!-- Tarjeta: veterinarios registrados -->
      <div class="bg-white shadow rounded-xl p-6">
        <h2 class="text-sm text-gray-500">Veterinarios registrados</h2>
        <p class="text-3xl font-semibold text-red-500">112</p>
      </div>
    </div>

    <!-- Sección de gráfico o evolución (placeholder) -->
    <div class="mt-8 bg-white p-6 rounded-xl shadow">
      <h2 class="text-lg font-semibold text-gray-700 mb-4">Actividad mensual</h2>
      <div class="h-40 flex items-center justify-center text-gray-400 italic">
        (Gráfico de actividad aquí)
      </div>
    </div>
  </div>
</template>

<script setup>
// Futuro: podrías importar datos desde la store o API aquí
</script>

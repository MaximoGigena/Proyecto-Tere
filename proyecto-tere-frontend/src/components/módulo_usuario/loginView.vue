<template>
  <div class="h-screen w-screen flex items-center justify-center bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500">
    <!-- Card de login -->
    <div class="bg-white/10 backdrop-blur-xl rounded-3xl shadow-2xl p-8 w-[90%] max-w-md border border-white/20">
      <h1 class="text-3xl font-bold text-white mb-6 text-center">Iniciar Sesión</h1>

      <!-- Inputs -->
      <div class="space-y-4">
        <input
          type="email"
          placeholder="Correo electrónico"
          class="w-full px-4 py-3 rounded-xl bg-white/20 text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-pink-400"
        />
        <input
          type="password"
          placeholder="Contraseña"
          class="w-full px-4 py-3 rounded-xl bg-white/20 text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-pink-400"
        />
        <button
          class="w-full py-3 bg-pink-500 hover:bg-pink-600 transition rounded-xl text-white font-semibold shadow-lg"
        >
          Ingresar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
// Acá más adelante conectás con tu backend (Laravel, Firebase, etc.)
</script>

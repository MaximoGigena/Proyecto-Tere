<!-- views/VeterinarioPendiente.vue -->
<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 text-center p-6">
    <!-- Icono animado -->
    <div class="mb-6 animate-pulse">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-20 w-20 text-blue-500"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>

    <!-- Mensaje principal -->
    <h1 class="text-2xl font-bold text-gray-800 mb-2">
      Solicitud en revisión
    </h1>
    <p class="text-gray-600 max-w-md mb-8">
      Hemos recibido tu solicitud para registrarte como <span class="font-semibold">veterinario</span>. 
      Nuestro equipo revisará tu información y te notificaremos cuando tu cuenta esté aprobada.
    </p>

    <!-- Bloque de estado -->
    <div class="bg-white shadow-lg rounded-2xl p-6 w-full max-w-md">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-500">Estado actual:</span>
        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-600">
          Pendiente
        </span>
      </div>
    </div>

    <!-- Opcional: botón de cerrar sesión -->
    <button
      @click="logout"
      class="mt-10 px-5 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-xl shadow"
    >
      Volver al inicio de sesión
    </button>
  </div>
</template>

<script>
export default {
  name: "VeterinarioPendiente",
  methods: {
    logout() {
      // Acá va tu lógica para cerrar sesión
      this.$router.push("/");
    },
  },
};
</script>

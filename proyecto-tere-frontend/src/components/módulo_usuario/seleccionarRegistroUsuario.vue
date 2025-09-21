<template>
  <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-b from-green-100 to-white px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-12 text-center">
      ¿Cómo querés registrarte?
    </h1>

    <div class="flex flex-col md:flex-row gap-8 w-full max-w-3xl justify-center">
      <!-- Usuario -->
      <button
        @click="registrarComo('usuario')"
        class="flex flex-col items-center justify-center bg-green-500 hover:bg-green-600 text-white font-semibold py-8 px-10 rounded-2xl shadow-xl transition duration-300 text-2xl w-full md:w-1/2"
      >
        <i class="fas fa-user text-5xl mb-4"></i>
        Soy Usuario
      </button>

      <!-- Veterinario -->
      <button
        @click="registrarComo('veterinario')"
        class="flex flex-col items-center justify-center bg-blue-500 hover:bg-blue-600 text-white font-semibold py-8 px-10 rounded-2xl shadow-xl transition duration-300 text-2xl w-full md:w-1/2"
      >
        <i class="fas fa-stethoscope text-5xl mb-4"></i>
        Soy Veterinario
      </button>
    </div>
  </div>
</template>


<script setup>
import { useRoute, useRouter } from 'vue-router'
import { onMounted } from 'vue'

const route = useRoute()

onMounted(() => {
  const token = route.query.token
  if (token) {
    localStorage.setItem('auth_token', token)
  }
})

const router = useRouter()

function registrarComo(tipo) {
  if (tipo === 'usuario') {
    router.push('/registro/usuario')
  } else if (tipo === 'veterinario') {
    router.push('/registro/veterinario')
  }
}
</script>

<style scoped>
</style>

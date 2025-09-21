<template>
  <div class="min-h-screen bg-white text-gray-800 flex flex-col">
    <!-- Encabezado -->
    <section class="bg-gradient-to-r from-teal-500 to-green-300 py-16 px-6 text-center">
      <router-link @click="volverOInicio()" to="/" class="absolute top-4 left-4">
          <img 
            src="@/assets/Logo_Pagina_Oscura.png" 
            alt="Logo TERE"
            class="h-60 w-auto hover:opacity-80 transition -mt-16"
          />
        </router-link>
      <h1 class="text-4xl md:text-5xl font-bold text-white">Contacto</h1>
      <p class="mt-4 text-lg md:text-xl text-gray-50">¿Tenés preguntas o querés sumarte al proyecto TERE?</p>
    </section>

    <!-- Sección de contacto -->
    <section class="py-16 px-6 max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12">
      <!-- Información -->
      <div class="space-y-6">
        <h2 class="text-2xl font-semibold">Hablemos</h2>
        <p class="text-gray-600">
          Si querés colaborar, reportar un problema o conocer más sobre el modelo TERE, podés escribirnos directamente o completar el formulario.
        </p>
        <div class="space-y-3 text-center">
        <!-- Email -->
        <div class="flex items-center justify-center gap-2">
            <span class="text-teal-600 text-xl">📧</span>
            <a href="mailto:proyectoterearg@gmail.com" class="text-gray-700 hover:underline">
                proyectoterearg@gmail.com
            </a>
        </div>

        <!-- Redes Sociales -->
        <div class="flex gap-4 justify-center mt-4 text-xl">
            <a href="https://www.instagram.com/proyecto_tere_2025/" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-instagram-square hover:text-emerald-800 hover:scale-125 focus:scale-125 transition-transform duration-200"></i>
            </a>
            <a href="https://www.tiktok.com/@proyecto.tere" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-tiktok hover:text-emerald-800 hover:scale-125 focus:scale-125 transition-transform duration-200"></i>
            </a>
            <a href="https://www.youtube.com/@proyectotere" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-youtube hover:text-emerald-800 hover:scale-125 focus:scale-125 transition-transform duration-200"></i>
            </a>
            <a href="https://x.com/Tere_Project" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-x-twitter hover:text-emerald-800 hover:scale-125 focus:scale-125 transition-transform duration-200"></i>
            </a>
            <a href="#" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-facebook hover:text-emerald-800 hover:scale-125 focus:scale-125 transition-transform duration-200"></i>
            </a>
            <a href="https://www.reddit.com/r/ProyectoTere/" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-reddit-alien hover:text-emerald-800 hover:scale-125 focus:scale-125 transition-transform duration-200"></i>
            </a>
        </div>
</div>

      </div>

      <!-- Formulario -->
      <form @submit.prevent="handleSubmit" class="space-y-6 bg-gray-50 p-6 rounded-2xl shadow-md">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
          <input
            v-model="form.name"
            type="text"
            id="name"
            class="mt-1 w-full border border-gray-300 rounded-md p-2"
            required
          />
        </div>
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
          <input
            v-model="form.email"
            type="email"
            id="email"
            class="mt-1 w-full border border-gray-300 rounded-md p-2"
            required
          />
        </div>
        <div>
          <label for="message" class="block text-sm font-medium text-gray-700">Mensaje</label>
          <textarea
            v-model="form.message"
            id="message"
            rows="4"
            class="mt-1 w-full border border-gray-300 rounded-md p-2"
            required
          ></textarea>
        </div>
        <button
          type="submit"
          class="bg-teal-600 text-white py-2 px-4 rounded-md hover:bg-teal-700 transition"
        >
          Enviar mensaje
        </button>
        <p v-if="enviado" class="text-green-600 font-medium">¡Mensaje enviado correctamente!</p>
      </form>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const form = ref({
  name: '',
  email: '',
  message: '',
});

const enviado = ref(false);

function handleSubmit() {
  // Acá iría la lógica real (enviar a backend, API, etc.)
  console.log('Formulario enviado:', form.value);
  enviado.value = true;

  // Resetear formulario luego de enviar
  form.value = {
    name: '',
    email: '',
    message: '',
  };

  setTimeout(() => (enviado.value = false), 4000); // ocultar mensaje luego de 4 seg
}
</script>

<template>
  <div class="min-h-screen bg-white text-gray-800 flex flex-col">
    <!-- Encabezado -->
    <section class="bg-gradient-to-r from-teal-100 to-green-200 py-16 px-6 text-center">
      <h1 class="text-4xl md:text-5xl font-bold text-teal-700">Contacto</h1>
      <p class="mt-4 text-lg md:text-xl text-gray-700">¿Tenés preguntas o querés sumarte al proyecto TERE?</p>
    </section>

    <!-- Sección de contacto -->
    <section class="py-16 px-6 max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12">
      <!-- Información -->
      <div class="space-y-6">
        <h2 class="text-2xl font-semibold">Hablemos</h2>
        <p class="text-gray-600">
          Si querés colaborar, reportar un problema o conocer más sobre el modelo TERE, podés escribirnos directamente o completar el formulario.
        </p>
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <span class="text-teal-600 text-xl">📧</span>
            <a href="mailto:equipo@tere.org" class="text-gray-700 hover:underline">equipo@tere.org</a>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-teal-600 text-xl">🌐</span>
            <a href="https://www.tere.org" class="text-gray-700 hover:underline" target="_blank">www.tere.org</a>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-teal-600 text-xl">🐦</span>
            <a href="https://twitter.com/tere_org" class="text-gray-700 hover:underline" target="_blank">@tere_org</a>
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

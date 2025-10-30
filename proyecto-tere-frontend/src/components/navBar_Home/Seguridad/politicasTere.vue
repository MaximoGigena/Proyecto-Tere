<template>
  <div class="min-h-screen bg-white text-gray-800 flex flex-col">
    <!-- Encabezado -->
    <section class="bg-gradient-to-r from-teal-500 to-green-300 py-16 px-6 text-center">
      <!-- Logo en esquina -->
        <router-link @click="volverOInicio()" to="/" class="absolute top-4 left-4">
          <img 
            src="@/assets/Logo_Pagina_Oscura.png" 
            alt="Logo TERE"
            class="h-60 w-auto hover:opacity-80 transition -mt-16"
          />
        </router-link>
      <h1 class="text-4xl md:text-5xl font-bold text-white">Políticas del Proyecto TERE</h1>
      <p class="mt-4 text-lg md:text-xl text-gray-50">Comprometidos con el bienestar animal, la privacidad y la legalidad</p>
    </section>

    <!-- Contenido -->
    <section class="py-12 px-6 max-w-4xl mx-auto space-y-10">
      <!-- Introducción -->
      <article>
        <h2 class="text-2xl font-semibold text-teal-700 mb-2">1. Propósito del sistema</h2>
        <p class="text-gray-700">
          TERE (Tecnología para la Equidad en la Relación con los Animales) es una plataforma digital que busca fomentar la adopción
          responsable, garantizar la trazabilidad y proteger los derechos tanto de los animales como de los humanos involucrados.
        </p>
      </article>

      <!-- Protección de Datos -->
      <article>
        <h2 class="text-2xl font-semibold text-teal-700 mb-2">2. Protección de datos personales</h2>
        <p class="text-gray-700">
          Toda la información personal de los usuarios es gestionada conforme a la Ley 25.326 (Ley de Protección de los Datos Personales).
          Los datos no se comparten con terceros sin consentimiento y se usan únicamente para el funcionamiento del sistema.
        </p>
      </article>

      <!-- Tenencia y Adopciones -->
      <article>
        <h2 class="text-2xl font-semibold text-teal-700 mb-2">3. Políticas de tenencia y adopción</h2>
        <p class="text-gray-700">
          Las adopciones gestionadas por TERE se realizan bajo los principios de tenencia responsable. El sistema evalúa
          compatibilidad, historial de usuarios, y registra cada proceso para garantizar legalidad y transparencia.
        </p>
        <ul class="list-disc list-inside text-gray-700 mt-2">
          <li>No se permite la entrega de animales con fines comerciales.</li>
          <li>Se exige vacunación, identificación y seguimiento post-adopción.</li>
          <li>Todo abandono o maltrato será reportado automáticamente.</li>
        </ul>
      </article>

      <!-- Legalidad -->
      <article>
        <h2 class="text-2xl font-semibold text-teal-700 mb-2">4. Marco legal</h2>
        <p class="text-gray-700">
          El modelo TERE se encuentra enmarcado en las siguientes normativas nacionales:
        </p>
        <ul class="list-disc list-inside text-gray-700 mt-2">
          <li>Ley 22.421 de Conservación de la Fauna.</li>
          <li>Ley 14.346 de Protección Animal.</li>
          <li>Ley 25.326 de Protección de Datos Personales.</li>
          <li>Reglamentaciones de SENASA para trazabilidad y control sanitario.</li>
        </ul>
      </article>

      <!-- Responsabilidades del Usuario -->
      <article>
        <h2 class="text-2xl font-semibold text-teal-700 mb-2">5. Responsabilidades del usuario</h2>
        <p class="text-gray-700">
          Los usuarios del sistema deben:
        </p>
        <ul class="list-disc list-inside text-gray-700 mt-2">
          <li>Brindar información verdadera y completa.</li>
          <li>Tratar con respeto a otros usuarios y animales.</li>
          <li>Respetar los tiempos y condiciones de adopción.</li>
          <li>Actualizar el estado de las mascotas vinculadas.</li>
        </ul>
      </article>

      <!-- Modificaciones -->
      <article>
        <h2 class="text-2xl font-semibold text-teal-700 mb-2">6. Modificaciones a estas políticas</h2>
        <p class="text-gray-700">
          Las políticas pueden actualizarse para adaptarse a nuevas leyes o funcionalidades del sistema. Los cambios se
          notificarán mediante la app y se considerarán aceptados al continuar su uso.
        </p>
      </article>
    </section>

    <!-- CTA Final -->
    <section class="bg-teal-600 text-white py-12 px-6 text-center">
      <h2 class="text-2xl font-bold mb-4">¿Tenés dudas sobre estas políticas?</h2>
      <router-link
        to="/Contactanos"
        class="inline-block bg-white text-teal-700 font-semibold py-2 px-6 rounded-full hover:bg-gray-100 transition"
      >
        Contactanos
      </router-link>
    </section>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
const router = useRouter()

function volverOInicio() {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/')
  }
}
</script>
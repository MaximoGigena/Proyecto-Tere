<template>
  <div class="min-h-screen bg-white text-gray-800 flex flex-col">
    <!-- Hero -->
    <section class="bg-gradient-to-r from-green-100 to-teal-200 py-16 px-6 text-center">
      <h1 class="text-4xl md:text-5xl font-bold text-teal-700">Modelo TERE</h1>
      <p class="mt-4 text-lg md:text-xl text-gray-700">Tecnología para la Equidad en la Relación con los Animales</p>
    </section>

    <!-- ¿Qué es TERE? -->
    <section class="py-12 px-6 max-w-4xl mx-auto text-center">
      <h2 class="text-3xl font-semibold mb-4">¿Qué es TERE?</h2>
      <p class="text-lg text-gray-600">
        TERE es un sistema inteligente que gestiona procesos de adopción, tenencia y protección animal,
        integrando datos legales, sanitarios y sociales. Actúa como intermediario tecnológico para garantizar la
        trazabilidad, transparencia y bienestar de las mascotas.
      </p>
    </section>

    <!-- Componentes del Modelo -->
    <section class="bg-gray-50 py-12 px-6">
      <h2 class="text-3xl font-semibold text-center mb-10">Componentes del Modelo</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
        <CardTere title="Usuarios" icon="👤">
          Registro, autenticación y roles diferenciados (adoptantes, protectores, organizaciones, veterinarios).
        </CardTere>
        <CardTere title="Mascotas" icon="🐶">
          Perfil completo, historial sanitario, ubicación y estado de adopción.
        </CardTere>
        <CardTere title="Adopciones" icon="🏡">
          Proceso guiado y controlado legalmente para la entrega y seguimiento de mascotas.
        </CardTere>
        <CardTere title="Inteligencia" icon="🧠">
          Recomendaciones automáticas, alertas de riesgo, coincidencias y evaluación de compatibilidad.
        </CardTere>
        <CardTere title="Marco Legal" icon="⚖️">
          Basado en Ley 22.421, Ley 14.346, Ley 25.326 y articulación con SENASA y autoridades.
        </CardTere>
        <CardTere title="Trazabilidad" icon="📍">
          Registro de eventos, ubicación, vacunación, tenencia y cambios de estado de la mascota.
        </CardTere>
      </div>
    </section>

    <!-- Proceso Inteligente -->
    <section class="py-16 px-6 max-w-5xl mx-auto">
      <h2 class="text-3xl font-semibold text-center mb-10">¿Cómo Funciona TERE?</h2>
      <ol class="space-y-6 border-l-4 border-teal-500 pl-6">
        <li v-for="(step, i) in pasos" :key="i" class="relative">
          <div class="absolute -left-3 w-6 h-6 bg-teal-500 rounded-full border-4 border-white"></div>
          <h3 class="text-xl font-medium">{{ step.title }}</h3>
          <p class="text-gray-600">{{ step.desc }}</p>
        </li>
      </ol>
    </section>

    <!-- CTA -->
    <section class="bg-teal-600 py-12 px-6 text-white text-center">
      <h2 class="text-2xl font-bold mb-4">¿Querés conocer el sistema en acción?</h2>
      <router-link
        to="/explorar"
        class="inline-block bg-white text-teal-700 font-semibold py-2 px-6 rounded-full hover:bg-gray-100 transition"
      >
        Explorar TERE
      </router-link>
    </section>
  </div>
</template>

<script setup>
const pasos = [
  {
    title: "1. Registro y perfilado",
    desc: "Los usuarios ingresan al sistema y completan sus datos. El sistema identifica el rol automáticamente.",
  },
  {
    title: "2. Vinculación de mascotas",
    desc: "Cada mascota cuenta con un perfil con información legal, sanitaria y de comportamiento.",
  },
  {
    title: "3. Adopción responsable",
    desc: "Las adopciones pasan por filtros automáticos y validaciones legales para asegurar su legalidad y bienestar.",
  },
  {
    title: "4. Seguimiento y alertas",
    desc: "El sistema monitorea la tenencia y emite alertas si detecta riesgos, abandonos o cambios indebidos.",
  },
  {
    title: "5. Inteligencia aplicada",
    desc: "Módulos inteligentes optimizan adopciones, identifican perfiles compatibles y anticipan conflictos.",
  },
];
</script>

<!-- COMPONENTE CARD -->
<script>
export default {
  components: {
    CardTere: {
      props: ['title', 'icon'],
      template: `
        <div class="bg-white rounded-2xl shadow-md p-6 hover:shadow-lg transition">
          <div class="text-4xl mb-4">{{ icon }}</div>
          <h3 class="text-xl font-semibold mb-2">{{ title }}</h3>
          <p class="text-gray-600"><slot /></p>
        </div>
      `,
    },
  },
};
</script>

<style scoped>

</style>

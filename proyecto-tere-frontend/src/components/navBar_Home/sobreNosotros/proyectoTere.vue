<template>
  <div class="min-h-screen bg-white text-gray-800 flex flex-col">
    <!-- Hero -->
    <section class="bg-gradient-to-r from-teal-500 to-green-300 py-8 px-6 text-center">
       <router-link @click="volverOInicio()" to="/" class="absolute top-4 left-4">
          <img 
            src="@/assets/Logo_Pagina_Oscura.png" 
            alt="Logo TERE"
            class="h-60 w-auto hover:opacity-80 transition -mt-16"
          />
        </router-link>
          <h1 class="text-4xl md:text-5xl font-bold text-white">Modelo TERE</h1>

      <p class="mt-2 text-lg md:text-xl text-gray-50">
          Un sistema P2P para facilitar la adopción, gestión y seguimiento descentralizado de nuestras mascotas.
      </p>
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

    <!-- Motivo TERE -->
    <section class="bg-gray-50 py-12 px-6">
    <h2 class="text-3xl font-semibold text-teal-700 text-center mb-10">La Inspiración detrás de TERE</h2>

    <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
        <!-- Imagen de la perrita Tere -->
        <div class="flex justify-center">
            <img 
                src="/src/assets/la tere tere2.png" 
                alt="Foto de Tere, la perrita que inspiró este proyecto" 
                class="rounded-lg shadow-lg w-80 h-auto object-cover"
            />
        </div>

        <!-- Descripción emotiva -->
        <div>
            <h3 class="text-2xl font-bold text-teal-700 mb-4">¿Quién fue Tere?</h3>
            <p class="text-gray-700 leading-relaxed">
                Tere fue una perrita rescatada que, a pesar de sus inicios difíciles, demostró cuánto puede cambiar una vida con el amor y cuidado de otra vida. 
            </p>

            <p class="mt-4 text-gray-700 leading-relaxed">
                Tere un día fue dada en adopción, este sistema es mi disculpa hacia ella.
                Este sistema nace de su historia y busca ofrecer a todas las mascotas la oportunidad que ella tuvo: ser vista, cuidada y acompañada en cada etapa de su vida.
            </p>

            <p class="mt-4 text-gray-700 leading-relaxed">
                Cada función del modelo TERE refleja la empatía, el compromiso y la responsabilidad que inspiró su historia.
            </p>
        </div>
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
        to="/"
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
    desc: "Cada mascota cuenta con un perfil con información legal, sanitaria y social.",
  },
  {
    title: "3. Adopción responsable",
    desc: "Las adopciones pasan por filtros automáticos y validaciones entre usuarios.",
  },
  {
    title: "4. Seguimiento y alertas",
    desc: "El sistema monitorea la tenencia y emite alertas de vacunación y proximos tratamientos.",
  },
  {
    title: "5. Inteligencia aplicada",
    desc: "Módulos inteligentes que optimizan adopciones y identifican perfiles compatibles.",
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

function volverOInicio() {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/')
  }
}
</script>

<style scoped>

</style>

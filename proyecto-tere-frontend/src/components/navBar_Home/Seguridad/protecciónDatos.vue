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
      <h1 class="text-4xl md:text-5xl font-bold text-white">Protección de Datos</h1>
      <p class="mt-4 text-lg md:text-xl text-gray-50">
        Conocé cómo el proyecto TERE protege tu información personal.
      </p>
    </section>

    <!-- Contenido principal -->
    <section class="py-12 px-6 max-w-4xl mx-auto space-y-10">
      <!-- Fundamento legal -->
      <article>
        <h2 class="text-2xl font-semibold text-emerald-700 mb-2">1. Normativa aplicable</h2>
        <p class="text-gray-700">
          El tratamiento de datos personales en TERE se ajusta a lo establecido por la 
          <strong>Ley Nacional 25.326 de Protección de Datos Personales</strong>. Esta normativa garantiza la confidencialidad,
          integridad y disponibilidad de la información brindada por los usuarios.
        </p>
      </article>

      <!-- Finalidad -->
      <article>
        <h2 class="text-2xl font-semibold text-emerald-700 mb-2">2. Finalidad del tratamiento</h2>
        <p class="text-gray-700">
          Los datos personales recolectados son utilizados exclusivamente para:
        </p>
        <ul class="list-disc list-inside text-gray-700 mt-2">
          <li>Gestionar perfiles de usuario, adopciones y seguimiento de animales.</li>
          <li>Validar identidades y verificar compatibilidades para la adopción.</li>
          <li>Generar estadísticas anónimas para mejorar el sistema.</li>
        </ul>
      </article>

      <!-- Derechos del titular -->
      <article>
        <h2 class="text-2xl font-semibold text-emerald-700 mb-2">3. Derechos de los usuarios</h2>
        <p class="text-gray-700">
          En cualquier momento, podés ejercer tus derechos de:
        </p>
        <ul class="list-disc list-inside text-gray-700 mt-2">
          <li><strong>Acceso:</strong> Consultar qué datos tuyos están almacenados.</li>
          <li><strong>Rectificación:</strong> Corregir datos incorrectos o desactualizados.</li>
          <li><strong>Cancelación:</strong> Eliminar tu cuenta y tus datos, salvo los exigidos por ley.</li>
          <li><strong>Oposición:</strong> Solicitar que tus datos no sean usados con ciertos fines.</li>
        </ul>
        <p class="mt-2">
          Para ejercerlos, escribinos a: <a href="mailto:datos@tere.org" class="text-emerald-600 underline">datos@tere.org</a>
        </p>
      </article>

      <!-- Seguridad -->
      <article>
        <h2 class="text-2xl font-semibold text-emerald-700 mb-2">4. Seguridad y confidencialidad</h2>
        <p class="text-gray-700">
          TERE aplica medidas técnicas y organizativas para proteger los datos personales, incluyendo:
        </p>
        <ul class="list-disc list-inside text-gray-700 mt-2">
          <li>Cifrado en tránsito y en reposo.</li>
          <li>Control de accesos con autenticación y registros de actividad.</li>
          <li>Auditorías periódicas y protocolos de recuperación ante incidentes.</li>
        </ul>
      </article>

      <!-- Cesión a terceros -->
      <article>
        <h2 class="text-2xl font-semibold text-emerald-700 mb-2">5. Cesión y uso compartido</h2>
        <p class="text-gray-700">
          Los datos no serán compartidos con terceros salvo en los siguientes casos:
        </p>
        <ul class="list-disc list-inside text-gray-700 mt-2">
          <li>Autoridades sanitarias como SENASA, bajo acuerdos de trazabilidad.</li>
          <li>Organizaciones de adopción o cuidado animal asociadas al sistema.</li>
          <li>Por requerimiento legal o judicial fundado.</li>
        </ul>
      </article>

      <!-- Cambios en la política -->
      <article>
        <h2 class="text-2xl font-semibold text-emerald-700 mb-2">6. Cambios en esta política</h2>
        <p class="text-gray-700">
          Cualquier modificación será comunicada mediante la plataforma. El uso continuado del sistema implica aceptación de las actualizaciones.
        </p>
      </article>
    </section>

    <!-- Contacto final -->
    <section class="bg-emerald-600 text-white py-12 px-6 text-center">
      <h2 class="text-2xl font-bold mb-4">¿Tenés dudas sobre tus datos personales?</h2>
      <router-link
        to="/Contactanos"
        class="inline-block bg-white text-emerald-700 font-semibold py-2 px-6 rounded-full hover:bg-gray-100 transition"
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
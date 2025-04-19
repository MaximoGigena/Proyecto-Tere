<template>
  <div>
    <!-- Hero Section -->
    <div class="hero relative h-screen text-white" :style="{ backgroundImage: `url(${Imagenfinal})`, backgroundSize: 'cover', backgroundPosition: 'center' }">
      <header class="fixed w-full top-0 z-50 flex justify-between items-center px-16 py-4 h-24 bg-black/30">
        <div class="flex items-center">
          <img :src="logo" alt="Logo de Tere" class="h-16 object-contain scale-350" />
        </div>
        <nav class="flex space-x-5 text-white font-medium">
          <a href="#" class="hover:text-gray-300 transition">Información</a>
          <a href="#" class="hover:text-gray-300 transition">Seguridad</a>
          <router-link to="/encuentros" class="text-blue-500 hover:underline">
            Ir a Encuentros
          </router-link>
        </nav>
      </header>

      <div class="absolute top-1/2 left-10 transform -translate-y-1/2 text-left z-10">
        <h1 class="text-5xl mb-4 font-serif drop-shadow-xl">Adopción Responsable</h1>
        <div class="flex justify-center gap-5 flex-wrap">
          <a href="#" class="bg-white text-black hover:bg-gray-200 px-6 py-3 rounded-full font-bold transition" @click.prevent="abrirModal('Iniciar sesión')">Iniciar sesión</a>
          <a href="#" class="bg-gradient-to-r from-emerald-200 to-emerald-700 text-white hover:from-emerald-600 hover:to-emerald-700 px-6 py-3 rounded-full font-bold transition" @click.prevent="abrirModal('Crear cuenta')">Crear cuenta</a>
        </div>
      </div>
    </div>

    <!-- Info Section -->
    <div ref="infoSection" :class="['transition-all duration-1000 ease-in-out px-16 py-5 bg-[#f9f9f9] text-[#333]', { 'opacity-100 translate-y-0': seVeInfo, 'opacity-0 translate-y-24': !seVeInfo }]">
      <!-- Recuadros -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
        <!-- Recuadro 1 -->
        <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-lg h-full">
          <h3 class="text-xl font-semibold mb-2">🐾 Adopción y Seguimiento 🐾</h3>
          <div class="h-[2px] bg-gray-300 mb-3 w-48"></div>
          <p>
            Tere abarca todo el ciclo de vida de la relación humano-animal, desde la adopción, el seguimiento legal, hasta la tenencia responsable.
          </p>
        </div>

      <!-- Recuadro 2 -->
      <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg h-full">
        <h3 class="text-xl font-semibold mb-2">🐈‍⬛ Confianza total en cada paso 🐈‍⬛</h3>
        <div class="h-[2px] bg-gray-300 mb-3 w-48"></div>
        <p>
          cada adopción se construye con transparencia. Mostramos perfiles verificados, historiales claros y conexiones seguras entre usuarios. garantizamos que cada encuentro sea auténtico, confiable y alineado con el bienestar de las mascotas. 
        </p>
      </div>

      <!-- Recuadro 3 -->
      <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg h-full">
        <h3 class="text-xl font-semibold mb-2">🦮 Bienestar e integridad animal 🦮</h3>
        <div class="h-[2px] bg-gray-300 mb-3 w-48"></div>
        <p>
          El bienestar de cada animal es prioridad. Asegurarte de tener tiempo, espacio y paciencia.
        </p>
      </div>
    </div>
    </div>

    <footer class="bg-[#f8f8f8] px-16 py-10">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-8 text-sm">
        
        <!-- Columna Legal -->
        <div>
          <h4 class="font-bold mb-3">Legal</h4>
          <ul class="space-y-2">
            <li><a href="#">Privacy</a></li>
            <li><a href="#">Consumer Health Data</a></li>
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Terms</a></li>
            <li><a href="#">Cookie Policy</a></li>
            <li><a href="#">Intellectual Property</a></li>
          </ul>
        </div>

        <!-- Columna Careers -->
        <div>
          <h4 class="font-bold mb-3">Careers</h4>
          <ul class="space-y-2">
            <li><a href="#">Careers Portal</a></li>
            <li><a href="#">Tech Blog</a></li>
          </ul>
        </div>

        <!-- Columna Social -->
        <div>
          <h4 class="font-bold mb-3">Social</h4>
          <div class="flex gap-3 text-xl">
            <!-- Iconos de ejemplo con HeroIcons, FontAwesome o similares -->
            <a href="https://www.instagram.com/proyecto_tere_2025/"><i class="fab fa-instagram-square hover:text-emerald-800 transition"></i></a>
            <a href="#"><i class="fab fa-tiktok hover:text-emerald-800 transition"></i></a>
            <a href="#"><i class="fab fa-youtube hover:text-emerald-800 transition"></i></a>
            <a href="https://x.com/Tere_Project"><i class="fab fa-x-twitter hover:text-emerald-800 transition"></i></a>
            <a href="#"><i class="fab fa-facebook hover:text-emerald-800 transition"></i></a>
            <a href="https://www.reddit.com/r/ProyectoTere/"><i class="fab fa-reddit-alien hover:text-emerald-800 transition"></i></a>
          </div>
        </div>

        <!-- Columna Extra -->
        <div>
          <h4 class="font-bold mb-3">FAQ</h4>
          <ul class="space-y-2">
            <li><a href="#">Destinations</a></li>
            <li><a href="#">Press Room</a></li>
            <li><a href="#">Contact</a></li>
            <li><a href="#">Promo Code</a></li>
          </ul>
        </div>
        
      </div>

      <!-- Línea inferior -->
      <div class="border-t border-gray-700 mt-4"></div>
      <h1>© 2025 Tere, All Rights Reserved.</h1>
    </footer>

    <!-- Modal -->
    <div v-show="modalVisible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="w-full max-w-sm p-6 bg-white rounded-lg shadow-md relative">
        <button @click="cerrarModal" class="absolute top-2 right-3 text-gray-600 hover:text-black text-4xl">&times;</button>
        <div class="flex flex-col items-center">
          <img :src="fondo" alt="Logo Tere" class="w-40 mb-4" />
          <p class="text-3xl text-blue-400 font-bold text-center">{{ modalTitulo }}</p>
        </div>
        <div class="mt-6 space-y-4">
          <button class="flex items-center justify-center w-full px-4 py-3 border border-gray-300 rounded-full bg-white">
            <img :src="telefono" class="w-6 h-6 mr-2" /> Continuar con móvil
          </button>
          <button class="flex items-center justify-center w-full px-4 py-3 border border-gray-300 rounded-full bg-white">
            <img :src="correo" class="w-6 h-6 mr-2" /> Continuar con correo
          </button>
          <button class="bg-[#1877f2] text-white w-full px-4 py-3 rounded-full flex items-center justify-center">
            <i class="fab fa-facebook text-xl"></i>&nbsp;&nbsp;Continuar con Facebook
          </button>
          <button class="bg-black text-white w-full px-4 py-3 rounded-full flex items-center justify-center">
            <i class="fab fa-google text-xl"></i>&nbsp;&nbsp;Continuar con Google
          </button>
        </div>
        <p class="mt-4 text-xs text-gray-500 text-center">
          <a href="#" class="text-blue-600 hover:underline">¿No puedes entrar a tu cuenta?</a>
        </p>
        <p class="mt-4 text-xs text-gray-500 text-center">Nunca compartiremos nada sin tu permiso</p>
        <div class="mt-4 text-xs text-center text-gray-500">
          Al registrarte, aceptas nuestras <a href="#" class="text-blue-600 hover:underline">Condiciones de uso</a>.<br />
          Consulta nuestra <a href="#" class="text-blue-600 hover:underline">Política de privacidad</a>.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import logo from '@/assets/Logo_Pagina_Oscura.png'
import fondo from '@/assets/fondodecadapagina.png'
import telefono from '@/assets/684853.png'
import correo from '@/assets/95645.png'
import Imagenfinal from '@/assets/Imagen final.jpg'

const modalVisible = ref(false)
const modalTitulo = ref('Iniciar sesión')
const seVeInfo = ref(false)
const infoSection = ref(null)

function abrirModal(titulo) {
  modalVisible.value = true
  modalTitulo.value = titulo
}

function cerrarModal() {
  modalVisible.value = false
}

onMounted(() => {
  window.addEventListener('scroll', () => {
    const triggerPoint = window.innerHeight * 0.7
    if (infoSection.value && infoSection.value.getBoundingClientRect().top < triggerPoint) {
      seVeInfo.value = true
    }
  })
})
</script>

<style scoped>
/* convertido todo a tailwind en el template */
</style>

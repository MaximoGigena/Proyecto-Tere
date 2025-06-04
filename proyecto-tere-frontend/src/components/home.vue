<template>
  <div class="h-screen">
    <!-- Hero Section -->
    <div class="hero h-screen text-white" :style="{ backgroundImage: `url(${Imagenfinal})`, backgroundSize: 'cover', backgroundPosition: 'center' }">
      <header class="absolute w-full top-0  flex justify-between items-center px-16 py-4 h-24 bg-black/10">
        <div class="flex items-center mr-10">
          <img :src="logo" alt="Logo de Tere" class="h-16 object-contain scale-300 mt-2" />
        </div>
        <nav class="flex items-center space-x-5 text-white font-medium flex-grow ml-10">
          <div class="relative group">
           <!-- MENU Sobre Nosotros -->
              <button class="hover:text-green-300 transition">
                Sobre Nosotros
               </button>
              <!-- MENU oculto -->
              <div class="absolute left-0 hidden group-hover:block bg-black/10 text-white mt-0 rounded shadow-md py-2 w-48 z-10">
                 <router-link to="/ProyectoTere" class="group relative flex items-center px-4 py-2 pl-8 hover:text-green-300 hover:underline">  
                  <svg xmlns="http://www.w3.org/2000/svg"
                      class="w-4 h-4 text-green-400 absolute left-2 top-1/2 -translate-y-1/2 opacity-0 scale-75 group-hover:opacity-100 group-hover:scale-100 transition-all duration-200"
                      fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 18.657l-6.828-6.829a4 4 0 010-5.656z" />
                  </svg>
                  <span>Proyecto tere</span>
                </router-link>

                <router-link to="/Contactanos" class="group relative flex items-center px-4 py-2 pl-8 hover:text-green-300 hover:underline">  
                  <svg xmlns="http://www.w3.org/2000/svg"
                      class="w-4 h-4 text-green-400 absolute left-2 top-1/2 -translate-y-1/2 opacity-0 scale-75 group-hover:opacity-100 group-hover:scale-100 transition-all duration-200"
                      fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 18.657l-6.828-6.829a4 4 0 010-5.656z" />
                  </svg>
                  <span>Contactanos</span>
                </router-link>
              </div>
          </div>
           <!-- MENU DE SEGURIDAD -->
            <div class="relative group">
              <button class="hover:text-green-300 transition">
                Seguridad
              </button>
              <!-- MENU OCULTO -->
              <div class="absolute left-0 hidden group-hover:block bg-black/10 text-white mt-0 rounded shadow-md py-2 w-48 z-10">
                <router-link to="/Politicas" class="group relative flex items-center px-4 py-2 pl-8 hover:text-green-300 hover:underline">  
                  <svg xmlns="http://www.w3.org/2000/svg"
                      class="w-4 h-4 text-green-400 absolute left-2 top-1/2 -translate-y-1/2 opacity-0 scale-75 group-hover:opacity-100 group-hover:scale-100 transition-all duration-200"
                      fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 18.657l-6.828-6.829a4 4 0 010-5.656z" />
                  </svg>
                  <span>Politicas</span>
                </router-link>


                <router-link to="/ProtecciónDatos" class="group relative flex items-center px-4 py-2 pl-8 hover:text-green-300 hover:underline">  
                  <svg xmlns="http://www.w3.org/2000/svg"
                      class="w-4 h-4 text-green-400 absolute left-2 top-1/2 -translate-y-1/2 opacity-0 scale-75 group-hover:opacity-100 group-hover:scale-100 transition-all duration-200"
                      fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 18.657l-6.828-6.829a4 4 0 010-5.656z" />
                  </svg>
                  <span>Protección de Datos</span>
                </router-link>
              </div>
            </div> 

            <!-- MENU DE APOYO -->
            <div class="relative group">
              <button class="hover:text-green-300 transition">
                Apoyanos
              </button>
              <!-- MENU OCULTO -->
              <div class="absolute left-0 hidden group-hover:block bg-black/10 text-white mt-0 rounded shadow-md py-2 w-48 z-10">
                <router-link to="/Donaciones" class="group relative flex items-center px-4 py-2 pl-8 hover:text-green-300 hover:underline">  
                  <svg xmlns="http://www.w3.org/2000/svg"
                      class="w-4 h-4 text-green-400 absolute left-2 top-1/2 -translate-y-1/2 opacity-0 scale-75 group-hover:opacity-100 group-hover:scale-100 transition-all duration-200"
                      fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 18.657l-6.828-6.829a4 4 0 010-5.656z" />
                  </svg>
                  <span>Donaciones</span>
                </router-link>


                <router-link to="/Colaboradores" class="group relative flex items-center px-4 py-2 pl-8 hover:text-green-300 hover:underline">  
                  <svg xmlns="http://www.w3.org/2000/svg"
                      class="w-4 h-4 text-green-400 absolute left-2 top-1/2 -translate-y-1/2 opacity-0 scale-75 group-hover:opacity-100 group-hover:scale-100 transition-all duration-200"
                      fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 18.657l-6.828-6.829a4 4 0 010-5.656z" />
                  </svg>
                  <span>Colaboradores</span>
                </router-link>
              </div>
            </div> 
            


          <router-link to="/encuentros" class="text-blue-500 hover:underline">
            Ir a Encuentros
          </router-link>

          <router-link to="/veterinarios" class="text-green-100 hover:underline">
            veterinarios only 
          </router-link>

          <router-link to="/administradores" class="text-red-600 hover:underline">
            admins only 
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

        <div class="flex justify-center">
          <!-- Columna Social centrada -->
          <div class="flex flex-col items-center text-center text-3xl">
            <h4 class="font-bold mb-3">Social</h4>
            <div class="flex gap-4 justify-center">
              <!-- Iconos de FontAwesome -->
              <a href="https://www.instagram.com/proyecto_tere_2025/"><i class="fab fa-instagram-square hover:text-emerald-800 transition"></i></a>
              <a href="#"><i class="fab fa-tiktok hover:text-emerald-800 transition"></i></a>
              <a href="#"><i class="fab fa-youtube hover:text-emerald-800 transition"></i></a>
              <a href="https://x.com/Tere_Project"><i class="fab fa-x-twitter hover:text-emerald-800 transition"></i></a>
              <a href="#"><i class="fab fa-facebook hover:text-emerald-800 transition"></i></a>
              <a href="https://www.reddit.com/r/ProyectoTere/"><i class="fab fa-reddit-alien hover:text-emerald-800 transition"></i></a>
            </div>
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
import { onMounted, ref, onBeforeUnmount} from 'vue'
import logo from '@/assets/logo tere.png'
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

onMounted(() => {
  const handleScroll = () => {
    const scrollY = window.scrollY;
    // Ajustá el valor según el tamaño de tu pantalla o del hero
    if (scrollY > window.innerHeight * 0.5) {
      seVeInfo.value = true;
    } else {
      seVeInfo.value = false;
    }
  };

  window.addEventListener('scroll', handleScroll);
  handleScroll(); // Llamar una vez al cargar por si ya está abajo

  onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
  });
});
</script>

<style>
/* Elimina la barra de desplazamiento pero mantiene el scroll */
body {
  overflow-y: scroll; /* Permite el desplazamiento sin mostrar la barra */
  scrollbar-width: thin; /* Hacer la scrollbar fina en Firefox */
  scrollbar-color: transparent transparent; /* Fondo de la scrollbar transparente */
}

/* Ocultar la scrollbar en Webkit (Chrome, Safari) */
::-webkit-scrollbar {
  display: none; /* Oculta la barra de desplazamiento */
}

/* Si deseas que la scrollbar siga siendo visible al pasar el cursor, puedes usar esto */
body:hover::-webkit-scrollbar {
  display: block;
}

/* Si quieres modificar el estilo de la scrollbar en casos específicos */
::-webkit-scrollbar-thumb {
  background-color: transparent; /* Barra de desplazamiento invisible */
}

::-webkit-scrollbar-track {
  background: transparent; /* Fondo de la track de la barra de desplazamiento también invisible */
}
</style>

<!-- contenidoMascota -->
<template>    
        <div
            ref="scrollContainer"
            class="flex-1 overflow-y-auto overflow-x-overlay [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden px-2"
            >

            <!-- Imagen principal -->
            <div class="relative w-full min-h-[75vh] rounded-4xl overflow-hidden">
                <img :src="burro" alt="Burro" class="w-full h-130 object-cover rounded-4xl" />
            

                <!-- Info mascota -->
              <div class="absolute top-5 left-4 bg-white px-3 py-1 rounded-md shadow text-sm font-semibold w-fit">
                Nombre: {{ mascota.nombre }}, <span class="font-normal">sexo: {{ mascota.sexo }}</span>
              </div>

              <div class="absolute top-13 left-4 bg-blue-500 text-white text-xs px-2 py-1 rounded-md w-fit">
                Edad: {{ mascota.edad }}
              </div>

                <button
                v-if="mostrarBotonVolver"
                @click="router.back()"
                class="absolute right-14 top-3 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
                </button>

                  <button  
                    v-if="$route.query.from === 'perfil'"
                    @click="router.back()"
                    class="absolute right-14 top-3 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                  >
                    <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
                  </button>

                  <button  
                      v-if="$route.fullPath.includes('/perfil/mascota/')"
                      @click="router.push('/explorar/perfil/')"
                      class="absolute right-14 top-3 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                      <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
                    </button>

                    <button  
                      v-if="$route.path.startsWith('/explorar/cerca/') && $route.params.id"
                      @click="router.push('/explorar/cerca')"
                      class="absolute right-14 top-3 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                      <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
                    </button>



                <button
                @click="mostrar = true"
                class="absolute top-4 right-4 z-30 text-black bg-white bg-opacity-80 rounded-full px-3 py-1 text-lg shadow hover:bg-opacity-100 transition"
                >
                ...
                </button>

                <PasoAlgo v-if="mostrar" @close="mostrar = false" />
            </div>

            <!-- Descripción -->
            <div class="px-4 pt-4 pb-6 bg-white space-y-4">
                <div class="space-y-2">
                <h2 class="text-4xl font-bold text-gray-800">Descripción</h2>
                <p class="text-lg font-semibold text-gray-800">
                    Con sus largas orejas y su mirada dulce, este burro tiene un encanto irresistible. Es un animal inteligente y curioso, que disfruta de la compañía y de explorar su entorno. Aunque a veces pueda parecer un poco terco, en realidad es muy noble y agradecido.
                    Este burro se adapta bien a diferentes entornos, siempre y cuando tenga espacio para moverse, un refugio seguro y una alimentación adecuada. Le encanta recibir mimos y cepillados, y te recompensará con su afecto y su presencia tranquila.
                </p>
                </div>
            </div>
            <!-- Etiquetas de la Mascota -->
            <div class="flex flex-wrap gap-3">
                    <!-- Personalidad -->
                    <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Personalidad">
                        <div class=" mr-2">
                            <font-awesome-icon :icon="['fas', 'heart']" class="text-pink-500 text-sm"/>
                        </div>
                        <span class="text-gray-700">Amigable</span>
                    </div>
                    
                    <!-- nivel de energía -->
                    <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Energia">
                        <font-awesome-icon :icon="['fas', 'bolt']" class="text-gray-500 mr-2" />
                        <span class="text-gray-700">Alto</span>
                    </div>
                    
                    <!-- Tamaño -->
                    <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Tamaño">
                        <font-awesome-icon :icon="['fas', 'ruler-combined']" class="text-gray-500 mr-2"/>
                        <span class="text-gray-700">Grande</span>
                    </div>
                    
                    <!-- Alimentación -->
                    <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Alimentación">
                    <font-awesome-icon :icon="['fas', 'bowl-food']" class="text-gray-500 mr-2"/>
                        <span class="text-gray-700">saludable</span>
                    </div>
                    
                    <!-- Ejercicio -->
                    <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Ejercicio">
                        <font-awesome-icon :icon="['fas', 'dumbbell']" class="fa-solid fa-dumbbell text-gray-500 mr-2"/>
                        <span class="text-gray-700">Regularmente</span>
                    </div>
                    
                    <!-- fertilidad -->
                    <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Fertilidad">
                        <font-awesome-icon :icon="['fas', 'seedling']" class="text-gray-500 mr-2"/>
                        <span class="text-gray-700">Esteril</span>
                    </div>
                    
                    <!-- Afinidad a niños -->
                    <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="interacción con niños">
                        <font-awesome-icon :icon="['fas', 'baby-carriage']" class="fa-solid fa-baby-carriage text-gray-500 mr-2"/>
                        <span class="text-gray-700">Compañera</span>
                    </div>
                    
                </div>
                <div class="relative w-full min-h-[80vh] rounded-4xl overflow-hidden mt-4">
                <img :src="'https://cdn.pixabay.com/photo/2024/09/09/17/22/donkey-9035452_1280.jpg'" alt="Burro" class="w-full h-130 object-cover rounded-4xl" />
                </div>
                <!-- Historial Mascota -->
                <div class="mt-6 flex justify-center">
                    <button class="bg-purple-300 hover:bg-purple-600 text-white text-2xl font-bold py-5 px-10 rounded-md" @click="goToHistorial">
                        Historiales
                    </button>
                </div>
                <div class="relative w-full min-h-[80vh] rounded-4xl overflow-hidden mt-4">
                <img :src="'https://cdn.pixabay.com/photo/2020/12/29/22/57/donkey-5871800_960_720.jpg'" alt="Burro" class="w-full h-130 object-cover rounded-4xl" />
                </div>
                <div class="px-4 pt-4 pb-6 bg-white space-y-4">
                <div class="space-y-2">
                <h2 class="text-4xl font-bold text-gray-800">Ubicación Actual</h2>
                <p class="text-lg font-semibold text-gray-800">
                    Argentina, Misiones, Apóstoles 
                </p>
                </div>
            </div>
                <div class="h-20"></div>
            </div>
</template>


<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import burro from '@/assets/burro.png'
import PasoAlgo from '../components/reportarMascota.vue'



const route = useRoute()
const router = useRouter()
const scrollContainer = ref(null)
const mostrar = ref(false)
const path = ref('')

// Accede a los parámetros de la ruta
const id = computed(() => route.params.id)
const from = computed(() => route.query.from)

// Datos de la mascota (deberías obtenerlos de una API o prop)
const mascota = computed(() => {
  return {
    id: id.value,
    nombre: 'Lola',
    edad: '2 años',
    sexo: 'Hembra',
    imagen: 'https://cdn.pixabay.com/photo/2025/04/08/16/46/pyjama-9521835_1280.jpg'
  }
})



// Manejo del scroll
onMounted(() => {
  document.body.style.overflow = 'hidden'
})

onUnmounted(() => {
  document.body.style.overflow = ''
})

function goToHistorial() {
  const query = {
    ...route.query,
    from: route.name, // 'perfil-Mascota' o 'mascota-cerca'
    originalParams: JSON.stringify(route.params) 
  };
  
  router.replace({
    path: '/revisar/propietarios',
    query: query
  });
}

const mostrarBotonVolver = computed(() => from.value === 'cerca')
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

.scroll-container::-webkit-scrollbar {
  width: 0px;
  background: transparent;
}

</style>
  

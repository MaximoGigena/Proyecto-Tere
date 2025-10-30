<!-- contenidoMascota -->
<template> 
   <div class="bg-white backdrop-blur-md border border-white rounded-2xl 
         overflow-y-auto max-h-[83vh] w-full shadow-2xl 
         transition-all duration-300 relative mx-0"
    >


       <div
        ref="scrollContainer"
        class="flex-1 overflow-y-auto overflow-x-overlay 
        [scrollbar-width:none] [-ms-overflow-style:none] 
        [&::-webkit-scrollbar]:hidden ml-4"
        >
            <!-- Imagen principal -->
            <div class="relative w-full min-h-[76vh] rounded-4xl overflow-hidden">
                <div v-if="galleryImages[0]" class="relative w-full rounded-4xl overflow-hidden">
                  <img
                    :src="galleryImages[0]"
                    alt="Foto secundaria 1"
                    class="w-full max-h-[80vh] object-contain rounded-4xl bg-gray-100"
                    @click="openGallery(0)"
                    @error="onImgError"
                  />
                </div>
                        
                <!-- Info mascota -->
              <div class="absolute top-5 left-4 bg-white px-3 py-1 rounded-md shadow text-sm font-semibold w-fit">
                Nombre: {{ mascotaComputed.nombre }}, <span class="font-normal">sexo: {{ mascotaComputed.sexo }}</span>
              </div>

              <div class="absolute top-13 left-4 bg-blue-500 text-white text-xs px-2 py-1 rounded-md w-fit">
                Edad: {{ mascotaComputed.edad }} {{ mascotaComputed.unidad_edad || '' }}
              </div>

                  <button  
                      v-if="$route.fullPath.includes('/perfil/mascota/')"
                      @click="handleClose"
                      class="absolute right-3 top-3 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                      <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
                    </button>

                    <button  
                      v-if="$route.path.startsWith('/explorar/cerca/') && $route.params.id"
                      @click="handleClose"
                      class="absolute right-3 top-3 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                      <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
                    </button>

                    <button
                    v-if="$route.path.startsWith('/explorar/cerca/') && $route.params.id"
                    @click="mostrar = true"
                    class="absolute top-15 right-3 z-30 text-red-700 bg-white bg-opacity-80 rounded-full px-3 pt-0 py-1 text-4xl hover:bg-red-100 hover:text-red-800 hover:shadow-lg hover:scale-110 transition w-10 h-10 font-bold"
                    >
                    !
                    </button>

                <button
                v-if="$route.path.startsWith('/explorar/encuentros') "
                @click="mostrar = true"
                class="absolute top-3 right-3 z-30 text-red-700 bg-white bg-opacity-80 rounded-full px-3 pt-0 py-1 text-4xl hover:bg-red-100 hover:text-red-800 hover:shadow-lg hover:scale-110 transition w-10 h-10 font-bold"
                >
                !
                </button>

                <PasoAlgo v-if="mostrar" @close="mostrar = false" />
            </div>

            <!-- Descripción -->
            <div class="px-4 pt-4 pb-6 bg-white space-y-4">
                <div class="space-y-2">
                <h2 class="text-4xl font-bold text-gray-800">Descripción</h2>
                <p class="text-lg font-semibold text-gray-800">
                     {{ mascotaComputed.caracteristicas?.descripcion }}
                </p>
                </div>
            </div>
            
           <!-- Imagen secundaria 2 -->
            <div v-if="galleryImages[1]" class="relative w-full rounded-4xl overflow-hidden">
              <img
                :src="galleryImages[1]"
                alt="Foto secundaria 2"
                class="w-full max-h-[80vh] object-contain rounded-4xl bg-gray-100"
                @click="openGallery(1)"
                @error="onImgError"
              />
            </div>

            <!-- Características -->            
            <!-- Etiquetas de la Mascota -->
            <div class="flex flex-wrap gap-3 mt-2 justify-center">
                    <!-- Personalidad -->
                    <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Personalidad">
                        <div class=" mr-2">
                            <font-awesome-icon :icon="['fas', 'heart']" class="text-pink-500 text-sm"/>
                        </div>
                         {{ mascotaComputed.caracteristicas?.personalidad }}
                    </div>
                    
                    <!-- nivel de energía -->
                    <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Energia">
                        <font-awesome-icon :icon="['fas', 'bolt']" class="text-gray-500 mr-2" />
                        {{ mascotaComputed.caracteristicas?.energia }}
                    </div>
                    
                    <!-- Tamaño -->
                    <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Tamaño">
                        <font-awesome-icon :icon="['fas', 'ruler-combined']" class="text-gray-500 mr-2"/>
                        {{ mascotaComputed.caracteristicas?.tamano }}
                    </div>
                    
                    <!-- Alimentación -->
                    <div class="bg-white rounded-full shadow-sm border border-gray-200 px-4 py-2 flex items-center hover:shadow-2xl transition-all duration-300" title="Alimentación">
                    <font-awesome-icon :icon="['fas', 'bowl-food']" class="text-gray-500 mr-2"/>
                        {{ mascotaComputed.caracteristicas?.alimentacion }}
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
                        {{ mascotaComputed.caracteristicas?.comportamiento_ninos }}
                    </div>
                    
                </div>
                <div class="relative w-full min-h-[80vh] rounded-4xl overflow-hidden mt-4">
                  
                <!-- Imagen secundaria 3 -->
                <div v-if="galleryImages[2]" class="relative w-full rounded-4xl overflow-hidden">
                  <img
                    :src="galleryImages[2]"
                    alt="Foto secundaria 2"
                    class="w-full max-h-[80vh] object-contain rounded-4xl bg-gray-100"
                    @click="openGallery(2)"
                    @error="onImgError"
                  />
                </div>
            
                </div>
                <!-- Historial Mascota -->
                <div class="flex justify-center mt-2">
                    <button class="bg-purple-300 hover:bg-purple-600 text-white text-2xl font-bold py-5 px-10 rounded-md" @click="goToHistorial">
                        Historiales
                    </button>
                </div>

                  <!-- Collage final (últimas fotos si existen más de 3) -->
                  <div
                    v-if="galleryImages.length > 3"
                    class="grid grid-cols-2 sm:grid-cols-3 gap-3 rounded-2xl overflow-hidden mt-10 mb-20"
                  >
                    <div
                      v-for="(img, i) in galleryImages.slice(3)"
                      :key="i"
                      class="relative group cursor-pointer"
                    >
                      <img
                        :src="img"
                        alt="Collage mascota"
                        class="w-full h-48 object-cover rounded-2xl transform group-hover:scale-105 transition duration-300"
                        @click="openGallery(i + 3)"
                        @error="onImgError"
                      />
                    </div>
                  </div>

                <div
                  class="px-4 pt-4 pb-6 bg-white space-y-4"
                  :class="{'mt-2': galleryImages.length > 3, 'mt-2': galleryImages.length <= 3}"
                >
                  <div class="space-y-2">
                    <h2 class="text-4xl font-bold text-gray-800">Ubicación Actual</h2>
                    <p class="text-lg font-semibold text-gray-800">
                      Argentina, Misiones, Apóstoles 
                    </p>
                  </div>
                </div>
              <!-- Contenedor de botones -->
                <div 
                  v-if="showButtonsContainer"
                  ref="botonesAnimados"
                  :class="{'opacity-0 translate-y-10': !mostrarBotones, 'opacity-100 translate-y-0': mostrarBotones}"
                  class="flex justify-center gap-14 z-20 transition-all duration-700 ease-out"
                >
                  <button 
                    v-if="$route.path.startsWith('/explorar/encuentros')"
                    class="bg-white border border-black w-16 h-16 rounded-full shadow-lg flex items-center justify-center transition duration-300">
                    <font-awesome-icon :icon="['fas', 'xmark']" class="text-black text-5xl hover:text-red-400" />
                  </button>

                  <button 
                    v-if="$route.path.startsWith('/explorar/cerca/')"
                    class="bg-white border border-black w-16 h-16 rounded-full shadow-lg flex items-center justify-center transition duration-300">
                    <font-awesome-icon :icon="['fas', 'comment']" class="text-black text-4xl hover:text-purple-400" />
                  </button>
                  <button 
                    class="bg-white border border-black w-16 h-16 rounded-full shadow-lg flex items-center justify-center transition duration-300"
                    @click="abrirAdvertencia"
                  >
                    <font-awesome-icon :icon="['fas','heart']" class="text-black text-4xl hover:text-green-400"/>
                  </button>
                  <AdvertenciaAdopcion ref="advertenciaRef" @continue="continuarAdopcion" />
                </div>

                <div class="h-20"></div>
            </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import burro from '@/assets/burro.png'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import PasoAlgo from '@/components/módulo_mascotas/reportarMascota.vue'
import AdvertenciaAdopcion from '@/components/módulo_adopciones/advertenciaParaAdoptantes.vue'

const advertenciaRef = ref(null)

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const mascota = ref(null)
const cargando = ref(true)
const error = ref(null)

const scrollContainer = ref(null)
const mostrar = ref(false)
const path = ref('')

const mostrarBotones = ref(false)
const botonesAnimados = ref(null)
const showButtonsContainer = ref(false)

// Accede a los parámetros de la ruta
const id = computed(() => route.params.id)
const from = computed(() => route.query.from)

// Verificar autenticación 
onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      showMessage('Debe iniciar sesión para acceder a esta página', 'error')
      setTimeout(() => {
        router.push('/')
      }, 2000)
      return
    }
  }
})

function abrirAdvertencia() {
  if (advertenciaRef.value) {
    advertenciaRef.value.open()
  }
}

function continuarAdopcion() {
  console.log('Usuario aceptó la advertencia, continuar proceso de adopción')
}

function handleClose() {
  // Limpiar observer antes de navegar
  if (observer && botonesAnimados.value) {
    observer.unobserve(botonesAnimados.value)
    observer.disconnect()
    observer = null
  }
  
  if (route.fullPath.includes('/perfil/mascota/')) {
    router.push('/explorar/perfil/mascotas')
  } else if (route.path.startsWith('/explorar/cerca/') && route.params.id) {
    router.push('/explorar/cerca')
  }
}

let observer = null

const images = ref([
  burro,
  'https://cdn.pixabay.com/photo/2024/09/09/17/22/donkey-9035452_1280.jpg',
  'https://cdn.pixabay.com/photo/2020/12/29/22/57/donkey-5871800_960_720.jpg'
]);

// Inicializar observer de forma segura
const initObserver = () => {
  if (observer) {
    observer.disconnect()
    observer = null
  }

  if (!botonesAnimados.value) return

  observer = new IntersectionObserver(
    ([entry]) => {
      mostrarBotones.value = entry.isIntersecting
    },
    { 
      threshold: 0.5,
      rootMargin: '0px'
    }
  )
  
  observer.observe(botonesAnimados.value)
}

function goToHistorial() {
  const query = {
    ...route.query,
    from: route.name,
    originalParams: JSON.stringify(route.params) 
  };
  
  router.replace({
    path: '/revisar/tutores',
    query: query
  });
}

const mostrarBotonVolver = computed(() => from.value === 'cerca')

// --- Cargar mascota ---
async function cargarMascota() {
  const id = route.params.id
  if (!id) {
    mascota.value = null
    return
  }

  try {
    cargando.value = true
    error.value = null
    const response = await axios.get(`/api/mascotas/${id}`, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      }
    })

    mascota.value = response.data?.mascota ?? null
  } catch (err) {
    console.error('Error cargando mascota:', err)
    error.value = 'No se pudo cargar la información de la mascota.'
    mascota.value = null
  } finally {
    cargando.value = false
  }
}

// --------- Computeds de fallback / data mostrada / mascota de prueba / datos de prueba ----------
const mascotaComputed = computed(() => {
  if (mascota.value) return mascota.value

  return {
    id: 'demo-burro',
    nombre: 'Lola (demo)',
    edad: '2',
    unidad_edad: 'Años',
    sexo: 'Hembra',
    descripcion: `Con sus largas orejas y su mirada dulce... (demo)`,
    caracteristicas: {
      tamano: 'Grande',
      pelaje: 'Corto',
      alimentacion: 'saludable',
      energia: 'Alto',
      comportamiento_animales: 'Amigable',
      comportamiento_ninos: 'Compañera',
      personalidad: 'Amigable',
      descripcion: 'Demo: burro de ejemplo'
    },
    fotos: [
      { id: 'demo-1', url: burro, es_principal: true },
      { id: 'demo-2', url: 'https://cdn.pixabay.com/photo/2024/09/09/17/22/donkey-9035452_1280.jpg', es_principal: false },
      { id: 'demo-3', url: 'https://cdn.pixabay.com/photo/2020/12/29/22/57/donkey-5871800_960_720.jpg', es_principal: false },
      { id: 'demo-4', url: 'https://cdn.pixabay.com/photo/2019/05/08/19/30/donkey-4189421_1280.jpg', es_principal: false },
      { id: 'demo-5', url: 'https://cdn.pixabay.com/photo/2014/05/11/13/40/donkey-foal-341903_1280.jpg', es_principal: false }
    ],
    ubicacionTexto: 'Argentina, Misiones, Apóstoles'
  }
})

const fotoPrincipal = computed(() => {
  const fotos = mascotaComputed.value?.fotos || []
  if (fotos.length) {
    const principal = fotos.find(f => f.es_principal || f.esPrincipal || f.principal)
    return (principal && (principal.url || principal.getUrl || principal.ruta_foto)) || fotos[0].url || burro
  }
  return burro
})

const galleryImages = computed(() => {
  const fotos = mascotaComputed.value?.fotos || []
  if (!fotos.length) return [burro]
  return fotos.map(f => f.url || f.getUrl || burro)
})

function onImgError(event) {
  event.target.src = burro
}

// -------------- lifecycle + observer corregido --------------
onMounted(async () => {
  document.body.style.overflow = 'hidden'
  
  // Esperar a que el DOM se renderice completamente
  await nextTick()
  
  // Inicializar el contenedor de botones después de que el DOM esté listo
  showButtonsContainer.value = true
  
  // Esperar un tick más para asegurar que el elemento esté en el DOM
  await nextTick()
  
  // Inicializar observer de forma segura
  if (botonesAnimados.value) {
    initObserver()
  }
  
  cargarMascota()
})

onUnmounted(() => {
  // Limpiar observer de forma segura
  if (observer) {
    observer.disconnect()
    observer = null
  }
  
  document.body.style.overflow = ''
})

// ---------------- openGallery con manejo de errores ---------------
const openGallery = (index) => {
  const id = route.params.id || mascotaComputed.value.id
  if (!id) {
    console.error('No se pudo determinar el ID de la mascota')
    return
  }

  try {
    router.push({
      name: 'galeria-mascota-imagen',
      params: { id: id, imageIndex: index },
      query: { images: JSON.stringify(galleryImages.value) }
    }).catch(err => {
      if (err.name !== 'NavigationDuplicated') {
        console.error('Error de navegación:', err)
      }
    })
  } catch (error) {
    console.error('Error al abrir galería:', error)
  }
}
</script>
  

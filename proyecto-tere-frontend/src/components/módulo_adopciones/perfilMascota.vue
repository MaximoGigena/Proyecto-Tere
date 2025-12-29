<!-- perfilMascota.vue -->
<template>
  <div class="w-full h-full flex flex-col relative">
    <!-- Header sticky compacto -->
    <div class="sticky top-0 z-30 bg-white px-4 py-1 flex items-center justify-between border-b border-gray-100">
      <div class="text-xl font-bold text-gray-800 leading-tight">
        Explorando mascotas ({{ currentIndex + 1 }}/{{ ofertas.length }})
      </div>

      <button
        @click="mostrarFiltros = true"
        class="inline-flex whitespace-nowrap items-center gap-2 px-3 py-1 rounded-full
              bg-blue-100 text-blue-700 hover:bg-blue-700 hover:text-white
              transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300"
      >
        <span class="font-medium text-sm sm:text-base">Filtrar</span>
        <font-awesome-icon :icon="['fas', 'filter']" class="text-base sm:text-lg" />
      </button>
    </div>


    <!-- Contenedor principal -->
    <div 
      ref="swipeContainer"
      class="flex-1 min-h-0 pb-16 relative bg-gray-50"
    >
      <!-- Estado de carga -->
      <div v-if="cargando" class="flex items-center justify-center h-full">
        <div class="text-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500 mx-auto mb-4"></div>
          <p class="text-gray-600">Cargando mascotas...</p>
        </div>
      </div>

      <!-- Sin resultados -->
      <div v-else-if="ofertas.length === 0" class="flex items-center justify-center h-full">
        <div class="text-center p-8 max-w-md">
          <font-awesome-icon :icon="['fas', 'paw']" class="text-6xl text-gray-300 mb-4" />
          <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay mascotas disponibles</h3>
          <p class="text-gray-500">Prueba con otros filtros o vuelve más tarde</p>
        </div>
      </div>

      <!-- Stack de mascotas -->
      <transition-group 
        name="card-stack" 
        tag="div" 
        class="relative h-full"
      >
        <div
          v-for="(oferta, index) in ofertas"
          :key="oferta.id_oferta || `oferta-${index}`"
          :class="[
            'absolute top-0 left-0 right-0 bottom-0 transition-all duration-300 ease-out',
            index === currentIndex ? 'z-30 opacity-100' : 'z-10 opacity-0 pointer-events-none',
            index > currentIndex ? 'translate-y-10' : ''
          ]"
        >
          <div class="h-full flex items-center justify-center p-4">
            <ContenidoMascota 
              :oferta-actual="oferta"
              @like="handleLike"
              @dislike="handleDislike"
              @close="handleCardClose"
              @next="moverSiguiente"
              @prev="moverAnterior"
              @swipe-completed="onSwipeCompleted"
            />
          </div>
        </div>
      </transition-group>

      <!-- Botones de navegación -->
      <div 
        v-if="!cargando && ofertas.length > 0"
        class="absolute bottom-6 left-0 right-0 flex justify-center gap-4 z-20 px-4"
      >
        <!-- Botón anterior -->
        <button 
          v-if="currentIndex > 0"
          @click="moverAnterior"
          :class="[
            'bg-white border-2 border-gray-300 w-12 h-12 rounded-full shadow-lg',
            'flex items-center justify-center transition duration-300',
            'hover:bg-gray-100 active:scale-95'
          ]"
          aria-label="Anterior"
        >
          <font-awesome-icon 
            :icon="['fas', 'arrow-left']" 
            class="text-gray-600 text-xl" 
          />
        </button>

        <!-- Botón siguiente -->
        <button 
          v-if="currentIndex < ofertas.length - 1"
          @click="moverSiguiente"
          :class="[
            'bg-white border-2 border-gray-300 w-12 h-12 rounded-full shadow-lg',
            'flex items-center justify-center transition duration-300',
            'hover:bg-gray-100 active:scale-95'
          ]"
          aria-label="Siguiente"
        >
          <font-awesome-icon 
            :icon="['fas', 'arrow-right']" 
            class="text-gray-600 text-xl" 
          />
        </button>
      </div>
    </div>

    <!-- Overlay de filtros -->
    <transition name="fade">
      <div 
        v-if="mostrarFiltros" 
        class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
        @click.self="mostrarFiltros = false"
      >
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
          <div class="p-4 max-w-xl mx-auto">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold text-gray-800">Filtrar Mascotas</h2>
              <button @click="mostrarFiltros = false" class="text-gray-500 hover:text-gray-700">
                <font-awesome-icon :icon="['fas', 'times']" class="text-3xl" />
              </button>
            </div>
            
            <FiltrosComponente @cerrar="mostrarFiltros = false" />
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ContenidoMascota from '@/components/módulo_mascotas/contenidoMascota.vue'
import FiltrosComponente from './filtrosAdopciones.vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'

const mostrarFiltros = ref(false)
const route = useRoute()
const router = useRouter()
const { accessToken, isAuthenticated } = useAuth()

// Estado principal
const ofertas = ref([])
const currentIndex = ref(0)
const cargando = ref(true)
const procesando = ref(false)

// Cargar las ofertas disponibles
const cargarOfertas = async () => {
  try {
    cargando.value = true;
    ofertas.value = [];
    
    // Usar el endpoint específico para swipe que excluye mascotas ya vistas
     const response = await axios.get('/api/adopciones/ofertas-para-swipe', {
      headers: { 'Authorization': `Bearer ${accessToken.value}` }
    });

    if (response.data.success && response.data.data) {
      ofertas.value = response.data.data;
      currentIndex.value = 0;
      
      console.log('Ofertas para swipe cargadas:', ofertas.value.length);
    } else {
      console.error('Error en respuesta de ofertas para swipe:', response.data.message);
    }
  } catch (error) {
    console.error('Error cargando ofertas para swipe:', error);
  } finally {
    cargando.value = false;
  }
};

// Handlers para los eventos del componente ContenidoMascota
const handleLike = (data) => {
  console.log('Like desde ContenidoMascota:', data)
  // El componente hijo ya registró la interacción, solo avanzar
  moverSiguiente()
}

const handleDislike = (data) => {
  console.log('Dislike desde ContenidoMascota:', data)
  // El componente hijo ya registró la interacción, solo avanzar
  moverSiguiente()
}

const handleCardClose = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
  }
}

const onSwipeCompleted = (action) => {
  console.log('Swipe completado desde ContenidoMascota:', action)
}

// Handlers para los botones del padre (alternativos)
const handleLikeAccion = async () => {
  if (procesando.value || currentIndex.value >= ofertas.value.length) return
  
  procesando.value = true
  const ofertaActual = ofertas.value[currentIndex.value]
  
  try {
    // Simular click en el botón like del componente hijo
    console.log('Like desde botón del padre:', ofertaActual.id_oferta)
    
    // Aquí podrías emitir un evento al componente hijo si lo necesitas
    // Pero es mejor dejar que el hijo maneje su propia lógica
    
    // Solo avanzar
    moverSiguiente()
    
  } catch (error) {
    console.error('Error en like del padre:', error)
  } finally {
    procesando.value = false
  }
}

const handleDislikeAccion = async () => {
  if (procesando.value || currentIndex.value >= ofertas.value.length) return
  
  procesando.value = true
  const ofertaActual = ofertas.value[currentIndex.value]
  
  try {
    // Simular click en el botón dislike del componente hijo
    console.log('Dislike desde botón del padre:', ofertaActual.id_oferta)
    
    // Solo avanzar
    moverSiguiente()
    
  } catch (error) {
    console.error('Error en dislike del padre:', error)
  } finally {
    procesando.value = false
  }
}

// Navegación
const moverSiguiente = () => {
  if (currentIndex.value < ofertas.value.length - 1) {
    currentIndex.value++
  } else {
    // No hay más ofertas, recargar
    currentIndex.value = 0
    cargarOfertas()
  }
}

const moverAnterior = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
  }
}

// Inicializar
onMounted(() => {
  if (isAuthenticated.value) {
    cargarOfertas()
  }
})
</script>

<style scoped>
.card-stack-enter-active,
.card-stack-leave-active {
  transition: all 0.4s ease;
}

.card-stack-enter-from {
  opacity: 0;
  transform: translateY(30px);
}

.card-stack-leave-to {
  opacity: 0;
  transform: translateX(-100px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
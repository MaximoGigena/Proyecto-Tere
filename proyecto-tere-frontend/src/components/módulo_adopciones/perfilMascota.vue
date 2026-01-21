<!-- perfilMascota.vue -->
<template>
  <div class="w-full h-full flex flex-col relative">
    <!-- Header sticky compacto -->
    <div class="sticky top-0 z-30 bg-white px-4 py-1 flex items-center justify-between border-b border-gray-100">
      <div class="text-xl font-bold text-gray-800 leading-tight">
        <!-- ✅ Muestra información de ubicación si está disponible -->
        <template v-if="ubicacionCargada">
          Mascotas cerca de ti ({{ currentIndex + 1 }}/{{ ofertas.length }})
        </template>
        <template v-else>
          Explorando mascotas ({{ currentIndex + 1 }}/{{ ofertas.length }})
        </template>
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
          <p class="text-gray-600">Buscando mascotas cerca de ti...</p>
          <p class="text-sm text-gray-500 mt-2">Ordenando por proximidad</p>
        </div>
      </div>

      <!-- Sin resultados -->
      <div v-else-if="ofertas.length === 0" class="flex items-center justify-center h-full">
        <div class="text-center p-8 max-w-md">
          <font-awesome-icon :icon="['fas', 'paw']" class="text-6xl text-gray-300 mb-4" />
          <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay mascotas disponibles</h3>
          <p class="text-gray-500">Prueba con otros filtros o vuelve más tarde</p>
          <button 
            @click="cargarOfertas"
            class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition"
          >
            Volver a buscar
          </button>
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

      <!-- Indicador de proximidad en la oferta actual -->
      <div 
        v-if="!cargando && ofertas.length > 0 && ofertas[currentIndex]?.distancia"
        class="absolute top-20 left-0 right-0 z-10 px-4"
      >
        <div class="flex justify-center">
          <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/90 backdrop-blur-sm rounded-full shadow">
            <font-awesome-icon :icon="['fas', 'location-dot']" class="text-blue-500" />
            <span class="text-sm font-medium text-gray-700">
              {{ ofertas[currentIndex].distancia }}
              <span v-if="ofertas[currentIndex].mascota?.ubicacion_texto">
                • {{ ofertas[currentIndex].mascota.ubicacion_texto }}
              </span>
            </span>
          </div>
        </div>
      </div>

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
            
            <!-- ✅ Pasar evento para aplicar filtros y recargar -->
            <FiltrosComponente 
              @cerrar="mostrarFiltros = false" 
              @filtrar="aplicarFiltros"
            />
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
const ubicacionCargada = ref(false) // ✅ Nuevo estado para ubicación

// ✅ Función para obtener ubicación del usuario
const obtenerUbicacionUsuario = async () => {
  try {
    console.log('📍 Intentando obtener ubicación del usuario...')
    
    const response = await axios.get('/api/user/location', {
      headers: { 'Authorization': `Bearer ${accessToken.value}` }
    })
    
    if (response.data.success && response.data.data) {
      console.log('📍 Ubicación obtenida:', response.data.data)
      ubicacionCargada.value = true
      return true
    }
  } catch (err) {
    console.warn('⚠️ No se pudo obtener ubicación:', err.message)
    
    // Intentar alternativa: obtener del perfil
    try {
      const userResponse = await axios.get('/api/user', {
        headers: { 'Authorization': `Bearer ${accessToken.value}` }
      })
      
      if (userResponse.data && userResponse.data.ubicacionActual) {
        console.log('📍 Ubicación obtenida del perfil:', userResponse.data.ubicacionActual)
        ubicacionCargada.value = true
        return true
      }
    } catch (userErr) {
      console.warn('⚠️ No se pudo obtener ubicación del perfil:', userErr.message)
    }
  }
  
  return false
}

// ✅ Cargar las ofertas ordenadas por proximidad
const cargarOfertas = async () => {
  try {
    cargando.value = true;
    ofertas.value = [];
    
    // Intentar obtener ubicación del usuario
    const tieneUbicacion = await obtenerUbicacionUsuario();
    
    // Usar el endpoint de proximidad para obtener TODAS las ofertas ordenadas por cercanía
    const endpoint = '/api/adopciones/proximidad';
    console.log(`📍 Cargando ofertas desde: ${endpoint}`);
    console.log(`📍 Usuario ${tieneUbicacion ? 'tiene' : 'NO tiene'} ubicación`);
    
    const response = await axios.get(endpoint, {
      headers: { 'Authorization': `Bearer ${accessToken.value}` }
    });

    if (response.data.success && response.data.data) {
      // ✅ Obtener todas las ofertas (ya vienen ordenadas por proximidad del controlador)
      const todasLasOfertas = response.data.data;
      
      // ✅ Excluir ofertas ya vistas/interactuadas
      // Primero obtener las mascotas ya interactuadas
      const mascotasInteractuadasResponse = await axios.get('/api/adopciones/mis-interacciones', {
        headers: { 'Authorization': `Bearer ${accessToken.value}` }
      });
      
      let mascotasInteractuadas = [];
      if (mascotasInteractuadasResponse.data.success) {
        mascotasInteractuadas = mascotasInteractuadasResponse.data.data.map(i => i.mascota_id);
        console.log(`📍 Mascotas ya interactuadas: ${mascotasInteractuadas.length}`);
      }
      
      // Filtrar ofertas excluyendo las ya interactuadas
      const ofertasFiltradas = todasLasOfertas.filter(oferta => 
        !mascotasInteractuadas.includes(oferta.mascota.id)
      );
      
      // Limitar a 20 ofertas para no sobrecargar
      ofertas.value = ofertasFiltradas.slice(0, 20);
      currentIndex.value = 0;
      
      console.log('📍 Ofertas para swipe cargadas por proximidad:');
      console.log(`- Total encontradas: ${todasLasOfertas.length}`);
      console.log(`- Interactuadas excluidas: ${mascotasInteractuadas.length}`);
      console.log(`- Disponibles para swipe: ${ofertas.value.length}`);
      
      // Mostrar información de distancia en consola
      ofertas.value.forEach((oferta, index) => {
        console.log(`📍 Oferta ${index + 1}: ${oferta.mascota.nombre} - ${oferta.distancia || 'Sin ubicación'}`);
      });
      
      // Si no hay ofertas, mostrar mensaje
      if (ofertas.value.length === 0) {
        console.log('📍 No hay ofertas nuevas disponibles');
        // Podrías mostrar un mensaje al usuario aquí
      }
    } else {
      console.error('Error en respuesta de ofertas por proximidad:', response.data.message);
    }
  } catch (error) {
    console.error('Error cargando ofertas por proximidad:', error);
    
    // ✅ FALLBACK: Intentar con el endpoint original si falla
    console.log('📍 Intentando con endpoint alternativo...');
    try {
      const fallbackResponse = await axios.get('/api/adopciones/ofertas-para-swipe', {
        headers: { 'Authorization': `Bearer ${accessToken.value}` }
      });
      
      if (fallbackResponse.data.success && fallbackResponse.data.data) {
        ofertas.value = fallbackResponse.data.data;
        currentIndex.value = 0;
        console.log('📍 Ofertas cargadas por fallback:', ofertas.value.length);
      }
    } catch (fallbackError) {
      console.error('Error en fallback también:', fallbackError);
    }
  } finally {
    cargando.value = false;
  }
};

// ✅ Función para recargar ofertas (cuando se llega al final)
const recargarOfertas = async () => {
  console.log('📍 Recargando ofertas...');
  await cargarOfertas();
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

// Navegación
const moverSiguiente = () => {
  if (currentIndex.value < ofertas.value.length - 1) {
    currentIndex.value++
  } else {
    // No hay más ofertas, recargar
    console.log('📍 Última oferta alcanzada, recargando...');
    recargarOfertas();
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
<!-- SwipeOfertas.vue - Versión corregida -->
<template>
  <div class="relative w-full h-full">
    <!-- Vista actual -->
    <div 
      v-if="mascotasDisponibles.length > 0 && currentIndex < mascotasDisponibles.length"
      class="absolute inset-0"
      :class="{ 'z-10': isCurrentActive }"
    >
      <contenidoMascota
        :ofertaActual="mascotasDisponibles[currentIndex]"
        :mascotasDisponibles="mascotasDisponibles"
        :currentIndex="currentIndex"
        @like="handleLike"
        @dislike="handleDislike"
        @next="loadNextMascota"
        @prev="loadPrevMascota"
        @close="$emit('close')"
        @swipe-completed="onSwipeCompleted"
      />
    </div>
    
    <!-- Vista siguiente (precargada) -->
    <div 
      v-if="mascotasDisponibles.length > 0 && currentIndex + 1 < mascotasDisponibles.length"
      class="absolute inset-0 opacity-70 scale-95"
      :class="{ 'z-5': isNextActive }"
    >
      <contenidoMascota
        :ofertaActual="mascotasDisponibles[currentIndex + 1]"
        :key="`next-${currentIndex + 1}`"
        class="pointer-events-none"
      />
    </div>
    
    <!-- Mensaje cuando no hay más ofertas -->
    <div 
      v-if="mascotasDisponibles.length === 0 || currentIndex >= mascotasDisponibles.length"
      class="flex flex-col items-center justify-center h-full text-center p-8"
    >
      <font-awesome-icon :icon="['fas', 'paw']" class="text-6xl text-gray-400 mb-4" />
      <h3 class="text-2xl font-bold text-gray-700 mb-2">¡No hay más mascotas!</h3>
      <p class="text-gray-600">Vuelve más tarde para ver nuevas ofertas de adopción.</p>
      <button 
        @click="$emit('close')"
        class="mt-6 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        Volver al inicio
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import contenidoMascota from './contenidoMascota.vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
  initialOfertas: {
    type: Array,
    default: () => []
  },
  filtros: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'no-more-ofertas'])
const { accessToken } = useAuth()

const mascotasDisponibles = ref([])
const currentIndex = ref(0)
const isLoading = ref(false)
const hasMore = ref(true)

const isCurrentActive = computed(() => currentIndex.value < mascotasDisponibles.value.length)
const isNextActive = computed(() => currentIndex.value + 1 < mascotasDisponibles.value.length)

// Cargar ofertas iniciales
onMounted(async () => {
  if (props.initialOfertas.length > 0) {
    mascotasDisponibles.value = props.initialOfertas
  } else {
    await fetchOfertasParaSwipe()
  }
})

async function fetchOfertasParaSwipe() {
  if (isLoading.value || !hasMore.value) return
  
  isLoading.value = true
  try {
    const response = await axios.get('/api/adopciones/ofertas-para-swipe', {
      params: {
        filtros: JSON.stringify(props.filtros)
      },
      headers: {
        'Authorization': `Bearer ${accessToken.value}`
      }
    })
    
    if (response.data.success) {
      if (response.data.data.length > 0) {
        mascotasDisponibles.value = [...mascotasDisponibles.value, ...response.data.data]
      } else {
        hasMore.value = false
      }
    }
  } catch (error) {
    console.error('Error fetching ofertas para swipe:', error)
  } finally {
    isLoading.value = false
  }
}

async function handleLike(data) {
  console.log('=== handleLike en SwipeOfertas ===')
  console.log('Like a mascota:', data)
  
  // IMPORTANTE: Solo avanzar, no registrar aquí
  // El registro se hace en contenidoMascota.vue
  
  // Si el like NO viene con advertencia (es decir, ya pasó por la advertencia)
  // avanzamos inmediatamente
  if (!data.esAdvertencia) {
    console.log('Like directo (sin advertencia), avanzando...')
    loadNextMascota()
  } else {
    console.log('Like con advertencia pendiente, esperando confirmación...')
    // No avanzamos aquí, esperamos el evento swipe-completed
  }
}

async function handleDislike(data) {
  console.log('=== handleDislike en SwipeOfertas ===')
  console.log('Dislike a mascota:', data)
  
  // Solo necesitamos avanzar a la siguiente oferta
  loadNextMascota()
}

function loadNextMascota() {
  console.log('=== loadNextMascota llamado ===')
  console.log('Índice actual:', currentIndex.value)
  console.log('Total ofertas:', mascotasDisponibles.value.length)
  
  if (currentIndex.value < mascotasDisponibles.value.length - 1) {
    currentIndex.value++
    console.log('Nuevo índice:', currentIndex.value)
    
    // Precargar más ofertas si estamos cerca del final
    if (currentIndex.value >= mascotasDisponibles.value.length - 3 && hasMore.value) {
      console.log('Precargando más ofertas...')
      fetchOfertasParaSwipe()
    }
  } else {
    console.log('No hay más ofertas en la lista actual')
    // No hay más ofertas disponibles
    if (hasMore.value) {
      console.log('Intentando cargar más ofertas del servidor...')
      // Intentar cargar más ofertas
      fetchOfertasParaSwipe().then(() => {
        if (mascotasDisponibles.value.length > currentIndex.value + 1) {
          currentIndex.value++
          console.log('Ofertas cargadas, nuevo índice:', currentIndex.value)
        } else {
          console.log('No hay más ofertas disponibles')
          emit('no-more-ofertas')
        }
      })
    } else {
      console.log('No hay más ofertas disponibles (hasMore = false)')
      emit('no-more-ofertas')
    }
  }
}

function loadPrevMascota() {
  console.log('loadPrevMascota')
  if (currentIndex.value > 0) {
    currentIndex.value--
  }
}

function onSwipeCompleted(swipeData) {
  console.log('=== onSwipeCompleted en SwipeOfertas ===')
  console.log('Datos del swipe completado:', swipeData)
  
  // Si el swipe fue un like completado (después de advertencia)
  if (swipeData.tipo === 'like') {
    console.log('Like completado después de advertencia, avanzando...')
    loadNextMascota()
  }
  // Si fue cancelado, no hacemos nada (ya se reseteó en contenidoMascota)
}
</script>
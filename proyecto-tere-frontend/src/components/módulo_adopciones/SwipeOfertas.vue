<!-- SwipeOfertas.vue -->
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

// Reemplaza el script completo de SwipeOfertas.vue:
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
  console.log('Like a mascota:', data)
  
  // Aquí ya se registró la interacción en contenidoMascota.vue
  // Solo necesitamos avanzar a la siguiente oferta
  loadNextMascota()
}

async function handleDislike(data) {
  console.log('Dislike a mascota:', data)
  
  // Aquí ya se registró la interacción en contenidoMascota.vue
  // Solo necesitamos avanzar a la siguiente oferta
  loadNextMascota()
}

function loadNextMascota() {
  if (currentIndex.value < mascotasDisponibles.value.length - 1) {
    currentIndex.value++
    
    // Precargar más ofertas si estamos cerca del final
    if (currentIndex.value >= mascotasDisponibles.value.length - 3 && hasMore.value) {
      fetchOfertasParaSwipe()
    }
  } else {
    // No hay más ofertas disponibles
    if (hasMore.value) {
      // Intentar cargar más ofertas
      fetchOfertasParaSwipe().then(() => {
        if (mascotasDisponibles.value.length > currentIndex.value + 1) {
          currentIndex.value++
        } else {
          emit('no-more-ofertas')
        }
      })
    } else {
      emit('no-more-ofertas')
    }
  }
}

function loadPrevMascota() {
  if (currentIndex.value > 0) {
    currentIndex.value--
  }
}

function onSwipeCompleted(action) {
  console.log('Swipe completado:', action)
}
</script>
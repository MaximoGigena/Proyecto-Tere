<!-- GaleriaMascota.vue -->
<template>
  <div class="fixed inset-0 bg-black bg-opacity-90 z-50 flex flex-col">
    <!-- Controles -->
    <div class="flex justify-between items-center p-4 text-white">
      <span class="text-lg">{{ currentIndex + 1 }} / {{ images.length }}</span>
      <button 
        @click="closeGallery" 
        class="p-2 rounded-full hover:bg-gray-700"
      >
        <font-awesome-icon :icon="['fas', 'xmark']" class="text-2xl"/>
      </button>
    </div>
    
    <!-- Imagen principal -->
    <div class="flex-1 flex items-center justify-center relative">
      <button 
        @click="prevImage" 
        class="absolute left-4 p-3 rounded-full bg-black bg-opacity-50 text-white hover:bg-opacity-70 z-10"
        :disabled="currentIndex === 0"
      >
        <font-awesome-icon :icon="['fas', 'chevron-left']" class="text-2xl"/>
      </button>
      
      <img 
        :src="images[currentIndex]" 
        :alt="`Imagen ${currentIndex + 1} de mascota`" 
        class="max-h-[80vh] max-w-full object-contain"
      />
      
      <button 
        @click="nextImage" 
        class="absolute right-4 p-3 rounded-full bg-black bg-opacity-50 text-white hover:bg-opacity-70 z-10"
        :disabled="currentIndex === images.length - 1"
      >
        <font-awesome-icon :icon="['fas', 'chevron-right']" class="text-2xl"/>
      </button>
    </div>
    
    <!-- Miniaturas -->
    <div class="p-4 flex overflow-x-auto gap-2 bg-black bg-opacity-50">
      <div 
        v-for="(img, index) in images" 
        :key="index"
        @click="goToImage(index)"
        class="flex-shrink-0 w-16 h-16 cursor-pointer border-2"
        :class="{'border-blue-500': currentIndex === index, 'border-transparent': currentIndex !== index}"
      >
        <img :src="img" class="w-full h-full object-cover" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const props = defineProps({
  images: {
    type: Array,
    required: true
  },
  initialIndex: {
    type: Number,
    default: 0
  }
})

// Usa las imágenes de las props o de la ruta
const images = ref(props.images || (route.query.images ? JSON.parse(route.query.images) : []))
const currentIndex = ref(parseInt(route.params.imageIndex) || props.initialIndex || 0)

// Sincronizar con la ruta
watch(() => route.params.imageIndex, (newIndex) => {
  if (newIndex !== undefined) {
    currentIndex.value = parseInt(newIndex)
  }
})

// Métodos de navegación
const nextImage = () => {
  if (currentIndex.value < images.value.length - 1) {
    currentIndex.value++
    updateRoute()
  }
}

const prevImage = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
    updateRoute()
  }
}

const goToImage = (index) => {
  currentIndex.value = index
  updateRoute()
}

const updateRoute = () => {
  router.replace({
    name: 'galeria-mascota-imagen',
    params: { 
      id: route.params.id, 
      imageIndex: currentIndex.value 
    },
    query: {
      images: JSON.stringify(images.value)
    }
  })
}

const closeGallery = () => {
  router.back();
}

// Manejar navegación con teclado
const handleKeyUp = (e) => {
  if (e.key === 'ArrowRight') nextImage()
  if (e.key === 'ArrowLeft') prevImage()
  if (e.key === 'Escape') closeGallery()
}

onMounted(() => {
  window.addEventListener('keyup', handleKeyUp)
  
  // Validación inicial
  if (images.value.length === 0) {
    console.error('No hay imágenes para mostrar')
    closeGallery()
  }
})

onUnmounted(() => {
  window.removeEventListener('keyup', handleKeyUp)
})

// En GaleriaMascota.vue
onMounted(() => {
  try {
    window.addEventListener('keyup', handleKeyUp)
    
    if (!props.images || props.images.length === 0) {
      throw new Error('No se recibieron imágenes para la galería')
    }
    
    if (currentIndex.value >= props.images.length) {
      currentIndex.value = 0
      updateRoute()
    }
  } catch (error) {
    console.error('Error al inicializar galería:', error)
    closeGallery()
  }
})
</script>
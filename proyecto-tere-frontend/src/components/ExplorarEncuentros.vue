<template>
  <div
  ref="animatedBg"
  class="bg-cover bg-repeat bg-center min-h-screen flex items-center justify-center">

   <div class="w-11/12 max-w-2xl bg-white h-screen shadow-lg relative flex flex-col">
      <!-- Contenido principal (siempre visible) -->
      <div class="relative w-full h-full">
    <!-- Contenido principal (siempre visible) -->
    <router-view v-slot="{ Component }">
      <component :is="Component" />
    </router-view>

    <!-- Overlay para perfiles -->
    <router-view 
      v-slot="{ Component, route }"
      name="overlay"
    >
      <transition name="fade">
        <div 
          v-if="route.params.id" 
          class="absolute inset-0 z-40 bg-white overflow-y-auto"
        >
          <component :is="Component" />
        </div>
      </transition>
    </router-view>
  </div>

      <!-- Navegación inferior -->
      <div class="absolute bottom-0 w-full bg-white border-t py-3 text-gray-600 flex justify-around">
        <router-link
          v-for="item in navItems"
          :key="item.id"
          :to="item.path"
          class="flex flex-col items-center px-4 py-1 rounded-md transition relative"
          :class="isActive(item.path) ? 'bg-white text-black' : 'text-gray-600 hover:text-black'"
        >
          <font-awesome-icon :icon="['fas', item.icon]" class="text-xl" />
          <span class="text-xs">{{ item.label }}</span>
          <span
            v-if="item.id === 'chats'"
            class="absolute top-1 right-3 bg-red-500 text-white text-xs px-1.5 rounded-full"
          >●</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick} from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { RouterLink } from 'vue-router'
import huellas from '@/assets/huellas.png'


const animatedBg = ref(null)

onMounted(() => {
  if (animatedBg.value) {
    animatedBg.value.style.backgroundImage = `url(${huellas})`
    animatedBg.value.style.animation = 'moverHuellas 120s linear infinite'
    animatedBg.value.style.backgroundPosition = '0 0'
  }
})




const route = useRoute()
const activo = ref('encuentros')
const router = useRouter()
const scrollContainer = ref(null)

const navItems = [
  { id: 'cerca', label: 'Cerca', icon: 'fa-location-dot', path: '/explorar/cerca' },
  { id: 'encuentros', label: 'Encuentros', icon: 'fa-paw', path: '/explorar/encuentros' },
  { id: 'chats', label: 'Chats', icon: 'fa-comment', path: '/explorar/chats' },
  { id: 'perfil', label: 'Perfil', icon: 'fa-user', path: '/explorar/perfil' },
]

const isActive = (path) => route.path === path

onMounted(() => {
  document.body.style.overflow = 'hidden'
})

onUnmounted(() => {
  document.body.style.overflow = 'auto'
})

onMounted(() => {
  if (route.path === '/encuentros') {
    router.replace('/explorar/encuentros')
  }
})

onMounted(() => {
  nextTick(() => {
    const content = scrollContainer.value
    if (!content) return

    const thumb = content.querySelector('.scroll-thumb')
    if (!thumb) return

    const updateThumb = () => {
      const scrollTop = content.scrollTop
      const scrollHeight = content.scrollHeight
      const clientHeight = content.clientHeight

      const thumbHeight = (clientHeight / scrollHeight) * clientHeight
      const thumbTop = (scrollTop / scrollHeight) * clientHeight

      thumb.style.height = `${thumbHeight}px`
      thumb.style.top = `${thumbTop}px`
    }

    content.addEventListener('scroll', updateThumb)
    updateThumb()
  })
})

</script>


<style>
  @keyframes moverHuellas {
    0% {
      background-position: 0 0;
    }
    100% {
      background-position: 0 1024px;
    }
  }

  .animate-huellas {
    animation: moverHuellas 120s linear infinite;
  }

</style>

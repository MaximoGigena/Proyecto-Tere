<template> 
  <div class="flex items-center justify-center min-h-screen bg-green-200">
    <div class="w-11/12 max-w-2xl bg-white h-screen shadow-lg  relative flex flex-col items-center">
      <!-- Contenido a Navegar -->
       <!-- Contenido a Navegar -->
          <!-- Contenido dinámico -->
              <div class="flex-1 overflow-y-auto w-full">
                <router-view v-slot="{ Component, route }">
                  <transition name="fade" mode="out-in">
                    <component :is="Component" :key="route.fullPath" />
                  </transition>
                </router-view>
              </div>

      <!-- Navegación inferior -->
      <div class="absolute bottom-0 w-full bg-white border-t py-3 text-gray-600 flex justify-around">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <router-link
          v-for="item in navItems"
          :key="item.id"
          :to="item.path"
          class="flex flex-col items-center px-4 py-1 rounded-md transition relative"
          :class="isActive(item.path) ? 'bg-white text-black' : 'text-gray-600 hover:text-black'"
        >
          <i :class="item.icon + ' text-xl'"></i>
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


const route = useRoute()
const activo = ref('encuentros')
const router = useRouter()
const scrollContainer = ref(null)

const navItems = [
  { id: 'cerca', label: 'Cerca', icon: 'fa-solid fa-location-dot', path: '/explorar/cerca' },
  { id: 'encuentros', label: 'Encuentros', icon: 'fa-solid fa-paw', path: '/explorar/encuentros' },
  { id: 'chats', label: 'Chats', icon: 'fa-solid fa-comment', path: '/explorar/chats' },
  { id: 'perfil', label: 'Perfil', icon: 'fa-solid fa-user', path: '/explorar/perfil' },
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

</style>

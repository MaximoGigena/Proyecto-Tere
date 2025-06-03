<template>
  <transition name="slide-up">
    <div
      v-if="mostrar"
      ref="animatedBg"
      class="fixed top-0 left-[23rem] right-0 bottom-0 z-50 flex justify-center items-start pt-20 px-4 bg-[rgba(0,0,0,0.03)] bg-cover bg-repeat bg-center backdrop-blur-sm"
      @click.self="cerrar"
    >
      <div class="bg-white w-full max-w-md rounded-lg shadow-lg p-4">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold text-center mx-auto">Filtros disponibles</h2>
          <button @click="cerrar" class="text-gray-600 hover:text-black text-4xl -mt-8">&times;</button>
        </div>
        <VeterinarioFiltros />
      </div>
    </div>
  </transition>
</template>



<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import VeterinarioFiltros from '@/components/módulo_veterinario/veterinarioFiltros.vue'
import huellas from '@/assets/huellas.png'

const animatedBg = ref(null)
const router = useRouter()


const mostrar = ref(true)

function cerrar() {
  mostrar.value = false
  setTimeout(() => {
    router.push('/veterinarios')
  }, 200) // Tiempo suficiente para que corra la animación de salida
  
}

onMounted(() => {
  if (animatedBg.value) {
    animatedBg.value.style.backgroundImage = `url(${huellas})`
    animatedBg.value.style.animation = 'moverHuellas 120s linear infinite'
    animatedBg.value.style.backgroundPosition = '0 0'
  }
  
  console.log('Filtros overlay abierto')
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

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-up-enter-from {
  transform: translateY(100%);
  opacity: 0;
}

.slide-up-enter-to {
  transform: translateY(0);
  opacity: 1;
}

.slide-up-leave-from {
  transform: translateY(0);
  opacity: 1;
}

.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}
</style>

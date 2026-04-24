<!-- components/ComingSoonOverlay.vue -->
<template>
  <div v-if="show" class="absolute inset-0 z-50 flex items-center justify-center">
    <!-- Fondo con blur -->
    <div class="absolute inset-0 bg-gray-200 backdrop-blur-sm"></div>
    
    <!-- Contenido del overlay -->
    <div class="relative bg-gray-300 rounded-2xl shadow-2xl max-w-2xl mx-4 animate-fadeInUp">
      <div class="text-center p-8 md:p-12">
        <!-- Indicadores de sección -->
        <div class="flex justify-center gap-2 mb-6">
          <button 
            v-for="(section, index) in sections" 
            :key="index"
            @click="currentSection = index"
            class="w-2 h-2 rounded-full transition-all duration-300"
            :class="currentSection === index ? 'bg-gray-800 w-6' : 'bg-gray-500'"
          ></button>
        </div>

        <!-- Sección 1: Nueva sección -->
        <div v-if="currentSection === 0" class="transition-opacity duration-300">
          <h2 class="text-3xl md:text-5xl font-bold text-gray-800 mb-6 animate-bounceIn">
            {{ sections[0].title }}
          </h2>
          
          <p class="text-gray-800 mb-8 text-lg animate-fadeIn delay-200">
            {{ sections[0].description }}
          </p>
          
          <div class="mt-4 animate-scaleIn">
            <img 
              :src="sections[0].image" 
              :alt="sections[0].alt" 
              class="max-w-full h-auto mx-auto"
            >
          </div>
          
          <!-- SOLO BOTÓN SIGUIENTE EN LA ESQUINA DERECHA -->
          <div class="flex justify-end mt-8">
            <button 
              @click="nextSection"
              class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors text-gray-700 font-medium"
            >
              Siguiente →
            </button>
          </div>
        </div>

        <!-- Sección 2: Sección existente -->
        <div v-if="currentSection === 1" class="transition-opacity duration-300">
          <h2 class="text-3xl md:text-5xl font-bold text-gray-800 mb-6 animate-bounceIn">
            {{ sections[1].title }}
          </h2>
          
          <p class="text-gray-800 mb-8 text-lg animate-fadeIn delay-200">
            {{ sections[1].description }}
          </p>
          
          <div class="mt-4 animate-scaleIn">
            <img 
              :src="sections[1].image" 
              :alt="sections[1].alt" 
              class="max-w-full h-auto mx-auto"
            >
          </div>
          
          <!-- SOLO BOTÓN ANTERIOR EN LA ESQUINA IZQUIERDA, NADA A LA DERECHA -->
          <div class="flex justify-start mt-8">
            <button 
              @click="prevSection"
              class="px-6 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors text-gray-700 font-medium"
            >
              ← Anterior
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import gatitoStop from '@/assets/gatitoStop.png'
import gatitosLaburando from '@/assets/gatitosLaburando.png'

export default {
  name: 'ComingSoonOverlay',
  props: {
    show: {
      type: Boolean,
      default: true
    }
  },
  emits: ['close'],
  data() {
    return {
      currentSection: 0,
      sections: [
        {
          title: "Espera un poco más...",
          description: "Construcción en curso",
          image: gatitoStop,
          alt: "Novedades emocionantes"
        },
        {
          title: "Estamos trabajando a dos patas...",
          description: "Esta funcionalidad estará disponible muy pronto. Mientras tanto, ¡nuestros gatitos están dándolo todo para traértela lo antes posible!",
          image: gatitosLaburando,
          alt: "Gatitos trabajando"
        }
      ]
    }
  },
  methods: {
    nextSection() {
      this.currentSection++
    },
    prevSection() {
      this.currentSection--
    }
  }
}
</script>

<style scoped>
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes bounceIn {
  0% {
    opacity: 0;
    transform: scale(0.3);
  }
  50% {
    opacity: 1;
    transform: scale(1.05);
  }
  70% {
    transform: scale(0.98);
  }
  100% {
    transform: scale(1);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fadeInUp {
  animation: fadeInUp 0.5s ease-out;
}

.animate-bounceIn {
  animation: bounceIn 0.7s ease-out;
}

.animate-fadeIn {
  animation: fadeIn 0.5s ease-out;
}

.animate-scaleIn {
  animation: scaleIn 0.4s ease-out 0.3s both;
}

.delay-200 {
  animation-delay: 0.2s;
}

.transition-opacity {
  transition: opacity 0.3s ease-in-out;
}
</style>
<!-- carpetaHistoriales.vue -->
<template>
  <div 
  ref="animatedBg"
  class="bg-cover bg-repeat bg-center flex flex-col h-screen relative">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
      
      <!-- Header -->
      <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold">{{ tituloCabecera }}</h1>
        <button
          @click="cerrarVista"
          class="text-white hover:text-red-400 transition"
          title="Cerrar"
        >
          <font-awesome-icon :icon="['fas', 'times']" />
        </button>
      </div>
  
      <!-- Tabs como links -->
      <div class="flex border-b border-gray-200 bg-gray-100">
        <router-link
          v-for="(tab, index) in tabs"
          :key="index"
          :to="{
              path: tab.to,
              query: {
                ...$route.query, // Mantiene from/originalParams
                currentTab: tab.nombre
              }
            }"
          class="flex items-center justify-center gap-2 flex-1 py-2 text-sm font-medium text-center transition-colors"
          :class="{
            'border-b-2 border-blue-500 bg-white text-blue-600': isTabActive(tab),
            'text-gray-600 hover:bg-gray-200 hover:text-gray-800': !isTabActive(tab)
          }"
        >
          <font-awesome-icon :icon="['fas', tab.icon]" class="text-xl" />
          <span>{{ tab.nombre }}</span>
        </router-link>
      </div>
      <router-view />
    </div>
  </div>
</template>
  
<script setup>
const tabs = [
  { nombre: 'Dueños', icon: 'shield-dog', to: '/revisar/propietarios' },
  { nombre: 'Vacunas', icon: 'syringe', to: '/revisar/vacunas' },
  { nombre: 'Médico', icon: 'stethoscope',to: '/revisar/historialMedico', activeNames: ['historialMedico', 'procedimientos', 'diagnosticos', 'medicamentos', 'terapias'] },
]

import { useRouter, useRoute } from 'vue-router'
import { ref,computed, onMounted } from 'vue'
import huellas from '@/assets/huellas.png';

const animatedBg = ref(null)

onMounted(() => {
  if (animatedBg.value) {
    animatedBg.value.style.backgroundImage = `url(${huellas})`
    animatedBg.value.style.animation = 'moverHuellas 120s linear infinite'
    animatedBg.value.style.backgroundPosition = '0 0'
  }
})


const route = useRoute()

const router = useRouter()

// Cierra la vista restaurando los valores originales
async function cerrarVista() {
  // Obtiene valores del primer nivel O del query actual
  const from = route.query.from || route.matched[0]?.meta?.from;
  const originalParams = route.query.originalParams 
    ? JSON.parse(route.query.originalParams)
    : route.matched[0]?.meta?.originalParams || {};

  if (!from) {
    console.error('No se encontró origen para redirección');
    return router.push('/explorar/encuentros');
  }

  try {
    const targetRoute = {
      name: from, // 'perfil-Mascota' o 'mascota-cerca'
      params: originalParams,
      query: { 
        from: from.split('-')[0],
        ts: Date.now() // Evita caché
      }
    };
    
    await router.push(targetRoute);
  } catch (error) {
    console.error('Error al cerrar:', error);
    router.push('/explorar/encuentros');
  }
}




const titulosPorRuta = {
  propietarios: 'Dueños de la Mascota',
  vacunas: 'Vacunas Aplicadas',
  historialMedico: 'Historial Médico',
}
const tituloCabecera = computed(() => {
  return titulosPorRuta[route.name] || 'Historial de la Mascota'
})

const isTabActive = (tab) => {
  return tab.activeNames?.includes(route.name) || tab.to === route.path
}

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
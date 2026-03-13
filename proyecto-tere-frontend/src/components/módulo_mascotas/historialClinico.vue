<!-- historialClinico.vue modificado -->
<template>
  <div class="flex flex-col h-[calc(100vh-140px)] w-full">
    <!-- Navbar de íconos -->
    <nav class="flex justify-around items-center bg-white border-b border-gray-200 py-3 sticky top-0 z-20">
      <router-link 
        v-for="nav in navItems"
        :key="nav.name"
        :to="{
          name: nav.name,
          params: { id: mascotaId },
          query: {
            ...$route.query,
            tab: nav.name
          }
        }"
        class="flex flex-col items-center p-2 rounded-full mx-2 text-gray-500 hover:text-blue-500 transition-all duration-200"
        :class="{ 'text-blue-600 bg-blue-50': $route.name === nav.name }"
        style="width: 60px;"
      >
        <font-awesome-icon
          :icon="['fas', nav.icon]"
          class="text-2xl mb-1"
        />
        <span class="text-xs font-medium mt-1">{{ nav.label }}</span>
      </router-link>
    </nav>

    <!-- Contenido con verificación de permisos -->
    <div class="flex-1 overflow-y-auto p-4">
      <template v-if="tienePermisoHistorial">
        <div v-if="$route.matched.length">
          <router-view 
            v-slot="{ Component }"
            :mascotaId="mascotaId"
            :ofertaId="ofertaId"
            :tienePermisoHistorial="tienePermisoHistorial"
            :nombreMascota="nombreMascota"
            :puedeContactarTutor="puedeContactarTutor"
          >
            <transition name="fade" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </div>
      </template>
      
      <template v-else>
        <SinPermisoHistorial
          tipo-historial="clínico"
          :nombre-mascota="nombreMascota"
          :puede-contactar="puedeContactarTutor"
          :oferta-id="ofertaId"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useRoute } from 'vue-router'
import SinPermisoHistorial from '@/components/ElementosGraficos/SinPermisoHistorial.vue'

const props = defineProps({
  mascotaId: {
    type: [Number, String],
    required: true
  },
  ofertaId: {
    type: [Number, String],
    default: null
  },
  tienePermisoHistorial: {
    type: Boolean,
    default: true
  },
  nombreMascota: {
    type: String,
    default: 'la mascota'
  },
  puedeContactarTutor: {
    type: Boolean,
    default: false
  }
})

const route = useRoute()

const navItems = computed(() => {
  const isOverlay = route.meta.overlay
  return [
    { 
      name: isOverlay ? 'veterinario-cirugias' : 'cirugias', 
      icon: 'heart-pulse', 
      label: 'Cirugías'
    },
    { 
      name: isOverlay ? 'veterinario-farmacos' : 'farmacos', 
      icon: 'prescription-bottle-medical', 
      label: 'Fármacos'
    },
    { 
      name: isOverlay ? 'veterinario-terapias' : 'terapias', 
      icon: 'bandage', 
      label: 'Terapias'
    },
    { 
      name: isOverlay ? 'veterinario-diagnosticos' : 'diagnosticos', 
      icon: 'microscope', 
      label: 'Diagnósticos'
    },
    { 
      name: isOverlay ? 'veterinario-paliativos' : 'paliativos', 
      icon: 'staff-snake', 
      label: 'Paliativos'
    }
  ]
})

onMounted(() => {
  console.log('📋 HistorialClinico montado', {
    mascotaId: props.mascotaId,
    ofertaId: props.ofertaId,
    tienePermiso: props.tienePermisoHistorial,
    nombreMascota: props.nombreMascota,
    puedeContactar: props.puedeContactarTutor
  })
})

// Watch para debug
watch(() => props.tienePermisoHistorial, (newVal) => {
  console.log('🔄 HistorialClinico - Permiso cambiado:', newVal)
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
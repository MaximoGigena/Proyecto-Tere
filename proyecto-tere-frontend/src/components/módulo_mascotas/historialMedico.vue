<template>
  <div class="flex flex-col h-[calc(100vh-140px)] w-full">
    <!-- Navbar de íconos -->
    <nav class="flex justify-around items-center bg-white border-b border-gray-200 py-3 sticky top-0 z-20">
      <router-link 
        v-for="nav in navItems"
        :key="nav.name"
        :to="{
          name: nav.name,
          query: {
            ...$route.query, // Mantiene from/originalParams
            tab: nav.name // Opcional: para tracking
          }
        }"
        class="flex flex-col items-center p-2 rounded-full text-gray-500 hover:text-blue-500 transition-all duration-200"
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

    <!-- Contenido dinámico con verificación de existencia -->
    <div v-if="$route.matched.length" class="flex-1 overflow-y-auto p-4">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" v-if="Component" />
        </transition>
      </router-view>
    </div>
  </div>
</template>


<script>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'


export default {
  name: "HistorialMedico",
  components: {
    'font-awesome-icon': FontAwesomeIcon
  },
  data() {
    // si queres dinamismo en las rutas hijas agregalas aca 
    return {
      navItems: [
        { 
          name: this.$route.meta.overlay ? 'veterinario-cirugias' : 'cirugias', 
          icon: 'heart-pulse', 
          label: 'Cirugías'
        },
        { 
          name: this.$route.meta.overlay ? 'veterinario-tratamientos' : 'tratamientos', 
          icon: 'file-waveform', 
          label: 'Tratamientos'
        },
        { 
          name: this.$route.meta.overlay ? 'veterinario-medicamentos' : 'medicamentos', 
          icon: 'prescription-bottle-medical', 
          label: 'Medicamentos'
        },
        { 
          name: this.$route.meta.overlay ? 'veterinario-terapias' : 'terapias', 
          icon: 'bandage', 
          label: 'Terapias'
        }
      ]
    }
  },
  computed: {
    currentRouteName() {
      return this.$route.name;
    }
  },
  watch: {
    '$route'(to) {
      // Forzar recarga si cambia el ID de la mascota
      if (to.params.id !== this.$route.params.id) {
        this.$router.go(0); // Recarga suave
      }
    }
  }
}
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

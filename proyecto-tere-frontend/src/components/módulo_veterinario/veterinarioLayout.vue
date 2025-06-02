<!-- VeterinarioLayout.vue -->
<template>
  <div class="flex h-screen">
    <!-- Sidebar FIJA -->
    <VeterinarioSideBarMascotas class="fixed left-0 top-0 h-full w-72 z-50" />

    <!-- Contenido a la derecha -->
    <div class="ml-72 flex-1 h-full overflow-y-auto bg-gray-50">
      <router-view name="right" v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </div>
  </div>
</template>

<script>
import VeterinarioSideBarMascotas from './sideBarMascotas.vue'

export default {
  components: {
    VeterinarioSideBarMascotas,
  },
};
</script>

<style>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>

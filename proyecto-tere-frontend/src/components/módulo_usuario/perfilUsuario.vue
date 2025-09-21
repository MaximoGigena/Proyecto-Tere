<!-- views/perfilUsuario -->
<template>
  <div class="w-full h-full flex flex-col relative bg-gray-50"> <!-- Añadido bg-gray-50 para el fondo -->
    <!-- Header -->
    <div class="sticky top-0 z-30 bg-white px-4 py-3 shadow">
      <div class="flex items-center justify-between text-2xl font-bold text-gray-800">
        <span>Perfil de Usuario</span>
        <div class="flex gap-4 text-xl text-gray-600">
          <router-link
            to="/usuarioConfiguracion"
            title="Configuración"
            class="hover:text-black transition"
          >
            <font-awesome-icon :icon="['fas', 'gear']" />
          </router-link>
          <router-link
            to="/usuarioEdicion"
            title="Editar Datos"
            class="hover:text-black transition"
          >
             <font-awesome-icon :icon="['fas', 'user-pen']"/>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Contenido Scrollable -->
    <div class="flex-1 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
      <!-- Contenedor principal con márgenes -->
      <div class="max-w-4xl mx-auto w-full px-4 py-2">
        <!-- Sección de información del usuario -->
        <div class="flex items-center gap-6 p-4 bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
        <img src="https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg" 
            alt="usuario"
            class="w-28 h-28 rounded-full object-cover border-4 border-indigo-500" />
        <div>
          <p class="text-gray-900 font-semibold text-lg hover:underline cursor-pointer">Nombre de usuario</p>
          <p class="text-gray-500 text-sm mt-1 flex items-center gap-2">
            <i class="fa-solid fa-envelope text-indigo-500"></i>
            email@usuario.com
          </p>
        </div>
      </div>


       <!-- Tabs -->
      <div class="flex justify-center border-b text-sm font-medium mb-6">
        <div class="flex items-center gap-48 text-xl font-bold">
           <router-link 
              to="/explorar/perfil/mascotas" 
              class="w-36 px-3 py-2 text-center"
              :class="{
                'border-b-2 border-black text-black': activeTab === 'mascotas',
                'text-gray-400': activeTab !== 'mascotas'
              }"
            >
            Mascotas
          </router-link>
          <router-link 
            to="/explorar/perfil/adopciones" 
            class="w-36 px-3 py-2 text-center"
            :class="{
              'border-b-2 border-black text-black': $route.path.endsWith('/adopciones'),
              'text-gray-400': !$route.path.endsWith('/adopciones')
            }"
          >
            Adopciones
          </router-link>
        </div>
      </div>

        <!-- Contenido dinámico -->
        <div class="w-full min-h-[400px] bg-white rounded-lg shadow-sm p-4 mb-16">
          <router-view></router-view>
        </div>
      </div>
      
      <div class="h-15"></div>
    </div>
  </div>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router'
import { computed } from 'vue'
const router = useRouter()
const route = useRoute()

const activeTab = computed(() => {
  return route.meta.activeTab || 'mascotas' // Default a 'mascotas' si no existe meta
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>


<!-- MetricasUsuario.vue -->
<template>
  <div class="metricas-usuario bg-gray-50 min-h-screen p-4 md:p-6">
    <!-- Encabezado separado para router de pestañas -->
    <div class="metricas-header flex flex-col md:flex-row md:items-center justify-between mb-8 pb-6 border-b border-gray-200">
      <!-- Botón de regreso -->
        <button 
          @click="goBack" 
          class="back-button flex items-center justify-center w-10 h-10 rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition-all duration-200"
          title="Regresar"
        >
          <span class="text-xl">←</span>
        </button>
      
      <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4 md:mb-0">
        {{ titulo }}
      </h1>
      
      <!-- Pestañas de navegación -->
      <div class="tabs-container">
        <nav class="tabs-navigation flex flex-wrap gap-2 md:gap-1">
          <router-link 
            v-for="tab in tabs"
            :key="tab.name"
            :to="{ name: tab.name }"
            class="tab-link px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
            :class="[
              $route.name === tab.name 
                ? 'bg-blue-600 text-white shadow-md' 
                : 'bg-white text-gray-700 hover:bg-gray-100 hover:text-gray-900 border border-gray-300'
            ]"
          >
            <span class="flex items-center gap-2">
              <span v-if="tab.icon">{{ tab.icon }}</span>
              {{ tab.label }}
            </span>
          </router-link>
        </nav>
      </div>
    </div>
    
    <!-- Contenido principal donde se renderizarán las vistas -->
    <div class="metricas-content">
      <router-view />
    </div>
  </div>
</template>

<script>
export default {
  name: 'MetricasUsuario',
  
  props: {
    titulo: {
      type: String,
      default: 'Métricas de Usuarios'
    },
    reporteInicial: {
      type: String,
      default: 'volumen'
    }
  },
  
  data() {
    return {
      tabs: [
        {
          name: 'MetricasVolumen',
          label: 'Métricas',
          icon: '👥'
        },
        {
          name: 'MetricasReportes',
          label: 'Reportes',
          icon: '📋'
        }
      ]
    };
  },

  methods: {
    goBack() {
      this.$router.back();
    }
  }
};
</script>

<style scoped>
.tab-link {
  min-width: 100px;
  text-align: center;
}

.tab-link:hover {
  transform: translateY(-2px);
}

.tab-link.router-link-exact-active {
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

@media (max-width: 640px) {
  .tabs-navigation {
    justify-content: center;
  }
  
  .tab-link {
    flex: 1;
    min-width: auto;
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
  }
}
</style>
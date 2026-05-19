<template>
  <div class="fixed left-0 top-0 h-screen w-92 bg-white shadow-lg border-r overflow-y-auto z-50 p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold">Buscar Mascotas</h1>
      <button
        @click="abrirFiltro"
        class="inline-flex whitespace-nowrap items-center gap-2 px-5 py-2.5 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-700 hover:text-white transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-300"
      >
        <span class="font-medium text-sm sm:text-base">Filtrar Mascotas</span>
        <font-awesome-icon :icon="['fas', 'filter']" class="text-lg sm:text-xl" />
      </button>
    </div>

    <!-- Selector de tipo de búsqueda -->
    <div class="mb-4">
      <select v-model="tipoBusqueda" class="w-full p-2 border rounded mb-2">
        <option value="nombre">Por nombre de mascota</option>
        <option value="tutor">Por nombre/email del tutor</option>
        <option value="especie">Por especie</option>
      </select>
      
      <input
        v-model="busqueda"
        type="text"
        :placeholder="getPlaceholder()"
        class="w-full p-2 border rounded"
        @keyup.enter="buscarMascotas(busqueda, tipoBusqueda)"
      />
    </div>

    <!-- Estado de carga -->
    <div v-if="cargando" class="text-center py-4">
      <div class="inline-flex items-center gap-2">
        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
        <span class="text-gray-500">Buscando mascotas...</span>
      </div>
    </div>

    <!-- Resultados -->
    <ul v-else-if="mascotas.length > 0">
      <li
        v-for="mascota in mascotas"
        :key="mascota.id"
        class="p-3 border rounded mb-2 flex items-center gap-4 hover:bg-gray-50 transition-colors"
      >
        <img
          :src="getMascotaFoto(mascota)"
          alt="Foto de la mascota"
          class="w-16 h-16 object-cover rounded-full border"
        />
        
        <div class="flex-1">
          <p class="font-semibold">{{ mascota.nombre }}</p>
          <p class="text-sm text-gray-500">Especie: {{ mascota.especie }}</p>
          <p class="text-sm text-gray-500">Tutor: {{ getNombreTutor(mascota) }}</p>

          <div class="flex justify-center mt-3 mr-12">
            <button
              @click="verHistorial(mascota)"
              class="bg-blue-500 text-white px-4 py-1.5 rounded-full hover:bg-blue-600 transition-colors"
            >
              Ver Historial
            </button>
          </div>
        </div>
      </li>
    </ul>

    <!-- Sin resultados -->
    <div v-else-if="busqueda.length >= 2 && !cargando" class="text-center py-4">
      <span class="text-gray-500">No se encontraron mascotas</span>
    </div>

    <!-- Instrucción inicial -->
    <div v-else class="text-center py-8">
      <p class="text-gray-400">Ingresa al menos 2 caracteres para buscar</p>
    </div>

    <router-view name="overlay" v-slot="{ Component }">
      <transition name="slide-up">
        <component :is="Component" />
      </transition>
    </router-view>
  </div>
</template>

<script>
import { ref, watch } from 'vue';
import { storeToRefs } from 'pinia'; // ✅ IMPORTAR storeToRefs
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth'
import { useBusquedaStore } from '@/stores/busquedaMascotasStore'

export default {
  name: 'VeterinarioBuscarMascotas',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const { accessToken, isAuthenticated, checkAuth } = useAuth()
    const busquedaStore = useBusquedaStore()

    // ✅ USAR storeToRefs para mantener la reactividad
    const { busqueda, tipoBusqueda, mascotas, cargando } = storeToRefs(busquedaStore)
    
    // Obtener las funciones del store (no son refs)
    const { setResultados, setBusqueda, setCargando, limpiarResultados } = busquedaStore

    // Debounce para búsquedas
    let timeoutId = null;

    const getPlaceholder = () => {
      const placeholders = {
        nombre: 'Buscar por nombre de mascota...',
        tutor: 'Buscar por nombre o email del tutor...', 
        especie: 'Buscar por especie (canino, felino...)...'
      };
      return placeholders[tipoBusqueda.value];
    };

    const getNombreTutor = (mascota) => {
      if (mascota.usuario?.nombre) {
        return mascota.usuario.nombre;
      }
      if (mascota.tutor_nombre) {
        return mascota.tutor_nombre;
      }
      return 'N/A';
    };

    const getMascotaFoto = (mascota) => {
      console.log('Debug - mascota.fotos:', mascota.fotos);
      
      // Si no hay fotos, placeholder
      if (!mascota.fotos || mascota.fotos.length === 0) {
        return 'https://via.placeholder.com/100?text=Sin+Foto';
      }
      
      // Usar foto_principal_url si existe (más simple)
      if (mascota.foto_principal_url) {
        console.log('Usando foto_principal_url:', mascota.foto_principal_url);
        return mascota.foto_principal_url;
      }
      
      // Buscar foto principal manualmente
      const fotoPrincipal = mascota.fotos.find(foto => foto.es_principal === 1 || foto.es_principal === true);
      const foto = fotoPrincipal || mascota.fotos[0];
      
      console.log('Foto seleccionada:', foto);
      
      // Priorizar optimized_urls.thumbnail
      if (foto.optimized_urls && foto.optimized_urls.thumbnail) {
        return foto.optimized_urls.thumbnail;
      }
      
      // Si no, usar url
      if (foto.url) {
        return foto.url;
      }
      
      // Último recurso: construir URL desde ruta_foto
      if (foto.ruta_foto) {
        let ruta = foto.ruta_foto;
        if (ruta.startsWith('storage/')) {
          return '/' + ruta;
        }
        if (!ruta.startsWith('/storage/') && !ruta.startsWith('http')) {
          return '/storage/' + ruta;
        }
        return ruta;
      }
      
      return 'https://via.placeholder.com/100?text=Sin+Foto';
    };

    const buscarMascotas = async (termino, tipo) => {
      if (!termino || termino.length < 2) {
        setResultados([]);
        return;
      }

      if (!isAuthenticated.value) {
        console.error('Usuario no autenticado');
        await checkAuth();
        if (!isAuthenticated.value) {
          router.push('/login');
          return;
        }
      }

      try {
        setCargando(true);
        
        const params = new URLSearchParams({
          termino: termino.trim(),
          tipo
        });
        
        const url = `/api/mascotas/buscar?${params}`;
        
        console.log('🔍 Buscando mascotas:', url);
        
        const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${accessToken.value}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        });

        console.log('📡 Respuesta del servidor:', response.status, response.statusText);

        if (!response.ok) {
          if (response.status === 401) {
            throw new Error('No autorizado - token inválido o expirado');
          }
          throw new Error(`Error ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        console.log('✅ Datos recibidos:', result);
        console.log('📊 Cantidad de mascotas:', result.mascotas?.length || 0);
        
        // Guardar en el store
        setResultados(result.mascotas || []);
        setBusqueda(termino, tipo);
        
        // Debug: Verificar que se guardaron
        console.log('💾 Store después de guardar:', {
          mascotas_en_store: mascotas.value.length,
          primer_elemento: mascotas.value[0]
        });
        
      } catch (error) {
        console.error('❌ Error buscando mascotas:', error);
        setResultados([]);
        
        if (error.message.includes('No autorizado')) {
          console.error('🔐 No autorizado - redirigiendo a login');
          router.push('/login');
        }
      } finally {
        setCargando(false);
      }
    };

    // Watch para búsqueda automática
    watch([busqueda, tipoBusqueda], ([nuevoTermino, nuevoTipo]) => {
      if (timeoutId) clearTimeout(timeoutId);
      
      if (nuevoTermino.length >= 2) {
        timeoutId = setTimeout(() => {
          buscarMascotas(nuevoTermino, nuevoTipo);
        }, 500);
      } else if (nuevoTermino.length === 0) {
        setResultados([]);
      }
    });

    function abrirFiltro() {
      router.push({ name: 'buscarMascotasConFiltros' });
    }

    function verHistorial(mascota) {
      const query = {
        ...route.query,
        from: route.name || 'buscarMascotas',
        originalParams: JSON.stringify(route.params),
        id: mascota.id,
        currentTab: 'tutores',
        ts: Date.now()
      };

      router.replace({
        name: 'veterinario-tutores',
        params: { id: mascota.id },
        query,
      });
    }

    return {
      busqueda,
      tipoBusqueda,
      mascotas,
      cargando,
      route,
      verHistorial,
      abrirFiltro,
      getPlaceholder,
      getMascotaFoto,
      getNombreTutor,
      buscarMascotas
    };
  }
};
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

.slide-up-enter-to,
.slide-up-leave-from {
  transform: translateY(0);
  opacity: 1;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
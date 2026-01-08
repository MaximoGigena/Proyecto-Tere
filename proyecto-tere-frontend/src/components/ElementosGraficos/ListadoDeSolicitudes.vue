<template>
  <div class="relative mt-3 px-3">
    <!-- Flecha izquierda -->
    <button
      @click="scrollLeft"
      class="absolute left-4 top-1/2 -translate-y-1/2 z-10 p-3 bg-gray-800/80 hover:bg-gray-700/90 backdrop-blur-sm rounded-full transition-all duration-300 shadow-lg hover:shadow-xl border border-gray-600/30 hover:scale-110 group"
      v-if="perfilesConPaletas.length > 3"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white group-hover:text-amber-400 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
    </button>
    
    <!-- Lista scrollable -->
    <div
      ref="listaPerfiles"
      class="flex gap-3 overflow-x-auto scrollbar-thin scrollbar-thumb-green-300/40 scrollbar-track-transparent"
    >
      <div
        v-if="perfilesConPaletas.length === 0"
        class="flex flex-col items-center justify-center min-w-full py-8"
      >
        <p class="text-gray-500 text-sm">No hay solicitudes pendientes</p>
      </div>
      
      <!-- Router-link simplificado -->
      <router-link
        v-for="(perfil, index) in perfilesConPaletas" 
        :key="perfil.unique_key || perfil.solicitud_id || index" 
        :to="generarRuta(perfil)"
        :class="[
          'flex flex-col items-center shadow-sm hover:shadow-md transition rounded-xl p-3 min-w-[110px] cursor-pointer',
          perfil.bgClass
        ]"
        :title="`Solicitud ${perfil.solicitud_id} para adoptar a ${perfil.mascota_nombre || 'tu mascota'}`"
      >
        <div class="relative">
          <img
            :src="perfil.img"
            class="w-16 h-16 rounded-full object-cover border-2 border-white"
            :alt="perfil.nombre"
            @error="setDefaultAvatar"
          />
          <span
            class="absolute -bottom-1 left-1/2 -translate-x-1/2 text-[9px] px-1.5 py-0.5 text-white rounded-full shadow-sm"
            :class="perfil.badgeClass"
          >
            Solicitud
          </span>
        </div>
        <span class="mt-2 text-xs font-medium text-white text-center line-clamp-2">
          {{ perfil.nombre }}
        </span>
        <span v-if="perfil.mascota_nombre" class="text-[10px] text-white/80 mt-1">
          Para: {{ perfil.mascota_nombre }}
        </span>
      </router-link>
    </div>

    <!-- Flecha derecha -->
    <button
      @click="scrollRight"
      class="absolute right-4 top-1/2 -translate-y-1/2 z-10 p-3 bg-gray-800/80 hover:bg-gray-700/90 backdrop-blur-sm rounded-full transition-all duration-300 shadow-lg hover:shadow-xl border border-gray-600/30 hover:scale-110 group"
      v-if="perfilesConPaletas.length > 3"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white group-hover:text-amber-400 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
      </svg>
    </button>
  </div>
</template>

<script setup>
import { ref, reactive, defineProps, defineEmits, watch } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const listaPerfiles = ref(null);

// Definir props y eventos
const props = defineProps({
  perfiles: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['abrir-perfil']);

// Función para fallback de avatar
const setDefaultAvatar = (event) => {
  event.target.src = 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png';
};

// Paletas de colores
const palettes = [
  { bgClass: 'bg-gradient-to-r from-blue-400 to-orange-300', badgeClass: 'bg-blue-600' },
  { bgClass: 'bg-gradient-to-r from-green-400 to-yellow-300', badgeClass: 'bg-green-600' },
  { bgClass: 'bg-gradient-to-r from-purple-400 to-pink-300', badgeClass: 'bg-purple-600' },
  { bgClass: 'bg-gradient-to-r from-teal-400 to-cyan-300', badgeClass: 'bg-teal-600' },
  { bgClass: 'bg-gradient-to-r from-indigo-400 to-blue-300', badgeClass: 'bg-indigo-600' },
  { bgClass: 'bg-gradient-to-r from-red-400 to-orange-400', badgeClass: 'bg-red-600' },
];

// Variable reactiva para los perfiles con paletas
const perfilesConPaletas = reactive([]);

// Función para generar ruta dinámica
const generarRuta = (perfil) => {
  // Verificar si el perfil tiene los datos necesarios
  const solicitanteId = perfil.solicitante_id || perfil.id;
  
  if (!solicitanteId) {
    console.error('Perfil sin solicitante_id:', perfil);
    return { name: 'home' };
  }
  
  // Solo pasar userId como parámetro, el resto como query
  return {
    name: 'user-profile-list',
    params: { 
      userId: solicitanteId.toString()
    },
    query: { 
      from: 'chats-list',
      solicitud_id: perfil.solicitud_id || perfil.id,
      mascota_nombre: perfil.mascota_nombre,
      fecha_solicitud: perfil.fecha_solicitud,
      mascota_id: perfil.mascota_id,
      unique_key: perfil.unique_key || `${solicitanteId}_${perfil.mascota_id}_${perfil.solicitud_id || perfil.id}`
    }
  };
};

// Función para actualizar perfiles con paletas
const actualizarPerfilesConPaletas = () => {
  console.log('🔄 ListadoDeSolicitudes: Actualizando perfiles...');
  
  // Limpiar el array
  perfilesConPaletas.splice(0, perfilesConPaletas.length);
  
  // Si hay perfiles en props, usarlos
  if (props.perfiles && props.perfiles.length > 0) {
    console.log(`ListadoDeSolicitudes: Recibidos ${props.perfiles.length} perfiles`);
    
    // Crear un mapa para evitar duplicados (usuario + mascota)
    const perfilesUnicos = new Map();
    
    props.perfiles.forEach((perfil, index) => {
      const paletteIndex = index % palettes.length;
      
      // Verificar que tenga datos mínimos
      if (!perfil.solicitante_id && !perfil.id) {
        console.warn('Perfil sin identificador:', perfil);
        return;
      }
      
      // Crear una clave única combinando usuario y mascota
      const solicitanteId = perfil.solicitante_id || perfil.id;
      const mascotaId = perfil.mascota_id || 'sin-mascota';
      const claveUnica = `${solicitanteId}_${mascotaId}`;
      
      console.log(`Procesando: Usuario ${solicitanteId} - Mascota ${mascotaId} - Clave ${claveUnica}`);
      
      // Solo agregar si no existe ya este usuario+mascota
      if (!perfilesUnicos.has(claveUnica)) {
        // Crear perfil con paleta
        const perfilConPaleta = {
          ...perfil,
          ...palettes[paletteIndex],
          // Asegurar que todos los campos necesarios existan
          id: perfil.solicitud_id || perfil.id, // ID de la solicitud
          solicitud_id: perfil.solicitud_id || perfil.id,
          solicitante_id: perfil.solicitante_id || perfil.id,
          mascota_id: perfil.mascota_id,
          unique_key: claveUnica + '_' + (perfil.solicitud_id || perfil.id)
        };
        
        perfilesUnicos.set(claveUnica, perfilConPaleta);
        perfilesConPaletas.push(perfilConPaleta);
        
        console.log(`  ✅ Agregado: Solicitud ${perfilConPaleta.solicitud_id} para ${perfilConPaleta.mascota_nombre}`);
      } else {
        console.log(`  ⚠️  Omitido duplicado: Ya existe solicitud para usuario ${solicitanteId} y mascota ${mascotaId}`);
      }
    });
    
    console.log(`Total perfiles únicos mostrados: ${perfilesConPaletas.length}`);
  } else {
    console.log('No hay perfiles para mostrar en el carrusel');
  }
};

// Inicializar perfiles con paletas
actualizarPerfilesConPaletas();

// Observar cambios en las props
watch(() => props.perfiles, () => {
  console.log('🔍 Props cambiaron, actualizando perfiles...');
  actualizarPerfilesConPaletas();
}, { deep: true });

// Funciones de scroll
function scrollLeft() {
  if (listaPerfiles.value) {
    listaPerfiles.value.scrollBy({
      left: -150,
      behavior: 'smooth'
    });
  }
}

function scrollRight() {
  if (listaPerfiles.value) {
    listaPerfiles.value.scrollBy({
      left: 150,
      behavior: 'smooth'
    });
  }
}

// Función para abrir perfil (compatibilidad)
function abrirPerfilUsuario(perfil) {
  console.log('👤 Abriendo perfil desde ListadoDeSolicitudes:', perfil);
  
  // Emitir el evento al componente padre
  emit('abrir-perfil', perfil.solicitante_id || perfil.id);
}
</script>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
  height: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background-color: rgba(134, 239, 172, 0.4);
  border-radius: 20px;
}

.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
</style>
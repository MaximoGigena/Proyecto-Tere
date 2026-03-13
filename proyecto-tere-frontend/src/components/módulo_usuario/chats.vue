<template>
  <div class="w-full h-full flex flex-col relative">
    <!-- Header sticky dentro del scroll -->
    <div class="sticky top-0 z-30 bg-white px-4 py-2">
      <!-- Primera fila: título y botón -->
      <div class="flex items-center justify-between px-2">
        <div class="text-2xl font-bold text-gray-800 pointer-events-none">
          Chats
        </div>
        <button
          @click="toggleNotifications"
          class="relative text-gray-700 hover:text-black transition"
        >
          <font-awesome-icon :icon="['fas', 'bell']" class="text-2xl"/>

          <!-- Badge -->
          <span
            v-if="totalNotificaciones > 0"
            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs 
                  w-5 h-5 rounded-full flex items-center justify-center"
          >
            {{ totalNotificaciones }}
          </span>
        </button>
      </div>

      <!-- Segunda fila: título solicitudes -->
      <h2 class="text-xl font-semibold mt-4 mb-2 px-2 text-gray-800 tracking-wide">
        {{ totalSolicitudesPendientes }} Solicitudes de Adopción
      </h2>

      <!-- Tercera fila: Componente de solicitudes -->
      <SolicitudesLista 
        :perfiles="solicitudesRecibidasFormateadas"
        @abrir-perfil="abrirPerfilUsuario"
      />
    </div>
    
   
    <!-- Notificaciones Overlay - COMPLETAMENTE FUERA DE LA ESTRUCTURA -->
    <NotificationsOverlay
      v-if="showNotifications"
      :notificaciones="notificaciones"
      @close="showNotifications = false"
    />
    
    <div class="sticky z-30 bg-white px-4 py-2 border-b border-gray-600"></div>
    
    <!-- Contenedor de scroll -->
    <div
      ref="scrollContainer"
      class="relative w-full flex-1 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden px-4"
    > 
      <!-- Notificación de chats -->
      <div class="flex items-center gap-12">
        <!-- Notificación de solicitudes con botón de filtro adentro -->
        <div class="relative flex items-center justify-between border border-black rounded-full mt-4 
            bg-white text-black w-full px-4">
  
          <!-- Grupo izquierdo (sobre + texto) -->
          <div class="flex items-center gap-4">
            <!-- Icono de sobre (igual) -->
            <div class="w-16 h-16 flex items-center -ml-4 justify-center rounded-full bg-black border-2 border-white">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 4H4C2.897 4 2 4.897 2 6v12c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V6c0-1.103-.897-2-2-2zm0 2v.511l-8 5-8-5V6h16zm-16 12V9.489l7.386 4.616a1 1 0 0 0 1.228 0L20 9.489V18H4z"/>
              </svg>
            </div>

            <!-- Texto de notificación con contadores separados -->
            <div class="flex flex-col">
              <span class="text-lg font-semibold text-black">
                {{ mensajeSolicitudes }}
              </span>
            </div>
          </div>

          <!-- Botón de filtro -->
          <div class="relative">
            <button
              @click="open = !open"
             class="flex items-center gap-2 px-4 py-2 rounded-full 
              bg-gradient-to-r from-gray-400 via-gray-500 to-gray-600 
              text-white font-medium shadow-lg transition-transform duration-300 hover:scale-105"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 019 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
              </svg>
              <span class="text-sm">{{ selectedFilter || "Filtrar por" }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Dropdown -->
            <transition name="fade">
              <div v-if="open" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl z-50">
                <ul class="py-2">
                  <li
                    v-for="(filter, index) in filters"
                    :key="index"
                    @click="selectFilter(filter)"
                    class="px-4 py-2 cursor-pointer hover:bg-indigo-100 rounded-lg transition-colors"
                  >
                    {{ filter }}
                  </li>
                </ul>
              </div>
            </transition>
          </div>
        </div>
      </div>

      <div class="h-2"></div>

      <!-- Loading -->
      <div v-if="loadingChats" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Lista de chats -->
      <div v-else>
        <div v-for="(chat, index) in chatsFiltrados" :key="index" class="relative">
          <div class="flex justify-between items-center gap-3 mb-2 min-h-[72px] transition duration-200 hover:bg-blue-100 rounded-xl">
            <router-link
              :to="{
                path: `/explorar/chats/${chat.id || chat.chat_id || (index + 1)}`,
                query: {
                  nombre: chat.nombre,
                  img: chat.img,
                  from: 'chats',
                  solicitud_id: chat.solicitud_id
                }
              }"
              class="flex-1 flex items-start gap-3 py-4 pl-4"
            >
              <div class="relative">
                <img :src="chat.img" class="w-16 h-16 rounded-full object-cover" :alt="chat.nombre">
                <!-- Indicador de online -->
                <div v-if="chat.online" class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 rounded-full border-2 border-white"></div>
              </div>
              <div class="flex-1">
                <div class="flex items-center justify-between">
                  <p class="font-semibold text-lg">{{ chat.nombre }}</p>
                  <span v-if="chat.mascota_nombre" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                    {{ chat.mascota_nombre }}
                  </span>
                </div>
                <p class="text-sm text-gray-700 truncate">{{ chat.mensaje }}</p>
                <div class="flex items-center justify-between mt-1">
                  <span class="text-xs text-gray-500">
                    {{ chat.fecha ? formatFecha(chat.fecha) : '' }}
                  </span>
                  <span v-if="chat.mensajes_no_leidos > 0" 
                        class="bg-blue-600 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center">
                    {{ chat.mensajes_no_leidos }}
                  </span>
                </div>
              </div>
            </router-link>
            
            <button
              @click.stop="toggleFavorite(chat)"
              class="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 p-2 transition duration-200 mt-2"
              :class="chat.favorito ? 'text-green-400' : 'text-gray-400 hover:text-gray-500'"
            >
              <font-awesome-icon :icon="['fas', 'star']" class="text-2xl"/>
            </button>
          </div>
        </div>

        <!-- Sin chats -->
        <div v-if="chats.length === 0" class="text-center py-12">
          <svg class="w-24 h-24 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <p class="text-gray-500 text-lg">No tienes chats activos</p>
          <p class="text-gray-400 text-sm">Inicia una conversación desde una solicitud de adopción</p>
        </div>
      </div>
      
      <div class="h-28"></div>
    </div>
  </div>
</template>
  
<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import SolicitudesLista from '@/components/ElementosGraficos/ListadoDeSolicitudes.vue';
import NotificationsOverlay from '@/components/módulo_usuario/NotificacionesOverlay.vue';
import useNotificaciones from '@/composables/useNotificaciones';

const router = useRouter();

// Usar el composable de notificaciones
const {
  notificaciones,
  estadisticas,
  cargando: cargandoNotificaciones,
  error: errorNotificaciones,
  cargarNotificaciones,
  hasMore
} = useNotificaciones();

// Variables locales
const open = ref(false);
const selectedFilter = ref(null);
const filters = ref(["Todos", "Favoritos"]);

// Datos reactivos
const solicitudesRecibidas = ref([]);
const chats = ref([]);
const loadingChats = ref(false);
const loadingSolicitudes = ref(false);
const showNotifications = ref(false);

// Variables para notificaciones (definidas localmente)
const totalNoLeidas = ref(0);

// Computed properties
const totalNotificaciones = computed(() => {
  if (estadisticas.value?.no_leidas > 0) return estadisticas.value.no_leidas;
  return notificaciones.value.length;
});

const totalChatsActivos = computed(() => {
  return chats.value.length;
});

const solicitudesRecibidasFormateadas = computed(() => {
  console.log('🔄 Formateando solicitudes para ListadoDeSolicitudes:', solicitudesRecibidas.value);
  
  if (!solicitudesRecibidas.value || solicitudesRecibidas.value.length === 0) {
    return [];
  }
  
  return solicitudesRecibidas.value.map(solicitud => {
    return {
      id: solicitud.solicitud_id || solicitud.id,
      solicitud_id: solicitud.solicitud_id || solicitud.id,
      solicitante_id: solicitud.solicitante_id || solicitud.id,
      nombre: solicitud.nombre,
      img: solicitud.img,
      mascota_id: solicitud.mascota_id,
      mascota_nombre: solicitud.mascota_nombre,
      fecha_solicitud: solicitud.fecha_solicitud,
      estado: solicitud.estado,
      unique_key: `${solicitud.solicitante_id || solicitud.id}_${solicitud.mascota_id}_${solicitud.solicitud_id || solicitud.id}`
    };
  });
});

const totalSolicitudesPendientes = computed(() => {
  return solicitudesRecibidas.value.filter(s => s.estado === 'pendiente').length;
});

const totalSolicitudes = computed(() => {
  return solicitudesRecibidas.value.length;
});

const mensajeSolicitudes = computed(() => {
  const solicitudesCount = totalSolicitudesPendientes.value;
  const chatsCount = totalChatsActivos.value;
  
  if (solicitudesCount === 0 && chatsCount === 0) {
    return 'No tienes solicitudes ni chats activos';
  }
  
  if (solicitudesCount === 0) {
    return `No tienes solicitudes y ${chatsCount} ${chatsCount === 1 ? 'chat activo' : 'chats activos'}`;
  }
  
  if (chatsCount === 0) {
    return `${solicitudesCount} ${solicitudesCount === 1 ? 'solicitud' : 'solicitudes'} y 0 chats activos`;
  }
  
  const solicitudText = solicitudesCount === 1 ? 'solicitud' : 'solicitudes';
  const chatText = chatsCount === 1 ? 'chat activo' : 'chats activos';
  
  return `Tienes ${solicitudesCount} ${solicitudText} y ${chatsCount} ${chatText}`;
});

const chatsFiltrados = computed(() => {
  if (!selectedFilter.value || selectedFilter.value === 'Todos') {
    return chats.value;
  }
  
  if (selectedFilter.value === 'Favoritos') {
    return chats.value.filter(chat => chat.favorito === true);
  }
  
  return chats.value;
});

// Funciones
const formatFecha = (fecha) => {
  if (!fecha) return '';
  
  try {
    if (typeof fecha === 'string') {
      const date = new Date(fecha);
      return date.toLocaleDateString('es-ES', { 
        day: '2-digit', 
        month: 'short' 
      });
    }
    return fecha;
  } catch (e) {
    return fecha;
  }
};

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
  if (showNotifications.value && notificaciones.value.length === 0) {
    cargarNotificaciones();
  }
};

async function cargarChats() {
  try {
    loadingChats.value = true;
    console.log('Cargando chats...');
    
    const response = await axios.get('/api/chats', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    if (response.data.success) {
      chats.value = response.data.data.chats.map(chat => ({
        id: chat.chat_id,
        chat_id: chat.chat_id,
        usuario_id: chat.usuario_id,
        nombre: chat.nombre,
        img: chat.img,
        mensaje: chat.ultimo_mensaje || 'Inicia una conversación',
        fecha: chat.ultimo_mensaje_en,
        mensajes_no_leidos: chat.mensajes_no_leidos || 0,
        solicitud_id: chat.solicitud_id,
        mascota_nombre: chat.mascota_nombre,
        favorito: chat.favorito || false,
        online: chat.online || false
      }));
      
      console.log(`Cargados ${chats.value.length} chats`);
    } else {
      console.error('Error en respuesta:', response.data);
      mantenerDatosEjemplo();
    }
  } catch (err) {
    console.error('Error cargando chats:', err);
    mantenerDatosEjemplo();
  } finally {
    loadingChats.value = false;
  }
}

function mantenerDatosEjemplo() {
  console.log('Usando datos de ejemplo para chats');
  chats.value = [{
    id: 1,
    nombre: 'Josefina, dolores del mes',
    mensaje: 'Te molesta si me como a tu perro?',
    img: 'https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg',
    favorito: false,
    online: true,
    mensajes_no_leidos: 2
  }];
}

async function toggleFavorite(chat) {
  try {
    const chatId = chat.chat_id || chat.id;
    
    if (!chatId) {
      console.error('Error: El chat no tiene ID válido', chat);
      return;
    }
    
    console.log(`Enviando solicitud para chat ID: ${chatId}`);
    
    const estadoAnterior = chat.favorito;
    chat.favorito = !chat.favorito;
    
    const baseURL = axios.defaults.baseURL || 'http://localhost:8000';
    const url = `${baseURL}/api/chats/${chatId}/favorite`;
    
    console.log('URL completa:', url);
    
    const response = await axios.post(url, {}, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });

    if (!response.data.success) {
      chat.favorito = estadoAnterior;
      console.error('Error en respuesta:', response.data);
    }
  } catch (err) {
    console.error('Error actualizando favorito:', {
      mensaje: err.message,
      status: err.response?.status,
      data: err.response?.data,
      config: err.config
    });
    
    if (chat) chat.favorito = !chat.favorito;
  }
}

async function cargarSolicitudesRecibidas() {
  try {
    loadingSolicitudes.value = true;
    console.log('Cargando solicitudes recibidas...');
    
    const response = await axios.get('/api/solicitudes/recibidas', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    console.log('Respuesta del servidor:', response.data);
    
    if (response.data.success) {
      solicitudesRecibidas.value = response.data.data.solicitudes || [];
      console.log(`Cargadas ${solicitudesRecibidas.value.length} solicitudes:`);
    } else {
      console.error('Error en respuesta:', response.data);
      usarDatosEjemplo();
    }
  } catch (err) {
    console.error('Error cargando solicitudes:', err);
    usarDatosEjemplo();
  } finally {
    loadingSolicitudes.value = false;
  }
}

function usarDatosEjemplo() {
  console.log('Usando datos de ejemplo para desarrollo');
  solicitudesRecibidas.value = [
    {
      id: 1,
      solicitante_id: 101,
      nombre: 'La Abu',
      img: 'https://cdn.pixabay.com/photo/2020/01/15/19/45/witch-4768770_1280.jpg',
      mascota_nombre: 'Firulais',
      fecha_solicitud: '15/12/2024 14:30',
      estado: 'pendiente'
    },
    {
      id: 2,
      solicitante_id: 102,
      nombre: 'Mohamed',
      img: 'https://cdn.pixabay.com/photo/2020/07/16/07/36/man-5410019_960_720.jpg',
      mascota_nombre: 'Luna',
      fecha_solicitud: '14/12/2024 10:15',
      estado: 'pendiente'
    },
    {
      id: 3,
      solicitante_id: 103,
      nombre: 'Mauricio',
      img: 'https://cdn.pixabay.com/photo/2020/05/16/16/41/man-5178199_1280.jpg',
      mascota_nombre: 'Rocky',
      fecha_solicitud: '13/12/2024 16:45',
      estado: 'pendiente'
    }
  ];
}

function selectFilter(filter) {
  selectedFilter.value = filter;
  open.value = false;
  console.log('Filtrando por:', filter);
  
  if (filter === 'Favoritos') {
    const favoritosCount = chats.value.filter(c => c.favorito).length;
    if (favoritosCount === 0) {
      console.log('No tienes chats favoritos');
    }
  }
}

function abrirPerfilUsuario(userId) {
  console.log('Abriendo perfil de usuario desde padre:', userId);
}

// Ciclo de vida
onMounted(() => {
  console.log('✅ Componente Chats montado');
  cargarSolicitudesRecibidas();
  cargarChats();
});
</script>

<style>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
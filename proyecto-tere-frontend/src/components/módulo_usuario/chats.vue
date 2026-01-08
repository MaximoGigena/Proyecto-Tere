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
        <div class="relative flex items-center justify-between border border-black rounded-full 
            bg-white text-black w-full px-4">
  
          <!-- Grupo izquierdo (sobre + texto) -->
          <div class="flex items-center gap-4">
            <!-- Icono de sobre -->
            <div class="w-16 h-16 flex items-center -ml-4 justify-center rounded-full bg-black border-2 border-white">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 4H4C2.897 4 2 4.897 2 6v12c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V6c0-1.103-.897-2-2-2zm0 2v.511l-8 5-8-5V6h16zm-16 12V9.489l7.386 4.616a1 1 0 0 0 1.228 0L20 9.489V18H4z"/>
              </svg>
            </div>

            <!-- Texto de notificación -->
            <span class="text-lg font-semibold text-black">
              {{ mensajeSolicitudes }}
            </span>
          </div>

          <!-- Botón de filtro -->
          <div class="relative">
            <button
              @click="open = !open"
              class="flex items-center gap-2 px-4 py-2 rounded-full 
                    bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 
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
        <div v-for="(chat, index) in chats" :key="index" class="relative">
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
              class="absolute right-4 top-1/2 transform -translate-y-1/2 z-10 p-2 transition duration-200"
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

const router = useRouter();

const open = ref(false);
const selectedFilter = ref(null);
const filters = ref(["Todas", "Pendientes", "Aprobadas", "Rechazadas"]);

// Datos reactivos
const solicitudesRecibidas = ref([]);
const chats = ref([]);
const loadingChats = ref(false);
const loadingSolicitudes = ref(false);


// AÑADE ESTAS NUEVAS PROPIEDADES
const showNotifications = ref(false);
const notificaciones = ref([]);

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
  // Opcional: cargar notificaciones cuando se abre
  if (showNotifications.value && notificaciones.value.length === 0) {
    cargarNotificaciones();
  }
};

// Computed property para el total de notificaciones
const totalNotificaciones = computed(() => {
  return notificaciones.value.length;
});

// Función para cargar notificaciones
async function cargarNotificaciones() {
  try {
    console.log('Cargando notificaciones...');
    
    // Ejemplo de API - ajusta según tu backend
    const response = await axios.get('/api/notificaciones', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    if (response.data.success) {
      notificaciones.value = response.data.data.notificaciones || [];
      console.log(`Cargadas ${notificaciones.value.length} notificaciones`);
    } else {
      console.error('Error cargando notificaciones:', response.data);
      // Datos de ejemplo
      usarNotificacionesEjemplo();
    }
  } catch (err) {
    console.error('Error cargando notificaciones:', err);
    // Datos de ejemplo
    usarNotificacionesEjemplo();
  }
}

function usarNotificacionesEjemplo() {
  console.log('Usando notificaciones de ejemplo');
  notificaciones.value = [
    {
      id: 1,
      titulo: 'Nueva solicitud de adopción',
      mensaje: 'Juan ha enviado una solicitud para adoptar a Firulais',
      tipo: 'info',
      fecha: '2024-12-20T10:30:00'
    },
    {
      id: 2,
      titulo: 'Chat no respondido',
      mensaje: 'Tienes un chat pendiente de respuesta desde hace 2 días',
      tipo: 'advertencia',
      fecha: '2024-12-19T15:45:00'
    },
    {
      id: 3,
      titulo: 'Solicitud aprobada',
      mensaje: 'La solicitud para adoptar a Luna ha sido aprobada',
      tipo: 'info',
      fecha: '2024-12-18T09:15:00'
    }
  ];
}

// Computed properties - IDÉNTICO A LA VERSIÓN VIEJA
// REEMPLAZA la computed property solicitudesRecibidasFormateadas con esto:
const solicitudesRecibidasFormateadas = computed(() => {
  console.log('🔄 Formateando solicitudes para ListadoDeSolicitudes:', solicitudesRecibidas.value);
  
  if (!solicitudesRecibidas.value || solicitudesRecibidas.value.length === 0) {
    return [];
  }
  
  // Formatear EXACTAMENTE como espera ListadoDeSolicitudes.vue
  return solicitudesRecibidas.value.map(solicitud => {
    console.log('Procesando solicitud:', {
      id: solicitud.id,
      solicitud_id: solicitud.solicitud_id,
      solicitante_id: solicitud.solicitante_id,
      nombre: solicitud.nombre
    });
    
    return {
      // ID de la solicitud (no del usuario)
      id: solicitud.solicitud_id || solicitud.id,
      // ID único de la solicitud
      solicitud_id: solicitud.solicitud_id || solicitud.id,
      // ID del usuario solicitante
      solicitante_id: solicitud.solicitante_id || solicitud.id, // Fallback
      // Nombre del solicitante
      nombre: solicitud.nombre,
      // Foto del solicitante
      img: solicitud.img,
      // ID de la mascota
      mascota_id: solicitud.mascota_id,
      // Nombre de la mascota
      mascota_nombre: solicitud.mascota_nombre,
      // Fecha de la solicitud
      fecha_solicitud: solicitud.fecha_solicitud,
      // Estado
      estado: solicitud.estado,
      // Clave única para evitar duplicados
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
  const count = totalSolicitudesPendientes.value;
  if (count === 0) return 'No tienes solicitudes pendientes';
  if (count === 1) return 'Tienes 1 solicitud de adopción';
  return `Tienes ${count} solicitudes de adopción`;
});

// Función para formatear fecha
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

// Funciones para chats
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
        favorito: false,
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
  // Datos de ejemplo como en la versión vieja
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
    chat.favorito = !chat.favorito;
    console.log(`${chat.nombre} favorito:`, chat.favorito);
  } catch (err) {
    console.error('Error actualizando favorito:', err);
    chat.favorito = !chat.favorito;
  }
}

// Función para cargar solicitudes - VERSIÓN SIMPLIFICADA COMO LA VIEJA
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
      
      solicitudesRecibidas.value.forEach(s => {
        console.log(`- ID: ${s.id}, Nombre: ${s.nombre}, Estado: ${s.estado}, Solicitante ID: ${s.solicitante_id}`);
      });
    } else {
      console.error('Error en respuesta:', response.data);
      // Datos de ejemplo
      usarDatosEjemplo();
    }
  } catch (err) {
    console.error('Error cargando solicitudes:', err);
    // Datos de ejemplo
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
  // Lógica de filtrado simple
  if (filter === 'Todas') {
    cargarSolicitudesRecibidas();
  }
}

function abrirPerfilUsuario(userId) {
  console.log('Abriendo perfil de usuario desde padre:', userId);
  // Esta función se mantiene simple, la navegación la maneja el componente hijo
}

// Ciclo de vida
onMounted(() => {
  console.log('✅ Componente Chats montado');
  cargarSolicitudesRecibidas();
  cargarChats();
  // Opcional: cargar notificaciones al montar
  // cargarNotificaciones();
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
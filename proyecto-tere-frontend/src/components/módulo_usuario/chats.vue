<template>
  <div class="w-full h-full flex flex-col relative">
    <!-- Header sticky dentro del scroll -->
    <div class="sticky top-0 z-30 bg-white px-4 py-2">
      <!-- Primera fila: título y botón -->
      <div class="flex items-center justify-between px-2">
        <div class="text-2xl font-bold text-gray-800 pointer-events-none">
          Chats
        </div>
        <button class="text-gray-700 hover:text-black transition">
          <font-awesome-icon :icon="['fas', 'bell']" class="text-2xl"/>
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
    
    <div class="sticky z-30 bg-white px-4 py-2 border-b border-gray-600"></div>
    
    <!-- Contenedor de scroll -->
    <div
      ref="scrollContainer"
      class="relative w-full flex-1 overflow-y-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden px-4"
    > 
      <div class="h-2"></div>
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

          <!-- Botón de filtro (grupo derecho separado) -->
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

      <!-- Lista de chats -->
      <div v-for="(chat, index) in chats" :key="index" class="relative">
        <div class="flex justify-between items-center gap-3 mb-2 min-h-[72px] transition duration-200 hover:bg-blue-100 rounded-xl">
          <router-link
            :to="{
              path: `/explorar/chats/${index + 1}`,
              query: {
                nombre: chat.nombre,
                img: chat.img,
                from: 'chats'
              }
            }"
            class="flex-1 flex items-start gap-3 py-4 pl-4"
          >
            <img :src="chat.img" class="w-16 h-16 rounded-full object-cover" :alt="chat.nombre">
            <div>
              <p class="font-semibold text-lg">{{ chat.nombre }}</p>
              <p class="text-sm text-gray-700">{{ chat.mensaje }}</p>
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
      
      <div class="h-28"></div>
    </div>
  </div>
</template>
  
<script setup>
import { reactive, ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useRoute } from 'vue-router';
import axios from 'axios';
import SolicitudesLista from '@/components/ElementosGraficos/ListadoDeSolicitudes.vue';

const route = useRoute();
const router = useRouter();

const open = ref(false);
const selectedFilter = ref(null);
const filters = ref(["Todas", "Pendientes", "Aprobadas", "Rechazadas"]);

// Determinar si estamos en desarrollo
const isDevelopment = import.meta.env.MODE === 'development' || 
                     import.meta.env.DEV || 
                     window.location.hostname === 'localhost' ||
                     window.location.hostname === '127.0.0.1';

// Datos reactivos
const solicitudesRecibidas = ref([]);
const todasLasSolicitudes = ref([]); // Para debugging
const loading = ref(false);
const error = ref(null);

// Computed properties
const solicitudesRecibidasFormateadas = computed(() => {
  console.log('Formateando solicitudes:', solicitudesRecibidas.value);
  return solicitudesRecibidas.value.map(solicitud => ({
    id: solicitud.solicitante_id,
    nombre: solicitud.nombre,
    img: solicitud.img,
    solicitud_id: solicitud.id,
    mascota_nombre: solicitud.mascota_nombre,
    fecha_solicitud: solicitud.fecha_solicitud,
    estado: solicitud.estado
  }));
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

// Funciones
function selectFilter(filter) {
  selectedFilter.value = filter;
  open.value = false;
  filtrarSolicitudes(filter);
}

async function cargarSolicitudesRecibidas() {
  try {
    loading.value = true;
    error.value = null;
    console.log('Cargando solicitudes recibidas...');
    
    const response = await axios.get('/api/solicitudes/recibidas', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    console.log('Respuesta del servidor:', response.data);
    
    if (response.data.success) {
      solicitudesRecibidas.value = response.data.data.solicitudes;
      console.log(`Cargadas ${solicitudesRecibidas.value.length} solicitudes:`);
      solicitudesRecibidas.value.forEach(s => {
        console.log(`- ID: ${s.id}, Nombre: ${s.nombre}, Estado: ${s.estado}`);
      });
    } else {
      error.value = response.data.message || 'Error al cargar solicitudes';
      console.error('Error en respuesta:', response.data);
    }
  } catch (err) {
    console.error('Error cargando solicitudes:', err);
    error.value = 'No se pudieron cargar las solicitudes. Intenta nuevamente.';
    
    // Datos de ejemplo para desarrollo - SIEMPRE mostrar para debug
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
      },
      {
        id: 4,
        solicitante_id: 104,
        nombre: 'Michael',
        img: 'https://cdn.pixabay.com/photo/2025/08/23/07/48/sadhu-9791446_960_720.jpg',
        mascota_nombre: 'Max',
        fecha_solicitud: '12/12/2024 09:20',
        estado: 'pendiente'
      },
      {
        id: 5,
        solicitante_id: 105,
        nombre: 'Pepa',
        img: 'https://cdn.pixabay.com/photo/2025/05/08/14/54/vietnam-9587582_1280.jpg',
        mascota_nombre: 'Bobby',
        fecha_solicitud: '11/12/2024 15:10',
        estado: 'pendiente'
      },
      {
        id: 6,
        solicitante_id: 106,
        nombre: 'Sofia',
        img: 'https://cdn.pixabay.com/photo/2025/04/28/20/02/woman-9565637_1280.jpg',
        mascota_nombre: 'Coco',
        fecha_solicitud: '10/12/2024 11:45',
        estado: 'pendiente'
      }
    ];
  } finally {
    loading.value = false;
  }
}

// Función para cargar TODAS las solicitudes (para debugging)
async function cargarTodasSolicitudes() {
  try {
    console.log('Cargando TODAS las solicitudes...');
    const response = await axios.get('/api/solicitudes/todas-recibidas', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    console.log('Respuesta de TODAS las solicitudes:', response.data);
    
    if (response.data.success) {
      todasLasSolicitudes.value = response.data.data.solicitudes;
      console.log(`Total de solicitudes en BD: ${response.data.data.total}`);
      console.log('Estadísticas:', response.data.data.estadisticas);
      
      // Mostrar todas las solicitudes en consola
      todasLasSolicitudes.value.forEach(s => {
        console.log(`- ID: ${s.id}, Nombre: ${s.nombre}, Estado: ${s.estado}, Usuario Type: ${s.usuario_type}`);
      });
    }
  } catch (err) {
    console.error('Error cargando todas las solicitudes:', err);
  }
}

async function filtrarSolicitudes(filtro) {
  if (filtro === 'Todas') {
    await cargarSolicitudesRecibidas();
  } else {
    console.log('Filtrando por:', filtro);
    // Implementar lógica de filtrado local
    const estadoMap = {
      'Pendientes': 'pendiente',
      'Aprobadas': 'aprobada',
      'Rechazadas': 'rechazada'
    };
    
    if (estadoMap[filtro]) {
      // Filtrar localmente
      const filtradas = solicitudesRecibidas.value.filter(
        s => s.estado === estadoMap[filtro]
      );
      // Podrías actualizar una copia temporal aquí
      console.log(`Filtradas: ${filtradas.length} solicitudes`);
    }
  }
}

async function cargarConteoSolicitudes() {
  try {
    const response = await axios.get('/api/solicitudes/pendientes/conteo', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    if (response.data.success) {
      console.log('Conteo de solicitudes pendientes:', response.data.data.conteo);
    }
  } catch (err) {
    console.error('Error cargando conteo:', err);
  }
}

// Datos de ejemplo para chats (mantener como están)
const chats = reactive([
  {
    id: 1,
    nombre: 'Josefina, dolores del mes',
    mensaje: 'Te molesta si me como a tu perro?',
    img: 'https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg',
    favorito: false
  },
]);

// Ciclo de vida
onMounted(() => {
  cargarSolicitudesRecibidas();
  cargarConteoSolicitudes();
});

// Funciones existentes (mantener igual)
const abrirPerfilUsuario = (userId) => {
  console.log('Abriendo perfil de usuario:', userId);
  // La navegación ya se maneja en el componente hijo
};

const toggleFavorite = (chat) => {
  chat.favorito = !chat.favorito;
  console.log(`${chat.nombre} favorito:`, chat.favorito);
};
</script>

<style>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
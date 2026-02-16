<template>
  <div class="w-full h-full flex flex-col relative">
    <!-- Header sticky con información del chat -->
    <div class="sticky top-0 z-30 bg-white px-4 py-2 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <button @click="volverAchats" class="text-gray-700 hover:text-black transition p-2">
          <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
        </button>
        
        <div class="flex flex-col items-center flex-1 mx-4">
          <div class="flex items-center gap-3">
            <div class="relative">
              <img 
                :src="currentChat.img" 
                @click="abrirPerfilUsuario" 
                class="w-12 h-12 rounded-full object-cover cursor-pointer" 
                :alt="currentChat.nombre" 
              />
              <div v-if="currentChat.online" 
                   class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
            </div>
            <div class="flex flex-col">
              <span class="font-semibold text-lg">{{ currentChat.nombre }}</span>
              <span class="text-xs text-gray-500">{{ currentChat.online ? 'En línea' : 'Desconectado' }}</span>
            </div>
          </div>
          
          <!-- 🔥 NUEVO: Indicador de progreso de interacciones -->
          <div v-if="mostrarIndicadorInteracciones" class="w-full max-w-md mt-2">
            <div class="flex items-center justify-between text-xs">
              <span class="text-gray-600 font-medium">
                Progreso para aprobación: 
                <span :class="{
                  'text-green-600 font-bold': interacciones.progreso >= 100,
                  'text-blue-600': interacciones.progreso < 100
                }">
                  {{ interacciones.actual }}/{{ interacciones.requerido }} interacciones
                </span>
              </span>
              
            </div>
            <!-- Mensaje informativo -->
            <p v-if="interacciones.faltan > 0" class="text-xs text-gray-500 mt-1">
              ⚠️ Faltan {{ interacciones.faltan }} mensajes intercambiados para habilitar la aprobación
            </p>
            <p v-else-if="interacciones.actual >= interacciones.requerido" class="text-xs text-green-600 font-medium mt-1">
              ✅ ¡Chat listo para proceder con la adopción!
            </p>
            
            
          </div>
        </div>
      </div>
    </div>

    <!-- Estado de carga inicial -->
    <div v-if="isInitialLoading" 
         class="flex-1 flex flex-col items-center justify-center p-4">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
      <p class="text-gray-600">
        <span v-if="loadingStates.chat">Cargando información del chat...</span>
        <span v-else-if="loadingStates.mensajes">Cargando mensajes...</span>
      </p>
      <p class="text-gray-400 text-sm mt-2">Esto puede tomar unos segundos</p>
    </div>

    <!-- Mensaje de error -->
    <div v-else-if="loadingStates.error" 
         class="flex-1 flex flex-col items-center justify-center p-4 bg-red-50">
      <svg class="w-16 h-16 text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-red-600 font-medium mb-2">Error al cargar el chat</p>
      <p class="text-red-500 text-center max-w-md">{{ loadingStates.error }}</p>
      <div class="flex gap-2 mt-4">
        <button @click="reintentarCarga" 
                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">
          Reintentar
        </button>
        <button @click="volverAchats" 
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
          Volver atrás
        </button>
      </div>
    </div>

    <!-- Área de mensajes -->
    <div v-else
         ref="messagesContainer"
         class="flex-1 overflow-y-auto p-4 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden bg-gray-50"
    >
      <!-- Loading más mensajes (scroll infinito) -->
      <div v-if="loadingMessages && page > 1" class="flex justify-center py-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-2 text-gray-500">Cargando más mensajes...</span>
      </div>

      <!-- 🔥 NUEVO: Mensaje informativo sobre interacciones -->
      <div v-if="interacciones.actual === 1 && !interaccionesCargadas" class="mb-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mx-auto max-w-lg">
          <div class="flex items-center">
            <font-awesome-icon :icon="['fas', 'info-circle']" class="text-blue-500 mr-2"/>
            <p class="text-sm text-blue-700">
              <strong>Importante:</strong> Necesitas intercambiar al menos <strong>5 mensajes</strong> 
              con el {{ esDueñoMascota ? 'solicitante' : 'dueño de la mascota' }} 
              para habilitar la aprobación de adopción.
            </p>
          </div>
        </div>
      </div>

      <!-- Mensajes -->
      <div v-for="(message, index) in messages" :key="message.id" 
          :class="['flex mb-4', message.sender === 'me' ? 'justify-start' : 'justify-end']">
        
        <div :class="[
          'max-w-xs lg:max-w-md px-4 py-2 rounded-lg shadow-sm',
          message.sender === 'me' 
            ? 'bg-blue-500 text-white rounded-tl-none'  
            : 'bg-white text-gray-800 border border-gray-200 rounded-tr-none', 
          message.es_temporal ? 'opacity-70' : '',
          message.error ? 'border-red-300 bg-red-50' : ''
        ]">
          <p class="whitespace-pre-wrap break-words">{{ message.text }}</p>
          <div class="flex items-center justify-end mt-1">
            <p class="text-xs" :class="message.sender === 'me' ? 'text-blue-100' : 'text-gray-500'">
              {{ formatTime(message.time) }}
              <span v-if="message.es_temporal" class="italic"> (enviando...)</span>
              <span v-if="message.error" class="text-red-400"> (error)</span>
            </p>
            <span v-if="message.sender === 'me' && message.leido && !message.es_temporal" 
                  class="ml-2 text-blue-300">
              ✓
            </span>
          </div>
          
          <!-- 🔥 NUEVO: Indicador de mensaje que cuenta para interacciones -->
          <div v-if="message.tipo === 'texto' && message.sender !== 'me'" 
               class="text-xs text-gray-400 mt-1">
            <font-awesome-icon :icon="['fas', 'comment']" class="mr-1"/>
            Cuenta para interacción
          </div>
        </div>
      </div>

      <!-- Sin mensajes -->
      <div v-if="messages.length === 0 && !loadingMessages" class="flex flex-col items-center justify-center h-full">
        <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <p class="text-gray-500">No hay mensajes todavía</p>
        <p class="text-gray-400 text-sm">Envía un mensaje para iniciar la conversación</p>
        
        <!-- 🔥 NUEVO: Información sobre interacciones -->
        <div v-if="mostrarIndicadorInteracciones" class="mt-6 bg-blue-50 p-4 rounded-lg max-w-md">
          <h3 class="font-semibold text-blue-800 mb-2">📝 Importante para la adopción</h3>
          <ul class="text-sm text-blue-700 space-y-1">
            <li class="flex items-start">
              <font-awesome-icon :icon="['fas', 'message']" class="mt-0.5 mr-2 text-blue-600"/>
              <span>Debes intercambiar <strong>al menos 5 mensajes</strong> con el {{ esDueñoMascota ? 'solicitante' : 'dueño' }}</span>
            </li>
            <li class="flex items-start">
              <font-awesome-icon :icon="['fas', 'handshake']" class="mt-0.5 mr-2 text-blue-600"/>
              <span>Ambos deben enviar mensajes (no solo uno)</span>
            </li>
            <li class="flex items-start">
              <font-awesome-icon :icon="['fas', 'check-circle']" class="mt-0.5 mr-2 text-blue-600"/>
              <span>Al alcanzar 5 interacciones, se habilitará la opción de aprobar la adopción</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- 🔥 INPUT CON INFORMACIÓN DE INTERACCIONES -->
    <div id="chat-input-container" class="sticky bottom-0 bg-white border-t border-gray-200 p-3 z-40">
      <!-- Contador de interacciones mini -->
      <div v-if="mostrarIndicadorInteracciones" class="flex items-center justify-between mb-2 px-1">
        <div class="flex items-center text-xs">
        </div>
      </div>
      
      <div class="flex items-center gap-2">
        <button @click="abrirAdjuntos" 
                :disabled="isInputDisabled()"
                class="text-gray-500 hover:text-gray-700 disabled:opacity-50 p-2 transition-colors">
          <font-awesome-icon :icon="['fas', 'paperclip']" class="text-xl"/>
        </button>
        <input 
          v-model="newMessage"
          @keydown.enter.prevent="handleEnterKey"
          :disabled="isInputDisabled()"
          type="text" 
          :placeholder="getPlaceholder()"
          class="flex-1 border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
          ref="messageInput"
        >
        <button 
          @click="sendMessage"
          :disabled="isSendDisabled()"
          class="text-gray-500 hover:text-blue-500 disabled:opacity-50 p-2 transition-colors duration-200"
        >
          <font-awesome-icon v-if="!sendingMessage" :icon="['fas', 'paper-plane']" class="text-xl"/>
          <font-awesome-icon v-else :icon="['fas', 'spinner']" class="text-xl animate-spin"/>
        </button>
      </div>
      <!-- Mensaje de estado -->
      <div v-if="estadoEnvio" class="text-xs text-center mt-1 px-2">
        <span :class="{
          'text-green-600': estadoEnvio.tipo === 'success',
          'text-red-600': estadoEnvio.tipo === 'error',
          'text-blue-600': estadoEnvio.tipo === 'info'
        }">
          {{ estadoEnvio.mensaje }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick, onBeforeMount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const instanceId = Math.random().toString(36).substring(7);
console.log(`Componente SalaDeChat montado - Instancia: ${instanceId}`);

// Limpiar cualquier intervalo previo al montar
onBeforeMount(() => {
  if (window.chatPollingInterval) {
    clearInterval(window.chatPollingInterval);
    window.chatPollingInterval = null;
  }
  detenerPolling();
});

const loadingStates = ref({
  chat: false,
  mensajes: false,
  error: null
});

const route = useRoute();
const router = useRouter();

// Variables reactivas
const newMessage = ref('');
const messagesContainer = ref(null);
const messageInput = ref(null);
const messages = ref([]);
const loadingMessages = ref(false);
const sendingMessage = ref(false);
const chatInfo = ref(null);
const page = ref(1);
const hasMore = ref(true);
const estadoEnvio = ref(null);
const isInitialLoading = ref(true);
const cargandoNuevosMensajes = ref(false);
const isEnterPressed = ref(false);
const pollingActive = ref(false);
const interaccionesCargadas = ref(false);
const mostrarModalEstadisticas = ref(false);

// 🔥 NUEVO: Variables para interacciones
const interacciones = ref({
  actual: 0,
  requerido: 5,
  progreso: 0,
  faltan: 5,
  detalle: {
    usuario_actual: 0,
    otro_usuario: 0
  }
});

// 🔥 NUEVO: Estado de estadísticas
const estadisticasChat = ref({
  total_mensajes: 0,
  listo_para_adopcion: false,
  puede_aprobar: false,
  fecha_habilitado_adopcion: null
});

let pollingInterval = null;

// Computed properties actualizadas
const mostrarIndicadorInteracciones = computed(() => {
  // Mostrar solo si hay una solicitud de adopción asociada
  return currentChat.value.solicitud_id && interaccionesCargadas.value;
});

const esDueñoMascota = computed(() => {
  // Asumimos que el usuario actual es el dueño si creó el chat
  // En tu implementación, podrías necesitar lógica específica
  return chatInfo.value?.solicitud_id && currentChat.value.solicitud_id;
});

// Función para detener el polling
function detenerPolling() {
  pollingActive.value = false;
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
}

// 🔥 NUEVO: Función para cargar estadísticas de interacciones
async function cargarEstadisticasInteracciones() {
  try {
    const chatId = route.params.id;
    const token = localStorage.getItem('token');
    
    if (!token || !chatId) return;
    
    const response = await axios.get(`/api/chats/${chatId}/interacciones`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    if (response.data.success) {
      const data = response.data.data;
      const stats = data.estadisticas || data;
      
      // Actualizar interacciones
      const interaccionesUsuario = stats.interacciones_usuario || stats.mensajes_usuario_actual || 0;
      const interaccionesOtro = stats.interacciones_otro_usuario || stats.mensajes_otro_usuario || 0;
      
      // Calcular interacciones mínimas (ping-pong)
      const minInteracciones = Math.min(interaccionesUsuario, interaccionesOtro);
      
      interacciones.value = {
        actual: minInteracciones,
        requerido: 5,
        progreso: Math.min(100, (minInteracciones / 5) * 100),
        faltan: Math.max(0, 5 - minInteracciones),
        detalle: {
          usuario_actual: interaccionesUsuario,
          otro_usuario: interaccionesOtro
        }
      };
      
      // Guardar estadísticas generales
      estadisticasChat.value = {
        total_mensajes: stats.total_interacciones || 0,
        listo_para_adopcion: stats.alcanzo_interaccion_minima || false,
        puede_aprobar: stats.puede_aprobar || (minInteracciones >= 5),
        fecha_habilitado_adopcion: stats.fecha_habilitacion || null
      };
      
      interaccionesCargadas.value = true;
      
      console.log('Estadísticas cargadas:', {
        interacciones: interacciones.value,
        estadisticas: estadisticasChat.value
      });
    }
  } catch (err) {
    console.error('Error cargando estadísticas de interacciones:', err);
    // Si falla, intentamos con el endpoint alternativo
    await cargarEstadisticasAlternativo();
  }
}

// 🔥 NUEVO: Función alternativa para cargar estadísticas
async function cargarEstadisticasAlternativo() {
  try {
    const chatId = route.params.id;
    const token = localStorage.getItem('token');
    
    if (!token) return;
    
    const response = await axios.get(`/api/chats/${chatId}/estadisticas-interaccion`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    if (response.data.success) {
      const data = response.data.data;
      if (data.estadisticas) {
        const stats = data.estadisticas;
        
        const minInteracciones = Math.min(stats.mensajes_usuario_cedente || 0, stats.mensajes_usuario_solicitante || 0);
        
        // Determinar qué conteo corresponde al usuario actual
        const userActual = stats.detalle?.usuario_actual;
        const interaccionesUsuario = userActual?.mensajes || stats.interacciones_usuario || 0;
        const interaccionesOtro = stats.detalle?.otro_usuario?.mensajes || stats.interacciones_otro_usuario || 0;
        
        interacciones.value = {
          actual: minInteracciones,
          requerido: 5,
          progreso: Math.min(100, (minInteracciones / 5) * 100),
          faltan: Math.max(0, 5 - minInteracciones),
          detalle: {
            usuario_actual: interaccionesUsuario,
            otro_usuario: interaccionesOtro
          }
        };
        
        interaccionesCargadas.value = true;
      }
    }
  } catch (err) {
    console.error('Error cargando estadísticas alternativas:', err);
  }
}

// Cargar nuevos mensajes (CON REF LOCAL)
async function cargarNuevosMensajes() {
  const yaEstaCargando = cargandoNuevosMensajes.value;
  
  if (yaEstaCargando) {
    return;
  }
  
  try {
    cargandoNuevosMensajes.value = true;
    
    const chatId = route.params.id;
    const token = localStorage.getItem('token');
    
    if (!token) {
      detenerPolling();
      return;
    }
    
    // Si no hay mensajes, no hay necesidad de buscar nuevos
    if (messages.value.length === 0) {
      cargandoNuevosMensajes.value = false;
      return;
    }
    
    const ultimoMensajeId = messages.value[messages.value.length - 1].id;
    
    const response = await axios.get(`/api/chats/${chatId}/mensajes`, {
      params: { 
        after: ultimoMensajeId, 
        per_page: 50 
      },
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (response.data.success && response.data.data.mensajes.length > 0) {
      const nuevosMensajes = response.data.data.mensajes;
      
      // Filtrar mensajes duplicados
      const nuevosIds = nuevosMensajes.map(m => m.id);
      const mensajesExistentes = messages.value.filter(m => !nuevosIds.includes(m.id));
      
      messages.value = [...mensajesExistentes, ...nuevosMensajes];
      
      // 🔥 NUEVO: Actualizar estadísticas después de recibir nuevos mensajes
      if (nuevosMensajes.some(m => m.tipo === 'texto')) {
        await cargarEstadisticasInteracciones();
      }
      
      const container = messagesContainer.value;
      if (container) {
        const nearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100;
        if (nearBottom) {
          setTimeout(() => {
            if (container) {
              container.scrollTop = container.scrollHeight;
            }
          }, 50);
        }
      }
    }
  } catch (err) {
    // Solo log en desarrollo
    if (process.env.NODE_ENV !== 'production') {
      console.error('Error cargando nuevos mensajes:', err);
    }
    
    // Detener polling en caso de errores de autenticación
    if (err.response?.status === 401) {
      detenerPolling();
      localStorage.removeItem('token');
      router.push('/login');
    }
  } finally {
    cargandoNuevosMensajes.value = false;
  }
}

// Función para iniciar el polling
function iniciarPolling() {
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
  
  pollingActive.value = true;
  
  const pollFunction = async () => {
    if (pollingActive.value && 
        !isInitialLoading.value && 
        !loadingStates.value.error && 
        !loadingMessages.value &&
        !cargandoNuevosMensajes.value) {
      await cargarNuevosMensajes();
    }
  };
  
  pollingInterval = setInterval(pollFunction, 5000);
}

// 🔥 NUEVO: Función para mostrar estadísticas detalladas
function mostrarEstadisticasDetalladas() {
  mostrarModalEstadisticas.value = true;
}

// 🔥 NUEVO: Función para ir a aprobar solicitud
function irAAprobarSolicitud() {
  if (currentChat.value.solicitud_id) {
    router.push({
      name: 'adoption-request',
      params: { solicitudId: currentChat.value.solicitud_id }
    });
  }
}

// Función auxiliares de UI
function handleEnterKey(event) {
  if (!isEnterPressed.value && !isSendDisabled()) {
    isEnterPressed.value = true;
    sendMessage();
    
    setTimeout(() => {
      isEnterPressed.value = false;
    }, 500);
  }
  
  event.preventDefault();
}

function isInputDisabled() {
  return sendingMessage.value || isInitialLoading.value || loadingStates.value.error;
}

function isSendDisabled() {
  return sendingMessage.value || 
         isInitialLoading.value || 
         loadingStates.value.error || 
         !newMessage.value.trim();
}

function getPlaceholder() {
  if (loadingStates.value.error) return 'Error al cargar chat';
  if (isInitialLoading.value) return 'Cargando chat...';
  if (sendingMessage.value) return 'Enviando mensaje...';
  return 'Escribe un mensaje...';
}

// Computed
const currentChat = computed(() => {
  if (route.query.nombre) {
    return {
      id: route.params.id,
      nombre: route.query.nombre || 'Usuario',
      img: route.query.img || 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png',
      online: false,
      solicitud_id: route.query.solicitud_id
    };
  }
  
  if (chatInfo.value) {
    return {
      id: chatInfo.value.chat_id,
      nombre: chatInfo.value.nombre || 'Usuario',
      img: chatInfo.value.img || 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png',
      online: chatInfo.value.online || false,
      solicitud_id: chatInfo.value.solicitud_id
    };
  }
  
  return {
    id: route.params.id,
    nombre: 'Cargando...',
    img: 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png',
    online: false,
    solicitud_id: null
  };
});

// Funciones principales actualizadas
async function cargarChat() {
  try {
    loadingStates.value.chat = true;
    const chatId = route.params.id;
    
    const token = localStorage.getItem('token');
    if (!token) {
      loadingStates.value.error = 'No autenticado';
      return;
    }
    
    const response = await axios.get(`/api/chats/${chatId}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (response.data.success) {
      chatInfo.value = response.data.data.chat;
      
      // 🔥 NUEVO: Cargar estadísticas de interacciones
      if (chatInfo.value.solicitud_id) {
        await cargarEstadisticasInteracciones();
      }
    } else {
      loadingStates.value.error = response.data.message || 'Error cargando chat';
    }
  } catch (err) {
    loadingStates.value.error = `Error: ${err.message}`;
    
    if (err.response?.status === 401) {
      localStorage.removeItem('token');
      router.push('/login');
    }
  } finally {
    loadingStates.value.chat = false;
  }
}

async function cargarMensajes(reset = false) {
  try {
    if (reset) {
      page.value = 1;
      hasMore.value = true;
      messages.value = [];
    }
    
    if (!hasMore.value && !reset) return;
    
    loadingMessages.value = true;
    loadingStates.value.mensajes = true;
    
    const chatId = route.params.id;
    const token = localStorage.getItem('token');
    
    const response = await axios.get(`/api/chats/${chatId}/mensajes`, {
      params: { page: page.value, per_page: 50 },
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (response.data.success) {
      const nuevosMensajes = response.data.data.mensajes;
      
      if (reset) {
        messages.value = nuevosMensajes.reverse();
      } else {
        messages.value = [...nuevosMensajes.reverse(), ...messages.value];
      }
      
      hasMore.value = page.value < response.data.data.last_page;
      page.value++;
      
      if (reset) {
        setTimeout(scrollToBottom, 100);
      }
    } else {
      loadingStates.value.error = response.data.message || 'Error cargando mensajes';
    }
  } catch (err) {
    loadingStates.value.error = `Error: ${err.message}`;
  } finally {
    loadingMessages.value = false;
    loadingStates.value.mensajes = false;
  }
}

async function sendMessage() {
  if (isSendDisabled() || sendingMessage.value) {
    return;
  }
  
  try {
    sendingMessage.value = true;
    estadoEnvio.value = { tipo: 'info', mensaje: 'Enviando...' };
    
    const chatId = route.params.id;
    const mensajeTexto = newMessage.value.trim();
    
    // Mensaje temporal
    const tempMessage = {
      id: Date.now(),
      chat_id: chatId,
      text: mensajeTexto,
      sender: 'me',
      userId: localStorage.getItem('user_id'),
      time: new Date(),
      leido: false,
      tipo: 'texto',
      es_sistema: false,
      es_temporal: true
    };
    
    messages.value.push(tempMessage);
    const mensajeAnterior = newMessage.value;
    newMessage.value = '';
    
    nextTick(() => {
      if (messageInput.value) {
        messageInput.value.focus();
      }
      scrollToBottom();
    });
    
    const response = await axios.post(`/api/chats/${chatId}/mensajes`, {
      contenido: mensajeTexto,
      tipo: 'texto'
    }, {
      headers: { 
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      }
    });

    if (response.data.success) {
      const mensajeReal = response.data.data.mensaje;
      const index = messages.value.findIndex(m => m.es_temporal && m.id === tempMessage.id);
      
      if (index !== -1) {
        messages.value.splice(index, 1, mensajeReal);
      }
      
      // 🔥 NUEVO: Actualizar estadísticas de interacciones después de enviar
      if (response.data.data.estadisticas_interaccion) {
        const stats = response.data.data.estadisticas_interaccion;
        const minInteracciones = Math.min(stats.interacciones_usuario || 0, stats.interacciones_otro_usuario || 0);
        
        interacciones.value = {
          actual: minInteracciones,
          requerido: 5,
          progreso: Math.min(100, (minInteracciones / 5) * 100),
          faltan: Math.max(0, 5 - minInteracciones),
          detalle: {
            usuario_actual: stats.interacciones_usuario || 0,
            otro_usuario: stats.interacciones_otro_usuario || 0
          }
        };
        
        estadisticasChat.value = {
          total_mensajes: stats.total_interacciones || 0,
          listo_para_adopcion: stats.alcanzo_interaccion_minima || false,
          puede_aprobar: stats.alcanzo_interaccion_minima || false,
          fecha_habilitado_adopcion: stats.fecha_habilitacion || null
        };
      } else {
        // Si no vienen en la respuesta, recargar
        await cargarEstadisticasInteracciones();
      }
      
      estadoEnvio.value = { tipo: 'success', mensaje: 'Mensaje enviado ✓' };
      
      setTimeout(() => { 
        estadoEnvio.value = null;
      }, 2000);
    } else {
      const index = messages.value.findIndex(m => m.es_temporal && m.id === tempMessage.id);
      if (index !== -1) {
        messages.value[index].error = true;
        messages.value[index].text = `${mensajeAnterior} (Error al enviar)`;
      }
      
      estadoEnvio.value = { 
        tipo: 'error', 
        mensaje: `Error: ${response.data.message || 'Error desconocido'}` 
      };
      newMessage.value = mensajeAnterior;
    }
  } catch (err) {
    let errorMsg = 'No se pudo enviar el mensaje. ';
    if (err.response?.status === 401) {
      errorMsg = 'No estás autenticado.';
      localStorage.removeItem('token');
      router.push('/login');
    } else if (err.response?.status === 403) {
      errorMsg = 'No tienes permiso.';
    } else if (err.response?.status === 404) {
      errorMsg = 'Chat no encontrado.';
    } else if (err.response?.data?.message) {
      errorMsg += err.response.data.message;
    } else if (err.message.includes('Network Error')) {
      errorMsg = 'Error de conexión. Verifica tu internet.';
    }
    
    estadoEnvio.value = { tipo: 'error', mensaje: errorMsg };
  } finally {
    sendingMessage.value = false;
  }
}

// Funciones auxiliares
const formatTime = (date) => {
  if (!date) return '';
  if (typeof date === 'string') date = new Date(date);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const scrollToBottom = () => {
  if (messagesContainer.value) {
    setTimeout(() => {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }, 50);
  }
};

const volverAchats = () => {
  router.push('/explorar/chats');
};

const abrirPerfilUsuario = () => {
  if (currentChat.value.solicitud_id) {
    router.push({
      name: 'adoption-request',
      params: { solicitudId: currentChat.value.solicitud_id }
    });
  } else if (chatInfo.value?.usuario_id) {
    router.push({
      name: 'user-profile-room',
      params: { userId: chatInfo.value.usuario_id },
      query: { from: 'chat-room' }
    });
  }
};

function abrirAdjuntos() {
  if (isInputDisabled()) return;
  estadoEnvio.value = { tipo: 'info', mensaje: 'Funcionalidad en desarrollo' };
  setTimeout(() => { estadoEnvio.value = null; }, 2000);
}

async function reintentarCarga() {
  loadingStates.value.error = null;
  await inicializarChat();
}

const handleScroll = () => {
  if (!messagesContainer.value || loadingMessages.value || !hasMore.value) return;
  if (messagesContainer.value.scrollTop === 0) {
    cargarMensajes();
  }
};

// Inicialización actualizada
async function inicializarChat() {
  // Detener polling anterior
  detenerPolling();
  
  // Resetear estado
  isInitialLoading.value = true;
  loadingStates.value = { chat: false, mensajes: false, error: null };
  chatInfo.value = null;
  messages.value = [];
  page.value = 1;
  hasMore.value = true;
  interaccionesCargadas.value = false;
  interacciones.value = {
    actual: 0,
    requerido: 5,
    progreso: 0,
    faltan: 5,
    detalle: { usuario_actual: 0, otro_usuario: 0 }
  };
  
  const token = localStorage.getItem('token');
  if (!token) {
    loadingStates.value.error = 'No autenticado';
    setTimeout(() => router.push('/login'), 1500);
    return;
  }
  
  try {
    await cargarChat();
    
    if (!loadingStates.value.error) {
      await cargarMensajes(true);
    }
    
    isInitialLoading.value = false;
    
    // Iniciar polling SOLO después de carga exitosa
    if (!loadingStates.value.error) {
      iniciarPolling();
    }
    
    setTimeout(() => {
      if (messageInput.value) {
        messageInput.value.focus();
      }
    }, 300);
    
  } catch (error) {
    loadingStates.value.error = `Error: ${error.message}`;
    isInitialLoading.value = false;
  }
}

// Ciclo de vida
onMounted(() => {
  inicializarChat();
  
  if (messagesContainer.value) {
    messagesContainer.value.addEventListener('scroll', handleScroll);
  }
});

onUnmounted(() => {
  detenerPolling();
  
  if (messagesContainer.value) {
    messagesContainer.value.removeEventListener('scroll', handleScroll);
  }
});

// Watch para cambios de chat
watch(() => route.params.id, async (newId, oldId) => {
  if (newId && newId !== oldId) {
    await inicializarChat();
  }
});
</script>

<style scoped>
/* ESTILOS QUE GARANTIZAN VISIBILIDAD */
#chat-input-container {
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  z-index: 9999 !important;
  position: sticky !important;
  bottom: 0 !important;
  background: white !important;
  border-top: 1px solid #e5e7eb !important;
  padding: 12px !important;
}

#chat-input-container input {
  opacity: 1 !important;
  display: block !important;
  visibility: visible !important;
}

/* Si el problema es de altura, forzar altura mínima */
.flex-1 {
  min-height: 0 !important;
  overflow: auto !important;
}

/* Asegurar que el contenedor de mensajes no tape el input */
.bg-gray-50 {
  flex: 1 1 0% !important;
  min-height: 0 !important;
  overflow-y: auto !important;
}

/* 🔥 NUEVO: Estilos para la barra de progreso */
.bg-blue-500 {
  background-color: #3b82f6;
}

.bg-green-500 {
  background-color: #10b981;
}

.bg-gray-200 {
  background-color: #e5e7eb;
}

.bg-gray-400 {
  background-color: #9ca3af;
}

/* Animación para la barra de progreso */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 500ms;
}
</style>
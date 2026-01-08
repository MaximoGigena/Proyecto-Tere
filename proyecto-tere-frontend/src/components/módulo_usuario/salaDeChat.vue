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
        </div>
        
        <button class="text-gray-700 hover:text-black transition p-2">
          <font-awesome-icon :icon="['fas', 'ellipsis-vertical']" class="text-xl"/>
        </button>
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

      <!-- Mensajes -->
      <div v-for="(message, index) in messages" :key="message.id" 
          :class="['flex mb-4', message.sender === 'me' ? 'justify-start' : 'justify-end']">
        <!-- 🔥 CAMBIO: 'justify-start' para 'me' y 'justify-end' para otros -->
        
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
        </div>
      </div>

      <!-- Sin mensajes -->
      <div v-if="messages.length === 0 && !loadingMessages" class="flex flex-col items-center justify-center h-full">
        <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <p class="text-gray-500">No hay mensajes todavía</p>
        <p class="text-gray-400 text-sm">Envía un mensaje para iniciar la conversación</p>
      </div>
    </div>

    <!-- 🔥 INPUT CORREGIDO - SIN MULTIPLES ENVIOS 🔥 -->
    <div id="chat-input-container" class="sticky bottom-0 bg-white border-t border-gray-200 p-3 z-40">
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

let pollingInterval = null;

// Estado para ver qué está cargando
const loadingStates = ref({
  chat: false,
  mensajes: false,
  error: null
});

// Función para detener el polling
function detenerPolling() {
  pollingActive.value = false;
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
}

// Cargar nuevos mensajes (CON REF LOCAL)
async function cargarNuevosMensajes() {
  // Usar la variable local directamente para evitar problemas de referencia
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
  
  // Usar una función nombrada para evitar problemas de contexto
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

// Funciones principales
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

// Inicialización
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
</style>
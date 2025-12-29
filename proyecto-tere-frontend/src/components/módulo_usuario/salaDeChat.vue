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

    <!-- Área de mensajes -->
    <div 
      ref="messagesContainer"
      class="flex-1 overflow-y-auto p-4 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden bg-gray-50"
    >
      <!-- Loading más mensajes -->
      <div v-if="loadingMessages && page > 1" class="flex justify-center py-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>

      <!-- Mensajes -->
      <div v-for="(message, index) in messages" :key="message.id" 
           :class="['flex mb-4', message.sender === 'me' ? 'justify-end' : 'justify-start']">
        <div :class="['max-w-xs lg:max-w-md px-4 py-2 rounded-lg shadow-sm', 
                     message.sender === 'me' 
                     ? 'bg-blue-500 text-white rounded-tr-none' 
                     : 'bg-white text-gray-800 border border-gray-200 rounded-tl-none']">
          <p class="whitespace-pre-wrap break-words">{{ message.text }}</p>
          <div class="flex items-center justify-end mt-1">
            <p class="text-xs" :class="message.sender === 'me' ? 'text-blue-100' : 'text-gray-500'">
              {{ formatTime(message.time) }}
            </p>
            <span v-if="message.sender === 'me' && message.leido" 
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

    <!-- Input para enviar mensajes -->
    <div class="sticky bottom-0 bg-white border-t border-gray-200 p-3">
      <div class="flex items-center gap-2">
        <button class="text-gray-500 hover:text-gray-700 p-2">
          <font-awesome-icon :icon="['fas', 'paperclip']" class="text-xl"/>
        </button>
        <input 
          v-model="newMessage"
          @keyup.enter="sendMessage"
          :disabled="sendingMessage"
          type="text" 
          placeholder="Escribe un mensaje..."
          class="flex-1 border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
        >
        <button 
          @click="sendMessage"
          :disabled="!newMessage.trim() || sendingMessage"
          class="text-gray-500 hover:text-blue-500 disabled:opacity-50 p-2"
        >
          <font-awesome-icon v-if="!sendingMessage" :icon="['fas', 'paper-plane']" class="text-xl"/>
          <font-awesome-icon v-else :icon="['fas', 'spinner']" class="text-xl animate-spin"/>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const newMessage = ref('');
const messagesContainer = ref(null);
const messages = ref([]);
const loadingMessages = ref(false);
const sendingMessage = ref(false);
const chatInfo = ref(null);
const page = ref(1);
const hasMore = ref(true);

// Obtener información del chat
const currentChat = computed(() => {
  if (chatInfo.value) {
    return {
      id: chatInfo.value.chat_id,
      nombre: route.query.nombre || chatInfo.value.nombre || 'Usuario',
      img: route.query.img || chatInfo.value.img || 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png',
      online: chatInfo.value.online || false,
      solicitud_id: chatInfo.value.solicitud_id
    };
  }
  
  // Fallback a datos de la ruta
  return {
    id: route.params.id,
    nombre: route.query.nombre || 'Usuario',
    img: route.query.img || 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png',
    online: false,
    solicitud_id: route.query.solicitud_id
  };
});

// Cargar información del chat
async function cargarChat() {
  try {
    const chatId = route.params.id;
    
    const response = await axios.get(`/api/chats/${chatId}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    if (response.data.success) {
      chatInfo.value = response.data.data.chat;
      console.log('Chat cargado:', chatInfo.value);
      
      // Si no hay datos en la ruta, actualizar la ruta con la info real
      if (!route.query.nombre && chatInfo.value.nombre) {
        router.replace({
          query: {
            ...route.query,
            nombre: chatInfo.value.nombre,
            img: chatInfo.value.img
          }
        });
      }
    }
  } catch (err) {
    console.error('Error cargando chat:', err);
  }
}

// Cargar mensajes
async function cargarMensajes(reset = false) {
  try {
    if (reset) {
      page.value = 1;
      hasMore.value = true;
      messages.value = [];
    }
    
    if (!hasMore.value && !reset) return;
    
    loadingMessages.value = true;
    const chatId = route.params.id;
    
    const response = await axios.get(`/api/chats/${chatId}/mensajes`, {
      params: {
        page: page.value,
        per_page: 50
      },
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
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
      
      // Scroll al final si es la primera carga
      if (reset) {
        setTimeout(scrollToBottom, 100);
      }
    }
  } catch (err) {
    console.error('Error cargando mensajes:', err);
  } finally {
    loadingMessages.value = false;
  }
}

// Enviar mensaje
async function sendMessage() {
  if (!newMessage.value.trim() || sendingMessage.value) return;
  
  try {
    sendingMessage.value = true;
    const chatId = route.params.id;
    
    const response = await axios.post(`/api/chats/${chatId}/mensajes`, {
      contenido: newMessage.value.trim(),
      tipo: 'texto'
    }, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    if (response.data.success) {
      const mensaje = response.data.data.mensaje;
      messages.value.push(mensaje);
      newMessage.value = '';
      scrollToBottom();
    }
  } catch (err) {
    console.error('Error enviando mensaje:', err);
    alert('No se pudo enviar el mensaje. Intenta nuevamente.');
  } finally {
    sendingMessage.value = false;
  }
}

// Funciones auxiliares
const formatTime = (date) => {
  if (typeof date === 'string') {
    date = new Date(date);
  }
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
  if (route.query.from === 'chats') {
    router.push('/explorar/chats');
  } else {
    router.back();
  }
};

const abrirPerfilUsuario = () => {
  if (currentChat.value.solicitud_id) {
    router.push({
      name: 'adoption-request',
      params: {
        solicitudId: currentChat.value.solicitud_id
      }
    });
  } else if (chatInfo.value?.usuario_id) {
    router.push({
      name: 'user-profile-room',
      params: {
        userId: chatInfo.value.usuario_id
      },
      query: { from: 'chat-room' }
    });
  }
};

// Manejo de scroll para cargar más mensajes
const handleScroll = () => {
  if (!messagesContainer.value || loadingMessages.value || !hasMore.value) return;
  
  const container = messagesContainer.value;
  if (container.scrollTop === 0) {
    cargarMensajes();
  }
};

// Ciclo de vida
onMounted(async () => {
  await cargarChat();
  await cargarMensajes(true);
  
  if (messagesContainer.value) {
    messagesContainer.value.addEventListener('scroll', handleScroll);
  }
  
  // Configurar polling para nuevos mensajes
  const pollingInterval = setInterval(async () => {
    if (messages.value.length > 0) {
      await cargarNuevosMensajes();
    }
  }, 5000); // Cada 5 segundos
  
  onUnmounted(() => {
    clearInterval(pollingInterval);
    if (messagesContainer.value) {
      messagesContainer.value.removeEventListener('scroll', handleScroll);
    }
  });
});

// Cargar solo nuevos mensajes
async function cargarNuevosMensajes() {
  try {
    const chatId = route.params.id;
    const ultimoMensajeId = messages.value.length > 0 ? messages.value[messages.value.length - 1].id : null;
    
    if (!ultimoMensajeId) return;
    
    const response = await axios.get(`/api/chats/${chatId}/mensajes`, {
      params: {
        after: ultimoMensajeId,
        per_page: 50
      },
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });

    if (response.data.success && response.data.data.mensajes.length > 0) {
      const nuevosMensajes = response.data.data.mensajes;
      messages.value = [...messages.value, ...nuevosMensajes];
      
      // Auto-scroll si el usuario está cerca del final
      const container = messagesContainer.value;
      if (container) {
        const nearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100;
        if (nearBottom) {
          scrollToBottom();
        }
      }
    }
  } catch (err) {
    console.error('Error cargando nuevos mensajes:', err);
  }
}

// Watch para cambios en el chat
watch(() => route.params.id, async (newId) => {
  if (newId) {
    await cargarChat();
    await cargarMensajes(true);
  }
});
</script>
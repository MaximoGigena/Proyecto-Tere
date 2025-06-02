<template>
  <div class="w-full h-full flex flex-col relative">
    <!-- Header sticky con información del chat -->
    <div class="sticky top-0 z-30 bg-white px-4 py-2 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <button @click="volverAchats" class="text-gray-700 hover:text-black transition">
          <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
        </button>
        
        <div class="flex flex-col items-center">
          <img 
            :src="currentChat.img" 
            @click="abrirPerfilUsuario" 
            class="w-12 h-12 rounded-full object-cover cursor-pointer" 
            :alt="currentChat.nombre" 
          />
          <span class="font-semibold">{{ currentChat.nombre }}</span>
          <span class="text-xs text-gray-500">{{ currentChat.online ? 'En línea' : 'Desconectado' }}</span>
        </div>
        
        <button class="text-gray-700 hover:text-black transition">
          <font-awesome-icon :icon="['fas', 'ellipsis-vertical']" class="text-xl"/>
        </button>
      </div>
    </div>

    <!-- Área de mensajes -->
    <div 
      ref="messagesContainer"
      class="flex-1 overflow-y-auto p-4 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden bg-gray-50"
    >
      <div v-for="(message, index) in messages" :key="index" 
           :class="['flex mb-4', message.sender === 'me' ? 'justify-end' : 'justify-start']">
        <div :class="['max-w-xs lg:max-w-md px-4 py-2 rounded-lg', 
                     message.sender === 'me' ? 'bg-blue-500 text-white' : 'bg-white text-gray-800 border border-gray-200']">
          <p>{{ message.text }}</p>
          <p class="text-xs mt-1 text-right" :class="message.sender === 'me' ? 'text-blue-100' : 'text-gray-500'">
            {{ formatTime(message.time) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Input para enviar mensajes -->
    <div class="sticky bottom-0 bg-white border-t border-gray-200 p-3">
      <div class="flex items-center gap-2">
        <button class="text-gray-500 hover:text-gray-700">
          <font-awesome-icon :icon="['fas', 'paperclip']" class="text-xl"/>
        </button>
        <input 
          v-model="newMessage"
          @keyup.enter="sendMessage"
          type="text" 
          placeholder="Escribe un mensaje..."
          class="flex-1 border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
        <button 
          @click="sendMessage"
          :disabled="!newMessage.trim()"
          class="text-gray-500 hover:text-blue-500 disabled:opacity-50"
        >
          <font-awesome-icon :icon="['fas', 'paper-plane']" class="text-xl"/>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const newMessage = ref('');
const messagesContainer = ref(null);

// Datos hardcodeados de los chats
const chatData = {
  '1': {
    id: 1,
    nombre: 'Josefina, dolores del mes',
    img: 'https://cdn.pixabay.com/photo/2020/10/07/16/24/woman-5635665_960_720.jpg',
    online: true
  },
  '2': {
    id: 2,
    nombre: 'Pepita, juana',
    img: 'https://cdn.pixabay.com/photo/2020/01/27/17/04/cigar-4797760_960_720.jpg',
    online: true
  },
  '3': {
    id: 3,
    nombre: 'Maria, Rosa',
    img: 'https://cdn.pixabay.com/photo/2025/04/15/15/06/woman-9535611_1280.jpg',
    online: false
  },
  '4': {
    id: 4,
    nombre: 'Doña, juana',
    img: 'https://cdn.pixabay.com/photo/2025/04/15/19/41/woman-9536174_960_720.jpg',
    online: true
  }
};

// Chat actual - versión corregida
const currentChat = computed(() => {
  const chatId = route.params.id?.toString() || '1'; // Usa '1' como default si no hay ID
  return chatData[chatId] || chatData['1']; // Devuelve el chat o el primero por defecto
});

// Función para abrir perfil - versión simplificada
const abrirPerfilUsuario = () => {
  router.push({
    name: 'user-profile-room',
    params: {
      userId: currentChat.value.id
    },
     query: { from: 'chat-room' } 
  });
};

// Mensajes de ejemplo
const messages = ref([
  { text: 'Hola, ¿cómo estás?', sender: 'them', time: new Date(Date.now() - 3600000) },
  { text: '¡Hola! Estoy bien, gracias por preguntar. ¿Y tú?', sender: 'me', time: new Date(Date.now() - 3500000) },
  { text: 'Bien también. ¿Te molesta si me como a tu perro?', sender: 'them', time: new Date(Date.now() - 3400000) },
  { text: '¡¿Qué?! No, por supuesto que me molesta. ¿Por qué preguntas eso?', sender: 'me', time: new Date(Date.now() - 3300000) },
  { text: 'Era una broma, jaja. Solo quería ver tu reacción. En realidad estoy interesada en adoptarlo.', sender: 'them', time: new Date(Date.now() - 3200000) },
  { text: 'Ah, menos mal. Me asustaste por un momento. ¿Quieres conocerlo?', sender: 'me', time: new Date(Date.now() - 3100000) },
  { text: 'Sí, me encantaría. ¿Cuándo podríamos quedar?', sender: 'them', time: new Date(Date.now() - 3000000) }
]);

// Función para formatear la hora
const formatTime = (date) => {
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

// Función para enviar mensaje
const sendMessage = () => {
  if (!newMessage.value.trim()) return;
  
  messages.value.push({
    text: newMessage.value,
    sender: 'me',
    time: new Date()
  });
  
  newMessage.value = '';
  
  // Simular respuesta
  setTimeout(() => {
    messages.value.push({
      text: 'Gracias por tu mensaje. Te responderé pronto.',
      sender: 'them',
      time: new Date()
    });
    scrollToBottom();
  }, 1000);
  
  scrollToBottom();
};

// Función para hacer scroll al final
const scrollToBottom = () => {
  if (messagesContainer.value) {
    setTimeout(() => {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }, 50);
  }
};

// Debuggear al montar
onMounted(() => {
  console.log('Chat cargado:', currentChat.value);
  scrollToBottom();
});

const volverAchats = () => {
  // Verifica si venimos de la vista de chats
  if (route.query.from === 'chats') {
    router.push('/explorar/chats');
  } else {
    // Fallback por si acaso
    router.back();
  }
};
</script>
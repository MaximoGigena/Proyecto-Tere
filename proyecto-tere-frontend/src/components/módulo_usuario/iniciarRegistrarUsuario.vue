<template>
  <div v-show="visible" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="w-full max-w-sm h-154 p-6 bg-white rounded-lg shadow-md relative">
      <button @click="$emit('cerrar')" class="absolute top-2 right-3 text-gray-600 hover:text-black text-4xl">&times;</button>
      
      <div class="flex flex-col items-center">
        <img :src="fondo" alt="Logo Tere" class="w-40 mb-4" />
        <p class="text-3xl text-blue-400 font-bold text-center">{{ titulo }}</p>
      </div>

      <div class="mt-6 space-y-4">
        <button class="flex items-center justify-center w-full px-4 py-3 border border-gray-300 rounded-full bg-white">
          <img :src="telefono" class="w-6 h-6 mr-2" /> Continuar con móvil
        </button>
        <button class="flex items-center justify-center w-full px-4 py-3 border border-gray-300 rounded-full bg-white">
          <img :src="correo" class="w-6 h-6 mr-2" /> Continuar con correo
        </button>

        <!-- Botón de Facebook con pointer-events-auto -->
        <button 
          @click="redirectToFacebook"
          :disabled="loadingFacebook"
          :class="[
            'bg-[#1877f2] text-white w-full px-4 py-3 rounded-full flex items-center justify-center',
            'transition-all duration-200 relative pointer-events-auto',
            loadingFacebook ? 'opacity-75 cursor-not-allowed' : 'hover:bg-blue-600 active:bg-blue-700'
          ]"
        >
          <template v-if="loadingFacebook">
            <i class="fas fa-spinner fa-spin text-xl"></i>&nbsp;&nbsp;Procesando...
          </template>
          <template v-else>
            <i class="fab fa-facebook text-xl"></i>&nbsp;&nbsp;Continuar con Facebook
          </template>
        </button>

        <!-- Botón de Google -->
        <button 
          @click="redirectToGoogle"
          :disabled="loading"
          :class="[
            'bg-black text-white w-full px-4 py-3 rounded-full flex items-center justify-center',
            'transition-all duration-200 relative pointer-events-auto',
            loading ? 'opacity-75 cursor-not-allowed' : 'hover:bg-gray-800 active:bg-gray-900',
            error ? 'ring-2 ring-red-500' : ''
          ]"
        >
          <template v-if="loading">
            <i class="fas fa-spinner fa-spin text-xl"></i>&nbsp;&nbsp;Procesando...
          </template>
          <template v-else>
            <i class="fab fa-google text-xl"></i>&nbsp;&nbsp;Continuar con Google
          </template>
        </button>
      </div>

      <p class="mt-4 text-xs text-gray-500 text-center">
        <a href="#" class="text-blue-600 hover:underline">¿No puedes entrar a tu cuenta?</a>
      </p>

      <p class="mt-4 text-xs text-gray-500 text-center">
        Nunca compartiremos nada sin tu permiso
      </p>

      <div class="mt-4 text-xs text-center text-gray-500">
        Al registrarte, aceptas nuestras 
        <a href="#" class="text-blue-600 hover:underline">Condiciones de uso</a>.<br />
        Consulta nuestra 
        <a href="#" class="text-blue-600 hover:underline">Política de privacidad</a>.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const props = defineProps({
  visible: Boolean,
  titulo: {
    type: String,
    default: 'Iniciar sesión o registrarse'
  },
  fondo: String,
  telefono: String,
  correo: String
});

const emit = defineEmits(['cerrar', 'loading', 'error']);

const loading = ref(false);
const error = ref(false);
const googleClientId = import.meta.env.VITE_GOOGLE_CLIENT_ID;
const loadingFacebook = ref(false);
const router = useRouter();
const { processTokenFromUrl } = useAuth();

// Verificar si hay token en la URL al cargar el componente
onMounted(async () => {
  const hasToken = await processTokenFromUrl();
  if (hasToken) {
    emit('cerrar');
    // El usuario ya está autenticado, no necesitas hacer nada más
  }
});

const redirectToGoogle = () => {
  try {
    loading.value = true;
    error.value = false;
    
    // Redirección simple a Google OAuth
    window.location.href = 'http://localhost:8000/auth/google';
    
  } catch (err) {
    console.error('Error en redirección a Google:', err);
    loading.value = false;
    error.value = true;
    alert('Error al conectar con Google. Por favor, intenta nuevamente.');
  }
};

const redirectToFacebook = () => {
  console.log('🔵 Botón Facebook clickeado'); // Debug 1
  try {
    loadingFacebook.value = true;
    error.value = false;
    
    console.log('🔄 Redirigiendo a Facebook...'); // Debug 2
    console.log('📍 URL destino:', 'http://localhost:8000/auth/facebook'); // Debug 3
    
    // Redirección al endpoint de Laravel
    window.location.href = 'http://localhost:8000/auth/facebook';
    
  } catch (err) {
    console.error('❌ Error en redirección a Facebook:', err);
    loadingFacebook.value = false;
    error.value = true;
    alert('Error al conectar con Facebook. Por favor, intenta nuevamente.');
  }
};

</script>

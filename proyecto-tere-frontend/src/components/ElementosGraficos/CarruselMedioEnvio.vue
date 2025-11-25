<template>
  <div class="w-full flex flex-col items-center">
    <h2 class="text-lg font-semibold mb-3 text-gray-800">
      Seleccioná un medio de envío para la documentación del procedimiento
    </h2>

    <!-- Debug info -->
    <div v-if="debug" class="mb-4 p-2 bg-yellow-100 rounded text-xs">
      <p><strong>Debug:</strong> usuarioId = {{ props.usuarioId }}</p>
      <p>Medios cargados: {{ medios.length }}</p>
      <p>Cargando: {{ cargando }}</p>
    </div>

    <!-- Loading state -->
    <div v-if="cargando" class="flex items-center justify-center py-4">
      <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
      <span class="ml-2 text-gray-600">Cargando medios de contacto...</span>
    </div>

    <!-- Carrusel -->
    <div v-else-if="medios.length" class="relative w-full max-w-sm overflow-hidden">
      <div
        ref="carousel"
        class="flex gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth scrollbar-thin scrollbar-thumb-transparent px-4"
      >
        <div
          v-for="medio in medios"
          :key="medio.id"
          class="min-w-[120px] snap-center flex flex-col items-center justify-center bg-white border rounded-2xl shadow-sm cursor-pointer transition-all duration-300"
          :class="medioSeleccionado === medio.id ? 'border-blue-500 scale-105 shadow-md' : 'border-gray-200 hover:scale-105'"
          @click="seleccionarMedio(medio.id)"
        >
          <div class="p-4 flex flex-col items-center gap-2">
            <component :is="medio.icon" class="w-8 h-8" :class="medio.color" />
            <span class="text-sm font-medium text-gray-700">{{ medio.nombre }}</span>
            <span class="text-xs text-gray-500 truncate max-w-[100px]">{{ medio.valor }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-4">
      <p class="text-gray-500 text-sm">No hay medios de contacto disponibles para este usuario.</p>
      <p class="text-gray-400 text-xs mt-1">El usuario no ha proporcionado email, teléfono ni Telegram.</p>
    </div>

    <!-- Selected medium info -->
    <div v-if="medioSeleccionado && medioSeleccionadoInfo" class="mt-4 p-3 bg-blue-50 rounded-lg">
      <p class="text-sm text-blue-700">
        <span class="font-semibold">Medio seleccionado:</span> 
        {{ medioSeleccionadoInfo.nombre }} - {{ medioSeleccionadoInfo.valor }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from "vue";
import { Mail, Send, MessageCircle } from "lucide-vue-next";
import axios from "axios";
import { useAuth } from '@/composables/useAuth';

const props = defineProps({
  usuarioId: {
    type: Number,
    required: true
  }
});

const emit = defineEmits(["update:medio"]);

const { accessToken, isAuthenticated } = useAuth();

const medios = ref([]);
const medioSeleccionado = ref(null);
const cargando = ref(false);
const error = ref(null);
const debug = ref(false); // Cambiar a false en producción

const iconMap = {
  email: { icon: Mail, color: "text-blue-500" },
  telegram: { icon: Send, color: "text-sky-500" },
  whatsapp: { icon: MessageCircle, color: "text-green-500" }
};

// Información del medio seleccionado
const medioSeleccionadoInfo = computed(() => {
  if (!medioSeleccionado.value) return null;
  return medios.value.find(m => m.id === medioSeleccionado.value);
});

async function cargarMedios() {
  if (!props.usuarioId) {
    console.warn("❌ No se proporcionó usuarioId");
    return;
  }

  if (!isAuthenticated.value) {
    error.value = "No autenticado. Por favor, inicie sesión.";
    console.error("❌ Usuario no autenticado");
    return;
  }

  console.log(`🔄 Cargando medios para usuario ID: ${props.usuarioId}`);
  console.log(`🔐 Token disponible: ${!!accessToken.value}`);
  
  cargando.value = true;
  error.value = null;
  
  try {
    const response = await axios.get(`/api/usuarios/${props.usuarioId}/medios`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });
    
    console.log("📡 Respuesta de API medios:", response.data);
    
    if (response.data.success && response.data.data && response.data.data.length) {
      medios.value = response.data.data.map((m) => ({
        ...m,
        ...iconMap[m.id]
      }));
      console.log("✅ Medios cargados:", medios.value);
      
      // Seleccionar el primer medio por defecto
      if (medios.value.length > 0 && !medioSeleccionado.value) {
        seleccionarMedio(medios.value[0].id);
      }
    } else {
      medios.value = [];
      console.log("ℹ️ No hay medios de contacto disponibles:", response.data.message);
    }
  } catch (error) {
    console.error("❌ Error al cargar medios:", error);
    console.error("Detalles del error:", error.response?.data);
    
    if (error.response?.status === 401) {
      error.value = "No autorizado. Por favor, verifique su sesión.";
    } else if (error.response?.status === 404) {
      error.value = "No se encontraron medios de contacto para este usuario.";
    } else {
      error.value = "Error al cargar los medios de contacto. Intente nuevamente.";
    }
    
    medios.value = [];
  } finally {
    cargando.value = false;
  }
}

function seleccionarMedio(id) {
  medioSeleccionado.value = id;
  emit("update:medio", id);
  console.log(`🎯 Medio seleccionado: ${id}`);
}

// Recargar medios cuando cambie el usuarioId
watch(() => props.usuarioId, (newVal) => {
  console.log(`👤 UsuarioId cambiado: ${newVal}`);
  if (newVal && isAuthenticated.value) {
    cargarMedios();
  } else {
    medios.value = [];
    medioSeleccionado.value = null;
    error.value = null;
  }
});

// También observar cambios en la autenticación
watch(isAuthenticated, (newVal) => {
  console.log(`🔐 Estado de autenticación cambiado: ${newVal}`);
  if (newVal && props.usuarioId) {
    cargarMedios();
  }
});

onMounted(() => {
  console.log("🚀 CarruselMedioEnvio montado");
  console.log("👤 UsuarioId recibido:", props.usuarioId);
  console.log("🔐 Usuario autenticado:", isAuthenticated.value);
  
  if (props.usuarioId && isAuthenticated.value) {
    cargarMedios();
  } else if (!isAuthenticated.value) {
    error.value = "Esperando autenticación...";
  }
});
</script>
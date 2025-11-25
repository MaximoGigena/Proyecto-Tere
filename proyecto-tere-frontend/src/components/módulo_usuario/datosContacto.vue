<!-- components/usuario/DatosContacto.vue -->
<template>
  <div>
    <div class="flex items-center my-6">
      <div class="flex-grow border-t border-gray-600"></div>
      <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
        Datos de Contacto 
      </h5>
      <div class="flex-grow border-t border-gray-600"></div>
    </div>
    
    <p>Estos datos nos permiten ponernos en contacto con vos en caso de que un veterinario u otro usuario necesite comunicarse por una consulta o seguimiento, Tus datos van a permanecer anonimos y lejos del alcance de los demas usuarios. (Son opcionales, pero te agradecemos cualquier colaboración).</p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
      <!-- Teléfono -->
      <div>
        <label class="block font-medium">Teléfono</label>
        <input
          v-model="contacto.telefono_contacto"
          type="tel"
          class="w-full border rounded p-2"
          placeholder="Ej: +54 9 11 1234 5xxx"
        />
      </div>

      <!-- Correo electrónico -->
      <div>
        <label class="block font-medium">Correo electrónico</label>
        <input
          v-model="contacto.email_contacto"
          type="email"
          class="w-full border rounded p-2"
          placeholder="Ej: ejemplo@email.com"
        />
      </div>

      <!-- DNI -->
      <div>
        <label class="block font-medium">DNI</label>
        <input
          v-model="contacto.dni"
          type="text"
          class="w-full border rounded p-2"
          placeholder="Ej: 45.208.xxx"
        />
      </div>

      <!-- Nombre Completo -->
      <div>
        <label class="block font-medium">Nombre Completo</label>
        <input
          v-model="contacto.nombre_completo"
          type="text"
          class="w-full border rounded p-2"
          placeholder="Ej: Juan Pepito"
        />
      </div>

      <!-- En la sección de Telegram del formulario -->
      <div class="col-span-full">
        <label class="block font-medium mb-2">Notificaciones por Telegram</label>
        <div class="border rounded-lg p-4 bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <p class="font-medium">Recibe alertas importantes directamente en Telegram</p>
              <p class="text-sm text-gray-600 mt-1">
                {{ telegramConfigurado ? 
                  '✅ Telegram configurado - Recibirás notificaciones importantes' : 
                  '⚠️ No configurado - Te perderás alertas de mascotas, veterinarios, etc.' 
                }}
              </p>
              <!-- NUEVO: Mostrar el email que se usará -->
              <p class="text-xs text-blue-600 mt-1" v-if="!telegramConfigurado">
                📱 Se vinculará con tu email: <strong>{{ emailRegistro }}</strong>
              </p>
              <p class="text-xs text-green-600 mt-1" v-if="telegramConfigurado">
                ✅ Vinculado con: <strong>{{ emailRegistro }}</strong>
              </p>
            </div>
            <button
              type="button"
              @click="configurarTelegram"
              :class="[
                'px-4 py-2 rounded font-medium transition-colors flex items-center gap-2 whitespace-nowrap',
                telegramConfigurado ? 
                  'bg-green-100 text-green-700 hover:bg-green-200' : 
                  'bg-blue-500 text-white hover:bg-blue-600'
              ]"
            >
              <font-awesome-icon :icon="['fab', 'telegram']" v-if="!telegramConfigurado" />
              {{ telegramConfigurado ? '✅ Configurado' : 'Configurar' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Configuración de Telegram -->
    <div v-if="showTelegramModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4">Configurar Telegram - Paso a Paso</h3>
        
        <div class="space-y-4">
          <!-- Paso 1 -->
          <div class="bg-green-50 border border-green-200 rounded p-3">
            <p class="font-medium text-green-800">✅ Paso 1: Comando listo (copiado automáticamente)</p>
            <code class="block bg-white p-2 rounded border mt-2 font-mono text-sm">/start {{ emailContacto }}</code>
            <p class="text-xs text-green-600 mt-1">Ya está en tu portapapeles</p>
          </div>
          
          <!-- Paso 2 -->
          <div class="bg-blue-50 border border-blue-200 rounded p-3">
            <p class="font-medium text-blue-800">📱 Paso 2: Abre Telegram</p>
              <button 
                @click="abrirTelegram"  
                class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full flex items-center gap-2 justify-center"
              >
                <font-awesome-icon :icon="['fab', 'telegram']" />
                Abrir Telegram
              </button>
          </div>
          
          <!-- Paso 3 -->
          <div class="bg-purple-50 border border-purple-200 rounded p-3">
            <p class="font-medium text-purple-800">📋 Paso 3: Pega y envía</p>
            <p class="text-sm text-purple-700 mt-1">En Telegram, pega el comando y envíalo</p>
          </div>
          
          <!-- Paso 4 -->
          <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
            <p class="font-medium text-yellow-800">🔍 Paso 4: Verifica</p>
            <button 
              @click="verificarTelegram" 
              class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 w-full"
            >
              ✅ Ya envié el comando - Verificar
            </button>
          </div>
        </div>
        
        <div class="flex justify-end mt-4">
          <button
            @click="showTelegramModal = false"
            class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<!-- components/usuario/DatosContacto.vue -->
<script setup>
import { reactive, defineProps, defineEmits, watch, computed, ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthToken } from '@/composables/useAuthToken'

const { accessToken } = useAuthToken()

// Props para recibir datos existentes - AGREGAR EMAIL OBLIGATORIO
const props = defineProps({
  datosIniciales: {
    type: Object,
    default: () => ({})
  },
  usuarioId: {
    type: [Number, String],
    default: null
  },
  // NUEVO: Email obligatorio del registro
  emailRegistro: {
    type: String,
    required: true // Hacerlo obligatorio
  }
})

// Emits para enviar datos actualizados
const emit = defineEmits(['datosActualizados'])

// Estados para Telegram
const showTelegramModal = ref(false)
const telegramConfigurado = ref(false)
const telegramChatId = ref(null)
const verificandoTelegram = ref(false)

// Configuración del bot de Telegram
const telegramBotUsername = 'Proyecto_Tere_bot'
const telegramBotLink = `https://t.me/${telegramBotUsername}`

// Estado reactivo para los datos de contacto
const contacto = reactive({
  dni: props.datosIniciales.dni || '',
  telefono_contacto: props.datosIniciales.telefono_contacto || '',
  // USAR EL EMAIL DEL REGISTRO COMO VALOR POR DEFECTO
  email_contacto: props.datosIniciales.email_contacto || props.emailRegistro || '',
  nombre_completo: props.datosIniciales.nombre_completo || ''
})

// Computed para el email de contacto - AHORA SIEMPRE USA EL EMAIL DEL REGISTRO
const emailContacto = computed(() => {
  // Forzar que siempre use el email del registro para Telegram
  return props.emailRegistro;
})

// Watcher para emitir cambios
watch(contacto, (nuevosDatos) => {
  emit('datosActualizados', nuevosDatos)
}, { deep: true })

// Función para configurar Telegram - AHORA NO NECESITA VALIDACIÓN DE EMAIL
const configurarTelegram = () => {
  // ✅ YA NO VALIDAMOS email_contacto porque usamos el email del registro
  const comandoCompleto = `/start ${props.emailRegistro}`;
  
  navigator.clipboard.writeText(comandoCompleto).then(() => {
    showTelegramModal.value = true;
    
    // Mensaje más claro indicando que usa el email del registro
    setTimeout(() => {
      alert(`✅ Comando copiado: "${comandoCompleto}"\n\n📱 Ahora:\n1. Abre Telegram\n2. Busca @${telegramBotUsername}\n3. PEGA este comando\n4. ENVÍA el mensaje\n5. Vuelve aquí y haz click en "Verificar"\n\n💡 Se usará tu email de registro: ${props.emailRegistro}`);
    }, 500);
  });
}

// Función mejorada para abrir Telegram
const abrirTelegram = () => {
  // ✅ YA NO VALIDAMOS porque siempre tenemos el email del registro
  const encodedEmail = encodeURIComponent(props.emailRegistro);
  const webLink = `https://t.me/${telegramBotUsername}?start=${encodedEmail}`;
  
  console.log('🔗 Enlace de Telegram con email de registro:', webLink);
  
  const nuevaVentana = window.open(webLink, '_blank', 'noopener,noreferrer');
  
  if (!nuevaVentana) {
    // Fallback más explícito
    const comandoCompleto = `/start ${props.emailRegistro}`;
    navigator.clipboard.writeText(comandoCompleto).then(() => {
      alert(`⚠️ No se pudo abrir Telegram automáticamente.\n\n✅ PERO hemos copiado el comando exacto al portapapeles.\n\n📋 Ahora:\n1. Abre Telegram MANUALMENTE\n2. Busca @${telegramBotUsername}\n3. PEGA y ENVÍA este comando:\n\n"${comandoCompleto}"\n\n4. Vuelve aquí y haz click en "Ya lo hice"\n\n💡 Se usará tu email de registro: ${props.emailRegistro}`);
    });
  } else {
    // Mensaje MÁS CLARO para el usuario
    alert(`📱 Se abrirá Telegram. POR FAVOR:\n\n🚨 NO ENVÍES solo "/start"\n\n✅ DEBES enviar el mensaje COMPLETO que aparece pre-escrito:\n\n"/start ${props.emailRegistro}"\n\n🔍 Verifica que el mensaje incluya tu email de registro antes de enviar.`);
  }
}

// Función para verificar el estado de Telegram
const verificarEstadoTelegram = async () => {
  if (!props.usuarioId) return;
  
  try {
    const response = await axios.get(`/api/telegram/usuarios/${props.usuarioId}/telegram-chat-id`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    });
    
    if (response.data.success && response.data.data.telegram_chat_id) {
      telegramConfigurado.value = true;
      telegramChatId.value = response.data.data.telegram_chat_id;
    } else {
      telegramConfigurado.value = false;
      telegramChatId.value = null;
    }
  } catch (error) {
    console.log('ℹ️ Usuario no tiene Telegram configurado aún');
    telegramConfigurado.value = false;
  }
}

// Función corregida para verificar Telegram - AHORA USA EMAIL DEL REGISTRO
const verificarTelegram = async () => {
  // ✅ YA NO VALIDAMOS porque siempre tenemos el email del registro
  console.log('🔍 Verificando Telegram por email de registro:', props.emailRegistro);
  
  try {
    verificandoTelegram.value = true;
    
    const response = await axios.get(`/api/telegram/verificar-por-email`, {
      params: {
        email: props.emailRegistro // Usar email del registro
      }
    });

    console.log('✅ Respuesta verificación por email:', response.data);

    if (response.data.success && response.data.data.telegram_chat_id) {
      telegramConfigurado.value = true;
      telegramChatId.value = response.data.data.telegram_chat_id;
      
      alert('🎉 ¡Telegram configurado correctamente!\n\nAhora recibirás notificaciones importantes por Telegram.');
      showTelegramModal.value = false;
      
      // ✅ ACTUALIZAR EL USUARIO SI ESTÁ EN MODO REGISTRO
      if (props.usuarioId) {
        await actualizarUsuarioConTelegram();
      }
    } else {
      alert('⚠️ Aún no hemos detectado la configuración de Telegram.\n\nAsegúrate de:\n1. Haber enviado el mensaje /start al bot\n2. Esperar unos segundos\n\nReintentando en 3 segundos...');
      
      setTimeout(async () => {
        await verificarTelegram();
      }, 3000);
    }
    
  } catch (error) {
    console.error('❌ Error al verificar Telegram por email:', error);
    
    if (error.response?.status === 404) {
      alert('⚠️ Aún no hemos detectado la configuración de Telegram.\n\nAsegúrate de:\n1. Haber enviado el mensaje /start al bot\n2. Esperar unos segundos\n\nReintentando en 3 segundos...');
      
      setTimeout(async () => {
        await verificarTelegram();
      }, 3000);
    } else {
      alert('❌ Error al verificar Telegram. Intenta nuevamente.');
    }
  } finally {
    verificandoTelegram.value = false;
  }
}

// Función para actualizar el usuario con el chat ID de Telegram
const actualizarUsuarioConTelegram = async () => {
  if (!props.usuarioId || !telegramChatId.value) return;
  
  try {
    const response = await axios.post(`/api/telegram/guardar-chat-id`, {
      email: props.emailRegistro, // Usar email del registro
      telegram_chat_id: telegramChatId.value
    }, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    });
    
    console.log('✅ Chat ID guardado en usuario:', response.data);
  } catch (error) {
    console.error('❌ Error guardando chat ID:', error);
  }
}

// Método para obtener datos (útil para el componente padre)
const obtenerDatos = () => {
  return { ...contacto }
}

// Método para limpiar datos
const limpiarDatos = () => {
  contacto.dni = ''
  contacto.telefono_contacto = ''
  // NO limpiar email_contacto si queremos mantener el email del registro
  contacto.nombre_completo = ''
}

// Verificar estado de Telegram cuando el componente se monta
onMounted(() => {
  console.log('📧 Email de registro recibido:', props.emailRegistro);
  
  if (props.usuarioId) {
    verificarEstadoTelegram();
  }
})

// Exponer métodos al componente padre
defineExpose({
  obtenerDatos,
  limpiarDatos,
  emailContacto,
  verificarEstadoTelegram
})
</script>
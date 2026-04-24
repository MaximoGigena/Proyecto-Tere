<!-- components/usuario/DatosContacto.vue -->
<template>
  <div>
    <div class="flex items-center my-6">
      <div class="flex-grow border-t border-gray-600"></div>
      <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
        Datos de Contacto 
        <span v-if="esModificacion" class="text-sm text-blue-600 ml-2">(Modo edición)</span>
      </h5>
      <div class="flex-grow border-t border-gray-600"></div>
    </div>
    
    <p class="mb-4">
      Estos datos nos permiten ponernos en contacto con vos. 
      <span v-if="esModificacion">Puedes modificar la información existente o dejarla como está.</span>
      <span v-else>Tus datos van a permanecer anónimos y lejos del alcance de los demás usuarios. (Son opcionales, pero te agradecemos cualquier colaboración).</span>
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
      <!-- Teléfono -->
      <!-- Teléfono - Ahora con selector de país -->
      <div>
        <TelefonoInput 
          v-model="contacto.telefono_contacto"
          @update:model-value="handleTelefonoChange"
        />
        <p v-if="errorTelefono" class="text-red-500 text-xs mt-1">
          {{ errorTelefono }}
        </p>
      </div>

      <!-- Correo electrónico -->
      <div>
        <label class="block font-medium">Correo electrónico de contacto</label>
        <input
          v-model="contacto.email_contacto"
          type="email"
          class="w-full border rounded p-2"
          placeholder="Ej: ejemplo@email.com"
        />
        <p class="text-xs text-gray-500 mt-1">
          {{ esModificacion ? 'Si no se modifica, se mantendrá el valor actual' : 'Opcional - Para contactarte' }}
        </p>
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

      <!-- Sección de Telegram -->
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
          <div class="bg-green-50 border border-green-200 rounded p-3">
            <p class="font-medium text-green-800">✅ Paso 1: Enlace generado (copiado automáticamente)</p>
            <code class="block bg-white p-2 rounded border mt-2 font-mono text-xs break-all">{{ telegramLink }}</code>
            <p class="text-xs text-green-600 mt-1">Ya está en tu portapapeles</p>
          </div>
          
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
          
          <div class="bg-purple-50 border border-purple-200 rounded p-3">
            <p class="font-medium text-purple-800">📋 Paso 3: Envía el comando</p>
            <p class="text-sm text-purple-700 mt-1">
              Al hacer clic en el enlace, se abrirá el chat con @{{ telegramBotUsername || telegramBotUsernameDefault }} 
              con el comando /start pre-escrito. Solo debes enviarlo.
            </p>
          </div>
          
          <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
            <p class="font-medium text-yellow-800">🔍 Paso 4: Verifica</p>
            <button 
              @click="verificarTelegram" 
              :disabled="verificandoTelegram"
              class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 w-full disabled:opacity-50"
            >
              {{ verificandoTelegram ? 'Verificando...' : '✅ Ya envié el comando - Verificar' }}
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

<script setup>
import { reactive, defineProps, defineEmits, watch, computed, ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthToken } from '@/composables/useAuthToken'
import TelefonoInput from '@/components/ElementosGraficos/TelefonoInput.vue'

const { accessToken } = useAuthToken()

const props = defineProps({
  datosIniciales: {
    type: Object,
    default: () => ({})
  },
  usuarioId: {
    type: [Number, String],
    default: null
  },
  emailRegistro: {
    type: String,
    required: true
  },
  esModificacion: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['datosActualizados'])

const errorTelefono = ref('')

// Estados para Telegram
const showTelegramModal = ref(false)
const telegramConfigurado = ref(false)
const telegramChatId = ref(null)
const verificandoTelegram = ref(false)
const telegramToken = ref(null)
const telegramLink = ref('')
const telegramBotUsername = ref('')

const telegramBotUsernameDefault = 'Proyecto_Tere_bot'

// Función para validar teléfono argentino
const validarTelefono = (telefono) => {
  if (!telefono) return true // Opcional
  
  // Limpiar el número (solo dígitos)
  const clean = telefono.replace(/\D/g, '')
  
  // Validar según diferentes países
  if (clean.startsWith('54')) { // Argentina
    if (clean.length < 10 || clean.length > 13) {
      errorTelefono.value = 'El teléfono argentino debe tener entre 10 y 13 dígitos'
      return false
    }
  } 
  else if (clean.startsWith('34')) { // España
    if (clean.length !== 11) {
      errorTelefono.value = 'El teléfono español debe tener 9 dígitos (+34)'
      return false
    }
  }
  else if (clean.startsWith('52')) { // México
    if (clean.length < 12 || clean.length > 13) {
      errorTelefono.value = 'El teléfono mexicano debe tener 10-11 dígitos (+52)'
      return false
    }
  }
  else if (clean.startsWith('56')) { // Chile
    if (clean.length !== 11) {
      errorTelefono.value = 'El teléfono chileno debe tener 9 dígitos (+56)'
      return false
    }
  }
  else if (clean.startsWith('598')) { // Uruguay
    if (clean.length !== 11) {
      errorTelefono.value = 'El teléfono uruguayo debe tener 8 dígitos (+598)'
      return false
    }
  }
  else if (clean.startsWith('57')) { // Colombia
    if (clean.length !== 12) {
      errorTelefono.value = 'El teléfono colombiano debe tener 10 dígitos (+57)'
      return false
    }
  }
  else if (clean.startsWith('1')) { // Estados Unidos
    if (clean.length !== 11) {
      errorTelefono.value = 'El teléfono de EE.UU. debe tener 10 dígitos (+1)'
      return false
    }
  }
  else {
    errorTelefono.value = 'Formato de teléfono no reconocido'
    return false
  }
  
  errorTelefono.value = ''
  return true
}

// Estado reactivo para los datos de contacto
const contacto = reactive({
  dni: props.datosIniciales.dni || '',
  telefono_contacto: props.datosIniciales.telefono_contacto || '',
  email_contacto: props.datosIniciales.email_contacto || props.emailRegistro || '',
  nombre_completo: props.datosIniciales.nombre_completo || ''
})

const handleTelefonoChange = (nuevoTelefono) => {
  contacto.telefono_contacto = nuevoTelefono
  validarTelefono(nuevoTelefono)
}

const emailContacto = computed(() => props.emailRegistro)

// Watcher para emitir cambios con validación
watch(contacto, (nuevosDatos) => {
  if (nuevosDatos.telefono_contacto && !validarTelefono(nuevosDatos.telefono_contacto)) {
    return
  }
  emit('datosActualizados', nuevosDatos)
}, { deep: true })


// Watcher para emitir cambios
watch(contacto, (nuevosDatos) => {
  emit('datosActualizados', nuevosDatos)
}, { deep: true })

const generarTokenTelegram = async () => {
  try {
    const response = await axios.post('/api/telegram/generar-token', {}, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    });
    
    if (response.data.success) {
      telegramToken.value = response.data.data.token;
      telegramLink.value = response.data.data.telegram_link;
      telegramBotUsername.value = response.data.data.bot_username;
      
      return true;
    }
    return false;
  } catch (error) {
    console.error('❌ Error generando token:', error);
    alert('Error al generar enlace de Telegram. Intenta nuevamente.');
    return false;
  }
}

const configurarTelegram = async () => {
  showTelegramModal.value = true;
  
  const tokenGenerado = await generarTokenTelegram();
  
  if (tokenGenerado) {
    // Copiar enlace completo al portapapeles
    navigator.clipboard.writeText(telegramLink.value).then(() => {
      setTimeout(() => {
        alert(`✅ Enlace de vinculación copiado al portapapeles!\n\n📱 Ahora:\n1. Abre Telegram\n2. Pega el enlace en cualquier chat\n3. Haz clic en el enlace\n4. Envía el comando /start que aparecerá automáticamente\n5. Vuelve aquí y haz click en "Verificar"\n\n🔗 Enlace: ${telegramLink.value}`);
      }, 500);
    });
  } else {
    alert('❌ Error al generar el enlace. Intenta nuevamente.');
  }
}

const abrirTelegram = () => {
  if (telegramLink.value) {
    const nuevaVentana = window.open(telegramLink.value, '_blank', 'noopener,noreferrer');
    
    if (!nuevaVentana) {
      navigator.clipboard.writeText(telegramLink.value).then(() => {
        alert(`⚠️ No se pudo abrir Telegram automáticamente.\n\n✅ Hemos copiado el enlace al portapapeles.\n\n📋 Ahora:\n1. Abre Telegram MANUALMENTE\n2. PEGA el enlace en cualquier chat\n3. Haz clic en el enlace\n\n🔗 Enlace: ${telegramLink.value}`);
      });
    }
  } else {
    configurarTelegram();
  }
}

const verificarEstadoTelegram = async () => {
  if (!props.usuarioId) return;
  
  try {
    const response = await axios.get(`/api/usuarios/${props.usuarioId}/telegram`, {
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

const verificarTelegram = async () => {
  if (!telegramToken.value) {
    alert('⚠️ Primero genera el enlace de vinculación.');
    return;
  }
  
  try {
    verificandoTelegram.value = true;
    
    const response = await axios.post('/api/telegram/verificar-token', {
      token: telegramToken.value
    }, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    });

    if (response.data.success) {
      telegramConfigurado.value = true;
      telegramChatId.value = response.data.data.telegram_chat_id;
      
      alert('🎉 ¡Telegram configurado correctamente!\n\nAhora recibirás notificaciones importantes por Telegram.');
      showTelegramModal.value = false;
      
      // Recargar estado
      await verificarEstadoTelegram();
    }
    
  } catch (error) {
    console.error('❌ Error al verificar Telegram:', error);
    
    if (error.response?.status === 404) {
      alert('⚠️ Aún no hemos detectado la configuración de Telegram.\n\nAsegúrate de:\n1. Haber abierto el enlace y enviado el comando /start al bot\n2. Esperar unos segundos\n\nReintentando en 3 segundos...');
      
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

const obtenerDatos = () => {
  return { ...contacto }
}

const limpiarDatos = () => {
  if (!props.esModificacion) {
    contacto.dni = ''
    contacto.telefono_contacto = ''
    contacto.nombre_completo = ''
  }
}

onMounted(() => {
  console.log('📧 Email de registro recibido:', props.emailRegistro);
  console.log('🔧 Modo edición:', props.esModificacion);

  if (contacto.telefono_contacto) {
    validarTelefonoArgentino(contacto.telefono_contacto)
  }
  
  if (props.usuarioId) {
    verificarEstadoTelegram();
  }
})

defineExpose({
  obtenerDatos,
  limpiarDatos,
  emailContacto,
  verificarEstadoTelegram
})
</script>
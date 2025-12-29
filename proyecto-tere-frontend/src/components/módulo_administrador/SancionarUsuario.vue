<!-- SancionarUsuario.vue -->
<template>
  <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg rounded-xl shadow p-6 space-y-6">

      <!-- Header -->
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-800">Aplicar sanción</h3>
        <button @click="emit('cerrar')" class="text-gray-400 hover:text-gray-600">
          ✕
        </button>
      </div>

      <!-- Usuario -->
      <div class="bg-gray-50 p-4 rounded-lg text-sm">
        <p><strong>Usuario:</strong> {{ usuarioDenunciado.nombre }}</p>
        <p><strong>Email:</strong> {{ usuarioDenunciado.email }}</p>
        <p><strong>ID:</strong> {{ usuarioDenunciado.id }}</p>
      </div>

      <!-- Tipo de sanción -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Tipo de sanción
        </label>
        <select v-model="sancion.tipo" class="w-full border rounded px-3 py-2">
          <option disabled value="">Seleccionar…</option>
          <option value="ADVERTENCIA">Advertencia</option>
          <option value="SUSPENSION_TEMPORAL">Suspensión temporal</option>
          <option value="SUSPENSION_PERMANENTE">Suspensión permanente</option>
        </select>
      </div>

      <!-- Duración -->
      <div v-if="sancion.tipo === 'SUSPENSION_TEMPORAL'">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Duración (días)
        </label>
        <input
          type="number"
          min="1"
          v-model.number="sancion.duracion"
          class="w-full border rounded px-3 py-2"
        />
      </div>

      <!-- Motivo -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Motivo / notas internas
        </label>
        <textarea
          v-model="sancion.motivo"
          rows="3"
          class="w-full border rounded px-3 py-2"
        />
      </div>

      <!-- Acciones -->
      <div class="flex justify-end space-x-3 pt-4 border-t">
        <button
          @click="emit('cerrar')"
          class="bg-gray-100 px-4 py-2 rounded-lg"
        >
          Cancelar
        </button>

        <button
          @click="aplicarSancion"
          :disabled="enviando || !sancion.tipo || !sancion.motivo"
          class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ enviando ? 'Aplicando…' : 'Confirmar sanción' }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
  denunciaId: {
    type: Number,
    required: true
  },
  usuarioDenunciado: {
    type: Object,
    required: true
  },
  gravedad: {
    type: String,
    default: 'Media'
  }
})

const emit = defineEmits(['cerrar', 'sancionAplicada'])
const { accessToken } = useAuth()

const enviando = ref(false)
const yaTieneSancion = ref(false)

// Definir el objeto sancion
const sancion = reactive({
  tipo: '',
  duracion: 7, // Valor por defecto
  motivo: ''
})

// Mapeos para los tipos de sanción
const tipoMap = {
  'ADVERTENCIA': 'ADVERTENCIA',
  'SUSPENSION_TEMPORAL': 'SUSPENSION_TEMPORAL',
  'SUSPENSION_PERMANENTE': 'SUSPENSION_PERMANENTE'
}

// Mapeo para niveles según gravedad
const nivelMap = {
  'Alta': 'GRAVE',
  'Media': 'MODERADO',
  'Baja': 'LEVE'
}

// Verificar si ya tiene sanción activa
const verificarSancionExistente = async () => {
  try {
    const headers = {
      Authorization: `Bearer ${accessToken.value}`,
      'Content-Type': 'application/json'
    };
    
    const response = await axios.get(`/api/denuncias/${props.denunciaId}/sanciones-activas`, { headers });
    
    if (response.data.data && response.data.data.length > 0) {
      yaTieneSancion.value = true;
      alert('⚠️ Este usuario ya tiene una sanción activa para esta denuncia. Revisa en el historial.');
    }
  } catch (error) {
    console.log('No hay sanciones activas o error:', error);
  }
}

onMounted(() => {
  verificarSancionExistente();
});

const aplicarSancion = async () => {
  if (yaTieneSancion.value) {
    if (!confirm('Ya existe una sanción activa. ¿Deseas aplicar una nueva sanción?')) {
      return;
    }
  }
  
  if (!sancion.tipo) {
    alert('Seleccioná un tipo de sanción');
    return;
  }
  
  if (!sancion.motivo.trim()) {
    alert('Ingresá un motivo para la sanción');
    return;
  }
  
  if (sancion.tipo === 'SUSPENSION_TEMPORAL' && (!sancion.duracion || sancion.duracion < 1)) {
    alert('Para suspensión temporal, ingresá una duración válida en días');
    return;
  }

  try {
    enviando.value = true
    
    const payload = {
      tipo: tipoMap[sancion.tipo],
      nivel: nivelMap[props.gravedad] || 'MODERADO',
      razon: sancion.motivo,
      descripcion: `Sanción aplicada por denuncia #${props.denunciaId}`,
      duracion_dias: sancion.tipo === 'SUSPENSION_TEMPORAL' ? sancion.duracion : null,
      notas_admin: sancion.motivo
    };
    
    console.log('Payload a enviar:', payload);
    
    const url = `/api/sanciones/denuncia/${props.denunciaId}/aplicar`;
    
    const headers = {
      Authorization: `Bearer ${accessToken.value}`,
      'Content-Type': 'application/json'
    };
    
    const response = await axios.post(url, payload, { headers });
    
    console.log('✅ Sanción aplicada:', response.data);
    
    emit('sancionAplicada')
    emit('cerrar')
    
  } catch (error) {
    console.error('ERROR:', error);
    
    if (error.response) {
      console.error('Status:', error.response.status);
      console.error('Data:', error.response.data);
      
      if (error.response.status === 400 && error.response.data?.message?.includes('Ya existe')) {
        alert('⚠️ ' + error.response.data.message + '\n\nRevisa las sanciones activas antes de aplicar una nueva.');
        yaTieneSancion.value = true;
      } else if (error.response.status === 422 && error.response.data?.errors) {
        const errores = Object.values(error.response.data.errors).flat().join('\n');
        alert(`Errores de validación:\n${errores}`);
      } else {
        alert(`Error ${error.response.status}: ${error.response.data?.message || 'Error del servidor'}`);
      }
    } else {
      alert('Error de conexión con el servidor');
    }
  } finally {
    enviando.value = false
  }
}
</script>
<!-- SinPermisoHistorial.vue -->
<template>
  <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
    <!-- Icono de candado con animación suave -->
    <div class="relative mb-6">
      <div class="absolute inset-0 bg-yellow-100 rounded-full animate-ping opacity-20"></div>
      <div class="relative bg-yellow-100 p-6 rounded-full">
        <font-awesome-icon :icon="['fas', 'lock']" class="text-5xl text-yellow-600" />
      </div>
    </div>
    
    <!-- Título y mensaje principal -->
    <h3 class="text-2xl font-bold text-gray-800 mb-3">
      Historial {{ tipoHistorial }} no disponible
    </h3>
    
    <p class="text-gray-600 max-w-md mb-4">
      {{ mensaje }}
    </p>
    
    <!-- Mensaje adicional según el contexto -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 max-w-md">
      <div class="flex items-start gap-3">
        <font-awesome-icon 
          :icon="['fas', 'info-circle']" 
          class="text-yellow-600 text-xl mt-0.5 flex-shrink-0" 
        />
        <div class="text-left text-sm text-yellow-800">
          <p class="font-medium mb-1">¿Por qué no veo esta información?</p>
          <p>
            El tutor de {{ nombreMascota }} ha decidido no compartir el 
            historial {{ tipoHistorial }} por privacidad.
          </p>
          <p v-if="puedeContactar" class="mt-2">
            Si tienes preguntas específicas sobre la salud de {{ nombreMascota }}, 
            puedes contactar al tutor a través de los medios habilitados en la oferta.
          </p>
        </div>
      </div>
    </div>
    
    <!-- Sugerencia de acción -->
    <button 
      v-if="puedeContactar && !contactando"
      @click="irAContacto"
      class="mt-6 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
    >
      <font-awesome-icon :icon="['fas', 'comment']" />
      <span>Contactar al tutor</span>
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useRouter } from 'vue-router'

const props = defineProps({
  tipoHistorial: {
    type: String,
    default: 'médico',
    validator: (value) => ['preventivo', 'clínico', 'médico'].includes(value)
  },
  nombreMascota: {
    type: String,
    required: true
  },
  puedeContactar: {
    type: Boolean,
    default: false
  },
  ofertaId: {
    type: [Number, String],
    default: null
  }
})

const router = useRouter()
const contactando = ref(false)

const mensaje = computed(() => {
  const mensajes = {
    preventivo: 'La información sobre vacunas, desparasitaciones y otros cuidados preventivos no está disponible.',
    clínico: 'Los registros de cirugías, fármacos, terapias y diagnósticos no están disponibles.',
    médico: 'El historial médico completo no está disponible por decisión del tutor.'
  }
  return mensajes[props.tipoHistorial] || mensajes.médico
})

const irAContacto = () => {
  if (props.ofertaId) {
    // Navegar a la sección de contacto de la oferta
    router.push({
      name: 'detalle-oferta',
      params: { id: props.ofertaId },
      hash: '#contacto' // Scroll a la sección de contacto
    })
  }
}
</script>

<style scoped>
@keyframes ping {
  75%, 100% {
    transform: scale(1.5);
    opacity: 0;
  }
}
.animate-ping {
  animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}
</style>
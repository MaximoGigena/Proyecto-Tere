<!-- /components/TiempoRegistro.vue -->
<template>
  <div 
    class="tiempo-registro-component flex items-center justify-center w-28 h-28 rounded-2xl bg-purple-500 border border-purple-700 text-center hover:bg-purple-600 transition-all duration-200 cursor-default"
    :class="customClass"
    :title="tooltipText"
  >
    <div class="flex flex-col items-center justify-center">
      <p class="text-sm text-white font-semibold uppercase mb-2">
        {{ label }}
      </p>
      <p class="text-xs text-white font-semibold uppercase mb-2">
        {{ label2 }}
      </p>
      <div class="flex flex-col items-center justify-center">
        <i 
          :class="iconClass" 
          class="text-xl text-white mb-1"
        ></i>
        <p 
          v-if="showDays && dias > 0" 
          class="text-xl text-white"
        >
          {{ dias }} {{ dias === 1 ? 'día' : 'días' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// Props configurables
const props = defineProps({
  // Fecha de registro (obligatoria)
  fechaRegistro: {
    type: [String, Date],
    required: true
  },
  
  // Opciones de personalización
  label: {
    type: String,
    default: 'Registrado'
  },

  label2: {
    type: String,
    default: 'hace'
  },
  
  size: {
    type: String,
    default: 'normal', // 'small', 'medium', 'normal'
    validator: (value) => ['small', 'medium', 'normal'].includes(value)
  },
  
  showIcon: {
    type: Boolean,
    default: true
  },
  
  icon: {
    type: String,
    default: 'fa-paw'
  },
  
  showDays: {
    type: Boolean,
    default: false
  },
  
  // Clases CSS personalizadas
  customClass: {
    type: String,
    default: ''
  },
  
  // Estilos personalizados
  backgroundColor: {
    type: String,
    default: 'bg-indigo-50'
  },
  
  textColor: {
    type: String,
    default: 'text-indigo-700'
  },
  
  borderColor: {
    type: String,
    default: 'border-indigo-200'
  },
  
  // Tamaño del ícono
  iconSize: {
    type: String,
    default: 'text-2xl',
    validator: (value) => ['text-xl', 'text-2xl', 'text-3xl', 'text-4xl'].includes(value)
  }
})

// Computed properties
const dias = computed(() => {
  const fecha = new Date(props.fechaRegistro)
  const ahora = new Date()
  return Math.floor((ahora - fecha) / (1000 * 60 * 60 * 24))
})

const tiempoFormateado = computed(() => {
  if (dias.value === 0) return 'Hoy'
  if (dias.value === 1) return 'Ayer'
  if (dias.value < 7) return `Hace ${dias.value} días`
  if (dias.value < 30) {
    const semanas = Math.floor(dias.value / 7)
    return `Hace ${semanas} semana${semanas > 1 ? 's' : ''}`
  }
  if (dias.value < 365) {
    const meses = Math.floor(dias.value / 30)
    return `Hace ${meses} mes${meses > 1 ? 'es' : ''}`
  }
  const anios = Math.floor(dias.value / 365)
  return `Hace ${anios} año${anios > 1 ? 's' : ''}`
})

const tooltipText = computed(() => {
  const fecha = new Date(props.fechaRegistro)
  return `Registrado: ${fecha.toLocaleDateString('es-ES', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })} - ${tiempoFormateado.value}`
})

const iconClass = computed(() => {
  if (!props.showIcon) return ''
  return `fa-solid ${props.icon} ${props.iconSize}`
})

// También puedes exponer métodos si son necesarios
defineExpose({
  dias,
  tiempoFormateado
})
</script>

<style scoped>
.tiempo-registro-component {
  min-width: 112px; /* w-28 */
  min-height: 112px; /* h-28 */
}
</style>
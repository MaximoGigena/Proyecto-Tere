<!-- components/módulo_usuario/overlayRegistro.vue -->
<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Header del Overlay -->
      <div class="flex justify-between items-center p-6 border-b">
        <h3 class="text-2xl font-bold text-gray-800">{{ titulo }}</h3>
        <button
          @click="cerrar"
          class="text-gray-500 hover:text-gray-700 text-2xl transition-colors"
        >
          ×
        </button>
      </div>
      
      <!-- Contenido dinámico -->
      <div class="flex-grow overflow-y-auto p-6">
        <component
          :is="componente"
          v-bind="propsComponente"
          @datos-actualizados="handleDatosUpdate"
          ref="componenteRef"
        />
      </div>

      <!-- Footer con acciones dinámicas -->
      <div class="flex justify-end gap-4 p-6 border-t bg-gray-50">
        <button
          v-if="mostrarBotonOmitir"
          @click="omitir"
          class="px-6 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-100 transition-colors"
        >
          {{ textoOmitir }}
        </button>
        <button
          v-if="mostrarBotonGuardar"
          @click="guardar"
          class="px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
        >
          {{ textoGuardar }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props
const props = defineProps({
  titulo: {
    type: String,
    default: 'Formulario'
  },
  componente: {
    type: [Object, String],
    required: true
  },
  propsComponente: {
    type: Object,
    default: () => ({})
  },
  mostrarBotonOmitir: {
    type: Boolean,
    default: true
  },
  mostrarBotonGuardar: {
    type: Boolean,
    default: true
  },
  textoOmitir: {
    type: String,
    default: 'Omitir por ahora'
  },
  textoGuardar: {
    type: String,
    default: 'Guardar y Continuar'
  }
})

// Emits
const emit = defineEmits(['guardar', 'omitir', 'cerrar', 'datos-actualizados'])

// Refs
const componenteRef = ref(null)

// Computed
const datosActuales = computed(() => {
  return componenteRef.value?.obtenerDatos?.() || props.propsComponente
})

// Métodos
const handleDatosUpdate = (datos) => {
  emit('datos-actualizados', datos)
}

const guardar = () => {
  emit('guardar', datosActuales.value)
}

const omitir = () => {
  emit('omitir')
}

const cerrar = () => {
  emit('cerrar')
}

// Exponer métodos al componente padre
defineExpose({
  obtenerDatos: () => datosActuales.value,
  limpiarDatos: () => componenteRef.value?.limpiarDatos?.()
})
</script>
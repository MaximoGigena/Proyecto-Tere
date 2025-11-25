<!-- tarjetaMascota.vue -->
<template>
  <div 
    :class="`flex items-center relative shadow-lg transition-all duration-300 ${bgColor} rounded-full px-4 py-6 pl-22`"
  >
    <!-- Imagen circular que sobresale -->
    <img
      :src="mascota.imagen || mascota.foto_principal_url || '/default-mascota.jpg'"
      :alt="mascota.nombre"
      class="w-30 h-30 rounded-full border-4 ml-6 border-blue-300 object-cover absolute -left-4 top-1/2 -translate-y-1/2"
    />

    <!-- Información dentro del óvalo -->
    <div class="flex-1 flex items-center justify-between pl-12">
      <div class="text-sm">
        <p class="font-semibold">{{ mascota.nombre }}</p>
        <p>Edad: {{ mascota.edad_formateada || 'Edad no disponible' }}</p>
        <p>Sexo: {{ formatoSexo }}</p>
      </div>

      <!-- Botones de acción -->
      <div class="flex items-center space-x-3">
        <button 
          class="flex items-center gap-2 bg-indigo-500 hover:bg-indigo-600 
                 text-white text-sm font-medium px-3 py-2.5 rounded-lg shadow-sm transition-all duration-300"
          @click.stop="emit('editar', mascota.id)"
        >
          <font-awesome-icon :icon="['fas', 'paw']" class="text-base" />
          <span>Ceder</span>
        </button>

        <!-- Línea divisoria -->
        <div class="border-l border-gray-300 h-6"></div>

        <button 
          @click.stop="editarMascota"
          class="text-blue-600 hover:text-blue-800 transition-colors"
          title="Editar mascota"
        >
          <font-awesome-icon :icon="['fas', 'pen-to-square']" size="lg" />
        </button>
        <button 
          @click.stop="eliminarMascota"
          class="text-red-600 hover:text-red-800 transition-colors"
          title="Eliminar mascota"
        >
          <font-awesome-icon :icon="['fas', 'trash']" size="lg" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { computed } from 'vue'

const props = defineProps({
  mascota: {
    type: Object,
    required: true
  },
  bgColor: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['editar', 'eliminar', 'ceder'])

// Computed para la edad con múltiples fallbacks
const edadFormateada = computed(() => {
  const mascota = props.mascota
  
  // 1. Intentar con edad_formateada directo
  if (mascota.edad_formateada && mascota.edad_formateada !== 'Edad no disponible') {
    return mascota.edad_formateada
  }
  
  // 2. Intentar con la relación edadRelacion
  if (mascota.edad_relacion && mascota.edad_relacion.edad_formateada) {
    return mascota.edad_relacion.edad_formateada
  }
  
  // 3. Intentar con el accessor edad
  if (mascota.edad && mascota.edad !== 'Edad no disponible') {
    return mascota.edad
  }
  
  return 'Edad no disponible'
})

// Computed para formatear el sexo
const formatoSexo = computed(() => {
  const sexo = props.mascota.sexo
  if (sexo === 'macho') return 'Macho'
  if (sexo === 'hembra') return 'Hembra'
  return sexo || 'No especificado'
})

const editarMascota = () => {
  emit('editar', props.mascota.id)
}

const eliminarMascota = () => {
  emit('eliminar', props.mascota.id)
}

const cederMascota = () => {
  emit('ceder', props.mascota.id)
}
</script>
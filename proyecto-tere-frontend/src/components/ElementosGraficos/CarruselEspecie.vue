<template>
  <div>
    
    <div class="flex items-center justify-center gap-4">
      <!-- Botón anterior -->
      <button
        type="button"
        @click="prevEspecie"
        :class="['w-10 h-10 flex items-center justify-center rounded-full text-white transition-all duration-300 transform hover:scale-110 shadow-lg', getButtonColorClasses(especieIndex)]"
      >
        ‹
      </button>

      <!-- Contenedor del carrusel con animación - MODIFICADO -->
      <div class="relative w-32 h-32 rounded-2xl overflow-hidden"> <!-- Added rounded-2xl here -->
        <div 
          class="flex transition-transform duration-500 ease-in-out"
          :style="{ transform: `translateX(-${especieIndex * 100}%)` }"
        >
          <!-- Icono y nombre con colores y animación -->
          <div
            v-for="(especie, index) in especies"
            :key="especie.value"
            class="flex-shrink-0 w-32 h-32 flex flex-col items-center justify-center p-4 rounded-2xl transition-all duration-300 transform cursor-pointer min-w-32"
            :class="[
              getColorClasses(index),
              modelValue === especie.value 
                ? 'scale-110 shadow-2xl ring-4 ring-white ring-opacity-50' 
                : 'scale-100 opacity-70 hover:opacity-90 hover:scale-105'
            ]"
            @click="seleccionarEspecie(especie.value)"
          >
            <font-awesome-icon 
              :icon="especie.icon" 
              class="text-4xl mb-2 text-white drop-shadow-md" 
            />
            <span class="text-white font-semibold text-sm drop-shadow-md text-center">{{ especie.label }}</span>
          </div>
        </div>
      </div>

      <!-- Botón siguiente -->
      <button
        type="button"
        @click="nextEspecie"
        :class="['w-10 h-10 flex items-center justify-center rounded-full text-white transition-all duration-300 transform hover:scale-110 shadow-lg', getButtonColorClasses(especieIndex)]"
      >
        ›
      </button>
    </div>

    <!-- Indicadores del carrusel -->
    <div class="flex justify-center mt-4 space-x-2">
      <button
        v-for="(especie, index) in especies"
        :key="index"
        @click="especieIndex = index"
        class="w-3 h-3 rounded-full transition-all duration-300"
        :class="[
          index === especieIndex 
            ? getDotColor(index) + ' transform scale-125' 
            : 'bg-gray-300'
        ]"
      ></button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  especies: {
    type: Array,
    default: () => [
      { value: 'canino', label: 'Caninos', icon: ['fas', 'dog'] },
      { value: 'felino', label: 'Felinos', icon: ['fas', 'cat'] },
      { value: 'equino', label: 'Equinos', icon: ['fas', 'horse-head'] },
      { value: 'bovino', label: 'Bovinos', icon: ['fas', 'cow'] },
      { value: 'ave', label: 'Aves', icon: ['fas', 'crow'] },
      { value: 'pez', label: 'Peces', icon: ['fas', 'fish-fins'] },
      { value: 'otro', label: 'Otro', icon: ['fas', 'paw'] }
    ]
  }
})

// Emits
const emit = defineEmits(['update:modelValue'])

// Estado interno
const especieIndex = ref(0)
const currentEspecieIndex = computed(() => {
  const index = props.especies.findIndex(e => e.value === props.modelValue)
  return index === -1 ? 0 : index
})


// Watcher para sincronizar el índice cuando cambia el valor desde fuera
watch(currentEspecieIndex, (newIndex) => {
  if (newIndex !== -1 && newIndex !== especieIndex.value) {
    especieIndex.value = newIndex
  }
}, { immediate: true })

// Función para obtener clases de color según el índice
const getColorClasses = (index) => {
  const colors = [
    'bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600', // caninos
    'bg-gradient-to-br from-pink-400 via-red-500 to-rose-600',       // felinos
    'bg-gradient-to-br from-orange-400 via-yellow-500 to-amber-600', // equinos
    'bg-gradient-to-tr from-green-500 via-emerald-500 to-teal-600',  // bovinos
    'bg-gradient-to-br from-lime-400 via-green-500 to-emerald-600',  // aves
    'bg-gradient-to-tr from-cyan-400 via-blue-500 to-indigo-600',    // peces
    'bg-gradient-to-br from-teal-400 via-green-500 to-emerald-600'   // Otro
  ]
  return colors[index] || colors[0]
}

// Función para obtener color de los dots indicadores
const getDotColor = (index) => {
  const colors = [
    'bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-500', // caninos
    'bg-gradient-to-r from-pink-400 via-red-400 to-rose-500',       // felinos
    'bg-gradient-to-r from-orange-400 via-yellow-400 to-amber-500', // equinos
    'bg-gradient-to-r from-green-400 via-emerald-400 to-teal-500',  // bovinos
    'bg-gradient-to-r from-lime-400 via-green-400 to-emerald-500',  // aves
    'bg-gradient-to-r from-cyan-400 via-blue-400 to-indigo-500',    // peces
    'bg-gradient-to-r from-teal-400 via-green-400 to-emerald-500'   // Otro
  ]
  return colors[index] || colors[0]
}

// Función para obtener clases de color para botones según el índice
const getButtonColorClasses = (index) => {
  const colors = [
    // caninos
    'bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-600 hover:from-blue-600 hover:via-indigo-600 hover:to-purple-700',
    // felinos
    'bg-gradient-to-r from-pink-400 via-red-500 to-rose-600 hover:from-pink-500 hover:via-red-600 hover:to-rose-700',
    // equinos
    'bg-gradient-to-r from-orange-400 via-yellow-500 to-amber-600 hover:from-orange-500 hover:via-yellow-600 hover:to-amber-700',
    // bovinos
    'bg-gradient-to-r from-green-500 via-emerald-500 to-teal-600 hover:from-green-600 hover:via-emerald-600 hover:to-teal-700',
    // aves
    'bg-gradient-to-r from-lime-400 via-green-500 to-emerald-600 hover:from-lime-500 hover:via-green-600 hover:to-emerald-700',
    // peces
    'bg-gradient-to-r from-cyan-400 via-blue-500 to-indigo-600 hover:from-cyan-500 hover:via-blue-600 hover:to-indigo-700',
    // otro
    'bg-gradient-to-r from-teal-400 via-green-500 to-emerald-600 hover:from-teal-500 hover:via-green-600 hover:to-emerald-700'
  ]
  return colors[index] || colors[0]
}

// Métodos
const seleccionarEspecie = (value) => {
  emit('update:modelValue', value)
  // Encontrar el índice de la especie seleccionada
  const index = props.especies.findIndex(e => e.value === value)
  if (index !== -1) {
    especieIndex.value = index
  }
}

const prevEspecie = () => {
  especieIndex.value = (especieIndex.value - 1 + props.especies.length) % props.especies.length
  emit('update:modelValue', props.especies[especieIndex.value].value)
}

const nextEspecie = () => {
  especieIndex.value = (especieIndex.value + 1) % props.especies.length
  emit('update:modelValue', props.especies[especieIndex.value].value)
}
</script>
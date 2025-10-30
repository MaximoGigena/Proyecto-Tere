<template>
  <div>
    <div class="flex items-center justify-center gap-4">
      <!-- Botón anterior -->
      <button
        @click="prev"
        class="w-10 h-10 flex items-center justify-center rounded-full text-white transition-all duration-300 transform hover:scale-110 shadow-lg"
        :class="getBtnColor(currentIndex)"
      >‹</button>

      <!-- Carrusel -->
      <div class="relative w-80 h-32 overflow-hidden">
        <div class="flex items-center justify-center h-full">
          <div
            v-for="(esp, i) in especies"
            :key="esp.value"
            class="absolute flex-shrink-0 w-24 h-24 flex flex-col items-center justify-center rounded-2xl cursor-pointer transition-all duration-500 mx-2"
            :class="[
              getColor(i),
              getPositionClass(i)
            ]"
            :style="getPositionStyle(i)"
            @click="select(i)"
          >
            <font-awesome-icon :icon="esp.icon" class="text-3xl mb-1 text-white drop-shadow-md" />
            <span class="text-white font-semibold text-xs text-center drop-shadow-md">{{ esp.label }}</span>
          </div>
        </div>
      </div>

      <!-- Botón siguiente -->
      <button
        @click="next"
        class="w-10 h-10 flex items-center justify-center rounded-full text-white transition-all duration-300 transform hover:scale-110 shadow-lg"
        :class="getBtnColor(currentIndex)"
      >›</button>
    </div>

    <!-- Indicadores -->
    <div class="flex justify-center mt-4 space-x-2">
      <button
        v-for="(esp, i) in especies"
        :key="i"
        @click="currentIndex = i"
        class="w-3 h-3 rounded-full transition-all duration-300"
        :class="i === currentIndex ? getDot(i) + ' scale-125' : 'bg-gray-300'"
      ></button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
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

const emit = defineEmits(['update:modelValue'])
const currentIndex = ref(0)

const colors = [
  'from-blue-500 via-indigo-500 to-purple-600',
  'from-pink-400 via-red-500 to-rose-600',
  'from-orange-400 via-yellow-500 to-amber-600',
  'from-green-500 via-emerald-500 to-teal-600',
  'from-lime-400 via-green-500 to-emerald-600',
  'from-cyan-400 via-blue-500 to-indigo-600',
  'from-teal-400 via-green-500 to-emerald-600'
]

const getColor = i => `bg-gradient-to-br ${colors[i] || colors[0]}`
const getDot = i => `bg-gradient-to-r ${colors[i] || colors[0]}`
const getBtnColor = i => `bg-gradient-to-r ${colors[i] || colors[0]} hover:brightness-110`

const getPositionClass = (i) => {
  const diff = i - currentIndex.value
  
  if (diff === 0) {
    return 'scale-125 opacity-100 ring-4 ring-white ring-opacity-50 shadow-2xl z-20' // Centro - resaltado
  } else if (Math.abs(diff) === 1) {
    return 'scale-100 opacity-80 z-10' // Laterales - visibles
  } else {
    return 'scale-90 opacity-30 z-0' // Lejanos - opacados
  }
}

const getPositionStyle = (i) => {
  const diff = i - currentIndex.value
  let translateX = diff * 120 // Ajusta este valor para el espaciado
  
  // Si está muy lejos, ocultarlo completamente
  if (Math.abs(diff) > 2) {
    return {
      transform: `translateX(${translateX}px) scale(0.8)`,
      opacity: '0',
      pointerEvents: 'none'
    }
  }
  
  return {
    transform: `translateX(${translateX}px)`
  }
}

const select = (i) => {
  currentIndex.value = i
  toggle(props.especies[i].value)
}

const toggle = (val) => {
  const copy = [...props.modelValue]
  const i = copy.indexOf(val)
  if (i === -1) {
    copy.push(val)
  } else {
    copy.splice(i, 1)
  }
  emit('update:modelValue', copy)
}

const prev = () => {
  currentIndex.value = (currentIndex.value - 1 + props.especies.length) % props.especies.length
}

const next = () => {
  currentIndex.value = (currentIndex.value + 1) % props.especies.length
}
</script>

<style scoped>
/* Transiciones suaves */
div[class*='flex-shrink-0'] {
  transition: all 0.5s ease;
}
</style>

<template>
  <div>
    <div class="flex items-center justify-center gap-4">
      <!-- Botón anterior -->
      <button
        type="button"
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
            class="absolute flex-shrink-0 w-24 h-24 flex flex-col items-center justify-center rounded-2xl cursor-pointer transition-all duration-500 mx-2 border-2"
            :class="[
              isSelected(esp.value)
                ? getColor(i) + ' ring-4 ring-offset-2 ring-white shadow-2xl scale-125'
                : 'bg-gray-200 text-gray-500 hover:brightness-90',
              getPositionClass(i)
            ]"
            :style="getPositionStyle(i)"
            @click="toggleSelection(esp.value)"
          >
            <font-awesome-icon
              :icon="esp.icon"
              class="text-3xl mb-1 drop-shadow-md"
              :class="isSelected(esp.value) ? 'text-white' : 'text-gray-500'"
            />
            <span
              class="font-semibold text-xs text-center drop-shadow-md"
              :class="isSelected(esp.value) ? 'text-white' : 'text-gray-600'"
            >
              {{ esp.label }}
            </span>
          </div>
        </div>
      </div>

      <!-- Botón siguiente -->
      <button
        type="button"
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
        @click="goToIndex(i)"
        class="w-3 h-3 rounded-full transition-all duration-300"
        :class="[
          i === currentIndex ? getDot(i) + ' scale-125' : 'bg-gray-300',
          isSelected(esp.value) ? getDot(i) + ' ring-2 ring-white' : ''
        ]"
      ></button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: { 
    type: Array, 
    default: () => [] 
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
  if (diff === 0) return 'opacity-100 z-20'
  else if (Math.abs(diff) === 1) return 'opacity-90 z-10'
  else return 'opacity-40 z-0'
}

const getPositionStyle = (i) => {
  const diff = i - currentIndex.value
  let translateX = diff * 120
  if (Math.abs(diff) > 2) {
    return {
      transform: `translateX(${translateX}px) scale(0.8)`,
      opacity: '0',
      pointerEvents: 'none'
    }
  }
  return { transform: `translateX(${translateX}px)` }
}

// Navegación independiente
const goToIndex = (index) => {
  currentIndex.value = index
}

const prev = () => {
  currentIndex.value = (currentIndex.value - 1 + props.especies.length) % props.especies.length
}

const next = () => {
  currentIndex.value = (currentIndex.value + 1) % props.especies.length
}

// Selección controlada - solo emite cambios explícitos
const toggleSelection = (especieValue) => {
  const copy = [...props.modelValue]
  const index = copy.indexOf(especieValue)
  
  if (index === -1) {
    copy.push(especieValue)
  } else {
    copy.splice(index, 1)
  }
  
  // Emitir el nuevo array de selecciones
  emit('update:modelValue', copy)
}

const isSelected = (especieValue) => props.modelValue.includes(especieValue)
</script>

<style scoped>
div[class*='flex-shrink-0'] {
  transition: all 0.5s ease;
}
</style>
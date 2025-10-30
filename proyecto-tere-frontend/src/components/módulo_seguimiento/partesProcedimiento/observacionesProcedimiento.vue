<template>
  <div>
    <div>
      <h4 class="font-semibold text-gray-700 mb-2 text-lg">Historial de Observaciones</h4>
      <div class="space-y-3">
        <div 
          v-for="(obs, index) in observaciones" 
          :key="index"
          class="border-l-4 border-blue-500 bg-gray-50 p-3 rounded-r-lg"
        >
          <p class="text-gray-800">{{ obs.texto }}</p>
          <p class="text-xs text-gray-500 mt-1">{{ obs.fecha }} - {{ obs.autor }}</p>
        </div>
      </div>
    </div>
    <div
      v-if="$route.query.from && decodeURIComponent($route.query.from).startsWith('/veterinarios')"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroCirugia"
        class="text-white bg-green-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-green-700 hover:scale-105 transition transform duration-200"
      >
        + Observación
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

// Verificar y mantener el parámetro 'from' si no está presente
if (!route.query.from && route.meta.originalFrom) {
  router.replace({
    name: route.name,
    params: route.params,
    query: { ...route.query, from: route.meta.originalFrom }
  })
}

const props = defineProps({
  procedimiento: {
    type: Object,
    required: true
  }
})

const nuevaObservacion = ref('')
const observaciones = ref([
  {
    texto: 'El paciente presentó buena recuperación postoperatoria.',
    fecha: '15/06/2025',
    autor: 'Dr. Juan Pérez'
  },
  {
    texto: 'Suturas retiradas sin complicaciones.',
    fecha: '20/06/2025',
    autor: 'Enf. María Gómez'
  }
])

const agregarObservacion = () => {
  if (nuevaObservacion.value.trim()) {
    observaciones.value.unshift({
      texto: nuevaObservacion.value,
      fecha: new Date().toLocaleDateString(),
      autor: 'Usuario Actual'
    })
    nuevaObservacion.value = ''
  }
}
</script>
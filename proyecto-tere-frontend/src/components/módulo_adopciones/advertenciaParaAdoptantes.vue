<template>
  <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full p-6 md:p-10 relative">
      <button @click="close" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800 text-2xl font-bold">×</button>
      <header class="mb-6">
        <h1 class="text-2xl md:text-3xl font-semibold">Advertencia y compromiso de adopción</h1>
        <p class="text-sm text-gray-600 mt-2">Antes de continuar con la adopción, confirme cada punto obligatorio.</p>
      </header>
      <main class="space-y-4">
        <ul class="space-y-3">
          <li v-for="(item, idx) in items" :key="item.id" class="flex items-start gap-3">
            <input type="checkbox" :id="item.id" v-model="item.checked" class="mt-1 h-5 w-5 rounded text-emerald-600 focus:ring-emerald-500" />
            <div>
              <label :for="item.id" class="font-medium text-gray-800">{{ item.title }}</label>
              <p class="text-sm text-gray-600">{{ item.description }}</p>
            </div>
          </li>
        </ul>
        <div class="pt-4 border-t flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div class="text-sm text-gray-600">
            <p>Requerido: <span class="font-semibold">todas</span> las casillas deben estar marcadas para continuar.</p>
            <p v-if="!allChecked" class="text-red-600">Por favor confirme todos los puntos.</p>
          </div>
          <div class="flex gap-2">
            <button @click="close" class="px-4 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50">Cancelar</button>
            <button :disabled="!allChecked" @click="continueAdoption" :class="['px-4 py-2 rounded-lg text-white font-medium', allChecked ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-emerald-300 cursor-not-allowed']">Continuar</button>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, defineEmits, defineExpose } from 'vue'

const emits = defineEmits(['close','continue'])
const visible = ref(false)

const items = reactive([
  { id: 'compromiso-cuidado', title: 'Me comprometo a brindarle cuidado, alimento y cariño de por vida.', description: 'Acepto que la mascota requiere atención diaria, alimentación adecuada y afecto.', checked: false },
  { id: 'vacunacion-esterilizacion', title: 'Aceptaré las vacunas y esterilización recomendadas por el veterinario.', description: 'Comprendo la importancia de la prevención sanitaria y la esterilización responsable.', checked: false },
  { id: 'visitas-veterinarias', title: 'Realizaré visitas veterinarias periódicas y seguiré tratamientos indicados.', description: 'En caso de enfermedad o dudas, acudiré a un profesional veterinario.', checked: false },
  { id: 'responsabilidad-legal', title: 'Cumpliré las normas legales y de convivencia (patente, registros, leyes locales).', description: 'Me responsabilizo por el cumplimiento de normativas locales y cuidados mínimos.', checked: false },
  { id: 'espacio-tiempo-recursos', title: 'Dispongo de espacio, tiempo y recursos para su bienestar.', description: 'Confirmo que puedo integrarlo a mi hogar y dedicarle atención diaria.', checked: false },
  { id: 'fotos-uso', title: 'Autorizo el uso de fotos en caso de seguimiento o difusión de adopción.', description: 'La organización podrá publicar fotos para seguimiento o promoción del programa.', checked: false },
  { id: 'no-regalo', title: 'No doy la mascota como regalo ni la abandono.', description: 'Comprendo que la mascota no debe entregarse como sorpresa o regalo sin planificación.', checked: false },
])

const allChecked = computed(() => items.every(i => i.checked))

function continueAdoption() {
  if(allChecked.value) {
    emits('continue')
    close()
  }
}

function open() { visible.value = true }
function close() { visible.value = false; emits('close') }

// Exponer las funciones al componente padre
defineExpose({ open, close, visible })
</script>

<style scoped>
/* Overlay styles */
</style>


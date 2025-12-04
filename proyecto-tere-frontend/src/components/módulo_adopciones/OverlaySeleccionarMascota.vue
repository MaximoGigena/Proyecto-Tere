<!-- components/OverlaySeleccionarMascota.vue -->
<template>
  <div 
    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
    @click.self="cerrar"
  >
    <div class="bg-white rounded-2xl w-full max-w-lg mx-4 p-6 shadow-xl">
      
      <!-- Título y botón cerrar -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h2 class="text-2xl font-bold text-gray-800">
            Seleccionar mascota
          </h2>
          <p class="text-gray-600 text-sm mt-1">
            Elige una mascota para crear una oferta de adopción
          </p>
        </div>
        <button 
          @click="cerrar"
          class="text-gray-500 hover:text-gray-700 hover:bg-gray-100 w-10 h-10 rounded-full flex items-center justify-center transition"
        >
          <span class="text-2xl">&times;</span>
        </button>
      </div>

      <!-- Lista de mascotas -->
      <div 
        v-if="mascotas.length > 0" 
        class="space-y-3 max-h-[400px] overflow-y-auto pr-2 pb-2"
      >
        <div
          v-for="m in mascotas"
          :key="m.id"
          @click="select(m)"
          class="flex items-center gap-4 p-4 rounded-xl cursor-pointer transition-all duration-200 border hover:shadow-md"
          :class="m.id === seleccion?.id 
            ? 'border-blue-500 bg-blue-50 shadow-md' 
            : 'border-gray-200 hover:bg-gray-50'"
        >
          <!-- Foto de la mascota -->
          <div class="relative">
            <img
              :src="m.foto"
              alt="foto mascota"
              class="w-16 h-16 object-cover rounded-xl"
              @error="handleImageError"
            />
            <div 
              v-if="m.id === seleccion?.id"
              class="absolute -top-1 -right-1 bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm"
            >
              ✓
            </div>
          </div>

          <!-- Información de la mascota -->
          <div class="flex flex-col flex-1">
            <div class="flex items-center justify-between">
              <span class="text-gray-900 font-bold text-lg">{{ m.nombre }}</span>
              <span class="text-blue-600 font-semibold text-sm">{{ m.especie }}</span>
            </div>
            
            <div class="grid grid-cols-2 gap-2 mt-2">
              <div class="flex items-center gap-1">
                <span class="text-gray-500 text-sm">Raza:</span>
                <span class="text-gray-800 text-sm font-medium">{{ m.raza || 'No especificada' }}</span>
              </div>
              <div class="flex items-center gap-1">
                <span class="text-gray-500 text-sm">Edad:</span>
                <span class="text-gray-800 text-sm font-medium">{{ m.edad || '?' }} años</span>
              </div>
            </div>
            
            <div v-if="m.descripcion" class="mt-1">
              <p class="text-gray-600 text-sm line-clamp-2">{{ m.descripcion }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Mensaje si no hay mascotas disponibles -->
      <div 
        v-else 
        class="text-center py-10 text-gray-500"
      >
        <div class="mb-4">
          <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
          </svg>
        </div>
        <p class="text-lg mb-2">No tenés mascotas disponibles</p>
        <p class="text-sm">Registrá una mascota primero para poder ponerla en adopción.</p>
        <button
          @click="cerrar"
          class="mt-4 text-blue-600 hover:text-blue-800 font-medium"
        >
          Volver
        </button>
      </div>

      <!-- Botones de acción -->
      <div v-if="mascotas.length > 0" class="mt-6 pt-4 border-t border-gray-200">
        <div class="flex gap-3">
          <button
            @click="cerrar"
            class="flex-1 bg-gray-200 text-gray-800 py-3 rounded-xl font-semibold hover:bg-gray-300 transition"
          >
            Cancelar
          </button>
          <button
            class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-blue-600"
            :disabled="!seleccion"
            @click="confirmar"
          >
            <div class="flex items-center justify-center gap-2">
              <span>Continuar con</span>
              <span v-if="seleccion" class="font-bold">{{ seleccion.nombre }}</span>
              <svg v-if="seleccion" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </div>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  mascotas: {
    type: Array,
    required: true,
    default: () => []
  }
})

const emit = defineEmits(['close', 'select'])
const seleccion = ref(null)

// Manejar error en carga de imagen
function handleImageError(event) {
  event.target.src = '/placeholder-mascota.jpg'
}

function select(mascota) {
  seleccion.value = mascota
}

function confirmar() {
  if (!seleccion.value) return
  emit('select', seleccion.value)
}

function cerrar() {
  emit('close')
}
</script>

<style scoped>
.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;

  /* Propiedad estándar */
  line-clamp: 2;
}


/* Scrollbar personalizada */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
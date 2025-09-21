<template>
  <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
    Gestión de Tipos de Cirugías
  </h2>

  <div class="max-w-4xl mx-auto p-6">

    <!-- Contenedor blanco que envuelve las tarjetas/listas -->
    <div class="border p-4 rounded-lg shadow bg-white max-w-8xl mx-auto">
      <div v-if="tiposCirugias.length" class="flex flex-wrap -m-2">
        <div v-for="tipo in tiposCirugias" :key="tipo.id" class="w-1/3 p-2">
          <div class="p-6 border rounded-2xl bg-white shadow-sm hover:shadow-md transition flex flex-col justify-between h-full">
            <!-- Contenido de la tarjeta -->
            <div>
              <div class="flex justify-between items-start">
                <h3 class="text-lg font-semibold text-gray-800">{{ tipo.nombre }}</h3>
                <span class="text-xs text-gray-400">#{{ tipo.id }}</span>
              </div>
              <p class="text-sm text-gray-600 mt-1">{{ tipo.descripcion }}</p>
            </div>

            <div class="mt-3 flex justify-between items-center">
              <span
                class="text-xs px-2 py-1 rounded-full font-medium"
                :class="tipo.activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
              >
                {{ tipo.activo ? 'Activo' : 'Inactivo' }}
              </span>

              <div class="flex gap-2 text-gray-500">
                <button @click="editarTipo(tipo)" class="hover:text-blue-600 transition"> <Edit class="w-5 h-5" /> </button> 
                <button @click="eliminarTipo(tipo)" class="hover:text-red-600 transition"> <Trash2 class="w-5 h-5" /> </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Botón flotante -->
    <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50">
      <button 
        @click="abrirRegistro()"
        class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-full shadow-lg hover:bg-blue-700 transition"
      >
        + Nuevo Tipo
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Edit, Trash2 } from 'lucide-vue-next'
import { useRouter } from 'vue-router'

const router = useRouter()


const tiposCirugias = ref([
  { id: 1, nombre: "Esterilización", descripcion: "Cirugía de esterilización (castración)", activo: true },
  { id: 2, nombre: "Cesárea", descripcion: "Cirugía para parto asistido", activo: true },
  { id: 3, nombre: "Tumores", descripcion: "Extracción de tumores o masas", activo: false },
  { id: 4, nombre: "Ortopédica", descripcion: "Cirugía de huesos y articulaciones", activo: true }
])

const mostrarModal = ref(false)
const editando = ref(null)
const form = ref({ id: null, nombre: "", descripcion: "", activo: true })

const abrirRegistro = () => {
  router.push('/registro/registroTipoCirugia')
}

const editarTipo = tipo => {
  editando.value = tipo.id
  form.value = { ...tipo }
  mostrarModal.value = true
}

const eliminarTipo = tipo => {
  if (confirm(`¿Eliminar el tipo de cirugía "${tipo.nombre}"?`)) {
    tiposCirugias.value = tiposCirugias.value.filter(t => t.id !== tipo.id)
  }
}


const cerrarModal = () => (mostrarModal.value = false)
</script>

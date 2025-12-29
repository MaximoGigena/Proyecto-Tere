<!-- SeleccionarDiagnostico.vue -->
<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden">
      <!-- Header del modal -->
      <div class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center">
        <h2 class="text-2xl font-bold">Seleccionar Diagnósticos</h2>
        <button 
          @click="$emit('cerrar')"
          class="text-white hover:text-gray-200 text-2xl"
        >
          &times;
        </button>
      </div>

      <!-- Contenido del modal -->
      <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
          <div class="flex">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="tabActiva = tab.id"
              :class="[
                'px-6 py-3 text-sm md:text-base font-medium transition-colors duration-200',
                tabActiva === tab.id
                  ? 'text-blue-600 border-b-2 border-blue-600'
                  : 'text-gray-500 hover:text-blue-500'
              ]"
            >
              {{ tab.titulo }}
            </button>
          </div>
        </div>

        <!-- Pestaña 1: Diagnósticos seleccionados -->
        <div v-if="tabActiva === 'seleccionados'">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">
              Diagnósticos seleccionados ({{ diagnosticosSeleccionados.length }})
            </h3>
            <button
              @click="limpiarDiagnosticos"
              v-if="diagnosticosSeleccionados.length > 0"
              class="flex items-center gap-2 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors font-semibold"
            >
              <span>🗑️</span> Limpiar todos
            </button>
          </div>

          <!-- Diagnósticos seleccionados -->
          <div v-if="diagnosticosSeleccionados.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div
              v-for="diagnostico in diagnosticosSeleccionados"
              :key="diagnostico.id"
              :class="[
                'rounded-lg border-l-4 shadow-md hover:shadow-lg transition-shadow duration-200 bg-white',
                diagnostico.prioridad === 'alta' ? 'border-red-500' :
                diagnostico.prioridad === 'baja' ? 'border-green-500' : 'border-blue-500'
              ]"
            >
              <div class="p-4 bg-blue-50 flex justify-between items-center">
                <h4 class="font-bold text-gray-800">{{ diagnostico.nombre }}</h4>
                <button
                  @click="eliminarDiagnostico(diagnostico.id)"
                  class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-600 rounded-full hover:bg-red-500 hover:text-white transition-colors font-bold"
                  title="Eliminar diagnóstico"
                >
                  ✕
                </button>
              </div>
              <div class="p-4">
                <p class="text-gray-600 mb-4">{{ diagnostico.descripcion }}</p>
                <div class="flex flex-wrap gap-2 text-sm">
                  <span
                    :class="[
                      'px-3 py-1 rounded-full font-semibold',
                      diagnostico.prioridad === 'alta' ? 'bg-red-100 text-red-800' :
                      diagnostico.prioridad === 'media' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-green-100 text-green-800'
                    ]"
                  >
                    {{ diagnostico.prioridad === 'alta' ? 'Urgente' : 
                       diagnostico.prioridad === 'media' ? 'Moderado' : 'Leve' }}
                  </span>
                  <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-semibold">
                    {{ diagnostico.categoria }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Mensaje si no hay diagnósticos seleccionados -->
          <div v-else class="text-center py-12">
            <div class="text-5xl mb-4">🔍</div>
            <h4 class="text-xl font-semibold text-gray-700 mb-2">No hay diagnósticos seleccionados</h4>
            <p class="text-gray-500">Agrega diagnósticos desde el catálogo</p>
          </div>
        </div>

        <!-- Pestaña 2: Catálogo de diagnósticos -->
        <div v-if="tabActiva === 'catalogo'">
          <div class="mb-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Catálogo de diagnósticos</h3>
            <div class="flex flex-col md:flex-row gap-4 mb-6">
              <div class="relative flex-grow">
                <input
                  type="text"
                  v-model="filtroBusqueda"
                  placeholder="Buscar diagnóstico..."
                  class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">🔍</span>
              </div>
              <select
                v-model="filtroCategoria"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Todas las categorías</option>
                <option value="dermatologia">Dermatología</option>
                <option value="gastrointestinal">Gastrointestinal</option>
                <option value="respiratorio">Respiratorio</option>
                <option value="oftalmologia">Oftalmología</option>
                <option value="traumatologia">Traumatología</option>
                <option value="infeccioso">Infeccioso</option>
                <option value="parasitario">Parasitario</option>
              </select>
            </div>
          </div>

          <!-- Lista de diagnósticos disponibles -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div
              v-for="diagnostico in diagnosticosFiltrados"
              :key="diagnostico.id"
              class="border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow"
            >
              <div class="p-4 bg-blue-50 flex justify-between items-center">
                <h4 class="font-bold text-gray-800">{{ diagnostico.nombre }}</h4>
                <button
                  @click="agregarDiagnostico(diagnostico)"
                  :disabled="diagnosticoYaAgregado(diagnostico.id)"
                  :class="[
                    'px-4 py-2 rounded-lg font-semibold transition-colors',
                    diagnosticoYaAgregado(diagnostico.id)
                      ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                      : 'bg-blue-500 text-white hover:bg-blue-600'
                  ]"
                >
                  {{ diagnosticoYaAgregado(diagnostico.id) ? 'Agregado' : '+ Agregar' }}
                </button>
              </div>
              <div class="p-4">
                <p class="text-gray-600 mb-4">{{ diagnostico.descripcion }}</p>
                <div class="flex flex-wrap gap-2 mb-3">
                  <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                    {{ diagnostico.categoria }}
                  </span>
                  <span
                    :class="[
                      'px-3 py-1 rounded-full text-sm font-semibold',
                      diagnostico.prioridad === 'alta' ? 'bg-red-100 text-red-800' :
                      diagnostico.prioridad === 'media' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-green-100 text-green-800'
                    ]"
                  >
                    {{ diagnostico.prioridad === 'alta' ? 'Urgente' : 
                       diagnostico.prioridad === 'media' ? 'Moderado' : 'Leve' }}
                  </span>
                </div>
                <div class="pt-3 border-t border-gray-100">
                  <strong class="text-gray-700">Síntomas comunes:</strong>
                  <span class="text-gray-600 ml-2">{{ diagnostico.sintomas }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Mensaje si no hay resultados -->
          <div v-if="diagnosticosFiltrados.length === 0" class="text-center py-12">
            <div class="text-5xl mb-4">🔍</div>
            <h4 class="text-xl font-semibold text-gray-700 mb-2">No se encontraron diagnósticos</h4>
            <p class="text-gray-500">Intenta con otros términos de búsqueda o categorías</p>
          </div>
        </div>
      </div>

      <!-- Footer del modal -->
      <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end gap-4">
        <button
          @click="$emit('cerrar')"
          class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors font-semibold"
        >
          Cancelar
        </button>
        <button
          @click="guardarYSalir"
          class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-semibold"
        >
          Guardar Diagnósticos
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, defineProps, defineEmits } from 'vue'

const props = defineProps({
  diagnosticosIniciales: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['cerrar', 'guardar'])

// Estado interno
const tabActiva = ref('seleccionados')
const filtroBusqueda = ref('')
const filtroCategoria = ref('')
const diagnosticosSeleccionados = ref([...props.diagnosticosIniciales])

const tabs = [
  { id: 'seleccionados', titulo: 'Diagnósticos seleccionados' },
  { id: 'catalogo', titulo: 'Catálogo de diagnósticos' }
]

const catalogoDiagnosticos = [
  {
    id: 101,
    nombre: 'Dermatitis alérgica',
    descripcion: 'Inflamación de la piel debido a reacción alérgica a alimentos o alérgenos ambientales.',
    prioridad: 'media',
    categoria: 'dermatologia',
    sintomas: 'Picazón, enrojecimiento, pérdida de pelo'
  },
  {
    id: 102,
    nombre: 'Otitis externa',
    descripcion: 'Infección del canal auditivo externo, común en perros con orejas caídas.',
    prioridad: 'media',
    categoria: 'dermatologia',
    sintomas: 'Sacudida de cabeza, mal olor, enrojecimiento'
  },
  {
    id: 105,
    nombre: 'Gastroenteritis aguda',
    descripcion: 'Inflamación del tracto gastrointestinal con vómitos y diarrea.',
    prioridad: 'alta',
    categoria: 'gastrointestinal',
    sintomas: 'Vómitos, diarrea, letargo, deshidratación'
  },
  {
    id: 106,
    nombre: 'Fractura de fémur',
    descripcion: 'Ruptura del hueso del fémur, generalmente por trauma.',
    prioridad: 'alta',
    categoria: 'traumatologia',
    sintomas: 'Cojeera severa, dolor, hinchazón'
  }
]

// Computed
const diagnosticosFiltrados = computed(() => {
  let filtrados = catalogoDiagnosticos
  
  if (filtroBusqueda.value) {
    const busqueda = filtroBusqueda.value.toLowerCase()
    filtrados = filtrados.filter(d => 
      d.nombre.toLowerCase().includes(busqueda) || 
      d.descripcion.toLowerCase().includes(busqueda) ||
      d.sintomas.toLowerCase().includes(busqueda)
    )
  }
  
  if (filtroCategoria.value) {
    filtrados = filtrados.filter(d => d.categoria === filtroCategoria.value)
  }
  
  return filtrados
})

// Métodos
const agregarDiagnostico = (diagnostico) => {
  if (diagnosticoYaAgregado(diagnostico.id)) return
  
  diagnosticosSeleccionados.value.push({
    ...diagnostico,
    fecha: new Date().toLocaleDateString('es-ES')
  })
}

const eliminarDiagnostico = (id) => {
  const index = diagnosticosSeleccionados.value.findIndex(d => d.id === id)
  if (index !== -1) {
    diagnosticosSeleccionados.value.splice(index, 1)
  }
}

const diagnosticoYaAgregado = (id) => {
  return diagnosticosSeleccionados.value.some(d => d.id === id)
}

const limpiarDiagnosticos = () => {
  if (confirm('¿Estás seguro de que deseas eliminar todos los diagnósticos seleccionados?')) {
    diagnosticosSeleccionados.value = []
  }
}

const guardarYSalir = () => {
  emit('guardar', diagnosticosSeleccionados.value)
  emit('cerrar')
}
</script>

<style scoped>
.animate-slide-in {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
</style>
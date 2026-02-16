<template>
  <div class="metricas-mascotas-container p-6 bg-gray-50 min-h-screen">
    <!-- Encabezado -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-800 mb-2">Sistema de Seguimiento Médico de Mascotas</h1>
      <p class="text-gray-600">Monitoreo de métricas de salud para todas las mascotas</p>
    </div>

    <!-- Filtros y selección de mascota -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
      <div class="flex flex-wrap gap-4 mb-6">
        <!-- Selección de mascota -->
        <div class="w-full md:w-auto">
          <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Mascota</label>
          <select 
            v-model="mascotaSeleccionadaId"
            @change="cargarMetricasDeMascota"
            class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
          >
            <option value="">Todas las mascotas</option>
            <option 
              v-for="mascota in mascotas" 
              :key="mascota.id" 
              :value="mascota.id"
            >
              {{ mascota.nombre }} ({{ mascota.tipo }})
            </option>
          </select>
        </div>

        <!-- Filtro por fecha -->
        <div class="w-full md:w-auto">
          <label class="block text-sm font-medium text-gray-700 mb-2">Rango de Fechas</label>
          <div class="flex gap-2">
            <input 
              type="date" 
              v-model="fechaInicio"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
            <span class="self-center text-gray-500">a</span>
            <input 
              type="date" 
              v-model="fechaFin"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
          </div>
        </div>

        <!-- Botón para registrar nueva métrica -->
        <div class="self-end">
          <button 
            @click="mostrarModalRegistro = true"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Métrica
          </button>
        </div>
      </div>

      <!-- Resumen estadístico -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
          <div class="text-blue-600 font-semibold">Peso Promedio</div>
          <div class="text-2xl font-bold text-blue-700">{{ estadisticas.pesoPromedio }} kg</div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg border border-green-100">
          <div class="text-green-600 font-semibold">Temperatura Promedio</div>
          <div class="text-2xl font-bold text-green-700">{{ estadisticas.temperaturaPromedio }}°C</div>
        </div>
        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
          <div class="text-yellow-600 font-semibold">Frecuencia Cardíaca</div>
          <div class="text-2xl font-bold text-yellow-700">{{ estadisticas.frecuenciaCardiacaPromedio }} lpm</div>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
          <div class="text-purple-600 font-semibold">Último Registro</div>
          <div class="text-2xl font-bold text-purple-700">{{ estadisticas.ultimoRegistro }}</div>
        </div>
      </div>
    </div>

    <!-- Gráfico de métricas -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
      <h2 class="text-xl font-semibold text-gray-800 mb-4">Evolución de Métricas</h2>
      <div class="h-64 flex items-center justify-center border border-gray-200 rounded-lg">
        <!-- Aquí iría la implementación del gráfico con Chart.js o similar -->
        <div class="text-center text-gray-500">
          <svg class="w-16 h-16 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          <p>Gráfico de evolución de métricas</p>
        </div>
      </div>
    </div>

    <!-- Tabla de métricas -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800">Historial de Métricas</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mascota</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peso (kg)</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Temperatura (°C)</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frec. Cardíaca</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observaciones</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="metrica in metricasFiltradas" 
              :key="metrica.id"
              class="hover:bg-gray-50 transition"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatFecha(metrica.fecha) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                      <span class="text-blue-600 font-semibold">
                        {{ metrica.mascotaNombre.charAt(0) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ metrica.mascotaNombre }}</div>
                    <div class="text-sm text-gray-500">{{ metrica.mascotaTipo }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="getClasePeso(metrica.peso)">
                  {{ metrica.peso }} kg
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="getClaseTemperatura(metrica.temperatura)">
                  {{ metrica.temperatura }}°C
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ metrica.frecuenciaCardiaca }} lpm
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                {{ metrica.observaciones || 'Sin observaciones' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button 
                  @click="editarMetrica(metrica)"
                  class="text-blue-600 hover:text-blue-900 mr-3"
                >
                  Editar
                </button>
                <button 
                  @click="eliminarMetrica(metrica.id)"
                  class="text-red-600 hover:text-red-900"
                >
                  Eliminar
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal para registrar/editar métricas -->
    <div v-if="mostrarModalRegistro" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">
            {{ metricaEditando ? 'Editar Métrica' : 'Registrar Nueva Métrica' }}
          </h3>
          <button @click="cerrarModal" class="text-gray-400 hover:text-gray-500">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="guardarMetrica" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Mascota</label>
            <select 
              v-model="nuevaMetrica.mascotaId"
              required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Seleccionar mascota</option>
              <option 
                v-for="mascota in mascotas" 
                :key="mascota.id" 
                :value="mascota.id"
              >
                {{ mascota.nombre }} ({{ mascota.tipo }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Peso (kg)</label>
              <input 
                type="number" 
                step="0.1"
                v-model="nuevaMetrica.peso"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Temperatura (°C)</label>
              <input 
                type="number" 
                step="0.1"
                v-model="nuevaMetrica.temperatura"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              >
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Frecuencia Cardíaca (lpm)</label>
            <input 
              type="number"
              v-model="nuevaMetrica.frecuenciaCardiaca"
              required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Observaciones</label>
            <textarea 
              v-model="nuevaMetrica.observaciones"
              rows="3"
              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            ></textarea>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button 
              type="button"
              @click="cerrarModal"
              class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button 
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
              {{ metricaEditando ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

// Tipos de datos
interface Mascota {
  id: string
  nombre: string
  tipo: string
  raza?: string
  edad?: number
}

interface Metrica {
  id: string
  mascotaId: string
  mascotaNombre: string
  mascotaTipo: string
  fecha: Date
  peso: number
  temperatura: number
  frecuenciaCardiaca: number
  observaciones?: string
}

// Estado del componente
const mascotas = ref<Mascota[]>([
  { id: '1', nombre: 'Max', tipo: 'Perro', raza: 'Golden Retriever', edad: 3 },
  { id: '2', nombre: 'Luna', tipo: 'Gato', raza: 'Siamés', edad: 2 },
  { id: '3', nombre: 'Rocky', tipo: 'Perro', raza: 'Bulldog', edad: 5 },
  { id: '4', nombre: 'Mimi', tipo: 'Gato', raza: 'Persa', edad: 4 }
])

const metricas = ref<Metrica[]>([
  { 
    id: '1', 
    mascotaId: '1', 
    mascotaNombre: 'Max', 
    mascotaTipo: 'Perro',
    fecha: new Date('2024-01-15'), 
    peso: 25.5, 
    temperatura: 38.5, 
    frecuenciaCardiaca: 90,
    observaciones: 'Peso estable, buen estado general'
  },
  { 
    id: '2', 
    mascotaId: '2', 
    mascotaNombre: 'Luna', 
    mascotaTipo: 'Gato',
    fecha: new Date('2024-01-14'), 
    peso: 4.2, 
    temperatura: 38.8, 
    frecuenciaCardiaca: 140,
    observaciones: 'Control rutinario'
  }
])

const mascotaSeleccionadaId = ref<string>('')
const fechaInicio = ref<string>('')
const fechaFin = ref<string>('')
const mostrarModalRegistro = ref<boolean>(false)
const metricaEditando = ref<Metrica | null>(null)

const nuevaMetrica = ref({
  mascotaId: '',
  peso: 0,
  temperatura: 0,
  frecuenciaCardiaca: 0,
  observaciones: ''
})

// Métodos computados
const metricasFiltradas = computed(() => {
  let filtradas = [...metricas.value]

  // Filtrar por mascota
  if (mascotaSeleccionadaId.value) {
    filtradas = filtradas.filter(m => m.mascotaId === mascotaSeleccionadaId.value)
  }

  // Filtrar por fecha
  if (fechaInicio.value) {
    const fechaInicioDate = new Date(fechaInicio.value)
    filtradas = filtradas.filter(m => new Date(m.fecha) >= fechaInicioDate)
  }

  if (fechaFin.value) {
    const fechaFinDate = new Date(fechaFin.value)
    filtradas = filtradas.filter(m => new Date(m.fecha) <= fechaFinDate)
  }

  // Ordenar por fecha (más reciente primero)
  return filtradas.sort((a, b) => new Date(b.fecha).getTime() - new Date(a.fecha).getTime())
})

const estadisticas = computed(() => {
  const metricasFiltradasArray = metricasFiltradas.value
  
  if (metricasFiltradasArray.length === 0) {
    return {
      pesoPromedio: '0.0',
      temperaturaPromedio: '0.0',
      frecuenciaCardiacaPromedio: '0',
      ultimoRegistro: 'N/A'
    }
  }

  const sumaPeso = metricasFiltradasArray.reduce((sum, m) => sum + m.peso, 0)
  const sumaTemp = metricasFiltradasArray.reduce((sum, m) => sum + m.temperatura, 0)
  const sumaFC = metricasFiltradasArray.reduce((sum, m) => sum + m.frecuenciaCardiaca, 0)

  const ultimoRegistro = metricasFiltradasArray.length > 0 
    ? formatFecha(metricasFiltradasArray[0].fecha)
    : 'N/A'

  return {
    pesoPromedio: (sumaPeso / metricasFiltradasArray.length).toFixed(1),
    temperaturaPromedio: (sumaTemp / metricasFiltradasArray.length).toFixed(1),
    frecuenciaCardiacaPromedio: Math.round(sumaFC / metricasFiltradasArray.length).toString(),
    ultimoRegistro
  }
})

// Métodos
const cargarMetricasDeMascota = () => {
  // Aquí se cargarían las métricas de la API según la mascota seleccionada
  console.log('Cargando métricas para mascota:', mascotaSeleccionadaId.value)
}

const editarMetrica = (metrica: Metrica) => {
  metricaEditando.value = metrica
  nuevaMetrica.value = {
    mascotaId: metrica.mascotaId,
    peso: metrica.peso,
    temperatura: metrica.temperatura,
    frecuenciaCardiaca: metrica.frecuenciaCardiaca,
    observaciones: metrica.observaciones || ''
  }
  mostrarModalRegistro.value = true
}

const guardarMetrica = () => {
  if (metricaEditando.value) {
    // Actualizar métrica existente
    const index = metricas.value.findIndex(m => m.id === metricaEditando.value?.id)
    if (index !== -1) {
      const mascota = mascotas.value.find(m => m.id === nuevaMetrica.value.mascotaId)
      metricas.value[index] = {
        ...metricas.value[index],
        ...nuevaMetrica.value,
        mascotaNombre: mascota?.nombre || '',
        mascotaTipo: mascota?.tipo || '',
        fecha: new Date()
      }
    }
  } else {
    // Crear nueva métrica
    const mascota = mascotas.value.find(m => m.id === nuevaMetrica.value.mascotaId)
    const nueva: Metrica = {
      id: Date.now().toString(),
      mascotaId: nuevaMetrica.value.mascotaId,
      mascotaNombre: mascota?.nombre || '',
      mascotaTipo: mascota?.tipo || '',
      fecha: new Date(),
      peso: nuevaMetrica.value.peso,
      temperatura: nuevaMetrica.value.temperatura,
      frecuenciaCardiaca: nuevaMetrica.value.frecuenciaCardiaca,
      observaciones: nuevaMetrica.value.observaciones
    }
    metricas.value.unshift(nueva)
  }

  cerrarModal()
}

const eliminarMetrica = (id: string) => {
  if (confirm('¿Está seguro de eliminar esta métrica?')) {
    metricas.value = metricas.value.filter(m => m.id !== id)
  }
}

const cerrarModal = () => {
  mostrarModalRegistro.value = false
  metricaEditando.value = null
  nuevaMetrica.value = {
    mascotaId: '',
    peso: 0,
    temperatura: 0,
    frecuenciaCardiaca: 0,
    observaciones: ''
  }
}

const formatFecha = (fecha: Date): string => {
  return new Date(fecha).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const getClasePeso = (peso: number): string => {
  if (peso < 10) return 'bg-yellow-100 text-yellow-800'
  if (peso > 30) return 'bg-red-100 text-red-800'
  return 'bg-green-100 text-green-800'
}

const getClaseTemperatura = (temp: number): string => {
  if (temp < 38 || temp > 39.5) return 'bg-red-100 text-red-800'
  return 'bg-green-100 text-green-800'
}

// Inicialización
onMounted(() => {
  // Establecer fechas por defecto (último mes)
  const hoy = new Date()
  const haceUnMes = new Date()
  haceUnMes.setMonth(hoy.getMonth() - 1)
  
  fechaInicio.value = haceUnMes.toISOString().split('T')[0]
  fechaFin.value = hoy.toISOString().split('T')[0]
})
</script>

<style scoped>
.metricas-mascotas-container {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
}
</style>
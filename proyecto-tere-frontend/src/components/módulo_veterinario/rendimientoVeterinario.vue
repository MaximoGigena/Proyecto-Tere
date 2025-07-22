<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">RENDIMIENTO VETERINARIO</h1>
        <p class="text-sm text-gray-500 mt-1">Período: <span class="font-medium">01 Ene 2023 - 30 Jun 2023</span></p>
      </div>
      <div class="mt-4 md:mt-0 flex items-center space-x-3">
        <div class="relative">
          <select class="appearance-none bg-white border border-gray-300 rounded-md pl-3 pr-8 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option>Últimos 6 meses</option>
            <option>Últimos 3 meses</option>
            <option>Este año</option>
            <option>Personalizado</option>
          </select>
          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
          </div>
        </div>
        <button class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Exportar
        </button>
      </div>
    </div>

    <!-- Tarjetas de métricas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Pacientes atendidos -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Pacientes atendidos</p>
            <p class="mt-1 text-3xl font-semibold text-gray-900">247</p>
            <p class="mt-1 flex items-center text-sm text-green-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
              </svg>
              12.5% vs período anterior
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-blue-50 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Ingresos generados -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Ingresos generados</p>
            <p class="mt-1 text-3xl font-semibold text-gray-900">$184,950</p>
            <p class="mt-1 flex items-center text-sm text-green-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
              </svg>
              8.3% vs período anterior
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-green-50 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Procedimientos realizados -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Procedimientos</p>
            <p class="mt-1 text-3xl font-semibold text-gray-900">312</p>
            <p class="mt-1 flex items-center text-sm text-red-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
              </svg>
              3.2% vs período anterior
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-purple-50 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Satisfacción clientes -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Satisfacción</p>
            <p class="mt-1 text-3xl font-semibold text-gray-900">4.8<span class="text-xl text-gray-500">/5</span></p>
            <p class="mt-1 flex items-center text-sm text-green-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
              </svg>
              0.4% vs período anterior
            </p>
          </div>
          <div class="h-12 w-12 rounded-full bg-yellow-50 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráficos principales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Evolución mensual -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-medium text-gray-900">Evolución mensual</h2>
          <div class="flex space-x-2">
            <button class="px-3 py-1 text-xs font-medium rounded-md" :class="activeChart === 'pacientes' ? 'bg-blue-100 text-blue-800' : 'text-gray-500 hover:bg-gray-100'"
              @click="activeChart = 'pacientes'">
              Pacientes
            </button>
            <button class="px-3 py-1 text-xs font-medium rounded-md" :class="activeChart === 'ingresos' ? 'bg-blue-100 text-blue-800' : 'text-gray-500 hover:bg-gray-100'"
              @click="activeChart = 'ingresos'">
              Ingresos
            </button>
            <button class="px-3 py-1 text-xs font-medium rounded-md" :class="activeChart === 'procedimientos' ? 'bg-blue-100 text-blue-800' : 'text-gray-500 hover:bg-gray-100'"
              @click="activeChart = 'procedimientos'">
              Procedimientos
            </button>
          </div>
        </div>
        <div class="h-80">
          <canvas ref="evolutionChart" class="w-full h-full"></canvas>
        </div>
      </div>

      <!-- Distribución por tipo -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-6">Distribución por tipo</h2>
        <div class="flex flex-col md:flex-row">
          <div class="h-64 w-full md:w-1/2">
            <canvas ref="typeChart" class="w-full h-full"></canvas>
          </div>
          <div class="w-full md:w-1/2 mt-6 md:mt-0 md:pl-6">
            <div class="space-y-4">
              <div v-for="(item, index) in distributionData" :key="index" class="flex items-center">
                <div class="h-3 w-3 rounded-full mr-3" :style="{ backgroundColor: chartColors[index] }"></div>
                <div class="flex-1">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-700">{{ item.label }}</span>
                    <span class="font-medium text-gray-900">{{ item.value }}%</span>
                  </div>
                  <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full" :style="{ width: `${item.value}%`, backgroundColor: chartColors[index] }"></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-6 pt-6 border-t border-gray-100">
              <p class="text-sm text-gray-500">El veterinario tiene mayor enfoque en <span class="font-medium text-gray-700">consultas generales</span> y <span class="font-medium text-gray-700">vacunaciones</span>, representando el 68% de su actividad.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de últimos pacientes -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="text-lg font-medium text-gray-900">Últimos pacientes atendidos</h2>
        <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">Ver todos</button>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paciente</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Procedimiento</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valoración</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(patient, index) in lastPatients" :key="index" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ patient.date }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ patient.name }}</div>
                    <div class="text-sm text-gray-500">{{ patient.specie }}, {{ patient.age }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{
                  'bg-blue-100 text-blue-800': patient.type === 'Consulta',
                  'bg-green-100 text-green-800': patient.type === 'Vacunación',
                  'bg-purple-100 text-purple-800': patient.type === 'Cirugía'
                }">
                  {{ patient.type }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ patient.procedure }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{
                  'bg-green-100 text-green-800': patient.status === 'Completado',
                  'bg-yellow-100 text-yellow-800': patient.status === 'En tratamiento',
                  'bg-red-100 text-red-800': patient.status === 'Urgente'
                }">
                  {{ patient.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex items-center">
                    <svg v-for="star in 5" :key="star" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="{
                      'text-yellow-400': star <= patient.rating,
                      'text-gray-300': star > patient.rating
                    }" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  </div>
                  <span class="ml-1 text-xs text-gray-500">({{ patient.reviews }})</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Chart from 'chart.js/auto'

const activeChart = ref('pacientes')
const chartColors = ['#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EF4444']

const distributionData = [
  { label: 'Consultas generales', value: 42 },
  { label: 'Vacunaciones', value: 26 },
  { label: 'Cirugías', value: 15 },
  { label: 'Urgencias', value: 12 },
  { label: 'Otros', value: 5 }
]

const lastPatients = [
  { date: '28/06/2023', name: 'Rex', specie: 'Perro', age: '5 años', type: 'Consulta', procedure: 'Control postoperatorio', status: 'Completado', rating: 5, reviews: 12 },
  { date: '27/06/2023', name: 'Mishi', specie: 'Gato', age: '3 años', type: 'Vacunación', procedure: 'Vacuna antirrábica', status: 'Completado', rating: 4, reviews: 8 },
  { date: '26/06/2023', name: 'Lola', specie: 'Conejo', age: '2 años', type: 'Cirugía', procedure: 'Esterilización', status: 'En tratamiento', rating: 5, reviews: 5 },
  { date: '25/06/2023', name: 'Rocky', specie: 'Perro', age: '7 años', type: 'Consulta', procedure: 'Dermatitis alérgica', status: 'Urgente', rating: 3, reviews: 15 },
  { date: '24/06/2023', name: 'Nala', specie: 'Gato', age: '4 años', type: 'Consulta', procedure: 'Control anual', status: 'Completado', rating: 5, reviews: 20 }
]

let evolutionChart = ref(null)
let typeChart = ref(null)

onMounted(() => {
  // Gráfico de evolución
  const evolutionCtx = evolutionChart.value.getContext('2d')
  new Chart(evolutionCtx, {
    type: 'line',
    data: {
      labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
      datasets: [
        {
          label: 'Pacientes atendidos',
          data: [35, 42, 38, 45, 52, 48],
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59, 130, 246, 0.05)',
          tension: 0.3,
          fill: true
        },
        {
          label: 'Ingresos (en miles)',
          data: [25, 30, 28, 35, 40, 38],
          borderColor: '#10B981',
          backgroundColor: 'rgba(16, 185, 129, 0.05)',
          tension: 0.3,
          fill: true,
          hidden: true
        },
        {
          label: 'Procedimientos',
          data: [45, 50, 55, 60, 52, 50],
          borderColor: '#8B5CF6',
          backgroundColor: 'rgba(139, 92, 246, 0.05)',
          tension: 0.3,
          fill: true,
          hidden: true
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top'
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  })

  // Gráfico de distribución
  const typeCtx = typeChart.value.getContext('2d')
  new Chart(typeCtx, {
    type: 'doughnut',
    data: {
      labels: distributionData.map(item => item.label),
      datasets: [{
        data: distributionData.map(item => item.value),
        backgroundColor: chartColors,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          display: false
        }
      }
    }
  })
})
</script>

<style scoped>
/* Estilos personalizados si son necesarios */
</style>
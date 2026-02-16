<template>
  <div class="metricas-mascotas bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen p-4 md:p-6">
    <!-- Header con título y controles -->
    <div class="metricas-header mb-8">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">{{ titulo }}</h1>
          <p class="text-gray-600 mt-2">Monitorea la salud y actividad de tus mascotas</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3">
          <select 
            v-model="mascotaSeleccionada"
            @change="cambiarMascota"
            class="px-4 py-2.5 bg-white border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option v-for="mascota in mascotas" :key="mascota.id" :value="mascota.id">
              {{ mascota.nombre }} - {{ mascota.tipo }}
            </option>
          </select>
          
          <select 
            v-model="periodoSeleccionado"
            @change="cambiarPeriodo"
            class="px-4 py-2.5 bg-white border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="7d">Últimos 7 días</option>
            <option value="30d">Últimos 30 días</option>
            <option value="90d">Últimos 90 días</option>
            <option value="ytd">Este año</option>
          </select>
        </div>
      </div>
      
      <!-- Tarjeta de información de la mascota -->
      <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border border-blue-100">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
          <div class="relative">
            <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-lg">
              <img 
                :src="mascotaActual.foto" 
                :alt="mascotaActual.nombre"
                class="w-full h-full object-cover"
              />
            </div>
            <span 
              class="absolute -bottom-2 -right-2 px-3 py-1 rounded-full text-xs font-semibold text-white"
              :class="{
                'bg-green-500': mascotaActual.salud === 'Excelente',
                'bg-yellow-500': mascotaActual.salud === 'Buena',
                'bg-orange-500': mascotaActual.salud === 'Regular',
                'bg-red-500': mascotaActual.salud === 'Preocupante'
              }"
            >
              {{ mascotaActual.salud }}
            </span>
          </div>
          
          <div class="flex-1">
            <div class="flex flex-wrap items-center gap-4 mb-4">
              <h2 class="text-2xl font-bold text-gray-800">{{ mascotaActual.nombre }}</h2>
              <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                {{ mascotaActual.tipo }}
              </span>
              <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                {{ mascotaActual.raza }}
              </span>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div>
                <p class="text-sm text-gray-500">Edad</p>
                <p class="text-lg font-semibold text-gray-800">{{ mascotaActual.edad }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Peso</p>
                <p class="text-lg font-semibold text-gray-800">{{ mascotaActual.peso }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Último chequeo</p>
                <p class="text-lg font-semibold text-gray-800">{{ mascotaActual.ultimoChequeo }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Dueño</p>
                <p class="text-lg font-semibold text-gray-800">{{ mascotaActual.dueno }}</p>
              </div>
            </div>
          </div>
          
          <button 
            @click="generarReporte"
            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Reporte de Salud
          </button>
        </div>
      </div>
    </div>

    <!-- KPIs de Salud -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-2xl p-6 shadow-lg border border-green-100">
        <div class="flex items-center justify-between mb-4">
          <div>
            <p class="text-sm font-medium text-gray-500">Actividad Diaria</p>
            <p class="text-3xl font-bold text-gray-800">{{ kpis.actividad.valor }} min</p>
          </div>
          <div class="p-3 bg-green-50 rounded-xl">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
          </div>
        </div>
        <div class="flex items-center text-sm">
          <span :class="kpis.actividad.tendencia > 0 ? 'text-green-600' : 'text-red-600'">
            {{ kpis.actividad.tendencia > 0 ? '+' : '' }}{{ kpis.actividad.tendencia }}%
          </span>
          <span class="text-gray-400 ml-2">vs. semana pasada</span>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-lg border border-blue-100">
        <div class="flex items-center justify-between mb-4">
          <div>
            <p class="text-sm font-medium text-gray-500">Calorías Quemadas</p>
            <p class="text-3xl font-bold text-gray-800">{{ kpis.calorias.valor }} cal</p>
          </div>
          <div class="p-3 bg-blue-50 rounded-xl">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
            </svg>
          </div>
        </div>
        <div class="text-sm text-gray-400">Metabolismo activo</div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-lg border border-yellow-100">
        <div class="flex items-center justify-between mb-4">
          <div>
            <p class="text-sm font-medium text-gray-500">Peso Saludable</p>
            <p class="text-3xl font-bold text-gray-800">{{ kpis.peso.valor }} kg</p>
          </div>
          <div class="p-3 bg-yellow-50 rounded-xl">
            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
            </svg>
          </div>
        </div>
        <div class="flex items-center text-sm">
          <span class="text-green-600 font-medium">Dentro del rango ideal</span>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-lg border border-red-100">
        <div class="flex items-center justify-between mb-4">
          <div>
            <p class="text-sm font-medium text-gray-500">Sueño Promedio</p>
            <p class="text-3xl font-bold text-gray-800">{{ kpis.sueno.valor }} hrs</p>
          </div>
          <div class="p-3 bg-red-50 rounded-xl">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
          </div>
        </div>
        <div class="text-sm text-gray-400">Descanso adecuado</div>
      </div>
    </div>

    <!-- Gráficos principales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <!-- Gráfico de actividad -->
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-800">Actividad Diaria</h3>
          <div class="flex gap-2">
            <button 
              v-for="tipo in tiposActividad" 
              :key="tipo"
              @click="cambiarTipoActividad(tipo)"
              class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="{
                'bg-blue-100 text-blue-700': tipoActividadSeleccionada === tipo,
                'bg-gray-100 text-gray-700 hover:bg-gray-200': tipoActividadSeleccionada !== tipo
              }"
            >
              {{ tipo }}
            </button>
          </div>
        </div>
        <div class="h-80">
          <canvas ref="graficoActividad"></canvas>
        </div>
      </div>

      <!-- Gráfico de sueño -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Patrones de Sueño</h3>
        <div class="h-80">
          <canvas ref="graficoSueno"></canvas>
        </div>
      </div>
    </div>

    <!-- Métricas de Alimentación y Salud -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Alimentación -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-800">Registro de Alimentación</h3>
          <button 
            @click="abrirModal('alimentacion')"
            class="px-4 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg font-medium text-sm transition-colors"
          >
            + Agregar Comida
          </button>
        </div>
        
        <div class="space-y-4">
          <div 
            v-for="item in alimentacion" 
            :key="item.id"
            class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors"
          >
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-full flex items-center justify-center"
                :class="{
                  'bg-green-100': item.tipo === 'Comida',
                  'bg-blue-100': item.tipo === 'Agua',
                  'bg-yellow-100': item.tipo === 'Snack'
                }"
              >
                <svg class="w-5 h-5" 
                  :class="{
                    'text-green-600': item.tipo === 'Comida',
                    'text-blue-600': item.tipo === 'Agua',
                    'text-yellow-600': item.tipo === 'Snack'
                  }"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                  <path v-if="item.tipo === 'Comida'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                  <path v-if="item.tipo === 'Agua'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                  <path v-if="item.tipo === 'Snack'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <div>
                <p class="font-medium text-gray-800">{{ item.nombre }}</p>
                <p class="text-sm text-gray-500">{{ item.hora }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-semibold text-gray-800">{{ item.cantidad }}</p>
              <p class="text-xs text-gray-400">{{ item.calorias }} calorías</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recordatorios de Salud -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-800">Recordatorios de Salud</h3>
          <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
            {{ recordatoriosPendientes }} pendientes
          </span>
        </div>
        
        <div class="space-y-4">
          <div 
            v-for="recordatorio in recordatorios" 
            :key="recordatorio.id"
            class="p-4 border rounded-xl hover:shadow-md transition-all"
            :class="{
              'border-red-200 bg-red-50': recordatorio.prioridad === 'Alta',
              'border-yellow-200 bg-yellow-50': recordatorio.prioridad === 'Media',
              'border-blue-200 bg-blue-50': recordatorio.prioridad === 'Baja'
            }"
          >
            <div class="flex justify-between items-start mb-2">
              <div class="flex items-center gap-3">
                <div class="w-2 h-2 rounded-full"
                  :class="{
                    'bg-red-500': recordatorio.prioridad === 'Alta',
                    'bg-yellow-500': recordatorio.prioridad === 'Media',
                    'bg-blue-500': recordatorio.prioridad === 'Baja'
                  }"
                ></div>
                <p class="font-semibold text-gray-800">{{ recordatorio.titulo }}</p>
              </div>
              <span class="text-sm font-medium px-2 py-1 rounded"
                :class="{
                  'bg-red-100 text-red-800': recordatorio.prioridad === 'Alta',
                  'bg-yellow-100 text-yellow-800': recordatorio.prioridad === 'Media',
                  'bg-blue-100 text-blue-800': recordatorio.prioridad === 'Baja'
                }"
              >
                {{ recordatorio.prioridad }}
              </span>
            </div>
            <p class="text-sm text-gray-600 mb-3">{{ recordatorio.descripcion }}</p>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-500">{{ recordatorio.fecha }}</span>
              <button 
                @click="completarRecordatorio(recordatorio.id)"
                class="px-3 py-1 text-sm font-medium text-green-700 bg-green-100 hover:bg-green-200 rounded-lg transition-colors"
              >
                Completar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Historial de Veterinario -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
      <h3 class="text-xl font-bold text-gray-800 mb-6">Historial Veterinario</h3>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr class="bg-gray-50">
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo de Visita</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinario</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnóstico</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Próxima Visita</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="visita in historialVeterinario" 
              :key="visita.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ visita.fecha }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="{
                    'bg-green-100 text-green-800': visita.tipo === 'Rutina',
                    'bg-yellow-100 text-yellow-800': visita.tipo === 'Vacunación',
                    'bg-red-100 text-red-800': visita.tipo === 'Emergencia'
                  }"
                >
                  {{ visita.tipo }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ visita.veterinario }}</td>
              <td class="px-6 py-4 text-sm text-gray-800 max-w-xs truncate">{{ visita.diagnostico }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                :class="{
                  'text-green-600': visita.proximaVisita === 'En 6 meses',
                  'text-yellow-600': visita.proximaVisita === 'En 3 meses',
                  'text-red-600': visita.proximaVisita === 'Inmediata'
                }"
              >
                {{ visita.proximaVisita }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal para agregar alimentación -->
    <div v-if="modalAbierto" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 transition-opacity" @click="cerrarModal">
          <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
          <div class="bg-white px-6 pt-6 pb-4">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-xl font-bold text-gray-800">Agregar Comida</h3>
              <button @click="cerrarModal" class="text-gray-400 hover:text-gray-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="agregarAlimentacion">
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de comida</label>
                  <select v-model="nuevaComida.tipo" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="Comida">Comida Principal</option>
                    <option value="Snack">Snack</option>
                    <option value="Agua">Agua</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                  <input 
                    v-model="nuevaComida.nombre"
                    type="text" 
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Ej: Croquetas Premium"
                    required
                  />
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
                    <input 
                      v-model="nuevaComida.cantidad"
                      type="text" 
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                      placeholder="Ej: 150g"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Calorías</label>
                    <input 
                      v-model="nuevaComida.calorias"
                      type="number" 
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                      placeholder="Ej: 250"
                      required
                    />
                  </div>
                </div>
              </div>
              
              <div class="mt-8 flex gap-3">
                <button 
                  type="button"
                  @click="cerrarModal"
                  class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Cancelar
                </button>
                <button 
                  type="submit"
                  class="flex-1 px-4 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
                >
                  Guardar
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Chart, registerables } from 'chart.js';

export default {
  name: 'MetricasMascotas',
  
  props: {
    usuarioId: {
      type: String,
      required: true
    },
    titulo: {
      type: String,
      default: 'Métricas de Mascotas'
    }
  },

  data() {
    return {
      mascotaSeleccionada: '1',
      periodoSeleccionado: '30d',
      tipoActividadSeleccionada: 'Paseos',
      modalAbierto: false,
      modalTipo: '',
      
      mascotas: [
        {
          id: '1',
          nombre: 'Max',
          tipo: 'Perro',
          raza: 'Golden Retriever',
          edad: '3 años',
          peso: '28 kg',
          salud: 'Excelente',
          ultimoChequeo: 'Hace 2 meses',
          dueno: 'Juan Pérez',
          foto: 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=400&h=400&fit=crop'
        },
        {
          id: '2',
          nombre: 'Luna',
          tipo: 'Gato',
          raza: 'Siamés',
          edad: '2 años',
          peso: '4 kg',
          salud: 'Buena',
          ultimoChequeo: 'Hace 1 mes',
          dueno: 'María García',
          foto: 'https://images.unsplash.com/photo-1514888286974-6d03bdeacba8?w=400&h=400&fit=crop'
        }
      ],
      
      kpis: {
        actividad: { valor: 85, tendencia: 12.5 },
        calorias: { valor: 425, tendencia: 0 },
        peso: { valor: 28, tendencia: 0 },
        sueno: { valor: 14, tendencia: -2.3 }
      },
      
      tiposActividad: ['Paseos', 'Juego', 'Ejercicio', 'Total'],
      
      alimentacion: [
        { id: 1, tipo: 'Comida', nombre: 'Croquetas Premium', cantidad: '150g', calorias: 280, hora: '08:30 AM' },
        { id: 2, tipo: 'Agua', nombre: 'Agua fresca', cantidad: '500ml', calorias: 0, hora: '10:00 AM' },
        { id: 3, tipo: 'Snack', nombre: 'Galletas para perro', cantidad: '2 unidades', calorias: 80, hora: '03:00 PM' },
        { id: 4, tipo: 'Comida', nombre: 'Croquetas Premium', cantidad: '200g', calorias: 375, hora: '07:00 PM' }
      ],
      
      recordatorios: [
        { 
          id: 1, 
          titulo: 'Vacuna Antirrábica', 
          descripcion: 'Aplicar refuerzo anual de vacuna antirrábica', 
          fecha: '15 de Nov, 2023',
          prioridad: 'Alta',
          completado: false
        },
        { 
          id: 2, 
          titulo: 'Desparasitación', 
          descripcion: 'Administrar pastilla desparasitante mensual', 
          fecha: '20 de Nov, 2023',
          prioridad: 'Media',
          completado: false
        },
        { 
          id: 3, 
          titulo: 'Corte de uñas', 
          descripcion: 'Corte profesional de uñas en el veterinario', 
          fecha: '25 de Nov, 2023',
          prioridad: 'Baja',
          completado: false
        }
      ],
      
      historialVeterinario: [
        {
          id: 1,
          fecha: '15 Sep, 2023',
          tipo: 'Rutina',
          veterinario: 'Dr. Carlos Rodríguez',
          diagnostico: 'Chequeo general - Todo normal',
          proximaVisita: 'En 6 meses'
        },
        {
          id: 2,
          fecha: '20 Jul, 2023',
          tipo: 'Vacunación',
          veterinario: 'Dra. Ana Martínez',
          diagnostico: 'Vacuna antirrábica aplicada',
          proximaVisita: 'En 1 año'
        },
        {
          id: 3,
          fecha: '10 May, 2023',
          tipo: 'Emergencia',
          veterinario: 'Dr. Luis Fernández',
          diagnostico: 'Problema estomacal - Tratamiento aplicado',
          proximaVisita: 'Inmediata'
        }
      ],
      
      nuevaComida: {
        tipo: 'Comida',
        nombre: '',
        cantidad: '',
        calorias: ''
      },
      
      graficoActividad: null,
      graficoSueno: null
    };
  },

  computed: {
    mascotaActual() {
      return this.mascotas.find(m => m.id === this.mascotaSeleccionada) || this.mascotas[0];
    },
    
    recordatoriosPendientes() {
      return this.recordatorios.filter(r => !r.completado).length;
    }
  },

  mounted() {
    Chart.register(...registerables);
    this.inicializarGraficos();
  },

  beforeUnmount() {
    this.destruirGraficos();
  },

  methods: {
    cambiarMascota() {
      // Aquí cargarías los datos de la nueva mascota seleccionada
      this.actualizarGraficos();
    },

    cambiarPeriodo() {
      this.actualizarGraficos();
    },

    cambiarTipoActividad(tipo) {
      this.tipoActividadSeleccionada = tipo;
      // Aquí actualizarías el gráfico según el tipo de actividad
    },

    inicializarGraficos() {
      // Gráfico de actividad
      this.graficoActividad = new Chart(this.$refs.graficoActividad, {
        type: 'line',
        data: {
          labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
          datasets: [
            {
              label: 'Paseos',
              data: [30, 45, 35, 50, 40, 60, 45],
              borderColor: 'rgb(59, 130, 246)',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              borderWidth: 3,
              tension: 0.4,
              fill: true
            },
            {
              label: 'Juego',
              data: [20, 25, 30, 35, 25, 40, 30],
              borderColor: 'rgb(16, 185, 129)',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              borderWidth: 3,
              tension: 0.4,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: {
                padding: 20,
                usePointStyle: true
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Minutos'
              },
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              }
            },
            x: {
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              }
            }
          }
        }
      });

      // Gráfico de sueño
      this.graficoSueno = new Chart(this.$refs.graficoSueno, {
        type: 'radar',
        data: {
          labels: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
          datasets: [{
            label: 'Horas de Sueño',
            data: [14, 13, 15, 14, 12, 16, 15],
            backgroundColor: 'rgba(139, 92, 246, 0.2)',
            borderColor: 'rgb(139, 92, 246)',
            borderWidth: 2,
            pointBackgroundColor: 'rgb(139, 92, 246)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            r: {
              angleLines: {
                color: 'rgba(0, 0, 0, 0.1)'
              },
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              },
              pointLabels: {
                font: {
                  size: 11
                }
              },
              min: 0,
              max: 24,
              ticks: {
                stepSize: 4
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    },

    actualizarGraficos() {
      // Aquí actualizarías los gráficos con nuevos datos
      if (this.graficoActividad) {
        // Actualizar datos del gráfico de actividad
        this.graficoActividad.update();
      }
      if (this.graficoSueno) {
        // Actualizar datos del gráfico de sueño
        this.graficoSueno.update();
      }
    },

    destruirGraficos() {
      if (this.graficoActividad) {
        this.graficoActividad.destroy();
      }
      if (this.graficoSueno) {
        this.graficoSueno.destroy();
      }
    },

    generarReporte() {
      const reporte = {
        mascota: this.mascotaActual,
        kpis: this.kpis,
        periodo: this.periodoSeleccionado,
        fechaGeneracion: new Date().toISOString()
      };
      
      const blob = new Blob([JSON.stringify(reporte, null, 2)], { 
        type: 'application/json' 
      });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `reporte-salud-${this.mascotaActual.nombre.toLowerCase()}.json`;
      a.click();
      
      this.$toast.success('Reporte generado exitosamente');
    },

    abrirModal(tipo) {
      this.modalTipo = tipo;
      this.modalAbierto = true;
    },

    cerrarModal() {
      this.modalAbierto = false;
      this.modalTipo = '';
      this.nuevaComida = {
        tipo: 'Comida',
        nombre: '',
        cantidad: '',
        calorias: ''
      };
    },

    agregarAlimentacion() {
      const nuevoItem = {
        id: Date.now(),
        tipo: this.nuevaComida.tipo,
        nombre: this.nuevaComida.nombre,
        cantidad: this.nuevaComida.cantidad,
        calorias: parseInt(this.nuevaComida.calorias),
        hora: new Date().toLocaleTimeString('es-ES', { 
          hour: '2-digit', 
          minute: '2-digit' 
        })
      };
      
      this.alimentacion.unshift(nuevoItem);
      this.cerrarModal();
      this.$toast.success('Comida agregada exitosamente');
    },

    completarRecordatorio(id) {
      const recordatorio = this.recordatorios.find(r => r.id === id);
      if (recordatorio) {
        recordatorio.completado = true;
        this.$toast.success('Recordatorio completado');
      }
    }
  }
};
</script>
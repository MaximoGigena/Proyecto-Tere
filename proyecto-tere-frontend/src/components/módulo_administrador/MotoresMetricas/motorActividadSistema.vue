<template>
  <div class="dashboard-actividad-sistema bg-gray-50 min-h-screen p-6">
    <!-- Header del Dashboard -->
    <div class="mb-8">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">Monitor de Actividad del Sistema</h1>
          <p class="text-gray-600 mt-1">Métricas en tiempo real y históricas del sistema</p>
        </div>
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
            <span class="text-sm text-gray-600">Sistema Activo</span>
          </div>
          <div class="text-sm text-gray-500">
            Última actualización: {{ formatTime(ultimaActualizacion) }}
          </div>
          <button 
            @click="toggleActualizacionAutomatica"
            class="px-4 py-2 rounded-lg flex items-center gap-2"
            :class="actualizacionAutomatica ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-700'"
          >
            <svg class="w-5 h-5" :class="{'animate-spin': actualizacionAutomatica}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            {{ actualizacionAutomatica ? 'Actualizando...' : 'Actualizar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Resumen de Estado del Sistema -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Uso de CPU -->
      <div class="bg-white rounded-xl shadow-md p-6 border-l-4" :class="getEstadoCPUCss()">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-sm text-gray-500 uppercase tracking-wider">Uso de CPU</p>
            <p class="text-3xl font-bold mt-2">{{ metricasActuales.cpu.uso }}%</p>
          </div>
          <div class="relative w-16 h-16">
            <svg class="w-16 h-16 transform -rotate-90">
              <circle 
                cx="32" 
                cy="32" 
                r="28" 
                stroke="currentColor" 
                :stroke-width="4" 
                fill="none"
                class="text-gray-200"
              />
              <circle 
                cx="32" 
                cy="32" 
                r="28" 
                stroke="currentColor" 
                :stroke-width="4" 
                fill="none"
                :stroke-dasharray="circunferencia"
                :stroke-dashoffset="calcularOffsetCircular(metricasActuales.cpu.uso)"
                :class="getColorCPUCss()"
                stroke-linecap="round"
              />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
              <span class="text-lg font-semibold">{{ metricasActuales.cpu.nucleos }}</span>
            </div>
          </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
          <div class="flex justify-between">
            <span>Procesos: {{ metricasActuales.cpu.procesos }}</span>
            <span>Hilos: {{ metricasActuales.cpu.hilos }}</span>
          </div>
        </div>
      </div>

      <!-- Uso de Memoria -->
      <div class="bg-white rounded-xl shadow-md p-6 border-l-4" :class="getEstadoMemoriaCss()">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-sm text-gray-500 uppercase tracking-wider">Memoria RAM</p>
            <p class="text-3xl font-bold mt-2">{{ metricasActuales.memoria.uso }}%</p>
          </div>
          <div class="relative w-16 h-16">
            <svg class="w-16 h-16 transform -rotate-90">
              <circle 
                cx="32" 
                cy="32" 
                r="28" 
                stroke="currentColor" 
                :stroke-width="4" 
                fill="none"
                class="text-gray-200"
              />
              <circle 
                cx="32" 
                cy="32" 
                r="28" 
                stroke="currentColor" 
                :stroke-width="4" 
                fill="none"
                :stroke-dasharray="circunferencia"
                :stroke-dashoffset="calcularOffsetCircular(metricasActuales.memoria.uso)"
                :class="getColorMemoriaCss()"
                stroke-linecap="round"
              />
            </svg>
          </div>
        </div>
        <div class="mt-4">
          <div class="flex justify-between text-sm text-gray-600">
            <span>{{ formatBytes(metricasActuales.memoria.usada) }} usados</span>
            <span>{{ formatBytes(metricasActuales.memoria.total) }} total</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
            <div 
              class="h-2 rounded-full transition-all duration-500"
              :class="getColorMemoriaCss()"
              :style="{ width: `${metricasActuales.memoria.uso}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Almacenamiento -->
      <div class="bg-white rounded-xl shadow-md p-6 border-l-4" :class="getEstadoAlmacenamientoCss()">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-sm text-gray-500 uppercase tracking-wider">Almacenamiento</p>
            <p class="text-3xl font-bold mt-2">{{ metricasActuales.almacenamiento.uso }}%</p>
          </div>
          <div class="text-right">
            <div class="text-2xl font-semibold">{{ metricasActuales.almacenamiento.discos }}</div>
            <div class="text-sm text-gray-500">discos</div>
          </div>
        </div>
        <div class="mt-4">
          <div class="flex justify-between text-sm text-gray-600">
            <span>{{ formatBytes(metricasActuales.almacenamiento.usado) }} usados</span>
            <span>{{ formatBytes(metricasActuales.almacenamiento.total) }} total</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
            <div 
              class="h-2 rounded-full transition-all duration-500"
              :class="getColorAlmacenamientoCss()"
              :style="{ width: `${metricasActuales.almacenamiento.uso}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Red -->
      <div class="bg-white rounded-xl shadow-md p-6 border-l-4" :class="getEstadoRedCss()">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-sm text-gray-500 uppercase tracking-wider">Red</p>
            <p class="text-3xl font-bold mt-2">{{ metricasActuales.red.actividad }}%</p>
          </div>
          <div class="text-right">
            <div class="text-2xl font-semibold">{{ metricasActuales.red.conexiones }}</div>
            <div class="text-sm text-gray-500">conexiones</div>
          </div>
        </div>
        <div class="mt-4">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-gray-500">Descarga</p>
              <p class="font-semibold">{{ formatBytes(metricasActuales.red.descarga) }}/s</p>
            </div>
            <div>
              <p class="text-gray-500">Subida</p>
              <p class="font-semibold">{{ formatBytes(metricasActuales.red.subida) }}/s</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráficos y Métricas Detalladas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Gráfico de CPU en tiempo real -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-800">Uso de CPU en Tiempo Real</h3>
          <div class="flex gap-2">
            <button 
              v-for="intervalo in intervalosGrafico" 
              :key="intervalo"
              @click="cambiarIntervaloGrafico(intervalo)"
              class="px-3 py-1 text-sm rounded-lg"
              :class="intervaloGraficoSeleccionado === intervalo ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
            >
              {{ intervalo }}s
            </button>
          </div>
        </div>
        <div class="h-64">
          <div class="h-full flex items-end gap-1">
            <div 
              v-for="(punto, index) in datosCPU" 
              :key="index"
              class="flex-1 flex flex-col justify-end"
            >
              <div 
                class="transition-all duration-300 rounded-t"
                :class="getColorBarraCPU(punto.valor)"
                :style="{ height: `${punto.valor}%` }"
              ></div>
              <div class="text-xs text-gray-400 text-center mt-1 truncate">
                {{ punto.tiempo }}
              </div>
            </div>
          </div>
        </div>
        <div class="mt-4 flex justify-between text-sm text-gray-600">
          <div>Mín: {{ estadisticasCPU.min }}%</div>
          <div>Promedio: {{ estadisticasCPU.promedio }}%</div>
          <div>Máx: {{ estadisticasCPU.max }}%</div>
        </div>
      </div>

      <!-- Métricas de Procesos -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">Top Procesos Activos</h3>
        <div class="space-y-4">
          <div 
            v-for="proceso in procesosActivos" 
            :key="proceso.id"
            class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition"
          >
            <div class="flex justify-between items-center">
              <div>
                <div class="font-medium text-gray-900">{{ proceso.nombre }}</div>
                <div class="text-sm text-gray-500">PID: {{ proceso.pid }}</div>
              </div>
              <div class="flex items-center gap-4">
                <div class="text-right">
                  <div class="font-semibold">{{ proceso.cpu }}%</div>
                  <div class="text-xs text-gray-500">CPU</div>
                </div>
                <div class="text-right">
                  <div class="font-semibold">{{ formatBytes(proceso.memoria) }}</div>
                  <div class="text-xs text-gray-500">Memoria</div>
                </div>
                <div class="w-16">
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div 
                      class="h-2 rounded-full"
                      :class="getColorProcesoCPU(proceso.cpu)"
                      :style="{ width: `${Math.min(proceso.cpu, 100)}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Historial de Eventos y Alertas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Historial de Métricas -->
      <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">Historial de Métricas del Sistema</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPU</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Memoria</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Almacenamiento</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Red</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr 
                v-for="registro in historialMetricas" 
                :key="registro.id"
                class="hover:bg-gray-50 transition"
              >
                <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                  {{ formatDateTime(registro.timestamp) }}
                </td>
                <td class="px-4 py-3">
                  <span class="px-2 py-1 text-xs rounded-full" :class="getClaseMetrica(registro.cpu)">
                    {{ registro.cpu }}%
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span class="px-2 py-1 text-xs rounded-full" :class="getClaseMetrica(registro.memoria)">
                    {{ registro.memoria }}%
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span class="px-2 py-1 text-xs rounded-full" :class="getClaseMetrica(registro.almacenamiento)">
                    {{ registro.almacenamiento }}%
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span class="px-2 py-1 text-xs rounded-full" :class="getClaseMetrica(registro.red)">
                    {{ registro.red }}%
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span class="px-2 py-1 text-xs rounded-full" :class="getClaseEstado(registro.estado)">
                    {{ registro.estado }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-4 flex justify-between items-center">
          <button 
            @click="cargarMasHistorial"
            :disabled="cargandoHistorial"
            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-50"
          >
            {{ cargandoHistorial ? 'Cargando...' : 'Cargar más' }}
          </button>
          <div class="text-sm text-gray-500">
            Mostrando {{ historialMetricas.length }} registros
          </div>
        </div>
      </div>

      <!-- Panel de Alertas -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-800">Alertas del Sistema</h3>
          <button 
            @click="limpiarAlertas"
            class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200"
          >
            Limpiar todas
          </button>
        </div>
        <div class="space-y-4 max-h-96 overflow-y-auto">
          <div 
            v-for="alerta in alertas" 
            :key="alerta.id"
            class="border-l-4 p-4 bg-gray-50 rounded-r-lg"
            :class="getClaseAlerta(alerta.nivel)"
          >
            <div class="flex justify-between items-start">
              <div>
                <div class="font-medium">{{ alerta.titulo }}</div>
                <div class="text-sm text-gray-600 mt-1">{{ alerta.descripcion }}</div>
              </div>
              <button 
                @click="eliminarAlerta(alerta.id)"
                class="text-gray-400 hover:text-gray-600"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
            <div class="flex justify-between items-center mt-3">
              <span class="text-xs text-gray-500">{{ formatTime(alerta.timestamp) }}</span>
              <span class="text-xs px-2 py-1 rounded-full" :class="getClaseNivelAlerta(alerta.nivel)">
                {{ alerta.nivel }}
              </span>
            </div>
          </div>
          <div v-if="alertas.length === 0" class="text-center py-8 text-gray-500">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>No hay alertas activas</p>
            <p class="text-sm">El sistema está funcionando correctamente</p>
          </div>
        </div>
        <div class="mt-6">
          <h4 class="text-sm font-medium text-gray-700 mb-3">Configuración de Umbrales</h4>
          <div class="space-y-3">
            <div>
              <label class="text-xs text-gray-600">CPU Crítico</label>
              <div class="flex items-center gap-2">
                <input 
                  type="range" 
                  v-model="umbrales.cpuCritico"
                  min="70"
                  max="100"
                  class="flex-1"
                  @change="guardarUmbrales"
                >
                <span class="text-sm font-medium w-12">{{ umbrales.cpuCritico }}%</span>
              </div>
            </div>
            <div>
              <label class="text-xs text-gray-600">Memoria Crítica</label>
              <div class="flex items-center gap-2">
                <input 
                  type="range" 
                  v-model="umbrales.memoriaCritica"
                  min="80"
                  max="100"
                  class="flex-1"
                  @change="guardarUmbrales"
                >
                <span class="text-sm font-medium w-12">{{ umbrales.memoriaCritica }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Panel de Control -->
    <div class="mt-8 bg-white rounded-xl shadow-md p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-6">Panel de Control del Sistema</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <button 
          @click="exportarMetricas"
          class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition flex flex-col items-center"
        >
          <svg class="w-8 h-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <span class="text-sm font-medium">Exportar Reporte</span>
        </button>
        <button 
          @click="generarDiagnostico"
          class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition flex flex-col items-center"
        >
          <svg class="w-8 h-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-sm font-medium">Diagnóstico</span>
        </button>
        <button 
          @click="reiniciarMonitoreo"
          class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition flex flex-col items-center"
        >
          <svg class="w-8 h-8 text-yellow-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          <span class="text-sm font-medium">Reiniciar Monitoreo</span>
        </button>
        <button 
          @click="mostrarConfiguracionAvanzada"
          class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition flex flex-col items-center"
        >
          <svg class="w-8 h-8 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span class="text-sm font-medium">Configuración</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

// Tipos de datos
interface MetricasSistema {
  cpu: {
    uso: number
    nucleos: number
    procesos: number
    hilos: number
  }
  memoria: {
    uso: number
    total: number
    usada: number
    libre: number
  }
  almacenamiento: {
    uso: number
    total: number
    usado: number
    discos: number
  }
  red: {
    actividad: number
    subida: number
    descarga: number
    conexiones: number
  }
}

interface PuntoGrafico {
  tiempo: string
  valor: number
}

interface Proceso {
  id: string
  nombre: string
  pid: number
  cpu: number
  memoria: number
}

interface RegistroHistorial {
  id: string
  timestamp: Date
  cpu: number
  memoria: number
  almacenamiento: number
  red: number
  estado: string
}

interface Alerta {
  id: string
  titulo: string
  descripcion: string
  nivel: 'info' | 'advertencia' | 'critico'
  timestamp: Date
}

interface Umbrales {
  cpuCritico: number
  memoriaCritica: number
  almacenamientoCritico: number
}

// Estado del componente
const metricasActuales = ref<MetricasSistema>({
  cpu: { uso: 25, nucleos: 8, procesos: 156, hilos: 1024 },
  memoria: { uso: 65, total: 16 * 1024 * 1024 * 1024, usada: 10.4 * 1024 * 1024 * 1024, libre: 5.6 * 1024 * 1024 * 1024 },
  almacenamiento: { uso: 45, total: 512 * 1024 * 1024 * 1024, usado: 230 * 1024 * 1024 * 1024, discos: 2 },
  red: { actividad: 30, subida: 1.2 * 1024 * 1024, descarga: 4.5 * 1024 * 1024, conexiones: 42 }
})

const datosCPU = ref<PuntoGrafico[]>([])
const procesosActivos = ref<Proceso[]>([
  { id: '1', nombre: 'systemd', pid: 1, cpu: 0.5, memoria: 100 * 1024 * 1024 },
  { id: '2', nombre: 'nginx', pid: 1234, cpu: 2.3, memoria: 50 * 1024 * 1024 },
  { id: '3', nombre: 'postgresql', pid: 2345, cpu: 1.8, memoria: 200 * 1024 * 1024 },
  { id: '4', nombre: 'node', pid: 3456, cpu: 8.5, memoria: 150 * 1024 * 1024 },
  { id: '5', nombre: 'chrome', pid: 4567, cpu: 12.4, memoria: 500 * 1024 * 1024 }
])

const historialMetricas = ref<RegistroHistorial[]>([
  { id: '1', timestamp: new Date(Date.now() - 3600000), cpu: 22, memoria: 63, almacenamiento: 44, red: 28, estado: 'normal' },
  { id: '2', timestamp: new Date(Date.now() - 7200000), cpu: 18, memoria: 61, almacenamiento: 44, red: 32, estado: 'normal' },
  { id: '3', timestamp: new Date(Date.now() - 10800000), cpu: 35, memoria: 68, almacenamiento: 44, red: 25, estado: 'advertencia' },
  { id: '4', timestamp: new Date(Date.now() - 14400000), cpu: 45, memoria: 72, almacenamiento: 44, red: 38, estado: 'advertencia' },
  { id: '5', timestamp: new Date(Date.now() - 18000000), cpu: 28, memoria: 66, almacenamiento: 44, red: 22, estado: 'normal' }
])

const alertas = ref<Alerta[]>([
  { id: '1', titulo: 'CPU Alta', descripcion: 'Uso de CPU por encima del 80% durante 5 minutos', nivel: 'advertencia', timestamp: new Date(Date.now() - 600000) },
  { id: '2', titulo: 'Memoria Crítica', descripcion: 'Uso de memoria RAM alcanzando el 90%', nivel: 'critico', timestamp: new Date(Date.now() - 1200000) },
  { id: '3', titulo: 'Espacio en Disco', descripcion: 'Disco principal al 85% de capacidad', nivel: 'advertencia', timestamp: new Date(Date.now() - 1800000) }
])

const umbrales = ref<Umbrales>({
  cpuCritico: 85,
  memoriaCritica: 90,
  almacenamientoCritico: 85
})

const ultimaActualizacion = ref<Date>(new Date())
const actualizacionAutomatica = ref<boolean>(true)
const intervaloGraficoSeleccionado = ref<number>(10)
const cargandoHistorial = ref<boolean>(false)

const intervalosGrafico = [5, 10, 30, 60]

// Constantes
const circunferencia = 2 * Math.PI * 28

// Métodos computados
const estadisticasCPU = computed(() => {
  if (datosCPU.value.length === 0) {
    return { min: 0, max: 0, promedio: 0 }
  }
  
  const valores = datosCPU.value.map(d => d.valor)
  return {
    min: Math.min(...valores).toFixed(1),
    max: Math.max(...valores).toFixed(1),
    promedio: (valores.reduce((a, b) => a + b, 0) / valores.length).toFixed(1)
  }
})

// Métodos
const actualizarMetricas = () => {
  // Simular actualización de métricas del sistema
  const variacion = (Math.random() - 0.5) * 10
  
  // Actualizar CPU con variación aleatoria pero dentro de límites
  metricasActuales.value.cpu.uso = Math.max(0, Math.min(100, metricasActuales.value.cpu.uso + variacion))
  
  // Actualizar memoria
  metricasActuales.value.memoria.uso = Math.max(20, Math.min(95, metricasActuales.value.memoria.uso + variacion * 0.5))
  metricasActuales.value.memoria.usada = (metricasActuales.value.memoria.total * metricasActuales.value.memoria.uso) / 100
  
  // Actualizar red
  metricasActuales.value.red.actividad = Math.max(5, Math.min(80, metricasActuales.value.red.actividad + variacion * 2))
  
  // Agregar punto al gráfico
  const ahora = new Date()
  const hora = ahora.getHours().toString().padStart(2, '0')
  const minuto = ahora.getMinutes().toString().padStart(2, '0')
  
  datosCPU.value.push({
    tiempo: `${hora}:${minuto}`,
    valor: metricasActuales.value.cpu.uso
  })
  
  // Mantener solo los últimos 20 puntos
  if (datosCPU.value.length > 20) {
    datosCPU.value = datosCPU.value.slice(-20)
  }
  
  // Verificar alertas
  verificarAlertas()
  
  ultimaActualizacion.value = new Date()
}

const calcularOffsetCircular = (porcentaje: number): number => {
  return circunferencia - (porcentaje / 100) * circunferencia
}

const verificarAlertas = () => {
  const { cpu, memoria, almacenamiento } = metricasActuales.value
  
  // Verificar CPU
  if (cpu.uso > umbrales.value.cpuCritico) {
    agregarAlertaSiNoExiste(
      `CPU Crítico - ${cpu.uso}%`,
      `El uso de CPU ha superado el umbral del ${umbrales.value.cpuCritico}%`,
      'critico'
    )
  } else if (cpu.uso > umbrales.value.cpuCritico - 10) {
    agregarAlertaSiNoExiste(
      `CPU Alta - ${cpu.uso}%`,
      `El uso de CPU se acerca al umbral crítico`,
      'advertencia'
    )
  }
  
  // Verificar memoria
  if (memoria.uso > umbrales.value.memoriaCritica) {
    agregarAlertaSiNoExiste(
      `Memoria Crítica - ${memoria.uso}%`,
      `El uso de memoria ha superado el umbral del ${umbrales.value.memoriaCritica}%`,
      'critico'
    )
  }
}

const agregarAlertaSiNoExiste = (titulo: string, descripcion: string, nivel: Alerta['nivel']) => {
  const existe = alertas.value.some(alerta => 
    alerta.titulo === titulo && 
    Date.now() - alerta.timestamp.getTime() < 300000 // 5 minutos
  )
  
  if (!existe) {
    alertas.value.unshift({
      id: Date.now().toString(),
      titulo,
      descripcion,
      nivel,
      timestamp: new Date()
    })
    
    // Limitar a 10 alertas
    if (alertas.value.length > 10) {
      alertas.value = alertas.value.slice(0, 10)
    }
  }
}

// Métodos de utilidad
const formatBytes = (bytes: number): string => {
  const unidades = ['B', 'KB', 'MB', 'GB', 'TB']
  let i = 0
  while (bytes >= 1024 && i < unidades.length - 1) {
    bytes /= 1024
    i++
  }
  return `${bytes.toFixed(1)} ${unidades[i]}`
}

const formatTime = (date: Date): string => {
  return date.toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const formatDateTime = (date: Date): string => {
  return date.toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Métodos de estilos
const getEstadoCPUCss = (): string => {
  const uso = metricasActuales.value.cpu.uso
  if (uso > umbrales.value.cpuCritico) return 'border-red-500'
  if (uso > umbrales.value.cpuCritico - 15) return 'border-yellow-500'
  return 'border-green-500'
}

const getColorCPUCss = (): string => {
  const uso = metricasActuales.value.cpu.uso
  if (uso > umbrales.value.cpuCritico) return 'text-red-500'
  if (uso > umbrales.value.cpuCritico - 15) return 'text-yellow-500'
  return 'text-green-500'
}

const getEstadoMemoriaCss = (): string => {
  const uso = metricasActuales.value.memoria.uso
  if (uso > umbrales.value.memoriaCritica) return 'border-red-500'
  if (uso > umbrales.value.memoriaCritica - 10) return 'border-yellow-500'
  return 'border-blue-500'
}

const getColorMemoriaCss = (): string => {
  const uso = metricasActuales.value.memoria.uso
  if (uso > umbrales.value.memoriaCritica) return 'text-red-500 bg-red-500'
  if (uso > umbrales.value.memoriaCritica - 10) return 'text-yellow-500 bg-yellow-500'
  return 'text-blue-500 bg-blue-500'
}

const getEstadoAlmacenamientoCss = (): string => {
  const uso = metricasActuales.value.almacenamiento.uso
  if (uso > umbrales.value.almacenamientoCritico) return 'border-red-500'
  if (uso > umbrales.value.almacenamientoCritico - 10) return 'border-yellow-500'
  return 'border-purple-500'
}

const getColorAlmacenamientoCss = (): string => {
  const uso = metricasActuales.value.almacenamiento.uso
  if (uso > umbrales.value.almacenamientoCritico) return 'text-red-500 bg-red-500'
  if (uso > umbrales.value.almacenamientoCritico - 10) return 'text-yellow-500 bg-yellow-500'
  return 'text-purple-500 bg-purple-500'
}

const getEstadoRedCss = (): string => {
  const actividad = metricasActuales.value.red.actividad
  if (actividad > 70) return 'border-red-500'
  if (actividad > 50) return 'border-yellow-500'
  return 'border-green-500'
}

const getColorRedCss = (): string => {
  const actividad = metricasActuales.value.red.actividad
  if (actividad > 70) return 'text-red-500'
  if (actividad > 50) return 'text-yellow-500'
  return 'text-green-500'
}

const getClaseMetrica = (valor: number): string => {
  if (valor > 80) return 'bg-red-100 text-red-800'
  if (valor > 60) return 'bg-yellow-100 text-yellow-800'
  return 'bg-green-100 text-green-800'
}

const getClaseEstado = (estado: string): string => {
  switch (estado) {
    case 'critico': return 'bg-red-100 text-red-800'
    case 'advertencia': return 'bg-yellow-100 text-yellow-800'
    default: return 'bg-green-100 text-green-800'
  }
}

const getClaseAlerta = (nivel: string): string => {
  switch (nivel) {
    case 'critico': return 'border-red-500 bg-red-50'
    case 'advertencia': return 'border-yellow-500 bg-yellow-50'
    default: return 'border-blue-500 bg-blue-50'
  }
}

const getClaseNivelAlerta = (nivel: string): string => {
  switch (nivel) {
    case 'critico': return 'bg-red-200 text-red-800'
    case 'advertencia': return 'bg-yellow-200 text-yellow-800'
    default: return 'bg-blue-200 text-blue-800'
  }
}

const getColorBarraCPU = (valor: number): string => {
  if (valor > umbrales.value.cpuCritico) return 'bg-red-500'
  if (valor > umbrales.value.cpuCritico - 15) return 'bg-yellow-500'
  return 'bg-blue-500'
}

const getColorProcesoCPU = (valor: number): string => {
  if (valor > 10) return 'bg-red-500'
  if (valor > 5) return 'bg-yellow-500'
  return 'bg-green-500'
}

// Métodos de interacción
const toggleActualizacionAutomatica = () => {
  actualizacionAutomatica.value = !actualizacionAutomatica.value
}

const cambiarIntervaloGrafico = (intervalo: number) => {
  intervaloGraficoSeleccionado.value = intervalo
  // En una implementación real, esto cambiaría el intervalo de actualización
}

const cargarMasHistorial = async () => {
  cargandoHistorial.value = true
  // Simular carga de API
  await new Promise(resolve => setTimeout(resolve, 1000))
  
  const nuevoRegistro: RegistroHistorial = {
    id: (historialMetricas.value.length + 1).toString(),
    timestamp: new Date(Date.now() - historialMetricas.value.length * 3600000),
    cpu: Math.floor(Math.random() * 50) + 10,
    memoria: Math.floor(Math.random() * 40) + 50,
    almacenamiento: 44,
    red: Math.floor(Math.random() * 40) + 10,
    estado: Math.random() > 0.7 ? 'advertencia' : 'normal'
  }
  
  historialMetricas.value.push(nuevoRegistro)
  cargandoHistorial.value = false
}

const eliminarAlerta = (id: string) => {
  alertas.value = alertas.value.filter(alerta => alerta.id !== id)
}

const limpiarAlertas = () => {
  alertas.value = []
}

const guardarUmbrales = () => {
  // En una implementación real, esto guardaría en el backend
  localStorage.setItem('umbrales-sistema', JSON.stringify(umbrales.value))
}

const exportarMetricas = () => {
  const data = {
    metricas: metricasActuales.value,
    historial: historialMetricas.value,
    timestamp: new Date()
  }
  
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `metricas-sistema-${new Date().toISOString().split('T')[0]}.json`
  a.click()
  URL.revokeObjectURL(url)
}

const generarDiagnostico = () => {
  alert('Diagnóstico generado. Revisa el panel de alertas para recomendaciones.')
}

const reiniciarMonitoreo = () => {
  datosCPU.value = []
  historialMetricas.value = []
  alertas.value = []
  actualizarMetricas()
}

const mostrarConfiguracionAvanzada = () => {
  alert('Configuración avanzada - Esta funcionalidad estará disponible en la próxima versión')
}

// Ciclo de vida
let intervaloActualizacion: number

onMounted(() => {
  // Inicializar gráfico con datos
  for (let i = 0; i < 20; i++) {
    const tiempo = new Date(Date.now() - (19 - i) * 5000)
    const hora = tiempo.getHours().toString().padStart(2, '0')
    const minuto = tiempo.getMinutes().toString().padStart(2, '0')
    
    datosCPU.value.push({
      tiempo: `${hora}:${minuto}`,
      valor: Math.random() * 30 + 20
    })
  }
  
  // Configurar actualización automática
  intervaloActualizacion = setInterval(() => {
    if (actualizacionAutomatica.value) {
      actualizarMetricas()
    }
  }, 2000)
  
  // Cargar umbrales guardados
  const umbralesGuardados = localStorage.getItem('umbrales-sistema')
  if (umbralesGuardados) {
    umbrales.value = JSON.parse(umbralesGuardados)
  }
})

onUnmounted(() => {
  if (intervaloActualizacion) {
    clearInterval(intervaloActualizacion)
  }
})
</script>

<style scoped>
.dashboard-actividad-sistema {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

/* Personalización del slider */
input[type="range"] {
  -webkit-appearance: none;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 18px;
  height: 18px;
  background: #3b82f6;
  border-radius: 50%;
  cursor: pointer;
}

input[type="range"]::-moz-range-thumb {
  width: 18px;
  height: 18px;
  background: #3b82f6;
  border-radius: 50%;
  cursor: pointer;
  border: none;
}

/* Animaciones personalizadas */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
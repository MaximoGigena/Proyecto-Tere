<!-- Reportes de Métricas de Usuarios -->
<template>
  <div class="metricas-contenido">
    <!-- Loading -->
    <div v-if="isGenerating" class="fixed top-0 left-0 w-full h-full bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center">
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-700">Generando reporte...</p>
      </div>
    </div>

    <!-- Header simplificado -->
    <div class="filtros flex flex-col sm:flex-row gap-3 mb-8">
      <div class="flex-1">
        <h2 class="text-2xl font-bold text-gray-800">Generador de Reportes</h2>
        <p class="text-sm text-gray-600">Configura y genera reportes personalizados</p>
      </div>
      <button 
        @click="generateReport" 
        :disabled="!canGenerate"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg transition duration-200 flex items-center justify-center gap-2"
      >
        <span>{{ isGenerating ? '⏳' : '📊' }}</span>
        {{ isGenerating ? 'Generando...' : 'Generar Reporte' }}
      </button>
    </div>

    <!-- Filtros en tarjeta (estilo motor) -->
    <div class="filtros-avanzados bg-white rounded-xl shadow-sm p-6 mb-8">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-800">Configuración del Reporte</h3>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Título del Reporte -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Título del Reporte</label>
          <input
            type="text"
            v-model="reportTitle"
            placeholder="Ingrese título del reporte"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <!-- Selección de Métricas -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Métricas</label>
          <div class="relative">
            <select 
              v-model="newMetric" 
              @change="addMetric"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
            >
              <option value="">Agregar métrica...</option>
              <option
                v-for="metric in availableMetrics"
                :key="metric.id"
                :value="metric.id"
                :disabled="selectedMetrics.includes(metric.id)"
              >
                {{ metric.name }}
              </option>
            </select>
          </div>
          <div class="selected-metrics flex flex-wrap gap-1 mt-2">
            <span 
              v-for="metricId in selectedMetrics" 
              :key="metricId"
              class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs"
            >
              {{ getMetricName(metricId) }}
              <button 
                @click="toggleMetric(metricId)"
                class="text-blue-600 hover:text-blue-800"
              >
                ×
              </button>
            </span>
          </div>
        </div>

        <!-- Tipo de Gráfico -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Visualización</label>
          <select 
            v-model="selectedChartType"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
          >
            <option
              v-for="chart in chartTypes"
              :key="chart.type"
              :value="chart.type"
            >
              {{ chart.name }}
            </option>
          </select>
        </div>

        <!-- Rango de Fechas -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Rango de Fechas</label>
          <div class="grid grid-cols-2 gap-2">
            <input
              type="date"
              v-model="dateRange.start"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
            <input
              type="date"
              v-model="dateRange.end"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>

        <!-- Segmentación -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Segmentar por</label>
          <select v-model="segmentation" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
            <option value="">Sin segmentación</option>
            <option
              v-for="segment in segmentationOptions"
              :key="segment.value"
              :value="segment.value"
            >
              {{ segment.label }}
            </option>
          </select>
        </div>

        <!-- Comparación -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Comparación</label>
          <select v-model="comparisonPeriod" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
            <option value="">Sin comparación</option>
            <option value="previous_period">Período Anterior</option>
            <option value="same_period_last_year">
              Mismo Período Año Anterior
            </option>
          </select>
        </div>
      </div>

      <!-- Filtros adicionales -->
      <div class="mt-6 pt-6 border-t border-gray-200">
        <label class="block text-sm font-medium text-gray-700 mb-3">Filtros Adicionales</label>
        <div class="flex flex-wrap gap-2">
          <div
            v-for="filter in dynamicFilters"
            :key="filter.id"
            class="filter-item bg-gray-100 px-3 py-2 rounded-lg flex items-center gap-2"
          >
            <span class="text-xs text-gray-700">{{ filter.label }}:</span>
            <span class="text-xs font-medium">{{ filter.value || 'Todos' }}</span>
            <button 
              @click="removeFilter(filter.id)"
              class="text-gray-500 hover:text-red-500 text-sm"
            >
              ×
            </button>
          </div>
          <button 
            @click="addCustomFilter" 
            class="px-3 py-2 border border-dashed border-gray-300 rounded text-gray-600 hover:text-blue-600 hover:border-blue-400 transition-all duration-300 text-sm"
          >
            + Agregar Filtro
          </button>
        </div>
      </div>
    </div>

    <!-- Vista Previa del Reporte -->
    <div v-if="reportData" class="bg-white rounded-xl shadow-sm p-6 mb-8">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-800">{{ reportTitle }}</h3>
        <button 
          @click="exportCurrentReport" 
          class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition duration-200 flex items-center gap-2 text-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Exportar PDF
        </button>
      </div>

      <!-- Metadatos del reporte -->
      <div class="meta-info flex flex-wrap gap-2 mb-6">
        <span class="inline-flex items-center gap-2 text-xs text-gray-600 bg-gray-100 px-3 py-1.5 rounded-full">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          {{ formatDate(dateRange.start) }} - {{ formatDate(dateRange.end) }}
        </span>
        <span v-if="segmentation" class="inline-flex items-center gap-2 text-xs text-gray-600 bg-gray-100 px-3 py-1.5 rounded-full">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
          </svg>
          Segmentado por: {{ getSegmentationLabel() }}
        </span>
        <span v-if="comparisonPeriod" class="inline-flex items-center gap-2 text-xs text-gray-600 bg-gray-100 px-3 py-1.5 rounded-full">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          Comparando con: {{ getComparisonLabel() }}
        </span>
      </div>

      <!-- Gráfico Principal -->
      <div class="chart-container bg-white rounded-lg p-4 mb-6 border border-gray-200">
        <div class="h-96">
          <ReportChart
            v-if="reportData && selectedMetrics.length > 0"
            :report-data="reportData"
            :chart-type="selectedChartType"
            :selected-metrics="selectedMetrics"
            height="100%"
          />
          <div v-else class="h-full flex items-center justify-center">
            <div class="text-center">
              <div class="text-4xl mb-4 opacity-30">📊</div>
              <p class="text-gray-500">Selecciona métricas y genera el reporte para ver los gráficos</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Métricas Resumen -->
      <div class="metrics-summary mb-8" v-if="reportData?.summary">
        <h4 class="text-lg font-semibold text-gray-800 mb-5">Resumen de Métricas</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <div
            v-for="item in reportData.summary"
            :key="item.metric"
            class="summary-item bg-white p-5 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 border-l-4"
            :class="{
              'border-l-green-500': item.trend === 'up',
              'border-l-red-500': item.trend === 'down',
              'border-l-yellow-500': item.trend === 'stable'
            }"
          >
            <div class="summary-label text-xs text-gray-600 mb-2">{{ item.label }}</div>
            <div class="summary-value text-2xl font-bold text-gray-800 mb-2">{{ item.value }}</div>
            <div class="summary-change" v-if="item.change">
              <span :class="{
                'text-green-600': item.change.direction === 'up',
                'text-red-600': item.change.direction === 'down',
                'text-yellow-600': item.change.direction === 'stable'
              }" class="text-xs font-semibold">
                {{ item.change.direction === 'up' ? '↑' : item.change.direction === 'down' ? '↓' : '→' }}
                {{ item.change.value }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reportes Creados -->
    <div class="reports-grid mt-8">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-800">Reportes Creados</h3>
      </div>
      
      <div v-if="savedReports.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="report in savedReports"
          :key="report.id"
          class="report-card bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 p-6 border border-gray-100"
        >
          <div class="report-card-header flex justify-between items-start mb-4">
            <div>
              <h4 class="text-lg font-bold text-gray-800 mb-2">{{ report.title }}</h4>
              <div class="report-meta flex items-center gap-3 text-sm text-gray-600">
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  {{ formatDate(report.createdAt) }}
                </span>
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                  </svg>
                  {{ report.metricsCount }} métricas
                </span>
              </div>
            </div>
            <button
              @click="deleteReport(report.id)"
              class="text-gray-400 hover:text-red-500 transition-colors duration-200"
              title="Eliminar reporte"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
          
          <div class="report-preview-metrics mb-4">
            <div class="metrics-tags flex flex-wrap gap-2">
              <span 
                v-for="metric in report.selectedMetrics.slice(0, 3)" 
                :key="metric"
                class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs"
              >
                {{ getMetricName(metric) }}
              </span>
              <span 
                v-if="report.selectedMetrics.length > 3"
                class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs"
              >
                +{{ report.selectedMetrics.length - 3 }} más
              </span>
            </div>
          </div>
          
          <div class="report-card-footer flex justify-between items-center mt-6 pt-6 border-t border-gray-100">
            <div class="report-type flex items-center gap-2 text-sm text-gray-600">
              <span>{{ getChartTypeName(report.chartType) }}</span>
            </div>
            <button
              @click="exportReport(report)"
              class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-200 flex items-center gap-2 text-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Exportar PDF
            </button>
          </div>
        </div>
      </div>
      
      <div v-else class="empty-state bg-white rounded-xl p-12 text-center border border-gray-200">
        <div class="empty-icon text-6xl mb-6 opacity-30">
          <svg class="w-24 h-24 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <h3 class="text-xl font-medium text-gray-600 mb-2">No hay reportes creados</h3>
        <p class="text-gray-500 text-sm">Configura los filtros y genera tu primer reporte</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import axios from 'axios'
import ReportChart from '@/components/charts/ReportChart.vue'

export default {

  name: 'ReportGenerator',
  components: {
    ReportChart
  },

  name: 'ReportGenerator',
  setup() {
    // Inicializar autenticación
    const { 
      isAuthenticated, 
      accessToken,
      user,
      checkAndRedirectIfSuspended,
      isSuspendido,
      logout 
    } = useAuth()
    
    // Verificar suspensión al inicio
    if (checkAndRedirectIfSuspended()) {
      return
    }
    
    // Datos del reporte
    const reportTitle = ref('Reporte de Métricas')
    const reportData = ref(null)
    const isGenerating = ref(false)
    const newMetric = ref('')
    
    // Métricas disponibles
    const availableMetrics = ref([
      { id: 'users', name: 'Usuarios Activos', icon: '👥' },
      { id: 'revenue', name: 'Ingresos', icon: '💰' },
      { id: 'conversion', name: 'Tasa de Conversión', icon: '📈' },
      { id: 'sessions', name: 'Sesiones', icon: '🖥️' },
      { id: 'retention', name: 'Retención', icon: '📊' },
      { id: 'avg_order', name: 'Ticket Promedio', icon: '🛒' },
      { id: 'bounce_rate', name: 'Tasa de Rebote', icon: '↩️' },
      { id: 'pageviews', name: 'Vistas de Página', icon: '👁️' }
    ])
    
    const selectedMetrics = ref(['users', 'revenue', 'conversion'])
    
    // Tipos de gráficos
    const chartTypes = ref([
      { type: 'pie', name: 'Gráfico de Pastel', icon: '🥧' },
      { type: 'bar', name: 'Gráfico de Barras', icon: '📊' },
      { type: 'line', name: 'Gráfico de Líneas', icon: '📈' },
      { type: 'box', name: 'Gráfico de Caja', icon: '📦' },
      { type: 'scatter', name: 'Gráfico de Dispersión', icon: '•' },
      { type: 'heatmap', name: 'Mapa de Calor', icon: '🔥' }
    ])
    
    const selectedChartType = ref('bar')
    
    // Filtros
    const dateRange = ref({
      start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      end: new Date().toISOString().split('T')[0]
    })
    
    const segmentation = ref('')
    const segmentationOptions = ref([
      { value: 'age_group', label: 'Grupo de Edad' },
      { value: 'gender', label: 'Género' },
      { value: 'location', label: 'Ubicación' },
      { value: 'device', label: 'Dispositivo' },
      { value: 'source', label: 'Fuente de Tráfico' }
    ])
    
    const comparisonPeriod = ref('')
    const dynamicFilters = ref([])

    // Reportes guardados
    const localReports = ref([])  
    const savedReports = ref([])
    
    // Variables para manejar autorización
    const authorizationError = ref(false)
    const errorMessage = ref('')
    
    // Computed
    const canGenerate = computed(() => {
      return isAuthenticated.value && 
             selectedMetrics.value.length > 0 && 
             dateRange.value.start && 
             dateRange.value.end
    })
    
    // Computed para mostrar estado de autenticación
    const authStatus = computed(() => {
      if (!isAuthenticated.value) return 'No autenticado'
      if (isSuspendido()) return 'Cuenta suspendida'
      return 'Autenticado'
    })
    
    // Métodos
    const getMetricName = (metricId) => {
      const metric = availableMetrics.value.find(m => m.id === metricId)
      return metric ? metric.name : metricId
    }
    
    const getChartTypeName = (chartType) => {
      const chart = chartTypes.value.find(c => c.type === chartType)
      return chart ? chart.name : chartType
    }
    
    const toggleMetric = (metricId) => {
      const index = selectedMetrics.value.indexOf(metricId)
      if (index > -1) {
        selectedMetrics.value.splice(index, 1)
      }
    }
    
    const addMetric = () => {
      if (newMetric.value && !selectedMetrics.value.includes(newMetric.value)) {
        selectedMetrics.value.push(newMetric.value)
        newMetric.value = ''
      }
    }

    const saveReportToDatabase = async () => {
      try {
        const authAxios = createAuthenticatedAxios()
        
        const reportData = {
          nombre: reportTitle.value || 'Reporte sin título',
          descripcion: 'Reporte generado desde frontend',
          tipo_reporte: 'usuarios',
          configuracion: {
            chartType: selectedChartType.value,
            metrics: selectedMetrics.value
          },
          parametros: {
            metricas: selectedMetrics.value,
            fecha_inicio: dateRange.value.start,
            fecha_fin: dateRange.value.end,
            segmentacion: segmentation.value,
            comparacion_periodo: comparisonPeriod.value,
            filtros: dynamicFilters.value.reduce((acc, filter) => {
              acc[filter.id] = filter.value
              return acc
            }, {})
          },
          programado: false
        }
        
        const response = await authAxios.post('/reportes', reportData)
        
        if (response.data.success) {
          console.log('✅ Reporte guardado en BD:', response.data.data)
          
          // ✅ Actualizar la lista desde BD
          await fetchSavedReports()
          
          // ✅ También guardar temporalmente para acceso rápido
          localReports.value.unshift({
            id: response.data.data.id, // ID REAL de la BD
            title: response.data.data.nombre,
            selectedMetrics: selectedMetrics.value,
            chartType: selectedChartType.value,
            dateRange: { ...dateRange.value },
            createdAt: response.data.data.created_at,
            metricsCount: selectedMetrics.value.length,
            data: reportData.value
          })
        }
      } catch (error) {
        console.error('❌ Error guardando reporte:', error)
      }
    }
    
    const saveReport = () => {
      const report = {
        id: Date.now(),
        title: reportTitle.value || 'Reporte sin título',
        selectedMetrics: [...selectedMetrics.value],
        chartType: selectedChartType.value,
        dateRange: { ...dateRange.value },
        segmentation: segmentation.value,
        comparisonPeriod: comparisonPeriod.value,
        metricsCount: selectedMetrics.value.length,
        createdAt: new Date().toISOString(),
        data: reportData.value
      }
      
      savedReports.value.unshift(report) // Agregar al inicio
      localStorage.setItem('savedReports', JSON.stringify(savedReports.value))
    }
    
    const addCustomFilter = () => {
      const newFilter = {
        id: Date.now(),
        label: 'Filtro Personalizado',
        type: 'select',
        value: '',
        options: [
          { value: 'option1', label: 'Opción 1' },
          { value: 'option2', label: 'Opción 2' }
        ]
      }
      dynamicFilters.value.push(newFilter)
    }
    
    const removeFilter = (filterId) => {
      dynamicFilters.value = dynamicFilters.value.filter(f => f.id !== filterId)
    }
    
    const exportCurrentReport = async () => {
      if (!reportData.value) return;
      
      try {
        const authAxios = createAuthenticatedAxios();
        
        // Obtener el canvas del gráfico actual y convertirlo a imagen
        const chartElement = document.querySelector('.chart-container canvas');
        let chartImage = null;
        
        if (chartElement) {
          // Convertir canvas a imagen base64
          chartImage = chartElement.toDataURL('image/png');
        }
        
        // Preparar datos estructurados para el gráfico
        const datosGrafico = {
          type: selectedChartType.value,
          data: reportData.value, // Enviar los datos completos
          config: {
            titulo: reportTitle.value,
            tipo: selectedChartType.value,
            selectedMetrics: selectedMetrics.value,
            chartImage: chartImage // Incluir la imagen del gráfico
          }
        };
        
        const response = await authAxios.post('/reportes/exportar-con-graficos', {
          titulo: reportTitle.value,
          datos: reportData.value,
          grafico: datosGrafico,
          formato: 'pdf'
        }, {
          responseType: 'blob'
        });
        
        // Descargar archivo
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        
        link.href = url;
        link.download = `${reportTitle.value.replace(/\s+/g, '_')}_${new Date().getTime()}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
      } catch (error) {
        console.error('Error exportando PDF:', error);
        alert('Error al exportar el reporte: ' + (error.response?.data?.message || error.message));
      }
    };

    const exportReport = async (report) => {
      try {
        console.log('📤 Exportando reporte:', report)
        
        const authAxios = createAuthenticatedAxios()
        
        // ✅ Verificar si el ID es válido para BD (no es un timestamp gigante)
        const isDbId = report.id < 9999999999 // IDs de BD son pequeños
        
        if (!isDbId) {
          // ID parece ser de frontend, usar exportación directa
          console.warn('⚠️ ID de frontend detectado, usando exportación directa')
          await exportCurrentReport()
          return
        }
        
        // ✅ Usar la ruta correcta con ID de BD
        const response = await authAxios.get(`/reportes/${report.id}/exportar`, {
          responseType: 'blob',
          params: { 
            formato: 'pdf'
          }
        })
        
        // Crear nombre de archivo seguro
        const safeTitle = report.title
          .replace(/[^\w\s]/gi, '')
          .replace(/\s+/g, '_')
          .toLowerCase()
        
        const timestamp = new Date().toISOString().split('T')[0]
        const filename = `${safeTitle}_${timestamp}.pdf`
        
        // Descargar archivo
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        
        link.href = url
        link.download = filename
        link.style.display = 'none'
        
        document.body.appendChild(link)
        link.click()
        
        // Limpiar
        setTimeout(() => {
          document.body.removeChild(link)
          window.URL.revokeObjectURL(url)
        }, 100)
        
      } catch (error) {
        console.error('❌ Error exportando reporte:', error)
        
        // Manejo de error mejorado
        if (error.response) {
          console.error('📊 Estado:', error.response.status)
          console.error('📊 Headers:', error.response.headers)
          
          // Intentar leer el mensaje de error
          if (error.response.data instanceof Blob) {
            try {
              const errorText = await error.response.data.text()
              console.error('📊 Contenido del error:', errorText)
              
              let errorJson = {}
              try {
                errorJson = JSON.parse(errorText)
              } catch (e) {
                console.error('No se pudo parsear como JSON')
              }
              
              if (error.response.status === 404) {
                alert(`⚠️ Reporte no encontrado. Es posible que haya sido eliminado.\n\nID: ${report.id}\nTítulo: ${report.title}`)
                
                // Eliminar de la lista si no existe
                savedReports.value = savedReports.value.filter(r => r.id !== report.id)
                localReports.value = localReports.value.filter(r => r.id !== report.id)
              } else if (error.response.status === 500) {
                alert(`❌ Error interno del servidor:\n${errorJson.message || errorJson.error || 'Error desconocido'}`)
              }
            } catch (e) {
              console.error('Error leyendo blob:', e)
            }
          }
        } else {
          alert('Error de conexión al servidor')
        }
      }
    }
    
    const deleteReport = async (reportId) => {
      if (!confirm('¿Estás seguro de eliminar este reporte?')) {
        return
      }
      
      try {
        console.log('🗑️ Eliminando reporte ID:', reportId)
        
        const authAxios = createAuthenticatedAxios()
        
        // Verificar si es un ID de BD (no timestamp gigante)
        const isDbId = reportId < 9999999999 // IDs de BD son pequeños
        
        if (!isDbId) {
          // Es un ID local, eliminar solo del frontend
          savedReports.value = savedReports.value.filter(r => r.id !== reportId)
          localStorage.setItem('savedReports', JSON.stringify(savedReports.value))
          
          // También eliminar de localReports si existe
          localReports.value = localReports.value.filter(r => r.id !== reportId)
          
          alert('Reporte local eliminado')
          return
        }
        
        // Es un ID de BD, llamar al backend
        const response = await authAxios.delete(`/reportes/${reportId}`)
        
        if (response.data.success) {
          console.log('✅ Reporte eliminado de BD:', response.data.message)
          
          // Actualizar ambas listas
          savedReports.value = savedReports.value.filter(r => r.id !== reportId)
          localReports.value = localReports.value.filter(r => r.id !== reportId)
          
          // Mostrar mensaje de éxito
          alert('✅ Reporte eliminado exitosamente')
        } else {
          throw new Error(response.data.message || 'Error al eliminar')
        }
        
      } catch (error) {
        console.error('❌ Error eliminando reporte:', error)
        
        // Manejo de errores
        if (error.response) {
          switch (error.response.status) {
            case 401:
              alert('Sesión expirada. Por favor, inicia sesión nuevamente.')
              break
            case 403:
              alert('No tienes permisos para eliminar este reporte.')
              break
            case 404:
              alert('El reporte ya no existe o fue eliminado.')
              // Eliminar de las listas locales de todos modos
              savedReports.value = savedReports.value.filter(r => r.id !== reportId)
              localReports.value = localReports.value.filter(r => r.id !== reportId)
              break
            default:
              alert(`Error al eliminar el reporte: ${error.response.data?.message || error.message}`)
          }
        } else {
          alert('Error de conexión al servidor')
        }
      }
    }
    
    const getSegmentationLabel = () => {
      const option = segmentationOptions.value.find(opt => opt.value === segmentation.value)
      return option ? option.label : ''
    }
    
    const getComparisonLabel = () => {
      const labels = {
        previous_period: 'Período Anterior',
        same_period_last_year: 'Mismo Período Año Anterior',
        custom: 'Personalizado'
      }
      return labels[comparisonPeriod.value] || ''
    }
    
    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('es-ES')
    }

    // Crear instancia de axios con interceptor de autorización
    const createAuthenticatedAxios = () => {
      const instance = axios.create({
        baseURL: '/api',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      })
      
      // Interceptor para agregar token de autorización
      instance.interceptors.request.use(
        (config) => {
          if (accessToken.value) {
            config.headers.Authorization = `Bearer ${accessToken.value}`
          }
          return config
        },
        (error) => {
          return Promise.reject(error)
        }
      )
      
      // Interceptor para manejar errores de autorización
      instance.interceptors.response.use(
        (response) => response,
        (error) => {
          if (error.response && error.response.status === 401) {
            authorizationError.value = true
            errorMessage.value = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.'
            
            // Opcional: cerrar sesión automáticamente
            setTimeout(() => {
              logout()
            }, 3000)
          } else if (error.response && error.response.status === 403) {
            authorizationError.value = true
            errorMessage.value = 'No tienes permisos para acceder a esta funcionalidad.'
          }
          return Promise.reject(error)
        }
      )
      
      return instance
    }

    const generateReport = async () => {
      // Verificar autenticación antes de continuar
      if (!isAuthenticated.value) {
        authorizationError.value = true
        errorMessage.value = 'Debes iniciar sesión para generar reportes.'
        return
      }
      
      // Verificar si está suspendido
      if (isSuspendido()) {
        authorizationError.value = true
        errorMessage.value = 'Tu cuenta está suspendida. No puedes generar reportes.'
        return
      }
      
      isGenerating.value = true
      authorizationError.value = false
      errorMessage.value = ''
      
      try {
        const authAxios = createAuthenticatedAxios()
        
        const parametros = {
          metricas: selectedMetrics.value,
          fecha_inicio: dateRange.value.start,
          fecha_fin: dateRange.value.end,
          segmentacion: segmentation.value,
          comparacion_periodo: comparisonPeriod.value,
          filtros: dynamicFilters.value.reduce((acc, filter) => {
            acc[filter.id] = filter.value
            return acc
          }, {})
        }
        
        // Usar la ruta correcta - POST a /reportes/ejecutar
        const response = await authAxios.post('/reportes/ejecutar-directo', {
          parametros,
          formato: 'json'
        })
        
        console.log('✅ Respuesta del reporte:', response.data)
        
        if (response.data.success) {
          reportData.value = response.data.data.resultados

          await saveReportToDatabase()
          
          // Guardar en reportes recientes
          saveReport()
          
          // Mostrar éxito
          errorMessage.value = '✅ Reporte generado exitosamente'
          authorizationError.value = false
        } else {
          errorMessage.value = response.data.message || 'Error al generar el reporte'
          authorizationError.value = true
        }
        
      } catch (error) {
        console.error('❌ Error generando reporte:', error)
        
        // Mostrar error al usuario
        if (error.response) {
          errorMessage.value = error.response.data?.message || 
                              error.response.data?.error || 
                              `Error ${error.response.status}: ${error.response.statusText}`
        } else if (error.request) {
          errorMessage.value = 'No se pudo conectar con el servidor'
        } else {
          errorMessage.value = error.message || 'Error desconocido'
        }
        
        authorizationError.value = true
      } finally {
        isGenerating.value = false
      }
    }

    // Método para obtener métricas disponibles desde el backend
    const fetchMetricasDisponibles = async () => {
      // Verificar autenticación
      if (!isAuthenticated.value) return
      
      try {
        const authAxios = createAuthenticatedAxios()
        const response = await authAxios.get('/reportes/metricas/usuarios')
        availableMetrics.value = response.data.data || response.data
        segmentationOptions.value = response.data.segmentaciones || []
      } catch (error) {
        console.error('Error obteniendo métricas:', error)
        
        // Solo mostrar error si no es de autorización (ya se maneja en el interceptor)
        if (error.response && ![401, 403].includes(error.response.status)) {
          errorMessage.value = 'Error al cargar las métricas disponibles'
          authorizationError.value = true
        }
      }
    }

    const fetchSavedReports = async () => {
      try {
        const authAxios = createAuthenticatedAxios()
        const response = await authAxios.get('/reportes')
        
        console.log('📊 Respuesta completa de /reportes:', response.data)
        console.log('📊 Estructura de data:', response.data.data)
        console.log('📊 Tipo de data:', typeof response.data.data)
        
        // Si es paginación, los datos estarán en data.data
        if (response.data.data && Array.isArray(response.data.data)) {
          savedReports.value = response.data.data.map(reporte => ({
            id: reporte.id,
            title: reporte.nombre,
            selectedMetrics: reporte.parametros?.metricas || [],
            chartType: reporte.configuracion?.chartType || 'bar',
            dateRange: {
              start: reporte.parametros?.fecha_inicio || dateRange.value.start,
              end: reporte.parametros?.fecha_fin || dateRange.value.end
            },
            createdAt: reporte.created_at,
            metricsCount: reporte.parametros?.metricas?.length || 0,
            data: reporte.ejecuciones?.[0]?.resultados || null
          }))
          console.log('✅ Reportes cargados de BD:', savedReports.value.length)
        } 
        // Si Laravel retorna paginación, los datos podrían estar en data.data.data
        else if (response.data.data && response.data.data.data && Array.isArray(response.data.data.data)) {
          savedReports.value = response.data.data.data.map(reporte => ({
            id: reporte.id,
            title: reporte.nombre,
            selectedMetrics: reporte.parametros?.metricas || [],
            chartType: reporte.configuracion?.chartType || 'bar',
            dateRange: {
              start: reporte.parametros?.fecha_inicio || dateRange.value.start,
              end: reporte.parametros?.fecha_fin || dateRange.value.end
            },
            createdAt: reporte.created_at,
            metricsCount: reporte.parametros?.metricas?.length || 0,
            data: reporte.ejecuciones?.[0]?.resultados || null
          }))
          console.log('✅ Reportes cargados de BD (paginados):', savedReports.value.length)
        }
        // Si la respuesta es directamente un array
        else if (Array.isArray(response.data)) {
          savedReports.value = response.data.map(reporte => ({
            id: reporte.id,
            title: reporte.nombre,
            selectedMetrics: reporte.parametros?.metricas || [],
            chartType: reporte.configuracion?.chartType || 'bar',
            dateRange: {
              start: reporte.parametros?.fecha_inicio || dateRange.value.start,
              end: reporte.parametros?.fecha_fin || dateRange.value.end
            },
            createdAt: reporte.created_at,
            metricsCount: reporte.parametros?.metricas?.length || 0,
            data: reporte.ejecuciones?.[0]?.resultados || null
          }))
          console.log('✅ Reportes cargados de BD (array directo):', savedReports.value.length)
        }
        else {
          console.warn('⚠️ Formato de respuesta no reconocido:', response.data)
          savedReports.value = []
        }
        
      } catch (error) {
        console.error('❌ Error obteniendo reportes:', error)
        if (error.response) {
          console.error('📊 Respuesta de error:', error.response.data)
        }
      }
    }
    
    // Inicialización
    onMounted(() => {
      // Verificar autenticación al cargar el componente
      if (isAuthenticated.value) {
        fetchMetricasDisponibles()
        fetchSavedReports()
        
        // Cargar reportes locales solo si no hay de BD
        const saved = localStorage.getItem('savedReports')
        if (saved) {
          localReports.value = JSON.parse(saved)
          
          // Filtrar para no mostrar IDs inválidos
          localReports.value = localReports.value.filter(report => 
            report.id < 9999999999 // Solo IDs que podrían ser de BD
          )
        }
      } else {
        authorizationError.value = true
        errorMessage.value = 'Debes iniciar sesión para acceder a los reportes.'
      }
    })
    
    return {
      // Auth
      isAuthenticated,
      user,
      authStatus,
      authorizationError,
      errorMessage,
      
      // Data
      reportTitle,
      reportData,
      isGenerating,
      newMetric,
      availableMetrics,
      selectedMetrics,
      chartTypes,
      selectedChartType,
      dateRange,
      segmentation,
      segmentationOptions,
      comparisonPeriod,
      dynamicFilters,
      savedReports,
      
      // Computed
      canGenerate,
      
      // Methods
      getMetricName,
      getChartTypeName,
      toggleMetric,
      addMetric,
      generateReport,
      addCustomFilter,
      removeFilter,
      exportCurrentReport,
      exportReport,
      deleteReport,
      getSegmentationLabel,
      getComparisonLabel,
      formatDate
    }
  }
}
</script>
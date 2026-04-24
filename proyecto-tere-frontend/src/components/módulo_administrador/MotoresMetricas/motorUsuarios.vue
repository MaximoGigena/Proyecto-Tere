<!-- Motor de Métricas de Usuarios - Versión Mejorada con Granularidad -->
<template>
  <div class="metricas-contenido">
    <!-- Loading -->
    <div v-if="cargando" class="fixed top-0 left-0 w-full h-full bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center">
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-700">Cargando métricas...</p>
      </div>
    </div>

    <!-- Mensaje de error -->
    <div v-if="error" class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-red-700">{{ error }}</p>
          <button @click="intentarCargarDatos" class="text-sm text-red-600 hover:text-red-800 font-medium mt-1">
            Reintentar →
          </button>
        </div>
      </div>
    </div>

    <div class="filtros flex flex-col sm:flex-row gap-3">
        <select 
          v-model="reporteSeleccionado" 
          @change="cambiarReporte"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-700"
        >
          <option value="volumen">Volumen de Usuarios</option>
          <option value="crecimiento">Crecimiento</option>
          <option value="actividad">Actividad</option>
          <option value="comportamiento">Comportamiento</option>
          <option value="calidad">Calidad/Estado</option>
        </select>
        
        <button 
          @click="exportarDatos"
          class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition duration-200 flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Exportar CSV
        </button>
      </div>

    <!-- Filtros avanzados -->
    <div class="filtros-avanzados bg-white rounded-xl shadow-sm p-6 mb-8">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Filtros</h3>
        <button 
          @click="mostrarFiltrosAvanzados = !mostrarFiltrosAvanzados"
          class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1"
        >
          {{ mostrarFiltrosAvanzados ? 'Ocultar' : 'Mostrar' }} filtros avanzados
          <svg class="w-4 h-4" :class="{ 'rotate-180': mostrarFiltrosAvanzados }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
      </div>

      <div v-if="mostrarFiltrosAvanzados" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Fecha desde -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha desde</label>
          <input 
            type="date" 
            v-model="filtros.fecha_desde"
            @change="aplicarFiltros"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <!-- Fecha hasta -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha hasta</label>
          <input 
            type="date" 
            v-model="filtros.fecha_hasta"
            @change="aplicarFiltros"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <!-- Tipo de usuario -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de usuario</label>
          <select 
            v-model="filtros.tipo_usuario"
            @change="aplicarFiltros"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
          >
            <option value="">Todos</option>
            <option value="usuario">Usuario</option>
            <option value="veterinario">Veterinario</option>
            <option value="admin">Administrador</option>
          </select>
        </div>

        <!-- Estado -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
          <select 
            v-model="filtros.estado"
            @change="aplicarFiltros"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
          >
            <option value="">Todos</option>
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
            <option value="bloqueado">Bloqueado</option>
            <option value="verificado">Verificado</option>
            <option value="no_verificado">No verificado</option>
          </select>
        </div>

        <!-- Agrupación -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Agrupación</label>
          <select 
            v-model="filtros.agrupacion"
            @change="aplicarFiltros"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
          >
            <option value="diaria">Diaria</option>
            <option value="semanal">Semanal</option>
            <option value="mensual">Mensual</option>
            <option value="trimestral">Trimestral</option>
            <option value="anual">Anual</option>
          </select>
        </div>

        <!-- Granularidad (NUEVO) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Granularidad</label>
          <select 
            v-model="filtros.granularidad"
            @change="aplicarFiltros"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
          >
            <option value="dia">Por día</option>
            <option value="semana">Por semana</option>
            <option value="mes">Por mes</option>
            <option value="tipo_usuario">Por tipo de usuario</option>
            <option value="ubicacion">Por ubicación</option>
          </select>
        </div>

        <!-- Botones de acción -->
        <div class="flex items-end gap-2">
          <button 
            @click="aplicarFiltros"
            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200"
          >
            Aplicar
          </button>
          <button 
            @click="limpiarFiltros"
            class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition duration-200"
          >
            Limpiar
          </button>
        </div>
      </div>
    </div>

    <!-- Resumen de KPIs según el reporte seleccionado (mejorado con click para desglose) -->
    <div class="kpis-resumen grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div 
        v-for="kpi in kpisActuales" 
        :key="kpi.id" 
        class="kpi-tarjeta bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 p-6 border border-gray-100 cursor-pointer"
        :class="{ 
          'border-l-4 border-l-green-500': kpi.tendencia > 0,
          'border-l-4 border-l-red-500': kpi.tendencia < 0,
          'border-l-4 border-l-gray-300': kpi.tendencia === 0,
          'hover:scale-105': kpi.desglose
        }"
        @click="mostrarDesgloseKPI(kpi)"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-500 mb-1">{{ kpi.titulo }}</p>
            <div class="flex items-end gap-2">
              <p class="text-3xl font-bold text-gray-800">{{ formatearNumero(kpi.valor) }}</p>
              <div 
                v-if="kpi.tendencia !== null"
                class="flex items-center text-sm font-medium"
                :class="kpi.tendencia > 0 ? 'text-green-600' : 'text-red-600'"
              >
                <svg 
                  class="w-4 h-4 mr-1" 
                  :class="kpi.tendencia > 0 ? 'rotate-0' : 'rotate-180'"
                  fill="none" 
                  stroke="currentColor" 
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                {{ Math.abs(kpi.tendencia) }}%
              </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ kpi.descripcion }}</p>
            <!-- Indicador de desglose -->
            <div v-if="kpi.desglose" class="mt-2">
              <span class="text-xs text-blue-600 hover:text-blue-800">
                Click para ver detalles →
              </span>
            </div>
          </div>
          
          <div class="kpi-icono ml-4">
            <div 
              class="p-3 rounded-lg"
              :class="kpi.colorClase"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="kpi.icono === 'usuarios'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 3.75l-5.5 5.5-2.5-2.5"/>
                <path v-if="kpi.icono === 'crecimiento'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                <path v-if="kpi.icono === 'actividad'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                <path v-if="kpi.icono === 'comportamiento'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                <path v-if="kpi.icono === 'calidad'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráficos principales -->
    <div class="metricas-graficos grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Gráfico de evolución -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-800">{{ tituloGraficoEvolucion }}</h3>
          <button 
            @click="abrirModal('evolucion')"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
          >
            Ver detalles →
          </button>
        </div>
        <div class="h-80">
          <canvas ref="graficoEvolucion"></canvas>
        </div>
      </div>

      <!-- Gráfico de distribución -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-800">{{ tituloGraficoDistribucion }}</h3>
          <button 
            @click="abrirModal('distribucion')"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
          >
            Ver detalles →
          </button>
        </div>
        <div class="h-80">
          <canvas ref="graficoDistribucion"></canvas>
        </div>
      </div>
    </div>

    <!-- Tabla de datos detallados -->
    <div class="metricas-detalle bg-white rounded-xl shadow-sm p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-semibold text-gray-800">{{ tituloTabla }}</h3>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-500">
            {{ datosFiltrados.length }} registros encontrados
          </span>
        </div>
      </div>

      <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th 
                v-for="columna in columnasTablaActuales" 
                :key="columna.key"
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                <div class="flex items-center gap-1">
                  {{ columna.label }}
                  <button 
                    v-if="columna.ordenable" 
                    @click="ordenarPor(columna.key)"
                    class="p-1 hover:bg-gray-100 rounded"
                  >
                    <svg 
                      class="w-4 h-4" 
                      :class="{
                        'text-blue-600': orden.por === columna.key,
                        'text-gray-400': orden.por !== columna.key
                      }"
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path 
                        v-if="orden.por === columna.key && orden.direccion === 'asc'"
                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M5 15l7-7 7 7"
                      />
                      <path 
                        v-if="orden.por === columna.key && orden.direccion === 'desc'"
                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M19 9l-7 7-7-7"
                      />
                      <path 
                        v-if="orden.por !== columna.key"
                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                      />
                    </svg>
                  </button>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="item in datosPaginados" 
              :key="item.id || item.fecha || item.periodo"
              class="hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
              @click="seleccionarItem(item)"
            >
              <td 
                v-for="columna in columnasTablaActuales" 
                :key="columna.key"
                class="px-6 py-4 whitespace-nowrap text-sm"
                :class="columna.colorClase ? columna.colorClase(item[columna.key]) : 'text-gray-900'"
              >
                <template v-if="columna.formato === 'fecha'">
                  {{ formatearFecha(item[columna.key]) }}
                </template>
                <template v-else-if="columna.formato === 'porcentaje'">
                  {{ item[columna.key] }}%
                </template>
                <template v-else-if="columna.formato === 'estado'">
                  <span 
                    class="inline-flex px-3 py-1 rounded-full text-xs font-semibold"
                    :class="getEstadoClase(item[columna.key])"
                  >
                    {{ item[columna.key] }}
                  </span>
                </template>
                <template v-else>
                  {{ item[columna.key] }}
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="totalPaginas > 1" class="flex items-center justify-between mt-6 px-4">
        <div class="text-sm text-gray-700">
          Mostrando 
          <span class="font-medium">{{ ((paginaActual - 1) * itemsPorPagina) + 1 }}</span> 
          a 
          <span class="font-medium">{{ Math.min(paginaActual * itemsPorPagina, datosFiltrados.length) }}</span> 
          de 
          <span class="font-medium">{{ datosFiltrados.length }}</span> 
          resultados
        </div>
        
        <div class="flex gap-2">
          <button 
            @click="paginaAnterior"
            :disabled="paginaActual === 1"
            class="px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Anterior
          </button>
          
          <div class="flex items-center gap-1">
            <button 
              v-for="page in paginasVisibles" 
              :key="page"
              @click="irAPagina(page)"
              class="w-8 h-8 flex items-center justify-center text-sm font-medium rounded-md transition-colors"
              :class="{
                'bg-blue-600 text-white': paginaActual === page,
                'text-gray-700 hover:bg-gray-100': paginaActual !== page
              }"
            >
              {{ page }}
            </button>
            <span v-if="totalPaginas > 5 && paginaActual < totalPaginas - 2" class="px-2">...</span>
            <button 
              v-if="totalPaginas > 5 && paginaActual < totalPaginas - 2"
              @click="irAPagina(totalPaginas)"
              class="w-8 h-8 flex items-center justify-center text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md transition-colors"
            >
              {{ totalPaginas }}
            </button>
          </div>
          
          <button 
            @click="paginaSiguiente"
            :disabled="paginaActual === totalPaginas"
            class="px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de detalles MEJORADO con desgloses granulares -->
    <div v-if="mostrarModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
          <div class="absolute inset-0 bg-gray-500 opacity-75" @click="cerrarModal"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ modalTitulo }}
                  </h3>
                  <button 
                    @click="cerrarModal"
                    class="text-gray-400 hover:text-gray-500 focus:outline-none"
                  >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>
                <div class="mt-2 max-h-96 overflow-y-auto">
                  <!-- Desglose mejorado para KPIs -->
                  <div v-if="itemSeleccionado && modalTipo === 'kpi_detalle'" class="space-y-6">
                    <!-- Por tipo de usuario -->
                    <div v-if="itemSeleccionado.por_tipo" class="bg-gray-50 rounded-lg p-4">
                      <h4 class="font-semibold text-gray-800 mb-3">Distribución por Tipo de Usuario</h4>
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="(count, tipo) in itemSeleccionado.por_tipo" :key="tipo" 
                             class="bg-white rounded-lg p-3 text-center shadow-sm">
                          <p class="text-sm text-gray-500 capitalize">{{ tipo }}</p>
                          <p class="text-2xl font-bold text-gray-800">{{ formatearNumero(count) }}</p>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Por estado -->
                    <div v-if="itemSeleccionado.por_estado" class="bg-gray-50 rounded-lg p-4">
                      <h4 class="font-semibold text-gray-800 mb-3">Distribución por Estado</h4>
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="(count, estado) in itemSeleccionado.por_estado" :key="estado" 
                             class="bg-white rounded-lg p-3 text-center shadow-sm">
                          <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold" 
                                :class="getEstadoClase(estado)">
                            {{ estado }}
                          </span>
                          <p class="text-2xl font-bold text-gray-800 mt-2">{{ formatearNumero(count) }}</p>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Por fuente de registro -->
                    <div v-if="itemSeleccionado.por_fuente" class="bg-gray-50 rounded-lg p-4">
                      <h4 class="font-semibold text-gray-800 mb-3">Registro por Fuente</h4>
                      <div class="space-y-3">
                        <div v-for="(count, fuente) in itemSeleccionado.por_fuente" :key="fuente" class="flex items-center justify-between">
                          <span class="text-sm text-gray-600 capitalize">{{ fuente }}</span>
                          <div class="flex-1 mx-4">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                              <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" 
                                   :style="{ width: `${(count / totalFuentes) * 100}%` }"></div>
                            </div>
                          </div>
                          <span class="text-sm font-semibold text-gray-800">{{ formatearNumero(count) }}</span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Datos adicionales -->
                    <div v-if="itemSeleccionado.datos_adicionales" class="bg-gray-50 rounded-lg p-4">
                      <h4 class="font-semibold text-gray-800 mb-3">Información Adicional</h4>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="(value, key) in itemSeleccionado.datos_adicionales" :key="key" 
                             class="bg-white rounded-lg p-3 shadow-sm">
                          <p class="text-xs font-medium text-gray-500 uppercase">{{ key }}</p>
                          <p class="text-lg font-semibold text-gray-800">{{ value }}</p>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Tabla de datos detallados -->
                    <div v-if="itemSeleccionado.datos" class="bg-gray-50 rounded-lg p-4">
                      <h4 class="font-semibold text-gray-800 mb-3">Datos Detallados</h4>
                      <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                          <thead class="bg-gray-100">
                            <tr>
                              <th v-for="col in Object.keys(itemSeleccionado.datos[0] || {})" 
                                  :key="col"
                                  class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                {{ col }}
                              </th>
                            </tr>
                          </thead>
                          <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(row, idx) in itemSeleccionado.datos" :key="idx" class="hover:bg-gray-50">
                              <td v-for="col in Object.keys(row)" :key="col" 
                                  class="px-4 py-2 text-sm text-gray-900">
                                {{ row[col] }}
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Vista normal de detalles de tabla -->
                  <div v-else-if="itemSeleccionado && modalTipo === 'detalle'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="(value, key) in itemSeleccionado" :key="key" class="bg-gray-50 p-3 rounded-lg">
                      <p class="text-xs font-medium text-gray-500 uppercase">{{ key }}</p>
                      <p class="text-sm text-gray-900 mt-1">{{ value }}</p>
                    </div>
                  </div>
                  
                  <div v-else class="text-sm text-gray-500">
                    Información detallada de las métricas {{ reporteSeleccionado }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button 
              type="button" 
              @click="cerrarModal"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Chart, registerables } from 'chart.js';
import axios from 'axios';

Chart.register(...registerables);

export default {
  name: 'MetricasUsuarioContenido',
  
  props: {
    reporteInicial: {
      type: String,
      default: 'volumen'
    }
  },

  data() {
    const fechaHoy = new Date();
    const fechaHasta = fechaHoy.toISOString().split('T')[0];
    const fechaDesde = new Date(fechaHoy.setMonth(fechaHoy.getMonth() - 1)).toISOString().split('T')[0];

    return {
      reporteSeleccionado: this.reporteInicial,
      mostrarFiltrosAvanzados: true,
      cargando: false,
      error: null,
      metadatos: null,
      filtros: {
        fecha_desde: fechaDesde,
        fecha_hasta: fechaHasta,
        tipo_usuario: '',
        estado: '',
        agrupacion: 'mensual',
        granularidad: 'dia',
        reporte: this.reporteInicial
      },
      
      // Datos para diferentes reportes
      metricasVolumen: [],
      metricasCrecimiento: [],
      metricasActividad: [],
      metricasComportamiento: [],
      metricasCalidad: [],
      
      // KPIs para cada reporte
      kpisVolumen: [],
      kpisCrecimiento: [],
      kpisActividad: [],
      kpisComportamiento: [],
      kpisCalidad: [],
      
      columnasVolumen: [
        { key: 'fecha', label: 'Fecha', ordenable: true, formato: 'fecha' },
        { key: 'total_usuarios', label: 'Total Usuarios', ordenable: true },
        { key: 'usuarios', label: 'Usuarios', ordenable: true },
        { key: 'veterinarios', label: 'Veterinarios', ordenable: true },
        { key: 'admins', label: 'Administradores', ordenable: true },
        { key: 'activos', label: 'Activos', ordenable: true },
        { key: 'verificados', label: 'Verificados', ordenable: true }
      ],
      
      columnasCrecimiento: [
        { key: 'periodo', label: 'Período', ordenable: true },
        { key: 'nuevos_usuarios', label: 'Nuevos Usuarios', ordenable: true },
        { key: 'tasa_crecimiento', label: 'Tasa Crecimiento', ordenable: true, formato: 'porcentaje' },
        { key: 'variacion', label: 'Variación', ordenable: true, formato: 'porcentaje' }
      ],
      
      columnasActividad: [
        { key: 'fecha', label: 'Fecha', ordenable: true, formato: 'fecha' },
        { key: 'dau', label: 'DAU', ordenable: true },
        { key: 'mau', label: 'MAU', ordenable: true },
        { key: 'ratio_dau_mau', label: 'DAU/MAU', ordenable: true, formato: 'porcentaje' },
        { key: 'inactivos_30d', label: 'Inactivos >30d', ordenable: true },
        { key: 'acciones_promedio', label: 'Acciones/Promedio', ordenable: true }
      ],
      
      columnasComportamiento: [
        { key: 'segmento', label: 'Segmento', ordenable: true },
        { key: 'publican_mascotas', label: 'Publican Mascotas', ordenable: true, formato: 'porcentaje' },
        { key: 'perfil_completo', label: 'Perfil Completo', ordenable: true, formato: 'porcentaje' },
        { key: 'llegan_adopcion', label: 'Llegan a Adopción', ordenable: true, formato: 'porcentaje' },
        { key: 'funnel_registro', label: 'Funnel Registro', ordenable: true, formato: 'porcentaje' },
        { key: 'funnel_adopcion', label: 'Funnel Adopción', ordenable: true, formato: 'porcentaje' }
      ],
      
      columnasCalidad: [
        { key: 'categoria', label: 'Categoría', ordenable: true },
        { key: 'cantidad', label: 'Cantidad', ordenable: true },
        { key: 'porcentaje', label: 'Porcentaje', ordenable: true, formato: 'porcentaje' },
        { key: 'tendencia', label: 'Tendencia', ordenable: true, formato: 'porcentaje' },
        { key: 'estado', label: 'Estado', ordenable: true, formato: 'estado' }
      ],

      orden: {
        por: 'fecha',
        direccion: 'desc'
      },
      paginaActual: 1,
      itemsPorPagina: 10,
      mostrarModal: false,
      modalTipo: '',
      modalTitulo: '',
      graficoEvolucion: null,
      graficoDistribucion: null,
      itemSeleccionado: null,
      totalRegistros: 0,
      totalPaginas: 0,
      
      refreshInterval: null
    };
  },

  computed: {
    kpisActuales() {
      switch(this.reporteSeleccionado) {
        case 'volumen': return this.kpisVolumen;
        case 'crecimiento': return this.kpisCrecimiento;
        case 'actividad': return this.kpisActividad;
        case 'comportamiento': return this.kpisComportamiento;
        case 'calidad': return this.kpisCalidad;
        default: return this.kpisVolumen;
      }
    },

    datosActuales() {
      switch(this.reporteSeleccionado) {
        case 'volumen': return this.metricasVolumen;
        case 'crecimiento': return this.metricasCrecimiento;
        case 'actividad': return this.metricasActividad;
        case 'comportamiento': return this.metricasComportamiento;
        case 'calidad': return this.metricasCalidad;
        default: return this.metricasVolumen;
      }
    },

    columnasTablaActuales() {
      switch(this.reporteSeleccionado) {
        case 'volumen': return this.columnasVolumen;
        case 'crecimiento': return this.columnasCrecimiento;
        case 'actividad': return this.columnasActividad;
        case 'comportamiento': return this.columnasComportamiento;
        case 'calidad': return this.columnasCalidad;
        default: return this.columnasVolumen;
      }
    },

    tituloGraficoEvolucion() {
      const titulos = {
        volumen: 'Evolución de Usuarios',
        crecimiento: 'Crecimiento de Usuarios',
        actividad: 'Actividad Diaria (DAU/MAU)',
        comportamiento: 'Funnel de Conversión',
        calidad: 'Tendencia de Estados'
      };
      return titulos[this.reporteSeleccionado] || 'Evolución';
    },

    tituloGraficoDistribucion() {
      const titulos = {
        volumen: 'Distribución por Tipo',
        crecimiento: 'Comparativa Períodos',
        actividad: 'Distribución de Actividad',
        comportamiento: 'Segmentos de Comportamiento',
        calidad: 'Distribución de Estados'
      };
      return titulos[this.reporteSeleccionado] || 'Distribución';
    },

    tituloTabla() {
      const titulos = {
        volumen: 'Detalle de Volumen por Período',
        crecimiento: 'Detalle de Crecimiento',
        actividad: 'Detalle de Actividad',
        comportamiento: 'Detalle de Comportamiento',
        calidad: 'Detalle de Calidad/Estado'
      };
      return titulos[this.reporteSeleccionado] || 'Detalles';
    },

    datosOrdenados() {
      if (!this.datosActuales.length) return [];
      return [...this.datosActuales].sort((a, b) => {
        let valorA = a[this.orden.por];
        let valorB = b[this.orden.por];

        if (this.orden.por === 'fecha' && valorA && valorB) {
          valorA = new Date(valorA);
          valorB = new Date(valorB);
        }

        if (valorA < valorB) return this.orden.direccion === 'asc' ? -1 : 1;
        if (valorA > valorB) return this.orden.direccion === 'asc' ? 1 : -1;
        return 0;
      });
    },

    datosFiltrados() {
      if (!this.datosOrdenados.length) return [];
      return this.datosOrdenados.filter(item => {
        if (this.filtros.tipo_usuario && item.tipo_usuario !== this.filtros.tipo_usuario) {
          return false;
        }
        if (this.filtros.estado && item.estado !== this.filtros.estado) {
          return false;
        }
        if (item.fecha) {
          const fechaItem = new Date(item.fecha);
          const fechaDesde = new Date(this.filtros.fecha_desde);
          const fechaHasta = new Date(this.filtros.fecha_hasta);
          
          if (fechaItem < fechaDesde || fechaItem > fechaHasta) {
            return false;
          }
        }
        return true;
      });
    },

    datosPaginados() {
      const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
      const fin = inicio + this.itemsPorPagina;
      return this.datosFiltrados.slice(inicio, fin);
    },

    totalPaginas() {
      return Math.ceil(this.datosFiltrados.length / this.itemsPorPagina);
    },

    paginasVisibles() {
      const paginas = [];
      const maxVisible = 5;
      
      if (this.totalPaginas <= maxVisible) {
        for (let i = 1; i <= this.totalPaginas; i++) {
          paginas.push(i);
        }
      } else {
        let start = Math.max(1, this.paginaActual - 2);
        let end = Math.min(this.totalPaginas, start + maxVisible - 1);
        
        if (end - start + 1 < maxVisible) {
          start = Math.max(1, end - maxVisible + 1);
        }
        
        for (let i = start; i <= end; i++) {
          paginas.push(i);
        }
      }
      
      return paginas;
    },

    totalFuentes() {
      if (!this.itemSeleccionado?.por_fuente) return 0;
      return Object.values(this.itemSeleccionado.por_fuente).reduce((a, b) => a + b, 0);
    }
  },

  watch: {
    'reporteSeleccionado': {
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          this.paginaActual = 1;
          this.filtros.reporte = newVal;
          this.cargarDatos();
        }
      }
    }
  },

  async mounted() {
    try {
      console.log('🔐 Inicializando contenido de métricas...');
      
      this.$nextTick(() => {
        setTimeout(() => {
          this.inicializarGraficos();
          this.cargarDatos();
        }, 300);
      });
      
      this.iniciarActualizacionAutomatica();
    } catch (error) {
      console.error('Error en mounted():', error);
    }
  },

  beforeUnmount() {
    this.destruirGraficos();
    this.detenerActualizacionAutomatica();
  },

  methods: {
    getAuthHeaders() {
      try {
        const token = localStorage.getItem('token') || 
                      localStorage.getItem('access_token') ||
                      localStorage.getItem('auth_token');
        
        if (token) {
          console.log('🔐 Token obtenido de localStorage');
          return {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          };
        }
        
        console.warn('⚠️ No se encontró token en localStorage');
        return {};
        
      } catch (error) {
        console.error('❌ Error obteniendo headers:', error);
        return {};
      }
    },

    async manejarErrorAutenticacion(error) {
      console.error('🔑 Error de autenticación:', error?.response?.status || error.message);
      
      localStorage.removeItem('token');
      localStorage.removeItem('access_token');
      localStorage.removeItem('auth_token');
      
      this.redirigirALogin();
    },

    redirigirALogin() {
      localStorage.clear();
      sessionStorage.clear();
      
      if (window.location.pathname !== '/home') {
        window.location.href = '/home';
      }
    },

    async apiGet(endpoint, params = {}) {
      try {
        const headers = this.getAuthHeaders();
        
        if (!headers.Authorization) {
          throw new Error('No autenticado');
        }
        
        const response = await axios.get(endpoint, { 
          headers,
          params,
          timeout: 30000
        });
        
        if (response.data.success === false) {
          throw new Error(response.data.message || 'Error en la respuesta del servidor');
        }
        
        return response.data;
      } catch (error) {
        console.error(`Error en ${endpoint}:`, error);
        
        if (error.response?.status === 401) {
          await this.manejarErrorAutenticacion(error);
        }
        
        throw error;
      }
    },

    async cargarDatos() {
      if (this.cargando) return;
      
      this.cargando = true;
      this.error = null;
      
      try {
        console.log('📡 Cargando métricas granulares...');
        
        const params = {
          reporte: this.reporteSeleccionado,
          fecha_desde: this.filtros.fecha_desde,
          fecha_hasta: this.filtros.fecha_hasta,
          tipo_usuario: this.filtros.tipo_usuario,
          estado: this.filtros.estado,
          agrupacion: this.filtros.agrupacion,
          granularidad: this.filtros.granularidad
        };
        
        const response = await this.apiGet('/api/metricas/usuarios/granulares', params);
        
        if (response?.success) {
          this.actualizarDatosReporteGranular(response.data.metricas, response.data.kpis);
          this.metadatos = response.data.metadata;
          
          this.$nextTick(() => {
            setTimeout(() => this.actualizarGraficos(), 100);
          });
        } else {
          throw new Error(response?.message || 'Error al cargar datos');
        }
        
      } catch (error) {
        console.error('❌ Error cargando métricas:', error);
        this.error = error.message;
        
        if (process.env.NODE_ENV === 'development' && !this.datosActuales.length) {
          this.mostrarDatosDePrueba();
        }
      } finally {
        this.cargando = false;
      }
    },

    actualizarDatosReporteGranular(metricas, kpis) {
      const metricasCopy = JSON.parse(JSON.stringify(metricas || []));
      const kpisCopy = JSON.parse(JSON.stringify(kpis || []));
      
      switch(this.reporteSeleccionado) {
        case 'volumen':
          this.metricasVolumen = metricasCopy;
          this.kpisVolumen = this.mapearKPIsGranulares(kpisCopy, 'volumen');
          break;
        case 'crecimiento':
          this.metricasCrecimiento = metricasCopy;
          this.kpisCrecimiento = this.mapearKPIsGranulares(kpisCopy, 'crecimiento');
          break;
        case 'actividad':
          this.metricasActividad = metricasCopy;
          this.kpisActividad = this.mapearKPIsGranulares(kpisCopy, 'actividad');
          break;
        case 'comportamiento':
          this.metricasComportamiento = metricasCopy;
          this.kpisComportamiento = this.mapearKPIsGranulares(kpisCopy, 'comportamiento');
          break;
        case 'calidad':
          this.metricasCalidad = metricasCopy;
          this.kpisCalidad = this.mapearKPIsGranulares(kpisCopy, 'calidad');
          break;
      }
    },

    mapearKPIsGranulares(kpisBackend, tipo) {
      if (!kpisBackend || !kpisBackend.length) return [];
      
      return kpisBackend.map((kpi, index) => ({
        id: index + 1,
        titulo: kpi.titulo || kpi.nombre,
        valor: kpi.valor,
        tendencia: kpi.tendencia,
        descripcion: kpi.descripcion,
        icono: this.getIconoTipo(tipo),
        colorClase: this.getColorClase(index),
        desglose: kpi.desglose,
        detalleCompleto: kpi.detalleCompleto
      }));
    },

    mostrarDesgloseKPI(kpi) {
      if (kpi.desglose) {
        this.modalTitulo = `📊 Detalles: ${kpi.titulo}`;
        this.itemSeleccionado = kpi.desglose;
        this.modalTipo = 'kpi_detalle';
        this.mostrarModal = true;
      }
    },

    aplicarFiltros() {
      this.paginaActual = 1;
      this.cargarDatos();
    },

    limpiarFiltros() {
      const fechaHoy = new Date();
      const fechaHasta = fechaHoy.toISOString().split('T')[0];
      const fechaDesde = new Date(fechaHoy.setMonth(fechaHoy.getMonth() - 1)).toISOString().split('T')[0];
      
      this.filtros = {
        fecha_desde: fechaDesde,
        fecha_hasta: fechaHasta,
        tipo_usuario: '',
        estado: '',
        agrupacion: 'mensual',
        granularidad: 'dia',
        reporte: this.reporteSeleccionado
      };
      
      this.cargarDatos();
    },

    cambiarReporte() {
      this.paginaActual = 1;
      this.filtros.reporte = this.reporteSeleccionado;
      this.cargarDatos();
    },

    exportarDatos() {
      const datosExportar = this.datosFiltrados;
      const csv = this.convertirACSV(datosExportar);
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', `metricas-${this.reporteSeleccionado}-${this.filtros.fecha_desde}-${this.filtros.fecha_hasta}.csv`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    },

    convertirACSV(datos) {
      if (!datos.length) return '';
      const headers = this.columnasTablaActuales.map(col => col.label);
      const rows = datos.map(item => 
        this.columnasTablaActuales.map(col => item[col.key] || '')
      );
      return [headers, ...rows].map(row => row.join(',')).join('\n');
    },

    formatearNumero(numero) {
      if (numero === undefined || numero === null) return '0';
      return new Intl.NumberFormat('es-ES').format(numero);
    },

    formatearFecha(fecha) {
      if (!fecha) return '';
      return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    getEstadoClase(estado) {
      const clases = {
        'activo': 'bg-green-100 text-green-800',
        'inactivo': 'bg-gray-100 text-gray-800',
        'bloqueado': 'bg-red-100 text-red-800',
        'verificado': 'bg-blue-100 text-blue-800',
        'no_verificado': 'bg-yellow-100 text-yellow-800',
        'pendiente': 'bg-yellow-100 text-yellow-800',
        'aprobado': 'bg-green-100 text-green-800',
        'rechazado': 'bg-red-100 text-red-800'
      };
      return clases[estado] || 'bg-gray-100 text-gray-800';
    },

    seleccionarItem(item) {
      this.itemSeleccionado = item;
      this.modalTipo = 'detalle';
      this.modalTitulo = 'Detalles del Registro';
      this.abrirModal('detalle');
    },

    abrirModal(tipo) {
      this.mostrarModal = true;
      if (tipo !== 'kpi_detalle') {
        this.modalTipo = tipo;
        this.modalTitulo = this.tituloGraficoEvolucion;
      }
    },

    cerrarModal() {
      this.mostrarModal = false;
      this.modalTipo = '';
      this.modalTitulo = '';
      this.itemSeleccionado = null;
    },

    getIconoTipo(tipo) {
      const iconos = {
        volumen: 'usuarios',
        crecimiento: 'crecimiento',
        actividad: 'actividad',
        comportamiento: 'comportamiento',
        calidad: 'calidad'
      };
      return iconos[tipo] || 'usuarios';
    },

    getColorClase(index) {
      const colores = [
        'bg-blue-50 text-blue-600',
        'bg-green-50 text-green-600',
        'bg-purple-50 text-purple-600',
        'bg-orange-50 text-orange-600'
      ];
      return colores[index % colores.length];
    },

    inicializarGraficos() {
      console.log('📊 Inicializando gráficos...');
      
      this.destruirGraficos();
      
      if (!this.$refs.graficoEvolucion || !this.$refs.graficoDistribucion) {
        console.warn('⚠️ Refs de gráficos no disponibles, reintentando...');
        setTimeout(() => this.inicializarGraficos(), 100);
        return;
      }
      
      try {
        this.graficoEvolucion = new Chart(this.$refs.graficoEvolucion, {
          type: 'line',
          data: { labels: [], datasets: [] },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
          }
        });
      } catch (error) {
        console.error('❌ Error inicializando gráfico evolución:', error);
      }
      
      try {
        this.graficoDistribucion = new Chart(this.$refs.graficoDistribucion, {
          type: 'bar',
          data: { labels: [], datasets: [] },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
          }
        });
      } catch (error) {
        console.error('❌ Error inicializando gráfico distribución:', error);
      }
    },

    actualizarGraficos() {
      if (!this.graficoEvolucion || !this.graficoDistribucion) return;

      const datosActuales = this.datosActuales;
      if (!datosActuales.length) return;
      
      switch(this.reporteSeleccionado) {
        case 'volumen':
          this.actualizarGraficoVolumen(datosActuales);
          break;
        case 'crecimiento':
          this.actualizarGraficoCrecimiento(datosActuales);
          break;
        case 'actividad':
          this.actualizarGraficoActividad(datosActuales);
          break;
        case 'comportamiento':
          this.actualizarGraficoComportamiento(datosActuales);
          break;
        case 'calidad':
          this.actualizarGraficoCalidad(datosActuales);
          break;
      }
    },

    actualizarGraficoVolumen(datos) {
      const ultimos15 = datos.slice(-15);
      if (!ultimos15.length) return;
      
      this.graficoEvolucion.data.labels = ultimos15.map(d => 
        d.fecha ? new Date(d.fecha).toLocaleDateString('es-ES', { month: 'short', day: 'numeric' }) : ''
      );
      this.graficoEvolucion.data.datasets = [
        {
          label: 'Total Usuarios',
          data: ultimos15.map(d => d.total_usuarios || 0),
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          borderWidth: 2,
          tension: 0.4,
          fill: true
        },
        {
          label: 'Usuarios Activos',
          data: ultimos15.map(d => d.activos || 0),
          borderColor: 'rgb(16, 185, 129)',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          borderWidth: 2,
          tension: 0.4
        }
      ];
      
      const tipos = ['usuarios', 'veterinarios', 'admins'];
      const valores = tipos.map(tipo => {
        return datos.reduce((sum, d) => sum + (d[tipo] || 0), 0) / datos.length;
      });
      
      this.graficoDistribucion.data.labels = ['Usuarios', 'Veterinarios', 'Administradores'];
      this.graficoDistribucion.data.datasets = [{
        label: 'Promedio',
        data: valores,
        backgroundColor: ['rgba(59, 130, 246, 0.7)', 'rgba(16, 185, 129, 0.7)', 'rgba(139, 92, 246, 0.7)'],
        borderRadius: 6
      }];
      
      this.graficoEvolucion.update();
      this.graficoDistribucion.update();
    },

    actualizarGraficoCrecimiento(datos) {
      this.graficoEvolucion.data.labels = datos.map(d => d.periodo || '');
      this.graficoEvolucion.data.datasets = [
        {
          label: 'Nuevos Usuarios',
          data: datos.map(d => d.nuevos_usuarios || 0),
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          borderWidth: 2,
          tension: 0.4,
          fill: true
        }
      ];
      
      this.graficoDistribucion.data.labels = datos.map(d => d.periodo || '');
      this.graficoDistribucion.data.datasets = [{
        label: 'Variación %',
        data: datos.map(d => parseFloat(d.variacion || 0)),
        backgroundColor: datos.map(d => 
          parseFloat(d.variacion || 0) >= 0 ? 'rgba(16, 185, 129, 0.7)' : 'rgba(239, 68, 68, 0.7)'
        ),
        borderRadius: 6
      }];
      
      this.graficoEvolucion.update();
      this.graficoDistribucion.update();
    },

    actualizarGraficoActividad(datos) {
      const ultimos15 = datos.slice(-15);
      if (!ultimos15.length) return;
      
      this.graficoEvolucion.data.labels = ultimos15.map(d => 
        d.fecha ? new Date(d.fecha).toLocaleDateString('es-ES', { month: 'short', day: 'numeric' }) : ''
      );
      this.graficoEvolucion.data.datasets = [
        {
          label: 'DAU',
          data: ultimos15.map(d => d.dau || 0),
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          borderWidth: 2,
          tension: 0.4,
          fill: true
        },
        {
          label: 'MAU',
          data: ultimos15.map(d => d.mau || 0),
          borderColor: 'rgb(139, 92, 246)',
          backgroundColor: 'rgba(139, 92, 246, 0.1)',
          borderWidth: 2,
          tension: 0.4
        }
      ];
      
      this.graficoDistribucion.update();
      this.graficoEvolucion.update();
    },

    actualizarGraficoComportamiento(datos) {
      this.graficoDistribucion.data.labels = datos.map(d => d.segmento || '');
      this.graficoDistribucion.data.datasets = [{
        label: 'Tasa de Conversión',
        data: datos.map(d => d.llegan_adopcion || 0),
        backgroundColor: 'rgba(59, 130, 246, 0.7)',
        borderRadius: 6
      }];
      
      this.graficoEvolucion.data.labels = datos.map(d => d.segmento || '');
      this.graficoEvolucion.data.datasets = [{
        label: 'Funnel de Conversión',
        data: datos.map(d => d.funnel_adopcion || 0),
        borderColor: 'rgb(16, 185, 129)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        borderWidth: 2,
        tension: 0.4
      }];
      
      this.graficoEvolucion.update();
      this.graficoDistribucion.update();
    },

    actualizarGraficoCalidad(datos) {
      this.graficoDistribucion.data.labels = datos.map(d => d.categoria || '');
      this.graficoDistribucion.data.datasets = [{
        label: 'Porcentaje',
        data: datos.map(d => d.porcentaje || 0),
        backgroundColor: datos.map((_, i) => {
          const colores = ['rgba(59, 130, 246, 0.7)', 'rgba(16, 185, 129, 0.7)', 'rgba(239, 68, 68, 0.7)', 'rgba(245, 158, 11, 0.7)'];
          return colores[i % colores.length];
        }),
        borderRadius: 6
      }];
      
      this.graficoDistribucion.update();
    },

    destruirGraficos() {
      [this.graficoEvolucion, this.graficoDistribucion].forEach(grafico => {
        if (grafico) {
          grafico.destroy();
        }
      });
    },

    ordenarPor(columna) {
      if (this.orden.por === columna) {
        this.orden.direccion = this.orden.direccion === 'asc' ? 'desc' : 'asc';
      } else {
        this.orden.por = columna;
        this.orden.direccion = 'asc';
      }
    },

    paginaAnterior() {
      if (this.paginaActual > 1) {
        this.paginaActual--;
      }
    },

    paginaSiguiente() {
      if (this.paginaActual < this.totalPaginas) {
        this.paginaActual++;
      }
    },

    irAPagina(pagina) {
      this.paginaActual = pagina;
    },

    iniciarActualizacionAutomatica() {
      this.detenerActualizacionAutomatica();
      
      this.refreshInterval = setInterval(() => {
        console.log('🔄 Actualizando datos automáticamente...');
        this.cargarDatos();
      }, 600000);
    },

    detenerActualizacionAutomatica() {
      if (this.refreshInterval) {
        clearInterval(this.refreshInterval);
        this.refreshInterval = null;
      }
    },

    mostrarDatosDePrueba() {
      console.log('🔧 Generando datos de prueba...');
      
      const fecha = new Date();
      const datosPrueba = [];
      
      for (let i = 0; i < 15; i++) {
        const fechaItem = new Date(fecha);
        fechaItem.setDate(fechaItem.getDate() - i);
        
        datosPrueba.push({
          id: i + 1,
          fecha: fechaItem.toISOString().split('T')[0],
          total_usuarios: Math.floor(Math.random() * 1000) + 500,
          usuarios: Math.floor(Math.random() * 700) + 300,
          veterinarios: Math.floor(Math.random() * 100) + 50,
          admins: Math.floor(Math.random() * 10) + 5,
          activos: Math.floor(Math.random() * 800) + 400,
          verificados: Math.floor(Math.random() * 600) + 300
        });
      }
      
      this.metricasVolumen = datosPrueba;
      
      this.kpisVolumen = [
        {
          id: 1,
          titulo: 'Total Usuarios',
          valor: Math.round(datosPrueba.reduce((sum, item) => sum + item.total_usuarios, 0) / datosPrueba.length),
          tendencia: 12.5,
          descripcion: 'Usuarios totales en promedio',
          icono: 'usuarios',
          colorClase: 'bg-blue-50 text-blue-600',
          desglose: {
            por_tipo: {
              usuarios: 450,
              veterinarios: 75,
              administradores: 8
            },
            por_estado: {
              activo: 420,
              inactivo: 85,
              bloqueado: 28
            }
          }
        },
        {
          id: 2,
          titulo: 'Usuarios Activos',
          valor: Math.round(datosPrueba.reduce((sum, item) => sum + item.activos, 0) / datosPrueba.length),
          tendencia: 8.3,
          descripcion: 'Usuarios activos diariamente',
          icono: 'actividad',
          colorClase: 'bg-green-50 text-green-600'
        },
        {
          id: 3,
          titulo: 'Nuevos Registros',
          valor: Math.floor(Math.random() * 50) + 20,
          tendencia: 15.2,
          descripcion: 'Nuevos usuarios por día',
          icono: 'crecimiento',
          colorClase: 'bg-purple-50 text-purple-600'
        },
        {
          id: 4,
          titulo: 'Tasa Verificación',
          valor: Math.floor(Math.random() * 30) + 60,
          tendencia: 3.8,
          descripcion: 'Porcentaje de usuarios verificados',
          icono: 'calidad',
          colorClase: 'bg-orange-50 text-orange-600'
        }
      ];
      
      setTimeout(() => {
        this.actualizarGraficos();
      }, 500);
    },

    intentarCargarDatos() {
      this.error = null;
      this.cargarDatos();
    }
  }
};
</script>

<style scoped>
.kpi-tarjeta {
  transition: all 0.2s ease;
}

.kpi-tarjeta:hover {
  transform: translateY(-2px);
}

.rotate-180 {
  transform: rotate(180deg);
}

.cursor-pointer {
  cursor: pointer;
}

/* Animación para las barras de progreso */
.bg-blue-600 {
  transition: width 0.5s ease-in-out;
}
</style>
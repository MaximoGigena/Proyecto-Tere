<template>
  <div class="metricas-veterinarios bg-gradient-to-br from-teal-50 to-blue-50 min-h-screen p-4 md:p-6">
    <!-- Header con controles principales -->
    <div class="metricas-header mb-8">
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ titulo }}</h1>
          <p class="text-gray-600 mt-2">Dashboard de gestión y métricas para veterinarios</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="relative">
            <input 
              type="date"
              v-model="fechaSeleccionada"
              @change="cambiarFecha"
              class="pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
            />
            <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          
          <select 
            v-model="clinicaSeleccionada"
            @change="cambiarClinica"
            class="px-4 py-2.5 bg-white border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
          >
            <option value="todas">Todas las clínicas</option>
            <option v-for="clinica in clinicas" :key="clinica.id" :value="clinica.id">
              {{ clinica.nombre }}
            </option>
          </select>
          
          <button 
            @click="exportarReporte"
            class="px-6 py-2.5 bg-gradient-to-r from-teal-600 to-blue-600 text-white font-semibold rounded-xl hover:from-teal-700 hover:to-blue-700 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Exportar
          </button>
        </div>
      </div>
      
      <!-- Tarjetas de resumen rápido -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 shadow-lg border-l-4 border-l-teal-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-gray-500">Consultas Hoy</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ metricas.consultasHoy }}</p>
            </div>
            <div class="p-3 bg-teal-50 rounded-xl">
              <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Promedio diario</span>
              <span class="font-semibold text-green-600">+12%</span>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
              <div class="bg-teal-600 h-2 rounded-full" style="width: 75%"></div>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 shadow-lg border-l-4 border-l-blue-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-gray-500">Ingresos Mensuales</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">${{ formatCurrency(metricas.ingresosMensuales) }}</p>
            </div>
            <div class="p-3 bg-blue-50 rounded-xl">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Vs. mes anterior</span>
              <span class="font-semibold text-green-600">+8.5%</span>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
              <div class="bg-blue-600 h-2 rounded-full" style="width: 85%"></div>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 shadow-lg border-l-4 border-l-purple-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-gray-500">Tasa de Ocupación</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ metricas.tasaOcupacion }}%</p>
            </div>
            <div class="p-3 bg-purple-50 rounded-xl">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
              </svg>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Capacidad utilizada</span>
              <span class="font-semibold text-yellow-600">-2%</span>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
              <div class="bg-purple-600 h-2 rounded-full" style="width: 92%"></div>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-2xl p-6 shadow-lg border-l-4 border-l-orange-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-gray-500">Clientes Nuevos</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ metricas.clientesNuevos }}</p>
            </div>
            <div class="p-3 bg-orange-50 rounded-xl">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
              </svg>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Este mes</span>
              <span class="font-semibold text-green-600">+24%</span>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
              <div class="bg-orange-600 h-2 rounded-full" style="width: 65%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráficos principales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Gráfico de consultas por veterinario -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900">Consultas por Veterinario</h3>
          <select 
            v-model="periodoConsultas"
            @change="actualizarGraficos"
            class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
          >
            <option value="semana">Esta semana</option>
            <option value="mes">Este mes</option>
            <option value="trimestre">Este trimestre</option>
          </select>
        </div>
        <div class="h-80">
          <canvas ref="graficoConsultas"></canvas>
        </div>
      </div>
      
      <!-- Gráfico de tipos de consulta -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900">Distribución por Tipo</h3>
          <button 
            @click="mostrarDetallesTipos"
            class="px-4 py-1.5 text-sm bg-teal-50 text-teal-700 hover:bg-teal-100 rounded-lg font-medium transition-colors"
          >
            Ver detalles
          </button>
        </div>
        <div class="h-80">
          <canvas ref="graficoTipos"></canvas>
        </div>
      </div>
    </div>

    <!-- Agenda y consultas próximas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <!-- Agenda del día -->
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900">Agenda del Día</h3>
          <div class="flex gap-2">
            <button 
              v-for="veterinario in veterinarios"
              :key="veterinario.id"
              @click="cambiarVeterinario(veterinario.id)"
              class="px-3 py-1.5 text-sm rounded-lg transition-colors"
              :class="{
                'bg-teal-100 text-teal-700': veterinarioSeleccionado === veterinario.id,
                'bg-gray-100 text-gray-700 hover:bg-gray-200': veterinarioSeleccionado !== veterinario.id
              }"
            >
              {{ veterinario.nombre.split(' ')[0] }}
            </button>
          </div>
        </div>
        
        <div class="space-y-4">
          <div 
            v-for="cita in agendaFiltrada"
            :key="cita.id"
            class="p-4 border rounded-xl hover:shadow-md transition-all"
            :class="{
              'border-blue-200 bg-blue-50': cita.tipo === 'Consulta',
              'border-yellow-200 bg-yellow-50': cita.tipo === 'Vacunación',
              'border-red-200 bg-red-50': cita.tipo === 'Emergencia',
              'border-green-200 bg-green-50': cita.tipo === 'Cirugía'
            }"
          >
            <div class="flex justify-between items-start mb-2">
              <div>
                <p class="font-semibold text-gray-900">{{ cita.mascota }}</p>
                <p class="text-sm text-gray-600">{{ cita.propietario }}</p>
              </div>
              <div class="text-right">
                <p class="font-medium text-gray-900">{{ cita.hora }}</p>
                <span class="inline-block px-2 py-1 text-xs font-medium rounded mt-1"
                  :class="{
                    'bg-blue-100 text-blue-800': cita.tipo === 'Consulta',
                    'bg-yellow-100 text-yellow-800': cita.tipo === 'Vacunación',
                    'bg-red-100 text-red-800': cita.tipo === 'Emergencia',
                    'bg-green-100 text-green-800': cita.tipo === 'Cirugía'
                  }"
                >
                  {{ cita.tipo }}
                </span>
              </div>
            </div>
            <div class="flex justify-between items-center mt-3">
              <span class="text-sm text-gray-500">{{ cita.motivo }}</span>
              <div class="flex gap-2">
                <button 
                  @click="iniciarConsulta(cita)"
                  class="px-3 py-1 text-sm font-medium text-teal-700 bg-teal-100 hover:bg-teal-200 rounded-lg transition-colors"
                >
                  Iniciar
                </button>
                <button 
                  @click="verHistorial(cita)"
                  class="px-3 py-1 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                >
                  Historial
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mt-6 pt-6 border-t border-gray-200">
          <button 
            @click="agregarCita"
            class="w-full px-4 py-3 border-2 border-dashed border-gray-300 text-gray-600 hover:text-gray-800 hover:border-gray-400 rounded-xl font-medium transition-colors flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Agregar Nueva Cita
          </button>
        </div>
      </div>
      
      <!-- Estadísticas rápidas -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Estadísticas Rápidas</h3>
        
        <div class="space-y-6">
          <div>
            <p class="text-sm font-medium text-gray-500 mb-2">Especies Atendidas</p>
            <div class="space-y-3">
              <div v-for="especie in estadisticas.especies" :key="especie.nombre">
                <div class="flex justify-between text-sm mb-1">
                  <span class="text-gray-700">{{ especie.nombre }}</span>
                  <span class="font-semibold">{{ especie.porcentaje }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="h-2 rounded-full"
                    :class="{
                      'bg-blue-500': especie.nombre === 'Perro',
                      'bg-teal-500': especie.nombre === 'Gato',
                      'bg-yellow-500': especie.nombre === 'Ave',
                      'bg-purple-500': especie.nombre === 'Exótico'
                    }"
                    :style="{ width: `${especie.porcentaje}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
          
          <div>
            <p class="text-sm font-medium text-gray-500 mb-2">Tiempo Promedio por Consulta</p>
            <div class="flex items-center gap-3">
              <div class="text-3xl font-bold text-gray-900">{{ estadisticas.tiempoPromedio }} min</div>
              <div class="text-sm text-green-600 font-semibold flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                -5%
              </div>
            </div>
          </div>
          
          <div>
            <p class="text-sm font-medium text-gray-500 mb-2">Satisfacción del Cliente</p>
            <div class="flex items-center gap-3">
              <div class="text-3xl font-bold text-gray-900">{{ estadisticas.satisfaccion }}%</div>
              <div class="flex">
                <svg v-for="i in 5" :key="i" class="w-5 h-5"
                  :class="i <= Math.ceil(estadisticas.satisfaccion / 20) ? 'text-yellow-400' : 'text-gray-300'"
                  fill="currentColor" viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top medicamentos y procedimientos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Medicamentos más recetados -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900">Medicamentos Más Recetados</h3>
          <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
            Este mes
          </span>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicamento</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tendencia</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="med in medicamentos" :key="med.id" class="hover:bg-gray-50">
                <td class="px-4 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                      :class="{
                        'bg-blue-50': med.tipo === 'Antibiótico',
                        'bg-green-50': med.tipo === 'Analgésico',
                        'bg-yellow-50': med.tipo === 'Vacuna',
                        'bg-red-50': med.tipo === 'Antiinflamatorio'
                      }"
                    >
                      <svg class="w-5 h-5"
                        :class="{
                          'text-blue-600': med.tipo === 'Antibiótico',
                          'text-green-600': med.tipo === 'Analgésico',
                          'text-yellow-600': med.tipo === 'Vacuna',
                          'text-red-600': med.tipo === 'Antiinflamatorio'
                        }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                      </svg>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ med.nombre }}</p>
                      <p class="text-xs text-gray-500">{{ med.tipo }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <span class="font-semibold text-gray-900">{{ med.cantidad }}</span>
                  <p class="text-xs text-gray-500">recetas</p>
                </td>
                <td class="px-4 py-4">
                  <div class="flex items-center"
                    :class="med.tendencia > 0 ? 'text-green-600' : 'text-red-600'"
                  >
                    <svg class="w-4 h-4 mr-1"
                      :class="med.tendencia > 0 ? 'rotate-0' : 'rotate-180'"
                      fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    {{ Math.abs(med.tendencia) }}%
                  </div>
                </td>
                <td class="px-4 py-4">
                  <div class="flex items-center gap-2">
                    <div class="w-24 bg-gray-200 rounded-full h-2">
                      <div 
                        class="h-2 rounded-full"
                        :class="{
                          'bg-green-500': med.stock >= 70,
                          'bg-yellow-500': med.stock >= 30 && med.stock < 70,
                          'bg-red-500': med.stock < 30
                        }"
                        :style="{ width: `${med.stock}%` }"
                      ></div>
                    </div>
                    <span class="text-sm font-medium"
                      :class="{
                        'text-green-600': med.stock >= 70,
                        'text-yellow-600': med.stock >= 30 && med.stock < 70,
                        'text-red-600': med.stock < 30
                      }"
                    >
                      {{ med.stock }}%
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Procedimientos realizados -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900">Procedimientos Realizados</h3>
          <select 
            v-model="periodoProcedimientos"
            @change="actualizarProcedimientos"
            class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
          >
            <option value="mes">Este mes</option>
            <option value="trimestre">Este trimestre</option>
            <option value="anio">Este año</option>
          </select>
        </div>
        
        <div class="space-y-4">
          <div 
            v-for="proc in procedimientos"
            :key="proc.id"
            class="p-4 border rounded-xl hover:shadow-sm transition-all"
          >
            <div class="flex justify-between items-start mb-3">
              <div>
                <p class="font-semibold text-gray-900">{{ proc.nombre }}</p>
                <p class="text-sm text-gray-500">{{ proc.descripcion }}</p>
              </div>
              <span class="px-3 py-1 text-sm font-semibold rounded-lg"
                :class="{
                  'bg-blue-100 text-blue-800': proc.categoria === 'Rutina',
                  'bg-purple-100 text-purple-800': proc.categoria === 'Diagnóstico',
                  'bg-red-100 text-red-800': proc.categoria === 'Cirugía',
                  'bg-green-100 text-green-800': proc.categoria === 'Preventivo'
                }"
              >
                {{ proc.categoria }}
              </span>
            </div>
            
            <div class="flex justify-between items-center">
              <div>
                <p class="text-sm text-gray-500">Realizados</p>
                <p class="text-xl font-bold text-gray-900">{{ proc.cantidad }}</p>
              </div>
              
              <div>
                <p class="text-sm text-gray-500">Ingreso promedio</p>
                <p class="text-xl font-bold text-gray-900">${{ formatCurrency(proc.ingresoPromedio) }}</p>
              </div>
              
              <div class="text-right">
                <p class="text-sm text-gray-500">Tiempo promedio</p>
                <p class="text-xl font-bold text-gray-900">{{ proc.tiempoPromedio }} min</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para nueva cita -->
    <div v-if="modalNuevaCita" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
        <div class="fixed inset-0 transition-opacity" @click="cerrarModal">
          <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
          <div class="bg-white px-6 pt-6 pb-4">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-2xl font-bold text-gray-900">Nueva Cita</h3>
              <button @click="cerrarModal" class="text-gray-400 hover:text-gray-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="guardarNuevaCita" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Veterinario</label>
                  <select 
                    v-model="nuevaCita.veterinarioId"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                    required
                  >
                    <option v-for="vet in veterinarios" :key="vet.id" :value="vet.id">
                      {{ vet.nombre }}
                    </option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Fecha y Hora</label>
                  <input 
                    v-model="nuevaCita.fechaHora"
                    type="datetime-local"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                    required
                  />
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Mascota</label>
                  <input 
                    v-model="nuevaCita.mascota"
                    type="text"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                    placeholder="Ej: Max"
                    required
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Especie</label>
                  <select 
                    v-model="nuevaCita.especie"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                  >
                    <option value="Perro">Perro</option>
                    <option value="Gato">Gato</option>
                    <option value="Ave">Ave</option>
                    <option value="Exótico">Exótico</option>
                  </select>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Propietario</label>
                <input 
                  v-model="nuevaCita.propietario"
                  type="text"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                  placeholder="Nombre completo del dueño"
                  required
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Consulta</label>
                <select 
                  v-model="nuevaCita.tipo"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                >
                  <option value="Consulta">Consulta General</option>
                  <option value="Vacunación">Vacunación</option>
                  <option value="Cirugía">Cirugía</option>
                  <option value="Emergencia">Emergencia</option>
                  <option value="Control">Control</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de la Visita</label>
                <textarea 
                  v-model="nuevaCita.motivo"
                  rows="3"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                  placeholder="Describa el motivo de la consulta..."
                ></textarea>
              </div>
              
              <div class="flex gap-4 pt-6 border-t border-gray-200">
                <button 
                  type="button"
                  @click="cerrarModal"
                  class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors"
                >
                  Cancelar
                </button>
                <button 
                  type="submit"
                  class="flex-1 px-6 py-3 bg-gradient-to-r from-teal-600 to-blue-600 text-white font-semibold rounded-xl hover:from-teal-700 hover:to-blue-700 transition-colors"
                >
                  Programar Cita
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
  name: 'MetricasVeterinarios',
  
  props: {
    veterinarioId: {
      type: String,
      default: null
    },
    titulo: {
      type: String,
      default: 'Dashboard Veterinario'
    }
  },

  data() {
    return {
      fechaSeleccionada: new Date().toISOString().split('T')[0],
      clinicaSeleccionada: 'todas',
      veterinarioSeleccionado: '1',
      periodoConsultas: 'semana',
      periodoProcedimientos: 'mes',
      modalNuevaCita: false,
      
      clinicas: [
        { id: '1', nombre: 'Clínica Central' },
        { id: '2', nombre: 'Sucursal Norte' },
        { id: '3', nombre: 'Sucursal Sur' }
      ],
      
      veterinarios: [
        { id: '1', nombre: 'Dr. Carlos Rodríguez', especialidad: 'Cirugía' },
        { id: '2', nombre: 'Dra. Ana Martínez', especialidad: 'Dermatología' },
        { id: '3', nombre: 'Dr. Luis Fernández', especialidad: 'Cardiología' }
      ],
      
      metricas: {
        consultasHoy: 18,
        ingresosMensuales: 45280,
        tasaOcupacion: 92,
        clientesNuevos: 24
      },
      
      agenda: [
        {
          id: 1,
          veterinarioId: '1',
          mascota: 'Max',
          propietario: 'Juan Pérez',
          especie: 'Perro',
          tipo: 'Consulta',
          motivo: 'Revisión anual',
          hora: '09:00',
          duracion: 30
        },
        {
          id: 2,
          veterinarioId: '1',
          mascota: 'Luna',
          propietario: 'María García',
          especie: 'Gato',
          tipo: 'Vacunación',
          motivo: 'Vacuna antirrábica',
          hora: '10:30',
          duracion: 20
        },
        {
          id: 3,
          veterinarioId: '2',
          mascota: 'Rocky',
          propietario: 'Carlos López',
          especie: 'Perro',
          tipo: 'Emergencia',
          motivo: 'Problema estomacal',
          hora: '11:15',
          duracion: 45
        },
        {
          id: 4,
          veterinarioId: '3',
          mascota: 'Mimi',
          propietario: 'Laura Torres',
          especie: 'Gato',
          tipo: 'Cirugía',
          motivo: 'Esterilización',
          hora: '14:00',
          duracion: 120
        }
      ],
      
      estadisticas: {
        especies: [
          { nombre: 'Perro', porcentaje: 65 },
          { nombre: 'Gato', porcentaje: 28 },
          { nombre: 'Ave', porcentaje: 4 },
          { nombre: 'Exótico', porcentaje: 3 }
        ],
        tiempoPromedio: 25,
        satisfaccion: 94
      },
      
      medicamentos: [
        { id: 1, nombre: 'Amoxicilina', tipo: 'Antibiótico', cantidad: 42, tendencia: 12, stock: 85 },
        { id: 2, nombre: 'Carprofeno', tipo: 'Antiinflamatorio', cantidad: 38, tendencia: 8, stock: 60 },
        { id: 3, nombre: 'Rabisin', tipo: 'Vacuna', cantidad: 35, tendencia: 15, stock: 45 },
        { id: 4, nombre: 'Tramadol', tipo: 'Analgésico', cantidad: 28, tendencia: -5, stock: 30 },
        { id: 5, nombre: 'Enrofloxacina', tipo: 'Antibiótico', cantidad: 25, tendencia: 10, stock: 75 }
      ],
      
      procedimientos: [
        { 
          id: 1, 
          nombre: 'Consulta General', 
          descripcion: 'Revisión rutinaria', 
          categoria: 'Rutina',
          cantidad: 245,
          ingresoPromedio: 45,
          tiempoPromedio: 25
        },
        { 
          id: 2, 
          nombre: 'Vacunación', 
          descripcion: 'Aplicación de vacunas', 
          categoria: 'Preventivo',
          cantidad: 128,
          ingresoPromedio: 35,
          tiempoPromedio: 15
        },
        { 
          id: 3, 
          nombre: 'Ecografía', 
          descripcion: 'Diagnóstico por imagen', 
          categoria: 'Diagnóstico',
          cantidad: 42,
          ingresoPromedio: 120,
          tiempoPromedio: 40
        },
        { 
          id: 4, 
          nombre: 'Cirugía Menor', 
          descripcion: 'Procedimientos quirúrgicos', 
          categoria: 'Cirugía',
          cantidad: 28,
          ingresoPromedio: 350,
          tiempoPromedio: 90
        }
      ],
      
      nuevaCita: {
        veterinarioId: '1',
        fechaHora: '',
        mascota: '',
        especie: 'Perro',
        propietario: '',
        tipo: 'Consulta',
        motivo: ''
      },
      
      graficoConsultas: null,
      graficoTipos: null
    };
  },

  computed: {
    agendaFiltrada() {
      if (this.veterinarioSeleccionado === 'todos') {
        return this.agenda;
      }
      return this.agenda.filter(cita => cita.veterinarioId === this.veterinarioSeleccionado);
    },
    
    veterinarioActual() {
      return this.veterinarios.find(v => v.id === this.veterinarioSeleccionado) || this.veterinarios[0];
    }
  },

  mounted() {
    Chart.register(...registerables);
    this.inicializarGraficos();
    this.setupNuevaCitaFecha();
  },

  beforeUnmount() {
    this.destruirGraficos();
  },

  methods: {
    setupNuevaCitaFecha() {
      const now = new Date();
      now.setMinutes(now.getMinutes() + 30);
      this.nuevaCita.fechaHora = now.toISOString().slice(0, 16);
    },

    cambiarFecha() {
      this.actualizarGraficos();
    },

    cambiarClinica() {
      this.actualizarGraficos();
    },

    cambiarVeterinario(id) {
      this.veterinarioSeleccionado = id;
    },

    inicializarGraficos() {
      // Gráfico de consultas por veterinario
      this.graficoConsultas = new Chart(this.$refs.graficoConsultas, {
        type: 'bar',
        data: {
          labels: this.veterinarios.map(v => v.nombre.split(' ')[1]),
          datasets: [
            {
              label: 'Consultas Completadas',
              data: [42, 38, 35],
              backgroundColor: 'rgb(20, 184, 166)',
              borderRadius: 8,
              borderSkipped: false
            },
            {
              label: 'Consultas Pendientes',
              data: [8, 5, 12],
              backgroundColor: 'rgb(209, 213, 219)',
              borderRadius: 8,
              borderSkipped: false
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
              beginAtZero: true,
              title: {
                display: true,
                text: 'Número de Consultas'
              },
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          }
        }
      });

      // Gráfico de tipos de consulta
      this.graficoTipos = new Chart(this.$refs.graficoTipos, {
        type: 'doughnut',
        data: {
          labels: ['Consulta General', 'Vacunación', 'Cirugía', 'Emergencia', 'Control'],
          datasets: [{
            data: [45, 25, 15, 10, 5],
            backgroundColor: [
              'rgb(20, 184, 166)',
              'rgb(59, 130, 246)',
              'rgb(168, 85, 247)',
              'rgb(239, 68, 68)',
              'rgb(245, 158, 11)'
            ],
            borderWidth: 0,
            hoverOffset: 15
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'right',
              labels: {
                padding: 20,
                usePointStyle: true
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return `${context.label}: ${context.raw}%`;
                }
              }
            }
          },
          cutout: '65%'
        }
      });
    },

    actualizarGraficos() {
      // Aquí actualizarías los datos de los gráficos según los filtros
      if (this.graficoConsultas) {
        this.graficoConsultas.update();
      }
      if (this.graficoTipos) {
        this.graficoTipos.update();
      }
    },

    actualizarProcedimientos() {
      // Actualizar datos de procedimientos según el periodo
    },

    destruirGraficos() {
      if (this.graficoConsultas) {
        this.graficoConsultas.destroy();
      }
      if (this.graficoTipos) {
        this.graficoTipos.destroy();
      }
    },

    formatCurrency(value) {
      return new Intl.NumberFormat('es-ES').format(value);
    },

    exportarReporte() {
      const reporte = {
        fecha: this.fechaSeleccionada,
        clinica: this.clinicaSeleccionada,
        metricas: this.metricas,
        estadisticas: this.estadisticas,
        veterinario: this.veterinarioActual,
        fechaGeneracion: new Date().toISOString()
      };
      
      const blob = new Blob([JSON.stringify(reporte, null, 2)], { 
        type: 'application/json' 
      });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `reporte-veterinario-${new Date().toISOString().split('T')[0]}.json`;
      a.click();
      
      this.mostrarToast('Reporte exportado exitosamente', 'success');
    },

    agregarCita() {
      this.modalNuevaCita = true;
    },

    cerrarModal() {
      this.modalNuevaCita = false;
      this.nuevaCita = {
        veterinarioId: '1',
        fechaHora: '',
        mascota: '',
        especie: 'Perro',
        propietario: '',
        tipo: 'Consulta',
        motivo: ''
      };
      this.setupNuevaCitaFecha();
    },

    guardarNuevaCita() {
      const nuevaCita = {
        id: Date.now(),
        ...this.nuevaCita,
        hora: new Date(this.nuevaCita.fechaHora).toLocaleTimeString('es-ES', { 
          hour: '2-digit', 
          minute: '2-digit' 
        }),
        duracion: 30
      };
      
      this.agenda.push(nuevaCita);
      this.agenda.sort((a, b) => a.hora.localeCompare(b.hora));
      this.cerrarModal();
      this.mostrarToast('Cita programada exitosamente', 'success');
    },

    iniciarConsulta(cita) {
      this.$router.push({
        name: 'consulta-veterinaria',
        params: { citaId: cita.id }
      });
    },

    verHistorial(cita) {
      this.mostrarToast(`Historial de ${cita.mascota} cargado`, 'info');
    },

    mostrarDetallesTipos() {
      this.mostrarToast('Mostrando detalles de tipos de consulta', 'info');
    },

    mostrarToast(mensaje, tipo = 'info') {
      // Implementación con tu librería de toast preferida
      console.log(`${tipo}: ${mensaje}`);
      // Ejemplo con vue-toastification:
      // this.$toast[mensaje](tipo);
    }
  }
};
</script>
<template>
  <div class="metricas-adopciones bg-gradient-to-br from-orange-50 to-pink-50 min-h-screen p-4 md:p-6">
    <!-- Header principal -->
    <div class="metricas-header mb-8">
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">{{ titulo }}</h1>
          <p class="text-gray-600 mt-2">Sistema de seguimiento y métricas para adopciones de mascotas</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="relative">
            <select 
              v-model="refugioSeleccionado"
              @change="cambiarRefugio"
              class="pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            >
              <option value="todos">Todos los refugios</option>
              <option v-for="refugio in refugios" :key="refugio.id" :value="refugio.id">
                {{ refugio.nombre }}
              </option>
            </select>
            <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
          </div>
          
          <div class="relative">
            <select 
              v-model="periodoSeleccionado"
              @change="cambiarPeriodo"
              class="pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            >
              <option value="7d">Últimos 7 días</option>
              <option value="30d">Últimos 30 días</option>
              <option value="90d">Últimos 90 días</option>
              <option value="anio">Este año</option>
            </select>
            <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          
          <button 
            @click="generarReporteAdopciones"
            class="px-6 py-2.5 bg-gradient-to-r from-orange-600 to-pink-600 text-white font-semibold rounded-xl hover:from-orange-700 hover:to-pink-700 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Reporte
          </button>
        </div>
      </div>
      
      <!-- Tarjetas de resumen -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Mascotas para adopción -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border-l-4 border-l-orange-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-gray-500">Mascotas para Adopción</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ metricas.mascotasAdopcion }}</p>
            </div>
            <div class="p-3 bg-orange-50 rounded-xl">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Nuevas esta semana</span>
              <span class="font-semibold text-green-600">+{{ metricas.nuevasMascotas }}</span>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
              <div class="bg-orange-600 h-2 rounded-full" style="width: 85%"></div>
            </div>
          </div>
        </div>
        
        <!-- Adopciones este mes -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border-l-4 border-l-green-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-gray-500">Adopciones este Mes</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ metricas.adopcionesMes }}</p>
            </div>
            <div class="p-3 bg-green-50 rounded-xl">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Tasa de éxito</span>
              <span class="font-semibold text-green-600">{{ metricas.tasaExito }}%</span>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
              <div class="bg-green-600 h-2 rounded-full" style="width: 92%"></div>
            </div>
          </div>
        </div>
        
        <!-- Tiempo promedio de espera -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border-l-4 border-l-blue-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-gray-500">Tiempo Promedio de Espera</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ metricas.tiempoEspera }} días</p>
            </div>
            <div class="p-3 bg-blue-50 rounded-xl">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Vs. mes anterior</span>
              <span class="font-semibold text-green-600">-12%</span>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
              <div class="bg-blue-600 h-2 rounded-full" style="width: 75%"></div>
            </div>
          </div>
        </div>
        
        <!-- Solicitudes pendientes -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border-l-4 border-l-purple-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-gray-500">Solicitudes Pendientes</p>
              <p class="text-3xl font-bold text-gray-900 mt-2">{{ metricas.solicitudesPendientes }}</p>
            </div>
            <div class="p-3 bg-purple-50 rounded-xl">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Requieren revisión</span>
              <span class="font-semibold text-orange-600">{{ metricas.requierenRevision }}</span>
            </div>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
              <div class="bg-purple-600 h-2 rounded-full" style="width: 65%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráficos principales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Gráfico de adopciones por mes -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900">Adopciones por Mes</h3>
          <div class="flex gap-2">
            <button 
              v-for="especie in especiesFiltro"
              :key="especie"
              @click="cambiarEspecieFiltro(especie)"
              class="px-3 py-1.5 text-sm rounded-lg transition-colors"
              :class="{
                'bg-orange-100 text-orange-700': especieFiltroSeleccionada === especie,
                'bg-gray-100 text-gray-700 hover:bg-gray-200': especieFiltroSeleccionada !== especie
              }"
            >
              {{ especie }}
            </button>
          </div>
        </div>
        <div class="h-80">
          <canvas ref="graficoAdopciones"></canvas>
        </div>
      </div>
      
      <!-- Gráfico de especies en adopción -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900">Distribución por Especie</h3>
          <button 
            @click="mostrarDetallesEspecies"
            class="px-4 py-1.5 text-sm bg-orange-50 text-orange-700 hover:bg-orange-100 rounded-lg font-medium transition-colors"
          >
            Ver todas
          </button>
        </div>
        <div class="h-80">
          <canvas ref="graficoEspecies"></canvas>
        </div>
      </div>
    </div>

    <!-- Mascotas destacadas y solicitudes -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <!-- Mascotas destacadas -->
      <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900">Mascotas Destacadas</h3>
          <button 
            @click="agregarMascota"
            class="px-4 py-2 bg-orange-50 text-orange-700 hover:bg-orange-100 rounded-lg font-medium text-sm transition-colors flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nueva Mascota
          </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div 
            v-for="mascota in mascotasDestacadas"
            :key="mascota.id"
            class="border rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300"
          >
            <div class="relative">
              <img 
                :src="mascota.foto"
                :alt="mascota.nombre"
                class="w-full h-48 object-cover"
              />
              <div class="absolute top-3 right-3">
                <span class="px-2 py-1 text-xs font-semibold rounded-full text-white"
                  :class="{
                    'bg-green-500': mascota.estado === 'Disponible',
                    'bg-orange-500': mascota.estado === 'En proceso',
                    'bg-gray-500': mascota.estado === 'Adoptado'
                  }"
                >
                  {{ mascota.estado }}
                </span>
              </div>
              <div class="absolute bottom-3 left-3">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-white/90 text-gray-800">
                  {{ mascota.tiempoEnRefugio }}
                </span>
              </div>
            </div>
            
            <div class="p-4">
              <div class="flex justify-between items-start mb-3">
                <div>
                  <h4 class="font-bold text-gray-900">{{ mascota.nombre }}</h4>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="px-2 py-1 text-xs font-medium rounded"
                      :class="{
                        'bg-blue-100 text-blue-800': mascota.especie === 'Perro',
                        'bg-pink-100 text-pink-800': mascota.especie === 'Gato',
                        'bg-yellow-100 text-yellow-800': mascota.especie === 'Otro'
                      }"
                    >
                      {{ mascota.especie }}
                    </span>
                    <span class="text-sm text-gray-500">{{ mascota.edad }}</span>
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-sm text-gray-500">Solicitudes</div>
                  <div class="text-lg font-bold text-gray-900">{{ mascota.solicitudes }}</div>
                </div>
              </div>
              
              <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ mascota.descripcion }}</p>
              
              <div class="flex gap-2">
                <button 
                  @click="verMascota(mascota.id)"
                  class="flex-1 px-3 py-2 text-sm font-medium text-orange-700 bg-orange-100 hover:bg-orange-200 rounded-lg transition-colors"
                >
                  Ver detalles
                </button>
                <button 
                  v-if="mascota.estado === 'Disponible'"
                  @click="solicitarAdopcion(mascota.id)"
                  class="flex-1 px-3 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors"
                >
                  Adoptar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Solicitudes recientes -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900">Solicitudes Recientes</h3>
          <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-medium">
            {{ solicitudesRecientes.length }} nuevas
          </span>
        </div>
        
        <div class="space-y-4">
          <div 
            v-for="solicitud in solicitudesRecientes"
            :key="solicitud.id"
            class="p-4 border rounded-xl hover:shadow-sm transition-all"
            :class="{
              'border-orange-200 bg-orange-50': solicitud.estado === 'Pendiente',
              'border-blue-200 bg-blue-50': solicitud.estado === 'En revisión',
              'border-green-200 bg-green-50': solicitud.estado === 'Aprobada',
              'border-red-200 bg-red-50': solicitud.estado === 'Rechazada'
            }"
          >
            <div class="flex justify-between items-start mb-3">
              <div>
                <p class="font-semibold text-gray-900">{{ solicitud.nombreAdoptante }}</p>
                <p class="text-sm text-gray-500">{{ solicitud.email }}</p>
              </div>
              <span class="px-2 py-1 text-xs font-semibold rounded"
                :class="{
                  'bg-orange-100 text-orange-800': solicitud.estado === 'Pendiente',
                  'bg-blue-100 text-blue-800': solicitud.estado === 'En revisión',
                  'bg-green-100 text-green-800': solicitud.estado === 'Aprobada',
                  'bg-red-100 text-red-800': solicitud.estado === 'Rechazada'
                }"
              >
                {{ solicitud.estado }}
              </span>
            </div>
            
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-lg overflow-hidden">
                <img 
                  :src="solicitud.mascotaFoto"
                  :alt="solicitud.mascotaNombre"
                  class="w-full h-full object-cover"
                />
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ solicitud.mascotaNombre }}</p>
                <p class="text-xs text-gray-500">{{ solicitud.fecha }}</p>
              </div>
            </div>
            
            <div class="flex gap-2">
              <button 
                @click="aprobarSolicitud(solicitud.id)"
                v-if="solicitud.estado === 'Pendiente' || solicitud.estado === 'En revisión'"
                class="flex-1 px-3 py-1.5 text-sm font-medium text-green-700 bg-green-100 hover:bg-green-200 rounded-lg transition-colors"
              >
                Aprobar
              </button>
              <button 
                @click="rechazarSolicitud(solicitud.id)"
                v-if="solicitud.estado === 'Pendiente' || solicitud.estado === 'En revisión'"
                class="flex-1 px-3 py-1.5 text-sm font-medium text-red-700 bg-red-100 hover:bg-red-200 rounded-lg transition-colors"
              >
                Rechazar
              </button>
              <button 
                @click="verSolicitud(solicitud.id)"
                class="flex-1 px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
              >
                Ver
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Estadísticas de adopción y procesos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Estadísticas de proceso -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Estadísticas del Proceso</h3>
        
        <div class="space-y-6">
          <div>
            <p class="text-sm font-medium text-gray-500 mb-2">Tiempo Promedio por Etapa</p>
            <div class="space-y-4">
              <div v-for="etapa in etapasProceso" :key="etapa.nombre">
                <div class="flex justify-between text-sm mb-1">
                  <span class="text-gray-700">{{ etapa.nombre }}</span>
                  <span class="font-semibold">{{ etapa.tiempoPromedio }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="h-2 rounded-full"
                    :class="etapa.claseColor"
                    :style="{ width: `${etapa.progreso}%` }"
                  ></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ etapa.descripcion }}</p>
              </div>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div class="p-4 bg-orange-50 rounded-xl">
              <p class="text-sm font-medium text-gray-500">Tasa de Finalización</p>
              <p class="text-2xl font-bold text-gray-900 mt-2">{{ estadisticas.tasaFinalizacion }}%</p>
            </div>
            <div class="p-4 bg-green-50 rounded-xl">
              <p class="text-sm font-medium text-gray-500">Retorno al Refugio</p>
              <p class="text-2xl font-bold text-gray-900 mt-2">{{ estadisticas.tasaRetorno }}%</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Características de adoptantes -->
      <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900">Perfil de Adoptantes</h3>
          <button 
            @click="exportarPerfiles"
            class="px-4 py-1.5 text-sm bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg font-medium transition-colors flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Exportar
          </button>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr class="bg-gray-50">
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Perfil</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Porcentaje</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tasa de Éxito</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preferencia</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="perfil in perfilesAdoptantes" :key="perfil.nombre" class="hover:bg-gray-50">
                <td class="px-4 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center"
                      :class="perfil.claseBg"
                    >
                      <svg class="w-4 h-4" :class="perfil.claseIcono" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ perfil.nombre }}</p>
                      <p class="text-xs text-gray-500">{{ perfil.descripcion }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <div class="flex items-center gap-2">
                    <div class="w-24 bg-gray-200 rounded-full h-2">
                      <div class="bg-orange-600 h-2 rounded-full" :style="{ width: `${perfil.porcentaje}%` }"></div>
                    </div>
                    <span class="text-sm font-semibold">{{ perfil.porcentaje }}%</span>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <span class="px-2 py-1 text-xs font-semibold rounded-full"
                    :class="{
                      'bg-green-100 text-green-800': perfil.tasaExito >= 90,
                      'bg-yellow-100 text-yellow-800': perfil.tasaExito >= 80 && perfil.tasaExito < 90,
                      'bg-orange-100 text-orange-800': perfil.tasaExito < 80
                    }"
                  >
                    {{ perfil.tasaExito }}%
                  </span>
                </td>
                <td class="px-4 py-4">
                  <span class="text-sm text-gray-900">{{ perfil.preferencia }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Lista completa de mascotas -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-gray-900">Todas las Mascotas</h3>
        <div class="flex gap-4">
          <div class="relative">
            <input 
              v-model="busquedaMascota"
              @input="filtrarMascotas"
              type="text"
              placeholder="Buscar mascota..."
              class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            />
            <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          
          <select 
            v-model="filtroEstado"
            @change="filtrarMascotas"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
          >
            <option value="todos">Todos los estados</option>
            <option value="Disponible">Disponible</option>
            <option value="En proceso">En proceso</option>
            <option value="Adoptado">Adoptado</option>
          </select>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr class="bg-gray-50">
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mascota</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Especie</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Edad</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiempo en Refugio</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solicitudes</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr 
              v-for="mascota in mascotasFiltradas"
              :key="mascota.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-lg overflow-hidden">
                    <img 
                      :src="mascota.foto"
                      :alt="mascota.nombre"
                      class="w-full h-full object-cover"
                    />
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">{{ mascota.nombre }}</p>
                    <p class="text-sm text-gray-500">{{ mascota.raza }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="{
                    'bg-blue-100 text-blue-800': mascota.especie === 'Perro',
                    'bg-pink-100 text-pink-800': mascota.especie === 'Gato',
                    'bg-yellow-100 text-yellow-800': mascota.especie === 'Otro'
                  }"
                >
                  {{ mascota.especie }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ mascota.edad }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ mascota.tiempoEnRefugio }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="font-semibold text-gray-900">{{ mascota.solicitudes }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-3 py-1 text-xs font-semibold rounded-full"
                  :class="{
                    'bg-green-100 text-green-800': mascota.estado === 'Disponible',
                    'bg-orange-100 text-orange-800': mascota.estado === 'En proceso',
                    'bg-gray-100 text-gray-800': mascota.estado === 'Adoptado'
                  }"
                >
                  {{ mascota.estado }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button 
                  @click="verMascota(mascota.id)"
                  class="text-orange-600 hover:text-orange-900 mr-3"
                >
                  Ver
                </button>
                <button 
                  @click="editarMascota(mascota.id)"
                  class="text-blue-600 hover:text-blue-900"
                >
                  Editar
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="flex justify-between items-center mt-6 pt-6 border-t border-gray-200">
        <p class="text-sm text-gray-500">
          Mostrando {{ mascotasFiltradas.length }} de {{ totalMascotas }} mascotas
        </p>
        <div class="flex gap-2">
          <button 
            @click="paginaAnterior"
            :disabled="paginaActual === 1"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Anterior
          </button>
          <span class="px-4 py-2 text-sm font-medium text-gray-700">
            Página {{ paginaActual }}
          </span>
          <button 
            @click="paginaSiguiente"
            :disabled="paginaActual === totalPaginas"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de nueva mascota -->
    <div v-if="modalNuevaMascota" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
        <div class="fixed inset-0 transition-opacity" @click="cerrarModal">
          <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
          <div class="bg-white px-6 pt-6 pb-4">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-2xl font-bold text-gray-900">Nueva Mascota para Adopción</h3>
              <button @click="cerrarModal" class="text-gray-400 hover:text-gray-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="guardarNuevaMascota" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                  <input 
                    v-model="nuevaMascota.nombre"
                    type="text"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                    placeholder="Ej: Max"
                    required
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Especie</label>
                  <select 
                    v-model="nuevaMascota.especie"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                  >
                    <option value="Perro">Perro</option>
                    <option value="Gato">Gato</option>
                    <option value="Conejo">Conejo</option>
                    <option value="Ave">Ave</option>
                    <option value="Otro">Otro</option>
                  </select>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Raza</label>
                  <input 
                    v-model="nuevaMascota.raza"
                    type="text"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                    placeholder="Ej: Golden Retriever"
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Edad</label>
                  <input 
                    v-model="nuevaMascota.edad"
                    type="text"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                    placeholder="Ej: 3 años"
                    required
                  />
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <textarea 
                  v-model="nuevaMascota.descripcion"
                  rows="3"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                  placeholder="Describa a la mascota, su personalidad, necesidades especiales..."
                ></textarea>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Foto URL</label>
                  <input 
                    v-model="nuevaMascota.foto"
                    type="url"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                    placeholder="https://ejemplo.com/foto.jpg"
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Refugio</label>
                  <select 
                    v-model="nuevaMascota.refugioId"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                  >
                    <option v-for="refugio in refugios" :key="refugio.id" :value="refugio.id">
                      {{ refugio.nombre }}
                    </option>
                  </select>
                </div>
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
                  class="flex-1 px-6 py-3 bg-gradient-to-r from-orange-600 to-pink-600 text-white font-semibold rounded-xl hover:from-orange-700 hover:to-pink-700 transition-colors"
                >
                  Agregar Mascota
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
  name: 'MetricasAdopciones',
  
  props: {
    refugioId: {
      type: String,
      default: null
    },
    titulo: {
      type: String,
      default: 'Sistema de Adopciones'
    }
  },

  data() {
    return {
      refugioSeleccionado: 'todos',
      periodoSeleccionado: '30d',
      especieFiltroSeleccionada: 'Todas',
      busquedaMascota: '',
      filtroEstado: 'todos',
      paginaActual: 1,
      itemsPorPagina: 10,
      modalNuevaMascota: false,
      
      refugios: [
        { id: '1', nombre: 'Refugio Central', ubicacion: 'Ciudad', capacidad: 50 },
        { id: '2', nombre: 'Refugio Norte', ubicacion: 'Zona Norte', capacidad: 30 },
        { id: '3', nombre: 'Refugio Sur', ubicacion: 'Zona Sur', capacidad: 40 }
      ],
      
      metricas: {
        mascotasAdopcion: 124,
        nuevasMascotas: 18,
        adopcionesMes: 42,
        tasaExito: 92,
        tiempoEspera: 15,
        solicitudesPendientes: 28,
        requierenRevision: 12
      },
      
      especiesFiltro: ['Todas', 'Perros', 'Gatos', 'Otros'],
      
      mascotasDestacadas: [
        {
          id: 1,
          nombre: 'Max',
          especie: 'Perro',
          raza: 'Golden Retriever',
          edad: '3 años',
          descripcion: 'Muy cariñoso y juguetón, ideal para familias con niños. Le encanta pasear y jugar con pelotas.',
          foto: 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=400&h=300&fit=crop',
          estado: 'Disponible',
          tiempoEnRefugio: '2 meses',
          solicitudes: 5
        },
        {
          id: 2,
          nombre: 'Luna',
          especie: 'Gato',
          raza: 'Siamés',
          edad: '2 años',
          descripcion: 'Tranquila y cariñosa, prefiere ambientes calmados. Se lleva bien con otros gatos.',
          foto: 'https://images.unsplash.com/photo-1514888286974-6d03bdeacba8?w=400&h=300&fit=crop',
          estado: 'En proceso',
          tiempoEnRefugio: '3 semanas',
          solicitudes: 3
        },
        {
          id: 3,
          nombre: 'Rocky',
          especie: 'Perro',
          raza: 'Mestizo',
          edad: '1 año',
          descripcion: 'Energético y leal, necesita dueño activo. Aprendió comandos básicos.',
          foto: 'https://images.unsplash.com/photo-1517849845537-4d257902454a?w=400&h=300&fit=crop',
          estado: 'Disponible',
          tiempoEnRefugio: '1 mes',
          solicitudes: 8
        },
        {
          id: 4,
          nombre: 'Mimi',
          especie: 'Gato',
          raza: 'Persa',
          edad: '4 años',
          descripcion: 'Cariñosa y tranquila, ideal para personas mayores o familias tranquilas.',
          foto: 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=400&h=300&fit=crop',
          estado: 'Disponible',
          tiempoEnRefugio: '4 meses',
          solicitudes: 2
        }
      ],
      
      solicitudesRecientes: [
        {
          id: 1,
          nombreAdoptante: 'Juan Pérez',
          email: 'juan@email.com',
          mascotaNombre: 'Max',
          mascotaFoto: 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=100&h=100&fit=crop',
          fecha: 'Hoy, 10:30 AM',
          estado: 'Pendiente'
        },
        {
          id: 2,
          nombreAdoptante: 'María García',
          email: 'maria@email.com',
          mascotaNombre: 'Luna',
          mascotaFoto: 'https://images.unsplash.com/photo-1514888286974-6d03bdeacba8?w=100&h=100&fit=crop',
          fecha: 'Ayer, 15:45',
          estado: 'En revisión'
        },
        {
          id: 3,
          nombreAdoptante: 'Carlos López',
          email: 'carlos@email.com',
          mascotaNombre: 'Rocky',
          mascotaFoto: 'https://images.unsplash.com/photo-1517849845537-4d257902454a?w=100&h=100&fit=crop',
          fecha: '2 días atrás',
          estado: 'Aprobada'
        },
        {
          id: 4,
          nombreAdoptante: 'Ana Torres',
          email: 'ana@email.com',
          mascotaNombre: 'Mimi',
          mascotaFoto: 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=100&h=100&fit=crop',
          fecha: '3 días atrás',
          estado: 'Rechazada'
        }
      ],
      
      etapasProceso: [
        {
          nombre: 'Recepción de solicitud',
          tiempoPromedio: '1-2 días',
          progreso: 100,
          claseColor: 'bg-green-500',
          descripcion: 'Revisión inicial de documentación'
        },
        {
          nombre: 'Entrevista telefónica',
          tiempoPromedio: '3-5 días',
          progreso: 85,
          claseColor: 'bg-blue-500',
          descripcion: 'Contacto con el adoptante'
        },
        {
          nombre: 'Visita domiciliaria',
          tiempoPromedio: '5-7 días',
          progreso: 70,
          claseColor: 'bg-yellow-500',
          descripcion: 'Verificación de condiciones'
        },
        {
          nombre: 'Aprobación final',
          tiempoPromedio: '1-2 días',
          progreso: 90,
          claseColor: 'bg-orange-500',
          descripcion: 'Decisión del comité'
        },
        {
          nombre: 'Entrega de mascota',
          tiempoPromedio: '1-3 días',
          progreso: 95,
          claseColor: 'bg-purple-500',
          descripcion: 'Firmas y preparativos'
        }
      ],
      
      estadisticas: {
        tasaFinalizacion: 78,
        tasaRetorno: 8
      },
      
      perfilesAdoptantes: [
        {
          nombre: 'Familias con niños',
          descripcion: '2-4 miembros, casa propia',
          porcentaje: 45,
          tasaExito: 94,
          preferencia: 'Perros jóvenes',
          claseBg: 'bg-blue-50',
          claseIcono: 'text-blue-600'
        },
        {
          nombre: 'Parejas jóvenes',
          descripcion: '20-35 años, departamento',
          porcentaje: 28,
          tasaExito: 88,
          preferencia: 'Gatos adultos',
          claseBg: 'bg-pink-50',
          claseIcono: 'text-pink-600'
        },
        {
          nombre: 'Personas solteras',
          descripcion: '30-50 años, trabajo estable',
          porcentaje: 18,
          tasaExito: 85,
          preferencia: 'Perros medianos',
          claseBg: 'bg-orange-50',
          claseIcono: 'text-orange-600'
        },
        {
          nombre: 'Personas mayores',
          descripcion: '60+ años, jubilados',
          porcentaje: 9,
          tasaExito: 92,
          preferencia: 'Gatos senior',
          claseBg: 'bg-purple-50',
          claseIcono: 'text-purple-600'
        }
      ],
      
      todasLasMascotas: [
        // Aquí irían todas las mascotas, por ahora usamos las destacadas y agregamos más
        ...this.mascotasDestacadas,
        {
          id: 5,
          nombre: 'Toby',
          especie: 'Perro',
          raza: 'Beagle',
          edad: '2 años',
          foto: 'https://images.unsplash.com/photo-1518717758536-85ae29035b6d?w=100&h=100&fit=crop',
          estado: 'Adoptado',
          tiempoEnRefugio: '1 mes',
          solicitudes: 0
        },
        {
          id: 6,
          nombre: 'Nala',
          especie: 'Gato',
          raza: 'Mestizo',
          edad: '6 meses',
          foto: 'https://images.unsplash.com/photo-1513360371669-4adf3dd7dff8?w=100&h=100&fit=crop',
          estado: 'Disponible',
          tiempoEnRefugio: '3 semanas',
          solicitudes: 1
        }
      ],
      
      nuevaMascota: {
        nombre: '',
        especie: 'Perro',
        raza: '',
        edad: '',
        descripcion: '',
        foto: '',
        refugioId: '1'
      },
      
      graficoAdopciones: null,
      graficoEspecies: null
    };
  },

  computed: {
    mascotasFiltradas() {
      let filtradas = this.todasLasMascotas;
      
      // Filtro por búsqueda
      if (this.busquedaMascota) {
        const busqueda = this.busquedaMascota.toLowerCase();
        filtradas = filtradas.filter(m => 
          m.nombre.toLowerCase().includes(busqueda) ||
          m.raza.toLowerCase().includes(busqueda)
        );
      }
      
      // Filtro por estado
      if (this.filtroEstado !== 'todos') {
        filtradas = filtradas.filter(m => m.estado === this.filtroEstado);
      }
      
      return filtradas;
    },
    
    totalMascotas() {
      return this.todasLasMascotas.length;
    },
    
    totalPaginas() {
      return Math.ceil(this.mascotasFiltradas.length / this.itemsPorPagina);
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
    cambiarRefugio() {
      this.actualizarGraficos();
    },

    cambiarPeriodo() {
      this.actualizarGraficos();
    },

    cambiarEspecieFiltro(especie) {
      this.especieFiltroSeleccionada = especie;
      this.actualizarGraficos();
    },

    inicializarGraficos() {
      // Gráfico de adopciones por mes
      this.graficoAdopciones = new Chart(this.$refs.graficoAdopciones, {
        type: 'line',
        data: {
          labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
          datasets: [
            {
              label: 'Adopciones Completadas',
              data: [32, 28, 35, 40, 38, 42, 45, 48, 44, 50, 52, 55],
              borderColor: 'rgb(249, 115, 22)',
              backgroundColor: 'rgba(249, 115, 22, 0.1)',
              borderWidth: 3,
              tension: 0.4,
              fill: true
            },
            {
              label: 'Solicitudes Recibidas',
              data: [45, 40, 50, 55, 52, 58, 62, 65, 60, 68, 70, 72],
              borderColor: 'rgb(236, 72, 153)',
              backgroundColor: 'rgba(236, 72, 153, 0.1)',
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
              position: 'top'
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Cantidad'
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

      // Gráfico de especies en adopción
      this.graficoEspecies = new Chart(this.$refs.graficoEspecies, {
        type: 'doughnut',
        data: {
          labels: ['Perros', 'Gatos', 'Conejos', 'Aves', 'Otros'],
          datasets: [{
            data: [65, 25, 5, 3, 2],
            backgroundColor: [
              'rgb(59, 130, 246)',
              'rgb(236, 72, 153)',
              'rgb(245, 158, 11)',
              'rgb(139, 92, 246)',
              'rgb(107, 114, 128)'
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
      if (this.graficoAdopciones) {
        this.graficoAdopciones.update();
      }
      if (this.graficoEspecies) {
        this.graficoEspecies.update();
      }
    },

    destruirGraficos() {
      if (this.graficoAdopciones) {
        this.graficoAdopciones.destroy();
      }
      if (this.graficoEspecies) {
        this.graficoEspecies.destroy();
      }
    },

    generarReporteAdopciones() {
      const reporte = {
        periodo: this.periodoSeleccionado,
        refugio: this.refugioSeleccionado === 'todos' ? 'Todos' : 
                 this.refugios.find(r => r.id === this.refugioSeleccionado)?.nombre,
        metricas: this.metricas,
        estadisticas: this.estadisticas,
        fechaGeneracion: new Date().toISOString(),
        mascotasDisponibles: this.todasLasMascotas.filter(m => m.estado === 'Disponible').length
      };
      
      const blob = new Blob([JSON.stringify(reporte, null, 2)], { 
        type: 'application/json' 
      });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `reporte-adopciones-${new Date().toISOString().split('T')[0]}.json`;
      a.click();
      
      this.mostrarToast('Reporte exportado exitosamente', 'success');
    },

    agregarMascota() {
      this.modalNuevaMascota = true;
    },

    cerrarModal() {
      this.modalNuevaMascota = false;
      this.nuevaMascota = {
        nombre: '',
        especie: 'Perro',
        raza: '',
        edad: '',
        descripcion: '',
        foto: '',
        refugioId: '1'
      };
    },

    guardarNuevaMascota() {
      const nuevaMascota = {
        id: Date.now(),
        ...this.nuevaMascota,
        estado: 'Disponible',
        tiempoEnRefugio: 'Nuevo',
        solicitudes: 0,
        foto: this.nuevaMascota.foto || 'https://images.unsplash.com/photo-1513360371669-4adf3dd7dff8?w=400&h=300&fit=crop'
      };
      
      this.todasLasMascotas.unshift(nuevaMascota);
      this.cerrarModal();
      this.mostrarToast('Mascota agregada exitosamente', 'success');
    },

    verMascota(id) {
      const mascota = this.todasLasMascotas.find(m => m.id === id);
      if (mascota) {
        this.$router.push({
          name: 'detalle-mascota',
          params: { mascotaId: id }
        });
      }
    },

    editarMascota(id) {
      this.mostrarToast(`Editando mascota ${id}`, 'info');
    },

    solicitarAdopcion(id) {
      this.mostrarToast('Redirigiendo a formulario de adopción', 'info');
    },

    aprobarSolicitud(id) {
      const solicitud = this.solicitudesRecientes.find(s => s.id === id);
      if (solicitud) {
        solicitud.estado = 'Aprobada';
        this.mostrarToast('Solicitud aprobada exitosamente', 'success');
      }
    },

    rechazarSolicitud(id) {
      const solicitud = this.solicitudesRecientes.find(s => s.id === id);
      if (solicitud) {
        solicitud.estado = 'Rechazada';
        this.mostrarToast('Solicitud rechazada', 'info');
      }
    },

    verSolicitud(id) {
      this.mostrarToast(`Viendo detalles de solicitud ${id}`, 'info');
    },

    mostrarDetallesEspecies() {
      this.mostrarToast('Mostrando todas las especies', 'info');
    },

    exportarPerfiles() {
      this.mostrarToast('Exportando perfiles de adoptantes', 'info');
    },

    filtrarMascotas() {
      this.paginaActual = 1;
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

    mostrarToast(mensaje, tipo = 'info') {
      // Implementación con tu librería de toast preferida
      console.log(`${tipo}: ${mensaje}`);
    }
  }
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
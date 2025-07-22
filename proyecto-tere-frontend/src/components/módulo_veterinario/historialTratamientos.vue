<template>
  <div class="max-w-6xl mx-auto p-6 bg-white rounded-lg shadow-sm">
    <!-- Encabezado -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-2xl font-light text-gray-800">HISTORIAL MÉDICO</h1>
        <div class="flex items-center mt-2">
          <span class="text-sm text-gray-500 mr-4">Paciente: <span class="font-medium text-gray-700">Max (Golden Retriever)</span></span>
          <span class="text-sm text-gray-500">N° Historia Clínica: <span class="font-medium text-gray-700">HC-2023-00542</span></span>
        </div>
      </div>
      <div class="flex items-center space-x-3">
        <button class="p-2 rounded-full bg-gray-100 hover:bg-gray-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
            <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2h-1.528A6 6 0 004 9.528V4z" />
            <path fill-rule="evenodd" d="M8 10a4 4 0 00-3.446 6.032l-1.261 1.26a1 1 0 101.414 1.415l1.261-1.261A4 4 0 108 10zm-2 4a2 2 0 114 0 2 2 0 01-4 0z" clip-rule="evenodd" />
          </svg>
        </button>
        <button class="p-2 rounded-full bg-gray-100 hover:bg-gray-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
      <div class="flex items-center space-x-3">
        <div class="relative">
          <select class="appearance-none bg-white border border-gray-300 rounded-md pl-3 pr-8 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option>Todos los tratamientos</option>
            <option>Vacunaciones</option>
            <option>Cirugías</option>
            <option>Medicamentos</option>
            <option>Consultas</option>
          </select>
          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
          </div>
        </div>
        <div class="relative">
          <input type="date" class="bg-white border border-gray-300 rounded-md pl-3 pr-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
      </div>
      <div class="relative w-full md:w-64">
        <input type="text" placeholder="Buscar en historial..." class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
    </div>

    <!-- Lista de tratamientos -->
    <div class="space-y-4">
      <!-- Tarjeta de tratamiento -->
      <div v-for="(tratamiento, index) in tratamientos" :key="index" class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow duration-200">
        <div class="flex flex-col md:flex-row">
          <!-- Columna izquierda - Fecha y tipo -->
          <div class="w-full md:w-32 bg-gray-50 p-4 flex flex-col items-center justify-center border-b md:border-b-0 md:border-r border-gray-200">
            <div class="text-xs text-gray-500 uppercase font-medium mb-1">{{ tratamiento.fecha }}</div>
            <div class="flex items-center justify-center w-12 h-12 rounded-full" :class="{
              'bg-blue-100 text-blue-600': tratamiento.tipo === 'Consulta',
              'bg-green-100 text-green-600': tratamiento.tipo === 'Vacunación',
              'bg-purple-100 text-purple-600': tratamiento.tipo === 'Cirugía',
              'bg-yellow-100 text-yellow-600': tratamiento.tipo === 'Medicamento'
            }">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path v-if="tratamiento.tipo === 'Consulta'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                <path v-if="tratamiento.tipo === 'Vacunación'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                <path v-if="tratamiento.tipo === 'Cirugía'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                <path v-if="tratamiento.tipo === 'Medicamento'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
              </svg>
            </div>
            <span class="mt-2 text-xs font-medium" :class="{
              'text-blue-600': tratamiento.tipo === 'Consulta',
              'text-green-600': tratamiento.tipo === 'Vacunación',
              'text-purple-600': tratamiento.tipo === 'Cirugía',
              'text-yellow-600': tratamiento.tipo === 'Medicamento'
            }">{{ tratamiento.tipo }}</span>
          </div>
          
          <!-- Columna derecha - Detalles -->
          <div class="flex-1 p-4">
            <div class="flex justify-between items-start">
              <div>
                <h3 class="font-medium text-gray-900">{{ tratamiento.procedimiento }}</h3>
                <p class="text-sm text-gray-500 mt-1">Realizado por: <span class="text-gray-700">{{ tratamiento.veterinario }}</span></p>
              </div>
              <button @click="toggleDetalles(index)" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                {{ tratamiento.mostrarDetalles ? 'Ocultar' : 'Ver detalles' }}
              </button>
            </div>
            
            <!-- Detalles expandibles -->
            <div v-if="tratamiento.mostrarDetalles" class="mt-4 pt-4 border-t border-gray-100">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Descripción</h4>
                  <p class="text-sm text-gray-700">{{ tratamiento.descripcion }}</p>
                </div>
                <div>
                  <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Medicamentos</h4>
                  <ul class="space-y-2">
                    <li v-for="(med, medIndex) in tratamiento.medicamentos" :key="medIndex" class="text-sm text-gray-700">
                      <span class="font-medium">{{ med.nombre }}</span> - {{ med.dosis }} cada {{ med.frecuencia }}
                    </li>
                  </ul>
                </div>
              </div>
              
              <div class="mt-4 pt-4 border-t border-gray-100">
                <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Observaciones</h4>
                <p class="text-sm text-gray-700">{{ tratamiento.observaciones }}</p>
              </div>
              
              <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
                <button class="flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Descargar informe
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Paginación -->
    <div class="mt-8 flex items-center justify-between">
      <div class="text-sm text-gray-500">
        Mostrando <span class="font-medium">1</span> a <span class="font-medium">5</span> de <span class="font-medium">12</span> tratamientos
      </div>
      <div class="flex space-x-2">
        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
          Anterior
        </button>
        <button class="px-3 py-1 border border-blue-500 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
          1
        </button>
        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
          2
        </button>
        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
          3
        </button>
        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
          Siguiente
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const tratamientos = ref([
  {
    fecha: '15/03/2023',
    tipo: 'Consulta',
    procedimiento: 'Control anual de salud',
    veterinario: 'Dra. Laura Martínez',
    descripcion: 'Revisión general del estado de salud. Se observa buen estado físico, pelaje en condiciones normales y mucosas hidratadas.',
    medicamentos: [
      { nombre: 'Vitaminas Caninas Plus', dosis: '1 comprimido', frecuencia: '24 horas' }
    ],
    observaciones: 'Recomendado aumentar actividad física en 15 minutos diarios. Volver a control en 6 meses.',
    mostrarDetalles: false
  },
  {
    fecha: '10/02/2023',
    tipo: 'Vacunación',
    procedimiento: 'Vacuna antirrábica',
    veterinario: 'Dr. Carlos Rodríguez',
    descripcion: 'Aplicación de vacuna antirrábica anual. El paciente respondió bien al procedimiento sin reacciones adversas.',
    medicamentos: [],
    observaciones: 'El paciente mostró leve molestia durante la aplicación pero se calmó rápidamente. No presentó fiebre posterior.',
    mostrarDetalles: false
  },
  {
    fecha: '22/01/2023',
    tipo: 'Cirugía',
    procedimiento: 'Esterilización',
    veterinario: 'Dra. Ana López',
    descripcion: 'Procedimiento de ovariohisterectomía rutinario. Duración: 45 minutos. Anestesia general sin complicaciones.',
    medicamentos: [
      { nombre: 'Antibiótico VetMax', dosis: '1 tableta', frecuencia: '12 horas' },
      { nombre: 'Analgésico DogPain', dosis: '1 ml', frecuencia: '24 horas' }
    ],
    observaciones: 'Sutura absorbible. Control de herida en 10 días. Evitar baños por 15 días y usar collar isabelino.',
    mostrarDetalles: false
  },
  {
    fecha: '05/12/2022',
    tipo: 'Medicamento',
    procedimiento: 'Tratamiento antiparasitario',
    veterinario: 'Dr. Juan Pérez',
    descripcion: 'Administración de tratamiento completo contra parásitos internos y externos.',
    medicamentos: [
      { nombre: 'Antiparasitario TotalDog', dosis: '2.5 ml', frecuencia: 'una sola dosis' }
    ],
    observaciones: 'Repetir tratamiento en 3 meses. Observar posibles efectos secundarios como letargo o falta de apetito.',
    mostrarDetalles: false
  },
  {
    fecha: '14/11/2022',
    tipo: 'Consulta',
    procedimiento: 'Revisión por dermatitis',
    veterinario: 'Dra. Laura Martínez',
    descripcion: 'El paciente presenta dermatitis alérgica en zona abdominal. Se realizó raspado de piel para descartar infección secundaria.',
    medicamentos: [
      { nombre: 'Shampoo medicado Dermocan', dosis: 'Aplicación tópica', frecuencia: 'cada 3 días' },
      { nombre: 'Antihistamínico VetAlerg', dosis: '1 tableta', frecuencia: '12 horas' }
    ],
    observaciones: 'Recomendado cambio de dieta a alimento hipoalergénico por 8 semanas para evaluar respuesta.',
    mostrarDetalles: false
  }
])

function toggleDetalles(index) {
  tratamientos.value[index].mostrarDetalles = !tratamientos.value[index].mostrarDetalles
}
</script>

<style scoped>
/* Estilos personalizados si son necesarios */
</style>
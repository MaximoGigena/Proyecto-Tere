<!-- historial de dueños -->
<template>
    <div class="bg-gray-100 p-6 min-h-screen">
      <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Header -->
        <div class="bg-gray-800 text-white p-4">
          <h1 class="text-xl font-bold">Historial de Dueños</h1>
        </div>
  
        <!-- Sort option -->
        <div class="p-4 border-b">
          <div class="flex items-center text-gray-600">
            <span class="mr-2">Ordenar</span>
            <button class="text-gray-700 hover:text-gray-900">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
            <button class="text-gray-700 hover:text-gray-900 ml-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
  
        <!-- Owners list -->
        <div v-for="(owner, index) in owners" :key="index" class="p-4 border-b last:border-b-0">
          <div class="mb-2">
            <h2 class="font-semibold text-lg">{{ owner.nombre }}</h2>
          </div>
  
          <div class="grid grid-cols-2 gap-2 text-sm text-gray-600 mb-3">
            <div>
              <span class="font-medium">Fecha de Adopción:</span> {{ owner.adopcion }}
            </div>
            <div>
              <span class="font-medium">Fecha de Desligo:</span> {{ owner.desligo }}
            </div>
          </div>
  
          <div class="flex justify-end space-x-2">
            <button
              :class="[
                'px-3 py-1 rounded flex items-center',
                owner.contactable
                  ? 'bg-orange-500 hover:bg-orange-600 text-white'
                  : 'bg-gray-300 text-gray-700 opacity-50 cursor-not-allowed',
              ]"
              :disabled="!owner.contactable"
            >
              <span class="mr-1">Contactar</span>
              <span>{{ owner.contactable ? '✗' : '✗' }}</span>
            </button>
            <button
              class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded flex items-center"
            >
              <span class="mr-1">Contactar</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  const owners = [
    {
      nombre: 'Nombre del Dueño',
      adopcion: '15/05/2020',
      desligo: '20/10/2022',
      contactable: true,
    },
    {
      nombre: 'Otro Dueño',
      adopcion: '01/03/2023',
      desligo: 'Presente',
      contactable: false,
    },
  ];
  </script>
  
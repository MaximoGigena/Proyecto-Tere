<template>
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
      
      <!-- Header -->
      <div class="bg-gray-800 text-white p-4">
        <h1 class="text-xl font-bold">Historial de la Mascota</h1>
      </div>
  
      <!-- Tabs como links -->
      <div class="flex border-b border-gray-200 bg-gray-100">
        <router-link
          v-for="(tab, index) in tabs"
          :key="index"
          :to="tab.to"
          class="flex items-center justify-center gap-2 flex-1 py-2 text-sm font-medium text-center"
          :class="{
            'border-b-2 border-blue-500 bg-white': $route.path.endsWith(tab.to),
            'text-gray-600 hover:bg-gray-200': !$route.path.endsWith(tab.to)
          }"
        >
          <font-awesome-icon :icon="['fas', tab.icon]" />
          <span>{{ tab.nombre }}</span>
        </router-link>
      </div>
  
      <!-- Aquí se cargan los componentes hijos -->
      <div class="p-4">
        <router-view />
      </div>
    </div>
  </template>
  
  <script setup>
  const tabs = [
    { nombre: 'Dueños', icon: 'user', to: '/revisar/dueños' },
    { nombre: 'Vacunas', icon: 'syringe', to: '/revisar/vacunas' },
    { nombre: 'Médico', icon: 'fa-solid stethoscope', to: '/revisar/historialMedico' },
  ]
  </script>
  
  
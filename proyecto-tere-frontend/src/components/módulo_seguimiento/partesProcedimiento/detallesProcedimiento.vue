<template>
  <div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <h4 class="font-semibold text-gray-700 mb-3 text-lg">Información Básica</h4>
        <div class="space-y-2">
          <p><strong class="text-gray-700">Veterinario:</strong> {{ procedimiento.veterinario || 'No especificado' }}</p>
          <p><strong class="text-gray-700">Estado:</strong> 
            <span :class="{
              'bg-green-100 text-green-800': procedimiento.estado === 'Completado',
              'bg-yellow-100 text-yellow-800': procedimiento.estado === 'Pendiente',
              'bg-red-100 text-red-800': procedimiento.estado === 'Cancelado'
            }" class="px-2 py-1 rounded-full text-xs font-medium">
              {{ procedimiento.estado || 'Completado' }}
            </span>
          </p>
          <p><strong class="text-gray-700">Paciente:</strong> {{ procedimiento.paciente || 'No especificado' }}</p>
        </div>
      </div>
      
      <div>
        <h4 class="font-semibold text-gray-700 mb-3 text-lg">Detalles Médicos</h4>
        <div class="space-y-2">
          <p><strong class="text-gray-700">Anestesia:</strong> {{ procedimiento.anestesia || 'No especificada' }}</p>
          <p><strong class="text-gray-700">Duración:</strong> {{ procedimiento.duracion || 'No especificada' }}</p>
          <p><strong class="text-gray-700">Complicaciones:</strong> {{ procedimiento.complicaciones || 'Ninguna' }}</p>
        </div>
      </div>
    </div>
    
    <div class="mt-6">
      <h4 class="font-semibold text-gray-700 mb-3 text-lg">Observaciones</h4>
      <div class="bg-gray-50 p-4 rounded-lg">
        <p class="text-gray-600">{{ procedimiento.observaciones || 'No hay observaciones registradas' }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  procedimiento: {
    type: Object,
    required: true
  }
})
</script>
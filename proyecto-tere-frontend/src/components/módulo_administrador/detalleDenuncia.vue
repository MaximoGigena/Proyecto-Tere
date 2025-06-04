<template>
  <div class="max-w-2xl mx-auto p-6 bg-white rounded-xl shadow space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Detalle de denuncia</h1>

    <div class="space-y-4 text-gray-700 text-sm">
      <div>
        <span class="font-semibold">Razón:</span> {{ denuncia.razon }}
      </div>
      <div>
        <span class="font-semibold">Gravedad:</span>
        <span :class="gravedadColor(denuncia.gravedad)">{{ denuncia.gravedad }}</span>
      </div>
      <div>
        <span class="font-semibold">Fecha:</span> {{ formatFecha(denuncia.fecha) }}
      </div>
      <div>
        <span class="font-semibold">Estado:</span>
        <span :class="estadoColor(denuncia.estado)">{{ denuncia.estado }}</span>
      </div>
      <div>
        <span class="font-semibold">Descripción:</span>
        <p class="mt-1 text-gray-600 whitespace-pre-wrap">{{ denuncia.descripcion }}</p>
      </div>

      <div v-if="denuncia.evidenciaUrl">
        <span class="font-semibold">Evidencia:</span>
        <img
          :src="denuncia.evidenciaUrl"
          alt="Evidencia"
          class="mt-2 max-w-full rounded shadow border"
        />
      </div>
    </div>

    <div class="text-right">
      <button @click="$emit('volver')" class="text-blue-600 hover:underline text-sm">
        ← Volver
      </button>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  denuncia: {
    type: Object,
    required: true
  }
})

// Función para formato de fecha
function formatFecha(fechaStr) {
  return new Date(fechaStr).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Colores por gravedad
function gravedadColor(gravedad) {
  switch (gravedad) {
    case 'Alta':
      return 'text-red-600 font-semibold'
    case 'Media':
      return 'text-yellow-600 font-semibold'
    case 'Baja':
      return 'text-green-600 font-semibold'
    default:
      return ''
  }
}

// Colores por estado
function estadoColor(estado) {
  switch (estado) {
    case 'Pendiente':
      return 'text-gray-500 italic'
    case 'En revisión':
      return 'text-yellow-600'
    case 'Resuelta':
      return 'text-green-700 font-semibold'
    default:
      return ''
  }
}
</script>

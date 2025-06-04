<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Mis denuncias enviadas</h1>

    <!-- Filtro de orden -->
    <div class="bg-white p-4 rounded-xl shadow flex flex-wrap items-center gap-4">
      <label class="text-sm font-medium text-gray-700">
        Ordenar por:
        <select v-model="orden" class="ml-2 border border-gray-300 rounded px-3 py-1">
          <option value="razon">Razón</option>
          <option value="gravedad">Gravedad</option>
          <option value="fecha">Fecha</option>
        </select>
      </label>
    </div>

    <!-- Tabla o detalle -->
    <div class="bg-white p-6 rounded-xl shadow">
      <template v-if="detalleActual">
        <!-- Vista de detalle -->
        <DetalleDenuncia :denuncia="detalleActual" @volver="detalleActual = null" />
      </template>

      <template v-else>
        <!-- Tabla de denuncias -->
        <table class="w-full text-sm text-left text-gray-700">
          <thead class="text-xs text-gray-500 uppercase bg-gray-100">
            <tr>
              <th class="px-4 py-2">Razón</th>
              <th class="px-4 py-2">Gravedad</th>
              <th class="px-4 py-2">Fecha</th>
              <th class="px-4 py-2 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(denuncia, index) in denunciasOrdenadas"
              :key="index"
              class="border-b hover:bg-gray-50"
            >
              <td class="px-4 py-2">{{ denuncia.razon }}</td>
              <td class="px-4 py-2">{{ denuncia.gravedad }}</td>
              <td class="px-4 py-2">{{ formatFecha(denuncia.fecha) }}</td>
              <td class="px-4 py-2 text-right">
                <button
                  @click="verDetalle(denuncia)"
                  class="text-blue-600 hover:underline"
                >
                  Ver detalles
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!denunciasOrdenadas.length" class="text-center text-gray-400 italic mt-4">
          Aún no has enviado ninguna denuncia.
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import DetalleDenuncia from './detalleDenuncia.vue' // Asegúrate que el path sea correcto

const orden = ref('fecha')
const detalleActual = ref(null)

const denuncias = ref([
  {
    razon: 'Maltrato animal',
    gravedad: 'Alta',
    fecha: '2025-05-10',
    estado: 'En revisión',
    descripcion: 'El adoptante deja a la mascota sin agua por días.',
    evidenciaUrl: 'https://placekitten.com/400/300'
  },
  {
    razon: 'Falsedad de datos',
    gravedad: 'Media',
    fecha: '2025-05-21',
    estado: 'Pendiente',
    descripcion: 'El usuario proporcionó documentos falsos.',
    evidenciaUrl: null
  }
])

const denunciasOrdenadas = computed(() => {
  return [...denuncias.value].sort((a, b) => {
    if (orden.value === 'fecha') return new Date(b.fecha) - new Date(a.fecha)
    return a[orden.value].localeCompare(b[orden.value])
  })
})

function verDetalle(denuncia) {
  detalleActual.value = denuncia
}

function formatFecha(fechaStr) {
  const options = { year: 'numeric', month: 'short', day: 'numeric' }
  return new Date(fechaStr).toLocaleDateString(undefined, options)
}
</script>



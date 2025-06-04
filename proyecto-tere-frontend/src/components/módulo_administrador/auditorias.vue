<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Auditorías del sistema</h1>

    <!-- Filtros simples (placeholder) -->
    <div class="bg-white p-4 rounded-xl shadow flex flex-col sm:flex-row items-start sm:items-center gap-4">
      <label class="text-sm font-medium text-gray-700">
        Usuario:
        <input v-model="filtroUsuario" type="text" placeholder="Nombre o ID" class="ml-2 mt-1 sm:mt-0 border border-gray-300 rounded px-3 py-1">
      </label>

      <label class="text-sm font-medium text-gray-700">
        Fecha:
        <input v-model="filtroFecha" type="date" class="ml-2 mt-1 sm:mt-0 border border-gray-300 rounded px-3 py-1">
      </label>

      <button
        @click="filtrarAuditorias"
        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded shadow ml-auto"
      >
        Filtrar
      </button>
    </div>

    <!-- Tabla de auditorías -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-gray-100 text-gray-700 font-semibold">
          <tr>
            <th class="px-6 py-3">Usuario</th>
            <th class="px-6 py-3">Acción</th>
            <th class="px-6 py-3">Fecha</th>
            <th class="px-6 py-3">Detalles</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(log, i) in auditoriasFiltradas" :key="i" class="border-t hover:bg-gray-50">
            <td class="px-6 py-3">{{ log.usuario }}</td>
            <td class="px-6 py-3">{{ log.accion }}</td>
            <td class="px-6 py-3">{{ log.fecha }}</td>
            <td class="px-6 py-3 text-gray-600 italic">{{ log.detalles }}</td>
          </tr>
          <tr v-if="auditoriasFiltradas.length === 0">
            <td colspan="4" class="text-center py-6 text-gray-400 italic">No se encontraron registros.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Datos simulados
const auditorias = ref([
  { usuario: 'admin1', accion: 'Login', fecha: '2025-06-02', detalles: 'Inicio de sesión exitoso' },
  { usuario: 'veterinario5', accion: 'Edición de mascota', fecha: '2025-06-01', detalles: 'Modificó ficha médica' },
  { usuario: 'refugioX', accion: 'Adopción', fecha: '2025-06-01', detalles: 'Marcó mascota como adoptada' },
  { usuario: 'admin1', accion: 'Eliminar usuario', fecha: '2025-05-30', detalles: 'Eliminó cuenta de usuario inactivo' },
])

// Filtros
const filtroUsuario = ref('')
const filtroFecha = ref('')

// Lógica de filtrado
const auditoriasFiltradas = computed(() => {
  return auditorias.value.filter(log => {
    const coincideUsuario = !filtroUsuario.value || log.usuario.toLowerCase().includes(filtroUsuario.value.toLowerCase())
    const coincideFecha = !filtroFecha.value || log.fecha === filtroFecha.value
    return coincideUsuario && coincideFecha
  })
})

function filtrarAuditorias() {
  // No es necesario porque el filtro es reactivo, pero podrías agregar lógica futura
}
</script>

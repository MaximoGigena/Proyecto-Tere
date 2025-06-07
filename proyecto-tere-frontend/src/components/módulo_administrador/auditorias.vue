<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Auditorías del sistema</h1>

    <!-- Filtros -->
    <div class="bg-white p-4 rounded-xl shadow flex flex-col sm:flex-row items-start sm:items-center gap-4">
      <label class="text-sm font-medium text-gray-700">
        ID Auditoría:
        <input v-model="filtroId" type="text" placeholder="Ej: 101" class="ml-2 mt-1 sm:mt-0 border border-gray-300 rounded px-3 py-1">
      </label>

      <label class="text-sm font-medium text-gray-700">
        Usuario:
        <input v-model="filtroUsuario" type="text" placeholder="Nombre de usuario" class="ml-2 mt-1 sm:mt-0 border border-gray-300 rounded px-3 py-1">
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

    <!-- Tabla con botón por fila -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-gray-100 text-gray-700 font-semibold">
          <tr>
            <th class="px-6 py-3">ID Auditoría</th>
            <th class="px-6 py-3">Usuario</th>
            <th class="px-6 py-3">Fecha</th>
            <th class="px-6 py-3">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(log, i) in auditoriasFiltradas" :key="i" class="border-t hover:bg-gray-50">
            <td class="px-6 py-3">{{ log.id }}</td>
            <td class="px-6 py-3">{{ log.usuario }}</td>
            <td class="px-6 py-3">{{ log.fecha }}</td>
            <td class="px-6 py-3">
              <button
                @click="descargarAuditoria(log)"
                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs shadow font-bold"
              >
                Descargar PDF
                <font-awesome-icon :icon="['fas', 'file-pdf']" class="text-xl"/>
              </button>
            </td>
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
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

// Datos simulados
const auditorias = ref([
  { id: 101, usuario: 'admin1', fecha: '2025-06-02' },
  { id: 102, usuario: 'admin1', fecha: '2025-06-01' },
  { id: 103, usuario: 'veterinario5', fecha: '2025-06-01' },
  { id: 104, usuario: 'refugioX', fecha: '2025-05-30' },
])

// Filtros
const filtroId = ref('')
const filtroUsuario = ref('')
const filtroFecha = ref('')

// Computed de filtrado
const auditoriasFiltradas = computed(() => {
  return auditorias.value.filter(log => {
    const coincideId = !filtroId.value || log.id.toString().includes(filtroId.value)
    const coincideUsuario = !filtroUsuario.value || log.usuario.toLowerCase().includes(filtroUsuario.value.toLowerCase())
    const coincideFecha = !filtroFecha.value || log.fecha === filtroFecha.value
    return coincideId && coincideUsuario && coincideFecha
  })
})

// PDF por auditoría
function descargarAuditoria(auditoria) {
  const doc = new jsPDF()
  doc.text('Auditoría del sistema', 14, 16)
  autoTable(doc, {
    startY: 25,
    head: [['Campo', 'Valor']],
    body: [
      ['ID Auditoría', auditoria.id],
      ['Usuario', auditoria.usuario],
      ['Fecha', auditoria.fecha],
    ],
    styles: { fontSize: 10 },
  })
  doc.save(`auditoria_${auditoria.id}.pdf`)
}

function filtrarAuditorias() {
  // Placeholder para lógica extra si se desea
}
</script>

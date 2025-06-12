<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold mb-4">Solicitudes de Veterinarios</h1>

    <!-- 🔍 Filtros -->
    <div class="bg-white shadow p-4 rounded-xl mb-6 flex flex-wrap gap-4">
      <input
        v-model="filtroNombre"
        type="text"
        placeholder="Buscar por nombre"
        class="border rounded px-3 py-2 w-full md:w-1/3"
      />
      <select v-model="filtroEspecialidad" class="border rounded px-3 py-2 w-full md:w-1/3">
        <option value="">Todas las especialidades</option>
        <option v-for="esp in especialidadesUnicas" :key="esp" :value="esp">{{ esp }}</option>
      </select>
    </div>

    <!-- 🧾 Lista de solicitudes filtradas -->
    <div v-if="solicitudesFiltradas.length === 0" class="text-gray-500">No hay solicitudes que coincidan.</div>

    <div v-else class="space-y-4">
      <div
        v-for="veterinario in solicitudesFiltradas"
        :key="veterinario.id"
        class="bg-white shadow p-4 rounded-xl flex justify-between items-center"
      >
        <div>
          <h2 class="text-lg font-semibold">{{ veterinario.nombre }}</h2>
          <p class="text-sm text-gray-600">{{ veterinario.email }}</p>
          <p class="text-sm text-gray-600">Matricula: {{ veterinario.matricula }}</p>
        </div>

        <div class="flex space-x-2">
          <button @click="verDetalle(veterinario)" class="bg-blue-500 hover:underline text-white px-3 py-1 rounded hover:bg-blue-800">Ver detalles</button>
          <button @click="aceptar(veterinario.id)" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-800">Aceptar</button>
          <button @click="rechazar(veterinario.id)" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-800">Rechazar</button>
        </div>
      </div>
    </div>

    <!-- Modal de detalle -->
    <div v-if="detalleVisible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-xl w-full max-w-md">
        <h3 class="text-xl font-bold mb-2">Detalle del Veterinario</h3>
        <p><strong>Nombre:</strong> {{ seleccionado.nombre }}</p>
        <p><strong>Email:</strong> {{ seleccionado.email }}</p>
        <p><strong>Teléfono:</strong> {{ seleccionado.telefono }}</p>
        <p><strong>Matrícula:</strong> {{ seleccionado.matricula }}</p>
        <p><strong>Especialidad:</strong> {{ seleccionado.especialidad }}</p>
        <button @click="detalleVisible = false" class="mt-4 bg-gray-300 px-4 py-1 rounded hover:bg-gray-400">Cerrar</button>
      </div>
    </div>
  </div>
  <div v-if="solicitudes.length === 0" class="text-gray-500 ml-4">No hay solicitudes pendientes.</div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Datos simulados (esto debería venir del backend vía API)
const solicitudes = ref([
  {
    id: 1,
    nombre: 'Dr. Juan Pérez',
    email: 'juanperez@vetmail.com',
    telefono: '123456789',
    matricula: 'MAT-4582',
    especialidad: 'Clínica de pequeños animales'
  },
  {
    id: 2,
    nombre: 'Dra. Laura Gómez',
    email: 'lauragomez@vetmail.com',
    telefono: '987654321',
    matricula: 'MAT-8721',
    especialidad: 'Cirugía veterinaria'
  }
])

const detalleVisible = ref(false)
const seleccionado = ref({})

const filtroNombre = ref('')
const filtroEspecialidad = ref('')

// 🧮 Computed: filtrar por nombre y especialidad
const solicitudesFiltradas = computed(() => {
  return solicitudes.value.filter(v => {
    const coincideNombre = v.nombre.toLowerCase().includes(filtroNombre.value.toLowerCase())
    const coincideEspecialidad = filtroEspecialidad.value === '' || v.especialidad === filtroEspecialidad.value
    return coincideNombre && coincideEspecialidad
  })
})

// Extraer especialidades únicas
const especialidadesUnicas = computed(() => {
  const set = new Set(solicitudes.value.map(v => v.especialidad))
  return Array.from(set)
})


function verDetalle(vet) {
  seleccionado.value = vet
  detalleVisible.value = true
}

function aceptar(id) {
  // Acá iría la llamada a la API (ej: axios.post('/api/solicitudes/aceptar', { id }))
  solicitudes.value = solicitudes.value.filter(v => v.id !== id)
  alert(`Solicitud aceptada (ID: ${id})`)
}

function rechazar(id) {
  // Acá también llamás a la API para rechazar
  solicitudes.value = solicitudes.value.filter(v => v.id !== id)
  alert(`Solicitud rechazada (ID: ${id})`)
}
</script>

<style scoped>
/* Opcional: ocultar scroll del fondo cuando el modal está activo */
body.modal-open {
  overflow: hidden;
}
</style>

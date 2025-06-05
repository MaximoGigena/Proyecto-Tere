<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Denuncias recibidas</h1>

    <!-- Filtro de orden -->
    <!-- Filtro de orden y filtro de razón -->
      <div class="bg-white p-4 rounded-xl shadow flex flex-wrap items-center gap-4">
        <!-- Ordenar -->
        <label class="text-sm font-medium text-gray-700">
          Ordenar por:
          <select v-model="orden" class="ml-2 border border-gray-300 rounded px-3 py-1">
            <option value="razon">Razón</option>
            <option value="gravedad">Gravedad</option>
            <option value="fecha">Fecha</option>
            <option value="fecha">Estado</option>
          </select>
        </label>

        <!-- Filtrar -->
        <label class="text-sm font-medium text-gray-700">
          Filtrar por razón:
          <select v-model="filtroRazon" class="ml-2 border border-gray-300 rounded px-3 py-1">
            <option value="">Todas</option>
            <option v-for="razon in razonesUnicas" :key="razon" :value="razon">
              {{ razon }}
            </option>
          </select>
        </label>

        <!-- Filtrar -->
        <label class="text-sm font-medium text-gray-700">
          Filtrar por gravedad:
          <select v-model="filtroGravedad" class="ml-2 border border-gray-300 rounded px-3 py-1">
            <option value="">Todas</option>
            <option v-for="gravedad in razonesGravedad" :key="gravedad" :value="gravedad">
              {{ gravedad }}
            </option>
          </select>
        </label>

        <!-- Filtrar -->
        <label class="text-sm font-medium text-gray-700">
          Filtrar por estado:
          <select v-model="filtroEstado" class="ml-2 border border-gray-300 rounded px-3 py-1">
            <option value="">Todas</option>
            <option v-for="estado in razonesEstado" :key="estado" :value="estado">
              {{ estado }}
            </option>
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
          <thead class="text-xs text-white uppercase bg-blue-500">
            <tr>
              <th class="px-4 py-2">Razón</th>
              <th class="px-4 py-2">Gravedad</th>
              <th class="px-4 py-2">Fecha</th>
              <th class="px-4 py-2">Estado</th>
              <th class="px-4 py-2 text-center">Acciones</th>
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
              <td class="px-4 py-2">{{ denuncia.estado }}</td>
              <td class="px-4 py-2 text-right">
                <button
                  @click="verDetalle(denuncia)"
                  class="bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium mr-28 px-3 py-1.5 rounded-lg transition-colors duration-150"
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
const filtroRazon = ref('')
const filtroGravedad = ref('')
const filtroEstado= ref('')

const denuncias = ref([
  {
    denunciante: 'Usuario1423',
    denunciado: 'Usuario123',
    razon: 'Maltrato animal',
    gravedad: 'Alta',
    fecha: '2025-05-10',
    estado: 'En revisión',
    descripcion: 'El adoptante deja a la mascota sin agua por días.',
    evidenciaUrl: 'https://assets.nationbuilder.com/texashumane/pages/493/features/original/animal-abuse.png?1548995529'
  },
  {
    denunciante: 'Usuario1424',
    denunciado: 'Usuario124',
    razon: 'Falsedad de datos',
    gravedad: 'Media',
    fecha: '2025-05-21',
    estado: 'Pendiente',
    descripcion: 'El usuario proporcionó documentos falsos.',
    evidenciaUrl: null
  },
  {
    denunciante: 'Usuario1429',
    denunciado: 'Usuario125',
    razon: 'Comportamiento inapropiado',
    gravedad: 'Baja',
    fecha: '2025-05-12',
    estado: 'Pendiente',
    descripcion: 'El usuario muestra un comportamiento inpropiado frente a otros usuarios.',
    evidenciaUrl: 'https://cdn.pixabay.com/photo/2020/06/25/14/08/whatsapp-5339803_1280.png'
  },
  {
    denunciante: 'Usuario1428',
    denunciado: 'Usuario126',
    razon: 'Comportamiento inapropiado',
    gravedad: 'Baja',
    fecha: '2025-05-12',
    estado: 'Pendiente',
    descripcion: 'El usuario muestra un comportamiento inpropiado frente a otros usuarios.',
    evidenciaUrl: 'https://cdn.pixabay.com/photo/2020/06/25/14/08/whatsapp-5339803_1280.png'
  },
  {
    denunciante: 'Usuario1427',
    denunciado: 'Usuario127',
    razon: 'Maltrato animal',
    gravedad: 'Alta',
    fecha: '2025-05-14',
    estado: 'En revisión',
    descripcion: 'El adoptante deja a la mascota sin agua por días.',
    evidenciaUrl: 'https://assets.nationbuilder.com/texashumane/pages/493/features/original/animal-abuse.png?1548995529'
  },
  {
    denunciante: 'Usuario1426',
    denunciado: 'Usuario128',
    razon: 'Falsedad de datos',
    gravedad: 'Media',
    fecha: '2025-05-29',
    estado: 'Pendiente',
    descripcion: 'El usuario proporcionó documentos falsos.',
    evidenciaUrl: null
  },
  {
    denunciante: 'Usuario1425',
    denunciado: 'Usuario129',
    razon: 'Comportamiento inapropiado',
    gravedad: 'Baja',
    fecha: '2025-05-15',
    estado: 'Pendiente',
    descripcion: 'El usuario muestra un comportamiento inpropiado frente a otros usuarios.',
    evidenciaUrl:'https://cdn.pixabay.com/photo/2020/06/25/14/08/whatsapp-5339803_1280.png'
  }
])

const denunciasOrdenadas = computed(() => {
  let lista = [...denuncias.value]

  // Aplicar filtro
  if (filtroRazon.value) {
    lista = lista.filter(d => d.razon === filtroRazon.value)
  }

   // Aplicar filtro
  if (filtroGravedad.value) {
    lista = lista.filter(d => d.gravedad === filtroGravedad.value)
  }

    // Aplicar filtro
  if (filtroEstado.value) {
    lista = lista.filter(d => d.estado === filtroEstado.value)
  }

  // Aplicar orden
  return lista.sort((a, b) => {
    if (orden.value === 'fecha') return new Date(b.fecha) - new Date(a.fecha)
    return a[orden.value].localeCompare(b[orden.value])
  })
})


const razonesUnicas = computed(() => {
  const todas = denuncias.value.map(d => d.razon)
  return [...new Set(todas)]
})

const razonesGravedad = computed(() => {
  const todas = denuncias.value.map(d => d.gravedad)
  return [...new Set(todas)]
})

const razonesEstado = computed(() => {
  const todas = denuncias.value.map(d => d.estado)
  return [...new Set(todas)]
})

function verDetalle(denuncia) {
  detalleActual.value = denuncia
}

function formatFecha(fechaStr) {
  const options = { year: 'numeric', month: 'short', day: 'numeric' }
  return new Date(fechaStr).toLocaleDateString(undefined, options)
}
</script>



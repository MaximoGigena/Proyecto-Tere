<template>
  <div class="p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
      <font-awesome-icon :icon="['fas', 'notes-medical']" />
      Episodios Médicos
    </h2>

    <div class="mb-4 flex justify-end">
      <button
        @click="abrirNuevoEpisodio"
        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow"
      >
        + Nuevo Episodio
      </button>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div
        v-for="episodio in episodios"
        :key="episodio.id"
        @click="seleccionarEpisodio(episodio)"
        class="relative border rounded-xl p-4 shadow-sm bg-white cursor-pointer hover:ring-2 hover:ring-blue-300 transition"
      >
        <div class="absolute right-3 top-3 flex space-x-2 z-10">
          <button
            @click.stop="editarEpisodio(episodio)"
            class="text-gray-500 hover:text-blue-600"
          >
            <font-awesome-icon :icon="['fas', 'pen']" />
          </button>
          <button
            @click.stop="eliminarEpisodio(episodio.id)"
            class="text-gray-500 hover:text-red-600"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
        </div>

        <h3 class="font-bold text-gray-800 text-lg mb-1">{{ episodio.titulo }}</h3>
        <p class="text-sm text-gray-600">Fecha: {{ episodio.fecha }}</p>
        <p class="text-gray-600 text-sm truncate">Descripción: {{ episodio.descripcion }}</p>
      </div>
    </div>

    <div v-if="episodioSeleccionado" class="mt-6 border-t pt-4">
      <h3 class="text-xl font-semibold text-gray-700 mb-2">Detalles del Episodio</h3>
      <p><strong>Título:</strong> {{ episodioSeleccionado.titulo }}</p>
      <p><strong>Fecha:</strong> {{ episodioSeleccionado.fecha }}</p>
      <p><strong>Descripción:</strong> {{ episodioSeleccionado.descripcion }}</p>
      <p><strong>Observaciones:</strong> {{ episodioSeleccionado.observaciones }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const episodios = ref([
  {
    id: 1,
    titulo: 'Consulta por vómitos',
    fecha: '2025-06-10',
    descripcion: 'Revisión por vómitos recurrentes, posible gastritis.',
    observaciones: 'Se recetó omeprazol por 5 días.',
  },
  {
    id: 2,
    titulo: 'Control de post-quirúrgico',
    fecha: '2025-06-20',
    descripcion: 'Revisión de puntos tras cirugía.',
    observaciones: 'Cicatrización correcta, sin complicaciones.',
  },
])

const episodioSeleccionado = ref(null)

function abrirNuevoEpisodio() {
  console.log('Abrir formulario para nuevo episodio')
}

function editarEpisodio(episodio) {
  console.log('Editar episodio:', episodio)
}

function eliminarEpisodio(id) {
  console.log('Eliminar episodio ID:', id)
}

function seleccionarEpisodio(episodio) {
  episodioSeleccionado.value = episodio
}
</script>

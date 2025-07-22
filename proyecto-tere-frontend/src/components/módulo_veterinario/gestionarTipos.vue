<template>
  <div class="p-8">
    <h1 class="text-2xl font-bold mb-4">Gestión de Tipos de Tratamientos Médicos</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div v-for="categoria in categorias" :key="categoria.nombre" class="border p-4 rounded-lg shadow bg-white">
        <h2 class="text-lg font-semibold mb-2">{{ categoria.nombre }}</h2>

        <ul class="space-y-2 mb-4">
          <li v-for="(item, index) in categoria.items" :key="index" class="flex justify-between items-center bg-gray-100 px-3 py-1 rounded">
            <span>{{ item }}</span>
            <button @click="eliminarItem(categoria.nombre, index)" class="text-red-500 hover:text-red-700">Eliminar</button>
          </li>
        </ul>

        <div class="flex gap-2">
          <input
            v-model="categoria.nuevoItem"
            type="text"
            placeholder="Nuevo tipo..."
            class="border rounded px-2 py-1 w-full"
          />
          <button
            @click="agregarItem(categoria.nombre)"
            class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
          >
            Añadir
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'

const categorias = reactive([
  { nombre: 'Tipos de Vacunas', items: [], nuevoItem: '' },
  { nombre: 'Tipos de Desparasitaciones', items: [], nuevoItem: '' },
  { nombre: 'Tipos de Revisiones Médicas', items: [], nuevoItem: '' },
  { nombre: 'Tipos de Alergias', items: [], nuevoItem: '' },
  { nombre: 'Tipos de Cirugías', items: [], nuevoItem: '' },
  { nombre: 'Tipos de Terapias', items: [], nuevoItem: '' },
  { nombre: 'Tipos de Fármacos', items: [], nuevoItem: '' },
  { nombre: 'Tipos de Diagnósticos', items: [], nuevoItem: '' },
  { nombre: 'Tipos de Paliativos', items: [], nuevoItem: '' }
])

function agregarItem(nombreCategoria) {
  const categoria = categorias.find(c => c.nombre === nombreCategoria)
  if (categoria.nuevoItem.trim() !== '') {
    categoria.items.push(categoria.nuevoItem.trim())
    categoria.nuevoItem = ''
  }
}

function eliminarItem(nombreCategoria, index) {
  const categoria = categorias.find(c => c.nombre === nombreCategoria)
  categoria.items.splice(index, 1)
}
</script>

<style scoped>
h1, h2 {
  color: #333;
}
</style>

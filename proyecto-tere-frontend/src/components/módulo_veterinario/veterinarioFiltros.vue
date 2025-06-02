<template>
  <div class="p-4 max-w-xl mx-auto">
    <h2 class="text-xl text-center font-semibold mb-4">Filtros disponibles</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Especie -->
      <div>
        <label class="block text-sm font-medium mb-1">Especie</label>
        <select v-model="filtros.especie" class="w-full rounded border p-2">
          <option value="">Todas</option>
          <option value="perro">Perro</option>
          <option value="gato">Gato</option>
          <option value="exotico">Exótico</option>
        </select>
      </div>

      <!-- Edad -->
      <div>
        <label class="block text-sm font-medium mb-1">Edad (años)</label>
        <input type="number" v-model.number="filtros.edad" min="0" class="w-full rounded border p-2" placeholder="Edad exacta o mínima" />
      </div>

      <!-- Sexo -->
      <div>
        <label class="block text-sm font-medium mb-1">Sexo</label>
        <select v-model="filtros.sexo" class="w-full rounded border p-2">
          <option value="">Cualquiera</option>
          <option value="macho">Macho</option>
          <option value="hembra">Hembra</option>
        </select>
      </div>

      <!-- Estado clínico -->
      <div>
        <label class="block text-sm font-medium mb-1">Estado clínico</label>
        <select v-model="filtros.estado" class="w-full rounded border p-2">
          <option value="">Todos</option>
          <option value="estable">Estable</option>
          <option value="critico">Crítico</option>
          <option value="en tratamiento">En tratamiento</option>
        </select>
      </div>

      <!-- Tipo de intervención -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Tipo de intervención</label>
        <div class="flex flex-wrap gap-2">
          <label class="flex items-center gap-2">
            <input type="checkbox" v-model="filtros.intervenciones" value="vacunación" />
            <span>Vacunación</span>
          </label>
          <label class="flex items-center gap-2">
            <input type="checkbox" v-model="filtros.intervenciones" value="castración" />
            <span>Castración</span>
          </label>
          <label class="flex items-center gap-2">
            <input type="checkbox" v-model="filtros.intervenciones" value="tratamiento" />
            <span>Tratamiento</span>
          </label>
          <label class="flex items-center gap-2">
            <input type="checkbox" v-model="filtros.intervenciones" value="cirugía" />
            <span>Cirugía</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Botones -->
    <div class="flex justify-end gap-2 mt-6">
      <button @click="limpiarFiltros" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Limpiar</button>
      <button @click="aplicarFiltros" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Aplicar filtros</button>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'



const router = useRouter()

const filtros = reactive({
  especie: '',
  edad: null,
  sexo: '',
  estado: '',
  intervenciones: [],
})

function limpiarFiltros() {
  filtros.especie = ''
  filtros.edad = null
  filtros.sexo = ''
  filtros.estado = ''
  filtros.intervenciones = []
}

function aplicarFiltros() {
  console.log('Filtros aplicados:', { ...filtros })
  // Podés emitir un evento o hacer una consulta acá
}
</script>

<style>  
 
</style> 
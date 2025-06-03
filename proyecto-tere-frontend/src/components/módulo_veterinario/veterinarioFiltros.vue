<template>
  <div class="p-4 max-w-xl mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Especie -->

      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Especie</label>
        <button
          @click="mostrarTaxonomias = !mostrarTaxonomias"
          class="w-full text-left bg-white border rounded p-2 hover:bg-gray-100"
        >
          {{ filtros.especie.length ? filtros.especie.join(', ') : 'Seleccionar taxonomías' }}
        </button>

        <!-- Selector de taxonomías (condicional) -->
        <div v-if="mostrarTaxonomias" class="mt-2 border rounded p-3 bg-gray-50">
          <div class="grid gap-2">
            <div
              v-for="taxonomia in taxonomias"
              :key="taxonomia"
              class="flex items-center gap-2"
            >
              <input
                type="checkbox"
                :id="taxonomia"
                :value="taxonomia"
                v-model="filtros.especie"
                class="accent-green-600"
              />
              <label :for="taxonomia" class="text-gray-800">{{ taxonomia }}</label>
            </div>
          </div>

          <!-- Opcional: botón para cerrar -->
          <div class="flex justify-center mt-3">
            <button @click="mostrarTaxonomias = false" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-all"
            >
              Listo
            </button>
          </div>
        </div>
      </div>


      <!-- Edad -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Edad (rango etario)</label>
        <button
          @click="mostrarEdad = !mostrarEdad"
          class="w-full text-left bg-white border rounded p-2 hover:bg-gray-100"
        >
          {{ filtros.edad.length ? filtros.edad.join(', ') : 'Seleccionar edad' }}
        </button>

        <div v-if="mostrarEdad" class="mt-2 border rounded p-3 bg-gray-50">
          <div v-for="opcion in opcionesEdad" :key="opcion" class="flex items-center gap-2 mb-1">
            <input
              type="checkbox"
              :id="opcion"
              :value="opcion"
              v-model="filtros.edad"
              class="accent-blue-600"
            />
            <label :for="opcion" class="text-sm text-gray-700">{{ opcion }}</label>
          </div>

          <div class="flex justify-center mt-3">
            <button @click="mostrarEdad = false" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-all"
            >
              Listo
            </button>
          </div>
        </div>
      </div>



      <!-- Sexo -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Sexo</label>
        <button
          @click="mostrarSexo = !mostrarSexo"
          class="w-full text-left bg-white border rounded p-2 hover:bg-gray-100"
        >
          {{ filtros.sexo.length ? filtros.sexo.join(', ') : 'Seleccionar sexo' }}
        </button>

        <div v-if="mostrarSexo" class="mt-2 border rounded p-3 bg-gray-50">
          <div v-for="opcion in opcionesSexo" :key="opcion" class="flex items-center gap-2 mb-1">
            <input
              type="checkbox"
              :id="'sexo-' + opcion"
              :value="opcion"
              v-model="filtros.sexo"
              class="accent-blue-600"
            />
            <label :for="'sexo-' + opcion" class="text-sm text-gray-700 capitalize">{{ opcion }}</label>
          </div>

          <div class="flex justify-center mt-3">
            <button @click="mostrarSexo = false" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-all"
            >
              Listo
            </button>
          </div>
        </div>
      </div>
      
      <!-- Filtro de Ubicación -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Ubicación</label>

        <button
          @click="mostrarUbicacion = !mostrarUbicacion"
          class="w-full text-left bg-white border rounded p-2 hover:bg-gray-100"
        >
          {{ filtros.ubicacion ? filtros.ubicacion : 'Seleccionar ubicación' }}
        </button>

        <div v-if="mostrarUbicacion" class="mt-2 border rounded p-3 bg-gray-50">
          <input
            type="text"
            v-model="busquedaUbicacion"
            @input="filtrarLocalidades"
            class="w-full rounded border p-2"
            placeholder="Buscar localidad..."
          />

          <ul v-if="sugerenciasUbicacion.length" class="mt-2 max-h-40 overflow-y-auto">
            <li
              v-for="(localidad, index) in sugerenciasUbicacion"
              :key="index"
              @click="seleccionarUbicacion(localidad)"
              class="p-2 cursor-pointer hover:bg-gray-200 rounded"
            >
              {{ localidad }}
            </li>
          </ul>
        </div>
      </div>


    </div>

    <!-- Botones -->
    <div class="flex justify-center gap-2 mt-6">
      <button @click="limpiarFiltros" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Limpiar</button>
      <button @click="aplicarFiltros" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Aplicar filtros</button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

// Router
const router = useRouter()

// Estado de los filtros visibles
const mostrarTaxonomias = ref(false)
const mostrarEdad = ref(false)
const mostrarSexo = ref(false)
const mostrarUbicacion = ref(false)

// Filtros seleccionados
const filtros = reactive({
  especie: [],
  edad: [],
  sexo: [],
  ubicacion: ''
})

// Datos base
const taxonomias = [
  'Perros', 'Gatos', 'Aves', 'Roedores', 'Reptiles', 'Peces', 'Animales de granja', 'Exóticos'
]
const opcionesEdad = ['Cachorro', 'Joven', 'Adulto', 'Abuelo']
const opcionesSexo = ['Macho', 'Hembra']
const localidadesDisponibles = [
  'Buenos Aires', 'Córdoba', 'Rosario', 'Mendoza', 'La Plata',
  'San Miguel de Tucumán', 'Salta', 'Mar del Plata', 'San Juan', 'Resistencia', 'Neuquén'
]

// Ubicación - autocompletado
const busquedaUbicacion = ref('')
const sugerenciasUbicacion = ref([])

function filtrarLocalidades() {
  const query = busquedaUbicacion.value.toLowerCase()
  sugerenciasUbicacion.value = localidadesDisponibles.filter(loc =>
    loc.toLowerCase().includes(query)
  )
}

function seleccionarUbicacion(localidad) {
  filtros.ubicacion = localidad
  busquedaUbicacion.value = ''
  sugerenciasUbicacion.value = []
  mostrarUbicacion.value = false
}

// Acciones
function limpiarFiltros() {
  filtros.especie = []
  filtros.edad = []
  filtros.sexo = []
  filtros.ubicacion = ''
}

function aplicarFiltros() {
  console.log('Filtros aplicados:', { ...filtros })
}
</script>


<style>  
 
</style> 
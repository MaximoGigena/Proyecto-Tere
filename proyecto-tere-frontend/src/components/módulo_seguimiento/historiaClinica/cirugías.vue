<template>
  <div class="p-4 min-w-[400px] flex flex-col h-full">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'hand-holding-medical']" class="mr-2" />
      Procedimientos Quirúrgicos
    </h2>
    <p class="flex-grow">Contenido del las cirugías..</p>
    <div class="space-y-4">
      <div
        v-for="cirugia in cirugias"
        :key="cirugia.id"
        class="relative border rounded-xl p-4 shadow-sm bg-white cursor-pointer hover:shadow-md transition"
        @click="abrirProcedimiento(cirugia)"
      >
        <!-- Íconos de acción en la esquina superior -->
        <div 
          v-if="$route.path.startsWith('/veterinarios')"
          class="absolute right-3 top-3 flex space-x-2">
          <button
            class="text-gray-500 hover:text-blue-600 transition"
            @click.stop="editarCirugia(cirugia)"
          >
            <font-awesome-icon :icon="['fas', 'pen']" />
          </button>
          <button
            class="text-gray-500 hover:text-red-600 transition"
            @click.stop="eliminarCirugia(cirugia.id)"
          >
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
          <button
            @click.stop="abrirRegistroCirugia"
            class="text-white bg-orange-600 rounded-full px-1 py-1 text-base font-bold shadow-md hover:bg-orange-700 hover:scale-105 transition transform duration-200"
          >
            Derivar
          </button>
        </div>
        
        <h3 class="text-lg font-bold text-gray-700 mb-2">{{ cirugia.nombre }}</h3>
        <p class="text-gray-600"><strong>Fecha:</strong> {{ cirugia.fecha }}</p>
      </div>
    </div>

    <div
      v-if="$route.path.startsWith('/veterinarios')"
      class="mt-4 flex justify-center"
    >
      <button
        @click="abrirRegistroCirugia"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Cirugía
      </button>
    </div>
  </div>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const currentRoute = useRoute()


const abrirProcedimiento = (cirugia) => {
  // Crear objeto de query preservando los parámetros existentes
  const queryParams = {
    ...currentRoute.query,
    from: currentRoute.fullPath // Guardamos la ruta completa actual
  }
  
  // Eliminar parámetros que no queremos pasar
  delete queryParams.id
  delete queryParams.currentTab
  
  router.push({
    name: 'procedimiento-detalles',
    params: { id: cirugia.id },
    query: queryParams
  })
}

const abrirRegistroCirugia = () => {
  router.push({
    path: '/registro/cirugia',
    query: {
      from: '/historialClinico/cirugias'
    }
  })
}

const editarCirugia = (cirugia) => {
  console.log('Editar cirugía:', cirugia)
  // Aquí puedes implementar la lógica de edición
}

const eliminarCirugia = (id) => {
  console.log('Eliminar cirugía con ID:', id)
  // Aquí puedes implementar la lógica de eliminación
}

const cirugias = [
  {
    id: 1,
    nombre: 'Esterilización',
    fecha: '2025-06-12',
  },
  {
    id: 2,
    nombre: 'Extracción de tumor',
    fecha: '2025-05-22',
  },
]
</script>
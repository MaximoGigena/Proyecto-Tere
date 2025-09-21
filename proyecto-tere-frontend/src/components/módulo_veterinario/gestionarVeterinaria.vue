<template>
  <div class="p-6 max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Columna Derecha (ahora izquierda): Lista de centros -->
      <div>
        <h2 class="text-xl font-semibold mb-4">Centros Veterinarios</h2>
        <div class="space-y-4">
          <div 
            v-for="(c, index) in listaCentros" 
            :key="index" 
            @click="seleccionarCentro(c)"
            class="border-2 border-dashed border-gray-400 rounded-xl p-4 cursor-pointer hover:bg-gray-50 relative"
          >
            <p class="font-bold">{{ c.nombre }}</p>
            <p>Dirección: {{ c.direccion }}</p>
            <p>Horarios: {{ c.horarios }}</p>

            <!-- Botones de acción -->
            <div class="absolute bottom-2 right-3 flex items-center gap-3">
              <button @click.stop="editarCentro(c)" class="text-gray-700 hover:text-gray-900">
                <i class="fas fa-pen"></i>
              </button>
              <button @click.stop="eliminarCentro(index)" class="text-red-500 hover:text-red-700">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Botón nuevo centro -->
        <div class="mt-6">
          <button @click="registrarNuevoCentro" class="bg-green-600 text-white px-4 py-2 rounded-xl hover:bg-green-700 w-full">
            + Nuevo Centro
          </button>
        </div>
      </div>

      <!-- Contenido del centro seleccionado (derecha en desktop) -->
      <div class="border-l border-gray-300 pl-6">
        <!-- Aquí renderizamos contenidoCentro.vue dinámicamente -->
        <ContenidoCentro v-if="centroSeleccionado" :centro="centroSeleccionado" />
        
        <!-- Mensaje cuando no hay centro seleccionado -->
        <div v-else class="text-gray-400 text-center mt-10">
          Seleccioná un centro para ver los detalles
        </div>
      </div>
  </div>

</template>


<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import ContenidoCentro from './contenidoCentro.vue'  // importamos la vista

const router = useRouter() 

const listaCentros = ref([])
const centroSeleccionado = ref(null) // ahora guardamos el objeto seleccionado

onMounted(() => {
  listaCentros.value = [
    {
      nombre: 'Vet San Roque',
      direccion: 'Av. Belgrano 1234',
      telefono: '11-4567-8901',
      email: 'sanroque@vet.com',
      horarios: '8am a 9pm',
      servicios: ['Vacunación', 'Castraciones'],
      img: 'https://cdn.pixabay.com/photo/2016/11/29/03/53/clinic-1869223_960_720.jpg',
      descripcion: 'Centro veterinario especializado en atención integral de mascotas.',
      experiencia: '15 años',
      especialidades: ['Medicina general', 'Cirugía', 'Dermatología'],
      ubicacion: 'Buenos Aires, Argentina',
      fotos: [
        'https://cdn.pixabay.com/photo/2017/08/10/05/01/animal-2619350_960_720.jpg'
      ]
    },
    {
      nombre: 'Vet Animalia',
      direccion: 'Calle 456',
      telefono: '11-1234-5678',
      email: 'animalia@vet.com',
      horarios: '9am a 6pm',
      servicios: ['Consultas', 'Vacunación'],
      img: 'https://cdn.pixabay.com/photo/2016/11/29/03/53/clinic-1869223_960_720.jpg',
      descripcion: 'Atención integral para todas las mascotas.',
      experiencia: '10 años',
      especialidades: ['Medicina general', 'Dermatología'],
      ubicacion: 'Buenos Aires, Argentina',
      fotos: [
        'https://cdn.pixabay.com/photo/2017/01/30/21/45/vet-2020140_960_720.jpg'
      ]
    }
  ]
})

function seleccionarCentro(c) {
  centroSeleccionado.value = c
}

function editarCentro(c) {
  seleccionarCentro(c)
}

function eliminarCentro(index) {
  listaCentros.value.splice(index, 1)
  if (centroSeleccionado.value === listaCentros.value[index]) {
    centroSeleccionado.value = null
  }
}

function registrarNuevoCentro() {
   router.push('/registro/registroCentroVeterinario') 
}
</script>




<!-- vacunas -->
 <template>
  <div class="p-4 w-full flex flex-col h-full min-h-[400px]">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'vial-circle-check']" class="mr-2" />
      Vacunas de la Mascota
    </h2>
    <p class="flex-grow">Contenido del las Vacunas..</p>
   
    <div
      v-if="$route.query.from === 'veterinarios'"
      class="mt-4 flex justify-center"
    >
      <button
        @click="mostrar = true"
        class="text-white bg-blue-600 rounded-full px-5 py-2 text-base md:text-lg font-bold shadow-md hover:bg-blue-700 hover:scale-105 transition transform duration-200"
      >
        + Vacuna
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const mostrar = ref(false)
</script>
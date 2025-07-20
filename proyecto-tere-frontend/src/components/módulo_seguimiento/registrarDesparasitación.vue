<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Desparasitación</h1>

    <form @submit.prevent="registrarDesparasitacion" class="space-y-4">
      <!-- DATOS OBLIGATORIOS -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Obligatorios</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna izquierda -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Fecha de la desparasitación</label>
            <input v-model="desparasitacion.fecha" type="date" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Tipo de desparasitación</label>
            <div class="flex gap-2">
                <!-- Contenedor relativo para el input con ícono -->
                <div class="relative w-full">
                  <input 
                    v-model="desparasitacion.tipo" 
                    type="text" 
                    required 
                    class="w-full border rounded p-2 pr-10" 
                    placeholder="Gastrointestinal, Externa, etc."
                  />
                  <font-awesome-icon 
                    :icon="['fas', 'magnifying-glass']" 
                    class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                  />
                </div>

                <!-- Botón de + Tipo -->
                <button 
                  type="button"
                  @click="abrirRegistroTipoDesparasitacion"
                  class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
                >
                  + Tipo 
                </button>
              </div>
          </div>

          <div>
            <label class="block font-medium">Nombre del desparasitante</label>
            <div class="flex gap-2">
              <input v-model="desparasitacion.nombreProducto" type="text" required class="w-full border rounded p-2" placeholder="Nombe comercial o genérico" />
              <!-- Botón de + Tipo -->
                <button 
                  type="button"
                  class="bg-red-500 text-white px-4 rounded font-bold hover:bg-red-700 transition-colors whitespace-nowrap"
                >
                  + Asociar Desparasitante 
                </button>
              </div>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Dosis aplicada</label>
            <input v-model="desparasitacion.dosis" type="text" required class="w-full border rounded p-2" placeholder="Ej: 1 comprimido, 0.5 ml, etc." />
          </div>

          <div>
            <label class="block font-medium">Frecuencia de renovación</label>
            <div class="flex">
              <input v-model="desparasitacion.frecuenciaValor" type="number" min="1" required class="w-1/2 border rounded-l p-2" placeholder="Cantidad" />
              <select v-model="desparasitacion.frecuenciaUnidad" required class="w-1/2 border rounded-r p-2">
                <option value="dias">Días</option>
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- DATOS OPCIONALES -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Opcionales</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
        <div>
          <label class="block font-medium">Peso de la mascota (kg)</label>
          <input v-model="desparasitacion.peso" type="number" step="0.1" min="0" class="w-full border rounded p-2" />
        </div>

        <div>
          <label class="block font-medium">Próxima fecha sugerida</label>
          <input v-model="desparasitacion.proximaFecha" type="date" class="w-full border rounded p-2" />
        </div>

        <div class="col-span-full">
          <div class="flex gap-2 items-center mb-1">
            <label class="block font-medium mb-1">Observaciones adicionales</label>
            <button 
                  type="button"
                  class="bg-green-500 text-white text-xl px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap"
                >
                  + Observación
            </button>
          </div>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors">Registrar Desparasitación</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const desparasitacion = reactive({
  fecha: '',
  tipo: '',
  nombreProducto: '',
  dosis: '',
  frecuenciaValor: '',
  frecuenciaUnidad: 'dias',
  peso: null,
  proximaFecha: '',
  observaciones: ''
})

const abrirRegistroTipoDesparasitacion = () => {
  router.push({
    path: '/registro/registroTipoDesparasitacion',
    query: {
      from: '/historialPreventivo/desparasitaciones/registro/desparasitacion'
    }
  });
};

const registrarDesparasitacion = () => {
  const payload = {
    ...desparasitacion,
    frecuenciaCompleta: `${desparasitacion.frecuenciaValor} ${desparasitacion.frecuenciaUnidad}`
  }
  
  // Aquí iría la lógica para enviar los datos al servidor
  console.log('Datos a enviar:', payload)
}
</script>
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Vacunación</h1>

    <form @submit.prevent="registrarVacunacion" class="space-y-4">
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
            <label class="block font-medium">Tipo de vacuna aplicada</label>
            <div class="flex gap-2">
                <!-- Contenedor relativo para el input con ícono -->
                <div class="relative w-full">
                  <input 
                    v-model="vacuna.tipo" 
                    type="text" 
                    required 
                    class="w-full border rounded p-2 pr-10" 
                    placeholder="Rabia, Moquillo, etc."
                  />
                  <font-awesome-icon 
                    :icon="['fas', 'magnifying-glass']" 
                    class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                  />
                </div>

                <!-- Botón de + Tipo -->
                <button 
                  type="button"
                  @click="abrirRegistroTipoVacuna"
                  class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
                >
                  + Tipo 
                </button>
              </div>
          </div>

          <div>
            <label class="block font-medium">Fecha de aplicación</label>
            <input v-model="vacuna.fechaAplicacion" type="date" required class="w-full border rounded p-2" />
          </div>

          <div class="flex gap-2 items-center mb-1">
            <label class="block font-medium mb-1">Centro Veterinario donde se realizo</label>
            <button 
                  type="button"
                  class="bg-green-500 text-white text-xl px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap"
                >
                  + Centro
            </button>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Número de dosis</label>
            <input v-model="vacuna.numeroDosis" type="text" required class="w-full border rounded p-2" placeholder="Ej: 1° dosis, Refuerzo anual, etc." />
          </div>

          <div>
            <label class="block font-medium">Lote / Serie del frasco</label>
            <input v-model="vacuna.lote" type="text" required class="w-full border rounded p-2" />
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
          <label class="block font-medium">Fecha próxima dosis (si aplica)</label>
          <input v-model="vacuna.proximaDosis" type="date" class="w-full border rounded p-2" />
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors">Registrar Vacunación</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const abrirRegistroTipoVacuna = () => {
  router.push({
    path: '/registro/registroTipoVacuna',
    query: {
      from: '/historialPreventivo/vacunas/registro/vacuna'
    }
  });
};

const vacuna = reactive({
  tipo: '',
  fechaAplicacion: '',
  numeroDosis: '',
  lote: '',
  proximaDosis: '',
  observaciones: ''
})

const registrarVacunacion = () => {
  // Aquí iría la lógica para enviar los datos al servidor
  console.log('Datos a enviar:', vacuna)
}
</script>
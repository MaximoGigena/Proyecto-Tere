<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Tipo de Alergia/Sensibilidad</h1>

    <form @submit.prevent="registrarAlergia" class="space-y-4">
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
            <label class="block font-medium">Nombre del tipo de alergia/sensibilidad</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="alergia.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Alergia a la penicilina, Sensibilidad alimentaria"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Descripción clínica breve</label>
            <textarea 
              v-model="alergia.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción de la alergia/sensibilidad"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Categoría</label>
            <select v-model="alergia.categoria" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="medicamento">Medicamento</option>
              <option value="alimento">Alimento</option>
              <option value="ambiental">Ambiental</option>
              <option value="contacto">Por contacto</option>
              <option value="otra">Otra</option>
            </select>
            <input 
              v-if="alergia.categoria === 'otra'"
              v-model="alergia.categoriaOtro"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique la categoría"
            />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Reacción común asociada</label>
            <input 
              v-model="alergia.reaccion" 
              type="text" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Ej: Diarrea, dermatitis, anafilaxia"
            />
          </div>

          <div>
            <label class="block font-medium">Nivel de riesgo típico</label>
            <select v-model="alergia.riesgo" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="leve">Leve</option>
              <option value="moderado">Moderado</option>
              <option value="grave">Grave</option>
              <option value="muy_grave">Muy grave</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Áreas afectadas</label>
            <div class="flex flex-wrap gap-2">
              <label v-for="area in areasAfectadas" :key="area.value" class="flex items-center space-x-2">
                <input type="checkbox" v-model="alergia.areas" :value="area.value" class="rounded">
                <span>{{ area.label }}</span>
              </label>
            </div>
            <input 
              v-model="alergia.otraArea"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Otra área afectada (especificar)"
            />
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
          <label class="block font-medium">Tratamiento recomendado estándar</label>
          <textarea 
            v-model="alergia.tratamiento" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Protocolo de tratamiento recomendado"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Recomendaciones clínicas</label>
          <textarea 
            v-model="alergia.recomendaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Consejos para manejo clínico"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Especie afectada</label>
          <select v-model="alergia.especie" class="w-full border rounded p-2">
            <option value="">Seleccione una opción</option>
            <option value="canino">Canino</option>
            <option value="felino">Felino</option>
            <option value="ave">Ave</option>
            <option value="roedor">Roedor</option>
            <option value="exotico">Exótico</option>
            <option value="todos">Todos</option>
          </select>
        </div>

        <div>
          <label class="block font-medium">Sustancia/factor desencadenante</label>
          <input 
            v-model="alergia.desencadenante" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Si se conoce"
          />
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Conducta recomendada ante exposición</label>
          <textarea 
            v-model="alergia.conducta" 
            rows="3" 
            maxlength="500" 
            class="w-full border rounded p-2 resize-none"
          ></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ alergia.conducta.length }}/500 caracteres</p>
        </div>

        <div class="col-span-full">
          <div class="gap-2 items-center mb-1">
            <label class="block font-medium mb-1">Observaciones adicionales</label>
            <textarea 
            v-model="alergia.conducta" 
            rows="3" 
            maxlength="500" 
            class="w-full border rounded p-2 resize-none"
          ></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ alergia.observaciones.length }}/500 caracteres</p>
          </div>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="button" @click="cancelar" class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors">Cancelar</button>
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors">+ Tipo</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const areasAfectadas = [
  { value: 'piel', label: 'Piel' },
  { value: 'ojos', label: 'Ojos' },
  { value: 'oidos', label: 'Oídos' },
  { value: 'respiratorio', label: 'Sistema respiratorio' },
  { value: 'digestivo', label: 'Sistema digestivo' },
  { value: 'neurologico', label: 'Sistema neurológico' },
  { value: 'otro', label: 'Otro' }
]

const alergia = reactive({
  nombre: '',
  descripcion: '',
  categoria: '',
  categoriaOtro: '',
  reaccion: '',
  riesgo: '',
  areas: [],
  otraArea: '',
  tratamiento: '',
  recomendaciones: '',
  especie: '',
  desencadenante: '',
  conducta: '',
  observaciones: ''
})

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.push('/tipos-alergia')
  }
}

const registrarAlergia = () => {
  const payload = {
    ...alergia,
    categoriaFinal: alergia.categoria === 'otra' ? alergia.categoriaOtro : alergia.categoria,
    areasAfectadas: [...alergia.areas, alergia.otraArea].filter(Boolean).join(', ')
  }
  
  console.log('Datos a enviar:', payload)
  // Aquí iría la lógica para enviar los datos al servidor
  alert('Tipo de alergia/sensibilidad registrado correctamente')
  router.push('/tipos-alergia')
}
</script>
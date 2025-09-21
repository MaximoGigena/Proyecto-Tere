<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Tipo de Vacuna</h1>

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
            <label class="block font-medium">Nombre de la vacuna</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="vacuna.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Rabia, Moquillo, Pentavalente"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Enfermedades que previene</label>
            <textarea 
              v-model="vacuna.enfermedades" 
              rows="2" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Liste las enfermedades que cubre esta vacuna"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Especie objetivo</label>
            <select v-model="vacuna.especie" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="canino">Canino</option>
              <option value="felino">Felino</option>
              <option value="ave">Ave</option>
              <option value="roedor">Roedor</option>
              <option value="exotico">Exótico</option>
              <option value="todos">Todos</option>
            </select>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Edad mínima de aplicación</label>
            <div class="flex">
              <input 
                v-model="vacuna.edadMinima" 
                type="number" 
                min="0" 
                step="0.5" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Edad" 
              />
              <select 
                v-model="vacuna.edadUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
              >
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
                <option value="años">Años</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Dosis recomendada</label>
            <div class="flex">
              <input 
                v-model="vacuna.dosis" 
                type="number" 
                min="0.1" 
                step="0.1" 
                required 
                class="w-3/4 border rounded-l p-2" 
                placeholder="Cantidad" 
              />
              <select 
                v-model="vacuna.dosisUnidad" 
                required 
                class="w-1/4 border rounded-r p-2"
              >
                <option value="ml">ml</option>
                <option value="dosis">dosis</option>
                <option value="gotas">gotas</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Vía de administración</label>
            <select v-model="vacuna.via" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="subcutanea">Subcutánea</option>
              <option value="intramuscular">Intramuscular</option>
              <option value="oral">Oral</option>
              <option value="nasal">Nasal</option>
            </select>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-4">
        <div>
          <label class="block font-medium">Frecuencia de aplicación</label>
          <select v-model="vacuna.frecuencia" required class="w-full border rounded p-2">
            <option value="">Seleccione una opción</option>
            <option value="unica">Única vez</option>
            <option value="anual">Anual</option>
            <option value="semestral">Cada 6 meses</option>
            <option value="personalizada">Personalizada</option>
          </select>
          <input 
            v-if="vacuna.frecuencia === 'personalizada'"
            v-model="vacuna.frecuenciaPersonalizada"
            type="text"
            class="w-full border rounded p-2 mt-2"
            placeholder="Especifique el esquema (ej: cada 3 meses)"
          />
        </div>

        <div>
          <label class="block font-medium">Obligatoriedad</label>
          <select v-model="vacuna.obligatoria" required class="w-full border rounded p-2">
            <option value="">Seleccione una opción</option>
            <option value="obligatoria">Obligatoria</option>
            <option value="opcional">Opcional</option>
            <option value="depende">Depende de la región</option>
          </select>
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
          <label class="block font-medium">Intervalo entre dosis (si aplica)</label>
          <input 
            v-model="vacuna.intervaloDosis" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Ej: 21 días, 1 mes, etc."
          />
        </div>

        <div>
          <label class="block font-medium">Fabricante o laboratorio</label>
          <input 
            v-model="vacuna.fabricante" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Nombre del laboratorio"
          />
        </div>

        <div>
          <label class="block font-medium">Riesgos conocidos</label>
          <textarea 
            v-model="vacuna.riesgos" 
            rows="2" 
            class="w-full border rounded p-2" 
            placeholder="Efectos adversos reportados"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Recomendaciones profesionales</label>
          <textarea 
            v-model="vacuna.recomendaciones" 
            rows="2" 
            class="w-full border rounded p-2" 
            placeholder="Consejos para su aplicación"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Lote / Serie del frasco</label>
          <input 
            v-model="vacuna.lote" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Número de lote"
          />
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

const vacuna = reactive({
  nombre: '',
  enfermedades: '',
  especie: '',
  edadMinima: '',
  edadUnidad: 'semanas',
  dosis: '',
  dosisUnidad: 'ml',
  via: '',
  frecuencia: '',
  frecuenciaPersonalizada: '',
  obligatoria: '',
  intervaloDosis: '',
  fabricante: '',
  riesgos: '',
  recomendaciones: '',
  lote: ''
})

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const registrarVacunacion = () => {
  const datosEnvio = {
    ...vacuna,
    edadCompleta: `${vacuna.edadMinima} ${vacuna.edadUnidad}`,
    dosisCompleta: `${vacuna.dosis} ${vacuna.dosisUnidad}`,
    frecuenciaFinal: vacuna.frecuencia === 'personalizada' ? vacuna.frecuenciaPersonalizada : vacuna.frecuencia
  }
  
  console.log('Datos a enviar:', datosEnvio)
  // Aquí iría la lógica para enviar los datos al servidor
  alert('Tipo de vacuna registrado correctamente')
  router.push('/tipos-vacuna')
}
</script>
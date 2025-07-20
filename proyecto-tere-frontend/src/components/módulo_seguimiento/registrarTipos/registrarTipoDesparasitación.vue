<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Tipo de Desparasitación</h1>

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
            <label class="block font-medium">Nombre del desparasitante</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="desparasitacion.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Nombre comercial o genérico"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Parásitos tratados</label>
            <div class="flex flex-wrap gap-2">
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="desparasitacion.parasitos" value="internos" class="rounded">
                <span>Internos</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="desparasitacion.parasitos" value="externos" class="rounded">
                <span>Externos</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="desparasitacion.parasitos" value="pulgas" class="rounded">
                <span>Pulgas</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="desparasitacion.parasitos" value="garrapatas" class="rounded">
                <span>Garrapatas</span>
              </label>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="desparasitacion.parasitos" value="otros" class="rounded">
                <span>Otros</span>
              </label>
            </div>
            <input 
              v-if="desparasitacion.parasitos.includes('otros')"
              v-model="desparasitacion.otrosParasitos"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique otros parásitos"
            >
          </div>

          <div>
            <label class="block font-medium">Vía de administración</label>
            <select v-model="desparasitacion.via" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="oral">Oral</option>
              <option value="topica">Tópica</option>
              <option value="inyectable">Inyectable</option>
              <option value="otra">Otra</option>
            </select>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Especies a las que aplica</label>
            <select v-model="desparasitacion.especies" multiple class="w-full border rounded p-2 h-[120px]">
              <option value="canino">Canino</option>
              <option value="felino">Felino</option>
              <option value="ave">Ave</option>
              <option value="roedor">Roedor</option>
              <option value="exotico">Exótico</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Edad mínima de aplicación</label>
            <div class="flex">
              <input 
                v-model="desparasitacion.edadMinima" 
                type="number" 
                min="0" 
                step="0.5" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Edad" 
              />
              <select 
                v-model="desparasitacion.edadUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
              >
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Frecuencia estándar</label>
            <div class="flex">
              <input 
                v-model="desparasitacion.frecuencia" 
                type="number" 
                min="1" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Cantidad" 
              />
              <select 
                v-model="desparasitacion.frecuenciaUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
              >
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
          <label class="block font-medium">Recomendaciones profesionales</label>
          <textarea 
            v-model="desparasitacion.recomendaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Consejos para su aplicación"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Riesgos conocidos</label>
          <textarea 
            v-model="desparasitacion.riesgos" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Efectos adversos reportados"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Dosis recomendada</label>
          <input 
            v-model="desparasitacion.dosis" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Ej: 1 comprimido, 0.5 ml, etc."
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

const desparasitacion = reactive({
  nombre: '',
  parasitos: [],
  otrosParasitos: '',
  via: '',
  especies: [],
  edadMinima: '',
  edadUnidad: 'semanas',
  frecuencia: '',
  frecuenciaUnidad: 'meses',
  recomendaciones: '',
  riesgos: '',
  dosis: ''
})

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.push('/tipos-desparasitacion')
  }
}

const registrarDesparasitacion = () => {
  const payload = {
    ...desparasitacion,
    edadCompleta: `${desparasitacion.edadMinima} ${desparasitacion.edadUnidad}`,
    frecuenciaCompleta: `${desparasitacion.frecuencia} ${desparasitacion.frecuenciaUnidad}`,
    parasitosLista: desparasitacion.parasitos.includes('otros') 
      ? [...desparasitacion.parasitos.filter(p => p !== 'otros'), desparasitacion.otrosParasitos].join(', ')
      : desparasitacion.parasitos.join(', ')
  }
  
  console.log('Datos a enviar:', payload)
  // Aquí iría la lógica para enviar los datos al servidor
  alert('Tipo de desparasitación registrado correctamente')
  router.push('/tipos-desparasitacion')
}
</script>
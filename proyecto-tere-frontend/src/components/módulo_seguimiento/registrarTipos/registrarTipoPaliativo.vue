<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Procedimiento Paliativo</h1>

    <form @submit.prevent="registrarProcedimiento" class="space-y-4">
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
            <label class="block font-medium">Nombre del procedimiento paliativo</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="procedimiento.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Control del dolor crónico"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Descripción general</label>
            <textarea 
              v-model="procedimiento.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción detallada del procedimiento"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Especie objetivo</label>
            <select v-model="procedimiento.especie" required class="w-full border rounded p-2">
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
            <label class="block font-medium">Objetivo terapéutico principal</label>
            <select v-model="procedimiento.objetivo" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="alivio_dolor">Alivio del dolor</option>
              <option value="mejora_movilidad">Mejora de movilidad</option>
              <option value="soporte_respiratorio">Soporte respiratorio</option>
              <option value="soporte_nutricional">Soporte nutricional</option>
              <option value="acompañamiento">Acompañamiento final</option>
              <option value="otro">Otro</option>
            </select>
            <input 
              v-if="procedimiento.objetivo === 'otro'"
              v-model="procedimiento.objetivoOtro"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique el objetivo"
            />
          </div>

          <div>
            <label class="block font-medium">Frecuencia o duración sugerida</label>
            <div class="flex">
              <input v-model="procedimiento.frecuenciaValor" type="number" min="1" required class="w-1/2 border rounded-l p-2" placeholder="Cantidad" />
              <select v-model="procedimiento.frecuenciaUnidad" required class="w-1/2 border rounded-r p-2">
                <option value="horas">Horas</option>
                <option value="dias">Días</option>
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
                <option value="sesiones">Sesiones</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Indicaciones clínicas comunes</label>
            <textarea 
              v-model="procedimiento.indicaciones" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Condiciones o síntomas que indican este procedimiento"
            ></textarea>
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
          <label class="block font-medium">Contraindicaciones</label>
          <textarea 
            v-model="procedimiento.contraindicaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Situaciones donde no aplicar este procedimiento"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Riesgos o efectos secundarios</label>
          <textarea 
            v-model="procedimiento.riesgos" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Posibles efectos adversos"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Recursos necesarios</label>
          <div class="flex gap-2 items-center mb-1">
            <input 
              v-model="recursoTemporal" 
              type="text" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Medicamento X, Equipo de oxígeno, etc."
              @keyup.enter="agregarRecurso"
            />
            <button 
              type="button"
              class="bg-green-500 text-white px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap"
              @click="agregarRecurso"
            >
              + Agregar
            </button>
          </div>
          <div class="flex flex-wrap gap-2 mt-2">
            <div 
              v-for="(recurso, index) in procedimiento.recursos" 
              :key="index"
              class="bg-gray-100 px-3 py-1 rounded-full flex items-center gap-2"
            >
              {{ recurso }}
              <button 
                type="button"
                @click="eliminarRecurso(index)"
                class="text-red-500 hover:text-red-700"
              >
                <font-awesome-icon :icon="['fas', 'times']" />
              </button>
            </div>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Recomendaciones clínicas</label>
          <textarea 
            v-model="procedimiento.recomendaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Recomendaciones para el equipo médico"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Observaciones adicionales</label>
          <textarea 
            v-model="procedimiento.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante"
          ></textarea>
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

const procedimiento = reactive({
  nombre: '',
  descripcion: '',
  especie: '',
  objetivo: '',
  objetivoOtro: '',
  indicaciones: '',
  frecuenciaValor: '',
  frecuenciaUnidad: 'dias',
  contraindicaciones: '',
  riesgos: '',
  recursos: [],
  recomendaciones: '',
  observaciones: ''
})

const recursoTemporal = ref('')
const archivos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null
})))

const inputsArchivo = ref([])

const agregarRecurso = () => {
  if (recursoTemporal.value.trim() !== '') {
    procedimiento.recursos.push(recursoTemporal.value.trim())
    recursoTemporal.value = ''
  }
}

const eliminarRecurso = (index) => {
  procedimiento.recursos.splice(index, 1)
}

const esImagen = (archivo) => {
  if (!archivo) return false
  return archivo.type.startsWith('image/')
}

const handleArchivo = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    archivos.value[index].archivo = file
    archivos.value[index].preview = esImagen(file) ? URL.createObjectURL(file) : null
  }
}

const activarInput = (index) => {
  inputsArchivo.value[index]?.click()
}

const quitarArchivo = (index) => {
  archivos.value[index].archivo = null
  archivos.value[index].preview = null
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const registrarProcedimiento = () => {
  const formData = new FormData()
  
  // Preparar datos para enviar
  const datosEnvio = {
    ...procedimiento,
    frecuencia: `${procedimiento.frecuenciaValor} ${procedimiento.frecuenciaUnidad}`,
    objetivoTerapeutico: procedimiento.objetivo === 'otro' ? procedimiento.objetivoOtro : procedimiento.objetivo,
    recursos: procedimiento.recursos.join('; ')
  }

  for (const campo in datosEnvio) {
    if (datosEnvio[campo] !== null && datosEnvio[campo] !== '') {
      formData.append(campo, datosEnvio[campo])
    }
  }

  archivos.value.forEach((archivo, i) => {
    if (archivo.archivo) {
      formData.append(`archivo${i + 1}`, archivo.archivo)
    }
  })
  
  console.log('Datos a enviar:', formData)
  // Aquí iría la lógica para enviar los datos al servidor
  alert('Procedimiento registrado correctamente')
  router.push('/cuidados-paliativos')
}
</script>
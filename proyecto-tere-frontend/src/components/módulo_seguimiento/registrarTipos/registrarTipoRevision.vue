<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Tipo de Revisión Médica</h1>

    <form @submit.prevent="registrarRevision" class="space-y-4">
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
            <label class="block font-medium">Nombre del tipo de revisión</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="revision.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Chequeo general, Control postquirúrgico"
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
              v-model="revision.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción del propósito y alcance de la revisión"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Frecuencia recomendada</label>
            <select v-model="revision.frecuencia" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="anual">Anual</option>
              <option value="semestral">Semestral</option>
              <option value="trimestral">Trimestral</option>
              <option value="mensual">Mensual</option>
              <option value="post_procedimiento">Post-procedimiento</option>
              <option value="personalizada">Personalizada</option>
            </select>
            <input 
              v-if="revision.frecuencia === 'personalizada'"
              v-model="revision.frecuenciaPersonalizada"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique la frecuencia"
            />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Áreas para revisar</label>
            <div class="flex flex-wrap gap-2">
              <label v-for="area in areasRevisar" :key="area.value" class="flex items-center space-x-2">
                <input type="checkbox" v-model="revision.areas" :value="area.value" class="rounded">
                <span>{{ area.label }}</span>
              </label>
            </div>
            <input 
              v-model="revision.otraArea"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Otra área a revisar (especificar)"
            />
          </div>

          <div>
            <label class="block font-medium">Indicadores clave esperables</label>
            <textarea 
              v-model="revision.indicadores" 
              rows="3" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Peso, temperatura, pulso, etc."
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
          <label class="block font-medium">Especie objetivo</label>
          <select v-model="revision.especie" class="w-full border rounded p-2">
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
          <label class="block font-medium">Edad sugerida para la revisión</label>
          <div class="flex">
            <input 
              v-model="revision.edadSugerida" 
              type="number" 
              min="0" 
              step="0.5" 
              class="w-1/2 border rounded-l p-2" 
              placeholder="Edad" 
            />
            <select 
              v-model="revision.edadUnidad" 
              class="w-1/2 border rounded-r p-2"
            >
              <option value="semanas">Semanas</option>
              <option value="meses">Meses</option>
              <option value="años">Años</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-medium">Recomendaciones profesionales</label>
          <textarea 
            v-model="revision.recomendaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Consejos para realizar esta revisión"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Riesgos clínicos asociados</label>
          <textarea 
            v-model="revision.riesgos" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Posibles complicaciones o riesgos"
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

const areasRevisar = [
  { value: 'piel', label: 'Piel' },
  { value: 'ojos', label: 'Ojos' },
  { value: 'oidos', label: 'Oídos' },
  { value: 'boca', label: 'Boca' },
  { value: 'corazon', label: 'Corazón' },
  { value: 'pulmones', label: 'Pulmones' },
  { value: 'abdomen', label: 'Abdomen' },
  { value: 'articulaciones', label: 'Articulaciones' },
  { value: 'comportamiento', label: 'Comportamiento' },
  { value: 'nutricion', label: 'Nutrición' }
]

const revision = reactive({
  nombre: '',
  descripcion: '',
  frecuencia: '',
  frecuenciaPersonalizada: '',
  areas: [],
  otraArea: '',
  indicadores: '',
  especie: '',
  edadSugerida: '',
  edadUnidad: 'meses',
  recomendaciones: '',
  riesgos: ''
})

const archivos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null
})))

const inputsArchivo = ref([])

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

const registrarRevision = () => {
  const formData = new FormData()
  
  // Preparar datos para enviar
  const datosEnvio = {
    ...revision,
    frecuenciaFinal: revision.frecuencia === 'personalizada' ? revision.frecuenciaPersonalizada : revision.frecuencia,
    areasRevisar: [...revision.areas, revision.otraArea].filter(Boolean).join(', '),
    edadCompleta: revision.edadSugerida ? `${revision.edadSugerida} ${revision.edadUnidad}` : null
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
  alert('Tipo de revisión registrado correctamente')
  router.push('/tipos-revision')
}
</script>
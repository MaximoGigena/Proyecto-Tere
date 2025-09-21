<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Tipo de Terapia</h1>

    <form @submit.prevent="registrarTerapia" class="space-y-4">
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
            <label class="block font-medium">Nombre del tipo de terapia</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="terapia.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Fisioterapia postquirúrgica, Quimioterapia"
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
              v-model="terapia.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción detallada de la terapia"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Especie objetivo</label>
            <select v-model="terapia.especie" required class="w-full border rounded p-2">
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
            <label class="block font-medium">Duración típica del tratamiento</label>
            <div class="flex">
              <input 
                v-model="terapia.duracionValor" 
                type="number" 
                min="1" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Cantidad" 
              />
              <select 
                v-model="terapia.duracionUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
              >
                <option value="sesiones">Sesiones</option>
                <option value="dias">Días</option>
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Frecuencia sugerida</label>
            <select v-model="terapia.frecuencia" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="diaria">Diaria</option>
              <option value="semanal">Semanal</option>
              <option value="quincenal">Quincenal</option>
              <option value="mensual">Mensual</option>
              <option value="personalizada">Personalizada</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Requisitos o condiciones previas</label>
            <textarea 
              v-model="terapia.requisitos" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Condiciones que debe cumplir el paciente para esta terapia"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Indicaciones clínicas comunes</label>
            <textarea 
              v-model="terapia.indicaciones" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Casos o condiciones donde se recomienda esta terapia"
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
            v-model="terapia.contraindicaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Situaciones donde no aplicar esta terapia"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Riesgos o efectos secundarios</label>
          <textarea 
            v-model="terapia.riesgos" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Posibles efectos adversos conocidos"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Recomendaciones clínicas</label>
          <textarea 
            v-model="terapia.recomendaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Consejos para la aplicación de esta terapia"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Observaciones adicionales</label>
          <textarea 
            v-model="terapia.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante sobre esta terapia"
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

const terapia = reactive({
  nombre: '',
  descripcion: '',
  especie: '',
  duracionValor: '',
  duracionUnidad: 'sesiones',
  frecuencia: '',
  requisitos: '',
  indicaciones: '',
  contraindicaciones: '',
  riesgos: '',
  recomendaciones: '',
  observaciones: ''
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

const registrarTerapia = () => {
  const formData = new FormData()
  
  // Preparar datos para enviar
  const datosEnvio = {
    ...terapia,
    duracion: `${terapia.duracionValor} ${terapia.duracionUnidad}`
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
  alert('Tipo de terapia registrado correctamente')
  router.push('/tipos-terapia')
}
</script>
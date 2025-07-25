<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Cuidados Paliativos</h1>

    <form @submit.prevent="registrarCuidados" class="space-y-4">
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
            <label class="block font-medium">Tipo de procedimiento paliativo</label>
            <div class="flex gap-2">
              <!-- Contenedor relativo para el input con ícono -->
              <div class="relative w-full">
                <input 
                  v-model="cuidados.tipo" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Control del dolor cronico, Soporte Respiratorio, etc."
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>

              <!-- Botón de + Tipo -->
              <button 
                type="button"
                @click="abrirRegistroTipoPaliativo"
                class="bg-blue-500 text-white px-4 rounded font-bold hover:bg-blue-700 transition-colors whitespace-nowrap"
              >
                + Tipo 
              </button>
            </div>
          </div>

          <div>
            <label class="block font-medium">Fecha de aplicación/inicio</label>
            <input v-model="cuidados.fechaInicio" type="datetime-local" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Motivo (diagnóstico base)</label>
            <div class="flex gap-2">
              <input v-model="cuidados.diagnostico" type="text" required class="w-full border rounded p-2" placeholder="Motivo que justificó la cirugía" />
              <!-- Botón de + Tipo -->
                <button 
                  type="button"
                  class="bg-orange-500 text-white px-4 rounded font-bold hover:bg-orange-700 transition-colors whitespace-nowrap"
                >
                  + Asociar Diagnostico 
                </button>
              </div>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Resultado/efecto observado</label>
            <select v-model="cuidados.resultado" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="mejoria">Mejoría evidente</option>
              <option value="alivio">Alivio parcial</option>
              <option value="estabilizacion">Estabilización</option>
              <option value="sin_cambio">Sin cambios</option>
              <option value="empeoramiento">Empeoramiento</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Estado de la mascota</label>
            <select v-model="cuidados.estado" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="estable">Estable</option>
              <option value="dolor_controlado">Con dolor controlado</option>
              <option value="dolor_parcial">Con dolor parcialmente controlado</option>
              <option value="deterioro">En deterioro</option>
              <option value="critico">Crítico</option>
            </select>
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
          <label class="block font-medium">Frecuencia de seguimiento</label>
          <div class="flex">
            <input v-model="cuidados.frecuenciaValor" type="number" min="1" class="w-1/2 border rounded-l p-2" placeholder="Cantidad" />
            <select v-model="cuidados.frecuenciaUnidad" class="w-1/2 border rounded-r p-2">
              <option value="horas">Horas</option>
              <option value="dias">Días</option>
              <option value="semanas">Semanas</option>
              <option value="meses">Meses</option>
            </select>
          </div>
        </div>

        <div class="col-span-full">
          <div class="flex gap-2 items-center mb-1">
            <label class="block font-medium mb-1">Observaciones clínicas</label>
            <button 
                  type="button"
                  class="bg-green-500 text-white text-xl px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap"
                >
                  + Observación
            </button>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Medicación complementaria</label>
          <textarea v-model="cuidados.medicacion" rows="3" maxlength="500" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ cuidados.medicacion.length }}/500 caracteres</p>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Recomendaciones para el tutor</label>
          <textarea v-model="cuidados.recomendaciones" rows="3" maxlength="500" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ cuidados.recomendaciones.length }}/500 caracteres</p>
        </div>

        <!-- Archivos adjuntos -->
       <div class="col-span-full">
            <label class="block font-medium mb-2">Archivos adjuntos</label>
            <div class="flex flex-wrap gap-x-2 gap-y-2">
              <div
                v-for="(archivo, index) in archivos"
                :key="index"
                class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-20 w-20"
                @click="!archivo.preview && activarInput(index)"
              >
                <!-- Botón eliminar -->
                <button
                  type="button"
                  @click.stop="quitarArchivo(index)"
                  v-if="archivo.preview"
                  class="absolute top-0.5 right-0.5 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700"
                >
                  <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-lg" />
                </button>

                <!-- Input oculto -->
                <input
                  :ref="el => inputsArchivo[index] = el"
                  type="file"
                  @change="handleArchivo($event, index)"
                  class="hidden"
                />

                <!-- Vista previa -->
                <div v-if="archivo.preview" class="h-full flex flex-col">
                  <img
                    v-if="esImagen(archivo.archivo)"
                    :src="archivo.preview"
                    alt="Preview"
                    class="w-full h-full object-cover rounded-md mx-auto flex-grow"
                  />
                  <div v-else class="h-full flex items-center justify-center p-1">
                    <font-awesome-icon :icon="['fas', 'file']" class="text-3xl text-gray-500" />
                  </div>
                  <div class="text-[10px] truncate px-1">{{ archivo.archivo.name }}</div>
                </div>

                <!-- Indicador visual si no hay archivo -->
                <div v-else class="text-green-400 flex flex-col justify-center items-center h-full">
                  <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-2xl mb-0.5" />
                  <div class="text-[10px] text-gray-400">Agregar</div>
                </div>
              </div>
            </div>
            <p class="text-xs text-gray-500 mt-1">Puede adjuntar recetas, imágenes del medicamento, informes, etc.</p>
          </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors">Registrar Cuidados</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const abrirRegistroTipoPaliativo = () => {
  router.push({
    path: '/registro/registroTipoPaliativo',
    query: {
      from: '/historialClinico/paliativos/registro/paliativo'
    }
  });
};

const router = useRouter()
const route = useRoute()

const cuidados = reactive({
  tipo: '',
  tipoOtro: '',
  fechaInicio: '',
  diagnostico: '',
  resultado: '',
  estado: '',
  frecuenciaValor: '',
  frecuenciaUnidad: 'dias',
  observaciones: '',
  medicacion: '',
  recomendaciones: ''
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

const registrarCuidados = () => {
  const formData = new FormData()
  
  // Preparar datos para enviar
  const datosEnvio = {
    ...cuidados,
    frecuencia: cuidados.frecuenciaValor ? `${cuidados.frecuenciaValor} ${cuidados.frecuenciaUnidad}` : null,
    tipoProcedimiento: cuidados.tipo === 'otro' ? cuidados.tipoOtro : cuidados.tipo
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
}
</script>
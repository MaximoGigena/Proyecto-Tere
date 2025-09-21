<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Tipo de Fármaco</h1>

    <form @submit.prevent="registrarFarmaco" class="space-y-4">
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
            <label class="block font-medium">Nombre comercial</label>
            <input 
              v-model="farmaco.nombreComercial" 
              type="text" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Ej: Rimadyl, Baytril, etc."
            />
          </div>

          <div>
            <label class="block font-medium">Nombre genérico</label>
            <input 
              v-model="farmaco.nombreGenerico" 
              type="text" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Ej: Carprofeno, Enrofloxacina"
            />
          </div>

          <div>
            <label class="block font-medium">Composición/principio activo</label>
            <textarea 
              v-model="farmaco.composicion" 
              rows="2" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Principio activo y concentración"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Categoría terapéutica</label>
            <select v-model="farmaco.categoria" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="analgesico">Analgésico</option>
              <option value="antibiotico">Antibiótico</option>
              <option value="antiparasitario">Antiparasitario</option>
              <option value="antiinflamatorio">Antiinflamatorio</option>
              <option value="antifungico">Antifúngico</option>
              <option value="antiviral">Antiviral</option>
              <option value="anestesico">Anestésico</option>
              <option value="otro">Otro</option>
            </select>
            <input 
              v-if="farmaco.categoria === 'otro'"
              v-model="farmaco.categoriaOtro"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique la categoría"
            />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Especie objetivo</label>
            <select v-model="farmaco.especie" class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="canino">Canino</option>
              <option value="felino">Felino</option>
              <option value="ave">Ave</option>
              <option value="roedor">Roedor</option>
              <option value="exotico">Exótico</option>
              <option value="todos">Todos</option>
              <option value="ninguna">No aplica</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Dosis terapéutica recomendada</label>
            <div class="flex">
              <input 
                v-model="farmaco.dosis" 
                type="number" 
                min="0.1" 
                step="0.1" 
                required 
                class="w-3/4 border rounded-l p-2" 
                placeholder="Cantidad" 
              />
              <select 
                v-model="farmaco.unidad" 
                required 
                class="w-1/4 border rounded-r p-2"
              >
                <option value="mg">mg</option>
                <option value="ml">ml</option>
                <option value="UI">UI</option>
                <option value="mcg">mcg</option>
                <option value="gotas">gotas</option>
              </select>
            </div>
            <div class="flex mt-2">
              <select 
                v-model="farmaco.frecuenciaUnidad" 
                required 
                class="w-1/2 border rounded-l p-2"
              >
                <option value="kg">por kg</option>
                <option value="dosis">por dosis</option>
              </select>
              <input 
                v-model="farmaco.frecuencia" 
                type="text" 
                required 
                class="w-1/2 border rounded-r p-2" 
                placeholder="Ej: cada 8h, 1 vez al día"
              />
            </div>
          </div>

          <div>
            <label class="block font-medium">Vía de administración</label>
            <select v-model="farmaco.via" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="oral">Oral</option>
              <option value="subcutanea">Subcutánea</option>
              <option value="intramuscular">Intramuscular</option>
              <option value="intravenosa">Intravenosa</option>
              <option value="topica">Tópica</option>
              <option value="oftalmica">Oftálmica</option>
              <option value="otica">Ótica</option>
              <option value="otra">Otra</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Indicaciones clínicas comunes</label>
            <textarea 
              v-model="farmaco.indicaciones" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Condiciones o enfermedades donde se indica este fármaco"
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
            v-model="farmaco.contraindicaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Situaciones donde no debe usarse este fármaco"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Interacciones medicamentosas</label>
          <textarea 
            v-model="farmaco.interacciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Fármacos que no deben combinarse con este"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Reacciones adversas frecuentes</label>
          <textarea 
            v-model="farmaco.reacciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Efectos secundarios comunes"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Fabricante/Laboratorio</label>
          <input 
            v-model="farmaco.fabricante" 
            type="text" 
            class="w-full border rounded p-2" 
            placeholder="Nombre del laboratorio"
          />
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Recomendaciones clínicas</label>
          <textarea 
            v-model="farmaco.recomendaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Consejos para su administración y manejo"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Observaciones adicionales</label>
          <textarea 
            v-model="farmaco.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante sobre este fármaco"
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

const farmaco = reactive({
  nombreComercial: '',
  nombreGenerico: '',
  composicion: '',
  categoria: '',
  categoriaOtro: '',
  especie: '',
  dosis: '',
  unidad: 'mg',
  frecuenciaUnidad: 'kg',
  frecuencia: '',
  via: '',
  indicaciones: '',
  contraindicaciones: '',
  interacciones: '',
  reacciones: '',
  fabricante: '',
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

const registrarFarmaco = () => {
  const formData = new FormData()
  
  // Preparar datos para enviar
  const datosEnvio = {
    ...farmaco,
    dosisCompleta: `${farmaco.dosis} ${farmaco.unidad} ${farmaco.frecuenciaUnidad}`,
    categoriaFinal: farmaco.categoria === 'otro' ? farmaco.categoriaOtro : farmaco.categoria
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
  alert('Tipo de fármaco registrado correctamente')
  router.push('/tipos-farmaco')
}
</script>
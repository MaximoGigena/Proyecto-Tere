<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Tipo de Cirugía</h1>

    <form @submit.prevent="registrarCirugia" class="space-y-4">
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
            <label class="block font-medium">Nombre del tipo de cirugía</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="cirugia.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Esterilización, Limpieza dental, Extirpación de tumor"
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
              v-model="cirugia.descripcion" 
              rows="4" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción detallada del procedimiento quirúrgico"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Especie objetivo</label>
            <select v-model="cirugia.especie" required class="w-full border rounded p-2">
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
            <label class="block font-medium">Frecuencia esperada</label>
            <select v-model="cirugia.frecuencia" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="unica">Única vez</option>
              <option value="potencial_repetible">Potencialmente repetible</option>
              <option value="multiple">Múltiples veces</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Duración estimada</label>
            <div class="flex">
              <input 
                v-model="cirugia.duracion" 
                type="number" 
                min="1" 
                required 
                class="w-1/2 border rounded-l p-2" 
                placeholder="Tiempo" 
              />
              <select 
                v-model="cirugia.duracionUnidad" 
                required 
                class="w-1/2 border rounded-r p-2"
              >
                <option value="minutos">Minutos</option>
                <option value="horas">Horas</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Riesgos comunes asociados</label>
            <textarea 
              v-model="cirugia.riesgos" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Complicaciones potenciales de este procedimiento"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Recomendaciones preoperatorias</label>
            <textarea 
              v-model="cirugia.recomendacionesPre" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Preparación necesaria antes de la cirugía"
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
          <label class="block font-medium">Recomendaciones postoperatorias</label>
          <textarea 
            v-model="cirugia.recomendacionesPost" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Cuidados después de la cirugía"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Requerimientos de anestesia</label>
          <textarea 
            v-model="cirugia.anestesia" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Tipo de anestesia recomendada"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Equipamiento quirúrgico necesario</label>
          <div class="flex gap-4">
            <textarea 
              v-model="equipamientoTemporal" 
              rows="2" 
              class="w-64 border rounded p-2 resize-none" 
              placeholder="Liste el equipamiento necesario"
            ></textarea>
            <button 
              type="button"
              @click="agregarEquipamiento"
              class="bg-green-500 text-white px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap"
            >
              + Agregar
            </button>
          </div>
          <div class="flex flex-wrap gap-2 mt-2">
            <div 
              v-for="(item, index) in cirugia.equipamiento" 
              :key="index"
              class="bg-gray-100 px-3 py-1 rounded-full flex items-center gap-2"
            >
              {{ item }}
              <button 
                type="button"
                @click="eliminarEquipamiento(index)"
                class="text-red-500 hover:text-red-700"
              >
                <font-awesome-icon :icon="['fas', 'times']" />
              </button>
            </div>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Observaciones adicionales</label>
          <textarea 
            v-model="cirugia.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante sobre este procedimiento"
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

const cirugia = reactive({
  nombre: '',
  descripcion: '',
  especie: '',
  frecuencia: '',
  duracion: '',
  duracionUnidad: 'minutos',
  riesgos: '',
  recomendacionesPre: '',
  recomendacionesPost: '',
  anestesia: '',
  equipamiento: [],
  observaciones: ''
})

const equipamientoTemporal = ref('')
const archivos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null
})))

const inputsArchivo = ref([])

const agregarEquipamiento = () => {
  if (equipamientoTemporal.value.trim() !== '') {
    cirugia.equipamiento.push(equipamientoTemporal.value.trim())
    equipamientoTemporal.value = ''
  }
}

const eliminarEquipamiento = (index) => {
  cirugia.equipamiento.splice(index, 1)
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
    router.push('/tipos-cirugia')
  }
}

const registrarCirugia = () => {
  const formData = new FormData()
  
  // Preparar datos para enviar
  const datosEnvio = {
    ...cirugia,
    duracionCompleta: `${cirugia.duracion} ${cirugia.duracionUnidad}`,
    equipamientoLista: cirugia.equipamiento.join('; ')
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
  alert('Tipo de cirugía registrado correctamente')
  router.push('/tipos-cirugia')
}
</script>
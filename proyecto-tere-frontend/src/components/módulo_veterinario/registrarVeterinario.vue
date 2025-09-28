<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Veterinario</h1>

    <form @submit.prevent="registrarVeterinario" class="space-y-4">
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
            <label class="block font-medium">Nombre completo</label>
            <input v-model="veterinario.nombre" type="text" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Correo electrónico profesional</label>
            <input v-model="veterinario.email" type="email" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Matrícula Profesional</label>
            <input v-model="veterinario.matricula" type="text" required class="w-full border rounded p-2" />
          </div>

          <div>
            <label class="block font-medium">Especialidad</label>
            <input v-model="veterinario.especialidad" type="text" class="w-full border rounded p-2" placeholder="Ej: Clínica general, cirugía, etc." />
          </div>
        </div>

        <!-- Columna derecha - Fotos -->
        <div>
          <label class="block font-medium mb-2">Foto del veterinario</label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div
              v-for="(foto, index) in fotos"
              :key="index"
              class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-full aspect-square"
              @click="!foto.preview && activarInput(index)"
            >
              <button type="button" @click.stop="quitarFoto(index)" v-if="foto.preview" class="absolute top-1 right-1 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700 mt-35 -mr-2">
                <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-3xl" />
              </button>

              <input :ref="el => inputsFoto[index] = el" type="file" accept="image/*" @change="handleFoto($event, index)" class="hidden" />

              <div v-if="foto.preview" class="h-full flex flex-col">
                <img :src="foto.preview" alt="Preview" class="w-full h-full object-cover rounded-md border-gray-300 mx-auto flex-grow" />
              </div>

              <div v-else class="text-green-400 mt-14">
                <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-4xl mb-2" />
                <div class="text-gray-400">Agregar foto</div>
              </div>
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
          <label class="block font-medium">Años de experiencia</label>
          <input v-model="veterinario.experiencia" type="number" min="0" class="w-full border rounded p-2" />
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Biografía o descripción profesional</label>
          <textarea v-model="veterinario.descripcion" rows="4" maxlength="500" class="w-full border rounded p-2 resize-none"></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">{{ veterinario.descripcion.length }}/500 caracteres</p>
        </div>
      </div>

      <!-- CONTACTO -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos de Contacto</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
        <div>
          <label class="block font-medium">Teléfono de contacto</label>
          <input v-model="veterinario.telefono" type="tel" class="w-full border rounded p-2" />
        </div>

        <div>
          <label class="block font-medium">Email de contacto</label>
          <input v-model="veterinario.emailContacto" type="text" class="w-full border rounded p-2" />
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button 
          type="submit" 
          :disabled="loading"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
        >
          <span v-if="loading">Procesando...</span>
          <span v-else>Solicitar Cuenta</span>
        </button>
      </div>

      <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ errorMessage }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const loading = ref(false)
const errorMessage = ref('')

const veterinario = reactive({
  nombre: '',
  email: '',
  matricula: '',
  especialidad: '',
  experiencia: '',
  descripcion: '',
  telefono: '',
  emailContacto: '',
})

const fotos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null
})))

const inputsFoto = ref([])

const handleFoto = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    fotos.value[index].archivo = file
    fotos.value[index].preview = URL.createObjectURL(file)
  }
}

const activarInput = (index) => {
  inputsFoto.value[index]?.click()
}

const quitarFoto = (index) => {
  fotos.value[index].archivo = null
  fotos.value[index].preview = null
}

const registrarVeterinario = async () => {
  loading.value = true
  errorMessage.value = ''
  
  try {
    const formData = new FormData()
    
    // Agregar datos del veterinario
    formData.append('nombre', veterinario.nombre)
    formData.append('email', veterinario.email)
    formData.append('matricula', veterinario.matricula)
    formData.append('especialidad', veterinario.especialidad)
    formData.append('experiencia', veterinario.experiencia)
    formData.append('descripcion', veterinario.descripcion)
    formData.append('telefono', veterinario.telefono)
    formData.append('emailContacto', veterinario.emailContacto)
    
    // Agregar fotos
    fotos.value.forEach((foto, i) => {
      if (foto.archivo) {
        formData.append(`foto${i}`, foto.archivo)
      }
    })
    
    // Enviar datos al backend
    const response = await fetch('http://localhost:8000/api/registrar-veterinario', {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'application/json'
      }
    })

    const data = await response.json()
    
    if (!response.ok) {
      throw new Error(data.message || 'Error al enviar solicitud')
    }
    
    // Éxito - redirigir a pantalla de espera
    router.push('/veterinario-pendiente')
    
  } catch (error) {
    console.error('Error:', error)
    errorMessage.value = error.message || 'Ocurrió un error inesperado'
  } finally {
    loading.value = false
  }
}
</script>

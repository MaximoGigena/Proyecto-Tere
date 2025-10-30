<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE"
        class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar Centro Veterinario' : 'Registrar Centro Veterinario' }}</h1>

    <form @submit.prevent="esEdicion ? actualizarCentro() : registrarCentro()" class="space-y-4">
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
            <label class="block font-medium">Nombre del centro</label>
            <input v-model="centro.nombre" type="text" required class="w-full border rounded p-2" />
          </div>
          <div>
            <label class="block font-medium">CUIT / CUIL / DNI</label>
            <input v-model="centro.identificacion" type="text" required class="w-full border rounded p-2" />
          </div>
          <div>
            <label class="block font-medium">Dirección completa</label>
            <input v-model="centro.direccion" type="text" required placeholder="Calle, número, localidad, provincia, país"
              class="w-full border rounded p-2" />
          </div>
          <div>
            <label class="block font-medium">Teléfono de contacto</label>
            <input v-model="centro.telefono" type="tel" required class="w-full border rounded p-2" />
          </div>
          <div>
            <label class="block font-medium">Correo electrónico</label>
            <input v-model="centro.email" type="email" required class="w-full border rounded p-2" />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Especialidades médicas ofrecidas</label>
            <textarea v-model="centro.especialidades" rows="4" required
              placeholder="Ej: Clínica general, cirugía, diagnóstico por imagen, etc."
              class="w-full border rounded p-2 resize-none"></textarea>
          </div>

          <div>
            <label class="block font-medium mb-2">Imagen o logotipo del centro</label>
            <div class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-40 flex items-center justify-center"
              @click="activarInput">
              <input ref="inputLogo" type="file" accept="image/*" @change="handleLogo" class="hidden" />
              <div v-if="centro.logoPreview" class="h-full w-full flex">
                <img :src="centro.logoPreview" alt="Logo preview"
                  class="w-full h-full object-contain rounded-md border-gray-300 mx-auto" />
              </div>
              <div v-else class="text-green-400">
                <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-4xl mb-2" />
                <div class="text-gray-400">Agregar logo</div>
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
          <label class="block font-medium">Matrícula del establecimiento</label>
          <input v-model="centro.matricula" type="text" class="w-full border rounded p-2" />
        </div>
        <div>
          <label class="block font-medium">Horarios de atención</label>
          <input v-model="centro.horarios" type="text" placeholder="Ej: Lunes a viernes 9-18hs"
            class="w-full border rounded p-2" />
        </div>
        <div class="col-span-full">
          <label class="block font-medium">Página web y/o redes sociales</label>
          <input v-model="centro.web" type="text" placeholder="Ej: www.centrovet.com / @centrovet"
            class="w-full border rounded p-2" />
        </div>
      </div>

      <!-- BOTONES -->
      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="submit" :disabled="loading"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300">
          <span v-if="loading">Procesando...</span>
          <span v-else>{{ esEdicion ? 'Actualizar Centro' : 'Solicitar Registro' }}</span>
        </button>
      </div>

      <div v-if="errorMessage"
        class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ errorMessage }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const errorMessage = ref('')
const { accessToken, isAuthenticated, checkAuth } = useAuth()

// Determinar si estamos en modo edición
const esEdicion = computed(() => route.params.id !== undefined)

const centro = reactive({
  nombre: '',
  identificacion: '',
  direccion: '',
  telefono: '',
  email: '',
  especialidades: '',
  matricula: '',
  horarios: '',
  web: '',
  logo: null,
  logoPreview: null,
})

const inputLogo = ref(null)

onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      showMessage('Debe iniciar sesión para acceder a esta página', 'error')
      setTimeout(() => {
        router.push('/')
      }, 2000)
      return
    }
  }

  // Si estamos en modo edición, cargar los datos del centro
  if (esEdicion.value) {
    await cargarCentroParaEdicion()
  }
})

const cargarCentroParaEdicion = async () => {
  try {
    loading.value = true
    const centroId = route.params.id
    
    const response = await fetch(`http://localhost:8000/api/centros-veterinarios/${centroId}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
    })

    const data = await response.json()
    
    if (!response.ok) {
      throw new Error(data.message || 'Error al cargar el centro')
    }

    if (data.success) {
      const centroData = data.data
      // Llenar el formulario con los datos existentes
      centro.nombre = centroData.nombre
      centro.identificacion = centroData.identificacion
      centro.direccion = centroData.direccion
      centro.telefono = centroData.telefono
      centro.email = centroData.email
      centro.especialidades = centroData.especialidades_medicas
      centro.matricula = centroData.matricula_establecimiento || '' // Cambiado
      centro.horarios = centroData.horarios_atencion || '' // Cambiado
      centro.web = centroData.web_redes_sociales || '' // Cambiado
      
      // Cargar preview del logo si existe
      if (centroData.logo_path) {
        centro.logoPreview = `http://localhost:8000/storage/${centroData.logo_path}`
      }
    }
    
  } catch (error) {
    console.error('Error:', error)
    errorMessage.value = error.message || 'Error al cargar los datos del centro'
  } finally {
    loading.value = false
  }
}

const activarInput = () => {
  inputLogo.value?.click()
}

const handleLogo = (event) => {
  const file = event.target.files[0]
  if (file) {
    centro.logo = file
    centro.logoPreview = URL.createObjectURL(file)
  }
}

const registrarCentro = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const formData = new FormData()
    formData.append('nombre', centro.nombre)
    formData.append('identificacion', centro.identificacion)
    formData.append('direccion', centro.direccion)
    formData.append('telefono', centro.telefono)
    formData.append('email', centro.email)
    formData.append('especialidades_medicas', centro.especialidades)
    formData.append('matricula_establecimiento', centro.matricula || '') // Cambiado
    formData.append('horarios_atencion', centro.horarios || '') // Cambiado
    formData.append('web_redes_sociales', centro.web || '') // Cambiado
    if (centro.logo) {
      formData.append('logo', centro.logo)
    }

    const response = await fetch('http://localhost:8000/api/registrar-centro', {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
    })

    const data = await response.json()
    
    if (!response.ok) {
      if (response.status === 422 && data.errors) {
        const errorMessages = Object.values(data.errors).flat().join(', ')
        throw new Error(`Error de validación: ${errorMessages}`)
      }
      throw new Error(data.message || 'Error al registrar centro veterinario')
    }

    alert('Centro veterinario registrado exitosamente')
    router.push('/veterinarios/centroVeterinario')
  } catch (error) {
    console.error('Error:', error)
    errorMessage.value = error.message || 'Ocurrió un error inesperado'
  } finally {
    loading.value = false
  }
}

const actualizarCentro = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const formData = new FormData()
    formData.append('nombre', centro.nombre)
    formData.append('identificacion', centro.identificacion)
    formData.append('direccion', centro.direccion)
    formData.append('telefono', centro.telefono)
    formData.append('email', centro.email)
    formData.append('especialidades_medicas', centro.especialidades)
    formData.append('matricula_establecimiento', centro.matricula || '')
    formData.append('horarios_atencion', centro.horarios || '')
    formData.append('web_redes_sociales', centro.web || '')
    if (centro.logo) {
      formData.append('logo', centro.logo)
    }

    // DEPURACIÓN: Ver qué datos se están enviando
    console.log('Datos del formulario:')
    for (let [key, value] of formData.entries()) {
      console.log(`${key}:`, value)
    }

    const centroId = route.params.id
    
    // SOLUCIÓN: Usar POST con _method=PUT para FormData
    formData.append('_method', 'PUT')
    
    const response = await fetch(`http://localhost:8000/api/centros-veterinarios/${centroId}`, {
      method: 'POST', // Cambiar a POST
      body: formData,
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
        // No incluir Content-Type para FormData, el navegador lo establece automáticamente
      },
    })

    const data = await response.json()
    
    if (!response.ok) {
      if (response.status === 422 && data.errors) {
        const errorMessages = Object.values(data.errors).flat().join(', ')
        throw new Error(`Error de validación: ${errorMessages}`)
      }
      throw new Error(data.message || 'Error al actualizar centro veterinario')
    }

    alert('Centro veterinario actualizado exitosamente')
    router.push('/veterinarios/centroVeterinario')
  } catch (error) {
    console.error('Error:', error)
    errorMessage.value = error.message || 'Ocurrió un error inesperado'
  } finally {
    loading.value = false
  }
}
</script>
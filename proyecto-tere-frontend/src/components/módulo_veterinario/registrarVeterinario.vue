<!-- registrarVeterinario.vue - Versión CORREGIDA -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esModificacion ? 'Modificar Veterinario' : 'Registrar Veterinario' }}</h1>

    <!-- Modal de Confirmación -->
    <div v-if="showModal" 
         data-testid="modal-confirmacion"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4">{{ esModificacion ? 'Confirmar Modificación' : 'Confirmar Registro' }}</h3>
        <p class="mb-6">{{ esModificacion ? '¿Estás seguro de que deseas modificar los datos de este veterinario?' : '¿Estás seguro de que deseas registrar este veterinario?' }}</p>
        <div class="flex justify-end gap-4">
          <button
            @click="showModal = false"
            class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50"
          >
            Cancelar
          </button>
          <button
            @click="confirmarAccion"
            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
          >
            {{ esModificacion ? 'Modificar' : 'Registrar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Indicador de modo edición -->
    <div v-if="esModificacion" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
      <p class="text-blue-700">✏️ Modo edición - Estás modificando los datos del veterinario</p>
    </div>

    <form @submit.prevent="mostrarModal" class="space-y-4">
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
            <input 
              v-model="veterinario.nombre" 
              type="text" 
              :required="!esModificacion"
              class="w-full border rounded p-2" 
            />
          </div>

          <div>
            <label class="block font-medium">Correo electrónico profesional</label>
            <input 
              v-model="veterinario.email" 
              type="email" 
              :required="!esModificacion"
              :disabled="esModificacion"
              class="w-full border rounded p-2"
              :class="{ 'bg-gray-100': esModificacion }"
            />
            <p v-if="esModificacion" class="text-xs text-gray-500 mt-1">
              El email no se puede modificar por seguridad
            </p>
          </div>

          <div>
            <label class="block font-medium">Matrícula Profesional</label>
            <input 
              v-model="veterinario.matricula" 
              type="text" 
              :required="!esModificacion"
              class="w-full border rounded p-2" 
            />
          </div>

          <div>
            <label class="block font-medium">Especialidad</label>
            <input 
              v-model="veterinario.especialidad" 
              type="text" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Clínica general, cirugía, etc." 
            />
          </div>
        </div>

        <!-- Columna derecha - Fotos -->
        <div>
          <label class="block font-medium mb-2">{{ esModificacion ? 'Foto del veterinario (actual)' : 'Foto del veterinario' }}</label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div
              v-for="(foto, index) in fotos"
              :key="index"
              class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-full aspect-square"
              @click="!foto.preview && activarInput(index)"
            >
              <button 
                type="button" 
                @click.stop="quitarFoto(index)" 
                v-if="foto.preview" 
                class="absolute top-1 right-1 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700"
              >
                <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-3xl" />
              </button>

              <input 
                :ref="el => inputsFoto[index] = el" 
                type="file" 
                accept="image/*" 
                @change="handleFoto($event, index)" 
                class="hidden" 
              />

              <div v-if="foto.preview" class="h-full flex flex-col">
                <img 
                  :src="foto.preview" 
                  alt="Preview" 
                  class="w-full h-full object-cover rounded-md border-gray-300 mx-auto flex-grow" 
                />
              </div>

              <div v-else class="text-green-400 mt-14">
                <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-4xl mb-2" />
                <div class="text-gray-400">{{ esModificacion && index === 0 ? 'Cambiar foto' : 'Agregar foto' }}</div>
              </div>
            </div>
          </div>
          <p v-if="esModificacion && fotos[0]?.preview && !fotos[0]?.archivo" class="text-xs text-gray-500 mt-2">
            La foto actual se mantendrá si no seleccionas una nueva
          </p>
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
          <input 
            v-model="veterinario.experiencia" 
            type="number" 
            min="0" 
            class="w-full border rounded p-2" 
          />
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Biografía o descripción profesional</label>
          <textarea 
            v-model="veterinario.descripcion" 
            rows="4" 
            maxlength="500" 
            class="w-full border rounded p-2 resize-none"
          ></textarea>
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
          <input 
            v-model="veterinario.telefono" 
            type="tel" 
            class="w-full border rounded p-2" 
          />
        </div>

        <div>
          <label class="block font-medium">Email de contacto</label>
          <input 
            v-model="veterinario.emailContacto" 
            type="text" 
            class="w-full border rounded p-2" 
          />
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button 
          type="button"
          @click="confirmarCancelar"
          :disabled="loading"
          class="bg-gray-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-gray-700 transition-colors disabled:bg-gray-300"
        >
          Cancelar
        </button>
        
        <button 
          type="submit" 
          :disabled="loading"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 rounded-full hover:bg-blue-700 transition-colors disabled:bg-blue-300"
        >
          <span v-if="loading">Procesando...</span>
          <span v-else>{{ esModificacion ? 'Guardar Cambios' : 'Solicitar Cuenta' }}</span>
        </button>
      </div>

      <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ errorMessage }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const errorMessage = ref('')
const showModal = ref(false)
const esModificacion = ref(false)
const veterinarioId = ref(null)

// Obtener el token de autenticación
const { accessToken, isAuthenticated, checkAuth } = useAuth()

// ✅ Función para headers de autenticación (SOLO para modificación)
const getAuthHeaders = () => {
  if (!accessToken.value) {
    throw new Error('No hay token de autenticación disponible')
  }
  
  return {
    'Authorization': `Bearer ${accessToken.value}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
}

// ✅ Función para headers de autenticación para FormData (SOLO para modificación)
const getAuthHeadersFormData = () => {
  if (!accessToken.value) {
    throw new Error('No hay token de autenticación disponible')
  }
  
  return {
    'Authorization': `Bearer ${accessToken.value}`,
    'Accept': 'application/json'
  }
}

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
  preview: null,
  esExistente: false
})))

const inputsFoto = ref([])

const handleFoto = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    fotos.value[index].archivo = file
    fotos.value[index].preview = URL.createObjectURL(file)
    fotos.value[index].esExistente = false
  }
}

const activarInput = (index) => {
  inputsFoto.value[index]?.click()
}

const quitarFoto = (index) => {
  fotos.value[index].archivo = null
  fotos.value[index].preview = null
  fotos.value[index].esExistente = false
}

const mostrarModal = () => {
  // Validaciones previas
  if (!esModificacion.value) {
    const tieneFotos = fotos.value.some(foto => foto.archivo !== null || foto.esExistente);
    if (!tieneFotos) {
      errorMessage.value = 'Debes subir al menos una foto';
      return;
    }
  }
  
  showModal.value = true
}

const confirmarAccion = () => {
  showModal.value = false
  if (esModificacion.value) {
    modificarVeterinario()
  } else {
    registrarVeterinario()
  }
}

// ✅ REGISTRO - NO requiere autenticación (sin token)
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

    const tieneFotos = fotos.value.some(foto => foto.archivo !== null);
    if (!tieneFotos) {
      errorMessage.value = 'Debes subir al menos una foto';
      return;
    }
    
    // ✅ Enviar datos al backend SIN autenticación (sin headers de Authorization)
    const response = await fetch('http://localhost:8000/api/registrar-veterinario', {
      method: 'POST',
      body: formData
      // ⚠️ IMPORTANTE: No incluir headers de autenticación
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

// ✅ MODIFICACIÓN - SÍ requiere autenticación (con token)
const modificarVeterinario = async () => {
  loading.value = true
  errorMessage.value = ''
  
  try {
    // Verificar autenticación
    if (!isAuthenticated.value) {
      throw new Error('No estás autenticado. Por favor, inicia sesión.')
    }
    
    const formData = new FormData()
    
    // Agregar datos del veterinario
    formData.append('nombre', veterinario.nombre)
    formData.append('matricula', veterinario.matricula)
    formData.append('especialidad', veterinario.especialidad)
    formData.append('experiencia', veterinario.experiencia)
    formData.append('descripcion', veterinario.descripcion)
    formData.append('telefono', veterinario.telefono)
    formData.append('emailContacto', veterinario.emailContacto)
    
    // Agregar nuevas fotos
    fotos.value.forEach((foto, i) => {
      if (foto.archivo) {
        formData.append(`foto${i}`, foto.archivo)
      }
    })
    
    // Usar POST con _method=PUT para servidores que no soportan PUT con FormData
    formData.append('_method', 'PUT')
    
    // ✅ Enviar datos al backend CON autenticación
    const response = await fetch(`http://localhost:8000/api/veterinario/${veterinarioId.value}`, {
      method: 'POST',
      body: formData,
      headers: getAuthHeadersFormData() // ✅ Solo aquí se requiere token
    })

    const data = await response.json()
    
    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Sesión expirada. Por favor, inicia sesión nuevamente.')
      }
      if (response.status === 403) {
        throw new Error('No tienes permiso para modificar este perfil.')
      }
      if (response.status === 422) {
        const errors = data.errors
        const errorMessages = Object.values(errors).flat().join(', ')
        throw new Error(errorMessages || 'Error de validación')
      }
      throw new Error(data.message || 'Error al modificar veterinario')
    }
    
    alert(data.message || 'Veterinario modificado exitosamente')
    
    if (route.path.includes('/admin') || window.location.pathname.includes('/admin')) {
      router.push('/admin/veterinarios')
    } else {
      router.push('/veterinarios/perfil')
    }
    
  } catch (error) {
    console.error('Error:', error)
    errorMessage.value = error.message || 'Ocurrió un error inesperado'
  } finally {
    loading.value = false
  }
}

const cargarDatosVeterinario = async (id) => {
  loading.value = true
  try {
    // Verificar autenticación
    if (!isAuthenticated.value) {
      throw new Error('No estás autenticado. Por favor, inicia sesión.')
    }
    
    // Usar axios con el token de autenticación
    const response = await axios.get(`/api/veterinario/${id}`, {
      headers: getAuthHeaders()
    })
    
    if (response.data.success) {
      const data = response.data.data
      
      // Cargar datos en el formulario
      veterinario.nombre = data.nombre
      veterinario.email = data.email
      veterinario.matricula = data.matricula
      veterinario.especialidad = data.especialidad || ''
      veterinario.experiencia = data.experiencia || ''
      veterinario.descripcion = data.descripcion || ''
      veterinario.telefono = data.telefono || ''
      veterinario.emailContacto = data.email_contacto || ''
      
      // Cargar fotos existentes
      if (data.fotos && data.fotos.length > 0) {
        data.fotos.forEach((fotoUrl, index) => {
          if (index < fotos.value.length) {
            fotos.value[index].preview = fotoUrl
            fotos.value[index].esExistente = true
            fotos.value[index].archivo = null
          }
        })
      }
    } else {
      throw new Error('Error al cargar los datos')
    }
    
  } catch (error) {
    console.error('Error cargando datos:', error)
    
    if (error.response?.status === 401) {
      errorMessage.value = 'Sesión expirada. Por favor, inicia sesión nuevamente.'
    } else {
      errorMessage.value = error.response?.data?.message || 'Error al cargar los datos del veterinario'
    }
  } finally {
    loading.value = false
  }
}

function confirmarCancelar() {
  const mensaje = esModificacion.value 
    ? '¿Estás seguro de que deseas cancelar la modificación? Los cambios no se guardarán.'
    : '¿Estás seguro de que deseas cancelar el registro?'
  
  if (window.confirm(mensaje)) {
    cerrar()
  }
}

const cerrar = () => {
  if (route.query.from) {
    router.push(route.query.from)
  } else {
    router.back()
  }
}

// Verificar autenticación al montar el componente
onMounted(async () => {
  const id = route.params.id || route.query.id
  
  if (id) {
    esModificacion.value = true
    veterinarioId.value = id
    // ✅ Solo para modificación verificamos autenticación
    const isAuth = await checkAuth()
    if (!isAuth) {
      errorMessage.value = 'Debes iniciar sesión para modificar un veterinario'
      // Opcional: redirigir al login después de unos segundos
      setTimeout(() => {
        router.push('/login')
      }, 2000)
      return
    }
    await cargarDatosVeterinario(id)
  }
  
  console.log(esModificacion.value ? 'Modificación de veterinario' : 'Registro de veterinario')
  console.log('Modo:', esModificacion.value ? 'Requiere autenticación' : 'No requiere autenticación')
})
</script>
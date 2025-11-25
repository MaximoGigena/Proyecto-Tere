<!-- registrarUsuario.vue - FLUJO COMPLETO -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>
  
  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">Registrar Usuario</h1>

    <!-- Modal de Confirmación -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4">Confirmar Registro</h3>
        <p class="mb-6">¿Estás seguro de que deseas registrar este usuario?</p>
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
            Registrar
          </button>
        </div>
      </div>
    </div>

    <!-- Overlay para Datos Opcionales (PRIMERO) -->
    <OverlayModal
      v-if="showOverlayDatosOpcionales"
      titulo="Datos Opcionales"
      :componente="DatosOpcionales"
      :props-componente="propsDatosOpcionales"
      texto-omitir="Omitir por ahora"
      texto-guardar="Continuar con Contacto"
      @guardar="guardarDatosOpcionales"
      @omitir="omitirDatosOpcionales"
      @cerrar="confirmarCerrarDatosOpcionales"
    />

    <!-- Overlay para Datos de Contacto (SEGUNDO) -->
    <OverlayModal
      v-if="showOverlayDatosContacto"
      titulo="Datos de Contacto"
      :componente="DatosContacto"
      :props-componente="propsDatosContacto"
      texto-omitir="Omitir contacto"
      texto-guardar="Finalizar Registro"
      @guardar="guardarDatosContacto"
      @omitir="omitirDatosContacto"
      @cerrar="confirmarCerrarDatosContacto"
    />

    <form @submit.prevent="mostrarModal" class="space-y-4">
      <!-- ... (formulario de datos obligatorios igual) ... -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
          Datos Obligatorios
        </h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna izquierda - Formulario -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Nombre</label>
            <input
              v-model="usuario.nombre"
              type="text"
              required
              class="w-full border rounded p-2 focus:outline-none focus:ring"
            />
          </div>  

          <div>
            <label class="block font-medium">Email</label>
            <input
              v-model="usuario.email"
              type="text"
              required
              class="w-full border rounded p-2 focus:outline-none focus:ring"
            />
          </div> 
          
          <div>
            <label class="block font-medium">Contraseña</label>
            <input
              v-model="usuario.password"
              type="password"
              required
              class="w-full border rounded p-2 focus:outline-none focus:ring"
            />
          </div>

          <div>
            <label class="block font-medium">Confirmar Contraseña</label>
            <input
              v-model="usuario.confirmPassword"
              type="password"
              required
              class="w-full border rounded p-2 focus:outline-none focus:ring"
            />
          </div>

          <div>
            <label class="block font-medium mb-1">Fecha de nacimiento</label>
            <div class="flex gap-2">
              <!-- Día -->
              <input
                v-model.number="usuario.fechaNacimiento.dia"
                type="number"
                min="1"
                max="31"
                placeholder="Día"
                required
                class="w-1/3 border rounded p-2"
              />

              <!-- Mes -->
              <select
                v-model="usuario.fechaNacimiento.mes"
                required
                class="w-1/3 border rounded p-2"
              >
                <option disabled value="">Mes</option>
                <option value="1">Enero</option>
                <option value="2">Febrero</option>
                <option value="3">Marzo</option>
                <option value="4">Abril</option>
                <option value="5">Mayo</option>
                <option value="6">Junio</option>
                <option value="7">Julio</option>
                <option value="8">Agosto</option>
                <option value="9">Septiembre</option>
                <option value="10">Octubre</option>
                <option value="11">Noviembre</option>
                <option value="12">Diciembre</option>
              </select>

              <!-- Año -->
              <input
                v-model.number="usuario.fechaNacimiento.anio"
                type="number"
                min="1930"
                :max="new Date().getFullYear()"
                placeholder="Año"
                required
                class="w-1/3 border rounded p-2"
              />
            </div>
          </div>
        </div>

        <!-- Columna derecha - Fotos -->
        <div>
          <label class="block font-medium mb-2">Sube al menos 1 foto de tu persona</label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div
              v-for="(foto, index) in fotos"
              :key="index"
              class="relative border-2 border-dashed border-gray-600 rounded-md text-center cursor-pointer h-full aspect-square"
              @click="!foto.preview && activarInput(index)" 
            >
              <!-- Botón eliminar -->
              <button
                type="button"
                @click.stop="quitarFoto(index)" 
                v-if="foto.preview"
                class="absolute top-1 right-1 bg-white rounded-full shadow z-10 text-red-500 hover:text-red-700 mt-35 -mr-2"
              >
                <font-awesome-icon :icon="['fas', 'circle-xmark']" class="text-3xl" />
              </button>

              <!-- Input oculto con ref dinámico -->
              <input
                :ref="el => inputsFoto[index] = el"
                type="file"
                accept="image/*"
                @change="handleFoto($event, index)"
                class="hidden"
              />

              <!-- Vista previa -->
              <div v-if="foto.preview" class="h-full flex flex-col">
                <img
                  :src="foto.preview"
                  alt="Preview"
                  class="w-full h-full object-cover rounded-md border-gray-300 mx-auto flex-grow"
                />
              </div>

              <!-- Indicador visual si no hay foto -->
              <div v-else class="text-green-400 mt-14">
                <font-awesome-icon :icon="['fas', 'circle-plus']" class="text-4xl mb-2" />
                <div class="text-gray-400">Agregar foto</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="pt-4 flex items-center justify-center gap-4">
        <!-- Botón Cancelar -->
        <button
          type="button"
          @click="confirmarCancelar"
          class="bg-gray-500 text-white font-bold text-2xl px-4 py-2 text-center rounded-full hover:bg-gray-700 transition-colors"
        >
          Cancelar
        </button>
        
        <!-- Botón Registrar usuario -->
        <button
          type="submit"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 text-center rounded-full hover:bg-blue-700 transition-colors"
        >
          Registrar Usuario
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useAuthToken } from '@/composables/useAuthToken'
import OverlayModal from '@/components/módulo_usuario/overlayRegistro.vue'
import DatosOpcionales from '@/components/módulo_usuario/DatosOpcionales.vue'
import DatosContacto from '@/components/módulo_usuario/DatosContacto.vue'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, setToken, clearToken } = useAuthToken()


// Estado del usuario (se mantiene igual)
const usuario = reactive({
  nombre: '',
  email: '',
  password: '',
  confirmPassword: '',
  edad: null,
  fechaNacimiento: {
    dia: null,
    mes: '',
    anio: null,
  }
})

// Estados para overlays
const showOverlayDatosOpcionales = ref(false)
const showOverlayDatosContacto = ref(false)
const showModal = ref(false)

// Datos para pasar a los componentes
const propsDatosOpcionales = reactive({
  datosIniciales: {}
})


// En la fase 1, siempre es registro
const esModificacion = ref(false)

const propsDatosContacto = reactive({
  datosIniciales: {},
  usuarioId: null, // Se establecerá después del registro

  emailRegistro: usuario.email // Pasar el email del registro
})

const fotos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null
})))

const inputsFoto = ref([])

// Métodos para fotos (se mantienen igual)
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

const calcularEdad = () => {
  if (usuario.fechaNacimiento.dia && usuario.fechaNacimiento.mes && usuario.fechaNacimiento.anio) {
    const hoy = new Date();
    const fechaNac = new Date(
      usuario.fechaNacimiento.anio,
      usuario.fechaNacimiento.mes - 1,
      usuario.fechaNacimiento.dia
    );
    
    let edad = hoy.getFullYear() - fechaNac.getFullYear();
    const mes = hoy.getMonth() - fechaNac.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) {
      edad--;
    }
    
    usuario.edad = edad;
  }
}

const mostrarModal = () => {
  calcularEdad();
  
  if (usuario.password !== usuario.confirmPassword) {
    alert('Las contraseñas no coinciden');
    return;
  }
  
  showModal.value = true;
}

const confirmarAccion = async () => {
  showModal.value = false;
  await registrarUsuario();
}

const registrarUsuario = async () => {
  try {
    const formData = new FormData();
    
    // Datos básicos
    formData.append('nombre', usuario.nombre);
    formData.append('email', usuario.email);
    formData.append('password', usuario.password);
    if (usuario.edad) formData.append('edad', usuario.edad);

    // Foto de perfil
    if (fotos.value[0]?.archivo) {
      formData.append('foto_perfil', fotos.value[0].archivo);
    }

    // Obtener CSRF token
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });

    const response = await axios.post('/api/registrar-usuario', formData, {
      withCredentials: true,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (response.data.success) {
      if (response.data.access_token) {
        setToken(response.data.access_token);
      }

      // Guardar ID para el siguiente paso
      if (response.data.user?.id) {
        propsDatosContacto.usuarioId = response.data.user.id;
      }

      // ✅ ACTUALIZAR el email en las props (importante porque al inicio es string vacío)
      propsDatosContacto.emailRegistro = usuario.email;

      // Mostrar overlay
      showOverlayDatosOpcionales.value = true;

    } else {
      alert(response.data.message || 'Error al registrar usuario');
    }

  } catch (error) {
    console.error('Error completo:', error);

    if (error.response) {
      console.error('Datos del error:', error.response.data);
      alert(`Error: ${error.response.data.message || 'Error en el servidor'}`);
    } else if (error.request) {
      alert('Error de conexión. Verifica tu red o si el servidor está funcionando.');
    } else {
      alert('Error al enviar los datos. Por favor intenta nuevamente.');
    }
  }
};


// ========== MANEJO DE DATOS OPCIONALES ==========
const guardarDatosOpcionales = async (datos) => {
  try {
    if (datos && Object.keys(datos).length > 0) {
      const response = await axios.post('/api/actualizar-datos-opcionales', datos, {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Accept': 'application/json'
        }
      });

      if (response.data.success) {
        console.log('Datos opcionales guardados exitosamente');
      }
    }

    // Cerrar overlay actual y mostrar SIGUIENTE: Datos de Contacto
    showOverlayDatosOpcionales.value = false;
    showOverlayDatosContacto.value = true;
    
  } catch (error) {
    console.error('Error al guardar datos opcionales:', error);
    // Aún así continuar con datos de contacto
    showOverlayDatosOpcionales.value = false;
    showOverlayDatosContacto.value = true;
  }
}

const omitirDatosOpcionales = () => {
  // Saltar directamente a datos de contacto
  showOverlayDatosOpcionales.value = false;
  showOverlayDatosContacto.value = true;
}

const confirmarCerrarDatosOpcionales = () => {
  if (confirm('¿Saltar datos opcionales e ir directamente a datos de contacto?')) {
    omitirDatosOpcionales();
  }
}

// ========== MANEJO DE DATOS DE CONTACTO ==========
const guardarDatosContacto = async (datos) => {
  try {
    if (datos && Object.keys(datos).length > 0) {
      const response = await axios.post('/api/actualizar-datos-contacto', datos, {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Accept': 'application/json'
        }
      });

      if (response.data.success) {
        console.log('Datos de contacto guardados exitosamente');
      }
    }

    // FINALIZAR - Redirigir al explorar encuentros
    showOverlayDatosContacto.value = false;
    router.push('/explorar/encuentros');
    
  } catch (error) {
    console.error('Error al guardar datos de contacto:', error);
    alert('Los datos de contacto no pudieron guardarse, pero puedes completarlos más tarde en tu perfil.');
    router.push('/explorar/encuentros');
  }
}

const omitirDatosContacto = () => {
  // Finalizar registro sin datos de contacto
  showOverlayDatosContacto.value = false;
  router.push('/explorar/encuentros');
}

const confirmarCerrarDatosContacto = () => {
  if (confirm('¿Estás seguro de que deseas omitir los datos de contacto? Podrás completarlos más tarde en tu perfil.')) {
    omitirDatosContacto();
  }
}

function confirmarCancelar() {
  if (window.confirm("¿Estás seguro de que deseas cancelar y volver?")) {
    cerrar();
  }
}

const cerrar = () => {
  if (route.query.from) {
    router.push(route.query.from)
  } else {
    router.back()
  }
}

onMounted(() => {
  console.log('Registro de usuario - Flujo completo con overlays');
});
</script>
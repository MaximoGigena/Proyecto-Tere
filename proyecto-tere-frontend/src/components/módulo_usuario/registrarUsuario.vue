<!-- registrarUsuario.vue - Versión modificada para soportar modificación -->
<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>
  
  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esModificacion ? 'Modificar Usuario' : 'Registrar Usuario' }}</h1>

    <!-- Modal de Confirmación -->
    <div v-if="showModal" 
          data-testid="modal-confirmacion"
          class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4">{{ esModificacion ? 'Confirmar Modificación' : 'Confirmar Registro' }}</h3>
        <p class="mb-6">{{ esModificacion ? '¿Estás seguro de que deseas modificar este usuario?' : '¿Estás seguro de que deseas registrar este usuario?' }}</p>
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

    <!-- Overlay para Datos Opcionales -->
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

    <!-- Overlay para Datos de Contacto -->
    <OverlayModal
      v-if="showOverlayDatosContacto"
      titulo="Datos de Contacto"
      :componente="DatosContacto"
      :props-componente="propsDatosContacto"
      texto-omitir="Omitir contacto"
      texto-guardar="Finalizar"
      @guardar="guardarDatosContacto"
      @omitir="omitirDatosContacto"
      @cerrar="confirmarCerrarDatosContacto"
    />

    <form @submit.prevent="mostrarModal" class="space-y-4">
      <!-- Indicador de modo edición -->
      <div v-if="esModificacion" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
        <p class="text-blue-700"> Modo edición - Estás modificando los datos del usuario</p>
      </div>

      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
          Datos {{ esModificacion ? 'Principales' : 'Obligatorios' }}
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
              placeholder="Nombre del usuario" 
              type="text"
              :required="!esModificacion"
              class="w-full border rounded p-2 focus:outline-none focus:ring"
            />
          </div>  

          <div>
            <label class="block font-medium">Email</label>
            <input
              v-model="usuario.email"
              type="email"
              placeholder="Email"
              :required="!esModificacion"
              :disabled="esModificacion"
              class="w-full border rounded p-2 focus:outline-none focus:ring"
              :class="{ 'bg-gray-100': esModificacion }"
            />
            <p v-if="esModificacion" class="text-xs text-gray-500 mt-1">
              El email no se puede modificar por seguridad
            </p>
          </div> 
          
          <div v-if="!esModificacion">
            <label class="block font-medium">Contraseña</label>
            <input
              v-model="usuario.password"
              type="password"
              required
              class="w-full border rounded p-2 focus:outline-none focus:ring"
            />
          </div>

          <div v-if="!esModificacion">
            <label class="block font-medium">Confirmar Contraseña</label>
            <input
              v-model="usuario.confirmPassword"
              type="password"
              required
              class="w-full border rounded p-2 focus:outline-none focus:ring"
            />
          </div>

          <div v-else>
            <label class="block font-medium">Contraseña (dejar en blanco para no cambiar)</label>
            <input
              v-model="usuario.nuevaPassword"
              type="password"
              placeholder="Nueva contraseña"
              class="w-full border rounded p-2 focus:outline-none focus:ring"
            />
            <input
              v-model="usuario.confirmNuevaPassword"
              type="password"
              placeholder="Confirmar nueva contraseña"
              class="w-full border rounded p-2 focus:outline-none focus:ring mt-2"
            />
          </div>

          <div>
            <label class="block font-medium mb-1">Fecha de nacimiento</label>
            <div class="flex gap-2">
              <input
                v-model.number="usuario.fechaNacimiento.dia"
                type="number"
                min="1"
                max="31"
                placeholder="Día"
                :required="!esModificacion"
                class="w-1/3 border rounded p-2"
              />
              <select
                v-model="usuario.fechaNacimiento.mes"
                :required="!esModificacion"
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
              <input
                v-model.number="usuario.fechaNacimiento.anio"
                type="number"
                min="1930"
                :max="new Date().getFullYear()"
                placeholder="Año"
                :required="!esModificacion"
                class="w-1/3 border rounded p-2"
              />
            </div>
          </div>
        </div>

        <!-- Columna derecha - Fotos -->
        <div>
          <label class="block font-medium mb-2">{{ esModificacion ? 'Foto de perfil actual' : 'Sube al menos 1 foto de tu persona' }}</label>
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
          <p v-if="esModificacion && fotos[0]?.preview" class="text-xs text-gray-500 mt-2">
            La foto actual se mantendrá si no seleccionas una nueva
          </p>
        </div>
      </div>
      
      <div class="pt-4 flex items-center justify-center gap-4">
        <button
          type="button"
          @click="confirmarCancelar"
          class="bg-gray-500 text-white font-bold text-2xl px-4 py-2 text-center rounded-full hover:bg-gray-700 transition-colors"
        >
          Cancelar
        </button>
        
        <button
          type="submit"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 text-center rounded-full hover:bg-blue-700 transition-colors"
        >
          {{ esModificacion ? 'Guardar Cambios' : 'Registrar Usuario' }}
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
import { useUsuarioModificacion } from '@/composables/useUsuarioModificacion'
import OverlayModal from '@/components/módulo_usuario/overlayRegistro.vue'
import DatosOpcionales from '@/components/módulo_usuario/DatosOpcionales.vue'
import DatosContacto from '@/components/módulo_usuario/DatosContacto.vue'

const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, setToken, clearToken } = useAuthToken()
const { 
  usuarioModificacion, 
  cargarDatosUsuario, 
  actualizarDatosBasicos,
  actualizarDatosOpcionales,
  actualizarDatosContacto,
  cargando 
} = useUsuarioModificacion()

// Detectar si es modificación o registro
const esModificacion = ref(false)
const userId = ref(null)

// Estado del usuario
const usuario = reactive({
  nombre: '',
  email: '',
  password: '',
  confirmPassword: '',
  nuevaPassword: '',
  confirmNuevaPassword: '',
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

const propsDatosContacto = reactive({
  datosIniciales: {},
  usuarioId: null,
  emailRegistro: '',
  esModificacion: false
})

const fotos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null
})))

const inputsFoto = ref([])

// Métodos para fotos
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
  
  if (!esModificacion.value && usuario.password !== usuario.confirmPassword) {
    alert('Las contraseñas no coinciden');
    return;
  }
  
  if (esModificacion.value && usuario.nuevaPassword && usuario.nuevaPassword !== usuario.confirmNuevaPassword) {
    alert('Las nuevas contraseñas no coinciden');
    return;
  }
  
  showModal.value = true;
}

const confirmarAccion = async () => {
  showModal.value = false;
  if (esModificacion.value) {
    await modificarUsuario();
  } else {
    await registrarUsuario();
  }
}

// Función para modificar usuario
const modificarUsuario = async () => {
  try {
    // Preparar datos básicos
    const datosBasicos = {
      nombre: usuario.nombre,
      email: usuario.email,
      edad: usuario.edad,
      fechaNacimiento: usuario.fechaNacimiento,
      fotoPerfil: fotos.value[0]?.archivo
    }
    
    // Si hay nueva contraseña
    if (usuario.nuevaPassword) {
      datosBasicos.password = usuario.nuevaPassword
    }
    
    // 1. Actualizar datos básicos
    await actualizarDatosBasicos(datosBasicos)
    
    // 2. Recargar los datos del usuario para tener los datos opcionales actualizados
    await cargarDatosUsuario(userId.value)
    
    // 3. Configurar props para datos opcionales con los datos RECIÉN CARGADOS
    propsDatosOpcionales.datosIniciales = {
      ocupacion: usuarioModificacion.ocupacion || '',
      tipoVivienda: usuarioModificacion.tipoVivienda || '',
      experienciaMascotas: usuarioModificacion.experienciaMascotas || '',
      conviveConNiños: usuarioModificacion.conviveConNiños || '',
      conviveConMascotas: usuarioModificacion.conviveConMascotas || '',
      descripcion: usuarioModificacion.descripcion || ''
    }
    
    // 4. Configurar props para datos de contacto
    // ✅ CORRECCIÓN: Usar usuarioId (ID de la tabla usuarios) no userId
    propsDatosContacto.usuarioId = usuarioModificacion.user_id // o usuarioId.value
    propsDatosContacto.emailRegistro = usuario.email
    propsDatosContacto.esModificacion = true
    propsDatosContacto.datosIniciales = {
      dni: usuarioModificacion.dni || '',
      telefono_contacto: usuarioModificacion.telefono_contacto || '',
      email_contacto: usuarioModificacion.email_contacto || '',
      nombre_completo: usuarioModificacion.nombre_completo || ''
    }
    
    // 5. Mostrar overlays
    showOverlayDatosOpcionales.value = true
    
  } catch (error) {
    console.error('Error al modificar usuario:', error)
    alert('Error al modificar los datos del usuario')
  }
}

// Función para registrar usuario (existente, con modificaciones)
const registrarUsuario = async () => {
  try {
    const formData = new FormData();
    
    formData.append('nombre', usuario.nombre);
    formData.append('email', usuario.email);
    formData.append('password', usuario.password);
    if (usuario.edad) formData.append('edad', usuario.edad);

    if (fotos.value[0]?.archivo) {
      formData.append('foto_perfil', fotos.value[0].archivo);
    }

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

      if (response.data.user?.id) {
        propsDatosContacto.usuarioId = response.data.user.id;
        userId.value = response.data.user.id;
      }

      propsDatosContacto.emailRegistro = usuario.email;
      propsDatosContacto.esModificacion = false;

      showOverlayDatosOpcionales.value = true;

    } else {
      alert(response.data.message || 'Error al registrar usuario');
    }

  } catch (error) {
    console.error('Error completo:', error);
    alert('Error al registrar usuario');
  }
};

// ========== MANEJO DE DATOS OPCIONALES ==========
const guardarDatosOpcionales = async (datos) => {
  try {
    if (datos && Object.keys(datos).length > 0) {
      if (esModificacion.value) {
        await actualizarDatosOpcionales(datos)
      } else {
        const response = await axios.post('/api/actualizar-datos-opcionales', datos, {
          headers: {
            'Authorization': `Bearer ${accessToken.value}`,
            'Accept': 'application/json'
          }
        });
        
        if (!response.data.success) {
          console.warn('Error guardando datos opcionales');
        }
      }
      console.log('Datos opcionales guardados exitosamente');
    }

    showOverlayDatosOpcionales.value = false;
    showOverlayDatosContacto.value = true;
    
  } catch (error) {
    console.error('Error al guardar datos opcionales:', error);
    showOverlayDatosOpcionales.value = false;
    showOverlayDatosContacto.value = true;
  }
}

const omitirDatosOpcionales = () => {
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
      if (esModificacion.value) {
        await actualizarDatosContacto(datos)
      } else {
        const response = await axios.post('/api/actualizar-datos-contacto', datos, {
          headers: {
            'Authorization': `Bearer ${accessToken.value}`,
            'Accept': 'application/json'
          }
        });
        
        if (!response.data.success) {
          console.warn('Error guardando datos de contacto');
        }
      }
      console.log('Datos de contacto guardados exitosamente');
    }

    showOverlayDatosContacto.value = false;
    
    if (esModificacion.value) {
      router.push('/explorar/perfil/mascotas'); // O la ruta que corresponda
      alert('Usuario modificado exitosamente');
    } else {
      router.push('/explorar/encuentros');
    }
    
  } catch (error) {
    console.error('Error al guardar datos de contacto:', error);
    if (esModificacion.value) {
      router.push('/explorar/perfil/mascotas');
      alert('Usuario modificado, pero hubo problemas con los datos de contacto');
    } else {
      alert('Los datos de contacto no pudieron guardarse, pero puedes completarlos más tarde en tu perfil.');
      router.push('/explorar/encuentros');
    }
  }
}

const omitirDatosContacto = () => {
  showOverlayDatosContacto.value = false;
  
  if (esModificacion.value) {
    router.push('/admin/usuarios');
    alert('Usuario modificado exitosamente');
  } else {
    router.push('/explorar/encuentros');
  }
}

const confirmarCerrarDatosContacto = () => {
  const mensaje = esModificacion.value 
    ? '¿Estás seguro de que deseas omitir los datos de contacto? Los datos existentes se mantendrán.'
    : '¿Estás seguro de que deseas omitir los datos de contacto? Podrás completarlos más tarde en tu perfil.';
  
  if (confirm(mensaje)) {
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

// Cargar datos si es modo modificación
onMounted(async () => {
  const userIdParam = route.params.id || route.query.id
  
  if (userIdParam) {
    esModificacion.value = true
    userId.value = userIdParam
    
    const cargado = await cargarDatosUsuario(userIdParam)
    
    if (cargado) {
      // Llenar el formulario con los datos cargados
      usuario.nombre = usuarioModificacion.nombre
      usuario.email = usuarioModificacion.email
      usuario.edad = usuarioModificacion.edad
      usuario.fechaNacimiento = { ...usuarioModificacion.fechaNacimiento }
      
      // Si hay foto de perfil, mostrarla
      if (usuarioModificacion.foto_perfil) {
        fotos.value[0].preview = usuarioModificacion.foto_perfil
        // No es necesario archivo ya que es URL existente
      }
    } else {
      alert('Error al cargar los datos del usuario')
      router.back()
    }
  }
  
  console.log(esModificacion.value ? 'Modificación de usuario' : 'Registro de usuario');
});
</script>
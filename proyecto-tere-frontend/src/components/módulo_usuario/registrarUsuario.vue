<!-- registrarUsuario.vue-->
<template>
   <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
      <div class="max-w-6xl mx-auto flex items-center">
        <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
      </div>
   </div>
  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto ">
    <h1 class="text-4xl font-bold mb-4">{{ esModificacion ? 'Modificar Usuario' : 'Registrar Usuario' }}</h1>

    <!-- Modal de Confirmación -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-4">
          {{ esModificacion ? 'Confirmar Modificación' : 'Confirmar Registro' }}
        </h3>
        <p class="mb-6">
          {{ esModificacion 
            ? '¿Estás seguro de que deseas guardar los cambios?' 
            : '¿Estás seguro de que deseas registrar este usuario?' 
          }}
        </p>
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
            {{ esModificacion ? 'Guardar Cambios' : 'Registrar' }}
          </button>
        </div>
      </div>
    </div>

    <form @submit.prevent="mostrarModal" class="space-y-4">
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
              :disabled="esModificacion"
              class="w-full border rounded p-2 focus:outline-none focus:ring"
            />
          </div>  

          <div>
            <label class="block font-medium">Email</label>
            <input
              v-model="usuario.email"
              type="text"
              required
              :disabled="esModificacion"
              class="w-full border rounded p-2 focus:outline-none focus:ring"
            />
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
                class="w-1/3 border rounded p-2"
              />

              <!-- Mes -->
              <select
                v-model="usuario.fechaNacimiento.mes"
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
                class="w-1/3 border rounded p-2"
              />
            </div>
          </div>
        </div>

        <!-- Columna derecha - Fotos -->
        <div>
          <label class="block font-medium mb-2">Sube al menos 1 foto de tu persona </label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div
              v-for="(foto, index) in fotos"
              :key="index"
              :required="!esModificacion"
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

      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
          Datos Opcionales
        </h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <!-- Descripción -->
      <p>
        Si bien estos datos son opcionales, completarlos nos ayuda a conocer mejor tus intereses, hábitos y estilo de vida, lo que puede mejorar la experiencia en la plataforma.
      </p>

      <!-- Formulario -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
        <div>
          <label class="block font-medium">Ocupación</label>
          <input
            v-model="usuario.ocupacion"
            type="text"
            class="w-full border rounded p-2"
            placeholder="Ej: Estudiante, Médico, Freelancer"
          />
        </div>

        <div>
          <label class="block font-medium">Tipo de vivienda</label>
          <select
            v-model="usuario.tipoVivienda"
            class="w-full border rounded p-2"
          >
            <option disabled value="">Seleccionar</option>
            <option value="departamento">Departamento</option>
            <option value="casa">Casa</option>
            <option value="campo">Casa en el campo</option>
            <option value="otro">Otro</option>
          </select>
        </div>

        <div>
          <label class="block font-medium">Experiencia con mascotas</label>
          <select
            v-model="usuario.experienciaMascotas"
            class="w-full border rounded p-2"
          >
            <option disabled value="">Seleccionar</option>
            <option value="nueva">Nunca tuve mascotas</option>
            <option value="poca">Tuve pocas veces</option>
            <option value="media">Tengo experiencia moderada</option>
            <option value="alta">Experto/a en cuidado animal</option>
          </select>
        </div>

        <div>
          <label class="block font-medium">Convive con niños</label>
          <select
            v-model="usuario.conviveConNiños"
            class="w-full border rounded p-2"
          >
            <option disabled value="">Seleccionar</option>
            <option value="si">Sí</option>
            <option value="no">No</option>
          </select>
        </div>

        <div>
          <label class="block font-medium">Convive con otras mascotas</label>
          <select
            v-model="usuario.conviveConMascotas"
            class="w-full border rounded p-2"
          >
            <option disabled value="">Seleccionar</option>
            <option value="si">Sí</option>
            <option value="no">No</option>
          </select>
        </div>

        <div class="col-span-full">
          <label class="block font-medium mb-1">Descripción</label>
          <textarea
            v-model="usuario.descripcion"
            rows="4"
            maxlength="500"
            placeholder="Contanos más sobre vos: tu estilo de vida, motivaciones para adoptar, experiencia con animales, etc."
            class="w-full border rounded p-2 resize-none focus:outline-none focus:ring"
          ></textarea>
          <p class="text-sm text-gray-500 text-right mt-1">
            {{ usuario.descripcion.length }}/500 caracteres
          </p>
        </div>
      </div>
      
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
          Datos de Contacto 
        </h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>
      
      <p>Estos datos nos permiten ponernos en contacto con vos en caso de que un veterinario u otro usuario necesite comunicarse por una consulta o seguimiento, Tus datos van a permanecer anonimos y lejos del alcance de los demas usuarios. (Son opcionales, pero te agradecemos cualquier colaboración).</p>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
        <!-- Teléfono -->
        <div>
          <label class="block font-medium">Teléfono</label>
          <input
            v-model="usuario.telefono_contacto"
            type="tel"
            class="w-full border rounded p-2"
            placeholder="Ej: +54 9 11 1234 5xxx"
          />
        </div>

        <!-- Correo electrónico -->
        <div>
          <label class="block font-medium">Correo electrónico</label>
          <input
            v-model="usuario.email_contacto"
            type="email"
            class="w-full border rounded p-2"
            placeholder="Ej: ejemplo@email.com"
          />
        </div>

        <!-- Dirección -->
        <div>
          <label class="block font-medium">DNI</label>
          <input
            v-model="usuario.dni"
            type="text"
            class="w-full border rounded p-2"
            placeholder="Ej: 45.208.xxx"
          />
        </div>

        <!-- Localidad -->
        <div>
          <label class="block font-medium">Nombre Completo</label>
          <input
            v-model="usuario.nombre_completo"
            type="text"
            class="w-full border rounded p-2"
            placeholder="Ej: Juan Pepito"
          />
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
        
        <!-- Botón Registrar/Modificar usuario -->
        <button
          type="submit"
          class="bg-blue-500 text-white font-bold text-2xl px-4 py-2 text-center rounded-full hover:bg-blue-700 transition-colors"
        >
          {{ esModificacion ? 'Modificar Usuario' : 'Registrar Usuario' }}
        </button>
      </div>
    </form>
  </div>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { reactive } from 'vue'
import axios from 'axios'
import { useAuthToken } from '@/composables/useAuthToken'
import { useAuth } from '@/composables/useAuth'


const router = useRouter()
const route = useRoute()
const { accessToken, isAuthenticated, setToken, clearToken } = useAuthToken()

// Estado para controlar si es modificación o registro
const esModificacion = ref(false)
const showModal = ref(false)
const usuarioId = ref(null)

// REEMPLAZAR la función determinarModo existente con esta versión corregida
const determinarModo = async () => {
  console.log('🔍 Determinando modo de operación...');
  
  // DEBUG mejorado
  console.log('🔐 Estado de autenticación:', {
    accessToken: accessToken.value ? 'SÍ (' + accessToken.value.substring(0, 10) + '...)' : 'NO',
    isAuthenticated: isAuthenticated.value ? 'SÍ' : 'NO'
  });

  // 1. PRIMERO: Verificar ID en query parameters (viene de perfilUsuario)
  const idFromQuery = route.query.id;
  if (idFromQuery) {
    esModificacion.value = true;
    usuarioId.value = idFromQuery;
    console.log('✅ Modo modificación activado por ID de query:', usuarioId.value);
    await cargarUsuario();
    return;
  }

  // 2. SEGUNDO: Verificar ID en params (por si acaso)
  const idFromParams = route.params.id;
  if (idFromParams) {
    esModificacion.value = true;
    usuarioId.value = idFromParams;
    console.log('✅ Modo modificación activado por ID de params:', usuarioId.value);
    await cargarUsuario();
    return;
  }

  // 3. TERCERO: Si hay token, usar usuario autenticado
  if (accessToken.value && isAuthenticated.value) {
    try {
      console.log('🔄 Obteniendo usuario autenticado...');
      
      const response = await axios.get('/api/user', {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Accept': 'application/json'
        }
      });

      const userData = response.data;
      console.log('✅ Usuario autenticado:', userData);

      if (userData && userData.userable_id) {
        esModificacion.value = true;
        usuarioId.value = userData.userable_id;
        console.log('✅ Modo edición activado con ID autenticado:', usuarioId.value);
        await cargarUsuario();
        return;
      }
    } catch (error) {
      console.error('❌ Error al obtener usuario autenticado:', error);
    }
  }

  // 4. SI LLEGAMOS AQUÍ: Es modo registro
  esModificacion.value = false;
  usuarioId.value = null;
  console.log('✅ Modo registro activado');
};

// SOLO UN onMounted - ELIMINA EL SEGUNDO
onMounted(async () => {
  console.log('🔄 Componente montado, determinando modo...');
  
  // DEBUG: Verificar qué hay en localStorage
  console.log('🔍 DEBUG localStorage:', {
    auth_token: localStorage.getItem('auth_token'),
    token_type: localStorage.getItem('token_type'),
    allStorage: { ...localStorage }
  });
  
  console.log('Route params:', route.params);
  console.log('Route query:', route.query);
  
  await determinarModo();
});




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
  },
  ocupacion: '',
  tipoVivienda: '',
  experienciaMascotas: '',
  conviveConNiños: '',
  conviveConMascotas: '',
  descripcion: '',
  dni: '',
  telefono_contacto: '',
  email_contacto: '',
  nombre_completo: ''
})

const fotos = ref(Array.from({ length: 6 }, () => ({
  archivo: null,
  preview: null
})))

const handleFoto = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    fotos.value[index].archivo = file
    fotos.value[index].preview = URL.createObjectURL(file)
  }
}

const inputsFoto = ref([])

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

// Mostrar modal en lugar de enviar directamente
const mostrarModal = () => {
  calcularEdad();
  
  if (!esModificacion.value && usuario.password !== usuario.confirmPassword) {
    alert('Las contraseñas no coinciden');
    return;
  }
  
  showModal.value = true;
}

// Confirmar la acción (registro o modificación)
const confirmarAccion = async () => {
  showModal.value = false;
  
  if (esModificacion.value) {
    await modificarUsuario();
  } else {
    await registrarUsuario();
  }
}

// Cargar datos del usuario para modificación
const cargarUsuario = async () => {
  try {
    if (!usuarioId.value) {
      console.error('❌ No hay ID de usuario para cargar');
      return;
    }

    console.log('🔄 Cargando usuario con ID:', usuarioId.value);
    
    // Verificar que tenemos token
    if (!accessToken.value) {
      console.error('❌ No hay token de autenticación');
      return;
    }

    const response = await axios.get(`/api/usuarios/${usuarioId.value}`, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json'
      }
    });

    console.log('✅ Respuesta del servidor:', response.data);

    if (response.data.success && response.data.usuario) {
      const userData = response.data.usuario;
      
      // Llenar campos básicos
      usuario.nombre = userData.nombre || '';
      usuario.email = userData.email || '';
      usuario.edad = userData.edad || null;
      
      // Calcular fecha de nacimiento desde la edad (aproximado)
      if (userData.edad) {
        const hoy = new Date();
        const anioNacimiento = hoy.getFullYear() - userData.edad;
        usuario.fechaNacimiento.anio = anioNacimiento;
        usuario.fechaNacimiento.dia = usuario.fechaNacimiento.dia || 1;
        usuario.fechaNacimiento.mes = usuario.fechaNacimiento.mes || '1';
      }
      
      // Llenar características
      if (userData.caracteristicas) {
        usuario.ocupacion = userData.caracteristicas.ocupacion || '';
        usuario.tipoVivienda = userData.caracteristicas.tipoVivienda || '';
        usuario.experienciaMascotas = userData.caracteristicas.experiencia || '';
        usuario.conviveConNiños = userData.caracteristicas.convivenciaNiños || '';
        usuario.conviveConMascotas = userData.caracteristicas.convivenciaMascotas || '';
        usuario.descripcion = userData.caracteristicas.descripción || '';
      }
      
      // Llenar contacto
      if (userData.contacto) {
        usuario.dni = userData.contacto.dni || '';
        usuario.telefono_contacto = userData.contacto.telefono || '';
        usuario.email_contacto = userData.contacto.email || '';
        usuario.nombre_completo = userData.contacto.nombre_completo || '';
      }
      
      // Llenar fotos
      if (userData.fotos && userData.fotos.length > 0) {
        fotos.value = fotos.value.map(() => ({ archivo: null, preview: null }));
        
        userData.fotos.forEach((foto, index) => {
          if (index < fotos.value.length && foto.ruta_foto) {
            // USAR url_foto SI ESTÁ DISPONIBLE, SINO CONSTRUIR LA RUTA
            fotos.value[index].preview = foto.url_foto || `/storage/${foto.ruta_foto}`;
            console.log('🖼️ Foto cargada:', fotos.value[index].preview);
          }
        });
      }

      console.log('✅ Usuario cargado correctamente para modificación');
    }

  } catch (error) {
    console.error('❌ Error al cargar usuario:', error);
    
    if (error.response?.status === 401) {
      alert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
      clearToken();
      router.push('/');
    } else if (error.response?.status === 404) {
      console.error('Usuario no encontrado, cambiando a modo registro');
      esModificacion.value = false;
      usuarioId.value = null;
    }
  }
};

const registrarUsuario = async () => {
  try {
    const formData = new FormData();
    
    // Datos básicos
    formData.append('nombre', usuario.nombre);
    formData.append('email', usuario.email);
    formData.append('password', usuario.password);
    if (usuario.edad) formData.append('edad', usuario.edad);
    
    // Características
    if (usuario.tipoVivienda) formData.append('tipoVivienda', usuario.tipoVivienda);
    if (usuario.ocupacion) formData.append('ocupacion', usuario.ocupacion);
    if (usuario.experienciaMascotas) formData.append('experiencia', usuario.experienciaMascotas);
    if (usuario.conviveConNiños) formData.append('convivenciaNiños', usuario.conviveConNiños);
    if (usuario.conviveConMascotas) formData.append('convivenciaMascotas', usuario.conviveConMascotas);
    if (usuario.descripcion) formData.append('descripcion', usuario.descripcion);
    
    // Datos de contacto
    if (usuario.dni) formData.append('dni', usuario.dni);
    if (usuario.telefono_contacto) formData.append('telefono_contacto', usuario.telefono_contacto);
    if (usuario.email_contacto) formData.append('email_contacto', usuario.email_contacto);
    if (usuario.nombre_completo) formData.append('nombre_completo', usuario.nombre_completo);

    // Foto de perfil
    if (fotos.value[0]?.archivo) {
      formData.append('foto_perfil', fotos.value[0].archivo);
    }

    // Obtener CSRF token primero
    await axios.get('/sanctum/csrf-cookie', {
      withCredentials: true
    });

    const response = await axios.post('/api/registrar-usuario', formData, {
      withCredentials: true,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (response.data.success) {
      if (response.data.access_token) {
        setToken(response.data.access_token)
      }
      
      alert('Usuario registrado exitosamente');
      router.push('/explorar/encuentros');
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
}

const modificarUsuario = async () => {
  try {
    console.log('🔄 ===== INICIANDO MODIFICACIÓN DE USUARIO =====');
    
    if (!isAuthenticated.value) {
      alert('Debes estar autenticado para modificar un usuario');
      return;
    }

    const formData = new FormData();
    
    // DEBUG: Mostrar qué datos se van a enviar
    console.log('📤 Datos a enviar:', {
      edad: usuario.edad,
      tipoVivienda: usuario.tipoVivienda,
      ocupacion: usuario.ocupacion,
      experienciaMascotas: usuario.experienciaMascotas,
      conviveConNiños: usuario.conviveConNiños,
      conviveConMascotas: usuario.conviveConMascotas,
      descripcion: usuario.descripcion,
      dni: usuario.dni,
      telefono_contacto: usuario.telefono_contacto,
      email_contacto: usuario.email_contacto,
      nombre_completo: usuario.nombre_completo,
      tieneFoto: !!fotos.value[0]?.archivo
    });
    
    // Datos básicos (solo los que se pueden modificar)
    if (usuario.edad) formData.append('edad', usuario.edad);
    
    // Características
    if (usuario.tipoVivienda) formData.append('tipoVivienda', usuario.tipoVivienda);
    if (usuario.ocupacion) formData.append('ocupacion', usuario.ocupacion);
    if (usuario.experienciaMascotas) formData.append('experiencia', usuario.experienciaMascotas);
    if (usuario.conviveConNiños) formData.append('convivenciaNiños', usuario.conviveConNiños);
    if (usuario.conviveConMascotas) formData.append('convivenciaMascotas', usuario.conviveConMascotas);
    if (usuario.descripcion) formData.append('descripcion', usuario.descripcion);
    
    // Datos de contacto
    if (usuario.dni) formData.append('dni', usuario.dni);
    if (usuario.telefono_contacto) formData.append('telefono_contacto', usuario.telefono_contacto);
    if (usuario.email_contacto) formData.append('email_contacto', usuario.email_contacto);
    if (usuario.nombre_completo) formData.append('nombre_completo', usuario.nombre_completo);

    // Foto de perfil (solo si se cambió)
    if (fotos.value[0]?.archivo) {
      formData.append('foto_perfil', fotos.value[0].archivo);
      console.log('📸 Foto adjuntada');
    }

    // DEBUG: Verificar qué hay en el FormData antes de enviar
    console.log('📋 CONTENIDO DEL FORM DATA:');
    for (let [key, value] of formData.entries()) {
      console.log(`   ${key}:`, value);
    }
    
    // DEBUG: Verificar headers
    console.log('📋 HEADERS A ENVIAR:', {
      'Authorization': `Bearer ${accessToken.value.substring(0, 20)}...`,
      'Content-Type': 'multipart/form-data',
      'X-Requested-With': 'XMLHttpRequest'
    });

    console.log('🔐 Token de autenticación:', accessToken.value ? accessToken.value.substring(0, 20) + '...' : 'NO');
    console.log('👤 ID de usuario:', usuarioId.value);

    // Obtener CSRF token primero
    console.log('🛡️ Obteniendo token CSRF...');
    await axios.get('/sanctum/csrf-cookie', {
      withCredentials: true
    });

    console.log('🚀 Enviando solicitud PUT...');
    const response = await axios.post(`/api/usuarios/${usuarioId.value}`, formData, {
      withCredentials: true,
      headers: {
        'Authorization': `Bearer ${accessToken.value}`,
        'Accept': 'application/json',
        'Content-Type': 'multipart/form-data',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    console.log('✅ Respuesta del servidor:', response.data);

    if (response.data.success) {
      alert('Usuario modificado exitosamente');
      router.push('/explorar/perfil/mascotas');
    } else {
      alert(response.data.message || 'Error al modificar usuario');
    }

    if (response.data.success) {
      console.log('✅ Modificación exitosa, verificando cambios...');
      
      // Hacer una nueva petición para verificar los cambios
      const verificationResponse = await axios.get(`/api/usuarios/${usuarioId.value}`, {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Accept': 'application/json'
        }
      });
      
      console.log('🔍 Datos después de la modificación:', verificationResponse.data);
      
      alert('Usuario modificado exitosamente');
      router.push('/explorar/perfil/mascotas');
    }
    
  } catch (error) {
    console.error('❌ ===== ERROR EN MODIFICACIÓN =====');
    console.error('❌ Error completo:', error);
    
    if (error.response) {
      console.error('❌ Datos del error:', error.response.data);
      console.error('❌ Status:', error.response.status);
      alert(`Error: ${error.response.data.message || 'Error en el servidor'}`);
    } else if (error.request) {
      console.error('❌ No se recibió respuesta:', error.request);
      alert('Error de conexión. Verifica tu red o si el servidor está funcionando.');
    } else {
      console.error('❌ Error de configuración:', error.message);
      alert('Error al enviar los datos. Por favor intenta nuevamente.');
    }
  }
}

// Función temporal para debug
const debugAuth = () => {
  console.log('🐛 DEBUG AUTH STATE:', {
    accessToken: accessToken.value,
    isAuthenticated: isAuthenticated.value,
    localStorage: {
      auth_token: localStorage.getItem('auth_token'),
      token: localStorage.getItem('token')
    },
    usuarioId: usuarioId.value,
    esModificacion: esModificacion.value
  });
}

// SOLO UN onMounted - ELIMINA CUALQUIER OTRO onMounted
onMounted(async () => {
  console.log('🔄 Componente montado...');
  await determinarModo();
  debugAuth();
});
</script>

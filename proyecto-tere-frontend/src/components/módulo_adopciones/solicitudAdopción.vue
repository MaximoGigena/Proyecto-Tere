<!-- solicitudAdopción.vue -->
<template>
  <!-- Overlay pantalla completa -->
  <div v-if="loading" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-3">
    <div class="bg-white rounded-3xl p-12 shadow-2xl">
      <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-lg text-gray-700">Cargando información de la solicitud...</p>
    </div>
  </div>

  <div v-else-if="error" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-3">
    <div class="bg-white rounded-3xl p-12 shadow-2xl max-w-md">
      <div class="text-center">
        <font-awesome-icon :icon="['fas','triangle-exclamation']" class="text-red-500 text-6xl mb-4" />
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Error al cargar la solicitud</h2>
        <p class="text-gray-600 mb-6">{{ error }}</p>
        <button
          @click="cerrarOverlay"
          class="px-6 py-3 rounded-xl bg-gray-900 text-white hover:bg-black transition"
        >
          Cerrar
        </button>
      </div>
    </div>
  </div>

  <div v-else class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-3">
    <!-- Contenedor principal dividido en 2 -->
    <div
      class="w-full max-w-[1400px] h-[92vh] bg-white rounded-3xl shadow-2xl overflow-hidden flex"
    >
      <!-- Columna izquierda: Solicitud -->
      <div class="flex flex-col bg-gray-50 w-1/2 min-w-0">
        <!-- Encabezado + acciones (sticky) -->
        <div class="sticky top-0 z-10 bg-gray-50/90 backdrop-blur px-4 py-3 border-b">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="text-2xl font-bold text-gray-900">
                Solicitud #{{ datosSolicitud.id }}
              </h2>
              <div class="flex flex-wrap items-center gap-2 mt-1 text-sm text-gray-600">
                <span class="font-medium">Mascota:</span>
                <span class="font-semibold text-gray-800">{{ datosOferta?.mascota?.nombre || 'Cargando...' }}</span>
                <span class="text-gray-300">•</span>
                <span>Recibida: {{ fechaFormateada }}</span>
                <span class="text-gray-300">•</span>
                <span
                  :class="[
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold border',
                    estadoClasses
                  ]"
                >
                  {{ estadoTraducido }}
                </span>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="rechazarSolicitud"
                :disabled="procesando"
                class="px-3 py-2 rounded-xl border border-red-200 text-red-700 hover:bg-red-50 active:scale-[.98] transition disabled:opacity-50 disabled:cursor-not-allowed"
                title="Rechazar solicitud"
              >
                <font-awesome-icon :icon="['fas','xmark']" class="mr-2" /> Rechazar
              </button>
              <button
                @click="aprobarSolicitud"
                :disabled="procesando"
                class="px-3 py-2 rounded-xl bg-green-600 text-white hover:bg-green-700 active:scale-[.98] shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed"
                title="Aprobar solicitud"
              >
                <font-awesome-icon :icon="['fas','check']" class="mr-2" /> Aprobar
              </button>
            </div>
          </div>
          <div class="mt-2 flex flex-wrap gap-2">
            <RouterLink
              v-if="datosSolicitud.idUsuarioSolicitante"
              :to="{ name: 'chat-room', params: { id: datosSolicitud.idUsuarioSolicitante }, query: { from: 'adoption-request' } }"
              class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition"
            >
              <font-awesome-icon :icon="['fas','comment-dots']" /> Abrir chat
            </RouterLink>
            <button
              @click="marcarContactado = !marcarContactado"
              :class="[
                'inline-flex items-center gap-2 px-3 py-2 rounded-xl border active:scale-[.98] transition',
                marcarContactado ? 'border-green-200 text-green-700 bg-green-50' : 'border-gray-200 text-gray-700 hover:bg-gray-50'
              ]"
            >
              <font-awesome-icon :icon="['fas', marcarContactado ? 'check-circle' : 'phone']" />
              {{ marcarContactado ? 'Marcado como contactado' : 'Marcar contactado' }}
            </button>
          </div>
        </div>

        <!-- Contenido scrollable de la solicitud -->
        <div
          ref="leftScroll"
          class="flex-1 overflow-y-auto overflow-x-hidden px-4 py-4 invisible-scrollbar"
        >
          <!-- Resumen de la mascota -->
          <div v-if="datosOferta?.mascota" class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
              <img 
                :src="datosOferta.mascota.foto_principal_url || 'https://cdn.pixabay.com/photo/2017/09/25/13/12/dog-2785074_1280.jpg'" 
                :alt="datosOferta.mascota.nombre" 
                class="w-16 h-16 rounded-2xl object-cover"
              />
              <div>
                <div class="text-lg font-semibold text-gray-800">{{ datosOferta.mascota.nombre }}</div>
                <div class="text-sm text-gray-600">
                  {{ datosOferta.mascota.especie || 'Especie no especificada' }} 
                  • {{ datosOferta.mascota.raza || 'Raza no especificada' }} 
                  • {{ datosOferta.mascota.edad_formateada || 'Edad no especificada' }}
                </div>
              </div>
            </div>
            <div v-if="datosOferta.mascota.caracteristicas && Object.keys(datosOferta.mascota.caracteristicas).length" class="mt-3">
              <div class="text-sm text-gray-500 mb-1">Características:</div>
              <div class="flex flex-wrap gap-1">
                <span 
                  v-for="(value, key) in datosOferta.mascota.caracteristicas" 
                  :key="key"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-50 text-blue-700 border border-blue-100"
                >
                  {{ key }}: {{ value }}
                </span>
              </div>
            </div>
          </div>

          <!-- Secciones de la solicitud -->
          <div class="mt-4 space-y-4">
            <!-- Datos principales de la solicitud -->
            <section class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-3">Datos de la solicitud</h3>
              <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-gray-700">
                <div>
                  <dt class="text-gray-500">Estado</dt>
                  <dd class="font-medium">{{ estadoTraducido }}</dd>
                </div>
                <div>
                  <dt class="text-gray-500">Fecha de solicitud</dt>
                  <dd class="font-medium">{{ fechaFormateada }}</dd>
                </div>
                <div>
                  <dt class="text-gray-500">ID de solicitud</dt>
                  <dd class="font-medium">{{ datosSolicitud.idSolicitud || datosSolicitud.id }}</dd>
                </div>
                <div v-if="datosSolicitud.aceptóTerminos !== undefined">
                  <dt class="text-gray-500">Aceptó términos</dt>
                  <dd class="font-medium">{{ datosSolicitud.aceptóTerminos ? 'Sí' : 'No' }}</dd>
                </div>
              </dl>
            </section>

            <!-- Información de la oferta de adopción -->
            <section v-if="datosOferta" class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-3">Información de la oferta</h3>
              <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-gray-700">
                <div>
                  <dt class="text-gray-500">Estado de la oferta</dt>
                  <dd class="font-medium">{{ datosOferta.estado_oferta }}</dd>
                </div>
                <div>
                  <dt class="text-gray-500">Permiso historial médico</dt>
                  <dd class="font-medium">{{ datosOferta.permiso_historial_medico ? 'Sí' : 'No' }}</dd>
                </div>
                <div>
                  <dt class="text-gray-500">Permiso contacto tutor</dt>
                  <dd class="font-medium">{{ datosOferta.permiso_contacto_tutor ? 'Sí' : 'No' }}</dd>
                </div>
                <div>
                  <dt class="text-gray-500">Fecha creación</dt>
                  <dd class="font-medium">{{ formatFecha(datosOferta.created_at) }}</dd>
                </div>
              </dl>
            </section>

            <!-- Información del solicitante -->
            <section v-if="solicitanteInfo" class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-3">Información del solicitante</h3>
              <div class="flex items-start gap-3 mb-3">
                <img 
                  :src="solicitanteInfo.img" 
                  :alt="solicitanteInfo.nombre"
                  class="w-12 h-12 rounded-full object-cover"
                />
                <div>
                  <div class="font-medium text-gray-800">{{ solicitanteInfo.nombre }}</div>
                  <div class="text-sm text-gray-600">ID: {{ datosSolicitud.idUsuarioSolicitante }}</div>
                </div>
              </div>
            </section>

            <!-- Permisos de la oferta -->
            <section v-if="datosOferta" class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-3">Permisos de la oferta</h3>
              <div class="space-y-2">
                <div class="flex items-center gap-2">
                  <font-awesome-icon 
                    :icon="['fas', datosOferta.permiso_historial_medico ? 'check-circle' : 'times-circle']" 
                    :class="datosOferta.permiso_historial_medico ? 'text-green-500' : 'text-red-500'"
                  />
                  <span>Compartir historial médico completo</span>
                </div>
                <div class="flex items-center gap-2">
                  <font-awesome-icon 
                    :icon="['fas', datosOferta.permiso_contacto_tutor ? 'check-circle' : 'times-circle']" 
                    :class="datosOferta.permiso_contacto_tutor ? 'text-green-500' : 'text-red-500'"
                  />
                  <span>Permitir contacto directo con tutor anterior</span>
                </div>
              </div>
            </section>

            <!-- Fotos adicionales de la mascota -->
            <section v-if="datosOferta?.mascota?.fotos && datosOferta.mascota.fotos.length > 0" class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-3">Fotos de la mascota</h3>
              <div class="grid grid-cols-3 gap-2">
                <div
                  v-for="(foto, index) in datosOferta.mascota.fotos"
                  :key="index"
                  class="aspect-square rounded-lg overflow-hidden"
                >
                  <img 
                    :src="foto.url || asset('storage/' + foto.ruta_foto)" 
                    :alt="`Foto ${index + 1} de ${datosOferta.mascota.nombre}`"
                    class="w-full h-full object-cover hover:scale-110 transition-transform duration-300 cursor-pointer"
                    @click="abrirImagen(foto.url || asset('storage/' + foto.ruta_foto))"
                  />
                </div>
              </div>
            </section>

            <!-- Notas -->
            <section class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-2">Notas internas</h3>
              <textarea
                id="notas-internas"
                name="notas"
                v-model="notasInternas"
                rows="4"
                class="w-full rounded-2xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-3 text-sm"
                placeholder="Observaciones del evaluador..."
              ></textarea>
              <div class="mt-2 text-right">
                <button 
                  @click="guardarNotas"
                  :disabled="guardandoNotas"
                  class="px-3 py-2 rounded-xl bg-gray-900 text-white hover:bg-black active:scale-[.98] disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="guardandoNotas">
                    <font-awesome-icon :icon="['fas','spinner']" class="animate-spin mr-2" />
                    Guardando...
                  </span>
                  <span v-else>Guardar notas</span>
                </button>
              </div>
            </section>
          </div>
        </div>
      </div>

      <!-- Columna derecha: Perfil (scroll independiente) -->
      <div class="flex flex-col w-1/2 min-w-0 border-l border-gray-200">
        <!-- Contenedor scrollable del perfil -->
        <div class="flex-1 overflow-y-auto overflow-x-hidden invisible-scrollbar">
          <PerfilUsuarioOverlay 
            v-if="solicitanteInfo" 
            :perfil="perfilUsuario" 
            :solicitud-id="datosSolicitud.idSolicitud || datosSolicitud.id"
            class="w-full" 
          />
          <div v-else class="h-full flex items-center justify-center">
            <div class="text-center p-8">
              <font-awesome-icon :icon="['fas','user-slash']" class="text-gray-400 text-6xl mb-4" />
              <p class="text-gray-500">No se pudo cargar la información del solicitante</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import PerfilUsuarioOverlay from '@/components/módulo_usuario/contenidoUsuario.vue'

const route = useRoute()
const router = useRouter()

const leftScroll = ref(null)
const mostrarReporte = ref(false)
const marcarContactado = ref(false)

// Estados reactivos
const loading = ref(true)
const error = ref(null)
const procesando = ref(false)
const guardandoNotas = ref(false)
const notasInternas = ref('')

// Datos de la solicitud y oferta
const datosSolicitud = ref({})
const datosOferta = ref(null)
const solicitanteInfo = ref(null)

// Computed properties
const fechaFormateada = computed(() => {
  if (!datosSolicitud.value.fechaSolicitud) return 'Fecha no disponible'
  
  try {
    const date = new Date(datosSolicitud.value.fechaSolicitud)
    return date.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (e) {
    return datosSolicitud.value.fechaSolicitud
  }
})

const estadoTraducido = computed(() => {
  const estado = datosSolicitud.value.estadoSolicitud
  const estados = {
    'pendiente': 'Pendiente',
    'aprobada': 'Aprobada',
    'rechazada': 'Rechazada',
    'cancelada': 'Cancelada',
    'expirada': 'Expirada'
  }
  return estados[estado] || estado || 'Desconocido'
})

const estadoClasses = computed(() => {
  const estado = datosSolicitud.value.estadoSolicitud
  if (estado === 'aprobada') return 'bg-green-50 text-green-700 border-green-200'
  if (estado === 'rechazada') return 'bg-red-50 text-red-700 border-red-200'
  if (estado === 'cancelada') return 'bg-gray-50 text-gray-700 border-gray-200'
  if (estado === 'expirada') return 'bg-orange-50 text-orange-700 border-orange-200'
  return 'bg-amber-50 text-amber-700 border-amber-200' // Pendiente
})

const perfilUsuario = computed(() => {
  return {
    id: datosSolicitud.value.idUsuarioSolicitante,
    nombre: solicitanteInfo.value?.nombre || 'Usuario',
    img: solicitanteInfo.value?.img || 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png',
    edad: 'Edad no disponible',
    descripcion: solicitanteInfo.value?.descripcion || 'Solicitante de adopción',
    experiencia: 'No especificada',
    tipoCuidador: 'No especificado',
    mascotas: 'No especificado',
    ubicacion: 'Ubicación no disponible',
    fotos: []
  }
})

// Funciones
function formatFecha(fecha) {
  if (!fecha) return 'No disponible'
  try {
    const date = new Date(fecha)
    return date.toLocaleDateString('es-ES')
  } catch (e) {
    return fecha
  }
}

function asset(path) {
  return import.meta.env.VITE_APP_URL ? import.meta.env.VITE_APP_URL + path : path
}

async function cargarDatosSolicitud() {
  try {
    loading.value = true
    error.value = null
    
    const solicitudId = route.query.solicitud_id || route.params.solicitudId
    
    if (!solicitudId) {
      console.error('Error: No se proporcionó ID de solicitud')
      throw new Error('No se proporcionó ID de solicitud')
    }

    console.log('=== INICIO cargarDatosSolicitud ===')
    console.log('Solicitud ID:', solicitudId)
    console.log('Token:', localStorage.getItem('token') ? 'Presente' : 'Ausente')
    console.log('URL completa:', `/api/solicitudes/${solicitudId}`)

    // 1. Cargar información de la solicitud específica
    const responseSolicitud = await axios.get(`/api/solicitudes/${solicitudId}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })

    console.log('Respuesta de API:', responseSolicitud)
    console.log('Datos respuesta:', responseSolicitud.data)

    if (responseSolicitud.data.success) {
      datosSolicitud.value = responseSolicitud.data.data.solicitud
      console.log('Datos de solicitud cargados:', datosSolicitud.value)
      
      // Cargar información del solicitante desde la solicitud
      if (responseSolicitud.data.data.solicitante) {
        solicitanteInfo.value = {
          id: responseSolicitud.data.data.solicitante.id,
          nombre: responseSolicitud.data.data.solicitante.nombre || responseSolicitud.data.data.solicitante.name,
          img: responseSolicitud.data.data.solicitante.foto_perfil_url || 
               'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png',
          descripcion: responseSolicitud.data.data.solicitante.descripcion || ''
        }
        console.log('Solicitante info:', solicitanteInfo.value)
      }
      
      // 2. Cargar la oferta de adopción relacionada
      const mascotaId = datosSolicitud.value.idMascota
      if (mascotaId) {
        console.log('Buscando oferta para mascota ID:', mascotaId)
        await cargarOfertaPorMascota(mascotaId)
      } else {
        console.warn('Solicitud no tiene ID de mascota asociado')
      }
      
    } else {
      console.error('Error en respuesta:', responseSolicitud.data)
      throw new Error(responseSolicitud.data.message || 'Error al cargar solicitud')
    }
    
    console.log('=== FIN cargarDatosSolicitud ===')
    
  } catch (err) {
    console.error('=== ERROR cargando datos de solicitud ===')
    console.error('Error completo:', err)
    console.error('Respuesta error:', err.response)
    console.error('Mensaje:', err.message)
    
    error.value = err.response?.data?.message || err.message || 'Error al cargar la solicitud'
    
    // Datos de ejemplo para desarrollo
    if (import.meta.env.DEV || window.location.hostname === 'localhost') {
      console.log('Usando datos de ejemplo para desarrollo')
      datosSolicitud.value = {
        idSolicitud: route.query.solicitud_id || '123',
        idUsuarioSolicitante: route.params.userId || '101',
        idMascota: '1',
        estadoSolicitud: 'pendiente',
        aceptóTerminos: true,
        fechaSolicitud: new Date().toISOString()
      }
      
      solicitanteInfo.value = {
        nombre: route.query.nombre || 'Usuario Ejemplo',
        img: route.query.img || 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png'
      }
      
      // Datos de ejemplo para la oferta
      datosOferta.value = {
        id_oferta: '456',
        estado_oferta: 'publicada',
        permiso_historial_medico: true,
        permiso_contacto_tutor: true,
        created_at: new Date().toISOString(),
        mascota: {
          id: '1',
          nombre: 'Firulais',
          especie: 'Perro',
          raza: 'Mestizo',
          edad_formateada: '2 años',
          foto_principal_url: 'https://cdn.pixabay.com/photo/2017/09/25/13/12/dog-2785074_1280.jpg',
          caracteristicas: {
            tamaño: 'Mediano',
            pelaje: 'Corto',
            color: 'Marrón'
          },
          fotos: [
            {
              url: 'https://cdn.pixabay.com/photo/2017/09/25/13/12/dog-2785074_1280.jpg',
              ruta_foto: '',
              es_principal: true
            }
          ]
        }
      }
      
      error.value = null
    }
  } finally {
    loading.value = false
  }
}

async function cargarOfertaPorMascota(mascotaId) {
  try {
    console.log('Buscando oferta para mascota ID:', mascotaId)
    
    // Buscar ofertas activas para esta mascota
    const response = await axios.get(`/api/adopciones/ofertas/mascota/${mascotaId}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.data.success && response.data.data) {
      datosOferta.value = response.data.data
      console.log('Oferta cargada:', datosOferta.value)
    } else {
      // Si no hay una ruta específica, intentar obtener de la lista general
      await cargarTodasOfertasYBuscarmascota(mascotaId)
    }
    
  } catch (err) {
    console.error('Error cargando oferta:', err)
    // No establecer error aquí, ya que la solicitud podría cargarse sin oferta
  }
}

async function cargarTodasOfertasYBuscarmascota(mascotaId) {
  try {
    // Cargar ofertas del usuario y buscar la que corresponda a la mascota
    const response = await axios.get('/api/adopciones/mis-mascotas/en-adopcion', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.data.success && response.data.data) {
      const ofertaEncontrada = response.data.data.find(
        oferta => oferta.id === mascotaId || oferta.mascota_id === mascotaId
      )
      
      if (ofertaEncontrada) {
        // Cargar detalles completos de la oferta
        const detalleResponse = await axios.get(`/api/adopciones/ofertas/${ofertaEncontrada.oferta_id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        })
        
        if (detalleResponse.data.success) {
          datosOferta.value = detalleResponse.data.data.oferta
        }
      }
    }
  } catch (err) {
    console.error('Error buscando oferta en lista:', err)
  }
}

async function aprobarSolicitud() {
  if (!confirm('¿Estás seguro de aprobar esta solicitud de adopción? La mascota será transferida al adoptante inmediatamente.')) return
  
  try {
    procesando.value = true
    const solicitudId = datosSolicitud.value.idSolicitud || datosSolicitud.value.id
    
    const response = await axios.put(`/api/solicitudes/${solicitudId}/aprobar`, {}, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.data.success) {
      datosSolicitud.value.estadoSolicitud = 'aprobada'
      
      // Mostrar mensaje de transferencia exitosa
      alert('✅ Solicitud aprobada y mascota transferida exitosamente\n' +
            'La mascota ahora pertenece al adoptante.')
      
      // Recargar datos para mostrar nueva información
      await cargarDatosSolicitud()
    } else {
      throw new Error(response.data.message || 'Error al aprobar solicitud')
    }
  } catch (err) {
    console.error('Error aprobando solicitud:', err)
    alert(err.response?.data?.message || err.message || 'Error al aprobar la solicitud')
  } finally {
    procesando.value = false
  }
}

async function rechazarSolicitud() {
  if (!confirm('¿Estás seguro de rechazar esta solicitud de adopción?')) return
  
  try {
    procesando.value = true
    const solicitudId = datosSolicitud.value.idSolicitud || datosSolicitud.value.id
    
    const response = await axios.put(`/api/solicitudes/${solicitudId}/rechazar`, {}, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.data.success) {
      datosSolicitud.value.estadoSolicitud = 'rechazada'
      alert('Solicitud rechazada exitosamente')
      
      // Opcional: Cerrar el overlay o recargar datos
      cerrarOverlay()
    } else {
      throw new Error(response.data.message || 'Error al rechazar solicitud')
    }
  } catch (err) {
    console.error('Error rechazando solicitud:', err)
    alert(err.response?.data?.message || err.message || 'Error al rechazar la solicitud')
  } finally {
    procesando.value = false
  }
}

async function guardarNotas() {
  try {
    guardandoNotas.value = true
    const solicitudId = datosSolicitud.value.idSolicitud || datosSolicitud.value.id
    
    const response = await axios.put(`/api/solicitudes/${solicitudId}/notas`, {
      notas: notasInternas.value
    }, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.data.success) {
      alert('Notas guardadas exitosamente')
    } else {
      throw new Error(response.data.message || 'Error al guardar notas')
    }
  } catch (err) {
    console.error('Error guardando notas:', err)
    alert(err.response?.data?.message || err.message || 'Error al guardar notas')
  } finally {
    guardandoNotas.value = false
  }
}

function abrirImagen(url) {
  window.open(url, '_blank')
}

function cerrarOverlay() {
  router.back()
}

// Manejo del scroll del body al abrir/cerrar overlay
onMounted(() => {
  const prev = document.body.style.overflow
  document.body.dataset.prevOverflow = prev
  document.body.style.overflow = 'hidden'
  
  console.log('SolicitudAdopcion overlay montado - params:', route.params, 'query:', route.query)
  
  // Cargar datos cuando se monta el componente
  cargarDatosSolicitud()
})

onUnmounted(() => {
  document.body.style.overflow = document.body.dataset.prevOverflow || ''
  delete document.body.dataset.prevOverflow
})

// Observar cambios en los parámetros de ruta
watch(() => route.query.solicitud_id, () => {
  if (route.query.solicitud_id) {
    cargarDatosSolicitud()
  }
})

watch(() => route.params.userId, () => {
  if (route.params.userId) {
    cargarDatosSolicitud()
  }
})
</script>

<style scoped>
/* Scrollbar casi invisible (aplicar con la clase utility invisible-scrollbar) */
.invisible-scrollbar {
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE/Edge */
}
.invisible-scrollbar::-webkit-scrollbar {
  display: none; /* Chrome/Safari */
}

.debug-border {
  border: 2px solid red;
}

.debug-bg {
  background-color: rgba(255, 0, 0, 0.1);
}

/* Estilos para la animación de carga */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
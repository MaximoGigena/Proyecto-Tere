<!-- AdvertenciaAdopcion.vue - CON TAMAÑO FIJO -->
<template>
  <div class="flex flex-col h-[70vh] max-h-[70vh] w-full bg-white rounded-2xl overflow-hidden">
    <!-- Header del modal -->
    <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between z-10 shrink-0">
      <!-- Título a la izquierda -->
      <h1 class="text-lg font-semibold text-gray-800">Adopción responsable</h1>
      
      <!-- Contenedor vacío para balancear -->
      <div class="w-6"></div>
      
      <!-- Botón de cerrar a la derecha -->
      <button @click="cerrar" class="text-gray-500 hover:text-gray-800 text-4xl font-bold">
        ×
      </button>
    </div>

    <!-- Contenido scrollable -->
    <div class="flex-1 overflow-y-auto">
      <!-- ========================================================= -->
      <!--                 PANTALLA 1: ADVERTENCIAS                  -->
      <!-- ========================================================= -->
      <div v-if="pantalla === 'advertencias'" class="p-4">
        <header class="mb-4">
          <p class="text-sm text-gray-600">
            Confirme cada punto obligatorio para continuar.
          </p>
        </header>

        <!-- Lista de advertencias -->
        <div class="max-h-[40vh] overflow-y-auto pr-2">
          <ul class="space-y-3">
            <li v-for="(item) in items" :key="item.id" class="flex items-start gap-3">
              <input type="checkbox" :id="item.id" v-model="item.checked"
                class="mt-0.5 h-5 w-5 rounded text-emerald-600 focus:ring-emerald-500 flex-shrink-0" />
              <div class="min-w-0 flex-1">
                <label :for="item.id" class="font-medium text-gray-800 text-sm block">
                  {{ item.title }}
                </label>
                <p class="text-xs text-gray-600 mt-1">{{ item.description }}</p>
              </div>
            </li>
          </ul>
        </div>

        <div class="pt-4 border-t mt-4">
          <div class="text-sm text-gray-600 mb-3">
            <p><span class="font-semibold">Todas</span> las casillas deben estar marcadas.</p>
            <p v-if="!allChecked" class="text-red-600 text-xs mt-1">
              Por favor confirme todos los puntos.
            </p>
          </div>

          <div class="flex gap-3">
            <button @click="cerrar"
              class="flex-1 px-4 py-2 text-sm rounded-lg border border-gray-300 bg-white hover:bg-gray-50 transition-colors">
              Cancelar
            </button>

            <button
              @click="pantalla = 'confirmar'"
              :disabled="!allChecked"
              :class="[
                'flex-1 px-4 py-2 text-sm rounded-lg text-white font-medium transition-colors',
                allChecked ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-emerald-300 cursor-not-allowed'
              ]">
              Continuar
            </button>
          </div>
        </div>
      </div>

      <!-- ========================================================= -->
      <!--        PANTALLA 2: CONFIRMAR Y ENVIAR SOLICITUD           -->
      <!-- ========================================================= -->
      <div v-if="pantalla === 'confirmar'" class="p-4">
        <!-- INFO MASCOTA -->
        <div v-if="mascota" class="bg-blue-50 p-3 rounded-lg mb-4">
          <div class="flex items-center gap-3">
            <img :src="mascota.fotoPrincipal || burro" 
                 class="w-14 h-14 rounded-full object-cover border-2 border-blue-200">
            <div>
              <h3 class="font-bold text-base text-gray-800">{{ mascota.nombre }}</h3>
              <p class="text-sm text-gray-600">{{ mascota.edad_formateada || "Edad no disponible" }}</p>
              <p class="text-xs text-gray-500">{{ mascota.sexo }} • {{ mascota.caracteristicas?.tamano }}</p>
            </div>
          </div>
        </div>

        <!-- Mensaje -->
        <div class="text-center py-3">
          <font-awesome-icon :icon="['fas', 'heart']" class="text-red-500 text-3xl mb-2" />
          <h3 class="text-lg font-semibold text-gray-800 mb-1">
            ¿Confirmar solicitud de adopción?
          </h3>
          <p class="text-sm text-gray-600">
            Se notificará al protector/a para revisar tu solicitud.
          </p>
        </div>

        <!-- ACEPTAR TÉRMINOS -->
        <div class="border-t border-b py-3 mb-4">
          <div class="flex items-start gap-3">
            <input type="checkbox" id="finales" v-model="aceptoFinal"
              class="mt-0.5 h-5 w-5 rounded text-blue-600 focus:ring-blue-500" />
            <label for="finales" class="text-sm">
              <span class="font-medium">Confirmo que acepto los términos finales</span>
              <p class="text-gray-600 mt-1">
                La solicitud puede ser rechazada según criterios de adopción responsable.
              </p>
            </label>
          </div>
        </div>

        <!-- ESTADOS -->
        <div v-if="estado === 'cargando'" class="text-center py-3">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-2 text-sm text-gray-600">Enviando solicitud...</p>
        </div>

        <div v-if="estado === 'exito'" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
          <h4 class="font-bold text-green-800 text-sm">¡Solicitud enviada!</h4>
          <p class="text-green-700 text-sm">
            Serás notificado cuando el protector/a la revise.
          </p>
          <div class="mt-3 flex justify-center">
            <button @click="verMisSolicitudes"
              class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">
              Ver mis solicitudes
            </button>
          </div>
        </div>

        <div v-if="estado === 'error'" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
          <h4 class="font-bold text-red-800 text-sm">Error al enviar solicitud</h4>
          <p class="text-red-700 text-sm">{{ mensajeError }}</p>
        </div>

        <!-- BOTONES -->
        <div v-if="estado === 'inicial'" class="flex gap-3">
          <button @click="pantalla = 'advertencias'"
            class="flex-1 px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            Volver
          </button>

          <button
            @click="enviarSolicitud"
            :disabled="!aceptoFinal"
            :class="[
              'flex-1 px-4 py-2 text-sm rounded-lg font-medium',
              aceptoFinal ? 'bg-blue-600 text-white hover:bg-blue-700'
                          : 'bg-blue-300 text-white cursor-not-allowed'
            ]">
            Enviar Solicitud
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, defineExpose, defineProps, defineEmits } from "vue"
import { useRouter } from "vue-router"
import axios from "axios"
import { useAuth } from "@/composables/useAuth"
import burro from "@/assets/burro.png"

// No usar props, vamos a pasar los parámetros directamente en la función open
const emit = defineEmits(["close", "success", "error"])

const visible = ref(false)
const pantalla = ref("advertencias")

// Datos de la mascota cargados dinámicamente
const mascota = ref(null)
const cargandoMascota = ref(false)

// Variables para almacenar IDs
const ofertaId = ref(null)
const mascotaId = ref(null)

// ---------- ADVERTENCIAS ----------
const items = reactive([
  { id: "cuidado", title: "Cuidaré a la mascota de por vida.", description: "Requiere atención diaria.", checked: false },
  { id: "vacunas", title: "Acepto vacunas y esterilización.", description: "Prevención sanitaria obligatoria.", checked: false },
  { id: "veterinario", title: "Realizaré controles veterinarios.", description: "Seguiré indicaciones profesionales.", checked: false },
  { id: "legal", title: "Cumpliré normativas legales.", description: "Registros, patente y convivencia.", checked: false },
  { id: "recursos", title: "Dispongo de recursos y tiempo.", description: "Garantizo bienestar y espacio.", checked: false },
  { id: "fotos", title: "Autorizo uso de fotos.", description: "Para seguimiento o difusión.", checked: false },
  { id: "no-regalo", title: "No la regalaré ni abandonaré.", description: "Evitar prácticas irresponsables.", checked: false }
])

const allChecked = computed(() => items.every(i => i.checked))

// ---------- CONFIRMAR ----------
const aceptoFinal = ref(false)
const estado = ref("inicial")
const mensajeError = ref("")
const router = useRouter()
const { accessToken } = useAuth()

async function cargarDatosMascota() {
  cargandoMascota.value = true
  mascota.value = null
  
  try {
    let endpoint = ''
    let config = {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`
      }
    }
    
    // Prioridad: si tenemos ofertaId, cargar desde la oferta
    if (ofertaId.value) {
      endpoint = `/api/adopciones/ofertas/${ofertaId.value}`
      console.log('Cargando desde oferta:', endpoint)
      
      const response = await axios.get(endpoint, config)
      
      if (response.data.success) {
        // Extraer la mascota de la estructura de respuesta
        if (response.data.data?.mascota) {
          mascota.value = response.data.data.mascota
          console.log('Mascota cargada desde oferta:', mascota.value)
        }
        // Si no está en data.mascota, buscar en otras ubicaciones
        else if (response.data.mascota) {
          mascota.value = response.data.mascota
        } else if (response.data.data) {
          // Asumir que el data es la mascota directamente
          mascota.value = response.data.data
        }
      }
    }
    // Si no tenemos ofertaId pero sí mascotaId, cargar directamente
    else if (mascotaId.value) {
      endpoint = `/api/mascotas/${mascotaId.value}`
      console.log('Cargando mascota directamente:', endpoint)
      
      const response = await axios.get(endpoint, config)
      
      if (response.data.success) {
        mascota.value = response.data.data
        console.log('Mascota cargada directamente:', mascota.value)
      }
    }
    
    if (!mascota.value) {
      console.warn('No se pudo cargar la mascota')
      mensajeError.value = 'No se pudo cargar la información de la mascota'
    }
    
  } catch (error) {
    console.error('Error cargando datos de mascota:', error)
    mensajeError.value = 'Error al cargar información de la mascota: ' + error.message
  } finally {
    cargandoMascota.value = false
  }
}

function cerrar() {
  // Emitir el evento de cierre para que el padre maneje la animación
  emit("close")
  
  // Solo resetear el estado interno
  pantalla.value = "advertencias"
  items.forEach(item => item.checked = false)
  aceptoFinal.value = false
  estado.value = "inicial"
  mascota.value = null
  ofertaId.value = null
  mascotaId.value = null
  mensajeError.value = ""
}

async function enviarSolicitud() {
  if (!aceptoFinal.value) {
    mensajeError.value = "Debe aceptar los términos finales"
    return
  }

  estado.value = "cargando"
  mensajeError.value = ""

  try {
    // Determinar el idMascota correcto
    let idMascotaParaEnviar = null
    
    // Opción 1: Usar el ID de la mascota cargada
    if (mascota.value?.id) {
      idMascotaParaEnviar = mascota.value.id
    }
    // Opción 2: Usar el mascotaId que recibimos
    else if (mascotaId.value) {
      idMascotaParaEnviar = mascotaId.value
    }
    // Opción 3: Si tenemos ofertaId, obtener la mascota desde la oferta
    else if (ofertaId.value) {
      const response = await axios.get(`/api/adopciones/ofertas/${ofertaId.value}`, {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`
        }
      })
      
      if (response.data.success) {
        // Intentar extraer el ID de la mascota de varias formas
        if (response.data.data?.mascota?.id) {
          idMascotaParaEnviar = response.data.data.mascota.id
        } else if (response.data.data?.id) {
          idMascotaParaEnviar = response.data.data.id
        } else if (response.data.mascota?.id) {
          idMascotaParaEnviar = response.data.mascota.id
        }
      }
    }
    
    console.log('ID de mascota para enviar la solicitud:', idMascotaParaEnviar)
    console.log('Tipo de idMascota:', typeof idMascotaParaEnviar)
    
    if (!idMascotaParaEnviar) {
      throw new Error('No se pudo identificar la mascota para la solicitud')
    }

    // Asegurarse de que idMascota sea un número
    idMascotaParaEnviar = parseInt(idMascotaParaEnviar)
    
    // Preparar datos para enviar
    const datosEnvio = { 
      idMascota: idMascotaParaEnviar, 
      aceptóTerminos: true 
    }
    
    console.log('Datos a enviar:', datosEnvio)
    console.log('URL a enviar:', '/api/adopciones/solicitudes-adopcion')

    const response = await axios.post(
      "/api/adopciones/solicitudes-adopcion",
      datosEnvio,
      { 
        headers: { 
          'Authorization': `Bearer ${accessToken.value}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        } 
      }
    )

    console.log('Respuesta del servidor:', response.data)
    
    if (response.data.success) {
      estado.value = "exito"
      emit("success", response.data.data)
    } else {
      throw new Error(response.data.message || 'Error desconocido del servidor')
    }
  } catch (err) {
    estado.value = "error"
    console.error('Error completo al enviar solicitud:', err)
    console.error('Respuesta de error completa:', err.response?.data)
    
    // Mostrar errores específicos de validación
    if (err.response?.data?.errors) {
      console.error('Errores de validación:', err.response.data.errors)
      mensajeError.value = Object.entries(err.response.data.errors)
        .map(([campo, errores]) => `${campo}: ${errores.join(', ')}`)
        .join('; ')
    } else {
      mensajeError.value =
        err.response?.data?.message ||
        err.response?.data?.error ||
        err.message ||
        "Error al enviar la solicitud"
    }
    
    emit("error", err)
  }
}

function verMisSolicitudes() {
  cerrar()
  router.push("/perfil/solicitudes")
}

async function open(ofertaIdParam = null, mascotaIdParam = null) { 
  console.log('Modal abierto con parámetros:', { ofertaIdParam, mascotaIdParam })
  console.log('=== AdvertenciaAdopcion.open() llamado ===')
  console.log('Parámetros recibidos:', { ofertaIdParam, mascotaIdParam })
  console.log('Componente visible:', visible.value)
  
  // Guardar los IDs
  ofertaId.value = ofertaIdParam
  mascotaId.value = mascotaIdParam
  
  // Resetear estado
  pantalla.value = "advertencias"
  items.forEach(item => item.checked = false)
  aceptoFinal.value = false
  estado.value = "inicial"
  mascota.value = null
  
  // Mostrar el modal
  visible.value = true

  console.log('Modal visible establecido a:', visible.value)
  
  // Cargar datos de mascota
  await cargarDatosMascota()

  console.log('Datos de mascota cargados:', mascota.value)
}

defineExpose({ open, cerrar })
</script>

<style scoped>
/* Scrollbar personalizado */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
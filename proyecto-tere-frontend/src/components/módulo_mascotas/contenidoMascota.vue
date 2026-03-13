<!-- contenidoMascota.vue -->
<template> 
   <div class="bg-white backdrop-blur-md border border-white rounded-2xl 
         overflow-y-auto max-h-[80vh] w-full shadow-2xl 
         transition-all duration-300 relative mx-0"
    >
       <div
        ref="scrollContainer"
        class="flex-1 overflow-y-auto overflow-x-overlay 
        [scrollbar-width:none] [-ms-overflow-style:none] 
        [&::-webkit-scrollbar]:hidden ml-4"
        >
            <!-- Imagen principal -->
            <div class="relative w-full rounded-4xl overflow-hidden" :class="{'min-h-[60vh]': galleryImages.length <= 1, 'min-h-[76vh]': galleryImages.length > 1}">
                <div v-if="galleryImages[0]" class="relative w-full rounded-4xl overflow-hidden">
                  <img
                    :src="galleryImages[0]"
                    alt="Foto secundaria 1"
                    class="w-full h-full object-cover rounded-4xl bg-gray-100"
                    :class="{'max-h-[60vh]': galleryImages.length <= 1, 'max-h-[80vh]': galleryImages.length > 1}"
                    @click="openGallery(0)"
                    @error="onImgError"
                  />
                </div>
                        
              <!-- Info mascota -->
              <div class="absolute top-5 left-4 flex flex-col gap-2">
                <!-- Etiqueta Nombre -->
                <div class="bg-white px-3 py-1 rounded-md shadow text-sm font-semibold w-fit">
                  <span>Nombre: {{ mascotaComputed.nombre }}</span>
                </div>
                
                <!-- Etiqueta Sexo con iconos y colores -->
                <div 
                  class="px-3 py-1 rounded-md shadow text-sm font-semibold w-fit flex items-center gap-2"
                  :class="{
                    'bg-blue-100 text-blue-800 border border-blue-300': mascotaComputed.sexo?.toLowerCase() === 'macho',
                    'bg-pink-100 text-pink-800 border border-pink-300': mascotaComputed.sexo?.toLowerCase() === 'hembra',
                    'bg-white text-gray-800 border border-gray-300': !['macho', 'hembra'].includes(mascotaComputed.sexo?.toLowerCase())
                  }"
                >
                  <font-awesome-icon 
                    v-if="mascotaComputed.sexo?.toLowerCase() === 'macho'"
                    :icon="['fas', 'mars']" 
                    class="text-blue-600"
                  />
                  <font-awesome-icon 
                    v-else-if="mascotaComputed.sexo?.toLowerCase() === 'hembra'"
                    :icon="['fas', 'venus']" 
                    class="text-pink-600"
                  />
                  <span>Sexo: {{ mascotaComputed.sexo }}</span>
                </div>

                <!-- Etiqueta Edad -->
                <div class="bg-blue-500 text-white text-xs px-2 py-1 rounded-md w-fit">
                  Edad: {{ edadDisplay }}
                </div>

                <!-- Etiqueta Castrado -->
                <div 
                  v-if="mascotaComputed.castrado !== null"
                  class="bg-green-500 text-white text-xs px-2 py-1 rounded-md w-fit"
                  :class="{'bg-green-500': mascotaComputed.castrado, 'bg-yellow-500': !mascotaComputed.castrado}"
                >
                  {{ castradoLabel }}
                </div>
              </div>

                  <button  
                      v-if="$route.fullPath.includes('/perfil/mascota/')"
                      @click="handleClose"
                      class="absolute right-3 top-3 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                      <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
                    </button>

                    <button  
                      v-if="$route.path.startsWith('/explorar/cerca/') && $route.params.id"
                      @click="handleClose"
                      class="absolute right-3 top-3 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                      <font-awesome-icon :icon="['fas', 'xmark']" class="text-xl"/>
                    </button>

                    <button
                    v-if="$route.path.startsWith('/explorar/cerca/') && $route.params.id"
                    @click="mostrar = true"
                    class="absolute top-15 right-3 z-30 text-red-700 bg-white bg-opacity-80 rounded-full px-3 pt-0 py-1 text-4xl hover:bg-red-100 hover:text-red-800 hover:shadow-lg hover:scale-110 transition w-10 h-10 font-bold"
                    >
                    !
                    </button>

                <button
                v-if="$route.path.startsWith('/explorar/encuentros') "
                @click="mostrar = true"
                class="absolute top-3 right-3 z-30 text-red-700 bg-white bg-opacity-80 rounded-full px-3 pt-0 py-1 text-4xl hover:bg-red-100 hover:text-red-800 hover:shadow-lg hover:scale-110 transition w-10 h-10 font-bold"
                >
                !
                </button>

                <!-- Contenedor de reporte -->
                <PasoAlgo 
                  v-if="mostrar" 
                  @close="mostrar = false"
                  :mascotaId="mascotaComputed?.id"
                  :ofertaId="ofertaActual?.id_oferta"
                />
            </div>

            <!-- Descripción -->
            <div class="px-4 pt-4 pb-6 bg-white space-y-4">
              <div class="space-y-2">
                <h2 class="text-4xl font-bold text-gray-800">Descripción</h2>
                <p class="text-lg font-semibold text-gray-800">
                  {{ mascotaComputed.caracteristicas?.descripcion }}
                </p>
              </div>
            </div>
            
           <!-- Imagen secundaria 2 -->
            <div v-if="galleryImages[1]" class="relative w-full rounded-4xl overflow-hidden">
              <img
                :src="galleryImages[1]"
                alt="Foto secundaria 2"
                class="w-full h-full object-cover rounded-4xl bg-gray-100"
                @click="openGallery(1)"
                @error="onImgError"
              />
            </div>

            <!-- Componente de Características -->
            <CaracteristicasMascota :mascota="mascotaComputed" />
                
                <!-- Imagen secundaria 3 -->
                <div v-if="galleryImages[2]" class="relative w-full rounded-4xl overflow-hidden mt-4">
                  <img
                    :src="galleryImages[2]"
                    alt="Foto secundaria 2"
                    class="w-full h-full object-cover rounded-4xl bg-gray-100"
                    @click="openGallery(2)"
                    @error="onImgError"
                  />
                </div>
            
                <!-- Historial Mascota -->
                <div class="flex justify-center mt-4">
                    <button class="bg-purple-300 hover:bg-purple-600 text-white text-2xl font-bold py-4 px-8 rounded-md transition-all duration-300" @click="goToHistorial">
                        Historiales
                    </button>
                </div>

                  <!-- Collage final (últimas fotos si existen más de 3) -->
                  <div
                    v-if="galleryImages.length > 3"
                    class="grid grid-cols-2 sm:grid-cols-3 gap-3 rounded-2xl overflow-hidden mt-10 mb-20"
                  >
                    <div
                      v-for="(img, i) in galleryImages.slice(3)"
                      :key="i"
                      class="relative group cursor-pointer"
                    >
                      <img
                        :src="img"
                        alt="Collage mascota"
                        class="w-full h-48 object-cover rounded-2xl transform group-hover:scale-105 transition duration-300"
                        @click="openGallery(i + 3)"
                        @error="onImgError"
                      />
                    </div>
                  </div> 
                <!-- Reemplazar esta sección -->
                <div
                  class="px-4 pt-4 pb-6 bg-white space-y-4"
                >
                  <div class="space-y-2">
                    <h2 class="text-4xl font-bold text-gray-800">Ubicación Actual</h2>
                    
                    <!-- Mostrar cargando -->
                    <div v-if="cargandoUbicacion" class="text-gray-500">
                      <span class="animate-pulse">Cargando ubicación...</span>
                    </div>
                    
                    <!-- Mostrar ubicación -->
                    <p v-else class="text-lg font-semibold text-gray-800">
                      {{ ubicacionDisplay }}
                    </p>
                  </div>
                </div>
              
              <!-- Contenedor de botones -->
                <div 
                  v-if="showButtonsContainer"
                  ref="botonesAnimados"
                  :class="{'opacity-0 translate-y-10': !mostrarBotones, 'opacity-100 translate-y-0': mostrarBotones}"
                  class="flex justify-center gap-14 z-20 transition-all duration-700 ease-out pb-20"
                >

                  <button 
                    v-if="$route.path.startsWith('/explorar/cerca/')"
                    class="bg-white border border-black w-16 h-16 rounded-full shadow-lg flex items-center justify-center transition duration-300">
                    <font-awesome-icon :icon="['fas', 'comment']" class="text-black text-4xl hover:text-purple-400" />
                  </button>
                  <button 
                    v-if="$route.path.startsWith('/explorar/cerca/') && !tieneSolicitudActiva"
                    class="bg-white border border-black w-16 h-16 rounded-full shadow-lg flex items-center justify-center transition duration-300"
                    @click="abrirAdvertencia"
                    :disabled="verificandoSolicitud"
                  >
                    <font-awesome-icon 
                      :icon="['fas','heart']" 
                      class="text-black text-4xl hover:text-green-400"
                      :class="{'opacity-50 cursor-not-allowed': verificandoSolicitud}"
                    />
                  </button>

                <!-- En contenidoMascota.vue, modifica el BotonesSwipe -->
                    <!-- En contenidoMascota.vue, dentro del template -->
                    <BotonesSwipe
                      v-if="$route.path.startsWith('/explorar/encuentros')"
                      ref="botonesSwipeRef"
                      :mascotaId="mascotaComputed?.id"
                      :ofertaId="ofertaActual?.id_oferta || route.params.id"
                      :mostrarBotones="mostrarBotones"
                      :mostrarInstrucciones="true"
                      :contenedorElement="contenedorPrincipal"
                      :mostrarAdvertencia="true" 
                      @like="onLike"
                      @dislike="onDislike"
                      @swipe-start="onSwipeStart"
                      @swipe-end="onSwipeEnd"
                      @swipe-cancel="onSwipeCancel"
                      @swipe-animation="onSwipeAnimation"
                      @mostrar-advertencia="onMostrarAdvertencia" 
                    />
            </div>
         </div>
     </div>
    
    <!-- Contenedor de advertencia -->
    <transition name="slide-up">
      <div 
        v-if="mostrarAdvertencia"
        class="absolute top-0 left-0 right-0 bottom-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4"
      >
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl max-h-[70vh] h-[70vh]">
          <AdvertenciaAdopcion 
            ref="advertenciaRef" 
            @close="onAdopcionCancel"
            @success="onAdopcionSuccess"
            @error="onAdopcionError"
          />
        </div>
      </div>
    </transition>
</template>

<!-- El script se mantiene EXACTAMENTE IGUAL -->
<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { defineProps, defineEmits } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import burro from '@/assets/burro.png'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import { useInteracciones } from '@/composables/useInteracciones'
import PasoAlgo from '@/components/módulo_mascotas/reportarMascota.vue'
import AdvertenciaAdopcion from '@/components/módulo_adopciones/advertenciaParaAdoptantes.vue'
import CaracteristicasMascota from '@/components/ElementosGraficos/CaracteristicasMascota.vue'
import BotonesSwipe from '@/components/ElementosGraficos/BotonesSwipe.vue'

const advertenciaRef = ref(null)
const botonesSwipeRef = ref(null)

const router = useRouter()
const route = useRoute()

const ubicacionUsuario = ref(null)
const cargandoUbicacion = ref(false)

const tieneSolicitudActiva = ref(false)
const verificandoSolicitud = ref(false)

const { accessToken, isAuthenticated, checkAuth, obtenerUbicacionUsuario } = useAuth()

const mascota = ref(null)
const cargando = ref(true)
const error = ref(null)

const scrollContainer = ref(null)
const mostrar = ref(false)
const path = ref('')

const mostrarBotones = ref(false)
const botonesAnimados = ref(null)
const showButtonsContainer = ref(false)

// Nuevas variables para el swipe
const contenedorPrincipal = ref(null)
const swipeTransform = ref('')
const swipeClass = ref('')
const procesandoSwipe = ref(false)

// Estado para controlar la animación
const mostrarAdvertencia = ref(false)

const { registrarInteraccion } = useInteracciones()

// Accede a los parámetros de la ruta
const id = computed(() => route.params.id)
const from = computed(() => route.query.from)

defineOptions({
  inheritAttrs: false
});

const props = defineProps({
  ofertaActual: {
    type: Object,
    default: null
  },
  // NUEVO PROP
  tieneSolicitudActiva: {
    type: Boolean,
    default: false
  }
})

// Define emits para comunicar acciones al padre
const emit = defineEmits(['like', 'dislike', 'close', 'next', 'prev', 'swipe-completed'])

// En contenidoMascota.vue, actualiza onMostrarAdvertencia
async function onMostrarAdvertencia(data) {
  console.log('=== onMostrarAdvertencia llamado ===')
  console.log('Datos recibidos:', data)
  console.log('Mascota computada:', mascotaComputed.value)
  console.log('Oferta actual:', props.ofertaActual)
  
  // Guardar posición actual de scroll
  if (scrollContainer.value) {
    scrollContainer.value.scrollTop = 0
  }

  // Primero, mostrar el contenedor de advertencia
  mostrarAdvertencia.value = true
  
  // Esperar a que Vue actualice el DOM y monte el componente
  await nextTick()
  await new Promise(resolve => setTimeout(resolve, 100)) // Pequeña espera adicional
  
  console.log('advertenciaRef después de mostrar:', advertenciaRef.value)
  
  // Verificar que el componente esté montado
  if (advertenciaRef.value && typeof advertenciaRef.value.open === 'function') {
    console.log('Contexto: Vista de encuentros (swipe) - Mostrando advertencia')
    
    // Usar los datos del evento para determinar qué mostrar
    const ofertaIdParaAdvertencia = data.ofertaId || props.ofertaActual?.id_oferta
    const mascotaIdParaAdvertencia = data.mascotaId || mascotaComputed.value?.id
    
    console.log('IDs para advertencia:', {
      ofertaIdParaAdvertencia,
      mascotaIdParaAdvertencia
    })
    
    // Pasar los IDs correctos a la advertencia
    if (ofertaIdParaAdvertencia) {
      console.log('Abriendo advertencia con ofertaId:', ofertaIdParaAdvertencia)
      await advertenciaRef.value.open(ofertaIdParaAdvertencia, null)
    } else if (mascotaIdParaAdvertencia && mascotaIdParaAdvertencia !== 'demo-burro') {
      console.log('Abriendo advertencia con mascotaId:', mascotaIdParaAdvertencia)
      await advertenciaRef.value.open(null, mascotaIdParaAdvertencia)
    } else {
      console.error('No se pudo determinar el ID para la advertencia')
      mostrarNotificacion('Error al abrir formulario de adopción', 'error')
      mostrarAdvertencia.value = false
      procesandoSwipe.value = false
      return
    }
    
    console.log('Advertencia abierta correctamente')
    
    // Desplazar al usuario hacia el modal
    setTimeout(() => {
      const modalElement = document.querySelector('.advertencia-container')
      if (modalElement) {
        modalElement.scrollIntoView({ behavior: 'smooth', block: 'end' })
      }
    }, 100)
  } else {
    console.error('advertenciaRef no disponible o método open no encontrado')
    console.error('advertenciaRef:', advertenciaRef.value)
    mostrarNotificacion('Error al abrir formulario de adopción', 'error')
    mostrarAdvertencia.value = false
    procesandoSwipe.value = false
  }
}

// Función para cargar la ubicación del usuario
async function cargarUbicacionUsuario() {
  try {
    cargandoUbicacion.value = true
    
    console.log('=== CARGANDO UBICACIÓN ===')
    console.log('Mascota computada:', mascotaComputed.value)
    console.log('Oferta actual:', props.ofertaActual)
    
    // ✅ Prioridad 1: Usar ubicación que ya viene en los datos de la mascota
    if (mascotaComputed.value?.ubicacion) {
      console.log('✅ Usando ubicación desde datos de la mascota:', mascotaComputed.value.ubicacion)
      ubicacionUsuario.value = mascotaComputed.value.ubicacion
      return
    }
    
    // ✅ Prioridad 2: Usar ubicación textual si existe
    if (mascotaComputed.value?.ubicacion_texto) {
      console.log('✅ Usando ubicación textual:', mascotaComputed.value.ubicacion_texto)
      ubicacionUsuario.value = {
        city: mascotaComputed.value.ubicacion_texto.split(',')[0]?.trim(),
        state: mascotaComputed.value.ubicacion_texto.split(',')[1]?.trim(),
        country: mascotaComputed.value.ubicacion_texto.split(',')[2]?.trim()
      }
      return
    }
    
    // ✅ Prioridad 3: Intentar obtener ubicación del tutor si tenemos su ID
    const tutorId = mascotaComputed.value?.usuario_id || props.ofertaActual?.id_usuario_responsable
    
    if (tutorId) {
      console.log('🔄 Obteniendo ubicación del tutor:', tutorId)
      try {
        const response = await axios.get(`/api/usuarios/${tutorId}/ubicacion`, {
          headers: {
            'Authorization': `Bearer ${accessToken.value}`
          }
        })
        
        if (response.data.success) {
          console.log('✅ Ubicación obtenida de API:', response.data.data)
          ubicacionUsuario.value = response.data.data
          return
        }
      } catch (apiError) {
        console.error('Error obteniendo ubicación de API:', apiError)
      }
    }
    
    // ✅ Fallback a ubicación por defecto
    console.log('⚠️ Usando ubicación por defecto')
    ubicacionUsuario.value = {
      city: 'Buenos Aires',
      state: 'Buenos Aires',
      country: 'Argentina'
    }
    
  } catch (error) {
    console.error('Error cargando ubicación:', error)
    ubicacionUsuario.value = {
      city: 'Buenos Aires',
      state: 'Buenos Aires',
      country: 'Argentina'
    }
  } finally {
    cargandoUbicacion.value = false
  }
}

// Computed para mostrar la ubicación formateada

const ubicacionDisplay = computed(() => {
    console.log('📌 Calculando ubicacionDisplay')
    console.log('Ubicación usuario:', ubicacionUsuario.value)
    console.log('Mascota computada ubicacion:', mascota.value?.ubicacion)
    console.log('Mascota computada ubicacion_texto:', mascota.value?.ubicacion_texto)
    
    // Verificar si la mascota tiene ubicación
    if (mascota.value?.ubicacion) {
        console.log('✅ Mascota tiene ubicación propia:', mascota.value.ubicacion)
        return mascota.value.ubicacion_texto || 
               `${mascota.value.ubicacion.city}, ${mascota.value.ubicacion.country}`
    }
    
    // Si no, usar ubicación del usuario (solo para demo)
    if (ubicacionUsuario.value) {
        const { city, state, country } = ubicacionUsuario.value
        console.log('⚠️ Usando ubicación del usuario como fallback')
        return [city, state, country].filter(Boolean).join(', ')
    }
    
    console.log('❌ Sin ubicación disponible')
    return 'Ubicación no disponible'
})

// Función para verificar si ya existe una solicitud activa
async function verificarSolicitudActiva() {
    const mascotaId = mascotaComputed.value?.id
    
    // Si es la mascota demo, no verificar
    if (!mascotaId || mascotaId === 'demo-burro') {
        tieneSolicitudActiva.value = false
        return
    }
    
    try {
        verificandoSolicitud.value = true
        const response = await axios.get(`/api/solicitudes/verificar-activa/${mascotaId}`, {
            headers: {
                'Authorization': `Bearer ${accessToken.value}`
            }
        })
        
        if (response.data.success) {
            tieneSolicitudActiva.value = response.data.data.tieneSolicitudActiva
            console.log('✅ Verificación de solicitud:', {
                mascotaId,
                tieneSolicitudActiva: tieneSolicitudActiva.value,
                solicitud: response.data.data.solicitud
            })
        }
    } catch (error) {
        console.error('Error verificando solicitud activa:', error)
        tieneSolicitudActiva.value = false
    } finally {
        verificandoSolicitud.value = false
    }
}

// Función para manejar el dislike
async function onLike(data) {
    console.log('Like recibido desde BotonesSwipe:', data)
    
    // Verificar si ya tiene solicitud activa (solo en vista cerca)
    if (!route.path.startsWith('/explorar/encuentros') && tieneSolicitudActiva.value) {
        console.log('⚠️ Ya existe una solicitud activa para esta mascota')
        mostrarNotificacion('Ya has enviado una solicitud de adopción para esta mascota', 'info')
        return
    }
    
    try {
        // Registrar la interacción en la base de datos
        await registrarInteraccion({
            mascota_id: data.mascotaId,
            oferta_id: data.ofertaId || props.ofertaActual?.id_oferta,
            tipo_interaccion: 'like'
        })
        
        // Emitir evento al componente padre
        emit('like', {
            mascotaId: data.mascotaId,
            ofertaId: data.ofertaId || props.ofertaActual?.id_oferta
        })
        
        // Si estamos en "cerca de ti", abrir advertencia
        if (route.path.startsWith('/explorar/cerca/')) {
            abrirAdvertencia()
        } 
        // Si estamos en "encuentros", emitir evento para avanzar
        else if (route.path.startsWith('/explorar/encuentros')) {
            emit('next')
        }
        
    } catch (error) {
        console.error('Error registrando like:', error)
        mostrarNotificacion('Error al registrar tu interés', 'error')
    }
}


async function onDislike(data) {
  console.log('Dislike recibido desde BotonesSwipe:', data)
  
  try {
    // Registrar la interacción en la base de datos
    await registrarInteraccion({
      mascota_id: data.mascotaId,
      oferta_id: data.ofertaId || props.ofertaActual?.id_oferta,
      tipo_interaccion: 'dislike'
    })
    
    // Emitir evento al componente padre
    emit('dislike', {
      mascotaId: data.mascotaId,
      ofertaId: data.ofertaId || props.ofertaActual?.id_oferta
    })
    
    // Si estamos en "encuentros", emitir evento para avanzar
    if (route.path.startsWith('/explorar/encuentros')) {
      emit('next')  // Esto le dice al padre que avance
    }
    
  } catch (error) {
    console.error('Error registrando dislike:', error)
    mostrarNotificacion('Error al registrar tu decisión', 'error')
  }
}

function onSwipeStart(tipo) {
  console.log('Swipe iniciado:', tipo)
  procesandoSwipe.value = true
}

function onSwipeEnd(tipo) {
  console.log('Swipe finalizado:', tipo)
  // El reset se maneja en las funciones onLike/onDislike
}

function onSwipeCancel(tipo) {
  console.log('Swipe cancelado:', tipo)
  procesandoSwipe.value = false
  resetSwipeAnimation()
}

function onSwipeAnimation(animation) {
  // Aplicar animación recibida del componente BotonesSwipe
  swipeTransform.value = animation.transform
  swipeClass.value = animation.opacity
}

function resetSwipeAnimation() {
  swipeTransform.value = ''
  swipeClass.value = ''
}


// Verificar autenticación 
onMounted(async () => {
  console.log('Componente contenidoMascota montado')
  console.log('advertenciaRef inicial:', advertenciaRef.value)

  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      mostrarNotificacion('Debe iniciar sesión para acceder a esta página', 'error')
      setTimeout(() => {
        router.push('/')
      }, 2000)
      return
    }
  }
})

async function abrirAdvertencia() {
    console.log('=== abrirAdvertencia llamado ===')
    
    // Verificar nuevamente antes de abrir (por si acaso)
    if (tieneSolicitudActiva.value) {
        console.log('⚠️ Ya existe una solicitud activa, no se puede abrir otra')
        mostrarNotificacion('Ya has enviado una solicitud de adopción para esta mascota', 'info')
        return
    }
    
    // Guardar posición actual de scroll
    if (scrollContainer.value) {
        scrollContainer.value.scrollTop = 0
    }

    // Mostrar la advertencia con animación
    mostrarAdvertencia.value = true
    
    setTimeout(() => {
        if (advertenciaRef.value && typeof advertenciaRef.value.open === 'function') {
            const ofertaId = route.params.id
            const mascotaId = route.query.mascota_id || mascotaComputed.value?.id
            
            advertenciaRef.value.open(ofertaId, mascotaId)
            
            setTimeout(() => {
                const modalElement = document.querySelector('.advertencia-container')
                if (modalElement) {
                    modalElement.scrollIntoView({ behavior: 'smooth', block: 'end' })
                }
            }, 100)
        } else {
            console.error('advertenciaRef no disponible')
            mostrarNotificacion('Error al abrir formulario de adopción', 'error')
            mostrarAdvertencia.value = false
        }
    }, 100)
}

watch(mostrarAdvertencia, (newVal) => {
  if (newVal) {
    // Cuando se muestra la advertencia, deshabilitar scroll en el contenedor
    if (scrollContainer.value) {
      scrollContainer.value.style.overflow = 'hidden'
    }
  } else {
    // Cuando se cierra, restaurar scroll
    if (scrollContainer.value) {
      scrollContainer.value.style.overflow = 'auto'
    }
  }
})

// En contenidoMascota.vue, actualiza onAdopcionSuccess
async function onAdopcionSuccess(data) {
    console.log('=== onAdopcionSuccess llamado ===')
    console.log('Datos de adopción exitosa:', data)
    
    // Marcar que ahora tiene una solicitud activa
    tieneSolicitudActiva.value = true
    
    mostrarNotificacion('¡Solicitud enviada con éxito!', 'success')
    mostrarAdvertencia.value = false
    
    // Si estamos en la vista de swipe, completar el like
    if (route.path.startsWith('/explorar/encuentros')) {
        console.log('Completando flujo de swipe después de adopción')
        
        try {
            const interaccionData = {
                mascota_id: mascotaComputed.value?.id || null,
                oferta_id: props.ofertaActual?.id_oferta || null,
                tipo_interaccion: 'like'
            }
            
            if (interaccionData.mascota_id || interaccionData.oferta_id) {
                await registrarInteraccion(interaccionData)
                console.log('Interacción registrada correctamente')
            }
            
            emit('swipe-completed', {
                tipo: 'like',
                data: data
            })
            emit('next')
            
        } catch (err) {
            console.error('Error registrando interacción final:', err)
            emit('swipe-completed', {
                tipo: 'like',
                data: data,
                error: err.message
            })
            emit('next')
        }
    }
}

// Manejar cancelacion en adopción
function onAdopcionCancel() {
  console.log('=== onAdopcionCancel llamado ===')
  console.log('Adopción cancelada desde modal')
  mostrarAdvertencia.value = false
  procesandoSwipe.value = false // Resetear estado de swipe
  
  // Si estamos en swipe, resetear la animación
  if (route.path.startsWith('/explorar/encuentros')) {
    console.log('Reseteando animación de swipe cancelado')
    resetSwipeAnimation()
    
    // Notificar al componente padre que el swipe fue cancelado
    emit('swipe-cancel', 'like')
  }
}

// Modificar handleAdopcionClose para ocultar con animación
function handleAdopcionClose() {
  console.log('Modal de adopción cerrado')
  mostrarAdvertencia.value = false
  
  // Guardar posición de scroll antes de mostrar el modal
  if (scrollContainer.value) {
    const scrollTop = scrollContainer.value.scrollTop
    // Restaurar scroll después de cerrar
    requestAnimationFrame(() => {
      if (scrollContainer.value) {
        scrollContainer.value.scrollTop = scrollTop
      }
    })
  }
}

function handleClose() {
  // Limpiar observer antes de navegar
  if (observer && botonesAnimados.value) {
    observer.unobserve(botonesAnimados.value)
    observer.disconnect()
    observer = null
  }
  
  if (route.fullPath.includes('/perfil/mascota/')) {
    router.push('/explorar/perfil/mascotas')
  } else if (route.path.startsWith('/explorar/cerca/') && route.params.id) {
    router.push('/explorar/cerca')
  }
}

let observer = null

const images = ref([
  burro,
  'https://cdn.pixabay.com/photo/2024/09/09/17/22/donkey-9035452_1280.jpg',
  'https://cdn.pixabay.com/photo/2020/12/29/22/57/donkey-5871800_960_720.jpg'
]);

function goToHistorial() {
  // ✅ Obtener el ID de la mascota
  const mascotaId = mascotaComputed.value?.id;
  
  // ✅ Obtener el ID de la oferta de múltiples fuentes posibles
  const ofertaId = props.ofertaActual?.id_oferta || // De props
                   route.params.id ||                // De parámetros de ruta
                   route.query.ofertaId ||           // De query params
                   route.query.oferta_id;            // De query params alternativo
  
  // Validar que tenemos un ID de mascota válido
  if (!mascotaId || mascotaId === 'demo-burro') {
    console.error('❌ No se puede navegar al historial: ID de mascota no válido', mascotaId);
    mostrarNotificacion('No se puede acceder al historial de esta mascota', 'error');
    return;
  }
  
  // Verificar si tenemos permisos de historial desde donde venimos
  const tienePermisoHistorial = props.ofertaActual?.permisos?.historial_medico || 
                               route.query.permisoHistorial === '1';
  
  // Si NO tenemos permiso de historial, mostrar advertencia
  if (!tienePermisoHistorial) {
    console.log('⚠️ No hay permiso para ver historial médico');
    mostrarNotificacion('El tutor no ha compartido el historial médico de esta mascota', 'info');
    // Podrías mostrar un modal informativo en lugar de bloquear
    // return; // Descomentar si quieres bloquear la navegación
  }
  
  console.log('🚀 Navegando a historial:', {
    mascotaId,
    ofertaId,
    nombreMascota: mascotaComputed.value?.nombre,
    tienePermisoHistorial,
    puedeContactar: props.ofertaActual?.permisos?.contacto_tutor || false
  });
  
  // Construir query params
  const queryParams = {
    from: route.name,
    ofertaId: ofertaId || '',  // Incluir aunque sea vacío
    permisoHistorial: tienePermisoHistorial ? '1' : '0',
    puedeContactar: props.ofertaActual?.permisos?.contacto_tutor ? '1' : '0',
    nombreMascota: mascotaComputed.value?.nombre || 'Mascota',
    origen: ofertaId ? 'oferta' : 'mascota',
    ts: Date.now()
  };
  
  // Limpiar undefined
  Object.keys(queryParams).forEach(key => {
    if (queryParams[key] === undefined || queryParams[key] === null) {
      delete queryParams[key];
    }
  });
  
  // Navegar al historial
  router.push({
    name: 'tutores', // O la ruta correcta para tu historial
    params: { id: mascotaId },
    query: queryParams
  });
}

const mostrarBotonVolver = computed(() => from.value === 'cerca')

// --- Cargar mascota desde oferta o directamente ---
async function cargarMascota() {
  if (props.ofertaActual) {
    console.log('Usando oferta actual:', props.ofertaActual);
    mascota.value = props.ofertaActual.mascota;
    console.log('Mascota cargada desde props:', mascota.value);
    console.log('Fotos de la mascota:', mascota.value?.fotos);
    cargando.value = false;
    return;
  }

  const idMascota = route.params.id || route.query.mascota_id;
  const idOferta = route.params.id || route.query.oferta_id;
  
  // Si no hay ID, usar demo
  if (!idMascota && !idOferta) {
    console.log('No hay ID, usando demo');
    mascota.value = null;
    return;
  }

  try {
    cargando.value = true;
    error.value = null;
    
    let endpoint = '';
    let params = {};
    
    // Determinar qué endpoint usar
    if (idOferta && route.path.startsWith('/explorar/cerca/')) {
      // Cargar desde oferta de adopción
      endpoint = `/api/adopciones/ofertas/${idOferta}`;
      console.log('Cargando desde oferta:', endpoint);
    } else if (idMascota) {
      // Cargar mascota directamente
      endpoint = `/api/mascotas/${idMascota}`;
      console.log('Cargando mascota directa:', endpoint);
    } else {
      throw new Error('No se pudo determinar la ruta para cargar la mascota');
    }
    
    const response = await axios.get(endpoint, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      }
    });

    console.log('Respuesta completa:', response.data);

    // Manejar diferentes estructuras de respuesta
    if (response.data.success) {
      if (response.data.data && response.data.data.mascota) {
        // Respuesta desde ofertas: { success: true, data: { mascota: {...} } }
        mascota.value = response.data.data.mascota;
        console.log('Mascota cargada desde data.mascota:', mascota.value);
        console.log('Fotos cargadas:', mascota.value?.fotos);
      } else if (response.data.data) {
        // Respuesta desde mascotas: { success: true, data: {...} }
        mascota.value = response.data.data;
        console.log('Mascota cargada desde data:', mascota.value);
        console.log('Fotos cargadas:', mascota.value?.fotos);
      } else if (response.data.mascota) {
        // Respuesta alternativa
        mascota.value = response.data.mascota;
        console.log('Mascota cargada desde mascota:', mascota.value);
      } else {
        mascota.value = response.data;
        console.log('Mascota cargada desde root:', mascota.value);
      }
    } else {
      throw new Error(response.data.message || 'Error en la respuesta del servidor');
    }
    
  } catch (err) {
    console.error('Error cargando mascota:', err);
    console.error('Detalles del error:', err.response?.data);
    error.value = err.response?.data?.message || err.message || 'No se pudo cargar la información de la mascota.';
    mascota.value = null;
  } finally {
    cargando.value = false;
  }
}

// --------- Computeds de fallback / data mostrada / mascota de prueba / datos de prueba ----------
const mascotaComputed = computed(() => {
  if (mascota.value) return mascota.value

  return {
    id: 'demo-burro',
    nombre: 'Lola (demo)',
    edad: '2',
    unidad_edad: 'Años',
    sexo: 'Hembra',
    castrado: true,
    descripcion: `Con sus largas orejas y su mirada dulce... (demo)`,
    caracteristicas: {
      tamano: 'Grande',
      pelaje: 'Corto',
      alimentacion: 'saludable',
      energia: 'Alto',
      comportamiento_animales: 'Amigable',
      comportamiento_ninos: 'Compañera',
      personalidad: 'Amigable',
      descripcion: 'Demo: burro de ejemplo'
    },
    fotos: [
      { id: 'demo-1', url: burro, es_principal: true },
      { id: 'demo-2', url: 'https://cdn.pixabay.com/photo/2024/09/09/17/22/donkey-9035452_1280.jpg', es_principal: false },
      { id: 'demo-3', url: 'https://cdn.pixabay.com/photo/2020/12/29/22/57/donkey-5871800_960_720.jpg', es_principal: false },
      { id: 'demo-4', url: 'https://cdn.pixabay.com/photo/2019/05/08/19/30/donkey-4189421_1280.jpg', es_principal: false },
      { id: 'demo-5', url: 'https://cdn.pixabay.com/photo/2014/05/11/13/40/donkey-foal-341903_1280.jpg', es_principal: false }
    ],
    ubicacionTexto: 'Argentina, Misiones, Apóstoles'
  }
})

const fotoPrincipal = computed(() => {
  const fotos = mascotaComputed.value?.fotos || []
  if (fotos.length) {
    const principal = fotos.find(f => f.es_principal || f.esPrincipal || f.principal)
    return (principal && (principal.url || principal.getUrl || principal.ruta_foto)) || fotos[0].url || burro
  }
  return burro
})

const galleryImages = computed(() => {
  const fotos = mascotaComputed.value?.fotos || [];
  console.log('Fotos disponibles para galería:', fotos);
  
  if (!fotos.length) {
    console.log('No hay fotos, usando fallback');
    return [burro];
  }
  
  // Mapear correctamente las URLs
  const urls = fotos.map(f => {
    // Si la foto tiene un método getUrl (del accessor en el modelo)
    if (f.url) {
      console.log(`Foto ${f.id}: Usando f.url:`, f.url);
      return f.url;
    }
    // Si es una ruta local
    else if (f.ruta_foto) {
      // Verificar si ya es una URL completa
      if (f.ruta_foto.startsWith('http')) {
        return f.ruta_foto;
      }
      // Construir URL desde storage
      const url = `/storage/${f.ruta_foto.replace('storage/', '')}`;
      console.log(`Foto ${f.id}: Construyendo URL:`, url);
      return url;
    }
    // Fallback
    else {
      console.log(`Foto ${f.id}: Sin URL válida, usando fallback`);
      return burro;
    }
  });
  
  console.log('URLs finales para galería:', urls);
  return urls;
});

function onImgError(event) {
  event.target.src = burro
}

// -------------- lifecycle --------------
onMounted(async () => {
    document.body.style.overflow = 'hidden'
    
    await nextTick()
    showButtonsContainer.value = true
    await nextTick()
    
    if (botonesAnimados.value) {
        initObserver()
    }
    
    await Promise.all([
        cargarMascota(),
        cargarUbicacionUsuario()
    ])
    
    // Verificar solicitud activa después de cargar la mascota
    // Solo en vista "cerca", NO en "encuentros"
    if (!route.path.startsWith('/explorar/encuentros')) {
        await verificarSolicitudActiva()
    }
})


onUnmounted(() => {
  // Limpiar observer de forma segura
  if (observer) {
    observer.disconnect()
    observer = null
  }
  
  document.body.style.overflow = ''
})

// ---------------- openGallery con manejo de errores ---------------
const openGallery = (index) => {
  const mascotaId = mascotaComputed.value?.id;
  const ofertaId = route.params.id || route.query.oferta_id;
  
  if (!mascotaId && !ofertaId) {
    console.error('No se pudo determinar el ID de la mascota u oferta');
    return;
  }

  try {
    const queryParams = {
      images: JSON.stringify(galleryImages.value),
      from: route.name,
      originalParams: JSON.stringify(route.params)
    };
    
    // Agregar oferta_id si existe
    if (ofertaId && route.query.oferta_id !== ofertaId) {
      queryParams.oferta_id = ofertaId;
    }
    
    // Agregar mascota_id si existe
    if (mascotaId && route.query.mascota_id !== mascotaId) {
      queryParams.mascota_id = mascotaId;
    }
    
    // Navegar a la galería
    router.push({
      name: 'galeria-mascota-imagen',
      params: { 
        id: mascotaId || ofertaId,
        imageIndex: index 
      },
      query: queryParams
    }).catch(err => {
      if (err.name !== 'NavigationDuplicated') {
        console.error('Error de navegación:', err);
      }
    });
  } catch (error) {
    console.error('Error al abrir galería:', error);
  }
};

// Computed para mostrar la edad correctamente
const edadDisplay = computed(() => {
  const mascota = mascotaComputed.value
  
  if (mascota.edad_formateada && mascota.edad_formateada !== 'Edad no disponible') {
    return mascota.edad_formateada
  }
  
  if (mascota.edad && mascota.unidad_edad) {
    return `${mascota.edad} ${mascota.unidad_edad}`
  }
  
  return 'Edad no disponible'
})

const castradoLabel = computed(() => {
  const castrado = mascotaComputed.value?.castrado
  
  if (castrado === null || castrado === undefined) {
    return 'Castración: No especificado'
  }
  
  return castrado ? 'Castrado/a' : 'No castrado/a'
})

// Función de notificación
function mostrarNotificacion(mensaje, tipo = 'info') {
  console.log(`${tipo.toUpperCase()}: ${mensaje}`)
  // Implementar notificaciones si es necesario
}


// Observador para mostrar/ocultar botones
const initObserver = () => {
  if (observer) {
    observer.disconnect()
    observer = null
  }

  if (!botonesAnimados.value) return

  observer = new IntersectionObserver(
    ([entry]) => {
      mostrarBotones.value = entry.isIntersecting
    },
    { 
      threshold: 0.5,
      rootMargin: '0px'
    }
  )
  
  observer.observe(botonesAnimados.value)
}

// Agregar este watch después de las funciones
watch(mostrarAdvertencia, async (newVal) => {
  if (newVal) {
    // Esperar a que el componente se monte
    await nextTick()
    console.log('Componente AdvertenciaAdopcion montado:', !!advertenciaRef.value)
    
    if (!advertenciaRef.value) {
      console.error('ERROR: advertenciaRef sigue siendo null después de montar')
    }
  }
})

watch(() => mascotaComputed.value?.id, async (newId, oldId) => {
    // Solo verificar si cambió y NO estamos en swipe
    if (newId && newId !== oldId && !route.path.startsWith('/explorar/encuentros')) {
        await verificarSolicitudActiva()
    }
})

// En onMounted o cuando se asigna props.ofertaActual
watch(() => props.ofertaActual, (newVal) => {
  console.log('🔄 ofertaActual cambió:', {
    id_oferta: newVal?.id_oferta,
    permisos: newVal?.permisos,
    tiene_permiso_historial: newVal?.permisos?.historial_medico
  });
}, { immediate: true });

</script>

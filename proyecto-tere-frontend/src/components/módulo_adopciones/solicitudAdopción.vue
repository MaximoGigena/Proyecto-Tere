<template>
  <!-- Overlay pantalla completa -->
  <div class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-3">
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
                Solicitud #{{ solicitud.id }}
              </h2>
              <div class="flex flex-wrap items-center gap-2 mt-1 text-sm text-gray-600">
                <span class="font-medium">Mascota:</span>
                <span class="font-semibold text-gray-800">{{ solicitud.mascota.nombre }}</span>
                <span class="text-gray-300">•</span>
                <span>Recibida: {{ solicitud.fecha }}</span>
                <span class="text-gray-300">•</span>
                <span
                  :class="[
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold border',
                    estadoClasses
                  ]"
                >
                  {{ solicitud.estado }}
                </span>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="rechazar"
                class="px-3 py-2 rounded-xl border border-red-200 text-red-700 hover:bg-red-50 active:scale-[.98] transition"
                title="Rechazar solicitud"
              >
                <font-awesome-icon :icon="['fas','xmark']" class="mr-2" /> Rechazar
              </button>
              <button
                @click="aprobar"
                class="px-3 py-2 rounded-xl bg-green-600 text-white hover:bg-green-700 active:scale-[.98] shadow-sm transition"
                title="Aprobar solicitud"
              >
                <font-awesome-icon :icon="['fas','check']" class="mr-2" /> Aprobar
              </button>
            </div>
          </div>
          <div class="mt-2 flex flex-wrap gap-2">
            <RouterLink
                v-if="perfil.id"
                :to="{ name: 'chat-room', params: { id: perfil.id }, query: { from: 'adoption-request' } }"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-blue-600 text-white ..."
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
          <div class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
              <img :src="solicitud.mascota.img" :alt="solicitud.mascota.nombre" class="w-16 h-16 rounded-2xl object-cover" />
              <div>
                <div class="text-lg font-semibold text-gray-800">{{ solicitud.mascota.nombre }}</div>
                <div class="text-sm text-gray-600">{{ solicitud.mascota.especie }} • {{ solicitud.mascota.raza }} • {{ solicitud.mascota.edad }}</div>
              </div>
            </div>
          </div>

          <!-- Secciones de la solicitud -->
          <div class="mt-4 space-y-4">
            <!-- Datos principales -->
            <section class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-3">Datos de la solicitud</h3>
              <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-gray-700">
                <div>
                  <dt class="text-gray-500">Estado</dt>
                  <dd class="font-medium">{{ solicitud.estado }}</dd>
                </div>
                <div>
                  <dt class="text-gray-500">Fecha</dt>
                  <dd class="font-medium">{{ solicitud.fecha }}</dd>
                </div>
                <div>
                  <dt class="text-gray-500">Modalidad</dt>
                  <dd class="font-medium">{{ solicitud.modalidad }}</dd>
                </div>
                <div>
                  <dt class="text-gray-500">Ubicación</dt>
                  <dd class="font-medium">{{ solicitud.ubicacion }}</dd>
                </div>
              </dl>
            </section>

            <!-- Hogar -->
            <section class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-3">Hogar y convivencia</h3>
              <ul class="space-y-1 text-sm text-gray-700">
                <li>
                  <span class="text-gray-500">Tipo de vivienda:</span>
                  <span class="font-medium">{{ solicitud.domicilio.tipo }}</span>
                </li>
                <li>
                  <span class="text-gray-500">Patio:</span>
                  <span class="font-medium">{{ solicitud.domicilio.patio ? 'Sí' : 'No' }}</span>
                </li>
                <li>
                  <span class="text-gray-500">Cercada:</span>
                  <span class="font-medium">{{ solicitud.domicilio.cercada ? 'Sí' : 'No' }}</span>
                </li>
                <li>
                  <span class="text-gray-500">Conviven:</span>
                  <span class="font-medium">{{ solicitud.conviven.join(', ') }}</span>
                </li>
                <li>
                  <span class="text-gray-500">Otras mascotas:</span>
                  <span class="font-medium">{{ solicitud.otrasMascotas }}</span>
                </li>
              </ul>
            </section>

            <!-- Motivaciones / Respuestas -->
            <section class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-3">Motivaciones y respuestas</h3>
              <div class="space-y-3">
                <div v-for="(qa, i) in solicitud.respuestas" :key="i" class="bg-gray-50 rounded-2xl p-3">
                  <div class="text-xs font-semibold text-gray-500 uppercase">{{ qa.p }}</div>
                  <div class="text-sm text-gray-800 mt-1">{{ qa.r }}</div>
                </div>
              </div>
            </section>

            <!-- Referencias -->
            <section class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-3">Referencias</h3>
              <ul class="divide-y divide-gray-100">
                <li v-for="(refe, idx) in solicitud.referencias" :key="idx" class="py-2 flex items-center justify-between">
                  <div>
                    <div class="font-medium text-gray-800">{{ refe.nombre }}</div>
                    <div class="text-sm text-gray-600">{{ refe.relacion }}</div>
                  </div>
                  <a :href="`tel:${refe.contacto}`" class="text-sm text-blue-600 hover:underline">{{ refe.contacto }}</a>
                </li>
              </ul>
            </section>

            <!-- Documentos -->
            <section v-if="solicitud.documentos && solicitud.documentos.length" class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-3">Documentos adjuntos</h3>
              <div class="flex flex-wrap gap-2">
                <a
                  v-for="(doc, i) in solicitud.documentos"
                  :key="i"
                  :href="doc.url"
                  target="_blank"
                  class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 text-sm"
                >
                  <font-awesome-icon :icon="['fas','paperclip']" /> {{ doc.nombre }}
                </a>
              </div>
            </section>

            <!-- Notas -->
            <section class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100">
              <h3 class="text-xl font-bold text-gray-800 mb-2">Notas internas</h3>
              <!-- id y name añadidos para evitar advertencia de autocompletado -->
              <textarea
                id="notas-internas"
                name="notas"
                v-model="solicitud.notas"
                rows="4"
                class="w-full rounded-2xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-3 text-sm"
                placeholder="Observaciones del evaluador..."
              ></textarea>
              <div class="mt-2 text-right">
                <button class="px-3 py-2 rounded-xl bg-gray-900 text-white hover:bg-black active:scale-[.98]">Guardar notas</button>
              </div>
            </section>
          </div>
        </div>
      </div>

      <!-- Columna derecha: Perfil (scroll independiente) -->
       <div class="flex flex-col w-1/2 min-w-0 border-l border-gray-200">
        <!-- Contenedor scrollable del perfil -->
        <div class="flex-1 overflow-y-auto overflow-x-hidden invisible-scrollbar">
          <PerfilUsuarioOverlay :perfil="perfil" class="w-full" /> 
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import ReportarUsuario from '@/components/módulo_usuario/reportarUsuario.vue'
// Reusar el componente de perfil que ya tenés (ajusta la ruta si hace falta)
import PerfilUsuarioOverlay from '@/components/módulo_usuario/contenidoUsuario.vue'

const route = useRoute()
const router = useRouter()

const leftScroll = ref(null)
const mostrarReporte = ref(false)
const marcarContactado = ref(false)


// Perfil del solicitante (mock - en producción vendría de API). Si tu componente de perfil lee los params
// directamente, podés quitar esta prop y dejar que el componente use useRoute(). Aquí lo paso por prop para
// que quede explícito y reutilizable.
const perfil = computed(() => {
  return {
    id: route.params.userId,
    nombre: `Usuario ${route.params.userId || '—'}`,
    img: 'https://cdn.pixabay.com/photo/2020/07/16/07/36/man-5410019_960_720.jpg',
    edad: '30 años',
    descripcion:
      'Amante de los animales con experiencia en cuidado de mascotas. Comprometido con el bienestar animal y la adopción responsable.',
    experiencia: 'Experto',
    tipoCuidador: 'Hogar temporal',
    mascotas: '3',
    ubicacion: 'Buenos Aires, Argentina',
    fotos: [
      'https://cdn.pixabay.com/photo/2020/12/29/22/57/donkey-5871800_960_720.jpg',
      'https://cdn.pixabay.com/photo/2024/09/09/17/22/donkey-9035452_1280.jpg'
    ]
  }
})

// Datos de solicitud (mock)
const solicitud = ref({
  id: 'A-10234',
  fecha: '2025-08-20',
  estado: 'Pendiente',
  mascota: {
    nombre: 'Firulais',
    img: 'https://cdn.pixabay.com/photo/2017/09/25/13/12/dog-2785074_1280.jpg',
    especie: 'Perro',
    raza: 'Mestizo',
    edad: '2 años'
  },
  modalidad: 'Presencial',
  ubicacion: 'Buenos Aires',
  domicilio: { tipo: 'Casa', patio: true, cercada: true },
  conviven: ['2 adultos', '1 niño'],
  otrasMascotas: '1 gato',
  respuestas: [
    { p: '¿Por qué querés adoptar?', r: 'Porque amo a los perros' },
    { p: '¿Qué experiencia tenés?', r: 'Ya adopté antes' }
  ],
  referencias: [
    { nombre: 'Juan Pérez', relacion: 'Amigo', contacto: '123456789' }
  ],
  documentos: [{ nombre: 'DNI.pdf', url: '#' }],
  notas: ''
})


// Estilos dinámicos del estado
const estadoClasses = computed(() => {
  const e = solicitud.value.estado
  if (e === 'Aprobada') return 'bg-green-50 text-green-700 border-green-200'
  if (e === 'Rechazada') return 'bg-red-50 text-red-700 border-red-200'
  return 'bg-amber-50 text-amber-700 border-amber-200'
})

// Acciones
function aprobar() {
  solicitud.value.estado = 'Aprobada'
}
function rechazar() {
  solicitud.value.estado = 'Rechazada'
}

// Manejo del scroll del body al abrir/cerrar overlay
onMounted(() => {
  const prev = document.body.style.overflow
  document.body.dataset.prevOverflow = prev
  document.body.style.overflow = 'hidden'
  console.log('ContenidoPerfil (overlay) montado — params:', route.params)
})

onUnmounted(() => {
  document.body.style.overflow = document.body.dataset.prevOverflow || ''
  delete document.body.dataset.prevOverflow
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
</style>

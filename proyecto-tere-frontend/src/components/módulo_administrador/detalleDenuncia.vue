<template>
  <div class="space-y-6">
    <!-- Header con botón volver -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-800">Detalles de la Denuncia</h2>
      <button
        @click="emit('volver')"
        class="bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium px-4 py-2 rounded-lg transition-colors duration-150"
      >
        ← Volver a la lista
      </button>
    </div>

    <!-- Información principal -->
    <div class="bg-white p-6 rounded-xl shadow space-y-6">
      <!-- Estado y gravedad -->
      <div class="flex items-center justify-between">
        <div>
          <span :class="estadoClase" class="px-3 py-1 rounded-full text-sm font-medium">
            {{ formatEstado(denuncia.estado) }}
          </span>
          <span :class="gravedadClase" class="ml-3 px-3 py-1 rounded-full text-sm font-medium">
            Gravedad: {{ denuncia.gravedad }}
          </span>
        </div>
        <div class="text-sm text-gray-500">
          Denuncia #{{ denuncia.id }} · {{ formatFechaCompleta(denuncia.fecha_completa) }}
        </div>
      </div>

      <!-- Datos de la denuncia -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Columna izquierda -->
        <div class="space-y-4">
          <div>
            <h3 class="font-semibold text-gray-700 mb-2">Información de la Denuncia</h3>
            <div class="space-y-2">
              <p><strong>Categoría:</strong> {{ denuncia.razon }}</p>
              <p><strong>Subcategoría:</strong> {{ denuncia.subrazon }}</p>
              <p><strong>Descripción:</strong></p>
              <div class="bg-gray-50 p-3 rounded-lg">
                {{ denuncia.descripcion || 'Sin descripción adicional' }}
              </div>
            </div>
          </div>

          <!-- Información de la mascota -->
          <div v-if="denuncia.mascota">
            <h3 class="font-semibold text-gray-700 mb-2">Mascota Denunciada</h3>
            <div class="flex items-center space-x-3">
              <div v-if="denuncia.mascota.foto" class="w-16 h-16 rounded-lg overflow-hidden">
                <img 
                  :src="denuncia.mascota.foto" 
                  :alt="denuncia.mascota.nombre"
                  class="w-full h-full object-cover"
                >
              </div>
              <div>
                <p><strong>Nombre:</strong> {{ denuncia.mascota.nombre }}</p>
                <p><strong>Especie:</strong> {{ denuncia.mascota.especie }}</p>
                <p><strong>ID:</strong> {{ denuncia.mascota.id }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <!-- Información del usuario denunciado -->
          <div v-if="denuncia.usuario_denunciado">
            <h3 class="font-semibold text-gray-700 mb-2">Usuario Denunciado</h3>
            <div class="space-y-2">
              <p><strong>Nombre:</strong> {{ denuncia.usuario_denunciado.nombre }}</p>
              <p><strong>Email:</strong> {{ denuncia.usuario_denunciado.email }}</p>
              <p><strong>ID:</strong> {{ denuncia.usuario_denunciado.id }}</p>
            </div>
          </div>

          <!-- Información de la oferta -->
          <div v-if="denuncia.oferta">
            <h3 class="font-semibold text-gray-700 mb-2">Información de la Oferta</h3>
            <div class="space-y-2">
              <p><strong>ID Oferta:</strong> {{ denuncia.oferta.id_oferta }}</p>
              <p><strong>Estado:</strong> {{ denuncia.oferta.estado_oferta }}</p>
              <p><strong>Permisos:</strong></p>
              <ul class="list-disc list-inside ml-2">
                <li>Historial médico: {{ denuncia.oferta.permiso_historial_medico ? 'Sí' : 'No' }}</li>
                <li>Contacto tutor: {{ denuncia.oferta.permiso_contacto_tutor ? 'Sí' : 'No' }}</li>
              </ul>
            </div>
          </div>

          <!-- Notas del administrador -->
          <div v-if="denuncia.detalles?.notas_admin">
            <h3 class="font-semibold text-gray-700 mb-2">Notas del Administrador</h3>
            <div class="bg-yellow-50 p-3 rounded-lg border border-yellow-200">
              {{ denuncia.detalles.notas_admin }}
            </div>
            <p class="text-sm text-gray-500 mt-1" v-if="denuncia.detalles.resuelta_en">
              Resuelta el: {{ formatFechaCompleta(denuncia.detalles.resuelta_en) }}
            </p>
          </div>

          <!-- Fechas -->
          <div class="text-sm text-gray-500 space-y-1">
            <p v-if="denuncia.detalles?.creada">
              <strong>Creada:</strong> {{ formatFechaCompleta(denuncia.detalles.creada) }}
            </p>
            <p v-if="denuncia.detalles?.actualizada">
              <strong>Actualizada:</strong> {{ formatFechaCompleta(denuncia.detalles.actualizada) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Acciones -->
      <div class="pt-4 border-t flex justify-end space-x-3">
        <button
          v-if="denuncia.estado === 'pendiente'"
          @click="cancelarDenuncia"
          class="bg-red-100 text-red-700 hover:bg-red-200 font-medium px-4 py-2 rounded-lg transition-colors duration-150"
        >
          Cancelar Denuncia
        </button>
        <button
          @click="mostrarSancionarUsuario = true"
          class="bg-blue-100 text-green-700 hover:bg-blue-200 font-medium px-4 py-2 rounded-lg transition-colors duration-150"
        >
          Sancionar Usuario
        </button>
      </div>
    </div>

    <!-- Modal para sancionar usuario -->
    <SancionarUsuario
      v-if="mostrarSancionarUsuario"
      :denuncia-id="denuncia.id"
      :usuario-denunciado="denuncia.usuario_denunciado"
      :gravedad="denuncia.gravedad"
      @cerrar="mostrarSancionarUsuario = false"
      @sancion-aplicada="onSancionAplicada"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import { useAuth } from '@/composables/useAuth'
import SancionarUsuario from '@/components/módulo_administrador/SancionarUsuario.vue'

const props = defineProps({
  denuncia: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['volver', 'actualizar'])
const { accessToken } = useAuth()

const mostrarSancionarUsuario = ref(false)

// Computed para clases de estado
const estadoClase = computed(() => {
  const clases = {
    'pendiente': 'bg-yellow-100 text-yellow-800',
    'en_revision': 'bg-blue-100 text-blue-800',
    'resuelta': 'bg-green-100 text-green-800',
    'descarta': 'bg-gray-100 text-gray-800'
  }
  return clases[props.denuncia.estado] || 'bg-gray-100 text-gray-800'
})

// Computed para clases de gravedad
const gravedadClase = computed(() => {
  const clases = {
    'Alta': 'bg-red-100 text-red-800',
    'Media': 'bg-yellow-100 text-yellow-800',
    'Baja': 'bg-green-100 text-green-800'
  }
  return clases[props.denuncia.gravedad] || 'bg-gray-100 text-gray-800'
})

// Formatear estado
const formatEstado = (estado) => {
  const estados = {
    'pendiente': 'Pendiente',
    'en_revision': 'En revisión',
    'resuelta': 'Resuelta',
    'descarta': 'Descartada'
  }
  return estados[estado] || estado
}

// Formatear fecha completa
const formatFechaCompleta = (fechaStr) => {
  if (!fechaStr) return ''
  const date = new Date(fechaStr)
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Cancelar denuncia
const cancelarDenuncia = async () => {
  if (!confirm('¿Estás seguro de que quieres cancelar esta denuncia?')) {
    return
  }
  
  try {
    console.log('Cancelando denuncia:', props.denuncia.id)
    alert('Funcionalidad de cancelación en desarrollo')
  } catch (error) {
    console.error('Error cancelando denuncia:', error)
    alert('Error al cancelar la denuncia')
  }
}

// Cuando se aplica una sanción
const onSancionAplicada = () => {
  // Cerrar modal
  mostrarSancionarUsuario.value = false
  
  // Opcional: mostrar mensaje de éxito
  alert('Sanción aplicada correctamente')
  
  // Opcional: emitir evento para actualizar la denuncia
  emit('actualizar')
}
</script>

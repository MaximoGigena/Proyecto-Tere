<template>
  <!-- Backdrop con z-index más alto -->
  <div
    class="fixed inset-0 z-50"
    @click.self="handleClose"
  >
    <!-- Panel -->
    <div
      class="absolute top-16 right-4 w-96 max-h-[70vh]
             bg-white rounded-2xl shadow-2xl
             overflow-y-auto p-4"
      @click.stop
    >
      <!-- Encabezado -->
      <div class="flex justify-between items-center mb-4">
        <div>
          <h3 class="text-lg font-semibold">Notificaciones</h3>
          <div v-if="estadisticas" class="text-xs text-gray-500">
            {{ estadisticas.no_leidas }} sin leer de {{ estadisticas.total }}
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            v-if="estadisticas?.no_leidas > 0"
            @click="marcarTodasComoLeidas"
            class="text-xs text-blue-600 hover:text-blue-800 px-2 py-1 rounded hover:bg-blue-50"
            :disabled="marcandoTodas"
          >
            {{ marcandoTodas ? 'Marcando...' : 'Marcar todas como leídas' }}
          </button>
          <button
            @click="handleClose"
            class="text-gray-500 hover:text-gray-700 p-1 rounded-full hover:bg-gray-100"
          >
            <font-awesome-icon :icon="['fas', 'times']" />
          </button>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>

      <!-- Cargando -->
      <div v-if="cargando" class="text-center py-8">
        <font-awesome-icon :icon="['fas', 'spinner']" class="animate-spin text-blue-500 text-xl" />
        <p class="text-sm text-gray-500 mt-2">Cargando notificaciones...</p>
      </div>

      <!-- Vacío -->
      <div v-else-if="notificaciones.length === 0"
           class="text-gray-500 text-sm text-center py-8">
        <font-awesome-icon :icon="['far', 'bell']" class="text-3xl mb-3 text-gray-300" />
        <p>No tienes notificaciones</p>
      </div>

      <!-- Lista -->
      <div v-else>
        <div
          v-for="n in notificaciones"
          :key="n.id"
          class="p-3 mb-2 rounded-xl border flex gap-3
                 cursor-pointer transition-colors relative"
          :class="[
            n.leida ? 'bg-white' : 'bg-blue-50',
            n.tipo === 'ADVERTENCIA' || n.tipo === 'SANCION'
              ? 'border-red-200 hover:bg-red-50'
              : 'border-gray-200 hover:bg-gray-50'
          ]"
          @click="toggleExpand(n.id)"
        >
          <!-- Indicador no leída -->
          <div v-if="!n.leida" class="absolute top-2 right-2">
            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
          </div>

          <!-- Icono -->
          <font-awesome-icon
            :icon="icono(n.tipo)"
            class="text-lg mt-1 flex-shrink-0"
            :class="iconoColor(n.tipo)"
          />

          <!-- Contenido -->
          <div class="flex-1 min-w-0">
            <!-- Header -->
            <div class="flex justify-between items-start">
              <p class="font-medium text-sm"
                 :class="{ 'text-gray-600': n.leida }">
                {{ n.titulo }}
              </p>

              <!-- Flecha -->
              <button
                class="ml-2 text-gray-400 hover:text-gray-600"
                @click.stop="toggleExpand(n.id)"
              >
                <font-awesome-icon
                  :icon="estaExpandida(n.id)
                    ? ['fas', 'chevron-down']
                    : ['fas', 'chevron-right']"
                />
              </button>
            </div>

            <!-- Contenido resumido -->
            <p
              v-if="!estaExpandida(n.id)"
              class="text-sm text-gray-600 mt-1 line-clamp-2"
            >
              {{ n.contenido }}
            </p>

            <!-- Contenido completo -->
            <div
              v-else
              class="mt-2 text-sm text-gray-700 whitespace-pre-line"
            >
              {{ n.contenido }}
            </div>

            <!-- Info adicional -->
            <div class="flex justify-between items-center mt-3">
              <div class="flex items-center gap-2">
                <span
                  class="text-xs px-2 py-1 rounded-full"
                  :class="badgeColor(n.tipo)"
                >
                  {{ n.tipo_formateado || n.tipo }}
                </span>
                <span v-if="n.origen_formateado" class="text-xs text-gray-400">
                  {{ n.origen_formateado }}
                </span>
              </div>
              <span class="text-xs text-gray-400">
                {{ formatFecha(n.created_at) }}
              </span>
            </div>

            <!-- Acciones -->
            <div
              v-if="estaExpandida(n.id)"
              class="mt-3 flex justify-between items-center"
            >
              <div class="flex gap-2">
                <button
                  v-if="!n.leida"
                  @click.stop="marcarComoLeida(n.id)"
                  class="text-xs text-gray-400 hover:text-gray-600"
                >
                  <font-awesome-icon :icon="['far', 'check-circle']" />
                </button>
                <button
                  @click.stop="eliminarNotificacion(n.id)"
                  class="text-xs text-gray-400 hover:text-red-600"
                >
                  <font-awesome-icon :icon="['far', 'trash-can']" />
                </button>
              </div>

              <button
                @click.stop="handleNotificationClick(n)"
                class="text-xs text-blue-600 hover:text-blue-800"
              >
                Ver detalle
              </button>
            </div>
          </div>
        </div>

        <!-- Cargar más -->
        <div v-if="hasMore" class="text-center mt-4">
          <button
            @click="cargarMas"
            :disabled="cargandoMas"
            class="text-sm text-blue-600 hover:text-blue-800 px-4 py-2
                   rounded-lg hover:bg-blue-50 disabled:opacity-50"
          >
            {{ cargandoMas ? 'Cargando...' : 'Cargar más notificaciones' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import useNotificaciones, { type Notificacion } from '@/composables/useNotificaciones';

const router = useRouter();
const emit = defineEmits(['close']);

/* =====================================================
   Accordion / Expand
===================================================== */
const notificacionExpandidaId = ref<number | null>(null);

const toggleExpand = async (id: number) => {
  // Buscar la notificación
  const notificacion = notificaciones.value.find(n => n.id === id);
  
  // Si la notificación existe y no está leída, marcarla como leída
  if (notificacion && !notificacion.leida) {
    try {
      await marcarComoLeida(id);
      
      // Actualizar la notificación localmente
      const index = notificaciones.value.findIndex(n => n.id === id);
      if (index !== -1) {
        notificaciones.value[index] = {
          ...notificaciones.value[index],
          leida: true,
          fecha_lectura: new Date().toISOString()
        };
      }
      
      // Actualizar estadísticas
      if (estadisticas.value && estadisticas.value.no_leidas > 0) {
        estadisticas.value.no_leidas--;
      }
    } catch (error) {
      console.error('Error al marcar como leída:', error);
    }
  }
  
  // Expandir/contraer
  notificacionExpandidaId.value =
    notificacionExpandidaId.value === id ? null : id;
};

const estaExpandida = (id: number) =>
  notificacionExpandidaId.value === id;

/* =====================================================
   Composable
===================================================== */
const {
  notificaciones,
  estadisticas,
  cargando,
  cargandoMas,
  marcandoTodas,
  error,
  hasMore,
  cargarNotificaciones,
  cargarMas,
  marcarComoLeida,
  marcarTodasComoLeidas,
  eliminarNotificacion
} = useNotificaciones();

/* =====================================================
   Iconos y estilos
===================================================== */
const icono = (tipo: string) => {
  const map: Record<string, [string, string]> = {
    ADVERTENCIA: ['fas', 'triangle-exclamation'],
    SANCION: ['fas', 'ban'],
    SISTEMA: ['fas', 'server'],
    MENSAJE: ['fas', 'envelope'],
    PROCEDIMIENTO: ['fas', 'clipboard-list'],
    DENUNCIA: ['fas', 'flag'],
    OFERTA: ['fas', 'paw'],
    SOLICITUD: ['fas', 'handshake'],
    ALERTA: ['fas', 'bell']
  };
  return map[tipo] || ['fas', 'bell'];
};

const iconoColor = (tipo: string) => {
  if (tipo === 'ADVERTENCIA' || tipo === 'SANCION') return 'text-red-500';
  if (tipo === 'OFERTA') return 'text-green-500';
  if (tipo === 'MENSAJE') return 'text-blue-500';
  return 'text-gray-500';
};

const badgeColor = (tipo: string) => {
  const colors: Record<string, string> = {
    ADVERTENCIA: 'bg-red-100 text-red-800',
    SANCION: 'bg-red-100 text-red-800',
    OFERTA: 'bg-green-100 text-green-800',
    MENSAJE: 'bg-blue-100 text-blue-800',
    DENUNCIA: 'bg-orange-100 text-orange-800',
    SISTEMA: 'bg-gray-100 text-gray-800'
  };
  return colors[tipo] || 'bg-gray-100 text-gray-800';
};

/* =====================================================
   Fecha
===================================================== */
const formatFecha = (f: string) => {
  if (!f) return '';
  const date = new Date(f);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

  if (diffDays === 0) {
    return date.toLocaleTimeString('es-AR', {
      hour: '2-digit',
      minute: '2-digit'
    });
  } else if (diffDays === 1) {
    return 'Ayer';
  } else if (diffDays < 7) {
    return date.toLocaleDateString('es-AR', { weekday: 'short' });
  } else {
    return date.toLocaleDateString('es-AR', {
      day: '2-digit',
      month: 'short'
    });
  }
};

/* =====================================================
   Navegación (Ver detalle)
===================================================== */
const handleNotificationClick = async (notificacion: Notificacion) => {
  if (!notificacion.leida) {
    await marcarComoLeida(notificacion.id);
  }

  if (notificacion.referencia_tipo && notificacion.referencia_id) {
    switch (notificacion.referencia_tipo) {
      case 'sancion':
        router.push(`/sanciones/${notificacion.referencia_id}`);
        break;
      case 'denuncia':
        router.push(`/denuncias/${notificacion.referencia_id}`);
        break;
      case 'mascota':
        router.push(`/mascotas/${notificacion.referencia_id}`);
        break;
      case 'usuario':
        router.push(`/perfil/${notificacion.referencia_id}`);
        break;
      default:
        console.warn(
          'Tipo de referencia no manejado:',
          notificacion.referencia_tipo
        );
    }
  }

  emit('close');
};

/* =====================================================
   Modal
===================================================== */
const handleClose = () => {
  emit('close');
};

/* =====================================================
   Lifecycle
===================================================== */
onMounted(() => {
  cargarNotificaciones();
});

let intervaloRefresco: number;

onMounted(() => {
  intervaloRefresco = window.setInterval(() => {
    if (!cargando) {
      cargarNotificaciones(1, false);
    }
  }, 30000);
});

onUnmounted(() => {
  if (intervaloRefresco) {
    clearInterval(intervaloRefresco);
  }
});
</script>


<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

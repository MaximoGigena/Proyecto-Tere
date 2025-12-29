import { ref, computed } from 'vue';
import api from '@/services/api';

export interface Notificacion {
  id: number;
  usuario_id: number;
  tipo: string;
  titulo: string;
  contenido: string;
  origen: string;
  referencia_tipo: string | null;
  referencia_id: number | null;
  leida: boolean;
  fecha_lectura: string | null;
  activa: boolean;
  created_at: string;
  updated_at: string;
  tipo_formateado?: string;
  origen_formateado?: string;
}

export interface NotificacionesResponse {
  success: boolean;
  data: {
    data: Notificacion[];
    current_page: number;
    last_page: number;
    total: number;
  };
}

export interface Estadisticas {
  total: number;
  no_leidas: number;
  por_tipo: Record<string, number>;
}

export default function useNotificaciones() {
  // Estado reactivo
  const notificaciones = ref<Notificacion[]>([]);
  const estadisticas = ref<Estadisticas | null>(null);
  const cargando = ref(false);
  const cargandoMas = ref(false);
  const marcandoTodas = ref(false);
  const currentPage = ref(1);
  const lastPage = ref(1);
  const error = ref<string | null>(null);

  // Computed
  const hasMore = computed(() => currentPage.value < lastPage.value);
  const totalNoLeidas = computed(() => estadisticas.value?.no_leidas || 0);

  // Métodos
  const cargarNotificaciones = async (page = 1, loadMore = false) => {
    if (loadMore) {
      cargandoMas.value = true;
    } else {
      cargando.value = true;
    }
    
    error.value = null;

    try {
      const params = {
        page,
        per_page: 10,
        leida: false // Primero las no leídas
      };

      const response = await api.get<NotificacionesResponse>('/notificaciones', { params });
      
      if (loadMore) {
        notificaciones.value = [...notificaciones.value, ...response.data.data.data];
      } else {
        notificaciones.value = response.data.data.data;
      }
      
      currentPage.value = response.data.data.current_page;
      lastPage.value = response.data.data.last_page;
      
      // Cargar estadísticas solo si es la primera página
      if (!loadMore) {
        await cargarEstadisticas();
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al cargar notificaciones';
      console.error('Error al cargar notificaciones:', err);
    } finally {
      cargando.value = false;
      cargandoMas.value = false;
    }
  };

  const cargarMas = async () => {
    if (hasMore.value && !cargandoMas.value) {
      await cargarNotificaciones(currentPage.value + 1, true);
    }
  };

  const cargarEstadisticas = async () => {
    try {
      const response = await api.get<{ success: boolean; data: Estadisticas }>('/notificaciones/estadisticas');
      estadisticas.value = response.data.data;
    } catch (err: any) {
      console.error('Error al cargar estadísticas:', err);
    }
  };

  const marcarComoLeida = async (id: number) => {
    try {
      // CAMBIA ESTO: usa PUT en lugar de POST
      await api.put(`/notificaciones/${id}/leer`);
      
      // Actualizar localmente
      const index = notificaciones.value.findIndex(n => n.id === id);
      if (index !== -1) {
        // IMPORTANTE: Crea un nuevo array para reactividad
        const updatedNotifications = [...notificaciones.value];
        updatedNotifications[index] = {
          ...updatedNotifications[index],
          leida: true,
          fecha_lectura: new Date().toISOString()
        };
        notificaciones.value = updatedNotifications;
        
        // Actualizar estadísticas
        if (estadisticas.value && estadisticas.value.no_leidas > 0) {
          estadisticas.value.no_leidas--;
        }
      }
      
      return true;
    } catch (err: any) {
      console.error('Error al marcar notificación como leída:', err);
      throw err;
    }
  };
  
  const marcarTodasComoLeidas = async () => {
    if (marcandoTodas.value) return;
    
    marcandoTodas.value = true;
    try {
      await api.post('/notificaciones/marcar-todas-leidas');
      
      // Actualizar localmente
      notificaciones.value.forEach(n => {
        n.leida = true;
      });
      
      // Actualizar estadísticas
      if (estadisticas.value) {
        estadisticas.value.no_leidas = 0;
      }
    } catch (err: any) {
      console.error('Error al marcar todas como leídas:', err);
      throw err;
    } finally {
      marcandoTodas.value = false;
    }
  };

  const eliminarNotificacion = async (id: number) => {
    try {
      await api.delete(`/notificaciones/${id}`);
      
      // Eliminar localmente
      const notificacionEliminada = notificaciones.value.find(n => n.id === id);
      notificaciones.value = notificaciones.value.filter(n => n.id !== id);
      
      // Actualizar estadísticas
      if (estadisticas.value && notificacionEliminada) {
        estadisticas.value.total--;
        if (!notificacionEliminada.leida) {
          estadisticas.value.no_leidas--;
        }
      }
    } catch (err: any) {
      console.error('Error al eliminar notificación:', err);
      throw err;
    }
  };

  const refrescar = async () => {
    await cargarNotificaciones(1, false);
  };

  return {
    // Estado
    notificaciones,
    estadisticas,
    cargando,
    cargandoMas,
    marcandoTodas,
    error,
    currentPage,
    lastPage,
    
    // Computed
    hasMore,
    totalNoLeidas,
    
    // Métodos
    cargarNotificaciones,
    cargarMas,
    marcarComoLeida,
    marcarTodasComoLeidas,
    eliminarNotificacion,
    refrescar
  };
}
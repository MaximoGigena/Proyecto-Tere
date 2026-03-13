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
        per_page: 10
      };

      const response = await api.get('/notificaciones', { params });
      
      // Verificar la estructura de la respuesta y extraer las notificaciones
      let notificacionesData: Notificacion[] = [];
      
      if (response.data?.data?.notificaciones && Array.isArray(response.data.data.notificaciones)) {
        // Estructura: { data: { notificaciones: [...], paginacion: {...} } }
        notificacionesData = response.data.data.notificaciones;
        currentPage.value = response.data.data.paginacion?.current_page || page;
        lastPage.value = response.data.data.paginacion?.last_page || 1;
      } else if (response.data?.data?.data && Array.isArray(response.data.data.data)) {
        // Estructura: { data: { data: [...], current_page, last_page } } (paginación de Laravel)
        notificacionesData = response.data.data.data;
        currentPage.value = response.data.data.current_page || page;
        lastPage.value = response.data.data.last_page || 1;
      } else if (Array.isArray(response.data?.data)) {
        // Estructura: { data: [...] }
        notificacionesData = response.data.data;
        currentPage.value = page;
        lastPage.value = 1;
      } else if (Array.isArray(response.data)) {
        // Estructura: [...] (directo)
        notificacionesData = response.data;
        currentPage.value = page;
        lastPage.value = 1;
      }
      
      if (loadMore) {
        notificaciones.value = [...notificaciones.value, ...notificacionesData];
      } else {
        notificaciones.value = notificacionesData;
      }
      
      // Cargar estadísticas solo si es la primera página
      if (!loadMore) {
        await cargarEstadisticas();
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al cargar notificaciones';
      console.error('Error al cargar notificaciones:', err);
      // Asegurar que notificaciones sea siempre un array
      if (!loadMore) {
        notificaciones.value = [];
      }
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
      const response = await api.get('/notificaciones/estadisticas');
      if (response.data?.success && response.data?.data) {
        estadisticas.value = response.data.data;
      } else if (response.data?.data) {
        estadisticas.value = response.data.data;
      }
    } catch (err: any) {
      console.error('Error al cargar estadísticas:', err);
      // No propagar el error para no bloquear la UI
    }
  };

  const marcarComoLeida = async (id: number) => {
    try {
      // Intentar con PUT primero, si falla probar con POST
      try {
        await api.put(`/notificaciones/${id}/leer`);
      } catch (putError) {
        // Si PUT falla, intentar con POST como fallback
        await api.post(`/notificaciones/${id}/leer`);
      }
      
      // Actualizar localmente
      const index = notificaciones.value.findIndex(n => n.id === id);
      if (index !== -1) {
        // Crear un nuevo array para reactividad
        const updatedNotifications = [...notificaciones.value];
        updatedNotifications[index] = {
          ...updatedNotifications[index],
          leida: true,
          fecha_lectura: new Date().toISOString()
        };
        notificaciones.value = updatedNotifications;
        
        // Actualizar estadísticas
        if (estadisticas.value && estadisticas.value.no_leidas > 0) {
          estadisticas.value = {
            ...estadisticas.value,
            no_leidas: estadisticas.value.no_leidas - 1
          };
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
      
      // Actualizar localmente - crear nuevo array
      notificaciones.value = notificaciones.value.map(n => ({
        ...n,
        leida: true,
        fecha_lectura: n.fecha_lectura || new Date().toISOString()
      }));
      
      // Actualizar estadísticas
      if (estadisticas.value) {
        estadisticas.value = {
          ...estadisticas.value,
          no_leidas: 0
        };
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
      
      // Guardar la notificación eliminada para actualizar estadísticas
      const notificacionEliminada = notificaciones.value.find(n => n.id === id);
      
      // Eliminar localmente - crear nuevo array
      notificaciones.value = notificaciones.value.filter(n => n.id !== id);
      
      // Actualizar estadísticas
      if (estadisticas.value && notificacionEliminada) {
        estadisticas.value = {
          ...estadisticas.value,
          total: estadisticas.value.total - 1,
          no_leidas: notificacionEliminada.leida 
            ? estadisticas.value.no_leidas 
            : estadisticas.value.no_leidas - 1
        };
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
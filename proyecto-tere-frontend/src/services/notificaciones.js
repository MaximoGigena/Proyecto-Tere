import api from '@/services/api';

export const notificacionesService = {
  // Obtener todas las notificaciones
  async getNotificaciones(params = {}) {
    try {
      const response = await api.get('/notificaciones', { params });
      return response.data;
    } catch (error) {
      console.error('Error al obtener notificaciones:', error);
      throw error;
    }
  },

  // Marcar una notificación como leída
  async marcarComoLeida(id) {
    try {
      const response = await api.post(`/notificaciones/${id}/leer`);
      return response.data;
    } catch (error) {
      console.error('Error al marcar notificación como leída:', error);
      throw error;
    }
  },

  // Marcar todas como leídas
  async marcarTodasComoLeidas() {
    try {
      const response = await api.post('/notificaciones/marcar-todas-leidas');
      return response.data;
    } catch (error) {
      console.error('Error al marcar todas las notificaciones como leídas:', error);
      throw error;
    }
  },

  // Eliminar una notificación
  async eliminarNotificacion(id) {
    try {
      const response = await api.delete(`/notificaciones/${id}`);
      return response.data;
    } catch (error) {
      console.error('Error al eliminar notificación:', error);
      throw error;
    }
  },

  // Obtener estadísticas
  async getEstadisticas() {
    try {
      const response = await api.get('/notificaciones/estadisticas');
      return response.data;
    } catch (error) {
      console.error('Error al obtener estadísticas:', error);
      throw error;
    }
  }
};
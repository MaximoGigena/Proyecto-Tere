import axios, {
  AxiosInstance,
  AxiosResponse,
  InternalAxiosRequestConfig
} from 'axios';

// Configuración base de axios
const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
});

// Interceptor para agregar token de autenticación
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token');
    if (token) {
      config.headers = config.headers || {};
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor para manejar respuestas
api.interceptors.response.use(
  (response: AxiosResponse) => response,
  async (error) => {
    const originalRequest = error.config;
    const currentPath = window.location.pathname;
    
    // Si es error 403 (cuenta suspendida)
    if (error.response?.status === 403) {
      const data = error.response.data;
      
      // Verificar si es un error de cuenta suspendida
      const isSuspended = data.message && data.message.includes('suspend') || 
          data.error && data.error.includes('suspend') ||
          data.success === false && data.data?.esta_suspendido ||
          data.data?.estado === 'suspendido' || // Añade esta línea
          data.estado === 'suspendido'; // Y esta línea
      
      if (isSuspended) {
        
        // Guardar datos de suspensión en localStorage
        if (data.data) {
          localStorage.setItem('suspension_data', JSON.stringify(data.data));
        } else {
          // Guardar información básica si no hay data.data
          localStorage.setItem('suspension_data', JSON.stringify({
            razon: data.message || 'Cuenta suspendida',
            estado: 'suspendido'
          }));
        }
        
        // EVITA REDIRECCIÓN EN EL INTERCEPTOR
        // En su lugar, lanza un error específico
        return Promise.reject({
          ...error,
          isSuspended: true,
          suspensionData: data.data || { razon: data.message }
        });
      }
    }
    
    // Manejar errores 401 (no autorizado)
    if (error.response?.status === 401) {
      // Token expirado o no autenticado
      localStorage.removeItem('token');
      localStorage.removeItem('auth_token');
      localStorage.removeItem('suspension_data');
      
      if (currentPath !== '/') {
        window.location.href = '/';
      }
    }
    
    // Manejar error 419 (CSRF token expirado)
    if (error.response?.status === 419) {
      // Token CSRF expirado (Laravel Sanctum)
      try {
        await api.post('/sanctum/csrf-cookie');
        return api(originalRequest);
      } catch (refreshError) {
        return Promise.reject(refreshError);
      }
    }
    
    return Promise.reject(error);
  }
);

export default api;
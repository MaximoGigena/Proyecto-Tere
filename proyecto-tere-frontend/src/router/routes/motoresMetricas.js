// router/index.js
import MetricasDashboard from '@/components/módulo_administrador/metricas.vue' // Tu vista principal
import MetricasUsuario from '@/components/módulo_administrador/MotoresMetricas/metricasUsuario.vue'
import MetricasMascotas from '@/components/módulo_administrador/MotoresMetricas/motorMascotas.vue'
import MetricasAdopciones from '@/components/módulo_administrador/MotoresMetricas/motorAdopciones.vue'
import MetricasVeterinarios from '@/components/módulo_administrador/MotoresMetricas/motorVeterinarios.vue'
import MetricasSeguimientoMedico from '@/components/módulo_administrador/MotoresMetricas/motorSeguimientoMedico.vue'
import MetricasActividadSistema from '@/components/módulo_administrador/MotoresMetricas/motorActividadSistema.vue'

export const MotoresMetricas = [
  {
    path: '/metricas',
    name: 'MetricasDashboard',
    component: MetricasDashboard, // Esta es tu vista principal
    meta: {
      requiresAuth: true,
      title: 'Dashboard de Métricas'
    }
  },
  {
    path: '/metricas/usuarios',
    name: 'MetricasUsuarios',
    component: MetricasUsuario,
    meta: {
      requiresAuth: true,
      title: 'Métricas de Usuarios'
    }
  },
  {
    path: '/metricas/mascotas',
    name: 'MetricasMascotas',
    component: MetricasMascotas,
    meta: {
      requiresAuth: true,
      title: 'Métricas de Mascotas'
    }
  },
  {
    path: '/metricas/veterinarios',
    name: 'MetricasVeterinarios',
    component: MetricasVeterinarios,
    meta: {
      requiresAuth: true,
      title: 'Métricas de Veterinarios'
    }
  },
  {
    path: '/metricas/adopciones',
    name: 'MetricasAdopciones',
    component: MetricasAdopciones,
    meta: {
      requiresAuth: true,
      title: 'Métricas de Adopciones'
    }
  },
  {
    path: '/metricas/seguimiento-medico',
    name: 'MetricasSeguimientoMedico',
    component: MetricasSeguimientoMedico,
    meta: {
      requiresAuth: true,
      title: 'Métricas de Seguimiento Médico'
    }
  },
  {
    path: '/metricas/actividad-sistema',
    name: 'MetricasActividadSistema',
    component: MetricasActividadSistema,
    meta: {
      requiresAuth: true,
      title: 'Métricas de Actividad del Sistema'
    }
  }
]
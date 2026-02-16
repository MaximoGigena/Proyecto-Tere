// Importar los componentes
import MetricasUsuario from '@/components/módulo_administrador/MotoresMetricas/metricasUsuario.vue'
import MetricasUsuarioContenido from '@/components/módulo_administrador/MotoresMetricas/motorUsuarios.vue'
import ReportesUsuarioContenido from '@/components/módulo_administrador/MotoresMetricas/reportesMetricasUsuario.vue'

export const motorMetricasUsuarios = [
  {
    path: '/metricas-usuarios',
    component: MetricasUsuario,
    children: [
      {
        path: '',
        name: 'MetricasDashboard',
        redirect: { name: 'MetricasVolumen' }
      },
      {
        path: 'volumen',
        name: 'MetricasVolumen',
        component: MetricasUsuarioContenido,
        props: { reporteInicial: 'volumen' }
      },
      {
        path: 'crecimiento',
        name: 'MetricasCrecimiento',
        component: MetricasUsuarioContenido,
        props: { reporteInicial: 'crecimiento' }
      },
      {
        path: 'actividad',
        name: 'MetricasActividad',
        component: MetricasUsuarioContenido,
        props: { reporteInicial: 'actividad' }
      },
      {
        path: 'comportamiento',
        name: 'MetricasComportamiento',
        component: MetricasUsuarioContenido,
        props: { reporteInicial: 'comportamiento' }
      },
      {
        path: 'calidad',
        name: 'MetricasCalidad',
        component: MetricasUsuarioContenido,
        props: { reporteInicial: 'calidad' }
      },
      {
        path: 'reportes',
        name: 'MetricasReportes',
        component: ReportesUsuarioContenido
      }
    ]
  }
]
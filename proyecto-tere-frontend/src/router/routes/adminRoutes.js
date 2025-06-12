// adminRoutes.js
import navBarAdmin from '@/components/módulo_administrador/navBarAdmin.vue'
import Reportes from '@/components/módulo_administrador/denuncias.vue'
import Metricas from '@/components/módulo_administrador/metricas.vue'
import Auditorias from '@/components/módulo_administrador/auditorias.vue'
import Solicitudes from '@/components/módulo_administrador/solicitudes.vue'

export const adminRoutes = [
  {
    path: '/admin',
    component: navBarAdmin, // padre
    children: [
      {
        path: 'reportes',
        component: Reportes,
        name: 'adminReportes',
      },
      {
        path: 'metricas',
        component: Metricas,
        name: 'adminMetricas',
      },
      {
        path: 'auditorias',
        component: Auditorias,
        name: 'adminAuditorias',
      },
      {
        path: 'solicitudes',
        component: Solicitudes,
        name: 'adminSolicitudes',
      },
      {
        path: '',
        redirect: 'reportes',
      }
    ],
  },
]

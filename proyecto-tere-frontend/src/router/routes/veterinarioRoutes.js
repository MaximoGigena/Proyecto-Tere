// adminRoutes.js
import navBarVeterinario from '@/components/módulo_veterinario/navBarVeterinario.vue'
import Busqueda from '@/components/módulo_veterinario/sideBarMascotas.vue'
import Tipos from '@/components/módulo_veterinario/gestionarTipos.vue'
import Perfil from '@/components/módulo_veterinario/perfilVeterinario.vue'
import Notificaciones from '@/components/módulo_veterinario/veterinarioNotificaciones.vue'
import Historial from '@/components/módulo_veterinario/historialTratamientos.vue'
import Rendimiento from '@/components/módulo_veterinario/rendimientoVeterinario.vue'

export const veterinarioRoutes = [
  {
    path: '/veterinarios',
    component: navBarVeterinario, // padre
    children: [
      {
        path: 'busqueda',
        component: Busqueda,
        name: 'veterinarioBusqueda',
      },
      {
        path: 'historial',
        component: Historial,
        name: 'veterinarioHistorial',
      },
      {
        path: 'rendimiento',
        component: Rendimiento,
        name: 'veterinarioRendimiento',
      },
      {
        path: 'tipos',
        component: Tipos,
        name: 'veterinarioTipos',
      },
      {
        path: 'perfil',
        component: Perfil,
        name: 'veterinarioPerfil',
      },
      {
        path: 'notificaciones',
        component: Notificaciones,
        name: 'veterinarioNotificaciones',
      },
      {
        path: '',
        redirect: 'reportes',
      }
    ],
  },
]

// router/rutasUsuario.js
import ExplorarEncuentros from '@/components/módulo_usuario/ExplorarEncuentros.vue'
import PerfilUsuario from '@/components/módulo_usuario/perfilUsuario.vue'
import PerfilMascotas from '@/components/módulo_usuario/mascotasUsuario.vue'
import PerfilAdopciones from '@/components/módulo_usuario/gestionarAdopciones.vue'

export const rutasUsuario = [
  {
    path: '/explorar',
    component: ExplorarEncuentros,
    children: [
      {
        path: 'perfil',
        component: PerfilUsuario,
        redirect: '/explorar/perfil/mascotas', // Redirección aquí también
        children: [
          {
            path: 'mascotas',
            component: PerfilMascotas,
            meta: { activeTab: 'mascotas' } // Meta información útil
          },
          {
            path: 'adopciones',
            component: PerfilAdopciones,
            meta: { activeTab: 'adopciones' }
          },
          {
            path: '', // Ruta vacía
            redirect: '/explorar/perfil/mascotas' // Redirección doble garantizada
          }
        ]
      }
    ]
  }
]
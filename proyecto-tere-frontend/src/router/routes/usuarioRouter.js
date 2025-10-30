// router/rutasUsuario.js
import ExplorarEncuentros from '@/components/módulo_usuario/ExplorarEncuentros.vue'
import PerfilUsuario from '@/components/módulo_usuario/perfilUsuario.vue'
import PerfilMascotas from '@/components/módulo_mascotas/mascotasUsuario.vue'
import PerfilAdopciones from '@/components/módulo_adopciones/gestionarAdopciones.vue'

export const rutasUsuario = [
  {
    path: '/explorar',
    component: ExplorarEncuentros,
    children: [
      {
        path: 'perfil',
        component: PerfilUsuario,
        redirect: '/explorar/perfil/mascotas', // 👈 Redirige directo a mascotas
        children: [
          {
            path: 'mascotas',
            name: 'mis-mascotas',
            component: PerfilMascotas,
            meta: { activeTab: 'mascotas' }
          },
          {
            path: 'adopciones',
            component: PerfilAdopciones,
            meta: { activeTab: 'adopciones' }
          }
        ]
      }
    ]
  }
]

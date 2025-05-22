// Importa las rutas
import PerfilUsuario from '@/components/perfilUsuario.vue'
import contenidoMascota from '@/components/contenidoMascota.vue'
import ExplorarEncuentros from '@/components/ExplorarEncuentros.vue' 

export const perfilUsuario = [
  {
    path: '/explorar',
    component: ExplorarEncuentros,
    children: [
      {
        path: 'perfil',
        component: PerfilUsuario
      },
      {
        path: 'perfil/mascota/:id',
        components: {
          default: PerfilUsuario,
          overlay: contenidoMascota
        },
        props: {
          overlay: true
        }
      }
    ]
  }
]

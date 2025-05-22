// Importa las rutas
import PerfilUsuario from '@/components/perfilUsuario.vue'
import contenidoMascota from '@/components/contenidoMascota.vue'
import ExplorarEncuentros from '@/components/ExplorarEncuentros.vue' 
import historialMedico from '@/components/historialMedico.vue'

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
        name: 'perfil-Mascota', 
        components: {
          default: PerfilUsuario,
          overlay: contenidoMascota
        },
        props: {
          overlay: true
        },
        children: [
          {
            path: 'historial',
            component: historialMedico,
            props: true
          },
          // Podés agregar más subvistas si querés:
          // { path: 'galeria', component: galeriaFotos, props: true }
        ]
      }
    ]
  }
]

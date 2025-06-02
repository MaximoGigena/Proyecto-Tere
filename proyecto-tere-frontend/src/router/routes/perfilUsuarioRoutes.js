// Importa las rutas
import PerfilUsuario from '@/components/módulo_usuario/perfilUsuario.vue'
import contenidoMascota from '@/components/módulo_mascotas/contenidoMascota.vue'
import ExplorarEncuentros from '@/components/módulo_usuario/ExplorarEncuentros.vue' 
import historialMedico from '@/components/módulo_mascotas/historialMedico.vue'

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

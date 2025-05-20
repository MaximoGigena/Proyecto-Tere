import { createRouter, createWebHistory } from 'vue-router'
import PerfilUsuario from '@/components/perfilUsuario.vue'
import ContenidoMascota from '@/components/contenidoMascota.vue'
import ContenedorPrincipal from '@/components/ExplorarEncuentros.vue' 

const routes = [
  {
    path: '/explorar/perfil',
  name: 'perfil',
  component: ContenedorPrincipal,
  children: [
      {
        path: '', // ruta base: /explorar/perfil
        component: PerfilUsuario
      },
      {
        path: 'mascota/:id', // 👈 hijo: /explorar/perfil/mascota/:id
        name: 'mascota-detalle',
        components: {
          default: PerfilUsuario,
          overlay: ContenidoMascota
        },
        props: {
          default: false,
          overlay: true
        }
      }
    ]
  }
]



const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
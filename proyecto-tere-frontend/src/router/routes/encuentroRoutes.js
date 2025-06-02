// routes/encuentrosRoutes.js
import explorarLayout from '@/components/módulo_usuario/ExplorarEncuentros.vue'
import PerfilMascota from '@/components/módulo_adopciones/perfilMascota.vue'
import Chats from '@/components/módulo_usuario/chats.vue'
import Cerca from '@/components/módulo_adopciones/perfilesMascotasCerca.vue'
import Perfil from '@/components/módulo_usuario/perfilUsuario.vue'
import ChatRoom from '@/components/módulo_usuario/salaDeChat.vue'

// routes/encuentrosRoutes.js
// routes/encuentrosRoutes.js
export const encuentrosRoutes = [
  {
    path: '/explorar',
    component: explorarLayout,
    children: [
      { path: '', redirect: 'encuentros' },
      { path: 'encuentros', component: PerfilMascota },
      { 
        path: 'chats', 
        component: Chats, // Este es el componente de lista de chats
      },
      { 
        path: 'chats/:id', // Ruta separada para el chat individual
        name: 'ChatRoom',
        component: ChatRoom,
        props: true
      },
      { path: 'cerca', component: Cerca },
      { path: 'perfil', component: Perfil },
    ]
  }
]
// routes/encuentrosRoutes.js
import explorarLayout from '@/components/ExplorarEncuentros.vue'
import PerfilMascota from '@/components/perfilMascota.vue'
import Chats from '@/components/chats.vue'
import Cerca from '@/components/perfilesCerca.vue'
import Perfil from '@/components/perfilUsuario.vue'

export const encuentrosRoutes = [
  {
    path: '/explorar',
    component: explorarLayout,
    children: [
      { path: '', redirect: '/explorar/encuentros' },
      { path: 'encuentros', component: PerfilMascota },
      { path: 'chats', component: Chats },
      { path: 'cerca', component: Cerca },
      { path: 'perfil', component: Perfil },
    ]
  }
]

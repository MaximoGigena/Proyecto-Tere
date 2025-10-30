import ExplorarEncuentros from '@/components/módulo_usuario/ExplorarEncuentros.vue';
import ChatsLista from '@/components/módulo_usuario/chats.vue';
import ContenidoPerfil from '@/components/módulo_adopciones/solicitudAdopción.vue';

export const chatRoutes = [
  {
    path: '/explorar',
    component: ExplorarEncuentros,
    children: [
      {
        path: 'chats',
        component: ChatsLista,
        name: 'chats-list'
      },
      {
        path: 'perfil/:userId',
        name: 'user-profile-list',
        components: {
          default: ChatsLista,
          overlay: ContenidoPerfil
        },
        props: {
          default: (route) => ({
            from: route.query.from || 'chats-list',
            userId: route.params.userId
          }),
          overlay: (route) => ({
            userId: route.params.userId
          })
        }
      }
    ]
  }
];
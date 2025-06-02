import ExplorarEncuentros from '@/components/módulo_usuario/ExplorarEncuentros.vue';
import ChatsLista from '@/components/módulo_usuario/chats.vue';
import ContenidoPerfil from '@/components/módulo_usuario/contenidoUsuario.vue';

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
        name: 'user-profile-list', // Nombre único para esta versión
        components: {
          default: ChatsLista,
          overlay: ContenidoPerfil
        },
        props: {
          default: () => ({
            from: 'chats-list',
            id: null
          }),
          overlay: true
        }
      }
    ]
  }
];
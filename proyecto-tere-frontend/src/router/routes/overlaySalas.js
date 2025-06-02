import ExplorarEncuentros from '@/components/módulo_usuario/ExplorarEncuentros.vue';
import SalaDeChat from '@/components/módulo_usuario/salaDeChat.vue';
import ContenidoPerfil from '@/components/módulo_usuario/contenidoUsuario.vue';

export const chatRoom = [
  {
    path: '/explorar',
    component: ExplorarEncuentros,
    children: [
      {
        path: 'chats/:id',
        component: SalaDeChat,
        name: 'chat-room',
        props: true
      },
      {
        path: 'perfil/:userId',
        name: 'user-profile-room', // Nombre único para esta versión
        components: {
          default: SalaDeChat,
          overlay: ContenidoPerfil
        },
        props: {
          default: (route) => ({
            from: 'chat-room',
            id: route.params.userId
          }),
          overlay: true
        }
      }
    ]
  }
];
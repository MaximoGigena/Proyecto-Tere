import ExplorarEncuentros from '@/components/ExplorarEncuentros.vue';
import SalaDeChat from '@/components/salaDeChat.vue';
import ChatsLista from '@/components/chats.vue';
import ContenidoPerfil from '@/components/contenidoUsuario.vue';

export const chatRoutes = [
  {
    path: '/explorar',
    component: ExplorarEncuentros,
    children: [
      // Ruta para lista de chats
      {
        path: 'chats',
        component: ChatsLista,
        name: 'chats-list'
      },
      
      // Ruta para salas de chat individuales
      {
        path: 'chats/:id',
        component: SalaDeChat,
        name: 'chat-room',
        props: true
      },
      
      // Ruta para perfiles (overlay)
      {
        path: 'perfil/:userId',
        name: 'user-profile',
        components: {
          default: ChatsLista, // O SalaDeChat según desde dónde vengas
          overlay: ContenidoPerfil
        },
        components: {
          default: SalaDeChat, // O SalaDeChat según desde dónde vengas
          overlay: ContenidoPerfil
        },
        props: {
          default: (route) => ({ 
            from: route.query.from,
            id: route.query.from === 'chat-room' ? route.params.userId : null
          }),
          overlay: true
        }
      }
    ]
  }
];
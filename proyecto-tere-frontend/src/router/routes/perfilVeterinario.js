import PerfilVeterinario from '@/components/módulo_veterinario/perfilVeterinario.vue'
import EditarVeterinario from '@/components/módulo_veterinario/registrarVeterinario.vue'

export const UsuarioVeterinario = [
  {
    path: '/perfil-veterinario',
    name: 'PerfilVeterinario',
    component: PerfilVeterinario,
    meta: { requiresAuth: true }
  },
  // También puedes agregar esta ruta para edición con ID en la URL
  {
    path: '/editar-veterinario/:id',
    name: 'EditarVeterinarioConId',
    component: EditarVeterinario,
    meta: { requiresAuth: true }
  }
]

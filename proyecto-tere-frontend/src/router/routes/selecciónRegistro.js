import SeleccionTipoUsuario from '@/components/módulo_usuario/seleccionarRegistroUsuario.vue'
import RegistroUsuario from '@/components/módulo_usuario/registrarUsuario.vue'
import RegistroVeterinario from '@/components/módulo_veterinario/registrarVeterinario.vue'

export const SeleccionarRegistro = [
  {
    path: '/seleccionarRegistro',
    name: 'SeleccionTipoUsuario',
    component: SeleccionTipoUsuario
  },
 
 {
    path: '/registro/usuario',
    name: 'RegistroUsuario',
    component: RegistroUsuario
  },
  {
    path: '/registro/veterinario',
    name: 'RegistroVeterinario',
    component: RegistroVeterinario
  },
]



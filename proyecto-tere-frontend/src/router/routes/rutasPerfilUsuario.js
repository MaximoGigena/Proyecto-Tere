import ConfiguracionUsuario from '@/components/módulo_usuario/configuracionesUsuario.vue'
import EditarDatosUsuario from '@/components/módulo_usuario/editarDatosUsuario.vue'

export const ConfiguracionesUsuario = [
  {
    path: '/usuarioConfiguracion',
    name: 'Configuración',
    component: ConfiguracionUsuario
  },
  {
    path: '/usuarioEdicion',
    name: 'Editar datos',    
    component: EditarDatosUsuario,
  }
  // ...otras rutas
]

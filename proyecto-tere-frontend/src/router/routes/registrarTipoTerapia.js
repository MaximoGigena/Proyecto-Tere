import RegistrarTipoTerapia from '@/components/módulo_seguimiento/registrarTipos/registrarTipoTerapia.vue'

export const registrarTipoTerapia = [
   {
    path: '/registro/registroTipoTerapia',
    component: RegistrarTipoTerapia,
    name: 'registrarTipoTerapia',
  },
  {
    path: '/registro/registroTipoTerapia/:id',
    name: 'editarTipoTerapia',
    component: RegistrarTipoTerapia, 
    props: true 
  }
]
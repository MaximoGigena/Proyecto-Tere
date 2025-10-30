import RegistrarTipoFarmaco from '@/components/módulo_seguimiento/registrarTipos/registroTipoFarmaco.vue'

export const registrarTipoFarmaco = [
   {
    path: '/registro/registroTipoFarmaco',
    component: RegistrarTipoFarmaco,
    name: 'registrarTipoFarmaco',
  },
  {
    path: '/registro/registroTipoFarmaco/:id',
    name: 'editarTipoFarmaco',
    component: RegistrarTipoFarmaco, 
    props: true 
  }
]
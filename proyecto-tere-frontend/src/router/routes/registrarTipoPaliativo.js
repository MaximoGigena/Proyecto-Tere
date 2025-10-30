import RegistrarTipoPaliativo from '@/components/módulo_seguimiento/registrarTipos/registrarTipoPaliativo.vue'

export const registrarTipoPaliativo = [
   {
    path: '/registro/registroTipoPaliativo',
    component: RegistrarTipoPaliativo,
    name: 'RegistrarTipoPaliativo',
  },
  {
    path: '/registro/registroTipoPaliativo/:id',
    name: 'editarTipoPaliativo',
    component: RegistrarTipoPaliativo, 
    props: true 
  }
]
import RegistrarTipoCirugia from '@/components/módulo_seguimiento/registrarTipos/registroTipoCirugia.vue'

export const registrarTipoCirugia = [
   {
    path: '/registro/registroTipoCirugia',
    component: RegistrarTipoCirugia,
    name: 'registrarTipoCirugia',
  },
  {
    path: '/registro/registroTipoCirugia/:id',
    name: 'editarTipoCirugia',
    component: RegistrarTipoCirugia, 
    props: true 
  }
]
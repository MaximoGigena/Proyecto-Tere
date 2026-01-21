import RegistrarFarmaco from '@/components/módulo_seguimiento/registrarFarmaco.vue'

export const registrarFarmaco = [
   {
    path: '/registro/farmaco',
    component: RegistrarFarmaco,
    name: 'RegistrarFarmaco',
    props: true
  },
  {
    path: '/editar/farmaco/:farmacoId',
    name: 'EditarFarmaco',
    component: RegistrarFarmaco,
    props: true
  }
]
import RegistrarTerapias from '@/components/módulo_seguimiento/registrarTerapia.vue'

export const registrarTerapia = [
   {
    path: '/registro/terapia',
    component: RegistrarTerapias,
    name: 'registrarTerapia',
    props: true
  },
  {
    path: '/editar/terapia/:terapiaId',
    component: RegistrarTerapias,
    name: 'editarTerapia',
    props: true
  }
]
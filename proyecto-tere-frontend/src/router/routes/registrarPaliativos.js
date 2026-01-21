import RegistrarPaliativo from '@/components/módulo_seguimiento/registrarPaliativos.vue'

export const registrarPaliativo = [
   {
    path: '/registro/paliativo',
    component: RegistrarPaliativo,
    name: 'RegistrarPaliativo',
    props: true
  },
  {
    path: '/editar/paliativo/:id',
    component: RegistrarPaliativo,
    name: 'EditarPaliativo',
    props: true
  }
]
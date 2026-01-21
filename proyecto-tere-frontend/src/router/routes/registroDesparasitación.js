import RegistrarDesparasitacion from '@/components/módulo_seguimiento/registrarDesparasitación.vue'

export const registrarDesparasitacion = [
  {
    path: '/registro/desparasitacion',
    name: 'registrarDesparasitacion',
    component: RegistrarDesparasitacion,
    props: true
  },
  {
    path: '/editar/desparasitacion/:desparasitacionId',
    name: 'editarDesparasitacion',
    component: RegistrarDesparasitacion,
    props: true
  }
]
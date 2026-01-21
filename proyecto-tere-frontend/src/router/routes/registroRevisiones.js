import RegistrarRevisión from '@/components/módulo_seguimiento/registrarRevisión.vue'

export const registrarRevisión = [
   {
    path: '/registro/revision',
    component: RegistrarRevisión,
    name: 'registrarRevisión',
    props: true
  },
  {
    path: '/editar/revision/:revisionId',
    component: RegistrarRevisión,
    name: 'editarRevisión',
    props: true
  }
]
import RegistrarDiagnostico from '@/components/módulo_seguimiento/registrarDiagnostico.vue'

export const registrarDiagnostico = [
   {
    path: '/registro/diagnostico/:mascotaId?',
    component: RegistrarDiagnostico,
    name: 'registrarDiagnostico',
    props: true
  },
  {
    path: '/editar/diagnostico/:diagnosticoId',
    component: RegistrarDiagnostico,
    name: 'editarDiagnostico',
    props: true
  }
]
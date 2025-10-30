import RegistrarTipoDiagnostico from '@/components/módulo_seguimiento/registrarTipos/registrarTipoDiagnostico.vue'

export const registrarTipoDiagnostico = [
   {
    path: '/registro/registroTipoDiagnostico',
    component: RegistrarTipoDiagnostico,
    name: 'RegistrarTipoDiagnostico',
  },
  {
   path: '/registro/registroTipoDiagnostico/:id',
   name: 'editarTipoDiagnostico',
   component: RegistrarTipoDiagnostico, 
   props: true 
  }
]
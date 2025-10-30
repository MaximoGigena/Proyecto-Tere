import RegistrarTipoDesparasitacion from '@/components/módulo_seguimiento/registrarTipos/registrarTipoDesparasitación.vue'

export const registrarTipoDesparasitacion = [
   {
    path: '/registro/registroTipoDesparasitacion',
    component: RegistrarTipoDesparasitacion,
    name: 'registrarTipoDesparasitacion',
    },
    {
      path: '/registro/registroTipoDesparasitacion/:id',
      name: 'editarTipoDesparasitacion',
      component: RegistrarTipoDesparasitacion, 
      props: true 
    }
]
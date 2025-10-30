import RegistrarTipoRevision from '@/components/módulo_seguimiento/registrarTipos/registrarTipoRevision.vue'

export const registrarTipoRevision = [
   {
    path: '/registro/registroTipoRevision',
    name: 'registrarTipoRevision',
    component: RegistrarTipoRevision,
  },
  {
    path: '/registro/registroTipoRevision/:id',
    name: 'editarTipoRevision',
    component: RegistrarTipoRevision,
    props: true 
  }
]
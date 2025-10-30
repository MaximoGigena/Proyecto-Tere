import RegistrarTipoVacuna from '@/components/módulo_seguimiento/registrarTipos/registrarTipoVacuna.vue'

export const registrarTipoVacuna = [
  {
    path: '/registro/registroTipoVacuna',
    name: 'registrarTipoVacuna',
    component: RegistrarTipoVacuna,
  },
  {
    path: '/registro/registroTipoVacuna/:id',
    name: 'editarTipoVacuna',
    component: RegistrarTipoVacuna, 
    props: true 
  }
]

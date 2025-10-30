import RegistrarTipoAlergia from '@/components/módulo_seguimiento/registrarTipos/registrarTipoAlergia.vue'

export const registrarTipoAlergia = [
  {
    path: '/registro/registroTipoAlergia/', // ← Ruta específica para nuevo
    name: 'registrarTipoAlergia',
    component: RegistrarTipoAlergia,
  },
  {
    path: '/registro/registroTipoAlergia/:id', // ← Ruta específica para editar (sin ?)
    name: 'editarTipoAlergia',
    component: RegistrarTipoAlergia, 
    props: true 
  }
]
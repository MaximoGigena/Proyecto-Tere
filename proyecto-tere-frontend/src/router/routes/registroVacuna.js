import RegistrarVacuna from '@/components/módulo_seguimiento/registrarVacuna.vue'

export const registrarVacuna = [
  {
    path: '/registro/vacuna/:mascotaId?',
    name: 'registrarVacuna',
    component: RegistrarVacuna,
    props: true
  },
  {
    path: '/editar/vacuna/:vacunaId',
    name: 'editarVacuna',
    component: RegistrarVacuna,
    props: true
  }
]
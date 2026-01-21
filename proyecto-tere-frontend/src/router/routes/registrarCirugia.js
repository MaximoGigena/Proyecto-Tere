import RegistrarCirugia from '@/components/módulo_seguimiento/registrarCirugía.vue'
import path from 'path'

export const registrarCirugia = [
   {
    path: '/registro/cirugia',
    component: RegistrarCirugia,
    name: 'registrarCirugia',
    props: true
  },
  {
    path: '/editar/cirugia/:cirugiaId',
    component: RegistrarCirugia,
    name: 'editarCirugia',
    props: true
  }
]
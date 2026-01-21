import RegistrarAlergia from '@/components/módulo_seguimiento/registrarAlergia.vue'

export const registrarAlergia = [
   {
    path: '/registro/alergia',
    component: RegistrarAlergia,
    name: 'RegistrarAlergia',
    props: true
   },
   {
    path: '/editar/alergia/:alergiaId',
    name: 'editarAlergia',
    component: RegistrarAlergia,
    props: true
   }


]
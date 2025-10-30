import editarMascota from '@/components/módulo_mascotas/registrarMascota.vue';

export const editarMascotaRoutes = [
    {
    path: '/explorar/perfil/mascotas/editar/:id',
    name: 'editar-mascota',
    component: editarMascota,
    }
];
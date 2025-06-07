// galeriaMascotas.js

import MascotaView from '@/components/módulo_mascotas/contenidoMascota.vue'
import GaleriaMascota from '@/components/módulo_mascotas/galeriaMascota.vue'

export const rutasGaleria = [
  {
    path: '/mascota/:id',
    name: 'mascota',
    component: MascotaView,
    props: true
  },
  {
    path: '/mascota/:id/galeria',
    name: 'galeria-mascota',
    component: GaleriaMascota,
    props: true
  },
    // En tu archivo de rutas (router.js o similar)
    {
    path: '/mascota/:id/galeria/:imageIndex',
    name: 'galeria-mascota-imagen',
    component: GaleriaMascota,
    props: (route) => ({
        images: route.query.images ? JSON.parse(route.query.images) : [],
        initialIndex: parseInt(route.params.imageIndex) || 0
    })
    }
]

import contenidoMascota from '@/components/contenidoMascota.vue'
import PerfilesCerca from '@/components/perfilesCerca.vue'
import ContenedorPrincipal from '@/components/ExplorarEncuentros.vue' 

export const mascotasCerca = [
  {
    path: '/explorar/cerca',
    component: ContenedorPrincipal, // Solo este componente manejará el overlay
    children: [
      {
        path: '', // Ruta base
        name: 'perfiles-cerca',
        component: PerfilesCerca
      },
      {
        path: ':id', // Ruta con parámetro
        name: 'perfil-mascota',
        components: {
          default: PerfilesCerca, // Se mantiene visible debajo
          overlay: contenidoMascota  // Se muestra encima
          
        },
        props: {
          overlay: true // Pasa el :id como prop al overlay
        }
      }
    ]
  }
]
import contenidoMascota from '@/components/contenidoMascota.vue'
import PerfilesCerca from '@/components/perfilesCerca.vue'
import ContenedorPrincipal from '@/components/ExplorarEncuentros.vue' 
import historialMedico from '@/components/historialMedico.vue'


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
        name: 'mascota-cerca', // Nombre de la ruta
        components: {
          default: PerfilesCerca, // Se mantiene visible debajo
          overlay: contenidoMascota  // Se muestra encima
          
        },
        props: {
          overlay: true // Pasa el :id como prop al overlay
        },
        children: [
                  {
                    path: 'historial',
                    component: historialMedico,
                    props: true
                  },
                  // Podés agregar más subvistas si querés:
                  // { path: 'galeria', component: galeriaFotos, props: true }
                ]
      }
    ]
  }
]
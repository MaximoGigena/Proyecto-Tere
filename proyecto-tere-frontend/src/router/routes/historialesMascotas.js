// routes/historialesMascotas.js
import historialLayout from '@/components/módulo_mascotas/carpetaHistoriales.vue'
import propietarios from '@/components/módulo_mascotas/historialDueños.vue'
import Episodios from '@/components/módulo_mascotas/historialEpisodios.vue'
import vacunas from '@/components/módulo_mascotas/historialPreventivos.vue'
import HistorialMedicoLayout from '@/components/módulo_mascotas/historialClinico.vue'
import Procedimientos from '@/components/módulo_seguimiento/historiaClinica/cirugías.vue' 
import Fármacos from '@/components/módulo_seguimiento/historiaClinica/Medicamentos.vue'
import Terapias from '@/components/módulo_seguimiento/historiaClinica/Terapias.vue'
import Diagnosticos from '@/components/módulo_seguimiento/historiaClinica/Diagnosticos.vue'
import Paliativos from '@/components/módulo_seguimiento/historiaClinica/Paliativos.vue'
import Vacunas from '@/components/módulo_seguimiento/historiaPreventivos/vacunas.vue'
import Desparasitación from '@/components/módulo_seguimiento/historiaPreventivos/desparasitaciones.vue'
import Revisión from '@/components/módulo_seguimiento/historiaPreventivos/revisiones.vue'
import Alergias from '@/components/módulo_seguimiento/historiaPreventivos/alergias.vue'

// Rutas comunes con nombres base
const baseRoutes = [
  { 
    path: 'tutores', 
    name: 'tutores', 
    component: propietarios,
    meta: { overlay: false },
    props: (route) => ({
      id: route.params.id || route.query.id,
      isOverlay: route.meta?.overlay || false,
      ...route.query
    })
  },
  { 
    path: 'episodios', 
    name: 'episodios', 
    component: Episodios,
    meta: { overlay: false },
    props: (route) => ({
      id: route.params.id || route.query.id,
      isOverlay: route.meta?.overlay || false,
      ...route.query
    })
  },
  { 
    path: 'historialPreventivo',  
    component: vacunas,
    children: [
      { 
        path: '', // Ruta base para historialPreventivo
        name: 'historialPreventivo',
        redirect: { name: 'vacunas' } 
      },
      { 
        path: 'vacunas',
        name: 'vacunas',
        component: Vacunas,
        props: (route) => ({
          id: route.params.id || route.query.id,
          isOverlay: route.meta?.overlay || false,
          ...route.query
        })
      },
      { 
        path: 'desparasitaciones',
        name: 'desparasitaciones',
        component: Desparasitación,
        props: (route) => ({
          id: route.params.id || route.query.id,
          isOverlay: route.meta?.overlay || false,
          ...route.query
        })
      },
      { 
        path: 'revisiones', // Cambiado de "revisiónes" a "revisiones" (sin tilde)
        name: 'revisiones',
        component: Revisión,
        props: (route) => ({
          id: route.params.id || route.query.id,
          isOverlay: route.meta?.overlay || false,
          ...route.query
        })
      },
      { 
        path: 'alergias',
        name: 'alergias',
        component: Alergias,
        props: (route) => ({
          id: route.params.id || route.query.id,
          isOverlay: route.meta?.overlay || false,
          ...route.query
        })
      }
    ]
  },
  { 
    path: 'historialClinico',
    component: HistorialMedicoLayout,
    children: [
      { 
        path: '',
        name: 'historialClinico',
        redirect: { name: 'cirugias' } 
      },
      { 
        path: 'cirugias',
        name: 'cirugias',
        component: Procedimientos,
        props: (route) => ({
          id: route.params.id || route.query.id,
          isOverlay: route.meta?.overlay || false,
          ...route.query
        })
      },
      { 
        path: 'farmacos', // Cambiado de "fármacos" a "farmacos" (sin tilde)
        name: 'farmacos',
        component: Fármacos,
        props: (route) => ({
          id: route.params.id || route.query.id,
          isOverlay: route.meta?.overlay || false,
          ...route.query
        })
      },
      { 
        path: 'terapias',
        name: 'terapias',
        component: Terapias,
        props: (route) => ({
          id: route.params.id || route.query.id,
          isOverlay: route.meta?.overlay || false,
          ...route.query
        })
      },
      { 
        path: 'diagnosticos',
        name: 'diagnosticos',
        component: Diagnosticos,
        props: (route) => ({
          id: route.params.id || route.query.id,
          isOverlay: route.meta?.overlay || false,
          ...route.query
        })
      },
      { 
        path: 'paliativos',
        name: 'paliativos',
        component: Paliativos,
        props: (route) => ({
          id: route.params.id || route.query.id,
          isOverlay: route.meta?.overlay || false,
          ...route.query
        })
      }
    ]
  }
];

// Rutas normales
export const historialesMascotas = [
  {
    path: '/revisar',
    component: historialLayout,
    children: [
      { path: '', redirect: { name: 'tutores' } },
      ...baseRoutes
    ]
  }
];

// Rutas overlay para veterinarios
export const overlayVeterinario = [
  {
    path: '/veterinarios',
    component: () => import('@/components/módulo_veterinario/veterinarioLayout.vue'),
    children: [
      {
        path: '',
        components: {
          right: {
            template: '<div class="h-full flex items-center justify-center text-red-400">Selecciona una mascota para ver el historial</div>',
          }
        }
      },
      {
        path: 'mascota/:id',
        components: {
          right: historialLayout,
        },
        props: { right: true },
        children: [
          { path: '', redirect: { name: 'veterinario-tutores' } },
          // Propietarios
          { 
            path: 'tutores',
            name: 'veterinario-tutores',
            component: propietarios,
            meta: { overlay: true },
            props: (route) => ({
              id: route.params.id,
              isOverlay: true,
              ...route.query
            })
          },
          { 
            path: 'episodios',
            name: 'veterinario-episodios',
            component: Episodios,
            meta: { overlay: true },
            props: (route) => ({
              id: route.params.id,
              isOverlay: true,
              ...route.query
            })
          },
          // Vacunas
          { 
            path: 'historialPreventivo',
            component: vacunas,
            children: [
              { 
                path: '',
                name: 'veterinario-historialPreventivo',
                redirect: { name: 'veterinario-vacunas' }
              },
              { 
                path: 'vacunas',
                name: 'veterinario-vacunas',
                component: Vacunas,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'desparasitaciones',
                name: 'veterinario-desparasitaciones',
                component: Desparasitación,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'revisiones',
                name: 'veterinario-revisiones',
                component: Revisión,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'alergias',
                name: 'veterinario-alergias',
                component: Alergias,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              }
            ]
          },
          // Historial Médico
          { 
            path: 'historialClinico',
            component: HistorialMedicoLayout,
            children: [
              { 
                path: '',
                name: 'veterinario-historialClinico',
                redirect: { name: 'veterinario-cirugias' }
              },
              { 
                path: 'cirugias',
                name: 'veterinario-cirugias',
                component: Procedimientos,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'farmacos',
                name: 'veterinario-farmacos',
                component: Fármacos,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'terapias',
                name: 'veterinario-terapias',
                component: Terapias,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'diagnosticos',
                name: 'veterinario-diagnosticos',
                component: Diagnosticos,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'paliativos',
                name: 'veterinario-paliativos',
                component: Paliativos,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
            ]
          }
        ]
      }
    ]
  }
];